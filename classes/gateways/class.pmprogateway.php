<?php	
	//require_once(dirname(__FILE__) . "/class.pmprogateway.php");
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
						$order->ProfileStartDate = date_i18n("Y-m-d") . "T0:0:0";
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
						$order->ProfileStartDate = date_i18n("Y-m-d") . "T0:0:0";														
						$order->TrialBillingCycles++;
						
						//add a billing cycle to make up for the trial, if applicable
						if($order->TotalBillingCycles)
							$order->TotalBillingCycles++;
					}
					else
					{
						//add a period to the start date to account for the initial payment
						$order->ProfileStartDate = date_i18n("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
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
							$order->ProfileStartDate = date_i18n("Y-m-d") . "T0:0:0";
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
							$order->ProfileStartDate = date_i18n("Y-m-d") . "T0:0:0";														
							$order->TrialBillingCycles++;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						else
						{
							//add a period to the start date to account for the initial payment
							$order->ProfileStartDate = date_i18n("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
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

		function get_next_payment_date( &$subscription ) {
			global $wpdb;

			// 1. Get date of most recent order for this subscription
			$morder = $subscription->get_last_order();
			if ( ! is_a( $morder, 'MemberOrder' ) || empty( $morder->timestamp ) ) {
				// No valid order found.
				return '0000-00-00 00:00:00';
			}

			// 2. Get the MU data if available, if not get level_id from order and assume default settings.
			if ( ! empty( $subscription->membership_level_id ) ) {
				$level = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE id = '$subscription->membership_level_id'" );
			} else {
				$level = new PMPro_Membership_Level( $morder->membership_id );
			}
			if ( empty( $level->cycle_number ) || empty( $level->cycle_period ) ) {
				// Level not recurring.
				return '0000-00-00 00:00:00';
			}

			// 3. Calculate next payment date
			$r = strtotime( '+' . $level->cycle_number . ' ' . $level->cycle_period, $morder->timestamp );

			/**
			 * Filter the next payment date.
			 * Should not be done for other gateways.
			 *
			 * @since 1.8.5
			 *
			 * @param mixed $r false or the next payment date timestamp
			 * @param int $user_id The user id to get the next payment date for
			 * @param string $order_status Status or array of statuses to find the last order based on.
			 */
			$r = apply_filters( 'pmpro_next_payment', $r, $morder->user_id, $morder->status );
			return get_gmt_from_date( date( 'Y-m-d H:i:s', $r ) );
		}

		function getTransactionStatus(&$order)
		{			
			//this looks different for each gateway, but generally an array of some sort
			return array();
		}
	}
