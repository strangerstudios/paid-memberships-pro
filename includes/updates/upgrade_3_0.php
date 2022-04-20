<?php
/**
 * Upgrade to 3.0
 *
 * We added the subscription and subscriptionmeta tables. In order to
 * populate these tables for existing sites, we are going to:
 * 1. Create a subscription for each unique subscription_transaction_id in the orders table.
 * 2. Mark all created subscriptions as needing to be synced with gateway.
 * 3. Change all `cancelled` orders to `success` so that we can remove `cancelled` status.
 *
 * @since TBD
 */
function pmpro_upgrade_3_0() {
	global $wpdb;

	// Create a subscription for each unique subscription_transaction_id in the orders table.
	$sqlQuery = "
		INSERT INTO {$wpdb->pmpro_subscriptions} ( user_id, membership_level_id, gateway,  gateway_environment, subscription_transaction_id, status )
		SELECT DISTINCT user_id, membership_id, gateway, gateway_environment, subscription_transaction_id, 'active'
		FROM {$wpdb->pmpro_membership_orders}
		WHERE subscription_transaction_id <> ''
		AND gateway <> ''
		AND gateway_environment <> ''
		AND status = 'success'
		";
	$wpdb->query( $sqlQuery );

	// Mark all created subscriptions as needing to be synced with gateway.
	$sqlQuery = "
		INSERT INTO {$wpdb->pmpro_subscriptionmeta} ( pmpro_subscription_id, meta_key, meta_value )
		SELECT DISTINCT id, 'has_default_migration_data', '1'
		FROM {$wpdb->pmpro_subscriptions}
		";
	$wpdb->query( $sqlQuery );

	// Change all `cancelled` orders to `success` so that we can remove `cancelled` status.
	$sqlQuery = "
		UPDATE {$wpdb->pmpro_membership_orders}
		SET status = 'success'
		WHERE status = 'cancelled'
		";
	//$wpdb->query( $sqlQuery ); // Disabled for now to not interfere with development sites.

	return 3.0;
}
