<?php
/**
 * Add a widget to the dashboard.
 *
 * The callback function for the meta box is the same as the callback function for the report page.
 */
add_action( 'add_meta_boxes', function() {
	add_meta_box(
		'pmpro_dashboard_report_membership_stats',
		__( 'Membership Stats', 'paid-memberships-pro' ),
		'pmpro_report_memberships_widget',
		'toplevel_page_pmpro-dashboard',
		'advanced'
	);
} );