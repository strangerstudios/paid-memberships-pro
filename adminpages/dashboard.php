<?php
/**
 * The Memberships Dashboard admin page for Paid Memberships Pro
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
$metaboxes_dir = dirname( __FILE__ ) . '/metaboxes/';
$metaboxes_files = glob( $metaboxes_dir . '*.php' );
foreach ( $metaboxes_files as $file ) {
	if ( file_exists( $file ) ) {
		require_once( $file );
	}
}

/**
 * Filter the meta boxes to display on the Paid Memberships Pro dashboard.
 * 
 * @since 3.1
 * 
 * @param array $pmpro_dashboard_meta_boxes Array of meta boxes to display on the dashboard. Hint: Use the associative array key as the meta box ID.
 */
// The meta boxes array for the dashboard.
$pmpro_dashboard_meta_boxes = apply_filters( 'pmpro_dashboard_meta_boxes', array(
	// Row 1 (Welcome, spans 3 columns), side column
	'pmpro_dashboard_welcome' => array(
		'title'    => esc_html__( 'Welcome to Paid Memberships Pro', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_welcome_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 3,
		'grid_column_start' => 1,
	),
	'pmpro_dashboard_welcome_side' => array(
		'title'    => esc_html__( 'License & Community', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_welcome_side_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 4,
	),
	// Row 2
	'pmpro_dashboard_report_sales' => array(
		'title'    => esc_html__( 'Sales and Revenue', 'paid-memberships-pro' ),
		'callback' => 'pmpro_report_sales_widget',
		'context'  => 'grid',
		'capability' => 'pmpro_reports',
		'columns' => 1,
		'grid_column_start' => 1,
	),
	'pmpro_dashboard_report_recent_members' => array(
		'title'    => esc_html__( 'Recent Members', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_report_recent_members_callback',
		'context'  => 'grid',
		'capability' => 'pmpro_memberslist',
		'columns' => 2,
		'grid_column_start' => 2,
	),
	'pmpro_dashboard_report_membership_stats' => array(
		'title'    => esc_html__( 'Membership Stats', 'paid-memberships-pro' ),
		'callback' => 'pmpro_report_memberships_widget',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 4,
	),
	// Row 3
	'pmpro_dashboard_report_logins' => array(
		'title'    => esc_html__( 'Visits, Views, and Logins', 'paid-memberships-pro' ),
		'callback' => 'pmpro_report_login_widget',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 1,
	),
	'pmpro_dashboard_report_recent_orders' => array(
		'title'    => esc_html__( 'Recent Orders', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_report_recent_orders_callback',
		'context'  => 'grid',
		'capability' => 'pmpro_orders',
		'columns' => 2,
		'grid_column_start' => 2,
	),
	'pmpro_dashboard_news_updates' => array(
		'title'    => esc_html__( 'Paid Memberships Pro News and Updates', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_news_updates_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 4,
	),
));

/**
 * Add all the meta boxes for the PMPro dashboard.
 */
foreach ( $pmpro_dashboard_meta_boxes as $id => $meta_box ) {
	if (
		( empty( $meta_box['capability'] ) || current_user_can( $meta_box['capability'] ) )
		&& isset( $meta_box['context'] )
		&& $meta_box['context'] !== 'grid'
	) {
		add_meta_box(
			$id,
			$meta_box['title'],
			$meta_box['callback'],
			'toplevel_page_pmpro-dashboard',
			$meta_box['context']
		);
	}
}

/**
 * Render dashboard metaboxes in the grid layout.
 * This function will reorder the metaboxes based on the saved user preferences.
 * 
 * @since 3.5
 * @param array $meta_boxes Array of metaboxes to render.
 * @param string $screen_id The screen ID where the metaboxes are being rendered.
 * @return void
 */
function pmpro_render_dashboard_grid_metaboxes( $meta_boxes, $screen_id ) {
    // Get saved order for current user
    $saved_order = get_user_meta( get_current_user_id(), 'pmpro_dashboard_metabox_order', true );
    
    // If we have a saved order, reorder the metaboxes array
    if ( ! empty( $saved_order ) ) {
        $order_array = explode( ',', $saved_order );
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
        if ( $meta_box['context'] !== 'grid' ) {
            continue;
        }
        if ( ! empty( $meta_box['capability'] ) && ! current_user_can( $meta_box['capability'] ) ) {
            continue;
        }
        
        // Sanitize and validate column span (1-4)
        $span = isset( $meta_box['columns'] ) ? max( 1, min( intval( $meta_box['columns'] ), 4 ) ) : 1;
        
        // Build the CSS classes
        $classes = array(
            'postbox',
            'pmpro-colspan-' . $span
        );
        
        $class_string = implode( ' ', $classes );
        
        echo '<div id="' . esc_attr( $id ) . '" class="' . esc_attr( $class_string ) . '">';
        
        // Simplified metabox header - just title and drag handle
        echo '<div class="postbox-header">';
        echo '<h2 class="hndle ui-sortable-handle">' . esc_html( $meta_box['title'] ) . '</h2>';
        echo '</div>';
        
        echo '<div class="inside">';
        if ( is_callable( $meta_box['callback'] ) ) {
            call_user_func( $meta_box['callback'] );
        }
        echo '</div>';
        echo '</div>';
    }
}

/**
 * Load the Paid Memberships Pro dashboard-area header
 */
require_once( dirname( __FILE__ ) . '/admin_header.php' ); ?>

<hr class="wp-header-end">
<form id="pmpro-dashboard-form" method="post" action="admin-post.php">
	<div class="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder">
			<?php pmpro_render_dashboard_grid_metaboxes( $pmpro_dashboard_meta_boxes, 'toplevel_page_pmpro-dashboard' ); ?>
			<br class="clear">
		</div> <!-- end dashboard-widgets -->
		<?php wp_nonce_field( 'pmpro_metabox_order', 'pmpro_metabox_nonce' ); ?>
	</div> <!-- end dashboard-widgets-wrap -->
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
require_once( dirname( __FILE__ ) . '/admin_footer.php' );