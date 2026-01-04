<?php
/**
 * The Memberships Dashboard admin page for Paid Memberships Pro
 * Updated for 3-column grid layout
 *
 * @since 2.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the Paid Memberships Pro dashboard metaboxes.
 * This will include any custom metaboxes defined in the /metaboxes/ directory.
 */
$metaboxes_dir   = __DIR__ . '/metaboxes/';
$metaboxes_files = glob( $metaboxes_dir . '*.php' );
foreach ( $metaboxes_files as $file ) {
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

/**
 * Filter the meta boxes to display on the Paid Memberships Pro dashboard.
 *
 * @since 3.1
 *
 * @param array $pmpro_dashboard_meta_boxes Array of meta boxes to display on the dashboard. Hint: Use the associative array key as the meta box ID.
 */
// The meta boxes array for the dashboard - Updated for 3-column grid
$pmpro_dashboard_meta_boxes = apply_filters(
	'pmpro_dashboard_meta_boxes',
	array(
		'pmpro_dashboard_welcome'        => array(
			'title'             => esc_html__( 'Welcome to Paid Memberships Pro', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_welcome_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 4,
			'grid_column_start' => 1,
		),
		'pmpro_dashboard_quick_links'    => array(
			'title'             => esc_html__( 'Quick Links', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_quick_links_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 3,
			'grid_column_start' => 1,
		),
		'pmpro_dashboard_license_status' => array(
			'title'             => esc_html__( 'License Status', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_license_status_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 1,
			'grid_column_start' => 4,
		),
		'pmpro_dashboard_report_sales'   => array(
			'title'             => esc_html__( 'Sales and Revenue', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_report_sales_widget',
			'capability'        => 'pmpro_reports',
			'header_link'       => esc_url( admin_url( 'admin.php?page=pmpro-reports&report=sales' ) ),
			'header_link_text'  => esc_html__( 'View All Sales', 'paid-memberships-pro' ),
			'columns'           => 2,
			'grid_column_start' => 1,
		),
		'pmpro_dashboard_report_stats'   => array(
			'title'             => esc_html__( 'Membership Stats', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_report_memberships_widget',
			'capability'        => '',
			'header_link'       => esc_url( admin_url( 'admin.php?page=pmpro-reports&report=memberships' ) ),
			'header_link_text'  => esc_html__( 'View All Memberships', 'paid-memberships-pro' ),
			'columns'           => 2,
			'grid_column_start' => 3,
		),
		'pmpro_dashboard_recent_orders'  => array(
			'title'             => esc_html__( 'Recent Orders', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_report_recent_orders_callback',
			'capability'        => 'pmpro_orders',
			'header_link'       => esc_url( admin_url( 'admin.php?page=pmpro-orders' ) ),
			'header_link_text'  => esc_html__( 'View All Orders', 'paid-memberships-pro' ),
			'columns'           => 2,
			'grid_column_start' => 2,
		),
		'pmpro_dashboard_recent_members' => array(
			'title'             => esc_html__( 'Recent Members', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_report_recent_members_callback',
			'capability'        => 'pmpro_memberslist',
			'header_link'       => esc_url( admin_url( 'admin.php?page=pmpro-memberslist' ) ),
			'header_link_text'  => esc_html__( 'View All Members', 'paid-memberships-pro' ),
			'columns'           => 2,
			'grid_column_start' => 1,
		),
		'pmpro_dashboard_get_involved'   => array(
			'title'             => esc_html__( 'Get Involved', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_get_involved_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 1,
			'grid_column_start' => 1,
		),
		'pmpro_dashboard_follow_us'      => array(
			'title'             => esc_html__( 'Follow Us', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_follow_us_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 1,
			'grid_column_start' => 2,
		),
		'pmpro_dashboard_events'         => array(
			'title'             => esc_html__( 'Upcoming Events', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_events_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 1,
			'grid_column_start' => 3,
		),
		'pmpro_dashboard_news_updates'   => array(
			'title'             => esc_html__( 'News and Updates', 'paid-memberships-pro' ),
			'callback'          => 'pmpro_dashboard_news_updates_callback',
			'capability'        => '',
			'header_link'       => '',
			'header_link_text'  => '',
			'columns'           => 1,
			'grid_column_start' => 4,
		),
	)
);

/**
 * Render dashboard metaboxes in the grid layout.
 * This function will reorder the metaboxes based on the saved user preferences.
 * Updated for 3-column grid support.
 *
 * @since 3.5
 * @param array  $meta_boxes Array of metaboxes to render.
 * @param string $screen_id The screen ID where the metaboxes are being rendered.
 * @return void
 */
function pmpro_render_dashboard_grid_metaboxes( $meta_boxes, $screen_id ) {

	// Get saved order for current user
	$saved_order = get_user_meta( get_current_user_id(), 'pmpro_dashboard_metabox_order', true );

	// If we have a saved order, reorder the metaboxes array
	if ( ! empty( $saved_order ) ) {
		$order_array       = explode( ',', $saved_order );
		$ordered_metaboxes = array();

		// First, add metaboxes in the saved order
		foreach ( $order_array as $metabox_id ) {
			if ( isset( $meta_boxes[ $metabox_id ] ) ) {
				$ordered_metaboxes[ $metabox_id ] = $meta_boxes[ $metabox_id ];
			}
		}

		// Then add any metaboxes that weren't in the saved order (new ones)
		foreach ( $meta_boxes as $id => $meta_box ) {
			if ( ! isset( $ordered_metaboxes[ $id ] ) ) {
				$ordered_metaboxes[ $id ] = $meta_box;
			}
		}

		$meta_boxes = $ordered_metaboxes;
	}

	// Render the metaboxes in order
	foreach ( $meta_boxes as $id => $meta_box ) {
		if ( ! empty( $meta_box['capability'] ) && ! current_user_can( $meta_box['capability'] ) ) {
			continue;
		}

		// Sanitize and validate column span (1-4 for 4-column grid)
		$span = isset( $meta_box['columns'] ) ? max( 1, min( intval( $meta_box['columns'] ), 4 ) ) : 1;

		// Build the CSS classes
		$classes = array(
			'postbox',
			'pmpro-colspan-' . $span,
		);

		$class_string = implode( ' ', $classes );

		echo '<div id="' . esc_attr( $id ) . '" class="' . esc_attr( $class_string ) . '" role="listitem" aria-grabbed="false">';

		// Simplified metabox header - just title and drag handle
		echo '<div class="postbox-header">';
		echo '<span class="screen-reader-text">' . esc_html__( 'Spacebar to pickup. Use arrow keys to move. Press Enter to drop.', 'paid-memberships-pro' ) . '</span>';
		echo '<h2 class="hndle ui-sortable-handle pmpro-drag-handle" tabindex="0" role="button" aria-label="' . esc_attr__( 'Move ', 'paid-memberships-pro' ) . esc_attr( $meta_box['title'] ) . '"><span>' . esc_html( $meta_box['title'] ) . '</span></h2>';      // If a header link is provided, display it
		if ( ! empty( $meta_box['header_link'] ) && ! empty( $meta_box['header_link_text'] ) ) {
			echo '<p class="pmpro_report-button">';
			echo '<a class="button button-secondary" style="text-decoration: none;" href="' . esc_url( $meta_box['header_link'] ) . '">' . esc_html( $meta_box['header_link_text'] ) . '&nbsp;&rarr;</a>';
			echo '</p>';
		}
		echo '</h2>';
		echo '</div>';
		echo '<div class="inside">';
		if ( is_callable( $meta_box['callback'] ) ) {
			// Start output buffering to capture the callback output
			ob_start();
			call_user_func( $meta_box['callback'] );
			$output = ob_get_clean();

			// Remove "Details" button if it exists, we now use the header link from $pmpro_dashboard_meta_boxes
			$output = preg_replace(
				'#<p class="pmpro_report-button">.*?</p>#is',
				'',
				$output
			);

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		echo '</div>'; // Close the postbox div
	}
}

/**
 * Load the Paid Memberships Pro dashboard-area header
 */
require_once __DIR__ . '/admin_header.php'; ?>

<hr class="wp-header-end">
<form id="pmpro-dashboard-form" method="post" action="admin-post.php">
	<div class="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder" role="list">
			<?php pmpro_render_dashboard_grid_metaboxes( $pmpro_dashboard_meta_boxes, 'toplevel_page_pmpro-dashboard' ); ?>
		</div>
		<?php wp_nonce_field( 'pmpro_metabox_order', 'pmpro_metabox_nonce' ); ?>
	</div>
</form>

<?php

/**
 * Delete transients when orders are updated.
 *
 * @since 3.0
 */
function pmpro_report_dashboard_delete_transients() {
	delete_transient( 'pmpro_dashboard_report_recent_members' );
	delete_transient( 'pmpro_dashboard_report_recent_orders' );
}
add_action( 'pmpro_updated_order', 'pmpro_report_dashboard_delete_transients' );
add_action( 'pmpro_after_checkout', 'pmpro_report_dashboard_delete_transients' );
add_action( 'pmpro_after_change_membership_level', 'pmpro_report_dashboard_delete_transients' );

/**
 * Load the Paid Memberships Pro dashboard-area footer
 */
require_once __DIR__ . '/admin_footer.php';

// Register and enqueue the dashboard script.
wp_register_script(
	'pmpro_dashboard',
	plugins_url( 'js/pmpro-dashboard.js', PMPRO_BASE_FILE ),
	array( 'jquery' ),
	PMPRO_VERSION
);
wp_enqueue_script( 'pmpro_dashboard' );