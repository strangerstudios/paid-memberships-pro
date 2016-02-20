<?php
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
	pmpro_setOption("db_version", "1.71");		//no need to run other updates
	return 1.71;
}