<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $current_user, $pmpro_invoice;

//get invoice from DB
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only; selects which invoice to display.
if ( ! empty( $_REQUEST['invoice'] ) ) {
	$invoice_code = sanitize_text_field( wp_unslash( $_REQUEST['invoice'] ) );
} else {
	$invoice_code = NULL;
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Redirect non-user to the login page; pass the Invoice page as the redirect_to query arg.
if ( ! is_user_logged_in() ) {
	if ( ! empty( $invoice_code ) ) {
		$invoice_url = add_query_arg( 'invoice', $invoice_code, pmpro_url( 'invoice' ) );
	} else {
		$invoice_url = pmpro_url( 'invoice' );
	}
	wp_redirect( add_query_arg( 'redirect_to', urlencode( $invoice_url ), wp_login_url() ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- wp_login_url() is filterable (login_url) and may point to an offsite SSO login page.
	exit;
}

if ( ! empty( $invoice_code ) ) {
	$pmpro_invoice = new MemberOrder( $invoice_code );

	if ( ! $pmpro_invoice->id ) {
		// Redirect user to the account page if no invoice found.
		wp_redirect( pmpro_url( 'account' ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- pmpro_url() is filterable (add-ons such as Network Subsite point it at another domain) and is empty when no Account page is set.
		exit;
	}

	// Make sure they have permission to view this.
	if ( ! current_user_can( 'pmpro_orders' ) && $current_user->ID != $pmpro_invoice->user_id ) {
		wp_redirect( pmpro_url( 'account' ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- No permission. pmpro_url() is filterable (add-ons such as Network Subsite point it at another domain) and is empty when no Account page is set.
		exit;
	}
}
