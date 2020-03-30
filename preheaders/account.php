<?php

global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt;

if($current_user->ID)
    $current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

if (isset($_REQUEST['msg'])) {
    if ($_REQUEST['msg'] == 1) {
        $pmpro_msg = __('Your membership status has been updated - Thank you!', 'paid-memberships-pro' );
    } else {
        $pmpro_msg = __('Sorry, your request could not be completed - please try again in a few moments.', 'paid-memberships-pro' );
        $pmpro_msgt = "pmpro_error";
    }
} else {
    $pmpro_msg = false;
}

/**
 * Check if the current logged in user has a membership level.
 * If not, and the site is using the pmpro_account_preheader_redirect
 * filter, redirect to that page.
 */
if ( ! empty( $current_user->ID && empty( $current_user->membership_level->ID ) ) ) {
	$redirect = apply_filters( 'pmpro_account_preheader_redirect', false );
	if ( $redirect ) {
		wp_redirect( $redirect );
		exit;
	}
}

global $pmpro_levels;
$pmpro_levels = pmpro_getAllLevels();
