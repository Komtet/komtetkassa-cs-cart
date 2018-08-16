<?php

function fn_settings_actions_addons_rus_komtet_kassa_shop_id($value, $old_value)
{
    $value = trim($value);

    if (function_exists('ctype_alnum')) {
        $result = ctype_alnum($value);
    } else {
        $result = preg_match('/^[A-Za-z0-9]+$/', $value) ? true : false;
    }

    if (!$result) {
        fn_set_notification('E', __('error'), "КОМТЕТ Касса: ShopId заполнен неверно");
    }
}

function fn_settings_actions_addons_rus_komtet_kassa_shop_secret($value, $old_value)
{
    $value = trim($value);

    if (function_exists('ctype_alnum')) {
        $result = ctype_alnum($value);
    } else {
        $result = preg_match('/^[A-Za-z0-9]+$/', $value) ? true : false;
    }

    if (!$result) {
        fn_set_notification('E', __('error'), "КОМТЕТ Касса: Secret заполнен неверно");
    }
}

function fn_settings_actions_addons_rus_komtet_kassa_queue_id($value, $old_value)
{
    $value = trim($value);

    if (function_exists('ctype_digit')) {
        $result = ctype_digit($value);
    } else {
        $result = preg_match('/^[0-9]+$/', $value) ? true : false;
    }

    if (!$result) {
        fn_set_notification('E', __('error'), "КОМТЕТ Касса: ID очереди заполнен неверно");
    }
}
