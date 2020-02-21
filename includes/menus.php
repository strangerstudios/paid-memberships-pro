<?php
/**
 * Custom menu functions for Paid Memberships Pro
 *
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
