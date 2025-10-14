<?php 
/**
 * Template: Invoice
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
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
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
					<?php
					$pmpro_order_action_links = array();
					$pmpro_order_action_links['print'] = '<button class="' . esc_attr( pmpro_get_element_class( 'pmpro_btn-plain pmpro_btn-print' ) ) . '" onclick="window.print()">' .
						'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-printer"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>' .
						esc_html__( 'Print or Save as PDF', 'paid-memberships-pro' ) .
					'</button>';
					/**
					 * Filter the order action links.
					 *
					 * @since 3.6
					 *
					 * @param array $pmpro_order_action_links Array of actions to display.
					 * @param MemberOrder $pmpro_invoice The PMPro Invoice/Order object.
					 */
					$pmpro_order_action_links = apply_filters( 'pmpro_order_action_links', $pmpro_order_action_links, $pmpro_invoice );
					$allowed_html = array(
						'a' => array (
							'class' => array(),
							'href' => array(),
							'id' => array(),
							'target' => array(),
							'title' => array(),
							'aria-label' => array(),
						),
						'button' => array(
							'class' => array(),
							'onclick' => array(),
						),
						'path' => array(
							'd' => array(),
							'fill' => array(),
							'stroke' => array(),
							'stroke-width' => array(),
							'stroke-linecap' => array(),
							'stroke-linejoin' => array(),
						),
						'polyline' => array(
							'points' => array(),
						),
						'rect' => array(
							'x' => array(),
							'y' => array(),
							'width' => array(),
							'height' => array(),
						),
						'span' => array(
							'class' => array(),
						),
						'svg' => array(
							'xmlns' => array(),
							'width' => array(),
							'height' => array(),
							'viewbox' => array(),
							'fill' => array(),
							'stroke' => array(),
							'stroke-width' => array(),
							'stroke-linecap' => array(),
							'stroke-linejoin' => array(),
							'class' => array(),
						),
					);
					echo wp_kses( implode( '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_action_separator' ) ) . '">' . pmpro_actions_nav_separator() . '</span>', $pmpro_order_action_links ), $allowed_html );
					?>
				</div> <!-- end pmpro_card_actions -->
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-x-large' ) ); ?>">
					<?php echo esc_html( sprintf(
						__( 'Order #%s', 'paid-memberships-pro' ),
						$pmpro_invoice->code
					) ); ?>
					<?php
						if ( ! empty( $pmpro_invoice->status ) ) {
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
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value pmpro_tag pmpro_tag-' . $tag_style ) ); ?>"><?php echo esc_html( $display_status ); ?></span>
							<?php
						}
					?>
				</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">

					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

					<div id="pmpro_order_single-meta">
						<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2' ) ); ?>">
							<?php
								// Build the order meta.
								$pmpro_order_single_meta = array();

								// Order date.
								$pmpro_order_single_meta['order_date'] = array(
									'label' => __( 'Order date', 'paid-memberships-pro' ),
									'value' => date_i18n( get_option( 'date_format' ), $pmpro_invoice->getTimestamp() ),
								);

								// Payment method.
								if ( $pmpro_invoice->accountnumber ) {
									$pmpro_order_single_meta['payment_method'] = array(
										'label' => __( 'Payment method', 'paid-memberships-pro' ),
										'value' => ucwords( $pmpro_invoice->cardtype ) . ' ' . __( 'ending in', 'paid-memberships-pro' ) . ' ' . last4( $pmpro_invoice->accountnumber ),
									);
								} else if ( $pmpro_invoice->payment_type === 'Check' && ! empty( get_option( 'pmpro_check_gateway_label' ) ) ) {
									$pmpro_invoice->payment_type = get_option( 'pmpro_check_gateway_label' );
									$pmpro_order_single_meta['payment_method'] = array(
										'label' => __( 'Payment method', 'paid-memberships-pro' ),
										'value' => $pmpro_invoice->payment_type,
									);
								} elseif ( ! empty( $pmpro_invoice->payment_type ) ) {
									$pmpro_order_single_meta['payment_method'] = array(
										'label' => __( 'Payment method', 'paid-memberships-pro' ),
										'value' => $pmpro_invoice->payment_type,
									);
								} else {
									$pmpro_order_single_meta['payment_method'] = array(
										'label' => __( 'Payment method', 'paid-memberships-pro' ),
										'value' => __( '&#8212;', 'paid-memberships-pro' ),
									);
								}

								// Pay to.
								$business_address = get_option( 'pmpro_business_address' );
								if ( ! empty( $business_address['name'] ) ) {
									$pay_to = pmpro_formatAddress(
										$business_address['name'],
										$business_address['street'],
										$business_address['street2'],
										$business_address['city'],
										$business_address['state'],
										$business_address['zip'],
										$business_address['country'],
										$business_address['phone']
									);
								} else {
									$pay_to = get_option( 'blogname' );
								}
								$pmpro_order_single_meta['pay_to'] = array(
									'label' => __( 'Pay to', 'paid-memberships-pro' ),
									'value' => $pay_to,
								);

								// Bill to.
								$pmpro_order_single_meta['bill_to']['label'] = __( 'Bill to', 'paid-memberships-pro' );
								if ( $pmpro_invoice->has_billing_address() ) {
									$pmpro_order_single_meta['bill_to']['value'] = pmpro_formatAddress(
										$pmpro_invoice->billing->name,
										$pmpro_invoice->billing->street,
										$pmpro_invoice->billing->street2,
										$pmpro_invoice->billing->city,
										$pmpro_invoice->billing->state,
										$pmpro_invoice->billing->zip,
										$pmpro_invoice->billing->country,
										$pmpro_invoice->billing->phone
									);
								} else {
									$pmpro_order_single_meta['bill_to']['value'] = $pmpro_invoice->user->display_name . '<br />' . $pmpro_invoice->user->user_email;
								}

								/**
								 * Filter to add, edit, or remove information in the meta section of the single order frontend page.
								 *
								 * @since 3.1
								 * @param array $pmpro_order_single_meta Array of meta information.
								 * @param object $pmpro_invoice The PMPro Invoice/Order object.
								 * @return array $pmpro_order_single_meta Array of meta information.
								 */
								$pmpro_order_single_meta = apply_filters( 'pmpro_order_single_meta', $pmpro_order_single_meta, $pmpro_invoice );

								// Display the meta.
								foreach ( $pmpro_order_single_meta as $key => $value ) {
									?>
									<li id="pmpro_order_single-meta-<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php echo esc_html( $value['label'] ); ?></span>
										<?php echo wp_kses_post( $value['value'] ); ?>
									</li>
									<?php
								}
							?>
						</ul>
					</div> <!-- end pmpro_order_single-meta -->

					<?php if ( has_action( 'pmpro_invoice_bullets_top' ) || has_action( 'pmpro_invoice_bullets_bottom' ) ) { ?>

						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

						<div id="pmpro_order_single-more-information">

							<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ); ?>">
								<?php
									/**
									 * pmpro_invoice_bullets_top hook allows you to add information to the billing list (at the top).
									 *
									 * @since 1.7.2
									 * @param object $pmpro_invoice The PMPro Invoice/Order object.
									 */
									do_action( 'pmpro_invoice_bullets_top', $pmpro_invoice );

									/**
									 * pmpro_invoice_bullets_bottom hook allows you to add information to the billing list (at the bottom).
									 *
									 * @since 1.7.2
									 * @param object $pmpro_invoice The PMPro Invoice/Order object.
									 */
									do_action( 'pmpro_invoice_bullets_bottom', $pmpro_invoice );
								?>
							</ul>

						</div> <!-- end pmpro_order_single-more-information -->

						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

					<?php } ?>

					<?php
						/**
						 * Add additional content to the single order frontend page before the order item details.
						 *
						 * @since 3.1
						 * @param object $pmpro_invoice The PMPro Invoice/Order object.
						 */
						do_action( 'pmpro_order_single_before_order_details', $pmpro_invoice );
					?>

					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

					<?php
						// Get the price parts.
						$pmpro_price_parts = pmpro_get_price_parts( $pmpro_invoice, 'array' );
						if ( empty( $pmpro_price_parts ) ) {
							// If no price parts, this is a $0 order. Show 0 for total.
							$pmpro_price_parts = array( 'total' => array( 'label' => __( 'Total', 'paid-memberships-pro' ), 'value' => $pmpro_invoice->get_formatted_total() ) );
						}

						// If the order is refunded, add to price parts.
						if ( $pmpro_invoice->status == 'refunded' ) {
							$pmpro_price_parts['refunded']['label'] = __( 'Refunded', 'paid-memberships-pro' );
							$pmpro_price_parts['refunded']['value'] = $pmpro_price_parts['total']['value'];
						}

						// If the level was deleted, set the name to the level ID.
						if ( empty( $pmpro_invoice->membership_level ) ) {
							$pmpro_invoice->membership_level = new stdClass();
							/* translators: %s: level ID */
							$pmpro_invoice->membership_level->name = sprintf( __( 'Level ID %s', 'paid-memberships-pro' ), $pmpro_invoice->membership_id );
						}
					?>

					<div id="pmpro_order_single-items">
						<?php
							if ( (float)$pmpro_invoice->total > 0 && in_array( $pmpro_invoice->status, array( '', 'success', 'cancelled' ) ) ) {
								?>
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>">
									<?php
										echo esc_html(
											sprintf(
												__( '%1$s paid on %2$s', 'paid-memberships-pro' ),
												//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												pmpro_escape_price( $pmpro_price_parts['total']['value'] ),
												date_i18n( get_option( 'date_format' ), $pmpro_invoice->getTimestamp() )
											)
										);
									?>
								</h3>
								<?php
							}
						?>
						<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Description', 'paid-memberships-pro' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'paid-memberships-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th data-title="<?php esc_attr_e( 'Description', 'paid-memberships-pro' ); ?>">
										<?php
											echo esc_html(
												sprintf(
													// translators: 1: level name, 2: order code
													__( '%1$s for order #%2$s', 'paid-memberships-pro' ),
													$pmpro_invoice->membership_level->name,
													$pmpro_invoice->code,
												)
											);
										?>
										<?php
											if ( ! empty( $pmpro_invoice->billing->name ) ) {
												echo '<p>' . esc_html(
													sprintf(
														// translators: 1: user display name, 2: user email
														__( 'Account: %1$s (%2$s)', 'paid-memberships-pro' ),
														$pmpro_invoice->user->display_name,
														$pmpro_invoice->user->user_email
													)
												) . '</p>';
											}
										?>
										<?php
											$subscription_period_end = pmpro_get_subscription_period_end_date_for_order( $pmpro_invoice, get_option( 'date_format' ) );
											$order_date = date_i18n( get_option( 'date_format' ), $pmpro_invoice->getTimestamp() );
											if ( ! empty( $subscription_period_end ) && $subscription_period_end !== $order_date ) {
												?>
												<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-small' ) ); ?>">
													<?php echo esc_html( sprintf( __( '%1$s to %2$s', 'paid-memberships-pro' ), $order_date, $subscription_period_end ) ); ?>
												</p>
												<?php
											}
										?>
									</th>
									<td data-title="<?php esc_attr_e( 'Amount', 'paid-memberships-pro' ); ?>">
										<?php
										echo pmpro_escape_price( $pmpro_invoice->get_formatted_subtotal() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</td>
								</tr>
							</tbody>
							<tfoot>
								<?php
									foreach ( $pmpro_price_parts as $pmpro_price_part ) { ?>
										<tr>
											<td><?php echo esc_html( $pmpro_price_part['label'] ); ?></td>
											<td data-title="<?php echo esc_attr( $pmpro_price_part['label'] ); ?>">
												<?php echo pmpro_escape_price( $pmpro_price_part['value'] ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</td>
										</tr>
										<?php
									}
								?>
							</tfoot>
						</table>
						<?php if ( $pmpro_invoice->getDiscountCode() ) { ?>
							<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2' ) ); ?>">
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Discount Code', 'paid-memberships-pro' ); ?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>">
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-discount-code', 'pmpro_tag-discount-code' ) ); ?>"><?php echo esc_html( $pmpro_invoice->discount_code->code ); ?></span>
									</span>
								</li>
							</ul>
						<?php } ?>
					</div> <!-- end pmpro_order_single-payment -->
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
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
										<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-date' ) ); ?>" data-title="<?php esc_attr_e( 'Date', 'paid-memberships-pro' ); ?>"><a href="<?php echo esc_url( pmpro_url( "invoice", "?invoice=" . $order->code ) ) ?>"><?php echo esc_html( date_i18n(get_option("date_format"), $order->getTimestamp()) )?></a></th>
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
