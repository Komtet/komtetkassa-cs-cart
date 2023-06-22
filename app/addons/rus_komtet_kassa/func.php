<?php

use Tygh\Registry;
use Tygh\Settings;
require_once('Tygh/Addons/RusKomtetKassa/komtethelper.php');

/**
 * Gets extra settings.
 *
 * @return array
 */
function fn_rus_komtet_kassa_get_settings()
{
    if (!Registry::isExist('rus_komtet_kassa.extra_settings')) {
        $settings = json_decode(Registry::get('addons.rus_komtet_kassa.extra'), true);

        if (!is_array($settings)) {
            $settings = array();
        }

        Registry::set('rus_komtet_kassa.extra_settings', $settings, false);
    }

    return (array) Registry::get('rus_komtet_kassa.extra_settings');
}

/**
 * Updates extra setting.
 *
 * @param string    $setting_name   Extra setting name
 * @param mixed     $value          Value
 */
function fn_rus_komtet_kassa_update_setting($setting_name, $value)
{
    $settings = fn_rus_komtet_kassa_get_settings();
    $settings[$setting_name] = $value;

    Registry::set(sprintf('rus_komtet_kassa.extra_settings.%s', $setting_name), $value);
    Settings::instance()->updateValue('extra', json_encode($settings), 'rus_komtet_kassa', false, false);
}

/**
 * Gets external payments.
 *
 * @return array
 */
function fn_rus_komtet_kassa_get_external_payments()
{
    return fn_get_schema('rus_komtet_kassa', 'payments');
}

/**
 * Gets payments external identifiers.
 *
 * @return array
 */
function fn_rus_komtet_kassa_get_external_payments_ids()
{
    $settings = fn_rus_komtet_kassa_get_external_payments();

    return isset($settings['payments_map']) ? $settings['payments_map'] : array();
}

/**
 * Sets payment external identifier.
 *
 * @param int  $payment_id  Local identifier
 * @param int  $external_id External identifier
 */
function fn_rus_komtet_kassa_set_payment_external_id($payment_id, $external_id)
{
    $external_payments = fn_rus_komtet_kassa_get_external_payments();
    $map = fn_rus_komtet_kassa_get_external_payments_ids();

    if (isset($external_payments[$external_id])) {
        $map[$payment_id] = $external_id;
    } else {
        unset($map[$payment_id]);
    }

    fn_rus_komtet_kassa_register_update_setting('payments_map', $map);
}

/**
 * Gets payment external identifier.
 *
 * @param int $payment_id Payment identifier
 *
 * @return int|null
 */
function fn_rus_komtet_kassa_get_payment_external_id($payment_id)
{
    $map = fn_rus_komtet_kassa_get_external_payments_ids();

    return isset($map[$payment_id]) ? $map[$payment_id] : null;
}

/**
 * Hook handler: after order status changed.
 *
 * @param string $status_to     Order status to
 * @param string $status_from   Order status from
 * @param array  $order_info    Order data
 */
function fn_rus_komtet_kassa_change_order_status($status_to, $status_from, $order_info)
{
    $payment_ids = array_keys(Registry::get('addons.rus_komtet_kassa.payment_systems'));
    if(in_array(intval($order_info['payment_id']), $payment_ids, true)){

        $statuses_paid = array_keys(Registry::get('addons.rus_komtet_kassa.statuses_paid'));
        $statuses_refund = array_keys(Registry::get('addons.rus_komtet_kassa.statuses_refund'));

        $komtet_kassa_fisc_status = db_get_row('SELECT * FROM ?:rus_komtet_kassa_order_fiscalization_status WHERE order_id = ?i ', intval($order_info['order_id']));

        $is_order_was_returned = in_array($status_from, $statuses_refund, true);
        $is_order_was_paid = in_array($status_from, $statuses_paid, true);
        $is_order_will_be_returned = in_array($status_to, $statuses_refund, true);
        $is_order_will_be_paid = in_array($status_to, $statuses_paid, true);

        // если
        // (
        //  (заказ не фискализирован И делается оплата)
        //   ЛИБО
        //  (была ошибка фискализации И делается оплата/возврат)
        // )
        // ЛИБО
        // (
        //  заказ был фискализирован И ((он был оплачен И делается возврат) ЛИБО (он был возвращен И делается оплата))
        // )
        if (
            (
             ((empty($komtet_kassa_fisc_status) && $is_order_will_be_paid)) ||
             ($komtet_kassa_fisc_status['status'] == 'error' &&
              ($is_order_will_be_paid || $is_order_will_be_returned)
             )
            ) ||
            ($komtet_kassa_fisc_status['status'] == 'done' && (($is_order_was_returned && $is_order_will_be_paid) ||
                                                               ($is_order_was_paid && $is_order_will_be_returned)))
        )
        {

            $order = [
                'email' => $order_info['email'],
                'phone' => $order_info['phone'],
                'order_id' => intval($order_info['order_id']),
                'total' => $order_info['total'],
                'positions' => $order_info['products'],
                'shipping_cost' => $order_info['shipping_cost']
            ];

            $params = [
                'sno' => Registry::get('addons.rus_komtet_kassa.default_sno'),
                'is_print_check' => Registry::get('addons.rus_komtet_kassa.is_print_check'),
                'vat' => Registry::get('addons.rus_komtet_kassa.default_vat'),
                'shop_id' => Registry::get('addons.rus_komtet_kassa.shop_id'),
                'secret' => Registry::get('addons.rus_komtet_kassa.shop_secret'),
                'queue_id' => Registry::get('addons.rus_komtet_kassa.queue_id'),
                'statuses_paid' => Registry::get('addons.rus_komtet_kassa.statuses_paid'),
                'statuses_refund' => Registry::get('addons.rus_komtet_kassa.statuses_refund')
            ];

            komtetHelper::fiscalize($order, $params, $is_order_will_be_returned);
        }
    }
}
