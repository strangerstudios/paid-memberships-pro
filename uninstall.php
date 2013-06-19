<?php
/**
 * Leave no trace...
 * Use this file to remove all elements added by plugin, including database table
 */

// exit if uninstall/delete not called
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN'))
    exit();

// otherwise remove db tables
global $wpdb;

$tables = array(
    'pmpro_discount_codes',
    'pmpro_discount_codes_levels',
    'pmpro_discount_codes_uses',
    'pmpro_memberships_categories',
    'pmpro_memberships_pages',
    'pmpro_memberships_users',
    'pmpro_membership_levels',
    'pmpro_membership_orders'
);

foreach($tables as $table){
    $delete_table = $wpdb->prefix . $table;
    // setup sql query
    $sql = "DROP TABLE `$delete_table`";
    // run the query
    $wpdb->query($sql);
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

//delete options
global $wpdb;
$sqlQuery = "DELETE FROM $wpdb->options WHERE option_name LIKE 'pmpro_%'";
$wpdb->query($sqlQuery);
