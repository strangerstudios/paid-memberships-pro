<?php	
	global $isapage;
	$isapage = true;
	
	global $logstr;
	$logstr = "";		
	
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
		$event_id = $post_event->id;
	}
	else
	{
		$event_id = $_REQUEST['event_id'];
	}
		
	//get the event through the API now
	try
	{
		$event = Stripe_Event::retrieve($event_id);		
	}
	catch(Exception $e)
	{
		die("Could not find an event with ID #" . $event_id . ". " . $e->getMessage());
	}
	
	global $wpdb;
	
	//real event?
	if(!empty($event->id))
	{	
		//check what kind of event it is
		if($event->type == "invoice.payment_succeeded")
		{	
			//do we have this order yet? (check status too)
			$order = getOrderFromInvoiceEvent($event);
			
			//no? create it
			if(empty($order->id))
			{
				//last order for this subscription
				$old_order = getOldOrderFromInvoiceEvent($event);
				$user_id = $old_order->user_id;	
				$user = get_userdata($user_id);
				
				if(empty($old_order->id))
					die("Couldn't find the original subscription.");
				
				$invoice = $event->data->object;
				
				//alright. create a new order/invoice
				$morder = new MemberOrder();
				$morder->user_id = $old_order->user_id;
				$morder->membership_id = $old_order->membership_id;
				$morder->InitialPayment = $invoice->total / 100;	//not the initial payment, but the class is expecting that
				$morder->PaymentAmount = $invoice->total / 100;
				$morder->payment_transaction_id = $invoice->id;
				$morder->subscription_transaction_id = $invoice->customer;
				
				$morder->FirstName = $old_order->FirstName;
				$morder->LastName = $old_order->LastName;
				$morder->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . $this->user_id . "' LIMIT 1");		
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
				$morder->saveOrder();
				$morder->getMemberOrderByID($morder->id);
								
				//email the user their invoice				
				$pmproemail = new PMProEmail();				
				$pmproemail->sendInvoiceEmail($user, $morder);	
			}
			else
			{
				die("We've already processed this order with ID #" . $event->id);
			}
		}
		elseif($event->type == "invoice.payment_failed")
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

				echo "Sent email to the member and site admin. Thanks.";
				exit;
			}
			else
			{
				die("Could not find the related subscription for order with ID #" . $event->id);
			}
		}
		elseif($event->type == "customer.subscription.deleted")
		{						
			//for one of our users? if they still have a membership, notify the admin			
			$user = getUserFromCustomerEvent($event);
			if(!empty($user->ID))
			{			
				$pmproemail = new PMProEmail();	
				$pmproemail->data = array("body"=>"<p>" . $user->display_name . " (" . $user->user_login . ", " . $user->user_email . ") has had their payment subscription cancelled by Stripe. Please check that this user's membership is cancelled on your site if it should be.</p>");
				$pmproemail->sendEmail(get_bloginfo("admin_email"));	
			}
			else
			{
				die("Not a user here.");
			}
		}
	}
	else
	{
		die("Could not find an event with ID #" . $event_id);
	}

	function getUserFromInvoiceEvent($event)
	{
		global $wpdb;
		
		$customer_id = $event->data->object->customer;
		
		//look up the order
		$user_id = $wpdb->get_var("SELECT user_id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = '" . $customer_id . "' LIMIT 1");
		
		if(!empty($user_id))
			return get_userdata($user_id);
		else
			return false;
	}
	
	function getUserFromCustomerEvent($event)
	{
		global $wpdb;
		
		$customer_id = $event->data->object->customer;
		
		//look up the order
		$user_id = $wpdb->get_var("SELECT user_id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = '" . $customer_id . "' LIMIT 1");
		
		if(!empty($user_id))
			return get_userdata($user_id);
		else
			return false;
	}
	
	function getOldOrderFromInvoiceEvent($event)
	{
		global $wpdb;
		
		$customer_id = $event->data->object->customer;
						
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
		$invoice_id = $event->data->object->id;
		
		$order = new MemberOrder();
		$order->getMemberOrderByPaymentTransactionID($invoice_id);
		
		if(!empty($order->id))
			return $order;
		else
			return false;		
	}
?>
