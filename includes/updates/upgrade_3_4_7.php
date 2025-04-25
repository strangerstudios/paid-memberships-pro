<?php
/**
 * Upgrade to version 3.4.7
 *
 * We are encrypting the site url in the database
 *
 * @since 3.4.7
 */
function pmpro_upgrade_3_4_7() {
	$current_url_hash = get_option( 'pmpro_last_known_url' );

	// If we don't have a current URL hash yet, set it to the site URL hash.
	if ( ! empty( $current_url_hash ) ) {
		// Check if current value was sha256 encoded; if not, encode it.
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $current_url_hash ) ) {
			// Ensure we work with https urls, to standardize comparison.
			$current_url_hash = str_replace( 'http://', 'https://', $current_url_hash );
			$current_url_hash = hash( 'sha256', $current_url_hash );
			update_option( 'pmpro_last_known_url', $current_url_hash );
		}
	}
}
