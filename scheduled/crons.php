<?php			
/*
	Expiring Memberships		
*/
add_action("pmpro_cron_expire_memberships", "pmpro_cron_expire_memberships");		
function pmpro_cron_expire_memberships()
{
	global $wpdb;
			
	//make sure we only run once a day
	$today = date("Y-m-d");
	
	//look for memberships that expired before today
	$sqlQuery = "SELECT mu.user_id, mu.membership_id, mu.startdate, mu.enddate FROM $wpdb->pmpro_memberships_users mu WHERE mu.status = 'active' AND mu.enddate IS NOT NULL AND mu.enddate <> '' AND mu.enddate <> '0000-00-00 00:00:00' AND DATE(mu.enddate) <= '" . $today . "' ORDER BY mu.enddate";
					
	$expired = $wpdb->get_results($sqlQuery);
			
	foreach($expired as $e)
	{						
		//remove their membership
		pmpro_changeMembershipLevel(false, $e->user_id, 'expired');
		
		$send_email = apply_filters("pmpro_send_expiration_email", true, $e->user_id);
		if($send_email)
		{
			//send an email
			$pmproemail = new PMProEmail();
			$euser = get_userdata($e->user_id);		
			$pmproemail->sendMembershipExpiredEmail($euser);
			
			printf(__("Membership expired email sent to %s. ", "pmpro"), $euser->user_email);				
		}
	}
}

/*
	Expiration Warning Emails
*/
add_action("pmpro_cron_expiration_warnings", "pmpro_cron_expiration_warnings");
function pmpro_cron_expiration_warnings()
{	
	global $wpdb;
	
	//make sure we only run once a day
	$today = date("Y-m-d 00:00:00");
	
	$pmpro_email_days_before_expiration = apply_filters("pmpro_email_days_before_expiration", 7);
			
	//look for memberships that are going to expire within one week (but we haven't emailed them within a week)
	$sqlQuery = "SELECT mu.user_id, mu.membership_id, mu.startdate, mu.enddate 
	FROM $wpdb->pmpro_memberships_users mu 
	LEFT JOIN $wpdb->usermeta um ON um.user_id = mu.user_id 
	AND um.meta_key = 'pmpro_expiration_notice' 
	WHERE mu.status = 'active' 
	AND mu.enddate IS NOT NULL 
	AND mu.enddate <> '' 
	AND mu.enddate <> '0000-00-00 00:00:00' 
	AND DATE_SUB(mu.enddate, INTERVAL " . $pmpro_email_days_before_expiration . " Day) <= '" . $today . "' 
	AND (um.meta_value IS NULL OR DATE_ADD(um.meta_value, INTERVAL " . $pmpro_email_days_before_expiration . " Day) <= '" . $today . "') 
	ORDER BY mu.enddate";

	$expiring_soon = $wpdb->get_results($sqlQuery);
			
	foreach($expiring_soon as $e)
	{				
		$send_email = apply_filters("pmpro_send_expiration_warning_email", true, $e->user_id);
		if($send_email)
		{
			//send an email
			$pmproemail = new PMProEmail();
			$euser = get_userdata($e->user_id);		
			$pmproemail->sendMembershipExpiringEmail($euser);
			
			printf(__("Membership expiring email sent to %s. ", "pmpro"), $euser->user_email);
		}
			
		//update user meta so we don't email them again
		update_user_meta($e->user_id, "pmpro_expiration_notice", $today);
	}
}

/*
	Credit Card Expiring Warnings
*/
add_action("pmpro_cron_credit_card_expiring_warnings", "pmpro_cron_credit_card_expiring_warnings");	
function pmpro_cron_credit_card_expiring_warnings()
{
	global $wpdb;
	
	$next_month_date = date("Y-m-01", strtotime("+2 months"));
	
	$sqlQuery = "SELECT mu.user_id
					FROM  $wpdb->pmpro_memberships_users mu
						LEFT JOIN $wpdb->usermeta um1 ON mu.user_id = um1.user_id
							AND meta_key =  'pmpro_ExpirationMonth'
						LEFT JOIN $wpdb->usermeta um2 ON mu.user_id = um2.user_id
							AND um2.meta_key =  'pmpro_ExpirationYear'
						LEFT JOIN $wpdb->usermeta um3 ON mu.user_id = um3.user_id
							AND um3.meta_key = 'pmpro_credit_card_expiring_warning'
					WHERE mu.status =  'active'
						AND mu.cycle_number >0
						AND CONCAT(um2.meta_value, '-', um1.meta_value, '-01') < '" . $next_month_date . "'
						AND (um3.meta_value IS NULL OR CONCAT(um2.meta_value, '-', um1.meta_value, '-01') <> um3.meta_value)
				";
		
	$cc_expiring_user_ids = $wpdb->get_col($sqlQuery);
			
	if(!empty($cc_expiring_user_ids))
	{
		require_once(ABSPATH . 'wp-includes/pluggable.php');
	
		foreach($cc_expiring_user_ids as $user_id)
		{
			//get user				
			$euser = get_userdata($user_id);		
							
			//make sure their level doesn't have a billing limit that's been reached
			$euser->membership_level = pmpro_getMembershipLevelForUser($euser->ID);
			if(!empty($euser->membership_level->billing_limit))
			{
				/*
					There is a billing limit on this level, skip for now. 
					We should figure out how to tell if the limit has been reached
					and if not, email the user about the expiring credit card.
				*/
				continue;
			}
			
			//make sure they are using a credit card type billing method for their current membership level (check the last order)
			$last_order = new MemberOrder();
			$last_order->getLastMemberOrder($euser->ID);				
			if(empty($last_order->accountnumber))
				continue;
			
			//okay send them an email				
			$send_email = apply_filters("pmpro_send_credit_card_expiring_email", true, $e->user_id);
			if($send_email)
			{
				//send an email
				$pmproemail = new PMProEmail();					
				$pmproemail->sendCreditCardExpiringEmail($euser);
				
				printf(__("Credit card expiring email sent to %s. ", "pmpro"), $euser->user_email);				
			}
				
			//update user meta so we don't email them again
			update_user_meta($euser->ID, "pmpro_credit_card_expiring_warning", $euser->pmpro_ExpirationYear . "-" . $euser->pmpro_ExpirationMonth . "-01");				
		}
	}
}

/*
	Trial Ending Emails
	Commented out as of version 1.7.2 since this caused issues on some sites
	and doesn't take into account the many "custom trial" solutions that are
	in the wild (e.g. some trials are actually a delay of the subscription start date)
*/	
//add_action("pmpro_cron_trial_ending_warnings", "pmpro_cron_trial_ending_warnings");	
function pmpro_cron_trial_ending_warnings()
{
	global $wpdb;
			
	//make sure we only run once a day
	$today = date("Y-m-d 00:00:00");
	
	$pmpro_email_days_before_trial_end = apply_filters("pmpro_email_days_before_trial_end", 7);
	
	//look for memberships with trials ending soon (but we haven't emailed them within a week)
	$sqlQuery = "
	SELECT 
		mu.user_id, mu.membership_id, mu.startdate, mu.cycle_period, mu.trial_limit FROM $wpdb->pmpro_memberships_users mu LEFT JOIN $wpdb->usermeta um ON um.user_id = mu.user_id AND um.meta_key = 'pmpro_trial_ending_notice' 
	WHERE 
		mu.status = 'active' AND mu.trial_limit IS NOT NULL AND mu.trial_limit > 0 AND
		(
			(cycle_period = 'Day' AND DATE_ADD(mu.startdate, INTERVAL mu.trial_limit Day) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) OR
			(cycle_period = 'Week' AND DATE_ADD(mu.startdate, INTERVAL mu.trial_limit Week) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) OR
			(cycle_period = 'Month' AND DATE_ADD(mu.startdate, INTERVAL mu.trial_limit Month) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) OR
			(cycle_period = 'Year' AND DATE_ADD(mu.startdate, INTERVAL mu.trial_limit Year) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) 
		)		
					
		AND (um.meta_value IS NULL OR um.meta_value = '' OR DATE_ADD(um.meta_value, INTERVAL " . $pmpro_email_days_before_trial_end . " Day) <= '" . $today . "') 
	ORDER BY mu.startdate";
			
	$trial_ending_soon = $wpdb->get_results($sqlQuery);
	
	foreach($trial_ending_soon as $e)
	{							
		$send_email = apply_filters("pmpro_send_trial_ending_email", true, $e->user_id);
		if($send_email)
		{
			//send an email
			$pmproemail = new PMProEmail();
			$euser = get_userdata($e->user_id);		
			$pmproemail->sendTrialEndingEmail($euser);
			
			printf(__("Trial ending email sent to %s. ", "pmpro"), $euser->user_email);				
		}
			
		//update user meta so we don't email them again
		update_user_meta($e->user_id, "pmpro_trial_ending_notice", $today);
	}
}

/*
	On-date subscription updates.
	
	As of v2.0, these can be set for members with Stripe subscriptions.
*/
function pmpro_cron_subscription_updates()
{
	global $wpdb;

	//get all updates for today (or before today)
	$sqlQuery = "SELECT * 
				 FROM $wpdb->usermeta 
				 WHERE meta_key = 'pmpro_stripe_next_on_date_update' 
					AND meta_value IS NOT NULL 
					AND meta_value < '" . date("Y-m-d", strtotime("+1 day")) . "'";		
	$updates = $wpdb->get_results($sqlQuery);
			
	if(!empty($updates))
	{								
		//loop through
		foreach($updates as $update)
		{						
			//pull values from update
			$user_id = $update->user_id;
							
			$user = get_userdata($user_id);
			$user_updates = $user->pmpro_stripe_updates;
			$next_on_date_update = "";		
							
			//loop through updates looking for updates happening today or earlier
			foreach($user_updates as $key => $update)
			{				
				if($update['when'] == 'date' &&
					$update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'] <= date("Y-m-d")
				)
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
											
					//remove update from list
					unset($user_updates[$key]);									
				}
				elseif($update['when'] == 'date')
				{
					//this is an on date update for the future, update the next on date update
					if(!empty($next_on_date_update))
						$next_on_date_update = min($next_on_date_update, $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day']);
					else
						$next_on_date_update = $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'];
				}
			}
			
			//save updates in case we removed some
			update_user_meta($user_id, "pmpro_stripe_updates", $user_updates);
			
			//save date of next on-date update to make it easier to query for these in cron job
			update_user_meta($user_id, "pmpro_stripe_next_on_date_update", $next_on_date_update);
		}
	}
}