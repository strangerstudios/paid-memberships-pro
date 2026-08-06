<?php
/**
 * Upgrade to version 3.8.4
 *
 * Recover transaction IDs for Stripe Checkout orders that were completed
 * without them.
 *
 * Before this version, concurrent or out-of-order processing of the
 * checkout.session.completed and checkout.session.async_payment_succeeded
 * webhooks (delayed notification payment methods like SEPA or bank transfers)
 * could complete an order without its payment and subscription transaction
 * IDs. Affected subscription orders were left without a PMPro_Subscription
 * record, so later cancellations in WordPress never reached Stripe.
 *
 * The upgrade function schedules an Action Scheduler task that processes
 * candidate orders in batches, pulling the missing IDs from each order's
 * Stripe Checkout Session.
 *
 * @since 3.8.4
 */
function pmpro_upgrade_3_8_4() {
	global $wpdb;

	// Cheap check for a single candidate order so that sites without affected
	// Stripe Checkout orders don't schedule the recovery task at all.
	$candidate = $wpdb->get_var(
		"SELECT o.id
		FROM $wpdb->pmpro_membership_orders o
		INNER JOIN $wpdb->pmpro_membership_ordermeta om
			ON om.pmpro_membership_order_id = o.id
			AND om.meta_key = 'stripe_checkout_session_id'
			AND om.meta_value != ''
		WHERE o.gateway = 'stripe'
			AND o.status = 'success'
			AND ( o.payment_transaction_id = '' OR o.payment_transaction_id IS NULL )
			AND ( o.subscription_transaction_id = '' OR o.subscription_transaction_id IS NULL )
		LIMIT 1"
	);

	if ( ! empty( $candidate ) ) {
		// Action Scheduler is not initialized yet while the upgrade check runs.
		add_action( 'action_scheduler_init', function() {
			PMPro_Action_Scheduler::instance()->maybe_add_task(
				'pmpro_stripe_recover_checkout_transaction_ids',
				array(),
				'pmpro_async_tasks'
			);
		} );
	}
}

/**
 * Recover missing transaction IDs for Stripe Checkout orders via Action Scheduler.
 *
 * Pulls the IDs from each order's Stripe Checkout Session and, for
 * subscriptions, makes sure a PMPro_Subscription record exists so that
 * cancellations reach the gateway. Scheduled by pmpro_upgrade_3_8_4() and
 * re-queued until all candidate orders have been processed.
 *
 * @since 3.8.4
 */
function pmpro_stripe_recover_checkout_transaction_ids() {
	global $wpdb;

	// Get a batch of successful Stripe Checkout orders with no transaction IDs
	// that we haven't already tried to recover.
	$batch_size = 20;
	$order_ids  = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT o.id
			FROM $wpdb->pmpro_membership_orders o
			INNER JOIN $wpdb->pmpro_membership_ordermeta om
				ON om.pmpro_membership_order_id = o.id
				AND om.meta_key = 'stripe_checkout_session_id'
				AND om.meta_value != ''
			LEFT JOIN $wpdb->pmpro_membership_ordermeta tried
				ON tried.pmpro_membership_order_id = o.id
				AND tried.meta_key = 'stripe_checkout_transaction_id_recovery'
			WHERE o.gateway = 'stripe'
				AND o.status = 'success'
				AND ( o.payment_transaction_id = '' OR o.payment_transaction_id IS NULL )
				AND ( o.subscription_transaction_id = '' OR o.subscription_transaction_id IS NULL )
				AND tried.meta_id IS NULL
			ORDER BY o.id ASC
			LIMIT %d",
			$batch_size
		)
	);
	if ( empty( $order_ids ) ) {
		return;
	}

	// Queue the next run now rather than after the batch completes. Each order
	// can require several Stripe API calls, so a batch can be slow; if this run
	// times out or fails partway through, the queued follow-up still picks up
	// the remaining orders (processed orders are excluded via order meta). The
	// delay keeps the follow-up from running concurrently with this batch. The
	// chain ends when a run finds no candidate orders and returns above.
	PMPro_Action_Scheduler::instance()->maybe_add_task(
		'pmpro_stripe_recover_checkout_transaction_ids',
		array(),
		'pmpro_async_tasks',
		'+5 minutes'
	);

	// Load the Stripe library and set the API version.
	$stripe = new PMProGateway_stripe();

	foreach ( $order_ids as $order_id ) {
		$order = new MemberOrder( $order_id );
		if ( empty( $order->id ) ) {
			continue;
		}

		// Get the secret key for the environment this order was made in.
		if ( PMProGateway_stripe::using_api_keys() ) {
			$secret_key = get_option( 'pmpro_stripe_secretkey' );
		} elseif ( 'live' === $order->gateway_environment ) {
			$secret_key = get_option( 'pmpro_live_stripe_connect_secretkey' );
		} else {
			$secret_key = get_option( 'pmpro_sandbox_stripe_connect_secretkey' );
		}
		if ( empty( $secret_key ) ) {
			update_pmpro_membership_order_meta( $order->id, 'stripe_checkout_transaction_id_recovery', 'no_credentials' );
			continue;
		}
		\Stripe\Stripe::setApiKey( $secret_key );

		// Get the Checkout Session for this order from Stripe.
		$checkout_session_id = get_pmpro_membership_order_meta( $order->id, 'stripe_checkout_session_id', true );
		try {
			$checkout_session = \Stripe\Checkout\Session::retrieve( $checkout_session_id );
		} catch ( \Stripe\Error\Base $e ) {
			$checkout_session = null;
		} catch ( \Throwable $e ) {
			$checkout_session = null;
		} catch ( \Exception $e ) {
			$checkout_session = null;
		}
		if ( empty( $checkout_session ) ) {
			$order->add_order_note( __( 'Could not retrieve the Stripe Checkout Session for this order while trying to recover its missing transaction IDs.', 'paid-memberships-pro' ) );
			$order->saveOrder();
			update_pmpro_membership_order_meta( $order->id, 'stripe_checkout_transaction_id_recovery', 'failed' );
			continue;
		}

		// Pull the transaction IDs from the Checkout Session.
		if ( 'payment' === $checkout_session->mode && ! empty( $checkout_session->payment_intent ) ) {
			// One-time payment order. Assign the invoice or charge ID to the order.
			try {
				$payment_intent = \Stripe\PaymentIntent::retrieve(
					array(
						'id'     => $checkout_session->payment_intent,
						'expand' => array( 'latest_charge' ),
					)
				);
				if ( ! empty( $checkout_session->invoice ) ) {
					$order->payment_transaction_id = $checkout_session->invoice;
				} elseif ( ! empty( $payment_intent->latest_charge ) ) {
					$order->payment_transaction_id = $payment_intent->latest_charge->id;
				}
			} catch ( \Stripe\Error\Base $e ) {
				// Could not get payment intent. We just won't set a payment transaction ID.
			} catch ( \Throwable $e ) {
				// Could not get payment intent. We just won't set a payment transaction ID.
			} catch ( \Exception $e ) {
				// Could not get payment intent. We just won't set a payment transaction ID.
			}
		} elseif ( 'subscription' === $checkout_session->mode && ! empty( $checkout_session->subscription ) ) {
			// Subscription order. Assign the subscription ID and invoice ID to the order.
			$order->subscription_transaction_id = $checkout_session->subscription;
			try {
				$subscription = \Stripe\Subscription::retrieve(
					array(
						'id'     => $checkout_session->subscription,
						'expand' => array( 'latest_invoice' ),
					)
				);
				if ( ! empty( $subscription->latest_invoice->id ) ) {
					$order->payment_transaction_id = $subscription->latest_invoice->id;
				}
			} catch ( \Stripe\Error\Base $e ) {
				// Could not get the subscription. We just won't set a payment transaction ID.
			} catch ( \Throwable $e ) {
				// Could not get the subscription. We just won't set a payment transaction ID.
			} catch ( \Exception $e ) {
				// Could not get the subscription. We just won't set a payment transaction ID.
			}
		}

		if ( empty( $order->payment_transaction_id ) && empty( $order->subscription_transaction_id ) ) {
			// The Checkout Session doesn't have anything to recover for this order.
			update_pmpro_membership_order_meta( $order->id, 'stripe_checkout_transaction_id_recovery', 'nothing_to_recover' );
			continue;
		}

		$order->add_order_note( __( 'Missing transaction IDs for this order were recovered from its Stripe Checkout Session.', 'paid-memberships-pro' ) );
		$order->saveOrder();
		update_pmpro_membership_order_meta( $order->id, 'stripe_checkout_transaction_id_recovery', 'recovered' );

		// Make sure a subscription record exists for recovered subscription orders.
		// MemberOrder::saveOrder() only creates one if the user still has the
		// order's membership level, but affected orders may belong to members who
		// have since cancelled — exactly the ones a site owner needs to see, since
		// their cancellations never reached Stripe.
		if ( ! empty( $order->subscription_transaction_id ) && empty( PMPro_Subscription::get_subscription_from_subscription_transaction_id( $order->subscription_transaction_id, 'stripe', $order->gateway_environment ) ) ) {
			// PMPro_Subscription::create() syncs status, dates, and amounts from Stripe.
			PMPro_Subscription::create(
				array(
					'user_id'                     => $order->user_id,
					'membership_level_id'         => $order->membership_id,
					'gateway'                     => 'stripe',
					'gateway_environment'         => $order->gateway_environment,
					'subscription_transaction_id' => $order->subscription_transaction_id,
					'status'                      => 'active',
					'startdate'                   => date_i18n( 'Y-m-d H:i:s', $order->timestamp ),
				)
			);
		}
	}
}
