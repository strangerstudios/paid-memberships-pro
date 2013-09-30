<?php	
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	if(!class_exists("Braintree"))
		require_once(dirname(__FILE__) . "/../../includes/lib/Braintree/Braintree.php");
	class PMProGateway_braintree
	{
		function PMProGateway_braintree($gateway = NULL)
		{
			$this->gateway = $gateway;
			$this->gateway_environment = pmpro_getOption("gateway_environment");
			
			//convert to braintree nomenclature
			$environment = $this->gateway_environment;
			if($environment == "live")
				$environment = "production";
			
			Braintree_Configuration::environment($environment);
			Braintree_Configuration::merchantId(pmpro_getOption("braintree_merchantid"));
			Braintree_Configuration::publicKey(pmpro_getOption("braintree_publickey"));
			Braintree_Configuration::privateKey(pmpro_getOption("braintree_privatekey"));
			
			return $this->gateway;
		}										
		
		function process(&$order)
		{			
			//check for initial payment
			if(floatval($order->InitialPayment) == 0)
			{
				//just subscribe
				return $this->subscribe($order);
			}
			else
			{
				//charge then subscribe
				if($this->charge($order))
				{
					if(pmpro_isLevelRecurring($order->membership_level))
					{						
						if($this->subscribe($order))
						{
							//yay!
							return true;
						}
						else
						{
							//try to refund initial charge
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
						$order->error = __("Unknown error: Initial payment failed.", "pmpro");
					return false;
				}
			}				
		}		
		
		function charge(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//what amount to charge?			
			$amount = $order->InitialPayment;
						
			//tax
			$order->subtotal = $amount;
			$tax = $order->getTax(true);
			$amount = round((float)$order->subtotal + (float)$tax, 2);
						
			//create a customer
			$this->getCustomer($order);
			if(empty($this->customer))
			{				
				//failed to create customer
				return false;
			}			
			
			//charge
			try
			{ 
				$response = Braintree_Transaction::sale(array(
				  'amount' => $amount,
				  'customerId' => $this->customer->id				  
				));								
			}
			catch (Exception $e)
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = "Error: " . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}
						
			if($response->success)
			{
				//successful charge			
				$transaction_id = $response->transaction->id;
				$response = Braintree_Transaction::submitForSettlement($transaction_id);
				if($response->success)
				{
					$order->payment_transaction_id = $transaction_id;				
					$order->updateStatus("success");					
					return true;		
				}
				else
				{					
					$order->errorcode = true;
					$order->error = __("Error during settlement:", "pmpro") . " " . $response->message;
					$order->shorterror = $response->message;
					return false;
				}								
			}
			else
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = __("Error during charge:", "pmpro") . " " . $response->message;
				$order->shorterror = $response->message;
				return false;
			}									
		}
		
		/*
			This function will return a Braintree customer object.			
			If $this->customer is set, it returns it.
			It first checks if the order has a subscription_transaction_id. If so, that's the customer id.
			If not, it checks for a user_id on the order and searches for a customer id in the user meta.
			If a customer id is found, it checks for a customer through the Braintree API.
			If a customer is found and there is an AccountNumber on the order passed, it will update the customer.
			If no customer is found and there is an AccountNumber on the order passed, it will create a customer.
		*/
		function getCustomer(&$order, $force = false)
		{
			global $current_user;
			
			//already have it?
			if(!empty($this->customer) && !$force)
				return $this->customer;
						
			//try based on user id	
			if(!empty($order->user_id))
				$user_id = $order->user_id;
		
			//if no id passed, check the current user
			if(empty($user_id) && !empty($current_user->ID))
				$user_id = $current_user->ID;
		
			//check for a braintree customer id
			if(!empty($user_id))
			{			
				$customer_id = get_user_meta($user_id, "pmpro_braintree_customerid", true);	
			}
					
			//check for an existing stripe customer
			if(!empty($customer_id))
			{
				try 
				{
					$this->customer = Braintree_Customer::find($customer_id);
										
					//update the customer description and card
					if(!empty($order->accountnumber))
					{
						$response = Braintree_Customer::update(
						  $customer_id,
						  array(
							'firstName' => $order->FirstName,
							'lastName' => $order->LastName,
							'creditCard' => array(
								'number' => $order->braintree->number,
								'expirationDate' => $order->braintree->expiration_date,
								'cardholderName' => trim($order->FirstName . " " . $order->LastName),
								'options' => array(
									'updateExistingToken' => $customer_id
								)
							 )
						  )
						);

						if($response->success)
						{
							$this->customer = $result->customer;
						}
						else
						{
							$order->error = __("Failed to update customer.", "pmpro");
							$order->shorterror = $order->error;
							return false;
						}
					}
					
					return $this->customer;
				} 
				catch (Exception $e) 
				{
					//assume no customer found							
				}
			}
			
			//no customer id, create one
			if(!empty($order->accountnumber))
			{
				try
				{					
					$result = Braintree_Customer::create(array(
						'firstName' => $order->FirstName,
						'lastName' => $order->LastName,
						'email' => $order->Email,
						'phone' => $order->billing->phone,
						'creditCard' => array(
							'number' => $order->braintree->number,
							'expirationDate' => $order->braintree->expiration_date,
							'cvv' => $order->braintree->cvv,
							'cardholderName' =>  trim($order->FirstName . " " . $order->LastName),
							'billingAddress' => array(
								'firstName' => $order->FirstName,
								'lastName' => $order->LastName,
								'streetAddress' => $order->Address1,
								'extendedAddress' => $order->Address2,
								'locality' => $order->billing->city,
								'region' => $order->billing->state,
								'postalCode' => $order->billing->zip,
								'countryCodeAlpha2' => $order->billing->country
							)
						)
					));
					
					if($result->success)
					{
						$this->customer = $result->customer;
					}
					else
					{
						$order->error = __("Failed to create customer.", "pmpro");
						$order->shorterror = $order->error;
						return false;
					}										
				}
				catch (Exception $e)
				{					
					$order->error = __("Error creating customer record with Braintree:", "pmpro") . " " . $e->getMessage();
					$order->shorterror = $order->error;
					return false;
				}
				
				update_user_meta($user_id, "pmpro_braintree_customerid", $this->customer->id);					
				return $this->customer;
			}
			
			return false;			
		}
		
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//setup customer
			$this->getCustomer($order);
			if(empty($this->customer))
				return false;	//error retrieving customer
						
			//figure out the amounts
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);			
			$amount = round((float)$amount + (float)$amount_tax, 2);

			/*
				There are two parts to the trial. Part 1 is simply the delay until the first payment
				since we are doing the first payment as a separate transaction.
				The second part is the actual "trial" set by the admin.
				
				Stripe only supports Year or Month for billing periods, but we account for Days and Weeks just in case.
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
									
			//subscribe to the plan
			try
			{				
				$details = array(
				  'paymentMethodToken' => $this->customer->creditCards[0]->token,
				  'planId' => 'pmpro_' . $order->membership_id,
				  'price' => $amount				  
				);
				
				if(!empty($trial_period_days))
				{
					$details['trialPeriod'] = true;
					$details['trialDuration'] = $trial_period_days;
					$details['trialDurationUnit'] = "day";
				}
				
				if(!empty($order->TotalBillingCycles))
					$details['numberOfBillingCycles'] = $order->TotalBillingCycles;
				
				$result = Braintree_Subscription::create($details);								
			}
			catch (Exception $e)
			{				
				$order->error = __("Error subscribing customer to plan with Braintree:", "pmpro") . " " . $e->getMessage();
				//return error
				$order->shorterror = $order->error;
				return false;
			}
			
			if($result->success)
			{			
				//if we got this far, we're all good						
				$order->status = "success";		
				$order->subscription_transaction_id = $result->subscription->id;
				return true;
			}
			else
			{
				$order->error = __("Failed to subscribe with Braintree:", "pmpro") . " " . $result->message;
				$order->shorterror = $result->message;
				return false;
			}	
		}	
		
		function update(&$order)
		{
			//we just have to run getCustomer which will look for the customer and update it with the new token
			$this->getCustomer($order);
			
			if(!empty($this->customer))
			{
				return true;
			}			
			else
			{
				return false;	//couldn't find the customer
			}
		}
		
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//find the customer			
			if(!empty($order->subscription_transaction_id))
			{
				//cancel
				try 
				{ 
					$result = Braintree_Subscription::cancel($order->subscription_transaction_id);
				}
				catch(Exception $e)
				{
					$order->updateStatus("cancelled");	//assume it's been cancelled already
					$order->error = __("Could not find the subscription.", "pmpro");
					$order->shorterror = $order->error;
					return false;	//no subscription found	
				}
				
				if($result->success)
				{
					$order->updateStatus("cancelled");					
					return true;
				}
				else
				{
					$order->updateStatus("cancelled");	//assume it's been cancelled already
					$order->error = __("Could not find the subscription.", "pmpro");
					$order->shorterror = $order->error;
					return false;	//no subscription found	
				}
			}
			else
			{
				$order->error = __("Could not find the subscription.", "pmpro");
				$order->shorterror = $order->error;
				return false;	//no customer found
			}						
		}	
	}
