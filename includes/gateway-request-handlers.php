<?php

/**
 * Handle IPN/webhook requests from gateways
 * notifying site of subscription cancellation.
 *
 * @since 3.0
 *
 * @param string $subscription_transaction_id The subscription transaction ID.
 * @param string $gateway The gateway that sent the request.
 * @param string $gateway_environment 'live' or 'sandbox'.
 *
 * @return string Entry to add to IPN/webhook log.
 */
function pmpro_handle_subscription_cancellation_at_gateway( $subscription_transaction_id, $gateway, $gateway_environment ) {
	global $wpdb;

	// Find subscription.
	$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment );
	if ( empty( $subscription ) ) {
		// The subscription does not exist on this site. Bail.
		return 'ERROR: Could not find this subscription to cancel (subscription_transaction_id=' . $subscription_transaction_id . ').';
	}

	// Get the user associated with the subscription.
	$user = get_userdata( $subscription->get_user_id() );
	if ( empty( $user ) ) {
		// The user for this subscription does not exist. Let's just set the subscription status to cancelled.
		$subscription->set( 'status', 'cancelled' );
		$subscription->save();
		return 'ERROR: Could not cancel membership. No user attached to subscription #' . $subscription->get_id() . ' with subscription transaction id = ' . $subscription_transaction_id . '.';
	}

	// Legacy Stripe code to add action on subscription cancellation.
	if ( 'stripe' === $gateway ) {
		/**
		 * Action for when a subscription is cancelled at a payment gateway.
		 * Legacy filter brought over from Stripe.
		 *
		 * @deprecated 3.0
		 *
		 * @param int $user_id The ID of the user associated with the subscription.
		 */
		do_action_deprecated( 'pmpro_stripe_subscription_deleted', array( $user->ID ), '3.0' );
	}

	// Check if we have already cancelled the subscription in PMPro.
	if ( 'cancelled' === $subscription->get_status() ) {
		return 'We have already processed this cancellation. Probably originated from WP/PMPro. ( Subscription Transaction ID #' . $subscription_transaction_id . ')';
	}

	// Store the old next payment date for the subscription for later.
	$old_next_payment_date = $subscription->get_next_payment_date();

	// Mark the PMPro_Subscription as cancelled (also clears the next payment date).
	$subscription->set( 'status', 'cancelled' );
	$subscription->save();

	// Check if the billing limit has been reached.
	if ( $subscription->billing_limit_reached() ) {
		return 'The billing limit has been reached. No membership cancellation is needed. ( Subscription Transaction ID #' . $subscription_transaction_id . ')';
	}

	// Check to see if the user has the membership level associated with this subscription.
	if ( ! pmpro_hasMembershipLevel( $subscription->get_membership_level_id(), $user->ID ) ) {
		return 'The user no longer has the membership level associated with this subscription. No membership cancellation is needed. ( Subscription Transaction ID #' . $subscription_transaction_id . ')';
	}

	// Legacy Braintree code to add action on subscription cancellation.
	if ( 'braintree' === $gateway ) {
		$newest_order = $subscription->get_orders( array( 'limit' => 1 ) );
		if ( ! empty( $newest_order ) ) {
			/**
			 * Action for when a subscription is cancelled at a payment gateway.
			 * Legacy filter brought over from Braintree.
			 *
			 * @deprecated 3.0
			 *
			 * @param MemberOrder $newest_order The most recent order associated with the subscription.
			 */
			do_action_deprecated( 'pmpro_subscription_cancelled', array( current( $newest_order ) ), '3.0' );
		}
	}

	// Check if we want to try to extend the user's membership to the next payment date.
	if ( apply_filters( 'pmpro_cancel_on_next_payment_date', true, $subscription->get_membership_level_id(), $user->ID ) ) {
		// Check if $old_next_payment_date is in the future.
		if ( ! empty( $old_next_payment_date ) && $old_next_payment_date > current_time( 'timestamp' ) ) {
			// Set the enddate to the next payment date.
			pmpro_set_expiration_date( $user->ID, $subscription->get_membership_level_id(), $old_next_payment_date );

			// Clear the user's membership level cache.
			pmpro_clear_level_cache_for_user( $user->ID );

			// Send email to member.
			$myemail = new PMProEmail();
			$myemail->sendCancelOnNextPaymentDateEmail( $user, $subscription->get_membership_level_id() );

			// Send email to admin.
			$myemail = new PMProEmail();
			$myemail->sendCancelOnNextPaymentDateAdminEmail( $user, $subscription->get_membership_level_id() );

			return 'Cancelled membership for user with id = ' . $user->ID . '. Subscription transaction id = ' . $subscription_transaction_id . '. Membership extended to next payment date.';
		}
	}

	// We're not extending the user's membership to the next payment date, so cancel it now.
	pmpro_cancelMembershipLevel( $subscription->get_membership_level_id(), $user->ID, 'cancelled' );

	// Send an email to the member.
	$myemail = new PMProEmail();
	$myemail->sendCancelEmail( $user, $subscription->get_membership_level_id() );

	// Send an email to the admin.
	$myemail = new PMProEmail();
	$myemail->sendCancelAdminEmail( $user, $subscription->get_membership_level_id() );

	return 'Cancelled membership for user with id = ' . $user->ID . '. Subscription transaction id = ' . $subscription_transaction_id . '.';
}

/**
 * Get the most recent order's payment method information and assign
 * it to the order provided where possible
 *
 * @since 3.0
 *
 * @param object $order The Member Order we want to save the billing data to
 *
 * @return string Entry to add to IPN/webhook log.
 */
function pmpro_update_order_with_recent_payment_method( $order ){

	$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $order->subscription_transaction_id, $order->gateway,  $order->gateway_environment );

	if( $subscription !== NULL ) {

		$sub_orders = $subscription->get_orders( array( 'limit' => 2 ) );

		if( count( $sub_orders ) >= 2 ) {
			//Get the first order
			$first_sub_order = reset( $sub_orders );

			$order->payment_type = $first_sub_order->payment_type;
			$order->cardtype = $first_sub_order->cardtype;
			$order->accountnumber = $first_sub_order->accountnumber;
			$order->expirationmonth = $first_sub_order->expirationmonth;
			$order->expirationyear = $first_sub_order->expirationyear;

			$order->saveOrder();

			return 'Order '.$order->code.' has been updated with the payment method information from order '.$first_sub_order->code.'.';

		}
	
	}

	return 'No recent subscriptions associated with this order were found.';


}