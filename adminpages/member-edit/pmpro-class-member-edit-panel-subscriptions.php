<?php

class PMPro_Member_Edit_Panel_Subscriptions extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'subscriptions';
		$this->title = __( 'Subscriptions', 'paid-memberships-pro' );

		// Get the user's Stripe Customer if they have one.
		$user = self::get_user();
		$stripe = new PMProGateway_Stripe();
		$customer = $stripe->get_customer_for_user( $user->ID );

		// Link to the Stripe Customer if they have one.
		// TODO: Eventually make this a hook or filter so other gateways can add their own links.
		if ( ! empty( $customer ) ) {
			$this->title_link = '<a target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users" href="' . esc_url( 'https://dashboard.stripe.com/' . ( get_option( 'pmpro_gateway_environment' ) == 'sandbox' ? 'test/' : '' ) . 'customers/' . $customer->id ) . '">' . esc_html__( 'Edit customer in Stripe', 'paid-memberships-pro' ) . '</a>';
		}
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		global $wpdb;

		$user = self::get_user();

		// Show all active subscriptions for the user.
		$active_subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID );
		if ( $active_subscriptions ) { ?>
			<h3>
				<?php
					printf(
						esc_html__( 'Active Subscriptions (%d)', 'paid-memberships-pro' ),
						esc_html( number_format_i18n( count( $active_subscriptions ) ) )
					);
				?>
			</h3>
			<?php
			$this->display_subscription_table( $active_subscriptions );
		}

		// Show cancelled subscriptions for the user.
		$cancelled_subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, null, array( 'cancelled' ) );

		if ( $cancelled_subscriptions ) { ?>
		<div class="pmpro_section" data-visibility="hidden" data-activated="false">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="false">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
					<?php
						printf(
							esc_html__( 'Cancelled Subscriptions (%d)', 'paid-memberships-pro' ),
							esc_html( number_format_i18n( count( $cancelled_subscriptions ) ) )
						);
					?>
				</button>
			</div>
			<div class="pmpro_section_inside" style="display: none;">
			<?php
				// Optionally wrap table in scrollable box.
				$subscriptions_classes = array();
				if ( ! empty( $cancelled_subscriptions ) && count( $cancelled_subscriptions ) > 10 ) {
					$subscriptions_classes[] = "pmpro_scrollable";
				}
				$subscriptions_class = implode( ' ', array_unique( $subscriptions_classes ) );
				?>
				<div id="member-history-subscriptions" class="<?php echo esc_attr( $subscriptions_class ); ?>">
					<?php $this->display_subscription_table( $cancelled_subscriptions ); ?>
				</div>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<?php
		}
		// Show a message if there are no active or cancelled subscriptions.
		if ( empty( $active_subscriptions ) && empty( $cancelled_subscriptions ) ) {
			?>
			<p><?php esc_html_e( 'This user does not have any subscriptions.', 'paid-memberships-pro' ); ?></p>
			<?php
		}
	}

	/**
	 * Helper method to display a table of subscriptions.
	 *
	 * @since 3.0
	 *
	 * @param array $subscriptions Array of PMPro_Subscription objects to list in the table.
	 */
	private function display_subscription_table( $subscriptions ) {
		global $wpdb;

		// Make sure that we have subscriptions to display.
		if ( empty( $subscriptions ) ) {
			return;
		}

		$user = self::get_user();

		// Check if we are showing active or cancelled subscriptions.
		$showing_active_subscriptions = 'active' === $subscriptions[0]->get_status();

		// Output the table.
		?>
		<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Created', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></th>
					<th><?php echo esc_html( $showing_active_subscriptions ? __( 'Next Payment', 'paid-memberships-pro' ) : __( 'Ended', 'paid-memberships-pro' ) ); ?></th>
					<th><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$user_levels = pmpro_getMembershipLevelsForUser($user->ID);
				$user_level_ids = wp_list_pluck( $user_levels, 'id' );
				foreach ( $subscriptions as $subscription ) {
					$level = pmpro_getLevel( $subscription->get_membership_level_id() );
					?>
					<tr>
						<td class="has-row-actions">
							<strong>
								<?php
								if ( ! empty( $level ) ) {
									echo esc_html( $level->name );
								} elseif ( $subscription->get_membership_level_id() > 0 ) {
									/* translators: %d is the level ID */
									echo sprintf(
										esc_html__( 'Level ID: %d [deleted]', 'paid-memberships-pro' ),
										(int) $subscription->get_membership_level_id()
									);
								} else {
									esc_html_e( '&#8212;', 'paid-memberships-pro' );
								}
								?>
							</strong>
							<?php
							// Show warning if the user does not have the level for this subscription.
							if ( $showing_active_subscriptions && ! in_array( $subscription->get_membership_level_id(), $user_level_ids ) ) {
								?>
								<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">
									<?php esc_html_e( 'Membership Ended', 'paid-memberships-pro' ); ?>
								</span>
								<?php
							}

							// Show warning if the subscription had an error when trying to sync.
							$sync_error = get_pmpro_subscription_meta( $subscription->get_id(), 'sync_error', true );
							if ( ! empty( $sync_error ) ) {
								?>
								<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">
									<?php echo esc_html( __( 'Sync Error', 'paid-memberships-pro' ) . ': ' . $sync_error ); ?>
								</span>
								<?php
							}
							?>
							<div class="row-actions">
								<?php
								$actions = [
									'view'   => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url('admin.php' ) ) ),
										esc_html__( 'View Details', 'paid-memberships-pro' )
									)
								];

								$actions_html = [];

								foreach ( $actions as $action => $link ) {
									$actions_html[] = sprintf(
										'<span class="%1$s">%2$s</span>',
										esc_attr( $action ),
										$link
									);
								}

								if ( ! empty( $actions_html ) ) {
									echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>
						</td>
						<td>
							<?php
							echo esc_html( sprintf(
								// translators: %1$s is the date and %2$s is the time.
								__( '%1$s at %2$s', 'paid-memberships-pro' ),
								esc_html( $subscription->get_startdate( get_option( 'date_format' ) ) ),
								esc_html( $subscription->get_startdate( get_option( 'time_format' ) ) )
							) );
							?>
						</td>
						<td>
							<?php
							// Show the subscription fee.
							echo esc_html( $subscription->get_cost_text() );
							?>
						<td>
							<?php
							$date_to_show = $showing_active_subscriptions ? $subscription->get_next_payment_date( get_option( 'date_format' ) ) : $subscription->get_enddate( get_option( 'date_format' ) );
							$time_to_show = $showing_active_subscriptions ? $subscription->get_next_payment_date( get_option( 'time_format' ) ) : $subscription->get_enddate( get_option( 'time_format' ) );
							if ( ! empty( $showing_active_subscriptions ? $subscription->get_next_payment_date() : $subscription->get_enddate() ) ) {
								echo esc_html(
									sprintf(
										// translators: %1$s is the date and %2$s is the time.
										__( '%1$s at %2$s', 'paid-memberships-pro' ),
										esc_html( $date_to_show ),
										esc_html( $time_to_show )
									)
									);
							} else {
								echo '&#8212;';
							}
							?>
						</td>
						<td>
							<?php
							// Display the number of orders for this subscription and link to the orders page filtered by this subscription.
							$orders_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = %s", $subscription->get_subscription_transaction_id() ) );
							?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $subscription->get_subscription_transaction_id() ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders for this subscription', 'paid-memberships-pro' ); ?>"><?php echo esc_html( number_format_i18n( $orders_count ) ); ?></a>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php

	}
}