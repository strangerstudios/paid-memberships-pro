<?php
/**
 * All BuddyPress/BuddyBoss compatibility goes in here.
 */

/**
 * Cancel all active subscriptions right before the user deletes their own account. 
 * The 'bp_core_pre_delete_account' is primarily used for self-deletions, as requested through Settings.
 * 
 * @see https://www.buddyboss.com/resources/reference/functions/bp_core_delete_account/
 *
 * @param int $user_id - The user ID that is about to be deleted from WordPress.
 * @since TBD
 */
function pmpro_buddypress_cancel_sub_self_delete( $user_id ) {

    if ( pmpro_maybe_cancel_subscription( 'buddypress' ) ) {
        pmpro_changeMembershipLevel( 0, $user_id );
    }
}
add_action( 'bp_core_pre_delete_account', 'pmpro_buddypress_cancel_sub_self_delete' );