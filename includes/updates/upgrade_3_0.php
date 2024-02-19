<?php
/**
 * Upgrade to 3.0
 *
 * We added the subscription and subscriptionmeta tables. In order to
 * populate these tables for existing sites, we are going to create a
 * subscription for each unique subscription_transaction_id in the orders table.
 *
 * @since 3.0
 */
function pmpro_upgrade_3_0() {
	global $wpdb;

	// Create a subscription for each unique `subscription_transaction_id` in the orders table.
	$sqlQuery = "
		INSERT IGNORE INTO {$wpdb->pmpro_subscriptions} ( user_id, membership_level_id, gateway,  gateway_environment, subscription_transaction_id, status )
		SELECT DISTINCT user_id, membership_id, gateway, gateway_environment, subscription_transaction_id, IF(STRCMP(status,'success'), 'cancelled', 'active')
		FROM {$wpdb->pmpro_membership_orders}
		WHERE subscription_transaction_id <> ''
		AND gateway <> ''
		AND gateway_environment <> ''
		AND status in ('success','cancelled')
		";
	$wpdb->query( $sqlQuery );

	// If we added any subscriptions, create an update to fill out the data.
	if ( $wpdb->rows_affected ) {
		pmpro_addUpdate( 'pmpro_upgrade_3_0_ajax' );
	}

	// Change all `cancelled` orders to `success` so that we can remove `cancelled` status.
	$sqlQuery = "
		UPDATE {$wpdb->pmpro_membership_orders}
		SET status = 'success'
		WHERE status = 'cancelled'
		";
	$wpdb->query( $sqlQuery );

	return 3.0;
}

/**
 * Fill out data for migrated subscriptoins.
 * To do this, we just need to load each subscription and let it update itself.
 *
 * @since 3.0
 */
function pmpro_upgrade_3_0_ajax() {
	define ( 'PMPRO_UPGRADE_3_0_AJAX', true );

	// Migrated subscription data won't have a `cycle_number` or `billing_amount` set.
	$subscription_search_param = array(
		'billing_amount' => 0,
		'cycle_number' => 0,
		'limit' => 10,
	);
	$subscriptions = PMPro_Subscription::get_subscriptions( $subscription_search_param );

	// If we have no subscriptions left to update, remove the update.
	if ( empty( $subscriptions ) ) {
		pmpro_removeUpdate( 'pmpro_upgrade_3_0_ajax' );
	}
}
