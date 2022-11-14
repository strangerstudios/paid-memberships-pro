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
 * @since TBD
 *
 * @param MemberOrder $order The order to save the checkout fields for.
 */
function pmpro_save_checkout_data_to_order( $order ) {
	global $pmpro_level, $discount_code;

	// Save the request variables.
	$request_vars = $_REQUEST;
	unset( $request_vars['password'] );
	unset( $request_vars['password2'] );
	unset( $request_vars['password2_copy'] );
	update_pmpro_membership_order_meta( $order->id, 'checkout_request_vars', array_map( 'sanitize_text_field', $request_vars ) );

	// Save the checkout level.
	$pmpro_level_arr = (array) $pmpro_level;
	update_pmpro_membership_order_meta( $order->id, 'checkout_level', array_map( 'sanitize_text_field', $pmpro_level_arr ) );

	// Save the discount code.
	update_pmpro_membership_order_meta( $order->id, 'checkout_discount_code', sanitize_text_field( $discount_code ) );

	// Save any files that were uploaded.
	if ( ! empty( $_FILES ) ) {
		$files = array();
		foreach ( $_FILES as $arr_key => $file ) {
			// Move the file to the uploads/pmpro-register-helper/tmp directory.
			// Check for a register helper directory in wp-content.
			$upload_dir = wp_upload_dir();
			$pmprorh_dir = $upload_dir['basedir'] . "/pmpro-register-helper/tmp/";

			// Create the dir and subdir if needed
			if( ! is_dir( $pmprorh_dir ) ) {
				wp_mkdir_p( $pmprorh_dir );
			}

			// Move file
			$new_filename = $pmprorh_dir . basename( $file['tmp_name'] );
			move_uploaded_file($file['tmp_name'], $new_filename);

			// Update location of file
			$file['tmp_name'] = $new_filename;

			// Add the file to the array.
			$files[ $arr_key ] = $file;
		}
		update_pmpro_membership_order_meta( $order->id, 'checkout_files', array_map( 'sanitize_text_field', $files ) );
	}
}

/**
 * Complete an asynchronous checkout.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order to complete the checkout for.
 * @return bool True if the checkout was completed successfully, false otherwise.
 */
function pmpro_complete_async_checkout( $order ) {
	global $wpdb, $pmpro_level, $discount_code, $discount_code_id;

	// Pull the checkout level from order meta.
	$checkout_level_arr = get_pmpro_membership_order_meta( $order->id, 'checkout_level', true );
	$pmpro_level = (object) $checkout_level_arr;

	// Pull the discount code from order meta.
	$discount_code = get_pmpro_membership_order_meta( $order->id, 'checkout_discount_code', true );

	// Pull $_REQUEST variables out of order meta.
	$checkout_request_vars = get_pmpro_membership_order_meta( $order->id, 'checkout_request_vars', true );
	$_REQUEST = array_merge( $_REQUEST, $checkout_request_vars );

	// Pull $_FILES data out of order meta.
	$checkout_files = get_pmpro_membership_order_meta( $order->id, 'checkout_files', true );
	if ( ! empty( $checkout_files ) ) {
		$_FILES = array_merge( $_FILES, $checkout_files );
	}

	// Run the pmpro_checkout_before_change_membership_level action in case add ons need to set up.
	do_action( 'pmpro_checkout_before_change_membership_level', $order->user_id, $order );

	// Set the membership start date to current_time('timestamp') but allow filters.
	/**
	 * Filter the start date for the membership/subscription.
	 *
	 * @since 1.8.9
	 *
	 * @param string $startdate , datetime formatsted for MySQL (NOW() or YYYY-MM-DD)
	 * @param int $user_id , ID of the user checking out
	 * @param object $pmpro_level , object of level being checked out for
	 */
	$startdate = apply_filters( 'pmpro_checkout_start_date', "'" . current_time( 'mysql' ) . "'", $order->user_id, $pmpro_level );

	// Fix expiration date.
	if ( ! empty( $pmpro_level->expiration_number ) ) {
		$enddate = "'" . date_i18n( 'Y-m-d', strtotime( '+ ' . $pmpro_level->expiration_number . ' ' . $pmpro_level->expiration_period, current_time( 'timestamp' ) ) ) . "'";
	} else {
		$enddate = 'NULL';
	}

	// Calculate the end date from the current time but allow filters.
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
	$enddate = apply_filters( 'pmpro_checkout_end_date', $enddate, $order->user_id, $pmpro_level, $startdate );

	// Set up user's new level.
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
		'enddate'         => $enddate,
	);

	global $pmpro_error;
	if ( ! empty( $pmpro_error ) ) {
		echo $pmpro_error;
		ipnlog( $pmpro_error );
	}

	// Change level. Bail if level change fails.
	if ( pmpro_changeMembershipLevel( $custom_level, $order->user_id, 'changed' ) === false ) {
		return false;
	}

	// Mark the order as successful.
	$order->status = 'success';
	$order->saveOrder();

	// Add discount code use.
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
			do_action( 'pmpro_discount_code_used', $discount_code_id, $order->user_id, $order->id );
		}
	}

	// Save first and last name fields.
	if ( ! empty( $_REQUEST['first_name'] ) ) {
		$old_firstname = get_user_meta( $order->user_id, 'first_name', true );
		if ( empty( $old_firstname ) ) {
			update_user_meta( $order->user_id, 'first_name', sanitize_text_field( $_REQUEST['first_name'] ) );
		}
	}
	if ( ! empty( $_REQUEST['last_name'] ) ) {
		$old_lastname = get_user_meta( $order->user_id, 'last_name', true );
		if ( empty( $old_lastname ) ) {
			update_user_meta( $order->user_id, 'last_name', sanitize_text_field( $_REQUEST['last_name'] ) );
		}
	}

	// Run "after checkout" hook.
	do_action( 'pmpro_after_checkout', $order->user_id, $order );

	// Set up checkout emails.
	$user                   = get_userdata( $order->user_id );
	$user->membership_level = $pmpro_level; // Make sure they have the right level info.

	// Send email to member.
	$pmproemail = new PMProEmail();
	$pmproemail->sendCheckoutEmail( $user, $order );

	// Send email to admin.
	$pmproemail = new PMProEmail();
	$pmproemail->sendCheckoutAdminEmail( $user, $order );

	return true;
}
