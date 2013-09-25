<?php
	global $current_user, $pmpro_invoice;
	
	//must be logged in
	if(empty($current_user->ID) || (empty($current_user->membership_level->ID) && pmpro_getOption("gateway") != "paypalstandard" && pmpro_getOption("gateway") != "twocheckout"))
		wp_redirect(home_url());
	
	//if membership is a paying one, get invoice from DB
	if(!empty($current_user->membership_level) && !pmpro_isLevelFree($current_user->membership_level))
	{
		$pmpro_invoice = new MemberOrder();
		$pmpro_invoice->getLastMemberOrder($current_user->ID, apply_filters("pmpro_confirmation_order_status", array("success", "pending")));
	}