<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade routine against PMPro custom tables, which have no WordPress API or object cache layer.

function pmpro_upgrade_1_4_8()
{
	/*
		Adding a billing_country field to the orders table.		
	*/

	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';

	//billing_country
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_orders . "` ADD  `billing_country` VARCHAR( 128 ) NOT NULL AFTER  `billing_zip`
	";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- One-time upgrade query; only the $wpdb table name is interpolated.

	update_option("pmpro_db_version", "1.48");
	return 1.48;
}
