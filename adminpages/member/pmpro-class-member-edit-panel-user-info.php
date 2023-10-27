<?php

class PMPro_Member_Edit_Panel_User_Info extends PMPro_Member_Edit_Panel {
	public function get_title( $user_id ) {
		return __( 'User Info', 'paid-memberships-pro' );
	}

	public function display( $user_id ) {
		// Populate values from form.
		$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
		$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
		$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
		$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
		$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );

		// If we are edting a user, get the user information.
		if ( ! empty( $user_id ) ) {
			$check_user = get_userdata( $user_id );
			if ( ! empty( $check_user->ID ) ) {
				$user_login = $check_user->user_login;
				$user_email = $check_user->user_email;
				$first_name = $check_user->first_name;
				$last_name = $check_user->last_name;
				$role = $check_user->roles[0];
			}
		}

		// Show the form.
		?>
		<table class="form-table">
			<tr>
				<th><label for="user_login"><?php esc_html_e( 'Username (required)', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="user_login" id="user_login" autocapitalize="none" autocorrect="off" autocomplete="off" required <?php if ( ! empty( $_REQUEST['user_id'] ) ) { ?>readonly="true"<?php } ?> value="<?php echo esc_attr( $user_login ) ?>"></td>
			</tr>
			<tr>
				<th><label for="email"><?php esc_html_e( 'Email (required)', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="email" name="email" id="email" autocomplete="new-password" spellcheck="false" required value="<?php echo esc_attr( $user_email ) ?>"></td>
			</tr>
			<tr>
				<th><label for="first_name"><?php esc_html_e( 'First Name', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="first_name" id="first_name" autocomplete="off" value="<?php echo $first_name ?>"></td>
			</tr>
			<tr>
				<th><label for="last_name"><?php esc_html_e( 'Last Name', 'paid-memberships-pro' ); ?></label></th>
				<td><input type="text" name="last_name" id="last_name" autocomplete="off" value="<?php echo $last_name ?>"></td>
			</tr>						
			<?php
			// Only show for new users.
			if ( empty( $user_id ) ) {
				?>
				<tr>
					<th><label for="password"><?php esc_html_e( 'Password', 'paid-memberships-pro' ); ?></label></th>
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
			<?php if ( ! IS_PROFILE_PAGE && current_user_can( 'promote_user', $user_id ) ) { ?>
				<tr>
					<th><label for="role"><?php esc_html_e( 'Role', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<select name="role" id="role" class="<?php echo pmpro_getClassForField( 'role' ); ?>">
							<?php wp_dropdown_roles( $role ); ?>
						</select>
					</td>
				</tr>
			<?php } ?>
		</table>
		<?php
	}

	public function get_title_link( $user_id ) {
		if ( empty( $user_id ) ) {
			// Creating a new user, so we shouldn't link to profile.
			return '';
		}

		return '<a href="' .  esc_url( add_query_arg( array( 'user_id' => intval( $user_id ) ), admin_url( 'user-edit.php' ) ) ) . '" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users">' . esc_html__( 'Edit User', 'paid-memberships-pro' ) . '</a>';
	}

	public function get_submit_text( $user_id ) {
		return empty( $user_id ) ? __( 'Create User ') : __( 'Update User Info', 'paid-memberships-pro' );
	}

	// TODO: Password doesn't actually save.
	// TODO: Need permission check for changing roles
	// TODO: Review all of this.
	public function save( $user_id ) {
		global $pmpro_msgt, $pmpro_msg;

		// Populate values from form.
		$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
		$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
		$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
		$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
		$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );

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
			// Notify users if needed.
			if ( $send_password ) {
				wp_new_user_notification( $user_id, null, 'user' );
			}

			// clear vars
			$user_pass = '';
			
			// Set message and redirect if this is a new user.		
			if ( empty( $_REQUEST['user_id'] ) ) {
				// User inserted.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&pmpro_member_edit_panel=memberships&user_id=' . $user_id ) );
				exit;
			} else {
				// Users updated.
				pmpro_setMessage( esc_html__( 'User updated.', 'paid-memberships-pro' ), 'updated' );
			}			
		}
	}
}