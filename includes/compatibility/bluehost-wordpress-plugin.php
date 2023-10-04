<?php
/**
 * Compatibility for the Bluehost WordPress plugin.
 */

/**
 * When a user is logged in, the BlueHost plugin will check if their passowrd is
 * insecure. If it is, they will be redirected to an "insecure password" screen.
 * This can interrupt our checkout flow.
 *
 * This function will disable the BlueHost plugin's password check when a user is
 * checking out.
 *
 * @since 2.12.3
 */
function pmpro_bluehost_disable_password_check() {
	remove_action( 'wp_login', 'Newfold\WP\Module\Secure_Passwords\wp_login', 10, 2 );
}
add_action( 'pmpro_checkout_preheader', 'pmpro_bluehost_disable_password_check' );
