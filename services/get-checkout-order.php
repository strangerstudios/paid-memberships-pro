<?php
// TODO Docblock.
function pmpro_dump_order_to_json() {
    $order = pmpro_build_order_for_checkout();

    echo json_encode( $order );
    exit;
}
add_filter( 'pmpro_checkout_after_parameters_set', 'pmpro_dump_order_to_json', 9999 );

require_once( PMPRO_DIR . '/preheaders/checkout.php' );
