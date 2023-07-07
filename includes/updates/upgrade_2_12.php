<?php
// Upgrade for 2.12 yo!

/**
 * Alter the database tables to use varchar instead of enums.
 */
function pmpro_upgrade_2_12() {
    global $wpdb;

    // Discount code levels table adjustment.
    $sqlQuery = "ALTER TABLE  `" . $wpdb->pmpro_discount_codes_levels . "` CHANGE  `cycle_period`  `cycle_period` VARCHAR( 10 ) NOT NULL DEFAULT 'Month'";
    $wpdb->query( $sqlQuery );

    $sqlQuery = "ALTER TABLE  `" . $wpdb->pmpro_discount_codes_levels . "` CHANGE  `expiration_period`  `expiration_period` VARCHAR( 10 ) NOT NULL";
    $wpdb->query( $sqlQuery );

    // Membership levels table adjustment.
    $sqlQuery = "ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` CHANGE  `cycle_period`  `cycle_period` VARCHAR( 10 ) NOT NULL DEFAULT 'Month'";
    $wpdb->query( $sqlQuery );

    $sqlQuery = "ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` CHANGE  `expiration_period`  `expiration_period` VARCHAR( 10 ) NOT NULL";
    $wpdb->query( $sqlQuery );

    // Membership Users table adjustment
    $sqlQuery = "ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` CHANGE  `cycle_period`  `cycle_period` VARCHAR( 10 ) NOT NULL DEFAULT 'Month'";
    $wpdb->query( $sqlQuery );

    update_option( 'pmpro_db_version', '2.97' );

    return 2.97;
}