<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified via check_admin_referer() on pmpro_membershiplevels_nonce in adminpages/membershiplevels.php before this file is included.

$group_id = (int) $_REQUEST['saveid'];
$group_name = sanitize_text_field( stripslashes( $_REQUEST['name'] ) );
$allow_multi = empty( $_REQUEST['allow_multiple_selections'] ) ? 0 : 1;
$displayorder = (int) $_REQUEST['displayorder'];
if ( $group_id > 0 ) {
    // Save the group.
    pmpro_edit_level_group( $group_id, $group_name, $allow_multi, $displayorder );
} else {
    // Add a new group.
    pmpro_create_level_group( $group_name, $allow_multi );
}