<?php

use Komtet\KassaSdk\v1\Check;
use Komtet\KassaSdk\v1\Position;
use Komtet\KassaSdk\v1\Vat;
use Komtet\KassaSdk\v1\Client;
use Komtet\KassaSdk\v1\QueueManager;
use Komtet\KassaSdk\v1\Payment;
use Komtet\KassaSdk\Exception\SdkException;
use Komtet\KassaSdk\Exception\ClientException;
use Komtet\KassaSdk\Exception\ApiValidationException;


class komtetHelper
{
    public static function fiscalize($order, $params, $is_refund)
    {

        include_once __DIR__.'/kassa/src/v1/Check.php';
        include_once __DIR__.'/kassa/src/v1/Position.php';
        include_once __DIR__.'/kassa/src/v1/Vat.php';
        include_once __DIR__.'/kassa/src/v1/Client.php';
        include_once __DIR__.'/kassa/src/v1/QueueManager.php';
        include_once __DIR__.'/kassa/src/v1/Payment.php';
        include_once __DIR__.'/kassa/src/v1/Exception/SdkException.php';
        include_once __DIR__.'/kassa/src/v1/Exception/ClientException.php';

        $data = array (
            'order_id' => $order['order_id'],
            'status' => 'pending'
        );
        db_query('INSERT INTO ?:rus_komtet_kassa_order_fiscalization_status ?e', $data);

        $positions = $order['positions'];

        $method = $is_refund ? Check::INTENT_SELL_RETURN : Check::INTENT_SELL;

        if ($order['email']) {
            $user_contact = $order['email'];
        } else {
            $user_contact = mb_eregi_replace("[^0-9+]", '', $order['phone']);
        }

        $check = new Check($order['order_id'], $user_contact, $method, intval($params['sno']));
        $check->setShouldPrint($params['is_print_check']);

        $vat = new Vat($params['vat']);

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
