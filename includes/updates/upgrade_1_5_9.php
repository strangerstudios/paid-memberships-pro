<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade routine on PMPro custom tables; no WordPress API exists and caching does not apply.

function pmpro_upgrade_1_5_9()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';

	//fix firstpayment statuses
	$sqlQuery = "UPDATE " . $wpdb->pmpro_membership_orders . " SET status = 'success' WHERE status = 'firstpayment'";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- static query; table name from $wpdb->prefix.

	update_option("pmpro_db_version", "1.59");
	return 1.59;
}
