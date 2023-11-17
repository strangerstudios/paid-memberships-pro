<?php
global $pmpro_msg, $pmpro_msgt;

// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_subscriptions' ) ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

$subscription = PMPro_Subscription::get_subscription( empty( $_REQUEST['id'] ) ? null : sanitize_text_field( $_REQUEST['id'] ) );

// Process syncing with gateway.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['update'] ) && check_admin_referer( 'update', 'pmpro_subscriptions_nonce' ) ) {
	$subscription->update();
}

// Process cancelling a subscription.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['cancel'] ) && check_admin_referer( 'cancel', 'pmpro_subscriptions_nonce' ) ) {
	$subscription->cancel_at_gateway();
}

// Process moving a subscription to a new level.
if ( ! empty( $subscription ) && ! empty( $_REQUEST['change-level'] ) && is_numeric( $_REQUEST['change-level'] ) && check_admin_referer( 'change-level', 'pmpro_subscriptions_nonce' ) ) {
	$subscription->set( 'membership_level_id', sanitize_text_field( $_REQUEST['change-level'] ) );
	$subscription->save();
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
	$sub_username = empty( $sub_user ) ? '' : $sub_user->display_name;

	$sub_membership_level_id   = $subscription->get_membership_level_id();
	$sub_membership_level      = pmpro_getLevel( $sub_membership_level_id );
	$sub_membership_level_name = empty( $sub_membership_level ) ? '' : $sub_membership_level->name;
	?>

	<a
		href="<?php echo ( esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'update' => '1' ), admin_url('admin.php' ) ), 'update', 'pmpro_subscriptions_nonce'  ) ) ); ?>"
		title="<?php esc_attr_e( 'Sync With Gateway', 'paid-memberships-pro' ); ?>" 
		class="page-title-action pmpro-has-icon pmpro-has-icon-update">
		<?php esc_html_e( 'Sync With Gateway', 'paid-memberships-pro' ); ?>
	</a>

	<a
		href="javascript:void(0);"
		title="<?php esc_attr_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?>"
		class="page-title-action pmpro-has-icon pmpro-has-icon-no"
		<?php if ( 'active' != $subscription->get_status() ) { echo 'style="display: none"'; } ?>
		onclick="pmpro_askfirst('<?php esc_html_e( 'Please confirm that you want to cancel this subscription. This action stops any future charges at the gateway but does not cancel the corresponding membership level.', 'paid-memberships-pro' ); ?>', '<?php echo ( esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'cancel' => '1' ), admin_url('admin.php' ) ), 'cancel', 'pmpro_subscriptions_nonce'  ) ) ); ?>')">
		<?php esc_html_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?>
	</a>

	<?php
		if ( $pmpro_msg ) {
			?>
			<div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
				<?php echo wp_kses_post( $pmpro_msg ); ?>
			</div>
			<?php
		} else {
			?>
			<div id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
			<?php
		}
	?>

	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Subscription Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<?php if ( ! empty((int)$subscription->get_user_id() ) ) { ?>
				<div class="pmpro_member-box">
					<div class="pmpro_member-box-avatar">
						<?php echo get_avatar( (int)$subscription->get_user_id(), 64 ); ?>
					</div>
					<div class="pmpro_member-box-info">
						<h2><?php echo wp_kses_post( sprintf( __( 'Member: %s', 'paid-memberships-pro' ), '<strong>' . $sub_user->display_name . '</strong>' ) ); ?></h2>
						<div class="pmpro_member-box-actions">
							<?php
								$actions = [
									'edit_member'   => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$subscription->get_user_id() ), admin_url( 'admin.php' ) ) ),
										esc_html__( 'Edit Member', 'paid-memberships-pro' )
									),
									'edit_user' => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'user_id' => (int)$subscription->get_user_id() ), admin_url( 'user-edit.php' ) ) ),
										esc_html__( 'Edit User', 'paid-memberships-pro' )
									),
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
						<div class="pmpro_section-member-info-actions">
							
						</div>
					</div>
				</div>
			<?php } else { ?>
				<h2><?php echo esc_html_e( 'Unknown Member', 'paid-memberships-pro' ); ?></h2>
			<?php } ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ); ?></th>
						<td>
							<?php
							// Firstly, we want to show the membership level that the subscription is for.
							echo esc_html( $sub_membership_level_name );

							// If the subscription is active and the user has membership levels other than the one that the subscription is for, we should
							// give the option to move the subcription to another user level.
							if ( 'active' == $subscription->get_status() ) {
								// Get all of the user's membership levels.
								$user_membership_levels = pmpro_getMembershipLevelsForUser( $subscription->get_user_id() );

								// If the user has a level other than the one that the subscription is for, show a link to show settings to move sub to new level.
								$user_level_ids = empty( $user_membership_levels ) ? array() : wp_list_pluck( $user_membership_levels, 'id' );
								if ( ! empty( array_diff( $user_level_ids, array( $sub_membership_level_id ) ) ) ) {
									echo ' ';
									?>
									<a id="pmpro-show-change-subscription-level"><?php esc_html_e( 'Change', 'paid-memberships-pro' ); ?></a>
									<?php
								}

								// If the user does not have the level that this subscription is for, show a warning.
								if ( ! in_array( $sub_membership_level_id, $user_level_ids ) ) {
									?>
									<p class="description" style="color: red;"><?php esc_html_e( 'This user does not have the membership level that this subscription is for.', 'paid-memberships-pro' ); ?></p>
									<?php
								}

								// Show a dropdown and save button to change the subscription level that shows when the link is clicked.
								?>
								<div id="pmpro-change-subscription-level" style="display: none;">
									<form action="" method="post">
										<select name="change-level">
											<?php
											foreach ( $user_membership_levels as $user_membership_level ) {
												if ( $user_membership_level->id == $sub_membership_level_id ) {
													continue;
												}
												?>
												<option value="<?php echo esc_attr( $user_membership_level->id ); ?>" <?php selected( $user_membership_level->id, $sub_membership_level_id ); ?>><?php echo esc_html( $user_membership_level->name ); ?></option>
												<?php
											}
											?>
										</select>
										<?php wp_nonce_field( 'change-level', 'pmpro_subscriptions_nonce' ); ?>
										<input type="submit" value="<?php esc_attr_e( 'Update Subscription Level', 'paid-memberships-pro' ); ?>" />
									</form>
								</div>
								<script>
									jQuery(document).ready(function() {
										jQuery('#pmpro-show-change-subscription-level').click(function() {
											jQuery('#pmpro-change-subscription-level').show();
											jQuery(this).hide();
										});
									});
								</script>
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
						<td>
							<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-<?php echo esc_attr( $subscription->get_status() ); ?>">
								<?php echo ucwords( esc_html( $subscription->get_status() ) ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Created', 'paid-memberships-pro' ); ?></th>
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
					</tr>
					<tr  <?php if ( 'active' != $subscription->get_status() ) { echo 'style="display: none"'; } ?>>
						<th scope="row"><?php esc_html_e( 'Next Payment Date', 'paid-memberships-pro' ); ?></th>
						<td>
							<?php
								echo esc_html( sprintf(
									// translators: %1$s is the date and %2$s is the time.
									__( '%1$s at %2$s', 'paid-memberships-pro' ),
									esc_html( $subscription->get_next_payment_date( get_option( 'date_format' ) ) ),
									esc_html( $subscription->get_next_payment_date( get_option( 'time_format' ) ) )
								) );
							?>
						</td>
					</tr>
					<tr <?php if ( 'cancelled' != $subscription->get_status() ) { echo 'style="display: none"'; } ?>>
						<th scope="row"><?php esc_html_e( 'Ended', 'paid-memberships-pro' ); ?></th>
						<td>
							<?php 
								echo ! empty( $subscription->get_enddate() ) 
									? esc_html( sprintf(
										// translators: %1$s is the date and %2$s is the time.
										__( '%1$s at %2$s', 'paid-memberships-pro' ),
										esc_html( $subscription->get_enddate( get_option( 'date_format' ) ) ),
										esc_html( $subscription->get_enddate( get_option( 'time_format' ) ) )
									) )
									: '&#8212;';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></th>
						<td>
							<?php echo esc_html( $subscription->get_cost_text() ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Payment Gateway Information', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
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
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<?php
}

require_once( dirname( __FILE__ ) . '/admin_footer.php' );
