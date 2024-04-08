<?php

class PMPro_Member_Edit_Panel_User_Info extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$user = self::get_user();
		$this->slug = 'user-info';
		$this->title = empty( $user->ID ) ? __( 'Add New User', 'paid-memberships-pro' ) : __( 'User Info', 'paid-memberships-pro' );
		$this->title_link = empty( $user->ID ) ? '' : '<a href="' . esc_url( add_query_arg( array( 'user_id' => intval( $user->ID ) ), admin_url( 'user-edit.php' ) ) ) . '" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users">' . esc_html__( 'Edit User', 'paid-memberships-pro' ) . '</a>';
		$this->submit_text = empty( $user->ID ) ? __( 'Create User ') : __( 'Update User Info', 'paid-memberships-pro' );

		// Show user updated or user created message if necessary.
		if ( isset( $_REQUEST['user_id'] ) && ! empty( $_REQUEST['user_id'] && ! empty( $_REQUEST['user_info_action'] ) ) ) {
			if ( 'updated' === $_REQUEST['user_info_action'] ) {
				pmpro_setMessage( __( 'User updated.', 'paid-memberships-pro' ), 'pmpro_success' );
			} elseif ( 'created' === $_REQUEST['user_info_action'] ) {
				pmpro_setMessage( __( 'New user created.', 'paid-memberships-pro' ), 'pmpro_success' );
			}
		}

		// If user cannot edit users, empty the submit text and title link.
		if ( ! current_user_can( 'edit_users' ) ) {
			$this->submit_text = '';
			$this->title_link = '';
		}
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		// Populate values from form.
		// TO DO: Is it strange that we populate these from the form then override with the $user object immediately after?
		$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
		$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
		$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
		$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
		$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );
		$user_notes = ! empty( $_POST['user_notes'] ) ? stripslashes( sanitize_textarea_field( $_POST['user_notes'] ) ) : '';

		// If we are edting a user, get the user information.
		$user = self::get_user();
		if ( ! empty( $user->ID ) ) {
			$user_login = empty( $user_login ) ? $user->user_login : $user_login;
			$user_email = $user->user_email;
			$first_name = $user->first_name;
			$last_name = $user->last_name;
			$role = current( $user->roles );
			$user_notes = $user->user_notes;
		} else {
			// We are creating a new user.
			// Enqueue core WordPress script for passwords: generate, visibility, and strength check.
			wp_enqueue_script( 'user-profile' );
		}

		// If the user doesn't have the edit_users capability, make the fields read-only.
		$disable_fields = ! current_user_can( 'edit_users' ) ? 'disabled' : '';

		// Show a message if the user doesn't have permission to edit this user.
		if ( ! empty( $disable_fields ) ) {
			if ( empty( $user->ID ) ) {
				?>
				<div class="pmpro_message pmpro_alert">
					<p><?php esc_html_e( 'You do not have permission to create new users.', 'paid-memberships-pro' ); ?></p>
				</div>
				<?php
			} else {
				?>
				<div class="pmpro_message pmpro_alert">
					<p><?php esc_html_e( 'You do not have permission to edit this user. User information is displayed below as read-only.', 'paid-memberships-pro' ); ?></p>
				</div>
				<?php
			}
		}

		// Show the form.
		?>
		<table class="form-table">
			<tr class="form-field form-required">
				<th scope="row">
					<label for="user_login">
					<?php if ( $user->ID ) { ?>
						<?php esc_html_e( 'Username', 'paid-memberships-pro' ); ?>
					<?php } else { ?>
						<?php esc_html_e( 'Username (required)', 'paid-memberships-pro' ); ?>
					<?php } ?>
					</label>
				</th>
				<td>
					<input type="text" name="user_login" id="user_login" autocapitalize="none" autocorrect="off" autocomplete="off" required <?php echo ( $user->ID || ! empty( $disable_fields ) ) ? 'disabled' : ''; ?> value="<?php echo esc_attr( $user_login ) ?>">
					<?php if ( $user->ID ) { ?>
						<p class="description"><?php esc_html_e( 'Usernames cannot be changed.', 'paid-memberships-pro' ); ?></p>
					<?php } ?>
				</td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row"><label for="email"><?php esc_html_e( 'Email (required)', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="email" name="email" id="email" autocomplete="new-password" spellcheck="false" required value="<?php echo esc_attr( $user_email ); ?>" <?php echo esc_attr( $disable_fields ); ?>></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="first_name"><?php esc_html_e( 'First Name', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="first_name" id="first_name" autocomplete="off" value="<?php echo esc_attr( $first_name ); ?>" <?php echo esc_attr( $disable_fields ); ?>></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="last_name"><?php esc_html_e( 'Last Name', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="last_name" id="last_name" autocomplete="off" value="<?php echo esc_attr( $last_name ); ?>" <?php echo esc_attr( $disable_fields ); ?>></td>
			</tr>						
			<?php
			// Only show for new users.
			if ( empty( $user->ID ) && current_user_can( 'edit_users' ) ) {
				?>
				<tr class="form-field form-required user-pass1-wrap">
					<th scope="row">
						<label for="pass1">
							<?php esc_html_e( 'Password', 'paid-memberships-pro' ); ?>
							<span class="description hide-if-js"><?php esc_html_e( '(required)', 'paid-memberships-pro' ); ?></span>
						</label>
					</th>
					<td>
						<input type="hidden" value=" " />
						<button type="button" class="button wp-generate-pw hide-if-no-js"><?php esc_html_e( 'Generate password', 'paid-memberships-pro' ); ?></button>
						<div class="wp-pwd">
							<?php $initial_password = wp_generate_password( 24 ); ?>
							<div class="password-input-wrapper">
								<input type="password" name="pass1" id="pass1" class="regular-text" autocomplete="new-password" spellcheck="false" data-reveal="1" data-pw="<?php echo esc_attr( $initial_password ); ?>" aria-describedby="pass-strength-result" />
								<div style="display:none" id="pass-strength-result" aria-live="polite"></div>
							</div>
							<button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Hide password', 'paid-memberships-pro' ); ?>">
								<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
								<span class="text"><?php esc_html_e( 'Hide', 'paid-memberships-pro' ); ?></span>
							</button>
						</div>
					</td>
				</tr>
				<tr class="pw-weak">
					<th><?php esc_html_e( 'Confirm Password', 'paid-memberships-pro' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="pw_weak" class="pw-checkbox" />
							<?php esc_html_e( 'Confirm use of weak password', 'paid-memberships-pro' ); ?>
						</label>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="send_user_notification"><?php esc_html_e( 'Send User Notification', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input type="checkbox" name="send_user_notification" id="send_user_notification">
						<label for="send_user_notification"><?php esc_html_e( 'Send the new user an email about their account.', 'paid-memberships-pro' ); ?></label>
						<p class="description"><?php esc_html_e( 'This will send the user an email with their username and a link to reset their password. For security reasons, this email does not include the unencrypted password.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<?php
			}
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="user_notes"><?php esc_html_e( 'Member Notes', 'paid-memberships-pro' ); ?></label></th>
				<td>
					<textarea name="user_notes" id="user_notes" rows="5" class="<?php echo esc_attr( pmpro_getClassForField( 'user_notes' ) ); ?>" <?php echo esc_attr( $disable_fields ); ?>><?php echo esc_textarea( $user_notes ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Member notes are private and only visible to other users with membership management capabilities.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
			<?php if ( ! IS_PROFILE_PAGE && current_user_can( 'promote_user', $user->ID ) ) {
				?>
				<tr>
					<?php
						// Set the role to subscriber if the default role is subscriber.
						if ( empty( $user->ID ) && get_option( 'default_role' ) == 'subscriber' ) {
							?>
							<td colspan="2">
								<input type="hidden" name="role" id="role" value="subscriber">
							</td>
							<?php
						} else {
							?>
							<th scope="row"><label for="role"><?php esc_html_e( 'Role', 'paid-memberships-pro' ); ?></label></th>
							<td>
								<select name="role" id="role" class="<?php echo esc_attr( pmpro_getClassForField( 'role' ) ); ?>" <?php echo esc_attr( $disable_fields ); ?>>
									<?php wp_dropdown_roles( $role ); ?>
								</select>
							</td>
						<?php
						}
					?>
				</tr>
			<?php } ?>
		</table>
		<?php
		do_action( 'pmpro_after_membership_level_profile_fields', self::get_user() );
	}

	/**
	 * Save panel data and redirect if we are creating a new user.
	 */
	public function save() {
		// If the current user can't edit users, bail.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		// Get all roles for the site.
		$wp_roles = wp_roles();

		// Get the user we are editing or set up a new blank user.
		$user = self::get_user();
		$update = $user->ID ? true : false;

		if ( ! $update && isset( $_POST['user_login'] ) ) {
			$user->user_login = sanitize_user( wp_unslash( $_POST['user_login'] ), true );
		}

		$pass1 = '';
		if ( isset( $_POST['pass1'] ) ) {
			$pass1 = trim( $_POST['pass1'] );
		}

		if ( isset( $_POST['role'] ) && current_user_can( 'promote_users' ) && ( ! $user->ID || current_user_can( 'promote_user', $user->ID ) ) ) {
			$new_role = sanitize_text_field( $_POST['role'] );

			// If the new role isn't editable by the logged-in user die with error.
			$editable_roles = get_editable_roles();
			if ( ! empty( $new_role ) && empty( $editable_roles[ $new_role ] ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to give users that role.', 'paid-memberships-pro' ), 403 );
			}

			$potential_role = isset( $wp_roles->role_objects[ $new_role ] ) ? $wp_roles->role_objects[ $new_role ] : false;

			/*
			 * Don't let anyone with 'promote_users' edit their own role to something without it.
			 * Multisite super admins can freely edit their roles, they possess all caps.
			 */
			if (
				( is_multisite() && current_user_can( 'manage_network_users' ) ) ||
				get_current_user_id() !== $user->ID ||
				( $potential_role && $potential_role->has_cap( 'promote_users' ) )
			) {
				$user->role = $new_role;
			}
		}

		if ( isset( $_POST['email'] ) ) {
			$user->user_email = sanitize_text_field( wp_unslash( $_POST['email'] ) );
		}
		if ( isset( $_POST['first_name'] ) ) {
			$user->first_name = sanitize_text_field( $_POST['first_name'] );
		}
		if ( isset( $_POST['last_name'] ) ) {
			$user->last_name = sanitize_text_field( $_POST['last_name'] );
		}

		// Build the array of potential error messages and error fields.
		$errors = array();

		/* checking that username has been typed */
		if ( '' === $user->user_login ) {
			$errors['user_login'] = __( 'Please enter a username.', 'paid-memberships-pro' );
		}

		// Check for blank password when adding a user.
		if ( ! $update && empty( $pass1 ) ) {
			$errors['pass1'] = __( 'Please enter a password.', 'paid-memberships-pro' );
		}

		// Check for "\" in password.
		if ( str_contains( wp_unslash( $pass1 ), '\\' ) ) {
			$errors['pass1'] = __( 'Passwords may not contain the character "\\".', 'paid-memberships-pro' );
		}

		if ( ! empty( $pass1 ) ) {
			$user->user_pass = $pass1;
		}

		if ( ! $update && isset( $_POST['user_login'] ) && ! validate_username( $_POST['user_login'] ) ) {
			$errors['user_login'] = __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'paid-memberships-pro' );
		}

		if ( ! $update && username_exists( $user->user_login ) ) {
			$errors['user_found'] = sprintf(
				__('A user with username %1$s already exists. <a href="%2$s">Click here to edit this member</a>.', 'paid-memberships-pro'),
				esc_html( $user->user_login ),
				esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => username_exists( $user->user_login ) ), admin_url( 'admin.php' ) ) )
			);
		}

		/** This filter is documented in wp-includes/user.php */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );
	
		if ( in_array( strtolower( $user->user_login ), array_map( 'strtolower', $illegal_logins ), true ) ) {
			$errors['user_login'] = __( 'Sorry, that username is not allowed.', 'paid-memberships-pro' );
		}

		// Checking email address.
		if ( empty( $user->user_email ) ) {
			$errors['user_email'] = __( 'Please enter an email address.', 'paid-memberships-pro' );
		} elseif ( ! is_email( $user->user_email ) ) {
			$errors['user_email'] = __( 'The email address is not correct.', 'paid-memberships-pro' );
		} else {
			$owner_id = email_exists( $user->user_email );
			if ( $owner_id && ( ! $update || ( $owner_id !== $user->ID ) ) ) {
				$errors['user_found'] = sprintf(
					__('A user with email address %1$s already exists. <a href="%2$s">Click here to edit this member</a>.', 'paid-memberships-pro'),
					esc_html( $user->user_email ),
					esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => email_exists( $user->user_email ) ), admin_url( 'admin.php' ) ) )
				);
			}
		}

		if ( ! empty( $errors ) ) {
			// Set error messages.
			$error_messages = '<p>' . __( 'There were errors in the form.', 'paid-memberships-pro' ) . '</p>';
			$error_messages .= '<ul>';
			foreach ( $errors as $error ) {
				$error_messages .= '<li>' . $error . '</li>';
			}
			$error_messages .= '</ul>';

			// Set the message.
			pmpro_setMessage( $error_messages, 'pmpro_error' );
		} else {

			if ( $update ) {
				// Update the user.
				$user_id = wp_update_user( $user );
			} else {
				// Insert the user.
				$user_id = wp_insert_user( $user );

				// Notify users if needed.
				if ( isset( $_POST['send_user_notification'] ) ) {
					wp_new_user_notification( $user_id, null, 'user' );
				}
			}

			// Add other user meta
			$user_notes = ! empty( $_POST['user_notes'] ) ? sanitize_textarea_field( $_POST['user_notes'] ) : '';
			update_user_meta( $user_id, 'user_notes', $user_notes );

			// Set message and redirect if this is a new user.		
			if ( $update ) {
				// User updated.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&user_info_action=updated&user_id=' . $user_id ) );
				exit;
			} else {
				// User inserted.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&user_info_action=created&pmpro_member_edit_panel=memberships&user_id=' . $user_id ) );
			}
		}
	}
}