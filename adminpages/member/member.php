<?php
// only admins can get this
$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( $cap ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// Global vars.
global $wpdb, $current_user, $msg, $msgt, $pmpro_currency_symbol, $pmpro_required_user_fields, $pmpro_error_fields, $pmpro_msg, $pmpro_msgt;

// Check if editing a user.
if ( ! empty( $_REQUEST['user_id'] ) ) {
	$user_id = intval( $_REQUEST['user_id'] );
	$user = get_userdata( $user_id );
	if ( empty( $user->ID ) ) {
		$user_id = false;
	} else  {
		// We have a user, let's get the user metadata
		$user_notes = get_user_meta( $user_id, 'user_notes', true );
	}	
} else {
	$user_id = '';
}

// Define a constant if user is editing their own membership.
if ( ! defined( 'IS_PROFILE_PAGE' ) ) {
	define( 'IS_PROFILE_PAGE', ( $user_id === $current_user->ID ) );
}

// Some vars for the form.
$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
$user_pass = ! empty( $_POST['pass1'] ) ? $_POST['pass1'] : ''; // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$send_password = ! empty( $_POST['send_password'] ) ? intval( $_POST['send_password'] ) : false;
$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );
$user_notes = ! empty( $_POST['user_notes'] ) ? stripslashes( sanitize_text_field( $_POST['user_notes'] ) ) : '';

// Set values if editing a user and the form wasn't submitted.
if ( ! empty( $user ) && empty( $_POST ) ) {
	$user_login = $user->user_login;
	$user_display_name = $user->display_name;
	$user_email = $user->user_email;
	$first_name = $user->first_name;
	$last_name = $user->last_name;
	$user_notes = $user->user_notes;
	$role = implode( ', ', $user->roles );
}

// Get the current user level.
if ( isset( $_POST['membership_level'] ) ) {
	$membership_level = intval( $_POST['membership_level'] );
} elseif ( ! empty( $user ) ) {
	$user->membership_level = pmpro_getMembershipLevelForUser( $user_id );
	if ( ! empty( $user->membership_level ) ) {
		$membership_level = $user->membership_level->id;
	} else {
		$membership_level = '';
	}
} else {
		$membership_level = '';
}

// Handle user update/insert.
if ( ! empty( $_POST ) ) {
	// Make sure the current user can edit this user.
	// Alterred from wp-admin/user-edit.php.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( __( 'Sorry, you are not allowed to edit this user.', 'paid-memberships-pro' ) );
	}
	
	// Update the email address in signups, if present.
	// Alterred from wp-admin/user-edit.php.
	if ( is_multisite() ) {
		$user = get_userdata( $user_id );

		if ( $user->user_login && isset( $_POST['email'] ) && is_email( $_POST['email'] ) && $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM {$wpdb->signups} WHERE user_login = %s", $user->user_login ) ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->signups} SET user_email = %s WHERE user_login = %s", $_POST['email'], $user_login ) );
		}
	}
	
	// check for required fields
	$pmpro_required_user_fields = apply_filters(
		'pmpro_required_user_fields', array(
			'user_login' => $user_login,
			'user_email' => $user_email,
		)
	);
	$pmpro_error_fields = array();
	foreach ( $pmpro_required_user_fields as $key => $value ) {
		if ( empty( $value ) ) {
			$pmpro_error_fields[] = $key;
		}
	}

	if ( ! empty( $pmpro_error_fields ) ) {
		pmpro_setMessage( __( 'Please fill out all required fields:', 'paid-memberships-pro' ) . ' ' . implode( ', ', $pmpro_error_fields ), 'notice-error' );
	}

	// Check that the email and username are available.
	$ouser      = get_user_by( 'login', $user_login );
	$oldem_user = get_user_by( 'email', $user_email );

	/**
	 * This hook can be used to allow multiple accounts with the same email address.
	 * This is also set in preheaders/checkout.php
	 * @todo Abstract to a function so we only have one filter.
	 * Return null to allow duplicate users with the same email.
	 */
	$oldemail = apply_filters( "pmpro_checkout_oldemail", ( false !== $oldem_user ? $oldem_user->user_email : null ) );

	if ( ! empty( $ouser->user_login ) && $ouser->id !== $user_id ) {
		pmpro_setMessage( __( "That username is already taken. Please try another.", 'paid-memberships-pro' ), "notice-error" );
		$pmpro_error_fields[] = "username";
	}

	if ( ! empty( $oldemail ) && $oldem_user->id !== $user_id ) {
		pmpro_setMessage( __( "That email address is already in use. Please log in, or use a different email address.", 'paid-memberships-pro' ), "notice-error" );
		$pmpro_error_fields[] = "bemail";
		$pmpro_error_fields[] = "bconfirmemail";
	}

	// okay so far?
	if ( $pmpro_msgt != 'notice-error' ) {
		// random password if needed
		if ( ! $user_id && empty( $user_pass ) ) {
			$user_pass = wp_generate_password();
			$send_password = true; // Force this option to be true, if the password field was empty so the email may be sent.
		}

		// User data.
		$user_to_post = array( 
			'ID' => $user_id,
			'user_login' => $user_login,
			'user_email' => $user_email,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'role' => $role,
		);

		// Update or insert user.
		$user_id = ! empty( $_REQUEST['user_id'] ) ? wp_update_user($user_to_post) : wp_insert_user($user_to_post);
	}

	if ( ! $user_id ) {
		// Error during user update/insert.
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			pmpro_setMessage( __( 'Error updating user.', 'paid-memberships-pro' ), 'notice-error' );
			$user_id = intval( $_REQUEST['user_id'] );	// Reset user_id var so rest of form works.
		} else {
			pmpro_setMessage( __( 'Error creating user.', 'paid-memberships-pro' ), 'notice-error' );
		}
	} elseif ( $pmpro_msgt === 'notice-error' ) {		
		// There was another error above.
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			$pmpro_msg = esc_html__( 'There was an error updating this user: ', 'paid-memberships-pro' ) . $pmpro_msg;
		} else {
			$pmpro_msg = esc_html__( 'There was an error adding this user: ', 'paid-memberships-pro' ) . $pmpro_msg;
		}
	} else {
		// Update/insert all good.

		// Other user meta
		//update_user_meta( $user_id, 'user_notes', $user_notes );

		// Update the user's membership level.
		// TODO: ...

		// Notify users if needed.
		if ( $send_password ) {
			wp_new_user_notification( $user_id, null, 'user' );
		}

		// clear vars
		$user_pass = '';
		
		// Set message and redirect if this is a new user.		
		if ( empty( $_REQUEST['user_id'] ) ) {
			// User inserted.
			pmpro_setMessage( esc_html__( 'User added.', 'paid-memberships-pro' ), 'updated' );
			$_REQUEST['user_id'] = $user_id;
			?>
			<script>
				jQuery(document).ready(function() {					
					jQuery('#tab-2').click();
				});
			</script>
			<?php
		} else {
			// Users updated.
			pmpro_setMessage( esc_html__( 'User updated.', 'paid-memberships-pro' ), 'updated' );
		}			
	}
}
?>

<?php
/**
 * Load the Paid Memberships Pro dashboard-area header
 */
require_once( PMPRO_DIR . '/adminpages/admin_header.php' );
?>

<hr class="wp-header-end">
<h1 class="wp-heading-inline">
	<?php		
	if ( ! empty( $user->ID ) ) {
		echo get_avatar( $user->ID, 96 );
		echo wp_kses_post( sprintf( __( 'Edit Member: %s', 'paid-memberships-pro' ), '<strong>' . $user->display_name . '</strong>' ) );
	} else {
		echo esc_html_e( 'Add Member', 'paid-memberships-pro' );
	}
	?>
</h1>
<div>		
	<form class="pmpro-members" action="" method="post">
		<nav id="user-menu" role="tablist" aria-label="Add Member Field Tabs">
			<button
				role="tab"
				aria-selected="true"
				aria-controls="pmpro-user-info-panel"
				id="tab-1"				
				tabindex="0">
				<?php esc_html_e( 'User Info', 'paid-memberships-pro' ); ?>
			</button>				
			<button
				role="tab"
				aria-selected="false"
				aria-controls="pmpro-membership-panel"
				id="tab-2"
				<?php if ( empty( $user ) ) { ?>disabled="disabled"<?php } ?>
				tabindex="-1">
				<?php esc_html_e( 'Membership', 'paid-memberships-pro' ); ?>
			</button>
			<button
				role="tab"
				aria-selected="false"
				aria-controls="pmpro-subscriptions-panel"
				id="tab-3"
				<?php if ( empty( $user ) ) { ?>disabled="disabled"<?php } ?>
				tabindex="-1">
				<?php esc_html_e( 'Subscriptions', 'paid-memberships-pro' ); ?>
			</button>
			<button
				role="tab"
				aria-selected="false"
				aria-controls="pmpro-orders-panel"
				id="tab-4"
				<?php if ( empty( $user ) ) { ?>disabled="disabled"<?php } ?>
				tabindex="-1">
				<?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?>
			</button>
			<button
				role="tab"
				aria-selected="false"
				aria-controls="pmpro-other-info-panel"
				id="tab-5"
				<?php if ( empty( $user ) ) { ?>disabled="disabled"<?php } ?>
				tabindex="-1">
				<?php esc_html_e( 'Other Info', 'paid-memberships-pro' ); ?>
			</button>						
		</nav>

		<?php
			require_once( PMPRO_DIR . '/adminpages/member/user-info.php' );
			
			// Only show for existing users.
			if ( ! empty( $_REQUEST['user_id'] ) ) {
				require_once( PMPRO_DIR . '/adminpages/member/memberships.php' );
				require_once( PMPRO_DIR . '/adminpages/member/subscriptions.php' );
				require_once( PMPRO_DIR . '/adminpages/member/orders.php' );
			}

			require_once( PMPRO_DIR . '/adminpages/member/other.php' );
		?>	
		<div class="submit">
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">					
			<input type="submit" name="submit" value="Save User" class="button button-primary">
		</div>			
	</form>			
</div>

<?php
	require_once( PMPRO_DIR . '/adminpages/admin_footer.php' );	
