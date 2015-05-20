<?php
/*
	These functions below handle DB upgrades, etc
*/
function pmpro_checkForUpgrades()
{
	$pmpro_db_version = pmpro_getOption("db_version");
	
	//if we can't find the DB tables, reset db_version to 0
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';
	$table_exists = $wpdb->query("SHOW TABLES LIKE '" . $wpdb->pmpro_membership_levels . "'");	
	if(!$table_exists)		
		$pmpro_db_version = 0;
	
	if(!$pmpro_db_version)
		$pmpro_db_version = pmpro_upgrade_1();	
	
	if($pmpro_db_version < 1.115)
		$pmpro_db_version = pmpro_upgrade_1_1_15();		
	
	if($pmpro_db_version < 1.23)
		$pmpro_db_version = pmpro_upgrade_1_2_3();	
	
	if($pmpro_db_version < 1.318)
		$pmpro_db_version = pmpro_upgrade_1_3_18();
	
	if($pmpro_db_version < 1.4)
		$pmpro_db_version = pmpro_upgrade_1_4();
		
	if($pmpro_db_version < 1.42)
		$pmpro_db_version = pmpro_upgrade_1_4_2();
		
	if($pmpro_db_version < 1.48)
		$pmpro_db_version = pmpro_upgrade_1_4_8();

	if($pmpro_db_version < 1.5)
		$pmpro_db_version = pmpro_upgrade_1_5();
		
	if($pmpro_db_version < 1.59)
		$pmpro_db_version = pmpro_upgrade_1_5_9();
		
	if($pmpro_db_version < 1.6)
		$pmpro_db_version = pmpro_upgrade_1_6();
	
	//fix for fresh 1.7 installs
	if($pmpro_db_version == 1.7)
	{
		//check if we have an id column in the memberships_users table
		$wpdb->pmpro_memberships_users = $wpdb->prefix . 'pmpro_memberships_users';
		$col = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_memberships_users LIMIT 1");		
		if($wpdb->last_error == "Unknown column 'id' in 'field list'")
		{			
			//redo 1.5 fix
			pmpro_upgrade_1_5();
		}
		
		pmpro_db_delta();
		
		pmpro_setOption("db_version", "1.703");
		$pmpro_db_version = 1.703;
	}
		
	//updates from this point on should be like this if DB only
	if($pmpro_db_version < 1.71)
	{
		pmpro_db_delta();
		pmpro_setOption("db_version", "1.71");
		$pmpro_db_version = 1.71;
	}
	
	if($pmpro_db_version < 1.72)
	{		
		//schedule the credit card expiring cron
		wp_schedule_event(current_time('timestamp'), 'monthly', 'pmpro_cron_credit_card_expiring_warnings');
		
		pmpro_setOption("db_version", "1.72");
		$pmpro_db_version = 1.72;
	}
	
	/*
		1.7.3
		- default Stripe Billing Fields to true
		- unless Stripe Lite is activated, then deactivate Stripe Lite and set Stripe Billing Fields to false
	*/
	
	if($pmpro_db_version < 1.79)
	{
		//need to register caps for menu
		pmpro_activation();
		
		pmpro_setOption("db_version", "1.79");
		$pmpro_db_version = 1.79;
	}
	
	//set default filter_queries setting
	if($pmpro_db_version < 1.791)
	{
		if(!pmpro_getOption("showexcerpts"))
			pmpro_setOption("filterqueries", 1);
		else
			pmpro_SetOption("filterqueries", 0);
			
		pmpro_setOption("db_version", "1.791");
		$pmpro_db_version = 1.791;
	}
}

function pmpro_upgrade_1_7()
{
	pmpro_db_delta();	//just a db delta

	pmpro_setOption("db_version", "1.7");
	return 1.7;
}

function pmpro_upgrade_1_6()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';
	
	//add notes column to orders
	$sqlQuery = "ALTER TABLE  `" . $wpdb->pmpro_membership_orders . "` ADD  `notes` TEXT NOT NULL";
	$wpdb->query($sqlQuery);
	
	pmpro_setOption("db_version", "1.6");
	return 1.6;
}

function pmpro_upgrade_1_5_9()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';

	//fix firstpayment statuses
	$sqlQuery = "UPDATE " . $wpdb->pmpro_membership_orders . " SET status = 'success' WHERE status = 'firstpayment'";
	$wpdb->query($sqlQuery);
	
	pmpro_setOption("db_version", "1.59");
	return 1.59;
}

function pmpro_upgrade_1_5()
{
	/*
		Add the id and status fields to pmpro_memberships_users, change primary key to id instead of user_id
	*/

	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_memberships_users = $wpdb->prefix . 'pmpro_memberships_users';

	//remove primary key
	$sqlQuery = "ALTER TABLE `" . $wpdb->pmpro_memberships_users . "` DROP PRIMARY KEY";
	$wpdb->query($sqlQuery);

	//id
	$sqlQuery = "ALTER TABLE `" . $wpdb->pmpro_memberships_users . "` ADD  `id` BIGINT( 20 ) UNSIGNED AUTO_INCREMENT FIRST, ADD PRIMARY KEY(id)";
	$wpdb->query($sqlQuery);

	//status
	$sqlQuery = "ALTER TABLE `" . $wpdb->pmpro_memberships_users . "` ADD  `status` varchar( 20 ) NOT NULL DEFAULT 'active' AFTER `trial_limit`";
	$wpdb->query($sqlQuery);

	pmpro_setOption("db_version", "1.5");
	return 1.5;
}

function pmpro_upgrade_1_4_8()
{
	/*
		Adding a billing_country field to the orders table.		
	*/
	
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';
	
	//billing_country
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_orders . "` ADD  `billing_country` VARCHAR( 128 ) NOT NULL AFTER  `billing_zip`
	";
	$wpdb->query($sqlQuery);
	
	pmpro_setOption("db_version", "1.48");
	return 1.48;
}

function pmpro_upgrade_1_4_2()
{
	/*
		Setting the new use_ssl setting.
		PayPal Website Payments Pro, Authorize.net, and Stripe will default to use ssl.
		PayPal Express and the test gateway (no gateway) will default to not use ssl.
	*/
	$gateway = pmpro_getOption("gateway");
	if($gateway == "paypal" || $gateway == "authorizenet" || $gateway == "stripe")
		pmpro_setOption("use_ssl", 1);
	else
		pmpro_setOption("use_ssl", 0);
		
	pmpro_setOption("db_version", "1.42");
	return 1.42;
}

function pmpro_upgrade_1_4()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';
	
	//confirmation message
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` ADD  `confirmation` LONGTEXT NOT NULL AFTER  `description`
	";
	$wpdb->query($sqlQuery);
	
	pmpro_setOption("db_version", "1.4");
	return 1.4;
}

function pmpro_upgrade_1_3_18()
{
	//setting new email settings defaults
	pmpro_setOption("email_admin_checkout", "1");
	pmpro_setOption("email_admin_changes", "1");
	pmpro_setOption("email_admin_cancels", "1");
	pmpro_setOption("email_admin_billing", "1");

	pmpro_setOption("db_version", "1.318");	
	return 1.318;
}

function pmpro_upgrade_1_2_3()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';
	$wpdb->pmpro_memberships_users = $wpdb->prefix . 'pmpro_memberships_users';
	$wpdb->pmpro_memberships_categories = $wpdb->prefix . 'pmpro_memberships_categories';
	$wpdb->pmpro_memberships_pages = $wpdb->prefix . 'pmpro_memberships_pages';
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';
	$wpdb->pmpro_discount_codes = $wpdb->prefix . 'pmpro_discount_codes';
	$wpdb->pmpro_discount_codes_levels = $wpdb->prefix . 'pmpro_discount_codes_levels';
	$wpdb->pmpro_discount_codes_uses = $wpdb->prefix . 'pmpro_discount_codes_uses';	
	
	//expiration number and period for levels
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` ADD  `expiration_number` INT UNSIGNED NOT NULL ,
ADD  `expiration_period` ENUM(  'Day',  'Week',  'Month',  'Year' ) NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	//expiration number and period for discount code levels
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_discount_codes_levels . "` ADD  `expiration_number` INT UNSIGNED NOT NULL ,
ADD  `expiration_period` ENUM(  'Day',  'Week',  'Month',  'Year' ) NOT NULL
	";
	$wpdb->query($sqlQuery);		
	
	//end date for members
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` ADD  `enddate` DATETIME NULL AFTER  `startdate`
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` ADD INDEX (  `enddate` )
	";
	$wpdb->query($sqlQuery);
	
	pmpro_setOption("db_version", "1.23");	
	return 1.23;
}

function pmpro_upgrade_1_1_15()
{	
	/*
		DB table setup	
	*/
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';
	$wpdb->pmpro_memberships_users = $wpdb->prefix . 'pmpro_memberships_users';
	$wpdb->pmpro_memberships_categories = $wpdb->prefix . 'pmpro_memberships_categories';
	$wpdb->pmpro_memberships_pages = $wpdb->prefix . 'pmpro_memberships_pages';
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';
	$wpdb->pmpro_discount_codes = $wpdb->prefix . 'pmpro_discount_codes';
	$wpdb->pmpro_discount_codes_levels = $wpdb->prefix . 'pmpro_discount_codes_levels';
	$wpdb->pmpro_discount_codes_uses = $wpdb->prefix . 'pmpro_discount_codes_uses';	
	
	/*
		Changing some id columns to unsigned.			
	*/
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_categories . "` CHANGE  `membership_id`  `membership_id` INT( 11 ) UNSIGNED NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_categories . "` CHANGE  `category_id`  `category_id` INT( 11 ) UNSIGNED NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_pages . "` CHANGE  `membership_id`  `membership_id` INT( 11 ) UNSIGNED NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_pages . "` CHANGE  `page_id`  `page_id` INT( 11 ) UNSIGNED NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` CHANGE  `user_id`  `user_id`  INT( 11 ) UNSIGNED NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` CHANGE  `membership_id`  `membership_id` INT( 11 ) UNSIGNED NOT NULL
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_orders . "` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_orders . "` CHANGE  `user_id`  `user_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0'
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_membership_orders . "` CHANGE  `membership_id`  `membership_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0'
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` ADD  `code_id` INT UNSIGNED NOT NULL AFTER  `membership_id` ;
	";
	$wpdb->query($sqlQuery);
	
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_memberships_users . "` ADD INDEX (  `code_id` )
	";
	$wpdb->query($sqlQuery);		
	
	/*
		New tables for discount codes
	*/
	
	//wp_pmpro_discount_codes
	$sqlQuery = "		
		CREATE TABLE `" . $wpdb->pmpro_discount_codes . "` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `code` varchar(32) NOT NULL,
		  `starts` date NOT NULL,
		  `expires` date NOT NULL,
		  `uses` int(11) NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `code` (`code`),
		  KEY `starts` (`starts`),
		  KEY `expires` (`expires`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$wpdb->query($sqlQuery);	

	//wp_pmpro_discount_codes_levels
	$sqlQuery = "		
		CREATE TABLE `" . $wpdb->pmpro_discount_codes_levels . "` (
		  `code_id` int(11) unsigned NOT NULL,
		  `level_id` int(11) unsigned  NOT NULL,
		  `initial_payment` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `billing_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `cycle_number` int(11) NOT NULL DEFAULT '0',
		  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
		  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
		  `trial_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `trial_limit` int(11) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`code_id`,`level_id`),
		  KEY `initial_payment` (`initial_payment`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$wpdb->query($sqlQuery);	

	//wp_pmpro_discount_codes_uses
	$sqlQuery = "		
		CREATE TABLE `" . $wpdb->pmpro_discount_codes_uses . "` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `code_id` int(10) unsigned NOT NULL,
		  `user_id` int(10) unsigned NOT NULL,
		  `order_id` int(10) unsigned NOT NULL,
		  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  KEY `user_id` (`user_id`),
		  KEY `timestamp` (`timestamp`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
	";
	$wpdb->query($sqlQuery);
	
	pmpro_setOption("db_version", "1.115");
	
	//do the next update
	return pmpro_upgrade_1_2_2();
}

function pmpro_upgrade_1()
{		
	/*
		default options
	*/
	$nonmembertext = sprintf( __( 'This content is for !!levels!! members only.<br /><a href="%s">Register</a>', 'pmpro' ), wp_login_url() . "?action=register" );
	pmpro_setOption("nonmembertext", $nonmembertext);
	
	$notloggedintext = sprintf( __( 'This content is for !!levels!! members only.<br /><a href="%s">Log In</a> <a href="%s">Register</a>', 'pmpro' ), wp_login_url(), wp_login_url() . "?action=register" );
	'?action=register">Register</a>';
	pmpro_setOption("notloggedintext", $notloggedintext);
	
	$rsstext = __( "This content is for !!levels!! members only. Visit the site and log in/register to read.", 'pmpro' );
	pmpro_setOption("rsstext", $rsstext);
	
	$gateway_environment = "sandbox";
	pmpro_setOption("gateway_environment", $gateway_environment);
	
	$pmpro_currency = "USD";
	pmpro_setOption("currency", $pmpro_currency);
	
	$pmpro_accepted_credit_cards = "Visa,Mastercard,American Express,Discover";
	pmpro_setOption("accepted_credit_cards", $pmpro_accepted_credit_cards);		
	
	$parsed = parse_url(home_url()); 
	$hostname = $parsed['host'];
	$hostparts = explode(".", $hostname);
	$email_domain = $hostparts[count($hostparts) - 2] . "." . $hostparts[count($hostparts) - 1];		
	$from_email = "wordpress@" . $email_domain;
	pmpro_setOption("from_email", $from_email);
	
	$from_name = "WordPress";
	pmpro_setOption("from_name", $from_name);		
	
	//setting new email settings defaults
	pmpro_setOption("email_admin_checkout", "1");
	pmpro_setOption("email_admin_changes", "1");
	pmpro_setOption("email_admin_cancels", "1");
	pmpro_setOption("email_admin_billing", "1");
	
	pmpro_setOption("tospage", "");			
	
	//db update
	pmpro_db_delta();	
	
	//update version and return
	pmpro_setOption("db_version", "1.702");		//no need to run other updates
	return 1.702;
}

function pmpro_db_delta()
{
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $wpdb->prefix . 'pmpro_membership_levels';
	$wpdb->pmpro_memberships_users = $wpdb->prefix . 'pmpro_memberships_users';
	$wpdb->pmpro_memberships_categories = $wpdb->prefix . 'pmpro_memberships_categories';
	$wpdb->pmpro_memberships_pages = $wpdb->prefix . 'pmpro_memberships_pages';
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';
	$wpdb->pmpro_discount_codes = $wpdb->prefix . 'pmpro_discount_codes';
	$wpdb->pmpro_discount_codes_levels = $wpdb->prefix . 'pmpro_discount_codes_levels';
	$wpdb->pmpro_discount_codes_uses = $wpdb->prefix . 'pmpro_discount_codes_uses';	
	
	//wp_pmpro_membership_levels
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_membership_levels . "` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `description` longtext NOT NULL,
		  `confirmation` longtext NOT NULL,
		  `initial_payment` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `billing_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `cycle_number` int(11) NOT NULL DEFAULT '0',
		  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
		  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
		  `trial_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `trial_limit` int(11) NOT NULL DEFAULT '0',
		  `allow_signups` tinyint(4) NOT NULL DEFAULT '1',
		  `expiration_number` int(10) unsigned NOT NULL,
		  `expiration_period` enum('Day','Week','Month','Year') NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `allow_signups` (`allow_signups`),
		  KEY `initial_payment` (`initial_payment`),
		  KEY `name` (`name`)
		);
	";
	dbDelta($sqlQuery);
	
	//wp_pmpro_membership_orders
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_membership_orders . "` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `code` varchar(10) NOT NULL,
		  `session_id` varchar(64) NOT NULL DEFAULT '',
		  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `membership_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `paypal_token` varchar(64) NOT NULL DEFAULT '',
		  `billing_name` varchar(128) NOT NULL DEFAULT '',
		  `billing_street` varchar(128) NOT NULL DEFAULT '',
		  `billing_city` varchar(128) NOT NULL DEFAULT '',
		  `billing_state` varchar(32) NOT NULL DEFAULT '',
		  `billing_zip` varchar(16) NOT NULL DEFAULT '',
		  `billing_country` varchar(128) NOT NULL,
		  `billing_phone` varchar(32) NOT NULL,
		  `subtotal` varchar(16) NOT NULL DEFAULT '',
		  `tax` varchar(16) NOT NULL DEFAULT '',
		  `couponamount` varchar(16) NOT NULL DEFAULT '',
		  `certificate_id` int(11) NOT NULL DEFAULT '0',
		  `certificateamount` varchar(16) NOT NULL DEFAULT '',
		  `total` varchar(16) NOT NULL DEFAULT '',
		  `payment_type` varchar(64) NOT NULL DEFAULT '',
		  `cardtype` varchar(32) NOT NULL DEFAULT '',
		  `accountnumber` varchar(32) NOT NULL DEFAULT '',
		  `expirationmonth` char(2) NOT NULL DEFAULT '',
		  `expirationyear` varchar(4) NOT NULL DEFAULT '',
		  `status` varchar(32) NOT NULL DEFAULT '',
		  `gateway` varchar(64) NOT NULL,
		  `gateway_environment` varchar(64) NOT NULL,
		  `payment_transaction_id` varchar(64) NOT NULL,
		  `subscription_transaction_id` varchar(32) NOT NULL,
		  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `affiliate_id` varchar(32) NOT NULL,
		  `affiliate_subid` varchar(32) NOT NULL,
		  `notes` TEXT NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `code` (`code`),
		  KEY `session_id` (`session_id`),
		  KEY `user_id` (`user_id`),
		  KEY `membership_id` (`membership_id`),
		  KEY `status` (`status`),
		  KEY `timestamp` (`timestamp`),
		  KEY `gateway` (`gateway`),
		  KEY `gateway_environment` (`gateway_environment`),
		  KEY `payment_transaction_id` (`payment_transaction_id`),
		  KEY `subscription_transaction_id` (`subscription_transaction_id`),
		  KEY `affiliate_id` (`affiliate_id`),
		  KEY `affiliate_subid` (`affiliate_subid`)
		);
	";
	dbDelta($sqlQuery);
	
	//wp_pmpro_memberships_categories
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_memberships_categories . "` (
		  `membership_id` int(11) unsigned NOT NULL,
		  `category_id` int(11) unsigned NOT NULL,
		  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  UNIQUE KEY `membership_category` (`membership_id`,`category_id`),
		  UNIQUE KEY `category_membership` (`category_id`,`membership_id`)
		);
	";
	dbDelta($sqlQuery);
	
	//wp_pmpro_memberships_pages
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_memberships_pages . "` (
		  `membership_id` int(11) unsigned NOT NULL,
		  `page_id` int(11) unsigned NOT NULL,
		  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  UNIQUE KEY `category_membership` (`page_id`,`membership_id`),
		  UNIQUE KEY `membership_page` (`membership_id`,`page_id`)
		);
	";
	dbDelta($sqlQuery);
	
	//wp_pmpro_memberships_users
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_memberships_users . "` (
		   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		   `user_id` int(11) unsigned NOT NULL,
		   `membership_id` int(11) unsigned NOT NULL,
		   `code_id` int(11) unsigned NOT NULL,
		   `initial_payment` decimal(10,2) NOT NULL,
		   `billing_amount` decimal(10,2) NOT NULL,
		   `cycle_number` int(11) NOT NULL,
		   `cycle_period` enum('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
		   `billing_limit` int(11) NOT NULL,
		   `trial_amount` decimal(10,2) NOT NULL,
		   `trial_limit` int(11) NOT NULL,
		   `status` varchar(20) NOT NULL DEFAULT 'active',
		   `startdate` datetime NOT NULL,
		   `enddate` datetime DEFAULT NULL,
		   `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		   PRIMARY KEY (`id`),
		   KEY `membership_id` (`membership_id`),
		   KEY `modified` (`modified`),
		   KEY `code_id` (`code_id`),
		   KEY `enddate` (`enddate`),
		   KEY `user_id` (`user_id`),
		   KEY `status` (`status`)
		);
	";
	dbDelta($sqlQuery);		
	
	//wp_pmpro_discount_codes
	$sqlQuery = "		
		CREATE TABLE `" . $wpdb->pmpro_discount_codes . "` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `code` varchar(32) NOT NULL,
		  `starts` date NOT NULL,
		  `expires` date NOT NULL,
		  `uses` int(11) NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `code` (`code`),
		  KEY `starts` (`starts`),
		  KEY `expires` (`expires`)
		);
	";
	dbDelta($sqlQuery);	

	//wp_pmpro_discount_codes_levels
	$sqlQuery = "		
		CREATE TABLE `" . $wpdb->pmpro_discount_codes_levels . "` (
		  `code_id` int(11) unsigned NOT NULL,
		  `level_id` int(11) unsigned NOT NULL,
		  `initial_payment` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `billing_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `cycle_number` int(11) NOT NULL DEFAULT '0',
		  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
		  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
		  `trial_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `trial_limit` int(11) NOT NULL DEFAULT '0',
		  `expiration_number` int(10) unsigned NOT NULL,
		  `expiration_period` enum('Day','Week','Month','Year') NOT NULL,
		  PRIMARY KEY (`code_id`,`level_id`),
		  KEY `initial_payment` (`initial_payment`)
		);
	";
	dbDelta($sqlQuery);	

	//wp_pmpro_discount_codes_uses
	$sqlQuery = "		
		CREATE TABLE `" . $wpdb->pmpro_discount_codes_uses . "` (		  
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `code_id` int(10) unsigned NOT NULL,
		  `user_id` int(10) unsigned NOT NULL,
		  `order_id` int(10) unsigned NOT NULL,
		  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  KEY `user_id` (`user_id`),
		  KEY `timestamp` (`timestamp`)
		);
	";
	dbDelta($sqlQuery);
}