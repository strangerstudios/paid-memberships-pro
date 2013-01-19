<?php
/*
	These functions below handle DB upgrades, etc
*/
function pmpro_checkForUpgrades()
{
	$pmpro_db_version = pmpro_getOption("db_version");
	
	//if we can't find the DB tables, reset db_version to 0
	global $wpdb, $table_prefix;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_levels = $table_prefix . 'pmpro_membership_levels';
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
}

function pmpro_upgrade_1_5_9()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_membership_orders = $wpdb->prefix . 'pmpro_membership_orders';

	//fix firstpayment statuses
	$sqlQuery = "UPDATE " . $wpdb->pmpro_membership_orders . " SET status = 'success' WHERE status = 'firstpayment'";
	$wpdb->query($sqlQuery);
	
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
		ALTER TABLE  `" . $wpdb->pmpro_membership_levels . "` ADD INDEX (  `enddate` )
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
	$nonmembertext = "This content is for !!levels!! members only. <a href=\"" . wp_login_url() . "?action=register\">Register here</a>.";
	pmpro_setOption("nonmembertext", $nonmembertext);
	
	$notloggedintext = "This content is for !!levels!! members only. Please <a href=\"" . wp_login_url( get_permalink() ) . "\">login</a> to view this content. (<a href=\"" . wp_login_url() . "?action=register\">Register here</a>.)";
	pmpro_setOption("notloggedintext", $notloggedintext);
	
	$rsstext = "This content is for !!levels!! members only. Visit the site and log in/register to read.";
	pmpro_setOption("rsstext", $rsstext);
	
	$gateway_environment = "sandbox";
	pmpro_setOption("gateway_environment", $gateway_environment);
	
	$pmpro_currency = "USD";
	pmpro_setOption("currency", $pmpro_currency);
	
	$pmpro_accepted_credit_cards = "Visa,Mastercard,American Express,Discover";
	pmpro_setOption("accepted_credit_cards", $pmpro_accepted_credit_cards);		
	
	$parsed = parse_url(home_url()); 
	$hostname = $parsed[host];
	$hostparts = split("\.", $hostname);				
	$email_domain = $hostparts[count($hostparts) - 2] . "." . $hostparts[count($hostparts) - 1];		
	$from_email = "wordpress@" . $email_domain;
	pmpro_setOption("from_email", $from_email);
	
	$from_name = "WordPress";
	pmpro_setOption("from_name", $from_name);		
	
	pmpro_setOption("tospage", "");			
	
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
	
	//wp_pmpro_membership_levels
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_membership_levels . "` (
		  `id` int(11) UNSIGNED NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL,
		  `description` longtext NOT NULL,
		  `initial_payment` decimal(10,2) NOT NULL default '0.00',
		  `billing_amount` decimal(10,2) NOT NULL default '0.00',
		  `cycle_number` int(11) NOT NULL default '0',
		  `cycle_period` enum('Day','Week','Month','Year') default 'Month',
		  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
		  `trial_amount` decimal(10,2) NOT NULL default '0.00',
		  `trial_limit` int(11) NOT NULL default '0',		  
		  `allow_signups` tinyint(4) NOT NULL default '1',
		  PRIMARY KEY  (`id`),
		  KEY `allow_signups` (`allow_signups`),
		  KEY `initial_payment` (`initial_payment`),
		  KEY `name` (`name`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8
	";
	$wpdb->query($sqlQuery);
	
	//wp_pmpro_membership_orders
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_membership_orders . "` (
		  `id` int(11) UNSIGNED NOT NULL auto_increment,
		  `code` varchar(10) NOT NULL,
		  `session_id` varchar(64) NOT NULL default '',
		  `user_id` int(11) UNSIGNED NOT NULL default '0',
		  `membership_id` int(11) UNSIGNED NOT NULL default '0',
		  `paypal_token` varchar(64) NOT NULL default '',
		  `billing_name` varchar(128) NOT NULL default '',
		  `billing_street` varchar(128) NOT NULL default '',
		  `billing_city` varchar(128) NOT NULL default '',
		  `billing_state` varchar(32) NOT NULL default '',
		  `billing_zip` varchar(16) NOT NULL default '',
		  `billing_phone` varchar(32) NOT NULL,
		  `subtotal` varchar(16) NOT NULL default '',
		  `tax` varchar(16) NOT NULL default '',
		  `couponamount` varchar(16) NOT NULL default '',
		  `certificate_id` int(11) NOT NULL default '0',
		  `certificateamount` varchar(16) NOT NULL default '',
		  `total` varchar(16) NOT NULL default '',
		  `payment_type` varchar(64) NOT NULL default '',
		  `cardtype` varchar(32) NOT NULL default '',
		  `accountnumber` varchar(32) NOT NULL default '',
		  `expirationmonth` char(2) NOT NULL default '',
		  `expirationyear` varchar(4) NOT NULL default '',
		  `status` varchar(32) NOT NULL default '',
		  `gateway` varchar(64) NOT NULL,
		  `gateway_environment` varchar(64) NOT NULL,
		  `payment_transaction_id` varchar(64) NOT NULL,
		  `subscription_transaction_id` varchar(32) NOT NULL,
		  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
		  `affiliate_id` varchar(32) NOT NULL,
		  `affiliate_subid` varchar(32) NOT NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `code` (`code`),
		  KEY `session_id` (`session_id`),
		  KEY `user_id` (`user_id`),
		  KEY `membership_id` (`membership_id`),
		  KEY `timestamp` (`timestamp`),
		  KEY `gateway` (`gateway`),
		  KEY `gateway_environment` (`gateway_environment`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$wpdb->query($sqlQuery);
	
	//wp_pmpro_memberships_categories
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_memberships_categories . "` (
		  `membership_id` int(11) UNSIGNED NOT NULL,
		  `category_id` int(11) UNSIGNED NOT NULL,
		  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  UNIQUE KEY `membership_category` (`membership_id`,`category_id`),
		  UNIQUE KEY `category_membership` (`category_id`,`membership_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$wpdb->query($sqlQuery);
	
	//wp_pmpro_memberships_pages
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_memberships_pages . "` (
		  `membership_id` int(11) UNSIGNED NOT NULL,
		  `page_id` int(11) UNSIGNED NOT NULL,
		  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  UNIQUE KEY `category_membership` (`page_id`,`membership_id`),
		  UNIQUE KEY `membership_page` (`membership_id`,`page_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$wpdb->query($sqlQuery);
	
	//wp_pmpro_memberships_users
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_memberships_users . "` (
		  `user_id` int(11) UNSIGNED NOT NULL,
		  `membership_id` int(11) UNSIGNED NOT NULL,
		  `code_id` int(11) UNSIGNED NOT NULL,
		  `initial_payment` decimal(10,2) NOT NULL,
		  `billing_amount` decimal(10,2) NOT NULL,
		  `cycle_number` int(11) NOT NULL,
		  `cycle_period` enum('Day','Week','Month','Year') NOT NULL default 'Month',
		  `billing_limit` int(11) NOT NULL,
		  `trial_amount` decimal(10,2) NOT NULL,
		  `trial_limit` int(11) NOT NULL,		  
		  `startdate` datetime NOT NULL,
		  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  PRIMARY KEY  (`user_id`),
		  KEY `membership_id` (`membership_id`),
		  KEY `modified` (`modified`),
		  KEY `code_id` (`code_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$wpdb->query($sqlQuery);		
	
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
	return 1.115;
}