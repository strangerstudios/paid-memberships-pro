<?php
/**
 * Add meta box to dashboard page.
 *
 * The callback function for the meta box is the same as the callback function for the report page.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'pmpro_dashboard_report_sales',
		__( 'Sales and Revenue', 'paid-memberships-pro' ),
		'pmpro_report_sales_widget',
		'toplevel_page_pmpro-dashboard',
		'advanced'
	);
} );