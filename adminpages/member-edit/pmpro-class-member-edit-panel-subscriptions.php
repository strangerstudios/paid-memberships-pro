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
		?>
		<h3><?php esc_html_e( 'Active Subscriptions', 'paid-memberships-pro' ); ?></h3>
		<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Created', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Next Payment', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$user_levels = pmpro_getMembershipLevelsForUser($user->ID);
				$user_level_ids = wp_list_pluck( $user_levels, 'id' );
				foreach ( $active_subscriptions as $active_subscription ) {
					$level = pmpro_getLevel( $active_subscription->get_membership_level_id() );

					// If the user does not have the level for this subscription, we want to show the level name in red.
					$level_name       = $level->name;
					$level_name_style = '';
					if ( ! in_array( $level->id, $user_level_ids ) ) {
						$level_name      .= ' [' . esc_html__( 'Membership Ended', 'paid-memberships-pro' ) . ']';
						$level_name_style = 'color: red;';
					}
					?>
					<tr>
						<td style="<?php echo esc_attr( $level_name_style ); ?>">
							<?php echo esc_html( $level_name ); ?>
							<div class="row-actions">
								<?php
									$actions = [
										'edit'   => sprintf(
											'<a href="%1$s">%2$s</a>',
											esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $active_subscription->get_id() ), admin_url('admin.php' ) ) ),
											esc_html__( 'Edit', 'paid-memberships-pro' )
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
										echo implode( ' | ', $actions_html );
									}
								?>
							</div>
						</td>
						<td><?php echo esc_html( $active_subscription->get_startdate( get_option( 'date_format' ) ) ); ?></td>
						<td><?php echo esc_html( $active_subscription->get_next_payment_date( get_option( 'date_format' ) ) ); ?></td>
						<td>
							<?php
							// Display the number of orders for this subscription and link to the orders page filtered by this subscription.
							$orders_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = %s", $active_subscription->get_subscription_transaction_id() ) );
							?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $active_subscription->get_subscription_transaction_id() ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $orders_count ); ?></a>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>

		<?php
		// Show all subscriptions for user.
		$subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_subscriptions WHERE user_id = %d ORDER BY startdate DESC", $user->ID ) );
		$subscriptions_classes = array();
		if ( ! empty( $subscriptions ) && count( $subscriptions ) > 10 ) {
			$subscriptions_classes[] = "pmpro_scrollable";
		}
		$subscriptions_class = implode( ' ', array_unique( $subscriptions_classes ) );
		?>
		<h3><?php esc_html_e( 'All Subscriptions', 'paid-memberships-pro' ); ?></h3>
		<div id="member-history-subscriptions" class="<?php echo esc_attr( $subscriptions_class ); ?>">
		<?php if ( $subscriptions ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?>
					<th><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Next Payment Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Ended', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $subscriptions as $subscription ) { 
					$level = pmpro_getLevel( $subscription->membership_level_id );
					?>
					<tr>
						<td><?php echo esc_html( $subscription->startdate ); ?></td>
						<td><a href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->id ), admin_url('admin.php' ) ) ) ); ?>"><?php echo esc_html( $subscription->subscription_transaction_id ); ?></a></td>
						<td><?php if ( ! empty( $level ) ) { echo esc_html( $level->name ); } else { esc_html_e( 'N/A', 'paid-memberships-pro'); } ?></td>
						<td><?php echo esc_html( $subscription->gateway ); ?>
						<td><?php echo esc_html( $subscription->gateway_environment ); ?>
						<td><?php echo esc_html( $subscription->next_payment_date ); ?>
						<td><?php echo esc_html( $subscription->enddate ); ?>
						<td><?php echo esc_html( $subscription->status ); ?>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php } else { 
				esc_html_e( 'No subscriptions found.', 'paid-memberships-pro' );
			} ?>
		</div>
		<?php		
	}
}