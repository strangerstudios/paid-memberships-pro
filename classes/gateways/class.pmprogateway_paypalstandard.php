<?php
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	
	//load classes init method
	add_action('init', array('PMProGateway_paypalstandard', 'init'));
	
	class PMProGateway_paypalstandard extends PMProGateway
	{
		function PMProGateway_paypalstandard($gateway = NULL)
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
			add_filter('pmpro_gateways', array('PMProGateway_paypalstandard', 'pmpro_gateways'));
			
			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_paypalstandard', 'pmpro_payment_options'));
			
			/*
				This code is the same for PayPal Website Payments Pro, PayPal Express, and PayPal Standard
				So we only load it if we haven't already.
			*/
			global $pmpro_payment_option_fields_for_paypal;
			if(empty($pmpro_payment_option_fields_for_paypal))
			{				
				add_filter('pmpro_payment_option_fields', array('PMProGateway_paypalstandard', 'pmpro_payment_option_fields'), 10, 2);						
				$pmpro_payment_option_fields_for_paypal = true;
			}
			
			//code to add at checkout
			$gateway = pmpro_getGateway();
			if($gateway == "paypalstandard")
			{				
				add_filter('pmpro_include_billing_address_fields', '__return_false');
				add_filter('pmpro_include_payment_information_fields', '__return_false');
				add_filter('pmpro_required_billing_fields', array('PMProGateway_paypalstandard', 'pmpro_required_billing_fields'));
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalstandard', 'pmpro_checkout_default_submit_button'));
				add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_paypalstandard', 'pmpro_checkout_before_change_membership_level'), 10, 2);
			}
		}
		
		/**
		 * Make sure this gateway is in the gateways list
		 *		 
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['paypalstandard']))
				$gateways['paypalstandard'] = __('PayPal Standard', 'pmpro');
		
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
				<p><?php _e('Here is your IPN URL for reference. You SHOULD NOT set this in your PayPal settings.', 'pmpro');?> <pre><?php echo admin_url("admin-ajax.php") . "?action=ipnhandler";?></pre></p>
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
		 * Instead of change membership levels, send users to PayPal to pay.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_before_change_membership_level($user_id, $morder)
		{
			global $discount_code_id, $wpdb;
						
			//if no order, no need to pay
			if(empty($morder))
				return;
							
			$morder->user_id = $user_id;				
			$morder->saveOrder();
			
			//save discount code use
			if(!empty($discount_code_id))
				$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $user_id . "', '" . $morder->id . "', now())");	
			
			do_action("pmpro_before_send_to_paypal_standard", $user_id, $morder);
			
			$morder->Gateway->sendToPayPal($morder);
		}
		
		/**
		 * Process checkout.
		 *		
		 */
		function process(&$order)
		{						
			if(empty($order->code))
				$order->code = $order->getRandomCode();			
			
			//clean up a couple values
			$order->payment_type = "PayPal Standard";
			$order->CardType = "";
			$order->cardtype = "";
			
			//just save, the user will go to PayPal to pay
			$order->status = "review";														
			$order->saveOrder();

			return true;			
		}
		
		function sendToPayPal(&$order)
		{						
			global $pmpro_currency;			
			
			//taxes on initial amount
			$initial_payment = $order->InitialPayment;
			$initial_payment_tax = $order->getTaxForPrice($initial_payment);
			$initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);
			
			//taxes on the amount
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);			
			$amount = round((float)$amount + (float)$amount_tax, 2);			
			
			//build PayPal Redirect	
			$environment = pmpro_getOption("gateway_environment");
			if("sandbox" === $environment || "beta-sandbox" === $environment)
				$paypal_url ="https://www.sandbox.paypal.com/cgi-bin/webscr?business=" . urlencode(pmpro_getOption("gateway_email"));
			else
				$paypal_url = "https://www.paypal.com/cgi-bin/webscr?business=" . urlencode(pmpro_getOption("gateway_email"));
			
			if(pmpro_isLevelRecurring($order->membership_level))
			{				
				//convert billing period
				if($order->BillingPeriod == "Day")
					$period = "D";
				elseif($order->BillingPeriod == "Week")
					$period = "W";
				elseif($order->BillingPeriod == "Month")
					$period = "M";
				elseif($order->BillingPeriod == "Year")
					$period = "Y";				
				else
				{
					$order->error = "Invalid billing period: " . $order->BillingPeriod;
					$order->shorterror = "Invalid billing period: " . $order->BillingPeriod;
					return false;
				}
				
				//other args
				$paypal_args = array( 
					'cmd'           => '_xclick-subscriptions', 
					'a1'			=> number_format($initial_payment, 2),
					'p1'			=> $order->BillingFrequency,
					't1'			=> $period,
					'a3'			=> number_format($amount, 2),
					'p3'			=> $order->BillingFrequency,
					't3'			=> $period,
					'item_name'     => substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127),
					'email'         => $order->Email, 
					'no_shipping'   => '1', 
					'shipping'      => '0',
					'no_note'       => '1', 
					'currency_code' => $pmpro_currency, 
					'item_number'   => $order->code, 
					'charset'       => get_bloginfo( 'charset' ), 				
					'rm'            => '2', 
					'return'        => pmpro_url("confirmation", "?level=" . $order->membership_level->id),
					'notify_url'    => admin_url("admin-ajax.php") . "?action=ipnhandler",
					'src'			=> '1',
					'sra'			=> '1',
					'bn'			=> PAYPAL_BN_CODE
				);					
								
				//trial?
				/*
					Note here that the TrialBillingCycles value is being ignored. PayPal Standard only offers 1 payment during each trial period.
				*/
				if(!empty($order->TrialBillingPeriod))
				{					
					//if a1 and a2 are 0, let's just combine them. PayPal doesn't like a2 = 0.
					if($paypal_args['a1'] == 0 && $order->TrialAmount == 0)
					{
						$paypal_args['p1'] = $paypal_args['p1'] + $order->TrialBillingFrequency;
					}
					else
					{
						$trial_amount = $order->TrialAmount;
						$trial_tax = $order->getTaxForPrice($trial_amount);
						$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);
						
						$paypal_args['a2'] = $trial_amount;
						$paypal_args['p2'] = $order->TrialBillingFrequency;
						$paypal_args['t2'] = $period;
					}
				}
				else
				{
					//we can try to work in any change in ProfileStartDate
					$psd = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
					$adjusted_psd = apply_filters("pmpro_profile_start_date", $psd, $order);
					if($psd != $adjusted_psd)
					{
						//someone is trying to push the start date back
						$adjusted_psd_time = strtotime($adjusted_psd, current_time("timestamp"));
						$seconds_til_psd = $adjusted_psd_time - current_time('timestamp');
						$days_til_psd = floor($seconds_til_psd/(60*60*24));
						
						//push back trial one by days_til_psd
						if($days_til_psd > 90)
						{
							//we need to convert to weeks, because PayPal limits t1 to 90 days
							$weeks_til_psd = round($days_til_psd / 7);
							$paypal_args['p1'] = $weeks_til_psd;
							$paypal_args['t1'] = "W";
						}
						elseif($days_til_psd > 0)
						{							
							//use days
							$paypal_args['p1'] = $days_til_psd;
							$paypal_args['t1'] = "D";
						}																
					}
				}
				
				//billing limit?
				if(!empty($order->TotalBillingCycles))
				{
					if(!empty($trial_amount))
					{
						
						$srt = intval($order->TotalBillingCycles) - 1;	//subtract one for the trial period					
					}
					else
					{
						$srt = intval($order->TotalBillingCycles);						
					}
					
					//srt must be at least 2 or the subscription is not "recurring" according to paypal
					if($srt > 1)
						$paypal_args['srt'] = $srt;
					else
						$paypal_args['src'] = '0';
				}
				else
					$paypal_args['srt'] = '0';	//indefinite subscription
			}
			else
			{
				//other args
				$paypal_args = array( 
					'cmd'           => '_xclick', 
					'amount'        => number_format($initial_payment, 2), 				
					'item_name'     => substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127),
					'email'         => $order->Email, 
					'no_shipping'   => '1', 
					'shipping'      => '0',
					'no_note'       => '1', 
					'currency_code' => $pmpro_currency, 
					'item_number'   => $order->code, 
					'charset'       => get_bloginfo( 'charset' ), 				
					'rm'            => '2', 
					'return'        => pmpro_url("confirmation", "?level=" . $order->membership_level->id),
					'notify_url'    => admin_url("admin-ajax.php") . "?action=ipnhandler",
					'bn'			=> PAYPAL_BN_CODE
				 );	
			}						
			
			$nvpStr = "";
			foreach($paypal_args as $key => $value)
			{
				$nvpStr .= "&" . $key . "=" . urlencode($value);
			}
			
			//anything modders might add
			$additional_parameters = apply_filters("pmpro_paypal_express_return_url_parameters", array());									
			if(!empty($additional_parameters))
			{
				foreach($additional_parameters as $key => $value)				
					$nvpStr .= urlencode("&" . $key . "=" . $value);
			}
			
			$account_optional = apply_filters('pmpro_paypal_account_optional', true);
            		if ($account_optional)
                		$nvpStr .= '&SOLUTIONTYPE=Sole&LANDINGPAGE=Billing';

			$nvpStr = apply_filters("pmpro_paypal_standard_nvpstr", $nvpStr, $order);
			
			//redirect to paypal			
			$paypal_url .= $nvpStr;			
			
			//wp_die(str_replace("&", "<br />", $paypal_url));
			
			wp_redirect($paypal_url);
			exit;
		}
										
		function cancel(&$order)
		{			
			//paypal profile stuff
			$nvpStr = "";			
			$nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id) . "&ACTION=Cancel&NOTE=" . urlencode("User requested cancel.");
			
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
			$nvpreq = "METHOD=" . urlencode($methodName_) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . "&bn=" . urlencode(PAYPAL_BN_CODE) . $nvpStr_;
						
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
