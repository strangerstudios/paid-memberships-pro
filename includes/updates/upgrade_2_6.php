<?php
/**
 * Upgrade to 2.6
 * We changed the pmpro_cron_expire_memberships cron
 * to run hourly instead of daily.
 * To ensure that existing members still expire at least
 * 1 calendar day after their expiration date, we are
 * updating old expiration date timestamps to set
 * the time component to 11:59. This way e.g.
 * someone who checked out at 3pm on Dec 31 won't expire
 * until Jan 1 at midnight.
 * Going forward, we will always set the expiration time to 11:59
 * unless the level is set up to expire hourly.
 */
function pmpro_upgrade_2_6(){

	global $wpdb;

	$wpdb->hide_errors();

	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';
	$wpdb->pmpro_discount_codes_levels = $wpdb->prefix . 'pmpro_discount_codes_levels';

	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` MODIFY `expiration_period` enum('Hour', 'Day','Week','Month','Year') NOT NULL
	";
	$wpdb->query($sqlQuery);

	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_discount_codes_levels . "` MODIFY `expiration_period` enum('Hour', 'Day','Week','Month','Year') NOT NULL
	";
	$wpdb->query($sqlQuery);

	/**
	 * Reschedule Cron Job for Hourly Checks
	 */
	$next = wp_next_scheduled( 'pmpro_cron_expire_memberships' );
	if ( ! empty( $next ) ) {
		wp_unschedule_event( $next, 'pmpro_cron_expire_memberships' );
	}
	pmpro_maybe_schedule_event( current_time( 'timestamp' ), 'hourly', 'pmpro_cron_expire_memberships' );
}