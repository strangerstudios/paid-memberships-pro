<?php
/**
 * Upgrade to version 3.1
 *
 * We are eliminating the Accepted Card Types setting that was not actually validating ever.
 */
function pmpro_upgrade_3_1() {
    // Check if we have a setting for pmpro_accepted_card_types and delete it.
    $pmpro_accepted_credit_cards = get_option( 'pmpro_accepted_credit_cards' );
    if ( $pmpro_accepted_credit_cards !== false ) {
        delete_option( 'pmpro_accepted_credit_cards' );
    }

	// Update the version number
	update_option( 'pmpro_db_version', '3.1' );
}