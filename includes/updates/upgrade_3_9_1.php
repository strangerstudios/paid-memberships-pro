<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade to version TBD
 *
 * Optional emails get their unsubscribe links from the {{ email_preferences }} variable in the
 * email footer. Sites that saved their own footer don't have the variable yet, so add it.
 * The variable is empty in emails that members can't opt out of.
 *
 * @since TBD
 */
function pmpro_upgrade_3_9_1() {
	$footer = get_option( 'pmpro_email_footer_body' );
	if ( empty( $footer ) || false !== strpos( $footer, 'email_preferences' ) ) {
		return;
	}

	// Keep the links inside the closing body tag if the footer has one.
	$body_close_position = strripos( $footer, '</body>' );
	if ( false !== $body_close_position ) {
		$footer = substr_replace( $footer, '!!email_preferences!!', $body_close_position, 0 );
	} else {
		$footer .= '!!email_preferences!!';
	}

	update_option( 'pmpro_email_footer_body', $footer );
	delete_transient( 'pmproet_footer' );
}
