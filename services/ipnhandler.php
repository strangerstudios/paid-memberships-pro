<?php
	//in case the file is loaded directly
	if(!defined("WP_USE_THEMES"))
	{
		global $isapage;
		$isapage = true;

		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}

	//uncomment to log requests in logs/ipn.txt
	//define('PMPRO_IPN_DEBUG', true);

	//some globals
	global $wpdb, $gateway_environment, $logstr;
	$logstr = "";	//will put debug info here and write to ipnlog.txt

	//validate?
	if(!pmpro_ipnValidate())
	{
		//validation failed
		pmpro_ipnExit();
	}

	//assign posted variables to local variables
	$txn_type = pmpro_getParam("txn_type", "POST");
	$subscr_id = pmpro_getParam("subscr_id", "POST");
	$txn_id = pmpro_getParam("txn_id", "POST");
	$item_name = pmpro_getParam("item_name", "POST");
	$item_number = pmpro_getParam("item_number", "POST");
	$payment_status = pmpro_getParam("payment_status", "POST");
	$payment_amount = pmpro_getParam("payment_amount", "POST");
	$payment_currency = pmpro_getParam("payment_currency", "POST");
	$receiver_email = pmpro_getParam("receiver_email", "POST");
	$business_email = pmpro_getParam("business", "POST");
	$payer_email = pmpro_getParam("payer_email", "POST");
	$recurring_payment_id = pmpro_getParam("recurring_payment_id", "POST");

    if(empty($subscr_id))
        $subscr_id = $recurring_payment_id;

	//check the receiver_email
	if(!pmpro_ipnCheckReceiverEmail(array(strtolower($receiver_email), strtolower($business_email))))
	{
		//not our request
		pmpro_ipnExit();
	}

	/*
		PayPal Standard
		- we will get txn_type subscr_signup and subscr_payment (or subscr_eot or subscr_failed)
		- subscr_signup (if amount1 = 0, then we need to update membership, else ignore and wait for payment. create invoice for $0 with just subscr_id)
		- subscr_payment (check if we should update membership, add invoice for amount with subscr_id and payment_id)
		- web_accept for 1-time payment only

		PayPal Express
		- we will get txn_type express_checkout, or recurring_payment_profile_created, or recurring_payment (or recurring_payment_expired, or recurring_payment_skipped)

	*/

	//PayPal Standard Sign Up
	if($txn_type == "subscr_signup")
	{
		//if there is no amount1, this membership has a trial, and we need to update membership/etc
		$amount = pmpro_getParam("amount1", "POST");

		if((float)$amount <= 0)
		{
			//trial, get the order
			$morder = new MemberOrder($item_number);
			$morder->getMembershipLevel();
			$morder->getUser();

			//no txn_id on these, so let's use the subscr_id
			if(empty($txn_id))
				$txn_id = $subscr_id;

			//update membership
			if(pmpro_ipnChangeMembershipLevel($txn_id, $morder))
			{
				ipnlog("Checkout processed (" . $morder->code . ") success!");
			}
			else
			{
				ipnlog("ERROR: Couldn't change level for order (" . $morder->code . ").");
			}
		}
		else
		{
			//we're ignoring this. we will get a payment notice from IPN and process that
			ipnlog("Going to wait for the first payment to go through.");
		}

		pmpro_ipnExit();
	}

	//PayPal Standard Subscription Payment
	if($txn_type == "subscr_payment")
	{
		//is this a first payment?
		$last_subscr_order = new MemberOrder();
		if($last_subscr_order->getLastMemberOrderBySubscriptionTransactionID($subscr_id) == false)
		{
			//first payment, get order
			$morder = new MemberOrder($_POST['item_number']);
			$morder->getMembershipLevel();
			$morder->getUser();

			//update membership
			if(pmpro_ipnChangeMembershipLevel($txn_id, $morder))
			{
				ipnlog("Checkout processed (" . $morder->code . ") success!");
			}
			else
			{
				ipnlog("ERROR: Couldn't change level for order (" . $morder->code . ").");
			}

			pmpro_ipnExit();
		}
		else
		{
			//subscription payment, completed or failure?
			if($_POST['payment_status'] == "Completed")
				pmpro_ipnSaveOrder($txn_id, $last_subscr_order);
			elseif($_POST['payment_status'] == "Failed")
				pmpro_ipnFailedPayment($last_subscr_order);
			else
				ipnlog('Payment status is ' . $_POST['payment_status'] . '.');

			pmpro_ipnExit();
		}
	}

	//PayPal Standard Single Payment
	if($txn_type == "web_accept" && !empty($item_number))
	{
		//initial payment, get the order
		$morder = new MemberOrder($item_number);
		$morder->getMembershipLevel();
		$morder->getUser();

		//update membership
		if(pmpro_ipnChangeMembershipLevel($txn_id, $morder))
		{
			ipnlog("Checkout processed (" . $morder->code . ") success!");
		}
		else
		{
			ipnlog("ERROR: Couldn't change level for order (" . $morder->code . ").");
		}

		pmpro_ipnExit();
	}

	//PayPal Express Recurring Payments
	if($txn_type == "recurring_payment")
	{
		$last_subscr_order = new MemberOrder();
		if($last_subscr_order->getLastMemberOrderBySubscriptionTransactionID($subscr_id))
		{
			//subscription payment, completed or failure?
			if($_POST['payment_status'] == "Completed")
				pmpro_ipnSaveOrder($txn_id, $last_subscr_order);
			else
				pmpro_ipnFailedPayment($last_subscr_order);
		}
		else
		{
			ipnlog("ERROR: Couldn't find last order for this recurring payment (" . $subscr_id . ").");
		}

		pmpro_ipnExit();
	}

	//Recurring Payment Profile Cancelled (PayPal Express)
	if($txn_type == "recurring_payment_profile_cancel")
	{
		//find last order
		$last_subscr_order = new MemberOrder();
		if($last_subscr_order->getLastMemberOrderBySubscriptionTransactionID($recurring_payment_id) == false)
		{
			ipnlog("ERROR: Couldn't find this order to cancel (subscription_transaction_id=" . $recurring_payment_id . ").");

			pmpro_ipnExit();
		}
		else
		{
			//found order, let's cancel the membership
			$user = get_userdata($last_subscr_order->user_id);

			if(empty($user) || empty($user->ID))
			{
				ipnlog("ERROR: Could not cancel membership. No user attached to order #" . $last_subscr_order->id . " with subscription transaction id = " . $recurring_payment_id . ".");
			}
			else
			{
				/*
					We want to make sure this is a cancel originating from PayPal and not one already handled by PMPro.
					For example, if a user cancels on WP/PMPro side, we've already cancelled the membership.
					Also, if a user is changing levels, we don't want to cancel their new membership, just the old subscription at PayPal.

					So we check 2 things and don't cancel if:
					(1) This order already has "cancelled" status.
					(2) The user doesn't currently have the level attached to this order.
				*/

				if($last_subscr_order->status == "cancelled")
				{
					ipnlog("We've already processed this cancellation. Probably originated from WP/PMPro. (Order #" . $last_subscr_order->id . ", Subscription Transaction ID #" . $recurring_payment_id . ")");
				}
				elseif(!pmpro_hasMembershipLevel($last_subsc_order->membership_id, $user->ID))
				{
					ipnlog("This user has a different level than the one associated with this order. Their membership was probably changed by an admin or through an upgrade/downgrade. (Order #" . $last_subscr_order->id . ", Subscription Transaction ID #" . $recurring_payment_id . ")");
				}
				else
				{
					pmpro_changeMembershipLevel(0, $last_subscr_order->user_id, 'cancelled');

					ipnlog("Cancelled membership for user with id = " . $last_subscr_order->user_id . ". Subscription transaction id = " . $recurring_payment_id . ".");

					//send an email to the member
					$myemail = new PMProEmail();
					$myemail->sendCancelEmail($user);

					//send an email to the admin
					$myemail = new PMProEmail();
					$myemail->sendCancelAdminEmail($user, $last_subscr_order->membership_id);
				}
			}

			pmpro_ipnExit();
		}
	}

	//Subscription Cancelled (PayPal Standard)
	if($txn_type == "subscr_cancel")
	{
		//find last order
		$last_subscr_order = new MemberOrder();
		if($last_subscr_order->getLastMemberOrderBySubscriptionTransactionID($subscr_id) == false)
		{
			ipnlog("ERROR: Couldn't find this order to cancel (subscription_transaction_id=" . $subscr_id . ").");

			pmpro_ipnExit();
		}
		else
		{
			//found order, let's cancel the membership
			$user = get_userdata($last_subscr_order->user_id);

			if(empty($user) || empty($user->ID))
			{
				ipnlog("ERROR: Could not cancel membership. No user attached to order #" . $last_subscr_order->id . " with subscription transaction id = " . $subscr_id . ".");
			}
			else
			{
				/*
					We want to make sure this is a cancel originating from PayPal and not one already handled by PMPro.
					For example, if a user cancels on WP/PMPro side, we've already cancelled the membership.
					Also, if a user is changing levels, we don't want to cancel their new membership, just the old subscription at PayPal.

					So we check 2 things and don't cancel if:
					(1) This order already has "cancelled" status.
					(2) The user doesn't currently have the level attached to this order.
				*/

				if($last_subscr_order->status == "cancelled")
				{
					ipnlog("We've already processed this cancellation. Probably originated from WP/PMPro. (Order #" . $last_subscr_order->id . ", Subscription Transaction ID #" . $subscr_id . ")");
				}
				elseif(!pmpro_hasMembershipLevel($last_subsc_order->membership_id, $user->ID))
				{
					ipnlog("This user has a different level than the one associated with this order. Their membership was probably changed by an admin or through an upgrade/downgrade. (Order #" . $last_subscr_order->id . ", Subscription Transaction ID #" . $subscr_id . ")");
				}
				else
				{
					pmpro_changeMembershipLevel(0, $last_subscr_order->user_id, 'cancelled');

					ipnlog("Canceled membership for user with id = " . $last_subscr_order->user_id . ". Subscription transaction id = " . $subscr_id . ".");

					//send an email to the member
					$myemail = new PMProEmail();
					$myemail->sendCancelEmail($user);

					//send an email to the admin
					$myemail = new PMProEmail();
					$myemail->sendCancelAdminEmail($user, $last_subscr_order->membership_id);
				}
			}

			pmpro_ipnExit();
		}
	}

	//Other
	//if we got here, this is a different kind of txn
	ipnlog("No recurring payment id or item number. txn_type = " . $txn_type);
	pmpro_ipnExit();

	/*
		Add message to ipnlog string
	*/
	function ipnlog($s)
	{
		global $logstr;
		$logstr .= "\t" . $s . "\n";
	}

	/*
		Output ipnlog and exit;
	*/
	function pmpro_ipnExit()
	{
		global $logstr;

		//for log
		if($logstr)
		{
			$logstr = "Logged On: " . date("m/d/Y H:i:s") . "\n" . $logstr . "\n-------------\n";

			echo $logstr;

			//log in file or email?
			if(defined('PMPRO_IPN_DEBUG') && PMPRO_IPN_DEBUG === "log")
			{
				//file
				$loghandle = fopen(dirname(__FILE__) . "/../logs/ipn.txt", "a+");
				fwrite($loghandle, $logstr);
				fclose($loghandle);
			}
			elseif(defined('PMPRO_IPN_DEBUG'))
			{
				//email
				if(strpos(PMPRO_IPN_DEBUG, "@"))
					$log_email = PMPRO_IPN_DEBUG;	//constant defines a specific email address
				else
					$log_email = get_option("admin_email");

				wp_mail($log_email, get_option("blogname") . " IPN Log", nl2br($logstr));
			}
		}

		exit;
	}

	/*
		Validate the $_POST with PayPal
	*/
	function pmpro_ipnValidate()
	{
		//read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';

		//generate string to check with PayPal
		foreach($_POST as $key => $value)
		{
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
		}

		//post back to PayPal system to validate
		$gateway_environment = pmpro_getOption("gateway_environment");
		if($gateway_environment == "sandbox")
			$fp = wp_remote_post('https://www.' . $gateway_environment . '.paypal.com?' . $req, array("httpversion"=>"1.1", "Host"=>"www.paypal.com", "Connection"=>"Close"));
		else
			$fp = wp_remote_post('https://www.paypal.com?' . $req, array("httpversion"=>"1.1", "Host"=>"www.paypal.com", "Connection"=>"Close"));

		//error from PayPal
		if(!empty($fp->errors))
		{
			ipnlog("ERROR");
			ipnlog("Error Info: " . print_r($fp->errors, true) . "\n");
		}

		//log post vars and PayPal object
		ipnlog(print_r($_POST, true));
		//ipnlog(print_r($fp, true));

		if(empty($fp))
		{
			//HTTP ERROR
			ipnlog("HTTP ERROR");
		}
		else
		{
			ipnlog("FP!");

			$res = wp_remote_retrieve_body($fp);
			if(strcmp($res, "VERIFIED") == 0 || true)
			{
				//all good so far
				ipnlog("VERIFIED");
				return true;
			}
			else
			{
				//log for manual investigation
				ipnlog("INAVLID");
				return false;
			}
		}
	}

	/*
		Check that the email sent by PayPal matches our settings.
	*/
	function pmpro_ipnCheckReceiverEmail($email)
	{
		if(!is_array($email))
			$email = array($email);

		if(!in_array(strtolower(pmpro_getOption('gateway_email')), $email))
		{
			$r = false;
		}
		else
			$r = true;

		$r = apply_filters('pmpro_ipn_check_receiver_email', $r, $email);

		if($r)
			return true;
		else
		{
			if(!empty($_POST['receiver_email']))
				$receiver_email = $_POST['receiver_email'];
			else
				$receiver_email = "N/A";

			if(!empty($_POST['business']))
				$business = $_POST['business'];
			else
				$business = "N/A";

			//not yours
			ipnlog("ERROR: receiver_email (" . $receiver_email . ") and business email (" . $business . ") did not match (" . pmpro_getOption('gateway_email') . ")");
			return false;
		}

	}

	/*
		Change the membership level. We also update the membership order to include filtered valus.
	*/
	function pmpro_ipnChangeMembershipLevel($txn_id, &$morder)
	{
		//filter for level
		$morder->membership_level = apply_filters("pmpro_ipnhandler_level", $morder->membership_level, $morder->user_id);

		//fix expiration date
		if(!empty($morder->membership_level->expiration_number))
		{
			$enddate = "'" . date("Y-m-d", strtotime("+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period, current_time("timestamp"))) . "'";
		}
		else
		{
			$enddate = "NULL";
		}

		//get discount code
		$morder->getDiscountCode();
		if(!empty($morder->discount_code))
		{
			//update membership level
			$morder->getMembershipLevel(true);
			$discount_code_id = $morder->discount_code->id;
		}
		else
			$discount_code_id = "";

		//set the start date to current_time('timestamp') but allow filters
		$startdate = apply_filters("pmpro_checkout_start_date", "'" . current_time('mysql') . "'", $morder->user_id, $morder->membership_level);

		//custom level to change user to
		$custom_level = array(
			'user_id' => $morder->user_id,
			'membership_id' => $morder->membership_level->id,
			'code_id' => $discount_code_id,
			'initial_payment' => $morder->membership_level->initial_payment,
			'billing_amount' => $morder->membership_level->billing_amount,
			'cycle_number' => $morder->membership_level->cycle_number,
			'cycle_period' => $morder->membership_level->cycle_period,
			'billing_limit' => $morder->membership_level->billing_limit,
			'trial_amount' => $morder->membership_level->trial_amount,
			'trial_limit' => $morder->membership_level->trial_limit,
			'startdate' => $startdate,
			'enddate' => $enddate);

		global $pmpro_error;
		if(!empty($pmpro_error))
		{
			echo $pmpro_error;
			ipnlog($pmpro_error);
		}

		//change level and continue "checkout"
		if(pmpro_changeMembershipLevel($custom_level, $morder->user_id) !== false)
		{
			//update order status and transaction ids
			$morder->status = "success";
			$morder->payment_transaction_id = $txn_id;
			if(!empty($_POST['subscr_id']))
				$morder->subscription_transaction_id = $_POST['subscr_id'];
			else
				$morder->subscription_transaction_id = "";
			$morder->saveOrder();

			//add discount code use
			if(!empty($discount_code) && !empty($use_discount_code))
			{
				$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $morder->user_id . "', '" . $morder->id . "', '" . current_time('mysql') . "");
			}

			//save first and last name fields
			if(!empty($_POST['first_name']))
			{
				$old_firstname = get_user_meta($morder->user_id, "first_name", true);
				if(empty($old_firstname))
					update_user_meta($morder->user_id, "first_name", $_POST['first_name']);
			}
			if(!empty($_POST['last_name']))
			{
				$old_lastname = get_user_meta($morder->user_id, "last_name", true);
				if(empty($old_lastname))
					update_user_meta($morder->user_id, "last_name", $_POST['last_name']);
			}

			//hook
			do_action("pmpro_after_checkout", $morder->user_id);

			//setup some values for the emails
			if(!empty($morder))
				$invoice = new MemberOrder($morder->id);
			else
				$invoice = NULL;

			$user = get_userdata($morder->user_id);
			$user->membership_level = $morder->membership_level;		//make sure they have the right level info

			//send email to member
			$pmproemail = new PMProEmail();
			$pmproemail->sendCheckoutEmail($user, $invoice);

			//send email to admin
			$pmproemail = new PMProEmail();
			$pmproemail->sendCheckoutAdminEmail($user, $invoice);

			return true;
		}
		else
			return false;
	}

	/*
		Send an email RE a failed payment.
		$last_order passed in is the previous order for this subscription.
	*/
	function pmpro_ipnFailedPayment($last_order)
	{
		//hook to do other stuff when payments fail
		do_action("pmpro_subscription_payment_failed", $last_order);

		//create a blank order for the email
		$morder = new MemberOrder();
		$morder->user_id = $last_order->user_id;

		//add billing information if appropriate
		if($last_order->gateway == "paypal")		//website payments pro
		{
			$morder->billing->name = $_POST['address_name'];
			$morder->billing->street = $_POST['address_street'];
			$morder->billing->city = $_POST['address_city '];
			$morder->billing->state = $_POST['address_state'];
			$morder->billing->zip = $_POST['address_zip'];
			$morder->billing->country = $_POST['address_country_code'];
			$morder->billing->phone = get_user_meta($morder->user_id, "pmpro_bphone", true);

			//get CC info that is on file
			$morder->cardtype = get_user_meta($morder->user_id, "pmpro_CardType", true);
			$morder->accountnumber = hideCardNumber(get_user_meta($morder->user_id, "pmpro_AccountNumber", true), false);
			$morder->expirationmonth = get_user_meta($morder->user_id, "pmpro_ExpirationMonth", true);
			$morder->expirationyear = get_user_meta($morder->user_id, "pmpro_ExpirationYear", true);
		}

		// Email the user and ask them to update their credit card information
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureEmail($user, $morder);

		// Email admin so they are aware of the failure
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);

		ipnlog("Payment failed. Emails sent to " . $user->user_email . " and " . get_bloginfo("admin_email") . ".");

		return true;
	}

	/*
		Save a new order from IPN info.
		$last_order passed in is the previous order for this subscription.
	*/
	function pmpro_ipnSaveOrder($txn_id, $last_order)
	{
		global $wpdb;

		//check that txn_id has not been previously processed
		$old_txn = $wpdb->get_var("SELECT payment_transaction_id FROM $wpdb->pmpro_membership_orders WHERE payment_transaction_id = '" . $txn_id . "' LIMIT 1");

		if(empty($old_txn))
		{
			//hook for successful subscription payments
			do_action("pmpro_subscription_payment_completed");

			//save order
			$morder = new MemberOrder();
			$morder->user_id = $last_order->user_id;
			$morder->membership_id = $last_order->membership_id;
			$morder->payment_transaction_id = $txn_id;
			$morder->subscription_transaction_id = $last_order->subscription_transaction_id;
			$morder->gateway = $last_order->gateway;
			$morder->gateway_environment = $last_order->gateway_environment;

			// Payment Status
			$morder->status = 'success'; // We have confirmed that and thats the reason we are here.
			// Payment Type.
			$morder->payment_type = $last_order->payment_type;

			//set amount based on which PayPal type
			if($last_order->gateway == "paypal")
			{
				$morder->InitialPayment = $_POST['amount'];	//not the initial payment, but the class is expecting that
				$morder->PaymentAmount = $_POST['amount'];
			}
			elseif($last_order->gateway == "paypalexpress")
			{
				$morder->InitialPayment = $_POST['amount'];	//not the initial payment, but the class is expecting that
				$morder->PaymentAmount = $_POST['amount'];
			}
			elseif($last_order->gateway == "paypalstandard")
			{
				$morder->InitialPayment = $_POST['mc_gross'];	//not the initial payment, but the class is expecting that
				$morder->PaymentAmount = $_POST['mc_gross'];
			}

			$morder->FirstName = $_POST['first_name'];
			$morder->LastName = $_POST['last_name'];
			$morder->Email = $_POST['payer_email'];

			//get address info if appropriate
			if($last_order->gateway == "paypal")	//website payments pro
			{
				$morder->Address1 = get_user_meta($last_order->user_id, "pmpro_baddress1", true);
				$morder->City = get_user_meta($last_order->user_id, "pmpro_bcity", true);
				$morder->State = get_user_meta($last_order->user_id, "pmpro_bstate", true);
				$morder->CountryCode = "US";
				$morder->Zip = get_user_meta($last_order->user_id, "pmpro_bzip", true);
				$morder->PhoneNumber = get_user_meta($last_order->user_id, "pmpro_bphone", true);

				$morder->billing->name = $_POST['first_name'] . " " . $_POST['last_name'];
				$morder->billing->street = get_user_meta($last_order->user_id, "pmpro_baddress1", true);
				$morder->billing->city = get_user_meta($last_order->user_id, "pmpro_bcity", true);
				$morder->billing->state = get_user_meta($last_order->user_id, "pmpro_bstate", true);
				$morder->billing->zip = get_user_meta($last_order->user_id, "pmpro_bzip", true);
				$morder->billing->country = get_user_meta($last_order->user_id, "pmpro_bcountry", true);
				$morder->billing->phone = get_user_meta($last_order->user_id, "pmpro_bphone", true);

				//get CC info that is on file
				$morder->cardtype = get_user_meta($last_order->user_id, "pmpro_CardType", true);
				$morder->accountnumber = hideCardNumber(get_user_meta($last_order->user_id, "pmpro_AccountNumber", true), false);
				$morder->expirationmonth = get_user_meta($last_order->user_id, "pmpro_ExpirationMonth", true);
				$morder->expirationyear = get_user_meta($last_order->user_id, "pmpro_ExpirationYear", true);
				$morder->ExpirationDate = $morder->expirationmonth . $morder->expirationyear;
				$morder->ExpirationDate_YdashM = $morder->expirationyear . "-" . $morder->expirationmonth;
			}

			//save
			$morder->saveOrder();
			$morder->getMemberOrderByID($morder->id);

			//email the user their invoice
			$pmproemail = new PMProEmail();
			$pmproemail->sendInvoiceEmail(get_userdata($last_order->user_id), $morder);

			ipnlog("New order (" . $morder->code . ") created.");

			return true;
		}
		else
		{
			ipnlog("Duplicate Transaction ID: " . $txn_id);
			return false;
		}
	}

