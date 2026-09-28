<?php

/**
 * Calculate the profile start date to be sent to the payment gateway.
 *
 * @since 2.9
 *
 * @param MemberOrder $order       The order to calculate the start date for.
 * @param string      $date_format The format to use when formatting the profile start date.
 * @param bool        $filter      Whether to filter the profile start date.
 *
 * @return string The profile start date in UTC time and the desired $date_format.
 */
function pmpro_calculate_profile_start_date( $order, $date_format, $filter = true ) {
	// Get the checkout level.
	$level = $order->getMembershipLevelAtCheckout();

	// If the level already has a profile start date set, use it. Otherwise, calculate it based on the cycle number and period.
	if ( ! empty( $level->profile_start_date ) ) {
		// Use the profile start date that is already set.
		$profile_start_date = date_i18n( 'Y-m-d H:i:s', strtotime( $level->profile_start_date ) );
	} else {
		// Calculate the profile start date based on the cycle number and period.
		$profile_start_date = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $level->cycle_number . ' ' . $level->cycle_period ) );
	}

	// Filter the profile start date if needed.
	if ( $filter ) {
		/**
		 * Filter the profile start date.
		 *
		 * Note: We are passing $profile_start_date to strtotime before returning so
		 * YYYY-MM-DD HH:MM:SS is not 100% necessary, but we should transition add ons and custom code
		 * to use that format in case we update this code in the future.
		 *
		 * @since 1.4
		 * @deprecated 3.4 Set the 'profile_start_date' property on the checkout level object instead.
		 *
		 * @param string $profile_start_date The profile start date in UTC YYYY-MM-DD HH:MM:SS format.
		 * @param MemberOrder $order         The order that the profile start date is being calculated for.
		 *
		 * @return string The profile start date in UTC YYYY-MM-DD HH:MM:SS format.
		 */
		$profile_start_date = apply_filters_deprecated( 'pmpro_profile_start_date', array( $profile_start_date, $order ), '3.4', 'pmpro_checkout_level' );
	}

	// Convert $profile_start_date to correct format.
	return date_i18n( $date_format, strtotime( $profile_start_date ) );
}

/**
 * Save checkout data in order meta before sending user offsite to pay.
 *
 * @since 2.12.3
 *
 * @param MemberOrder $order The order to save the checkout fields for.
 */
 function pmpro_save_checkout_data_to_order( $order ) {
	global $pmpro_level, $discount_code;

	// Save some checkout information in the order so that we can access it when the payment is complete.
	// Save the request variables.
	$request_vars = $_REQUEST;

	// Unset sensitive request variables.
	$sensitive_vars = pmpro_get_sensitive_checkout_request_vars();
	foreach ( $sensitive_vars as $key ) {
		if ( isset( $request_vars[ $key ] ) ) {
			unset( $request_vars[ $key ] );
		}
	}
	update_pmpro_membership_order_meta( $order->id, 'checkout_request_vars', $request_vars );

	// Save the checkout level.
	$pmpro_level_arr = (array) $pmpro_level;
	update_pmpro_membership_order_meta( $order->id, 'checkout_level', $pmpro_level_arr );

	// Save the discount code.
	// @TODO: Remove this in v4.0. Discount codes should be set on the level object.
	update_pmpro_membership_order_meta( $order->id, 'checkout_discount_code', $discount_code );

	// Save any files that were uploaded.
	if ( ! empty( $_FILES ) ) {
		// Build an array of files to save.
		$files = array();
		foreach ( $_FILES as $arr_key => $file ) {
			// If this file should not be saved, skip it.
			$upload_check = pmpro_check_upload( $arr_key );
			if ( is_wp_error( $upload_check ) ) {
				continue;
			}

			// Make sure that the file was uploaded during this page load.
			if ( ! is_uploaded_file( sanitize_text_field( $file['tmp_name'] ) ) ) {						
				continue;
			}

			// Check for a register helper directory in wp-content and create it if needed.
			$upload_dir = wp_upload_dir();
			$pmprorh_dir = $upload_dir['basedir'] . "/pmpro-register-helper/tmp/";
			if( ! is_dir( $pmprorh_dir ) ) {
				wp_mkdir_p( $pmprorh_dir );
			}

			// Move file.
			$new_filename = $pmprorh_dir . basename( $file['tmp_name'] ) . '.' . $upload_check['filetype']['ext'];
			move_uploaded_file($file['tmp_name'], $new_filename);

			// Update location of file.
			$file['tmp_name'] = $new_filename;

			// Add the file to the array.
			$files[ $arr_key ] = $file;
		}
		update_pmpro_membership_order_meta( $order->id, 'checkout_files', $files );
	}
}

/**
 * Get the list of sensitive request variables that should not be saved in the database.
 *
 * @since 2.12.7
 *
 * @return array The list of sensitive request variables.
 */
function pmpro_get_sensitive_checkout_request_vars() {
	// These are the request variables that we do not want to save in the database.
	$sensitive_request_vars = array(
		'password',
		'password2',
		'password2_copy',
		'AccountNumber',
		'CVV',
		'ExpirationMonth',
		'ExpirationYear',
		'add_sub_accounts_password', // Creating users at checkout with Sponsored Members.
		'pmpro_checkout_nonce', // The checkout nonce.
		'checkjavascript', // Used to check if JavaScript is enabled.
		'submit-checkout', // Used to check if the checkout form was submitted.
		'submit-checkout_x', // Used to check if the checkout form was submitted.
	);

	/**
	 * Filter the list of sensitive request variables that should not be saved in the database.
	 *
	 * @since 2.12.7
	 *
	 * @param array $sensitive_request_vars The list of sensitive request variables.
	 */
	return apply_filters( 'pmpro_sensitive_checkout_request_vars', $sensitive_request_vars );
}

/**
 * Pull checkout data from order meta after returning from offsite payment.
 *
 * @since 2.12.3
 *
 * @param MemberOrder $order The order to pull the checkout fields for.
 */
function pmpro_pull_checkout_data_from_order( $order ) {
	global $pmpro_level, $discount_code;

	// Keep track of any checkout data that we expected to find but could not.
	$missing_data = array();

	// We need to pull the checkout level and fields data from the order.
	$checkout_level_arr = get_pmpro_membership_order_meta( $order->id, 'checkout_level', true );
	if ( ! is_array( $checkout_level_arr ) ) {
		$missing_data[] = 'checkout_level';
		$checkout_level_arr = array();
	}
	$pmpro_level = (object) $checkout_level_arr;
	$order->membership_level = $pmpro_level;

	// Set $discount_code_id.
	// @TODO: Remove this in v4.0. Discount codes should be set on the level object.
	$discount_code = get_pmpro_membership_order_meta( $order->id, 'checkout_discount_code', true );

	// Set $_REQUEST.
	$checkout_request_vars = get_pmpro_membership_order_meta( $order->id, 'checkout_request_vars', true );
	if ( is_array( $checkout_request_vars ) ) {
		$_REQUEST = array_merge( $_REQUEST, $checkout_request_vars );
	} else {
		$missing_data[] = 'checkout_request_vars';
	}

	// Set $_FILES.
	$checkout_files = get_pmpro_membership_order_meta( $order->id, 'checkout_files', true );
	if ( is_array( $checkout_files ) ) {
		$_FILES = array_merge( $_FILES, $checkout_files );
	}
	// Note: We do not track missing 'checkout_files' meta since it is only saved when files were uploaded.

	// If any of the checkout data that we expected is missing, note it on the order. Membership will still be
	// assigned, but data submitted during checkout may not be saved, so admins should know to follow up.
	if ( ! empty( $missing_data ) && ! empty( $order->id ) ) {
		$missing_data_note = sprintf(
			// translators: %s is a comma-separated list of order meta keys.
			__( 'Warning: Could not load the checkout data for this order (%s). Information submitted during checkout may not have been saved.', 'paid-memberships-pro' ),
			implode( ', ', $missing_data )
		);

		// Only add the note if we haven't already added it. This function may run more than once for
		// an order, such as when an admin rechecks the payment status for a token order.
		if ( false === strpos( (string) $order->notes, $missing_data_note ) ) {
			$order->add_order_note( $missing_data_note );
			$order->saveOrder();
		}
	}
}

/**
 * Complete a checkout.
 *
 * @since 3.1
 *
 * @param MemberOrder $order The order to complete the checkout for.
 * @return bool True if the checkout was completed successfully, false otherwise.
 */
 function pmpro_complete_checkout( $order ) {
	global $wpdb, $pmpro_level, $discount_code, $discount_code_id;

	// Run the pmpro_checkout_before_change_membership_level action in case add ons need to set up.
	do_action( 'pmpro_checkout_before_change_membership_level', $order->user_id, $order );

	/**
	 * Filter the start date for the membership/subscription.
	 *
	 * @since 1.8.9
	 *
	 * @param string $startdate , datetime formatsted for MySQL (NOW() or YYYY-MM-DD)
	 * @param int $user_id , ID of the user checking out
	 * @param object $pmpro_level , object of level being checked out for
	 */
	$startdate = apply_filters( "pmpro_checkout_start_date", "'" . current_time( 'mysql' ) . "'", $order->user_id, $pmpro_level );

	//fix expiration date
	if ( ! empty( $pmpro_level->expiration_number ) ) {
		if( $pmpro_level->expiration_period == 'Hour' ){
			$enddate =  date( "Y-m-d H:i:s", strtotime( "+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time( "timestamp" ) ) );
		} else {
			$enddate =  date( "Y-m-d 23:59:59", strtotime( "+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time( "timestamp" ) ) );
		}
	} else {
		$enddate = "NULL";
	}

	/**
	 * Filter the end date for the membership/subscription.
	 *
	 * @since 1.8.9
	 *
	 * @param string $enddate , datetime formatsted for MySQL (YYYY-MM-DD)
	 * @param int $user_id , ID of the user checking out
	 * @param object $pmpro_level , object of level being checked out for
	 * @param string $startdate , startdate calculated above
	 */
	$enddate = apply_filters( "pmpro_checkout_end_date", $enddate, $order->user_id, $pmpro_level, $startdate );

	// If we have a discount code but not the ID, get the ID.
	if ( ! empty( $pmpro_level->discount_code ) ) {
		$discount_code = $pmpro_level->discount_code;
		$discount_code_id = empty( $pmpro_level->code_id ) ? $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $discount_code ) . "' LIMIT 1" ) : $pmpro_level->code_id;
	} elseif ( ! empty( $discount_code ) && empty( $discount_code_id ) ) {
		// Throw a doing it wrong warning. If a discount code is being used, it should be set on the level.
		// @TODO: Remove this in v4.0 along with references to the discount code globals. Discount codes should be set on the level object.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Discount codes should be set on the $pmpro_level object.', 'paid-memberships-pro' ), '3.4' );
		$discount_code_id = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $discount_code ) . "' LIMIT 1" );
	}

	//custom level to change user to
	$custom_level = array(
		'user_id'         => $order->user_id,
		'membership_id'   => $pmpro_level->id,
		'code_id'         => $discount_code_id,
		'initial_payment' => $pmpro_level->initial_payment,
		'billing_amount'  => $pmpro_level->billing_amount,
		'cycle_number'    => $pmpro_level->cycle_number,
		'cycle_period'    => $pmpro_level->cycle_period,
		'billing_limit'   => $pmpro_level->billing_limit,
		'trial_amount'    => $pmpro_level->trial_amount,
		'trial_limit'     => $pmpro_level->trial_limit,
		'startdate'       => $startdate,
		'enddate'         => $enddate
	);

	//change level and continue "checkout"
	if ( pmpro_changeMembershipLevel( $custom_level, $order->user_id, 'changed' ) !== false ) {
		// Mark the order as successful.
		$order->status = "success";
		if ( ! empty( $discount_code_id ) ) {
			/**
			 * Ideally, we would set the discount code ID on the order when it is initially created, but
			 * this would conflict with Add Ons and custom code (specifically Sponsored Members) that
			 * expect discount codes only to be set after successful checkouts.
			 *
			 * @TODO: In the next breaking release, we should set the discount code ID on the order when it is initially created.
			 */
			$order->discount_code_id = $discount_code_id;
		}
		$order->saveOrder();

		//add discount code use
		if ( ! empty( $discount_code_id ) ) {
			do_action_deprecated( 'pmpro_discount_code_used', array( $discount_code_id, $order->user_id, $order->id ), '3.3.2', 'pmpro_added_order' );
		}

		//save first and last name fields
		if ( ! empty( $_POST['first_name'] ) ) {
			$old_firstname = get_user_meta( $order->user_id, "first_name", true );
			if ( empty( $old_firstname ) ) {
				update_user_meta( $order->user_id, "first_name", stripslashes( sanitize_text_field( $_POST['first_name'] ) ) );
			}
		}
		if ( ! empty( $_POST['last_name'] ) ) {
			$old_lastname = get_user_meta( $order->user_id, "last_name", true );
			if ( empty( $old_lastname ) ) {
				update_user_meta( $order->user_id, "last_name", stripslashes( sanitize_text_field( $_POST['last_name'] ) ) );
			}
		}

		if ( $pmpro_level->expiration_period == 'Hour' ){
			update_user_meta( $order->user_id, 'pmpro_disable_notifications', true );
		}

		//hook
		do_action( "pmpro_after_checkout", $order->user_id, $order );

		// Check if we should send emails.
		if ( apply_filters( 'pmpro_send_checkout_emails', true, $order ) ) {
			// Set up some values for the emails.
			$user                   = get_userdata( $order->user_id );
			$user->membership_level = $pmpro_level;        // Make sure that they have the right level info.

			// Send email to member.
			$pmproemail = new PMProEmail();
			$pmproemail->sendCheckoutEmail( $user, $order );

			// Send email to admin.
			$pmproemail = new PMProEmail();
			$pmproemail->sendCheckoutAdminEmail( $user, $order );
		}

		return true;
	} else {
		return false;
	}
}

/**
 * Legacy function.
 *
 * @since 2.12.3
 *
 * @param MemberOrder $order The order to complete the checkout for.
 * @return bool True if the checkout was completed successfully, false otherwise.
 */
function pmpro_complete_async_checkout( $order ) {
	return pmpro_complete_checkout( $order );
}

/**
 * AJAX method to get the checkout nonce.
 * Important for correcting the nonce value at checkout if the user is logged in during the same page load.
 *
 * @since 3.0.3
 */
function pmpro_get_checkout_nonce() {
	// Output the checkout nonce.
	echo esc_html( wp_create_nonce( 'pmpro_checkout_nonce' ) );

	// End the AJAX request.
	exit;
}
add_action( 'wp_ajax_pmpro_get_checkout_nonce', 'pmpro_get_checkout_nonce' );
add_action( 'wp_ajax_nopriv_pmpro_get_checkout_nonce', 'pmpro_get_checkout_nonce' );

/**
 * AJAX endpoint to validate the account fields on the checkout page.
 *
 * Runs the same checks as the server-side checkout validation so the user can
 * get feedback on a field as soon as they leave it instead of waiting for a
 * full form submission.
 *
 * Note: The username and email checks reveal whether a value is already
 * registered. That is inherent to validating these fields before checkout and
 * matches what the checkout page already shows after a failed submission.
 *
 * @since 3.9
 */
function pmpro_checkout_validate() {
	// Check the nonce. Skip enforcement for sites running a pre-3.0 custom
	// checkout template, which does not print the nonce field. This mirrors
	// the check in preheaders/checkout.php.
	if ( empty( $_REQUEST['pmpro_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['pmpro_checkout_nonce'] ), 'pmpro_checkout_nonce' ) ) {
		$skip_nonce_check = false;
		if ( 'yes' === get_option( 'pmpro_use_custom_page_template_checkout' ) ) {
			$loaded_path    = pmpro_get_template_path_to_load( 'checkout' );
			$loaded_version = pmpro_get_version_for_page_template_at_path( $loaded_path );
			if ( empty( $loaded_version ) || version_compare( $loaded_version, '3.0', '<' ) ) {
				$skip_nonce_check = true;
			}
		}
		if ( ! $skip_nonce_check ) {
			wp_send_json_error(
				array( 'message' => __( 'Nonce security check failed.', 'paid-memberships-pro' ) ),
				403
			);
		}
	}

	// The account fields are only validated when the checkout is creating a new user.
	if ( is_user_logged_in() ) {
		wp_send_json_success( array( 'fields' => new stdClass() ) );
	}

	// Read and sanitize the posted values. Passwords are not sanitized, matching preheaders/checkout.php.
	// The raw username is kept for the character check. sanitize_user() strips
	// illegal characters, so validate_username() must run on the raw value to
	// detect them, the same way the admin member-edit panel checks $_POST.
	$raw_username  = isset( $_REQUEST['username'] ) ? wp_unslash( $_REQUEST['username'] ) : '';
	$username      = sanitize_user( $raw_username, true );
	$password      = isset( $_REQUEST['password'] ) ? wp_unslash( $_REQUEST['password'] ) : '';
	$password2     = isset( $_REQUEST['password2'] ) ? wp_unslash( $_REQUEST['password2'] ) : '';
	$bemail        = isset( $_REQUEST['bemail'] ) ? sanitize_email( wp_unslash( $_REQUEST['bemail'] ) ) : '';
	$bconfirmemail = isset( $_REQUEST['bconfirmemail'] ) ? sanitize_email( wp_unslash( $_REQUEST['bconfirmemail'] ) ) : '';

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords must not be modified.
	if ( isset( $_REQUEST['password2_copy'] ) ) {
		$password2 = $password;
	}
	// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	/**
	 * Filter the fields that are required on the checkout page.
	 *
	 * @param array $pmpro_required_user_fields Array of field names and their submitted values.
	 */
	$pmpro_required_user_fields = array(
		'username'      => $username,
		'password'      => $password,
		'password2'     => $password2,
		'bemail'        => $bemail,
		'bconfirmemail' => $bconfirmemail,
	);
	$pmpro_required_user_fields = apply_filters( 'pmpro_required_user_fields', $pmpro_required_user_fields );

	$error_fields = array();

	// Required field checks.
	foreach ( $pmpro_required_user_fields as $key => $field ) {
		if ( ! $field ) {
			$error_fields[ $key ] = __( 'This field is required.', 'paid-memberships-pro' );
		}
	}

	// Password confirmation check.
	if ( $password !== $password2 ) {
		$error_fields['password']  = __( 'Your passwords do not match. Please try again.', 'paid-memberships-pro' );
		$error_fields['password2'] = __( 'Your passwords do not match. Please try again.', 'paid-memberships-pro' );
	}

	// Email confirmation check.
	if ( strcasecmp( $bemail, $bconfirmemail ) !== 0 ) {
		$error_fields['bemail']        = __( 'Your email addresses do not match. Please try again.', 'paid-memberships-pro' );
		$error_fields['bconfirmemail'] = __( 'Your email addresses do not match. Please try again.', 'paid-memberships-pro' );
	}

	// Email format check. Skipped when empty since the required check already covers it.
	if ( ! empty( $bemail ) && ! is_email( $bemail ) ) {
		$error_fields['bemail']        = __( 'The email address entered is in an invalid format. Please try again.', 'paid-memberships-pro' );
		$error_fields['bconfirmemail'] = __( 'The email address entered is in an invalid format. Please try again.', 'paid-memberships-pro' );
	}

	// Username format check.
	if ( ! empty( $raw_username ) && ! validate_username( $raw_username ) ) {
		$error_fields['username'] = __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'paid-memberships-pro' );
	}

	// Username uniqueness check.
	$ouser = get_user_by( 'login', $username );
	if ( ! empty( $ouser->user_login ) ) {
		$error_fields['username'] = __( 'That username is already taken. Please try another.', 'paid-memberships-pro' );
	}

	// Email uniqueness check.
	$oldem_user = get_user_by( 'email', $bemail );
	$oldem_user = apply_filters_deprecated( 'pmpro_checkout_oldemail', array( ( false !== $oldem_user ? $oldem_user->user_email : null ) ), '3.2' );
	if ( ! empty( $oldem_user ) ) {
		$error_fields['bemail']        = __( 'That email address is already in use. Please log in, or use a different email address.', 'paid-memberships-pro' );
		$error_fields['bconfirmemail'] = __( 'That email address is already in use. Please log in, or use a different email address.', 'paid-memberships-pro' );
	}

	/**
	 * Filter the field errors found while validating the checkout account fields.
	 *
	 * @param array $error_fields Array of field names pointing to error messages.
	 */
	$error_fields = apply_filters( 'pmpro_checkout_validate_field_errors', $error_fields );

	// An empty map means every field passed.
	wp_send_json_success( array( 'fields' => empty( $error_fields ) ? new stdClass() : $error_fields ) );
}
add_action( 'wp_ajax_pmpro_checkout_validate', 'pmpro_checkout_validate' );
add_action( 'wp_ajax_nopriv_pmpro_checkout_validate', 'pmpro_checkout_validate' );
