<?php
	class MemberOrder
	{
		function MemberOrder($id = NULL)
		{			
			if($id)
			{
				if(is_numeric($id))
					return $this->getMemberOrderByID($id);
				else
					return $this->getMemberOrderByCode($id);
			}
			else
				return true;	//blank constructor
		}
		
		function getMemberOrderByID($id)
		{
			global $wpdb;
			
			if(!$id)
				return false;
			
			$gmt_offset = get_option('gmt_offset');
			$dbobj = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(timestamp) + " . ($gmt_offset * 3600) . "  as timestamp FROM $wpdb->pmpro_membership_orders WHERE id = '$id' LIMIT 1");
			
			if($dbobj)
			{
				$this->id = $dbobj->id;
				$this->code = $dbobj->code;
				$this->session_id = $dbobj->session_id;
				$this->user_id = $dbobj->user_id;
				$this->membership_id = $dbobj->membership_id;
				$this->paypal_token = $dbobj->paypal_token;				
				$this->billing->name = $dbobj->billing_name;
				$this->billing->street = $dbobj->billing_street;
				$this->billing->city = $dbobj->billing_city;
				$this->billing->state = $dbobj->billing_state;
				$this->billing->zip = $dbobj->billing_zip;
				$this->billing->phone = $dbobj->billing_phone;
				
				//split up some values
				$nameparts = pnp_split_full_name($this->billing->name);
				$this->FirstName = $nameparts['fname'];
				$this->LastName = $nameparts['lname'];
				$this->Address1 = $this->billing->street;
				
				//get email from user_id
				$this->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . $this->user_id . "' LIMIT 1");
				
				$this->subtotal = $dbobj->subtotal;
				$this->tax = $dbobj->tax;
				$this->couponamount = $dbobj->couponamount;
				$this->certificate_id = $dbobj->certificate_id;
				$this->certificateamount = $dbobj->certificateamount;
				$this->total = $dbobj->total;
				$this->payment_type = $dbobj->payment_type;
				$this->cardtype = $dbobj->cardtype;
				$this->accountnumber = trim($dbobj->accountnumber);
				$this->expirationmonth = $dbobj->expirationmonth;
				$this->expirationyear = $dbobj->expirationyear;
				
				//date formats sometimes useful
				$this->ExpirationDate = $this->expirationmonth . $this->expirationyear;
				$this->ExpirationDate_YdashM = $this->expirationyear . "-" . $this->expirationmonth;				
				
				$this->status = $dbobj->status;
				$this->gateway = $dbobj->gateway;
				$this->gateway_environment = $dbobj->gateway_environment;
				$this->payment_transaction_id = $dbobj->payment_transaction_id;
				$this->subscription_transaction_id = $dbobj->subscription_transaction_id;
				$this->timestamp = $dbobj->timestamp;
				$this->affiliate_id = $dbobj->affiliate_id;
				$this->affiliate_subid = $dbobj->affiliate_subid;
				
				return $this->id;
			}
			else
				return false;	//didn't find it in the DB
		}
		
		function getLastMemberOrder($user_id = NULL)
		{
			global $current_user, $wpdb;
			if(!$user_id)
				$user_id = $current_user->ID;
			
			if(!$user_id)
				return false;
				
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' ORDER BY timestamp DESC LIMIT 1");
			
			return $this->getMemberOrderByID($id);
		}
		
		function getMemberOrderByCode($code)
		{
			global $wpdb;
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE code = '" . $code . "' LIMIT 1");
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}
		
		function getMemberOrderByPayPalToken($token)
		{
			global $wpdb;
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE paypal_token = '" . $token . "' LIMIT 1");
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}
		
		function getDiscountCode($force = false)
		{
			if($this->discount_code && !$force)
				return $this->discount_code;
				
			global $wpdb;
			$this->discount_code = $wpdb->get_row("SELECT dc.* FROM $wpdb->pmpro_discount_codes dc LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu ON dc.id = dcu.code_id WHERE dcu.order_id = '" . $this->id . "' LIMIT 1");
			
			return $this->discount_code;
		}
		
		function getUser()
		{
			global $wpdb;
			
			if($this->user)
				return $this->invoice->user;
				
			$gmt_offset = get_option('gmt_offset');
			$this->user = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(user_registered) + " . ($gmt_offset * 3600) . "  as user_registered FROM $wpdb->users WHERE ID = '" . $this->user_id . "' LIMIT 1");				
			return $this->user;						
		}
		
		function getMembershipLevel($force = false)
		{
			global $wpdb;
			
			if($this->membership_level && !$force)
				return $this->membership_level;
			
			//check if there is an entry in memberships_users first
			if($this->user_id)
			{
				$this->membership_level = $wpdb->get_row("SELECT l.id, l.name, l.description, l.allow_signups, mu.*, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE l.id = '" . $this->membership_id . "' AND mu.user_id = '" . $this->user_id . "' LIMIT 1");			
			}			
			
			//okay, do I have a discount code to check? (if there is no membership_level->membership_id value, that means there was no entry in memberships_users)
			if($this->discount_code && !$this->membership_level->membership_id)
			{
				$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $this->discount_code . "' AND cl.level_id = '" . $this->membership_id . "' LIMIT 1";			
				$this->membership_level = $wpdb->get_row($sqlQuery);
			}
			
			//just get the info from the membership table	(sigh, I really need to standardize the column names for membership_id/level_id) but we're checking if we got the information already or not
			if(!$this->membership_level->membership_id && !$this->membership_level->level_id)
			{
				$this->membership_level = $wpdb->get_row("SELECT l.* FROM $wpdb->pmpro_membership_levels l WHERE l.id = '" . $this->membership_id . "' LIMIT 1");			
			}
			
			return $this->membership_level;	
		}
		
		function getTaxForPrice($price)
		{
			//get options
			$tax_state = pmpro_getOption("tax_state");
			$tax_rate = pmpro_getOption("tax_rate");
					
			//calculate tax
			if($tax_state && $tax_rate)
			{
				//we have values, is this order in the tax state?
				if(trim(strtoupper($this->billing->state)) == trim(strtoupper($tax_state)))
				{					
					//set values array for filter
					$values = array("price" => $price, "tax_state" => $tax_state, "tax_rate" => $tax_rate, "billing_state" => $this->billing->state, "billing_city" => $this->billing_city, "billing_zip" => $this->billing->zip, "billing_country" => $this->billing->country);
					
					//return value, pass through filter
					return apply_filters("pmpro_tax", round((float)$price * (float)$tax_rate, 2), $values);
				}
			}
			
			return 0;
		}
		
		function getTax($force = false)
		{
			if($this->tax && !$force)
				return $this->tax;
		
			//reset
			$this->tax = $this->getTaxForPrice($this->subtotal);			
						
			return $this->tax;
		}
		
		function saveOrder()
		{
			global $current_user, $wpdb;
			
			//get a random code to use for the public ID
			if(!$this->code)
				$this->code = $this->getRandomCode();
			
			//figure out how much we charged
			$amount = $this->InitialPayment;
			
			//Todo: Make sure the session is started
			
			//Todo: Tax?!, Coupons, Certificates, affiliates
			$this->subtotal = $amount;
			$tax = $this->getTax(true);
					
			//build query			
			if($this->id)
			{
				//update
				$this->sqlQuery = "UPDATE $wpdb->pmpro_membership_orders
									SET `code` = '" . $this->code . "',
									`session_id` = '" . $this->session_id . "',
									`user_id` = '" . $this->user_id . "',
									`membership_id` = '" . $this->membership_id . "',
									`paypal_token` = '" . $this->paypal_token . "',
									`billing_name` = '" . $this->billing->name . "',
									`billing_street` = '" . $this->billing->street . "',
									`billing_city` = '" . $this->billing->city . "',
									`billing_state` = '" . $this->billing->state . "',
									`billing_zip` = '" . $this->billing->zip . "',
									`billing_phone` = '" . $this->billing->phone . "',
									`subtotal` = '" . $this->subtotal . "',
									`tax` = '" . $this->tax . "',
									`couponamount` = '" . $this->couponamount . "',
									`certificate_id` = '" . $this->certificate_id . "',
									`certificateamount` = '" . $this->certificateamount . "',
									`total` = '" . $this->total . "',
									`payment_type` = '" . $this->payment_type . "',
									`cardtype` = '" . $this->cardtype . "',
									`accountnumber` = '" . $this->accountnumber . "',
									`expirationmonth` = '" . $this->expirationmonth . "',
									`expirationyear` = '" . $this->expirationyear . "',
									`status` = '" . $this->status . "',
									`gateway` = '" . $this->gateway . "',
									`gateway_environment` = '" . $this->gateway_environment . "',
									`payment_transaction_id` = '" . $this->payment_transaction_id . "',
									`subscription_transaction_id` = '" . $this->subscription_transaction_id . "',									
									`affiliate_id` = '" . $this->affiliate_id . "',
									`affiliate_subid` = '" . $this->affiliate_subid . "'
									WHERE id = '" . $this->id . "'
									LIMIT 1";
			}
			else
			{
				//insert
				$this->sqlQuery = "INSERT INTO $wpdb->pmpro_membership_orders  
								(`code`, `session_id`, `user_id`, `membership_id`, `paypal_token`, `billing_name`, `billing_street`, `billing_city`, `billing_state`, `billing_zip`, `billing_phone`, `subtotal`, `tax`, `couponamount`, `certificate_id`, `certificateamount`, `total`, `payment_type`, `cardtype`, `accountnumber`, `expirationmonth`, `expirationyear`, `status`, `gateway`, `gateway_environment`, `payment_transaction_id`, `subscription_transaction_id`, `timestamp`, `affiliate_id`, `affiliate_subid`) 
								VALUES('" . $this->code . "',
									   '" . session_id() . "',
									   '" . $this->user_id . "',
									   '" . $this->membership_id . "',
									   '" . $this->paypal_token . "',
									   '" . $wpdb->escape(trim($this->billing->name)) . "',
									   '" . $wpdb->escape(trim($this->billing->street)) . "',
									   '" . $wpdb->escape($this->billing->city) . "',
									   '" . $wpdb->escape($this->billing->state) . "',
									   '" . $wpdb->escape($this->billing->zip) . "',
									   '" . cleanPhone($this->billing->phone) . "',
									   '" . $amount . "',
									   '" . $tax . "',
									   '" . $coupon. "',
									   '" . $certificate_id . "',
									   '" . $certficate_amount . "',
									   '" . ((float)$amount + (float)$tax) . "',
									   '" . $this->payment_type . "',
									   '" . $this->cardtype . "',
									   '" . hideCardNumber($this->accountnumber, false) . "',
									   '" . substr($this->ExpirationDate, 0, 2) . "',
									   '" . substr($this->ExpirationDate, 2, 4) . "',
									   '" . $this->status . "',
									   '" . pmpro_getOption("gateway") . "', 
									   '" . pmpro_getOption("gateway_environment") . "', 
									   '" . $this->payment_transaction_id . "',
									   '" . $this->subscription_transaction_id . "',
									   now(),
									   '" . $affiliate_id . "',
									   '" . $affiliate_subid . "'
									   )";		
			}
									   
			if($wpdb->query($this->sqlQuery) !== false)
				return $this->getMemberOrderByID($wpdb->insert_id);
			else
				return false;
		}
				
		function getRandomCode()
		{
			global $wpdb;
			
			while(!$code)
			{
				$scramble = md5(AUTH_KEY . time() . SECURE_AUTH_KEY);			
				$code = substr($scramble, 0, 10);
				$check = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE code = '$code' LIMIT 1");				
				if($check || is_numeric($code))
					$code = NULL;
			}
			
			return strtoupper($code);
		}
		
		function updateStatus($newstatus)
		{
			global $wpdb;
			
			if(!$this->id)
				return false;
		
			$this->status = $newstatus;
			$this->sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET status = '" . $wpdb->escape($newstatus) . "' WHERE id = '" . $this->id . "' LIMIT 1";
			if($wpdb->query($this->sqlQuery) !== false)
				return true;
			else
				return false;
		}
		
		function process()
		{
			$gateway = pmpro_getOption("gateway");
			if($gateway == "paypal")
			{												
				if(floatval($this->InitialPayment) == 0)
				{
					//auth first, then process
					$authorization_id = $this->authorizeWithPayPal();					
					if($authorization_id)
					{
						$this->voidAuthorizationWithPayPal($authorization_id);						
						$this->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod)) . "T0:0:0";
						$this->ProfileStartDate = apply_filters("pmpro_profile_start_date", $this->ProfileStartDate, $this);
						return $this->processWithPayPal();
					}
					else
					{
						if(!$this->error)
							$this->error = "Unknown error: Authorization failed.";
						return false;
					}
				}
				else
				{
					//charge first payment
					if($this->chargeWithPayPal())
					{																		
						//setup recurring billing
						if(pmpro_isLevelRecurring($this->membership_level))
						{
							$this->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod)) . "T0:0:0";
							$this->ProfileStartDate = apply_filters("pmpro_profile_start_date", $this->ProfileStartDate, $this);
							return $this->processWithPayPal();
						}
						else
						{
							//only a one time charge							
							$this->status = "success";	//saved on checkout page											
							$this->saveOrder();
							return true;
						}
					}
					else
					{
						if(!$this->error)
							$this->error = "Unknown error: Payment failed.";
						return false;
					}
				}				
			}				
			elseif($gateway == "paypalexpress")
			{																																		
				if(pmpro_isLevelRecurring($this->membership_level))
				{
					$this->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod)) . "T0:0:0";
					$this->ProfileStartDate = apply_filters("pmpro_profile_start_date", $this->ProfileStartDate, $this);
					return $this->processWithPayPalExpress();				
				}
				else
					return $this->chargeWithPayPalExpress();				
			}	
			elseif($gateway == "authorizenet")
			{				
				if(floatval($this->InitialPayment) == 0)
				{
					//auth first, then process
					if($this->authorizeWithAuthorizeNet())
					{						
						$this->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod)) . "T0:0:0";						
						$this->ProfileStartDate = apply_filters("pmpro_profile_start_date", $this->ProfileStartDate, $this);
						return $this->processWithAuthorizeNet();
					}
					else
					{
						if(!$this->error)
							$this->error = "Unknown error: Authorization failed.";
						return false;
					}
				}
				else
				{
					//charge first payment
					if($this->chargeWithAuthorizeNet())
					{							
						//setup recurring billing
						if(pmpro_isLevelRecurring($this->membership_level))
						{
							$this->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod)) . "T0:0:0";
							$this->ProfileStartDate = apply_filters("pmpro_profile_start_date", $this->ProfileStartDate, $this);
							return $this->processWithAuthorizeNet();
						}
						else
						{
							//only a one time charge
							$this->status = "success";	//saved on checkout page											
							return true;
						}
					}
					else
					{
						if(!$this->error)
							$this->error = "Unknown error: Payment failed.";
						return false;
					}
				}				
			}
			else
			{			
				if(pmpro_isAdmin())			
					$this->error = "You must <a href=\"" . home_url('/wp-admin/admin.php?page=pmpro-membershiplevels&view=payment') . "\">setup a Payment Gateway</a> before any payments will be processed.";
				else
					$this->error = "A Payment Gateway must be setup before any payments will be processed.";
				return false;
			}
		}
		
		function cancel()
		{
			$gateway = $this->gateway;
			
			//if there is no subscription id or this subscription has a status != success, it was already cancelled (or never existed)
			if(!$this->subscription_transaction_id || !$this->status != "success")
			{
				//cancel
				$this->updateStatus("cancelled");
				return true;
			}
			
			//if no gateway specified for the order, assume it is the current gateway
			if(!$gateway)			
				$gateway = pmpro_getOption("gateway");
						
			if($gateway == "paypal" || $gateway == "paypalexpress")
				return $this->cancelWithPayPal();
			elseif($gateway == "authorizenet")
				return $this->cancelWithAuthorizeNet();
			else
				return false;
		}
		
		function updateBilling()
		{
			$gateway = $this->gateway;
			
			//if no gateway specified for the order, assume it is the current gateway
			if(!$gateway)			
				$gateway = pmpro_getOption("gateway");
				
			if($gateway == "paypal")
				return $this->updateWithPayPal();
			elseif($gateway == "authorizenet")
				return $this->updateWithAuthorizeNet();
			else
				return false;
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
			$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
					
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
		
		function authorizeWithPayPal()
		{
			if(!$this->code)
				$this->code = $this->getRandomCode();
									
			//paypal profile stuff
			$nvpStr = "";
			if($this->Token)
				$nvpStr .= "&TOKEN=" . $this->Token;
			$nvpStr .="&AMT=1.00&CURRENCYCODE=" . pmpro_getOption("currency");			
			$nvpStr .= "&NOTIFYURL=" . urlencode(PMPRO_URL . "/services/ipnhandler.php");
			//$nvpStr .= "&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . $this->PaymentAmount;
			
			$nvpStr .= "&PAYMENTACTION=Authorization&IPADDRESS=" . $_SERVER['REMOTE_ADDR'] . "&INVNUM=" . $this->code;
						
			//credit card fields
			if($this->cardtype == "American Express")
				$cardtype = "Amex";
			else
				$cardtype = $this->cardtype;
			
			if($cardtype)			
				$nvpStr .= "&CREDITCARDTYPE=" . $cardtype . "&ACCT=" . $this->accountnumber . "&EXPDATE=" . $this->ExpirationDate . "&CVV2=" . $this->CVV2;

			//Maestro/Solo card fields. (Who uses these?) :)
			if($this->StartDate)
				$nvpStr .= "&STARTDATE=" . $this->StartDate . "&ISSUENUMBER=" . $this->IssueNumber;
			
			//billing address, etc
			if($this->Address1)
			{
				$nvpStr .= "&EMAIL=" . $this->Email . "&FIRSTNAME=" . $this->FirstName . "&LASTNAME=" . $this->LastName . "&STREET=" . $this->Address1;
				
				if($this->Address2)
					$nvpStr .= "&STREET2=" . $this->Address2;
				
				$nvpStr .= "&CITY=" . $this->billing->city . "&STATE=" . $this->billing->state . "&COUNTRYCODE=" . $this->billing->country . "&ZIP=" . $this->billing->zip . "&SHIPTOPHONENUM=" . $this->billing->phone;
			}

			$this->nvpStr = $nvpStr;
			
			$this->httpParsedResponseAr = $this->PPHttpPost('DoDirectPayment', $nvpStr);
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->authorization_id = $this->httpParsedResponseAr[TRANSACTIONID];
				$this->updateStatus("authorized");				
				return $this->authorization_id;				
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;				
			}	
		}
		
		function voidAuthorizationWithPayPal($authorization_id)
		{
			//paypal profile stuff
			$nvpStr="&AUTHORIZATIONID=" . $authorization_id . "&NOTE=Voiding an authorization for a recurring payment setup.";
		
			$this->httpParsedResponseAr = $this->PPHttpPost('DoVoid', $nvpStr);
									
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {				
				return true;				
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;				
			}	
		}
		
		function chargeWithPayPal()
		{			
			global $pmpro_currency;
			
			if(!$this->code)
				$this->code = $this->getRandomCode();
			
			//taxes on the amount
			$amount = $this->InitialPayment;
			$amount_tax = $this->getTaxForPrice($amount);						
			$this->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
			
			//paypal profile stuff
			$nvpStr = "";
			if($this->Token)
				$nvpStr .= "&TOKEN=" . $this->Token;
			$nvpStr .="&AMT=" . $amount . "&ITEMAMT=" . $this->InitialPayment . "&TAXAMT=" . $amount_tax . "&CURRENCYCODE=" . $pmpro_currency;			
			$nvpStr .= "&NOTIFYURL=" . urlencode(PMPRO_URL . "/services/ipnhandler.php");
			//$nvpStr .= "&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . $this->PaymentAmount;
			
			$nvpStr .= "&PAYMENTACTION=Sale&IPADDRESS=" . $_SERVER['REMOTE_ADDR'] . "&INVNUM=" . $this->code;			
			
			//credit card fields
			if($this->cardtype == "American Express")
				$cardtype = "Amex";
			else
				$cardtype = $this->cardtype;

			if($cardtype)			
				$nvpStr .= "&CREDITCARDTYPE=" . $cardtype . "&ACCT=" . $this->accountnumber . "&EXPDATE=" . $this->ExpirationDate . "&CVV2=" . $this->CVV2;

			//Maestro/Solo card fields. (Who uses these?) :)
			if($this->StartDate)
				$nvpStr .= "&STARTDATE=" . $this->StartDate . "&ISSUENUMBER=" . $this->IssueNumber;
			
			//billing address, etc
			if($this->Address1)
			{
				$nvpStr .= "&EMAIL=" . $this->Email . "&FIRSTNAME=" . $this->FirstName . "&LASTNAME=" . $this->LastName . "&STREET=" . $this->Address1;
				
				if($this->Address2)
					$nvpStr .= "&STREET2=" . $this->Address2;
				
				$nvpStr .= "&CITY=" . $this->billing->city . "&STATE=" . $this->billing->state . "&COUNTRYCODE=" . $this->billing->country . "&ZIP=" . $this->billing->zip . "&SHIPTOPHONENUM=" . $this->billing->phone;
			}

			$this->httpParsedResponseAr = $this->PPHttpPost('DoDirectPayment', $nvpStr);
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->payment_transaction_id = $this->httpParsedResponseAr[TRANSACTIONID];
				$this->updateStatus("firstpayment");				
				return true;				
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);				
				return false;				
			}			
		}
		
		function processWithPayPal()
		{										
			global $pmpro_currency;
			
			if(!$this->code)
				$this->code = $this->getRandomCode();			
			
			//taxes on the amount
			$amount = $this->PaymentAmount;
			$amount_tax = $this->getTaxForPrice($amount);						
			$this->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
						
			//paypal profile stuff
			$nvpStr = "";
			if($this->Token)
				$nvpStr .= "&TOKEN=" . $this->Token;
			$nvpStr .="&AMT=" . $this->PaymentAmount . "&TAXAMT=" . $amount_tax . "&CURRENCYCODE=" . $pmpro_currency . "&PROFILESTARTDATE=" . $this->ProfileStartDate;
			$nvpStr .= "&BILLINGPERIOD=" . $this->BillingPeriod . "&BILLINGFREQUENCY=" . $this->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling";
			$nvpStr .= "&DESC=" . $amount;
			$nvpStr .= "&NOTIFYURL=" . urlencode(PMPRO_URL . "/services/ipnhandler.php");
			//$nvpStr .= "&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . $this->PaymentAmount;
			
			//if billing cycles are defined						
			if($this->TotalBillingCycles)
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $this->TotalBillingCycles;
			
			//if a trial period is defined
			if($this->TrialBillingPeriod)
			{
				$trial_amount = $this->TrialAmount;
				$trial_tax = $this->getTaxForPrice($trial_amount);
				$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);
				
				$nvpStr .= "&TRIALBILLINGPERIOD=" . $this->TrialBillingPeriod . "&TRIALBILLINGFREQUENCY=" . $this->TrialBillingFrequency . "&TRIALAMNT=" . $trial_amount;
			}
			if($this->TrialBillingCycles)
				$nvpStr .= "&TRIALTOTALBILLINGCYCLES=" . $this->TrialBillingCycles;
			
			//credit card fields
			if($this->cardtype == "American Express")
				$cardtype = "Amex";
			else
				$cardtype = $this->cardtype;
			
			if($cardtype)			
				$nvpStr .= "&CREDITCARDTYPE=" . $cardtype . "&ACCT=" . $this->accountnumber . "&EXPDATE=" . $this->ExpirationDate . "&CVV2=" . $this->CVV2;

			//Maestro/Solo card fields. (Who uses these?) :)
			if($this->StartDate)
				$nvpStr .= "&STARTDATE=" . $this->StartDate . "&ISSUENUMBER=" . $this->IssueNumber;
			
			//billing address, etc
			if($this->Address1)
			{
				$nvpStr .= "&EMAIL=" . $this->Email . "&FIRSTNAME=" . $this->FirstName . "&LASTNAME=" . $this->LastName . "&STREET=" . $this->Address1;
				
				if($this->Address2)
					$nvpStr .= "&STREET2=" . $this->Address2;
				
				$nvpStr .= "&CITY=" . $this->billing->city . "&STATE=" . $this->billing->state . "&COUNTRYCODE=" . $this->billing->country . "&ZIP=" . $this->billing->zip . "&SHIPTOPHONENUM=" . $this->billing->phone;
			}

			$this->nvpStr = $nvpStr;
			
			$this->httpParsedResponseAr = $this->PPHttpPost('CreateRecurringPaymentsProfile', $nvpStr);
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->status = "success";				
				$this->subscription_transaction_id = urldecode($this->httpParsedResponseAr[PROFILEID]);
				return true;
				//exit('CreateRecurringPaymentsProfile Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;
				//exit('CreateRecurringPaymentsProfile failed: ' . print_r($httpParsedResponseAr, true));
			}
		}
		
		function cancelWithPayPal()
		{
			//paypal profile stuff
			$nvpStr = "";			
			$nvpStr .= "&PROFILEID=" . $this->subscription_transaction_id . "&ACTION=Cancel&NOTE=User requested cancel.";							
			
			$this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);
									
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {				
				$this->updateStatus("cancelled");					
				return true;
				//exit('CreateRecurringPaymentsProfile Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;
				//exit('CreateRecurringPaymentsProfile failed: ' . print_r($httpParsedResponseAr, true));
			}
		}
		
		function updateWithPayPal()
		{
			//paypal profile stuff
			$nvpStr = "";			
			$nvpStr .= "&PROFILEID=" . $this->subscription_transaction_id;
			
			//credit card fields
			if($this->cardtype == "American Express")
				$cardtype = "Amex";
			else
				$cardtype = $this->cardtype;
			
			//credit card fields
			if($cardtype)			
				$nvpStr .= "&CREDITCARDTYPE=" . $cardtype . "&ACCT=" . $this->accountnumber . "&EXPDATE=" . $this->ExpirationDate . "&CVV2=" . $this->CVV2;

			//Maestro/Solo card fields. (Who uses these?) :)
			if($this->StartDate)
				$nvpStr .= "&STARTDATE=" . $this->StartDate . "&ISSUENUMBER=" . $this->IssueNumber;
			
			//billing address, etc
			if($this->Address1)
			{
				$nvpStr .= "&EMAIL=" . $this->Email . "&FIRSTNAME=" . $this->FirstName . "&LASTNAME=" . $this->LastName . "&STREET=" . $this->Address1;
				
				if($this->Address2)
					$nvpStr .= "&STREET2=" . $this->Address2;
				
				$nvpStr .= "&CITY=" . $this->billing->city . "&STATE=" . $this->billing->state . "&COUNTRYCODE=" . $this->billing->country . "&ZIP=" . $this->billing->zip;
			}		
			
			$this->httpParsedResponseAr = $this->PPHttpPost('UpdateRecurringPaymentsProfile', $nvpStr);
									
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->status = "success";
				$this->subscription_transaction_id = urldecode($this->httpParsedResponseAr[PROFILEID]);
				return true;
				//exit('CreateRecurringPaymentsProfile Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;
				//exit('CreateRecurringPaymentsProfile failed: ' . print_r($httpParsedResponseAr, true));
			}
		}
		
		//PayPal Express, this is run first to authorize from PayPal
		function setExpressCheckout()
		{			
			global $pmpro_currency;
			
			if(!$this->code)
				$this->code = $this->getRandomCode();			
			
			//clean up a couple values
			$this->payment_type = "PayPal Express";
			$this->CardType = "";
			$this->cardtype = "";
			
			//taxes on initial amount
			$initial_payment = $this->InitialPayment;
			$initial_payment_tax = $this->getTaxForPrice($initial_payment);
			$initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);
			
			//taxes on the amount
			$amount = $this->PaymentAmount;
			$amount_tax = $this->getTaxForPrice($amount);						
			$this->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
						
			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .="&AMT=" . $initial_payment . "&TAXAMT=" . $amount_tax . "&CURRENCYCODE=" . $pmpro_currency . "&PROFILESTARTDATE=" . $this->ProfileStartDate;
			$nvpStr .= "&BILLINGPERIOD=" . $this->BillingPeriod . "&BILLINGFREQUENCY=" . $this->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling";
			$nvpStr .= "&DESC=" . $amount;
			$nvpStr .= "&NOTIFYURL=" . urlencode(PMPRO_URL . "/services/ipnhandler.php");
			$nvpStr .= "&NOSHIPPING=1&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . urlencode($this->membership_level->name . " at " . get_bloginfo("name")) . "&L_PAYMENTTYPE0=Any";
					
			//if billing cycles are defined						
			if($this->TotalBillingCycles)
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $this->TotalBillingCycles;
			
			//if a trial period is defined
			if($this->TrialBillingPeriod)
			{
				$trial_amount = $this->TrialAmount;
				$trial_tax = $this->getTaxForPrice($trial_amount);
				$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);
				
				$nvpStr .= "&TRIALBILLINGPERIOD=" . $this->TrialBillingPeriod . "&TRIALBILLINGFREQUENCY=" . $this->TrialBillingFrequency . "&TRIALAMNT=" . $trial_amount;
			}
			if($this->TrialBillingCycles)
				$nvpStr .= "&TRIALTOTALBILLINGCYCLES=" . $this->TrialBillingCycles;
			
			if($this->discount_code)
			{
				$nvpStr .= "&ReturnUrl=" . urlencode(pmpro_url("checkout", "?level=" . $this->membership_level->id . "&discount_code=" . $this->discount_code . "&review=" . $this->code));				
			}
			else
			{
				$nvpStr .= "&ReturnUrl=" . urlencode(pmpro_url("checkout", "?level=" . $this->membership_level->id . "&review=" . $this->code));				
			}
			$nvpStr .= "&CANCELURL=" . urlencode(pmpro_url("levels"));			
						
			$this->httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $nvpStr);
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->status = "token";				
				$this->paypal_token = urldecode($this->httpParsedResponseAr[TOKEN]);
				$this->subscription_transaction_id = urldecode($this->httpParsedResponseAr[PROFILEID]);
												
				//update order
				$this->saveOrder();							
							
				//redirect to paypal
				$paypal_url = "https://www.paypal.com/webscr&cmd=_express-checkout&token=" . $this->httpParsedResponseAr[TOKEN];
				$environment = pmpro_getOption("gateway_environment");				
				if("sandbox" === $environment || "beta-sandbox" === $environment) 
				{
					$paypal_url = "https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token="  . $this->httpParsedResponseAr[TOKEN];
				}		
								
				wp_redirect($paypal_url);				
				exit;
				
				//exit('SetExpressCheckout Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
			
			//write session?
			
			//redirect to PayPal
		}
		
		function getPayPalExpressCheckoutDetails()
		{			
			$nvpStr="&TOKEN=".$this->Token;
			
			/* Make the API call and store the results in an array.  If the
			call was a success, show the authorization details, and provide
			an action to complete the payment.  If failed, show the error
			*/
			$this->httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $nvpStr);
			
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->status = "review";				
				
				//update order
				$this->saveOrder();		
				
				return true;										
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
		}
				
		function chargeWithPayPalExpress()
		{
			global $pmpro_currency;
			
			if(!$this->code)
				$this->code = $this->getRandomCode();			
														
			//taxes on the amount
			$amount = $this->InitialPayment;
			$amount_tax = $this->getTaxForPrice($amount);						
			$this->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
						
			//paypal profile stuff
			$nvpStr = "";
			if($this->Token)
				$nvpStr .= "&TOKEN=" . $this->Token;
			$nvpStr .="&AMT=" . $this->InitialPayment . "&TAXAMT=" . $amount_tax . "&CURRENCYCODE=" . $pmpro_currency . "&PROFILESTARTDATE=" . $this->ProfileStartDate;
			$nvpStr .= "&BILLINGPERIOD=" . $this->BillingPeriod . "&BILLINGFREQUENCY=" . $this->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling";
			$nvpStr .= "&DESC=" . $amount;
			$nvpStr .= "&NOTIFYURL=" . urlencode(PMPRO_URL . "/services/ipnhandler.php");
			$nvpStr .= "&NOSHIPPING=1";
			
			$nvpStr .= "&PAYERID=" . $_SESSION['payer_id'] . "&PAYMENTACTION=sale";								
			$this->nvpStr = $nvpStr;
			
			$this->httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $nvpStr);
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {			
				$this->payment_transaction_id = urldecode($this->httpParsedResponseAr[PROFILEID]);								
				$this->status = "firstpayment";				
				
				//update order
				$this->saveOrder();	
				
				return true;	
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				return false;
				//exit('SetExpressCheckout failed: ' . print_r($httpParsedResponseAr, true));
			}
		}
		
		function processWithPayPalExpress()
		{
			global $pmpro_currency;
			
			if(!$this->code)
				$this->code = $this->getRandomCode();						
			
			//taxes on initial amount
			$initial_payment = $this->InitialPayment;
			$initial_payment_tax = $this->getTaxForPrice($initial_payment);
			$initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);
						
			//taxes on the amount
			$amount = $this->PaymentAmount;
			$amount_tax = $this->getTaxForPrice($amount);						
			$this->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
						
			//paypal profile stuff
			$nvpStr = "";
			if($this->Token)
				$nvpStr .= "&TOKEN=" . $this->Token;		
			$nvpStr .="&INITAMT=" . $initial_payment . "&AMT=" . $this->PaymentAmount . "&TAXAMT=" . $amount_tax . "&CURRENCYCODE=" . $pmpro_currency . "&PROFILESTARTDATE=" . $this->ProfileStartDate;
			$nvpStr .= "&BILLINGPERIOD=" . $this->BillingPeriod . "&BILLINGFREQUENCY=" . $this->BillingFrequency . "&AUTOBILLAMT=AddToNextBilling";			
			$nvpStr .= "&NOTIFYURL=" . urlencode(PMPRO_URL . "/services/ipnhandler.php");
			$nvpStr .= "&DESC=" . urlencode($this->membership_level->name . " at " . get_bloginfo("name"));
			
			//if billing cycles are defined						
			if($this->TotalBillingCycles)
				$nvpStr .= "&TOTALBILLINGCYCLES=" . $this->TotalBillingCycles;
			
			//if a trial period is defined
			if($this->TrialBillingPeriod)
			{
				$trial_amount = $this->TrialAmount;
				$trial_tax = $this->getTaxForPrice($trial_amount);
				$trial_amount = round((float)$trial_amount + (float)$trial_tax, 2);
				
				$nvpStr .= "&TRIALBILLINGPERIOD=" . $this->TrialBillingPeriod . "&TRIALBILLINGFREQUENCY=" . $this->TrialBillingFrequency . "&TRIALAMNT=" . $trial_amount;
			}
			if($this->TrialBillingCycles)
				$nvpStr .= "&TRIALTOTALBILLINGCYCLES=" . $this->TrialBillingCycles;
			
			$this->nvpStr = $nvpStr;
			
			$this->httpParsedResponseAr = $this->PPHttpPost('CreateRecurringPaymentsProfile', $nvpStr);
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$this->status = "success";				
				$this->payment_transaction_id = urldecode($this->httpParsedResponseAr[PROFILEID]);
				$this->subscription_transaction_id = urldecode($this->httpParsedResponseAr[PROFILEID]);
				
				//update order
				$this->saveOrder();					
				
				return true;				
			} else  {				
				$this->status = "error";
				$this->errorcode = $this->httpParsedResponseAr[L_ERRORCODE0];
				$this->error = urldecode($this->httpParsedResponseAr[L_LONGMESSAGE0]);
				$this->shorterror = urldecode($this->httpParsedResponseAr[L_SHORTMESSAGE0]);
				
				return false;				
			}
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
				fwrite($fp, $out);
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
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $posturl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
		
		//authorize just $1 to test credit card
		function authorizeWithAuthorizeNet()
		{
			if(!$this->code)
				$this->code = $this->getRandomCode();
			
			$gateway_environment = $this->gateway_environment;
			if(!$gateway_environment)
				$gateway_environment = pmpro_getOption("gateway_environment");
			if($gateway_environment == "live")
					$host = "secure.authorize.net";		
				else
					$host = "test.authorize.net";	
			
			$path = "/gateway/transact.dll";												
			$post_url = "https://" . $host . $path;

			//what amount to authorize? just $1 to test
			$amount = "1.00";		
			
			//combine address			
			$address = $this->Address1;
			if($this->Address2)
				$address .= "\n" . $this->Address2;
				
			//customer stuff
			$customer_email = $this->Email;
			$customer_phone = $this->billing->phone;
			
			$post_values = array(
				
				// the API Login ID and Transaction Key must be replaced with valid values
				"x_login"			=> pmpro_getOption("loginname"),
				"x_tran_key"		=> pmpro_getOption("transactionkey"),

				"x_version"			=> "3.1",
				"x_delim_data"		=> "TRUE",
				"x_delim_char"		=> "|",
				"x_relay_response"	=> "FALSE",

				"x_type"			=> "AUTH_ONLY",
				"x_method"			=> "CC",
				"x_card_type"		=> $this->cardtype,
				"x_card_num"		=> $this->accountnumber,
				"x_exp_date"		=> $this->ExpirationDate,
				"x_card_code"		=> $this->CVV2,
				
				"x_amount"			=> $amount,
				"x_description"		=> $this->level->name . " Membership",

				"x_first_name"		=> $this->FirstName,
				"x_last_name"		=> $this->LastName,
				"x_address"			=> $address,
				"x_city"			=> $this->billing->city,
				"x_state"			=> $this->billing->state,
				"x_zip"				=> $this->billing->zip,
				"x_country"			=> $this->billing->country,
				"x_invoice_num"		=> $this->code,
				"x_phone"			=> $customer_phone,
				"x_email"			=> $this->Email
				// Additional fields can be added here as outlined in the AIM integration
				// guide at: http://developer.authorize.net
			);
			
			// This section takes the input fields and converts them to the proper format
			// for an http post.  For example: "x_login=username&x_tran_key=a1B2c3D4"
			$post_string = "";
			foreach( $post_values as $key => $value )
				{ $post_string .= "$key=" . urlencode( str_replace("#", "%23", $value) ) . "&"; }
			$post_string = rtrim( $post_string, "& " );
						
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
				$this->payment_transaction_id = $response_array[4];
				$this->updateStatus("authorized");					
				return true;
			}
			else
			{
				//$this->status = "error";
				$this->errorcode = $response_array[2];
				$this->error = $response_array[3];
				$this->shorterror = $response_array[3];
				return false;
			}			
		}
		
		//charge first periods payment
		function chargeWithAuthorizeNet()
		{
			if(!$this->code)
				$this->code = $this->getRandomCode();
			
			$gateway_environment = $this->gateway_environment;
			if(!$gateway_environment)
				$gateway_environment = pmpro_getOption("gateway_environment");
			if($gateway_environment == "live")
					$host = "secure.authorize.net";		
				else
					$host = "test.authorize.net";	
			
			$path = "/gateway/transact.dll";												
			$post_url = "https://" . $host . $path;

			//what amount to charge?			
			$amount = $this->InitialPayment;
						
			//tax
			$this->subtotal = $amount;
			$tax = $this->getTax(true);
			$amount = round((float)$this->subtotal + (float)$tax, 2);
			
			//combine address			
			$address = $this->Address1;
			if($this->Address2)
				$address .= "\n" . $this->Address2;
			
			//customer stuff
			$customer_email = $this->Email;
			$customer_phone = $this->billing->phone;
			
			$post_values = array(
				
				// the API Login ID and Transaction Key must be replaced with valid values
				"x_login"			=> pmpro_getOption("loginname"),
				"x_tran_key"		=> pmpro_getOption("transactionkey"),

				"x_version"			=> "3.1",
				"x_delim_data"		=> "TRUE",
				"x_delim_char"		=> "|",
				"x_relay_response"	=> "FALSE",

				"x_type"			=> "AUTH_CAPTURE",
				"x_method"			=> "CC",
				"x_card_type"		=> $this->cardtype,
				"x_card_num"		=> $this->accountnumber,
				"x_exp_date"		=> $this->ExpirationDate,
				"x_card_code"		=> $this->CVV2,
				
				"x_amount"			=> $amount,
				"x_tax"				=> $tax,
				"x_description"		=> $this->level->name . " Membership",

				"x_first_name"		=> $this->FirstName,
				"x_last_name"		=> $this->LastName,
				"x_address"			=> $address,
				"x_city"			=> $this->billing->city,
				"x_state"			=> $this->billing->state,
				"x_zip"				=> $this->billing->zip,
				"x_country"			=> $this->billing->country,
				"x_invoice_num"		=> $this->code,
				"x_phone"			=> $customer_phone,
				"x_email"			=> $this->Email
				
				// Additional fields can be added here as outlined in the AIM integration
				// guide at: http://developer.authorize.net
			);
						
			// This section takes the input fields and converts them to the proper format
			// for an http post.  For example: "x_login=username&x_tran_key=a1B2c3D4"
			$post_string = "";
			foreach( $post_values as $key => $value )
				{ $post_string .= "$key=" . urlencode( str_replace("#", "%23", $value) ) . "&"; }
			$post_string = rtrim( $post_string, "& " );
						
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
				$this->payment_transaction_id = $response_array[4];
				$this->updateStatus("firstpayment");					
				return true;
			}
			else
			{
				//$this->status = "error";
				$this->errorcode = $response_array[2];
				$this->error = $response_array[3];
				$this->shorterror = $response_array[3];
				return false;
			}
		}
		
		function processWithAuthorizeNet()
		{		
			//define variables to send

			if(!$this->code)
				$this->code = $this->getRandomCode();
			
			$gateway_environment = $this->gateway_environment;
			if(!$gateway_environment)
				$gateway_environment = pmpro_getOption("gateway_environment");
			if($gateway_environment == "live")
					$host = "api.authorize.net";		
				else
					$host = "apitest.authorize.net";	
			
			$path = "/xml/v1/request.api";
			
			$loginname = pmpro_getOption("loginname");
			$transactionkey = pmpro_getOption("transactionkey");
			
			$amount = $this->PaymentAmount;
			$refId = $this->code;
			$name = $this->membership_name;
			$length = (int)$this->BillingFrequency;
			
			if($this->BillingPeriod == "Month")
				$unit = "months";
			elseif($this->BillingPeriod == "Day")
				$unit = "days";
			elseif($this->BillingPeriod == "Year" && $this->BillingFrequency == 1)
			{
				$unit = "months";
				$length = 12;
			}
			elseif($this->BillingPeriod == "Week")
			{
				$unit = "days";
				$length = $length * 7;	//converting weeks to days
			}
			else
				return false;	//authorize.net only supports months and days
				
			$startDate = substr($this->ProfileStartDate, 0, 10);
			$totalOccurrences = (int)$this->TotalBillingCycles;
			if(!$totalOccurrences)
				$totalOccurrences = 9999;
			$trialOccurrences = (int)$this->TrialBillingCycles;
			$trialAmount = $this->TrialAmount;
			
			//taxes
			$amount_tax = $this->getTaxForPrice($amount);
			$trial_tax = $this->getTaxForPrice($trialAmount);
			
			$this->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
			$trialAmount = round((float)$trialAmount + (float)$trial_tax, 2);
			
			//authorize.net doesn't support different periods between trial and actual
			
			if($this->TrialBillingPeriod && $this->TrialBillingPeriod != $this->BillingPeriod)
			{
				echo "F";
				return false;
			}
			
			$cardNumber = $this->accountnumber;			
			$expirationDate = $this->ExpirationDate_YdashM;						
			$cardCode = $this->CVV2;
			
			$firstName = $this->FirstName;
			$lastName = $this->LastName;

			//do address stuff then?
			$address = $this->Address1;
			if($this->Address2)
				$address .= "\n" . $this->Address2;
			$city = $this->billing->city;
			$state = $this->billing->state;
			$zip = $this->billing->zip;
			$country = $this->billing->country;						
			
			//customer stuff
			$customer_email = $this->Email;
			if(strpos($this->billing->phone, "+") === false)
				$customer_phone = $this->billing->phone;
			else
				$customer_phone = "";
				
			//make sure the phone is in an okay format
			$customer_phone = preg_replace("/[^0-9]/", "", $customer_phone);
			if(strlen($customer_phone) > 10)
				$customer_phone = "";
			
			//build xml to post
			$content =
					"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
					"<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					"<merchantAuthentication>".
					"<name>" . $loginname . "</name>".
					"<transactionKey>" . $transactionkey . "</transactionKey>".
					"</merchantAuthentication>".
					"<refId>" . $refId . "</refId>".
					"<subscription>".
					"<name>" . $name . "</name>".
					"<paymentSchedule>".
					"<interval>".
					"<length>". $length ."</length>".
					"<unit>". $unit ."</unit>".
					"</interval>".
					"<startDate>" . $startDate . "</startDate>".
					"<totalOccurrences>". $totalOccurrences . "</totalOccurrences>";
			if($trialOccurrences)
				$content .= 
					"<trialOccurrences>". $trialOccurrences . "</trialOccurrences>";
			$content .= 
					"</paymentSchedule>".
					"<amount>". $amount ."</amount>";
			if($trialOccurrences)
				$content .=
					"<trialAmount>" . $trialAmount . "</trialAmount>";
			$content .=
					"<payment>".
					"<creditCard>".
					"<cardNumber>" . $cardNumber . "</cardNumber>".
					"<expirationDate>" . $expirationDate . "</expirationDate>".
					"<cardCode>" . $cardCode . "</cardCode>".
					"</creditCard>".
					"</payment>".
					"<order><invoiceNumber>" . $this->code . "</invoiceNumber></order>".
					"<customer>".
					"<email>". $customer_email . "</email>".
					"<phoneNumber>". $customer_phone . "</phoneNumber>".
					"</customer>".
					"<billTo>".
					"<firstName>". $firstName . "</firstName>".
					"<lastName>" . $lastName . "</lastName>".
					"<address>". $address . "</address>".
					"<city>" . $city . "</city>".
					"<state>". $state . "</state>".
					"<zip>" . $zip . "</zip>".
					"<country>". $country . "</country>".					
					"</billTo>".
					"</subscription>".
					"</ARBCreateSubscriptionRequest>";
		
			//send the xml via curl
			$response = $this->send_request_via_curl($host,$path,$content);
			//if curl is unavilable you can try using fsockopen
			/*
			$response = send_request_via_fsockopen($host,$path,$content);
			*/
						
			if($response) {				
				list ($refId, $resultCode, $code, $text, $subscriptionId) = $this->parse_return($response);
				if($resultCode == "Ok")
				{
					$this->status = "success";	//saved on checkout page				
					$this->subscription_transaction_id = $subscriptionId;				
					return true;
				}
				else
				{
					$this->status = "error";
					$this->errorcode = $code;
					$this->error = $text;
					$this->shorterror = $text;									
					return false;
				}
			} else  {				
				$this->status = "error";
				$this->error = "Could not connect to Authorize.net";
				$this->shorterror = "Could not connect to Authorize.net";
				return false;				
			}
		}			
		
		function cancelWithAuthorizeNet()
		{
			//define variables to send					
			$subscriptionId = $this->subscription_transaction_id;
			$loginname = pmpro_getOption("loginname");
			$transactionkey = pmpro_getOption("transactionkey");
		
			$gateway_environment = $this->gateway_environment;
			if(!$gateway_environment)
				$gateway_environment = pmpro_getOption("gateway_environment");
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
					$this->updateStatus("cancelled");					
					return true;
				}
				else
				{
					//$this->status = "error";
					$this->errorcode = $code;
					$this->error = $text;
					$this->shorterror = $text;
					return false;
				}
			} 
			else  
			{								
				$this->status = "error";
				$this->error = "Could not connect to Authorize.net";
				$this->shorterror = "Could not connect to Authorize.net";
				return false;				
			}
		}
		
		function updateWithAuthorizeNet()
		{		
			//define variables to send					
			$gateway_environment = $this->gateway_environment;
			if(!$gateway_environment)
				$gateway_environment = pmpro_getOption("gateway_environment");
			if($gateway_environment == "live")
					$host = "api.authorize.net";		
				else
					$host = "apitest.authorize.net";	
			
			$path = "/xml/v1/request.api";
			
			$loginname = pmpro_getOption("loginname");
			$transactionkey = pmpro_getOption("transactionkey");
			
			$amount = $this->PaymentAmount;
			$refId = $this->code;
			$subscriptionId = $this->subscription_transaction_id;			
			
			$cardNumber = $this->accountnumber;			
			$expirationDate = $this->ExpirationDate_YdashM;						
			$cardCode = $this->CVV2;
			
			$firstName = $this->FirstName;
			$lastName = $this->LastName;

			//do address stuff then?
			$address = $this->Address1;
			if($this->Address2)
				$address .= "\n" . $this->Address2;
			$city = $this->billing->city;
			$state = $this->billing->state;
			$zip = $this->billing->zip;
			$country = $this->billing->country;						
			
			//customer stuff
			$customer_email = $this->Email;
			if(strpos($this->billing->phone, "+") === false)
				$customer_phone = $this->billing->phone;
			
			//build xml to post
			$content =
					"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
					"<ARBUpdateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
					"<merchantAuthentication>".
					"<name>" . $loginname . "</name>".
					"<transactionKey>" . $transactionkey . "</transactionKey>".
					"</merchantAuthentication>".
					"<refId>" . $refId . "</refId>".
					"<subscriptionId>" . $subscriptionId . "</subscriptionId>".
					"<subscription>".																	
					"<payment>".
					"<creditCard>".
					"<cardNumber>" . $cardNumber . "</cardNumber>".
					"<expirationDate>" . $expirationDate . "</expirationDate>".
					"<cardCode>" . $cardCode . "</cardCode>".
					"</creditCard>".
					"</payment>".
					"<customer>".
					"<email>". $customer_email . "</email>".
					"<phoneNumber>". formatPhone($customer_phone) . "</phoneNumber>".
					"</customer>".
					"<billTo>".
					"<firstName>". $firstName . "</firstName>".
					"<lastName>" . $lastName . "</lastName>".
					"<address>". $address . "</address>".
					"<city>" . $city . "</city>".
					"<state>". $state . "</state>".
					"<zip>" . $zip . "</zip>".
					"<country>". $country . "</country>".					
					"</billTo>".
					"</subscription>".
					"</ARBUpdateSubscriptionRequest>";
		
			//send the xml via curl
			$response = $this->send_request_via_curl($host,$path,$content);
			//if curl is unavilable you can try using fsockopen
			/*
			$response = send_request_via_fsockopen($host,$path,$content);
			*/
			
			
			if($response) {				
				list ($resultCode, $code, $text, $subscriptionId) = $this->parse_return($response);		
				
				if($resultCode == "Ok" || $code == "Ok")
				{					
					return true;
				}
				else
				{
					$this->status = "error";
					$this->errorcode = $code;
					$this->error = $text;
					$this->shorterror = $text;
					return false;
				}
			} else  {				
				$this->status = "error";
				$this->error = "Could not connect to Authorize.net";
				$this->shorterror = "Could not connect to Authorize.net";
				return false;				
			}
		}
	}
?>
