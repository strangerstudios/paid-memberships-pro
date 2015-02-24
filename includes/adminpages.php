<?php
/*
	Get array of PMPro Capabilities
*/
function pmpro_getPMProCaps()
{
	$pmpro_caps = array(
		//pmpro_memberships_menu //this controls viewing the menu itself
		'pmpro_membershiplevels',
		'pmpro_pagesettings',
		'pmpro_paymentsettings',
		'pmpro_emailsettings',
		'pmpro_advancedsettings',
		'pmpro_addons',
		'pmpro_memberslist',
		'pmpro_reports',
		'pmpro_orders',
		'pmpro_discountcodes'
	);
	
	return $pmpro_caps;
}

/*
	Dashboard Menu
*/
function pmpro_add_pages()
{
	global $wpdb;   

	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();
	
	//the top level menu links to the first page they have access to
	foreach($pmpro_caps as $cap)
	{
		if(current_user_can($cap))
		{
			$top_menu_cap = $cap;
			break;
		}
	}
	
	if(empty($top_menu_cap))
		return;
	
	add_menu_page(__('Memberships', 'pmpro'), __('Memberships', 'pmpro'), 'pmpro_memberships_menu', 'pmpro-membershiplevels', $top_menu_cap, 'dashicons-groups');
	add_submenu_page('pmpro-membershiplevels', __('Page Settings', 'pmpro'), __('Page Settings', 'pmpro'), 'pmpro_pagesettings', 'pmpro-pagesettings', 'pmpro_pagesettings');
	add_submenu_page('pmpro-membershiplevels', __('Payment Settings', 'pmpro'), __('Payment Settings', 'pmpro'), 'pmpro_paymentsettings', 'pmpro-paymentsettings', 'pmpro_paymentsettings');
	add_submenu_page('pmpro-membershiplevels', __('Email Settings', 'pmpro'), __('Email Settings', 'pmpro'), 'pmpro_emailsettings', 'pmpro-emailsettings', 'pmpro_emailsettings');
	add_submenu_page('pmpro-membershiplevels', __('Advanced Settings', 'pmpro'), __('Advanced Settings', 'pmpro'), 'pmpro_advancedsettings', 'pmpro-advancedsettings', 'pmpro_advancedsettings');
	add_submenu_page('pmpro-membershiplevels', __('Add Ons', 'pmpro'), __('Add Ons', 'pmpro'), 'pmpro_addons', 'pmpro-addons', 'pmpro_addons');
	add_submenu_page('pmpro-membershiplevels', __('Members List', 'pmpro'), __('Members List', 'pmpro'), 'pmpro_memberslist', 'pmpro-memberslist', 'pmpro_memberslist');
	add_submenu_page('pmpro-membershiplevels', __('Reports', 'pmpro'), __('Reports', 'pmpro'), 'pmpro_reports', 'pmpro-reports', 'pmpro_reports');
	add_submenu_page('pmpro-membershiplevels', __('Orders', 'pmpro'), __('Orders', 'pmpro'), 'pmpro_orders', 'pmpro-orders', 'pmpro_orders');
	add_submenu_page('pmpro-membershiplevels', __('Discount Codes', 'pmpro'), __('Discount Codes', 'pmpro'), 'pmpro_discountcodes', 'pmpro-discountcodes', 'pmpro_discountcodes');
	
	//rename the automatically added Memberships submenu item
	global $submenu;
	if(!empty($submenu['pmpro-membershiplevels']))
	{
		if(current_user_can("pmpro_membershiplevels"))
		{
			$submenu['pmpro-membershiplevels'][0][0] = __( 'Membership Levels', 'pmpro' );
			$submenu['pmpro-membershiplevels'][0][3] = __( 'Membership Levels', 'pmpro' );
		}
		else
		{
			unset($submenu['pmpro-membershiplevels']);
		}
	}
}
add_action('admin_menu', 'pmpro_add_pages');

/*
	Admin Bar
*/
function pmpro_admin_bar_menu() {
	global $wp_admin_bar;
	
	//view menu at all?
	if ( !current_user_can('pmpro_memberships_menu') || !is_admin_bar_showing() )
		return;
	
	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();
	
	//the top level menu links to the first page they have access to
	foreach($pmpro_caps as $cap)
	{
		if(current_user_can($cap))
		{
			$top_menu_page = str_replace("_", "-", $cap);
			break;
		}
	}		
	
	$wp_admin_bar->add_menu( array(
	'id' => 'paid-memberships-pro',
	'title' => __( '<span class="ab-icon"></span>Memberships', 'pmpro'),
	'href' => get_admin_url(NULL, '/admin.php?page=' . $top_menu_page) ) );
	
	if(current_user_can('pmpro_membershiplevels'))
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-membership-levels',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Membership Levels', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-membershiplevels') ) );
	
	if(current_user_can('pmpro_pagesettings'))
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-page-settings',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Page Settings', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-pagesettings') ) );
	
	if(current_user_can('pmpro_paymentsettings'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-payment-settings',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Payment Settings', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-paymentsettings') ) );
	
	if(current_user_can('pmpro_emailsettings'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-email-settings',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Email Settings', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-emailsettings') ) );
	
	if(current_user_can('pmpro_advancedsettings'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-advanced-settings',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Advanced Settings', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-advancedsettings') ) );
	
	if(current_user_can('pmpro_addons'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-addons',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Add Ons', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-addons') ) );	
	
	if(current_user_can('pmpro_memberslist'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-members-list',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Members List', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-memberslist') ) );
	
	if(current_user_can('pmpro_reports'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-reports',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Reports', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-reports') ) );
	
	if(current_user_can('pmpro_orders'))	
		$wp_admin_bar->add_menu( array(
		'id' => 'pmpro-orders',
		'parent' => 'paid-memberships-pro',
		'title' => __( 'Orders', 'pmpro'),
		'href' => get_admin_url(NULL, '/admin.php?page=pmpro-orders') ) );
	
	if(current_user_can('pmpro_discountcodes'))	
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


/*
Function to add links to the plugin action links
*/
function pmpro_add_action_links($links) {
	
	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();
	
	//the top level menu links to the first page they have access to
	foreach($pmpro_caps as $cap)
	{
		if(current_user_can($cap))
		{
			$top_menu_page = str_replace("_", "-", $cap);
			break;
		}
	}
	
	$new_links = array(
		'<a href="' . get_admin_url(NULL, 'admin.php?page=' . $top_menu_page) . '">Settings</a>',
	);
	return array_merge($new_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(PMPRO_DIR . "/paid-memberships-pro.php"), 'pmpro_add_action_links');

/*
Function to add links to the plugin row meta
*/
function pmpro_plugin_row_meta($links, $file) {
	if(strpos($file, 'paid-memberships-pro.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url( apply_filters( 'pmpro_docs_url', 'http://paidmembershipspro.com/documentation/' ) ) . '" title="' . esc_attr( __( 'View PMPro Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url( apply_filters( 'pmpro_support_url', 'http://paidmembershipspro.com/support/' ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpro_plugin_row_meta', 10, 2);
