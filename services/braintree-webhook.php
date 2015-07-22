<?php
	/*
		The Braintree webhook
	*/

	//when loading directly, make sure 200 status is given
	global $isapage;
	$isapage = true;

    global $logstr;
    $logstr = "";

	//in case the file is loaded directly
	if(!defined("WP_USE_THEMES"))
	{
		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}

	//globals
	global $wpdb;

	//load Braintree library, gateway class constructor does config
	require_once(dirname(__FILE__) . "/../classes/gateways/class.pmprogateway_braintree.php");
	$gateway = new PMProGateway_braintree();

	//verify
	if(!empty($_REQUEST['bt_challenge']))
		echo Braintree_WebhookNotification::verify($_REQUEST['bt_challenge']);
	else
		$logstr .= "Guessing you are just testing the URL out. Check that the timestamp updates on refresh to make sure this isn't being cached.";

	//only verifying?
	if(empty($_REQUEST['bt_payload']))
		pmpro_braintreeWebhookExit();

	//get notification
    try
    {
        $webhookNotification = Braintree_WebhookNotification::parse(
            $_REQUEST['bt_signature'], $_REQUEST['bt_payload']
        );
    }
    catch ( Exception $e )
    {
        {
            $logstr .= "Couldn't get notification with payload " . $_REQUEST['bt_payload'] . ". " . $e->getMessage();
            pmpro_braintreeWebhookExit();
        }
    }


	//subscription charged sucessfully
	if($webhookNotification->kind == "subscription_charged_successfully")
	{
		//need a subscription id
		if(empty($webhookNotification->subscription->id))
        {
            $logstr .= "No subscription ID.";
            pmpro_braintreeWebhookExit();
        }

		//figure out which order to attach to
		$old_order = new MemberOrder();
		$old_order->getLastMemberOrderBySubscriptionTransactionID($webhookNotification->subscription->id);

		//no order?
		if(empty($old_order))
        {
            $logstr .= "Couldn't find the original subscription with ID=" . $webhookNotification->subscription->id . ".";
            pmpro_braintreeWebhookExit();
        }

		//create new order
		$user_id = $old_order->user_id;
		$user = get_userdata($user_id);
		$user->membership_level = pmpro_getMembershipLevelForUser($user_id);

		if(empty($user))
        {
            $logstr .= "Couldn't find the old order's user. Order ID = " . $old_order->id . ".";
            pmpro_braintreeWebhookExit();
        }

		//data about this transaction
		$transaction = $webhookNotification->transactions[0];

		//alright. create a new order/invoice
		$morder = new MemberOrder();
		$morder->user_id = $old_order->user_id;
		$morder->membership_id = $old_order->membership_id;
		$morder->InitialPayment = $transaction->amount;	//not the initial payment, but the order class is expecting this
		$morder->PaymentAmount = $transaction->amount;
		$morder->payment_transaction_id = $transaction->id;
		$morder->subscription_transaction_id = $webhookNotification->subscription->id;

		$morder->gateway = $old_order->gateway;
		$morder->gateway_environment = $old_order->gateway_environment;

		$morder->FirstName = $transaction->billing_details->first_name;
		$morder->LastName = $transaction->billing_details->last_name;
		$morder->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . $old_order->user_id . "' LIMIT 1");
		$morder->Address1 = $transaction->billing_details->street_address;
		$morder->City = $transaction->billing_details->locality;
		$morder->State = $transaction->billing_details->region;
		//$morder->CountryCode = $old_order->billing->city;
		$morder->Zip = $transaction->billing_details->postal_code;
		$morder->PhoneNumber = $old_order->billing->phone;

		$morder->billing->name = trim($transaction->billing_details->first_name . " " . $transaction->billing_details->last_name);
		$morder->billing->street = $transaction->billing_details->street_address;
		$morder->billing->city = $transaction->billing_details->locality;
		$morder->billing->state = $transaction->billing_details->region;
		$morder->billing->zip = $transaction->billing_details->postal_code;
		$morder->billing->country = $transaction->billing_details->country_code_alpha2;
		$morder->billing->phone = $old_order->billing->phone;

		//get CC info that is on file
		$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
		$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
		$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
		$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);
		$morder->ExpirationDate = $morder->expirationmonth . $morder->expirationyear;
		$morder->ExpirationDate_YdashM = $morder->expirationyear . "-" . $morder->expirationmonth;

		//save
		$morder->status = "success";
		$morder->saveOrder();
		$morder->getMemberOrderByID($morder->id);

		//email the user their invoice
		$pmproemail = new PMProEmail();
		$pmproemail->sendInvoiceEmail($user, $morder);

		exit;
	}

	/*
		Note here: These next three checks all work the same way and send the same "billing failed" email, but kick off different actions based on the kind.
	*/

	//subscription charged unsuccessfully
	if($webhookNotification->kind == "subscription_charged_unsuccessfully")
	{
		//need a subscription id
		if(empty($webhookNotification->subscription->id)) {
            $logstr .= "No subscription ID.";
            pmpro_braintreeWebhookExit();
        }

		//figure out which order to attach to
		$old_order = new MemberOrder();
		$old_order->getLastMemberOrderBySubscriptionTransactionID($webhookNotification->subscription->id);

		if(empty($old_order))
        {
            $logstr .= "Couldn't find old order for failed payment with subscription id=" . $webhookNotification->subscription->id;
            pmpro_braintreeWebhookExit();
        }

		$user_id = $old_order->user_id;
		$user = get_userdata($user_id);
		$user->membership_level = pmpro_getMembershipLevelForUser($user_id);

		//generate billing failure email
		do_action("pmpro_subscription_payment_failed", $old_order);

		$transaction = $webhookNotification->transactions[0];

		//prep this order for the failure emails
		$morder = new MemberOrder();
		$morder->user_id = $user_id;
		$morder->billing->name = trim($transaction->billing_details->first_name . " " . $transaction->billing_details->first_name);
		$morder->billing->street = $transaction->billing_details->street_address;
		$morder->billing->city = $transaction->billing_details->locality;
		$morder->billing->state = $transaction->billing_details->region;
		$morder->billing->zip = $transaction->billing_details->postal_code;
		$morder->billing->country = $transaction->billing_details->country_code_alpha2;
		$morder->billing->phone = $old_order->billing->phone;

		//get CC info that is on file
		$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
		$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
		$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
		$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);

		// Email the user and ask them to update their credit card information
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureEmail($user, $morder);

		// Email admin so they are aware of the failure
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);

		echo "Sent email to the member and site admin. Thanks.";
		exit;
	}

	//subscription went past due
	if($webhookNotification->kind == "subscription_went_past_due")
	{
		//need a subscription id
		if(empty($webhookNotification->subscription->id))
        {
            $logstr .= "No subscription ID.";
            pmpro_braintreeWebhookExit();
        }

		//figure out which order to attach to
		$old_order = new MemberOrder();
		$old_order->getLastMemberOrderBySubscriptionTransactionID($webhookNotification->subscription->id);

		if(empty($old_order))
        {
            $logstr .= "Couldn't find old order for failed payment with subscription id=" . $webhookNotification->subscription->id;
            pmpro_braintreeWebhookExit();
        }

		$user_id = $old_order->user_id;
		$user = get_userdata($user_id);
		$user->membership_level = pmpro_getMembershipLevelForUser($user_id);

		//generate billing failure email
		do_action("pmpro_subscription_payment_failed", $old_order);
		do_action("pmpro_subscription_payment_went_past_due", $old_order);

		$transaction = $webhookNotification->transactions[0];

		//prep this order for the failure emails
		$morder = new MemberOrder();
		$morder->user_id = $user_id;
		$morder->billing->name = trim($transaction->billing_details->first_name . " " . $transaction->billing_details->first_name);
		$morder->billing->street = $transaction->billing_details->street_address;
		$morder->billing->city = $transaction->billing_details->locality;
		$morder->billing->state = $transaction->billing_details->region;
		$morder->billing->zip = $transaction->billing_details->postal_code;
		$morder->billing->country = $transaction->billing_details->country_code_alpha2;
		$morder->billing->phone = $old_order->billing->phone;

		//get CC info that is on file
		$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
		$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
		$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
		$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);

		// Email the user and ask them to update their credit card information
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureEmail($user, $morder);

		// Email admin so they are aware of the failure
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);

		echo "Sent email to the member and site admin. Thanks.";
		exit;
	}

	//subscription expired
	if($webhookNotification->kind == "subscription_expired")
	{
		//need a subscription id
		if(empty($webhookNotification->subscription->id))
        {
            $logstr .= "No subscription ID.";
            pmpro_braintreeWebhookExit();
        }

		//figure out which order to attach to
		$old_order = new MemberOrder();
		$old_order->getLastMemberOrderBySubscriptionTransactionID($webhookNotification->subscription->id);

		if(empty($old_order))
        {
            $logstr .= "Couldn't find old order for failed payment with subscription id=" . $webhookNotification->subscription->id;
            pmpro_braintreeWebhookExit();
        }

		$user_id = $old_order->user_id;
		$user = get_userdata($user_id);
		$user->membership_level = pmpro_getMembershipLevelForUser($user_id);

		//generate billing failure email
		do_action("pmpro_subscription_expired", $old_order);

		$transaction = $webhookNotification->transactions[0];

		//prep this order for the failure emails
		$morder = new MemberOrder();
		$morder->user_id = $user_id;
		$morder->billing->name = trim($transaction->billing_details->first_name . " " . $transaction->billing_details->first_name);
		$morder->billing->street = $transaction->billing_details->street_address;
		$morder->billing->city = $transaction->billing_details->locality;
		$morder->billing->state = $transaction->billing_details->region;
		$morder->billing->zip = $transaction->billing_details->postal_code;
		$morder->billing->country = $transaction->billing_details->country_code_alpha2;
		$morder->billing->phone = $old_order->billing->phone;

		//get CC info that is on file
		$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
		$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
		$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
		$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);

		// Email the user and ask them to update their credit card information
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureEmail($user, $morder);

		// Email admin so they are aware of the failure
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);

		echo "Sent email to the member and site admin. Thanks.";
		exit;
	}

	//subscription cancelled (they used one l canceled)
	if($webhookNotification->kind == "subscription_canceled")
	{
		//need a subscription id
		if(empty($webhookNotification->subscription->id))
        {
            $logstr .= "No subscription ID.";
            pmpro_braintreeWebhookExit();
        }

		//figure out which order to attach to
		$old_order = new MemberOrder();
		$old_order->getLastMemberOrderBySubscriptionTransactionID($webhookNotification->subscription->id);

		if(empty($old_order))
        {
            $logstr .= "Couldn't find old order for failed payment with subscription id=" . $webhookNotification->subscription->id;
            pmpro_braintreeWebhookExit();
        }

		$user_id = $old_order->user_id;
		$user = get_userdata($user_id);
		$user->membership_level = pmpro_getMembershipLevelForUser($user_id);

		//generate billing failure email
		do_action("pmpro_subscription_cancelled", $old_order);

		$transaction = $webhookNotification->transactions[0];

		//prep this order for the failure emails
		$morder = new MemberOrder();
		$morder->user_id = $user_id;
		$morder->billing->name = trim($transaction->billing_details->first_name . " " . $transaction->billing_details->first_name);
		$morder->billing->street = $transaction->billing_details->street_address;
		$morder->billing->city = $transaction->billing_details->locality;
		$morder->billing->state = $transaction->billing_details->region;
		$morder->billing->zip = $transaction->billing_details->postal_code;
		$morder->billing->country = $transaction->billing_details->country_code_alpha2;
		$morder->billing->phone = $old_order->billing->phone;

		//get CC info that is on file
		$morder->cardtype = get_user_meta($user_id, "pmpro_CardType", true);
		$morder->accountnumber = hideCardNumber(get_user_meta($user_id, "pmpro_AccountNumber", true), false);
		$morder->expirationmonth = get_user_meta($user_id, "pmpro_ExpirationMonth", true);
		$morder->expirationyear = get_user_meta($user_id, "pmpro_ExpirationYear", true);

		// Email the user and ask them to update their credit card information
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureEmail($user, $morder);

		// Email admin so they are aware of the failure
		$pmproemail = new PMProEmail();
		$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);

		echo "Sent email to the member and site admin. Thanks.";
		exit;
	}

function pmpro_braintreeWebhookExit()
{
    global $logstr;

    //for log
    if($logstr)
    {
        $logstr = "Logged On: " . date("m/d/Y H:i:s") . "\n" . $logstr . "\n-------------\n";

        echo $logstr;

        //log in file or email?
        if(defined('PMPRO_BRAINTREE_WEBHOOK_DEBUG') && PMPRO_BRAINTREE_WEBHOOK_DEBUG === "log")
        {
            //file
            $loghandle = fopen(dirname(__FILE__) . "/../logs/braintree-webhook.txt", "a+");
            fwrite($loghandle, $logstr);
            fclose($loghandle);
        }
        elseif(defined('PMPRO_BRAINTREE_WEBHOOK_DEBUG'))
        {
            //email
            if(strpos(PMPRO_BRAINTREE_WEBHOOK_DEBUG, "@"))
                $log_email = PMPRO_BRAINTREE_WEBHOOK_DEBUG;	//constant defines a specific email address
            else
                $log_email = get_option("admin_email");

            wp_mail($log_email, get_option("blogname") . " Braintree Webhook Log", nl2br($logstr));
        }
    }

    exit;
}