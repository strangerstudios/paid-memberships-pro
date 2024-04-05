<?php
/**
 * Upgrade to 3.0
 *
 * We added the subscription and subscriptionmeta tables. In order to
 * populate these tables for existing sites, we are going to create a
 * subscription for each unique subscription_transaction_id in the orders table.
 *
 * @since 3.0
 *
 * @param bool $rerunning_migration True if we are rerunning the migration. In this case, we want to clear out subscription data and always rerun the migration AJAX.
 */
function pmpro_upgrade_3_0( $rerunning_migration = false ) {
	global $wpdb;

	// If we are rerunning the migration, clear out subscription data.
	if ( $rerunning_migration ) {
		// We need to reset billing_amount, cycle_number, cycle_period, startdate, enddate, next_payment_date, billing_limit, and trial_amount, and trial_limit.
		$sqlQuery = "
			UPDATE {$wpdb->pmpro_subscriptions}
			SET billing_amount = 0, cycle_number = 0, cycle_period = 'Month', startdate = NULL, enddate = NULL, next_payment_date = NULL, billing_limit = 0, trial_amount = 0, trial_limit = 0
		";
		$wpdb->query( $sqlQuery );
	}

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

	// If we added any subscriptions or are rerunning the migration script, create an update to fill out the data.
	if ( $wpdb->rows_affected || $rerunning_migration ) {
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
	global $wpdb;

	define( 'PMPRO_UPGRADE_3_0_AJAX', true );

	// Migrated subscription data won't have a `cycle_number` or `billing_amount` set.
	$subscription_search_param = array(
		'billing_amount' => 0,
		'cycle_number' => 0,
		'limit' => 10,
	);
	$subscriptions = PMPro_Subscription::get_subscriptions( $subscription_search_param );

	// Check if the migration was successful.
	$failed_migrations = array();
	foreach ( $subscriptions as $subscription ) {
		// This is the same check we used to get subs to update. We want to avoid infinite loops.
		if ( empty( $subscription->get_billing_amount() ) && empty( $subscription->get_cycle_number() ) ) {
			$failed_migrations[] = $subscription->get_id();
		}
	}
	if ( ! empty( $failed_migrations ) ) {
		// If we have failed migrations, echo the error.
		echo esc_html( '[error] Failed to migrate subscriptions: ' . implode( ', ', $failed_migrations ) );
		return;
	}

	// Get the number of subs with a billing amount or cycle number.
	$migrated = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_subscriptions} WHERE billing_amount > 0 OR cycle_number > 0" );

	// Get the total number of subscriptions.
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_subscriptions}" );

	// Update the progress.
	echo '[' . (int)$migrated . '/' . (int)$total . ']';

	// If we have no subscriptions left to update, remove the update.
	if ( empty( $subscriptions ) ) {
		pmpro_removeUpdate( 'pmpro_upgrade_3_0_ajax' );
	}
}
