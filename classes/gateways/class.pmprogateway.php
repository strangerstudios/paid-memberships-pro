<?php
	/**
	 * This class serves as a base class for all gateways and is also used for orders set to the `free` or `test` gateway.
	 */
	//require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	#[AllowDynamicProperties]
	class PMProGateway
	{
		/**
		 * Process the payment for a checkout order.
		 * This includes charging the inital payment and setting up a subscription if applicable.
		 *
		 * @param MemberOrder $order The order object to process.
		 * @return bool True if the payment was processed successfully, false otherwise.
		 */
		function process( &$order ) {
			return true;
		}

		/**
		 * @deprecated 3.2
		 */
		function authorize(&$order)
		{
			_deprecated_function( __METHOD__, '3.2' );

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//simulate a successful authorization
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("authorized");													
			return true;					
		}

		/**
		 * @deprecated 3.2
		 */
		function void(&$order)
		{
			_deprecated_function( __METHOD__, '3.2' );

			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;
				
			//simulate a successful void
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("voided");					
			return true;
		}	

		/**
		 * @deprecated 3.2
		 */
		function charge(&$order)
		{
			_deprecated_function( __METHOD__, '3.2' );

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//simulate a successful charge
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("success");					
			return true;						
		}

		/**
		 * @deprecated 3.2
		 */
		function subscribe(&$order)
		{
			_deprecated_function( __METHOD__, '3.2' );

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
						
			//simulate a successful subscription processing
			$order->status = "success";		
			$order->subscription_transaction_id = "TEST" . $order->code;				
			return true;
		}	

		/**
		 * Update the billing information for the subscription associated with the passed order.
		 *
		 * @param MemberOrder $order The order object associated with the subscription to update.
		 * @return bool True if the billing information was updated successfully, false otherwise.
		 */
		function update( &$order ) {
			//simulate a successful billing update
			return true;
		}

		/**
		 * Cancel the subscription associated with the passed order.
		 *
		 * @deprecated 3.2 Use cancel_subscription insetad.
		 *
		 * @param MemberOrder $order The order object associated with the subscription to cancel.
		 * @return bool True if the subscription was canceled successfully, false otherwise.
		 */
		function cancel( &$order ) {
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;				
			return true;
		}

		/**
		 * Cancel a payment subscription.
		 *
		 * @param PMPro_Subscription $subscription The subscription to cancel.
		 * @return bool True if the subscription was canceled successfully, false otherwise.
		 */
		function cancel_subscription( $subscription ) {
			// Simulate a successful subscription cancelation.
			return true;
		}

		/**
		 * @deprecated 3.2
		 */
		function getSubscriptionStatus(&$order)
		{
			_deprecated_function( __METHOD__, '3.2' );
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//this looks different for each gateway, but generally an array of some sort
			return array();
		}

		/**
		 * @deprecated 3.2
		 */
		function getTransactionStatus(&$order)
		{
			_deprecated_function( __METHOD__, '3.2' );	
			//this looks different for each gateway, but generally an array of some sort
			return array();
		}		

		/**
		 * Check if the gateway supports a certain feature.
		 * 
		 * @since 3.0
		 * 
		 * @param string $feature The feature to check for.
		 * @return bool|string Whether the gateway supports the requested. A string may be returned in cases where a feature has different variations of support.
		 */
		public static function supports( $feature ) {
			// The base gateway doesn't support anything.			
			return false;
		}

		/**
		 * Synchronizes a subscription with this payment gateway.
		 *
		 * @since 3.0
		 *
		 * @param PMPro_Subscription $subscription The subscription to synchronize.
		 * @return string|null Error message is returned if update fails.
		 */
		public function update_subscription_info( $subscription ) {
			// Track the fields that need to be updated.
			$update_array = array();

			// Update the start date to the date of the first order for this subscription if it
			// it is earlier than the current start date.
			$oldest_orders = $subscription->get_orders( [
				'limit'   => 1,
				'orderby' => '`timestamp` ASC, `id` ASC',
			] );
			if ( ! empty( $oldest_orders ) ) {
				$oldest_order = current( $oldest_orders );
				if ( empty( $subscription->get_startdate() ) || $oldest_order->getTimestamp( true ) < strtotime( $subscription->get_startdate() ) ) {
					$update_array['startdate'] = date_i18n( 'Y-m-d H:i:s', $oldest_order->getTimestamp( true ) );
				}
			}

			// If the next payment date has passed, update the next payment date based on the most recent order.
			if ( strtotime( $subscription->get_next_payment_date() ) < time() && ! empty( $subscription->get_cycle_number() ) ) {
				// Only update the next payment date if we are not at checkout or if we don't have a next payment date yet.
				// We don't want to update profile start dates set at checkout.
				if ( ! pmpro_is_checkout() || empty( $subscription->get_next_payment_date() ) ) {
					$newest_orders = $subscription->get_orders( array( 'limit' => 1 ) );
					if ( ! empty( $newest_orders ) ) {
						// Get the most recent order.
						$newest_order = current( $newest_orders );

						// Calculate the next payment date.
						$update_array['next_payment_date'] = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $subscription->get_cycle_number() . ' ' . $subscription->get_cycle_period(), $newest_order->getTimestamp( true ) ) );
					}
				}
			}

			// Update the subscription.
			$subscription->set( $update_array );
		}
	}
