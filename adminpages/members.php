<?php
// only admins can get this
$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( $cap ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

// Global vars.
global $wpdb, $msg, $msgt, $pmpro_currency_symbol, $pmpro_required_user_fields, $pmpro_error_fields, $pmpro_msg, $pmpro_msgt;

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

// Some vars for the form.
$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
$user_pass = ! empty( $_POST['pass1'] ) ? $_POST['pass1'] : ''; // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$send_password = ! empty( $_POST['send_password'] ) ? intval( $_POST['send_password'] ) : false;
$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );
//$user_notes = ! empty( $_POST['user_notes'] ) ? stripslashes( sanitize_text_field( $_POST['user_notes'] ) ) : '';

// Set values if editing a user and the form wasn't submitted.
if ( ! empty( $user ) && empty( $_POST ) ) {
	$user_login = $user->user_login;
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
<style>
body.admin_page_pmpro-members .wrap h1.wp-heading-inline {
	margin-bottom: 1em;
}
form {
	display: flex;
	gap:40px;
}
form nav {
	flex-shrink: 0;
    width: 300px;
}
form nav button {
	background: none;
  	border: none;
  	display: block;
    padding: 12px 40px 12px 20px;
    color: #555;
    text-decoration: none;
    border-radius: 5px;
    position: relative;
	width: 100%;
	text-align: left;
}

form nav button[aria-selected="true"] {
    background-color: #e3e8ee;
    color: #333;
}

form nav button:hover {
	background-color: rgba(255,255,255,.7);

}
form nav button:focus {
	outline: 2px solid rgba(0,0,0,.6)
}

form div.panel-wrappers {
	padding: 25px 50px;
    background-color: #FFF;
    border-radius: 5px;
    box-shadow: 0 1px 4px rgb(18 25 97 / 8%);
	width: 100%;
}

form div.submit {
	clear: both;
	display: block;
	padding: 1em 0;
}
form td button.toggle-pass-visibility {
	background: transparent;
	border: none;
}

form td .dashicons {
	vertical-align: middle;
}
</style>

<?php
// Show messages if they exist.
if ( $pmpro_msg ) {
?>
	<div id="message" class="notice is-dismissible <?php echo esc_attr( $pmpro_msgt ); ?>">
	<p><strong><?php echo wp_kses_post( $pmpro_msg ); ?></strong></p>
	<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>	
<?php
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php		
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			echo esc_html( sprintf( __( 'Edit Member %s', 'paid-memberships-pro' ), $user_login ) );
			$userlink = '<a href="user-edit.php?user_id=' . intval( $user_id ) . '" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users">' . __( 'Edit User', 'paid-memberships-pro') . '</a>';
			echo ' &nbsp;' . $userlink;
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
					aria-controls="panel-1"
					id="tab-1"
					tabindex="0">
					User Info
				</button>				
				<button
					role="tab"
					aria-selected="false"
					aria-controls="panel-2"
					id="tab-2"
					tabindex="-1">
					Membership
				</button>							
			</nav>
			<div class="panel-wrappers">
				<div id="panel-1" role="tabpanel" tabindex="0" aria-labelledby="tab-1">
					<h2>User Info</h2>
					<table class="form-table">
						<tr>
							<th><label for="user_logim">Username (required)</label></th>
							<td><input type="text" name="user_login" id="user_login" autocapitalize="none" autocorrect="off" autocomplete="off" required <?php if ( ! empty( $_REQUEST['user_id'] ) ) { ?>readonly="true"<?php } ?> value="<?php echo esc_attr( $user_login ) ?>"></td>
						</tr>
						<tr>
							<th><label for="email">Email (required)</label></th>
							<td><input type="email" name="email" id="email" autocomplete="new-password" spellcheck="false" required value="<?php echo esc_attr( $user_email ) ?>"></td>
						</tr>
						<tr>
							<th><label for="first_name">First Name</label></th>
							<td><input type="text" name="first_name" id="first_name" autocomplete="off" value="<?php echo $first_name ?>"></td>
						</tr>
						<tr>
							<th><label for="last_name">Last Name</label></th>
							<td><input type="text" name="last_name" id="last_name" autocomplete="off" value="<?php echo $last_name ?>"></td>
						</tr>						
						<?php
						// Only show for new users.
						if ( empty( $_REQUEST['user_id'] ) ) {
						?>
						<tr>
							<th><label for="password">Password</label></th>
							<td>
								<input type="password" name="password" id="password" autocomplete="off" required value="">
								<button class="toggle-pass-visibility" aria-controls="password" aria-expanded="false"><span class="dashicons dashicons-visibility toggle-pass-visibility"></span></button>
							</td>
						</tr>
						<tr>
							<th><label for="send_password">Send User Notification</label></th>
							<td><input type="checkbox" name="send_password" id="send_password">
							<label for="send_password">Send the new user an email about their account.</label>
							</td>
						</tr>
						<?php
						}
						?>
						<tr>
							<th><label for="role"><?php esc_html_e( 'Role', 'paid-memberships-pro' ); ?></label></th>
							<td>
								<select name="role" id="role" class="<?php echo pmpro_getClassForField( 'role' ); ?>">
									<?php  wp_dropdown_roles( $role ); ?>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<?php
				// Only show for existing users.
				if ( ! empty( $_REQUEST['user_id'] ) ) {
				?>
				<div id="panel-2" role="tabpanel" tabindex="0" aria-labelledby="tab-2" hidden>
					<h2>Membership</h2>													
					<p>(New UI goes here.)</p>					
				</div>				
				<?php
				}
				?>
					
				<div class="submit">
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">					
					<input type="submit" name="submit" value="Save User" class="button button-primary">
				</div>
			</div>
		</form>			
    </div>
</div>
<script>
window.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll('[role="tab"]');
	const tabList = document.querySelector('[role="tablist"]');

	// Add a click event handler to each tab
	tabs.forEach((tab) => {
	tab.addEventListener("click", changeTabs);
	});

	// Enable arrow navigation between tabs in the tab list
	let tabFocus = 0;

	tabList.addEventListener("keydown", (e) => {
	// Move Down
	if (e.key === "ArrowDown" || e.key === "ArrowUp") {
		tabs[tabFocus].setAttribute("tabindex", -1);
		if (e.key === "ArrowDown") {
		tabFocus++;
		// If we're at the end, go to the start
		if (tabFocus >= tabs.length) {
			tabFocus = 0;
		}
		// Move Up
		} else if (e.key === "ArrowUp") {
		tabFocus--;
		// If we're at the start, move to the end
		if (tabFocus < 0) {
			tabFocus = tabs.length - 1;
		}
		}

		tabs[tabFocus].setAttribute("tabindex", 0);
		tabs[tabFocus].focus();
	}
	});

	document.querySelector('.toggle-pass-visibility').addEventListener('click', function(e) {
		e.preventDefault();
		const passInput = document.querySelector('#password');
		const classToReplace = passInput.getAttribute('type') == 'password' ? 'dashicons-hidden' : 'dashicons-visibility';
		const currentClass = passInput.getAttribute('type') == 'password' ? 'dashicons-visibility' : 'dashicons-hidden';
		passInput.getAttribute('type') == 'password' ? passInput.setAttribute('type', 'text') : passInput.setAttribute('type', 'password');
		e.currentTarget.firstChild.classList.replace(currentClass, classToReplace);

		if (input.getAttribute('type') == 'password') {
			input.setAttribute('type', 'text');
		} else {
			input.setAttribute('type', 'password');
		}
	});
});

function changeTabs(e) {
	e.preventDefault();
	const target = e.target;
	const parent = target.parentNode;
  	const grandparent = parent.parentNode;

  // Remove all current selected tabs
  parent
    .querySelectorAll('[aria-selected="true"]')
    .forEach((t) => t.setAttribute("aria-selected", false));

  // Set this tab as selected
  target.setAttribute("aria-selected", true);

  // Hide all tab panels
  grandparent
    .querySelectorAll('[role="tabpanel"]')
    .forEach((p) => p.setAttribute("hidden", true));

  // Show the selected panel
  grandparent.parentNode
    .querySelector(`#${target.getAttribute("aria-controls")}`)
    .removeAttribute("hidden");
}
</script>
