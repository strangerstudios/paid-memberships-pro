<?php
/**
 * Get array of PMPro Capabilities
 * Used below to figure out which page to have the main Membership menu link to.
 * The order is important. The first cap the user has is used.
 */
function pmpro_getPMProCaps() {
	$pmpro_caps = array(
		//pmpro_memberships_menu //this controls viewing the menu itself
		'pmpro_dashboard',				
		'pmpro_memberslist',
		'pmpro_orders',
		'pmpro_reports',				
		'pmpro_membershiplevels',
		'pmpro_discountcodes',
		'pmpro_pagesettings',
		'pmpro_paymentsettings',
		'pmpro_emailsettings',		
		'pmpro_emailtemplates',
		'pmpro_userfields',
		'pmpro_advancedsettings',
		'pmpro_addons',
		'pmpro_wizard',		
		'pmpro_updates',
		'pmpro_manage_pause_mode'
	);

	return $pmpro_caps;
}

/**
 * Dashboard Menu
 */
function pmpro_add_pages() {
	global $wpdb;

	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();

	//the top level menu links to the first page they have access to
	foreach( $pmpro_caps as $cap ) {
		if( current_user_can( $cap ) ) {
			$top_menu_cap = $cap;
			break;
		}
	}

	if( empty( $top_menu_cap ) ) {
		return;
	}

	// Top level menu
	add_menu_page( __( 'Memberships', 'paid-memberships-pro' ), __( 'Memberships', 'paid-memberships-pro' ), 'pmpro_memberships_menu', 'pmpro-dashboard', $top_menu_cap, 'dashicons-groups', 30 );
	
	// Main submenus
	add_submenu_page( 'pmpro-dashboard', __( 'Dashboard', 'paid-memberships-pro' ), __( 'Dashboard', 'paid-memberships-pro' ), 'pmpro_dashboard', 'pmpro-dashboard', 'pmpro_dashboard' );
	$list_table_hook = add_submenu_page( 'pmpro-dashboard', __( 'Members', 'paid-memberships-pro' ), __( 'Members', 'paid-memberships-pro' ), 'pmpro_memberslist', 'pmpro-memberslist', 'pmpro_memberslist' );
	add_submenu_page( 'pmpro-dashboard', __( 'Orders', 'paid-memberships-pro' ), __( 'Orders', 'paid-memberships-pro' ), 'pmpro_orders', 'pmpro-orders', 'pmpro_orders' );
	add_submenu_page( 'pmpro-dashboard', __( 'Reports', 'paid-memberships-pro' ), __( 'Reports', 'paid-memberships-pro' ), 'pmpro_reports', 'pmpro-reports', 'pmpro_reports' );
	add_submenu_page( 'pmpro-dashboard', __( 'Settings', 'paid-memberships-pro' ), __( 'Settings', 'paid-memberships-pro' ), 'pmpro_membershiplevels', 'pmpro-membershiplevels', 'pmpro_membershiplevels' );
	add_submenu_page( 'pmpro-dashboard', __( 'Add Ons', 'paid-memberships-pro' ), __( 'Add Ons', 'paid-memberships-pro' ), 'pmpro_addons', 'pmpro-addons', 'pmpro_addons' );

	// Check License Key for Correct Link Color
	$key = get_option( 'pmpro_license_key', '' );
	if ( pmpro_license_isValid( $key, NULL ) ) {
		$span_color = '#33FF00';
	} else {
		$span_color = '#FF3333';
	}
	add_submenu_page( 'pmpro-dashboard', __( 'License', 'paid-memberships-pro' ), __( '<span style="color: ' . $span_color . '">License</span>', 'paid-memberships-pro' ), 'manage_options', 'pmpro-license', 'pmpro_license_settings_page' );

	// Settings tabs
	add_submenu_page( 'admin.php', __( 'Discount Codes', 'paid-memberships-pro' ), __( 'Discount Codes', 'paid-memberships-pro' ), 'pmpro_discountcodes', 'pmpro-discountcodes', 'pmpro_discountcodes' );
	add_submenu_page( 'admin.php', __( 'Page Settings', 'paid-memberships-pro' ), __( 'Page Settings', 'paid-memberships-pro' ), 'pmpro_pagesettings', 'pmpro-pagesettings', 'pmpro_pagesettings' );
	add_submenu_page( 'admin.php', __( 'Payment Settings', 'paid-memberships-pro' ), __( 'Payment Settings', 'paid-memberships-pro' ), 'pmpro_paymentsettings', 'pmpro-paymentsettings', 'pmpro_paymentsettings' );
	add_submenu_page( 'admin.php', __( 'Email Settings', 'paid-memberships-pro' ), __( 'Email Settings', 'paid-memberships-pro' ), 'pmpro_emailsettings', 'pmpro-emailsettings', 'pmpro_emailsettings' );
	add_submenu_page( 'admin.php', __( 'Email Templates', 'paid-memberships-pro' ), __( 'Email Templates', 'paid-memberships-pro' ), 'pmpro_emailtemplates', 'pmpro-emailtemplates', 'pmpro_emailtemplates' );
	add_submenu_page( 'admin.php', __( 'User Fields', 'paid-memberships-pro' ), __( 'User Fields', 'paid-memberships-pro' ), 'pmpro_userfields', 'pmpro-userfields', 'pmpro_userfields' );
	add_submenu_page( 'admin.php', __( 'Advanced Settings', 'paid-memberships-pro' ), __( 'Advanced Settings', 'paid-memberships-pro' ), 'pmpro_advancedsettings', 'pmpro-advancedsettings', 'pmpro_advancedsettings' );

	add_action( 'load-' . $list_table_hook, 'pmpro_list_table_screen_options' );

	//updates page only if needed
	if ( pmpro_isUpdateRequired() ) {
		add_submenu_page( 'pmpro-dashboard', __( 'Updates Required', 'paid-memberships-pro' ), __( 'Updates Required', 'paid-memberships-pro' ), 'pmpro_updates', 'pmpro-updates', 'pmpro_updates' );
	}
	
	//Logic added here in order to always reach this page if PMPro is setup. ?page=pmpro-wizard is always reachable should people want to rerun through the Setup Wizard.
	if ( pmpro_show_setup_wizard_link() ) {
		$wizard_location = 'pmpro-dashboard';
	} else {
		$wizard_location = 'admin.php';	// Registers the page, but doesn't show up in menu.
	}
	
	add_submenu_page( $wizard_location, __( 'Setup Wizard', 'paid-memberships-pro' ), __( 'Setup Wizard', 'paid-memberships-pro' ), 'pmpro_wizard', 'pmpro-wizard', 'pmpro_wizard' );
}
add_action( 'admin_menu', 'pmpro_add_pages' );

/**
 * Keep the Memberships menu selected on subpages.
 */
function pmpro_parent_file( $parent_file ) {
	global $parent_file, $plugin_page, $submenu_file;
	
	$pmpro_settings_tabs = array(
		'pmpro-membershiplevels',
		'pmpro-discountcodes',
		'pmpro-pagesettings',
		'pmpro-paymentsettings',
		'pmpro-emailsettings',
		'pmpro-emailtemplates',
		'pmpro-advancedsettings',
	);
	
	if( isset( $_REQUEST['page']) && in_array( $_REQUEST['page'], $pmpro_settings_tabs ) ) {
		$parent_file = 'pmpro-dashboard';
		$plugin_page = 'pmpro-dashboard';
		$submenu_file = 'pmpro-membershiplevels';
	}
	
	return $parent_file;
}
add_filter( 'parent_file', 'pmpro_parent_file' );

/**
 * Admin Bar
 */
function pmpro_admin_bar_menu() {
	global $wp_admin_bar;

	//view menu at all?
	if ( ! current_user_can( 'pmpro_memberships_menu' ) || ! is_admin_bar_showing() ) {
		return;
	}
	
	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();

	//the top level menu links to the first page they have access to
	foreach ( $pmpro_caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			$top_menu_page = str_replace( '_', '-', $cap );
			break;
		}
	}

	$wp_admin_bar->add_menu(
		array(
			'id' => 'paid-memberships-pro',
			'title' => __( '<span class="ab-icon"></span>Memberships', 'paid-memberships-pro' ),
			'href' => admin_url( '/admin.php?page=' . $top_menu_page )
		) 
	);

	// Add menu item for Dashboard.
	if ( current_user_can( 'pmpro_dashboard' ) ) {
		$wp_admin_bar->add_menu( 
			array(
				'id' => 'pmpro-dashboard',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Dashboard', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-dashboard' ) 
			)
		);
	}
	
	// Add menu item for Members List.
	if ( current_user_can( 'pmpro_memberslist' ) ) {
		$wp_admin_bar->add_menu( 
			array(
				'id' => 'pmpro-members-list',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Members', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-memberslist' )
			)
		);
	}

	// Add menu item for Orders.
	if ( current_user_can( 'pmpro_orders' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-orders',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Orders', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-orders' )
			)
		);
	}

	// Add menu item for Reports.
	if ( current_user_can( 'pmpro_reports' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-reports',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Reports', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-reports' )
			)
		);
	}

	// Add menu item for Settings.
	if ( current_user_can( 'pmpro_membershiplevels' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-membership-levels',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Settings', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-membershiplevels' )
			)
		);
	}

	// Add menu item for Add Ons.
	if ( current_user_can( 'pmpro_addons' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-addons',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Add Ons', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-addons' )
			)
		);
	}

	// Add menu item for License.
	if ( current_user_can( 'manage_options' ) ) {
		// Check License Key for Correct Link Color
		$key = get_option( 'pmpro_license_key', '' );
		if ( pmpro_license_isValid( $key, NULL ) ) {
			$span_color = '#33FF00';
		} else {
			$span_color = '#FF3333';
		}
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-license',
				'parent' => 'paid-memberships-pro',
				'title' => __( '<span style="color: ' . $span_color . '; line-height: 26px;">License</span>', 'paid-memberships-pro' ),
				'href' => admin_url( '/admin.php?page=pmpro-license' )
			)
		);
	}
}
add_action( 'admin_bar_menu', 'pmpro_admin_bar_menu', 1000);

/// Add docblock
function pmpro_view_as_admin_bar_menu() {
	global $wp_admin_bar;

	// Only show when viewing the frontend of the site.
	if ( is_admin() ) {
		return;
	}

	/**
	 * Filter to hide the "View As" menu in the admin bar.
	 * @since TBD
	 * @param bool $hide_view_as_toolbar Whether to hide the "View As" menu in the admin bar. Default false.
	 */
	if ( apply_filters( 'pmpro_hide_view_as_toolbar', false ) ) {
		return;
	}

	//view menu at all?
	if ( ! current_user_can( 'pmpro_memberships_menu' ) || ! is_admin_bar_showing() ) {
		return;
	}

	// Let's save or delete the option now.
	if ( ! empty( $_REQUEST['pmpro-view-as-submit'] ) ) {

		// Let's get the value of the view_as now:
		$view_as = sanitize_text_field( $_REQUEST['pmpro_view_as'] );

		if ( $view_as == 'member' && ! empty( $_REQUEST['levels'] ) ) {
			update_option( 'pmpro_view_as', array_map( 'sanitize_text_field', $_REQUEST['levels'] ) );
		} elseif ( $view_as !== 'admin' ) {
			update_option( 'pmpro_view_as', $view_as );
		} else {
			delete_option( 'pmpro_view_as' );
		}

		 echo "<meta http-equiv='refresh' content='0'>";
	}

	// Let's get the option now so we can show it.
	$viewing_as = get_option( 'pmpro_view_as' );

	// Set the title and the option value.
	if ( is_array( $viewing_as ) ) {
		$view_as_option = $viewing_as;
		$title = __( 'Viewing as Member', 'paid-memberships-pro' );
	} elseif ( is_string( $viewing_as ) && ! empty( $viewing_as ) ) {
		$view_as_option = $viewing_as;
		$title = __( 'Viewing as', 'paid-memberships-pro' ) . ' ' . $view_as_option;
	} else {
		$title = __( 'View as...', 'paid-memberships-pro' );
		$view_as_option = $viewing_as;
	}

	$wp_admin_bar->add_menu(
		array(
			'id' => 'pmpro-view-as',
			'parent' => 'top-secondary',
			'title' => $title,
		)
	);

	?>
<script>	
document.addEventListener('DOMContentLoaded', function () {
  var viewAsMember = document.getElementById('pmpro_view_as_member');
  var viewAsSelect = document.getElementById('pmpro_view_as');

  viewAsMember.style.display = 'none';
  
  viewAsSelect.addEventListener('change', function () {
    if (this.value === 'member') {
      viewAsMember.style.display = 'block';
    } else {
      viewAsMember.style.display = 'none';
    }
  });
});
</script>
	<style>
		#wp-admin-bar-pmpro-view-as .ab-sub-wrapper{
  			height:85px;
			width:165px;
		}

		#pmpro_view_as_form select{
			margin-left:15px;
			width:80%;
		}

		#pmpro_view_as_form .button{
			padding:0 2% 0 2%;
			margin:5px;
			width: 90%;
			border: none;
			border-radius: 3px;
			cursor: pointer;
			text-decoration: none;
   			text-shadow: none;
		}

		#pmpro_view_as_form .button-primary {
			background: #2271b1;
    		border-color: #2271b1;
    		color: #fff;
		}

		#pmpro_view_as_member {
			margin:10px 0 10px 0;
		}

	</style>
	<?php

	// Get all levels to allow selection when viewing as member.
	$levels = pmpro_getAllLevels( true, true );

	// Use $selected_levels to show stored selected levels for VIEW AS.
	$selected_levels = is_array( $view_as_option ) ? $view_as_option : array();

	// Build a form input for what we are selecting now for the actual "View As"
	$form = '<form method="POST" id="pmpro_view_as_form" action="">';
	$form .= '<select name="pmpro_view_as" id="pmpro_view_as">';
	$form .= '<option value="admin" ' . selected( is_bool( $view_as_option ), true, false ) . '>' . esc_html__( 'Admin', 'paid-memberships-pro' ) . '</option>';
	$form .= '<option value="non-member"' . selected( ! empty( $view_as_option ), true, false ) .'>' .  esc_html__( 'Non-member', 'paid-memberships-pro' ) . '</option>';
	$form .= '<option value="member"' . selected( is_array( $view_as_option ), true, false ). '>' . esc_html__( 'Member', 'paid-memberships-pro' ) . '</option>';
	$form .= '</select><br/>';
	$form .= '<select name="levels[]" id="pmpro_view_as_member" multiple="multiple" />';
		foreach( $levels as $level ) {
			$is_selected = in_array( $level->id, $selected_levels ) ? true : false;
			$form .= '<option value="' . esc_attr( $level->id ) . '"' . selected( $is_selected, true, false ) . '>' . esc_html( $level->name ) . '</option>';
		}
	$form .= '</select>';
	$form .= '<input type="submit" name="pmpro-view-as-submit" class="button button-primary" value="' . __( 'Switch Access', 'paid-memberships-pro' ) . '"/>';
	$form .= '</form>';

	$wp_admin_bar->add_node( array(
		'parent' => 'pmpro-view-as',
		'id' => 'pmpro-view-as-input',
		'title' => $form,
	) );
}
add_action( 'admin_bar_menu', 'pmpro_view_as_admin_bar_menu' );

/**
 * Functions to load pages from adminpages directory
 */
function pmpro_reports() {
	//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );

	require_once( PMPRO_DIR . '/adminpages/reports.php' );
}

function pmpro_memberslist() {
	require_once( PMPRO_DIR . '/adminpages/memberslist.php' );
}

function pmpro_discountcodes() {
	require_once( PMPRO_DIR . '/adminpages/discountcodes.php' );
}

function pmpro_dashboard() {
	//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );

	require_once( PMPRO_DIR . '/adminpages/dashboard.php' );
}

function pmpro_wizard() {
	require_once( PMPRO_DIR . '/adminpages/wizard/wizard.php' );
}

function pmpro_membershiplevels() {
	require_once( PMPRO_DIR . '/adminpages/membershiplevels.php' );
}

function pmpro_pagesettings() {
	require_once( PMPRO_DIR . '/adminpages/pagesettings.php' );
}

function pmpro_paymentsettings() {
	require_once( PMPRO_DIR . '/adminpages/paymentsettings.php' );
}

function pmpro_emailsettings() {
	require_once( PMPRO_DIR . '/adminpages/emailsettings.php' );
}

function pmpro_userfields() {
	//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );
	
	require_once( PMPRO_DIR . '/adminpages/userfields.php' );
}

function pmpro_emailtemplates() {
	require_once( PMPRO_DIR . '/adminpages/emailtemplates.php' );
}

function pmpro_advancedsettings() {
	require_once( PMPRO_DIR . '/adminpages/advancedsettings.php' );
}

function pmpro_addons() {
	require_once( PMPRO_DIR . '/adminpages/addons.php' );
}

function pmpro_orders() {
	require_once( PMPRO_DIR . '/adminpages/orders.php' );
}

function pmpro_license_settings_page() {
	require_once( PMPRO_DIR . '/adminpages/license.php' );
}

function pmpro_updates() {
	require_once( PMPRO_DIR . '/adminpages/updates.php' );
}

/**
 * Move orphaned pages under the pmpro-dashboard menu page.
 */
function pmpro_fix_orphaned_sub_menu_pages( ) {
	global $submenu;

	if ( is_array( $submenu) && array_key_exists( 'pmpro-membershiplevels', $submenu ) ) {
		$pmpro_dashboard_submenu = $submenu['pmpro-dashboard'];	
		$pmpro_old_memberships_submenu = $submenu['pmpro-membershiplevels'];
	
		if ( is_array( $pmpro_dashboard_submenu ) && is_array( $pmpro_old_memberships_submenu ) ) {
			$submenu['pmpro-dashboard'] = array_merge( $pmpro_dashboard_submenu, $pmpro_old_memberships_submenu );
		}
	}
}
add_action( 'admin_init', 'pmpro_fix_orphaned_sub_menu_pages', 99 );

/**
 * Add a post display state for special PMPro pages in the page list table.
 *
 * @param array   $post_states An array of post display states.
 * @param WP_Post $post The current post object.
 */
function pmpro_display_post_states( $post_states, $post ) {
	// Get assigned page settings.
	global $pmpro_pages;

	if ( intval( $pmpro_pages['account'] ) === $post->ID ) {
		$post_states['pmpro_account_page'] = __( 'Membership Account Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['billing'] ) === $post->ID ) {
		$post_states['pmpro_billing_page'] = __( 'Membership Billing Information Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['cancel'] ) === $post->ID ) {
		$post_states['pmpro_cancel_page'] = __( 'Membership Cancel Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['checkout'] ) === $post->ID ) {
		$post_states['pmpro_checkout_page'] = __( 'Membership Checkout Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['confirmation'] ) === $post->ID ) {
		$post_states['pmpro_confirmation_page'] = __( 'Membership Confirmation Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['invoice'] ) === $post->ID ) {
		$post_states['pmpro_invoice_page'] = __( 'Membership Invoice Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['levels'] ) === $post->ID ) {
		$post_states['pmpro_levels_page'] = __( 'Membership Levels Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['login'] ) === $post->ID ) {
		$post_states['pmpro_login_page'] = __( 'Paid Memberships Pro Login Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['member_profile_edit'] ) === $post->ID ) {
		$post_states['pmpro_member_profile_edit_page'] = __( 'Member Profile Edit Page', 'paid-memberships-pro' );
	}

	return $post_states;
}
add_filter( 'display_post_states', 'pmpro_display_post_states', 10, 2 );

/**
 * Screen options for the List Table
 *
 * Callback for the load-($page_hook_suffix)
 * Called when the plugin page is loaded
 *
 * @since    2.0.0
 */
function pmpro_list_table_screen_options() {
	global $user_list_table;
	$arguments = array(
		'label'   => __( 'Members Per Page', 'paid-memberships-pro' ),
		'default' => 13,
		'option'  => 'users_per_page',
	);
	add_screen_option( 'per_page', $arguments );
	// instantiate the User List Table
	$user_list_table = new PMPro_Members_List_Table();
}

/**
 * Add links to the plugin action links
 */
function pmpro_add_action_links( $links ) {

	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();

	//the top level menu links to the first page they have access to
	foreach( $pmpro_caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			$top_menu_page = str_replace( '_', '-', $cap );
			break;
		}
	}

	$new_links = array(
		'<a href="' . admin_url( 'admin.php?page=' . $top_menu_page ) . '">Settings</a>',
	);
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( PMPRO_DIR . '/paid-memberships-pro.php' ), 'pmpro_add_action_links' );

/**
 * Add links to the plugin row meta
 */
function pmpro_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'paid-memberships-pro.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( apply_filters( 'pmpro_docs_url', 'http://paidmembershipspro.com/documentation/' ) ) . '" title="' . esc_attr( __( 'View PMPro Documentation', 'paid-memberships-pro' ) ) . '">' . __( 'Docs', 'paid-memberships-pro' ) . '</a>',
			'<a href="' . esc_url( apply_filters( 'pmpro_support_url', 'http://paidmembershipspro.com/support/' ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'paid-memberships-pro' ) ) . '">' . __( 'Support', 'paid-memberships-pro' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_plugin_row_meta', 10, 2 );
