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
	// Calculate the profile start date.
	$profile_start_date = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $order->BillingFrequency . ' ' . $order->BillingPeriod ) );

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
		 *
		 * @param string $profile_start_date The profile start date in UTC YYYY-MM-DD HH:MM:SS format.
		 * @param MemberOrder $order         The order that the profile start date is being calculated for.
		 *
		 * @return string The profile start date in UTC YYYY-MM-DD HH:MM:SS format.
		 */
		$profile_start_date = apply_filters( 'pmpro_profile_start_date', $profile_start_date, $order );
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
	// Unset restricted request variables.
	$restricted_vars = array(
		'password',
		'password2',
		'password2_copy',
		'AccountNumber',
		'CVV',
		'ExpirationMonth',
		'ExpirationYear',
		'add_sub_accounts_password', // Creating users at checkout with Sponsored Members.
	);
	foreach ( $restricted_vars as $key ) {
		if ( isset( $request_vars[ $key ] ) ) {
			unset( $request_vars[ $key ] );
		}
	}
	update_pmpro_membership_order_meta( $order->id, 'checkout_request_vars', $request_vars );

	// Save the checkout level.
	$pmpro_level_arr = (array) $pmpro_level;
	update_pmpro_membership_order_meta( $order->id, 'checkout_level', $pmpro_level_arr );

	// Save the discount code.
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
 * Pull checkout data from order meta after returning from offsite payment.
 *
 * @since 2.12.3
 *
 * @param MemberOrder $order The order to pull the checkout fields for.
 */
function pmpro_pull_checkout_data_from_order( $order ) {
	global $pmpro_level, $discount_code;
	// We need to pull the checkout level and fields data from the order.
	$checkout_level_arr = get_pmpro_membership_order_meta( $order->id, 'checkout_level', true );
	$pmpro_level = (object) $checkout_level_arr;

	// Set $discount_code_id.
	$discount_code = get_pmpro_membership_order_meta( $order->id, 'checkout_discount_code', true );
	
	// Set $_REQUEST.
	$checkout_request_vars = get_pmpro_membership_order_meta( $order->id, 'checkout_request_vars', true );
	$_REQUEST = array_merge( $_REQUEST, $checkout_request_vars );

	// Set $_FILES.
	$checkout_files = get_pmpro_membership_order_meta( $order->id, 'checkout_files', true );
	if ( ! empty( $checkout_files ) ) {
		$_FILES = array_merge( $_FILES, $checkout_files );
	}
}

/**
 * Complete an asynchronous checkout.
 *
 * @since 2.12.3
 *
 * @param MemberOrder $order The order to complete the checkout for.
 * @return bool True if the checkout was completed successfully, false otherwise.
 */
 function pmpro_complete_async_checkout( $order ) {
	global $wpdb, $pmpro_level, $discount_code, $discount_code_id;

	// Run the pmpro_checkout_before_change_membership_level action in case add ons need to set up.
	do_action( 'pmpro_checkout_before_change_membership_level', $order->user_id, $order );

	//set the start date to current_time('timestamp') but allow filters  (documented in preheaders/checkout.php)
	$startdate = apply_filters( "pmpro_checkout_start_date", "'" . current_time( 'mysql' ) . "'", $order->user_id, $pmpro_level );

	//fix expiration date
	if ( ! empty( $pmpro_level->expiration_number ) ) {
		$enddate = "'" . date_i18n( "Y-m-d", strtotime( "+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time( "timestamp" ) ) ) . "'";
	} else {
		$enddate = "NULL";
	}

	//filter the enddate (documented in preheaders/checkout.php)
	$enddate = apply_filters( "pmpro_checkout_end_date", $enddate, $order->user_id, $pmpro_level, $startdate );

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

	global $pmpro_error;
	if ( ! empty( $pmpro_error ) ) {		
		ipnlog( $pmpro_error );
	}

	//change level and continue "checkout"
	if ( pmpro_changeMembershipLevel( $custom_level, $order->user_id, 'changed' ) !== false ) {
		// Mark the order as successful.
		$order->status                 = "success";
		$order->saveOrder();

		//add discount code use
		if ( ! empty( $discount_code ) ) {
			$discount_code_id = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $discount_code ) . "' LIMIT 1" );
			if ( ! empty( $discount_code_id ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$wpdb->pmpro_discount_codes_uses} 
							( code_id, user_id, order_id, timestamp ) 
							VALUES( %d, %d, %s, %s )",
						$discount_code_id,
						$order->user_id,
						$order->id,
						current_time( 'mysql' )
					)	
				);
			}
			do_action( 'pmpro_discount_code_used', $discount_code_id, $order->user_id, $order->id );
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
