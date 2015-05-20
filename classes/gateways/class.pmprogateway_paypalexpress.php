<?php
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");

	//load classes init method
	add_action('init', array('PMProGateway_paypalexpress', 'init'));

	class PMProGateway_paypalexpress extends PMProGateway
	{
		function PMProGateway_paypalexpress($gateway = NULL)
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
			//make sure PayPal Express is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_paypalexpress', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_paypalexpress', 'pmpro_payment_options'));

			/*
				This code is the same for PayPal Website Payments Pro, PayPal Express, and PayPal Standard
				So we only load it if we haven't already.
			*/
			global $pmpro_payment_option_fields_for_paypal;
			if(empty($pmpro_payment_option_fields_for_paypal))
			{
				add_filter('pmpro_payment_option_fields', array('PMProGateway_paypalexpress', 'pmpro_payment_option_fields'), 10, 2);
				$pmpro_payment_option_fields_for_paypal = true;
			}

			//code to add at checkout
			$gateway = pmpro_getGateway();
			if($gateway == "paypalexpress")
			{
				add_filter('pmpro_include_billing_address_fields', '__return_false');
				add_filter('pmpro_include_payment_information_fields', '__return_false');
				add_filter('pmpro_required_billing_fields', array('PMProGateway_paypalexpress', 'pmpro_required_billing_fields'));
				add_filter('pmpro_checkout_new_user_array', array('PMProGateway_paypalexpress', 'pmpro_checkout_new_user_array'));
				add_filter('pmpro_checkout_confirmed', array('PMProGateway_paypalexpress', 'pmpro_checkout_confirmed'));
				add_action('pmpro_checkout_before_processing', array('PMProGateway_paypalexpress', 'pmpro_checkout_before_processing'));
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalexpress', 'pmpro_checkout_default_submit_button'));
				add_action('pmpro_checkout_after_form', array('PMProGateway_paypalexpress', 'pmpro_checkout_after_form'));
			}
		}

		/**
		 * Make sure this gateway is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['paypalexpress']))
				$gateways['paypalexpress'] = __('PayPal Express', 'pmpro');

			return $gateways;
		}

		/**
		 * Get a list of payment options that the this gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'gateway_email',
				'apiusername',
				'apipassword',
				'apisignature',
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
			//get stripe options
			$paypal_options = PMProGateway_paypalexpress::getGatewayOptions();

			//merge with others.
			$options = array_merge($paypal_options, $options);

			return $options;
		}

		/**
		 * Display fields for this gateway's options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
		?>
		<tr class="pmpro_settings_divider gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard" <?php if($gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<?php _e('PayPal Settings', 'pmpro'); ?>
			</td>
		</tr>
		<tr class="gateway gateway_paypalstandard" <?php if($gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<strong><?php _e('Note', 'pmpro');?>:</strong> <?php _e('We do not recommend using PayPal Standard. We suggest using PayPal Express, Website Payments Pro (Legacy), or PayPal Pro (Payflow Pro). <a target="_blank" href="http://www.paidmembershipspro.com/2013/09/read-using-paypal-standard-paid-memberships-pro/">More information on why can be found here.</a>', 'pmpro');?>
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard" <?php if($gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="gateway_email"><?php _e('Gateway Account Email', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="gateway_email" name="gateway_email" size="60" value="<?php echo esc_attr($values['gateway_email'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apiusername"><?php _e('API Username', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="apiusername" name="apiusername" size="60" value="<?php echo esc_attr($values['apiusername'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apipassword"><?php _e('API Password', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="apipassword" name="apipassword" size="60" value="<?php echo esc_attr($values['apipassword'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apisignature"><?php _e('API Signature', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="apisignature" name="apisignature" size="60" value="<?php echo esc_attr($values['apisignature'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_paypal gateway_paypalexpress gateway_paypalstandard" <?php if($gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php _e('IPN Handler URL', 'pmpro');?>:</label>
			</th>
			<td>
				<p><?php _e('To fully integrate with PayPal, be sure to set your IPN Handler URL to ', 'pmpro');?> <pre><?php echo admin_url("admin-ajax.php") . "?action=ipnhandler";?></pre></p>
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
		 * Save session vars before processing
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_before_processing()
		{
			global $current_user, $gateway;

			//save user fields for PayPal Express
			if(!$current_user->ID)
			{
				//get values from post
				if(isset($_REQUEST['username']))
					$username = trim($_REQUEST['username']);
				else
					$username = "";
				if(isset($_REQUEST['password']))
					$password = $_REQUEST['password'];
				else
					$password = "";
				if(isset($_REQUEST['bemail']))
					$bemail = $_REQUEST['bemail'];
				else
					$bemail = "";

				//save to session
				$_SESSION['pmpro_signup_username'] = $username;
				$_SESSION['pmpro_signup_password'] = $password;
				$_SESSION['pmpro_signup_email'] = $bemail;
			}

			//can use this hook to save some other variables to the session
			do_action("pmpro_paypalexpress_session_vars");
		}

		/**
		 * Review and Confirmation code.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_confirmed($pmpro_confirmed)
		{
			global $pmpro_msg, $pmpro_msgt, $pmpro_level, $current_user, $pmpro_review, $pmpro_paypal_token, $discount_code, $bemail;

			//PayPal Express Call Backs
			if(!empty($_REQUEST['review']))
			{
				if(!empty($_REQUEST['PayerID']))
					$_SESSION['payer_id'] = $_REQUEST['PayerID'];
				if(!empty($_REQUEST['paymentAmount']))
					$_SESSION['paymentAmount'] = $_REQUEST['paymentAmount'];
				if(!empty($_REQUEST['currencyCodeType']))
					$_SESSION['currCodeType'] = $_REQUEST['currencyCodeType'];
				if(!empty($_REQUEST['paymentType']))
					$_SESSION['paymentType'] = $_REQUEST['paymentType'];

				$morder = new MemberOrder();
				$morder->getMemberOrderByPayPalToken($_REQUEST['token']);
				$morder->Token = $morder->paypal_token; $pmpro_paypal_token = $morder->paypal_token;
				if($morder->Token)
				{
					if($morder->Gateway->getExpressCheckoutDetails($morder))
					{
						$pmpro_review = true;
					}
					else
					{
						$pmpro_msg = $morder->error;
						$pmpro_msgt = "pmpro_error";
					}
				}
				else
				{
					$pmpro_msg = __("The PayPal Token was lost.", "pmpro");
					$pmpro_msgt = "pmpro_error";
				}
			}
			elseif(!empty($_REQUEST['confirm']))
			{
				$morder = new MemberOrder();
				$morder->getMemberOrderByPayPalToken($_REQUEST['token']);
				$morder->Token = $morder->paypal_token; $pmpro_paypal_token = $morder->paypal_token;
				if($morder->Token)
				{
					//setup values
					$morder->membership_id = $pmpro_level->id;
					$morder->membership_name = $pmpro_level->name;
					$morder->discount_code = $discount_code;
					$morder->InitialPayment = $pmpro_level->initial_payment;
					$morder->PaymentAmount = $pmpro_level->billing_amount;
					$morder->ProfileStartDate = date("Y-m-d") . "T0:0:0";
					$morder->BillingPeriod = $pmpro_level->cycle_period;
					$morder->BillingFrequency = $pmpro_level->cycle_number;
					$morder->Email = $bemail;

					//setup level var
					$morder->getMembershipLevel();
					$morder->membership_level = apply_filters("pmpro_checkout_level", $morder->membership_level);

					//tax
					$morder->subtotal = $morder->InitialPayment;
					$morder->getTax();
					if($pmpro_level->billing_limit)
						$morder->TotalBillingCycles = $pmpro_level->billing_limit;

					if(pmpro_isLevelTrial($pmpro_level))
					{
						$morder->TrialBillingPeriod = $pmpro_level->cycle_period;
						$morder->TrialBillingFrequency = $pmpro_level->cycle_number;
						$morder->TrialBillingCycles = $pmpro_level->trial_limit;
						$morder->TrialAmount = $pmpro_level->trial_amount;
					}

					if($morder->confirm())
					{
						$pmpro_confirmed = true;
					}
					else
					{
						$pmpro_msg = $morder->error;
						$pmpro_msgt = "pmpro_error";
					}
				}
				else
				{
					$pmpro_msg = __("The PayPal Token was lost.", "pmpro");
					$pmpro_msgt = "pmpro_error";
				}
			}

			if(!empty($morder))
				return array("pmpro_confirmed"=>$pmpro_confirmed, "morder"=>$morder);
			else
				return $pmpro_confirmed;
		}

		/**
		 * Swap in user/pass/etc from session
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_new_user_array($new_user_array)
		{
			global $current_user;

			if(!$current_user->ID)
			{
				//reload the user fields
				$new_user_array['user_login'] = $_SESSION['pmpro_signup_username'];
				$new_user_array['user_pass'] = $_SESSION['pmpro_signup_password'];
				$new_user_array['user_email'] = $_SESSION['pmpro_signup_email'];

				//unset the user fields in session
				unset($_SESSION['pmpro_signup_username']);
				unset($_SESSION['pmpro_signup_password']);
				unset($_SESSION['pmpro_signup_email']);
			}

			return $new_user_array;
		}

		/**
		 * Process at checkout
		 *
		 * Repurposed in v2.0. The old process() method is now confirm().
		 */
		function process(&$order)
		{
			$order->payment_type = "PayPal Express";
			$order->cardtype = "";
			$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod)) . "T0:0:0";
			$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);

			return $this->setExpressCheckout($order);
		}

		/**
		 * Process charge or subscription after confirmation.
		 *
		 * @since 1.8
		 */
		function confirm(&$order)
		{
			if(pmpro_isLevelRecurring($order->membership_level))
			{
				$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
				$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
				return $this->subscribe($order);
			}
			else
				return $this->charge($order);
		}

		/**
		 * Swap in our submit buttons.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_default_submit_button($show)
		{
			global $gateway, $pmpro_requirebilling;

			//show our submit buttons
			?>
			<?php if($gateway == "paypal" || $gateway == "paypalexpress" || $gateway == "paypalstandard") { ?>
			<span id="pmpro_paypalexpress_checkout" <?php if(($gateway != "paypalexpress" && $gateway != "paypalstandard") || !$pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
				<input type="hidden" name="submit-checkout" value="1" />
				<input type="image" value="<?php _e('Check Out with PayPal', 'pmpro');?> &raquo;" src="<?php echo apply_filters("pmpro_paypal_button_image", "https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif");?>" />
			</span>
			<?php } ?>

			<span id="pmpro_submit_span" <?php if(($gateway == "paypalexpress" || $gateway == "paypalstandard") && $pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
				<input type="hidden" name="submit-checkout" value="1" />
				<input type="submit" class="pmpro_btn pmpro_btn-submit-checkout" value="<?php if($pmpro_requirebilling) { _e('Submit and Check Out', 'pmpro'); } else { _e('Submit and Confirm', 'pmpro');}?> &raquo;" />
			</span>
			<?php

			//don't show the default
			return false;
		}

		/**
		 * Scripts for checkout page.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_after_form()
		{
		?>
		<script>
			//choosing payment method
			jQuery('input[name=gateway]').click(function() {
				if(jQuery(this).val() == 'paypal')
				{
					jQuery('#pmpro_paypalexpress_checkout').hide();
					jQuery('#pmpro_billing_address_fields').show();
					jQuery('#pmpro_payment_information_fields').show();
					jQuery('#pmpro_submit_span').show();
				}
				else
				{
					jQuery('#pmpro_billing_address_fields').hide();
					jQuery('#pmpro_payment_information_fields').hide();
					jQuery('#pmpro_submit_span').hide();
					jQuery('#pmpro_paypalexpress_checkout').show();
				}
			});

			//select the radio button if the label is clicked on
			jQuery('a.pmpro_radio').click(function() {
				jQuery(this).prev().click();
			});
		</script>
		<?php
		}

		//PayPal Express, this is run first to authorize from PayPal
		function setExpressCheckout(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//clean up a couple values
			$order->payment_type = "PayPal Express";
			$order->CardType = "";
			$order->cardtype = "";

			//taxes on initial amount
			$initial_payment = $order->InitialPayment;
			$initial_payment_tax = $order->getTaxForPrice($initial_payment);
			$initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);

			//taxes on the amount
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);
			$amount = round((float)$amount + (float)$amount_tax, 2);

			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .="&AMT=" . $initial_payment . "&CURRENCYCODE=" . $pmpro_currency;
			if(!empty($order->ProfileStartDate) && strtotime($order->ProfileStartDate, current_time("timestamp")) > 0)
				$nvpStr .= "&PROFILESTARTDATE=" . $order->ProfileStartDate;
			if(!empty($order->BillingFrequency))
				$nvpStr .= "&BILLINGPERIOD=" . $order->BillingPeriod . "&BILLINGFREQUENCY=" . $order->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling&L_BILLINGTYPE0=RecurringPayments";
			$nvpStr .= "&DESC=" . urlencode(substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127));
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
			$nvpStr .= "&NOSHIPPING=1&L_BILLINGAGREEMENTDESCRIPTION0=" . urlencode(substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127)) . "&L_PAYMENTTYPE0=Any";

			//if billing cycles are defined
			if(!empty($order->TotalBillingCycles))
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $order->TotalBillingCycles;

			//if a trial period is defined
			if(!empty($order->TrialBillingPeriod))
			{
				$trial_amount = $order->TrialAmount;
				$trial_tax = $order->getTaxForPrice($trial_amount);
				$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);

				$nvpStr .= "&TRIALBILLINGPERIOD=" . $order->TrialBillingPeriod . "&TRIALBILLINGFREQUENCY=" . $order->TrialBillingFrequency . "&TRIALAMT=" . $trial_amount;
			}
			if(!empty($order->TrialBillingCycles))
				$nvpStr .= "&TRIALTOTALBILLINGCYCLES=" . $order->TrialBillingCycles;

			if(!empty($order->discount_code))
			{
				$nvpStr .= "&ReturnUrl=" . urlencode(pmpro_url("checkout", "?level=" . $order->membership_level->id . "&discount_code=" . $order->discount_code . "&review=" . $order->code));
			}
			else
			{
				$nvpStr .= "&ReturnUrl=" . urlencode(pmpro_url("checkout", "?level=" . $order->membership_level->id . "&review=" . $order->code));
			}

			$additional_parameters = apply_filters("pmpro_paypal_express_return_url_parameters", array());
			if(!empty($additional_parameters))
			{
				foreach($additional_parameters as $key => $value)
					$nvpStr .= urlencode("&" . $key . "=" . $value);
			}

			$nvpStr .= "&CANCELURL=" . urlencode(pmpro_url("levels"));

			$account_optional = apply_filters('pmpro_paypal_account_optional', true);
            		if ($account_optional)
                		$nvpStr .= '&SOLUTIONTYPE=Sole&LANDINGPAGE=Billing';

			$nvpStr = apply_filters("pmpro_set_express_checkout_nvpstr", $nvpStr, $order);

			///echo str_replace("&", "&<br />", $nvpStr);
			///exit;

			$this->httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->status = "token";
				$order->paypal_token = urldecode($this->httpParsedResponseAr['TOKEN']);

				//update order
				$order->saveOrder();

				//redirect to paypal
				$paypal_url = "https://www.paypal.com/webscr&cmd=_express-checkout&useraction=commit&token=" . $this->httpParsedResponseAr['TOKEN'];
				$environment = pmpro_getOption("gateway_environment");
				if("sandbox" === $environment || "beta-sandbox" === $environment)
				{
					$paypal_url = "https://www.sandbox.paypal.com/webscr&useraction=commit&cmd=_express-checkout&token="  . $this->httpParsedResponseAr['TOKEN'];
				}

				wp_redirect($paypal_url);
				exit;

				//exit('SetExpressCheckout Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}

			//write session?

			//redirect to PayPal
		}

		function getExpressCheckoutDetails(&$order)
		{
			$nvpStr="&TOKEN=".$order->Token;

			$nvpStr = apply_filters("pmpro_get_express_checkout_details_nvpstr", $nvpStr, $order);

			/* Make the API call and store the results in an array.  If the
			call was a success, show the authorization details, and provide
			an action to complete the payment.  If failed, show the error
			*/
			$this->httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->status = "review";

				//update order
				$order->saveOrder();

				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
		}

		function charge(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//taxes on the amount
			$amount = $order->InitialPayment;
			$amount_tax = $order->getTaxForPrice($amount);
			$order->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);

			//paypal profile stuff
			$nvpStr = "";
			if(!empty($order->Token))
				$nvpStr .= "&TOKEN=" . $order->Token;
			$nvpStr .="&AMT=" . $amount . "&CURRENCYCODE=" . $pmpro_currency;
			/*
			if(!empty($amount_tax))
				$nvpStr .= "&TAXAMT=" . $amount_tax;
			*/
			if(!empty($order->BillingFrequency))
				$nvpStr .= "&BILLINGPERIOD=" . $order->BillingPeriod . "&BILLINGFREQUENCY=" . $order->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling";
			$nvpStr .= "&DESC=" . urlencode(substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127));
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
			$nvpStr .= "&NOSHIPPING=1";

			$nvpStr .= "&PAYERID=" . $_SESSION['payer_id'] . "&PAYMENTACTION=sale";

			$nvpStr = apply_filters("pmpro_do_express_checkout_payment_nvpstr", $nvpStr, $order);

			$order->nvpStr = $nvpStr;

			$this->httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->payment_transaction_id = urldecode($this->httpParsedResponseAr['TRANSACTIONID']);
				$order->status = "success";

				//update order
				$order->saveOrder();

				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
		}

		function subscribe(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);

			//taxes on initial amount
			$initial_payment = $order->InitialPayment;
			$initial_payment_tax = $order->getTaxForPrice($initial_payment);
			$initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);

			//taxes on the amount
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);
			//$amount = round((float)$amount + (float)$amount_tax, 2);

			//paypal profile stuff
			$nvpStr = "";
			if(!empty($order->Token))
				$nvpStr .= "&TOKEN=" . $order->Token;
			$nvpStr .="&INITAMT=" . $initial_payment . "&AMT=" . $amount . "&CURRENCYCODE=" . $pmpro_currency . "&PROFILESTARTDATE=" . $order->ProfileStartDate;
			if(!empty($amount_tax))
				$nvpStr .= "&TAXAMT=" . $amount_tax;
			$nvpStr .= "&BILLINGPERIOD=" . $order->BillingPeriod . "&BILLINGFREQUENCY=" . $order->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling";
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
			$nvpStr .= "&DESC=" . urlencode(substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127));

			//if billing cycles are defined
			if(!empty($order->TotalBillingCycles))
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $order->TotalBillingCycles;

			//if a trial period is defined
			if(!empty($order->TrialBillingPeriod))
			{
				$trial_amount = $order->TrialAmount;
				$trial_tax = $order->getTaxForPrice($trial_amount);
				$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);

				$nvpStr .= "&TRIALBILLINGPERIOD=" . $order->TrialBillingPeriod . "&TRIALBILLINGFREQUENCY=" . $order->TrialBillingFrequency . "&TRIALAMT=" . $trial_amount;
			}
			if(!empty($order->TrialBillingCycles))
				$nvpStr .= "&TRIALTOTALBILLINGCYCLES=" . $order->TrialBillingCycles;

			$nvpStr = apply_filters("pmpro_create_recurring_payments_profile_nvpstr", $nvpStr, $order);

			$this->nvpStr = $nvpStr;

			///echo str_replace("&", "&<br />", $nvpStr);
			///exit;

			$this->httpParsedResponseAr = $this->PPHttpPost('CreateRecurringPaymentsProfile', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->status = "success";
				$order->payment_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);
				$order->subscription_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);

				//update order
				$order->saveOrder();

				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

				return false;
			}
		}

		function cancel(&$order)
		{
			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id) . "&ACTION=Cancel&NOTE=" . urlencode("User requested cancel.");

			$nvpStr = apply_filters("pmpro_manage_recurring_payments_profile_status_nvpstr", $nvpStr, $order);

			$this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]))
			{
				$order->updateStatus("cancelled");
				return true;
			}
			else
			{
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']) . ". " . __("Please contact the site owner or cancel your subscription from within PayPal to make sure you are not charged going forward.", "pmpro");
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

				return false;
			}
		}

		function getSubscriptionStatus(&$order)
		{
			if(empty($order->subscription_transaction_id))
				return false;

			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id);

			$nvpStr = apply_filters("pmpro_get_recurring_payments_profile_details_nvpstr", $nvpStr, $order);

			$this->httpParsedResponseAr = $this->PPHttpPost('GetRecurringPaymentsProfileDetails', $nvpStr);

			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]))
			{
				return $this->httpParsedResponseAr;
			}
			else
			{
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

				return false;
			}
		}

		/**
		 * PAYPAL Function
		 * Send HTTP POST Request
		 *
		 * @param	string	The API method name
		 * @param	string	The POST Message fields in &name=value pair format
		 * @return	array	Parsed HTTP Response body
		 */
		function PPHttpPost($methodName_, $nvpStr_) {
			global $gateway_environment;
			$environment = $gateway_environment;

			$API_UserName = pmpro_getOption("apiusername");
			$API_Password = pmpro_getOption("apipassword");
			$API_Signature = pmpro_getOption("apisignature");
			$API_Endpoint = "https://api-3t.paypal.com/nvp";
			if("sandbox" === $environment || "beta-sandbox" === $environment) {
				$API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";
			}

			$version = urlencode('72.0');

			// setting the curl parameters.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);

			// turning off the server and peer verification(TrustManager Concept).
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);

			// NVPRequest for submitting to server
			$nvpreq = "METHOD=" . urlencode($methodName_) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . "&BUTTONSOURCE=" . urlencode(PAYPAL_BN_CODE) . $nvpStr_;

			// setting the nvpreq as POST FIELD to curl
			curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

			// getting response from server
			$httpResponse = curl_exec($ch);

			if(!$httpResponse) {
				exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
			}

			// Extract the RefundTransaction response details
			$httpResponseAr = explode("&", $httpResponse);

			$httpParsedResponseAr = array();
			foreach ($httpResponseAr as $i => $value) {
				$tmpAr = explode("=", $value);
				if(sizeof($tmpAr) > 1) {
					$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
				}
			}

			if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
				exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
			}

			return $httpParsedResponseAr;
		}
	}
