<?php
/**
 * Paid Memberships Pro Dashboard Recent Orders Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 2.6.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
							<?php echo esc_html( pmpro_get_gateway_nicename( $order->gateway ) ); ?>
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
	<?php
}