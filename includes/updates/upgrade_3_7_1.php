<?php
/**
 * Upgrade to version 3.7.1
 *
 * Rename the Website Payments Pro gateway slug from 'paypal' to 'paypalwpp'
 * so that the 'paypal' slug can be used by the new PayPal Add On.
 *
 * Deprecate PayPal Express and ensure it keeps loading for sites that use it.
 *
 * @since 3.7.1
 */
function pmpro_upgrade_3_7_1() {
	global $wpdb;

	// Rename the WPP gateway slug in orders.
	$wpdb->query(
		"UPDATE {$wpdb->pmpro_membership_orders} SET gateway = 'paypalwpp' WHERE gateway = 'paypal'"
	);

	// Rename the WPP gateway slug in subscriptions.
	$wpdb->query(
		"UPDATE {$wpdb->pmpro_subscriptions} SET gateway = 'paypalwpp' WHERE gateway = 'paypal'"
	);

	// If the default gateway is 'paypal' (WPP), update it to 'paypalwpp'.
	if ( 'paypal' === get_option( 'pmpro_gateway' ) ) {
		update_option( 'pmpro_gateway', 'paypalwpp' );
	}

	// Get the current undeprecated gateways list.
	$undeprecated_gateways = get_option( 'pmpro_undeprecated_gateways' );
	if ( empty( $undeprecated_gateways ) ) {
		$undeprecated_gateways = array();
	} elseif ( is_string( $undeprecated_gateways ) ) {
		$undeprecated_gateways = explode( ',', $undeprecated_gateways );
	}

	// Rename 'paypal' to 'paypalwpp' in undeprecated gateways.
	$paypal_key = array_search( 'paypal', $undeprecated_gateways, true );
	if ( false !== $paypal_key ) {
		$undeprecated_gateways[ $paypal_key ] = 'paypalwpp';
	}

	/**
	 * Explicitly add 'paypalexpress' to undeprecated gateways if the site
	 * has any PPE orders, subscriptions, or uses it as the default gateway.
	 *
	 * This is necessary because many sites use PayPal Express as a secondary
	 * gateway alongside Stripe. The runtime detection in
	 * pmpro_check_for_deprecated_gateways() only checks the default gateway,
	 * so sites using PPE as a secondary option would lose access to it.
	 */
	if ( ! in_array( 'paypalexpress', $undeprecated_gateways, true ) ) {
		$has_ppe = false;

		// Check if PPE is the default gateway.
		if ( 'paypalexpress' === get_option( 'pmpro_gateway' ) ) {
			$has_ppe = true;
		}

		// Check if there are any PPE orders.
		if ( ! $has_ppe ) {
			$has_ppe = (bool) $wpdb->get_var(
				"SELECT 1 FROM {$wpdb->pmpro_membership_orders} WHERE gateway = 'paypalexpress' LIMIT 1"
			);
		}

		// Check if there are any PPE subscriptions.
		if ( ! $has_ppe ) {
			$has_ppe = (bool) $wpdb->get_var(
				"SELECT 1 FROM {$wpdb->pmpro_subscriptions} WHERE gateway = 'paypalexpress' LIMIT 1"
			);
		}

		if ( $has_ppe ) {
			$undeprecated_gateways[] = 'paypalexpress';
		}
	}

	update_option( 'pmpro_undeprecated_gateways', $undeprecated_gateways );
}
