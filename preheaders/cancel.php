<?php
	global $besecure;
	$besecure = false;

	global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_confirm, $pmpro_error;

	//get level information for current user
	if($current_user->ID)
		$current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

	//if no user or membership level, redirect to levels page
	if(!isset($current_user->membership_level->ID)) {
		wp_redirect(pmpro_url("levels"));
		exit;
	}

	//using the old ?level param?
	if(!empty($_REQUEST['level']) && empty($_REQUEST['levelstocancel'])) {
		$_REQUEST['levelstocancel'] = $_REQUEST['level'];
	}

	//check if a level was passed in to cancel specifically
	if(!empty($_REQUEST['levelstocancel']) && $_REQUEST['levelstocancel'] != 'all') {
		//convert spaces back to +
		$_REQUEST['levelstocancel'] = str_replace(array(' ', '%20'), '+', $_REQUEST['levelstocancel']);
		
		//get the ids
		$requested_ids = preg_replace("/[^0-9\+]/", "", $_REQUEST['levelstocancel']);
		$old_level_ids = array_map( 'intval', explode( "+", $requested_ids ) );
		
		//make sure the user has their old level
		if(!pmpro_hasMembershipLevel($old_level_ids)) {
			wp_redirect(pmpro_url("levels"));
			exit;
		}
	} else {
		$old_level_ids = false;	//cancel all levels
	}

	//are we confirming a cancellation?
	if(isset($_REQUEST['confirm']))
		$pmpro_confirm = (bool)$_REQUEST['confirm'];
	else
		$pmpro_confirm = false;

	if($pmpro_confirm) {
        if(!empty($old_level_ids)) {
        	$worked = true;
			foreach($old_level_ids as $old_level_id) {
				$worked = $worked && pmpro_cancelMembershipLevel($old_level_id, $current_user->ID, 'cancelled');
			}
        }
		else {
			$old_level_ids = $wpdb->get_col("SELECT DISTINCT(membership_id) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $current_user->ID . "' AND status = 'active'");
			$worked = pmpro_changeMembershipLevel(0, $current_user->ID, 'cancelled');
		}
        
		if($worked === true && empty($pmpro_error))
		{
			$pmpro_msg = __("Your membership has been cancelled.", 'paid-memberships-pro' );
			$pmpro_msgt = "pmpro_success";

			//send an email to the member
			$myemail = new PMProEmail();
			$myemail->sendCancelEmail($current_user, $old_level_ids);

			//send an email to the admin
			$myemail = new PMProEmail();
			$myemail->sendCancelAdminEmail($current_user, $old_level_ids);
		} else {
			global $pmpro_error;
			$pmpro_msg = $pmpro_error;
			$pmpro_msgt = "pmpro_error";
		}
	}
