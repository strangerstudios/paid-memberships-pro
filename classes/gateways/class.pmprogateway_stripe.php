<?php	
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");		
	
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
			
			$this->loadStripeLibrary();			
			Stripe::setApiKey(pmpro_getOption("stripe_secretkey"));
			
			return $this->gateway;
		}										
		
		/**
		 * Load the Stripe API library.
		 *		 
		 * @since 2.0
		 * Moved into a method in version 2.0 so we only load it when needed.
		 */
		function loadStripeLibrary()
		{
			//load Stripe library if it hasn't been loaded already (usually by another plugin using Stripe)
			if(!class_exists("Stripe"))
				require_once(dirname(__FILE__) . "/../../includes/lib/Stripe/Stripe.php");
		}
		
		/**
		 * Run on WP init
		 *		 
		 * @since 2.0
		 */
		static function init()
		{			
			//make sure Stripe is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_stripe', 'pmpro_gateways'));
			
			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_stripe', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_stripe', 'pmpro_payment_option_fields'), 10, 2);
			
			//add some fields to edit user page (Updates)
			add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_stripe', 'user_profile_fields'));
			add_action('profile_update', array('PMProGateway_stripe', 'user_profile_fields_save'));
		}
		
		/**
		 * Make sure Stripe is in the gateways list
		 *		 
		 * @since 2.0
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['stripe']))
				$gateways['stripe'] = __('Stripe', 'pmpro');
		
			return $gateways;
		}
		
		/**
		 * Get a list of payment options that the Stripe gateway needs/supports.
		 *		 
		 * @since 2.0
		 */
		static function getStripeOptions()
		{			
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'stripe_secretkey',
				'stripe_publishablekey',
				'stripe_billingaddress',
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
		 * @since 2.0
		 */
		static function pmpro_payment_options($options)
		{			
			//get stripe options
			$stripe_options = PMProGateway_stripe::getStripeOptions();
			
			//merge with others.
			$options = array_merge($stripe_options, $options);
			
			return $options;
		}
		
		/**
		 * Display fields for Stripe options.
		 *		 
		 * @since 2.0
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
		?>
		<tr class="pmpro_settings_divider gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>				
			<td colspan="2">
				<?php _e('Stripe Settings', 'pmpro'); ?>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_secretkey"><?php _e('Secret Key', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="stripe_secretkey" name="stripe_secretkey" size="60" value="<?php echo esc_attr($values['stripe_secretkey'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_publishablekey"><?php _e('Publishable Key', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="stripe_publishablekey" name="stripe_publishablekey" size="60" value="<?php echo esc_attr($values['stripe_publishablekey'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_billingaddress"><?php _e('Show Billing Address Fields', 'pmpro');?>:</label>
			</th>
			<td>
				<select id="stripe_billingaddress" name="stripe_billingaddress">
					<option value="0" <?php if(empty($values['stripe_billingaddress'])) { ?>selected="selected"<?php } ?>><?php _e('No', 'pmpro');?></option>
					<option value="1" <?php if(!empty($values['stripe_billingaddress'])) { ?>selected="selected"<?php } ?>><?php _e('Yes', 'pmpro');?></option>						
				</select>
				<small><?php _e("Stripe doesn't require billing address fields. Choose 'No' to hide them on the checkout page.<br /><strong>If No, make sure you disable address verification in the Stripe dashboard settings.</strong>", 'pmpro');?></small>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php _e('Web Hook URL', 'pmpro');?>:</label>
			</th>
			<td>
				<p><?php _e('To fully integrate with Stripe, be sure to set your Web Hook URL to', 'pmpro');?> <pre><?php echo admin_url("admin-ajax.php") . "?action=stripe_webhook";?></pre></p>
			</td>
		</tr>		
		<?php
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
			$last_order = new MemberOrder();
			$last_order->getLastMemberOrder($user->ID);
			
			//assume no sub to start
			$sub = false;
			
			//check that gateway is Stripe
			if($last_order->gateway == "stripe")
			{				
				//is there a customer?
				$last_order->Gateway->getCustomer($last_order);
				if(!empty($last_order->Gateway->customer))
				{					
					//find subscription with this order code
					$subscriptions = $last_order->Gateway->customer->subscriptions->all();						
					
					if(!empty($subscriptions))
						$sub = true;
				}				
			}			
			
			if(empty($sub))
			{
				//make sure we delete stripe updates
				update_user_meta($user->ID, "pmpro_stripe_updates", array());
			}
			else			
			{				
			?>
			<h3><?php _e("Subscription Updates", "pmpro"); ?></h3>
			<p>
				<?php 
					if(empty($_REQUEST['user_id'])) 
						_e("Subscription updates, allow you to change the member's subscription values at predefined times. Be sure to click Update Profile after making changes.", 'pmpro');
					else
						_e("Subscription updates, allow you to change the member's subscription values at predefined times. Be sure to click Update User after making changes.", 'pmpro');
				?>
			</p>
			<table class="form-table">
				<tr>
					<th><label for="membership_level"><?php _e("Update", "pmpro"); ?></label></th>
					<td id="updates_td">
						<?php
							$old_updates = $user->pmpro_stripe_updates;							
							if(is_array($old_updates))
							{
								$updates = array_merge(
									array(array('template'=>true, 'when'=>'now', 'date_month'=>'', 'date_day'=>'', 'date_year'=>'', 'billing_amount'=>'', 'cycle_number'=>'', 'cycle_period'=>'Month')),
									$old_updates
								);
							}
							else
								$updates = array(array('template'=>true, 'when'=>'now', 'date_month'=>'', 'date_day'=>'', 'date_year'=>'', 'billing_amount'=>'', 'cycle_number'=>'', 'cycle_period'=>'Month'));
														
							foreach($updates as $update)
							{
							?>
							<div class="updates_update" <?php if(!empty($update['template'])) { ?>style="display: none;"<?php } ?>>
								<select class="updates_when" name="updates_when[]">							
									<option value="now" <?php selected($update['when'], "now");?>>Now</option>
									<option value="payment" <?php selected($update['when'], "payment");?>>After Next Payment</option>
									<option value="date" <?php selected($update['when'], "date");?>>On Date</option>
								</select>
								<span class="updates_date" <?php if($uwhen != "date") { ?>style="display: none;"<?php } ?>>
									<select name="updates_date_month[]">
										<?php																
											for($i = 1; $i < 13; $i++)
											{
											?>
											<option value="<?php echo str_pad($i, 2, "0", STR_PAD_LEFT);?>" <?php if(!empty($update['date_month']) && $update['date_month'] == $i) { ?>selected="selected"<?php } ?>>
												<?php echo date("M", strtotime($i . "/1/" . $current_year));?>
											</option>
											<?php
											}
										?>
									</select>
									<input name="updates_date_day[]" type="text" size="2" value="<?php if(!empty($update['date_day'])) echo esc_attr($update['date_day']);?>" />
									<input name="updates_date_year[]" type="text" size="4" value="<?php if(!empty($update['date_year'])) echo esc_attr($update['date_year']);?>" />
								</span>
								<span class="updates_billing" <?php if($uwhen == "no") { ?>style="display: none;"<?php } ?>>
									<?php echo $pmpro_currency_symbol?><input name="updates_billing_amount[]" type="text" size="10" value="<?php echo esc_attr($update['billing_amount']);?>" /> 
									<small><?php _e('per', 'pmpro');?></small>
									<input name="updates_cycle_number[]" type="text" size="5" value="<?php echo esc_attr($update['cycle_number']);?>" />
									<select name="updates_cycle_period[]">
									  <?php							
										foreach ( $cycles as $name => $value ) {
										  echo "<option value='$value'";
										  if(!empty($update['cycle_period']) && $update['cycle_period'] == $value) echo " selected='selected'";
										  echo ">$name</option>";
										}
									  ?>
									</select>
								</span>	
								<span>
									<a class="updates_remove" href="javascript:void(0);">Remove</a>								
								</span>
							</div>
							<?php
							}
							?>						
						<p><a id="updates_new_update" href="javascript:void(0);">+ New Update</a></p>
					</td>
				</tr>				
			</table>						
			<script>
				jQuery(document).ready(function() {
					//function to update dropdowns/etc based on when field
					function updateSubscriptionUpdateFields(when)
					{
						if(jQuery(when).val() == 'date')
							jQuery(when).parent().children('.updates_date').show();
						else
							jQuery(when).parent().children('.updates_date').hide();
							
						if(jQuery(when).val() == 'no')
							jQuery(when).parent().children('.updates_billing').hide();
						else
							jQuery(when).parent().children('.updates_billing').show();
					}										

					//and update on page load
					jQuery('.updates_when').each(function() { if(jQuery(this).parent().css('display') != 'none') updateSubscriptionUpdateFields(this); });
					
					//add a new update when clicking to
					var num_updates_divs = <?php echo count($updates);?>;
					jQuery('#updates_new_update').click(function() {
						//get updates
						updates = jQuery('.updates_update').toArray();
												
						//clone the first one
						new_div = jQuery(updates[0]).clone();													
												
						//append
						new_div.insertBefore('#updates_new_update');
												
						//update events
						addUpdateEvents()
												
						//unhide it
						new_div.show();
						updateSubscriptionUpdateFields(new_div.children('.updates_when'));												
					});
										
					function addUpdateEvents()
					{
						//update when when changes
						jQuery('.updates_when').change(function() {
							updateSubscriptionUpdateFields(this);
						});
						
						//remove updates when clicking
						jQuery('.updates_remove').click(function() {						
							jQuery(this).parent().parent().remove();
						});
					}
					addUpdateEvents();
				});
			</script>
			<?php
			}
		}
		
		/**
		 * Process fields from the edit user page
		 *		 
		 * @since 2.0
		 */
		static function user_profile_fields_save($user_id)
		{
			global $wpdb;
			
			//check capabilities
			$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
			if(!current_user_can($membership_level_capability))
				return false;
			
			//make sure some value was passed
			if(!isset($_POST['updates_when']) || !is_array($_POST['updates_when']))
				return;
			
			//vars
			$updates = array();
			$next_on_date_update = "";
			
			//build array of updates (we skip the first because it's the template field for the JavaScript
			for($i = 1; $i < count($_POST['updates_when']); $i++)
			{
				$update = array();
				
				//all updates have these values
				$update['when'] = $_POST['updates_when'][$i];
				$update['billing_amount'] = $_POST['updates_billing_amount'][$i];
				$update['cycle_number'] = $_POST['updates_cycle_number'][$i];
				$update['cycle_period'] = $_POST['updates_cycle_period'][$i];
								
				//these values only for on date updates
				if($_POST['updates_when'][$i] == "date")
				{
					$update['date_month'] = str_pad($_POST['updates_date_month'][$i], 2, "0", STR_PAD_LEFT);
					$update['date_day'] = str_pad($_POST['updates_date_day'][$i], 2, "0", STR_PAD_LEFT);
					$update['date_year'] = $_POST['updates_date_year'][$i];
				}
				
				//make sure the update is valid
				if(empty($update['cycle_number']))
					continue;
				
				//if when is now, update the subscription
				if($update['when'] == "now")
				{
					//get level for user
					$user_level = pmpro_getMembershipLevelForUser($user_id);
					
					//get current plan at Stripe to get payment date
					$last_order = new MemberOrder();
					$last_order->getLastMemberOrder($user_id);
					$last_order->setGateway('stripe');
					$last_order->Gateway->getCustomer();
															
					if(!empty($last_order->Gateway->customer))
					{
						//find the first subscription
						if(!empty($last_order->Gateway->customer->subscriptions['data'][0]))
						{
							$first_sub = $last_order->Gateway->customer->subscriptions['data'][0]->__toArray();
							$end_timestamp = $first_sub['current_period_end'];
						}
					}
					
					//if we didn't get an end date, let's set one one cycle out
					$end_timestamp = strtotime("+" . $update['cycle_number'] . " " . $update['cycle_period']);
										
					//build order object
					$update_order = new MemberOrder();
					$update_order->setGateway('stripe');
					$update_order->user_id = $user_id;
					$update_order->membership_id = $user_level->id;
					$update_order->membership_name = $user_level->name;
					$update_order->InitialPayment = 0;
					$update_order->PaymentAmount = $update['billing_amount'];
					$update_order->ProfileStartDate = date("Y-m-d", $end_timestamp);
					$update_order->BillingPeriod = $update['cycle_period'];
					$update_order->BillingFrequency = $update['cycle_number'];
					
					//update subscription
					$update_order->Gateway->subscribe($update_order);
					
					//update membership
					$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users 
									SET billing_amount = '" . esc_sql($update['billing_amount']) . "', 
										cycle_number = '" . esc_sql($update['cycle_number']) . "', 
										cycle_period = '" . esc_sql($update['cycle_period']) . "' 
									WHERE user_id = '" . esc_sql($user_id) . "' 
										AND membership_id = '" . esc_sql($last_order->membership_id) . "' 
										AND status = 'active' 
									LIMIT 1";
													
					$wpdb->query($sqlQuery);
										
					continue;
				}
				elseif($update['when'] == 'date')
				{
					if(!empty($next_on_date_update))
						$next_on_date_update = min($next_on_date_update, $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day']);
					else
						$next_on_date_update = $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'];
				}
				
				//add to array				
				$updates[] = $update;				
			}
			
			//save in user meta
			update_user_meta($user_id, "pmpro_stripe_updates", $updates);
			
			//save date of next on-date update to make it easier to query for these in cron job
			update_user_meta($user_id, "pmpro_stripe_next_on_date_update", $next_on_date_update);
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
		function getCustomer(&$order = false, $force = false)
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

                return apply_filters('pmpro_stripe_create_customer', $this->customer);
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
						
			//for free trials, just push the start date of the subscription back
			if(!empty($order->TrialBillingCycles) && $order->TrialAmount == 0)
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
			elseif(!empty($order->TrialBillingCycles))
			{
				/*
					Let's set the subscription to the trial and give the user an "update" to change the sub later to full price (since v2.0)
				*/
				//figure out the user
				if(!empty($order->user_id))
					$user_id = $order->user_id;
				else
				{
					global $current_user;
					$user_id = $current_user->ID;
				}
				
				//add the update first (we're overwriting any other updates already on file)
				$user_updates = array();
				$user_updates[] = array(
					'when' => 'payment',
					'billing_amount' => $order->PaymentAmount,
					'cycle_period' => $order->BillingPeriod,
					'cycle_number' => $order->BillingFrequency
				);
				update_user_meta($user_id, "pmpro_stripe_updates", $user_updates);
				
				//now amount to equal the trial #s				
				$amount = $order->TrialAmount;
				$amount_tax = $order->getTaxForPrice($amount);			
				$amount = round((float)$amount + (float)$amount_tax, 2);
				
				//update order numbers so entry in invoice and pmpro_memberships_users is correct				
				$order->PaymentAmount = $order->TrialAmount;
				$order->TrialAmount = 0;
				$order->TrialBillingCycles = 0;
				
				$order->billing_amount = $order->PaymentAmount;
				$order->trial_amount = 0;
				$order->trial_limit = 0;
				
				global $pmpro_level;
				$pmpro_level->billing_amount = $order->PaymentAmount;
				$pmpro_level->trial_amount = 0;
				$pmpro_level->trial_limit = 0;			
			}			
						
			//create a plan
			try
			{
                $plan = array(
                    "amount" => $amount * 100,
                    "interval_count" => $order->BillingFrequency,
                    "interval" => strtolower($order->BillingPeriod),
                    "trial_period_days" => $trial_period_days,
                    "name" => $order->membership_name . " for order " . $order->code,
                    "currency" => strtolower(pmpro_getOption("currency")),
                    "id" => $order->code
                );

				$plan = Stripe_Plan::create(apply_filters('pmpro_stripe_create_plan_array', $plan));
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
				
				/*
					Clear updates for this user. (But not if checking out, we would have already done that.)
				*/
				if(empty($_REQUEST['submit-checkout']))
					update_user_meta($order->user_id, "pmpro_stripe_updates", array());
				
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