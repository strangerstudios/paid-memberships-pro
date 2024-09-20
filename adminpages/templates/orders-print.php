<?php
/**
 * Template for Print Orders
 *
 * @since 1.8.6
 * 
 * @var object $level
 * @var MemberOrder $order
 */
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<style>
		body {
			font-family: Arial, sans-serif;
		}
		.main, .header {
			display: block;
			margin: 0 0 20px 0;
		}
		.order, .order tr, .order th, .order td {
			border: 1px solid;
			border-collapse: collapse;
			padding: 10px;
			vertical-align: top;
		}
		.order thead th {
			font-weight: 700;
			text-align: left;
		}
		.order td:last-child {
			text-align: right;
		}
		.order tfoot th {
			font-weight: 400;
			text-align: right;
		}
		.order tfoot tr:last-child * {
			font-weight: 700;
		}
		.order {
			margin-top: 20px;
			width: 100%;
		}
		ul {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			gap: 20px;
			list-style: none;
			margin: 0;
			padding: 0;
		}
		ul li {
			margin: 0;
			padding: 0;
			width: calc( 50% - 10px );
		}
		@media screen {
			body {
				max-width: 50%;
				margin: 0 auto;
			}
		}
	</style>
</head>
<body>
	<header class="header">
		<h1><?php bloginfo( 'sitename' ); ?></h1>
		<h2><?php echo esc_html( sprintf( __( 'Order #%s', 'paid-memberships-pro' ), $order->code ) ); ?></h2>
		<p>
			<strong><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></strong><br />
			<?php echo esc_html( ucwords( $order->status ) ); ?>
		</p>
	</header>
	<main class="main">
		<ul>
		<?php
			// Build the order meta.
			$pmpro_order_single_meta = array();

			// Order date.
			$pmpro_order_single_meta['order_date'] = array(
				'label' => __( 'Order date', 'paid-memberships-pro' ),
				'value' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
			);

			// Payment method.
			if ( ! empty( $order->accountnumber ) || ! empty( $order->payment_type ) ) {
				if ( $order->accountnumber ) {
					$pmpro_order_single_meta['payment_method'] = array(
						'label' => __( 'Payment method', 'paid-memberships-pro' ),
						'value' => ucwords( $order->cardtype ) . ' ' . __( 'ending in', 'paid-memberships-pro' ) . ' ' . last4( $order->accountnumber ),
					);
				} else {
					if ( $order->payment_type === 'Check' && ! empty( get_option( 'pmpro_check_gateway_label' ) ) ) {
						$order->payment_type = get_option( 'pmpro_check_gateway_label' );
					}
					$pmpro_order_single_meta['payment_method'] = array(
						'label' => __( 'Payment method', 'paid-memberships-pro' ),
						'value' => $order->payment_type,
					);
				}
			}

			if ( (float)$order->total > 0 ) {
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
				} else {
					$pmpro_order_single_meta['bill_to']['value'] = $order->user->display_name . '<br />' . $order->user->user_email;
				}
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
				<li>
					<strong><?php echo esc_html( $value['label'] ); ?></strong><br />
					<?php echo wp_kses_post( $value['value'] ); ?>
				</li>
				<?php
			}
		?>
		</ul>
		<table class="order">
			<thead>
				<tr>
					<th><?php esc_html_e('ID', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e('Description', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e('Amount', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo esc_html( $order->membership_level->id ); ?></td>
					<td>
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
								<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-small' ) ); ?>">
									<?php echo esc_html( sprintf( __( '%1$s to %2$s', 'paid-memberships-pro' ), $order_date, $subscription_period_end ) ); ?>
								</p>
								<?php
							}
						?>
					</td>
					<td><?php echo pmpro_escape_price( $order->get_formatted_subtotal() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
			</tbody>
			<?php if ( (float)$order->total > 0 ) { ?>
				<tfoot>
					<?php
						// Get the price parts.
						$pmpro_price_parts = pmpro_get_price_parts( $order, 'array' );

						// If the order is refunded, add to price parts.
						if ( $order->status == 'refunded' ) {
							$pmpro_price_parts['refunded']['label'] = __( 'Refunded', 'paid-memberships-pro' );
							$pmpro_price_parts['refunded']['value'] = $pmpro_price_parts['total']['value'];
						}

						foreach ( $pmpro_price_parts as $pmpro_price_part ) { ?>
							<tr>
								<th colspan="2">
									<?php echo esc_html( $pmpro_price_part['label'] ); ?>
								</th>
								<td>
									<?php echo esc_html( $pmpro_price_part['value'] ); ?>
								</td>
							</tr>
							<?php
						}
					?>
				</tfoot>
			<?php } ?>
		</table>
		<?php if ( $order->getDiscountCode() ) { ?>
			<p><?php echo esc_html( sprintf( __( 'Discount Code: %s', 'paid-memberships-pro' ), esc_html( $order->discount_code->code ) ) ); ?></p>
			</p>
		<?php } ?>
	</main>
</body>
</html>
