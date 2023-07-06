<?php
/**
 *  On the admin side check if lifters streamline feature is enabled and react accordingly.
 */
function enable_streamlined_feature() {
	$is_lifter_streamnlined_enabled = get_option( 'pmpro_toggle_lifter_streamline_setup' ) == 'true';
	if($is_lifter_streamnlined_enabled ) {
		$lifter_streamline =  plugins_url() . '/paid-memberships-pro/css/lifter-streamline.css';
		wp_register_style( 'pmpro_lifter', $lifter_streamline, [], PMPRO_VERSION, 'screen' );
		wp_enqueue_style( 'pmpro_lifter' );
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	} else {
		wp_dequeue_style( 'pmpro_lifter' );
	}
}

add_action( 'admin_init','enable_streamlined_feature' );

/**
 * On LifterLMS plugin activation, activate streamline feature and install PMPro Courses plugin.
 */
function lifter_plugin_activation() {
	update_option( 'pmpro_toggle_lifter_streamline_setup', 'true' );
	include_once( PMPRO_DIR . '/classes/' . 'class-quiet-plugin-installer.php' ) ;
	$slug = 'pmpro-courses';
	$installer = new Quiet_Skin(compact('title', 'url', 'nonce', 'plugin', 'api') );
	$installer->download_install_and_activate($slug);
	exit( wp_redirect("/wp-admin/admin.php?page=pmpro-lifter-streamline") );
}


register_activation_hook( 'lifterlms/lifterlms.php', 'lifter_plugin_activation' );


/**
 * Backend Ajax function to toggle streamline feature.
 */
function toggle_streamline() {
	$status = $_POST['status'];
	update_option( 'pmpro_toggle_lifter_streamline_setup', $status);
	exit();
}

add_action( 'wp_ajax_toggle_streamline', 'toggle_streamline' );

/**
 * Override student dashboard template if streamline feature is enabled.
 */
function lifter_streamlined_orders( $template ) {
	global $wp;
	$is_lifter_streamnlined_enabled = get_option( 'pmpro_toggle_lifter_streamline_setup' ) == 'true';
	$is_student_dashboard = $wp->query_string == "pagename=student-dashboard";

	if( $is_lifter_streamnlined_enabled && $is_student_dashboard ) {
		$template = plugin_dir_path( __FILE__ ) . '/templates/my-orders.php';
	}

	return $template;
}

add_filter( 'template_include', 'lifter_streamlined_orders' );


/**
 * Check if page being loaded is the lifter wizard on the pages step and redirect to a streamlined version of the same page.
 */
function lifter_custom_step_pages () {
	global $pagenow;
	$is_lifter_streamnlined_enabled = get_option( 'pmpro_toggle_lifter_streamline_setup' ) == 'true';
	$is_page_step = $pagenow == "index.php" && $_GET && $_GET['page'] == "llms-setup" && $_GET['step'] == "pages";
	if ($is_lifter_streamnlined_enabled && $is_page_step) {
		wp_redirect("/wp-admin/admin.php?page=pmpro-lifter-streamline-step-pages");
	}
}

add_filter( 'admin_init', 'lifter_custom_step_pages' );

