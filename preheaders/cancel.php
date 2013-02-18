<?php
	global $besecure;
	$besecure = false;	
	
	global $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_confirm; 
	
	//if they don't have a membership, send them back to the subscription page
	if(!$current_user->membership_level->ID)
	{
		wp_redirect(pmpro_url("levels"));
	}		
	
	if(isset($_REQUEST['confirm']))
		$pmpro_confirm = $_REQUEST['confirm'];
	else
		$pmpro_confirm = false;
		
	if($pmpro_confirm)
	{		
		$old_level_id = $current_user->membership_level->id;
		$worked = pmpro_changeMembershipLevel(false, $current_user->ID);						
		if($worked === true)
		{			
			$pmpro_msg = "Your membership has been cancelled.";
			$pmpro_msgt = "pmpro_success";
			
			//send an email to the member
			$myemail = new PMProEmail();
			$myemail->sendCancelEmail();
			
			//send an email to the admin
			$myemail = new PMProEmail();
			$myemail->sendCancelAdminEmail($current_user, $old_level_id);			
		}
		else
		{
			global $pmpro_error;
			$pmpro_msg = $pmpro_error;
			$pmpro_msgt = "pmpro_error";			
		}		
	}		
?>
