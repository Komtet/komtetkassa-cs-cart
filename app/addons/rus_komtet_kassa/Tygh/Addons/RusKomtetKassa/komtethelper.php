<?php

use Komtet\KassaSdk\Check;
use Komtet\KassaSdk\Position;
use Komtet\KassaSdk\Vat;
use Komtet\KassaSdk\Client;
use Komtet\KassaSdk\QueueManager;
use Komtet\KassaSdk\Payment;


class komtetHelper
{
	public static function fiscalize($order, $params, $is_refund)
	{

		include_once __DIR__.'/kassa/Check.php';
		include_once __DIR__.'/kassa/Position.php';
		include_once __DIR__.'/kassa/Vat.php';
		include_once __DIR__.'/kassa/Client.php';
		include_once __DIR__.'/kassa/QueueManager.php';
		include_once __DIR__.'/kassa/Payment.php';
		include_once __DIR__.'/kassa/Exception/SdkException.php';

		$data = array (
		    'order_id' => $order['order_id'],
		    'status' => 'pending'
		);
		db_query('INSERT INTO ?:rus_komtet_kassa_order_fiscalization_status ?e', $data);

		$positions = $order['positions'];

		$method = $is_refund ? Check::INTENT_SELL_RETURN : Check::INTENT_SELL;

		$check = new Check($order['order_id'], $order['email'], $method, intval($params['sno']));
		$check->setShouldPrint($params['is_print_check']);

		$vat = new Vat($params['vat']);

		$total = 0.0;

		foreach( $positions as $position )
		{

			$total += round($position['amount']*$position['price'], 2);

			$positionObj = new Position($position['product'],
										round($position['price'], 2),
										floatval($position['amount']),
										round($position['amount']*$position['price'], 2),
										floatval($position['discount']),
										$vat);

			$check->addPosition($positionObj);
		}

		if (round($order['shipping_cost'], 2) > 0.0) {

			$total += round($order['shipping_cost'], 2);

			$shippingPosition = new Position("Доставка",
											 round($order['shipping_cost'], 2),
											 1,
											 round($order['shipping_cost'], 2),
											 0,
											 $vat);
			$check->addPosition($shippingPosition);
		}

		$payment = Payment::createCard(round($total, 2));
		$check->addPayment($payment);

		$client = new Client($params['shop_id'], $params['secret']);
		$queueManager = new QueueManager($client);

		$queueManager->registerQueue('print_que', $params['queue_id']);

		try {
		    $queueManager->putCheck($check, 'print_que');
		} catch (SdkException $e) {
			$data = array (
			    'status' => 'error',
			    'description' => $e->getMessage()
			);
		    db_query('UPDATE ?:rus_komtet_kassa_order_fiscalization_status SET ?u WHERE order_id = ?i', $data, $order['order_id']);
		}
	}
}
