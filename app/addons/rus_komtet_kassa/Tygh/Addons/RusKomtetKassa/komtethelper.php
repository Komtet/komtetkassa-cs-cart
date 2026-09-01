<?php

use Komtet\KassaSdk\v1\Check;
use Komtet\KassaSdk\v1\Position;
use Komtet\KassaSdk\v1\Vat;
use Komtet\KassaSdk\v1\CalculationMethod;
use Komtet\KassaSdk\v1\CalculationSubject;
use Komtet\KassaSdk\v1\Client;
use Komtet\KassaSdk\v1\QueueManager;
use Komtet\KassaSdk\v1\Payment;
use Komtet\KassaSdk\Exception\SdkException;
use Komtet\KassaSdk\Exception\ClientException;
use Komtet\KassaSdk\Exception\ApiValidationException;


class komtetHelper
{

    public static function getPaymentProps($orderStatusTo, $orderStatusFrom, $statusesPrepaid, $statusesPaid)
    {
        /**
         * Получение опций оплаты
         * @param string $orderStatusTo новый статус заказа
         * @param string $orderStatusFrom предыдущий статус заказа
         * @param array $statusesPrepaid статусы предоплаты из настроек
         * @param array $statusesPaid статусы оплаты из настроек
         */

        include_once __DIR__.'/kassa/src/v1/CalculationMethod.php';
        include_once __DIR__.'/kassa/src/v1/CalculationSubject.php';
        include_once __DIR__.'/kassa/src/v1/Vat.php';

        // Плагин настроен на 1 чек
        if (empty($statusesPrepaid) && array_key_exists($orderStatusTo, $statusesPaid)) {
            return array(
                'calculation_method' => CalculationMethod::FULL_PAYMENT,
                'calculation_subject' => CalculationSubject::PRODUCT,
                'is_full_payment' => false
            );
        }
        // Плагин настроен на 2 чека
        else if (!empty($statusesPrepaid)) {
            // Пробивается предоплата
            if (array_key_exists($orderStatusTo, $statusesPrepaid)) {
                return array(
                    'calculation_method' => CalculationMethod::PRE_PAYMENT_FULL,
                    'calculation_subject' => CalculationSubject::PAYMENT,
                    'is_full_payment' => false
                );
            }
            // Пробивается полная оплата
            else if (array_key_exists($orderStatusTo, $statusesPaid)) {
                return array(
                    'calculation_method' => CalculationMethod::FULL_PAYMENT,
                    'calculation_subject' => CalculationSubject::PRODUCT,
                    'is_full_payment' => true
                );
            }
        }

        return array(
            'calculation_method' => null,
            'is_full_payment' => null
        );
    }

    private static function getVatForCalculationMethod($vat, $calculationMethod) {
        include_once __DIR__.'/kassa/src/v1/CalculationMethod.php';
        include_once __DIR__.'/kassa/src/v1/Vat.php';

        if ($calculationMethod === CalculationMethod::PRE_PAYMENT_FULL) {
            switch ($vat) {
                case Vat::RATE_0:
                    return Vat::RATE_0;
                case Vat::RATE_5:
                    return Vat::RATE_105;
                case Vat::RATE_7:
                    return Vat::RATE_107;
                case Vat::RATE_10:
                    return Vat::RATE_110;
                case Vat::RATE_20:
                    return Vat::RATE_120;
                case Vat::RATE_22:
                    return Vat::RATE_122;
            }
        }
        return $vat;
    }

    public static function fiscalize($order, $params)
    {

        include_once __DIR__.'/kassa/src/v1/Check.php';
        include_once __DIR__.'/kassa/src/v1/Position.php';
        include_once __DIR__.'/kassa/src/v1/Vat.php';
        include_once __DIR__.'/kassa/src/v1/Client.php';
        include_once __DIR__.'/kassa/src/v1/CalculationSubject.php';
        include_once __DIR__.'/kassa/src/v1/QueueManager.php';
        include_once __DIR__.'/kassa/src/v1/Payment.php';
        include_once __DIR__.'/kassa/src/v1/Exception/SdkException.php';
        include_once __DIR__.'/kassa/src/v1/Exception/ClientException.php';
        include_once __DIR__.'/kassa/src/v1/Exception/ApiValidationException.php';

        $data = array (
            'order_id' => $order['order_id'],
            'status' => 'pending'
        );
        db_query('INSERT INTO ?:rus_komtet_kassa_order_fiscalization_status ?e', $data);

        $positions = $order['positions'];

        if ($order['email']) {
            $user_contact = $order['email'];
        } else {
            $user_contact = mb_eregi_replace("[^0-9+]", '', $order['phone']);
        }

        $intent = $params['is_order_will_be_returned'] ? Check::INTENT_SELL_RETURN : Check::INTENT_SELL;

        $check = new Check($order['order_id'], $user_contact, $intent, intval($params['sno']));
        $check->setShouldPrint($params['is_print_check']);
        $check->setInternet($params['is_internet']);

        $vat = self::getVatForCalculationMethod($params['vat'], $params['calculation_method']);
        $vat = new Vat($vat);

        $total = 0.0;

        foreach( $positions as $position )
        {
            $positionTotal = round($position['amount']*$position['price'], 2);
            $total += $positionTotal;

            $positionObj = new Position($position['product'],
                                        round($position['base_price'], 2), // price without discount of position
                                        floatval($position['amount']),
                                        $positionTotal,
                                        $vat);

            $positionObj->setCalculationMethod($params['calculation_method']);
            $positionObj->setCalculationSubject($params['calculation_subject']);

            $check->addPosition($positionObj);
        }

        $orderDiscount = $total - ($order['total'] - $order['shipping_cost']);
        $check->applyDiscount($orderDiscount);
        $total -= $orderDiscount;

        if (round($order['shipping_cost'], 2) > 0.0) {

            $total += round($order['shipping_cost'], 2);

            $shippingPosition = new Position("Доставка",
                                             round($order['shipping_cost'], 2),
                                             1,
                                             round($order['shipping_cost'], 2),
                                             $vat);

            $shippingPosition->setCalculationMethod($params['calculation_method']);
            $shippingPosition->setCalculationSubject(CalculationSubject::SERVICE);

            $check->addPosition($shippingPosition);
        }

        $payment = new Payment(Payment::TYPE_CARD, round($total, 2));
        $check->addPayment($payment);
        $client = new Client($params['shop_id'], $params['secret']);
        $queueManager = new QueueManager($client);
        $queueManager->registerQueue('print_que', $params['queue_id']);

        try {
            $queueManager->putCheck($check, 'print_que');
        } catch (ClientException $e) {
            $data = array (
                'status' => 'error',
                'description' => $e->getMessage()
            );
            fn_set_notification('W', fn_get_lang_var('warning'), '<pre>Komtet Kassa: '.print_r($data, true).'</pre>', true);
            db_query('UPDATE ?:rus_komtet_kassa_order_fiscalization_status SET ?u WHERE order_id = ?i', $data, $order['order_id']);
        }
    }
}
