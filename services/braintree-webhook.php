<?php			
	global $logstr;
	$logstr = "";		
	
	function pmpro_remove_headers($headers)
	{
		var_dump($headers);
		return array();
	}
	add_filter('wp_headers', 'pmpro_remove_headers');
	
	//in case the file is loaded directly
	if(!defined("WP_USE_THEMES"))
	{
		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}
	
	if(!class_exists("Braintree"))
		require_once(dirname(__FILE__) . "/../includes/lib/Braintree/Braintree.php");
			
	//config
	Braintree_Configuration::environment(pmpro_getOption("gateway_environment"));
	Braintree_Configuration::merchantId(pmpro_getOption("braintree_merchantid"));
	Braintree_Configuration::publicKey(pmpro_getOption("braintree_publickey"));
	Braintree_Configuration::privateKey(pmpro_getOption("braintree_privatekey"));	
	
	//verify		
	if(!empty($_REQUEST['bt_challenge']))
		echo Braintree_WebhookNotification::verify($_REQUEST['bt_challenge']);
		
	exit;
	
		
	//get notification
	$webhookNotification = Braintree_WebhookNotification::parse(
	  $_POST['bt_signature'], $_POST['bt_payload']
	);
	
	//which kind?
	if($webhookNotification->kind == "subscription_charged_successfully")
	{
		//get old order
		
		//create new order
		
		//save it
		
		//email
	}
	elseif($webhookNotification->kind == "subscription_charged_unsuccessfully")
	{
		//get old order
		
		//create order for email
		
		//Email the user and ask them to update their credit card information			
				
		//Email admin so they are aware of the failure
	}
	elseif($webhookNotification->kind == "subscription_canceled")
	{
		//for one of our users? if they still have a membership, notify the admin			
		//get user by subscription
		if(!empty($user->ID))
		{			
			do_action("pmpro_braintree_subscription_cancelled", $user->ID);	
			
			$pmproemail = new PMProEmail();	
			$pmproemail->data = array("body"=>"<p>" . $user->display_name . " (" . $user->user_login . ", " . $user->user_email . ") has had their payment subscription cancelled by Braintree. Please check that this user's membership is cancelled on your site if it should be.</p>");
			$pmproemail->sendEmail(get_bloginfo("admin_email"));	
		}
		else
		{
			die("Not a user here.");
		}		
	}
