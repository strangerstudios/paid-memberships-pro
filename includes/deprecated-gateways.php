<?php
/**
 * Deprecated gateway handling.
 *
 * Keeps deprecated gateways loading on sites that still use them, and provides
 * the workflow to migrate the remaining active subscriptions off of a
 * deprecated gateway (either to placeholder Stripe subscriptions or to
 * expiration dates) so the stored gateway credentials can be removed.
 *
 * @since TBD
 */

/**
 * Get the list of deprecated gateways.
 *
 * @since 3.5
 */
function pmpro_get_deprecated_gateways() {
	return apply_filters( 'pmpro_deprecated_gateways', array(
		'twocheckout',
		'cybersource',
		'paypalwpp',
		'authorizenet',
		'payflowpro',
		'paypalstandard',
		'braintree',
		'paypalexpress',
	) );
}

/**
 * Get the list of deprecated gateways that are still loaded by this site.
 *
 * @since TBD
 *
 * @return array
 */
function pmpro_get_undeprecated_gateways() {
	$undeprecated_gateways = get_option( 'pmpro_undeprecated_gateways' );
	if ( empty( $undeprecated_gateways ) ) {
		return array();
	}

	if ( is_string( $undeprecated_gateways ) ) {
		// pmpro_setOption turns this into a comma separated string.
		$undeprecated_gateways = explode( ',', $undeprecated_gateways );
	}

	return array_values( array_filter( array_map( 'sanitize_key', (array) $undeprecated_gateways ) ) );
}

/**
 * Whether the site has any deprecated gateways still loaded.
 *
 * @since TBD
 *
 * @return bool
 */
function pmpro_has_undeprecated_gateways() {
	return ! empty( pmpro_get_undeprecated_gateways() );
}

/**
 * Adds back deprecated gateways if they have ever been the selected gateway.
 * In future versions, we will remove gateway code entirely.
 * And you will have to use a stand alone add on for those gateways
 * or choose a new gateway.
 */
function pmpro_check_for_deprecated_gateways() {
	$undeprecated_gateways = pmpro_get_undeprecated_gateways();
	$default_gateway = get_option( 'pmpro_gateway' );

	$deprecated_gateways = pmpro_get_deprecated_gateways();
	foreach ( $deprecated_gateways as $deprecated_gateway ) {
		if ( $default_gateway === $deprecated_gateway || in_array( $deprecated_gateway, $undeprecated_gateways, true ) ) {
			require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_' . $deprecated_gateway . '.php' );
			if ( ! in_array( $deprecated_gateway, $undeprecated_gateways, true ) ) {
				$undeprecated_gateways[] = $deprecated_gateway;
				update_option( 'pmpro_undeprecated_gateways', $undeprecated_gateways );
			}
		}
	}
}

/**
 * Get the number of active subscriptions for a gateway in each environment.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @return array Counts keyed 'live' and 'sandbox'. Unrecognized environments count as sandbox.
 */
function pmpro_deprecated_gateway_get_subscription_counts( $gateway ) {
	global $wpdb;

	$counts = array(
		'live'    => 0,
		'sandbox' => 0,
	);

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gateway_environment, COUNT(*) as count
				FROM {$wpdb->pmpro_subscriptions}
				WHERE gateway = %s AND status = 'active'
				GROUP BY gateway_environment",
			$gateway
		)
	);
	foreach ( $rows as $row ) {
		$environment = 'live' === $row->gateway_environment ? 'live' : 'sandbox';
		$counts[ $environment ] += (int) $row->count;
	}

	return $counts;
}

/**
 * Get active subscription IDs for a gateway/environment after a given ID.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param int    $last_subscription_id Last subscription ID already queried.
 * @param int    $limit Number of subscription IDs to return.
 * @return int[]
 */
function pmpro_deprecated_gateway_get_active_subscription_ids( $gateway, $environment, $last_subscription_id = 0, $limit = 10 ) {
	global $wpdb;

	$subscription_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT id
				FROM {$wpdb->pmpro_subscriptions}
				WHERE gateway = %s
					AND gateway_environment = %s
					AND status = 'active'
					AND id > %d
				ORDER BY id ASC
				LIMIT %d",
			$gateway,
			$environment,
			(int) $last_subscription_id,
			(int) $limit
		)
	);

	return array_map( 'intval', $subscription_ids );
}

/**
 * Get the Action Scheduler group for a gateway/environment.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return string
 */
function pmpro_deprecated_gateway_get_action_group( $gateway, $environment ) {
	return 'pmpro_deprecated_gateway_' . sanitize_key( $gateway ) . '_' . sanitize_key( $environment );
}

/**
 * Whether pending or running batch actions exist for a gateway/environment.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return bool
 */
function pmpro_deprecated_gateway_has_scheduled_actions( $gateway, $environment ) {
	if ( ! function_exists( 'as_has_scheduled_action' ) ) {
		return false;
	}

	return as_has_scheduled_action( 'pmpro_deprecated_gateway_process_batch', null, pmpro_deprecated_gateway_get_action_group( $gateway, $environment ) );
}

/**
 * Get the saved workflow state for a gateway/environment.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return array Empty array if no workflow has run.
 */
function pmpro_deprecated_gateway_get_state( $gateway, $environment ) {
	$state = get_option( 'pmpro_deprecated_gateway_state_' . sanitize_key( $gateway ) . '_' . sanitize_key( $environment ) );
	return is_array( $state ) ? $state : array();
}

/**
 * Merge changes into the saved workflow state for a gateway/environment.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param array  $changes State keys to update.
 * @return array The updated state.
 */
function pmpro_deprecated_gateway_update_state( $gateway, $environment, $changes ) {
	$state = array_merge( pmpro_deprecated_gateway_get_state( $gateway, $environment ), $changes, array( 'updated_at' => time() ) );
	update_option( 'pmpro_deprecated_gateway_state_' . sanitize_key( $gateway ) . '_' . sanitize_key( $environment ), $state, false );
	return $state;
}

/**
 * Record the result of processing one subscription in the workflow state and log.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param string $outcome One of 'complete', 'skipped', 'needs_review'.
 * @param string $message Log message.
 */
function pmpro_deprecated_gateway_record_result( $gateway, $environment, $outcome, $message ) {
	if ( ! in_array( $outcome, array( 'complete', 'skipped', 'needs_review' ), true ) ) {
		$outcome = 'needs_review';
	}

	// Only write the counters being incremented. Writing the full state back
	// would revert a concurrent status change (e.g. an admin stopping the
	// workflow) to the stale snapshot read above.
	$state   = pmpro_deprecated_gateway_get_state( $gateway, $environment );
	$changes = array(
		'processed' => empty( $state['processed'] ) ? 1 : $state['processed'] + 1,
		$outcome    => empty( $state[ $outcome ] ) ? 1 : $state[ $outcome ] + 1,
	);

	pmpro_deprecated_gateway_update_state( $gateway, $environment, $changes );
	pmpro_deprecated_gateway_log( '[' . $outcome . '] ' . $message );
}

/**
 * Schedule a deprecated gateway workflow for the current environment.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $strategy Strategy slug: 'stripe' or 'expiration'.
 * @param bool   $send_email Whether to email members.
 * @param bool   $force Process subscriptions without an upcoming payment date by
 *               expiring the membership and cancelling at the gateway.
 * @return true|WP_Error
 */
function pmpro_deprecated_gateway_schedule( $gateway, $strategy, $send_email = true, $force = false ) {
	// Normalize the environment the same way pmpro_deprecated_gateway_get_status_data()
	// does so the state option key always matches what the panel reads.
	$environment = 'live' === get_option( 'pmpro_gateway_environment', 'sandbox' ) ? 'live' : 'sandbox';
	$gateway     = sanitize_key( $gateway );
	$strategy    = sanitize_key( $strategy );
	$send_email  = ! empty( $send_email );
	$force       = ! empty( $force );

	if ( ! in_array( $gateway, pmpro_get_deprecated_gateways(), true ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_not_deprecated', __( 'This workflow is only available for deprecated gateways.', 'paid-memberships-pro' ) );
	}

	if ( ! in_array( $strategy, array( 'stripe', 'expiration' ), true ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_invalid_strategy', __( 'Invalid migration type.', 'paid-memberships-pro' ) );
	}

	if ( pmpro_is_paused() ) {
		return new WP_Error( 'pmpro_deprecated_gateway_paused', __( 'Paid Memberships Pro services are paused because this looks like a staging or development copy of your site. Resume services before running this workflow.', 'paid-memberships-pro' ) );
	}

	if ( $gateway === get_option( 'pmpro_gateway' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_no_replacement', __( 'A different gateway must be active before this workflow can start.', 'paid-memberships-pro' ) );
	}

	if ( 'stripe' === $strategy && 'stripe' !== get_option( 'pmpro_gateway' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_stripe_unavailable', __( 'Stripe must be the active payment gateway before subscriptions can be migrated to Stripe.', 'paid-memberships-pro' ) );
	}

	if ( ! function_exists( 'as_enqueue_async_action' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_no_action_scheduler', __( 'Action Scheduler is not available, so this workflow cannot be scheduled.', 'paid-memberships-pro' ) );
	}

	$counts = pmpro_deprecated_gateway_get_subscription_counts( $gateway );
	$total  = 'live' === $environment ? $counts['live'] : $counts['sandbox'];
	if ( empty( $total ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_no_subscriptions', __( 'No active subscriptions were found for this gateway in the current environment.', 'paid-memberships-pro' ) );
	}

	// The state check closes the gap between a concurrent request marking the
	// workflow as running and its first batch action being enqueued.
	$state = pmpro_deprecated_gateway_get_state( $gateway, $environment );
	if (
		pmpro_deprecated_gateway_has_scheduled_actions( $gateway, $environment ) ||
		( ! empty( $state['status'] ) && 'running' === $state['status'] && time() - (int) $state['updated_at'] <= 60 )
	) {
		return new WP_Error( 'pmpro_deprecated_gateway_already_running', __( 'A workflow is already queued or running for this gateway.', 'paid-memberships-pro' ) );
	}

	// Start from a clean state so the new run's counters and feed are fresh.
	delete_option( 'pmpro_deprecated_gateway_state_' . $gateway . '_' . $environment );
	pmpro_deprecated_gateway_update_state(
		$gateway,
		$environment,
		array(
			'status'       => 'running',
			'strategy'     => $strategy,
			'send_email'   => $send_email,
			'force'        => $force,
			'started_at'   => time(),
			'completed_at' => 0,
			'total'        => $total,
			'processed'    => 0,
			'complete'     => 0,
			'skipped'      => 0,
			'needs_review' => 0,
			'note'         => '',
		)
	);

	// $unique guards against two concurrent start requests both passing the checks
	// above and enqueueing parallel batch chains. Only the initial enqueue can be
	// unique; uniqueness is per hook+group, so using it on the chained enqueue in
	// pmpro_deprecated_gateway_process_batch() would block the next batch.
	$action_id = as_enqueue_async_action(
		'pmpro_deprecated_gateway_process_batch',
		array( $gateway, $environment, $strategy, $send_email ? 1 : 0, $force ? 1 : 0, 0 ),
		pmpro_deprecated_gateway_get_action_group( $gateway, $environment ),
		true
	);
	if ( empty( $action_id ) ) {
		// A concurrent start request may have won the unique enqueue race, in which
		// case its workflow is running now and the state should be left as is.
		if ( pmpro_deprecated_gateway_has_scheduled_actions( $gateway, $environment ) ) {
			return new WP_Error( 'pmpro_deprecated_gateway_already_running', __( 'A workflow is already queued or running for this gateway.', 'paid-memberships-pro' ) );
		}
		pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'stopped', 'note' => __( 'The workflow could not be scheduled.', 'paid-memberships-pro' ) ) );
		return new WP_Error( 'pmpro_deprecated_gateway_schedule_failed', __( 'The workflow could not be scheduled. Check the migration log for details.', 'paid-memberships-pro' ) );
	}

	pmpro_deprecated_gateway_log( sprintf( 'Queued deprecated gateway workflow. Gateway=%s, environment=%s, strategy=%s, send_email=%s, force=%s, subscriptions=%d.', $gateway, $environment, $strategy, $send_email ? 'yes' : 'no', $force ? 'yes' : 'no', $total ) );

	// Kick the Action Scheduler queue so the workflow starts right away.
	if ( is_callable( array( 'PMPro_Action_Scheduler', 'dispatch_queue' ) ) ) {
		PMPro_Action_Scheduler::dispatch_queue();
	}

	return true;
}

/**
 * Stop a queued or running workflow for the current environment.
 *
 * Pending batches are unscheduled immediately; a batch that is mid-run will
 * finish its current group of subscriptions and then stop.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @return true|WP_Error
 */
function pmpro_deprecated_gateway_stop( $gateway ) {
	$environment = 'live' === get_option( 'pmpro_gateway_environment', 'sandbox' ) ? 'live' : 'sandbox';
	$gateway     = sanitize_key( $gateway );

	$state = pmpro_deprecated_gateway_get_state( $gateway, $environment );
	if ( empty( $state['status'] ) || 'running' !== $state['status'] ) {
		return new WP_Error( 'pmpro_deprecated_gateway_not_running', __( 'No workflow is currently running for this gateway.', 'paid-memberships-pro' ) );
	}

	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( '', array(), pmpro_deprecated_gateway_get_action_group( $gateway, $environment ) );
	}
	pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'stopped', 'note' => __( 'Stopped by an administrator. Start the workflow again to continue; subscriptions that were already processed will be skipped.', 'paid-memberships-pro' ) ) );
	pmpro_deprecated_gateway_log( 'Deprecated gateway workflow stopped by an administrator. Gateway=' . $gateway . ', environment=' . $environment . '.' );

	return true;
}

/**
 * Process one batch of deprecated gateway subscriptions.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param string $strategy Strategy slug.
 * @param bool   $send_email Whether to email members.
 * @param bool   $force Process subscriptions without an upcoming payment date by
 *               expiring the membership and cancelling at the gateway.
 * @param int    $last_subscription_id Last subscription ID processed by the previous batch.
 */
function pmpro_deprecated_gateway_process_batch( $gateway, $environment, $strategy, $send_email = true, $force = false, $last_subscription_id = 0 ) {
	$gateway              = sanitize_key( $gateway );
	$environment          = sanitize_key( $environment );
	$strategy             = sanitize_key( $strategy );
	$send_email           = ! empty( $send_email );
	$force                = ! empty( $force );
	$last_subscription_id = (int) $last_subscription_id;

	// Never touch gateway APIs from a paused (likely cloned) site. This also protects
	// against a database copied to staging while a workflow was queued on the live site.
	if ( pmpro_is_paused() ) {
		pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'stopped', 'note' => __( 'Stopped because Paid Memberships Pro services are paused on this site.', 'paid-memberships-pro' ) ) );
		pmpro_deprecated_gateway_log( 'Stopped deprecated gateway workflow because PMPro services are paused. Gateway=' . $gateway . ', environment=' . $environment . '.' );
		return;
	}

	// The state option is the control plane: stopping the workflow clears this flag.
	$state = pmpro_deprecated_gateway_get_state( $gateway, $environment );
	if ( empty( $state['status'] ) || 'running' !== $state['status'] ) {
		pmpro_deprecated_gateway_log( 'Skipped a deprecated gateway workflow batch because the workflow is no longer running. Gateway=' . $gateway . ', environment=' . $environment . '.' );
		return;
	}

	// The gateway calls below all use the current environment's endpoints, so bail if
	// the environment or replacement gateway changed since the workflow was scheduled.
	if ( $environment !== ( 'live' === get_option( 'pmpro_gateway_environment', 'sandbox' ) ? 'live' : 'sandbox' )
		|| ! in_array( $strategy, array( 'stripe', 'expiration' ), true )
		|| $gateway === get_option( 'pmpro_gateway' )
		|| ( 'stripe' === $strategy && 'stripe' !== get_option( 'pmpro_gateway' ) )
	) {
		pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'stopped', 'note' => __( 'Stopped because the gateway environment or active gateway changed after the workflow was scheduled.', 'paid-memberships-pro' ) ) );
		pmpro_deprecated_gateway_log( 'Stopped deprecated gateway workflow because the gateway environment or active gateway changed. Gateway=' . $gateway . ', environment=' . $environment . ', strategy=' . $strategy . '.' );
		return;
	}

	$batch_size       = 10;
	$subscription_ids = pmpro_deprecated_gateway_get_active_subscription_ids( $gateway, $environment, $last_subscription_id, $batch_size );
	if ( empty( $subscription_ids ) ) {
		pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'completed', 'completed_at' => time() ) );
		pmpro_deprecated_gateway_log( 'Deprecated gateway workflow completed. Gateway=' . $gateway . ', environment=' . $environment . '.' );
		return;
	}

	foreach ( $subscription_ids as $subscription_id ) {
		$result = pmpro_deprecated_gateway_process_subscription( (int) $subscription_id, $gateway, $environment, $strategy, $send_email, $force );
		pmpro_deprecated_gateway_record_result( $gateway, $environment, $result['outcome'], $result['message'] );
	}

	if ( count( $subscription_ids ) < $batch_size ) {
		pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'completed', 'completed_at' => time() ) );
		pmpro_deprecated_gateway_log( 'Deprecated gateway workflow completed. Gateway=' . $gateway . ', environment=' . $environment . '.' );
		return;
	}

	$action_id = as_enqueue_async_action(
		'pmpro_deprecated_gateway_process_batch',
		array( $gateway, $environment, $strategy, $send_email ? 1 : 0, $force ? 1 : 0, max( $subscription_ids ) ),
		pmpro_deprecated_gateway_get_action_group( $gateway, $environment )
	);
	if ( empty( $action_id ) ) {
		pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'stopped', 'note' => __( 'The next batch could not be queued. Start the workflow again to continue.', 'paid-memberships-pro' ) ) );
		pmpro_deprecated_gateway_log( 'Could not queue the next deprecated gateway workflow batch. Gateway=' . $gateway . ', environment=' . $environment . '.' );
	}
}
add_action( 'pmpro_deprecated_gateway_process_batch', 'pmpro_deprecated_gateway_process_batch', 10, 6 );


/**
 * Get the number of payments remaining on a billing-limited subscription.
 *
 * Billing limits do not count the subscription's initial checkout order, so a
 * subscription allows billing_limit + 1 successful orders in total. One initial
 * order is assumed even if it predates the recorded order history.
 *
 * @since TBD
 *
 * @param PMPro_Subscription $subscription The subscription to check.
 * @return int Remaining payments. 0 or less means the limit is already reached.
 */
function pmpro_deprecated_gateway_get_remaining_payments( $subscription ) {
	$billing_limit = (int) $subscription->get_billing_limit();
	$paid_orders   = count( $subscription->get_orders( array( 'status' => 'success', 'limit' => $billing_limit + 2 ) ) );
	return $billing_limit + 1 - max( 1, $paid_orders );
}

/**
 * Get identifying details for a subscription log entry.
 *
 * @since TBD
 *
 * @param PMPro_Subscription $subscription The subscription to describe.
 * @return string Subscription details for logs.
 */
function pmpro_deprecated_gateway_get_subscription_log_description( $subscription ) {
	$user = get_userdata( $subscription->get_user_id() );

	if ( empty( $user ) ) {
		$user_description = 'user #' . $subscription->get_user_id() . ' deleted';
	} else {
		$user_description = 'user ' . $user->user_login . ' <' . $user->user_email . '>';
	}

	$subscription_transaction_id = (string) $subscription->get_subscription_transaction_id();
	if ( '' === $subscription_transaction_id ) {
		$subscription_transaction_id = 'not set';
	}

	return 'subscription #' . $subscription->get_id()
		. ' (' . $user_description
		. '; subscription transaction ID: ' . $subscription_transaction_id . ')';
}

/**
 * Process one deprecated gateway subscription.
 *
 * Order of operations is deliberate: create the replacement (Stripe placeholder
 * or expiration date) first, email the member second, and cancel the old gateway
 * subscription last. Cancelling flips the local status permanently, so anything
 * that must happen for the member needs to happen before that final step.
 *
 * @since TBD
 *
 * @param int    $subscription_id Subscription ID.
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param string $strategy Strategy slug.
 * @param bool   $send_email Whether to email members.
 * @param bool   $force Process subscriptions without an upcoming payment date by
 *               expiring the membership and cancelling at the gateway.
 * @return array {
 *     @type string $outcome One of 'complete', 'skipped', 'needs_review'.
 *     @type string $message Log message.
 * }
 */
function pmpro_deprecated_gateway_process_subscription( $subscription_id, $gateway, $environment, $strategy, $send_email = true, $force = false ) {
	$subscription = PMPro_Subscription::get_subscription( $subscription_id );
	if ( empty( $subscription ) ) {
		return array( 'outcome' => 'skipped', 'message' => 'Subscription #' . $subscription_id . ' no longer exists.' );
	}

	// Used in every log message so entries can be traced back to the member and
	// the subscription at the gateway without cross-referencing IDs.
	$subscription_description = pmpro_deprecated_gateway_get_subscription_log_description( $subscription );

	if ( 'active' !== $subscription->get_status() || $gateway !== $subscription->get_gateway() || $environment !== $subscription->get_gateway_environment() ) {
		return array( 'outcome' => 'skipped', 'message' => ucfirst( $subscription_description ) . ' is no longer an active subscription for this gateway and environment.' );
	}

	// If the user no longer has the level, just cancel the old gateway subscription.
	if ( ! pmpro_hasMembershipLevel( $subscription->get_membership_level_id(), $subscription->get_user_id() ) ) {
		if ( $subscription->cancel_at_gateway() ) {
			return array( 'outcome' => 'complete', 'message' => 'Cancelled ' . $subscription_description . ' without migration because the user no longer has the associated membership level.' );
		}
		return array( 'outcome' => 'needs_review', 'message' => 'Could not confirm cancellation of ' . $subscription_description . ' at the gateway. The user no longer has the associated membership level. Verify this subscription in the gateway; an error email was sent to the admin.' );
	}

	// Load a Stripe placeholder created by an earlier run, if any. If one exists,
	// this member is on the Stripe path even if the strategy has since changed.
	$placeholder    = null;
	$placeholder_id = (int) get_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_subscription_id', true );
	if ( ! empty( $placeholder_id ) ) {
		$placeholder = PMPro_Subscription::get_subscription( $placeholder_id );
		if ( ! empty( $placeholder ) && 'active' !== $placeholder->get_status() ) {
			// The placeholder died since the last run (e.g. its trial ended without a
			// payment method). Clear the stale references so a fresh one is created
			// and the member is emailed again with the new dates. Bump the attempt
			// counter so the next Stripe create call uses a fresh idempotency key;
			// reusing the old key within 24 hours would make Stripe replay the
			// original response and hand back the dead subscription.
			update_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_attempt', (int) get_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_attempt', true ) + 1 );
			delete_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_subscription_id' );
			delete_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_transaction_id' );
			delete_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_email_sent' );
			$placeholder = null;
		}
	}

	// A Stripe subscription created by an earlier run that failed before saving the
	// local record. Loaded here so the check below never mistakes its local record
	// (created but not yet linked by meta when the earlier run died) for an
	// unrelated subscription.
	$transaction_id = (string) get_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_transaction_id', true );

	// If the user already has another active subscription for this level (e.g. they
	// already checked out again on the new gateway), just cancel the old gateway
	// subscription instead of migrating or setting an expiration date.
	$other_subscriptions = PMPro_Subscription::get_subscriptions_for_user( $subscription->get_user_id(), $subscription->get_membership_level_id() );
	foreach ( $other_subscriptions as $other_subscription ) {
		if (
			(int) $other_subscription->get_id() === (int) $subscription_id
			|| ( ! empty( $placeholder ) && (int) $other_subscription->get_id() === (int) $placeholder->get_id() )
			|| ( '' !== $transaction_id && (string) $other_subscription->get_subscription_transaction_id() === $transaction_id )
		) {
			continue;
		}
		if ( $subscription->cancel_at_gateway() ) {
			return array( 'outcome' => 'complete', 'message' => 'Cancelled ' . $subscription_description . ' without migration because the user already has active subscription #' . $other_subscription->get_id() . ' for this level.' );
		}
		return array( 'outcome' => 'needs_review', 'message' => 'Could not confirm cancellation of ' . $subscription_description . ' at the gateway. The user already has active subscription #' . $other_subscription->get_id() . ' for this level. Verify this subscription in the gateway; an error email was sent to the admin.' );
	}

	// The next payment date is the handoff point: when the new Stripe subscription
	// starts billing or when the membership expires. It is only needed when a
	// replacement has not been created yet by an earlier run. An active subscription
	// should always have an upcoming payment date, so a missing date is treated the
	// same as a past one: the gateway may no longer be billing this subscription.
	$handoff_timestamp = $subscription->get_next_payment_date( 'timestamp', false );
	$force_expiration  = false;
	if ( empty( $placeholder ) && ( empty( $handoff_timestamp ) || $handoff_timestamp <= time() ) ) {
		if ( ! $force ) {
			return array( 'outcome' => 'skipped', 'message' => ucfirst( $subscription_description ) . ' has no upcoming payment date, so the gateway may no longer be billing it. Migrate this subscription manually, or run the migration with the force option to cancel it and expire the membership.' );
		}
		// Force: expire the membership on the missed payment date and cancel below.
		$force_expiration  = true;
		$handoff_timestamp = empty( $handoff_timestamp ) ? time() : $handoff_timestamp;
	}

	$use_stripe = ( 'stripe' === $strategy && ! $force_expiration ) || ! empty( $placeholder );

	if ( $use_stripe ) {
		if ( empty( $placeholder ) ) {
			// Billing limits migrate as a remaining-payment count on the new
			// subscription, enforced locally by order counting just like native
			// Stripe subscriptions.
			$remaining_payments = 0;
			if ( ! empty( $subscription->get_billing_limit() ) ) {
				$remaining_payments = pmpro_deprecated_gateway_get_remaining_payments( $subscription );
				if ( $remaining_payments < 1 ) {
					// The limit was already reached, so no replacement is needed and
					// the membership is left unchanged, matching billing limit semantics.
					if ( $subscription->cancel_at_gateway() ) {
						return array( 'outcome' => 'complete', 'message' => 'Cancelled ' . $subscription_description . ' without migration because its billing limit has already been reached. The membership was left unchanged and no email was sent.' );
					}
					return array( 'outcome' => 'needs_review', 'message' => 'Could not confirm cancellation of ' . $subscription_description . ' at the gateway. Its billing limit has already been reached. Verify this subscription in the gateway; an error email was sent to the admin.' );
				}
			}

			// If an earlier run created the Stripe subscription but failed before saving
			// the local record, $transaction_id (loaded above) lets us reuse it instead
			// of creating a duplicate at Stripe.
			if ( '' === $transaction_id ) {
				if ( ! class_exists( 'PMProGateway_stripe' ) || ! method_exists( 'PMProGateway_stripe', 'create_deprecated_gateway_migration_subscription' ) ) {
					return array( 'outcome' => 'needs_review', 'message' => 'Could not create a Stripe placeholder for ' . $subscription_description . ' because the Stripe gateway is not available.' );
				}
				$stripe_gateway          = new PMProGateway_stripe( 'stripe' );
				$stripe_api_subscription = $stripe_gateway->create_deprecated_gateway_migration_subscription(
					$subscription,
					array(
						'trial_end' => $handoff_timestamp,
						'attempt'   => (int) get_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_attempt', true ),
					)
				);
				if ( is_wp_error( $stripe_api_subscription ) ) {
					return array( 'outcome' => 'needs_review', 'message' => 'Could not create a Stripe placeholder for ' . $subscription_description . '. Error: ' . $stripe_api_subscription->get_error_message() );
				}
				$transaction_id = $stripe_api_subscription->id;
				update_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_transaction_id', $transaction_id );
			}

			// The Stripe webhook or an earlier run may have already created the local
			// record. Only adopt an active one: a cancelled record with this
			// transaction ID means the Stripe subscription is dead, and create()
			// below will return null for it, surfacing this as needs_review instead
			// of silently migrating the member onto a dead subscription.
			$placeholder = PMPro_Subscription::get_subscription(
				array(
					'subscription_transaction_id' => $transaction_id,
					'gateway'                     => 'stripe',
					'gateway_environment'         => $environment,
					'status'                      => 'active',
				)
			);
			if ( empty( $placeholder ) ) {
				$placeholder = PMPro_Subscription::create(
					array(
						'user_id'                     => $subscription->get_user_id(),
						'membership_level_id'         => $subscription->get_membership_level_id(),
						'gateway'                     => 'stripe',
						'gateway_environment'         => $environment,
						'subscription_transaction_id' => $transaction_id,
						'status'                      => 'active',
						'startdate'                   => gmdate( 'Y-m-d H:i:s' ),
						'next_payment_date'           => gmdate( 'Y-m-d H:i:s', $handoff_timestamp ),
						'billing_amount'              => $subscription->get_billing_amount(),
						'cycle_number'                => $subscription->get_cycle_number(),
						'cycle_period'                => $subscription->get_cycle_period(),
						'billing_limit'               => $remaining_payments,
					)
				);
			}
			if ( empty( $placeholder ) ) {
				return array( 'outcome' => 'needs_review', 'message' => 'Created Stripe subscription ' . $transaction_id . ' for ' . $subscription_description . ', but the local PMPro subscription record could not be created. Verify this subscription in Stripe and PMPro.' );
			}

			update_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_stripe_subscription_id', $placeholder->get_id() );
			update_pmpro_subscription_meta( $placeholder->get_id(), 'deprecated_gateway_old_subscription_id', $subscription_id );

			// Billing limits do not count a subscription's initial order, but a
			// migrated subscription never has one. Record a $0 migration order so
			// the remaining-payment count is enforced exactly.
			if ( $remaining_payments >= 1 && empty( $placeholder->get_orders( array( 'limit' => 1 ) ) ) ) {
				$bridge_order                              = new MemberOrder();
				$bridge_order->user_id                     = $subscription->get_user_id();
				$bridge_order->membership_id               = $subscription->get_membership_level_id();
				$bridge_order->gateway                     = 'stripe';
				$bridge_order->gateway_environment         = $environment;
				$bridge_order->subscription_transaction_id = $transaction_id;
				$bridge_order->total                       = 0;
				$bridge_order->status                      = 'success';
				$bridge_order->notes                       = 'Deprecated gateway migration: stands in for the original checkout order of ' . $gateway . ' ' . $subscription_description . ' so the remaining billing limit of ' . $remaining_payments . ' is enforced.';
				$bridge_order->saveOrder();
			}
		}
	} else {
		pmpro_set_expiration_date( $subscription->get_user_id(), $subscription->get_membership_level_id(), $handoff_timestamp );
	}
	if ( ! empty( $handoff_timestamp ) ) {
		update_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_handoff_date', gmdate( 'Y-m-d H:i:s', $handoff_timestamp ) );
	}

	// Email the member before cancelling so a cancellation failure never leaves
	// a member unnotified. A meta flag prevents duplicate emails across reruns.
	$email_note         = '';
	$email_needs_review = false;
	if ( $send_email && ! get_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_email_sent', true ) ) {
		$user = get_userdata( $subscription->get_user_id() );
		if ( empty( $user ) ) {
			$email_note         = ' Could not email the member because the user no longer exists.';
			$email_needs_review = true;
		} else {
			$email = $use_stripe
				? new PMPro_Email_Template_Deprecated_Gateway_Stripe_Migration( $placeholder )
				: new PMPro_Email_Template_Deprecated_Gateway_Checkout_Required( $user, (int) $subscription->get_membership_level_id(), (int) $handoff_timestamp );
			if ( $email->send() ) {
				update_pmpro_subscription_meta( $subscription_id, 'deprecated_gateway_email_sent', time() );
				$email_note = ' Emailed the member.';
			} else {
				$email_note         = ' The member email failed to send.';
				$email_needs_review = true;
			}
		}
	}

	if ( ! $subscription->cancel_at_gateway() ) {
		return array( 'outcome' => 'needs_review', 'message' => ( $force_expiration ? 'Force: set the membership expiration date for ' . $subscription_description . ', but could not confirm cancellation' : 'Could not confirm cancellation of ' . $subscription_description ) . ' at the gateway. Verify this subscription in the gateway; an error email was sent to the admin.' . $email_note );
	}

	$outcome = $email_needs_review ? 'needs_review' : 'complete';
	if ( $use_stripe ) {
		return array( 'outcome' => $outcome, 'message' => 'Migrated ' . $subscription_description . ' to Stripe placeholder subscription #' . $placeholder->get_id() . ' and cancelled the old gateway subscription.' . $email_note );
	}
	return array( 'outcome' => $outcome, 'message' => ( $force_expiration ? 'Force: set' : 'Set' ) . ' the membership expiration date for ' . $subscription_description . ' and cancelled the old gateway subscription.' . $email_note );
}

/**
 * Clean up a deprecated gateway after its subscriptions are handled.
 *
 * Live subscriptions block cleanup no matter which environment is currently
 * selected: the stored credentials are shared by both environments, and
 * deleting them would strand any remaining live subscriptions.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @return true|WP_Error
 */
function pmpro_deprecated_gateway_cleanup_gateway( $gateway ) {
	$gateway = sanitize_key( $gateway );

	if ( ! in_array( $gateway, pmpro_get_deprecated_gateways(), true ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_cleanup_not_deprecated', __( 'This workflow is only available for deprecated gateways.', 'paid-memberships-pro' ) );
	}

	if ( pmpro_is_paused() ) {
		return new WP_Error( 'pmpro_deprecated_gateway_cleanup_paused', __( 'Paid Memberships Pro services are paused because this looks like a staging or development copy of your site. Resume services before removing gateway data.', 'paid-memberships-pro' ) );
	}

	if ( $gateway === get_option( 'pmpro_gateway' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_cleanup_no_replacement', __( 'A different gateway must be active before this gateway can be removed.', 'paid-memberships-pro' ) );
	}

	$counts = pmpro_deprecated_gateway_get_subscription_counts( $gateway );
	if ( ! empty( $counts['live'] ) ) {
		return new WP_Error(
			'pmpro_deprecated_gateway_cleanup_live_subscriptions',
			sprintf(
				// translators: %d: Number of live subscriptions.
				_n( '%d live subscription is still active for this gateway. Migrate it before removing gateway data.', '%d live subscriptions are still active for this gateway. Migrate them before removing gateway data.', $counts['live'], 'paid-memberships-pro' ),
				$counts['live']
			)
		);
	}

	if ( pmpro_deprecated_gateway_has_scheduled_actions( $gateway, 'live' ) || pmpro_deprecated_gateway_has_scheduled_actions( $gateway, 'sandbox' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_cleanup_blocked', __( 'A workflow is queued or running for this gateway. Wait for it to finish or stop it before removing gateway data.', 'paid-memberships-pro' ) );
	}

	delete_option( 'pmpro_deprecated_gateway_state_' . $gateway . '_live' );
	delete_option( 'pmpro_deprecated_gateway_state_' . $gateway . '_sandbox' );

	$undeprecated_gateways = array_values( array_diff( pmpro_get_undeprecated_gateways(), array( $gateway ) ) );
	update_option( 'pmpro_undeprecated_gateways', $undeprecated_gateways );

	// Delete stored credentials. The PayPal gateways share one set of credentials,
	// so only delete those once no PayPal gateway is loaded or active.
	$option_names           = array();
	$shared_paypal_gateways = array( 'paypalexpress', 'paypalwpp', 'paypalstandard' );
	if ( in_array( $gateway, $shared_paypal_gateways, true ) ) {
		if ( empty( array_intersect( $shared_paypal_gateways, $undeprecated_gateways ) ) && ! in_array( get_option( 'pmpro_gateway' ), $shared_paypal_gateways, true ) ) {
			$option_names = array( 'gateway_email', 'apiusername', 'apipassword', 'apisignature', 'paypalexpress_skip_confirmation' );
		}
	} else {
		switch ( $gateway ) {
			case 'authorizenet':
				$option_names = array( 'loginname', 'transactionkey', 'authnet_silent_post_token' );
				break;
			case 'payflowpro':
				$option_names = array( 'payflow_partner', 'payflow_vendor', 'payflow_user', 'payflow_pwd' );
				break;
			case 'braintree':
				$option_names = array( 'braintree_merchantid', 'braintree_publickey', 'braintree_privatekey', 'braintree_encryptionkey' );
				break;
			case 'twocheckout':
				$option_names = array( 'twocheckout_apiusername', 'twocheckout_apipassword', 'twocheckout_accountnumber', 'twocheckout_secretword' );
				break;
			case 'cybersource':
				$option_names = array( 'cybersource_merchantid', 'cybersource_securitykey' );
				break;
		}
	}

	foreach ( $option_names as $option_name ) {
		delete_option( 'pmpro_' . $option_name );
	}

	pmpro_deprecated_gateway_log( 'Deprecated gateway cleanup completed. Gateway=' . $gateway . '. Removed gateway from pmpro_undeprecated_gateways. Deleted options: ' . ( empty( $option_names ) ? 'none' : implode( ', ', $option_names ) ) . '.' );

	return true;
}

/**
 * Get everything the deprecated gateway panel needs to render its current status.
 *
 * Used for both the initial page render and AJAX status polling.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @return array
 */
function pmpro_deprecated_gateway_get_status_data( $gateway ) {
	$gateway     = sanitize_key( $gateway );
	$environment = 'live' === get_option( 'pmpro_gateway_environment', 'sandbox' ) ? 'live' : 'sandbox';
	$counts      = pmpro_deprecated_gateway_get_subscription_counts( $gateway );
	$paused      = pmpro_is_paused();
	$active      = get_option( 'pmpro_gateway' );
	$has_actions = pmpro_deprecated_gateway_has_scheduled_actions( $gateway, $environment );
	$state       = pmpro_deprecated_gateway_get_state( $gateway, $environment );

	// Self-heal: if the state says running but nothing is queued or in progress
	// (e.g. a batch action failed fatally), mark the workflow stopped so the admin
	// can start it again. The 60 second grace period avoids racing a fresh start.
	if ( ! empty( $state['status'] ) && 'running' === $state['status'] && ! $has_actions && time() - (int) $state['updated_at'] > 60 ) {
		$state = pmpro_deprecated_gateway_update_state( $gateway, $environment, array( 'status' => 'stopped', 'note' => __( 'The workflow stopped unexpectedly. Start it again to continue; subscriptions that were already processed will be skipped.', 'paid-memberships-pro' ) ) );
	}

	$is_running = ! empty( $state['status'] ) && 'running' === $state['status'];

	$start_blockers = array();
	if ( $paused ) {
		$start_blockers[] = __( 'Paid Memberships Pro services are paused because this looks like a staging or development copy of your site. Resume services to use this workflow.', 'paid-memberships-pro' );
	}
	if ( $gateway === $active ) {
		$start_blockers[] = __( 'Activate a different payment gateway before starting a migration.', 'paid-memberships-pro' );
	}
	if ( empty( $counts[ $environment ] ) ) {
		$start_blockers[]  = __( 'There are no active subscriptions for this gateway in the current environment.', 'paid-memberships-pro' );
		$other_environment = 'live' === $environment ? 'sandbox' : 'live';
		if ( ! empty( $counts[ $other_environment ] ) ) {
			$start_blockers[] = 'live' === $other_environment
				? __( 'To migrate the remaining live subscriptions, set the gateway environment to Live and reload this page.', 'paid-memberships-pro' )
				: __( 'To process the remaining sandbox subscriptions, set the gateway environment to Sandbox/Testing and reload this page.', 'paid-memberships-pro' );
		}
	}

	$cleanup_blockers = array();
	if ( $paused ) {
		$cleanup_blockers[] = __( 'Gateway data cannot be removed while Paid Memberships Pro services are paused.', 'paid-memberships-pro' );
	}
	if ( $gateway === $active ) {
		$cleanup_blockers[] = __( 'Gateway data cannot be removed while this is the active payment gateway.', 'paid-memberships-pro' );
	}
	if ( ! empty( $counts['live'] ) ) {
		$cleanup_blockers[] = sprintf(
			// translators: %d: Number of live subscriptions.
			_n( '%d live subscription is still active for this gateway. Live subscriptions must be migrated before gateway data can be removed, even while testing in the sandbox environment.', '%d live subscriptions are still active for this gateway. Live subscriptions must be migrated before gateway data can be removed, even while testing in the sandbox environment.', $counts['live'], 'paid-memberships-pro' ),
			$counts['live']
		);
	}
	if ( $has_actions || pmpro_deprecated_gateway_has_scheduled_actions( $gateway, 'live' === $environment ? 'sandbox' : 'live' ) ) {
		$cleanup_blockers[] = __( 'A workflow is queued or running for this gateway.', 'paid-memberships-pro' );
	}

	$workflow = null;
	if ( ! empty( $state['status'] ) ) {
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$workflow        = array(
			'status'            => $state['status'],
			'strategy'          => empty( $state['strategy'] ) ? '' : $state['strategy'],
			'total'             => empty( $state['total'] ) ? 0 : (int) $state['total'],
			'processed'         => empty( $state['processed'] ) ? 0 : (int) $state['processed'],
			'complete'          => empty( $state['complete'] ) ? 0 : (int) $state['complete'],
			'skipped'           => empty( $state['skipped'] ) ? 0 : (int) $state['skipped'],
			'needs_review'      => empty( $state['needs_review'] ) ? 0 : (int) $state['needs_review'],
			'note'              => empty( $state['note'] ) ? '' : $state['note'],
			'started_display'   => empty( $state['started_at'] ) ? '' : wp_date( $datetime_format, (int) $state['started_at'] ),
			'completed_display' => empty( $state['completed_at'] ) ? '' : wp_date( $datetime_format, (int) $state['completed_at'] ),
		);
	}

	return array(
		'gateway'          => $gateway,
		'environment'      => $environment,
		'counts'           => $counts,
		'paused'           => $paused,
		'has_replacement'  => $gateway !== $active,
		'stripe_available' => 'stripe' === $active && $gateway !== $active,
		'workflow'         => $workflow,
		'is_running'       => $is_running,
		'can_start'        => ! $is_running && empty( $start_blockers ),
		'start_blockers'   => $start_blockers,
		'can_cleanup'      => ! $is_running && empty( $cleanup_blockers ),
		'cleanup_blockers' => $cleanup_blockers,
	);
}

/**
 * Handle AJAX requests from the deprecated gateway panel.
 *
 * @since TBD
 */
function pmpro_deprecated_gateway_ajax() {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_paymentsettings' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) ), 403 );
	}
	check_ajax_referer( 'pmpro_deprecated_gateway', 'nonce' );

	$gateway = isset( $_POST['gateway'] ) ? sanitize_key( wp_unslash( $_POST['gateway'] ) ) : '';
	$task    = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : 'status';
	if ( ! in_array( $gateway, pmpro_get_deprecated_gateways(), true ) ) {
		wp_send_json_error( array( 'message' => __( 'This workflow is only available for deprecated gateways.', 'paid-memberships-pro' ) ), 400 );
	}

	$result   = true;
	$message  = '';
	$redirect = '';
	switch ( $task ) {
		case 'start':
			$strategy   = isset( $_POST['strategy'] ) ? sanitize_key( wp_unslash( $_POST['strategy'] ) ) : '';
			$send_email = empty( $_POST['skip_email'] );
			$force      = ! empty( $_POST['force'] );
			$result     = pmpro_deprecated_gateway_schedule( $gateway, $strategy, $send_email, $force );
			$message    = __( 'Migration workflow started.', 'paid-memberships-pro' );
			break;
		case 'stop':
			$result  = pmpro_deprecated_gateway_stop( $gateway );
			$message = __( 'Workflow stopped.', 'paid-memberships-pro' );
			break;
		case 'cleanup':
			$result   = pmpro_deprecated_gateway_cleanup_gateway( $gateway );
			$message  = __( 'Deprecated gateway data has been removed from this site.', 'paid-memberships-pro' );
			$redirect = add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'deprecated_gateway_removed' => $gateway ), admin_url( 'admin.php' ) );
			break;
		case 'activate_stripe':
			$environment = 'live' === get_option( 'pmpro_gateway_environment', 'sandbox' ) ? 'live' : 'sandbox';
			if ( ! class_exists( 'PMProGateway_stripe' ) || ! PMProGateway_stripe::has_connect_credentials( $environment ) ) {
				$result = new WP_Error( 'pmpro_deprecated_gateway_stripe_not_connected', __( 'Connect to Stripe before making it the active gateway.', 'paid-memberships-pro' ) );
			} else {
				update_option( 'pmpro_gateway', 'stripe' );

				// Make sure a webhook is set up, like the Stripe Connect return flow does.
				// Placeholder subscriptions rely on webhooks for renewal orders, billing
				// limit enforcement, and syncing cancellations when a trial lapses.
				$stripe_gateway          = new PMProGateway_stripe();
				$update_webhook_response = $stripe_gateway->update_webhook_events();
				if ( empty( $update_webhook_response ) || is_wp_error( $update_webhook_response ) ) {
					$result = new WP_Error( 'pmpro_deprecated_gateway_stripe_webhook', __( 'Stripe is now the active payment gateway, but a webhook could not be created automatically. Set up the webhook from the Stripe gateway settings before migrating subscriptions.', 'paid-memberships-pro' ) );
				} else {
					$message = __( 'Stripe is now the active payment gateway.', 'paid-memberships-pro' );
				}
			}
			break;
		case 'status':
			break;
		default:
			wp_send_json_error( array( 'message' => __( 'Invalid task.', 'paid-memberships-pro' ) ), 400 );
	}

	$data = pmpro_deprecated_gateway_get_status_data( $gateway );
	if ( is_wp_error( $result ) ) {
		$data['message'] = $result->get_error_message();
		$data['error']   = true;
	} elseif ( ! empty( $message ) ) {
		$data['message'] = $message;
		$data['error']   = false;
		if ( ! empty( $redirect ) ) {
			$data['redirect'] = $redirect;
		}
	}
	wp_send_json_success( $data );
}
add_action( 'wp_ajax_pmpro_deprecated_gateway', 'pmpro_deprecated_gateway_ajax' );

/**
 * Allow payment settings admins to view the migration log.
 *
 * @since TBD
 *
 * @param bool   $can_access Whether the file can be accessed.
 * @param string $file_dir File directory.
 * @param string $file File name.
 * @return bool
 */
function pmpro_deprecated_gateway_allow_log_access( $can_access, $file_dir, $file ) {
	if ( 'logs' === $file_dir && 'deprecated-gateways.txt' === $file && ( current_user_can( 'manage_options' ) || current_user_can( 'pmpro_paymentsettings' ) ) ) {
		return true;
	}

	return $can_access;
}
add_filter( 'pmpro_can_access_restricted_file', 'pmpro_deprecated_gateway_allow_log_access', 20, 3 );

/**
 * Append a message to the migration log file.
 *
 * @since TBD
 *
 * @param string $logstr Log output.
 */
function pmpro_deprecated_gateway_log( $logstr ) {
	$logstr = (string) $logstr;
	if ( '' === $logstr ) {
		return;
	}

	$logfile = pmpro_get_restricted_file_path( 'logs', 'deprecated-gateways.txt' );
	if ( empty( $logfile ) ) {
		return;
	}

	$loghandle = fopen( $logfile, 'a+' );
	if ( $loghandle ) {
		fwrite( $loghandle, '[' . date_i18n( 'Y-m-d H:i:s' ) . '] ' . $logstr . "\n" );
		fclose( $loghandle );
	}
}

/**
 * Render the deprecated gateway panel on the payment settings page.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 */
function pmpro_deprecated_gateway_render_panel( $gateway ) {
	$gateway = sanitize_key( $gateway );
	$data    = pmpro_deprecated_gateway_get_status_data( $gateway );

	$gateway_names = pmpro_gateways();
	$gateway_name  = empty( $gateway_names[ $gateway ] ) ? $gateway : $gateway_names[ $gateway ];

	$log_url = add_query_arg(
		array(
			'pmpro_restricted_file_dir' => 'logs',
			'pmpro_restricted_file'     => 'deprecated-gateways.txt',
		),
		admin_url( 'admin.php' )
	);
	$stripe_template_url   = add_query_arg( array( 'page' => 'pmpro-emailtemplates', 'edit' => 'deprecated_gateway_stripe_migration' ), admin_url( 'admin.php' ) );
	$checkout_template_url = add_query_arg( array( 'page' => 'pmpro-emailtemplates', 'edit' => 'deprecated_gateway_checkout_required' ), admin_url( 'admin.php' ) );

	// For the "activate a new gateway" step, offer the same Stripe Connect flow used in the setup wizard.
	$stripe_connected   = class_exists( 'PMProGateway_stripe' ) && PMProGateway_stripe::has_connect_credentials( $data['environment'] );
	$stripe_connect_url = add_query_arg(
		array(
			'action'              => 'authorize',
			'gateway_environment' => 'live' === $data['environment'] ? 'live' : 'test',
			'return_url'          => rawurlencode( add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'edit_gateway' => $gateway, 'pmpro_stripe_connect_nonce' => wp_create_nonce( 'pmpro_stripe_connect_nonce' ) ), admin_url( 'admin.php' ) ) ),
		),
		apply_filters( 'pmpro_stripe_connect_url', 'https://connect.paidmembershipspro.com' )
	);

	$config = array(
		'gateway' => $gateway,
		'log_url' => $log_url,
		'nonce'   => wp_create_nonce( 'pmpro_deprecated_gateway' ),
		'initial' => $data,
		'i18n'    => array(
			'env_live'                => __( 'Live environment', 'paid-memberships-pro' ),
			'env_sandbox'             => __( 'Sandbox environment', 'paid-memberships-pro' ),
			// translators: %s: number of live subscriptions.
			'finish_other_live'       => __( '%s live subscriptions still need to be migrated. Switch the Gateway Environment to Live/Production, updating the gateway API keys as needed, then return to this page to migrate them.', 'paid-memberships-pro' ),
			// translators: %s: number of sandbox subscriptions.
			'finish_other_sandbox'    => __( '%s sandbox subscriptions still need to be migrated. Switch the Gateway Environment to Sandbox/Testing, updating the gateway API keys as needed, then return to this page to migrate them.', 'paid-memberships-pro' ),
			'no_workflow'             => __( 'No workflow has been run for this gateway in the current environment yet.', 'paid-memberships-pro' ),
			'running'                 => __( 'Migration in progress', 'paid-memberships-pro' ),
			// translators: %1$s: number processed, %2$s: total number.
			'progress_of'             => __( '%1$s of %2$s subscriptions processed', 'paid-memberships-pro' ),
			// translators: %s: date and time.
			'completed_on'            => __( 'Workflow completed %s', 'paid-memberships-pro' ),
			// translators: %s: date and time.
			'started_on'              => __( 'Started %s', 'paid-memberships-pro' ),
			'stopped'                 => __( 'Workflow stopped', 'paid-memberships-pro' ),
			'chip_complete'           => __( 'Complete', 'paid-memberships-pro' ),
			'chip_skipped'            => __( 'Skipped', 'paid-memberships-pro' ),
			'chip_needs_review'       => __( 'Needs Review', 'paid-memberships-pro' ),
			'needs_review_warning'    => __( 'Some subscriptions need review. Search the migration log for "[needs_review]" entries and review each note before removing gateway data.', 'paid-memberships-pro' ),
			'skipped_warning'         => __( 'Some subscriptions were skipped. Search the migration log for "skipped" entries and handle them manually, or run the migration again with Force Migration enabled.', 'paid-memberships-pro' ),
			// translators: %1$s: number of subscriptions, %2$s: environment label.
			'confirm_start'           => __( 'This will process %1$s active subscriptions in the %2$s and cancel them at the old gateway.', 'paid-memberships-pro' ),
			'confirm_start_email'     => __( 'Members WILL be emailed.', 'paid-memberships-pro' ),
			'confirm_start_noemail'   => __( 'Members will NOT be emailed.', 'paid-memberships-pro' ),
			'confirm_start_stripe'    => __( 'Members who do not add a payment method before their next payment date will have their membership cancelled.', 'paid-memberships-pro' ),
			'confirm_start_force'     => __( 'Force is enabled: subscriptions without an upcoming payment date will be cancelled and their memberships expired.', 'paid-memberships-pro' ),
			'confirm_continue'        => __( 'Continue?', 'paid-memberships-pro' ),
			'download_log'            => __( 'Download Migration Log', 'paid-memberships-pro' ),
			'confirm_stop'            => __( 'Stop this workflow? Subscriptions that were already processed stay processed. You can start the workflow again later to continue.', 'paid-memberships-pro' ),
			'confirm_cleanup'         => __( 'This will permanently delete the stored credentials for this gateway and stop loading it on this site.', 'paid-memberships-pro' ),
			'error_generic'           => __( 'Something went wrong. Please reload the page and try again.', 'paid-memberships-pro' ),
		),
	);
	?>
	<div class="pmpro-dgs" id="pmpro-dgs">
		<style>
			/* WP admin's .button sets display:inline-block, which overrides the
			   browser's built-in [hidden] rule. Make hidden mean hidden. */
			.pmpro-dgs [hidden] { display: none !important; }
			.pmpro-dgs-counts { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 0 0 16px; }
			.pmpro-dgs-count { background: #FFF; border: 1px solid var(--pmpro--border--color, #E5E7EB); border-radius: var(--pmpro--border--radius, 6px); box-shadow: var(--pmpro--box-shadow, 1px 2px 3px #12196110); padding: 16px 20px; text-align: center; }
			.pmpro-dgs-count.is-current { border-color: #1A688B; background-color: var(--pmpro--color--blue-lightest, #F5F8FA); }
			.pmpro-dgs-count.is-current .pmpro-dgs-count-label { color: #1A688B; font-weight: 600; }
			.pmpro-dgs-count.is-current .pmpro-dgs-count-label:after { content: " (" attr(data-current-label) ")"; font-weight: 400; }
			.pmpro-dgs-count-num { display: block; font-size: 26px; font-weight: 700; line-height: 1.2; color: var(--pmpro--color--almost-black, #0F172A); }
			.pmpro-dgs-count-num.is-live { color: var(--pmpro--color--error-text, #721c24); }
			.pmpro-dgs-count-label { color: #666; }
			.pmpro-dgs .pmpro-stepper { margin: 20px 0; }
			.pmpro-dgs .pmpro-stepper__steps { list-style: none; margin: 0; padding: 0; }
			.pmpro-dgs .pmpro-stepper__step.is-done .pmpro-stepper__step-icon { background-color: var(--pmpro--color--success-text-alt, #45b45c); color: #FFF; font-weight: 700; }
			.pmpro-dgs .pmpro-stepper__step.is-done .pmpro-stepper__step-label { color: var(--pmpro--color--success-text, #0F441C); }
			.pmpro-dgs-workflow { border: 1px solid var(--pmpro--border--color, #E5E7EB); border-radius: var(--pmpro--border--radius, 6px); background: var(--pmpro--color--blue-lightest, #F5F8FA); padding: 12px 16px; margin: 0 0 16px; }
			.pmpro-dgs-workflow-title { font-weight: 600; margin: 0 0 4px; }
			.pmpro-dgs .spinner { float: none; margin: 0 4px 0 0; vertical-align: middle; }
			.pmpro-dgs .pmpro_message { margin: 0 0 16px; }
			.pmpro-dgs-meta { color: #646970; font-size: 12px; margin: 0 0 8px; }
			.pmpro-dgs-bar { height: 12px; background: #fff; border: 1px solid var(--pmpro--border--color, #E5E7EB); border-radius: var(--pmpro--border--radius, 6px); overflow: hidden; margin: 8px 0; }
			.pmpro-dgs-bar-fill { height: 100%; background: var(--pmpro--color--success-text-alt, #45b45c); }
			.pmpro-dgs-summary .pmpro_tag { margin-right: 5px; }
			.pmpro-dgs-actions { margin: 16px 0 0; }
			.pmpro-dgs-actions .button { margin-right: 8px; }
			.pmpro-dgs-blockers { margin: 10px 0 0 20px; color: #646970; font-size: 12px; list-style: disc; }
			.pmpro-dgs-footer { margin: 14px 0 0; padding-top: 12px; border-top: 1px solid var(--pmpro--border--color, #E5E7EB); }
		</style>
		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Deprecated Gateway Migration', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
			<div class="pmpro_message pmpro_error">
				<p><strong><?php esc_html_e( 'Notice: You Are Using a Deprecated Gateway', 'paid-memberships-pro' ); ?></strong></p>
				<p>
					<?php
					// translators: %s is the gateway name.
					printf( esc_html__( 'The %s gateway has been deprecated and will not receive updates or support. Follow the steps below to move your members to a supported gateway and remove this one from your site.', 'paid-memberships-pro' ), esc_html( $gateway_name ) );
					?>
				</p>
			</div>
			<nav class="pmpro-stepper">
				<ul class="pmpro-stepper__steps">
					<li class="pmpro-stepper__step" id="pmpro-dgs-step-1" data-step="1">
						<div class="pmpro-stepper__step-icon"><span class="pmpro-stepper__step-number">1</span></div>
						<span class="pmpro-stepper__step-label"><?php esc_html_e( 'Activate a new gateway', 'paid-memberships-pro' ); ?></span>
					</li>
					<li class="pmpro-stepper__step" id="pmpro-dgs-step-2" data-step="2">
						<div class="pmpro-stepper__step-icon"><span class="pmpro-stepper__step-number">2</span></div>
						<span class="pmpro-stepper__step-label"><?php esc_html_e( 'Migrate subscriptions', 'paid-memberships-pro' ); ?></span>
					</li>
					<li class="pmpro-stepper__step" id="pmpro-dgs-step-3" data-step="3">
						<div class="pmpro-stepper__step-icon"><span class="pmpro-stepper__step-number">3</span></div>
						<span class="pmpro-stepper__step-label"><?php esc_html_e( 'Remove gateway data', 'paid-memberships-pro' ); ?></span>
					</li>
				</ul>
				<div class="pmpro-stepper__step-divider"></div>
			</nav>
			<div id="pmpro-dgs-notice" hidden></div>
			<div id="pmpro-dgs-switch" <?php if ( $data['has_replacement'] ) { echo 'hidden'; } ?>>
				<p>
					<strong><?php esc_html_e( 'First, activate a new payment gateway.', 'paid-memberships-pro' ); ?></strong>
					<?php esc_html_e( 'We recommend Stripe: it is the only gateway with an automatic migration flow. Each active subscription is recreated at Stripe with its billing schedule intact, and members simply add a payment method. No new checkout is required.', 'paid-memberships-pro' ); ?>
				</p>
				<p>
					<?php if ( $stripe_connected ) { ?>
						<button type="button" class="button button-primary" id="pmpro-dgs-activate-stripe"><?php esc_html_e( 'Make Stripe the Active Gateway', 'paid-memberships-pro' ); ?></button>
						<span class="description"><?php esc_html_e( 'Your Stripe account is already connected in this environment.', 'paid-memberships-pro' ); ?></span>
					<?php } else { ?>
						<a href="<?php echo esc_url_raw( $stripe_connect_url ); ?>" class="pmpro-stripe-connect"><span><?php esc_html_e( 'Connect with Stripe', 'paid-memberships-pro' ); ?></span></a>
					<?php } ?>
				</p>
				<p class="description">
					<?php esc_html_e( 'Prefer PayPal? The new PayPal Add On is also supported, but subscriptions cannot be migrated automatically, so members will be emailed to check out again.', 'paid-memberships-pro' ); ?>
					<a href="https://www.paidmembershipspro.com/add-ons/pmpro-paypal/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=add-ons&utm_content=pmpro-paypal" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'Get the PayPal Add On', 'paid-memberships-pro' ); ?></a>
				</p>
			</div>
			<div id="pmpro-dgs-main" <?php if ( ! $data['has_replacement'] ) { echo 'hidden'; } ?>>
			<div class="pmpro-dgs-counts">
				<div class="pmpro-dgs-count" id="pmpro-dgs-card-live">
					<span class="pmpro-dgs-count-num" id="pmpro-dgs-count-live">&ndash;</span>
					<span class="pmpro-dgs-count-label" data-current-label="<?php esc_attr_e( 'current environment', 'paid-memberships-pro' ); ?>"><?php esc_html_e( 'Live subscriptions', 'paid-memberships-pro' ); ?></span>
				</div>
				<div class="pmpro-dgs-count" id="pmpro-dgs-card-sandbox">
					<span class="pmpro-dgs-count-num" id="pmpro-dgs-count-sandbox">&ndash;</span>
					<span class="pmpro-dgs-count-label" data-current-label="<?php esc_attr_e( 'current environment', 'paid-memberships-pro' ); ?>"><?php esc_html_e( 'Sandbox subscriptions', 'paid-memberships-pro' ); ?></span>
				</div>
			</div>
			<div class="pmpro-dgs-workflow" id="pmpro-dgs-workflow"></div>
			<div id="pmpro-dgs-finish" hidden>
				<div id="pmpro-dgs-finish-other" hidden>
					<p id="pmpro-dgs-finish-other-text"></p>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Go to Payment Settings', 'paid-memberships-pro' ); ?></a>
					</p>
				</div>
				<div id="pmpro-dgs-finish-clear" hidden>
					<p><?php esc_html_e( 'All subscriptions have been migrated off this gateway. Before removing gateway data, download the migration log and search it for "[needs_review]" entries. Review those notes before removing gateway data. Removing gateway data deletes the stored API credentials and stops loading this gateway on your site.', 'paid-memberships-pro' ); ?></p>
					<p>
						<button type="button" class="button button-primary" id="pmpro-dgs-cleanup"><?php esc_html_e( 'Remove Gateway Data', 'paid-memberships-pro' ); ?></button>
					</p>
				</div>
			</div>
			<table class="form-table" role="presentation" id="pmpro-dgs-controls">
				<tbody>
					<tr>
						<th scope="row">
							<label for="pmpro-dgs-strategy"><?php esc_html_e( 'Migration Type', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<select id="pmpro-dgs-strategy">
								<option value="stripe"><?php esc_html_e( 'Migrate to Stripe subscriptions', 'paid-memberships-pro' ); ?></option>
								<option value="expiration"><?php esc_html_e( 'Cancel subscriptions and set expiration dates', 'paid-memberships-pro' ); ?></option>
							</select>
							<p class="description" id="pmpro-dgs-desc-stripe" hidden>
								<?php esc_html_e( 'Each subscription is recreated at Stripe with no payment method on file and the old gateway subscription is cancelled. Memberships and expiration dates do not change. Members are emailed to add billing information before their next payment date. Members who do not add a payment method by that date will have their Stripe subscription and membership cancelled.', 'paid-memberships-pro' ); ?>
								<a href="<?php echo esc_url( $stripe_template_url ); ?>"><?php esc_html_e( 'Edit the Stripe migration email', 'paid-memberships-pro' ); ?></a>
							</p>
							<p class="description" id="pmpro-dgs-desc-expiration" hidden>
								<?php esc_html_e( 'Each membership is set to expire on the subscription\'s old next payment date and the old gateway subscription is cancelled. Members are emailed to check out again before their membership expires.', 'paid-memberships-pro' ); ?>
								<a href="<?php echo esc_url( $checkout_template_url ); ?>"><?php esc_html_e( 'Edit the checkout required email', 'paid-memberships-pro' ); ?></a>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pmpro-dgs-email"><?php esc_html_e( 'Member Emails', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<select id="pmpro-dgs-email">
								<option value="yes"><?php esc_html_e( 'Email members about the change', 'paid-memberships-pro' ); ?></option>
								<option value="no"><?php esc_html_e( 'Do not email members', 'paid-memberships-pro' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pmpro-dgs-force"><?php esc_html_e( 'Force Migration', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<label for="pmpro-dgs-force">
								<input type="checkbox" id="pmpro-dgs-force" />
								<?php esc_html_e( 'Also process subscriptions that would otherwise be skipped', 'paid-memberships-pro' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Subscriptions without an upcoming payment date are cancelled and their membership is expired on the missed payment date.', 'paid-memberships-pro' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="pmpro-dgs-actions">
				<button type="button" class="button button-primary" id="pmpro-dgs-start"><?php esc_html_e( 'Start Migration', 'paid-memberships-pro' ); ?></button>
				<button type="button" class="button" id="pmpro-dgs-stop" hidden><?php esc_html_e( 'Stop Workflow', 'paid-memberships-pro' ); ?></button>
			</div>
			<ul class="pmpro-dgs-blockers" id="pmpro-dgs-blockers"></ul>
			</div><!-- end pmpro-dgs-main -->
			<div class="pmpro-dgs-footer">
				<a href="<?php echo esc_url( $log_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View the full migration log', 'paid-memberships-pro' ); ?></a> |
				<a href="https://www.paidmembershipspro.com/documentation/compatibility/incompatible-deprecated-add-ons/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=deprecated-gateways#deprecated-payment-gateways" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'About Deprecated Gateways', 'paid-memberships-pro' ); ?></a> |
				<a href="https://www.paidmembershipspro.com/switching-payment-gateways/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=blog&utm_content=switching-payment-gateways" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'How to Switch Payment Gateways', 'paid-memberships-pro' ); ?></a>
				<?php if ( 'paypalexpress' === $gateway ) { ?>
					| <a href="https://www.paidmembershipspro.com/paypal-express-deprecation-hub/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=blog&utm_content=paypal-express-deprecation" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'PayPal Express Deprecation Hub', 'paid-memberships-pro' ); ?></a>
				<?php } ?>
			</div>
			</div><!-- end pmpro_section_inside -->
		</div><!-- end pmpro_section -->
		<script>
		( function() {
			var cfg = <?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
			var pollTimer = null;
			var $ = function( id ) { return document.getElementById( id ); };

			function sprintf( tpl ) {
				var args = Array.prototype.slice.call( arguments, 1 ), i = 0;
				return tpl.replace( /%(\d+\$)?s/g, function( match, pos ) {
					return String( args[ pos ? parseInt( pos, 10 ) - 1 : i++ ] );
				} );
			}

			function el( tag, className, text ) {
				var node = document.createElement( tag );
				if ( className ) { node.className = className; }
				if ( text ) { node.textContent = text; }
				return node;
			}

			function api( task, extra ) {
				var body = new URLSearchParams( { action: 'pmpro_deprecated_gateway', nonce: cfg.nonce, gateway: cfg.gateway, task: task } );
				Object.keys( extra || {} ).forEach( function( key ) { body.append( key, extra[ key ] ); } );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
					.then( function( response ) { return response.json(); } )
					.then( function( response ) {
						if ( ! response || ! response.success ) {
							notice( ( response && response.data && response.data.message ) || cfg.i18n.error_generic, true );
							return;
						}
						if ( response.data.redirect ) {
							window.location.href = response.data.redirect;
							return;
						}
						render( response.data );
						if ( response.data.message ) { notice( response.data.message, !! response.data.error ); }
					} )
					.catch( function() {
						notice( cfg.i18n.error_generic, true );
						// Keep polling through transient request failures so one bad
						// response doesn't freeze the progress display mid-migration.
						if ( cfg.latest && cfg.latest.is_running ) {
							clearTimeout( pollTimer );
							pollTimer = setTimeout( function() { api( 'status' ); }, 4000 );
						}
					} );
			}

			function notice( message, isError ) {
				var node = $( 'pmpro-dgs-notice' );
				node.textContent = '';
				node.className = 'pmpro_message ' + ( isError ? 'pmpro_error' : 'pmpro_success' );
				node.appendChild( el( 'p', '', message ) );
				node.hidden = false;
			}

			function warning( message ) {
				var node = el( 'div', 'pmpro_message pmpro_alert' );
				node.appendChild( el( 'p', '', message ) );
				return node;
			}

			function summary( w ) {
				var wrap = el( 'p', 'pmpro-dgs-summary' );
				wrap.appendChild( el( 'span', 'pmpro_tag pmpro_tag-success', cfg.i18n.chip_complete + ': ' + w.complete ) );
				wrap.appendChild( el( 'span', 'pmpro_tag pmpro_tag-' + ( w.skipped > 0 ? 'alert' : 'info' ), cfg.i18n.chip_skipped + ': ' + w.skipped ) );
				wrap.appendChild( el( 'span', 'pmpro_tag pmpro_tag-' + ( w.needs_review > 0 ? 'alert pmpro_tag-has_icon' : 'info' ), cfg.i18n.chip_needs_review + ': ' + w.needs_review ) );
				return wrap;
			}

			function renderWorkflow( d ) {
				var box = $( 'pmpro-dgs-workflow' ), w = d.workflow;
				box.textContent = '';
				if ( ! w ) {
					box.appendChild( el( 'p', 'pmpro-dgs-meta', cfg.i18n.no_workflow ) );
					return;
				}
				var title = el( 'p', 'pmpro-dgs-workflow-title' );
				if ( 'running' === w.status ) {
					title.appendChild( el( 'span', 'spinner is-active' ) );
					title.appendChild( document.createTextNode( cfg.i18n.running ) );
					box.appendChild( title );
					box.appendChild( el( 'p', 'pmpro-dgs-meta', sprintf( cfg.i18n.started_on, w.started_display ) ) );
					var bar = el( 'div', 'pmpro-dgs-bar' ), fill = el( 'div', 'pmpro-dgs-bar-fill' );
					var percent = w.total ? Math.min( 100, Math.round( w.processed / w.total * 100 ) ) : 0;
					fill.style.width = percent + '%';
					bar.appendChild( fill );
					box.appendChild( bar );
					box.appendChild( el( 'p', 'pmpro-dgs-meta', sprintf( cfg.i18n.progress_of, w.processed, w.total ) ) );
				} else if ( 'completed' === w.status ) {
					title.textContent = sprintf( cfg.i18n.completed_on, w.completed_display );
					box.appendChild( title );
				} else {
					title.textContent = cfg.i18n.stopped;
					box.appendChild( title );
					if ( w.note ) { box.appendChild( el( 'p', 'pmpro-dgs-meta', w.note ) ); }
				}
				box.appendChild( summary( w ) );
				if ( w.needs_review > 0 ) { box.appendChild( warning( cfg.i18n.needs_review_warning ) ); }
				if ( 'running' !== w.status && w.skipped > 0 ) { box.appendChild( warning( cfg.i18n.skipped_warning ) ); }
				if ( 'completed' === w.status ) {
					var download = el( 'a', 'button button-secondary', cfg.i18n.download_log );
					download.href = cfg.log_url;
					download.setAttribute( 'download', '' );
					box.appendChild( download );
				}
			}

			function render( d ) {
				cfg.latest = d;
				var isLive = 'live' === d.environment;
				$( 'pmpro-dgs-count-live' ).textContent = d.counts.live;
				$( 'pmpro-dgs-count-live' ).className = 'pmpro-dgs-count-num' + ( d.counts.live > 0 ? ' is-live' : '' );
				$( 'pmpro-dgs-count-sandbox' ).textContent = d.counts.sandbox;
				$( 'pmpro-dgs-card-live' ).className = 'pmpro-dgs-count' + ( isLive ? ' is-current' : '' );
				$( 'pmpro-dgs-card-sandbox' ).className = 'pmpro-dgs-count' + ( isLive ? '' : ' is-current' );

				function step( id, state ) {
					var item = $( id );
					item.className = 'pmpro-stepper__step' + ( state ? ' is-' + state : '' );
					item.querySelector( '.pmpro-stepper__step-number' ).textContent = 'done' === state ? '✓' : item.getAttribute( 'data-step' );
				}
				var migrated = 0 === d.counts.live && 0 === d.counts.sandbox;
				step( 'pmpro-dgs-step-1', d.has_replacement ? 'done' : 'active' );
				step( 'pmpro-dgs-step-2', migrated ? 'done' : ( d.has_replacement ? 'active' : '' ) );

				// Only surface cleanup once the current environment is fully migrated.
				var envMigrated = 0 === ( isLive ? d.counts.live : d.counts.sandbox );
				step( 'pmpro-dgs-step-3', envMigrated && d.can_cleanup ? 'active' : '' );

				$( 'pmpro-dgs-switch' ).hidden = d.has_replacement;
				$( 'pmpro-dgs-main' ).hidden = ! d.has_replacement;

				renderWorkflow( d );

				// On step 3, replace the migration controls with either a prompt to
				// switch environments or the final download/remove actions.
				var onStep3 = envMigrated && ! d.is_running;
				var otherCount = isLive ? d.counts.sandbox : d.counts.live;
				$( 'pmpro-dgs-controls' ).hidden = onStep3;
				$( 'pmpro-dgs-finish' ).hidden = ! onStep3;
				$( 'pmpro-dgs-finish-other' ).hidden = ! onStep3 || 0 === otherCount;
				$( 'pmpro-dgs-finish-clear' ).hidden = ! onStep3 || otherCount > 0;
				$( 'pmpro-dgs-finish-other-text' ).textContent = sprintf( isLive ? cfg.i18n.finish_other_sandbox : cfg.i18n.finish_other_live, otherCount );

				$( 'pmpro-dgs-start' ).hidden = d.is_running || onStep3;
				$( 'pmpro-dgs-start' ).disabled = ! d.can_start;
				$( 'pmpro-dgs-stop' ).hidden = ! d.is_running;
				$( 'pmpro-dgs-cleanup' ).disabled = ! d.can_cleanup;
				$( 'pmpro-dgs-strategy' ).disabled = d.is_running;
				$( 'pmpro-dgs-strategy' ).options[0].disabled = ! d.stripe_available;
				if ( ! d.stripe_available && 'stripe' === $( 'pmpro-dgs-strategy' ).value ) {
					$( 'pmpro-dgs-strategy' ).value = 'expiration';
				}
				$( 'pmpro-dgs-email' ).disabled = d.is_running;
				$( 'pmpro-dgs-force' ).disabled = d.is_running;
				updateDescription();

				var blockers = $( 'pmpro-dgs-blockers' );
				blockers.textContent = '';
				if ( ! d.is_running ) {
					// Only explain the action that is actually on screen: cleanup
					// blockers on step 3, start blockers while migrating.
					var visible_blockers = onStep3 ? ( 0 === otherCount ? d.cleanup_blockers : [] ) : d.start_blockers;
					visible_blockers.forEach( function( blocker ) {
						blockers.appendChild( el( 'li', '', blocker ) );
					} );
				}

				clearTimeout( pollTimer );
				if ( d.is_running ) {
					pollTimer = setTimeout( function() { api( 'status' ); }, 4000 );
				}
			}

			function updateDescription() {
				var isStripe = 'stripe' === $( 'pmpro-dgs-strategy' ).value;
				$( 'pmpro-dgs-desc-stripe' ).hidden = ! isStripe;
				$( 'pmpro-dgs-desc-expiration' ).hidden = isStripe;
			}
			$( 'pmpro-dgs-strategy' ).addEventListener( 'change', updateDescription );

			if ( $( 'pmpro-dgs-activate-stripe' ) ) {
				$( 'pmpro-dgs-activate-stripe' ).addEventListener( 'click', function() {
					// Preselect the Stripe migration path; render() reverts this if activation fails.
					$( 'pmpro-dgs-strategy' ).value = 'stripe';
					api( 'activate_stripe' );
				} );
			}

			$( 'pmpro-dgs-start' ).addEventListener( 'click', function() {
				var d = cfg.latest;
				var count = 'live' === d.environment ? d.counts.live : d.counts.sandbox;
				var envLabel = 'live' === d.environment ? cfg.i18n.env_live : cfg.i18n.env_sandbox;
				var sendEmail = 'yes' === $( 'pmpro-dgs-email' ).value;
				var force = $( 'pmpro-dgs-force' ).checked;
				var isStripe = 'stripe' === $( 'pmpro-dgs-strategy' ).value;
				var text = sprintf( cfg.i18n.confirm_start, count, envLabel ) + ' ' +
					( sendEmail ? cfg.i18n.confirm_start_email : cfg.i18n.confirm_start_noemail ) +
					( isStripe ? ' ' + cfg.i18n.confirm_start_stripe : '' ) +
					( force ? ' ' + cfg.i18n.confirm_start_force : '' ) + ' ' + cfg.i18n.confirm_continue;
				if ( window.confirm( text ) ) {
					api( 'start', { strategy: $( 'pmpro-dgs-strategy' ).value, skip_email: sendEmail ? '' : '1', force: force ? '1' : '' } );
				}
			} );

			$( 'pmpro-dgs-stop' ).addEventListener( 'click', function() {
				if ( window.confirm( cfg.i18n.confirm_stop ) ) { api( 'stop' ); }
			} );

			$( 'pmpro-dgs-cleanup' ).addEventListener( 'click', function() {
				if ( window.confirm( cfg.i18n.confirm_cleanup + ' ' + cfg.i18n.confirm_continue ) ) { api( 'cleanup' ); }
			} );

			render( cfg.initial );
		} )();
		</script>
	</div>
	<?php
}
