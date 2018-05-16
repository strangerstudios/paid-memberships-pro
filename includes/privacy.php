<?php
/**
 * Code to aid with user data privacy, e.g. GDPR compliance
 * 
 * @since  1.9.5
 */

/** 
 * Add suggested Privacy Policy language for PMPro
 * @since 1.9.5
 */
function pmpro_add_privacy_policy_content() {	
	// Check for support.
	if ( ! function_exists( 'wp_add_privacy_policy_content') ) {
		return;
	}

	$content = '...';

	wp_add_privacy_policy_content( 'Paid Memberships Pro', $content );
}
add_action( 'admin_init', 'pmpro_add_privacy_policy_content' );

/**
 * Register the personal data eraser for PMPro
 * @param array $erasers All erasers added so far
 */
function pmpro_register_personal_data_erasers( $erasers ) {
	$erasers[] = array(
 		'eraser_friendly_name' => __( 'Paid Memberships Pro Data' ),
 		'callback'             => 'pmpro_personal_data_eraser',
 	);

	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'pmpro_register_personal_data_erasers' );

/**
 * Personal data eraser for PMPro data.
 * @param string $email_address Email address of the user to be erased.
 * @param int    $page          For batching
 */
function pmpro_personal_data_eraser( $email_address, $page = 1 ) {
	// Erase any data we have about this user.
	
	// Keep track of how many items are removed and remaining.
	
	// Set done to false if we still have stuff to erase.
	$done = true;

	return array(
 		'num_items_removed'  => 0,
 		'num_items_retained' => 0,
 		'messages'           => array(),
 		'done'               => $done,
 	);
}

/**
 * Register the personal data exporter for PMPro.
 * @param array $exporters All exporters added so far
 */
function pmpro_register_personal_data_exporters( $exporters ) {
	$exporters[] = array(
		'exporter_friendly_name' => __( 'Paid Memberships Pro Data' ),
		'callback'               => 'pmpro_personal_data_exporter',
	);

	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'pmpro_register_personal_data_exporters' );

/**
 * Personal data exporter for PMPro data.
 */
function pmpro_personal_data_exporter( $email_address, $page = 1 ) {
	global $wpdb;

	$data_to_export = array();

	// What user is this?
	$user = get_user_by( 'email', $email_address );

	if( !empty( $user ) ) {
		// Add data stored in user meta.
		$personal_user_meta_fields = pmpro_get_personal_user_meta_fields();
		$sqlQuery = $wpdb->prepare( 
			"SELECT meta_key, meta_value
			 FROM {$wpdb->usermeta}
			 WHERE user_id = %d
			 AND meta_key IN( [IN_CLAUSE] )", intval($user->ID) );
		
		$in_clause_data = array_map( 'esc_sql', array_keys( $personal_user_meta_fields ) );
		$in_clause = "'" . implode( "', '", $in_clause_data ) . "'";	
		$sqlQuery = preg_replace( '/\[IN_CLAUSE\]/', $in_clause, $sqlQuery );
		
		$personal_user_meta_data = $wpdb->get_results( $sqlQuery, OBJECT_K );
		
		$user_meta_data_to_export = array();
		foreach( $personal_user_meta_fields as $key => $name ) {
			if( !empty( $personal_user_meta_data[$key] ) ) {
				$value = $personal_user_meta_data[$key]->meta_value;
			} else {
				$value = '';
			}

			$user_meta_data_to_export[] = array(
				'name' => $name,
				'value' => $value,
			);
		}

		$data_to_export[] = array(
			'group_id'    => 'pmpro_user_data',
			'group_label' => __( 'Paid Memberships Pro User Data' ),
			'item_id'     => "user-{$user->ID}",
			'data'        => $user_meta_data_to_export,
		);
		

		// Add membership history.
		$sqlQuery = "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . intval($user->ID) . "' ORDER BY id DESC";
		$history = $wpdb->get_results( $sqlQuery );
		foreach( $history as $item ) {
			if( $item->enddate === null || $item->enddate == '0000-00-00 00:00:00' ) {
				$item->enddate = __( 'Never', 'paid-memberships-pro' );
			} else {
				$item->enddate = date( get_option( 'date_format' ), strtotime( $item->enddate, current_time( 'timestamp' ) ) );
			}

			$history_data_to_export = array(
				array(
					'name'  => __( 'Level ID', 'paid-memberships-pro' ),
					'value' => $item->membership_id, 
				),
				array(
					'name'  => __( 'Start Date', 'paid-memberships-pro' ),
					'value' => date( get_option( 'date_format' ), strtotime( $item->startdate, current_time( 'timestamp' ) ) ),
				),
				array(
					'name'  => __( 'Date Modified', 'paid-memberships-pro' ),
					'value' => date( get_option( 'date_format' ), strtotime( $item->modified, current_time( 'timestamp' ) ) ),
				),
				array(
					'name'  => __( 'End Date', 'paid-memberships-pro' ),
					'value' => $item->enddate,
				),
				array(
					'name'  => __( 'Level Cost', 'paid-memberships-pro' ),
					'value' => pmpro_getLevelCost( $item, false, true ),
				),
				array(
					'name' => __( 'Status', 'paid-memberships-pro' ),
					'value' => $item->status,
				),
			);

			$data_to_export[] = array(
				'group_id'    => 'pmpro_membership_history',
				'group_label' => __( 'Paid Memberships Pro Membership History' ),
				'item_id'     => "memberships_users-{$item->id}",
				'data'        => $history_data_to_export,
			);
		}

		// Add order history.
		// TODO: grab order history and put into $data_to_export
		
	}

	$done = true;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Get list of user meta fields with labels to include in the PMPro data exporter
 */
function pmpro_get_personal_user_meta_fields() {
	$fields = array(
		'pmpro_bfirstname' => __( 'Billing First Name', 'paid-memberships-pro' ),
		'pmpro_blastname' => __( 'Billing Last Name', 'paid-memberships-pro' ),
		'pmpro_baddress1' => __( 'Billing Address 1', 'paid-memberships-pro' ),
		'pmpro_baddress2' => __( 'Billing Address 2', 'paid-memberships-pro' ),
		'pmpro_bcity' => __( 'Billing City', 'paid-memberships-pro' ),
		'pmpro_bstate' => __( 'Billing State/Province', 'paid-memberships-pro' ),
		'pmpro_bzipcode' => __( 'Billing Postal Code', 'paid-memberships-pro' ),
		'pmpro_bphone' => __( 'Billing Phone Number', 'paid-memberships-pro' ),
		'pmpro_bcountry' => __( 'Billing Country', 'paid-memberships-pro' ),
		'pmpro_CardType' => __( 'Credit Card Type', 'paid-memberships-pro' ),
		'pmpro_AccountNumber' => __( 'Credit Card Account Number', 'paid-memberships-pro' ),
		'pmpro_ExpirationMonth' => __( 'Credit Card Expiration Month', 'paid-memberships-pro' ),
		'pmpro_ExpirationYear' => __( 'Credit Card Expiration Year', 'paid-memberships-pro' ),
		'pmpro_logins' => __( 'Login Data', 'paid-memberships-pro' ),
		'pmpro_visits' => __( 'Visits Data', 'paid-memberships-pro' ),
		'pmpro_views' => __( 'Views Data', 'paid-memberships-pro' ),
	);

	$fields = apply_filters( 'pmpro_get_personal_user_meta_fields', $fields );

	return $fields;
}
