<?php

global $current_user, $pmpro_invoice;

if($current_user->ID)
    $current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

/*
	Use the filter to add your gateway here if you want to show them a message on the confirmation page while their checkout is pending.
	For example, when PayPal Standard is used, we need to wait for PayPal to send a message through IPN that the payment was accepted.
	In the meantime, the order is in pending status and the confirmation page shows a message RE waiting.
*/
$gateways_with_pending_status = apply_filters('pmpro_gateways_with_pending_status', array('paypalstandard', 'twocheckout', 'gourl'));
	
//must be logged in
if (empty($current_user->ID) || (empty($current_user->membership_level->ID) && !in_array(pmpro_getGateway(), $gateways_with_pending_status)))
    wp_redirect(home_url());

//if membership is a paying one, get invoice from DB
if (!empty($current_user->membership_level) && !pmpro_isLevelFree($current_user->membership_level)) {
    $pmpro_invoice = new MemberOrder();
    $pmpro_invoice->getLastMemberOrder($current_user->ID, apply_filters("pmpro_confirmation_order_status", array("success", "pending")));
}
