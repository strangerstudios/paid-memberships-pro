<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade to version 3.9
 *
 * 3.9 reads the same options that the retired Subscription Delays and Set Expiration
 * Dates Add Ons saved. Those Add Ons never deleted their options, so a site that stopped
 * using one of them would have its old settings go live again. Delete the options for
 * any of these Add Ons that is not active when the site upgrades.
 *
 * This runs while plugins are loading, before pmpro_check_for_deprecated_add_ons()
 * deactivates the retired Add Ons on admin_notices, so an Add On that the site was
 * still using is still listed as active here.
 *
 * @since 3.9.1
 */
function pmpro_upgrade_3_9() {
	global $wpdb;

	$add_ons = array(
		'pmpro-subscription-delays.php'  => array(
			'prefix'  => 'pmpro_subscription_delay_',
			'options' => array( 'pmpro_discount_code_subscription_delays' ),
		),
		'pmpro-set-expiration-dates.php' => array(
			'prefix'  => 'pmprosed_',
			'options' => array(),
		),
	);

	foreach ( $add_ons as $plugin_file => $settings ) {
		if ( pmpro_upgrade_3_9_was_add_on_active( $plugin_file ) ) {
			continue;
		}

		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $settings['prefix'] ) . '%'
			)
		);

		// Use delete_option() so that object caches are cleared too.
		foreach ( array_merge( $option_names, $settings['options'] ) as $option_name ) {
			delete_option( $option_name );
		}
	}
}

/**
 * Check whether a retired date Add On was active when the site upgraded to 3.9.
 *
 * Matches on the main plugin file so that a copy installed in a renamed folder still counts.
 *
 * @since 3.9.1
 *
 * @param string $plugin_file The main plugin file of the Add On.
 * @return bool
 */
function pmpro_upgrade_3_9_was_add_on_active( $plugin_file ) {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		// The first subsite to upgrade deactivates a network active Add On for the whole
		// network, so remember it for the subsites that upgrade later.
		$network_active_add_ons = (array) get_site_option( 'pmpro_upgrade_3_9_network_active_add_ons', array() );
		if ( in_array( $plugin_file, $network_active_add_ons, true ) ) {
			return true;
		}

		foreach ( array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) as $network_plugin ) {
			if ( basename( $network_plugin ) === $plugin_file ) {
				$network_active_add_ons[] = $plugin_file;
				update_site_option( 'pmpro_upgrade_3_9_network_active_add_ons', $network_active_add_ons );
				return true;
			}
		}
	}

	foreach ( $active_plugins as $active_plugin ) {
		if ( basename( $active_plugin ) === $plugin_file ) {
			return true;
		}
	}

	return false;
}
