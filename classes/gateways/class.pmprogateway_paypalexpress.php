<?php
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	class PMProGateway_paypalexpress
	{
		function PMProGateway_paypalexpress($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										
		
		function process(&$order)
		{				
			if(pmpro_isLevelRecurring($order->membership_level))
			{
				$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod)) . "T0:0:0";
				$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
				return $this->subscribe($order);				
			}
			else
				return $this->charge($order);	
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
			$order->subtotal = $amount;
			$amount = round((float)$amount + (float)$amount_tax, 2);
						
			//paypal profile stuff
			$nvpStr = "";
			$nvpStr .="&AMT=" . $initial_payment . "&CURRENCYCODE=" . $pmpro_currency;
			if(!empty($order->ProfileStartDate) && strtotime($order->ProfileStartDate) > 0)
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
			
			$nvpStr = apply_filters("pmpro_set_express_checkout_nvpstr", $nvpStr, $order);						
			
			///echo str_replace("&", "&<br />", $nvpStr);
			///exit;
			
			$this->httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $nvpStr);					
						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"])) {
				$order->status = "token";				
				$order->paypal_token = urldecode($this->httpParsedResponseAr['TOKEN']);
				$order->subscription_transaction_id = urldecode($this->httpParsedResponseAr['PROFILEID']);
												
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
			if($order->TotalBillingCycles)
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
	}