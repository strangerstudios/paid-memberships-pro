<?php
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	
	//load classes init method
	add_action('init', array('PMProGateway_check', 'init'));
	
	class PMProGateway_check extends PMProGateway
	{
		function __construct($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										
		
		/**
		 * Run on WP init
		 *		 
		 * @since 1.8
		 */
		static function init()
		{			
			//make sure Pay by Check is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_check', 'pmpro_gateways'));
			
			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_check', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_check', 'pmpro_payment_option_fields'), 10, 2);
			add_filter('pmpro_checkout_after_payment_information_fields', array('PMProGateway_check', 'pmpro_checkout_after_payment_information_fields'));

			//code to add at checkout
			$gateway = pmpro_getGateway();
			if($gateway == "check")
			{
				add_filter('pmpro_include_billing_address_fields', '__return_false');
				add_filter('pmpro_include_payment_information_fields', '__return_false');
				add_filter('pmpro_required_billing_fields', array('PMProGateway_check', 'pmpro_required_billing_fields'));
			}
		}
		
		/**
		 * Make sure Check is in the gateways list
		 *		 
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['check']))
				$gateways['check'] = __('Pay by Check', 'paid-memberships-pro' );
		
			return $gateways;
		}
		
		/**
		 * Get a list of payment options that the Check gateway needs/supports.
		 *		 
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{			
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'instructions',
				'check_gateway_label',
				'currency',
				'use_ssl',
				'tax_state',
				'tax_rate'
			);
			
			return $options;
		}
		
		/**
		 * Set payment options for payment settings page.
		 *		 
		 * @since 1.8
		 */
		static function pmpro_payment_options($options)
		{			
			//get check gateway options
			$check_options = PMProGateway_check::getGatewayOptions();
			
			//merge with others.
			$options = array_merge($check_options, $options);
			
			return $options;
		}
		
		/**
		 * Display fields for Check options.
		 *		 
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
			$check_gateway_label = ! empty( $values['check_gateway_label'] ) ? $values['check_gateway_label'] : __( 'Check', 'paid-memberships-pro' );
		?>
		<tr class="pmpro_settings_divider gateway gateway_check" <?php if($gateway != "check") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2><?php echo esc_html( sprintf( __( 'Pay by %s Settings', 'paid-memberships-pro' ), $check_gateway_label ) ); ?></h2>
			</td>
		</tr>
		<tr class="gateway gateway_check" <?php if($gateway != "check") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="check_gateway_label"><?php esc_html_e( 'Gateway Label', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="check_gateway_label" name="check_gateway_label" class="regular-text code" value="<?php echo esc_attr( $check_gateway_label ); ?>"/>
				<p class="description"><?php esc_html_e('The name of the custom payment method that will show on the frontend of your site. Useful for manual payment methods name like Wire Transfer, Direct Deposit, or Cash. Defaults to "Pay By Check".', 'paid-memberships-pro' );?></p>
			</td>
		</tr>
		<tr class="gateway gateway_check" <?php if($gateway != "check") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="instructions"><?php esc_html_e('Instructions', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<textarea id="instructions" name="instructions" rows="3" cols="50" class="large-text"><?php echo wp_kses_post( wpautop(  wp_unslash( $values['instructions'] ) ) ); ?></textarea>
				<p class="description"><?php echo esc_html( sprintf( __( 'Instructions for members to follow to complete their purchase when paying with %s. Shown on the membership checkout, confirmation, and invoice pages.', 'paid-memberships-pro' ), $check_gateway_label ) );?></p>
			</td>
		</tr>
		<?php
		}
		
		/**
		 * Remove required billing fields
		 *		 
		 * @since 1.8
		 */
		static function pmpro_required_billing_fields($fields)
		{
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
		 * Show instructions on checkout page
		 * Moved here from pages/checkout.php
		 * @since 1.8.9.3
		 */
		static function pmpro_checkout_after_payment_information_fields() {
			global $gateway, $pmpro_level;
			if ( $gateway == 'check' && ! pmpro_isLevelFree( $pmpro_level ) ) {
				$instructions = get_option( 'pmpro_instructions' );
				$check_gateway_label = get_option( 'pmpro_check_gateway_label' );
				?>
				<div id="pmpro_payment_information_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout', 'pmpro_payment_information_fields' ) ); ?>">
					<h2>
						<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-name' ) ); ?>"><?php echo esc_html( sprintf( __( 'Pay by %s', 'paid-memberships-pro' ), $check_gateway_label ) ); ?></span>
					</h2>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_check_instructions', 'pmpro_check_instructions' ) ); ?>">
							<?php echo wp_kses_post( wpautop( wp_unslash( $instructions ) ) ); ?>
						</div> <!-- end pmpro_checkout-field pmpro_check_instructions -->
					</div> <!-- end pmpro_checkout-fields -->
				</div> <!-- end pmpro_payment_information_fields -->
				<?php
			}
		}

		
		/**
		 * Process checkout.
		 *
		 */
		function process(&$order)
		{
			//clean up a couple values
			$order->payment_type = "Check";
			$order->CardType = "";
			$order->cardtype = "";
			
			//check for initial payment
			if(floatval($order->InitialPayment) == 0)
			{
				//auth first, then process
				if($this->authorize($order))
				{						
					$this->void($order);										
					if(!pmpro_isLevelTrial($order->membership_level))
					{
						//subscription will start today with a 1 period trial
						$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");
						$order->TrialBillingPeriod = $order->BillingPeriod;
						$order->TrialBillingFrequency = $order->BillingFrequency;													
						$order->TrialBillingCycles = 1;
						$order->TrialAmount = 0;
						
						//add a billing cycle to make up for the trial, if applicable
						if(!empty($order->TotalBillingCycles))
							$order->TotalBillingCycles++;
					}
					elseif($order->InitialPayment == 0 && $order->TrialAmount == 0)
					{
						//it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
						$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");														
						$order->TrialBillingCycles++;
						
						//add a billing cycle to make up for the trial, if applicable
						if($order->TotalBillingCycles)
							$order->TotalBillingCycles++;
					}
					else
					{
						//add a period to the start date to account for the initial payment
						$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp")));
					}
					
					$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
					return $this->subscribe($order);
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Authorization failed.", 'paid-memberships-pro' );
					return false;
				}
			}
			else
			{
				//charge first payment
				if($this->charge($order))
				{							
					//set up recurring billing					
					if(pmpro_isLevelRecurring($order->membership_level))
					{						
						if(!pmpro_isLevelTrial($order->membership_level))
						{
							//subscription will start today with a 1 period trial
							$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");
							$order->TrialBillingPeriod = $order->BillingPeriod;
							$order->TrialBillingFrequency = $order->BillingFrequency;													
							$order->TrialBillingCycles = 1;
							$order->TrialAmount = 0;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						elseif($order->InitialPayment == 0 && $order->TrialAmount == 0)
						{
							//it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
							$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");														
							$order->TrialBillingCycles++;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						else
						{
							//add a period to the start date to account for the initial payment
							$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp")));
						}
						
						$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
						if($this->subscribe($order))
						{
							$order->status = apply_filters("pmpro_check_status_after_checkout", "success");	//saved on checkout page	
							return true;
						}
						else
						{
							if($this->void($order))
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", 'paid-memberships-pro' );
							}
							else
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", 'paid-memberships-pro' );
								
								$order->error .= " " . __("A partial payment was made that we could not void. Please contact the site owner immediately to correct this.", 'paid-memberships-pro' );
							}
							
							return false;								
						}
					}
					else
					{
						//only a one time charge
						$order->status = apply_filters("pmpro_check_status_after_checkout", "success");	//saved on checkout page											
						return true;
					}
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Payment failed.", 'paid-memberships-pro' );
					
					return false;
				}	
			}	
		}
		
		function authorize(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//simulate a successful authorization
			$order->payment_transaction_id = "CHECK" . $order->code;
			$order->updateStatus("authorized");													
			return true;					
		}
		
		function void(&$order)
		{
			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;
				
			//simulate a successful void
			$order->payment_transaction_id = "CHECK" . $order->code;
			$order->updateStatus("voided");					
			return true;
		}	
		
		function charge(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//simulate a successful charge
			$order->payment_transaction_id = "CHECK" . $order->code;
			$order->updateStatus("success");					
			return true;						
		}
		
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
			
			//simulate a successful subscription processing
			$order->status = "success";		
			$order->subscription_transaction_id = "CHECK" . $order->code;				
			return true;
		}	
		
		function update(&$order)
		{
			//simulate a successful billing update
			return true;
		}
		
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//simulate a successful cancel			
			$order->updateStatus("cancelled");					
			return true;
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

			// Update the subscription's next payment date.
			// If there is a pending order for this subscription, the subscription's next payment date should be the timestamp of the oldest pending order.
			$pending_orders = $subscription->get_orders(
				array(
					'status'  => 'pending',
					'orderby' => '`timestamp` ASC, `id` ASC',
					'limit'   => 1,
				)
			);
			if ( ! empty( $pending_orders ) ) {
				// Get the oldest pending order.
				$oldest_pending_order = current( $pending_orders );

				// Set the next payment date to the timestamp of the oldest pending order.
				$update_array['next_payment_date'] = date_i18n( 'Y-m-d H:i:s', $oldest_pending_order->getTimestamp( true ) );
			} else {
				// If there are no pending orders, the subscription's next payment date should be updated to one payment period after the timestamp of the most recent 'success' order.
				$newest_orders = $subscription->get_orders(
					array(
						'status' => 'success',
						'limit'  => 1
					)
				);
				if ( ! empty( $newest_orders ) ) {
					// Get the most recent order.
					$newest_order = current( $newest_orders );

					// Calculate the next payment date.
					$update_array['next_payment_date'] = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $subscription->get_cycle_number() . ' ' . $subscription->get_cycle_period(), $newest_order->getTimestamp( true ) ) );
				}
			}

			// Update the subscription.
			$subscription->set( $update_array );
		}
	}
