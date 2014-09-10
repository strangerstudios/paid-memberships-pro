<?php
/*
Plugin Name: Paid Memberships Pro
Plugin URI: http://www.paidmembershipspro.com
Description: Plugin to Handle Memberships
Version: 1.7.14.2
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/
/*
	Copyright 2011	Stranger Studios	(email : jason@strangerstudios.com)
	GPLv2 Full license details in license.txt
*/

//version constant
define("PMPRO_VERSION", "1.7.14.2");

//if the session has been started yet, start it (ignore if running from command line)
if(defined('STDIN') )
{
	//command line
}
else
{
    if (version_compare(phpversion(), '5.4.0', '>=')) {
        if (session_status() == PHP_SESSION_NONE)
            session_start();
    }
    else {
        if(!session_id())
            session_start();
    }

}

/*
	Includes
*/
define("PMPRO_DIR", dirname(__FILE__));
require_once(PMPRO_DIR . "/includes/localization.php");			//localization functions
require_once(PMPRO_DIR . "/includes/lib/name-parser.php");		//parses "Jason Coleman" into firstname=>Jason, lastname=>Coleman
require_once(PMPRO_DIR . "/includes/functions.php");			//misc functions used by the plugin
require_once(PMPRO_DIR . "/includes/upgradecheck.php");			//database and other updates

require_once(PMPRO_DIR . "/scheduled/crons.php");				//crons for expiring members, sending expiration emails, etc

//require_once(PMPRO_DIR . "/classes/class.pmprogateway.php");	//loaded by memberorder class when needed
require_once(PMPRO_DIR . "/classes/class.memberorder.php");		//class to process and save orders
require_once(PMPRO_DIR . "/classes/class.pmproemail.php");		//setup and filter emails sent by PMPro

require_once(PMPRO_DIR . "/includes/filters.php");				//filters, hacks, etc, moved into the plugin
require_once(PMPRO_DIR . "/includes/reports.php");				//load reports for admin (reports may also include tracking code, etc)
require_once(PMPRO_DIR . "/includes/adminpages.php");			//dashboard pages
require_once(PMPRO_DIR . "/includes/services.php");				//services loaded by AJAX and via webhook, etc
require_once(PMPRO_DIR . "/includes/metaboxes.php");			//metaboxes for dashboard
require_once(PMPRO_DIR . "/includes/profile.php");				//edit user/profile fields
require_once(PMPRO_DIR . "/includes/https.php");				//code related to HTTPS/SSL
require_once(PMPRO_DIR . "/includes/notifications.php");		//check for notifications at PMPro, shown in PMPro settings
require_once(PMPRO_DIR . "/includes/init.php");					//code run during init, set_current_user, and wp hooks
require_once(PMPRO_DIR . "/includes/content.php");				//code to check for memebrship and protect content
require_once(PMPRO_DIR . "/includes/email.php");				//code related to email
require_once(PMPRO_DIR . "/includes/recaptcha.php");			//load recaptcha files if needed
require_once(PMPRO_DIR . "/includes/cleanup.php");				//clean things up when deletes happen, etc.
require_once(PMPRO_DIR . "/includes/login.php");				//code to redirect away from login/register page

require_once(PMPRO_DIR . "/includes/xmlrpc.php");				//xmlrpc methods

require_once(PMPRO_DIR . "/shortcodes/checkout_button.php");	//[checkout_button] shortcode to show link to checkout for a level
require_once(PMPRO_DIR . "/shortcodes/membership.php");			//[membership] shortcode to hide/show member content

/*
	Setup the DB and check for upgrades
*/
global $wpdb;

//check if the DB needs to be upgraded
if(is_admin())
	pmpro_checkForUpgrades();

/*
	Definitions
*/
define("SITENAME", str_replace("&#039;", "'", get_bloginfo("name")));
$urlparts = explode("//", home_url());
define("SITEURL", $urlparts[1]);
define("SECUREURL", str_replace("http://", "https://", get_bloginfo("wpurl")));
define("PMPRO_URL", WP_PLUGIN_URL . "/paid-memberships-pro");
define("PMPRO_DOMAIN", pmpro_getDomainFromURL(site_url()));

/*
	Globals
*/
global $gateway_environment;
$gateway_environment = pmpro_getOption("gateway_environment");

//when checking levels for users, we save the info here for caching. each key is a user id for level object for that user.
global $all_membership_levels;

//we sometimes refer to this array of levels
global $membership_levels;
$membership_levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

/*
	Activation/Deactivation
*/
function pmpro_activation()
{
	//schedule crons
	wp_schedule_event(current_time('timestamp'), 'daily', 'pmpro_cron_expiration_warnings');
	//wp_schedule_event(current_time('timestamp')(), 'daily', 'pmpro_cron_trial_ending_warnings');		//this warning has been deprecated since 1.7.2
	wp_schedule_event(current_time('timestamp'), 'daily', 'pmpro_cron_expire_memberships');
	wp_schedule_event(current_time('timestamp'), 'monthly', 'pmpro_cron_credit_card_expiring_warnings');

	//add caps to admin role
	$role = get_role( 'administrator' );
	$role->add_cap( 'pmpro_memberships_menu' );
	$role->add_cap( 'pmpro_membershiplevels' );	
	$role->add_cap( 'pmpro_edit_memberships' );
	$role->add_cap( 'pmpro_pagesettings' );	
	$role->add_cap( 'pmpro_paymentsettings' );
	$role->add_cap( 'pmpro_emailsettings' );
	$role->add_cap( 'pmpro_advancedsettings' );	
	$role->add_cap( 'pmpro_addons' );	
	$role->add_cap( 'pmpro_memberslist' );
	$role->add_cap( 'pmpro_membersliscsv' );
	$role->add_cap( 'pmpro_reports' );
	$role->add_cap( 'pmpro_orders' );
	$role->add_cap( 'pmpro_orderscsv' );
	$role->add_cap( 'pmpro_discountcodes' );	
}
function pmpro_deactivation()
{
	//remove crons
	wp_clear_scheduled_hook('pmpro_cron_expiration_warnings');
	wp_clear_scheduled_hook('pmpro_cron_trial_ending_warnings');
	wp_clear_scheduled_hook('pmpro_cron_expire_memberships');
	wp_clear_scheduled_hook('pmpro_cron_credit_card_expiring_warnings');   

	//remove caps from admin role
	$role = get_role( 'administrator' );
	$role->remove_cap( 'pmpro_memberships_menu' );
	$role->remove_cap( 'pmpro_membershiplevels' );	
	$role->remove_cap( 'pmpro_edit_memberships' );
	$role->remove_cap( 'pmpro_pagesettings' );	
	$role->remove_cap( 'pmpro_paymentsettings' );
	$role->remove_cap( 'pmpro_emailsettings' );
	$role->remove_cap( 'pmpro_advancedsettings' );
	$role->remove_cap( 'pmpro_addons' );
	$role->remove_cap( 'pmpro_memberslist' );
	$role->remove_cap( 'pmpro_membersliscsv' );
	$role->remove_cap( 'pmpro_reports' );
	$role->remove_cap( 'pmpro_orders' );
	$role->remove_cap( 'pmpro_orderscsv' );
	$role->remove_cap( 'pmpro_discountcodes' );
}
register_activation_hook(__FILE__, 'pmpro_activation');
register_deactivation_hook(__FILE__, 'pmpro_deactivation');
