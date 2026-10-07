<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_upgrade_1_4()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';

	//confirmation message
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` ADD  `confirmation` LONGTEXT NOT NULL AFTER  `description`
	";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-time upgrade; static ALTER on a $wpdb table name.

	update_option("pmpro_db_version", "1.4");
	return 1.4;
}
