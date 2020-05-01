<?php
/**
 * Add the "membership level" field to the edit user/profile page,
 * along with other membership-related fields.
 */
function pmpro_membership_level_profile_fields($user)
{
	global $current_user;

	$server_tz = date_default_timezone_get();
	$wp_tz =  get_option( 'timezone_string' );

	//option "timezone_string" is empty if set to UTC+0
	if(empty($wp_tz))
		$wp_tz = 'UTC';

	date_default_timezone_set($wp_tz);

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	global $wpdb;	
	$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

	if(!$levels)
		return "";
?>
<h3><?php _e("Membership Level", 'paid-memberships-pro' ); ?></h3>
<table class="form-table">
    <?php
		$show_membership_level = true;
		$show_membership_level = apply_filters("pmpro_profile_show_membership_level", $show_membership_level, $user);
		if($show_membership_level)
		{
		?>
		<tr>
			<th><label for="membership_level"><?php _e("Current Level", 'paid-memberships-pro' ); ?></label></th>
			<td>
				<select name="membership_level">
					<option value="" <?php if(empty($user->membership_level->ID)) { ?>selected="selected"<?php } ?>>-- <?php _e("None", 'paid-memberships-pro' );?> --</option>
				<?php
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php selected($level->id, (isset($user->membership_level->ID) ? $user->membership_level->ID : 0 )); ?>><?php echo $level->name?></option>
				<?php
					}
				?>
				</select>
                <span id="current_level_cost">
                <?php
                $membership_values = pmpro_getMembershipLevelForUser($user->ID);

				//we tweak the initial payment here so the text here effectively shows the recurring amount
				if(!empty($membership_values))
				{
					$membership_values->original_initial_payment = $membership_values->initial_payment;
					$membership_values->initial_payment = $membership_values->billing_amount;
				}

				if(empty($membership_values) || pmpro_isLevelFree($membership_values))
                {
					if(!empty($membership_values->original_initial_payment) && $membership_values->original_initial_payment > 0)
						echo __('Paid', 'paid-memberships-pro' ) . pmpro_formatPrice($membership_values->original_initial_payment) . ".";
					else
						_e('Not paying.', 'paid-memberships-pro' );
				}
				else
                {
                    echo pmpro_getLevelCost($membership_values, true, true);
                }
                ?>
                </span>
                <p id="cancel_description" class="description hidden"><?php _e("This will not change the subscription at the gateway unless the 'Cancel' checkbox is selected below.", 'paid-memberships-pro' ); ?></p>
            </td>
		</tr>
		<?php
		}

		$show_expiration = true;
		$show_expiration = apply_filters("pmpro_profile_show_expiration", $show_expiration, $user);
		if($show_expiration)
		{

			//is there an end date?
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
			$end_date = (!empty($user->membership_level) && !empty($user->membership_level->enddate)); // Returned as UTC timestamp

			// Convert UTC to local time
            if ( $end_date ) {
	            $user->membership_level->enddate = strtotime( $wp_tz, $user->membership_level->enddate );
            }

			//some vars for the dates
			$current_day = date("j", current_time('timestamp'));
			if($end_date)
				$selected_expires_day = date("j", $user->membership_level->enddate);
			else
				$selected_expires_day = $current_day;

			$current_month = date("M", current_time('timestamp'));
			if($end_date)
				$selected_expires_month = date("m", $user->membership_level->enddate);
			else
				$selected_expires_month = date("m");

			$current_year = date("Y", current_time('timestamp'));
			if($end_date)
				$selected_expires_year = date("Y", $user->membership_level->enddate);
			else
				$selected_expires_year = (int)$current_year + 1;
		?>
		<tr>
			<th><label for="expiration"><?php _e("Expires", 'paid-memberships-pro' ); ?></label></th>
			<td>
				<select id="expires" name="expires">
					<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", 'paid-memberships-pro' );?></option>
					<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", 'paid-memberships-pro' );?></option>
				</select>
				<span id="expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
					on
					<select name="expires_month">
						<?php
							for($i = 1; $i < 13; $i++)
							{
							?>
							<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/15/" . $current_year, current_time("timestamp")))?></option>
							<?php
							}
						?>
					</select>
					<input name="expires_day" type="text" size="2" value="<?php echo $selected_expires_day?>" />
					<input name="expires_year" type="text" size="4" value="<?php echo $selected_expires_year?>" />
				</span>
				<script>
					jQuery('#expires').change(function() {
						if(jQuery(this).val() == 1)
							jQuery('#expires_date').show();
						else
							jQuery('#expires_date').hide();
					});
				</script>
			</td>
		</tr>
        <tr class="more_level_options">
            <th></th>
            <td>
                <label for="send_admin_change_email"><input value="1" id="send_admin_change_email" name="send_admin_change_email" type="checkbox"> <?php _e( 'Send the user an email about this change.', 'paid-memberships-pro' ); ?></label>
            </td>
        </tr>
        <tr class="more_level_options">
            <th></th>
            <td>
                <label for="cancel_subscription"><input value="1" id="cancel_subscription" name="cancel_subscription" type="checkbox"> <?php _e("Cancel this user's subscription at the gateway.", "paid-memberships-pro" ); ?></label>
            </td>
        </tr>
		<?php
		}
		?>

		<?php
			$tospage_id = pmpro_getOption( 'tospage' );
			$consent_log = pmpro_get_consent_log( $user->ID, true );

			if( !empty( $tospage_id ) || !empty( $consent_log ) ) {
			?>
	        <tr>
				<th><label for="tos_consent_history"><?php _e("TOS Consent History", 'paid-memberships-pro' ); ?></label></th>
				<td id="tos_consent_history">
					<?php
						if( !empty( $consent_log ) ) {
							if( count( $consent_log ) > 10 ) {
								$scrollable = 'pmpro_scrollable';
							} else {
								$scrollable = '';
							}
							echo '<ul class="pmpro_consent_log ' . $scrollable . '">';
							foreach( $consent_log as $entry ) {
								echo '<li>' . pmpro_consent_to_text( $entry ) . '</li>';
							}
							echo '</ul>';
						} else {
							echo __( 'N/A', 'paid-memberships-pro' );
						}
					?>
				</td>
			</tr>
			<?php
			}
		?>
</table>
    <script>
        jQuery(document).ready(function() {
            //vars for fields
			var $membership_level_select = jQuery("[name=membership_level]");
            var $expires_select = jQuery("[name=expires]");
			var $expires_month_select = jQuery("[name=expires_month]");
			var $expires_day_text = jQuery("[name=expires_day]");
			var $expires_year_text = jQuery("[name=expires_year]");

			//note old data to check for changes
			var old_level = $membership_level_select.val();
            var old_expires = $expires_select.val();
			var old_expires_month = $expires_month_select.val();
			var old_expires_day = $expires_day_text.val();
			var old_expires_year = $expires_year_text.val();

			var current_level_cost = jQuery("#current_level_cost").text();

            //hide by default
			jQuery(".more_level_options").hide();

			function pmpro_checkForLevelChangeInProfile()
			{
				//cancelling sub or not
				if($membership_level_select.val() == 0) {
                    jQuery("#cancel_subscription").attr('checked', true);
                    jQuery("#current_level_cost").text('<?php _e("Not paying.", "paid-memberships-pro" ); ?>');
                }
                else {
                    jQuery("#cancel_subscription").attr('checked', false);
                    jQuery("#current_level_cost").text(current_level_cost);
                }

				//did level or expiration change?
                if(
					$membership_level_select.val() != old_level ||
					$expires_select.val() != old_expires ||
					$expires_month_select.val() != old_expires_month ||
					$expires_day_text.val() != old_expires_day ||
					$expires_year_text.val() != old_expires_year
				)
                {
                    jQuery(".more_level_options").show();
                    jQuery("#cancel_description").show();
                }
                else
                {
                    jQuery(".more_level_options").hide();
                    jQuery("#cancel_description").hide();
                }
			}

			//run check when fields change
            $membership_level_select.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_select.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_month_select.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_day_text.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_year_text.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });

            jQuery("#cancel_subscription").change(function() {
                if(jQuery(this).attr('checked') == 'checked')
                {
                    jQuery("#cancel_description").hide();
                    jQuery("#current_level_cost").text('<?php _e("Not paying.", "paid-memberships-pro" ); ?>');
                }
                else
                {
                    jQuery("#current_level_cost").text(current_level_cost);
                    jQuery("#cancel_description").show();
                }
            });
        });
    </script>
<?php
	do_action("pmpro_after_membership_level_profile_fields", $user);

	date_default_timezone_set( $server_tz );
}

/*
	When applied, previous subscriptions won't be cancelled when changing membership levels.
	Use a function here instead of __return_false so we can easily turn add and remove it.
*/
function pmpro_cancel_previous_subscriptions_false()
{
	return false;
}

//save the fields on update
function pmpro_membership_level_profile_fields_update()
{
	//get the user id
	global $wpdb, $current_user, $user_ID;
	wp_get_current_user();

	if(!empty($_REQUEST['user_id']))
		$user_ID = $_REQUEST['user_id'];

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	//level change
    if(isset($_REQUEST['membership_level']))
    {
        //if the level is being set to 0 by the admin, it's a cancellation.
        $changed_or_cancelled = '';
        if($_REQUEST['membership_level'] === 0 ||$_REQUEST['membership_level'] === '0' || $_REQUEST['membership_level'] =='')
        {
            $changed_or_cancelled = 'admin_cancelled';
        }
        else
            $changed_or_cancelled = 'admin_changed';

		//if the cancel at gateway box is not checked, don't cancel
		if(empty($_REQUEST['cancel_subscription']))
			add_filter('pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false');

		//do the change
        if(pmpro_changeMembershipLevel(intval($_REQUEST['membership_level']), $user_ID, $changed_or_cancelled))
        {
            //it changed. send email
            $level_changed = true;
        }
		elseif(!empty($_REQUEST['cancel_subscription']))
		{
			//the level didn't change, but we were asked to cancel the subscription at the gateway, let's do that
			$order = new MemberOrder();
			$order->getLastMemberOrder($user_ID);

			if(!empty($order) && !empty($order->id))
				$r = $order->cancel();
		}

		//remove filter after ward
		if(empty($_REQUEST['cancel_subscription']))
			remove_filter('pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false');
    }

	//expiration change
	if(!empty($_REQUEST['expires']))
	{
		//update the expiration date
		$expiration_date = intval($_REQUEST['expires_year']) . "-" . str_pad(intval($_REQUEST['expires_month']), 2, "0", STR_PAD_LEFT) . "-" . str_pad(intval($_REQUEST['expires_day']), 2, "0", STR_PAD_LEFT);
		$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users SET enddate = '" . $expiration_date . "' WHERE status = 'active' AND membership_id = '" . intval($_REQUEST['membership_level']) . "' AND user_id = '" . $user_ID . "' LIMIT 1";
		if($wpdb->query($sqlQuery))
			$expiration_changed = true;
	}
	elseif(isset($_REQUEST['expires']))
	{
		//already blank? have to check for null or '0000-00-00 00:00:00' or '' here.
		$sqlQuery = "SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE (enddate IS NULL OR enddate = '' OR enddate = '0000-00-00 00:00:00') AND status = 'active' AND user_id = '" . $user_ID . "' LIMIT 1";
		$blank = $wpdb->get_var($sqlQuery);

		if(empty($blank))
		{
			//null out the expiration
			$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users SET enddate = NULL WHERE status = 'active' AND membership_id = '" . intval($_REQUEST['membership_level']) . "' AND user_id = '" . $user_ID . "' LIMIT 1";
			if($wpdb->query($sqlQuery))
				$expiration_changed = true;
		}
	}

	//emails if there was a change
	if(!empty($level_changed) || !empty($expiration_changed))
	{
		//email to admin
		$pmproemail = new PMProEmail();
		if(!empty($expiration_changed))
			$pmproemail->expiration_changed = true;
		$pmproemail->sendAdminChangeAdminEmail(get_userdata($user_ID));

		//send email
		if(!empty($_REQUEST['send_admin_change_email']))
		{
			//email to member
			$pmproemail = new PMProEmail();
			if(!empty($expiration_changed))
				$pmproemail->expiration_changed = true;
			$pmproemail->sendAdminChangeEmail(get_userdata($user_ID));
		}
	}
}
add_action( 'show_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'edit_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'personal_options_update', 'pmpro_membership_level_profile_fields_update' );
add_action( 'edit_user_profile_update', 'pmpro_membership_level_profile_fields_update' );

/**
 * Sanitizes the passed value.
 *
 * @param array|int|null|string|stdClass $value The value to sanitize
 *
 * @return array|int|string|object     Sanitized value
 */
function pmpro_sanitize( $value ) {

	if ( is_array( $value ) ) {

		foreach ( $value as $key => $val ) {
			$value[ $key ] = pmprorh_sanitize( $val );
		}
	}

	if ( is_object( $value ) ) {

		foreach ( $value as $key => $val ) {
			$value->{$key} = pmprorh_sanitize( $val );
		}
	}

	if ( ( ! is_array( $value ) ) && ctype_alpha( $value ) ||
	     ( ( ! is_array( $value ) ) && strtotime( $value ) ) ||
	     ( ( ! is_array( $value ) ) && is_string( $value ) ) ||
	     ( ( ! is_array( $value ) ) && is_numeric( $value) )
	) {

		$value = sanitize_text_field( $value );
	}

	return $value;
}

/**
 * Display a frontend Member Profile Edit form and allow user to edit specific fields.
 *
 * @since 2.3
 */
function pmpro_member_profile_edit_form() { 
	global $current_user;

	if ( ! is_user_logged_in() ) {
		echo '<div class="pmpro_message pmpro_alert"><a href="' . esc_url( pmpro_login_url() ) . '">' . esc_html__( 'Log in to edit your profile.', 'paid-memberships-pro' ) . '</a></div>';
		return;
	}

	do_action( 'pmpro_personal_options_update', $current_user->ID );

	// Saving profile updates.
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'update-profile' && $current_user->ID == $_POST['user_id'] && wp_verify_nonce( $_POST['update_user_nonce'], 'update-user_' . $current_user->ID ) ) {
		$update           = true;
		$user     		  = new stdClass;
		$user->ID         = $_POST[ 'user_id' ];
	} else {
		$update = false;
	}

	if ( $update ) {

		$errors = array();

		// Get all values from the $_POST, sanitize them, and build the $user object.
		if ( isset( $_POST['email'] ) ) {
			$user->user_email = sanitize_text_field( wp_unslash( $_POST['email'] ) );
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

		// Show error messages.
		if ( ! empty( $errors ) ) { ?>
			<div class="pmpro_message pmpro_error">
				<?php
					foreach ( $errors as $key => $value ) {
						echo '<p>' . $value . '</p>';
					}
				?>
			</div>
		<?php } else {
			// Save updated profile fields.
			wp_update_user( $user );
			?>
			<div class="pmpro_message pmpro_success">
				<?php _e( 'Your profile has been updated.', 'paid-memberships-pro' ); ?>
			</div>
		<?php }
	} else {
		// Doing this so fields are set to new values after being submitted.
		$user = $current_user;
	}
	?>
	<div class="pmpro_member_profile_edit_wrap">
		<form id="member-profile-edit" class="pmpro_form" action="" method="post">

			<?php wp_nonce_field( 'update-user_' . $current_user->ID, 'update_user_nonce' ); ?>

			<div class="pmpro_checkout_box-user">
				<div class="pmpro_member_profile_edit-fields">
					<div class="pmpro_member_profile_edit-field pmpro_member_profile_edit-field-first_name">
						<label for="first_name"><?php _e( 'First Name', 'paid-memberships-pro' ); ?></label>
						<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" class="input <?php echo pmpro_getClassForField( 'first_name' );?>" />
					</div> <!-- end pmpro_member_profile_edit-field-first_name -->

					<div class="pmpro_member_profile_edit-field pmpro_member_profile_edit-field-last_name">
						<label for="last_name"><?php _e( 'Last Name', 'paid-memberships-pro' ); ?></label>
						<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" class="input <?php echo pmpro_getClassForField( 'last_name' );?>" />
					</div> <!-- end pmpro_member_profile_edit-field-last_name -->

					<div class="pmpro_member_profile_edit-field pmpro_member_profile_edit-field-display_name">
						<label for="display_name"><?php _e( 'Display name publicly as', 'paid-memberships-pro' ); ?></label>
						<input type="text" name="display_name" id="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" class="input <?php echo pmpro_getClassForField( 'display_name' );?>" />
						<span class="pmpro_asterisk"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_member_profile_edit-field-display_name -->

					<div class="pmpro_member_profile_edit-field pmpro_member_profile_edit-field-email">
						<label for="email"><?php _e( 'Email', 'paid-memberships-pro' ); ?></label>
						
						<?php if ( current_user_can( 'manage_options' ) ) { ?>
						<input type="text" readonly="readonly" name="email" id="email" value="<?php echo esc_attr( $user->user_email ); ?>" class="input <?php echo pmpro_getClassForField( 'email' );?>" />
						<p class="lite"><?php esc_html_e( 'Site administrators must use the WordPress dashboard to update their email address.', 'paid-memberships-pro' ); ?></p>
						<?php } else { ?>
						<input type="email" name="email" id="email" value="<?php echo esc_attr( $user->user_email ); ?>" class="input <?php echo pmpro_getClassForField( 'email' );?>" />
						<span class="pmpro_asterisk"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
						<?php } ?>
					</div>					
				</div> <!-- end pmpro_member_profile_edit-fields -->
			</div> <!-- end pmpro_checkout_box-user -->

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
			<input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>" />
			<div class="pmpro_submit">
				<hr />
				<input type="submit" name="submit" class="pmpro_btn pmpro_btn-submit" value="<?php _e( 'Update Profile', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="pmpro_btn pmpro_btn-cancel" value="<?php _e( 'Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo pmpro_url( 'account'); ?>';" />
			</div>
		</form>
	</div> <!-- end pmpro_member_profile_edit_wrap -->
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
	if ( ! wp_verify_nonce( $_POST['change_password_user_nonce'], 'change-password-user_' . $current_user->ID ) ) {
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
	<h2><?php _e( 'Change Password', 'paid-memberships-pro' ); ?></h2>
	<?php if ( ! empty( $pmpro_msg ) ) { ?>
		<div class="pmpro_message <?php echo esc_attr( $pmpro_msgt ); ?>">
			<?php echo esc_html( $pmpro_msg ); ?>
		</div>
	<?php } ?>
	<div class="pmpro_change_password_wrap">
		<form id="change-password" class="pmpro_form" action="" method="post">

			<?php wp_nonce_field( 'change-password-user_' . $current_user->ID, 'change_password_user_nonce' ); ?>

			<div class="pmpro_checkout_box-password">
				<div class="pmpro_change_password-fields">
					<div class="pmpro_change_password-field pmpro_change_password-field-password_current">
						<label for="password_current"><?php _e( 'Current Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="password_current" id="password_current" value="" class="input <?php echo pmpro_getClassForField( 'password_current' );?>" />
						<span class="pmpro_asterisk"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_change_password-field-password_current -->
					<div class="pmpro_change_password-field pmpro_change_password-field-pass1">
						<label for="pass1"><?php _e( 'New Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="pass1" id="pass1" value="" class="input pass1 <?php echo pmpro_getClassForField( 'pass1' );?>" autocomplete="off" />
						<span class="pmpro_asterisk"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
						<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php _e( 'Strength Indicator', 'paid-memberships-pro' ); ?></div>
						<p class="lite"><?php echo wp_get_password_hint(); ?></p>
					</div> <!-- end pmpro_change_password-field-pass1 -->
					<div class="pmpro_change_password-field pmpro_change_password-field-pass2">
						<label for="pass2"><?php _e( 'Confirm New Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="pass2" id="pass2" value="" class="input <?php echo pmpro_getClassForField( 'pass2' );?>" autocomplete="off" />
						<span class="pmpro_asterisk"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_change_password-field-pass2 -->
				</div> <!-- end pmpro_change_password-fields -->
			</div> <!-- end pmpro_checkout_box-password -->

			<input type="hidden" name="action" value="change-password" />
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ); ?>" />
			<div class="pmpro_submit">
				<hr />
				<input type="submit" class="pmpro_btn pmpro_btn-submit" value="<?php esc_attr_e('Change Password', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="pmpro_btn pmpro_btn-cancel" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo esc_url( pmpro_url( 'account') ); ?>';" />
			</div>
		</form>
	</div> <!-- end pmpro_change_password_wrap -->
	<?php
}
