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

		// Allow processing refunds.
		add_filter( 'pmpro_process_refund_paypalrest', array( 'PMProGateway_paypalrest', 'process_refund' ), 10, 2 );

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
					), 'https://connect.paidmembershipspro.com/paypal/v1' );
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
					<?php esc_html_e( 'Webhook URL', 'paid-memberships-pro' ); ?>:
				</th>
				<td>
					<p><code><?php echo esc_html( self::get_site_webhook_url() ); ?></code></p>
				</td>
			</tr>
			<tr class="gateway gateway_paypalrest" <?php if ( ! $display ) { ?>style="display: none;"<?php } ?>>
				<?php
				// Get the current webhook ID.
				$webhook_id = get_option( 'pmpro_paypalrest_webhook_id_' . $environment );

				// Determine which UI to show.
				if ( empty( $webhook_id ) ) {
					// We don't have a webhook set up. Show disconnected status and a checkbox to create a webhook.
					$webhook_status_label = __( 'Webhook Disconnected', 'paid-memberships-pro' );
					$webhook_status_checkbox_small = __( 'Check this box to create a webhook.', 'paid-memberships-pro' );
				} else {
					// Get the webhook object.
					$webhook_object = self::get_webhook( $webhook_id, $environment );
					if ( empty( $webhook_object ) ) {
						// We couldn't get the webhook object. Show an error message.
						$webhook_status_label = __( 'Webhook Does Not Exist', 'paid-memberships-pro' );
						$webhook_status_checkbox_small = __( 'Check this box to create a new webhook.', 'paid-memberships-pro' );
					} else {
						// We have a webhook object. Check the URL and events for the webhook.
						$webhook_events  = array_map( function( $event ) {
							return $event->name;
						}, $webhook_object->event_types );
						$required_events = self::get_required_webhook_events();
						if ( self::get_site_webhook_url() !== $webhook_object->url ) {
							// The webhook URL is incorrect. Show a warning message.
							$webhook_status_label = __( 'Webhook URL Incorrect', 'paid-memberships-pro' );
							$webhook_status_checkbox_small = __( 'Check this box to create a new webhook.', 'paid-memberships-pro' );
						} elseif ( count( array_diff( $required_events, $webhook_events ) ) > 0 ) {
							// The webhook events are incorrect. Show a warning message.
							$webhook_status_label = __( 'Webhook Events Incorrect', 'paid-memberships-pro' );
							$webhook_status_checkbox_small = __( 'Check this box to fix the webhook events.', 'paid-memberships-pro' );
						} else {
							// The webhook is set up correctly. Show a success message.
							$webhook_status_label = __( 'Webhook Connected', 'paid-memberships-pro' );
						}
					}
				}
				?>
				<th scope="row" valign="top">
					<?php
					// If the webhook is not set up correctly, show an error icon.
					echo esc_html( $webhook_status_label );
					if ( ! empty( $webhook_status_checkbox_small ) ) {
						?>
						<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
						<?php
					}
					?>:
				</th>
				<td>
					<?php
					if ( empty( $webhook_status_checkbox_small ) ) {
						?>
						<p><?php esc_html_e( 'Webhook ID', 'paid-memberships-pro' ); ?>: <code><?php echo esc_html( $webhook_id ); ?></code></p>
						<?php
					} else {
						?>
						<input type="checkbox" id="paypalrest_create_webhook_<?php echo esc_attr( $environment ); ?>" name="paypalrest_create_webhook_<?php echo esc_attr( $environment ); ?>" value="1" />
						<p class="description"><?php echo esc_html( $webhook_status_checkbox_small ); ?></p>
						<?php
						// If the site is not https://, show a warning message that PayPal only supports https://.
						if ( 'https' !== parse_url( self::get_site_webhook_url(), PHP_URL_SCHEME ) ) {
							?>
							<p class="description"><?php esc_html_e( 'PayPal only supports HTTPS URLs for webhooks.', 'paid-memberships-pro' ); ?></p>
							<?php
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<?php esc_html_e( 'Webhook Last Received', 'paid-memberships-pro' ); ?>:
				</th>
				<td>
					<?php
					$last_received = get_option( 'pmpro_paypalrest_webhook_last_received_' . $environment );
					if ( empty( $last_received ) ) {
						// We have never received a validated webhook.
						echo esc_html__( 'Never', 'paid-memberships-pro' );
					} else {
						// Output the date in the site date and time format.
						echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_received ) ) );
					}
					?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * When payment settings are saved, process checkboxes.
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

		// Check if the user wants to create a webhook.
		if ( ! empty( $_REQUEST['paypalrest_create_webhook_sandbox'] ) ) {
			self::create_webhook( 'sandbox' );
		}
		if ( ! empty( $_REQUEST['paypalrest_create_webhook_live'] ) ) {
			self::create_webhook( 'live' );
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
				update_pmpro_membership_order_meta( $order->id, 'paypalrest_order_id', $response->id );

				// Find the payer action link and redirect the user to it.
				$links = $response->links;
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

				$plan_id = self::get_plan_for_product( $product_id, $initial_payment_amount, $recurring_payment_amount, $level->cycle_period, $level->cycle_number, $trial_amount, $level->trial_limit, $level->name );
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
					$order->subscription_transaction_id = $response->id;
					$order->saveOrder();

					// Find the approve link and redirect the user to it.
					$links = $response->links;
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
		$paypal_subscription = $response;
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

			$paypal_plan = $response;
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
	 * Refunds an order (only supports full amounts).
	 *
	 * @since TBD
	 *
	 * @param bool $success Status of the refund (default: false).
	 * @param MemberOrder $order The order being refunded.
	 *
	 * @return bool True if the refund was successful, false otherwise.
	 */
	public static function process_refund( $success, $order ) {
		// If we've already somehow processed a refund, bail.
		if ( $success ) {
			return $success;
		}

		// If we don't have a transaction ID, bail.
		if ( empty( $order->payment_transaction_id ) ) {
			return false;
		}
	
		// Send the request to refund the payment.
		$response = self::send_request(
			'POST',
			'v2/payments/captures/' . $order->payment_transaction_id . '/refund',
			array(),
			$order->gateway_environment
		);

		// If we got an error string, save it to order notes.
		if ( is_string( $response ) ) {
			$order->notes .= "\n" . __( 'Error processing refund:', 'paid-memberships-pro' ) . ' ' . $response;
			$order->saveOrder();
			return false;
		}

		// If we got a successful response, set the order status to refunded.
		$order->status = 'refunded';
		$order->saveOrder();
		return true;

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
	 * @return object|string The response from the request or an error message.
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
		return json_decode( $response['body'] );
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
		$product_id = sanitize_text_field( $response->id );
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
	private static function get_plan_for_product( $product_id, $setup_fee, $amount, $cycle_period, $cycle_number, $trial_amount, $trial_limit, $level_name ) {
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
			$plans_summaries = $response->plans;
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

				$plan = $response;

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
				'name'       => substr( $level_name, 0, 127 ),
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

		return $response->id;
	}

	/**
	 * Get a list of webhook events that are required for Paid Memberships Pro.
	 *
	 * @since TBD
	 *
	 * @return array The list of webhook events.
	 */
	private static function get_required_webhook_events() {
		return array(
			'CHECKOUT.ORDER.APPROVED',
			'BILLING.SUBSCRIPTION.ACTIVATED',
			'PAYMENT.SALE.COMPLETED',
			'BILLING.SUBSCRIPTION.SUSPENDED',
			'BILLING.SUBSCRIPTION.CANCELLED',
			'BILLING.SUBSCRIPTION.EXPIRED',
			'PAYMENT.CAPTURE.REFUNDED',
			'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
		);
	}

	/**
	 * Get information about a webhook from PayPal.
	 *
	 * @since TBD
	 *
	 * @param string $webhook_id The ID of the webhook to get information about.
	 * @param string $gateway_environment The environment to use for the request.
	 * @return object|false The webhook object or false if the webhook could not be retrieved.
	 */
	private static function get_webhook( $webhook_id, $gateway_environment ) {
		$response = self::send_request(
			'GET',
			'v1/notifications/webhooks/' . $webhook_id,
			array(),
			$gateway_environment
		);

		if ( is_string( $response ) ) {
			return false;
		}
		return $response;
	}

	/**
	 * List all webhooks from PayPal.
	 *
	 * @since TBD
	 *
	 * @param string $gateway_environment The environment to use for the request.
	 * @return array|false The list of webhook objects or false if the webhooks could not be retrieved.
	 */
	private static function get_all_webhooks( $gateway_environment ) {
		$page = 1;
		$webhooks = array();
		while ( true ) {
			$response = self::send_request(
				'GET',
				'v1/notifications/webhooks/?' . http_build_query(
					array(
						'page_size' => 20, // 20 is the max.
						'page' => $page,
					)
				),
				array(),
				$gateway_environment
			);

			if ( is_string( $response ) ) {
				return false;
			}

			$webhooks = array_merge( $webhooks, $response->webhooks );
			if ( count( $webhooks ) < 20 ) {
				break;
			}
			$page++;
		}

		return $webhooks;
	}

	/**
	 * Create a webhook in PayPal.
	 *
	 * If a webhook with the same URL already exists, fix it and use that one instead.
	 *
	 * @since TBD
	 *
	 * @param string $gateway_environment The environment to use for the request.
	 */
	private static function create_webhook( $gateway_environment ) {
		// Get the webhook URL.
		$webhook_url = self::get_site_webhook_url();

		// Get all webhooks from PayPal.
		$webhooks = self::get_all_webhooks( $gateway_environment );

		// Check if a webhook with the same URL already exists.
		foreach ( $webhooks as $webhook ) {
			if ( $webhook->url === $webhook_url ) {
				// We found a matching webhook. Save the webhook ID.
				update_option( 'pmpro_paypalrest_webhook_id_' . $gateway_environment, $webhook->id );

				// Make sure the webhook has all the required events.
				$events = array_map( function( $event ) {
					return $event->name;
				}, $webhook->event_types );
				$required_events = self::get_required_webhook_events();
				if ( ! empty( array_diff( $required_events, $events ) ) ) {
					$response = self::send_request(
						'PATCH',
						'v1/notifications/webhooks/' . $webhook->id,
						array(
							array(
								'op' => 'replace',
								'path' => '/event_types',
								'value' => array_map( function( $event ) {
									return array( 'name' => $event );
								}, $required_events )
							),
						),
						$gateway_environment
					);
				}

				// Return to avoid creating a new webhook.
				return;
			}
		}

		// Create a new webhook.
		$event_types = array_map( function( $event ) {
			return array( 'name' => $event );
		}, self::get_required_webhook_events() );
		$response = self::send_request(
			'POST',
			'v1/notifications/webhooks',
			array(
				'url' => $webhook_url,
				'event_types' => $event_types,
			),
			$gateway_environment
		);

		// If successful, save the webhook ID.
		if ( ! is_string( $response ) ) {
			update_option( 'pmpro_paypalrest_webhook_id_' . $gateway_environment, $response->id );
		}
	}
}
