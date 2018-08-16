<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

/** @var array $schema */

// $schema['atol_online'] = function () {
//     $map = fn_rus_online_cash_register_get_payments_external_ids();
//     $payment_ids = array_keys($map);

//     if ($payment_ids) {
//         $exists = db_get_row(
//             'SELECT payment_id FROM ?:payments WHERE payment_id IN (?n) AND status = ?s LIMIT 1',
//             $payment_ids, 'A'
//         );

//         return !empty($exists);
//     }

//     return false;
// };

// return $schema;