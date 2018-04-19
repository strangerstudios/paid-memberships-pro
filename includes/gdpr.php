<?php
/**
 * Functions to aid with GDPR compliance.
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

	$content = '...':

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
	$data_to_export = array();

	// Add data stored in user meta.
	// Add membership history.
	// Add order history.

	$done = true;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}
