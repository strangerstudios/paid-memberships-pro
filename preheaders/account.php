<?php

global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt;

if($current_user->ID)
    $current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

if (isset($_REQUEST['msg'])) {
    if ($_REQUEST['msg'] == 1) {
        $pmpro_msg = __('Your membership status has been updated - Thank you!', 'paid-memberships-pro' );
    } else {
        $pmpro_msg = __('Sorry, your request could not be completed - please try again in a few moments.', 'paid-memberships-pro' );
        $pmpro_msgt = "pmpro_error";
    }
} else {
    $pmpro_msg = false;
}

//if no user, redirect to levels page
if (empty($current_user->ID)) {
    $redirect = apply_filters("pmpro_account_preheader_no_user_redirect", pmpro_url("levels"));
    if ($redirect) {
        wp_redirect($redirect);
        exit;
    }
}

//if no membership level, redirect to levels page
if (empty($current_user->membership_level->ID)) {
    $redirect = apply_filters("pmpro_account_preheader_redirect", pmpro_url("levels"));
    if ($redirect) {
        wp_redirect($redirect);
        exit;
    }
}

global $pmpro_levels;
$pmpro_levels = pmpro_getAllLevels();
