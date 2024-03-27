<?php
/**
 * Plugin Name: Paid Memberships Pro
 * Plugin URI: https://www.paidmembershipspro.com
 * Description: The most complete member management and membership subscriptions plugin for WordPress.
 * Version: 3.0.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: paid-memberships-pro
 * Domain Path: /languages
 */
/**
 * Copyright 2011-2024	Stranger Studios
 * (email : info@paidmembershipspro.com)
 * GPLv2 Full license details in license.txt
 */

// version constant
define( 'PMPRO_VERSION', '3.0.1' );
define( 'PMPRO_USER_AGENT', 'Paid Memberships Pro v' . PMPRO_VERSION . '; ' . site_url() );
define( 'PMPRO_MIN_PHP_VERSION', '5.6' );

/*
	Includes
*/
define( 'PMPRO_BASE_FILE', __FILE__ );
define( 'PMPRO_DIR', dirname( __FILE__ ) );

require_once( PMPRO_DIR . '/classes/class-deny-network-activation.php' );   // stop PMPro from being network activated
require_once( PMPRO_DIR . '/includes/sessions.php' );               // start/close PHP session vars

require_once( PMPRO_DIR . '/includes/localization.php' );           // localization functions
require_once( PMPRO_DIR . '/includes/lib/name-parser.php' );        // parses "Jason Coleman" into firstname=>Jason, lastname=>Coleman
require_once( PMPRO_DIR . '/includes/functions.php' );              // misc functions used by the plugin
require_once( PMPRO_DIR . '/includes/updates.php' );                // database and other updates
require_once( PMPRO_DIR . '/includes/upgradecheck.php' );           // database and other updates
require_once( PMPRO_DIR . '/includes/deprecated.php' );             // deprecated hooks and functions

if ( ! defined( 'PMPRO_LICENSE_SERVER' ) ) {
	require_once( PMPRO_DIR . '/includes/license.php' );            // defines location of addons data and licenses
}

require_once( PMPRO_DIR . '/includes/crons.php' );                  // cron-related functionality
require_once( PMPRO_DIR . '/scheduled/crons.php' );                 // crons for expiring members, sending expiration emails, etc

require_once( PMPRO_DIR . '/classes/class.memberorder.php' );       // class to process and save orders
require_once( PMPRO_DIR . '/classes/class.pmproemail.php' );        // setup and filter emails sent by PMPro
require_once( PMPRO_DIR . '/classes/class-pmpro-field.php' );
require_once( PMPRO_DIR . '/classes/class-pmpro-levels.php' );
require_once( PMPRO_DIR . '/classes/class-pmpro-subscription.php' );
require_once( PMPRO_DIR . '/classes/class-pmpro-admin-activity-email.php' );        // setup the admin activity email

require_once( PMPRO_DIR . '/includes/filters.php' );                // filters, hacks, etc, moved into the plugin
require_once( PMPRO_DIR . '/includes/reports.php' );                // load reports for admin (reports may also include tracking code, etc)

require_once( PMPRO_DIR . '/adminpages/reports/logins.php' );            // load the Logins report
require_once( PMPRO_DIR . '/adminpages/reports/memberships.php' );       // load the Memberships report
require_once( PMPRO_DIR . '/adminpages/reports/members-per-level.php' ); // load the Members Per Level report
require_once( PMPRO_DIR . '/adminpages/reports/sales.php' );             // load the Sales report

require_once( PMPRO_DIR . '/adminpages/member-edit.php' ); // load the Member Edit admin page.
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-abstract-class-member-edit-panel.php' );
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-class-member-edit-panel-user-info.php' );
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-class-member-edit-panel-memberships.php' );
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-class-member-edit-panel-subscriptions.php' );
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-class-member-edit-panel-orders.php' );
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-class-member-edit-panel-tos.php' );
require_once( PMPRO_DIR . '/adminpages/member-edit/pmpro-class-member-edit-panel-user-fields.php' );

require_once( PMPRO_DIR . '/includes/admin.php' );                  // admin notices and functionality
require_once( PMPRO_DIR . '/includes/adminpages.php' );             // dashboard pages
require_once( PMPRO_DIR . '/classes/class-pmpro-members-list-table.php' ); // Members List
require_once( PMPRO_DIR . '/classes/class-pmpro-orders-list-table.php' ); // Orders List
require_once( PMPRO_DIR . '/classes/class-pmpro-discount-code-list-table.php' ); // Discount Code List

require_once( PMPRO_DIR . '/includes/services.php' );               // services loaded by AJAX and via webhook, etc
require_once( PMPRO_DIR . '/includes/metaboxes.php' );              // metaboxes for dashboard
require_once( PMPRO_DIR . '/includes/profile.php' );                // edit user/profile fields
require_once( PMPRO_DIR . '/includes/https.php' );                  // code related to HTTPS/SSL
require_once( PMPRO_DIR . '/includes/menus.php' );                  // custom menu functions for PMPro
require_once( PMPRO_DIR . '/includes/notifications.php' );          // check for notifications at PMPro, shown in PMPro settings
require_once( PMPRO_DIR . '/includes/init.php' );                   // code run during init, set_current_user, and wp hooks
require_once( PMPRO_DIR . '/includes/scripts.php' );                // enqueue frontend and admin JS and CSS
require_once( PMPRO_DIR . '/includes/terms.php' );                  // allow restricting terms by membership level
require_once( PMPRO_DIR . '/includes/page-templates.php' );         // page templates

require_once( PMPRO_DIR . '/includes/content.php' );                // code to check for memebrship and protect content
require_once( PMPRO_DIR . '/includes/compatibility.php' );          // code to support compatibility for popular page builders
require_once( PMPRO_DIR . '/includes/email.php' );                  // code related to email
require_once( PMPRO_DIR . '/includes/fields.php' );                  // user fields
require_once( PMPRO_DIR . '/includes/recaptcha.php' );              // load recaptcha files if needed
require_once( PMPRO_DIR . '/includes/cleanup.php' );                // clean things up when deletes happen, etc.
require_once( PMPRO_DIR . '/includes/login.php' );                  // code to redirect away from login/register page
require_once( PMPRO_DIR . '/includes/capabilities.php' );           // manage PMPro capabilities for roles
require_once( PMPRO_DIR . '/includes/privacy.php' );                // code to aid with user data privacy, e.g. GDPR compliance
require_once( PMPRO_DIR . '/includes/pointers.php' );				// popover help pointers
require_once( PMPRO_DIR . '/includes/spam.php' );					// code to combat spam of various kinds
require_once( PMPRO_DIR . '/includes/abandoned-signups.php' );		// track users who were created at checkout but did not complete checkout.
require_once( PMPRO_DIR . '/includes/checkout.php' );		        // Common functions used at checkout.
require_once( PMPRO_DIR . '/includes/level-groups.php' );		    // Common functions for level groups.

require_once( PMPRO_DIR . '/includes/xmlrpc.php' );                 // xmlrpc methods
require_once( PMPRO_DIR . '/includes/rest-api.php' );               // rest API endpoints
require_once( PMPRO_DIR . '/includes/widgets.php' );                // widgets for PMPro
require_once( PMPRO_DIR . '/includes/gateway-request-handlers.php' ); // gateway request handlers

require_once( PMPRO_DIR . '/classes/class-pmpro-site-health.php' ); // Site Health information.

require_once( PMPRO_DIR . '/shortcodes/checkout_button.php' );      // [pmpro_checkout_button] shortcode to show link to checkout for a level
require_once( PMPRO_DIR . '/shortcodes/membership.php' );           // [membership] shortcode to hide/show member content
require_once( PMPRO_DIR . '/shortcodes/pmpro_account.php' );        // [pmpro_account] shortcode to show account information
require_once( PMPRO_DIR . '/shortcodes/pmpro_login.php' );          // [pmpro_login] shortcode to show a login form or logged in member info and menu.
require_once( PMPRO_DIR . '/shortcodes/pmpro_member.php' );         // [pmpro_member] shortcode to show user fields
require_once( PMPRO_DIR . '/shortcodes/pmpro_member_profile_edit.php' );         // [pmpro_member_profile_edit] shortcode to allow members to edit their profile
require_once( PMPRO_DIR . '/includes/blocks.php' ); // Set up blocks.

// load gateway
require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway.php' ); // loaded by memberorder class when needed

// load payment gateway class
require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_authorizenet.php' );

if ( version_compare( PHP_VERSION, '5.4.45', '>=' ) ) {
	require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_braintree.php' );
}

require_once( PMPRO_DIR . '/classes/class-pmpro-discount-codes.php' ); // loaded by memberorder class when needed

require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_check.php' );
require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_payflowpro.php' );
require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_paypalexpress.php' );
require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_paypalstandard.php' );

pmpro_check_for_deprecated_gateways();

if ( version_compare( PHP_VERSION, '5.3.29', '>=' ) ) {
	require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_stripe.php' );
	require_once( PMPRO_DIR . '/includes/lib/stripe-apple-pay/stripe-apple-pay.php' ); // rewrite rules to set up Apple Pay.
}

// Set up Wisdom tracking.
require_once PMPRO_DIR . '/classes/class-pmpro-wisdom-integration.php';
$wisdom_integration = PMPro_Wisdom_Integration::instance();
$wisdom_integration->setup_wisdom();

/*
	Setup the DB and check for upgrades
*/
global $wpdb;

// check if the DB needs to be upgraded
if ( is_admin() || defined('WP_CLI') ) {
	pmpro_checkForUpgrades();
}

// load plugin updater
if ( is_admin() ) {
	require_once( PMPRO_DIR . '/includes/addons.php' );
}

/*
	Definitions
*/
define( 'SITENAME', str_replace( '&#039;', "'", get_bloginfo( 'name' ) ) );
if ( ! defined( 'SITEURL'  ) ) {
	$urlparts = explode( '//', home_url() );
	define( 'SITEURL', $urlparts[1] );
}

if ( ! defined( 'SECUREURL'  ) ) {
	define( 'SECUREURL', str_replace( 'http://', 'https://', get_bloginfo( 'wpurl' ) ) );
}
define( 'PMPRO_URL', plugins_url( '', PMPRO_BASE_FILE ) );
define( 'PMPRO_DOMAIN', pmpro_getDomainFromURL( site_url() ) );
define( 'PAYPAL_BN_CODE', 'PaidMembershipsPro_SP' );

/*
	Globals
*/
global $gateway_environment;
$gateway_environment = get_option( 'pmpro_gateway_environment' );


// Returns a list of all available gateway
function pmpro_gateways() {
	$pmpro_gateways = array(
		''                  => __( 'Testing Only', 'paid-memberships-pro' ),
		'check'             => __( 'Pay by Check', 'paid-memberships-pro' ),
		'stripe'            => __( 'Stripe', 'paid-memberships-pro' ),
		'paypalexpress'     => __( 'PayPal Express', 'paid-memberships-pro' ),
		'payflowpro'        => __( 'PayPal Payflow Pro/PayPal Pro', 'paid-memberships-pro' ),
		'paypalstandard'    => __( 'PayPal Standard', 'paid-memberships-pro' ),
		'authorizenet'      => __( 'Authorize.net', 'paid-memberships-pro' ),
		'braintree'         => __( 'Braintree Payments', 'paid-memberships-pro' ),
	);

	if ( pmpro_onlyFreeLevels() ) {
		$pmpro_gateways[''] = __( 'Default', 'paid-memberships-pro' );
	}

	$check_gateway_label = get_option( 'pmpro_check_gateway_label' );
	if ( ! empty( $check_gateway_label ) ) {
		$pmpro_gateways['check'] =  esc_html( $check_gateway_label . ' (' . __( 'Pay by Check', 'paid-memberships-pro' ) . ')' );
	}

	return apply_filters( 'pmpro_gateways', $pmpro_gateways );
}


// when checking levels for users, we save the info here for caching. each key is a user id for level object for that user.
global $all_membership_levels;

// we sometimes refer to this array of levels
// DEPRECATED: Remove this in v3.0.
global $membership_levels;
$membership_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );

/*
	Activation/Deactivation
*/
// we need monthly crons
function pmpro_cron_schedules_monthly( $schedules ) {
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => __( 'Once a month', 'paid-memberships-pro' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'pmpro_cron_schedules_monthly' );

// activation
function pmpro_activation() {
	pmpro_maybe_schedule_crons();
	pmpro_set_capabilities_for_role( 'administrator', 'enable' );
	do_action( 'pmpro_activation' );
}

// deactivation
function pmpro_deactivation() {	
	// remove crons
	pmpro_clear_crons();

	// remove caps from admin role
	pmpro_set_capabilities_for_role( 'administrator', 'disable' );

	do_action( 'pmpro_deactivation' );
}
register_activation_hook( __FILE__, 'pmpro_activation' );
register_deactivation_hook( __FILE__, 'pmpro_deactivation' );
