<?php

class PMPro_Member_Edit_Panel_Subscriptions extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'subscriptions';
		$this->title = __( 'Subscriptions', 'paid-memberships-pro' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		global $wpdb;
		?>
		(edit customer in Stripe should go here once we have a hook for it)
		<?php
			// Show all subscriptions for user.
			$subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_subscriptions WHERE user_id = %d ORDER BY startdate DESC", self::get_user()->ID ) );

			// Build the selectors for the subscription history list based on history count.
			$subscriptions_classes = array();
			if ( ! empty( $subscriptions ) && count( $subscriptions ) > 10 ) {
				$subscriptions_classes[] = "pmpro_scrollable";
			}
			$subscriptions_class = implode( ' ', array_unique( $subscriptions_classes ) );
		?>
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