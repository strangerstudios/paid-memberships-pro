<?php
/**
 * WordPress.com Compatibility.
 * Supports version TBD.
 *
 * @since TBD
 */

 /**
  * Support Jetpack SSO login on WordPress.com itself.
  *
  * @since TBD
  */
function pmpro_jetpack_sso_handle_login() {
	global $pmpro_pages, $action;

	if ( ! is_page() ) {
		return;
	}

	$login_page_id = (int) pmpro_getOption( 'login_page_id' );

	if ( ! $login_page_id || ! is_page( $login_page_id ) ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';

	do_action( 'login_init' );
}

add_action( 'wp', 'pmpro_jetpack_sso_handle_login', 20 );
