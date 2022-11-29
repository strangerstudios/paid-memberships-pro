<?php
// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_subscriptions' ) ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

$subscription = PMPro_Subscription::get_subscription( empty( $_REQUEST['id'] ) ? null : sanitize_text_field( $_REQUEST['id'] ) );

// Process syncing with gateway.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['update'] ) ) {
	$subscription->update();
}

// Process cancelling a subscription.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['cancel'] ) ) {
	$subscription->cancel_at_gateway();
}

require_once( dirname( __FILE__ ) . '/admin_header.php' );

?>
<hr class="wp-header-end">
<h1 class="wp-heading-inline"><?php esc_html_e( 'View Subscription', 'paid-memberships-pro' ); ?></h1>
<?php

// Check if we have a subscription object.
if ( empty( $subscription ) ) {
	// Either a subscription ID wasn't passed or the subscription doesn't exist.
	?>
	<p><?php esc_html_e( 'Subscription not found.', 'paid-memberships-pro' ); ?></p>
	<?php
} else {
	// We have a subscription. Display all of its data.
	$sub_user = get_userdata( $subscription->get_user_id() );
	$sub_username = empty( $sub_user ) ? '' : $sub_user->user_login;

	$sub_membership_level = pmpro_getLevel( $subscription->get_membership_level_id() );
	$sub_membership_level_name = empty( $sub_membership_level ) ? '' : $sub_membership_level->name;
	?>
	<a href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'update' => '1' ), admin_url('admin.php' ) ) ) ); ?>" title="<?php esc_attr_e( 'Sync With Gateway', 'paid-memberships-pro' ); ?>" class="page-title-action"><?php esc_html_e( 'Sync With Gateway', 'paid-memberships-pro' ); ?></a>
	<a href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'cancel' => '1' ), admin_url('admin.php' ) ) ) ); ?>" title="<?php esc_attr_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?>" class="page-title-action" <?php if ( 'active' != $subscription->get_status() ) { echo 'style="display: none"'; } ?> onclick="return confirm('<?php esc_html_e( 'Are you sure that you would like to cancel this payment subscription? This will not cancel the corresponding membership level.', 'paid-memberships-pro' ); ?>')"><?php esc_html_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?></a>
	<h2><?php esc_html_e( 'Payment Gateway Information', 'paid-memberships-pro' ); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Subscription ID', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_subscription_transaction_id() ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_gateway() ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_gateway_environment() ); ?></td>
			</tr>
		</tbody>
	</table>
	<hr/>
	<h2><?php esc_html_e( 'Subscription Information', 'paid-memberships-pro' ); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'User', 'paid-memberships-pro' ); ?></th>
				<td><a href="<?php echo ( esc_url( add_query_arg( array( 'user_id' => $subscription->get_user_id() ), admin_url('user-edit.php' ) ) ) ); ?>"><?php echo esc_html( $sub_username ); ?></a></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $sub_membership_level_name ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_status() ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Start Date', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_startdate( 'Y-m-d H:i:s' ) ); ?></td>
			</tr>
			<tr  <?php if ( 'active' != $subscription->get_status() ) { echo 'style="display: none"'; } ?>>
				<th scope="row"><?php esc_html_e( 'Next Payment Date', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_next_payment_date( 'Y-m-d H:i:s' ) ); ?></td>
			</tr>
			<tr <?php if ( 'cancelled' != $subscription->get_status() ) { echo 'style="display: none"'; } ?>>
				<th scope="row"><?php esc_html_e( 'End Date', 'paid-memberships-pro' ); ?></th>
				<td><?php echo esc_html( $subscription->get_enddate( 'Y-m-d H:i:s' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></th>
				<td>
					<?php
					$billing_amount = $subscription->get_billing_amount();
					$cycle_number   = $subscription->get_cycle_number();
					$cycle_period   = $subscription->get_cycle_period();

					if ( $cycle_number == 1 ) {
						printf( esc_html__( '%1$s per %2$s', 'paid-memberships-pro' ), pmpro_formatPrice( $billing_amount ), $cycle_period );
					} else {
						printf( esc_html__( '%1$s every %2$s %3$ss', 'paid-memberships-pro' ), pmpro_formatPrice( $billing_amount ), $cycle_number, $cycle_period );
					}
					?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

require_once( dirname( __FILE__ ) . '/admin_footer.php' );
