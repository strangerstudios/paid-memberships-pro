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
		 * Find a legacy callback for a gateway class on a given filter hook.
		 *
		 * First checks if the callback is hooked to the filter (covers cases where
		 * init() added it for the active gateway). Then falls back to checking if the
		 * gateway class has a static method by that name (covers secondary gateways
		 * whose init() didn't fire for this page load).
		 *
		 * @since TBD
		 *
		 * @param string $gateway_class The gateway class name (e.g. 'PMProGateway_stripe').
		 * @param string $filter_name   The filter hook name to check.
		 * @return callable|false The callback if found, false otherwise.
		 */
		private static function get_legacy_gateway_callback( $gateway_class, $filter_name ) {
			if ( $gateway_class === 'PMProGateway' ) {
				return false;
			}

			// First, check if the gateway has hooked this filter (active gateway path).
			global $wp_filter;
			if ( ! empty( $wp_filter[ $filter_name ]->callbacks ) ) {
				foreach ( $wp_filter[ $filter_name ]->callbacks as $priority => $filters ) {
					foreach ( $filters as $filter ) {
						if ( is_array( $filter['function'] ) && $gateway_class === $filter['function'][0] ) {
							return $filter['function'];
						}
					}
				}
			}

			// Fall back to checking if the class has a method by that name (secondary gateway path).
			if ( method_exists( $gateway_class, $filter_name ) ) {
				return array( $gateway_class, $filter_name );
			}

			return false;
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

		/**
		 * Get the human-readable label for the gateway at checkout.
		 *
		 * Used for the radio button label when multiple gateways are available.
		 * Override in subclasses for a friendlier label (e.g., "Pay with Credit Card").
		 *
		 * @since TBD
		 *
		 * @return string The checkout label for this gateway.
		 */
		public static function get_checkout_label() {
			$gateways = pmpro_gateways();
			$gateway_slug = str_replace( 'PMProGateway_', '', get_called_class() );
			if ( get_called_class() === 'PMProGateway' ) {
				$gateway_slug = '';
			}
			if ( isset( $gateways[ $gateway_slug ] ) ) {
				return $gateways[ $gateway_slug ];
			}
			return ucwords( $gateway_slug );
		}

		/**
		 * Enqueue gateway-specific scripts and styles for checkout.
		 *
		 * Called by the checkout preheader for each enabled gateway so that
		 * all necessary JS/CSS is loaded when multiple gateways are available.
		 * Override in subclasses to enqueue gateway-specific assets.
		 *
		 * @since TBD
		 */
		public static function enqueue_checkout_scripts() {
			// Base class does nothing. Gateways override to enqueue their scripts.
		}

		/**
		 * Render the payment fields for this gateway at checkout.
		 *
		 * Called by the checkout page inside a gateway-specific container div.
		 * The default implementation first checks if the gateway has hooked
		 * `pmpro_include_payment_information_fields` (legacy pattern) and uses
		 * that if found. Otherwise, renders the standard credit card fields.
		 *
		 * Gateways that use their own fields (e.g., Stripe Elements) should
		 * override this method. Offsite gateways should override to render
		 * nothing or their redirect/submit button.
		 *
		 * @since TBD
		 */
		public static function show_checkout_fields() {
			global $wp_filter, $pmpro_requirebilling, $pmpro_show_discount_code, $discount_code,
				$CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;

			// Check if this gateway has a legacy pmpro_include_payment_information_fields callback
			// (for gateways that haven't been updated to override show_checkout_fields).
			// First check hooked filters, then check for a class method by that name.
			$gateway_class = get_called_class();
			$legacy_callback = self::get_legacy_gateway_callback( $gateway_class, 'pmpro_include_payment_information_fields' );
			if ( $legacy_callback ) {
				call_user_func( $legacy_callback, true );
				return;
			}
			?>
			<fieldset id="pmpro_payment_information_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_payment_information_fields' ) ); ?>" <?php if ( ! $pmpro_requirebilling || apply_filters( 'pmpro_hide_payment_information_fields', false ) ) { ?>style="display: none;"<?php } ?>>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
							<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?></h2>
						</legend>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
							<input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr( $CardType ); ?>" />
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-account-number', 'pmpro_payment-account-number' ) ); ?>">
								<label for="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Card Number', 'paid-memberships-pro' ); ?></label>
								<input id="AccountNumber" name="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'AccountNumber' ) ); ?>" type="text" value="<?php echo esc_attr( $AccountNumber ); ?>" data-encrypted-name="number" autocomplete="off" />
							</div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_payment-expiration', 'pmpro_payment-expiration' ) ); ?>">
									<label for="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Expiration Date', 'paid-memberships-pro' ); ?></label>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
										<select id="ExpirationMonth" name="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'ExpirationMonth' ) ); ?>">
											<?php for ( $i = 1; $i <= 12; $i++ ) {
												$val = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
												<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $ExpirationMonth, $val ); ?>><?php echo esc_html( $val ); ?></option>
											<?php } ?>
										</select>/<select id="ExpirationYear" name="ExpirationYear" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'ExpirationYear' ) ); ?>">
										<?php
											$num_years = apply_filters( 'pmpro_num_expiration_years', 10 );
											for ( $i = date_i18n( 'Y' ); $i < intval( date_i18n( 'Y' ) ) + intval( $num_years ); $i++ ) {
												?>
												<option value="<?php echo esc_attr( $i ); ?>" <?php if ( $ExpirationYear == $i ) { ?>selected="selected"<?php } elseif ( $i == date_i18n( 'Y' ) + 1 ) { ?>selected="selected"<?php } ?>><?php echo esc_html( $i ); ?></option>
												<?php
											}
										?>
										</select>
									</div> <!-- end pmpro_form_fields-inline -->
								</div>
								<?php
									$pmpro_show_cvv = apply_filters( 'pmpro_show_cvv', true );
									if ( $pmpro_show_cvv ) { ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-cvv', 'pmpro_payment-cvv' ) ); ?>">
										<label for="CVV" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Security Code (CVC)', 'paid-memberships-pro' ); ?></label>
										<input id="CVV" name="CVV" type="text" size="4" value="<?php if ( ! empty( $_REQUEST['CVV'] ) ) { echo esc_attr( sanitize_text_field( $_REQUEST['CVV'] ) ); } ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'CVV' ) ); ?>" />
									</div>
								<?php } ?>
							</div> <!-- end pmpro_cols-2 -->
							<?php if ( $pmpro_show_discount_code ) { ?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-discount-code', 'pmpro_payment-discount-code' ) ); ?>">
										<label for="pmpro_discount_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></label>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
											<input class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_alter_price', 'discount_code' ) ); ?>" id="pmpro_discount_code" name="pmpro_discount_code" type="text" size="10" value="<?php echo esc_attr( $discount_code ); ?>" />
											<input aria-label="<?php esc_html_e( 'Apply discount code', 'paid-memberships-pro' ); ?>" type="button" id="discount_code_button" name="discount_code_button" value="<?php esc_attr_e( 'Apply', 'paid-memberships-pro' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-discount-code', 'other_discount_code_button' ) ); ?>" />
										</div> <!-- end pmpro_form_fields-inline -->
										<div id="discount_code_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message', 'discount_code_message' ) ); ?>" style="display: none;"></div>
									</div>
								</div> <!-- end pmpro_cols-2 -->
							<?php } ?>
						</div> <!-- end pmpro_form_fields -->
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</fieldset> <!-- end pmpro_payment_information_fields -->
			<?php
		}

		/**
		 * Whether this gateway requires billing address fields at checkout.
		 *
		 * Used by the checkout JS to toggle billing address visibility when
		 * switching between gateways. Override in subclasses to return false
		 * for gateways that don't need billing address (e.g., PayPal, Check).
		 *
		 * @since TBD
		 *
		 * @return bool True if billing address fields should be shown, false otherwise.
		 */
		public static function requires_billing_address() {
			// Check for legacy pmpro_include_billing_address_fields callback.
			$gateway_class = get_called_class();
			$legacy_callback = self::get_legacy_gateway_callback( $gateway_class, 'pmpro_include_billing_address_fields' );
			if ( $legacy_callback ) {
				return (bool) call_user_func( $legacy_callback, true );
			}

			return true;
		}

		/**
		 * Modify the required billing fields for this gateway.
		 *
		 * Called by the checkout preheader to let the selected gateway remove
		 * fields it doesn't need (e.g., an offsite gateway removes all card fields,
		 * a gateway using hosted fields removes card fields since validation is remote).
		 *
		 * The default implementation checks if the gateway has hooked the legacy
		 * `pmpro_required_billing_fields` filter and calls that if found.
		 * Otherwise, returns the fields unchanged (all fields required).
		 *
		 * @since TBD
		 *
		 * @param array $fields Associative array of field_name => value.
		 * @return array Modified array of required fields.
		 */
		public static function get_required_billing_fields( $fields ) {
			// Check for legacy pmpro_required_billing_fields callback.
			$gateway_class = get_called_class();
			$legacy_callback = self::get_legacy_gateway_callback( $gateway_class, 'pmpro_required_billing_fields' );
			if ( $legacy_callback ) {
				return call_user_func( $legacy_callback, $fields );
			}

			return $fields;
		}

		/**
		 * Render the submit button for this gateway at checkout.
		 *
		 * The default implementation renders the standard "Submit and Check Out"
		 * button. Offsite gateways (PayPal, etc.) should override this to render
		 * their branded redirect button instead.
		 *
		 * The default implementation checks if the gateway has hooked the legacy
		 * `pmpro_checkout_default_submit_button` filter and calls that if found.
		 *
		 * @since TBD
		 */
		public static function show_submit_button() {
			global $pmpro_requirebilling;

			// Check for legacy pmpro_checkout_default_submit_button callback.
			$gateway_class = get_called_class();
			$legacy_callback = self::get_legacy_gateway_callback( $gateway_class, 'pmpro_checkout_default_submit_button' );
			if ( $legacy_callback ) {
				call_user_func( $legacy_callback, true );
				return;
			}

			// Default submit button.
			?>
			<span id="pmpro_submit_span">
				<input type="hidden" name="submit-checkout" value="1" />
				<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php if ( $pmpro_requirebilling ) { esc_attr_e( 'Submit and Check Out', 'paid-memberships-pro' ); } else { esc_attr_e( 'Submit and Confirm', 'paid-memberships-pro' ); } ?>" />
			</span>
			<?php
		}
	}
