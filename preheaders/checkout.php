<?php
	global $gateway, $wpdb, $besecure, $discount_code, $pmpro_level, $pmpro_levels, $pmpro_msg, $pmpro_msgt, $pmpro_review, $skip_account_fields, $pmpro_paypal_token, $pmpro_show_discount_code;
	
	//was a gateway passed?
	if(!empty($_REQUEST['gateway']))
		$gateway = $_REQUEST['gateway'];
	elseif(!empty($_REQUEST['review']))
		$gateway = "paypalexpress";
	else
		$gateway = pmpro_getOption("gateway");		
	
	//what level are they purchasing? (discount code passed)
	if(!empty($_REQUEST['level']) && !empty($_REQUEST['discount_code']))
	{
		$discount_code = preg_replace("/[^A-Za-z0-9]/", "", $_REQUEST['discount_code']);
		//check code
		$code_check = pmpro_checkDiscountCode($discount_code, (int)$_REQUEST['level'], true);		
		if($code_check[0] == false)
		{
			//error
			$pmpro_msg = $code_check[1];
			$pmpro_msgt = "pmpro_error";
			
			//don't use this code
			$use_discount_code = false;
		}
		else
		{			
			$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $discount_code . "' AND cl.level_id = '" . (int)$_REQUEST['level'] . "' LIMIT 1";			
			$pmpro_level = $wpdb->get_row($sqlQuery);
			
			$use_discount_code = true;
		}	
	}
	
	//what level are they purchasing? (no discount code)
	if(!$pmpro_level && $_REQUEST['level'])
	{
		$pmpro_level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . $wpdb->escape($_REQUEST['level']) . "' AND allow_signups = 1 LIMIT 1");	
	}
	
	//filter the level (for upgrades, etc)
	$pmpro_level = apply_filters("pmpro_checkout_level", $pmpro_level);		
		
	if(!$pmpro_level)
	{
		wp_redirect(pmpro_url("levels"));
		exit(0);
	}		
		
	global $wpdb, $current_user, $pmpro_requirebilling;	
	if(!pmpro_isLevelFree($pmpro_level))
	{
		//require billing and ssl
		$pagetitle = "Checkout: Payment Information";
		$pmpro_requirebilling = true;
		if($gateway != "paypalexpress" || !empty($_REQUEST['gateway']))
			$besecure = true;			
		else
			$besecure = false;		
	}		
	else
	{
		//no payment so we don't need ssl
		$pagetitle = "Setup Your Account";
		$pmpro_requirebilling = false;
		$besecure = false;		
	}
		
	//get all levels in case we need them
	global $pmpro_levels;
	$pmpro_levels = $wpdb->get_results( "SELECT * FROM " . $wpdb->pmpro_membership_levels . " WHERE allow_signups = 1", OBJECT );	
	
	//should we show the discount code field?
	if($wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes LIMIT 1"))
		$pmpro_show_discount_code = true;
	else
		$pmpro_show_discount_code = false;
	$pmpro_show_discount_code = apply_filters("pmpro_show_discount_code", $pmpro_show_discount_code);
		
	//by default we show the account fields if the user isn't logged in
	if($current_user->ID)
	{
		$skip_account_fields = true;
	}
	else
	{
		$skip_account_fields = false;
	}	
	//in case people want to have an account created automatically
	$skip_account_fields = apply_filters("pmpro_skip_account_fields", $skip_account_fields, $current_user);
	
	//some options
	global $tospage;
	$tospage = pmpro_getOption("tospage");
	if($tospage)
		$tospage = get_post($tospage);
	
	//load em up (other fields)
	global $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
	
	if(isset($_REQUEST['order_id']))
		$order_id = $_REQUEST['order_id'];
	if(isset($_REQUEST['bfirstname']))
		$bfirstname = trim(stripslashes($_REQUEST['bfirstname']));	
	if(isset($_REQUEST['blastname']))
		$blastname = trim(stripslashes($_REQUEST['blastname']));	
	if(isset($_REQUEST['fullname']))
		$fullname = $_REQUEST['fullname'];		//honeypot for spammers
	if(isset($_REQUEST['baddress1']))
		$baddress1 = trim(stripslashes($_REQUEST['baddress1']));		
	if(isset($_REQUEST['baddress2']))
		$baddress2 = trim(stripslashes($_REQUEST['baddress2']));
	if(isset($_REQUEST['bcity']))
		$bcity = trim(stripslashes($_REQUEST['bcity']));
	if(isset($_REQUEST['bstate']))
		$bstate = trim(stripslashes($_REQUEST['bstate']));
	if(isset($_REQUEST['bzipcode']))
		$bzipcode = trim(stripslashes($_REQUEST['bzipcode']));
	if(isset($_REQUEST['bcountry']))
		$bcountry = trim(stripslashes($_REQUEST['bcountry']));
	if(isset($_REQUEST['bphone']))
		$bphone = trim(stripslashes($_REQUEST['bphone']));
	if(isset($_REQUEST['bemail']))
		$bemail = trim(stripslashes($_REQUEST['bemail']));
	if(isset($_REQUEST['bconfirmemail']))
		$bconfirmemail = trim(stripslashes($_REQUEST['bconfirmemail']));
	if(isset($_REQUEST['CardType']))
		$CardType = $_REQUEST['CardType'];
	if(isset($_REQUEST['AccountNumber']))
		$AccountNumber = trim($_REQUEST['AccountNumber']);
	if(isset($_REQUEST['ExpirationMonth']))
		$ExpirationMonth = $_REQUEST['ExpirationMonth'];
	if(isset($_REQUEST['ExpirationYear']))
		$ExpirationYear = $_REQUEST['ExpirationYear'];
	if(isset($_REQUEST['CVV']))
		$CVV = trim($_REQUEST['CVV']);
	
	if(isset($_REQUEST['discount_code']))
		$discount_code = trim($_REQUEST['discount_code']);
	if(isset($_REQUEST['username']))
		$username = trim($_REQUEST['username']);
	if(isset($_REQUEST['password']))
		$password = $_REQUEST['password'];
	if(isset($_REQUEST['password2']))
		$password2 = $_REQUEST['password2'];
	if(isset($_REQUEST['tos']))
		$tos = $_REQUEST['tos'];		
	
	//_x stuff in case they clicked on the image button with their mouse
	if(isset($_REQUEST['submit-checkout']))
		$submit = $_REQUEST['submit-checkout'];
	if(empty($submit) && isset($_REQUEST['submit-checkout_x']) )
		$submit = $_REQUEST['submit-checkout_x'];	
	if(isset($submit) && $submit === "0") 
		$submit = true;	
	elseif(!isset($submit))
		$submit = false;
	
	//check their fields if they clicked continue
	if($submit && $pmpro_msgt != "pmpro_error")
	{		
		//if we're skipping the account fields and there is no user, we need to create a username and password
		if($skip_account_fields && !$current_user->ID)
		{
			$username = pmpro_generateUsername($bfirstname, $blastname, $bemail);
			$password = pmpro_getDiscountCode() . pmpro_getDiscountCode();	//using two random discount codes
			$password2 = $password;
		}	
		
		if($pmpro_requirebilling && $gateway != "paypalexpress")
		{
			$pmpro_required_billing_fields = array(
				"bfirstname" => $bfirstname,
				"blastname" => $blastname,
				"baddress1" => $baddress1,
				"bcity" => $bcity,
				"bstate" => $bstate,
				"bzipcode" => $bzipcode,
				"bphone" => $bphone,
				"bemail" => $bemail,
				"bcountry" => $bcountry,
				"CardyType" => $CardType,
				"AccountNumber" => $AccountNumber,
				"ExpirationMonth" => $ExpirationMonth,
				"ExpirationYear" => $ExpirationYear,
				"CVV" => $CVV
			);
			
			//filter
			$pmpro_required_billing_fields = apply_filters("pmpro_required_billing_fields", $pmpro_required_billing_fields);			
						
			foreach($pmpro_required_billing_fields as $key => $field)
			{
				if(!$field)
				{										
					$missing_billing_field = true;										
					break;
				}
			}
		}
				
		if(!empty($missing_billing_field))
		{
			$pmpro_msg = "Please complete all required fields.";
			$pmpro_msgt = "pmpro_error";
		}
		elseif(empty($current_user->ID) && (empty($username) || empty($password) || empty($password2)))
		{
			$pmpro_msg = "Please complete all account fields.";
			$pmpro_msgt = "pmpro_error";
		}
		elseif(isset($password) && $password != $password2)
		{
			$pmpro_msg = "Your passwords do not match. Please try again.";
			$pmpro_msgt = "pmpro_error";
		}
		elseif(isset($bemail) && $bemail != $bconfirmemail)
		{
			$pmpro_msg = "Your email addresses do not match. Please try again.";
			$pmpro_msgt = "pmpro_error";
		}		
		elseif(!empty($bemail) && !is_email($bemail))
		{
			$pmpro_msg = "The email address entered is in an invalid format. Please try again.";	
			$pmpro_msgt = "pmpro_error";
		}
		elseif(!empty($tospage) && empty($tos))
		{
			$pmpro_msg = "Please check the box to agree to the " . $tospage->post_title . ".";	
			$pmpro_msgt = "pmpro_error";
		}
		elseif(!empty($fullname))
		{
			$pmpro_msg = "Are you a spammer?";
			$pmpro_msgt = "pmpro_error";
		}
		else
		{
			//user supplied requirements
			$pmpro_continue_registration = apply_filters("pmpro_registration_checks", true);
						
			if($pmpro_continue_registration)
			{											
				//if creating a new user, check that the email and username are available
				if(empty($current_user->ID))
				{
					$oldusername = $wpdb->get_var("SELECT user_login FROM $wpdb->users WHERE user_login = '" . $wpdb->escape($username) . "' LIMIT 1");
					$oldemail = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email = '" . $wpdb->escape($bemail) . "' LIMIT 1");
					
					//this hook can be used to allow multiple accounts with the same email address
					$oldemail = apply_filters("pmpro_checkout_oldemail", $oldemail);
				}
				
				if(!empty($oldusername))
				{
					$pmpro_msg = "That username is already taken. Please try another.";
					$pmpro_msgt = "pmpro_error";
				}
				elseif(!empty($oldemail))
				{
					$pmpro_msg = "That email address is already taken. Please try another.";
					$pmpro_msgt = "pmpro_error";
				}
				else
				{								
					//check recaptch first
					global $recaptcha;
					if(!$skip_account_fields && ($recaptcha == 2 || ($recaptcha == 1 && !(float)$pmpro_level->billing_amount && !(float)$pmpro_level->trial_amount)))
					{
						global $recaptcha_privatekey;					
						$resp = recaptcha_check_answer($recaptcha_privatekey,
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);
							
						if(!$resp->is_valid) 
						{
							$pmpro_msg = "reCAPTCHA failed. (" . $resp->error . ") Please try again.";
							$pmpro_msgt = "pmpro_error";
						} 
						else 
						{
							// Your code here to handle a successful verification
							$pmpro_msg = "All good!";
						}
					}
					else
						$pmpro_msg = "All good!";										
					
					//no errors yet
					if($pmpro_msgt != "pmpro_error")
					{				
						//save user fields for PayPal Express
						if(!$current_user->ID && $gateway == "paypalexpress")
						{
							$_SESSION['pmpro_signup_username'] = $username;
							$_SESSION['pmpro_signup_password'] = $password;
							$_SESSION['pmpro_signup_email'] = $bemail;
							
							//can use this hook to save some other variables to the session
							do_action("pmpro_paypalexpress_session_vars");
						}
						
						if($pmpro_requirebilling)
						{
							$morder = new MemberOrder();			
							$morder->membership_id = $pmpro_level->id;
							$morder->membership_name = $pmpro_level->name;
							$morder->discount_code = $discount_code;
							$morder->InitialPayment = $pmpro_level->initial_payment;
							$morder->PaymentAmount = $pmpro_level->billing_amount;
							$morder->ProfileStartDate = date("Y-m-d") . "T0:0:0";
							$morder->BillingPeriod = $pmpro_level->cycle_period;
							$morder->BillingFrequency = $pmpro_level->cycle_number;
									
							if($pmpro_level->billing_limit)
								$morder->TotalBillingCycles = $pmpro_level->billing_limit;
						
							if(pmpro_isLevelTrial($pmpro_level))
							{
								$morder->TrialBillingPeriod = $pmpro_level->cycle_period;
								$morder->TrialBillingFrequency = $pmpro_level->cycle_number;
								$morder->TrialBillingCycles = $pmpro_level->trial_limit;
								$morder->TrialAmount = $pmpro_level->trial_amount;
							}
							
							//credit card values
							$morder->cardtype = $CardType;
							$morder->accountnumber = $AccountNumber;
							$morder->expirationmonth = $ExpirationMonth;
							$morder->expirationyear = $ExpirationYear;
							$morder->ExpirationDate = $ExpirationMonth . $ExpirationYear;
							$morder->ExpirationDate_YdashM = $ExpirationYear . "-" . $ExpirationMonth;
							$morder->CVV2 = $CVV;												
							
							//not saving email in order table, but the sites need it
							$morder->Email = $bemail;
							
							//sometimes we need these split up
							$morder->FirstName = $bfirstname;
							$morder->LastName = $blastname;						
							$morder->Address1 = $baddress1;
							$morder->Address2 = $baddress2;						
							
							//other values
							$morder->billing->name = $bfirstname . " " . $blastname;
							$morder->billing->street = trim($baddress1 . " " . $baddress2);
							$morder->billing->city = $bcity;
							$morder->billing->state = $bstate;
							$morder->billing->country = $bcountry;
							$morder->billing->zip = $bzipcode;
							$morder->billing->phone = $bphone;
									
							//$gateway = pmpro_getOption("gateway");										
							$morder->gateway = $gateway;
							
							//setup level var
							$morder->getMembershipLevel();
							
							//tax
							$morder->subtotal = $morder->InitialPayment;
							$morder->getTax();						
							
							if($gateway == "paypalexpress")
							{
								$morder->payment_type = "PayPal Express";
								$morder->cardtype = "";
								$morder->ProfileStartDate = date("Y-m-d", strtotime("+ " . $morder->BillingFrequency . " " . $morder->BillingPeriod)) . "T0:0:0";
								$morder->ProfileStartDate = apply_filters("pmpro_profile_start_date", $morder->ProfileStartDate, $morder);
								$pmpro_processed = $morder->setExpressCheckout();
							}
							else
							{
								$pmpro_processed = $morder->process();
							}
							
							if($pmpro_processed)
							{
								$pmpro_msg = "Payment accepted.";
								$pmpro_msgt = "pmpro_success";	
								$pmpro_confirmed = true;
							}			
							else
							{																								
								$pmpro_msg = $morder->error;
								if(!$pmpro_msg)
									$pmpro_msg = "Unknown error generating account. Please contact us to setup your membership.";
								$pmpro_msgt = "pmpro_error";								
							}	
														
						}		
						else // !$pmpro_requirebilling
						{
							//must have been a free membership, continue
							$pmpro_confirmed = true;
						}
					}													
				}
			}	//endif($pmpro_continue_registration)
		}
	}				
	
	//PayPal Express Call Backs
	if(!empty($_REQUEST['review']))
	{
		$_SESSION['payer_id'] = $_REQUEST['PayerID'];
		$_SESSION['paymentAmount']=$_REQUEST['paymentAmount'];
		$_SESSION['currCodeType']=$_REQUEST['currencyCodeType'];
		$_SESSION['paymentType']=$_REQUEST['paymentType'];
		
		$morder = new MemberOrder();
		$morder->getMemberOrderByPayPalToken($_REQUEST['token']);
		$morder->Token = $morder->paypal_token; $pmpro_paypal_token = $morder->paypal_token;				
		if($morder->Token)
		{
			if($morder->getPayPalExpressCheckoutDetails())
			{
				$pmpro_review = true;
			}
			else
			{
				$pmpro_msg = $morder->error;
				$pmpro_msgt = "error";
			}		
		}
		else
		{
			$pmpro_msg = "The PayPal Token was lost.";
			$pmpro_msgt = "error";
		}
	}
	elseif(!empty($_REQUEST['confirm']))
	{		
		$morder = new MemberOrder();
		$morder->getMemberOrderByPayPalToken($_REQUEST['token']);
		$morder->Token = $morder->paypal_token; $pmpro_paypal_token = $morder->paypal_token;	
		if($morder->Token)
		{		
			//setup values
			$morder->membership_id = $pmpro_level->id;
			$morder->membership_name = $pmpro_level->name;
			$morder->discount_code = $discount_code;
			$morder->InitialPayment = $pmpro_level->initial_payment;
			$morder->PaymentAmount = $pmpro_level->billing_amount;
			$morder->ProfileStartDate = date("Y-m-d") . "T0:0:0";
			$morder->BillingPeriod = $pmpro_level->cycle_period;
			$morder->BillingFrequency = $pmpro_level->cycle_number;
			$morder->Email = $bemail;
			
			//$gateway = pmpro_getOption("gateway");																	
			
			//setup level var
			$morder->getMembershipLevel();			
			
			//tax
			$morder->subtotal = $morder->InitialPayment;
			$morder->getTax();				
			if($pmpro_level->billing_limit)
				$morder->TotalBillingCycles = $pmpro_level->billing_limit;
		
			if(pmpro_isLevelTrial($pmpro_level))
			{
				$morder->TrialBillingPeriod = $pmpro_level->cycle_period;
				$morder->TrialBillingFrequency = $pmpro_level->cycle_number;
				$morder->TrialBillingCycles = $pmpro_level->trial_limit;
				$morder->TrialAmount = $pmpro_level->trial_amount;
			}
						
			if($morder->process())
			{						
				$submit = true;
				$pmpro_confirmed = true;
			
				if(!$current_user->ID)
				{
					//reload the user fields			
					$username = $_SESSION['pmpro_signup_username'];
					$password = $_SESSION['pmpro_signup_password'];
					$password2 = $password;
					$bemail = $_SESSION['pmpro_signup_email'];
					
					//unset the user fields in session
					unset($_SESSION['pmpro_signup_username']);
					unset($_SESSION['pmpro_signup_password']);
					unset($_SESSION['pmpro_signup_email']);
				}
			}
			else
			{								
				$pmpro_msg = $morder->error;
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$pmpro_msg = "The PayPal Token was lost.";
			$pmpro_msgt = "error";
		}
	}
	
	//if payment was confirmed create/update the user.
	if(!empty($pmpro_confirmed))
	{
		//do we need to create a user account?
		if(!$current_user->ID)
		{
			// create user
			require_once( ABSPATH . WPINC . '/registration.php');
			$user_id = wp_insert_user(array(
							"user_login" => $username,							
							"user_pass" => $password,
							"user_email" => $bemail)
							);
			if (!$user_id) {
				$pmpro_msg = "Your payment was accepted, but there was an error setting up your account. Please contact us.";
				$pmpro_msgt = "pmpro_error";
			} else {
			
				//check pmpro_wp_new_user_notification filter before sending the default WP email
				if(apply_filters("pmpro_wp_new_user_notification", true, $user_id, $pmpro_level->id))
					wp_new_user_notification($user_id, $password);								
		
				$wpuser = new WP_User(0, $username);
		
				//make the user a subscriber
				$wpuser->set_role("subscriber");
									
				//okay, log them in to WP							
				$creds = array();
				$creds['user_login'] = $username;
				$creds['user_password'] = $password;
				$creds['remember'] = true;
				$user = wp_signon( $creds, false );																	
			}
		}
		else
			$user_id = $current_user->ID;	
		
		if($user_id)
		{				
			//calculate the end date
			if($pmpro_level->expiration_number)
			{
				$enddate = "'" . date("Y-m-d", strtotime("+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period)) . "'";
			}
			else
			{
				$enddate = "NULL";
			}
			
			//update membership_user table.
			if($discount_code && $use_discount_code)
				$discount_code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . $discount_code . "' LIMIT 1");
			
			//set the start date to NOW() but allow filters
			$startdate = apply_filters("pmpro_checkout_start_date", "NOW()", $user_id, $pmpro_level);			
			
			$sqlQuery = "REPLACE INTO $wpdb->pmpro_memberships_users (user_id, membership_id, code_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, startdate, enddate) 
				VALUES('" . $user_id . "',
				'" . $pmpro_level->id . "',
				'" . $discount_code_id . "',
				'" . $pmpro_level->initial_payment . "',
				'" . $pmpro_level->billing_amount . "',
				'" . $pmpro_level->cycle_number . "',
				'" . $pmpro_level->cycle_period . "',
				'" . $pmpro_level->billing_limit . "',
				'" . $pmpro_level->trial_amount . "',
				'" . $pmpro_level->trial_limit . "',
				" . $startdate . ",
				" . $enddate . ")";
					
			if($wpdb->query($sqlQuery) !== false)
			{
				//we're good
				//add an item to the history table, cancel old subscriptions						
				if($morder)
				{
					$morder->user_id = $user_id;
					$morder->membership_id = $pmpro_level->id;
												
					$morder->saveOrder();																
					
					//cancel any other subscriptions they have
					$other_order_ids = $wpdb->get_col("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $current_user->ID . "' AND id <> '" . $morder->id . "' AND status = 'success' ORDER BY id DESC");
					foreach($other_order_ids as $order_id)
					{
						$c_order = new MemberOrder($order_id);
						$c_order->cancel();		
					}						
				}
			
				//update the current user
				global $current_user;
				if(!$current_user->ID && $user->ID)
					$current_user = $user;		//in case the user just signed up
				pmpro_set_current_user();
			
				//add discount code use
				if($discount_code && $use_discount_code)
				{					
					$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $current_user->ID . "', '" . $morder->id . "', now())");					
				}
			
				//save billing info ect, as user meta																		
				$meta_keys = array("pmpro_bfirstname", "pmpro_blastname", "pmpro_baddress1", "pmpro_baddress2", "pmpro_bcity", "pmpro_bstate", "pmpro_bzipcode", "pmpro_bphone", "pmpro_bemail", "pmpro_CardType", "pmpro_AccountNumber", "pmpro_ExpirationMonth", "pmpro_ExpirationYear");
				$meta_values = array($bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bphone, $bemail, $CardType, hideCardNumber($AccountNumber), $ExpirationMonth, $ExpirationYear);						
				pmpro_replaceUserMeta($user_id, $meta_keys, $meta_values);	
									
				//show the confirmation
				$ordersaved = true;
									
				//hook
				do_action("pmpro_after_checkout", $user_id);						
				do_action("pmpro_after_change_membership_level", $pmpro_level->id, $user_id);																									
				//send email
				$pmproemail = new PMProEmail();
				if($morder)
					$invoice = new MemberOrder($morder->id);						
				else
					$invoice = NULL;
				$user->membership_level = $pmpro_level;		//make sure they have the right level info
				$pmproemail->sendCheckoutEmail($current_user, $invoice);
												
				//redirect to confirmation
				wp_redirect(pmpro_url("confirmation"));
				exit;
			}
			else
			{
				//uh oh. we charged them then the membership creation failed
				if($morder->cancel())
				{
					$pmpro_msg = "IMPORTANT: Something went wrong during membership creation. Your credit card authorized, but we cancelled the order immediately. You should not try to submit this form again. Please contact the site owner to fix this issue.";
					$morder = NULL;
				}
				else
					$pmpro_msg = "IMPORTANT: Something went wrong during membership creation. Your credit card was charged, but we couldn't assign your membership. You should not submit this form again. Please contact the site owner to fix this issue.";
				$pmpro_error;
			}												
		}
	}	
	
	//default values
	if(empty($submit))
	{
		//show message if the payment gateway is not setup yet
		if($pmpro_requirebilling && !pmpro_getOption("gateway"))
		{
			if(pmpro_isAdmin())			
				$pmpro_msg = "You must <a href=\"" . home_url('/wp-admin/admin.php?page=pmpro-membershiplevels&view=payment') . "\">setup a Payment Gateway</a> before any payments will be processed.";
			else
				$pmpro_msg = "A Payment Gateway must be setup before any payments will be processed.";
			$pmpro_msgt = "";
		}
		
		//default values from DB
		$bfirstname = get_user_meta($current_user->ID, "pmpro_bfirstname", true);
		$blastname = get_user_meta($current_user->ID, "pmpro_blastname", true);
		$baddress1 = get_user_meta($current_user->ID, "pmpro_baddress1", true);
		$baddress2 = get_user_meta($current_user->ID, "pmpro_baddress2", true);
		$bcity = get_user_meta($current_user->ID, "pmpro_bcity", true);
		$bstate = get_user_meta($current_user->ID, "pmpro_bstate", true);
		$bzipcode = get_user_meta($current_user->ID, "pmpro_bzipcode", true);
		$bphone = get_user_meta($current_user->ID, "pmpro_bphone", true);
		$bemail = get_user_meta($current_user->ID, "pmpro_bemail", true);
		$bconfirmemail = get_user_meta($current_user->ID, "pmpro_bconfirmemail", true);
		$CardType = get_user_meta($current_user->ID, "pmpro_CardType", true);
		//$AccountNumber = hideCardNumber(get_user_meta($current_user->ID, "pmpro_AccountNumber", true), false);
		$ExpirationMonth = get_user_meta($current_user->ID, "pmpro_ExpirationMonth", true);
		$ExpirationYear = get_user_meta($current_user->ID, "pmpro_ExpirationYear", true);	
	}	
?>