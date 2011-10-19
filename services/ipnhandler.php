<?php	
	global $isapage;
	$isapage = true;
	
	global $logstr;
	$logstr = "";
		
	//wp includes	
	define('WP_USE_THEMES', false);
	require('../../../../wp-load.php');
	
	global $gateway_environment;
	
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
	$item_name = $_POST['item_name'];
	$item_number = $_POST['item_number'];
	$payment_status = $_POST['payment_status'];
	$payment_amount = $_POST['mc_gross'];
	$payment_currency = $_POST['mc_currency'];
	$txn_id = $_POST['txn_id'];
	$receiver_email = $_POST['receiver_email'];
	$payer_email = $_POST['payer_email'];

	if($fp->errors)
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
		if(strcmp($res, "VERIFIED") == 0) 
		{
			ipnlog("VERIFIED");
			//check the receiver_email
			if($_POST['receiver_email'] != pmpro_getOption('gateway_email'))
			{
				//not yours					
				ipnlog("ERROR: receiver_email (" . $_POST['receiver_email'] . ") did not match (" . pmpro_getOption('gateway_email') . ")");
			}													
			elseif($_POST['recurring_payment_id'])
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
					if(!$old_txn)
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
			else
			{
				ipnlog("No recurring payment id.");
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