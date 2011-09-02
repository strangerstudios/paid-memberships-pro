<?php
	global $besecure;
	$besecure = true;		
	
	global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt;
	global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
	
	//_x stuff in case they clicked on the image button with their mouse
	$submit = $_REQUEST['update-billing'];
	if(!$submit) $submit = $_REQUEST['update-billing_x'];	
	if($submit === "0") $submit = true;
		
	//check their fields if they clicked continue
	if($submit)
	{		
		//load em up (other fields)	
		$bfirstname = $_REQUEST['bfirstname'];	
		$blastname = $_REQUEST['blastname'];	
		$baddress1 = $_REQUEST['baddress1'];
		$baddress2 = $_REQUEST['baddress2'];
		$bcity = $_REQUEST['bcity'];
		$bstate = $_REQUEST['bstate'];
		$bzipcode = $_REQUEST['bzipcode'];
		$bphone = $_REQUEST['bphone'];
		$bemail = $_REQUEST['bemail'];
		$bconfirmemail = $_REQUEST['bconfirmemail'];
		$CardType = $_REQUEST['CardType'];
		$AccountNumber = $_REQUEST['AccountNumber'];
		$ExpirationMonth = $_REQUEST['ExpirationMonth'];
		$ExpirationYear = $_REQUEST['ExpirationYear'];
		$CVV = $_REQUEST['CVV'];	
		
		if(!$bfirstname || !$blastname || !$baddress1 || !$bcity || !$bstate || !$bzipcode || !$bphone || !$bemail || !$CardType || !$AccountNumber || !$ExpirationMonth || !$ExpirationYear || !$CVV)
		{
			//krumo(array($bname, $baddress1, $bcity, $bstate, $bzipcode, $bemail, $name, $address1, $city, $state, $zipcode));
			$pmpro_msg = "Please complete all required fields.";
			$pmpro_msgt = "pmpro_error";
		}		
		elseif($bemail != $bconfirmemail)
		{
			$pmpro_msg = "Your email addresses do not match. Please try again.";
			$pmpro_msgt = "pmpro_error";
		}		
		elseif(!is_email($bemail))
		{
			$pmpro_msg = "The email address entered is in an invalid format. Please try again.";	
			$pmpro_msgt = "pmpro_error";
		}			
		else
		{					
			//all good. update billing info.
			$pmpro_msg = "All good!";
						
			//change this
			$order_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $current_user->ID . "' AND membership_id = '" . $current_user->membership_level->ID . "' AND status = 'success' LIMIT 1");			
			if($order_id)
			{
				$morder = new MemberOrder($order_id);											
						
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
				$morder->billing->country = "US";
				$morder->billing->zip = $bzipcode;
				$morder->billing->phone = $bphone;							
				
				$worked = $morder->updateBilling();		

				if($worked)
				{
					//send email
					$pmproemail = new PMProEmail();
					$pmproemail->sendBillingEmail($current_user, $morder);				
				}
			}
			else
				$worked = true;
			
			if($worked)
			{
				//update the user meta too
				$meta_keys = array("pmpro_bfirstname", "pmpro_blastname", "pmpro_baddress1", "pmpro_baddress2", "pmpro_bcity", "pmpro_bstate", "pmpro_bzipcode", "pmpro_bphone", "pmpro_bemail", "pmpro_CardType", "pmpro_AccountNumber", "pmpro_ExpirationMonth", "pmpro_ExpirationYear");
				$meta_values = array($bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bphone, $bemail, $CardType, hideCardNumber($AccountNumber), $ExpirationMonth, $ExpirationYear);						
				pmpro_replaceUserMeta($current_user->ID, $meta_keys, $meta_values);
				
				//message
				$pmpro_msg = "Information updated. <a href=\"" . pmpro_url("account") . "\">&laquo; back to my account</a>";			
				$pmpro_msgt = "pmpro_success";								
			}			
			else
			{
				$pmpro_msg = $morder->error;
				if(!$pmpro_msg)
					$pmpro_msg = "Error updating billing information.";
				$pmpro_msgt = "pmpro_error";
			}				
		}
	}
	else
	{
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