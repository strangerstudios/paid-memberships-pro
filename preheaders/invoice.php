<?php

global $current_user, $pmpro_invoice;

if($current_user->ID)
    $current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

if (!is_user_logged_in()) {
    wp_redirect(pmpro_url("account"));
    exit;
}

//get invoice from DB
if (!empty($_REQUEST['invoice']))
    $invoice_code = sanitize_text_field($_REQUEST['invoice']);
else
    $invoice_code = NULL;

if (!empty($invoice_code)) {
    $pmpro_invoice = new MemberOrder($invoice_code);

    //var_dump($pmpro_invoice);
    if (!$pmpro_invoice->id) {
        wp_redirect(pmpro_url("account")); //no match
        exit;
    }

    //make sure they have permission to view this
    if (!current_user_can("administrator") && $current_user->ID != $pmpro_invoice->user_id) {
        wp_redirect(pmpro_url("account")); //no permission
        exit;
    }
}
