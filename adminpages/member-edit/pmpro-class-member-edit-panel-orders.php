<?php

class PMPro_Member_Edit_Panel_Orders extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$user = self::get_user();
		$this->slug = 'orders';
		$this->title = __( 'Orders', 'paid-memberships-pro' );
		$this->title_link = empty( $user->ID ) ? '' : '<a href=' . admin_url( 'admin.php?page=pmpro-orders&order=-1&user=' . $user->ID ) . ' class="page-title-action pmpro-has-icon pmpro-has-icon-plus">' . esc_html__( 'Add New Order', 'paid-memberships-pro' ) . '</a>';
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		global $wpdb;

		//Show all invoices for user
		$invoices = $wpdb->get_results( $wpdb->prepare( "SELECT mo.*, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = %d ORDER BY mo.timestamp DESC", self::get_user()->ID ) );

		// Build the selectors for the invoices history list based on history count.
		$invoices_classes = array();
		if ( ! empty( $invoices ) && count( $invoices ) > 10 ) {
			$invoices_classes[] = "pmpro_scrollable";
		}
		$invoice_class = implode( ' ', array_unique( $invoices_classes ) );
		?>
		<div id="member-history-orders" class="<?php echo esc_attr( $invoice_class ); ?>">
			<?php if ( $invoices ) { ?>
				<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
						<?php do_action('pmpromh_orders_extra_cols_header');?>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach ( $invoices as $invoice ) { 
						$level = pmpro_getLevel( $invoice->membership_id );
						?>
						<tr>
							<td>
								<?php
									echo esc_html( sprintf(
										// translators: %1$s is the date and %2$s is the time.
										__( '%1$s at %2$s', 'paid-memberships-pro' ),
										esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $invoice->timestamp ) ) ) ),
										esc_html( date_i18n( get_option( 'time_format' ), strtotime( get_date_from_gmt( $invoice->timestamp ) ) ) )
									) );
								?>
							</td>
							<td class="order_code column-order_code has-row-actions">
								<strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $invoice->code ); ?></a></strong>
								<div class="row-actions">
									<span class="id">
										<?php echo sprintf(
											// translators: %s is the Order ID.
											esc_html__( 'ID: %s', 'paid-memberships-pro' ),
											esc_html( $invoice->id )
										); ?>
									</span> |
									<span class="edit">
										<a title="<?php esc_attr_e( 'Edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
									</span> |
									<span class="print">
										<a target="_blank" title="<?php esc_attr_e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $invoice->id ), admin_url('admin-ajax.php' ) ) ); ?>"><?php esc_html_e( 'Print', 'paid-memberships-pro' ); ?></a>
									</span>
									<?php if ( function_exists( 'pmpro_add_email_order_modal' ) ) { ?>
										|
										<span class="email">
											<a title="<?php esc_attr_e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link" data-order="<?php echo esc_attr( $invoice->id ); ?>"><?php esc_html_e( 'Email', 'paid-memberships-pro' ); ?></a>
										</span>
									<?php } ?>
								</div> <!-- end .row-actions -->
							</td>
							<td>
								<?php
									if ( ! empty( $level ) ) {
										echo esc_html( $level->name );
									} elseif ( $invoice->membership_id > 0 ) { ?>
										[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
									<?php } else {
										esc_html_e( '&#8212;', 'paid-memberships-pro' );
									}
								?>
							</td>
							<td>
								<?php echo pmpro_escape_price( pmpro_formatPrice( $invoice->total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php
									if ( ! empty( $invoice->code_id ) ) {
										$discountQuery = $wpdb->prepare( "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = %d LIMIT 1", $invoice->code_id );
										$discount_code = $wpdb->get_row( $discountQuery );
										?>
										<a class="pmpro_discount_code-tag" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'discount-code' => $invoice->code_id, 'filter' => 'with-discount-code' ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders with this discount code', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $discount_code->code ); ?></a>
										<?php
									}
								?>
							</td>
							<td>
								<?php
									if ( ! empty( $invoice->subscription_transaction_id ) ) {
										$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $invoice->subscription_transaction_id, $invoice->gateway, $invoice->gateway_environment );
										if ( ! empty( $subscription ) ) {
											// Show the transaction ID and link it if the subscription is found.
											?>
											<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url('admin.php' ) ) ); ?>">
												<?php echo esc_html( $invoice->subscription_transaction_id ); ?>
											</a>
											<?php
										} else {
											// Show the transaction ID but do not link it if the subscription is not found.
											echo esc_html( $invoice->subscription_transaction_id );
										}
									} else {
										esc_html_e( '&#8212;', 'paid-memberships-pro' );
									}
								?>
							</td>
							<td>
								<?php
									if ( empty( $invoice->status ) ) {
										esc_html_e( '&#8212;', 'paid-memberships-pro' );
									} else { ?>
										<span class="pmpro_order-status pmpro_order-status-<?php echo esc_attr( $invoice->status ); ?>">
											<?php if ( in_array( $invoice->status, array( 'success', 'cancelled' ) ) ) {
												esc_html_e( 'Paid', 'paid-memberships-pro' );
											} else {
												echo esc_html( ucwords( $invoice->status ) );
											} ?>
										</span>
										<?php
									}
								?>
							</td>
							<?php do_action( 'pmpromh_orders_extra_cols_body', $invoice ); ?>
						</tr>
						<?php
					}
				?>
				</tbody>
				</table>
			<?php } else { ?>
				<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'No membership orders found.', 'paid-memberships-pro' ); ?></td>
						</tr>
					</tbody>
				</table>
			<?php } ?>
		</div> <!-- end #member-history-orders -->
		<?php
	}

	/**
	 * Check if the current user can view this panel.
	 * Can be overridden by child classes.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function should_show() {
		return current_user_can( 'pmpro_orders' );
	}
}