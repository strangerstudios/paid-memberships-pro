<?php	
	add_action("pmpro_cron_expiration_warnings", "pmpro_cron_expiration_warnings");		
	function pmpro_cron_expiration_warnings()
	{	
		global $wpdb;
		
		//make sure we only run once a day
		$today = date("Y-m-d 00:00:00");
		
		$pmpro_email_days_before_expiration = apply_filters("pmpro_email_days_before_expiration", 7);
		
		//look for memberships that are going to expire within one week (but we haven't emailed them within a week)
		$sqlQuery = "SELECT mu.user_id, mu.membership_id, mu.startdate, mu.enddate FROM $wpdb->pmpro_memberships_users mu LEFT JOIN $wpdb->usermeta um ON um.user_id = mu.user_id AND um.meta_key = 'pmpro_expiration_notice' WHERE mu.status = 'active' AND mu.enddate IS NOT NULL AND mu.enddate <> '' AND mu.enddate <> '0000-00-00 00:00:00' AND DATE_SUB(enddate, INTERVAL " . $pmpro_email_days_before_expiration . " Day) <= '" . $today . "' AND (um.meta_value IS NULL OR DATE_ADD(meta_value, INTERVAL " . $pmpro_email_days_before_expiration . " Day) <= '" . $today . "') ORDER BY mu.enddate";
		
		echo $sqlQuery . "<hr />";
				
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
				
				echo "Membership expiring email sent to " . $euser->user_email . ". ";
			}
				
			//update user meta so we don't email them again
			update_user_meta($euser->ID, "pmpro_expiration_notice", $today);
		}
	}
	
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
			pmpro_changeMembershipLevel(false, $e->user_id);
			
			$send_email = apply_filters("pmpro_send_expiration_email", true, $e->user_id);
			if($send_email)
			{
				//send an email
				$pmproemail = new PMProEmail();
				$euser = get_userdata($e->user_id);		
				$pmproemail->sendMembershipExpiredEmail($euser);
				
				echo "Membership expired email sent to " . $euser->user_email . ". ";
			}
		}
	}
	
	add_action("pmpro_cron_trial_ending_warnings", "pmpro_cron_trial_ending_warnings");	
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
			mu.status = 'active' mu.trial_limit IS NOT NULL AND mu.trial_limit > 0 AND
			(
				(cycle_period = 'Day' AND DATE_ADD(startdate, INTERVAL trial_limit Day) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) OR
				(cycle_period = 'Week' AND DATE_ADD(startdate, INTERVAL trial_limit Week) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) OR
				(cycle_period = 'Month' AND DATE_ADD(startdate, INTERVAL trial_limit Month) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) OR
				(cycle_period = 'Year' AND DATE_ADD(startdate, INTERVAL trial_limit Year) <= DATE_ADD('" . $today . "', INTERVAL " . $pmpro_email_days_before_trial_end . " Day)) 
			)			
						
			AND (um.meta_value IS NULL OR um.meta_value = '' OR DATE_ADD(meta_value, INTERVAL " . $pmpro_email_days_before_trial_end . " Day) <= '" . $today . "') 
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
				
				echo "Trial ending email sent to " . $euser->user_email . ". ";
			}
				
			//update user meta so we don't email them again
			update_user_meta($euser->ID, "pmpro_trial_ending_notice", $today);
		}
	}		
?>