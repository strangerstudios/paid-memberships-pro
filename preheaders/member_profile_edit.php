<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirect to login.
if ( ! is_user_logged_in() ) {
	$redirect = apply_filters( 'pmpro_member_profile_edit_preheader_redirect', pmpro_login_url() );
	if ( $redirect ) {
		wp_redirect( $redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The pmpro_member_profile_edit_preheader_redirect filter and pmpro_login_url() (pmpro_login_url, login_url filters) may point to an offsite/SSO login page.
		exit;
	}
}