<?php
/**
 * View Order screen (read-only).
 *
 */

global $wpdb;

// Get the user, membership, and subscription for this order.
$order->getUser();
$order->getMembershipLevel();
$subscription = $order->get_subscription();

?>

<div class="pmpro_two_col pmpro_two_col-right">

	<div class="pmpro_main">
		<div id="pmpro_order-view" class="pmpro_section">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php printf( esc_html__( 'Order # %s', 'paid-memberships-pro' ), esc_html( $order->code ) ); ?>
					<?php
						if ( ! empty( $order->status ) ) {
							if ( in_array( $order->status, array( '', 'success', 'cancelled' ) ) ) {
								$display_status = __( 'Paid', 'paid-memberships-pro' );
								$tag_style = 'success';
							} else {
								$display_status = ucwords( $order->status );
								if ( in_array( $order->status, array( 'error', 'refunded' ) ) ) {
									$tag_style = 'error';
								} else {
									$tag_style = 'alert';
								}
							}
							?>
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value pmpro_tag pmpro_tag-' . $tag_style ) ); ?>"><?php echo esc_html( $display_status ); ?></span>
							<?php
						}
					?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<ul class="pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2">
					<?php
						// Build the order meta.
						$pmpro_order_single_meta = array();

						// Order date.
						$pmpro_order_single_meta['order_date'] = array(
							'label' => __( 'Order date', 'paid-memberships-pro' ),
							'value' => sprintf(
								// translators: %1$s is the date and %2$s is the time.
								__( '%1$s at %2$s', 'paid-memberships-pro' ),
								date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
								date_i18n( get_option( 'time_format' ), $order->getTimestamp() )
							)
						);

						// Payment method.
						if ( $order->accountnumber ) {
							$pmpro_order_single_meta['payment_method'] = array(
								'label' => __( 'Payment method', 'paid-memberships-pro' ),
								'value' => ucwords( $order->cardtype ) . ' ' . __( 'ending in', 'paid-memberships-pro' ) . ' ' . last4( $order->accountnumber ),
							);
						} else if ( $order->payment_type === 'Check' && ! empty( get_option( 'pmpro_check_gateway_label' ) ) ) {
							$order->payment_type_nicename = get_option( 'pmpro_check_gateway_label' );
							$pmpro_order_single_meta['payment_method'] = array(
								'label' => __( 'Payment method', 'paid-memberships-pro' ),
								'value' => $order->payment_type_nicename,
							);
						} elseif ( ! empty( $order->payment_type ) ) {
							$pmpro_order_single_meta['payment_method'] = array(
								'label' => __( 'Payment method', 'paid-memberships-pro' ),
								'value' => $order->payment_type,
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
						if ( $order->has_billing_address() ) {
							$pmpro_order_single_meta['bill_to']['value'] = pmpro_formatAddress(
								$order->billing->name,
								$order->billing->street,
								$order->billing->street2,
								$order->billing->city,
								$order->billing->state,
								$order->billing->zip,
								$order->billing->country,
								$order->billing->phone
							);
						} elseif ( ! empty( $order->user ) ) {
							$pmpro_order_single_meta['bill_to']['value'] = $order->user->display_name . '<br />' . $order->user->user_email;
						} else {
							$pmpro_order_single_meta['bill_to']['value'] = '['. esc_html__( 'deleted', 'paid-memberships-pro' ).']';
						}

						/**
						 * Filter to add, edit, or remove information in the meta section of the single order frontend page.
						 *
						 * @since 3.1
						 * @param array $pmpro_order_single_meta Array of meta information.
						 * @param object $order The PMPro Invoice/Order object.
						 * @return array $pmpro_order_single_meta Array of meta information.
						 */
						$pmpro_order_single_meta = apply_filters( 'pmpro_order_single_meta', $pmpro_order_single_meta, $order );

						// Display the meta.
						foreach ( $pmpro_order_single_meta as $key => $value ) {
							?>
							<li id="pmpro_order_single-meta-<?php echo esc_attr( $key ); ?>" class="pmpro_list_item">
								<span class="pmpro_list_item_label"><?php echo esc_html( $value['label'] ); ?></span>
								<?php echo wp_kses_post( $value['value'] ); ?>
							</li>
							<?php
						}
					?>
				</ul>

				<?php
					// Get the price parts.
					$pmpro_price_parts = pmpro_get_price_parts( $order, 'array' );
					if ( empty( $pmpro_price_parts ) ) {
						// If no price parts, this is a $0 order. Show 0 for total.
						$pmpro_price_parts = array( 'total' => array( 'label' => __( 'Total', 'paid-memberships-pro' ), 'value' => $order->get_formatted_total() ) );
					}

					// If the order is refunded, add to price parts.
					if ( $order->status == 'refunded' ) {
						$pmpro_price_parts['refunded']['label'] = __( 'Refunded', 'paid-memberships-pro' );
						$pmpro_price_parts['refunded']['value'] = $pmpro_price_parts['total']['value'];
					}

					// More order total to the end.
					if ( isset( $pmpro_price_parts['total'] ) ) {
						$total_part = $pmpro_price_parts['total'];
						unset( $pmpro_price_parts['total'] );
						$pmpro_price_parts['total'] = $total_part;
					}

					// If the level was deleted, set the name to the level ID.
					if ( empty( $order->membership_level ) ) {
						$order->membership_level = new stdClass();
						/* translators: %s: level ID */
						$order->membership_level->name = sprintf( __( 'Level ID %s', 'paid-memberships-pro' ), $order->membership_id );
					}
				?>

				<?php
					if ( (float)$order->total > 0 && in_array( $order->status, array( '', 'success', 'cancelled' ) ) ) {
						?>
						<h3 class="pmpro_font-large">
							<?php
								echo esc_html(
									sprintf(
										__( '%1$s paid on %2$s', 'paid-memberships-pro' ),
										//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										pmpro_escape_price( $pmpro_price_parts['total']['value'] ),
										date_i18n( get_option( 'date_format' ), $order->getTimestamp() )
									)
								);
							?>
						</h3>
						<?php
					}
				?>
				<table class="pmpro_table pmpro_table-fixed">
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
											$order->membership_level->name,
											$order->code,
										)
									);
								?>
								<?php
									if ( ! empty( $order->billing->name ) ) {
										echo '<p>' . esc_html(
											sprintf(
												// translators: 1: user display name, 2: user email
												__( 'Account: %1$s (%2$s)', 'paid-memberships-pro' ),
												$order->user->display_name,
												$order->user->user_email
											)
										) . '</p>';
									}
								?>
								<?php
									$subscription_period_end = pmpro_get_subscription_period_end_date_for_order( $order, get_option( 'date_format' ) );
									$order_date = date_i18n( get_option( 'date_format' ), $order->getTimestamp() );
									if ( ! empty( $subscription_period_end ) && $subscription_period_end !== $order_date ) {
										?>
										<p>
											<?php echo esc_html( sprintf( __( '%1$s to %2$s', 'paid-memberships-pro' ), $order_date, $subscription_period_end ) ); ?>
										</p>
										<?php
									}
								?>
							</th>
							<td data-title="<?php esc_attr_e( 'Amount', 'paid-memberships-pro' ); ?>">
								<?php echo pmpro_escape_price( $order->get_formatted_subtotal() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

				<?php if ( $order->getDiscountCode() ) { ?>
					<ul class="pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2">
						<li class="pmpro_list_item">
							<span class="pmpro_list_item_label"><?php esc_html_e('Discount Code', 'paid-memberships-pro' ); ?></span>
							<span class="pmpro_tag pmpro_tag-discount-code"><?php echo esc_html( $order->discount_code->code ); ?></span>
						</li>
					</ul>
				<?php } ?>

			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->

		<div id="pmpro_order-view-gateway" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Payment Gateway Information', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<ul class="pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2">
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></span>
						<?php echo esc_html( pmpro_get_gateway_nicename( $order->gateway ) ); ?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Environment', 'paid-memberships-pro' ); ?></span>
						<?php echo esc_html( $order->gateway_environment ); ?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Payment Transaction ID', 'paid-memberships-pro' ); ?></span>
						<?php echo empty( $order->payment_transaction_id ) ? esc_html__( 'N/A', 'paid-memberships-pro' ) : '<code>' . esc_html( $order->payment_transaction_id ) . '</code>'; ?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Subscription ID', 'paid-memberships-pro' ); ?></span>
						<?php if ( empty( $subscription ) ) {
							echo esc_html__( 'N/A', 'paid-memberships-pro' );
						} else {
							echo '<code>' . esc_html( $order->subscription_transaction_id ) . '</code>';
							?>
							<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => (int) $subscription->get_id() ), admin_url( 'admin.php' ) ) ); ?>">
								<?php esc_html_e( 'View Subscription', 'paid-memberships-pro' ); ?>
							</a></p>
							<?php
							}
						?>
					</li>
				</ul>
			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->
		<?php
		/**
		 * Allow adding additional content to the order view page.
		 *
		 * @since 3.6
		 *
		 * @param MemberOrder $order The order object.
		 */
		do_action( 'pmpro_after_order_view_main', $order );
		?>
	</div> <!-- .pmpro_main -->
	<div class="pmpro_sidebar">
		<div id="pmpro_order-view-member" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Member Information', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php if ( ! empty( $order->user ) ) { ?>
					<div class="pmpro_member-box">
						<div class="pmpro_member-box-avatar">
							<?php echo get_avatar( (int) $order->user->ID, 64 ); ?>
						</div>
						<div class="pmpro_member-box-info">
							<h2><strong><?php echo esc_html( $order->user->display_name ); ?></strong></h2>
							<div class="pmpro_member-box-actions">
								<?php
								$actions = array(
									'edit_member' => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $order->user->ID ), admin_url( 'admin.php' ) ) ),
										esc_html__( 'Edit Member', 'paid-memberships-pro' )
									),
									'edit_user'   => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'user_id' => (int) $order->user->ID ), admin_url( 'user-edit.php' ) ) ),
										esc_html__( 'Edit User', 'paid-memberships-pro' )
									),
								);
								$actions_html = array();
								foreach ( $actions as $class => $link_html ) {
									$actions_html[] = sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $class ), $link_html );
								}
								echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</div>
						</div>
					</div>
				<?php } else { ?>
					<p><?php esc_html_e( 'The user ID associated with this order is not a valid user.', 'paid-memberships-pro' ); ?></p>
				<?php } ?>
			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->

		<div id="pmpro_order-view-actions" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Order Actions', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
					// Define the default actions as a named array.
					$order_actions = array();

					// Add a "Mark as Paid" button if allowed.
					if ( $order->status === 'pending' && $order->payment_type === 'Check' ) {
						$mark_paid_text = esc_html(
							sprintf(
								// translators: %s is the Order Code.
								__( 'Mark the payment for order %s as received. The user and admin may receive an email confirmation after the order update is processed. Are you sure you want to mark this order as paid?', 'paid-memberships-pro' ),
								str_replace( "'", '', $order->code )
							)
						);
						$mark_paid_nonce_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'       => 'pmpro-orders',
									'action'     => 'mark_payment_received',
									'paid_order' => $order->id,
									'order'      => $order->id,
									'id'         => $order->id,
								),
								admin_url( 'admin.php' )
							),
							'mark_payment_received',
							'pmpro_orders_nonce'
						);
						$order_actions['mark_order_paid'] = array(
							'title'   => esc_attr( sprintf( __( 'Mark order # %s as paid', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
							'href'    => esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $mark_paid_text ) . ', ' . wp_json_encode( $mark_paid_nonce_url ) . '); void(0);' ),
							'class'   => 'button is-success pmpro-has-icon pmpro-has-icon-image-rotate',
							'label'   => esc_html__( 'Mark Order as Paid', 'paid-memberships-pro' ),
						);
					}

					$order_actions['edit'] = array(
						'title'   => esc_attr( sprintf( __( 'Edit order # %s', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
						'href'    => esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'id' => $order->id, 'edit' => 1 ), admin_url( 'admin.php' ) ) ),
						'class'   => 'button button-secondary pmpro-has-icon pmpro-has-icon-edit',
						'label'   => esc_html__( 'Edit Order', 'paid-memberships-pro' ),
					);

					$order_actions['print'] = array(
						'title'   => esc_attr( sprintf( __( 'Print or save order # %s as PDF', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
						'href'    => esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'id' => $order->id ), admin_url( 'admin-ajax.php' ) ) ),
						'target'  => '_blank',
						'class'   => 'button button-secondary pmpro-has-icon pmpro-has-icon-printer',
						'label'   => esc_html__( 'Print or Save as PDF', 'paid-memberships-pro' ),
					);

					$order_actions['email'] = array(
						'title'   => esc_attr( sprintf( __( 'Send order # %s via email', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
						'href'    => '#TB_inline?width=600&height=200&inlineId=email_order',
						'class'   => 'thickbox email_link button button-secondary pmpro-has-icon pmpro-has-icon-email',
						'data-order' => esc_attr( $order->id ),
						'label'   => esc_html__( 'Send Order Via Email', 'paid-memberships-pro' ),
					);

					$order_actions['invoice'] = array(
						'title'   => esc_attr( sprintf( __( 'View order # %s as member', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
						'href'    => esc_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
						'target'  => '_blank',
						'class'   => 'button button-secondary pmpro-has-icon pmpro-has-icon-admin-users',
						'label'   => esc_html__( 'View Order As Member', 'paid-memberships-pro' ),
					);

					// Add the "Recheck Payment" button if allowed.
					if ( 'token' === $order->status && pmpro_can_check_token_order_for_completion( $order->id ) ) {
						$recheck_nonce_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'   => 'pmpro-orders',
									'action' => 'check_token_order',
									'token_order' => $order->id,
									'id'     => $order->id,
								),
								admin_url( 'admin.php' )
							),
							'check_token_order',
							'pmpro_orders_nonce'
						);
						$order_actions['check_token_order'] = array(
							'title'   => esc_attr(
								sprintf(
									/* translators: %s is the Order Code. */
									__( 'Recheck payment status for order # %s', 'paid-memberships-pro' ),
									$order->code
								)
							),
							'href'    => esc_url( $recheck_nonce_url ),
							'class'   => 'button button-secondary pmpro-has-icon pmpro-has-icon-image-rotate',
							'label'   => esc_html__( 'Recheck Payment Status', 'paid-memberships-pro' )
						);
					}

					// Add the "Refund" button if allowed.
					if ( pmpro_allowed_refunds( $order ) ) {
						$refund_text = esc_html(
							sprintf(
								// translators: %s is the Order Code.
								__( 'Refund order %s at the payment gateway. This action is permanent. The user and admin will receive an email confirmation after the refund is processed. Are you sure you want to refund this order?', 'paid-memberships-pro' ),
								str_replace( "'", '', $order->code )
							)
						);
						$refund_nonce_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'   => 'pmpro-orders',
									'action' => 'refund_order',
									'refund' => $order->id,
									'id'     => $order->id,
								),
								admin_url( 'admin.php' )
							),
							'refund_order',
							'pmpro_orders_nonce'
						);
						$order_actions['refund'] = array(
							'title'   => esc_attr( sprintf( __( 'Refund order # %s', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
							'href'    => esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $refund_text ) . ', ' . wp_json_encode( $refund_nonce_url ) . '); void(0);' ),
							'class'   => 'button button-secondary pmpro-has-icon pmpro-has-icon-image-rotate',
							'label'   => esc_html__( 'Refund Order', 'paid-memberships-pro' ),
						);
					}

					// Add the "Delete" button.
					$delete_text = esc_html(
						sprintf(
							// translators: %s is the Order Code.
							__( 'Deleting orders is permanent and can affect active users. Are you sure you want to delete order %s?', 'paid-memberships-pro' ),
							str_replace( "'", '', $order->code )
						)
					);
					$delete_nonce_url = wp_nonce_url(
						add_query_arg(
							array(
								'page'   => 'pmpro-orders',
								'action' => 'delete_order',
								'delete' => $order->id,
							),
							admin_url( 'admin.php' )
						),
						'delete_order',
						'pmpro_orders_nonce'
					);
					$order_actions['delete'] = array(
						'title'   => esc_attr( sprintf( __( 'Delete order # %s', 'paid-memberships-pro' ), esc_html( $order->code ) ) ),
						'href'    => esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $delete_text ) . ', ' . wp_json_encode( $delete_nonce_url ) . '); void(0);' ),
						'class'   => 'button is-destructive pmpro-has-icon pmpro-has-icon-trash',
						'label'   => esc_html__( 'Delete Order', 'paid-memberships-pro' ),
					);

					/**
					 * Allow filtering of actions on the single order view admin screen.
					 *
					 * @since 3.6
					 *
					 * @param array $order_actions The array of order actions.
					 * @param object $order The order object.
					 *
					 * @return array The filtered array of order actions.
					 */
					$order_actions = apply_filters( 'pmpro_order_view_actions', $order_actions, $order );

					// Output the actions.
					foreach ( $order_actions as $key => $action ) {
						?>
						<a
							<?php if ( ! empty( $action['title'] ) ) : ?>title="<?php echo esc_attr( $action['title'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['href'] ) ) : ?>href="<?php echo esc_attr( $action['href'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['target'] ) ) : ?>target="<?php echo esc_attr( $action['target'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['class'] ) ) : ?>class="<?php echo esc_attr( $action['class'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['data-order'] ) ) : ?>data-order="<?php echo esc_attr( $action['data-order'] ); ?>"<?php endif; ?>
						>
							<?php echo esc_html( $action['label'] ); ?>
						</a>
						<?php
					}
				?>
			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->
		<div id="pmpro_order-view-notes" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Order Notes', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
				if ( ! empty( $order->notes ) ) {
					echo wp_kses_post( nl2br( $order->notes ) );
				} else {
					echo '<p>' . esc_html__( 'No notes for this order.', 'paid-memberships-pro' ) . '</p>';
				}
				?>
				<button id="pmpro_add_order_note" class="button button-secondary pmpro-has-icon pmpro-has-icon-plus" type="button">
					<?php esc_html_e( 'Add Order Note', 'paid-memberships-pro' ); ?>
				</button>
				<form class="pmpro_add_order_note_form" method="post" action="" style="display:none;">
					<hr />
					<p><strong><label for="notes"><?php esc_html_e( 'Order Note', 'paid-memberships-pro' ); ?></label></strong></p>
					<p><textarea id="notes" name="notes" rows="4"></textarea></p>
					<input type="hidden" name="id" value="<?php echo esc_attr( $order->id ); ?>" />
					<input type="hidden" name="action" value="add_order_note" />
					<?php wp_nonce_field( 'add_order_note', 'pmpro_orders_nonce' ); ?>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Save Note', 'paid-memberships-pro' ); ?></button>
					<button id="pmpro_cancel_add_order_note" class="button button-cancel" type="button">
						<?php esc_html_e( 'Cancel', 'paid-memberships-pro' ); ?>
					</button>
				</form>
				<script>
					jQuery(document).ready(function($) {
						$('#pmpro_add_order_note').on('click', function() {
							$('.pmpro_add_order_note_form').toggle();
							$('.pmpro_add_order_note_form').find('textarea').focus();
							$('#pmpro_add_order_note').hide();
						});
						$('#pmpro_cancel_add_order_note').on('click', function() {
							$('.pmpro_add_order_note_form').hide();
							$('.pmpro_add_order_note_form').find('textarea').val('');
							$('#pmpro_add_order_note').show();
						});
					});
				</script>
			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->
	</div> <!-- .pmpro_sidebar -->
</div> <!-- .pmpro_two_col -->
