<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_pages;

// Redirect to login.
if ( ! is_user_logged_in() ) {
	$redirect = apply_filters( 'pmpro_account_preheader_redirect', pmpro_login_url( get_permalink( $pmpro_pages['account'] ) ) );
	if ( $redirect ) {
		wp_redirect( $redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Login URL can be filtered (login_url, pmpro_login_url, pmpro_account_preheader_redirect) to an offsite/SSO login page.
		exit;
	}
}

/**
 * Check if the current logged in user has a membership level.
 * If not, and the site is using the pmpro_account_preheader_redirect
 * filter, redirect to that page.
 */
if ( ! empty( $current_user->ID ) && empty( $current_user->membership_level->ID ) ) {
	$redirect = apply_filters( 'pmpro_account_preheader_redirect', false );
	if ( $redirect ) {
		wp_redirect( $redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Destination is set only by the pmpro_account_preheader_redirect filter, which may point offsite.
		exit;
	}
}

// Preventing conflicts with old account page templates and custom code that depend on the $pmpro_level global being set.
pmpro_getAllLevels();