<?php
global $current_user, $pmpro_invoice;

// Redirect non-user to the login page; pass the Confirmation page as the redirect_to query arg.
if ( ! is_user_logged_in() ) {
	// Get level ID from URL parameter.
	if ( ! empty( $_REQUEST['level'] ) ) {
		$confirmation_url = add_query_arg( 'level', sanitize_text_field( $_REQUEST['level'] ), pmpro_url( 'confirmation' ) );
	} else {
		$confirmation_url = pmpro_url( 'confirmation' );
	}
	wp_redirect( add_query_arg( 'redirect_to', urlencode( $confirmation_url ), pmpro_login_url() ) );
	exit;
}

// Get the membership level for the current user.
if ( $current_user->ID ) {
	$current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);
}

// Get the most recent invoice for the current user.
$pmpro_invoice = new MemberOrder();
$pmpro_invoice->getLastMemberOrder( $current_user->ID, apply_filters( 'pmpro_confirmation_order_status', array( 'success', 'pending', 'token' ) ) );

if ( ! in_array( $pmpro_invoice->status, array( 'pending', 'token' ) ) && empty( $current_user->membership_level ) ) {
	// The user does not have a membership level (including pending checkouts).
	// Redirect them to the account page.
	$redirect_url = pmpro_url( 'account' );
	wp_redirect( $redirect_url );
	exit;
} elseif ( ! empty( $current_user->membership_level ) && pmpro_isLevelFree( $current_user->membership_level ) ) {
	// User checked out for a free level. We are not going to show the invoice on the confirmation page.
	$pmpro_invoice = null;
} elseif ( in_array( $pmpro_invoice->status, array( 'pending', 'token' ) ) ) {
	// Enqueue PMPro Confirmation script.
	wp_register_script(
		'pmpro_confirmation',
		plugins_url( 'js/pmpro-confirmation.js', PMPRO_BASE_FILE ),
		array( 'jquery' ),
		PMPRO_VERSION
	);
	wp_localize_script(
		'pmpro_confirmation',
		'pmpro',
		array(
			'restUrl' => get_rest_url(),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'code'    => $pmpro_invoice->code,
		)
	);
	wp_enqueue_script( 'pmpro_confirmation' );
}
