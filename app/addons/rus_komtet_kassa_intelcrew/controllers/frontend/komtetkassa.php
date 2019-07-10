<?php

// if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'report') {

    $order_id = isset($_POST['external_id']) ? $_POST['external_id'] : null;
	$data = array (
	    'status' => isset($_POST['state']) ? $_POST['state'] : null,
	    'description' => isset($_POST['error_description']) ? $_POST['error_description'] : null
	);

    if ($order_id) {
    	db_query('UPDATE ?:rus_komtet_kassa_intelcrew_order_fiscalization_status SET ?u WHERE order_id = ?i', $data, $order_id);
    }

	fn_print_r("OK");
	exit();

}
