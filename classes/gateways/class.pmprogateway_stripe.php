<?php	
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	if(!class_exists("Stripe"))
		require_once(dirname(__FILE__) . "/../../includes/lib/Stripe/Stripe.php");
	class PMProGateway_stripe
	{
		function PMProGateway_stripe($gateway = NULL)
		{
			$this->gateway = $gateway;
			$this->gateway_environment = pmpro_getOption("gateway_environment");
			
			Stripe::setApiKey(pmpro_getOption("stripe_secretkey"));
			
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
						$order->error = "Unknown error: Initial payment failed.";
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
				$response = Stripe_Charge::create(array(
				  "amount" => $amount * 100, # amount in cents, again
				  "currency" => strtolower(pmpro_getOption("currency")),
				  "customer" => $this->customer->id,
				  "description" => "Order #" . $order->code . ", " . trim($order->FirstName . " " . $order->LastName) . " (" . $order->Email . ")"
				  )
				);
			}
			catch (Exception $e)
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = "Error: " . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}
			
			if(empty($response["failure_message"]))
			{
				//successful charge
				$order->payment_transaction_id = $response["id"];
				$order->updateStatus("success");					
				return true;		
			}
			else
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = $response['failure_message'];
				$order->shorterror = $response['failure_message'];
				return false;
			}									
		}
		
		/*
			This function will return a Stripe customer object.			
			If $this->customer is set, it returns it.
			It first checks if the order has a subscription_transaction_id. If so, that's the customer id.
			If not, it checks for a user_id on the order and searches for a customer id in the user meta.
			If a customer id is found, it checks for a customer through the Stripe API.
			If a customer is found and there is a stripeToken on the order passed, it will update the customer.
			If no customer is found and there is a stripeToken on the order passed, it will create a customer.
		*/
		function getCustomer(&$order, $force = false)
		{
			global $current_user;
			
			//already have it?
			if(!empty($this->customer) && !$force)
				return $this->customer;
			
			//transaction id?
			if(!empty($order->subscription_transaction_id))
				$customer_id = $order->subscription_transaction_id;
			else
			{
				//try based on user id	
				if(!empty($order->user_id))
					$user_id = $order->user_id;
			
				//if no id passed, check the current user
				if(empty($user_id) && !empty($current_user->ID))
					$user_id = $current_user->ID;
			
				//check for a stripe customer id
				if(!empty($user_id))
				{			
					$customer_id = get_user_meta($user_id, "pmpro_stripe_customerid", true);	
				}
			}
			
			//check for an existing stripe customer
			if(!empty($customer_id))
			{
				try 
				{
					$this->customer = Stripe_Customer::retrieve($customer_id);
					
					//update the customer description and card
					if(!empty($order->stripeToken))
					{
						$this->customer->description = trim($order->FirstName . " " . $order->LastName) . " (" . $order->Email . ")";
						$this->customer->card = $order->stripeToken;
						$this->customer->save();
					}
					
					return $this->customer;
				} 
				catch (Exception $e) 
				{
					//assume no customer found					
				}
			}
			
			//no customer id, create one
			if(!empty($order->stripeToken))
			{
				try
				{
					$this->customer = Stripe_Customer::create(array(
							  "description" => trim($order->FirstName . " " . $order->LastName) . " (" . $order->Email . ")",
							  "card" => $order->stripeToken
							));
				}
				catch (Exception $e)
				{
					$order->error = "Error creating customer record with Stripe: " . $e->getMessage();
					$order->shorterror = $order->error;
					return false;
				}
				
				update_user_meta($user_id, "pmpro_stripe_customerid", $this->customer->id);	
				
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
			$order->subtotal = $amount;
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
			
			//create a plan
			try
			{						
				$plan = Stripe_Plan::create(array(
				  "amount" => $amount * 100,
				  "interval_count" => $order->BillingFrequency,
				  "interval" => strtolower($order->BillingPeriod),
				  "trial_period_days" => $trial_period_days,
				  "name" => $order->membership_name . " for order " . $order->code,
				  "currency" => strtolower(pmpro_getOption("currency")),
				  "id" => $order->code)
				);
			}
			catch (Exception $e)
			{
				$order->error = "Error creating plan with Stripe:" . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}
			
			//subscribe to the plan
			try
			{				
				$this->customer->updateSubscription(array("prorate" => false, "plan" => $order->code));
			}
			catch (Exception $e)
			{
				//try to delete the plan
				$plan->delete();
				
				//return error
				$order->error = "Error subscribing customer to plan with Stripe:" . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}
			
			//delete the plan
			$plan = Stripe_Plan::retrieve($plan['id']);
			$plan->delete();		

			//if we got this far, we're all good						
			$order->status = "success";		
			$order->subscription_transaction_id = $this->customer['id'];	//transaction id is the customer id, we save it in user meta later too			
			return true;
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
			$this->getCustomer($order);
			
			if(!empty($this->customer))
			{
				//cancel
				try 
				{ 
					$this->customer->cancelSubscription();								
				}
				catch(Exception $e)
				{
					$order->updateStatus("cancelled");	//assume it's been cancelled already
					$order->error = "Could not find the subscription.";
					$order->shorterror = $order->error;
					return false;	//no subscription found	
				}
				
				$order->updateStatus("cancelled");					
				return true;
			}
			else
			{
				$order->error = "Could not find the subscription.";
				$order->shorterror = $order->error;
				return false;	//no customer found
			}						
		}	
	}
?>
