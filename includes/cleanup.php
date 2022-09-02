<?php

/*
	Clean things up when deletes happen, etc. (This stuff needs a better home.)
*/
// deleting a user? remove their account info.
function pmpro_delete_user( $user_id = null ) {

	//Disable any active subscriptions that are associated with the account
	if( isset( $_REQUEST['pmpro_delete_active_subscriptions'] ) && 
		$_REQUEST['pmpro_delete_active_subscriptions'] == '1' ) {
		if ( pmpro_changeMembershipLevel( 0, $user_id ) ) {
			// okay
		} else {
			// okay, guessing they didn't have a level
		}
	}

	//Remove all membership history for this user from the pmpro_memberships_users table
	if( isset( $_REQUEST['pmpro_delete_member_history'] ) && 
		$_REQUEST['pmpro_delete_member_history'] == '1' ) {
		pmpro_delete_membership_history( $user_id );
	}	
	
}
add_action( 'delete_user', 'pmpro_delete_user' );
add_action( 'wpmu_delete_user', 'pmpro_delete_user' );

/**
 * Show a notice on the Delete User form so admin knows that membership and subscriptions will be cancelled.
 *
 * @param WP_User $current_user WP_User object for the current user.
 * @param int[]   $userids      Array of IDs for users being deleted.
 */
function pmpro_delete_user_form_notice( $current_user, $userids ) {

	global $wpdb;
	// Check if any users for deletion have an an active membership level.
	foreach ( $userids as $user_id ) {
		$userids_have_levels = pmpro_hasMembershipLevel( null, $user_id );
		if ( ! empty( $userids_have_levels ) ) {
			break;
		}
	}

	?>
	<div class='pmpro_delete_user_actions'>
		<p><?php _e( 'What should be done with the PMPro Membership data for these users?', 'paid-memberships-pro' ); ?></p>
	<?php
	// Show a notice if users for deletion have an an active membership level.
	if ( ! empty( $userids_have_levels ) ) { ?>
		<div class="notice notice-error inline">
			<?php
			if ( count( $userids ) > 1 ) {
				_e( '<p><strong>Warning:</strong> One or more users for deletion have an active membership level.</p>', 'paid-memberships-pro' );
			} else {
				_e( '<p><strong>Warning:</strong> This user has an active membership level.</p>', 'paid-memberships-pro' );
			}
			?>
		</div>
		<p><input type='checkbox' name='pmpro_delete_active_subscriptions' id='pmpro_delete_active_subscriptions' value='1' /><label for='pmpro_delete_active_subscriptions'><?php _e('Cancel any related membership levels first. This may trigger cancellations at the gateway or other third party services.', 'paid-memberships-pro' ); ?></label></p>
		<?php
		}
		$member_history = $wpdb->get_var( "SELECT COUNT(*) as members FROM $wpdb->pmpro_memberships_users WHERE user_id IN (".implode(",",$userids ) .")" );

		if( intval( $member_history ) > 0 ) {
			?>
			<p><input type='checkbox' name='pmpro_delete_member_history' id='pmpro_delete_member_history' value='1' /><label for='pmpro_delete_member_history'><?php _e('Delete any related membership history. Order history will be retained.', 'paid-memberships-pro' ); ?></label></p>
			<?php
		}
		?>
	</div>
	<?php

}
add_action( 'delete_user_form', 'pmpro_delete_user_form_notice', 10, 2 );

// deleting a category? remove any level associations
function pmpro_delete_category( $cat_id = null ) {
	global $wpdb;
	$wpdb->delete(
		$wpdb->pmpro_memberships_categories,
		array( 'category_id' => $cat_id ),
		'%d'
	);
}
add_action( 'delete_category', 'pmpro_delete_category' );

// deleting a post? remove any level associations
function pmpro_delete_post( $post_id = null ) {
	global $wpdb;
	$wpdb->delete( 
		$wpdb->pmpro_memberships_pages, 
		array( 'page_id' => $post_id ), 
		array( '%d' )
	);
}
add_action( 'delete_post', 'pmpro_delete_post' );

function pmpro_delete_membership_history( $user_id ) {
	global $wpdb;
	$wpdb->delete( 
		$wpdb->pmpro_memberships_users, 
		array( 'user_id' => $user_id ), 
		array( '%d' )
	);
	// we don't remove the orders because it would affect reporting
}