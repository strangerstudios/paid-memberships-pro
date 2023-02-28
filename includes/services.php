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
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}
	
	$order_id = intval( $_REQUEST['order_id'] );
	$order = new MemberOrder($order_id);
		
	$r = array(
		'id' => (int)$order->id,
		'user_id' => (int)$order->user_id,
		'membership_id' => (int)$order->membership_id,
		'code' => esc_html( $order->code ),
		'Email' => sanitize_email( $order->Email ),		
	);
	
	echo wp_json_encode($r);
	exit;
}
add_action('wp_ajax_pmpro_get_order_json', 'pmpro_get_order_json');

function pmpro_update_level_order() {
	// only admins can get this
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_membershiplevels' ) ) ) {
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	$level_order = null;
	
	if ( isset( $_REQUEST['level_order'] ) && is_array( $_REQUEST['level_order'] ) ) {
		$level_order = array_map( 'intval', $_REQUEST['level_order'] );
		$level_order = implode(',', $level_order );
	} else if ( isset( $_REQUEST['level_order'] ) ) {
		$level_order = sanitize_text_field( $_REQUEST['level_order'] );
	}
	
	echo pmpro_setOption('level_order', $level_order);
    exit;
}
add_action('wp_ajax_pmpro_update_level_order', 'pmpro_update_level_order');

// User fields AJAX.
/**
 * Callback to draw a field group.
 */
function pmpro_userfields_get_group_ajax() {	
	echo pmpro_get_field_group_html();
    exit;
}
add_action( 'wp_ajax_pmpro_userfields_get_group', 'pmpro_userfields_get_group_ajax' );
 
/**
 * Callback to draw a field.
 */
function pmpro_userfields_get_field_ajax() {
 	echo pmpro_get_field_html();
	exit;
}
add_action( 'wp_ajax_pmpro_userfields_get_field', 'pmpro_userfields_get_field_ajax' );
