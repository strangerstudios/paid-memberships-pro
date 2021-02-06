<?php
/*
Plugin Name: TEAE Customizations
Plugin URI: https://www.paidmembershipspro.com/wp/pmpro-customizations/
Description: Customizations for the TEAE Website.
Version: .1
Author: TEAE
line 96 check about exporting display_name
*/

/**  These functions are set in functions.php in memberlite-child theme 
* buddypress vertical check boxes  44
* protected-directory/ directory.  73
* back to the top  191
* united39_internal  197
* united39_internal 204
* add order options to members loop  212
* bbpress forum breadcrumbs  232
* Sort alphabetical name listings by lastname  648
* View Counter for BBpress 252
* Paypal button sizing No need for pmpro paypal express addon  282
* Don't send membership expiring or expired emails for certain levels 293
* acf field for regions in calendar   330
* change invoice to receipt 493
* add member rate to expiring emails
*/

define('PMPRO_CUSTOMIZATIONS_DIR', dirname(__FILE__));
require_once(PMPRO_CUSTOMIZATIONS_DIR . "/includes/pmpro-register-helper-fields.php");
require_once(PMPRO_CUSTOMIZATIONS_DIR . "/includes/regions.php");

/* Make BuddyPress Activity Page the Member Dashboard and Redirect on Login */
function myprofile_shortcode() {
	global $current_user;
	get_currentuserinfo();
	$myprofileurl = '<a href="' . home_url() . '/members/' . $current_user->user_login . '/profile/">My Profile</a>';
	return $myprofileurl;
}
add_shortcode( 'myprofileurl', 'myprofile_shortcode' );

/**
 * Add this code to your PMPro Customizations Plugin - https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 * The my_pmpro_renew_membership_shortcode is a custom function creating a renew link for members.
 * Use the shortcode [pmpro_renew_button] to display the button anywhere on your site where shortcodes are recognized.
 *
 * @return string A link containing the URL string to renew.
 */
function my_pmpro_renew_membership_shortcode() {
	global $current_user, $pmpro_pages;
	// Current user empty (i.e. not logged in)
	if ( empty( $current_user ) ) {
		return;
	}
	
	$level = pmpro_getMembershipLevelForUser( $current_user->ID );
	// If the user does not have a membership level, don't display anything.
	if( empty( $level ) ) {
		return;
	}
	
	// CSS Styling that changes link into a button.
	?>
		<style>
			a.pmpro-renew-button {
				background-color: #4CAF50;
				border: none;
				color: #fff;
				padding: 15px 32px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 16px; 
			}
		</style>
	<?php
	
	$level_id = $level->id;
	$url = add_query_arg( 'level', $level_id, get_permalink( $pmpro_pages['checkout'] ) );
	return '<a class="pmpro-renew-button" href="' . esc_url( $url ) . '">Renew Membership</a>';
	
}
add_shortcode( 'pmpro_renew_button', 'my_pmpro_renew_membership_shortcode' );

/**
 * Filter the settings of email frequency sent when using the Extra Expiration Warning Emails Add On
 * https://www.paidmembershipspro.com/add-ons/extra-expiration-warning-emails-add-on/
 *
 * Update the $settings array to your list of number of days => ''.
 * Read the Add On documentation for additional customization using this filter.
 */
function custom_pmproeewe_email_frequency( $settings = array() ) {
	$settings = array(
		10 => '',
		30 => '',
	/*	60 => '',  */
	);
	return $settings;
}
add_filter( 'pmproeewe_email_frequency_and_templates', 'custom_pmproeewe_email_frequency', 10, 1 );


/**
 * Merge tags for Mailchimp
* updated by Kerch 9-6-19
 *
 */
function teae_pmpro_mailchimp_listsubscribe_fields( $fields, $user ) {
    
    $region = get_user_meta($user->ID, 'region', true);
	
    //$region = bp_get_profile_field_data( array( 'field' => '149', 'user_id' => $user->ID ) );
        
		
	$new_fields =  array(
		"NAME" => $user->display_name,
		"REGION" => $user->region,
		"MEMBERSHIP_ENDDATE" => $user->expires

		);
$fields = array_merge($fields, $new_fields);
      
return $fields;
}
add_action( 'pmpro_mailchimp_listsubscribe_fields', 'teae_pmpro_mailchimp_listsubscribe_fields', 10, 2 );



/**
 * A simple gist that changes "Billing Address" to "Address".
 */
function teae_billing_address_text_change_strings( $translated_text, $text, $domain ) {	
	if ( $translated_text == 'Billing Address' && $domain == 'paid-memberships-pro' ) {
		$translated_text = 'Address';
	}
	return $translated_text;
}
add_filter( 'gettext', 'teae_billing_address_text_change_strings', 20, 3 );

/**
 * By default cancelled members are changed to level 0. This recipe changes that behavior to give them a "cancelled" level that
 * you have created for that purpose. Can be used to downgrade someone to a free level if they cancel.
 */
/**
 * [pmpro_after_change_membership_level_default_level description]
 *
 * @param  [type] $level_id [description]
 * @param  [type] $user_id  [description]
 * @return [type]           [description]
 */
function pmpro_after_change_membership_level_default_level( $level_id, $user_id ) {
	// if we see this global set, then another gist is planning to give the user their level back
	global $pmpro_next_payment_timestamp;
	if ( ! empty( $pmpro_next_payment_timestamp ) ) {
		return;
	}
	if ( ( $level_id == 0 ) && function_exists( 'pmpro_changeMembershipLevel' ) ) {
		// cancelling, give them level 9 instead
		pmpro_changeMembershipLevel( 9, $user_id );
	}
}

/*
*******************************************
** added by Joe 09FEB19 **
*******************************************
	Change currencies depending on Paid Memberships Pro level. 
	Add this code to your active theme's functions.php or a custom plugin. 
	This is just an example that will need to be tweaked for your needs.
	
	Other places to look into swapping currencies:
	* Levels page.
	* Invoices page.
	* In emails.
	* In membership levels table in admin.
	* When using discount codes.
*/

/*
	Global to store levels with non-default currencies
	Keys are level ids. Values are an asrray with the currency abbreviation and symbol as the first and second entries. 
*/
global $level_currencies;
$level_currencies = array(
/*		5 => array("EUR", "&euro;"),
		6 => array("GBP", "&pound;")
*/
		8 => array("CAD", "&dollar;")
);

//main function to check for a currency level and update currencies
function update_currency_per_level($level_id) {
	global $pmpro_currency, $pmpro_currency_symbol, $level_currencies;

	foreach($level_currencies as $level_currency_id => $level_currency) {
		if($level_id == $level_currency_id) {
			$pmpro_currency = $level_currency[0];
			$pmpro_currency_symbol = $level_currency[1];
		}
	}
}

//change currency on checkout page
function my_pmpro_checkout_level($level) {
	update_currency_per_level($level->id);

	return $level;
}
add_filter("pmpro_checkout_level", "my_pmpro_checkout_level");

//change currency when sent as a request param
function my_init_currency_check() {
	if(!empty($_REQUEST['level']))
		return update_currency_per_level(intval($_REQUEST['level']));
}
add_action("init", "my_init_currency_check");

//params in the admin
function my_admin_init_currency_check() {
	if(!empty($_REQUEST['edit']) && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-membershiplevels')
		return update_currency_per_level(intval($_REQUEST['edit']));
}
add_action("admin_init", "my_admin_init_currency_check");


/**  Added by Kerch 3-1-2020
 * This will show the renewal date link within the number of days or less than the members expiration that you set in the code gist below.
 * from here: https://www.paidmembershipspro.com/choose-when-to-display-the-renew-link-to-members-who-sign-up-for-a-membership-level-with-an-expiration-date/
 */
function show_renewal_link_after_X_days( $r, $level ) {

	if ( empty( $level->enddate ) ) {
		return false;
	}

	$days = 60; // Change this to value.

	// Are we within the days until expiration?
	$now = current_time( 'timestamp' );

	if ( $now + ( $days * 3600 * 24 ) >= $level->enddate ) {
		$r = true;
	} else {
		$r = false;
	}

	return $r;
}

add_filter( 'pmpro_is_level_expiring_soon', 'show_renewal_link_after_X_days', 10, 2 );





/**
 * Set Display Name on Membership Checkout to include Joint member name if applicable. 
 */
function teae_display_name_joint_members( $user_id, $morder ) {
    
    // Get user's first and last name.
    $first_name = get_user_meta( $user_id, 'first_name', true );
    $last_name = get_user_meta( $user_id, 'last_name', true );

    // Get meta information for user's joint member, if available.
    $joint_member_first_name = get_user_meta( $user_id, 'joint_member_first_name', true );
    $joint_member_second_name = get_user_meta( $user_id, 'joint_member_second_name', true );
    
    if ( ! empty( $joint_member_second_name ) && $joint_member_second_name != $last_name ) {
        // Unique joint member last name, set display name.
        $display_name = trim( $first_name . ' ' . $last_name . ' and ' . $joint_member_first_name . ' ' . $joint_member_second_name );
    } elseif ( ! empty( $joint_member_second_name ) && $joint_member_second_name == $last_name ) {
        // Last names are the same, set display name.
        $display_name = trim( $first_name . ' and ' . $joint_member_first_name . ' ' . $last_name );
    } elseif ( ! empty( $joint_member_first_name ) ) {
        // No custom joint member last name set, set display name.
        $display_name = trim( $first_name . ' and ' . $joint_member_first_name . ' ' . $last_name );
    } else {
        $display_name = trim( $first_name . ' ' . $last_name );
    }
    
    // Should set "display_name" as well as the BuddyPress Profile field name.    
    $args = array(
        'ID' => $user_id,
        'display_name' => $display_name,
    );
	
	
	// Update the user display_name and the BuddyPress XProfile 'Name' field.
    wp_update_user( $args ) ;
    xprofile_set_field_data( 1, $user_id, $display_name );
}
add_action( 'pmpro_after_checkout', 'teae_display_name_joint_members', 20, 2);

/**
 * Modify the Theme My Login widget title and user links displayed.
 *
 */
function modify_tml_widget_title( $title, $instance, $id_base ) {
	global $current_user, $pmpro_pages;
	if ( is_user_logged_in() && $id_base === 'theme-my-login' ) {
		$user_ID = $current_user->ID;
		if ( ! empty( $pmpro_pages ) ) {
			$account_page      = get_post( $pmpro_pages['account'] );
			$user_account_link = '<a href="' . esc_url( pmpro_url( 'account' ) ) . '">' . preg_replace( '/\@.*/', '', $current_user->display_name ) . '</a>';
		} else {
			$user_account_link = '<a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . preg_replace( '/\@.*/', '', $current_user->display_name ) . '</a>';
		}
		$title = sprintf( __( 'Welcome, %s', 'memberlite' ), $user_account_link );
	}
	return $title;
}
add_filter( 'widget_title', 'modify_tml_widget_title', 10, 3 );

// Remove all user_links added via the Theme My Login widget's default logic.
function hide_tml_widget_user_links( $links ) {
	global $current_user;
	if( ! empty( $current_user ) && function_exists( 'bp_core_get_user_domain' ) ) {
		$buddypress_profile_link = bp_core_get_user_domain( $current_user->ID );
	} else {
		$buddypress_profile_link = admin_url( 'profile.php' );
	}
	
	$links = array(
		'profile'   => array(
			'title' => __( 'Profile' ),
			'url'   => $buddypress_profile_link,
		),
		'logout'    => array(
			'title' => __( 'Log Out' ),
			'url'   => wp_logout_url(),
		),
	);
	return $links;
}
add_filter ('tml_widget_user_links', 'hide_tml_widget_user_links' );


/*force update to mailchimp on admin save */
add_filter('pmpromc_profile_update', '__return_true');


/*
 * The Code Recipe will add a 2-day grace period to a members membership once the membership expires.
 */

function my_pmpro_membership_post_membership_expiry( $user_id, $level_id ) {
	// Make sure we aren't already in a grace period for this level
	$grace_level = get_user_meta( $user_id, 'grace_level', true );
	if ( empty( $grace_level ) || $grace_level !== $level_id ) {
		// Give them their level back with 15 day expiration
		$grace_level                  = array();
		$grace_level['user_id'] = $user_id;
		$grace_level['membership_id'] = $level_id;
		$grace_level['enddate']       = date( 'Y-m-d H:i:s', strtotime( '+2 days', current_time( 'timestamp' ) ) );
		$changed = pmpro_changeMembershipLevel( $grace_level, $user_id );
		update_user_meta( $user_id, 'grace_level', $level_id );
	}
	else
		delete_user_meta($user_id, 'grace_level');
}

add_action('pmpro_membership_post_membership_expiry', 'my_pmpro_membership_post_membership_expiry', 10, 2);


/*
  This code handles loading a file from the /protected-directory/ directory.

  ###
  # BEGIN protected folder lock down
  <IfModule mod_rewrite.c>
  RewriteBase /
  RewriteRule ^members_only/(.*)$ /?pmpro_getfile=$1 [L]
  </IfModule>
  # END protected folder lock down
  ###
*/
define('PROTECTED_DIR', 'members_only');	//change this to the name of the folder to protect

function my_pmpro_getfile()
{
	if(isset($_REQUEST['pmpro_getfile']))
	{
		global $wpdb;
	
		//prevent loops when redirecting to .php files
		if(!empty($_REQUEST['noloop']))
		{
			status_header( 500 );
			die("This file cannot be loaded through the get file script.");
		}
	
		$uri = $_REQUEST['pmpro_getfile'];
		if(!empty($uri) && $uri[0] == "/")
			$uri = substr($uri, 1, strlen($uri) - 1);

		/*
		Remove ../-like strings from the URI.
		Actually removes any combination of two or more ., /, and \.
		This will prevent traversal attacks and loading hidden files.
		*/
		$uri = preg_replace("/[\.\/\\\\]{2,}/", "", $uri);
	
		//edit to point at your protected directory
		// changed from    $new_uri = MEMBERS . '/' . $uri;
	$new_uri = members_only . '/' . $uri;
		
		$filename = ABSPATH . $new_uri;
		$pathParts = pathinfo($filename);				
		
		//remove params from the end
		if(strpos($filename, "?") !== false)
		{
			$parts = explode("?", $filename);
			$filename = $parts[0];
		}
		
		//add index.html if this is a directory
		if(is_dir($filename))
			$filename .= "index.html";
				
		//only checking if the file is pulled from outside the admin
		if(!is_admin())
		{			
			//non-members don't have access (checks for level 2 or 3)
			if(!pmpro_hasMembershipLevel())
			{
				//nope				
				//header('HTTP/1.1 503 Service Unavailable', true, 503);
				//echo "HTTP/1.1 503 Service Unavailable";
				wp_redirect(wp_login_url());
				exit;
			}			
		}
		
		//get mimetype
		require_once(PMPRO_DIR . '/classes/class.mimetype.php');
		$mimetype = new pmpro_mimetype();       		
		$file_mimetype = $mimetype->getType($filename);
		
		//in case we want to do something else with the file
		do_action("pmpro_getfile_before_readfile", $filename, $file_mimetype);
		
		//if file is not found, die
		if(!file_exists($filename))
		{
			status_header( 404 );
	        	nocache_headers();        
	        	die("File not found.");
		}
		
		//if blacklistsed file type, redirect to it instead
		$basename = basename($filename);
		$parts = explode('.', $basename);
		$ext = strtolower($parts[count($parts)-1]);
		
		//build blacklist and allow for filtering
		$blacklist = array("inc", "php", "php3", "php4", "php5", "phps", "phtml");
		$blacklist = apply_filters("pmpro_getfile_extension_blacklist", $blacklist);
		
		//check
		if(in_array($ext, $blacklist))
		{		
			//add a noloop param to avoid infinite loops
			$uri = add_query_arg("noloop", 1, $uri);
			
			//guess scheme and add host back to uri
			if(is_ssl())
				$uri = "https://" . $_SERVER['HTTP_HOST'] . "/" . $uri;
			else
				$uri = "http://" . $_SERVER['HTTP_HOST'] . "/" . $uri;
					
			wp_redirect($uri);
			exit;
		}
			
		require_once(PMPRO_DIR . '/classes/class.mimetype.php');
		
		//okay show the file
		header("Content-type: " . $file_mimetype); 	
		readfile($filename);
		exit;
	}
}
add_action("init", "my_pmpro_getfile");


/** Sort alphabetical name listings by lastname */
function alphabetize_by_last_name( $bp_user_query ) {
    if ( 'alphabetical' == $bp_user_query->query_vars['type'] )
        $bp_user_query->uid_clauses['orderby'] = "ORDER BY substring_index(u.display_name, ' ', -1)";
}
add_action ( 'bp_pre_user_query', 'alphabetize_by_last_name' );



/**
 * Add the billable_invoice email template to the Email Templates Admin Editor.
 */
 
 function my_add_email_template_to_pmproet_add_on( $template ) {
    $template['billable_invoice'] = array(
        'subject' => "Your latest invoice",
        'description' => 'Billable Invoice'
    );

    return $template;
}
add_filter( 'pmproet_templates', 'my_add_email_template_to_pmproet_add_on' );

// Change Invoice to Receipt

add_filter( 'gettext', 'change_my_text_example', 20, 3 );
/**
 * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/gettext
 */
function change_my_text_example( $translated_text, $text, $domain ) {  

        switch ( $translated_text ) {

            case 'INVOICE' :

                $translated_text = __( 'RECEIPT', 'paid-memberships-pro' );
                break;
        }

    return $translated_text;
}


add_filter( 'gettext', 'change_text_for_add_first_last_name_to_checkout', 20, 3 );

function change_text_for_add_first_last_name_to_checkout( $translated_text, $text, $domain ) {
	switch ( $translated_text ) {
			case 'Last Name':
			$translated_text = __( 'Last Name: Add Joint Member Name Below', 'pmpro' );
				}
	return $translated_text;
}

/* add  !!membership_cost!!   to expiring soon email */

function add_membership_cost_to_emails($data, $email)
{
	global $current_user;
	
	if(!isset($data['membership_cost']))
	{
		$data['membership_cost'] = pmpro_getLevelCost($current_user->membership_level);
	}
	
	return $data;
}

add_filter('pmpro_email_data', 'add_membership_cost_to_emails', 10, 2);


/**
 * This recipe creates new email variables for the billable_invoice.html
 * These fields will call the user id from the order to populate the !!receipt_username!! and !!receipt_enddate!!
 * so it is not the admins. Also added !!name!!
 *  many thanks to @dparker1005 who wrote most of this.
 * You can add this recipe to your site by creating a custom plugin
 * or using the Code Snippets plugin available for free in the WordPress repository.
 * Read this companion article for step-by-step directions on either method.
 * https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */

function my_pmpro_email_data($data, $email) {	

    if ( 'billable_invoice' === $email->template){
    
    $order = new MemberOrder( $data['order_code'] );
    $user_id = $order->user_id;

    $user = get_userdata( $user_id ); 
    $username = $user->user_login;
    $firstname = $user->first_name;
    $level = pmpro_getMembershipLevelForUser( $user_id ); 
    $enddate = $level->enddate;


   // $data['user_id'] = $userid; 

    $data['name'] = $firstname;

    $data['receipt_username'] = $username;

    if(!empty($level->enddate))
         $data['receipt_enddate'] = date_i18n(get_option('date_format'), $enddate);
    else
        $data['receipt_enddate'] = 'Reoccurring'; // Can be changed
    }
    return $data;
}
add_filter("pmpro_email_data", "my_pmpro_email_data", 10, 2);