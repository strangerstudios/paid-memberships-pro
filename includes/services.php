<?php
/*
	Loading a service?
*/
/*
	Note: The applydiscountcode goes through the site_url() instead of admin-ajax to avoid HTTP/HTTPS issues.
*/
if(isset($_REQUEST['action']) && $_REQUEST['action'] == "applydiscountcode")
{		
	function pmpro_applydiscountcode_init()
	{
		require_once(dirname(__FILE__) . "/../services/applydiscountcode.php");	
		exit;
	}
	add_action("init", "pmpro_applydiscountcode_init", 11);
}
function pmpro_wp_ajax_authnet_silent_post()
{		
	require_once(dirname(__FILE__) . "/../services/authnet-silent-post.php");	
	exit;	
}
add_action('wp_ajax_nopriv_authnet_silent_post', 'pmpro_wp_ajax_authnet_silent_post');
add_action('wp_ajax_authnet_silent_post', 'pmpro_wp_ajax_authnet_silent_post');
function pmpro_wp_ajax_getfile()
{
	require_once(dirname(__FILE__) . "/../services/getfile.php");	
	exit;	
}
add_action('wp_ajax_nopriv_getfile', 'pmpro_wp_ajax_getfile');
add_action('wp_ajax_getfile', 'pmpro_wp_ajax_getfile');
function pmpro_wp_ajax_ipnhandler()
{
	require_once(dirname(__FILE__) . "/../services/ipnhandler.php");	
	exit;	
}
add_action('wp_ajax_nopriv_ipnhandler', 'pmpro_wp_ajax_ipnhandler');
add_action('wp_ajax_ipnhandler', 'pmpro_wp_ajax_ipnhandler');
function pmpro_wp_ajax_stripe_webhook()
{
	require_once(dirname(__FILE__) . "/../services/stripe-webhook.php");	
	exit;	
}
add_action('wp_ajax_nopriv_stripe_webhook', 'pmpro_wp_ajax_stripe_webhook');
add_action('wp_ajax_stripe_webhook', 'pmpro_wp_ajax_stripe_webhook');
function pmpro_wp_ajax_braintree_webhook()
{
	require_once(dirname(__FILE__) . "/../services/braintree-webhook.php");	
	exit;	
}
add_action('wp_ajax_nopriv_braintree_webhook', 'pmpro_wp_ajax_braintree_webhook');
add_action('wp_ajax_braintree_webhook', 'pmpro_wp_ajax_braintree_webhook');
function pmpro_wp_ajax_twocheckout_ins()
{
	require_once(dirname(__FILE__) . "/../services/twocheckout-ins.php");	
	exit;	
}
add_action('wp_ajax_nopriv_twocheckout-ins', 'pmpro_wp_ajax_twocheckout_ins');
add_action('wp_ajax_twocheckout-ins', 'pmpro_wp_ajax_twocheckout_ins');
function pmpro_wp_ajax_memberlist_csv()
{
	require_once(dirname(__FILE__) . "/../adminpages/memberslist-csv.php");	
	exit;	
}
add_action('wp_ajax_memberslist_csv', 'pmpro_wp_ajax_memberlist_csv');
function pmpro_wp_ajax_orders_csv()
{
	require_once(dirname(__FILE__) . "/../adminpages/orders-csv.php");	
	exit;	
}
add_action('wp_ajax_orders_csv', 'pmpro_wp_ajax_orders_csv');


/**
 * Handles the Visits, Views and Logins Export
 */
function pmpro_wp_ajax_login_report_csv() {
	require_once(dirname(__FILE__) . "/../adminpages/login-csv.php");	
	exit;	
}
add_action('wp_ajax_login_report_csv', 'pmpro_wp_ajax_login_report_csv');

/**
 * Handles the Sales Export
 */
function pmpro_wp_ajax_sales_report_csv() {
	require_once(dirname(__FILE__) . "/../adminpages/sales-csv.php");	
	exit;	
}
add_action('wp_ajax_sales_report_csv', 'pmpro_wp_ajax_sales_report_csv');

/**
 * Handles the Membership Stats Export
 */
function pmpro_wp_ajax_membership_stats_csv() {
	require_once(dirname(__FILE__) . "/../adminpages/memberships-csv.php");	
	exit;	
}
add_action('wp_ajax_membership_stats_csv', 'pmpro_wp_ajax_membership_stats_csv');

/**
 * Load the Orders print view.
 *
 * @since 1.8.6
 */
function pmpro_orders_print_view() {
	require_once(dirname(__FILE__) . "/../adminpages/orders-print.php");
	exit;
}
add_action('wp_ajax_pmpro_orders_print_view', 'pmpro_orders_print_view');

/**
 * Get order JSON.
 *
 * @since 1.8.6
 * @since 2.9.10 - Only returns a subset of data. Only email is really used.
 */
function pmpro_get_order_json() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_orders' ) ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}
	
	$order_id = intval( $_REQUEST['order_id'] );
	$order = new MemberOrder($order_id);
	$user = get_userdata($order->user_id);
		
	$r = array(
		'id' => (int)$order->id,
		'user_id' => (int)$order->user_id,
		'membership_id' => (int)$order->membership_id,
		'code' => esc_html( $order->code ),
		'Email' => sanitize_email( empty( $user->user_email ) ? '' : $user->user_email ),		
	);
	
	echo wp_json_encode($r);
	exit;
}
add_action('wp_ajax_pmpro_get_order_json', 'pmpro_get_order_json');

function pmpro_update_level_order() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_membershiplevels' ) ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'pmpro_update_level_order' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	$level_order = null;
	
	if ( isset( $_REQUEST['level_order'] ) && is_array( $_REQUEST['level_order'] ) ) {
		$level_order = array_map( 'intval', $_REQUEST['level_order'] );
		$level_order = implode(',', $level_order );
	} else if ( isset( $_REQUEST['level_order'] ) ) {
		$level_order = sanitize_text_field( $_REQUEST['level_order'] );
	}
	
	echo esc_html( update_option('pmpro_level_order', $level_order) );
    exit;
}
add_action('wp_ajax_pmpro_update_level_order', 'pmpro_update_level_order');

function pmpro_update_level_group_order() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_membershiplevels' ) ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'pmpro_update_level_group_order' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	$level_group_order = null;
	
	if ( isset( $_REQUEST['level_group_order'] ) && is_array( $_REQUEST['level_group_order'] ) ) {
		$level_group_order = array_map( 'intval', $_REQUEST['level_group_order'] );
	} else if ( isset( $_REQUEST['level_group_order'] ) ) {
		$level_group_order = explode(',', sanitize_text_field( $_REQUEST['level_group_order'] ) );
	}

	$count = 1;
	foreach ( $level_group_order as $level_group_id ) {
		$level_group = pmpro_get_level_group( $level_group_id );
		if ( ! empty( $level_group ) ) {
			pmpro_edit_level_group( $level_group_id, $level_group->name, $level_group->allow_multiple_selections, $count );
		}
		$count++;
	}

	exit;
}
add_action('wp_ajax_pmpro_update_level_group_order', 'pmpro_update_level_group_order');

// User fields AJAX.
/**
 * Callback to draw a field group.
 *
 * @deprecated TBD
 */
function pmpro_userfields_get_group_ajax() {
	_deprecated_function( __FUNCTION__, 'TBD' );
    exit;
}
add_action( 'wp_ajax_pmpro_userfields_get_group', 'pmpro_userfields_get_group_ajax' );
 
/**
 * Callback to draw a field.
 *
 * @deprecated TBD
 */
function pmpro_userfields_get_field_ajax() {
	_deprecated_function( __FUNCTION__, 'TBD' );
	exit;
}
add_action( 'wp_ajax_pmpro_userfields_get_field', 'pmpro_userfields_get_field_ajax' );

function pmpro_update_field_order() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_userfields' ) ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'pmpro_update_field_order' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Get the field group that was reordered and the new order.
	$field_group = sanitize_text_field( $_REQUEST['group'] );
	$ordered_fields = array_map( 'sanitize_text_field', $_REQUEST['ordered_fields'] );
	
	// Get the current user fields settings.
	$current_settings = pmpro_get_user_fields_settings();

	// Find the group object that we are reordering.
	$group = null;
	foreach ( $current_settings as $group_settings ) {
		if ( $group_settings->name === $field_group ) {
			$group = $group_settings;
			break;
		}
	}
	if ( empty( $group ) ) {
		die( esc_html__( 'Could not find the group to reorder.', 'paid-memberships-pro' ) );
	}

	// Create an associative version of $group->fields to make it easier to reorder.
	$group_field_tmp = array();
	foreach ( $group->fields as $field ) {
		$group_field_tmp[ $field->name ] = $field;
	}

	// Create a reordered version of the fields.
	$reordered_fields = array();
	foreach ( $ordered_fields as $field_name ) {
		if ( isset( $group_field_tmp[ $field_name ] ) ) {
			$reordered_fields[] = $group_field_tmp[ $field_name ];
			unset( $group_field_tmp[ $field_name ] );
		}
	}

	// If there are any fields left in $group_field_tmp, add them to the end of $reordered_fields.
	if ( ! empty( $group_field_tmp ) ) {
		$reordered_fields = array_merge( $reordered_fields, $group_field_tmp );
	}

	// Update the group with the reordered fields.
	$group->fields = $reordered_fields;

	// Update the settings with the reordered group.
	update_option( 'pmpro_user_fields_settings', $current_settings );

    exit;
}
add_action('wp_ajax_pmpro_update_field_order', 'pmpro_update_field_order');


function pmpro_update_field_group_order() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_userfields' ) ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'pmpro_update_field_group_order' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Get the new order.
	$ordered_groups = array_map( 'sanitize_text_field', $_REQUEST['ordered_groups'] );

	// Get the current user fields settings.
	$current_settings = pmpro_get_user_fields_settings();

	// Create an associative version of $current_settings to make it easier to reorder.
	$current_settings_tmp = array();
	foreach ( $current_settings as $group_settings ) {
		$current_settings_tmp[ $group_settings->name ] = $group_settings;
	}

	// Create a reordered version of the groups.
	$reordered_groups = array();
	foreach ( $ordered_groups as $group_name ) {
		if ( isset( $current_settings_tmp[ $group_name ] ) ) {
			$reordered_groups[] = $current_settings_tmp[ $group_name ];
			unset( $current_settings_tmp[ $group_name ] );
		}
	}

	// If there are any groups left in $current_settings_tmp, add them to the end of $reordered_groups.
	if ( ! empty( $current_settings_tmp ) ) {
		$reordered_groups = array_merge( $reordered_groups, $current_settings_tmp );
	}

	// Update the settings with the reordered groups.
	update_option( 'pmpro_user_fields_settings', $reordered_groups );

	exit;
}
add_action('wp_ajax_pmpro_update_field_group_order', 'pmpro_update_field_group_order');
