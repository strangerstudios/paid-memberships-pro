<?php
/*
	Upgrade to 1.8.8
	* Fixing old Authorize.net orders with empty status.
	* Fixing old $0 Stripe orders.	
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade query against a PMPro custom table; there is no WordPress API or object cache layer for it.

function pmpro_upgrade_1_8_8() {
	global $wpdb;
	
	//Fixing old Authorize.net orders with empty status.
	$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET status = 'success' WHERE gateway = 'authorizenet' AND status = ''";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- One-time upgrade query; only the $wpdb table name is interpolated.
	
	// Since 3.0: Removed the Stripe update, which relied on deprecated code.


	update_option("pmpro_db_version", "1.88");
	return 1.88;
}
