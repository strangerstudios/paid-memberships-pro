<?php

/**
 * Check if there is a current logged in user with membership level.
 * If not, redirect to levels page.
 *
 * @since 2.3
 */
if ( ! is_user_logged_in( ) ) {
	$redirect = apply_filters( 'pmpro_member_profile_edit_preheader_redirect', pmpro_url( 'levels' ) );
	if ( $redirect ) {
		wp_redirect( $redirect );
		exit;
	}
}
