<?php
/**
 * Upgrade to version 3.5
 *
 * We are deleting all crons, updating Stripe webhook events, and setting up the restricted files directory.
 *
 * @since 3.5
 */
function pmpro_upgrade_3_5() {
	// Clear old crons out.
    $old_crons = array(
    'pmpro_cron_expire_memberships',
    'pmpro_cron_expiration_warnings',
    'pmpro_cron_credit_card_expiring_warnings',
    'pmpro_cron_admin_activity_email',
    'pmpro_cron_recurring_payment_reminders',
    'pmpro_cron_delete_tmp',
    'pmpro_license_check_key',
    );

    $crons   = _get_cron_array();    
    foreach ( $crons as $timestamp => $cron ) {
        foreach ( $cron as $hook => $events ) {
            if ( in_array( $hook, $old_crons, true ) ) {
                // Remove all events for this hook (regardless of args)
                wp_clear_scheduled_hook( $hook );
            }
        }
    }

    // Update Stripe webhook events.
    $stripe = new PMProGateway_Stripe();
    $stripe->update_webhook_events();

    // Set up the restricted files directory.
    pmpro_set_up_restricted_files_directory();
}