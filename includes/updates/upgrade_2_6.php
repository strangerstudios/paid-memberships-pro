<?php

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
	$timestamp = wp_next_scheduled( 'pmpro_cron_expire_memberships' );

	wp_unschedule_event( $timestamp, 'pmpro_cron_expire_memberships' );

	wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'pmpro_cron_expire_memberships' );

}

