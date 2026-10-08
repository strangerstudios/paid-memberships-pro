<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Delete a group.
$group_id = isset( $_REQUEST['group_id'] ) ? (int) $_REQUEST['group_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in adminpages/membershiplevels.php (pmpro_membershiplevels_nonce) before this file is included.
pmpro_delete_level_group( $group_id );