<?php
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	class PMProGateway_paypalstandard
	{
		function PMProGateway_paypalstandard($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										
		
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
			$order->subtotal = $amount;
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
					'p1'			=> '1',
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
					'sra'			=> '1'
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
					$psd = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod)) . "T0:0:0";
					$adjusted_psd = apply_filters("pmpro_profile_start_date", $psd, $order);
					if($psd != $adjusted_psd)
					{
						//someone is trying to push the start date back
						$adjusted_psd_time = strtotime($adjusted_psd);
						$seconds_til_psd = $adjusted_psd_time - time();
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
						$paypal_args['srt'] = intval($order->TotalBillingCycles) - 1;	//subtract 1 for the trial period
					else
						$paypal_args['srt'] = intval($order->TotalBillingCycles);
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
					'notify_url'    => admin_url("admin-ajax.php") . "?action=ipnhandler"
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

			//redirect to paypal			
			$paypal_url .= $nvpStr;			
			
			//die($paypal_url);
			
			wp_redirect($paypal_url);
			exit;
		}
										
		function cancel(&$order)
		{			
			//paypal profile stuff
			$nvpStr = "";			
			$nvpStr .= "&PROFILEID=" . $order->subscription_transaction_id . "&ACTION=Cancel&NOTE=User requested cancel.";							
			
			$this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);						
			if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]) || $this->httpParsedResponseAr['L_ERRORCODE0'] == "11556") {								
				$order->updateStatus("cancelled");					
				return true;
				//exit('CreateRecurringPaymentsProfile Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
			} else  {				
				$order->status = "error";
				$order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
				$order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
				$order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);
								
				return false;
				//exit('CreateRecurringPaymentsProfile failed: ' . print_r($httpParsedResponseAr, true));
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
?>
