<?php
/*
	This file was added in version 1.5.5 of the plugin. This file is meant to store various hacks, filters, and actions that were originally developed outside of the PMPro core and brought in later... or just things that are cleaner/easier to impement via hooks and filters.
*/

/*
	If checking out for the same level, add remaining days to the enddate.
	Pulled in from: https://gist.github.com/3678054
*/
function pmpro_checkout_level_extend_memberships($level)
{		
	global $pmpro_msg, $pmpro_msgt;

	//does this level expire? are they an existing user of this level?
	if(!empty($level) && !empty($level->expiration_number) && pmpro_hasMembershipLevel($level->id))
	{
		//get the current enddate of their membership
		global $current_user;
		$expiration_date = $current_user->membership_level->enddate;

		//calculate days left
		$todays_date = current_time('timestamp');
		$time_left = $expiration_date - $todays_date;

		//time left?
		if($time_left > 0)
		{
			//convert to days and add to the expiration date (assumes expiration was 1 year)
			$days_left = floor($time_left/(60*60*24));

			//figure out days based on period
			if($level->expiration_period == "Day")
				$total_days = $days_left + $level->expiration_number;
			elseif($level->expiration_period == "Week")
				$total_days = $days_left + $level->expiration_number * 7;
			elseif($level->expiration_period == "Month")
				$total_days = $days_left + $level->expiration_number * 30;
			elseif($level->expiration_period == "Year")
				$total_days = $days_left + $level->expiration_number * 365;

			//update number and period
			$level->expiration_number = $total_days;
			$level->expiration_period = "Day";
		}
	}

	return $level;
}
add_filter("pmpro_checkout_level", "pmpro_checkout_level_extend_memberships");
/*
	Same thing as above but when processed by the ipnhandler for PayPal standard.
*/
function pmpro_ipnhandler_level_extend_memberships($level, $user_id)
{		
	global $pmpro_msg, $pmpro_msgt;

	//does this level expire? are they an existing user of this level?
	if(!empty($level) && !empty($level->expiration_number) && pmpro_hasMembershipLevel($level->id, $user_id))
	{
		//get the current enddate of their membership
		$user_level = pmpro_getMembershipLevelForUser($user_id);		
		$expiration_date = $user_level->enddate;

		//calculate days left
		$todays_date = current_time('timestamp');
		$time_left = $expiration_date - $todays_date;

		//time left?
		if($time_left > 0)
		{
			//convert to days and add to the expiration date (assumes expiration was 1 year)
			$days_left = floor($time_left/(60*60*24));

			//figure out days based on period
			if($level->expiration_period == "Day")
				$total_days = $days_left + $level->expiration_number;
			elseif($level->expiration_period == "Week")
				$total_days = $days_left + $level->expiration_number * 7;
			elseif($level->expiration_period == "Month")
				$total_days = $days_left + $level->expiration_number * 30;
			elseif($level->expiration_period == "Year")
				$total_days = $days_left + $level->expiration_number * 365;

			//update number and period
			$level->expiration_number = $total_days;
			$level->expiration_period = "Day";
		}
	}

	return $level;
}
add_filter("pmpro_ipnhandler_level", "pmpro_ipnhandler_level_extend_memberships", 10, 2);

/*
	If checking out for the same level, keep your old startdate.
	Added with 1.5.5
*/
function pmpro_checkout_start_date_keep_startdate($startdate, $user_id, $level)
{			
	if(pmpro_hasMembershipLevel($level->id, $user_id))
	{
		global $wpdb;
		$sqlQuery = "SELECT startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . esc_sql($user_id) . "' AND membership_id = '" . esc_sql($level->id) . "' AND status = 'active' ORDER BY id DESC LIMIT 1";		
		$old_startdate = $wpdb->get_var($sqlQuery);
		
		if(!empty($old_startdate))
			$startdate = "'" . $old_startdate . "'";
	}
	
	return $startdate;
}
add_filter("pmpro_checkout_start_date", "pmpro_checkout_start_date_keep_startdate", 10, 3);

/*
	Stripe Lite Pulled into Core Plugin
*/
//Stripe Lite, Set the Globals/etc
$stripe_billingaddress = pmpro_getOption("stripe_billingaddress");
if(empty($stripe_billingaddress))
{
	global $pmpro_stripe_lite;
	$pmpro_stripe_lite = true;
	add_filter("pmpro_stripe_lite", "__return_true");
	add_filter("pmpro_required_billing_fields", "pmpro_required_billing_fields_stripe_lite");
}

//Stripe Lite, Don't Require Billing Fields
function pmpro_required_billing_fields_stripe_lite($fields)
{
	global $gateway;
	
	//ignore if not using stripe
	if($gateway != "stripe")
		return $fields;
	
	//some fields to remove
	$remove = array('bfirstname', 'blastname', 'baddress1', 'bcity', 'bstate', 'bzipcode', 'bphone', 'bcountry', 'CardType');
	
	//if a user is logged in, don't require bemail either
	global $current_user;
	if(!empty($current_user->user_email))
		$remove[] = 'bemail';
	
	//remove the fields
	foreach($remove as $field)
		unset($fields[$field]);
			
	//ship it!
	return $fields;
}

//copy other discount code to discount code if latter is not set
if(empty($_REQUEST['discount_code']) && !empty($_REQUEST['other_discount_code']))
{
	$_REQUEST['discount_code'] = $_REQUEST['other_discount_code'];
	$_POST['discount_code'] = $_POST['other_discount_code'];
	$_GET['discount_code'] = $_GET['other_discount_code'];
}