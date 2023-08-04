<?php
/*
	Clean things up when deletes happen, etc. (This stuff needs a better home.)
*/
// deleting a user? remove their account info.
function pmpro_delete_user( $user_id ) {
	global $pmpro_user_taxonomies;

	if ( empty( $user_id ) ) {
		return false;
	}

	// Check if an admin chose to cancel the user's active subscriptions.
	$cancel_active_subscriptions =  isset( $_REQUEST['pmpro_delete_active_subscriptions'] ) && $_REQUEST['pmpro_delete_active_subscriptions'] == '1';

	/**
	 * Filter to set whether or not to cancel active subscriptions when a user is deleted.
	 *
	 * @since 2.12
	 *
	 * @param bool $cancel_active_subscriptions True or false.
	 * @param int  $user_id                     The WordPress user ID.
	 */
	if ( apply_filters( 'pmpro_user_deletion_cancel_active_subscriptions', $cancel_active_subscriptions, $user_id ) ) {
		pmpro_changeMembershipLevel( 0, $user_id );
	}

	//Remove all membership history for this user from the pmpro_memberships_users table
	if ( isset( $_REQUEST['pmpro_delete_member_history'] ) && 
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
 * @param array   $userids      Array of IDs for users being deleted.
 * @since 2.10
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

	$sqlQuery = $wpdb->prepare( "SELECT COUNT(*) as members FROM $wpdb->pmpro_memberships_users WHERE user_id IN (%s)", implode( "," , $userids ) );

	$member_history = $wpdb->get_var( $sqlQuery );

	// Make sure that there is actually PMPro content to delete for these users.
	if ( empty( $userids_have_levels ) && empty( $member_history ) ) {
		// No PMPro content to delete, so we don't need to add anything to the form.
		return;
	}

	$allowed_html = array( 'strong' => array() );

	?>
	<div class='pmpro_delete_user_actions'>
		<p><?php esc_html_e( 'What should be done with the PMPro Membership data for these users?', 'paid-memberships-pro' ); ?></p>
	<?php
	// Show a notice if users for deletion have an an active membership level.
	if ( ! empty( $userids_have_levels ) ) { ?>
		<div class="notice notice-error inline">
			<?php
			if ( count( $userids ) > 1 ) {
				echo '<p>' . wp_kses( __( '<strong>Warning:</strong> One or more users for deletion have an active membership level.', 'paid-memberships-pro' ), $allowed_html ) . '</p>' ;
			} else {
				echo  '<p>' . wp_kses( __( '<strong>Warning:</strong> This user has an active membership level.', 'paid-memberships-pro' ), $allowed_html ) . '</p>';
			}
			?>
		</div>
		<p><input type='checkbox' name='pmpro_delete_active_subscriptions' id='pmpro_delete_active_subscriptions' value='1' /><label for='pmpro_delete_active_subscriptions'><?php esc_html_e('Cancel any related membership levels first. This may trigger cancellations at the gateway or other third party services.', 'paid-memberships-pro' ); ?></label></p>
		<?php
	}
		

	if ( intval( $member_history ) > 0 ) {
		?>
		<p><input type='checkbox' name='pmpro_delete_member_history' id='pmpro_delete_member_history' value='1' /><label for='pmpro_delete_member_history'><?php esc_html_e('Delete any related membership history. Order history will be retained.', 'paid-memberships-pro' ); ?></label></p>
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

/**
 * Delete all membership data for a specific user from the membership users table.
 *
 * @param int $user_id The WordPress user ID.
 * @since 2.10
 */
function pmpro_delete_membership_history( $user_id ) {

	if ( empty( $user_id ) ) {
		return false;
	}
	
	global $wpdb;
	$wpdb->delete( 
		$wpdb->pmpro_memberships_users, 
		array( 'user_id' => $user_id ), 
		array( '%d' )
	);
	// we don't remove the orders because it would affect reporting
}