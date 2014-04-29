<?php	
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	
	//load Stripe library if it hasn't been loaded already (usually by another plugin using Stripe)
	if(!class_exists("Stripe"))
		require_once(dirname(__FILE__) . "/../../includes/lib/Stripe/Stripe.php");
	
	//load classes init method
	add_action('init', array('PMProGateway_stripe', 'init'));
		
	/**
	 * PMProGateway_stripe Class
	 *
	 * Handles Stripe integration.
	 *
	 * @since  1.4
	 */
	class PMProGateway_stripe extends PMProGateway
	{
		/**
		 * Stripe Class Constructor
		 *		 
		 * @since 1.4
		 */
		function PMProGateway_stripe($gateway = NULL)
		{
			$this->gateway = $gateway;
			$this->gateway_environment = pmpro_getOption("gateway_environment");
			
			Stripe::setApiKey(pmpro_getOption("stripe_secretkey"));
			
			return $this->gateway;
		}										
		
		/**
		 * Run on WP init
		 *		 
		 * @since 2.0
		 */
		static function init()
		{			
			add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_stripe', 'user_profile_fields'));
		}
		
		/**
		 * Fields shown on edit user page
		 *		 
		 * @since 2.0
		 */
		static function user_profile_fields($user)
		{
			global $wpdb, $current_user, $pmpro_currency_symbol;

			$cycles = array( __('Day(s)', 'pmpro') => 'Day', __('Week(s)', 'pmpro') => 'Week', __('Month(s)', 'pmpro') => 'Month', __('Year(s)', 'pmpro') => 'Year' );
			$current_year = date("Y");
			$current_month = date("m");
			
			//make sure the current user has privileges
			$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
			if(!current_user_can($membership_level_capability))
				return false;

			//more privelges they should have			
			$show_membership_level = apply_filters("pmpro_profile_show_membership_level", true, $user);
			if(!$show_membership_level)
				return false;
				
			//check that user has a current subscription at Stripe
			$sub = true;
			
			if(!empty($sub))
			{
				$uwhen = "no";	///testing
			?>
			<h3><?php _e("Subscription Updates", "pmpro"); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="membership_level"><?php _e("Update", "pmpro"); ?></label></th>
					<td>
						<select id="updates_when" name="updates_when[]">
							<option value="no">No</option>
							<option value="now">Now</option>
							<option value="payment">After Next Payment</option>
							<option value="date">On Date</option>
						</select>
						<span id="updates_date" <?php if($uwhen != "date") { ?>style="display: none;"<?php } ?>>														
							<select name="updates_date_month[]">
								<?php																
									for($i = 1; $i < 13; $i++)
									{
									?>
									<option value="<?php echo $i?>" <?php if($i == $udate_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year))?></option>
									<?php
									}
								?>
							</select>
							<input name="updates_date_day[]"" type="text" size="2" value="" />
							<input name="updates_date_year[]" type="text" size="4" value="" />
						</span>
						<span id="updates_billing" <?php if($uwhen == "no") { ?>style="display: none;"<?php } ?>>
							<?php echo $pmpro_currency_symbol?><input name="updates_billing_amount[]" type="text" size="10" value="" /> 
							<small><?php _e('per', 'pmpro');?></small>
							<input name="updates_cycle_number[]" type="text" size="5" value="" />
							<select name="updates_cycle_period[]">
							  <?php							
								foreach ( $cycles as $name => $value ) {
								  echo "<option value='$value'";
								  if ( $uv == $value ) echo " selected='selected'";
								  echo ">$name</option>";
								}
							  ?>
							</select>
						</span>	
						<span id="updates_add">
							<a href="#">+ Update</a>
						</span>								
						<script>
							jQuery(document).ready(function() {
								jQuery('#updates_when').change(function() {
									if(jQuery(this).val() == 'date')
										jQuery('#updates_date').show();
									else
										jQuery('#updates_date').hide();
										
									if(jQuery(this).val() == 'no')
										jQuery('#updates_billing').hide();
									else
										jQuery('#updates_billing').show();
								});
							});
						</script>
					</td>
				</tr>				
			</table>
			<?php
			}
		}
		
		/**
		 * Process checkout and decide if a charge and or subscribe is needed
		 *		 
		 * @since 1.4
		 */
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
		
		/**
		 * Make a one-time charge with Stripe
		 *		 
		 * @since 1.4
		 */
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
				
		/**
		 * Get a Stripe customer object.
		 *		 
		 * If $this->customer is set, it returns it.
		 * It first checks if the order has a subscription_transaction_id. If so, that's the customer id.
		 * If not, it checks for a user_id on the order and searches for a customer id in the user meta.
		 * If a customer id is found, it checks for a customer through the Stripe API.
		 * If a customer is found and there is a stripeToken on the order passed, it will update the customer.
		 * If no customer is found and there is a stripeToken on the order passed, it will create a customer.
		 *
		 * @since 1.4
		 * @return Stripe_Customer|false
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
						$name = trim($order->FirstName . " " . $order->LastName);

						if (empty($name))
						{
							$name = trim($current_user->first_name . " " . $current_user->last_name);
						}

						$this->customer->description = $name . " (" . $order->Email . ")";
						$this->customer->email = $order->Email;
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
							  "email" => $order->Email,
							  "card" => $order->stripeToken
							));
				}
				catch (Exception $e)
				{
					$order->error = __("Error creating customer record with Stripe:", "pmpro") . " " . $e->getMessage();
					$order->shorterror = $order->error;
					return false;
				}
				
				if(!empty($user_id))
				{
					//user logged in/etc
					update_user_meta($user_id, "pmpro_stripe_customerid", $this->customer->id);	
				}
				else
				{
					//user not registered yet, queue it up
					global $pmpro_stripe_customer_id;
					$pmpro_stripe_customer_id = $this->customer->id;
					function pmpro_user_register_stripe_customerid($user_id)
					{
						global $pmpro_stripe_customer_id;
						update_user_meta($user_id, "pmpro_stripe_customerid", $pmpro_stripe_customer_id);
					}
					add_action("user_register", "pmpro_user_register_stripe_customerid");
				}
				
				return $this->customer;
			}
			
			return false;			
		}
		
		/**
		 * Create a new subscription with Stripe
		 *		 
		 * @since 1.4
		 */
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
			
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
				$order->error = __("Error creating plan with Stripe:", "pmpro") . $e->getMessage();
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
				$order->error = __("Error subscribing customer to plan with Stripe:", "pmpro") . $e->getMessage();
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
		
		/**
		 * Helper method to update the customer info via getCustomer
		 *		 
		 * @since 1.4
		 */
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
		
		/**
		 * Cancel a subscription at Stripe
		 *		 
		 * @since 1.4
		 */
		function cancel(&$order)
		{
			//no matter what happens below, we're going to cancel the order in our system
			$order->updateStatus("cancelled");
		
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//find the customer
			$this->getCustomer($order);									
			
			if(!empty($this->customer))
			{
				//find subscription with this order code
				$subscriptions = $this->customer->subscriptions->all();												
				
				if(!empty($subscriptions))
				{
					//in case only one is returned
					if(!is_array($subscriptions))
						$subscriptions = array($subscriptions);
				
					foreach($subscriptions as $sub)
					{						
						if($sub->data[0]->plan->id == $order->code)
						{
							//found it, cancel it
							try 
							{								
								$this->customer->subscriptions->retrieve($sub->data[0]->id)->cancel();
								break;
							}
							catch(Exception $e)
							{								
								$order->error = __("Could not cancel old subscription.", "pmpro");
								$order->shorterror = $order->error;
																
								return false;
							}
						}
					}
				}															
				
				return true;
			}
			else
			{
				$order->error = __("Could not find the subscription.", "pmpro");
				$order->shorterror = $order->error;
				return false;	//no customer found
			}						
		}	
	}