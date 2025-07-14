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

		/**
		 * Check whether the payment for a token order has been completed. If so, process the order.
		 *
		 * @param MemberOrder $order The order object to check.
		 * @return true|string True if the payment has been completed and the order processed. A string if an error occurred.
		 */
		function check_token_order( $order ) {
			return __( 'Checking token orders is not supported for this gateway.', 'paid-memberships-pro' );
		}

		/**
		 * Show the settings fields for this gateway.
		 *
		 * Each gateway class should override this method. For backwards compatibility, if this
		 * method is not overriden, we will find the method hooked into pmpro_payment_option_fields
		 * and use that instead.
		 *
		 * @since 3.5
		 */
		public static function show_settings_fields() {
			global $wp_filter;

			// Make sure the filter exists.
			if ( empty( $wp_filter['pmpro_payment_option_fields']->callbacks ) ) {
				return;
			}

			// Get the name of the gateway class.
			$gateway_class = get_called_class();

			// Try to get the gateway name. It should be everything after PMProGateway_.
			$gateway_name = str_replace( 'PMProGateway_', '', $gateway_class );

			// Try to get the legacy gateway options.
			$gateway_option_keys = self::get_legacy_gateway_options();
			$gateway_options = array();
			foreach ( $gateway_option_keys as $key ) {
				$gateway_options[ $key ] = get_option( 'pmpro_' . $key );
			}


			// Loop through all the filters for pmpro_payment_option_fields and see if any are for this gateway.
			foreach( $wp_filter['pmpro_payment_option_fields']->callbacks as $priority => $filters ) {
				foreach ( $filters as $filter ) {
					if ( is_array( $filter['function'] ) && $gateway_class == $filter['function'][0] ) {
						// This gateway has hooked into pmpro_payment_option_fields. Use that method instead.
						?>
						<table class="form-table">
							<tbody>
								<?php
								call_user_func( $filter['function'], $gateway_options, $gateway_name );
								?>
							</tbody>
						</table>
						<script>
							// Show the settings for this gateway.
							jQuery(document).ready(function() {
								jQuery('.gateway_<?php echo esc_js( $gateway_name ); ?>').show();
								jQuery('.gateway_<?php echo esc_js( strtolower( $gateway_name ) ); ?>').show();
							});
						</script>
						<?php
						return;
					}
				}
			}
		}

		/**
		 * Save the settings for this gateway.
		 *
		 * Each gateway class should override this method. For backwards compatibility, if this
		 * method is not overriden, we will get the list of options that need to be saved and
		 * save them.
		 *
		 * @since 3.5
		 */
		public static function save_settings_fields() {
			// Get the legacy gateway options to save.
			$options_to_save = self::get_legacy_gateway_options();

			// Loop through the options and save them.
			foreach ( $options_to_save as $option ) {
				pmpro_setOption( $option );
			}
		}

		/**
		 * Helper function to get the legacy gateway option values.
		 *
		 * @since 3.5
		 *
		 * @return array The legacy gateway options.
		 */
		private static function get_legacy_gateway_options() {
			global $wp_filter;

			// The options were defined using the pmpro_payment_options filter.
			// Make sure the filter exists.
			if ( empty( $wp_filter['pmpro_payment_options']->callbacks ) ) {
				return array();
			}

			// Loop through all the filters for pmpro_payment_options and see if any are for this gateway.
			$gateway_options = array();
			foreach( $wp_filter['pmpro_payment_options']->callbacks as $priority => $filters ) {
				foreach ( $filters as $filter ) {
					if ( is_array( $filter['function'] ) && get_called_class() == $filter['function'][0] ) {
						// This gateway has hooked into pmpro_payment_options. Use that method instead.
						$gateway_options = call_user_func( $filter['function'], $gateway_options );
					}
				}
			}

			// Remove any options that are global for all gateways.
			$options_to_remove = array(
				'gateway_environment',
				'currency',
				'tax_state',
				'tax_rate',
				'instructions',
			);
			$gateway_options = array_diff( $gateway_options, $options_to_remove );
			return $gateway_options;
		}

		/**
		 * Get a description for this gateway.
		 *
		 * @since 3.5
		 *
		 * @return string The description for this gateway.
		 */
		public static function get_description_for_gateway_settings() {
			return esc_html__( '&#8212;', 'paid-memberships-pro' );
		}
	}
