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

	// Change all `cancelled` orders to `success` so that we can remove `cancelled` status.
	$sqlQuery = "
		UPDATE {$wpdb->pmpro_membership_orders}
		SET status = 'success'
		WHERE status = 'cancelled'
		";
	$wpdb->query( $sqlQuery );

	// PMPro Stripe Billing Limits Add On has been merged into core and no longer needs `pmpro_stripe_billing_limit` user meta.
	$sqlQuery = "
		DELETE FROM {$wpdb->usermeta}
		WHERE meta_key = 'pmpro_stripe_billing_limit'
		";
	$wpdb->query( $sqlQuery );

	$wpdb->query( $sqlQuery );
	return 3.0;
}
