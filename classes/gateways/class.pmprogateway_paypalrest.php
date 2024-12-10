<?php

// Include PMProGateway class.
require_once( dirname( __FILE__ ) . "/class.pmprogateway.php" );

// Set up hooks/filters.
add_action( 'init', array( 'PMProGateway_paypalrest', 'init' ) );

/**
 * PMProGateway_paypalrest Class
 *
 * Handles PayPal REST integration.
 *
 * @since TBD
 */
class PMProGateway_paypalrest extends PMProGateway {
	/**
	 * Set up hooks/filters on init.
	 *
	 * @since TBD
	 */
	public static function init() {
		// Make sure PayPal REST is a gateway option.
		add_filter( 'pmpro_gateways', array( 'PMProGateway_paypalrest', 'pmpro_gateways' ) );

		// Add fields to the payment settings page.
		add_filter( 'pmpro_payment_options', array( 'PMProGateway_paypalrest', 'pmpro_payment_options' ) );
		add_filter( 'pmpro_payment_option_fields', array( 'PMProGateway_paypalrest', 'pmpro_payment_option_fields' ), 10, 2 );
		add_action( 'pmpro_after_saved_payment_options', array( 'PMProGateway_paypalrest', 'pmpro_after_saved_payment_options' ) );

		// Checkout filters.
		$gateway = pmpro_getGateway();
		if ( $gateway === 'paypalrest' ) {
			add_filter('pmpro_include_billing_address_fields', '__return_false');
			add_filter('pmpro_include_payment_information_fields', '__return_false');
			add_filter('pmpro_required_billing_fields', array('PMProGateway_paypalrest', 'pmpro_required_billing_fields'));
			add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalrest', 'pmpro_checkout_default_submit_button'));
		}
	}

	/**
	 * Check whether or not a gateway supports a specific feature.
	 * 
	 * @since 3.0
	 * 
	 * @return string|boolean $supports Returns whether or not the gateway supports the requested feature.
	 */
	public static function supports( $feature ) {
		$supports = array(
			'subscription_sync' => true,
			'payment_method_updates' => false,
		);

		if ( empty( $supports[$feature] ) ) {
			return false;
		}

		return $supports[$feature];
	}

	/**
	 * Add PayPal REST to the list of gateways.
	 *
	 * @since TBD
	 *
	 * @param array $gateways The list of gateway options.
	 * @return array The updated list of gateway options.
	 */
	public static function pmpro_gateways( $gateways ) {
		$gateways['paypalrest'] = 'PayPal REST';
		return $gateways;
	}

	/**
	 * Get a list of payment options that the PayPal REST gateway needs/supports.
	 * Note: This function needs to exist for the currency and tax settings to show.
	 *
	 * @since TBD
	 */
	public static function getGatewayOptions() {
		$options = array(
			'gateway_environment',
			'currency',
			'tax_state',
			'tax_rate',
		);

		return $options;
	}

	/**
	 * Set payment options for payment settings page.
	 *
	 * @since TBD
	 *
	 * @param array $options The list of payment options.
	 * @return array The updated list of payment options.
	 */
	public static function pmpro_payment_options( $options ) {
		// Get the list of gateway options.
		$paypalrest_options = self::getGatewayOptions();

		return array_merge( $options, $paypalrest_options );
	}

	/**
	 * Display fields for PayPal REST settings.
	 *
	 * @since TBD
	 *
	 * @param array $values The current values of the fields.
	 * @param string $gateway The current gateway.
	 */
	public static function pmpro_payment_option_fields( $values, $gateway ) {
		self::show_environment_fields( 'live', $gateway === 'paypalrest' );
		self::show_environment_fields( 'sandbox', $gateway === 'paypalrest' );
		?>
		<script id="paypal-js" src="https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>
		<?php
	}

	/**
	 * Show payment option fields for either the live or sandbox environment.
	 *
	 * @since TBD
	 *
	 * @param string $environment The environment to show fields for. Either 'sandbox' or 'live'.
	 * @param bool $display Whether or not to display the fields.
	 */
	private static function show_environment_fields( $environment, $display ) {
		$client_id = get_option( 'pmpro_paypalrest_client_id_' . $environment );
		$client_secret = get_option( 'pmpro_paypalrest_client_secret_' . $environment );
		?>
		<tr class="pmpro_settings_divider gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2 class="title"><?php echo esc_html__( 'PayPal REST Settings', 'paid-memberships-pro' ) . ' (' . esc_html( $environment ) . ')'; ?></h2>
			</td>
		</tr>
		<?php
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			// We are not connected to PayPal. Show the connect button.
			?>
			<tr class="gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label><?php esc_html_e( 'Connect to PayPal', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<?php
					// Set up a nonce for the OAuth callback.
					$nonce = wp_generate_password( 64, false );

					// Store the nonce in user meta.
					update_user_meta( get_current_user_id(), 'pmpro_paypalrest_oauth_nonce_' . $environment, $nonce );

					// Build the OAuth URL.
					$oauth_url = add_query_arg( array(
						'nonce' => $nonce,
						'environment' => $environment,
					), admin_url( '?pmpro_get_paypalrest_signup_link=pmpro_get_paypalrest_signup_link' ) ); // TODO: Change this to the actual URL.
					$paypal_script_callback_name = 'pmpro_paypalrest_oauth_callback_' . $environment;
					?>
					<script>
						function <?php echo esc_html( $paypal_script_callback_name ); ?>(authCode, sharedId) {
							fetch('/wp-admin/admin-ajax.php?action=pmpro_paypalrest_oauth&authCode=' + authCode + '&sharedId=' + sharedId + '&environment=' + '<?php echo esc_html( $environment ); ?>', {
								method: 'POST',
								headers: {
									'content-type': 'application/json'
								}
							}).then(function(res) {
								if (!res.ok) {
									alert("Something went wrong!");
								} else {
									location.reload();
								}
							});
						}
					</script>
					<a target="_blank" data-paypal-onboard-complete="<?php echo esc_html( $paypal_script_callback_name ); ?>" href="<?php echo esc_url( $oauth_url ); ?>&displayMode=minibrowser" data-paypal-button="true">Connect to PayPal</a>
				</td>
			</tr>
			<?php
		} else {
			// We are connected to PayPal. Show information.
			?>
			<tr class="gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<?php esc_html_e( 'Client ID', 'paid-memberships-pro' ); ?>:
				</th>
				<td>
					<p><code><?php echo esc_html( $client_id ); ?></code></p>
				</td>
			</tr>
			<tr class="gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<?php esc_html_e( 'Client Secret', 'paid-memberships-pro' ); ?>:
				</th>
				<td>
					<p><code><?php echo esc_html( $client_secret ); ?></code></p>
				</td>
			</tr>
			<tr class"gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="paypalrest_disconnect_<?php echo esc_attr( $environment ); ?>"><?php esc_html_e( 'Disconnect from PayPal', 'paid-memberships-pro' ); ?>:</label>
				</th>
				<td>
					<input type="checkbox" id="paypalrest_disconnect_<?php echo esc_attr( $environment ); ?>" name="paypalrest_disconnect_<?php echo esc_attr( $environment ); ?>" value="1" />
					<p class="description"><?php esc_html_e( 'Check this box to disconnect from PayPal when saving changes.', 'paid-memberships-pro' ); ?></p>
				</td>
			<tr class="pmpro_settings_divider gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<td colspan="2">
					<h2><?php esc_html_e( 'Webhook', 'paid-memberships-pro' ); ?></h2>
				</td>
			</tr>
			<tr class="gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<?php esc_html_e( 'Webhook URL', 'paid-memberships-pro' ); ?>
				</th>
				<td>
					<p><code><?php echo esc_html( self::get_site_webhook_url() ); ?></code></p>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * When payment settings are saved, if the user wants to disconnect from PayPal, do so.
	 *
	 * @since TBD
	 */
	public static function pmpro_after_saved_payment_options() {
		// Check if the user wants to disconnect from PayPal.
		if ( ! empty( $_REQUEST['paypalrest_disconnect_sandbox'] ) ) {
			delete_option( 'pmpro_paypalrest_client_id_sandbox' );
			delete_option( 'pmpro_paypalrest_client_secret_sandbox' );
		}
		if ( ! empty( $_REQUEST['paypalrest_disconnect_live'] ) ) {
			delete_option( 'pmpro_paypalrest_client_id_live' );
			delete_option( 'pmpro_paypalrest_client_secret_live' );
		}
	}

	/**
	 * Remove required billing fields.
	 *
	 * @since TBD
	 *
	 * @param array $fields The list of required billing fields.
	 * @return array The updated list of required billing fields.
	 */
	public static function pmpro_required_billing_fields( $fields ) {
		unset($fields['bfirstname']);
		unset($fields['blastname']);
		unset($fields['baddress1']);
		unset($fields['bcity']);
		unset($fields['bstate']);
		unset($fields['bzipcode']);
		unset($fields['bphone']);
		unset($fields['bemail']);
		unset($fields['bcountry']);
		unset($fields['CardType']);
		unset($fields['AccountNumber']);
		unset($fields['ExpirationMonth']);
		unset($fields['ExpirationYear']);
		unset($fields['CVV']);

		return $fields;
	}

	/**
	 * Swap in our submit buttons.
	 *
	 * @since 1.8
	 *
	 * @param bool $show Whether or not to show the default submit button.
	 * @return bool False to not show the default submit button.
	 */
	public static function pmpro_checkout_default_submit_button ( $show ) {
		global $gateway, $pmpro_requirebilling;

		//show our submit buttons
		?>
		<span id="pmpro_paypalrest_checkout" <?php if ( $gateway != 'paypalrest' || !$pmpro_requirebilling ) { ?>style="display: none;"<?php } ?>>
			<input type="hidden" name="submit-checkout" value="1" />
			<button type="submit" id="pmpro_btn-submit-paypalrest" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout pmpro_btn-submit-checkout-paypal' ) ); ?>">
				<?php
					printf(
						/* translators: %s is the PayPal logo */
						esc_html__( 'Check Out With %s', 'paid-memberships-pro' ),
						'<span class="pmpro_btn-submit-checkout-paypal-image"></span>'
					);
				?>
				<span class="screen-reader-text"><?php esc_html_e( 'PayPal', 'paid-memberships-pro' ); ?></span>
			</button>
		</span>

		<span id="pmpro_submit_span" <?php if ( $gateway == "paypalrest" && $pmpro_requirebilling ) { ?>style="display: none;"<?php } ?>>
			<input type="hidden" name="submit-checkout" value="1" />
			<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php if($pmpro_requirebilling) { esc_html_e('Submit and Check Out', 'paid-memberships-pro' ); } else { esc_html_e('Submit and Confirm', 'paid-memberships-pro' );}?>" />
		</span>
		<?php

		//don't show the default
		return false;
	}

	/**
	 * Process checkout.
	 */
	function process( &$order ) {
		// Prepare the order for an offsite asynchroneous payment.
		$order->status = 'token';
		$order->saveOrder();
		pmpro_save_checkout_data_to_order( $order );

		// Get the membership being purchased.
		$level = $order->getMembershipLevelAtCheckout();

		// Calculate the initial payment amount with tax.
		$initial_subtotal       = $order->subtotal;
		$initial_tax            = $order->getTaxForPrice( $initial_subtotal );
		$initial_payment_amount = pmpro_round_price( (float) $initial_subtotal + (float) $initial_tax );

		// We need to handle one-time payments and subscriptions differently.
		$error = null;
		if ( ! pmpro_isLevelRecurring( $level ) ) {
			// Sending the user to PayPal for a one-time payment.
			$response = self::send_request(
				'POST',
				'v2/checkout/orders',
				array(
					'intent'         => 'CAPTURE',
					'purchase_units' => array(
						array(
							'amount' => array(
								'currency_code' => 'USD',
								'value'         => (string) $initial_payment_amount,
							),
						),
					),
					'payment_source' => array(
						'paypal' => array(
							'experience_context' => array(
								'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
								'shipping_preference'       => 'NO_SHIPPING',
								'user_action'               => 'PAY_NOW',
								'return_url'                => apply_filters( 'pmpro_confirmation_url', add_query_arg( 'pmpro_level', $level->id, pmpro_url("confirmation" ) ), $order->user_id, $level ),
								'cancel_url'                => add_query_arg( 'pmpro_level', $level->id, pmpro_url("checkout" ) ),
							),
						),
					)
				)
			);

			// If we didn't get an error string, redirect the user to PayPal to pay.
			if ( ! is_string( $response ) ) {
				// Save the order ID so that we can complete the order later.
				update_pmpro_membership_order_meta( $order->id, 'paypalrest_order_id', json_decode( $response['body'] )->id );

				// Find the payer action link and redirect the user to it.
				$links = json_decode( $response['body'] )->links;
				foreach ( $links as $link ) {
					if ( $link->rel === 'payer-action' ) {
						wp_redirect( $link->href );
						exit;
					}
				}

				// If we didn't find an approve link, return an error message.
				$error = __( 'Could not find a payer action link.', 'paid-memberships-pro' );
			}

			// If we got an error string, save it to display to the user.
			$error = $response;
		} else {
			// Sending the user to PayPal for a subscription.
			// First, get the product ID for the level.
			$product_id = self::get_product_id_for_level( $level->id );
			if ( ! $product_id ) {
				// If we couldn't get the product ID, return an error message.
				$error = __( 'Error creating product.', 'paid-memberships-pro' );
			}

			// Next, get the plan ID for the product.
			if ( empty( $error ) ) {
				// Calculate the recurring payment amount with tax.
				$recurring_subtotal       = $level->billing_amount;
				$recurring_tax            = $order->getTaxForPrice( $recurring_subtotal );
				$recurring_payment_amount = pmpro_round_price( (float) $recurring_subtotal + (float) $recurring_tax );

				// Calculate the trial payment amount with tax.
				$trial_subtotal           = $level->trial_amount;
				$trial_tax                = $order->getTaxForPrice( $trial_subtotal );
				$trial_amount             = pmpro_round_price( (float) $trial_subtotal + (float) $trial_tax );

				$plan_id = self::get_plan_for_product( $product_id, $initial_payment_amount, $recurring_payment_amount, $level->cycle_period, $level->cycle_number, $trial_amount, $level->trial_limit );
				if ( ! $plan_id ) {
					// If we couldn't get the plan ID, return an error message.
					$error = __( 'Error creating plan.', 'paid-memberships-pro' );
				}
			}

			// Finally, create the subscription.
			if ( empty( $error ) ) {
				$response = self::send_request(
					'POST',
					'v1/billing/subscriptions',
					array(
						'plan_id'    => $plan_id,
						'start_time' => pmpro_calculate_profile_start_date( $order, 'c' ),
						'application_context' => array(
							'brand_name' => get_bloginfo( 'name' ),
							'shipping_preference' => 'NO_SHIPPING',
							'user_action' => 'SUBSCRIBE_NOW',
							'return_url' => apply_filters( 'pmpro_confirmation_url', add_query_arg( 'pmpro_level', $level->id, pmpro_url( 'confirmation' ) ), $order->user_id, $level ),
							'cancel_url' => add_query_arg( 'pmpro_level', $level->id, pmpro_url( 'checkout' ) ),
						),
					)
				);

				// If we didn't get an error string, redirect the user to PayPal.
				if ( ! is_string( $response ) ) {
					// Save the subscription ID so that we can complete the order later.
					$order->subscription_transaction_id = json_decode( $response['body'] )->id;
					$order->saveOrder();

					// Find the approve link and redirect the user to it.
					$links = json_decode( $response['body'] )->links;
					foreach ( $links as $link ) {
						if ( $link->rel === 'approve' ) {
							wp_redirect( $link->href );
							exit;
						}
					}

					// If we didn't find an approve link, return an error message.
					$error = __( 'Could not find an approve link.', 'paid-memberships-pro' );
				}

				// If we got an error string, save it to display to the user.
				$error = $response;
			}
		}

		// If we got an error, save it to the order and redirect the user to the error page.
		if ( ! empty( $error ) ) {
			$order->error = $error;
			$order->shorterror = $error;
		}

		return false;
	}

	/**
	 * Cancel a subscription in PayPal.
	 *
	 * @param PMPro_Subscription $subscription The subscription to cancel.
	 * @return bool False if we could not confirm that the subscription was cancelled at the gateway.
	 */
	function cancel_subscription( $subscription ) {
		// Send the request to cancel the subscription.
		$response = self::send_request(
			'POST',
			'v1/billing/subscriptions/' . $subscription->get_subscription_transaction_id() . '/cancel',
			array(),
			$subscription->get_gateway_environment()
		);

		// If we got an error, save it to the subscription.
		if ( is_string( $response ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Pull subscription info from Stripe.
	 *
	 * @param PMPro_Subscription $subscription to pull data for.
	 *
	 * @return string|null Error message is returned if update fails.
	 */
	public function update_subscription_info( $subscription ) {
		// Get the subscription from PayPal.
		$response = self::send_request(
			'GET',
			'v1/billing/subscriptions/' . $subscription->get_subscription_transaction_id(),
			array(),
			$subscription->get_gateway_environment()
		);
		if ( is_string( $response ) ) {
			// Couldn't get the subscription. Bail.
			return $response;
		}

		// Update the subscription with the new data.
		$paypal_subscription = json_decode( $response['body'] );
		$update_array = array(
			'startdate' => date( 'Y-m-d H:i:s', strtotime( $paypal_subscription->create_time ) ),
		);
		if ( 'ACTIVE' === $paypal_subscription->status ) {
			// Subscription is active.
			$update_array['status'] = 'active';

			// Get the next payment date.
			$update_array['next_payment_date'] = date( 'Y-m-d H:i:s', strtotime( $paypal_subscription->billing_info->next_billing_time ) );

			// Get the plan for the subscription.
			$response = self::send_request(
				'GET',
				'v1/billing/plans/' . $paypal_subscription->plan_id,
				array(),
				$subscription->get_gateway_environment()
			);
			if ( is_string( $response ) ) {
				// Couldn't get the plan. Let's save what we got and bail.
				$subscription->set( $update_array );
				return $response;
			}

			$paypal_plan = json_decode( $response['body'] );
			foreach( $paypal_plan->billing_cycles as $billing_cycle ) {
				if ( 'REGULAR' === $billing_cycle->tenure_type ) {
					$update_array['billing_amount'] = $billing_cycle->pricing_scheme->fixed_price->value;
					$update_array['cycle_number']   = $billing_cycle->frequency->interval_count;
					$update_array['cycle_period']   = ucfirst( strtolower( $billing_cycle->frequency->interval_unit ) );
				} elseif ( 'TRIAL' === $billing_cycle->tenure_type ) {
					$update_array['trial_amount'] = $billing_cycle->pricing_scheme->fixed_price->value;
					$update_array['trial_limit']  = $billing_cycle->total_cycles;
				}
			}
		} else {
			// Subscription is no longer active.
			$update_array['status'] = 'cancelled';
			$update_array['enddate'] = date( 'Y-m-d H:i:s', strtotime( $paypal_subscription->status_update_time ) );
		}

		// Save the updated subscription.
		$subscription->set( $update_array );
	}

	/**
	 * Send a request to the PayPal API.
	 *
	 * @since TBD
	 *
	 * @param string $method The HTTP method to use.
	 * @param string $endpoint_url The endpoint URL to send the request to (excluding the base URL).
	 * @param array $body The body to send with the request.
	 * @param string $gateway_environment The environment to use for the request. If empty, the current environment will be used.
	 *
	 * @return array|string The response from the request or an error message.
	 */
	public static function send_request( $method, $endpoint_url, $body = array(), $gateway_environment = '' ) {
		// If the gateway environment is not set, get it from the options.
		if ( empty( $gateway_environment ) ) {
			$gateway_environment = pmpro_getOption( 'gateway_environment' );
		}

		// If the gateway environment is still not set, default to 'sandbox'.
		if ( empty( $gateway_environment ) ) {
			$gateway_environment = 'sandbox';
		}

		// Get the base URL and credentials for the request.
		$base_url      = ( 'live' === $gateway_environment ) ? 'https://api-m.paypal.com/' : 'https://api-m.sandbox.paypal.com/';
		$client_id     = get_option( 'pmpro_paypalrest_client_id_' . $gateway_environment );
		$client_secret = get_option( 'pmpro_paypalrest_client_secret_' . $gateway_environment );

		// Build the request.
		$request_args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
				'Content-Type'  => 'application/json',
			)
		);
		if ( ! empty( $body ) ) {
			$request_args['body'] = json_encode( $body );
		}

		// Make the request using wp_remote_request().
		$response = wp_remote_request( $base_url . $endpoint_url, $request_args );

		// If response is a WP_Error, return the error message.
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		// If the response code is not in the 200 range, return an error message.
		if ( $response['response']['code'] < 200 || $response['response']['code'] >= 300 ) {
			return 'Error ' . $response['response']['code'] . ': ' . $response['response']['message'];
		}

		// Return the response.
		return $response;
	}

	/**
	 * Get the webhook URL for the site.
	 *
	 * @since TBD
	 *
	 * @return string The webhook URL.
	 */
	private static function get_site_webhook_url() {
		return admin_url( 'admin-ajax.php' ) . '?action=pmpro_paypalrest_webhook';
	}

	/**
	 * Get the product ID for a specific level. If the product ID does not exist, create it.
	 *
	 * @since TBD
	 *
	 * @param int $level_id The ID of the level to get the product ID for.
	 * @return string|false The product ID or false if the product ID is not found or created.
	 */
	private static function get_product_id_for_level( $level_id ) {
		// Get the product ID from the database.
		$product_id = get_option( 'pmpro_paypalrest_product_id_' . $level_id );

		// If we have a product ID, double check with PayPal to make sure it exists.
		if ( ! empty( $product_id ) ) {
			$response = self::send_request(
				'GET',
				'v1/catalogs/products/' . $product_id
			);

			// If $response is not an error message, return the product ID.
			if ( ! is_string( $response ) ) {
				return $product_id;
			}
		}

		// Create a new product.
		$level = pmpro_getLevel( $level_id );
		$response = self::send_request(
			'POST',
			'v1/catalogs/products',
			array(
				'name'        => substr( $level->name, 0, 127 ),
				'description' => __( 'Created by Paid Memberships Pro.', 'paid-memberships-pro' ),
				'type'        => 'SERVICE', // TODO: Should this be 'DIGITAL'?
			)
		);

		// If $response is an error message, return false.
		if ( is_string( $response ) ) {
			return false;
		}

		// Save the product ID to the database.
		$product_id = sanitize_text_field( json_decode( $response['body'] )->id );
		update_option( 'pmpro_paypalrest_product_id_' . $level_id, $product_id );
		return $product_id;
	}

	/**
	 * Get a plan for a given product, or create a new plan if one does not exist.
	 *
	 * @since TBD
	 *
	 * @param string $product_id The ID of the product to get the plan for.
	 * @param float $setup_fee The setup fee (initial payment) to charge for the plan.
	 * @param float $amount The amount to charge for the plan.
	 * @param string $cycle_period The period of the billing cycle.
	 * @param int $cycle_number The number of billing cycles.
	 * @param float $trial_amount The amount to charge for the trial period.
	 * @param int $trial_limit The number of trial periods (0 for no trial).
	 *
	 * @return string|false The plan ID or false if the plan ID is not found or created.
	 */
	private static function get_plan_for_product( $product_id, $setup_fee, $amount, $cycle_period, $cycle_number, $trial_amount, $trial_limit ) {
		// Check if we have already created a plan with the same parameters.
		$page = 1;
		while ( true ) {
			// Get a list of plans.
			$response = self::send_request(
				'GET',
				'v1/billing/plans/?' . http_build_query(
					array(
						'product_id' => $product_id,
						'page_size' => 20, // 20 is the max.
						'page' => $page,
					)
				)
			);

			// If we can't get plans, try to create a new one.
			if ( is_string( $response ) ) {
				break;
			}

			// If there are no plans, try to create a new one.
			$plans_summaries = json_decode( $response['body'] )->plans;
			if ( empty( $plans_summaries ) ) {
				break;
			}

			// Check each plan to see if it matches the parameters.
			foreach ( $plans_summaries as $plans_summary ) {
				// Get the full plan details.
				$response = self::send_request(
					'GET',
					'v1/billing/plans/' . $plans_summary->id
				);

				// If we can't get the plan details, try the next plan.
				if ( is_string( $response ) ) {
					continue;
				}

				$plan = json_decode( $response['body'] );

				// Check the initial payment.
				if ( (float) $setup_fee !== (float) $plan->payment_preferences->setup_fee->value ) {
					continue;
				}

				// Find the billing cycle where tenure_type is 'REGULAR'.
				$regular_cycle = null;
				foreach ( $plan->billing_cycles as $billing_cycle ) {
					if ( $billing_cycle->tenure_type === 'REGULAR' ) {
						$regular_cycle = $billing_cycle;
						break;
					}
				}
				if ( $regular_cycle === null ) {
					continue;
				}
				// Check the cycle information.
				if (
					(float) $amount !== (float)$regular_cycle->pricing_scheme->fixed_price->value ||
					$cycle_period !== $regular_cycle->frequency->interval_unit ||
					$cycle_number !== $regular_cycle->frequency->interval_count
				) {
					continue;
				}

				// Check the trial information.
				if ( ! empty( $trial_limit ) ) {
					// Find the billing cycle where tenure_type is 'TRIAL'.
					$trial_cycle = null;
					foreach ( $plan->billing_cycles as $billing_cycle ) {
						if ( $billing_cycle->tenure_type === 'TRIAL' ) {
							$trial_cycle = $billing_cycle;
							break;
						}
					}
					if ( $trial_cycle === null ) {
						continue;
					}
					if (
						(float)$trial_amount !== (float)$trial_cycle->pricing_scheme->fixed_price->value ||
						$trial_limit !== $trial_cycle->frequency->total_cycles ||
						$cycle_period !== $trial_cycle->frequency->interval_unit ||
						$cycle_number !== $trial_cycle->frequency->interval_count
					) {
						continue;
					}
				}

				// If we made it this far, we found a matching plan.
				return $plan->id;
			}
			$page++;
		}

		// We couldn't find a matching plan, so create a new one.
		$billing_cycles = array();
		$sequence = 1;
		if ( ! empty( $trial_amount ) ) {
			$billing_cycles[] = array(
				'frequency' => array(
					'interval_unit' => $cycle_period,
					'interval_count' => $cycle_number,
				),
				'tenure_type' => 'TRIAL',
				'sequence' => $sequence,
				'total_cycles' => $trial_limit,
				'pricing_scheme' => array(
					'fixed_price' => array(
						'value' => $trial_amount,
						'currency_code' => 'USD',
					),
				),
			);
			$sequence++;
		}
		$billing_cycles[] = array(
			'frequency' => array(
				'interval_unit' => $cycle_period,
				'interval_count' => $cycle_number,
			),
			'tenure_type' => 'REGULAR',
			'sequence' => $sequence,
			'total_cycles' => 0, // Run indefinitely.
			'pricing_scheme' => array(
				'fixed_price' => array(
					'value' => (string)$amount,
					'currency_code' => 'USD',
				),
			),
		);
		
		$response = self::send_request(
			'POST',
			'v1/billing/plans',
			array(
				'product_id' => $product_id,
				'name'       => 'Test Plan ' . substr( time(), -4 ),
				'billing_cycles' => $billing_cycles,
				'payment_preferences' => array(
					'auto_bill_outstanding' => true,
					'setup_fee_failure_action' => 'CANCEL',
					'setup_fee' => array(
						'value' => (string)$setup_fee,
						'currency_code' => 'USD',
					),
				),
			)
		);

		if ( is_string( $response ) ) {
			return false;
		}

		return json_decode( $response['body'] )->id;
	}
}

// Everything below here is sample code for generating OAuth connection urls and should be deleted once
// the Stranger Studios server is set up.
if ( ! empty( $_REQUEST['pmpro_get_paypalrest_signup_link'] ) ) {
	$nonce = empty($_REQUEST['nonce']) ? "" : $_REQUEST['nonce'];
	$environment = empty($_REQUEST['environment']) ? "sandbox" : $_REQUEST['environment'];

	// TODO: Get the correct client and secret IDs for the Stranger Studios platform account.
	$platform_client_id = defined( 'PMPRO_PAYPALREST_PLATFORM_CLIENT_ID' ) ? PMPRO_PAYPALREST_PLATFORM_CLIENT_ID : '';
	$platform_client_secret = defined( 'PMPRO_PAYPALREST_PLATFORM_CLIENT_SECRET' ) ? PMPRO_PAYPALREST_PLATFORM_CLIENT_SECRET : '';

	// Get the OAuth link to send the user to.
	$ch = curl_init( ( $environment === 'sandbox' ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com' ) . '/v2/customer/partner-referrals' );
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Content-Type: application/json",
		"Authorization: Basic " . base64_encode( $platform_client_id . ":" . $platform_client_secret )
	]);
	$data = [
		"operations" => [
			[
				"operation" => "API_INTEGRATION",
				"api_integration_preference" => [
					"rest_api_integration" => [
						"integration_method" => "PAYPAL",
						"integration_type" => "FIRST_PARTY",
						"first_party_details" => [
							"features" => ["PAYMENT", "REFUND"],
							"seller_nonce" => $nonce
						]
					]
				]
			]
		],
		"products" => ["EXPRESS_CHECKOUT"],
		"legal_consents" => [
			[
				"type" => "SHARE_DATA_CONSENT",
				"granted" => true
			]
		]
	];
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

	// Execute cURL request and get the response
	$response = curl_exec($ch);

	// Close cURL
	curl_close($ch);

	// If successful, return the link with rel action_url
	if ($response) {
		$response = json_decode($response, true);
		$links = $response['links'];
		foreach ($links as $link) {
			if ($link['rel'] === 'action_url') {
				// Redirect to the PayPal Partner Referrals API link
				header('Location: ' . $link['href']);
				exit;
			}
		}
	} else {
		// TODO: Handle error
	}
}
