<?php
/**
 * Add meta box to dashboard page.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'pmpro_dashboard_report_recent_orders',
		__( 'Recent Orders', 'paid-memberships-pro' ),
		'pmpro_dashboard_report_recent_orders_callback',
		'toplevel_page_pmpro-dashboard',
		'side'
	);
} );

/**
 * Callback function for pmpro_dashboard_report_recent_orders meta box to show last 5 recent orders and a link to view all Orders.
 */
function pmpro_dashboard_report_recent_orders_callback() {
	global $wpdb;

	$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS id FROM $wpdb->pmpro_membership_orders ORDER BY id DESC, timestamp DESC LIMIT 5";

	$order_ids = $wpdb->get_col( $sqlQuery );

	$totalrows = $wpdb->get_var( 'SELECT FOUND_ROWS() as found_rows' );
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
        					<a href="admin.php?page=pmpro-orders&order=<?php echo $order->id; ?>"><?php echo $order->code; ?></a>
        				</td>
        				<td class="username column-username">
        					<?php $order->getUser(); ?>
					        <?php if ( ! empty( $order->user ) ) { ?>
                                <a href="user-edit.php?user_id=<?php echo $order->user->ID; ?>"><?php echo $order->user->user_login; ?></a>
					        <?php } elseif ( $order->user_id > 0 ) { ?>
                                [<?php _e( 'deleted', 'paid-memberships-pro' ); ?>]
					        <?php } else { ?>
                                [<?php _e( 'none', 'paid-memberships-pro' ); ?>]
					        <?php } ?>

					        <?php if ( ! empty( $order->billing->name ) ) { ?>
                                <br/><?php echo $order->billing->name; ?>
					        <?php } ?>
        				</td>
                        <td>
							<?php
							$level = pmpro_getLevel( $order->membership_id );
							if ( ! empty( $level ) ) {
								echo $level->name;
							} elseif ( $order->membership_id > 0 ) { ?>
                                [<?php _e( 'deleted', 'paid-memberships-pro' ); ?>]
							<?php } else { ?>
                                [<?php _e( 'none', 'paid-memberships-pro' ); ?>]
							<?php }
							?>
                        </td>
        				<td><?php echo pmpro_escape_price( pmpro_formatPrice( $order->total ) ); ?></td>
        				<td>
                            <?php echo $order->gateway; ?>
                            <?php if ( $order->gateway_environment == 'test' ) {
	                            echo '(test)';
                            } ?>
                            <?php if ( ! empty( $order->status ) ) {
	                            echo '<br />(' . $order->status . ')';
                            } ?>
                        </td>
                        <td><?php echo date_i18n( get_option( 'date_format' ), $order->getTimestamp() ); ?></td>
        			</tr>
			        <?php
		        }
	        }
	        ?>
    		</tbody>
    	</table>
    </span>
	<?php if ( ! empty( $order_ids ) ) { ?>
        <p class="text-center"><a class="button button-primary"
                                  href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-orders' ) ); ?>"><?php esc_html_e( 'View All Orders ', 'paid-memberships-pro' ); ?></a>
        </p>
	<?php } ?>
	<?php
}
