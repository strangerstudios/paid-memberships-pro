<?php
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	class PMProGateway_payflowpro
	{
		function PMProGateway_payflowpro($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										
		
		function process(&$order)
		{
			if(floatval($order->InitialPayment) == 0)
			{
				//auth first, then process
				$authorization_id = $this->authorize($order);					
				if($authorization_id)
				{
					$this->void($order, $authorization_id);											
					$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod)) . "T0:0:0";
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
					//setup recurring billing
					if(pmpro_isLevelRecurring($order->membership_level))
					{
						$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod)) . "T0:0:0";
						$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
						if($this->subscribe($order))
						{							
							return true;
						}
						else
						{							
							if($this->void($order, $order->payment_transaction_id))
							{
								if(empty($order->error))
									$order->error = __("Unknown error: Payment failed.", "pmpro");
							}
							else
							{
								if(empty($order->error))
									$order->error = __("Unknown error: Payment failed.", "pmpro");
								
								$order->error .= " " . __("A partial payment was made that we could not refund. Please contact the site owner immediately to correct this.", "pmpro");
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
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
			//$nvpStr .= "&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . $order->PaymentAmount;
			
			$nvpStr .= "&CUSTIP=" . $_SERVER['REMOTE_ADDR'] . "&INVNUM=" . $order->code;
						
			//credit card fields
			if($order->cardtype == "American Express")
				$cardtype = "Amex";
			else
				$cardtype = $order->cardtype;
			
			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . $order->CVV2;
			
			//billing address, etc
			if(!empty($order->Address1))
			{
				$nvpStr .= "&EMAIL=" . $order->Email . "&FIRSTNAME=" . $order->FirstName . "&LASTNAME=" . $order->LastName . "&STREET=" . $order->Address1;
				
				if($order->Address2)
					$nvpStr .= " " . $order->Address2;
				
				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

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
		
		function void(&$order, $authorization_id)
		{
			if(empty($authorization_id))
				return false;
			
			//paypal profile stuff
			$nvpStr="&ORIGID=" . $authorization_id;
		
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
			$amount = $order->InitialPayment;
			$amount_tax = $order->getTaxForPrice($amount);						
			$order->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
			
			//paypal profile stuff
			$nvpStr = "";			
			$nvpStr .="&AMT=" . $amount . "&TAXAMT=" . $amount_tax;			
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
			//$nvpStr .= "&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . $order->PaymentAmount;
			
			$nvpStr .= "&CUSTIP=" . $_SERVER['REMOTE_ADDR'] . "&INVNUM=" . $order->code;			
			
			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . $order->CVV2;
			
			//billing address, etc
			if($order->Address1)
			{
				$nvpStr .= "&EMAIL=" . $order->Email . "&FIRSTNAME=" . $order->FirstName . "&LASTNAME=" . $order->LastName . "&STREET=" . $order->Address1;
				
				if($order->Address2)
					$nvpStr .= " " . $order->Address2;
				
				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

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
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);						
			$order->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
			
			if($order->BillingPeriod == "Week")
				$payperiod = "WEEK";
			elseif($order->BillingPeriod == "Month")
				$payperiod = "MONT";
			elseif($order->BillingPeriod == "Year")
				$payperiod = "YEAR";
			
			//paypal profile stuff
			$nvpStr = "&ACTION=A";			
			$nvpStr .="&AMT=" . $amount . "&TAXAMT=" . $amount_tax;			
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
			//$nvpStr .= "&L_BILLINGTYPE0=RecurringPayments&L_BILLINGAGREEMENTDESCRIPTION0=" . $order->PaymentAmount;
			
			$nvpStr .= "&PROFILENAME=" . urlencode(substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127));
			
			$nvpStr .= "&PAYPERIOD=" . $payperiod;
			
			$nvpStr .= "&CUSTIP=" . $_SERVER['REMOTE_ADDR'] . "&INVNUM=" . $order->code;	

			//if billing cycles are defined						
			if(!empty($order->TotalBillingCycles))
				$nvpStr .= "&TERM=" . $order->TotalBillingCycles;
			else
				$nvpStr .= "&TERM=0";
			
			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . $order->CVV2;
			
			/*
				Let's figure out the start date. There are two parts.
				1. We need to add the billing period to the start date to account for the initial payment.
				2. We can allow for free trials by further delaying the start date of the subscription.				
			*/
			if($order->BillingPeriod == "Year")
				$trial_period_days = $order->BillingFrequency * 365;	//annual
			elseif($order->BillingPeriod == "Day")
				$trial_period_days = $order->BillingFrequency * 1;		//daily
			elseif($order->BillingPeriod == "Week")
				$trial_period_days = $order->BillingFrequency * 7;		//weekly
			else
				$trial_period_days = $order->BillingFrequency * 30;	//assume monthly
				
			//convert to a profile start date
			$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $trial_period_days . " Day")) . "T0:0:0";			
			
			//filter the start date
			$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);			

			//convert back to days
			$trial_period_days = ceil(abs(strtotime(date("Y-m-d")) - strtotime($order->ProfileStartDate)) / 86400);

			//now add the actual trial set by the site
			if(!empty($order->TrialBillingCycles))						
			{
				$trialOccurrences = (int)$order->TrialBillingCycles;
				if($order->BillingPeriod == "Year")
					$trial_period_days = $trial_period_days + (365 * $order->BillingFrequency * $trialOccurrences);	//annual
				elseif($order->BillingPeriod == "Day")
					$trial_period_days = $trial_period_days + (1 * $order->BillingFrequency * $trialOccurrences);		//daily
				elseif($order->BillingPeriod == "Week")
					$trial_period_days = $trial_period_days + (7 * $order->BillingFrequency * $trialOccurrences);	//weekly
				else
					$trial_period_days = $trial_period_days + (30 * $order->BillingFrequency * $trialOccurrences);	//assume monthly				
			}			
			
			//convert back into a date
			$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $trial_period_days . " Day")) . "T0:0:0";
			
			//start date
			$nvpStr .= "&START=" . date("mdY", strtotime($order->ProfileStartDate));
			
			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . $order->CVV2;
			
			//billing address, etc
			if($order->Address1)
			{
				$nvpStr .= "&EMAIL=" . $order->Email . "&FIRSTNAME=" . $order->FirstName . "&LASTNAME=" . $order->LastName . "&STREET=" . $order->Address1;
				
				if($order->Address2)
					$nvpStr .= " " . $order->Address2;
				
				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

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
		
			//$order->error = "Recurring subscriptions with Payflow are not currently supported by Paid Memberships Pro";
			//return false;
		}	
		
		function update(&$order)
		{
			$order->getMembershipLevel();
					
			//paypal profile stuff
			$nvpStr = "&ORIGPROFILEID=" . $order->subscription_transaction_id . "&ACTION=M";						
			$nvpStr .= "&NOTIFYURL=" . urlencode(admin_url('admin-ajax.php') . "?action=ipnhandler");
					
			$nvpStr .= "&PROFILENAME=" . urlencode(substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127));
						
			$nvpStr .= "&CUSTIP=" . $_SERVER['REMOTE_ADDR'] . "&INVNUM=" . $order->code;	
			
			if(!empty($order->accountnumber))
				$nvpStr .= "&ACCT=" . $order->accountnumber . "&EXPDATE=" . $order->expirationmonth . substr($order->expirationyear, 2, 2) . "&CVV2=" . $order->CVV2;
			
			//billing address, etc
			if($order->Address1)
			{
				$nvpStr .= "&EMAIL=" . $order->Email . "&FIRSTNAME=" . $order->FirstName . "&LASTNAME=" . $order->LastName . "&STREET=" . $order->Address1;
				
				if($order->Address2)
					$nvpStr .= " " . $order->Address2;
				
				$nvpStr .= "&CITY=" . $order->billing->city . "&STATE=" . $order->billing->state . "&BILLTOCOUNTRY=" . $order->billing->country . "&ZIP=" . $order->billing->zip . "&PHONENUM=" . $order->billing->phone;
			}

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
			
			$this->nvpStr = $nvpStr;
			$this->httpParsedResponseAr = $this->PPHttpPost('R', $nvpStr);
												
			if("0" == strtoupper($this->httpParsedResponseAr["RESULT"])) {			
				$order->updateStatus("cancelled");					
				return true;				
			} else  {				
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
		 * @param	string	The API method name
		 * @param	string	The POST Message fields in &name=value pair format
		 * @return	array	Parsed HTTP Response body
		 */
		function PPHttpPost($methodName_, $nvpStr_) {
			global $gateway_environment;
			$environment = $gateway_environment;	
		
			$PARTNER = pmpro_getOption("payflow_partner");
			$VENDOR = pmpro_getOption("payflow_vendor");
			$USER = pmpro_getOption("payflow_user");
			$PWD = pmpro_getOption("payflow_pwd");
			$API_Endpoint = "https://payflowpro.paypal.com";
			if("sandbox" === $environment || "beta-sandbox" === $environment) {
				$API_Endpoint = "https://pilot-payflowpro.paypal.com";
			}						
			
			$version = urlencode('4');
		
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
			$nvpreq = "TRXTYPE=" . $methodName_ . "&TENDER=C&PARTNER=" . $PARTNER . "&VENDOR=" . $VENDOR . "&USER=" . $USER . "&PWD=" . $PWD . "&VERBOSITY=medium" . $nvpStr_;
										
			// setting the nvpreq as POST FIELD to curl
			curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
		
			// getting response from server
			$httpResponse = curl_exec($ch);
		
			if(empty($httpResponse)) {
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
		
			if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('RESULT', $httpParsedResponseAr)) {
				exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
			}
		
			return $httpParsedResponseAr;
		}
	}