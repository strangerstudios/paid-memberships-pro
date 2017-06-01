<?php

global $current_user;

if($current_user->ID)
    $current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);

//is there a default level to redirect to?
if (defined("PMPRO_DEFAULT_LEVEL"))
    $default_level = intval(PMPRO_DEFAULT_LEVEL);
else
    $default_level = false;

if ($default_level) {
    wp_redirect(pmpro_url("checkout", "?level=" . $default_level));
    exit;
}

global $wpdb, $pmpro_msg, $pmpro_msgt;
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

global $pmpro_levels, $pmpro_level_order;

$pmpro_levels = pmpro_getAllLevels(false, true);
$pmpro_level_order = pmpro_getOption('level_order');

if(!empty($pmpro_level_order))
{
    $order = explode(',',$pmpro_level_order);

    //reorder array
    $reordered_levels = array();
    foreach($order as $level_id) {
        foreach($pmpro_levels as $key=>$level) {
            if($level_id == $level->id)
                $reordered_levels[$key] = $pmpro_levels[$key];
        }
    }

    $pmpro_levels = $reordered_levels;
}

$pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);
