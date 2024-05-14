<?php
/**
 * Upgrade to version 3.1
 *
 * We are eliminating the SSL Seal Code setting.
 */
function pmpro_upgrade_3_1() {
    // Check if we have a setting for pmpro_sslseal and delete it.
    $pmpro_sslseal = get_option( 'pmpro_sslseal' );
    if ( $pmpro_sslseal !== false ) {
        delete_option( 'pmpro_sslseal' );
    }

	// Update the version number
	update_option( 'pmpro_db_version', '3.1' );
}
