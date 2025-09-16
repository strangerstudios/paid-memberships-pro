<?php
/**
 * WP Engine Compatibility
 *
 * In addition to this code to fix the PMPro password reset form, note that
 * WP Engine also has aggressive caching that your login page
 * should be excluded from, especially if you experience issues
 * with your login page.
 * https://wpengine.com/support/cache/#Cache_Exclusions
 *
 * @since 2.4.1
 */

/**
 * Add wpe-login parameter when sending lost password email.
 *
 * @param string $url of lost password page.
 */
function pmpro_wpe_fix_lostpass( $url ) {
	if ( strpos( $url, 'wpe-login=true' ) === false ) {
		$url = add_query_arg( 'wpe-login', true, $url );
	}
	return $url;
}
add_filter( 'lostpassword_url', 'pmpro_wpe_fix_lostpass' );

/**
 * Add wpe-login parameter when resetting password.
 *
 * @param string $url of reset password page.
 * @param string $path to lost password page from site domain.
 */
function pmpro_wpe_fix_resetpass( $url, $path ) {
	if ( strpos( $path, 'action=resetpass' ) !== false && strpos( $path, 'wpe-login=true' ) === false ) {
		$url = add_query_arg( 'wpe-login', 'true', $url );
	}
	return $url;
}
add_filter( 'site_url', 'pmpro_wpe_fix_resetpass', 10, 2 );

/**
 * Assume sites on WPEngine are using NGINX.
 *
 * @since 3.5
 */
add_filter( 'pmpro_is_nginx', '__return_true' );

/**
 * Increase the batch size for Action Scheduler on WP Engine.
 */
function pmpro_wpe_increase_batch_size( $batch_size ) {
	return $batch_size * 1.5;
}
add_filter( 'pmpro_action_scheduler_batch_size', 'pmpro_wpe_increase_batch_size' );
