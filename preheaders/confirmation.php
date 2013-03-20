<?php
	global $current_user, $pmpro_invoice;
	
	//must be logged in
	if(!$current_user->ID || !$current_user->membership_level->ID)
		wp_redirect(home_url());
	
	//if membership is a paying one, get invoice from DB
	if(!pmpro_isLevelFree($current_user->membership_level))
	{
		$pmpro_invoice = new MemberOrder();
		$pmpro_invoice->getLastMemberOrder($current_user->ID, apply_filters("pmpro_confirmation_order_status", array("success", "pending")));
	}
?>