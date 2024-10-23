<?php
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");

	//load classes init method
	add_action('init', array('PMProGateway_payflowpro', 'init'));

	class PMProGateway_payflowpro extends PMProGateway
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
			//make sure Payflow Pro/PayPal Pro is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_payflowpro', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_payflowpro', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_payflowpro', 'pmpro_payment_option_fields'), 10, 2);
		}

		/**
		 * Make sure this gateway is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['payflowpro']))
				$gateways['payflowpro'] = __('Payflow Pro/PayPal Pro', 'paid-memberships-pro' );

			return $gateways;
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
				'subscription_sync' => false,
				'payment_method_updates' => 'individual'
			);

			if ( empty( $supports[$feature] ) ) {
				return false;
			}

			return $supports[$feature];
		}

		/**
		 * Get a list of payment options that the this gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'gateway_environment',
				'payflow_partner',
				'payflow_vendor',
				'payflow_user',
				'payflow_pwd',
				'currency',
				'tax_state',
				'tax_rate',
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
			$payflowpro_options = PMProGateway_payflowpro::getGatewayOptions();

			//merge with others.
			$options = array_merge($payflowpro_options, $options);

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
		<tr class="pmpro_settings_divider gateway gateway_payflowpro" <?php if($gateway != "payflowpro") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<hr />
				<h2 class="title"><?php esc_html_e( 'Payflow Pro Settings', 'paid-memberships-pro' ); ?></h2>
			</td>
		</tr>
		<tr class="gateway gateway_payflowpro" <?php if($gateway != "payflowpro") { ?>style="display: none;"<?php } ?>>
		    <th scope="row" valign="top">
				<label for="payflow_partner"><?php esc_html_e('Partner', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="payflow_partner" name="payflow_partner" value="<?php echo esc_attr($values['payflow_partner'])?>" class="regular-text code" />
			</td>
	    </tr>
	    <tr class="gateway gateway_payflowpro" <?php if($gateway != "payflowpro") { ?>style="display: none;"<?php } ?>>
		    <th scope="row" valign="top">
				<label for="payflow_vendor"><?php esc_html_e('Vendor', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="payflow_vendor" name="payflow_vendor" value="<?php echo esc_attr($values['payflow_vendor'])?>" class="regular-text code" />
			</td>
	    </tr>
	    <tr class="gateway gateway_payflowpro" <?php if($gateway != "payflowpro") { ?>style="display: none;"<?php } ?>>
		    <th scope="row" valign="top">
				<label for="payflow_user"><?php esc_html_e('User', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="text" id="payflow_user" name="payflow_user" value="<?php echo esc_attr($values['payflow_user'])?>" class="regular-text code" />
			</td>
	    </tr>
	    <tr class="gateway gateway_payflowpro" <?php if($gateway != "payflowpro") { ?>style="display: none;"<?php } ?>>
		    <th scope="row" valign="top">
				<label for="payflow_pwd"><?php esc_html_e('Password', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<input type="password" id="payflow_pwd" name="payflow_pwd" value="<?php echo esc_attr($values['payflow_pwd'])?>" class="regular-text code" />
			</td>
	    </tr>
		<tr class="gateway gateway_payflowpro" <?php if($gateway != "payflowpro") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php esc_html_e('IPN Handler', 'paid-memberships-pro' );?></label>
			</th>
			<td>
				<p class="description">
				<?php
					$allowed_message_html = array (
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
						),
					);
					echo sprintf( wp_kses( __( 'Payflow does not use IPN. To sync recurring subscriptions, please see the <a target="_blank" href="%s" title="the Payflow Recurring Orders Add On">Payflow Recurring Orders Add On</a>.', 'paid-memberships-pro' ), $allowed_message_html ), 'https://www.paidmembershipspro.com/add-ons/payflow-recurring-orders-addon/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=add-ons&utm_content=payflow-recurring-orders-addon' );
				?>
				</p>
			</td>
		</tr>
		<?php
		}

		/**
		 * Process checkout.
		 *
		 */
		function process(&$order)
		{
			if(floatval($order->subtotal) == 0)
			{
				//auth first, then process
				$authorization_id = $this->authorize($order);
				if($authorization_id)
				{
					$this->void($order, $authorization_id);
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
						if($this->subscribe($order))
						{
							return true;
						}
						else
						{
							if($this->void($order, $order->payment_transaction_id))
							{
								if(empty($order->error))
									$order->error = __("Unknown error: Payment failed.", 'paid-memberships-pro' );
							}
							else
							{
								if(empty($order->error))
									$order->error = __("Unknown error: Payment failed.", 'paid-memberships-pro' );

								$order->error .= " " . __("A partial payment was made that we could not refund. Please contact the site owner immediately to correct this.", 'paid-memberships-pro' );
							}

							return false;
						}
					}
					else
					{
						//only a one time charge
						$order->status = "success";	//saved on checkout page
						$order->saveOrder();
						return true;
					}
				}
			}
		}

		function authorize(&$order)
		{
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//paypal profile stuff
			$nvpStr = "";

			$nvpStr .="&AMT=1.00";

			$nvpStr .= "&CUSTIP=" . pmpro_get_ip() . "&INVNUM=" . $order->code;

			//credit card fields
			if($order->cardtype == "American Express")
				$cardtype = "Amex";
			else
				$cardtype = $order->cardtype;

			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . ( empty( $_REQUEST['CVV'] ) ? '' : sanitize_text_field( $_REQUEST['CVV'] ) );

			//billing address, etc
			if(!empty($order->billing->street))
			{
				$user = get_userdata($order->user_id);
				$nameparts = pnp_split_full_name( $order->billing->name );
				$fname = empty( $nameparts['fname'] ) ? '' : $nameparts['fname'];
				$lname = empty( $nameparts['lname'] ) ? '' : $nameparts['lname'];
				$email = empty( $user->user_email ) ? '' : $user->user_email;
				$nvpStr .= "&EMAIL=" . $email . "&FIRSTNAME=" . $fname . "&LASTNAME=" . $lname . "&STREET=" . $order->billing->street;

				if($order->billing->street2)
					$nvpStr .= " " . $order->billing->street2;

				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

			/**
			 * Filter NVP string
			 *
			 * @since 1.8.5.6
			 */
			$nvpStr = apply_filters('pmpro_payflow_authorize_nvpstr', $nvpStr, $this);

			//for debugging, let's attach this to the class object
			$this->nvpStr = $nvpStr;

			$this->httpParsedResponseAr = $this->PPHttpPost('A', $nvpStr);

			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"])) {
				$order->authorization_id = $this->httpParsedResponseAr['PNREF'];
				$order->updateStatus("authorized");
				return $order->authorization_id;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['RESULT'];
				$order->error = urldecode($this->httpParsedResponseAr['RESPMSG']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['RESPMSG']);
				return false;
			}
		}

		function void(&$order, $authorization_id = null)
		{
			if(empty($authorization_id))
				return false;

			//paypal profile stuff
			$nvpStr="&ORIGID=" . $authorization_id;

			/**
			 * Filter NVP string
			 *
			 * @since 1.8.5.6
			 */
			$nvpStr = apply_filters('pmpro_payflow_void_nvpstr', $nvpStr, $this);

			$this->httpParsedResponseAr = $this->PPHttpPost('V', $nvpStr);

			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"])) {
				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['RESULT'];
				$order->error = urldecode($this->httpParsedResponseAr['RESPMSG']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['RESPMSG']);
				return false;
			}
		}

		function charge(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//taxes on the amount
			$amount_tax = $order->getTax(true);
			$amount = pmpro_round_price_as_string((float)$order->subtotal + (float)$amount_tax);

			//paypal profile stuff
			$nvpStr = "";

			// Only add CARDONFILE for initial charge if it's recurring.
			if ( pmpro_isLevelRecurring( $order->membership_level ) ) {
				/*
				 * Card on File is now required.
				 *
				 * CITR: The customer just performed an action to make the transaction.
				 * MITR: The customer passively approved during CITR to make this subsequent (recurring) transaction.
				 *
				 * @link https://developer.paypal.com/docs/payflow/integration-guide/card-on-file/
				 */
				$nvpStr .= "&CARDONFILE=CITR";
			}

			$nvpStr .="&AMT=" . $amount . "&TAXAMT=" . $amount_tax . "&CURRENCY=" . $pmpro_currency;

			$nvpStr .= "&CUSTIP=" . pmpro_get_ip() . "&INVNUM=" . $order->code;

			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . ( empty( $_REQUEST['CVV'] ) ? '' : sanitize_text_field( $_REQUEST['CVV'] ) );

			//billing address, etc
			if($order->billing->street)
			{
				$nameparts = pnp_split_full_name( $order->billing->name );
				$fname = empty( $nameparts['fname'] ) ? '' : $nameparts['fname'];
				$lname = empty( $nameparts['lname'] ) ? '' : $nameparts['lname'];
				$user = get_userdata($order->user_id);
				$email = empty( $user->user_email ) ? '' : $user->user_email;
				$nvpStr .= "&EMAIL=" . $email . "&FIRSTNAME=" . $fname . "&LASTNAME=" . $lname . "&STREET=" . $order->billing->street;

				if($order->billing->street2)
					$nvpStr .= " " . $order->billing->street2;

				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

			/**
			 * Filter NVP string
			 *
			 * @since 1.8.5.6
			 */
			$nvpStr = apply_filters('pmpro_payflow_charge_nvpstr', $nvpStr, $this);

			$this->nvpStr = $nvpStr;
			$this->httpParsedResponseAr = $this->PPHttpPost('S', $nvpStr);

			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"])) {
				$order->payment_transaction_id = $this->httpParsedResponseAr['PNREF'];
				$order->updateStatus("success");
				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['RESULT'];
				$order->error = urldecode($this->httpParsedResponseAr['RESPMSG']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['RESPMSG']);
				return false;
			}
		}

		function subscribe(&$order)
		{
			global $pmpro_currency;

			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);

			//taxes on the amount
			$level = $order->getMembershipLevelAtCheckout();
			$amount = $level->billing_amount;
			$amount_tax = $order->getTaxForPrice($amount);
			$amount = pmpro_round_price_as_string((float)$amount + (float)$amount_tax);

			if( $level->cycle_period == "Day")
				$payperiod = "DAYS";
			elseif( $level->cycle_period == "Week")
				$payperiod = "WEEK";
			elseif( $level->cycle_period == "Month")
				$payperiod = "MONT";
			elseif( $level->cycle_period == "Year")
				$payperiod = "YEAR";

			//paypal profile stuff
			$nvpStr = "&ACTION=A";

			/*
			 * Card on File is now required.
			 *
			 * CITR: The customer just performed an action to make the transaction.
			 * MITR: The customer passively approved during CITR to make this subsequent (recurring) transaction.
			 *
			 * @link https://developer.paypal.com/docs/payflow/integration-guide/card-on-file/
			 */
			$nvpStr .="&CARDONFILE=CITR";

			$nvpStr .="&AMT=" . $amount . "&TAXAMT=" . $amount_tax . "&CURRENCY=" . $pmpro_currency;

			$nvpStr .= "&PROFILENAME=" . urlencode( apply_filters( 'pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")) );

			$nvpStr .= "&PAYPERIOD=" . $payperiod;
			$nvpStr .= "&FREQUENCY=" . $level->cycle_number;

			$nvpStr .= "&CUSTIP=" . pmpro_get_ip(); // . "&INVNUM=" . $order->code;

			//if billing cycles are defined
			if(!empty($level->billing_limit))
				$nvpStr .= "&TERM=" . $level->billing_limit;
			else
				$nvpStr .= "&TERM=0";

			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . ( empty( $_REQUEST['CVV'] ) ? '' : sanitize_text_field( $_REQUEST['CVV'] ) );

			// Get the profile start date.
			$trial_period_days = ceil(abs(strtotime(date_i18n("Y-m-d"), current_time('timestamp')) - pmpro_calculate_profile_start_date( $order, 'U' ) ) / 86400);

			//now add the actual trial set by the site
			if(!empty($level->trial_limit))
			{
				$trialOccurrences = (int)$level->trial_limit;
				if( $level->cycle_period == "Year")
					$trial_period_days = $trial_period_days + (365 * $level->cycle_number * $trialOccurrences);	//annual
				elseif( $level->cycle_period == "Day")
					$trial_period_days = $trial_period_days + (1 * $level->cycle_number * $trialOccurrences);		//daily
				elseif( $level->cycle_period == "Week")
					$trial_period_days = $trial_period_days + (7 * $level->cycle_number * $trialOccurrences);	//weekly
				else
					$trial_period_days = $trial_period_days + (30 * $level->cycle_number * $trialOccurrences);	//assume monthly
			}

			//convert back into a date
			$profile_start_date = date_i18n("Y-m-d\TH:i:s", strtotime("+ " . $trial_period_days . " Day", current_time("timestamp")));

			//start date
			$nvpStr .= "&START=" . date_i18n("mdY", strtotime($profile_start_date));

			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . ( empty( $_REQUEST['CVV'] ) ? '' : sanitize_text_field( $_REQUEST['CVV'] ) );

			//billing address, etc
			if($order->billing->street)
			{
				$nameparts = pnp_split_full_name( $order->billing->name );
				$fname = empty( $nameparts['fname'] ) ? '' : $nameparts['fname'];
				$lname = empty( $nameparts['lname'] ) ? '' : $nameparts['lname'];
				$user = get_userdata($order->user_id);
				$email = empty( $user->user_email ) ? '' : $user->user_email;
				$nvpStr .= "&EMAIL=" . $email . "&FIRSTNAME=" . $fname . "&LASTNAME=" . $lname . "&STREET=" . $order->billing->street;

				if($order->billing->street2)
					$nvpStr .= " " . $order->billing->street2;

				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

			/**
			 * Filter NVP string
			 *
			 * @since 1.8.5.6
			 */
			$nvpStr = apply_filters('pmpro_payflow_subscribe_nvpstr', $nvpStr, $this);

			$this->nvpStr = $nvpStr;
			$this->httpParsedResponseAr = $this->PPHttpPost('R', $nvpStr);

			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"])) {
				$order->subscription_transaction_id = $this->httpParsedResponseAr['PROFILEID'];
				$order->status = "success";
				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['RESULT'];
				$order->error = urldecode($this->httpParsedResponseAr['RESPMSG']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['RESPMSG']);
				return false;
			}
		}

		function update(&$order)
		{
			$order->getMembershipLevel();

			//paypal profile stuff
			$nvpStr = "&ORIGPROFILEID=" . $order->subscription_transaction_id . "&ACTION=M";

			/*
			 * Card on File is now required.
			 *
			 * CITR: The customer just performed an action to make the transaction.
			 * MITR: The customer passively approved during CIT to make this subsequent (recurring) transaction.
			 *
			 * @link https://developer.paypal.com/docs/payflow/integration-guide/card-on-file/
			 */
			$nvpStr .= "&CARDONFILE=CITR";

			/* PayFlow Pro doesn't use IPN so this is a little confusing */
			// $nvpStr .= "&NOTIFYURL=" . urlencode( add_query_arg( 'action', 'ipnhandler', admin_url('admin-ajax.php') ) );

			$nvpStr .= "&PROFILENAME=" . urlencode( apply_filters( 'pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")) );

			$nvpStr .= "&CUSTIP=" . pmpro_get_ip(); // . "&INVNUM=" . $order->code;

			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . ( empty( $_REQUEST['CVV'] ) ? '' : sanitize_text_field( $_REQUEST['CVV'] ) );

			//billing address, etc
			if($order->billing->street)
			{
				$nameparts = pnp_split_full_name( $order->billing->name );
				$fname = empty( $nameparts['fname'] ) ? '' : $nameparts['fname'];
				$lname = empty( $nameparts['lname'] ) ? '' : $nameparts['lname'];
				$user = get_userdata($order->user_id);
				$email = empty( $user->user_email ) ? '' : $user->user_email;
				$nvpStr .= "&EMAIL=" . $email . "&FIRSTNAME=" . $fname . "&LASTNAME=" . $lname . "&STREET=" . $order->billing->street;

				if($order->billing->street2)
					$nvpStr .= " " . $order->billing->street2;

				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

			/**
			 * Filter NVP string
			 *
			 * @since 1.8.5.6
			 */
			$nvpStr = apply_filters('pmpro_payflow_update_nvpstr', $nvpStr, $this);

			$this->nvpStr = $nvpStr;
			$this->httpParsedResponseAr = $this->PPHttpPost('R', $nvpStr);

			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"])) {
				$order->subscription_transaction_id = $this->httpParsedResponseAr['PROFILEID'];
				$order->updateStatus("success");
				return true;
			} else  {
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['RESULT'];
				$order->error = urldecode($this->httpParsedResponseAr['RESPMSG']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['RESPMSG']);
				return false;
			}
		}

		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;

			//paypal profile stuff
			$nvpStr = "&ORIGPROFILEID=" . $order->subscription_transaction_id . "&ACTION=C";

			/**
			 * Filter NVP string
			 *
			 * @since 1.8.5.6
			 */
			$nvpStr = apply_filters('pmpro_payflow_cancel_nvpstr', $nvpStr, $this);

			$this->nvpStr = $nvpStr;
			$this->httpParsedResponseAr = $this->PPHttpPost('R', $nvpStr);

			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"]))
			{
				$order->updateStatus("cancelled");
				return true;
			}
			else
			{
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['RESULT'];
				$order->error = urldecode($this->httpParsedResponseAr['RESPMSG']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['RESPMSG']);
				return false;
			}
		}

		/**
		 * PAYPAL Function
		 * Send HTTP POST Request
		 *
		 * @param	string	$methodName_    The API method name
		 * @param	string	$nvpStr_         The POST Message fields in &name=value pair format
		 * @return	array	Parsed HTTP Response body
		 */
		function PPHttpPost($methodName_, $nvpStr_) {
			global $gateway_environment;
			$environment = $gateway_environment;

			$PARTNER = get_option("pmpro_payflow_partner");
			$VENDOR = get_option("pmpro_payflow_vendor");
			$USER = get_option("pmpro_payflow_user");
			$PWD = get_option("pmpro_payflow_pwd");
			$API_Endpoint = "https://payflowpro.paypal.com";
			if("sandbox" === $environment || "beta-sandbox" === $environment) {
				$API_Endpoint = "https://pilot-payflowpro.paypal.com";
			}

			$version = urlencode('4');

			// NVPRequest for submitting to server
			$nvpreq = "TRXTYPE=" . $methodName_ . "&TENDER=C&PARTNER=" . $PARTNER . "&VENDOR=" . $VENDOR . "&USER=" . $USER . "&PWD=" . $PWD . "&VERBOSITY=medium" . "&BUTTONSOURCE=" . urlencode(PAYPAL_BN_CODE) . $nvpStr_;

			//post to PayPal
			$response = wp_remote_post( $API_Endpoint, array(
					'timeout' => 60,
					'sslverify' => FALSE,
					'httpversion' => '1.1',
					'body' => $nvpreq
			    )
			);

			$httpParsedResponseAr = array();

			if ( is_wp_error( $response ) ) {
			   $error_message = $response->get_error_message();
			   wp_die( esc_html( "{$methodName_} failed: $error_message" ) );
			} else {
				//extract the response details
				parse_str(wp_remote_retrieve_body($response), $httpParsedResponseAr);

				//check for valid response
				if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('RESULT', $httpParsedResponseAr)) {
					exit( esc_html( "Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.") );
				}
			}

			return $httpParsedResponseAr;
		}
	}
