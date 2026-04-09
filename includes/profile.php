<?php
/**
 * Show an overview of active membership information linked to the single member dashboard.
 *
 * @since 3.0
 */
function pmpro_membership_levels_table_on_profile( $user ) {
	global $current_user;

	// If the user doesn't have the capability to edit members, don't show the table.
	if ( ! current_user_can( pmpro_get_edit_member_capability() ) ) {
		return false;
	}

	// Return early if we should not show membership information on the Edit User / Profile page.
	$show_membership_level = true;
	$show_membership_level = apply_filters( 'pmpro_profile_show_membership_level', $show_membership_level, $user );
	if ( ! $show_membership_level ) {
		return false;
	}

	// Get all membership levels for this user.
	$user_levels = pmpro_getMembershipLevelsForUser( $user->ID );
	?>
	<div id="pmpro-membership-levels-section">
		<h2><?php esc_html_e( 'Membership Levels', 'paid-memberships-pro' ); ?></h2>

		<?php if ( empty( $user_levels ) ) { ?>
			<p><?php esc_html_e( 'This user does not have any membership levels.', 'paid-memberships-pro' ); ?></p>
			<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $user->ID, 'pmpro_member_edit_panel' => 'memberships' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Add Membership', 'paid-memberships-pro' ); ?></a></p>
		<?php } else { ?>
			<p>
				<?php
					echo wp_kses_post( sprintf(
						// translators: %1$s is the link to the single member dashboard.
						__( 'This section shows an overview of active membership levels for this member. Use the %1$s to manage this member.', 'paid-memberships-pro' ),
						sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $user->ID, 'pmpro_member_edit_panel' => 'memberships' ), admin_url( 'admin.php' ) ) ),
							esc_html__( 'single member dashboard', 'paid-memberships-pro' )
						)
					) );
				?>
			</p>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Expiration', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach ( $user_levels as $user_level ) {
						$shown_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $user_level->ID );
						?>
						<tr>
							<td><?php echo esc_html( $shown_level->name ); ?></td>
							<td>
								<?php
									// Get the expiration date to show for this level.
									$enddate_to_show = $shown_level->enddate;
									if ( empty( $enddate_to_show ) ) {
										esc_html_e( 'Never', 'paid-memberships-pro' );
									} else {
										echo esc_html( sprintf(
											// translators: %1$s is the date and %2$s is the time.
											__( '%1$s at %2$s', 'paid-memberships-pro' ),
											esc_html( date_i18n( get_option( 'date_format'), $enddate_to_show ) ),
											esc_html( date_i18n( get_option( 'time_format'), $enddate_to_show ) )
										) );
									}
								?>
							</td>
							<td class="pmpro_levels_subscription_data">
								<?php
									$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $shown_level->id );
									if ( ! empty( $subscriptions ) ) {
										// If the user has more than 1 subscription, show a warning message.
										if ( count( $subscriptions ) > 1 ) {
											?>
											<div class="pmpro_message pmpro_error">
												<p>
													<?php
													echo wp_kses_post( sprintf(
														// translators: %1$d is the number of subscriptions and %2$s is the link to view subscriptions.
														_n(
															'This user has %1$d active subscription for this level. %2$s',
															'This user has %1$d active subscriptions for this level. %2$s',
															count( $subscriptions ),
															'paid-memberships-pro'
														),
														count( $subscriptions ),
														sprintf(
															'<a href="%1$s">%2$s</a>',
															esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $user->ID, 'pmpro_member_edit_panel' => 'subscriptions' ), admin_url( 'admin.php' ) ) ),
															esc_html__( 'View Subscriptions', 'paid-memberships-pro' )
														)
													) ); ?>
												</p>
											</div>
											<?php
										}
										$subscription = $subscriptions[0];
										echo esc_html( $subscription->get_cost_text() );
									} else {
										?>
										<p><?php esc_html_e( 'No subscription found.', 'paid-memberships-pro' ); ?></p>
										<?php
									}
								?>
							</td>
						</tr>
						<?php
					}
				?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $user->ID, 'pmpro_member_edit_panel' => 'memberships' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'View and Edit Member', 'paid-memberships-pro' ); ?></a></p>
		<?php
		}
	?>
	</div> <!-- end .pmpro-membership-levels-section -->
	<?php
}
add_action( 'show_user_profile', 'pmpro_membership_levels_table_on_profile' );
add_action( 'edit_user_profile', 'pmpro_membership_levels_table_on_profile' );


/**
 * Display a frontend Member Profile Edit form and allow user to edit specific fields.
 *
 * @since 2.3
 */
function pmpro_member_profile_edit_form() {
	global $current_user;

	if ( ! is_user_logged_in() ) {
		echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_alert', 'pmpro_alert' ) ) . '"><a href="' . esc_url( pmpro_login_url() ) . '">' . esc_html__( 'Log in to edit your profile.', 'paid-memberships-pro' ) . '</a></div>';
		return;
	}

	// Saving profile updates.
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'update-profile' && $current_user->ID == $_POST['user_id'] && wp_verify_nonce( sanitize_key( $_POST['update_user_nonce'] ), 'update-user_' . $current_user->ID ) ) {
		$update           = true;
		$user     		  = new stdClass;
		$user->ID         = intval( $_POST[ 'user_id' ] );
		do_action( 'pmpro_personal_options_update', $user->ID );
	} else {
		$update = false;
	}

	if ( $update ) {

		$errors = array();

		// Get all values from the $_POST, sanitize them, and build the $user object.
		if ( isset( $_POST['user_email'] ) ) {
			$user->user_email = sanitize_text_field( wp_unslash( $_POST['user_email'] ) );
		}
		if ( isset( $_POST['first_name'] ) ) {
			$user->first_name = sanitize_text_field( $_POST['first_name'] );
		}
		if ( isset( $_POST['last_name'] ) ) {
			$user->last_name = sanitize_text_field( $_POST['last_name'] );
		}
		if ( isset( $_POST['display_name'] ) ) {
			$user->display_name = sanitize_text_field( $_POST['display_name'] );
			$user->nickname = $user->display_name;
		}

		// Validate display name.
		if ( empty( $user->display_name ) ) {
			$errors[] = __( 'Please enter a display name.', 'paid-memberships-pro' );
		}

		// Don't allow admins to change their email address.
		if ( current_user_can( 'manage_options' ) ) {
			$user->user_email = $current_user->user_email;
		}

		// Validate email address.
		if ( empty( $user->user_email ) ) {
			$errors[] = __( 'Please enter an email address.', 'paid-memberships-pro' );
		} elseif ( ! is_email( $user->user_email ) ) {
			$errors[] = __( 'The email address isn&#8217;t correct.', 'paid-memberships-pro' );
		} else {
			$owner_id = email_exists( $user->user_email );
			if ( $owner_id && ( ! $update || ( $owner_id != $user->ID ) ) ) {
				$errors[] = __( 'This email is already registered, please choose another one.', 'paid-memberships-pro' );
			}
		}

		/**
		 * Fires before member profile update errors are returned.
		 *
		 * @param $errors WP_Error object (passed by reference).
		 * @param $update Whether this is a user update.
		 * @param $user   User object (passed by reference).
		 */
		do_action_ref_array( 'pmpro_user_profile_update_errors', array( &$errors, $update, &$user ) );

		// Show error messages.
		if ( ! empty( $errors ) ) { ?>
			<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_error', 'pmpro_error' ) ); ?>">
				<?php
					foreach ( $errors as $key => $value ) {
						echo '<p>' . esc_html( $value ) . '</p>';
					}
				?>
			</div>
		<?php } else {
			// Save updated profile fields.
			wp_update_user( $user );
			?>
			<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_success', 'pmpro_success' ) ); ?>">
				<?php esc_html_e( 'Your profile has been updated.', 'paid-memberships-pro' ); ?>
				<a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>"><?php esc_html_e( 'View Your Membership Account &rarr;', 'paid-memberships-pro' ); ?></a>
			</div>
		<?php }
	} else {
		// Doing this so fields are set to new values after being submitted.
		$user = $current_user;
	}
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<section id="pmpro_member_profile_edit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_member_profile_edit' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
				<form id="member-profile-edit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="" method="post"
					<?php
						/**
						 * Fires inside the member-profile-edit form tag in the pmpro_member_profile_edit_form function.
						 *
						 * @since 2.4.1
						 */
						do_action( 'pmpro_member_profile_edit_form_tag' );
					?>
				>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<?php wp_nonce_field( 'update-user_' . $current_user->ID, 'update_user_nonce' ); ?>

							<?php
								$user_fields = apply_filters( 'pmpro_member_profile_edit_user_object_fields',
									array(
										'first_name'	=> __( 'First Name', 'paid-memberships-pro' ),
										'last_name'		=> __( 'Last Name', 'paid-memberships-pro' ),
										'display_name'	=> __( 'Display name publicly as', 'paid-memberships-pro' ),
										'user_email'	=> __( 'Email', 'paid-memberships-pro' ),
									)
								);
								// Add autocomplete attributes for user fields.
								$user_field_autocomplete = array(
									'first_name'	=> 'given-name',
									'last_name'		=> 'family-name',
								);
							?>
							<fieldset id="pmpro_member_profile_edit-account-information" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_member_profile_edit-account-information' ) ); ?>">
								<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
									<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Account Information', 'paid-memberships-pro' ); ?></h2>
								</legend>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields pmpro_cols-2' ) ); ?>">
									<?php foreach ( $user_fields as $field_key => $label ) { ?>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-' . $field_key, 'pmpro_form_field-' . $field_key ) ); ?>">
											<label for="<?php echo esc_attr( $field_key ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php echo esc_html( $label ); ?></label>
											<?php if ( $field_key === 'user_email' ) {
												if ( current_user_can( 'manage_options' ) ) { ?>
													<input type="email" readonly="readonly" name="user_email" id="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'user_email' ) ); ?>" />
													<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php esc_html_e( 'Site administrators must use the WordPress dashboard to update their email address.', 'paid-memberships-pro' ); ?></p>
												<?php } else { ?>
													<input type="email" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( stripslashes( $user->{$field_key} ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', $field_key ) ); ?>" autocomplete="email" />
												<?php }
											} else { ?>
												<input type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( stripslashes( $user->{$field_key} ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', $field_key ) ); ?>"
												<?php echo isset( $user_field_autocomplete[ $field_key ] ) ? ' autocomplete="' . esc_attr( $user_field_autocomplete[ $field_key ] ) . '"' : ''; ?> />
											<?php } ?>
										</div>	<!-- end pmpro_form_field -->
									<?php } ?>
								</div> <!-- end pmpro_form_fields -->
							</fieldset> <!-- end pmpro_member_profile_edit-account-information -->

							<?php
								/**
								 * Fires after the default Your Member Profile fields.
								 *
								 * @since 2.3
								 *
								 * @param WP_User $current_user The current WP_User object.
								 */
								do_action( 'pmpro_show_user_profile', $current_user );
							?>
							<input type="hidden" name="action" value="update-profile" />
							<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ) ; ?>" />
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
								<button type="submit" name="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-update-profile', 'pmpro_btn-submit-update-profile' ) ); ?>" aria-label="<?php esc_attr_e( 'Submit the update profile form', 'paid-memberships-pro' ); ?>"><?php esc_html_e( 'Update Profile', 'paid-memberships-pro' ); ?></button>
								<button type="button" name="cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ) ); ?>" aria-label="<?php esc_attr_e( 'Cancel changes and return to the account page', 'paid-memberships-pro' ); ?>" onclick="location.href='<?php echo esc_url( pmpro_url( 'account') ); ?>';"><?php esc_html_e( 'Cancel', 'paid-memberships-pro' );?></button>
							</div>
						</div> <!-- end pmpro_card_content -->
					</div> <!-- end pmpro_card -->
				</form>
			</div> <!-- end pmpro_section_content -->
		</section> <!-- end pmpro_member_profile_edit -->
	</div> <!-- end pmpro -->
	<?php
}

/**
 * Process password updates.
 * Hooks into personal_options_update.
 * Doesn't need to hook into edit_user_profile_update since
 * our change password page is only for the current user.
 *
 * @since 2.3
 */
function pmpro_change_password_process() {
	global $current_user;

	// Make sure we're on the right page.
	if ( empty( $_POST['action'] ) || $_POST['action'] != 'change-password' ) {
		return;
	}

	// Only let users change their own password.
	if ( empty( $current_user ) || empty( $_POST['user_id'] ) || $current_user->ID != $_POST['user_id'] ) {
		return;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_POST['change_password_user_nonce'] ), 'change-password-user_' . $current_user->ID ) ) {
		return;
	}

	// Get all password values from the $_POST.
	if ( ! empty( $_POST['password_current'] ) ) {
		$password_current = sanitize_text_field( $_POST['password_current'] );
	} else {
		$password_current = '';
	}
	if ( ! empty( $_POST['pass1'] ) ) {
		$pass1 = sanitize_text_field( $_POST['pass1'] );
	} else {
		$pass1 = '';
	}
	if ( ! empty( $_POST['pass2'] ) ) {
		$pass2 = sanitize_text_field( $_POST['pass2'] );
	} else {
		$pass2 = '';
	}

	// Check that all password information is correct.
	$error = false;
	if ( isset( $password_current ) && ( empty( $pass1 ) || empty( $pass2 ) ) ) {
		$error = __( 'Please complete all fields.', 'paid-memberships-pro' );
	} elseif ( ! empty( $pass1 ) && empty( $password_current ) ) {
		$error = __( 'Please enter your current password.', 'paid-memberships-pro' );
	} elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
		$error = __( 'New passwords do not match.', 'paid-memberships-pro' );
	} elseif ( ! empty( $pass1 ) && ! wp_check_password( $password_current, $current_user->user_pass, $current_user->ID ) ) {
		$error = __( 'Your current password is incorrect.', 'paid-memberships-pro' );
	}

	// Change the password.
	if ( ! empty( $pass1 ) && empty( $error ) ) {
		wp_set_password( $pass1, $current_user->ID );

		//setting some cookies
		wp_set_current_user( $current_user->ID, $current_user->user_login );
		wp_set_auth_cookie( $current_user->ID, true, apply_filters( 'pmpro_checkout_signon_secure', force_ssl_admin() ) );

		pmpro_setMessage( __( 'Your password has been updated.', 'paid-memberships-pro' ), 'pmpro_success' );
	} else {
		pmpro_setMessage( $error, 'pmpro_error' );
	}
}
add_action( 'init', 'pmpro_change_password_process' );


/**
 * Display a frontend Change Password form and allow user to edit their password when logged in.
 *
 * @since 2.3
 */
function pmpro_change_password_form() {
	global $current_user, $pmpro_msg, $pmpro_msgt;
	?>
	<?php if ( ! empty( $pmpro_msg ) ) { ?>
		<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo esc_html( $pmpro_msg ); ?>
			<?php if ( $pmpro_msgt == 'pmpro_success' ) { ?>
				<a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>"><?php esc_html_e( 'View Your Membership Account &rarr;', 'paid-memberships-pro' ); ?></a>
			<?php } ?>
		</div>
	<?php } ?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<section id="pmpro_change_password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_change_password' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
				<form id="change-password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'change-password' ) ); ?>" action="" method="post">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
								<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
									<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Change Password', 'paid-memberships-pro' ); ?></h2>
								</legend>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
									<?php wp_nonce_field( 'change-password-user_' . $current_user->ID, 'change_password_user_nonce' ); ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password_current', 'pmpro_form_field-password_current' ) ); ?>">
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle' ) ); ?>">
											<label for="password_current" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
												<?php esc_html_e( 'Current Password', 'paid-memberships-pro' ); ?>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
											</label>
											<button type="button" class="pmpro_btn pmpro_btn-plain pmpro_btn-password-toggle hide-if-no-js" data-toggle="0" tabindex="2">
												<span class="pmpro_icon pmpro_icon-eye" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle-state' ) ); ?>"><?php esc_html_e( 'Show Password', 'paid-memberships-pro' ); ?></span>
											</button>
										</div> <!-- end pmpro_form_field-password-toggle -->
										<input type="password" name="password_current" id="password_current" value="" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required password_current', 'password_current' ) ); ?>" autocomplete="current-password" spellcheck="false" aria-required="true" tabindex="1" />
									</div> <!-- end pmpro_form_field-password_current -->
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-pass1', 'pmpro_form_field-pass1' ) ); ?>">
											<label for="pass1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
												<?php esc_html_e( 'New Password', 'paid-memberships-pro' ); ?>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
											</label>
											<input type="password" name="pass1" id="pass1" value="" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required pass1', 'pass1' ) ); ?>" autocomplete="new-password" aria-required="true" aria-describedby="pass-strength-result" tabindex="3" />
										</div> <!-- end pmpro_form_field-pass1 -->
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-pass2', 'pmpro_form_field-pass2' ) ); ?>">
											<label for="pass2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
												<?php esc_html_e( 'Confirm New Password', 'paid-memberships-pro' ); ?>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk'  )); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
											</label>
											<input type="password" name="pass2" id="pass2" value="" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required pass2', 'pass2' ) ); ?>" autocomplete="new-password" aria-required="true" spellcheck="false" tabindex="4" />
										</div> <!-- end pmpro_form_field-pass2 -->
									</div> <!-- end pmpro_cols-2 -->
									<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php echo esc_html( wp_get_password_hint() ); ?></p>
									<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php esc_html_e( 'Strength Indicator', 'paid-memberships-pro' ); ?></div>
								</div> <!-- end pmpro_form_fields -->
							</fieldset> <!-- end pmpro_form_fieldset -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
								<input type="hidden" name="action" value="change-password" />
								<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ); ?>" />
								<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit pmpro_btn-submit-change-password', 'pmpro_btn-submit-change-password' ) ); ?>" value="<?php esc_attr_e('Change Password', 'paid-memberships-pro' );?>" tabindex="5" />
								<input type="button" name="cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ) ); ?>" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo esc_url( pmpro_url( 'account') ); ?>';" tabindex="6" />
							</div> <!-- end pmpro_form_submit -->
						</div> <!-- end pmpro_card_content -->
					</div> <!-- end pmpro_card -->
				</form> <!-- end change-password -->
			</div> <!-- end pmpro_section_content -->
		</section> <!-- end pmpro_change_password -->
	</div> <!-- end pmpro -->
	<?php
}

/**
 * Add a link to the Edit Member page in PMPro inline with the Edit User screen's page title.
 */
function pmpro_add_edit_member_link_on_profile( $user ) {
	// Only show the link to users who can edit members.
	if ( ! current_user_can( pmpro_get_edit_member_capability() ) ) {
		return;
	}
	?>
	<script>
		jQuery(document).ready(function() {
			jQuery('h1.wp-heading-inline').append(' <a class="page-title-action" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $user->ID, 'pmpro_member_edit_panel' => 'memberships' ), admin_url( 'admin.php' ) ) ); ?>" target="_blank"><?php echo esc_html__( 'Edit Member', 'paid-memberships-pro' ); ?></a>');
		});
	</script>
	<?php
}
add_action( 'show_user_profile', 'pmpro_add_edit_member_link_on_profile' );
add_action( 'edit_user_profile', 'pmpro_add_edit_member_link_on_profile' );
