<?php
	//load classes init method
	add_action('init', array('PMProGateway_paypalsmart', 'init'));

	/**
	 * PMProGateway_gatewayname Class
	 *
	 * Handles example integration.
	 *
	 */
    require_once(dirname(__FILE__) . "/../../includes/lib/PayPalCheckoutSdk/vendor/autoload.php");
    use PayPalCheckoutSdk\Core\PayPalHttpClient;
    use PayPalCheckoutSdk\Core\ProductionEnvironment;
    use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
    use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

	class PMProGateway_paypalsmart extends PMProGateway
	{
		function PMProGateway($gateway = NULL)
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
			//make sure example is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_paypalsmart', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_paypalsmart', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_paypalsmart', 'pmpro_payment_option_fields'), 10, 2);

			//add some fields to edit user page
			add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_paypalsmart', 'user_profile_fields'));
			add_action('profile_update', array('PMProGateway_paypalsmart', 'user_profile_fields_save'));

			//updates cron
			add_action('pmpro_activation', array('PMProGateway_paypalsmart', 'pmpro_activation'));
			add_action('pmpro_deactivation', array('PMProGateway_paypalsmart', 'pmpro_deactivation'));
			add_action('pmpro_cron_example_subscription_updates', array('PMProGateway_paypalsmart', 'pmpro_cron_example_subscription_updates'));

			//code to add at checkout if example is the current gateway
			$gateway = pmpro_getOption("gateway");
			if($gateway == "paypalsmart")
			{
				add_action('pmpro_checkout_preheader', array('PMProGateway_paypalsmart', 'pmpro_checkout_preheader'));
                add_filter('pmpro_include_billing_address_fields', '__return_false');
                add_filter('pmpro_include_payment_information_fields', '__return_false');
				add_filter('pmpro_checkout_order', array('PMProGateway_paypalsmart', 'pmpro_checkout_order'));
				add_filter('pmpro_required_billing_fields', array('PMProGateway_paypalsmart', 'pmpro_required_billing_fields'));
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalsmart', 'pmpro_checkout_default_submit_button'));
				//add_filter('pmpro_include_billing_address_fields', array('PMProGateway_paypalsmart', 'pmpro_include_billing_address_fields'));
				//add_filter('pmpro_include_cardtype_field', array('PMProGateway_paypalsmart', 'pmpro_include_billing_address_fields'));
				//add_filter('pmpro_include_payment_information_fields', array('PMProGateway_paypalsmart', 'pmpro_include_payment_information_fields'));
			}
		}

		/**
		 * Make sure example is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['paypalsmart']))
				$gateways['paypalsmart'] = __('PayPal Smart', 'sb0');

			return $gateways;
		}

		/**
		 * Get a list of payment options that the example gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'paypal_client_id',
				'paypal_client_secret',
				'currency',
				'use_ssl',
				'tax_state',
				'tax_rate',
				'accepted_credit_cards'
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
			//get example options
			$example_options = PMProGateway_paypalsmart::getGatewayOptions();

			//merge with others.
			$options = array_merge($example_options, $options);

			return $options;
		}

		/**
		 * Display fields for example options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
		?>
		<tr class="pmpro_settings_divider gateway gateway_paypalsmart" <?php if($gateway != "example") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<?php _e('PayPal Smart Settings', 'sb0'); ?>
			</td>
		</tr>
		<tr class="gateway gateway_paypalsmart" <?php if($gateway != "paypalsmart") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="paypal_client_id"><?php _e('Client ID', 'sb0' );?>:</label>
			</th>
			<td>
				<input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo esc_attr($values['paypal_client_id'])?>" class="regular-text code" />
			</td>
		</tr>
		<tr class="gateway gateway_paypalsmart" <?php if($gateway != "paypalsmart") { ?>style="display: none;"<?php } ?>>
        			<th scope="row" valign="top">
        				<label for="paypal_client_secret"><?php _e('Client Secret', 'sb0' );?>:</label>
        			</th>
        			<td>
        				<input type="text" id="paypal_client_secret" name="paypal_client_secret" value="<?php echo esc_attr($values['paypal_client_secret'])?>" class="regular-text code" />
        			</td>
        		</tr>
		<?php
		}

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
		 * Filtering orders at checkout.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_order($morder)
		{
        	$morder->intent = $_POST['intent'];
        	if (isset($_POST['orderID']))
        	    $morder->orderID = $_POST['orderID'];
			return $morder;
		}


        static function pmpro_checkout_preheader()
        {
            wp_register_script( 'paypalsmart', 'https://www.paypal.com/sdk/js?client-id='.pmpro_getOption('paypal_client_id'), null, null );
            wp_register_script( 'jquery_validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/jquery.validate.min.js', null, null );
            wp_enqueue_script( 'paypalsmart' );
            wp_enqueue_script( 'jquery_validate' );
        }

        static function pmpro_checkout_default_submit_button($show)
        {
            global $gateway, $pmpro_requirebilling;

            //show our submit buttons
            ?>
            <div id="paypal-button-container"></div>

              <script>
              jQuery(() => {
                    // Initialize form validation on the registration form.
                // It has the name attribute "registration"
               jQuery("form#pmpro_form").validate({
                  // Specify validation rules
                  rules: {
                    // The key name on the left side is the name attribute
                    // of an input field. Validation rules are defined
                    // on the right side
                    username: 'required', // TODO: add alphanumeric validation
                    first_name: 'required',
                    last_name: 'required',
                    bemail: {
                      required: true,
                      // Specify that email should be validated
                      // by the built-in "email" rule
                      email: true
                    },
                    password: {
                      required: true,
                      minlength: 5
                    },
                    password2: {
                      equalTo: '#password'
                    },
                    bconfirmemail: {
                        equalTo: '#bemail'
                    }
                  },
                  // Specify validation error messages
                  messages: {
                    username: 'יש להזין שם משתמש',
                    first_name: "יש להזין שם פרטי",
                    last_name: "יש להזין שם משפחה",
                    password: {
                      required: "יש לבחור ססמה",
                      minlength: "הססמה חייבת להיות לפחות 5 אותיות"
                    },
                    password2: 'הססמאות חייבות להיות זהות',
                    bemail: "יש להזין כתובת אימייל תקינה",
                    bconfirmemail: "יש להזין כתובת אימייל תקינה"
                  },
                  // Make sure the form is submitted to the destination defined
                  // in the "action" attribute of the form when valid
                  submitHandler: function(form) {
                    return true;
                  }
                });
              })
                function validate_form() {
                    return jQuery("form#pmpro_form").valid();
                }
                const form_data = new URLSearchParams();
                paypal.Buttons({
                    env: 'sandbox', // Or 'production'
                    createOrder: (data, actions) => {

                        for (const pair of new FormData(document.getElementById('pmpro_form'))) {
                            form_data.append(pair[0], pair[1]);
                        }
                        form_data.append('javascriptok', '1');
                        form_data.append('submit-checkout', '1');
                        form_data.append('intent', 'CREATE');

                        return fetch(location.href, {
                            method: 'post',
                            body: form_data,
                        }).then(function(res) {
                            return res.json();
                          }).then(function(inner_data) {
                            return inner_data.orderID; // Use the same key name for order ID on the client and server
                          });
                    },
                    onClick: (data, actions) => validate_form() ? actions.resolve() : actions.reject(),
                    onApprove: (data, actions) => {
                        jQuery('#pmpro_user_fields').addClass('loader');
                        form_data.append('orderID', data.orderID);
                        form_data.set('intent', 'CAPTURE');
                        return fetch(location.href, {
                                                    method: 'post',
                                                    body: form_data,
                                                }).then(function(res) {
                                                    location.href = res.url;
                                                  });
                    }
                }).render('#paypal-button-container');
                // This function displays Smart Payment Buttons on your web page.
              </script>
            <?php

            //don't show the default
            return false;
        }
		/**
		 * Code to run after checkout
		 *
		 * @since 1.8
		 */
		static function pmpro_after_checkout($user_id, $morder)
		{
		}

		/**
		 * Use our own payment fields at checkout. (Remove the name attributes.)
		 * @since 1.8
		 */
		static function pmpro_include_payment_information_fields($include)
		{
		}

		/**
		 * Fields shown on edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields($user)
		{
		}

		/**
		 * Process fields from the edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields_save($user_id)
		{
		}

		/**
		 * Cron activation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_activation()
		{
			wp_schedule_event(time(), 'daily', 'pmpro_cron_example_subscription_updates');
		}

		/**
		 * Cron deactivation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_deactivation()
		{
			wp_clear_scheduled_hook('pmpro_cron_example_subscription_updates');
		}

		/**
		 * Cron job for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_cron_example_subscription_updates()
		{
		}


		function process(&$order)
		{
            $this->client = new PayPalHttpClient(new ProductionEnvironment(pmpro_getOption('paypal_client_id'), pmpro_getOption('paypal_client_secret')));
            return $order->intent === 'CREATE' ? $this->create($order) : $this->charge($order);
            /*
			//check for initial payment
			if(floatval($order->InitialPayment) == 0)
			{
				//auth first, then process
				if($this->authorize($order))
				{
					$this->void($order);
					if(!pmpro_isLevelTrial($order->membership_level))
					{
						//subscription will start today with a 1 period trial (initial payment charged separately)
						$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
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
						$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
						$order->TrialBillingCycles++;

						//add a billing cycle to make up for the trial, if applicable
						if($order->TotalBillingCycles)
							$order->TotalBillingCycles++;
					}
					else
					{
						//add a period to the start date to account for the initial payment
						$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
					}

					$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
					return $this->subscribe($order);
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Authorization failed.", "pmpro");
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
							$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
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
							$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
							$order->TrialBillingCycles++;

							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						else
						{
							//add a period to the start date to account for the initial payment
							$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
						}

						$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
						if($this->subscribe($order))
						{
							return true;
						}
						else
						{
							if($this->void($order))
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", "pmpro");
							}
							else
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", "pmpro");

								$order->error .= " " . __("A partial payment was made that we could not void. Please contact the site owner immediately to correct this.", "pmpro");
							}

							return false;
						}
					}
					else
					{
						//only a one time charge
						$order->status = "success";	//saved on checkout page
						return true;
					}
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Payment failed.", "pmpro");

					return false;
				}
			}*/
		}
        function create(&$order)
        {
            global $pmpro_currency;
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                                         'intent' => 'CAPTURE',
                                         'purchase_units' =>
                                             [
                                                     [
                                                         'amount' => [
                                                                 'currency_code' => $pmpro_currency,
                                                                 'value' => $order->InitialPayment
                                                             ]
                                                     ]
                                             ]
                                    ];

            $response = $this->client->execute($request);
            echo json_encode(['orderID' => $response->result->id], true);
            die();
        }

		/*
			Run an authorization at the gateway.
			Required if supporting recurring subscriptions
			since we'll authorize $1 for subscriptions
			with a $0 initial payment.
		*/
		function authorize(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//code to authorize with gateway and test results would go here

			//simulate a successful authorization
			$order->payment_transaction_id = "PAYPAL_SMART_" . $order->code;
			$order->updateStatus("authorized");
			return false;
		}

		/*
			Void a transaction at the gateway.
			Required if supporting recurring transactions
			as we void the authorization test on subs
			with a $0 initial payment and void the initial
			payment if subscription setup fails.
		*/
		function void(&$order)
		{
			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;

			//code to void an order at the gateway and test results would go here

			//simulate a successful void
			$order->payment_transaction_id = "PAYPAL_SMART_" . $order->code;
			$order->updateStatus("voided");
			return true;
		}

		/*
			Make a charge at the gateway.
			Required to charge initial payments.
		*/
		function charge(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

            $request = new OrdersCaptureRequest($order->orderID);
            $response = $this->client->execute($request);

			//code to charge with gateway and test results would go here

			//simulate a successful charge
			$order->payment_transaction_id = "PAYPAL_SMART_" . $order->code;
			$order->updateStatus("success");
			return true;
		}

		/*
			Setup a subscription at the gateway.
			Required if supporting recurring subscriptions.
		*/
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);

			//code to setup a recurring subscription with the gateway and test results would go here

			//simulate a successful subscription processing
			$order->status = "success";
			$order->subscription_transaction_id = "PAYPAL_SMART_" . $order->code;
			return true;
		}

		/*
			Update billing at the gateway.
			Required if supporting recurring subscriptions and
			processing credit cards on site.
		*/
		function update(&$order)
		{
			//code to update billing info on a recurring subscription at the gateway and test results would go here

			//simulate a successful billing update
			return true;
		}

		/*
			Cancel a subscription at the gateway.
			Required if supporting recurring subscriptions.
		*/
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;

			//code to cancel a subscription at the gateway and test results would go here

			//simulate a successful cancel
			$order->updateStatus("cancelled");
			return true;
		}

		/*
			Get subscription status at the gateway.
			Optional if you have code that needs this or
			want to support addons that use this.
		*/
		function getSubscriptionStatus(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;

			//code to get subscription status at the gateway and test results would go here

			//this looks different for each gateway, but generally an array of some sort
			return array();
		}

		/*
			Get transaction status at the gateway.
			Optional if you have code that needs this or
			want to support addons that use this.
		*/
		function getTransactionStatus(&$order)
		{
			//code to get transaction status at the gateway and test results would go here

			//this looks different for each gateway, but generally an array of some sort
			return array();
		}
	}