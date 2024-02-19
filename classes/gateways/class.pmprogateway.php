<?php	
	//require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	#[AllowDynamicProperties]
	class PMProGateway
	{	
		function __construct($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										
		
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
						$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");
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
						$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");														
						$order->TrialBillingCycles++;
						
						//add a billing cycle to make up for the trial, if applicable
						if($order->TotalBillingCycles)
							$order->TotalBillingCycles++;
					}
					else
					{
						//add a period to the start date to account for the initial payment
						$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp")));
					}
					
					$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
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
						if(!pmpro_isLevelTrial($order->membership_level))
						{
							//subscription will start today with a 1 period trial
							$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");
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
							$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s");														
							$order->TrialBillingCycles++;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						else
						{
							//add a period to the start date to account for the initial payment
							$order->ProfileStartDate = date_i18n("Y-m-d\TH:i:s", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp")));
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
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//simulate a successful authorization
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("authorized");													
			return true;					
		}
		
		function void(&$order)
		{
			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;
				
			//simulate a successful void
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("voided");					
			return true;
		}	
		
		function charge(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//simulate a successful charge
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("success");					
			return true;						
		}
		
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
						
			//simulate a successful subscription processing
			$order->status = "success";		
			$order->subscription_transaction_id = "TEST" . $order->code;				
			return true;
		}	
		
		function update(&$order)
		{
			//simulate a successful billing update
			return true;
		}
		
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//simulate a successful cancel			
			$order->updateStatus("cancelled");					
			return true;
		}	
		
		function getSubscriptionStatus(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//this looks different for each gateway, but generally an array of some sort
			return array();
		}

		function getTransactionStatus(&$order)
		{			
			//this looks different for each gateway, but generally an array of some sort
			return array();
		}		

		/**
		 * Check if the gateway supports a certain feature.
		 * 
		 * @since 3.0
		 * 
		 * @param string $feature The feature to check for.
		 * @return bool|string Whether the gateway supports the requested. A string may be returned in cases where a feature has different variations of support.
		 */
		public static function supports( $feature ) {
			// The base gateway doesn't support anything.			
			return false;
		}

		/**
		 * Synchronizes a subscription with this payment gateway.
		 *
		 * @since 3.0
		 *
		 * @param PMPro_Subscription $subscription The subscription to synchronize.
		 * @return string|null Error message is returned if update fails.
		 */
		public function update_subscription_info( $subscription ) {
			// Track the fields that need to be updated.
			$update_array = array();

			// Update the start date to the date of the first order for this subscription if it
			// it is earlier than the current start date.
			$oldest_orders = $subscription->get_orders( [
				'limit'   => 1,
				'orderby' => '`timestamp` ASC, `id` ASC',
			] );
			if ( ! empty( $oldest_orders ) ) {
				$oldest_order = current( $oldest_orders );
				if ( empty( $subscription->get_startdate() ) || $oldest_order->getTimestamp( true ) < strtotime( $subscription->get_startdate() ) ) {
					$update_array['startdate'] = date_i18n( 'Y-m-d H:i:s', $oldest_order->getTimestamp( true ) );
				}
			}

			// If the next payment date has passed, update the next payment date based on the most recent order.
			if ( strtotime( $subscription->get_next_payment_date() ) < time() && ! empty( $subscription->get_cycle_number() ) ) {
				// Only update the next payment date if we are not at checkout or if we don't have a next payment date yet.
				// We don't want to update profile start dates set at checkout.
				if ( ! pmpro_is_checkout() || empty( $subscription->get_next_payment_date() ) ) {
					$newest_orders = $subscription->get_orders( array( 'limit' => 1 ) );
					if ( ! empty( $newest_orders ) ) {
						// Get the most recent order.
						$newest_order = current( $newest_orders );

						// Calculate the next payment date.
						$update_array['next_payment_date'] = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $subscription->get_cycle_number() . ' ' . $subscription->get_cycle_period(), $newest_order->getTimestamp( true ) ) );
					}
				}
			}

			// Update the subscription.
			$subscription->set( $update_array );
		}
	}
