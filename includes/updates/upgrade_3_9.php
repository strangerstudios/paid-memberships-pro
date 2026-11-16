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
 * any of these Add Ons that the site is no longer using, keeping a copy so that support
 * can restore them if needed.
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

	$retired_settings = array();
	foreach ( $add_ons as $plugin_file => $settings ) {
		if ( pmpro_upgrade_3_9_was_add_on_in_use( $plugin_file ) ) {
			continue;
		}

		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $settings['prefix'] ) . '%'
			)
		);

		foreach ( array_merge( $option_names, $settings['options'] ) as $option_name ) {
			$value = get_option( $option_name );
			if ( false !== $value ) {
				$retired_settings[ $option_name ] = $value;
			}
		}
	}

	if ( empty( $retired_settings ) ) {
		return;
	}

	// Save the copy before deleting anything. Nothing reads this option at checkout.
	update_option( 'pmpro_upgrade_3_9_retired_date_settings', array_merge( (array) get_option( 'pmpro_upgrade_3_9_retired_date_settings', array() ), $retired_settings ), false );

	// Only delete settings that made it into the copy. Use delete_option() so that object caches are cleared too.
	$saved_settings = (array) get_option( 'pmpro_upgrade_3_9_retired_date_settings', array() );
	foreach ( array_keys( $retired_settings ) as $option_name ) {
		if ( array_key_exists( $option_name, $saved_settings ) ) {
			delete_option( $option_name );
		}
	}
}

/**
 * Check whether the site was still using a retired date Add On when it upgraded to 3.9.
 *
 * An Add On counts as in use if it is active, or if it was deactivated from the Plugins
 * screen recently enough that WordPress still lists it as recently deactivated (about a
 * week). That covers sites that turned the Add On off just before updating PMPro.
 *
 * Matches on the main plugin file so that a copy installed in a renamed folder still counts.
 *
 * @since 3.9.1
 *
 * @param string $plugin_file The main plugin file of the Add On.
 * @return bool
 */
function pmpro_upgrade_3_9_was_add_on_in_use( $plugin_file ) {
	$plugins = array_merge(
		(array) get_option( 'active_plugins', array() ),
		array_keys( (array) get_option( 'recently_activated', array() ) )
	);

	if ( is_multisite() ) {
		// The first subsite to upgrade deactivates a network active Add On for the whole
		// network, so remember it for the subsites that upgrade later. There is no way to
		// tell when every subsite has upgraded, so this site option is never deleted.
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

		$plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'recently_activated', array() ) ) );
	}

	foreach ( $plugins as $plugin ) {
		if ( basename( (string) $plugin ) === $plugin_file ) {
			return true;
		}
	}

	return false;
}
