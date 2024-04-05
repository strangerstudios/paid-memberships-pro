<?php
//include pmprogateway
require_once(dirname(__FILE__) . "/class.pmprogateway.php");

//load classes init method
add_action('init', array('PMProGateway_authorizenet', 'init'));

class PMProGateway_authorizenet extends PMProGateway
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
		//make sure Authorize.net is a gateway option
		add_filter('pmpro_gateways', array('PMProGateway_authorizenet', 'pmpro_gateways'));

		//add fields to payment settings
		add_filter('pmpro_payment_options', array('PMProGateway_authorizenet', 'pmpro_payment_options'));
		add_filter('pmpro_payment_option_fields', array('PMProGateway_authorizenet', 'pmpro_payment_option_fields'), 10, 2);

		add_filter('pmpro_checkout_order', array('PMProGateway_authorizenet', 'pmpro_checkout_order'));
		add_filter('pmpro_billing_order', array('PMProGateway_authorizenet', 'pmpro_checkout_order'));

	}

	static function pmpro_checkout_order( $morder ) {

		if ( isset( $_REQUEST['CVV'] ) ) {
			$authorizenet_cvv = sanitize_text_field( $_REQUEST['CVV'] );
		} else {
			$authorizenet_cvv = '';
		}

		$morder->CVV2 = $authorizenet_cvv;
		return $morder;
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
			'payment_method_updates' => 'individual'
		);

		if ( empty( $supports[$feature] ) ) {
			return false;
		}

		return $supports[$feature];
	}

	/**
	 * Make sure this gateway is in the gateways list
	 *
	 * @since 1.8
	 */
	static function pmpro_gateways($gateways)
	{
		if(empty($gateways['authorizenet']))
			$gateways['authorizenet'] = __('Authorize.net', 'paid-memberships-pro' );

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
			'loginname',
			'transactionkey',
			'currency',
			'use_ssl',
			'tax_state',
			'tax_rate',
			'accepted_credit_cards',
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
		$authorizenet_options = PMProGateway_authorizenet::getGatewayOptions();

		//merge with others.
		$options = array_merge($authorizenet_options, $options);

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
	<tr class="pmpro_settings_divider gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
		<td colspan="2">
			<hr />
			<h2 class="title"><?php esc_html_e('Authorize.net Settings', 'paid-memberships-pro' ); ?></h2>
		</td>
	</tr>
	<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
		<th scope="row" valign="top">
			<label for="loginname"><?php esc_html_e('Login Name', 'paid-memberships-pro' );?></label>
		</th>
		<td>
			<input type="text" id="loginname" name="loginname" value="<?php echo esc_attr($values['loginname'])?>" class="regular-text code" />
		</td>
	</tr>
	<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
		<th scope="row" valign="top">
			<label for="transactionkey"><?php esc_html_e('Transaction Key', 'paid-memberships-pro' );?></label>
		</th>
		<td>
			<input type="text" id="transactionkey" name="transactionkey" value="<?php echo esc_attr($values['transactionkey'])?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
		</td>
	</tr>
	<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
		<th scope="row" valign="top">
			<label><?php esc_html_e('Silent Post URL', 'paid-memberships-pro' );?></label>
		</th>
		<td>
			<p><?php esc_html_e('To fully integrate with Authorize.net, be sure to set your Silent Post URL to', 'paid-memberships-pro' ); ?></p>
			<p><code><?php echo esc_url( admin_url("admin-ajax.php") . "?action=authnet_silent_post" );?></code></p>
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
		//check for initial payment
		if(floatval($order->InitialPayment) == 0)
		{
			//auth first, then process
			if($this->authorize($order))
			{
				$this->void($order);
				$order->ProfileStartDate = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s' );
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
					$order->ProfileStartDate = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s' );
					if($this->subscribe($order))
					{
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
					$order->status = "success";	//saved on checkout page
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
		if(empty($order->code))
			$order->code = $order->getRandomCode();

		if(empty($order->gateway_environment))
			$gateway_environment = get_option("pmpro_gateway_environment");
		else
			$gateway_environment = $order->gateway_environment;
		if($gateway_environment == "live")
				$host = "secure.authorize.net";
			else
				$host = "test.authorize.net";

		$path = "/gateway/transact.dll";
		$post_url = "https://" . $host . $path;

		$post_url = apply_filters("pmpro_authorizenet_post_url", $post_url, $gateway_environment);

		//what amount to authorize? just $1 to test
		$amount = "1.00";

		//combine address
		$address = $order->Address1;
		if(!empty($order->Address2))
			$address .= "\n" . $order->Address2;

		//customer stuff
		$customer_email = $order->Email;
		$customer_phone = $order->billing->phone;

		if(!isset($order->membership_level->name))
			$order->membership_level->name = "";

		$post_values = array(

			// the API Login ID and Transaction Key must be replaced with valid values
			"x_login"			=> get_option("pmpro_loginname"),
			"x_tran_key"		=> get_option("pmpro_transactionkey"),

			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",

			"x_type"			=> "AUTH_ONLY",
			"x_method"			=> "CC",
			"x_card_type"		=> $order->cardtype,
			"x_card_num"		=> $order->accountnumber,
			"x_exp_date"		=> $order->ExpirationDate,

			"x_amount"			=> $amount,
			"x_description"		=> apply_filters( 'pmpro_authorizenet_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")),

			"x_first_name"		=> $order->FirstName,
			"x_last_name"		=> $order->LastName,
			"x_address"			=> $address,
			"x_city"			=> $order->billing->city,
			"x_state"			=> $order->billing->state,
			"x_zip"				=> $order->billing->zip,
			"x_country"			=> $order->billing->country,
			"x_invoice_num"		=> $order->code,
			"x_phone"			=> $customer_phone,
			"x_email"			=> $order->Email
			// Additional fields can be added here as outlined in the AIM integration
			// guide at: http://developer.authorize.net
		);

		if(!empty($order->CVV2))
			$post_values["x_card_code"] = $order->CVV2;

		$post_string = $this->build_post_string( $post_values, 'authorize' );

		//curl
		$request = curl_init($post_url); // initiate curl object
			curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
			curl_setopt($request, CURLOPT_USERAGENT, PMPRO_USER_AGENT); // setting the user agent
			$post_response = curl_exec($request); // execute curl post and store results in $post_response
			// additional options may be required depending upon your server configuration
			// you can find documentation on curl options at http://www.php.net/curl_setopt
		curl_close ($request); // close curl object

		// This line takes the response and breaks it into an array using the specified delimiting character
		$response_array = explode($post_values["x_delim_char"],$post_response);

		if($response_array[0] == 1)
		{
			$order->payment_transaction_id = $response_array[6];
			$order->updateStatus("authorized");

			return true;
		}
		else
		{
			//$order->status = "error";
			$order->errorcode = $response_array[2];
			$order->error = $response_array[3];
			$order->shorterror = $response_array[3];
			return false;
		}
	}

	function void(&$order)
	{
		if(empty($order->payment_transaction_id))
			return false;

		if(empty($order->gateway_environment))
			$gateway_environment = get_option("pmpro_gateway_environment");
		else
			$gateway_environment = $order->gateway_environment;
		if($gateway_environment == "live")
			$host = "secure.authorize.net";
		else
			$host = "test.authorize.net";

		$path = "/gateway/transact.dll";
		$post_url = "https://" . $host . $path;

		$post_url = apply_filters("pmpro_authorizenet_post_url", $post_url, $gateway_environment);

		$post_values = array(

			// the API Login ID and Transaction Key must be replaced with valid values
			"x_login"			=> get_option("pmpro_loginname"),
			"x_tran_key"		=> get_option("pmpro_transactionkey"),

			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",

			"x_type"			=> "VOID",
			"x_trans_id"			=> $order->payment_transaction_id
			// Additional fields can be added here as outlined in the AIM integration
			// guide at: http://developer.authorize.net
		);

		$post_string = $this->build_post_string( $post_values, 'void' );

		//curl
		$request = curl_init($post_url); // initiate curl object
			curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
			$post_response = curl_exec($request); // execute curl post and store results in $post_response
			// additional options may be required depending upon your server configuration
			// you can find documentation on curl options at http://www.php.net/curl_setopt
		curl_close ($request); // close curl object

		// This line takes the response and breaks it into an array using the specified delimiting character
		$response_array = explode($post_values["x_delim_char"],$post_response);
		if($response_array[0] == 1)
		{
			$order->payment_transaction_id = $response_array[4];
			$order->updateStatus("voided");
			return true;
		}
		else
		{
			//$order->status = "error";
			$order->errorcode = $response_array[2];
			$order->error = $response_array[3];
			$order->shorterror = $response_array[3];
			return false;
		}
	}

	function charge(&$order)
	{
		if(empty($order->code))
			$order->code = $order->getRandomCode();

		if(!empty($order->gateway_environment))
			$gateway_environment = $order->gateway_environment;
		if(empty($gateway_environment))
			$gateway_environment = get_option("pmpro_gateway_environment");
		if($gateway_environment == "live")
			$host = "secure.authorize.net";
		else
			$host = "test.authorize.net";

		$path = "/gateway/transact.dll";
		$post_url = "https://" . $host . $path;

		$post_url = apply_filters("pmpro_authorizenet_post_url", $post_url, $gateway_environment);

		//what amount to charge?
		$amount = $order->InitialPayment;

		//tax
		$order->subtotal = $amount;
		$tax = $order->getTax(true);
		$amount = pmpro_round_price_as_string((float)$order->subtotal + (float)$tax);

		//combine address
		$address = $order->Address1;
		if(!empty($order->Address2))
			$address .= "\n" . $order->Address2;

		//customer stuff
		$customer_email = $order->Email;
		$customer_phone = $order->billing->phone;

		if(!isset($order->membership_level->name))
			$order->membership_level->name = "";

		$post_values = array(

			// the API Login ID and Transaction Key must be replaced with valid values
			"x_login"			=> get_option("pmpro_loginname"),
			"x_tran_key"		=> get_option("pmpro_transactionkey"),

			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",

			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_card_type"		=> $order->cardtype,
			"x_card_num"		=> $order->accountnumber,
			"x_exp_date"		=> $order->ExpirationDate,

			"x_amount"			=> $amount,
			"x_tax"				=> $tax,
			"x_description"		=> apply_filters( 'pmpro_authorizenet_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name")),

			"x_first_name"		=> $order->FirstName,
			"x_last_name"		=> $order->LastName,
			"x_address"			=> $address,
			"x_city"			=> $order->billing->city,
			"x_state"			=> $order->billing->state,
			"x_zip"				=> $order->billing->zip,
			"x_country"			=> $order->billing->country,
			"x_invoice_num"		=> $order->code,
			"x_phone"			=> $customer_phone,
			"x_email"			=> $order->Email

			// Additional fields can be added here as outlined in the AIM integration
			// guide at: http://developer.authorize.net
		);


		if(!empty($order->CVV2) ) {
			$post_values["x_card_code"] = $order->CVV2;
		}

		$post_string = $this->build_post_string( $post_values, 'charge' );

		//curl
		$request = curl_init($post_url); // initiate curl object
			curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
			$post_response = curl_exec($request); // execute curl post and store results in $post_response
			// additional options may be required depending upon your server configuration
			// you can find documentation on curl options at http://www.php.net/curl_setopt
		curl_close ($request); // close curl object

		// This line takes the response and breaks it into an array using the specified delimiting character
		$response_array = explode($post_values["x_delim_char"],$post_response);
		if($response_array[0] == 1)
		{
			$order->payment_transaction_id = $response_array[6];
			$order->updateStatus("success");
			return true;
		}
		else
		{
			//$order->status = "error";
			$order->errorcode = $response_array[2];
			$order->error = $response_array[3];
			$order->shorterror = $response_array[3];
			return false;
		}
	}

	function subscribe(&$order)
	{
		//define variables to send

		if(empty($order->code))
			$order->code = $order->getRandomCode();

		//filter order before subscription. use with care.
		$order = apply_filters("pmpro_subscribe_order", $order, $this);

		if(!empty($order->gateway_environment))
			$gateway_environment = $order->gateway_environment;
		if(empty($gateway_environment))
			$gateway_environment = get_option("pmpro_gateway_environment");
		if($gateway_environment == "live")
				$host = "api.authorize.net";
			else
				$host = "apitest.authorize.net";

		$path = "/xml/v1/request.api";

		$loginname = get_option("pmpro_loginname");
		$transactionkey = get_option("pmpro_transactionkey");

		$amount = $order->PaymentAmount;
		$refId = $order->code;
		$name = $order->membership_name;
		$length = (int)$order->BillingFrequency;

		if($order->BillingPeriod == "Month")
			$unit = "months";
		elseif($order->BillingPeriod == "Day")
			$unit = "days";
		elseif($order->BillingPeriod == "Year" && $order->BillingFrequency == 1)
		{
			$unit = "months";
			$length = 12;
		}
		elseif($order->BillingPeriod == "Week")
		{
			$unit = "days";
			$length = $length * 7;	//converting weeks to days
		}
		else
			return false;	//authorize.net only supports months and days

		$startDate = substr($order->ProfileStartDate, 0, 10);
		if(!empty($order->TotalBillingCycles))
			$totalOccurrences = (int)$order->TotalBillingCycles;
		if(empty($totalOccurrences))
			$totalOccurrences = 9999;
		if(isset($order->TrialBillingCycles))
			$trialOccurrences = (int)$order->TrialBillingCycles;
		else
			$trialOccurrences = 0;
		if(isset($order->TrialAmount))
			$trialAmount = $order->TrialAmount;
		else
			$trialAmount = NULL;

		//taxes
		$amount_tax = $order->getTaxForPrice($amount);
		$trial_tax = $order->getTaxForPrice($trialAmount);

		$amount = pmpro_round_price_as_string((float)$amount + (float)$amount_tax);
		$trialAmount = pmpro_round_price_as_string((float)$trialAmount + (float)$trial_tax);

		//authorize.net doesn't support different periods between trial and actual

		if(!empty($order->TrialBillingPeriod) && $order->TrialBillingPeriod != $order->BillingPeriod)
		{
			return false;
		}

		$cardNumber = $order->accountnumber;
		$expirationDate = $order->ExpirationDate_YdashM;
		$cardCode = $order->CVV2;

		$firstName = $order->FirstName;
		$lastName = $order->LastName;

		//do address stuff then?
		$address = $order->Address1;
		if(!empty($order->Address2))
			$address .= "\n" . $order->Address2;
		$city = $order->billing->city;
		$state = $order->billing->state;
		$zip = $order->billing->zip;
		$country = $order->billing->country;

		//customer stuff
		$customer_email = $order->Email;
		if(strpos($order->billing->phone, "+") === false)
			$customer_phone = $order->billing->phone;
		else
			$customer_phone = "";

		//make sure the phone is in an okay format
		$customer_phone = preg_replace("/[^0-9]/", "", $customer_phone);
		if(strlen($customer_phone) > 10)
			$customer_phone = "";

		//build xml to post
		$this->content =
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
				"<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
				"<merchantAuthentication>".
				"<name>" . $loginname . "</name>".
				"<transactionKey>" . $transactionkey . "</transactionKey>".
				"</merchantAuthentication>".
				"<refId><![CDATA[" . substr($refId, 0, 20) . "]]></refId>".
				"<subscription>".
				"<name><![CDATA[" . substr($name, 0, 50) . "]]></name>".
				"<paymentSchedule>".
				"<interval>".
				"<length>". $length ."</length>".
				"<unit>". $unit ."</unit>".
				"</interval>".
				"<startDate>" . $startDate . "</startDate>".
				"<totalOccurrences>". $totalOccurrences . "</totalOccurrences>";
		if(!empty($trialOccurrences))
			$this->content .=
				"<trialOccurrences>". $trialOccurrences . "</trialOccurrences>";
		$this->content .=
				"</paymentSchedule>".
				"<amount>". $amount ."</amount>";
		if(!empty($trialOccurrences))
			$this->content .=
				"<trialAmount>" . $trialAmount . "</trialAmount>";
		$this->content .=
				"<payment>".
				"<creditCard>".
				"<cardNumber>" . $cardNumber . "</cardNumber>".
				"<expirationDate>" . $expirationDate . "</expirationDate>";
		if(!empty($cardCode))
			$this->content .= "<cardCode>" . $cardCode . "</cardCode>";
		$this->content .=
				"</creditCard>".
				"</payment>".
				"<order><invoiceNumber>" . substr($order->code, 0, 20) . "</invoiceNumber></order>".
				"<customer>".
				"<email>". substr($customer_email, 0, 255) . "</email>".
				"<phoneNumber>". substr($customer_phone, 0, 25) . "</phoneNumber>".
				"</customer>".
				"<billTo>".
				"<firstName><![CDATA[". substr($firstName, 0, 50) . "]]></firstName>".
				"<lastName><![CDATA[" . substr($lastName, 0, 50) . "]]></lastName>".
				"<address><![CDATA[". substr($address, 0, 60) . "]]></address>".
				"<city><![CDATA[" . substr($city, 0, 40) . "]]></city>".
				"<state>". substr($state, 0, 2) . "</state>".
				"<zip>" . substr($zip, 0, 20) . "</zip>".
				"<country>". substr($country, 0, 60) . "</country>".
				"</billTo>".
				"</subscription>".
				"</ARBCreateSubscriptionRequest>";

		//send the xml via curl
		$this->response = $this->send_request_via_curl($host,$path,$this->content);
		//if curl is unavilable you can try using fsockopen
		/*
		$response = send_request_via_fsockopen($host,$path,$content);
		*/

		if(!empty($this->response)) {
			list ($refId, $resultCode, $code, $text, $subscriptionId) = $this->parse_return($this->response);
			if($resultCode == "Ok")
			{
				$order->status = "success";	//saved on checkout page
				$order->subscription_transaction_id = $subscriptionId;
				return true;
			}
			else
			{
				$order->status = "error";
				$order->errorcode = $code;
				$order->error = $text;
				$order->shorterror = $text;
				return false;
			}
		} else  {
			$order->status = "error";
			$order->error = "Could not connect to Authorize.net";
			$order->shorterror = "Could not connect to Authorize.net";
			return false;
		}
	}

	function update(&$order)
	{
		//define variables to send
		$gateway_environment = $order->gateway_environment;
		if(empty($gateway_environment))
			$gateway_environment = get_option("pmpro_gateway_environment");
		if($gateway_environment == "live")
				$host = "api.authorize.net";
			else
				$host = "apitest.authorize.net";

		$path = "/xml/v1/request.api";

		$loginname = get_option("pmpro_loginname");
		$transactionkey = get_option("pmpro_transactionkey");

		//$amount = $order->PaymentAmount;
		$refId = $order->code;
		$subscriptionId = $order->subscription_transaction_id;

		$cardNumber = $order->accountnumber;
		$expirationDate = $order->ExpirationDate_YdashM;
		$cardCode = $order->CVV2;

		$firstName = $order->FirstName;
		$lastName = $order->LastName;

		//do address stuff then?
		$address = $order->Address1;
		if(!empty($order->Address2))
			$address .= "\n" . $order->Address2;
		$city = $order->billing->city;
		$state = $order->billing->state;
		$zip = $order->billing->zip;
		$country = $order->billing->country;

		//customer stuff
		$customer_email = $order->Email;
		if(strpos($order->billing->phone, "+") === false)
			$customer_phone = $order->billing->phone;


		//build xml to post
		$this->content =
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
				"<ARBUpdateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
				"<merchantAuthentication>".
				"<name><![CDATA[" . $loginname . "]]></name>".
				"<transactionKey>" . $transactionkey . "</transactionKey>".
				"</merchantAuthentication>".
				"<refId>" . substr($refId, 0, 20) . "</refId>".
				"<subscriptionId>" . $subscriptionId . "</subscriptionId>".
				"<subscription>".
				"<payment>".
				"<creditCard>".
				"<cardNumber>" . $cardNumber . "</cardNumber>".
				"<expirationDate>" . $expirationDate . "</expirationDate>";
		if(!empty($cardCode))
			$this->content .= "<cardCode>" . $cardCode . "</cardCode>";
		$this->content .=
				"</creditCard>".
				"</payment>".
				"<customer>".
				"<email>". substr($customer_email, 0, 255) . "</email>".
				"<phoneNumber>". substr(str_replace("1 (", "(", formatPhone($customer_phone)), 0, 25) . "</phoneNumber>".
				"</customer>".
				"<billTo>".
				"<firstName><![CDATA[". substr($firstName, 0, 50) . "]]></firstName>".
				"<lastName><![CDATA[" . substr($lastName, 0, 50) . "]]></lastName>".
				"<address><![CDATA[". substr($address, 0, 60) . "]]></address>".
				"<city><![CDATA[" . substr($city, 0, 40) . "]]></city>".
				"<state><![CDATA[". substr($state, 0, 2) . "]]></state>".
				"<zip>" . substr($zip, 0, 20) . "</zip>".
				"<country>". substr($country, 0, 60) . "</country>".
				"</billTo>".
				"</subscription>".
				"</ARBUpdateSubscriptionRequest>";

		//send the xml via curl
		$this->response = $this->send_request_via_curl($host,$path,$this->content);
		//if curl is unavilable you can try using fsockopen
		/*
		$response = send_request_via_fsockopen($host,$path,$order->content);
		*/


		if(!empty($this->response)) {
			list ($resultCode, $code, $text, $subscriptionId) = $this->parse_return($this->response);

			if($resultCode == "Ok" || $code == "Ok")
			{
				// Subscription successfully updated. Let's also set the new payment profile to be default.
				$customer_profile_id = intval( $this->substring_between($this->response, "<customerProfileId>", "</customerProfileId>") );
				$payment_profile_id  = intval( $this->substring_between($this->response, "<customerPaymentProfileId>", "</customerPaymentProfileId>") );
				if ( ! empty( $customer_profile_id ) && ! empty( $payment_profile_id ) ) {
					sleep(10); // Wait for the subscription to be updated. 5 seemed to be too quick, 10 seems to be enough.

					$this->content =
						"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
						"<updateCustomerPaymentProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
						"<merchantAuthentication>".
						"<name><![CDATA[" . $loginname . "]]></name>".
						"<transactionKey>" . $transactionkey . "</transactionKey>".
						"</merchantAuthentication>".
						"<customerProfileId>" . $customer_profile_id . "</customerProfileId>".
						"<paymentProfile>".
						"<billTo>".
						"<firstName><![CDATA[". substr($firstName, 0, 50) . "]]></firstName>".
						"<lastName><![CDATA[" . substr($lastName, 0, 50) . "]]></lastName>".
						"<address><![CDATA[". substr($address, 0, 60) . "]]></address>".
						"<city><![CDATA[" . substr($city, 0, 40) . "]]></city>".
						"<state><![CDATA[". substr($state, 0, 2) . "]]></state>".
						"<zip>" . substr($zip, 0, 20) . "</zip>".
						"<country>". substr($country, 0, 60) . "</country>".
						"</billTo>".
						"<payment>".
						"<creditCard>".
						"<cardNumber>" . $cardNumber . "</cardNumber>".
						"<expirationDate>" . $expirationDate . "</expirationDate>";
					if(!empty($cardCode))
						$this->content .= "<cardCode>" . $cardCode . "</cardCode>";
					$this->content .=
						"</creditCard>".
						"</payment>".
						"<defaultPaymentProfile>true</defaultPaymentProfile>".
						"<customerPaymentProfileId>" . $payment_profile_id . "</customerPaymentProfileId>".
						"</paymentProfile>".
						"</updateCustomerPaymentProfileRequest>";

					//send the xml via curl
					$this->response = $this->send_request_via_curl($host,$path,$this->content);
					//echo $this->response . '<br><br>'; // If we tried to update too soon, error will say that customer profile or payment profile doesn't exist.

					// In case we want to check that the payment profile was set to default.
					/*
					$this->content = 
						"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
						"<getCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
						"<merchantAuthentication>".
						"<name><![CDATA[" . $loginname . "]]></name>".
						"<transactionKey>" . $transactionkey . "</transactionKey>".
						"</merchantAuthentication>".
						"<customerProfileId>" . $customer_profile_id . "</customerProfileId>".
						"<includeIssuerInfo>true</includeIssuerInfo>".
						"</getCustomerProfileRequest>";
					$this->response = $this->send_request_via_curl($host,$path,$this->content);
					// Print entire repsonse.
					echo $this->response;
					wp_die();
					*/

				}
				return true;
			}
			else
			{
				$order->status = "error";
				$order->errorcode = $code;
				$order->error = $text;
				$order->shorterror = $text;
				return false;
			}
		} else  {
			$order->status = "error";
			$order->error = "Could not connect to Authorize.net";
			$order->shorterror = "Could not connect to Authorize.net";
			return false;
		}
	}

	function cancel(&$order)
	{
		//define variables to send
		if(!empty($order->subscription_transaction_id))
			$subscriptionId = $order->subscription_transaction_id;
		else
			$subscriptionId = "";
		$loginname = get_option("pmpro_loginname");
		$transactionkey = get_option("pmpro_transactionkey");

		if(!empty($order->gateway_environment))
			$gateway_environment = $order->gateway_environment;
		else
			$gateway_environment = get_option("pmpro_gateway_environment");

		if($gateway_environment == "live")
			$host = "api.authorize.net";
		else
			$host = "apitest.authorize.net";

		$path = "/xml/v1/request.api";

		if(!$subscriptionId || !$loginname || !$transactionkey)
			return false;

		//build xml to post
		$content =
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>".
				"<ARBCancelSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
				"<merchantAuthentication>".
				"<name>" . $loginname . "</name>".
				"<transactionKey>" . $transactionkey . "</transactionKey>".
				"</merchantAuthentication>" .
				"<subscriptionId>" . $subscriptionId . "</subscriptionId>".
				"</ARBCancelSubscriptionRequest>";

		//send the xml via curl
		$response = $this->send_request_via_curl($host,$path,$content);
		//if curl is unavilable you can try using fsockopen
		/*
		$response = send_request_via_fsockopen($host,$path,$content);
		*/

		//if the connection and send worked $response holds the return from Authorize.net
		if ($response)
		{
			list ($resultCode, $code, $text, $subscriptionId) = $this->parse_return($response);

			if($resultCode == "Ok" || $code == "Ok")
			{
				$order->updateStatus("cancelled");
				return true;
			}
			else
			{
				//$order->status = "error";
				$order->errorcode = $code;
				$order->error = $text;
				$order->shorterror = $text;
				return false;
			}
		}
		else
		{
			$order->status = "error";
			$order->error = __("Could not connect to Authorize.net", 'paid-memberships-pro' );
			$order->shorterror = __("Could not connect to Authorize.net", 'paid-memberships-pro' );
			return false;
		}
	}

	function getSubscriptionStatus(&$order)
	{
		//define variables to send
		if(!empty($order->subscription_transaction_id))
			$subscriptionId = $order->subscription_transaction_id;
		else
			$subscriptionId = "";
		$loginname = get_option("pmpro_loginname");
		$transactionkey = get_option("pmpro_transactionkey");

		if(!empty($order->gateway_environment))
			$gateway_environment = $order->gateway_environment;
		else
			$gateway_environment = get_option("pmpro_gateway_environment");

		if($gateway_environment == "live")
			$host = "api.authorize.net";
		else
			$host = "apitest.authorize.net";

		$path = "/xml/v1/request.api";

		if(!$subscriptionId || !$loginname || !$transactionkey)
			return false;

		//build xml to post
		$content =
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>".
				"<ARBGetSubscriptionStatusRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
				"<merchantAuthentication>".
				"<name>" . $loginname . "</name>".
				"<transactionKey>" . $transactionkey . "</transactionKey>".
				"</merchantAuthentication>" .
				"<subscriptionId>" . $subscriptionId . "</subscriptionId>".
				"</ARBGetSubscriptionStatusRequest>";

		//send the xml via curl
		$response = $this->send_request_via_curl($host,$path,$content);

		//if curl is unavilable you can try using fsockopen
		/*
		$response = send_request_via_fsockopen($host,$path,$content);
		*/

		//if the connection and send worked $response holds the return from Authorize.net
		if($response)
		{
			list ($resultCode, $code, $text, $subscriptionId) = $this->parse_return($response);

			$status = $this->substring_between($response,'<status>','</status>');

			if($resultCode == "Ok" || $code == "Ok")
			{
				return $status;
			}
			else
			{
				$order->status = "error";
				$order->errorcode = $resultCode;
				$order->error = $text;
				$order->shorterror = $text;
			}
		}
		else
		{
			$order->status = "error";
			$order->error = __("Could not connect to Authorize.net", 'paid-memberships-pro' );
			$order->shorterror = __("Could not connect to Authorize.net", 'paid-memberships-pro' );
		}
	}

	/**
	 * This function takes the post_values and converts them to the proper format
	 * for an http post. For example: "x_login=username&x_tran_key=a1B2c3D4"
	 *
	 * @since 2.3.4
	 *
	 * @param array  $post_values Values to be sent to the API
	 * @param string $action      API action being performed.
	 */
	function build_post_string( $post_values, $action ) {
		/**
		 * Filters values to be sent to authorize.net.
		 *
		 * @since 2.3.4
		 *
		 * @param array  $post_values that will be sent.
		 * @param string $action being performed.
		 */
		$post_values = apply_filters( 'pmpro_authorizenet_post_values', $post_values, $action );

		$post_string = '';
		foreach( $post_values as $key => $value ) {
			$post_string .= "$key=" . urlencode( str_replace("#", "%23", $value) ) . "&";
		}
		$post_string = rtrim( $post_string, "& " );

		return $post_string;
	}

	//Authorize.net Function
	//function to send xml request via fsockopen
	function send_request_via_fsockopen($host,$path,$content)
	{
		$posturl = "ssl://" . $host;
		$header = "Host: $host\r\n";
		$header .= "User-Agent: PHP Script\r\n";
		$header .= "Content-Type: text/xml\r\n";
		$header .= "Content-Length: ".strlen($content)."\r\n";
		$header .= "Connection: close\r\n\r\n";
		$fp = fsockopen($posturl, 443, $errno, $errstr, 30);
		if (!$fp)
		{
			$response = false;
		}
		else
		{
			error_reporting(E_ERROR);
			fputs($fp, "POST $path  HTTP/1.1\r\n");
			fputs($fp, $header.$content);
			$response = "";
			while (!feof($fp))
			{
				$response = $response . fgets($fp, 128);
			}
			fclose($fp);
			error_reporting(E_ALL ^ E_NOTICE);
		}
		return $response;
	}

	//Authorize.net Function
	//function to send xml request via curl
	function send_request_via_curl($host,$path,$content)
	{
		$posturl = "https://" . $host . $path;
		$posturl = apply_filters("pmpro_authorizenet_post_url", $posturl, get_option("pmpro_gateway_environment"));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $posturl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, PMPRO_USER_AGENT);	//set user agent
		$response = curl_exec($ch);
		return $response;
	}


	//Authorize.net Function
	//function to parse Authorize.net response
	function parse_return($content)
	{
		$refId = $this->substring_between($content,'<refId>','</refId>');
		$resultCode = $this->substring_between($content,'<resultCode>','</resultCode>');
		$code = $this->substring_between($content,'<code>','</code>');
		$text = $this->substring_between($content,'<text>','</text>');
		$subscriptionId = $this->substring_between($content,'<subscriptionId>','</subscriptionId>');
		return array ($refId, $resultCode, $code, $text, $subscriptionId);
	}

	//Authorize.net Function
	//helper function for parsing response
	function substring_between($haystack,$start,$end)
	{
		if (strpos($haystack,$start) === false || strpos($haystack,$end) === false)
		{
			return false;
		}
		else
		{
			$start_position = strpos($haystack,$start)+strlen($start);
			$end_position = strpos($haystack,$end);
			return substr($haystack,$start_position,$end_position-$start_position);
		}
	}

	/**
	 * Pull subscription info from Authorize.net.
	 *
	 * @param PMPro_Subscription $subscription to pull data for.
	 *
	 * @return string|null Error message is returned if update fails.
	 */
	public function update_subscription_info( $subscription ) {
		$subscription_id = $subscription->get_subscription_transaction_id();
		$loginname       = get_option( 'pmpro_loginname' );
		$transactionkey  = get_option( 'pmpro_transactionkey' );

		if( empty( $loginname ) || empty( $transactionkey ) ) {
			return __( 'Authorize.net login credentials are not set.', 'paid-memberships-pro' );
		}

		$host = get_option( 'pmpro_gateway_environment' ) === 'live' ? 'api.authorize.net' : 'apitest.authorize.net';
		$path = '/xml/v1/request.api';

		// Build xml to post.
		$content =
				'<?xml version="1.0" encoding="utf-8"?>'.
				'<ARBGetSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">'.
				'<merchantAuthentication>'.
				'<name>' . $loginname . '</name>'.
				'<transactionKey>' . $transactionkey . '</transactionKey>'.
				'</merchantAuthentication>' .
				'<subscriptionId>' . $subscription_id . '</subscriptionId>'.
				'</ARBGetSubscriptionRequest>';

		// Send the xml via curl.
		$response = $this->send_request_via_curl( $host, $path, $content );

		// Make sure we have a response.
		if ( ! $response ) {
			return __( 'Authorize.net connection failure.', 'paid-memberships-pro' );
		}

		// If the connection and send worked $response holds the return from Authorize.net.
		list ( $resultCode, $code, $text, $subscriptionId ) = $this->parse_return( $response );
		$status = $this->substring_between( $response, '<status>', '</status>' );

		// Make sure we have a good result.
		if ( $resultCode !== 'Ok' && $code !== 'Ok' ) {
			return __( 'Authorize.net error:', 'paid-memberships-pro' ) . ' ' . esc_html( $text );
		}

		// We have good data. Update the subscription.
		$update_array = array(
			'startdate' => $this->substring_between( $response, '<startDate>', '</startDate>' ) . ' 00:00:00',
		);
		if ( in_array( $this->substring_between( $response, '<status>', '</status>' ), array( 'active', 'suspended' ) ) ) {
			// Subscription is active.
			$update_array['status'] = 'active';
			$update_array['next_payment_date'] = null; // May need to calculate...
			$update_array['billing_amount'] = $this->substring_between( $response, '<amount>', '</amount>' );
			$update_array['cycle_number']   = $this->substring_between( $response, '<length>', '</length>' );
			$update_array['cycle_period']   = rtrim( ucfirst( $this->substring_between( $response, '<unit>', '</unit>' ) ), 's' ); // months > Month.
			$update_array['trial_amount']   = $this->substring_between( $response, '<trialAmount>', '</trialAmount>' );
			$update_array['trial_limit']    = $this->substring_between( $response, '<trialOccurrences>', '</trialOccurrences>' );

			// $response doesn't have the next payment date, so we need to calculate it.
			if ( strtotime( $update_array['startdate'] ) > time()) {
				// The first recurring payment has not yet been made. Use the start date.
				$update_array['next_payment_date'] = $update_array['startdate'];
			} else {
				// Recurring payments have been made. Calculate the next payment date.
				$newest_orders = $subscription->get_orders( array( 'limit' => 1 ) );
				if ( ! empty( $newest_orders ) ) {
					// Get the most recent order.
					$newest_order = current( $newest_orders );

					// Calculate the next payment date.
					$update_array['next_payment_date'] = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $update_array['cycle_number'] . ' ' . $update_array['cycle_period'], $newest_order->getTimestamp( true ) ) );
				}
			}
		} else {
			// Subscription is no longer active.
			// Can't fill subscription end date, info not present in $response.
			$update_array['status'] = 'cancelled';
		}
		$subscription->set( $update_array );
	}

	/**
	 * Cancels a subscription in Authorize.net.
	 *
	 * @param PMPro_Subscription $subscription to cancel.
	 */
	function cancel_subscription( $subscription ) {
		$subscription_id = $subscription->get_subscription_transaction_id();
		$loginname       = get_option( 'pmpro_loginname' );
		$transactionkey  = get_option( 'pmpro_transactionkey' );

		if( empty( $loginname ) || empty( $transactionkey ) ) {
			return false;
		}

		$host = get_option( 'pmpro_gateway_environment' ) === 'live' ? 'api.authorize.net' : 'apitest.authorize.net';
		$path = '/xml/v1/request.api';

		// Build xml to post.
		$content =
				'<?xml version="1.0" encoding="utf-8"?>'.
				'<ARBCancelSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">'.
				'<merchantAuthentication>'.
				'<name>' . $loginname . '</name>'.
				'<transactionKey>' . $transactionkey . '</transactionKey>'.
				'</merchantAuthentication>' .
				'<subscriptionId>' . $subscription_id . '</subscriptionId>'.
				'</ARBCancelSubscriptionRequest>';

		// Send the xml via curl.
		$response = $this->send_request_via_curl( $host, $path, $content );

		// Make sure we have a response.
		if ( ! $response ) {
			return false;
		}

		// Check if cancellation succeeded.
		list ( $resultCode, $code, $text, $subscriptionId ) = $this->parse_return( $response );
		return $resultCode == 'Ok' || $code == 'Ok';
	}
}
