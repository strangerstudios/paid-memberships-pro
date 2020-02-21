<?php
/**
 * Custom menu functions for Paid Memberships Pro
 *
 * @since 2.3
 */
function pmpro_register_menus() {
	// Register PMPro menu areas.
	register_nav_menus(
		array(
			'pmpro-member' => __( 'Member Form - Paid Memberships Pro', 'paid-memberships-pro' ),
		)
	);
}
add_action( 'after_setup_theme', 'pmpro_register_menus' );

/**
 * Hide the WordPress Toolbar from Non-Admins.
 *
 * @since 2.3
 */
function pmpro_hide_toolbar_from_non_admins() {

	// Get the Advanced Setting for toolbar display.
	$hide_toolbar = pmpro_getOption( 'hide_toolbar' );

	if ( ! current_user_can( 'administrator' ) && ! empty( $hide_toolbar ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
		//add_action( 'admin_print_scripts-profile.php', 'habfna_hide_admin_bar_settings' );
	}	
}
add_action( 'init', 'pmpro_hide_toolbar_from_non_admins', 9 );