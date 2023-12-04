<?php
global $current_user, $pmpro_invoice;

// Redirect non-user to the login page; pass the Confirmation page as the redirect_to query arg.
if ( ! is_user_logged_in() ) {
	// Get level ID from URL parameter.
	if ( ! empty( $_REQUEST['pmpro_level'] ) ) {
		$confirmation_url = add_query_arg( 'pmpro_level', sanitize_text_field( $_REQUEST['pmpro_level'] ), pmpro_url( 'confirmation' ) );
	} else {
		$confirmation_url = pmpro_url( 'confirmation' );
	}
	wp_redirect( add_query_arg( 'redirect_to', urlencode( $confirmation_url ), pmpro_login_url() ) );
	exit;
}

// If there was a level passed, grab it.
$confirmation_level = ! empty( $_REQUEST['pmpro_level'] ) ? intval( $_REQUEST['pmpro_level'] ) : null;
$confirmation_level = empty( $confirmation_level ) && ! empty( $_REQUEST['level'] ) ? intval( $_REQUEST['level'] ) : $confirmation_level; // Backwards compatibility.

// Get the corresponding invoice.
$pmpro_invoice = new MemberOrder();
if ( ! empty( $confirmation_level ) ) {
	$pmpro_invoice->getLastMemberOrder( $current_user->ID, apply_filters( 'pmpro_confirmation_order_status', array( 'success', 'pending', 'token' ) ), $confirmation_level );
} else {
	// If there wasn't a confirmation level passed, get the last invoice for the current user and use that level.
	$pmpro_invoice->getLastMemberOrder( $current_user->ID, apply_filters( 'pmpro_confirmation_order_status', array( 'success', 'pending', 'token' ) ) );
	$confirmation_level = $pmpro_invoice->membership_id;
}

// If no invoice was found or we still don't have a level, redirect to the account page.
if ( empty( $pmpro_invoice ) || empty( $confirmation_level ) ) {
	$redirect_url = pmpro_url( 'account' );
	wp_redirect( $redirect_url );
	exit;
}

// Get the full level object.
$user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $confirmation_level );
$current_user->membership_level = $user_level; // Backwards compatibility.

// If the user doesn't have the level they are confirming (including pending checkouts), redirect them to the account page.
if ( ! in_array( $pmpro_invoice->status, array( 'pending', 'token' ) ) && empty( $user_level ) ) {
	$redirect_url = pmpro_url( 'account' );
	wp_redirect( $redirect_url );
	exit;
}

// If the payment hasn't completed, enqueue JS to check for completion.
if ( in_array( $pmpro_invoice->status, array( 'pending', 'token' ) ) ) {
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
