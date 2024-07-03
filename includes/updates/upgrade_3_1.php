<?php
/**
 * Upgrade to version 3.1
 *
 * We are eliminating the SSL Seal Code setting.
 * We are also changing the default text and adding a setting for protected content messages.
 *
 * @since TBD
 */
function pmpro_upgrade_3_1() {
    delete_option( 'pmpro_sslseal' );
    delete_option( 'pmpro_accepted_credit_cards' );

	// Check if we have a setting for pmpro_nonmembertext and compare it to the default.
    $pmpro_nonmembertext = get_option( 'pmpro_nonmembertext' );
    if ( $pmpro_nonmembertext !== false ) {
		// We have text set, let's compare it to the old default.
		$old_default_nonmembertext = __( 'This content is for !!levels!! members only.<br /><a href="!!levels_page_url!!">Join Now</a>', 'paid-memberships-pro' );
		if ( $pmpro_nonmembertext == $old_default_nonmembertext ) {
			// This is the old default. Delete it.
			delete_option( 'pmpro_nonmembertext' );
		}
    }

	// Delete the pmpro_stripe_update_billing_flow option. The update billing flow now matches the payment flow.
	delete_option( 'pmpro_stripe_update_billing_flow' );

	// Update the version number
	update_option( 'pmpro_db_version', '3.1' );
}
