<?php

class PMPro_Member_Edit_Panel_User_Info extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$user = self::get_user();
		$this->slug = 'user_info';
		$this->title = __( 'User Info', 'paid-memberships-pro' );
		$this->title_link = empty( $user->ID ) ? '' : '<a href="' . esc_url( add_query_arg( array( 'user_id' => intval( $user->ID ) ), admin_url( 'user-edit.php' ) ) ) . '" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users">' . esc_html__( 'Edit User', 'paid-memberships-pro' ) . '</a>';
		$this->submit_text = empty( $user->ID ) ? __( 'Create User ') : __( 'Update User Info', 'paid-memberships-pro' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		// Populate values from form.
		$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
		$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
		$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
		$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
		$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );
		$user_notes = ! empty( $_POST['user_notes'] ) ? stripslashes( sanitize_textarea_field( $_POST['user_notes'] ) ) : '';

		// If we are edting a user, get the user information.
		$user = self::get_user();
		if ( ! empty( $user->ID ) ) {
			$user_login = $user->user_login;
			$user_email = $user->user_email;
			$first_name = $user->first_name;
			$last_name = $user->last_name;
			$role = $user->roles[0];
			$user_notes = $user->user_notes;
		} else {
			// We are creating a new user.
			// Enqueue core WordPress script for passwords: generate, visibility, and strength check.
			wp_enqueue_script( 'user-profile' );
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
					<input type="text" name="user_login" id="user_login" autocapitalize="none" autocorrect="off" autocomplete="off" required <?php if ( ! empty( $_REQUEST['user_id'] ) ) { ?>disabled="disabled"<?php } ?> value="<?php echo esc_attr( $user_login ) ?>">
					<?php if ( $user->ID ) { ?>
						<p class="description"><?php esc_html_e( 'Usernames cannot be changed.', 'paid-memberships-pro' ); ?></p>
					<?php } ?>
				</td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row"><label for="email"><?php esc_html_e( 'Email (required)', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="email" name="email" id="email" autocomplete="new-password" spellcheck="false" required value="<?php echo esc_attr( $user_email ) ?>"></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="first_name"><?php esc_html_e( 'First Name', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="first_name" id="first_name" autocomplete="off" value="<?php echo esc_attr( $first_name ); ?>"></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="last_name"><?php esc_html_e( 'Last Name', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="last_name" id="last_name" autocomplete="off" value="<?php echo esc_attr( $last_name ); ?>"></td>
			</tr>						
			<?php
			// Only show for new users.
			if ( empty( $user->ID ) ) {
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
					<th scope="row"><label for="send_password"><?php esc_html_e( 'Send User Notification', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input type="checkbox" name="send_password" id="send_password">
						<label for="send_password"><?php esc_html_e( 'Send the new user an email about their account.', 'paid-memberships-pro' ); ?></label>
						<p class="description"><?php esc_html_e( 'This will send the user an email with their username and a link to reset their password. For security reasons, this email does not include the unencrypted password.', 'paid-memberships-pro' ); ?></p>
					</td>
				</tr>
				<?php
			}
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="user_notes"><?php esc_html_e( 'Member Notes', 'paid-memberships-pro' ); ?></label></th>
				<td>
					<textarea name="user_notes" id="user_notes" rows="5" class="<?php echo pmpro_getClassForField( 'user_notes' ); ?>"><?php echo esc_textarea( $user_notes ); ?></textarea>
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
								<select name="role" id="role" class="<?php echo pmpro_getClassForField( 'role' ); ?>">
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
	}

	/**
	 * Save panel data and redirect if we are creating a new user.
	 */
	public function save() {
		global $wpdb, $pmpro_msgt, $pmpro_msg;

		$user = self::get_user();

		// Populate values from form.
		$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : $user->user_login;
		$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
		$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
		$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
		$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );
		$user_notes = ! empty( $_POST['user_notes'] ) ? stripslashes( sanitize_textarea_field( $_POST['user_notes'] ) ) : '';

		// Update the email address in signups, if present.
		// Alterred from wp-admin/user-edit.php.
		if ( is_multisite() ) {
			$user = get_userdata( $user->ID );

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
			pmpro_setMessage( __( 'Please fill out all required fields:', 'paid-memberships-pro' ) . ' ' . implode( ', ', $pmpro_error_fields ), 'pmpro_error' );
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

		if ( ! empty( $ouser->user_login ) && $ouser->id !== $user->ID ) {
			pmpro_setMessage( __( "That username is already taken. Please try another.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "username";
		}

		if ( ! empty( $oldemail ) && $oldem_user->id !== $user->ID ) {
			pmpro_setMessage( __( "That email address is already in use. Please log in, or use a different email address.", 'paid-memberships-pro' ), "pmpro_error" );
			$pmpro_error_fields[] = "bemail";
			$pmpro_error_fields[] = "bconfirmemail";
		}

		// okay so far?

		if ( $pmpro_msgt != 'pmpro_error' ) {
			// User data.
			$user_to_post = array( 
				'ID' => self::get_user()->ID,
				'user_login' => $user_login,
				'user_email' => $user_email,
				'first_name' => $first_name,
				'last_name' => $last_name,
			);

			// Set the role if the current user has permission.
			if ( ! IS_PROFILE_PAGE && current_user_can( 'promote_user', $user->ID ) ) {
				$user_to_post['role'] = $role;
			}

			// For new users, set the password.
			if ( ! $user->ID ) {
				$user_to_post['user_pass'] = empty( sanitize_text_field( $_REQUEST[ 'pass1' ] ) ) ? wp_generate_password() : sanitize_text_field( $_REQUEST[ 'pass1' ] );
				unset( $_REQUEST[ 'pass1' ] );
			}

			// Update or insert user.
			$updated_id = ! empty( $_REQUEST['user_id'] ) ? wp_update_user($user_to_post) : wp_insert_user($user_to_post);
		}

		if ( ! $updated_id ) {
			// Error during user update/insert.
			if ( ! empty( $_REQUEST['user_id'] ) ) {
				pmpro_setMessage( __( 'Error updating user.', 'paid-memberships-pro' ), 'pmpro_error' );
			} else {
				pmpro_setMessage( __( 'Error creating user.', 'paid-memberships-pro' ), 'pmpro_error' );
			}
		} elseif ( $pmpro_msgt === 'pmpro_error' ) {		
			// There was another error above.
			if ( ! empty( $_REQUEST['user_id'] ) ) {
				$pmpro_msg = esc_html__( 'There was an error updating this user: ', 'paid-memberships-pro' ) . $pmpro_msg;
			} else {
				$pmpro_msg = esc_html__( 'There was an error adding this user: ', 'paid-memberships-pro' ) . $pmpro_msg;
			}
		} else {
			// Update/insert all good.

			// Add other user meta
			update_user_meta( $updated_id, 'user_notes', $user_notes );

			// Notify users if needed.
			if ( ! $user->ID && ! empty( $_REQUEST['send_password'] ) ) {
				wp_new_user_notification( $updated_id, null, 'user' );
			}
			
			// Set message and redirect if this is a new user.		
			if ( empty( $_REQUEST['user_id'] ) ) {
				// User inserted.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&pmpro_member_edit_panel=memberships&user_id=' . $updated_id ) );
				exit;
			} else {
				// User updated.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&user_id=' . $updated_id ) );
			}
		}
	}
}