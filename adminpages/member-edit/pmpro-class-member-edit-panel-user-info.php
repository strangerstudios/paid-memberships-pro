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

		// If we are edting a user, get the user information.
		$user = self::get_user();
		if ( ! empty( $user->ID ) ) {
			$user_login = $user->user_login;
			$user_email = $user->user_email;
			$first_name = $user->first_name;
			$last_name = $user->last_name;
			$role = $user->roles[0];
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
			if ( empty( $user->ID ) ) {
				?>
				<tr>
					<th><label for="password"><?php esc_html_e( 'Password', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input type="password" name="password" id="password" autocomplete="off" required value="">
						<button class="toggle-pass-visibility" aria-controls="password" aria-expanded="false"><span class="dashicons dashicons-visibility toggle-pass-visibility"></span></button>
					</td>
				</tr>
				<tr>
					<th><label for="send_password"><?php esc_html_e( 'Send User Notification', 'paid-memberships-pro' ); ?></label></th>
					<td><input type="checkbox" name="send_password" id="send_password">
					<label for="send_password"><?php esc_html_e( 'Send the new user an email about their account.', 'paid-memberships-pro' ); ?></label>
					</td>
				</tr>
				<?php
			}
			?>
			<?php if ( ! IS_PROFILE_PAGE && current_user_can( 'promote_user', $user->ID ) ) { ?>
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

	/**
	 * Save panel data and redirect if we are creating a new user.
	 */
	public function save() {
		global $wpdb, $pmpro_msgt, $pmpro_msg;

		$user = self::get_user();

		// Populate values from form.
		$user_login = ! empty( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';
		$user_email = ! empty( $_POST['email'] ) ? stripslashes( sanitize_email( $_POST['email'] ) ) : '';
		$first_name = ! empty( $_POST['first_name'] ) ? stripslashes( sanitize_text_field( $_POST['first_name'] ) ): '';
		$last_name = ! empty( $_POST['last_name'] ) ? stripslashes( sanitize_text_field( $_POST['last_name'] ) ) : '';	
		$role = ! empty( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : get_option( 'default_role' );

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

		if ( ! empty( $ouser->user_login ) && $ouser->id !== $user->ID ) {
			pmpro_setMessage( __( "That username is already taken. Please try another.", 'paid-memberships-pro' ), "notice-error" );
			$pmpro_error_fields[] = "username";
		}

		if ( ! empty( $oldemail ) && $oldem_user->id !== $user->ID ) {
			pmpro_setMessage( __( "That email address is already in use. Please log in, or use a different email address.", 'paid-memberships-pro' ), "notice-error" );
			$pmpro_error_fields[] = "bemail";
			$pmpro_error_fields[] = "bconfirmemail";
		}

		// okay so far?
		if ( $pmpro_msgt != 'notice-error' ) {
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
				$user_to_post['user_pass'] = empty( sanitize_text_field( $_REQUEST[ 'password' ] ) ) ? wp_generate_password() : sanitize_text_field( $_REQUEST[ 'password' ] );
				unset( $_REQUEST[ 'password' ] );
			}

			// Update or insert user.
			$updated_id = ! empty( $_REQUEST['user_id'] ) ? wp_update_user($user_to_post) : wp_insert_user($user_to_post);
		}

		if ( ! $updated_id ) {
			// Error during user update/insert.
			if ( ! empty( $_REQUEST['user_id'] ) ) {
				pmpro_setMessage( __( 'Error updating user.', 'paid-memberships-pro' ), 'notice-error' );
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
			if ( ! $user->ID && ! empty( $_REQUEST['send_password'] ) ) {
				wp_new_user_notification( $updated_id, null, 'user' );
			}
			
			// Set message and redirect if this is a new user.		
			if ( empty( $_REQUEST['user_id'] ) ) {
				// User inserted.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&pmpro_member_edit_panel=memberships&user_id=' . $updated_id ) );
				exit;
			} else {
				// Users updated.
				wp_redirect( admin_url( 'admin.php?page=pmpro-member&user_id=' . $updated_id ) );
			}			
		}
	}
}