<?php
/**
 * WordPress.com Compatibility.
 * Supports version 2.8.4
 *
 * @since 2.8.4
 */

 /**
  * Support Jetpack SSO login on WordPress.com itself.
  *
  * @since 2.8.4
  */
function pmpro_jetpack_sso_handle_login() {
	global $pmpro_pages, $action;

	if ( ! is_page() ) {
		return;
	}


	if ( empty( $pmpro_pages['login'] ) || ! is_page( $pmpro_pages['login'] ) ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';

	do_action( 'login_init' );
}

add_action( 'wp', 'pmpro_jetpack_sso_handle_login', 20 );
