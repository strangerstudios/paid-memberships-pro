-- phpMyAdmin SQL Dump
-- version 2.8.2.4
-- http://www.phpmyadmin.net
-- 
-- Host: localhost:3306
-- Generation Time: Mar 23, 2011 at 09:58 AM
-- Server version: 5.0.77
-- PHP Version: 5.2.6
-- 
-- Database: `host_pmpro_main`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `wp_pmpro_membership_levels`
-- 

CREATE TABLE `wp_pmpro_membership_levels` (
  `id` int(11) NOT NULL auto_increment,
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `wp_pmpro_membership_orders`
-- 

CREATE TABLE `wp_pmpro_membership_orders` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(10) NOT NULL,
  `session_id` varchar(64) NOT NULL default '',
  `user_id` int(11) NOT NULL default '0',
  `membership_id` int(11) NOT NULL default '0',
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

-- --------------------------------------------------------

-- 
-- Table structure for table `wp_pmpro_memberships_categories`
-- 

CREATE TABLE `wp_pmpro_memberships_categories` (
  `membership_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `membership_category` (`membership_id`,`category_id`),
  UNIQUE KEY `category_membership` (`category_id`,`membership_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `wp_pmpro_memberships_pages`
-- 

CREATE TABLE `wp_pmpro_memberships_pages` (
  `membership_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `category_membership` (`page_id`,`membership_id`),
  UNIQUE KEY `membership_page` (`membership_id`,`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `wp_pmpro_memberships_users`
-- 

CREATE TABLE `wp_pmpro_memberships_users` (
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
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
  KEY `modified` (`modified`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
