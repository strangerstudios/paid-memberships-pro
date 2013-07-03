<?php
/*
Plugin Name: Paid Memberships Pro
Plugin URI: http://www.paidmembershipspro.com
Description: Plugin to Handle Memberships
Version: 1.7.0.2
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/
/*
	Copyright 2011	Stranger Studios	(email : jason@strangerstudios.com)
	GPLv2 Full license details in license.txt
*/

//if the session has been started yet, start it (ignore if running from command line)
if(defined('STDIN') )
{
	//command line
}
else
{
	if(!session_id())
		session_start();
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

require_once(PMPRO_DIR . "/shortcodes/checkout.php");			//[pmpro_checkout] shortcode for checkout pages
require_once(PMPRO_DIR . "/shortcodes/checkout_button.php");	//[checkout_button] shortcode to show link to checkout for a level
require_once(PMPRO_DIR . "/shortcodes/membership.php");			//[membership] shortcode to hide/show member content

/*
	Setup the DB and check for upgrades
*/
global $wpdb;
pmpro_checkForUpgrades();

/*
	Definitions
*/
define("SITENAME", str_replace("&#039;", "'", get_bloginfo("name")));
$urlparts = explode("//", home_url());
define("SITEURL", $urlparts[1]);
define("SECUREURL", str_replace("http://", "https://", get_bloginfo("wpurl")));
define("PMPRO_URL", WP_PLUGIN_URL . "/paid-memberships-pro");
define("PMPRO_VERSION", "1.7.0.2");
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
	wp_schedule_event(time(), 'daily', 'pmpro_cron_expiration_warnings');
	wp_schedule_event(time(), 'daily', 'pmpro_cron_trial_ending_warnings');
	wp_schedule_event(time(), 'daily', 'pmpro_cron_expire_memberships');
}
function pmpro_deactivation()
{
	wp_clear_scheduled_hook('pmpro_cron_expiration_warnings');
	wp_clear_scheduled_hook('pmpro_cron_trial_ending_warnings');
	wp_clear_scheduled_hook('pmpro_cron_expire_memberships');
}
register_activation_hook(__FILE__, 'pmpro_activation');
register_deactivation_hook(__FILE__, 'pmpro_deactivation');