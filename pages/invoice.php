<?php 
/**
 * Template: Invoice
 * Version: 3.0
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.0
 *
 * @author Paid Memberships Pro
 */
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<?php
	global $wpdb, $pmpro_invoice, $pmpro_msg, $pmpro_msgt, $current_user;

	if ( $pmpro_msg ) {
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
		<?php
	}

	if ( $pmpro_invoice ) {
		// Get the user and membership level for this order.
		$pmpro_invoice->getUser();
		$pmpro_invoice->getMembershipLevel();
		?>
		<section id="pmpro_order_single" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_order_single' ) ); ?>">
			<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>">
				<?php echo esc_html( sprintf(
					__('Order #%s on %s', 'paid-memberships-pro' ),
					$pmpro_invoice->code,
					date_i18n( get_option('date_format'), $pmpro_invoice->getTimestamp() )
				) ); ?>
			</h2>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
				<div id="pmpro_order_single-details" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmpro_order_single-details' ) ); ?>">
					<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
						<?php esc_html_e( 'Account Details', 'paid-memberships-pro' ); ?>
					</h3>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-3' ) ); ?>">
							<?php do_action("pmpro_invoice_bullets_top", $pmpro_invoice); ?>
							<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Account', 'paid-memberships-pro' );?></span>
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
									<?php echo esc_html( $pmpro_invoice->user->display_name ); ?><br />
									<?php echo esc_html( $pmpro_invoice->user->user_email ); ?>
								</span>
							</li>
							<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Membership Level', 'paid-memberships-pro' );?></span>
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo esc_html( $pmpro_invoice->membership_level->name ); ?></span>
							</li>
							<?php if ( ! empty( $pmpro_invoice->status ) ) { ?>
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Status', 'paid-memberships-pro' ); ?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
										<?php
											if ( in_array( $pmpro_invoice->status, array( '', 'success', 'cancelled' ) ) ) {
												$display_status = __( 'Paid', 'paid-memberships-pro' );
												$tag_style = 'success';
											} elseif ( $pmpro_invoice->status == 'refunded' ) {
												$display_status = __( 'Refunded', 'paid-memberships-pro' );
												$tag_style = 'error';
											} else {
												$display_status = ucwords( $pmpro_invoice->status );
												$tag_style = 'alert';
											}
										?>
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-' . $tag_style ) ); ?>"><?php echo esc_html( $display_status ); ?></span>
									</span>
								</li>
								<?php do_action( 'pmpro_invoice_bullets_bottom', $pmpro_invoice ); ?>
							<?php } ?>
						</ul>
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->

				<?php
					// Check instructions
					if ( $pmpro_invoice->gateway == "check" && ! pmpro_isLevelFree( $pmpro_invoice->membership_level ) ) { ?>
						<div id="pmpro_order_single-instructions" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmpro_order_single-instructions' ) ); ?>">
							<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
								<?php esc_html_e( 'Payment Instructions', 'paid-memberships-pro' ); ?>
							</h3>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_payment_instructions' ) ); ?>">
									<?php echo wp_kses_post( wpautop( wp_unslash( get_option( 'pmpro_instructions' ) ) ) ); ?>
								</div>
							</div> <!-- end pmpro_card_content -->
						</div> <!-- end pmpro_card -->
						<?php
					}
				?>
				<div id="pmpro_order_single-payment" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmpro_order_single-payment' ) ); ?>">
					<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
						<?php esc_html_e( 'Order Details', 'paid-memberships-pro' ); ?>
					</h3>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-3' ) ); ?>">
							<?php if ( ! empty( $pmpro_invoice->billing->street ) ) { ?>
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Billing Address', 'paid-memberships-pro' ); ?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_name' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->name ); ?></span>
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_street' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->street ); ?></span>
										<?php if($pmpro_invoice->billing->city && $pmpro_invoice->billing->state) { ?>
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_city' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->city ); ?></span>
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_state' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->state ); ?></span>
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_zip' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->zip ); ?></span>
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_country' ) ); ?>"><?php echo esc_html( $pmpro_invoice->billing->country ); ?></span>
										<?php } ?>
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_invoice-field-billing_phone' ) ); ?>"><?php echo esc_html( formatPhone($pmpro_invoice->billing->phone) ); ?></span>
									</span>
								</li>
							<?php } ?>

							<?php if ( $pmpro_invoice->getDiscountCode() ) { ?>
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-discount-code', 'pmpro_tag-discount-code' ) ); ?>"><?php echo esc_html( $pmpro_invoice->discount_code->code ); ?></span>
									</span>
								</li>
							<?php } ?>

							<?php if ( ! empty( $pmpro_invoice->accountnumber ) || ! empty( $pmpro_invoice->payment_type ) ) { ?>
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Payment Method', 'paid-memberships-pro' );?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
										<?php if($pmpro_invoice->accountnumber) { ?>
											<?php echo esc_html( ucwords( $pmpro_invoice->cardtype ) ); ?> <?php esc_html_e('ending in', 'paid-memberships-pro' );?> <?php echo esc_html( last4($pmpro_invoice->accountnumber) )?>
											<br />
											<?php esc_html_e('Expiration', 'paid-memberships-pro' );?>: <?php echo esc_html( $pmpro_invoice->expirationmonth ); ?>/<?php echo esc_html( $pmpro_invoice->expirationyear ); ?>
										<?php } else { 
											if ( $pmpro_invoice->payment_type === 'Check' && ! empty( get_option( 'pmpro_check_gateway_label' ) ) ) {
												$pmpro_invoice->payment_type = get_option( 'pmpro_check_gateway_label' );
											}
											?>
											<?php echo esc_html( $pmpro_invoice->payment_type ); ?>
										<?php } ?>
									</span>
								</li>
							<?php } ?>

							<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Total Billed', 'paid-memberships-pro' ); ?></span>
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
									<?php
										if ( (float)$pmpro_invoice->total > 0 ) {
											//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											echo pmpro_escape_price( pmpro_get_price_parts( $pmpro_invoice, 'span' ) );
										} else {
											//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											echo pmpro_escape_price( pmpro_formatPrice(0) );
										}
									?>
								</span>
							</li>
						</ul>
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</div> <!-- end pmpro_section_content -->
		</section> <!-- end pmpro_order_single -->
		<?php
	} else {
		// Get all orders for the user (the limit this returns is 100 for now).
		$orders = MemberOrder::get_orders(
			array(
				'status' => array( 'pending', 'refunded', 'success' ),
				'user_id' => $current_user->ID,
			)
		);
		?>
		<section id="pmpro_order_list" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_order_list' ) ); ?>">
			<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>">
				<?php esc_html_e( 'Order History', 'paid-memberships-pro' ); ?>
			</h2>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
				<?php
					if ( $orders ) {
						?>
						<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_table_orders', 'pmpro_table_orders' ) ); ?>">
							<thead>
								<tr>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-date' ) ); ?>"><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-level' ) ); ?>"><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-amount' ) ); ?>"><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-status' ) ); ?>"><?php esc_html_e( 'Status', 'paid-memberships-pro'); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php
								foreach( $orders as $order ) {
									// Get a member order object.
									$order_id = $order->id;
									$order = new MemberOrder;
									$order->getMemberOrderByID($order_id);
									$order->getMembershipLevel();

									// Set the display status and tag style.
									if ( in_array( $order->status, array( '', 'success', 'cancelled' ) ) ) {
										$display_status = esc_html__( 'Paid', 'paid-memberships-pro' );
										$tag_style = 'success';
									} elseif ( $order->status == 'pending' ) {
										// Some Add Ons set status to pending.
										$display_status = esc_html__( 'Pending', 'paid-memberships-pro' );
										$tag_style = 'alert';
									} elseif ( $order->status == 'refunded' ) {
										$display_status = esc_html__( 'Refunded', 'paid-memberships-pro' );
										$tag_style = 'error';
									}
									?>
									<tr id="pmpro_table_order-<?php echo esc_attr( $order->code ); ?>">
										<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-date' ) ); ?>" data-title="<?php esc_attr_e( 'Date', 'paid-memberships-pro' ); ?>"><a href="<?php echo esc_url( pmpro_url( "invoice", "?invoice=" . $order->code ) ) ?>"><?php echo esc_html( date_i18n(get_option("date_format"), $order->getTimestamp()) )?></a></td>
										<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-level' ) ); ?>" data-title="<?php esc_attr_e( 'Level', 'paid-memberships-pro' ); ?>"><?php if(!empty($order->membership_level)) echo esc_html( $order->membership_level->name ); else echo esc_html__("N/A", 'paid-memberships-pro' );?></td>
										<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-amount' ) ); ?>" data-title="<?php esc_attr_e( 'Amount', 'paid-memberships-pro' ); ?>"><?php
											//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											echo pmpro_escape_price( $order->get_formatted_total() ); ?></td>
										<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-status' ) ); ?>" data-title="<?php esc_attr_e( 'Status', 'paid-memberships-pro' ); ?>">
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-' . $tag_style ) ); ?>"><?php echo esc_html( $display_status ); ?></span>
										</td>
									</tr>
									<?php
								}
							?>
							</tbody>
						</table>
					<?php } else {
						?>
						<p><?php esc_html_e( 'No orders found.', 'paid-memberships-pro' ); ?></p>
						<?php
					}
				?>
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
		</section> <!-- end pmpro_order_list -->
		<?php
		}
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
		<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'paid-memberships-pro' );?></a></span>
		<?php if ( $pmpro_invoice ) { ?>
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-left' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "invoice" ) ) ?>"><?php esc_html_e('&larr; View All Orders', 'paid-memberships-pro' );?></a></span>
		<?php } ?>
	</div> <!-- end pmpro_actions_nav -->
</div> <!-- end pmpro -->
