<?php
	global $besecure;
	$besecure = false;

	global $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_confirm, $pmpro_error;

	//get level information for current user
	if($current_user->ID)
		$current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

	//if no user or membership level, redirect to levels page
	if(!isset($current_user->membership_level->ID)) {
		wp_redirect(pmpro_url("levels"));
		exit;
	}
	
	//check if a level was passed in to cancel specifically
	if(!empty($_REQUEST['level']))
		$old_level_id = intval($_REQUEST['level']);
	else
		$old_level_id = $current_user->membership_level->id;

	//make sure the user has their old level
	if(!pmpro_hasMembershipLevel($old_level_id)) {
		wp_redirect(pmpro_url("levels"));
		exit;
	}
		
	//are we confirming a cancellation?
	if(isset($_REQUEST['confirm']))
		$pmpro_confirm = $_REQUEST['confirm'];
	else
		$pmpro_confirm = false;

	if($pmpro_confirm) {		
        $worked = pmpro_cancelMembershipLevel($old_level_id, $current_user->ID, 'cancelled');
        if($worked === true && empty($pmpro_error))
		{
			$pmpro_msg = __("Your membership has been cancelled.", 'pmpro');
			$pmpro_msgt = "pmpro_success";

			//send an email to the member
			$myemail = new PMProEmail();
			$myemail->sendCancelEmail();

			//send an email to the admin
			$myemail = new PMProEmail();
			$myemail->sendCancelAdminEmail($current_user, $old_level_id);
		} else {
			global $pmpro_error;
			$pmpro_msg = $pmpro_error;
			$pmpro_msgt = "pmpro_error";
		}
	}