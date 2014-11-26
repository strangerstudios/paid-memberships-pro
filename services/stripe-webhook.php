<?php	
	global $isapage;
	$isapage = true;
	
	global $logstr;
	$logstr = "";		

	//you can define a different # of seconds (define PMPRO_STRIPE_WEBHOOK_DELAY in your wp-config.php) if you need this webhook to delay more or less
	if(!defined('PMPRO_STRIPE_WEBHOOK_DELAY'))
		define('PMPRO_STRIPE_WEBHOOK_DELAY', 2);
	
	//in case the file is loaded directly
	if(!defined("WP_USE_THEMES"))
	{
		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}
	
	if(!class_exists("Stripe"))
		require_once(dirname(__FILE__) . "/../includes/lib/Stripe/Stripe.php");
			
	Stripe::setApiKey(pmpro_getOption("stripe_secretkey"));
		
	// retrieve the request's body and parse it as JSON
	if(empty($_REQUEST['event_id']))
	{
		$body = @file_get_contents('php://input');
		$post_event = json_decode($body);
			
		//get the id
		if(!empty($post_event))
			$event_id = $post_event->id;
	}
	else
	{
		$event_id = $_REQUEST['event_id'];
	}
		
	//get the event through the API now
	if(!empty($event_id))
	{
		try
		{
			$event = Stripe_Event::retrieve($event_id);		
		}
		catch(Exception $e)
		{
			$logstr .= "Could not find an event with ID #" . $event_id . ". " . $e->getMessage();
			pmpro_stripeWebhookExit();
			//$event = $post_event;			//for testing you may want to assume that the passed in event is legit
		}
	}	
	
	global $wpdb;
		
	//real event?
	if(!empty($event->id))
	{	
		//check what kind of event it is
		if($event->type == "charge.succeeded")
		{	
			//do we have this order yet? (check status too)
			$order = getOrderFromInvoiceEvent($event);
			
			//no? create it
			if(empty($order->id))
			{
				//last order for this subscription
				$old_order = getOldOrderFromInvoiceEvent($event);				
				
				if(empty($old_order))
				{
					$logstr .= "Couldn't find the original subscription.";
					pmpro_stripeWebhookExit();
				}
					
				$user_id = $old_order->user_id;	
				$user = get_userdata($user_id);
				$user->membership_level = pmpro_getMembershipLevelForUser($user_id);
				
				if(empty($user))
				{
					$logstr .= "Couldn't find the old order's user. Order ID = " . $old_order->id . ".";
					pmpro_stripeWebhookExit();
				}
				
				$invoice = $event->data->object;
								
				//alright. create a new order/invoice
				$morder = new MemberOrder();
				$morder->user_id = $old_order->user_id;
				$morder->membership_id = $old_order->membership_id;
				$morder->InitialPayment = $invoice->amount / 100;	//not the initial payment, but the class is expecting that
				$morder->PaymentAmount = $invoice->amount / 100;
				$morder->payment_transaction_id = $invoice->id;
				$morder->subscription_transaction_id = $invoice->customer;					
				
				$morder->gateway = $old_order->gateway;
				$morder->gateway_environment = $old_order->gateway_environment;
				
				$morder->FirstName = $old_order->FirstName;
				$morder->LastName = $old_order->LastName;
				$morder->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . $old_order->user_id . "' LIMIT 1");		
				$morder->Address1 = $old_order->Address1;
				$morder->City = $old_order->billing->city;
				$morder->State = $old_order->billing->state;
				//$morder->CountryCode = $old_order->billing->city;
				$morder->Zip = $old_order->billing->zip;
				$morder->PhoneNumber = $old_order->billing->phone;	
								
				$morder->billing->name = $morder->FirstName . " " . $morder->LastName;
				$morder->billing->street = $old_order->billing->street;
				$morder->billing->city = $old_order->billing->city;
				$morder->billing->state = $old_order->billing->state;
				$morder->billing->zip = $old_order->billing->zip;
				$morder->billing->country = $old_order->billing->country;
				$morder->billing->phone = $old_order->billing->phone;
				
				//get CC info that is on file
				$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
				$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
				$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
				$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);	
				$morder->ExpirationDate = $morder->expirationmonth . $morder->expirationyear;
				$morder->ExpirationDate_YdashM = $morder->expirationyear . "-" . $morder->expirationmonth;
				
				//save
				$morder->status = "success";
				$morder->saveOrder();
				$morder->getMemberOrderByID($morder->id);
								
				//email the user their invoice				
				$pmproemail = new PMProEmail();				
				$pmproemail->sendInvoiceEmail($user, $morder);	
				
				$logstr .= "Created new order with ID #" . $morder->id . ". Event ID #" . $event->id . ".";
				
				/*
					Checking if there is an update "after next payment" for this user.
				*/
				$user_updates = $user->pmpro_stripe_updates;
				if(!empty($user_updates))
				{
					foreach($user_updates as $key => $update)
					{
						if($update['when'] == 'payment')
						{							
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
							$update_order->user_id = $user->ID;
							$update_order->membership_id = $user->membership_level->id;
							$update_order->membership_name = $user->membership_level->name;
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
							
							//remove this update
							unset($user_updates[$key]);
						
							//only process the first next payment update
							break;
						}
					}
					
					//save updates in case we removed some
					update_user_meta($user_id, "pmpro_stripe_updates", $user_updates);
				}
				
				pmpro_stripeWebhookExit();
			}
			else
			{
				$logstr .= "We've already processed this order with ID #" . $order->id . ". Event ID #" . $event->id . ".";
				pmpro_stripeWebhookExit();
			}
		}
		elseif($event->type == "charge.failed")
		{
			//last order for this subscription
			$old_order = getOldOrderFromInvoiceEvent($event);
			$user_id = $old_order->user_id;	
			$user = get_userdata($user_id);
			
			if(!empty($old_order->id))
			{			
				do_action("pmpro_subscription_payment_failed", $old_order);	
				
				//prep this order for the failure emails
				$morder = new MemberOrder();
				$morder->user_id = $user_id;
				$morder->billing->name = $old_order->billing->name;
				$morder->billing->street = $old_order->billing->street;
				$morder->billing->city = $old_order->billing->city;
				$morder->billing->state = $old_order->billing->state;
				$morder->billing->zip = $old_order->billing->zip;
				$morder->billing->country = $old_order->billing->country;
				$morder->billing->phone = $old_order->billing->phone;
				
				//get CC info that is on file
				$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
				$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
				$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
				$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);										
							
				// Email the user and ask them to update their credit card information			
				$pmproemail = new PMProEmail();				
				$pmproemail->sendBillingFailureEmail($user, $morder);
				
				// Email admin so they are aware of the failure
				$pmproemail = new PMProEmail();				
				$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);		

				$logstr .= "Subscription payment failed on order ID #" . $old_order->id . ". Sent email to the member and site admin.";
				pmpro_stripeWebhookExit();
			}
			else
			{
				$logstr .= "Could not find the related subscription for event with ID #" . $event->id . ".";
				if(!empty($event->data->object->customer))
					$logstr .= " Customer ID #" . $event->data->object->customer . ".";
				pmpro_stripeWebhookExit();
			}
		}
		elseif($event->type == "customer.subscription.deleted")
		{						
			//for one of our users? if they still have a membership, notify the admin			
			$user = getUserFromCustomerEvent($event, "success", true);
			if(!empty($user->ID))
			{				
				do_action("pmpro_stripe_subscription_deleted", $user->ID);	
				
				$pmproemail = new PMProEmail();	
				$pmproemail->data = array("body"=>"<p>" . sprintf(__("%s has had their payment subscription cancelled by Stripe. Please check that this user's membership is cancelled on your site if it should be.", "pmpro"), $user->display_name . " (" . $user->user_login . ", " . $user->user_email . ")") . "</p>");
				$pmproemail->sendEmail(get_bloginfo("admin_email"));
				
				$logstr .= "Subscription deleted for user ID #" . $user->ID . ". Event ID #" . $event->id . ".";
				pmpro_stripeWebhookExit();
			}
			else
			{
				//check for any user at all
				$user = getUserFromCustomerEvent($event);
				if(!empty($user->ID))
					$logstr .= "Stripe tells us a subscription is deleted. This was probably initiated from PMPro and the membership/order is already cancelled. Event ID #" . $event->id . ".";
				else
					$logstr .= "Stripe tells us a subscription is deleted, but we could not find a user here for that subscription. Could be a subscription managed by a different app or plugin. Event ID #" . $event->id . ".";
				pmpro_stripeWebhookExit();
			}
		}
	}
	else
	{
		if(!empty($event_id))
			$logstr .= "Could not find an event with ID #" . $event_id;
		else
			$logstr .= "No event ID given.";
		pmpro_stripeWebhookExit();
	}

	function getUserFromInvoiceEvent($event)
	{
		//pause here to give PMPro a chance to finish checkout
		sleep(PMPRO_STRIPE_WEBHOOK_DELAY);
		
		global $wpdb;
		
		$customer_id = $event->data->object->customer;
		
		//look up the order
		$user_id = $wpdb->get_var("SELECT user_id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = '" . esc_sql($customer_id) . "' LIMIT 1");
		
		if(!empty($user_id))
			return get_userdata($user_id);
		else
			return false;
	}
	
	function getUserFromCustomerEvent($event, $status = false, $checkplan = true)
	{
		//pause here to give PMPro a chance to finish checkout
		sleep(PMPRO_STRIPE_WEBHOOK_DELAY);
		
		global $wpdb;
		
		$customer_id = $event->data->object->customer;
		$plan_id = $event->data->object->plan->id;
				
		//look up the order
		$sqlQuery = "SELECT user_id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = '" . esc_sql($customer_id) . "' ";		
		if($status)
			$sqlQuery .= " AND status='" . esc_sql($status) . "' ";
		if($checkplan)
			$sqlQuery .= " AND code='" . esc_sql($plan_id) . "' ";
		$sqlQuery .= " LIMIT 1";
						
		$user_id = $wpdb->get_var($sqlQuery);
				
		if(!empty($user_id))
			return get_userdata($user_id);
		else
			return false;
	}
	
	function getOldOrderFromInvoiceEvent($event)
	{
		//pause here to give PMPro a chance to finish checkout
		sleep(PMPRO_STRIPE_WEBHOOK_DELAY);
		
		global $wpdb;
		
		$customer_id = $event->data->object->customer;
		
		// no customer passed? we can't cross reference
		if(empty($customer_id))
			return false;
		
		// okay, add an invoice. first lookup the user_id from the subscription id passed
		$old_order_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = '" . $customer_id . "' AND gateway = 'stripe' ORDER BY timestamp DESC LIMIT 1");
		$old_order = new MemberOrder($old_order_id);
		
		if(!empty($old_order->id))
			return $old_order;
		else
			return false;			
	}
	
	function getOrderFromInvoiceEvent($event)
	{
		//pause here to give PMPro a chance to finish checkout
		sleep(PMPRO_STRIPE_WEBHOOK_DELAY);
		
		$invoice_id = $event->data->object->id;
		
		$order = new MemberOrder();
		$order->getMemberOrderByPaymentTransactionID($invoice_id);
		
		if(!empty($order->id))
			return $order;
		else
			return false;		
	}

	function pmpro_stripeWebhookExit()
	{
		global $logstr;
		
		//for log
		if($logstr)
		{
			$logstr = "Logged On: " . date("m/d/Y H:i:s") . "\n" . $logstr . "\n-------------\n";		
			
			echo $logstr;
			
			//log in file or email?
			if(defined('PMPRO_STRIPE_WEBHOOK_DEBUG') && PMPRO_STRIPE_WEBHOOK_DEBUG === "log")
			{			
				//file
				$loghandle = fopen(dirname(__FILE__) . "/../logs/stripe-webhook.txt", "a+");	
				fwrite($loghandle, $logstr);
				fclose($loghandle);
			}
			elseif(defined('PMPRO_STRIPE_WEBHOOK_DEBUG'))
			{			
				//email
				if(strpos(PMPRO_STRIPE_WEBHOOK_DEBUG, "@"))
					$log_email = PMPRO_STRIPE_WEBHOOK_DEBUG;	//constant defines a specific email address
				else
					$log_email = get_option("admin_email");
				
				wp_mail($log_email, get_option("blogname") . " Stripe Webhook Log", nl2br($logstr));
			}
		}
		
		exit;
	}