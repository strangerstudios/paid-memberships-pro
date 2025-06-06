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
	'pmpro_dashboard_license_status' => array(
		'title'    => esc_html__( 'License Status', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_license_status_callback',
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
		'columns' => 2,
		'grid_column_start' => 1,
	),
	'pmpro_dashboard_report_membership_stats' => array(
		'title'    => esc_html__( 'Membership Stats', 'paid-memberships-pro' ),
		'callback' => 'pmpro_report_memberships_widget',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 2,
		'grid_column_start' => 3,
	),
	// Row 3
	'pmpro_dashboard_report_recent_members' => array(
		'title'    => esc_html__( 'Recent Members', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_report_recent_members_callback',
		'context'  => 'grid',
		'capability' => 'pmpro_memberslist',
		'columns' => 2,
		'grid_column_start' => 1,
	),
	'pmpro_dashboard_report_recent_orders' => array(
		'title'    => esc_html__( 'Recent Orders', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_report_recent_orders_callback',
		'context'  => 'grid',
		'capability' => 'pmpro_orders',
		'columns' => 2,
		'grid_column_start' => 3,
	),
	// Row 4
	'pmpro_dashboard_get_involved' => array(
		'title'    => esc_html__( 'Get Involved', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_get_involved_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 1,
	),
	'pmpro_dashboard_follow_us' => array(
		'title'    => esc_html__( 'Follow Us', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_follow_us_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 2,
	),
	'pmpro_dashboard_events' => array(
		'title'    => esc_html__( 'Upcoming Events', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_events_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 3,
	),
	'pmpro_dashboard_news_updates' => array(
		'title'    => esc_html__( 'News and Updates', 'paid-memberships-pro' ),
		'callback' => 'pmpro_dashboard_news_updates_callback',
		'context'  => 'grid',
		'capability' => '',
		'columns' => 1,
		'grid_column_start' => 4,
	),
));

/**
 * Add all the meta boxes for the dashboard.
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
 * Helper function to render dashboard metaboxes with saved order.
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
<script type="text/javascript">
jQuery(document).ready(function($) {    
    // Very simple sortable implementation
    $('#dashboard-widgets').sortable({
        items: '.postbox',
        handle: '.hndle',
        cursor: 'move',
        opacity: 0.8,
        placeholder: 'ui-sortable-placeholder',
        tolerance: 'pointer',
        stop: function(event, ui) {
            // Get all metabox IDs in their new order
            var newOrder = [];
            $('#dashboard-widgets .postbox').each(function() {
                var id = $(this).attr('id');
                if (id) {
                    newOrder.push(id);
                }
            });
                        
            // Save the new order via AJAX
            if (newOrder.length > 0) {
                var nonceValue = $('#pmpro_metabox_nonce').val();
                
                if (!nonceValue) {
                    console.error('Nonce field not found or empty');
                    return;
                }
                
                var data = {
                    action: 'pmpro_save_metabox_order',
                    pmpro_metabox_nonce: nonceValue,
                    order: newOrder.join(',')
                };
                                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    timeout: 10000,
                    error: function(xhr, status, error) {
                        console.error('AJAX Error - Status:', status);
                        console.error('AJAX Error - Error:', error);
                    }
                });
            }
        }
    });
    
    // Disable WordPress postbox functionality completely
    if (typeof postboxes !== 'undefined') {
        postboxes.handle_click = function() { return false; };
        postboxes.add_postbox_toggles = function() { return false; };
    }
});
</script>
<?php

/**
 * Callback function for pmpro_dashboard_welcome meta box.
 */
function pmpro_dashboard_welcome_callback() { ?>
	<div class="pmpro-dashboard-welcome-columns">
		<div class="pmpro-dashboard-welcome-column">
			<br />
					<iframe width="560" height="315" src="https://www.youtube.com/embed/IZpS9Mx76mw?si=A6OKdMHT6eBRIs9y" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				<p>
					<?php echo esc_html( __( 'For more guidance as you begin these steps,', 'paid-memberships-pro' ) ); ?>
					<a href="https://www.paidmembershipspro.com/documentation/initial-plugin-setup/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=documentation&utm_content=initial-plugin-setup" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'view the Initial Setup Guide and Docs.', 'paid-memberships-pro' ); ?></a>
				</p>
		</div>
		<div class="pmpro-dashboard-welcome-column">
			<?php global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready; ?>
			<h3><?php esc_html_e( 'Initial Setup', 'paid-memberships-pro' ); ?></h3>
			<ul>
				<?php if ( current_user_can( 'pmpro_membershiplevels' ) ) { ?>
					<li>
						<?php if ( empty( $pmpro_level_ready ) ) { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels&showpopup=1' ) );?>"><i class="dashicons dashicons-marker"></i> <?php esc_html_e( 'Create a Membership Level', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) );?>"><i class="dashicons dashicons-yes-alt"></i> <?php esc_html_e( 'View Membership Levels', 'paid-memberships-pro' ); ?></a>
						<?php } ?>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
					<li>
						<?php if ( empty( $pmpro_pages_ready ) ) { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) );?>"><i class="dashicons dashicons-marker"></i> <?php esc_html_e( 'Generate Membership Pages', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) );?>"><i class="dashicons dashicons-yes-alt"></i> <?php esc_html_e( 'Manage Membership Pages', 'paid-memberships-pro' ); ?>
						<?php } ?>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
					<li>
						<?php if ( empty( $pmpro_gateway_ready ) ) { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) );?>"><i class="dashicons dashicons-marker"></i> <?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) );?>"><i class="dashicons dashicons-yes-alt"></i> <?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?></a>
						<?php } ?>
					</li>
				<?php } ?>
			</ul>
			<h3><?php esc_html_e( 'Other Settings', 'paid-memberships-pro' ); ?></h3>
			<ul class="pmpro-dashboard-list-icons">
				<?php if ( current_user_can( 'pmpro_userfields' ) ) { ?>
				<li>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-userfields' ), get_admin_url(null, 'admin.php' ) ) ); ?>"><?php esc_attr_e( 'Manage User Fields', 'paid-memberships-pro' ); ?></a>
				</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_emailtemplates' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailtemplates' ) );?>"><?php esc_html_e( 'Customize Email Templates', 'paid-memberships-pro' );?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_designsettings' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-designsettings' ) );?>"><?php esc_html_e( 'View Design Settings', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_addons' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-addons' ) );?>"><?php esc_html_e( 'Explore Add Ons for Additional Features', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>
			</ul>
		</div> <!-- end pmpro-dashboard-welcome-column -->
	</div> <!-- end pmpro-dashboard-welcome-columns -->
	<?php
}

/**
 * Callback function for pmpro_dashboard_welcome meta box (side)
 */
function pmpro_dashboard_license_status_callback() { ?>
	<?php
		// Get saved license.
		$key = get_option( 'pmpro_license_key', '' );
		$pmpro_license_check = get_option( 'pmpro_license_check', array( 'license' => false, 'enddate' => 0 ) );
	?>
	<?php if ( ! pmpro_license_isValid() && empty( $key ) ) { ?>
		<p class="pmpro_message pmpro_error">
			<strong><?php esc_html_e( 'No support license key found.', 'paid-memberships-pro' ); ?></strong><br />
			<?php echo wp_kses_post( sprintf(__( '<a href="%s">Enter your key here</a>', 'paid-memberships-pro' ), esc_url( admin_url( 'admin.php?page=pmpro-license' ) ) ) );?>
		</p>
	<?php } elseif ( ! pmpro_license_isValid() ) { ?>
		<p class="pmpro_message pmpro_alert">
			<strong><?php esc_html_e( 'Your license is invalid or expired.', 'paid-memberships-pro' ); ?></strong><br />
			<?php echo wp_kses_post( sprintf(__( '<a href="%s">View your membership account</a> to verify your license key.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-dashboard%26utm_campaign%3Dmembership-account%26utm_content%3Dverify-license-key' ) );?>
		</p>
		<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a></p>
	<?php } elseif ( pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
		<p class="pmpro_message pmpro_alert"><?php echo wp_kses_post( sprintf(__('Your <strong>%1$s</strong> key is active. %1$s accounts include access to documentation and free downloads.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ) );?></p>
		<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a></p>
	<?php } else { ?>
		<p class="pmpro_message pmpro_success"><?php echo wp_kses_post( sprintf(__( '<strong>Thank you!</strong> A valid <strong>%s</strong> license key has been used to activate your support license on this site.', 'paid-memberships-pro' ), ucwords($pmpro_license_check['license'])));?></p>
		<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a></p>
		<p><a class="button button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fnew-topic%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dsupport%26utm_content%3Dnew-support-ticket" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Support Ticket', 'paid-memberships-pro' ); ?></a></p>
	<?php } ?>

	<?php if ( ! pmpro_license_isValid() || pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
		<hr />
		<p><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?></p>
		<p><a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=pricing&utm_content=upgrade" target="_blank" rel="noopener noreferrer" class="button button-hero"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a></p>
	<?php } ?>
	<?php
}

/*
 * Callback function for pmpro_dashboard_report_recent_members meta box to show last 5 recent members and a link to the Members List.
 */
function pmpro_dashboard_report_recent_members_callback() {
	global $wpdb;

	// Check if we have a cache.
	$theusers = get_transient( 'pmpro_dashboard_report_recent_members' );
	if ( false === $theusers ) {
		// No cached value. Get the users.
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP( CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone) ) as startdate, UNIX_TIMESTAMP( CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone) ) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE mu.membership_id > 0 AND mu.status = 'active' GROUP BY u.ID ORDER BY u.user_registered DESC LIMIT 5";
		$sqlQuery = apply_filters( 'pmpro_members_list_sql', $sqlQuery );
		$theusers = $wpdb->get_results( $sqlQuery );
		set_transient( 'pmpro_dashboard_report_recent_members', $theusers, 3600 * 24 );
	}
	?>
	<span id="pmpro_report_members" class="pmpro_report-holder">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Username', 'paid-memberships-pro' );?></th>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' );?></th>
					<th><?php esc_html_e( 'Joined', 'paid-memberships-pro' );?></th>
					<th><?php esc_html_e( 'Expires', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $theusers ) ) { ?>
				<tr>
					<td colspan="4"><p><?php esc_html_e( 'No members found.', 'paid-memberships-pro' ); ?></p></td>
				</tr>
			<?php } else {
				foreach ( $theusers as $auser ) {
					$auser = apply_filters( 'pmpro_members_list_user', $auser );
					//get meta
					$theuser = get_userdata( $auser->ID ); 
					
					// Lets check again if the user exists as it may be pulling "old data" from the transient.
					if ( ! isset( $theuser->ID ) ) {
						continue;
					}
					?>
					<tr>
						<td class="username column-username">
							<?php echo get_avatar($theuser->ID, 32)?>
							<strong>
								<?php
									$userlink = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$theuser->ID ), admin_url( 'admin.php' ) ) ) . '">' . esc_attr( $theuser->user_login ) . '</a>';
									$userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, $theuser );
									echo wp_kses_post( $userlink );
								?>
							</strong>
						</td>
						<td><?php echo esc_html( $auser->membership ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $theuser->user_registered ), current_time( 'timestamp' ) ) ) ); ?></td>
						<td><?php echo wp_kses_post( pmpro_get_membership_expiration_text( $auser->membership_id, $theuser ) ); ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</span>
	<?php if ( ! empty( $theusers ) ) { ?>
		<p class="pmpro_report-button"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-memberslist' ) ); ?>"><?php esc_html_e( 'View All Members ', 'paid-memberships-pro' ); ?></a></p>
	<?php } ?>
	<?php
}

/*
 * Callback function for pmpro_dashboard_report_recent_orders meta box to show last 5 recent orders and a link to view all Orders.
 */
function pmpro_dashboard_report_recent_orders_callback() {
	global $wpdb;

	// Check if we have a cache.
	$order_ids = get_transient( 'pmpro_dashboard_report_recent_orders' );
	if ( false === $order_ids) {
		// No cached value. Get the orders.
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS id FROM $wpdb->pmpro_membership_orders ORDER BY id DESC, timestamp DESC LIMIT 5";
		$order_ids = $wpdb->get_col( $sqlQuery );
		set_transient( 'pmpro_dashboard_report_recent_orders', $order_ids, 3600 * 24 );
	}
	?>
	<span id="pmpro_report_orders" class="pmpro_report-holder">
		<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr class="thead">
				<th><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'User', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
			</tr>
			</thead>
			<tbody id="orders" class="orders-list">
			<?php
				if ( empty( $order_ids ) ) { ?>
					<tr>
						<td colspan="6"><p><?php esc_html_e( 'No orders found.', 'paid-memberships-pro' ); ?></p></td>
					</tr>
				<?php } else {
					foreach ( $order_ids as $order_id ) {
					$order            = new MemberOrder();
					$order->nogateway = true;
					$order->getMemberOrderByID( $order_id );
					?>
					<tr>
						<td>
							<a href="admin.php?page=pmpro-orders&order=<?php echo esc_html( $order->id ); ?>"><?php echo esc_html( $order->code ); ?></a>
						</td>
						<td class="username column-username">
							<?php $order->getUser(); ?>
							<?php if ( ! empty( $order->user ) ) { ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$order->user->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $order->user->user_login ); ?></a>
							<?php } elseif ( $order->user_id > 0 ) { ?>
								[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
							<?php } else { ?>
								[<?php esc_html_e( 'none', 'paid-memberships-pro' ); ?>]
							<?php } ?>
							
							<?php if ( ! empty( $order->billing->name ) ) { ?>
								<br /><?php echo esc_html( $order->billing->name ); ?>
							<?php } ?>
						</td>
						<td>
							<?php
								$level = pmpro_getLevel( $order->membership_id );
								if ( ! empty( $level ) ) {
									echo esc_html( $level->name );
								} elseif ( $order->membership_id > 0 ) { ?>
									[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
								<?php } else { ?>
									[<?php esc_html_e( 'none', 'paid-memberships-pro' ); ?>]
								<?php }
							?>
						</td>
						<td><?php echo pmpro_escape_price( $order->get_formatted_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td>
							<?php echo esc_html( $order->gateway ); ?>
							<?php if ( $order->gateway_environment == 'test' ) {
								echo '(test)';
							} ?>
							<?php if ( ! empty( $order->status ) ) {
								echo '(' . esc_html( $order->status ) . ')'; 
							} ?>
						</td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), $order->getTimestamp() ) ); ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</span>
	<?php if ( ! empty( $order_ids ) ) { ?>
		<p class="pmpro_report-button"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-orders' ) ); ?>"><?php esc_html_e( 'View All Orders ', 'paid-memberships-pro' ); ?></a></p>
	<?php } ?>
	<?php
}


/**
 * Callback function for pmpro_dashboard_get_involved meta box
 */
function pmpro_dashboard_get_involved_callback() { ?>
	<p><?php esc_html_e( 'Join the PMPro Slack community to connect with other PMPro users and developers.', 'paid-memberships-pro' ); ?></p>
	<p><a href="https://www.paidmembershipspro.com/slack/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=slack&utm_content=join-community" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Join the Community', 'paid-memberships-pro' ); ?></a></p>
	<?php
}

/**
 * Callback function for pmpro_dashboard_follow_us meta box
 */
function pmpro_dashboard_follow_us_callback() { ?>
	<ul class="pmpro-dashboard-list-icons">
		<li><a href="https://github.com/strangerstudios/paid-memberships-pro" target="_blank"><img src="<?php echo esc_url( PMPRO_URL . '/images/github.svg' ); ?>" /> <?php esc_html_e( 'GitHub', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://www.youtube.com/channel/UCFtMIeYJ4_YVidi1aq9kl5g/" target="_blank"><img src="<?php echo esc_url( PMPRO_URL . '/images/youtube.svg' ); ?>" /> <?php esc_html_e( 'YouTube', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://www.facebook.com/PaidMembershipsPro" target="_blank"><img src="<?php echo esc_url( PMPRO_URL . '/images/facebook.svg' ); ?>" /> <?php esc_html_e( 'Facebook', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://x.com/pmproplugin" target="_blank"><img src="<?php echo esc_url( PMPRO_URL . '/images/twitter-x.svg' ); ?>" /> <?php esc_html_e( '@pmproplugin on X', 'paid-memberships-pro' ); ?></a></li>
	</ul>
	<?php
}

/**
 * Callback function for pmpro_dashboard_events meta box
 */
function pmpro_dashboard_events_callback() { ?>
	<p><?php esc_html_e( 'Join us for a masterclass, open office hours, or virtual Q&A session to get help building and growing your membership business.', 'paid-memberships-pro' ); ?></p>
	<p><a href="https://www.paidmembershipspro.com/live/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=slack&utm_content=events" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View the Event Calendar', 'paid-memberships-pro' ); ?></a></p>
	<?php
}

/*
 * Callback function for pmpro_dashboard_news_updates meta box to show RSS Feed from Paid Memberships Pro blog.
 */
function pmpro_dashboard_news_updates_callback() {

	// Get RSS Feed(s)
	include_once( ABSPATH . WPINC . '/feed.php' );

	// Get a SimplePie feed object from the specified feed source.
	$rss = fetch_feed( 'https://www.paidmembershipspro.com/feed/' );

	$maxitems = 0;

	if ( ! is_wp_error( $rss ) ) : // Checks that the object is created correctly

		// Figure out how many total items there are, but limit it to 5.
		$maxitems = $rss->get_item_quantity( 5 );

		// Build an array of all the items, starting with element 0 (first element).
		$rss_items = $rss->get_items( 0, $maxitems );

		// Shuffle the order and get a random one item to display.
		shuffle( $rss_items );
	endif;
	?>

	<ul>
		<?php if ( $maxitems == 0 ) : ?>
			<li><?php esc_html_e( 'No news found.', 'paid-memberships-pro' ); ?></li>
		<?php else : ?>
			<li>
				<a href="<?php echo esc_url( $rss_items[0]->get_permalink() ); ?>"
					title="<?php echo esc_attr( sprintf( __( 'Posted %s', 'paid-memberships-pro' ), date_i18n( get_option( 'date_format' ), $rss_items[0]->get_date( 'U' ) ) ) ); ?>">
					<?php echo esc_html( $rss_items[0]->get_title() ); ?>
				</a>
				<?php echo esc_html( date_i18n( get_option( 'date_format' ), $rss_items[0]->get_date( 'U' ) ) ); ?>
			</li>
		<?php endif; ?>
	</ul>
	<p><a href="https://www.paidmembershipspro.com/blog/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=blog&utm_content=news-updates-metabox"><?php esc_html_e( 'Read the Blog', 'paid-memberships-pro' ); ?></a></p>
	<?php
}

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