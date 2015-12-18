<?php	
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	
	//load classes init method
	add_action('init', array('PMProGateway_cybersource', 'init'));
	
	class PMProGateway_cybersource extends PMProGateway
	{
		function __construct($gateway = NULL)
		{
			if(!class_exists("CyberSourceSoapClient"))
				require_once(dirname(__FILE__) . "/../../includes/lib/CyberSource/cyber_source_soap_client.php");
			
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
			//make sure CyberSource is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_cybersource', 'pmpro_gateways'));
			
			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_cybersource', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_cybersource', 'pmpro_payment_option_fields'), 10, 2);						
		}
		
		/**
		 * Make sure this gateway is in the gateways list
		 *		 
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['cybersource']))
				$gateways['cybersource'] = __('CyberSource', 'pmpro');
		
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
				'cybersource_merchantid',
				'cybersource_securitykey',
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
			//get stripe options
			$cybersource_options = PMProGateway_cybersource::getGatewayOptions();
			
			//merge with others.
			$options = array_merge($cybersource_options, $options);
			
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
		<tr class="pmpro_settings_divider gateway gateway_cybersource" <?php if($gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<?php _e('CyberSource Settings', 'pmpro'); ?>
			</td>
		</tr>
		<tr class="gateway gateway_cybersource" <?php if($gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<strong><?php _e('Note', 'pmpro');?>:</strong> <?php _e('This gateway option is in beta. Some functionality may not be available. Please contact Paid Memberships Pro with any issues you run into. <strong>Please be sure to upgrade Paid Memberships Pro to the latest versions when available.</strong>', 'pmpro');?>
			</td>	
		</tr>
		<tr class="gateway gateway_cybersource" <?php if($gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="cybersource_merchantid"><?php _e('Merchant ID', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="cybersource_merchantid" name="cybersource_merchantid" size="60" value="<?php echo esc_attr($values['cybersource_merchantid'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_cybersource" <?php if($gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="cybersource_securitykey"><?php _e('Transaction Security Key', 'pmpro');?>:</label>
			</th>
			<td>
				<textarea id="cybersource_securitykey" name="cybersource_securitykey" rows="3" cols="80"><?php echo esc_textarea($values['cybersource_securitykey']);?></textarea>					
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
			}	
		}
	
		function getCardType($name)
		{
			$card_types = array(
				'Visa' => '001',
				'MasterCard' => '002',
				'Master Card' => '002',
				'AMEX' => '003',
				'American Express' => '003',
				'Discover' => '004',
				'Diners Club' => '005',
				'Carte Blanche' => '006',
				'JCB' => '007'				
			);
			
			if(isset($card_types[$name]))
				return $card_types[$name];
			else
				return false;
		}
	
		function getWSDL($order)
		{
			//which gateway environment?
			if(empty($order->gateway_environment))
				$gateway_environment = pmpro_getOption("gateway_environment");
			else
				$gateway_environment = $order->gateway_environment;
			
			//which host?
			if($gateway_environment == "live")
					$host = "ics2ws.ic3.com";		
				else
					$host = "ics2wstest.ic3.com";	

			//path
			$path = "/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.90.wsdl";												
			
			//build url
			$wsdl_url = "https://" . $host . $path;

			//filter
			$wsdl_url = apply_filters("pmpro_cybersource_wsdl_url", $wsdl_url, $gateway_environment);
			
			return $wsdl_url;
		}
		
		function authorize(&$order)
		{
			global $pmpro_currency;
			
			if(empty($order->code))
				$order->code = $order->getRandomCode();
						
			$wsdl_url = $this->getWSDL($order);
						
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
			
			//to store our request
			$request = new stdClass();
			
			//which service?
			$ccAuthService = new stdClass();
			$ccAuthService->run = "true";
			$request->ccAuthService = $ccAuthService;
			
			//merchant id and order code
			$request->merchantID = pmpro_getOption("cybersource_merchantid");
			$request->merchantReferenceCode = $order->code;
			
			//bill to
			$billTo = new stdClass();
			$billTo->firstName = $order->FirstName;
			$billTo->lastName = $order->LastName;
			$billTo->street1 = $address;
			$billTo->city = $order->billing->city;
			$billTo->state = $order->billing->state;
			$billTo->postalCode = $order->billing->zip;
			$billTo->country = $order->billing->country;
			$billTo->email = $order->Email;
			$billTo->ipAddress = $_SERVER['REMOTE_ADDR'];
			$request->billTo = $billTo;
			
			//card
			$card = new stdClass();
			$card->cardType = $this->getCardType($order->cardtype);
			$card->accountNumber = $order->accountnumber;
			$card->expirationMonth = $order->expirationmonth;
			$card->expirationYear = $order->expirationyear;
			$card->cvNumber = $order->CVV2;
			$request->card = $card;

			//currency
			$purchaseTotals = new stdClass();
			$purchaseTotals->currency = $pmpro_currency;
			$request->purchaseTotals = $purchaseTotals;

			//item/price
			$item0 = new stdClass();
			$item0->unitPrice = $amount;
			$item0->quantity = "1";
			$item0->productName = $order->membership_level->name . " Membership";
			$item0->productSKU = $order->membership_level->id;
			$item0->id = $order->membership_id;			
			$request->item = array($item0);
						
			$soapClient = new CyberSourceSoapClient($wsdl_url, array("merchantID"=>pmpro_getOption("cybersource_merchantid"), "transactionKey"=>pmpro_getOption("cybersource_securitykey")));
			$reply = $soapClient->runTransaction($request);
			
			if($reply->reasonCode == "100")
			{
				//success
				$order->payment_transaction_id = $reply->requestID;
				$order->updateStatus("authorized");									
				return true;
			}
			else
			{
				//error
				$order->errorcode = $reply->reasonCode;
				$order->error = $this->getErrorFromCode($reply->reasonCode);
				$order->shorterror = $this->getErrorFromCode($reply->reasonCode);
				return false;
			}			
		}
		
		function void(&$order)
		{
			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;
			
			//get wsdl
			$wsdl_url = $this->getWSDL($order);
			
			//to store our request
			$request = new stdClass();
			
			//which service?
			$voidService = new stdClass();
			$voidService->run = "true";
			$voidService->voidRequestID = $order->payment_transaction_id;
			$request->voidService = $voidService;
			
			//merchant id and order code
			$request->merchantID = pmpro_getOption("cybersource_merchantid");
			$request->merchantReferenceCode = $order->code;			
			
			$soapClient = new CyberSourceSoapClient($wsdl_url, array("merchantID"=>pmpro_getOption("cybersource_merchantid"), "transactionKey"=>pmpro_getOption("cybersource_securitykey")));
			$reply = $soapClient->runTransaction($request);
						
			if($reply->reasonCode == "100")
			{
				//success
				$order->payment_transaction_id = $reply->requestID;
				$order->updateStatus("voided");					
				return true;
			}
			else
			{
				//error
				$order->errorcode = $reply->reasonCode;
				$order->error = $this->getErrorFromCode($reply->reasonCode);
				$order->shorterror = $this->getErrorFromCode($reply->reasonCode);
				return false;
			}		
		}	
		
		function charge(&$order)
		{
			global $pmpro_currency;
			
			//get a code
			if(empty($order->code))
				$order->code = $order->getRandomCode();
						
			//get wsdl
			$wsdl_url = $this->getWSDL($order);
						
			//what amount to charge?			
			$amount = $order->InitialPayment;
						
			//tax
			$order->subtotal = $amount;
			$tax = $order->getTax(true);
			$amount = round((float)$order->subtotal + (float)$tax, 2);
			
			//combine address			
			$address = $order->Address1;
			if(!empty($order->Address2))
				$address .= "\n" . $order->Address2;
				
			//customer stuff
			$customer_email = $order->Email;
			$customer_phone = $order->billing->phone;
			
			if(!isset($order->membership_level->name))
				$order->membership_level->name = "";
						
			//to store our request
			$request = new stdClass();
						
			//authorize and capture			
			$ccAuthService = new stdClass();
			$ccAuthService->run = "true";
			$request->ccAuthService = $ccAuthService;
			
			$ccCaptureService = new stdClass();
			$ccCaptureService->run = "true";
			$request->ccCaptureService = $ccCaptureService;
			
			//merchant id and order code
			$request->merchantID = pmpro_getOption("cybersource_merchantid");
			$request->merchantReferenceCode = $order->code;
			
			//bill to
			$billTo = new stdClass();
			$billTo->firstName = $order->FirstName;
			$billTo->lastName = $order->LastName;
			$billTo->street1 = $address;
			$billTo->city = $order->billing->city;
			$billTo->state = $order->billing->state;
			$billTo->postalCode = $order->billing->zip;
			$billTo->country = $order->billing->country;
			$billTo->email = $order->Email;
			$billTo->ipAddress = $_SERVER['REMOTE_ADDR'];
			$request->billTo = $billTo;
			
			//card
			$card = new stdClass();
			$card->cardType = $this->getCardType($order->cardtype);
			$card->accountNumber = $order->accountnumber;
			$card->expirationMonth = $order->expirationmonth;
			$card->expirationYear = $order->expirationyear;
			$card->cvNumber = $order->CVV2;
			$request->card = $card;

			//currency
			$purchaseTotals = new stdClass();
			$purchaseTotals->currency = $pmpro_currency;
			$request->purchaseTotals = $purchaseTotals;

			//item/price
			$item0 = new stdClass();
			$item0->unitPrice = $amount;
			$item0->quantity = "1";
			$item0->productName = $order->membership_level->name . " Membership";
			$item0->productSKU = $order->membership_level->id;
			$item0->id = $order->membership_id;			
			$request->item = array($item0);
						
			$soapClient = new CyberSourceSoapClient($wsdl_url, array("merchantID"=>pmpro_getOption("cybersource_merchantid"), "transactionKey"=>pmpro_getOption("cybersource_securitykey")));
			$reply = $soapClient->runTransaction($request);
						
			if($reply->reasonCode == "100")
			{
				//success
				$order->payment_transaction_id = $reply->requestID;
				$order->updateStatus("success");									
				return true;
			}
			else
			{
				//error
				$order->errorcode = $reply->reasonCode;
				$order->error = $this->getErrorFromCode($reply->reasonCode);
				$order->shorterror = $this->getErrorFromCode($reply->reasonCode);
				return false;
			}					
		}
		
		function subscribe(&$order)
		{
			global $currency;
			
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
			
			//get wsdl
			$wsdl_url = $this->getWSDL($order);
			
			//to store our request
			$request = new stdClass();
			
			//set service type
			$paySubscriptionCreateService = new stdClass();
			$paySubscriptionCreateService->run = 'true';
			$paySubscriptionCreateService->disableAutoAuth = 'true';	//we do our own auth check
			$request->paySubscriptionCreateService  = $paySubscriptionCreateService;
			
			//merchant id and order code
			$request->merchantID = pmpro_getOption("cybersource_merchantid");
			$request->merchantReferenceCode = $order->code;
			
			/*
				set up billing amount/etc
			*/
			//figure out the amounts
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);			
			$amount = round((float)$amount + (float)$amount_tax, 2);

			/*
				There are two parts to the trial. Part 1 is simply the delay until the first payment
				since we are doing the first payment as a separate transaction.
				The second part is the actual "trial" set by the admin.								
			*/
			//figure out the trial length (first payment handled by initial charge)			
			if($order->BillingPeriod == "Year")
				$trial_period_days = $order->BillingFrequency * 365;	//annual
			elseif($order->BillingPeriod == "Day")
				$trial_period_days = $order->BillingFrequency * 1;		//daily
			elseif($order->BillingPeriod == "Week")
				$trial_period_days = $order->BillingFrequency * 7;		//weekly
			else
				$trial_period_days = $order->BillingFrequency * 30;	//assume monthly
				
			//convert to a profile start date
			$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $trial_period_days . " Day", current_time("timestamp"))) . "T0:0:0";
			
			//filter the start date
			$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);			

			//convert back to days
			$trial_period_days = ceil(abs(strtotime(date("Y-m-d"), current_time('timestamp')) - strtotime($order->ProfileStartDate, current_time("timestamp"))) / 86400);

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
			$profile_start_date = date("Ymd", strtotime("+ " . $trial_period_days . " Days"));
			
			//figure out the frequency
			if($order->BillingPeriod == "Year")
			{
				$frequency = "annually";	//ignoring BillingFrequency set on level.				
			}
			elseif($order->BillingPeriod == "Month")
			{
				if($order->BillingFrequency == 6)
					$frequency = "semi annually";
				elseif($order->BillingFrequency == 3)
					$frequency = "quarterly";
				else
					$frequency = "monthly";
			}
			elseif($order->BillingPeriod == "Week")
			{
				if($order->BillingFrequency == 4)
					$frequency = "quad-weekly";
				elseif($order->BillingFrequency == 2)
					$frequency = "bi-weekly";
				else
					$frequency = "weekly";
			}
			elseif($order->BillingPeriod == "Day")
			{
				if($order->BillingFrequency == 365)
					$frequency = "annually";
				elseif($order->BillingFrequency == 182)
					$frequency = "semi annually";
				elseif($order->BillingFrequency == 183)
					$frequency = "semi annually";
				elseif($order->BillingFrequency == 90)
					$frequency = "quaterly";
				elseif($order->BillingFrequency == 30)
					$frequency = "monthly";
				elseif($order->BillingFrequency == 15)
					$frequency = "semi-monthly";
				elseif($order->BillingFrequency == 28)
					$frequency = "quad-weekly";
				elseif($order->BillingFrequency == 14)
					$frequency = "bi-weekly";
				elseif($order->BillingFrequency == 7)
					$frequency = "weekly";				
			}			
			
			//set subscription info for API
			$subscription = new stdClass();
			$subscription->title = $order->membership_level->name;
			$subscription->paymentMethod = "credit card";
			$request->subscription = $subscription;
			
			//recurring info			
			$recurringSubscriptionInfo = new stdClass();
			$recurringSubscriptionInfo->amount = number_format($amount, 2);			
			$recurringSubscriptionInfo->startDate = $profile_start_date;
			$recurringSubscriptionInfo->frequency = $frequency;
			if(!empty($order->TotalBillingCycles))
				$recurringSubscriptionInfo->numberOfPayments = $order->TotalBillingCycles;				
			$request->recurringSubscriptionInfo = $recurringSubscriptionInfo;
			
			//combine address			
			$address = $order->Address1;
			if(!empty($order->Address2))
				$address .= "\n" . $order->Address2;
			
			//bill to
			$billTo = new stdClass();
			$billTo->firstName = $order->FirstName;
			$billTo->lastName = $order->LastName;
			$billTo->street1 = $address;
			$billTo->city = $order->billing->city;
			$billTo->state = $order->billing->state;
			$billTo->postalCode = $order->billing->zip;
			$billTo->country = $order->billing->country;
			$billTo->email = $order->Email;
			$billTo->ipAddress = $_SERVER['REMOTE_ADDR'];
			$request->billTo = $billTo;
			
			//card
			$card = new stdClass();
			$card->cardType = $this->getCardType($order->cardtype);
			$card->accountNumber = $order->accountnumber;
			$card->expirationMonth = $order->expirationmonth;
			$card->expirationYear = $order->expirationyear;
			$card->cvNumber = $order->CVV2;
			$request->card = $card;

			//currency
			$purchaseTotals = new stdClass();
			$purchaseTotals->currency = $pmpro_currency;
			$request->purchaseTotals = $purchaseTotals;			
			
			$soapClient = new CyberSourceSoapClient($wsdl_url, array("merchantID"=>pmpro_getOption("cybersource_merchantid"), "transactionKey"=>pmpro_getOption("cybersource_securitykey")));
			$reply = $soapClient->runTransaction($request);
						
			if($reply->reasonCode == "100")
			{
				//success
				$order->subscription_transaction_id = $reply->requestID;
				$order->status = "success";							
				return true;
			}
			else
			{
				//error
				$order->status = "error";
				$order->errorcode = $reply->reasonCode;
				$order->error = $this->getErrorFromCode($reply->reasonCode);
				$order->shorterror = $this->getErrorFromCode($reply->reasonCode);
				return false;
			}						
		}	
		
		function update(&$order)
		{						
			//get wsdl
			$wsdl_url = $this->getWSDL($order);
			
			//to store our request
			$request = new stdClass();
			
			//set service type
			$paySubscriptionUpdateService  = new stdClass();
			$paySubscriptionUpdateService ->run = "true";			
			$request->paySubscriptionUpdateService   = $paySubscriptionUpdateService ;
			
			//merchant id and order code
			$request->merchantID = pmpro_getOption("cybersource_merchantid");
			$request->merchantReferenceCode = $order->code;
						
			//set subscription info for API
			$recurringSubscriptionInfo = new stdClass();
			$recurringSubscriptionInfo->subscriptionID  = $order->subscription_transaction_id;
			$request->recurringSubscriptionInfo = $recurringSubscriptionInfo;
			
			//combine address			
			$address = $order->Address1;
			if(!empty($order->Address2))
				$address .= "\n" . $order->Address2;
			
			//bill to
			$billTo = new stdClass();
			$billTo->firstName = $order->FirstName;
			$billTo->lastName = $order->LastName;
			$billTo->street1 = $address;
			$billTo->city = $order->billing->city;
			$billTo->state = $order->billing->state;
			$billTo->postalCode = $order->billing->zip;
			$billTo->country = $order->billing->country;
			$billTo->email = $order->Email;
			$billTo->ipAddress = $_SERVER['REMOTE_ADDR'];
			$request->billTo = $billTo;
			
			//card
			$card = new stdClass();
			$card->cardType = $this->getCardType($order->cardtype);
			$card->accountNumber = $order->accountnumber;
			$card->expirationMonth = $order->expirationmonth;
			$card->expirationYear = $order->expirationyear;
			$card->cvNumber = $order->CVV2;
			$request->card = $card;
						
			$soapClient = new CyberSourceSoapClient($wsdl_url, array("merchantID"=>pmpro_getOption("cybersource_merchantid"), "transactionKey"=>pmpro_getOption("cybersource_securitykey")));
			$reply = $soapClient->runTransaction($request);
					
			if($reply->reasonCode == "100")
			{
				//success								
				return true;
			}
			else
			{
				//error
				$order->errorcode = $reply->reasonCode;
				$order->error = $this->getErrorFromCode($reply->reasonCode);
				$order->shorterror = $this->getErrorFromCode($reply->reasonCode);
				return false;
			}	
		}
		
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//get wsdl
			$wsdl_url = $this->getWSDL($order);
			
			//to store our request
			$request = new stdClass();
			
			//which service?
			$paySubscriptionDeleteService  = new stdClass();
			$paySubscriptionDeleteService ->run = "true";			
			$request->paySubscriptionDeleteService  = $paySubscriptionDeleteService ;
			
			//which order
			$recurringSubscriptionInfo  = new stdClass();
			$recurringSubscriptionInfo->subscriptionID = $order->subscription_transaction_id;
			$request->recurringSubscriptionInfo = $recurringSubscriptionInfo;
			
			//merchant id and order code
			$request->merchantID = pmpro_getOption("cybersource_merchantid");
			$request->merchantReferenceCode = $order->code;			
			
			$soapClient = new CyberSourceSoapClient($wsdl_url, array("merchantID"=>pmpro_getOption("cybersource_merchantid"), "transactionKey"=>pmpro_getOption("cybersource_securitykey")));
			$reply = $soapClient->runTransaction($request);
								
			if($reply->reasonCode == "100")
			{
				//success
				$order->updateStatus("cancelled");				
				return true;
			}
			else
			{
				//error
				$order->errorcode = $reply->reasonCode;
				$order->error = $this->getErrorFromCode($reply->reasonCode);
				$order->shorterror = $this->getErrorFromCode($reply->reasonCode);
				return false;
			}	
		}
		
		function getErrorFromCode($code)
		{
			$error_messages = array(
				"100" => "Successful transaction.",
				"101" => "The request is missing one or more required fields.",
				"102" => "One or more fields in the request contains invalid data. Check that your billing address is valid.",
				"104" => "Duplicate order detected.",
				"110" => "Only partial amount was approved.",
				"150" => "Error: General system failure.",
				"151" => "Error: The request was received but there was a server timeout.",
				"152" => "Error: The request was received, but a service did not finish running in time. ",
				"200" => "Address Verification Service (AVS) failure.",
				"201" => "Authorization failed.",
				"202" => "Expired card or invalid expiration date.",
				"203" => "The card was declined.",
				"204" => "Insufficient funds in the account.",
				"205" => "Stolen or lost card.",
				"207" => "Issuing bank unavailable.",
				"208" => "Inactive card or card not authorized for card-not-present transactions.",
				"209" => "American Express Card Identification Digits (CID) did not match.",
				"210" => "The card has reached the credit limit. ",
				"211" => "Invalid card verification number.",
				"221" => "The customer matched an entry on the processors negative file. ",
				"230" => "Card verification (CV) check failed.",
				"231" => "Invalid account number.",
				"232" => "The card type is not accepted by the payment processor.",
				"233" => "General decline by the processor.",
				"234" => "There is a problem with your CyberSource merchant configuration.",
				"235" => "The requested amount exceeds the originally authorized amount.",
				"236" => "Processor failure.",
				"237" => "The authorization has already been reversed.",
				"238" => "The authorization has already been captured.",
				"239" => "The requested transaction amount must match the previous transaction amount.",
				"240" => "The card type sent is invalid or does not correlate with the credit card number.",
				"241" => "The referenced request id is invalid for all follow-on transactions.",
				"242" => "The request ID is invalid.",
				"243" => "The transaction has already been settled or reversed.",
				"246" => "The capture or credit is not voidable because the capture or credit information has already been submitted to your processor. Or, you requested a void for a type of transaction that cannot be voided.",
				"247" => "You requested a credit for a capture that was previously voided.",
				"250" => "Error: The request was received, but there was a timeout at the payment processor.",
				"520" => "Smart Authorization failed."			
			);
			
			if(isset($error_messages[$code]))
				return $error_messages[$code];
			else
				return "Unknown error.";
		}
	}
