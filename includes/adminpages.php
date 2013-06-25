<?php
/*
	Dashboard Menu
*/
function pmpro_add_pages()
{
	global $wpdb;

	add_menu_page(__('Memberships', 'pmpro'), __('Memberships', 'pmpro'), 'manage_options', 'pmpro-membershiplevels', 'pmpro_membershiplevels', PMPRO_URL . '/images/menu_users.png');
	add_submenu_page('pmpro-membershiplevels', __('Page Settings', 'pmpro'), __('Page Settings', 'pmpro'), 'manage_options', 'pmpro-pagesettings', 'pmpro_pagesettings');
	add_submenu_page('pmpro-membershiplevels', __('Payment Settings', 'pmpro'), __('Payment Settings', 'pmpro'), 'manage_options', 'pmpro-paymentsettings', 'pmpro_paymentsettings');
	add_submenu_page('pmpro-membershiplevels', __('Email Settings', 'pmpro'), __('Email Settings', 'pmpro'), 'manage_options', 'pmpro-emailsettings', 'pmpro_emailsettings');
	add_submenu_page('pmpro-membershiplevels', __('Advanced Settings', 'pmpro'), __('Advanced Settings', 'pmpro'), 'manage_options', 'pmpro-advancedsettings', 'pmpro_advancedsettings');
	add_submenu_page('pmpro-membershiplevels', __('Add Ons', 'pmpro'), __('Add Ons', 'pmpro'), 'manage_options', 'pmpro-addons', 'pmpro_addons');
	add_submenu_page('pmpro-membershiplevels', __('Members List', 'pmpro'), __('Members List', 'pmpro'), 'manage_options', 'pmpro-memberslist', 'pmpro_memberslist');
	add_submenu_page('pmpro-membershiplevels', __('Reports', 'pmpro'), __('Reports', 'pmpro'), 'manage_options', 'pmpro-reports', 'pmpro_reports');	
	add_submenu_page('pmpro-membershiplevels', __('Orders', 'pmpro'), __('Orders', 'pmpro'), 'manage_options', 'pmpro-orders', 'pmpro_orders');
	add_submenu_page('pmpro-membershiplevels', __('Discount Codes', 'pmpro'), __('Discount Codes', 'pmpro'), 'manage_options', 'pmpro-discountcodes', 'pmpro_discountcodes');		
	
	//rename the automatically added Memberships submenu item
	global $submenu;
	if(!empty($submenu['pmpro-membershiplevels']))
	{
		$submenu['pmpro-membershiplevels'][0][0] = "Membership Levels";
		$submenu['pmpro-membershiplevels'][0][3] = "Membership Levels";
	}
}
add_action('admin_menu', 'pmpro_add_pages');

/*
	Admin Bar
*/
function pmpro_admin_bar_menu() {
	global $wp_admin_bar;
	if ( !is_super_admin() || !is_admin_bar_showing() )
		return;
	$wp_admin_bar->add_menu( array(
	'id' => 'paid-memberships-pro',
	'title' => __( 'Memberships', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-membershiplevels') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-membership-levels',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Membership Levels', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-membershiplevels') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-page-settings',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Page Settings', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-pagesettings') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-payment-settings',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Payment Settings', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-paymentsettings') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-email-settings',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Email Settings', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-emailsettings') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-advanced-settings',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Advanced Settings', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-advancedsettings') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-addons',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Add Ons', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-addons') ) );	
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-members-list',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Members List', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-memberslist') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-orders',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Orders', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-orders') ) );
	$wp_admin_bar->add_menu( array(
	'id' => 'pmpro-discount-codes',
	'parent' => 'paid-memberships-pro',
	'title' => __( 'Discount Codes', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=pmpro-discountcodes') ) );	
}
add_action('admin_bar_menu', 'pmpro_admin_bar_menu', 1000);

/*
	Functions to load pages from adminpages directory
*/
function pmpro_reports()
{
	require_once(PMPRO_DIR . "/adminpages/reports.php");
}

function pmpro_memberslist()
{
	require_once(PMPRO_DIR . "/adminpages/memberslist.php");
}

function pmpro_discountcodes()
{
	require_once(PMPRO_DIR . "/adminpages/discountcodes.php");
}

function pmpro_membershiplevels()
{
	require_once(PMPRO_DIR . "/adminpages/membershiplevels.php");
}

function pmpro_pagesettings()
{
	require_once(PMPRO_DIR . "/adminpages/pagesettings.php");
}

function pmpro_paymentsettings()
{
	require_once(PMPRO_DIR . "/adminpages/paymentsettings.php");
}

function pmpro_emailsettings()
{
	require_once(PMPRO_DIR . "/adminpages/emailsettings.php");
}

function pmpro_advancedsettings()
{
	require_once(PMPRO_DIR . "/adminpages/advancedsettings.php");
}

function pmpro_addons()
{
	require_once(PMPRO_DIR . "/adminpages/addons.php");
}

function pmpro_orders()
{
	require_once(PMPRO_DIR . "/adminpages/orders.php");
}