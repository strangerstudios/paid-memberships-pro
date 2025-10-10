<?php

// start with old order if applicable
$order_id = intval( $_REQUEST['id'] );
if ( $order_id > 0 ) {
	$order = new MemberOrder( $order_id );
} else {
	$order = new MemberOrder();
	$order->billing = new stdClass();
}

// update values
if ( isset( $_POST['code'] ) ) {
	$order->code = sanitize_text_field( $_POST['code'] );
}
if ( isset( $_POST['user_id'] ) ) {
	$order->user_id = intval( $_POST['user_id'] );
}
if ( isset( $_POST['membership_id'] ) ) {
	$order->membership_id = intval( $_POST['membership_id'] );
}
if ( isset( $_POST['billing_name'] ) ) {
	$order->billing->name = sanitize_text_field( wp_unslash( $_POST['billing_name'] ) );
}
if ( isset( $_POST['billing_street'] ) ) {
	$order->billing->street = sanitize_text_field( wp_unslash( $_POST['billing_street'] ) );
}
if ( isset( $_POST['billing_street2'] ) ) {
	$order->billing->street2 = sanitize_text_field( wp_unslash( $_POST['billing_street2'] ) );
}
if ( isset( $_POST['billing_city'] ) ) {
	$order->billing->city = sanitize_text_field( wp_unslash( $_POST['billing_city'] ) );
}
if ( isset( $_POST['billing_state'] ) ) {
	$order->billing->state = sanitize_text_field( wp_unslash( $_POST['billing_state'] ) );
}
if ( isset( $_POST['billing_zip'] ) ) {
	$order->billing->zip = sanitize_text_field( $_POST['billing_zip'] );
}
if ( isset( $_POST['billing_country'] ) ) {
	$order->billing->country = sanitize_text_field( wp_unslash( $_POST['billing_country'] ) );
}
if ( isset( $_POST['billing_phone'] ) ) {
	$order->billing->phone = sanitize_text_field( $_POST['billing_phone'] );
}
if ( isset( $_POST['subtotal'] ) ) {
	$order->subtotal = sanitize_text_field( $_POST['subtotal'] );
}
if ( isset( $_POST['tax'] ) ) {
	$order->tax = sanitize_text_field( $_POST['tax'] );
}

if ( isset( $_POST['total'] ) ) {
	$order->total = sanitize_text_field( $_POST['total'] );
}
if ( isset( $_POST['payment_type'] ) ) {
	$order->payment_type = sanitize_text_field( $_POST['payment_type'] );
}
if ( isset( $_POST['cardtype'] ) ) {
	$order->cardtype = sanitize_text_field( $_POST['cardtype'] );
}
if ( isset( $_POST['accountnumber'] ) ) {
	$order->accountnumber = sanitize_text_field( $_POST['accountnumber'] );
}
if ( isset( $_POST['expirationmonth'] ) ) {
	$order->expirationmonth = sanitize_text_field( $_POST['expirationmonth'] );
}
if ( isset( $_POST['expirationyear'] ) ) {
	$order->expirationyear = sanitize_text_field( $_POST['expirationyear'] );
}
if ( isset( $_POST['status'] ) ) {
	$order->status = pmpro_sanitize_with_safelist( $_POST['status'], pmpro_getOrderStatuses() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}
if ( isset( $_POST['gateway'] ) ) {
	$order->gateway = sanitize_text_field( $_POST['gateway'] );
}
if ( isset( $_POST['gateway_environment'] ) ) {
	$order->gateway_environment = sanitize_text_field( $_POST['gateway_environment'] );
}
if ( isset( $_POST['payment_transaction_id'] ) ) {
	$order->payment_transaction_id = sanitize_text_field( $_POST['payment_transaction_id'] );
}
if ( isset( $_POST['subscription_transaction_id'] ) ) {
	$order->subscription_transaction_id = sanitize_text_field( $_POST['subscription_transaction_id'] );
}
if ( isset( $_POST['notes'] ) ) {
	global $allowedposttags;
	$order->notes = wp_kses( wp_unslash( $_POST['notes'] ), $allowedposttags );
}

if ( isset( $_POST['date'] ) && $_POST['date'] !== '' ) {
	$date_raw = sanitize_text_field( wp_unslash( $_POST['date'] ) );
	$local_date_string = str_replace( 'T', ' ', $date_raw ) . ':00';
	$order->timestamp = (int) get_gmt_from_date( $local_date_string, 'U' );
}

// affiliate stuff
$affiliates = apply_filters( 'pmpro_orders_show_affiliate_ids', false );
if ( ! empty( $affiliates ) ) {
	if ( isset( $_POST['affiliate_id'] ) ) {
		$order->affiliate_id = sanitize_text_field( $_POST['affiliate_id'] );
	}
	if ( isset( $_POST['affiliate_subid'] ) ) {
		$order->affiliate_subid = sanitize_text_field( $_POST['affiliate_subid'] );
	}
}

// Set the discount code.
if( isset( $_REQUEST['discount_code_id'] ) ) {
	$order->discount_code_id = intval( $_REQUEST['discount_code_id'] );
}

// Save
if ( false !== $order->saveOrder() ) {
	$pmpro_msg  = __( 'Order saved successfully.', 'paid-memberships-pro' );
	$pmpro_msg .= ' <a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'id' => $order->id ), admin_url( 'admin.php' ) ) ) . '">'; 
	$pmpro_msg .= sprintf( __( 'View Order # %s', 'paid-memberships-pro' ), $order->code );
	$pmpro_msg .= '</a>';
	$pmpro_msgt = 'pmpro_success';

	// Make sure that $_REQUEST['id'] is set for the order edit page to avoid infinitely creating new orders when copying.
	$_REQUEST['id'] = $order->id;
} else {
	$pmpro_msg  = __( 'Error saving order.', 'paid-memberships-pro' );
	$pmpro_msgt = 'pmpro_error';
}
