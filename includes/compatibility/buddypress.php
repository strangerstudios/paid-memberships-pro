<?php
/**
 * All BuddyPress/BuddyBoss compatibility goes in here.
 */

/**
 * If a user is deleting their own account, we want to make sure we cancel their subscriptions as well.
 *
 * @see https://www.buddyboss.com/resources/reference/functions/bp_core_delete_account/
 *
 * @param int $user_id - The user ID that is about to be deleted from WordPress.
 * @since 2.12
 */
function pmpro_buddypress_cancel_sub_self_delete( $user_id ) {
	add_filter( 'pmpro_user_deletion_cancel_active_subscriptions', '__return_true' );
}
add_action( 'bp_core_pre_delete_account', 'pmpro_buddypress_cancel_sub_self_delete' );
