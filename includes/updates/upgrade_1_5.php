<?php
function pmpro_upgrade_1_5()
{
	/*
		Add the id and status fields to pmpro_memberships_users, change primary key to id instead of user_id
	*/

	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_memberships_users = $wpdb->prefix . 'pmpro_memberships_users';

	//remove primary key
	$sqlQuery = "ALTER TABLE `" . $wpdb->pmpro_memberships_users . "` DROP PRIMARY KEY";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Static upgrade query; table name from $wpdb->prefix.

	//id
	$sqlQuery = "ALTER TABLE `" . $wpdb->pmpro_memberships_users . "` ADD  `id` BIGINT( 20 ) UNSIGNED AUTO_INCREMENT FIRST, ADD PRIMARY KEY(id)";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Static upgrade query; table name from $wpdb->prefix.

	//status
	$sqlQuery = "ALTER TABLE `" . $wpdb->pmpro_memberships_users . "` ADD  `status` varchar( 20 ) NOT NULL DEFAULT 'active' AFTER `trial_limit`";
	$wpdb->query($sqlQuery); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Static upgrade query; table name from $wpdb->prefix.

	update_option("pmpro_db_version", "1.5");
	return 1.5;
}
