<?php

global $current_user, $pmpro_invoice;

//get invoice from DB
if ( ! empty( $_REQUEST['invoice'] ) ) {
	$invoice_code = sanitize_text_field( $_REQUEST['invoice'] );
} else {
	$invoice_code = NULL;
}

// Load the order by code if we have one.
if ( ! empty( $invoice_code ) ) {
	$pmpro_invoice = new MemberOrder( $invoice_code );

	if ( ! $pmpro_invoice->id ) {
		$pmpro_invoice = null;
	}
}

// Determine if the current visitor can view this order.
if ( ! empty( $pmpro_invoice ) ) {
	$can_view_order = is_user_logged_in() && ( current_user_can( 'pmpro_orders' ) || $current_user->ID == $pmpro_invoice->user_id );
} else {
	$can_view_order = is_user_logged_in();
}

/**
 * Filter whether the current visitor can view the requested order.
 *
 * @since TBD
 *
 * @param bool             $can_view_order Whether the visitor can view the order.
 * @param MemberOrder|null $pmpro_invoice  The order being viewed, or null if no order was requested.
 */
$can_view_order = apply_filters( 'pmpro_allow_viewing_order', $can_view_order, $pmpro_invoice );

if ( ! $can_view_order ) {
	if ( ! is_user_logged_in() ) {
		// Redirect non-user to the login page; pass the Invoice page as the redirect_to query arg.
		if ( ! empty( $invoice_code ) ) {
			$invoice_url = add_query_arg( 'invoice', $invoice_code, pmpro_url( 'invoice' ) );
		} else {
			$invoice_url = pmpro_url( 'invoice' );
		}
		wp_redirect( add_query_arg( 'redirect_to', urlencode( $invoice_url ), wp_login_url() ) );
	} else {
		// Logged-in user without permission; redirect to account page.
		wp_redirect( pmpro_url( 'account' ) );
	}
	exit;
}
