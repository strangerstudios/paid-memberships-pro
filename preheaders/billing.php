<?php
	global $besecure;
	$besecure = true;		
	
	global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt;
	global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
	
	//_x stuff in case they clicked on the image button with their mouse
	if(isset($_REQUEST['update-billing']))
		$submit = $_REQUEST['update-billing'];
	else
		$submit = false;		
	
	if(!$submit && isset($_REQUEST['update-billing_x']))
		$submit = $_REQUEST['update-billing_x'];	
	
	if($submit === "0") 
		$submit = true;
		
	//check their fields if they clicked continue
	if($submit)
	{		
		//load em up (other fields)	
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
		if(isset($_REQUEST['AccountNumbe']))
			$AccountNumber = trim($_REQUEST['AccountNumber']);
		if(isset($_REQUEST['ExpirationMonth']))
			$ExpirationMonth = $_REQUEST['ExpirationMonth'];
		if(isset($_REQUEST['ExpirationYear']))
			$ExpirationYear = $_REQUEST['ExpirationYear'];
		if(isset($_REQUEST['CVV']))
			$CVV = trim($_REQUEST['CVV']);	
		
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
