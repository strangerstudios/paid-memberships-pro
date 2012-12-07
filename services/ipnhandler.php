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
	
	global $wpdb, $gateway_environment;
	
	if(!empty($_REQUEST['test']))
	{
		$array = array("user_id"=>1, "membership_id"=>154, "code_id"=>"", "initial_payment"=>"0.00", "billing_amount"=>"0.00", "cycle_number"=>"0", "cycle_period"=>"Month", "billing_limit"=>"0", "trial_amount"=>"0.00", "trial_limit"=>"0", "startdate"=>"2012-12-07 T00:00:00", "enddate"=>NULL);
		pmpro_changeMembershipLevel($array, 1);
		global $pmpro_error;
		die($pmpro_error);
		
		exit;
	}
	
	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';
	
	foreach($_POST as $key => $value) 
	{
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
	}
	
	// post back to PayPal system to validate
	if($gateway_environment == "sandbox")
		$fp = wp_remote_post('https://www.' . $gateway_environment . '.paypal.com?' . $req);
	else
		$fp = wp_remote_post('https://www.paypal.com?' . $req);
			
	// assign posted variables to local variables
	if(!empty($_POST['item_name']))
		$item_name = $_POST['item_name'];
	else
		$item_name = "";
	
	if(!empty($_POST['item_number']))
		$item_number = $_POST['item_number'];
	else
		$item_number = "";
	
	if(!empty($_POST['payment_status']))
		$payment_status = $_POST['payment_status'];
	else
		$payment_status = "";
	
	if(!empty($_POST['mc_gross']))
		$payment_amount = $_POST['mc_gross'];
	else
		$payment_amount = "";
	
	if(!empty($_POST['mc_currency']))
		$payment_currency = $_POST['mc_currency'];
	else
		$payment_currency = "";
	
	if(!empty($_POST['txn_id']))
		$txn_id = $_POST['txn_id'];
	else
		$txn_id = "";
		
	if(!empty($_POST['receiver_email']))
		$receiver_email = $_POST['receiver_email'];
	else
		$receiver_email = "";
	
	if(!empty($_POST['payer_email']))
		$payer_email = $_POST['payer_email'];
	else
		$payer_email = "";

	if(!empty($fp->errors))
	{
		ipnlog("ERROR");		
		ipnlog("Error Info: " . print_r($fp->errors, true) . "\n");		
	}
	
	ipnlog(print_r($_POST, true));
	ipnlog(print_r($fp, true));
	
	if(!$fp) 
	{
		// HTTP ERROR
		ipnlog("HTTP ERROR");
	} 
	else
	{
		ipnlog("FP!");
		
		$res = wp_remote_retrieve_body($fp);
		if(strcmp($res, "VERIFIED") == 0 || true) 
		{
			ipnlog("VERIFIED");
			//check the receiver_email
			if($_POST['receiver_email'] != pmpro_getOption('gateway_email'))
			{
				//not yours					
				ipnlog("ERROR: receiver_email (" . $_POST['receiver_email'] . ") did not match (" . pmpro_getOption('gateway_email') . ")");
			}													
			elseif(!empty($_POST['recurring_payment_id']))
			{								
				// okay, add an invoice (maybe). first lookup the user_id from the subscription id passed
				$old_order_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = '" . $wpdb->escape($_POST['recurring_payment_id']) . "' AND gateway = 'paypal' ORDER BY timestamp DESC LIMIT 1");
				$old_order = new MemberOrder($old_order_id);
				$user_id = $old_order->user_id;	
				$user = get_userdata($user_id);		
				
				if($_POST['payment_status'] != "Completed")
				{
					//handle it						
					$morder = new MemberOrder();
					$morder->user_id = $user_id;
					$morder->billing->name = $_POST['address_name'];
					$morder->billing->street = $_POST['address_street'];
					$morder->billing->city = $_POST['address_city '];
					$morder->billing->state = $_POST['address_state'];
					$morder->billing->zip = $_POST['address_zip'];
					$morder->billing->country = $_POST['address_country_code'];
					$morder->billing->phone = get_user_meta($user_id, "pmpro_bphone", true);
					
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
					
					ipnlog("Payment failed. Emails sent to " . $user->user_email . " and " . get_bloginfo("admin_email") . ".");
				}
				else
				{				
					// check that txn_id has not been previously processed
					$old_txn = $wpdb->get_var("SELECT payment_transaction_id FROM $wpdb->pmpro_memberships_orders WHERE payment_transaction_id = '" . $_POST['txn_id'] . "' LIMIT 1");
					if(empty($old_txn))
					{												
						//save order
						$morder = new MemberOrder();
						$morder->user_id = $old_order->user_id;
						$morder->membership_id = $old_order->membership_id;
						$morder->InitialPayment = $_POST['amount'];	//not the initial payment, but the class is expecting that
						$morder->PaymentAmount = $_POST['amount'];
						$morder->payment_transaction_id = $_POST['txn_id'];
						$morder->subscription_transaction_id = $_POST['recurring_payment_id'];
						
						$morder->FirstName = $_POST['first_name'];
						$morder->LastName = $_POST['last_name'];
						$morder->Email = $_POST['payer_email'];			
						
						$morder->Address1 = get_user_meta($user_id, "pmpro_baddress1", true);
						$morder->City = get_user_meta($user_id, "pmpro_bcity", true);
						$morder->State = get_user_meta($user_id, "pmpro_bstate", true);
						$morder->CountryCode = "US";
						$morder->Zip = get_user_meta($user_id, "pmpro_bzip", true);
						$morder->PhoneNumber = get_user_meta($user_id, "pmpro_bphone", true);
						
						$morder->billing->name = $_POST['first_name'] . " " . $_POST['last_name'];
						$morder->billing->street = get_user_meta($user_id, "pmpro_baddress1", true);
						$morder->billing->city = get_user_meta($user_id, "pmpro_bcity", true);
						$morder->billing->state = get_user_meta($user_id, "pmpro_bstate", true);
						$morder->billing->zip = get_user_meta($user_id, "pmpro_bzip", true);
						$morder->billing->country = get_user_meta($user_id, "pmpro_bcountry", true);
						$morder->billing->phone = get_user_meta($user_id, "pmpro_bphone", true);
						
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
						
						ipnlog("New order (" . $morder->code . ") created.");
					}
					else
					{
						//dupe notice						
						ipnlog("Dupe. txn_id = " . $_POST['txn_id']);
					}
				}
			}
			elseif(!empty($_POST['item_number']))
			{				
				//get order by item number
				$morder = new MemberOrder($_POST['item_number']);												
				$morder->getMembershipLevel();
				$morder->getUser();		
							
				//fix expiration date		
				if(!empty($morder->membership_level->expiration_number))
				{
					$enddate = "'" . date("Y-m-d", strtotime("+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period)) . "'";
				}
				else
				{
					$enddate = "NULL";
				}
				
				//get discount code
				$use_discount_code = true;		//assume yes
				if(!empty($discount_code) && !empty($use_discount_code))
					$discount_code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . $discount_code . "' LIMIT 1");
				else
					$discount_code_id = "";
				
				//set the start date to NOW() but allow filters
				$startdate = apply_filters("pmpro_checkout_start_date", "NOW()", $morder->user_id, $morder->membership_level);
				
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
				
				if(pmpro_changeMembershipLevel($custom_level, $morder->user_id))
				{
					//we're good
					
					//update order status and transaction ids					
					$morder->status = "success";
					$morder->payment_transaction_id = $_POST['txn_id'];
					if(!empty($_POST['subscr_id']))
						$morder->subscription_transaction_id = $_POST['subscr_id'];
					else
						$morder->subscription_transaction_id = "";
					$morder->saveOrder();
										
					//add discount code use
					if(!empty($discount_code) && !empty($use_discount_code))
					{
						$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $current_user->ID . "', '" . $morder->id . "', now())");					
					}									
					
					//save first and last name fields
					if(!empty($_POST['first_name']))
					{
						$old_firstname = get_user_meta($morder->user_id, "first_name", true);
						if(!empty($old_firstname))
							update_user_meta($morder->user_id, "first_name", $_POST['first_name']);
					}
					if(!empty($_POST['last_name']))
					{
						$old_lastname = get_user_meta($morder->user_id, "last_name", true);
						if(!empty($old_lastname))
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

					ipnlog("Checkout processed (" . $morder->code . ") success!");		
				}
				else
				{
					ipnlog("ERROR: Couldn't change level for order (" . $morder->code . ").");		
				}				
			}
			else
			{
				ipnlog("No recurring payment id or item number.");
			}
		}
		elseif(strcmp($res, "INVALID") == 0) 
		{
			// log for manual investigation
			ipnlog("INAVLID");
		}		
	}
		
	function ipnlog($s)
	{		
		global $logstr;		
		$logstr .= "\t" . $s . "\n";
	}
		
	echo $logstr;
	
	//for log
	if($logstr)
	{
		$logstr = "Logged On: " . date("m/d/Y H:i:s") . "\n" . $logstr . "\n-------------\n";		
		$loghandle = fopen(dirname(__FILE__) . "/../logs/ipn.txt", "a+");	
		fwrite($loghandle, $logstr);
		fclose($loghandle);
	}
?>