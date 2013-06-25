<?php
	global $current_user, $pmpro_invoice;
	
	//get invoice from DB
	if(!empty($_REQUEST['invoice']))
		$invoice_code = $_REQUEST['invoice'];
	else
		$invoice_code = NULL;
		
	if(empty($invoice_code))
	{
		if(!defined('PMPRO_FLAG_NO_INVOICE_REDIRECT') || PMPRO_FLAG_NO_INVOICE_REDIRECT == false)
			wp_redirect(pmpro_url("account"));	//no code
	}
	else
	{
		$pmpro_invoice = new MemberOrder($invoice_code);
		//var_dump($pmpro_invoice);
		if(!$pmpro_invoice->id)
			wp_redirect(pmpro_url("account")); //no match
		
		//make sure they have permission to view this
		if(!current_user_can("administrator") && $current_user->ID != $pmpro_invoice->user_id)
			wp_redirect(pmpro_url("account")); //no permission				
	}