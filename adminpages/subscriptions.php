<?php
global $wpdb, $pmpro_msg, $pmpro_msgt;

// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_edit_members' ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
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

// Process linking a subscription.
if ( isset( $_REQUEST['action'] ) && 'link' === $_REQUEST['action'] ) {
	if ( ! empty( $_POST ) && check_admin_referer( 'link', 'pmpro_subscriptions_nonce' ) ) {
		// Make sure all required fields are set.
		if ( empty( $_POST['subscription_transaction_id'] ) || empty( $_POST['gateway'] ) || empty( $_POST['gateway_environment'] ) || empty( $_POST['user_id'] ) || empty( $_POST['membership_level_id'] ) ) {
			$pmpro_msg  = esc_html__( 'All fields are required.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the user ID is valid.
		if ( ! get_userdata( sanitize_text_field( $_POST['user_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid user ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the membership level ID is valid.
		if ( ! pmpro_getLevel( sanitize_text_field( $_POST['membership_level_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid membership level ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Check if this subscription already exists.
		if ( 'pmpro_error' !== $pmpro_msgt ) {
			$test_subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( sanitize_text_field( $_POST['subscription_transaction_id'] ), sanitize_text_field( $_POST['gateway'] ), sanitize_text_field( $_POST['gateway_environment'] ) );

			if ( ! empty( $test_subscription ) ) {
				$pmpro_msg  = esc_html__( 'This subscription already exists on your website.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
		}

		// Create a new subscription.
		if ( 'pmpro_error' !== $pmpro_msgt ) {
			$create_subscription_args = array(
				'user_id'              => sanitize_text_field( $_POST['user_id'] ),
				'membership_level_id'  => sanitize_text_field( $_POST['membership_level_id'] ),
				'gateway'              => sanitize_text_field( $_POST['gateway'] ),
				'gateway_environment'  => sanitize_text_field( $_POST['gateway_environment'] ),
				'subscription_transaction_id' => sanitize_text_field( $_POST['subscription_transaction_id'] ),
				'status'               => 'active',
			);
			$subscription = PMPro_Subscription::create( $create_subscription_args );

			if ( ! empty( $subscription ) ) {
				// Show a success message.
				$pmpro_msg  = esc_html__( 'Subscription linked successfully.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_success';

				// Go to the "view" page.
				unset( $_REQUEST['action'] );
			} else {
				// Show an error message.
				$pmpro_msg  = esc_html__( 'Error linking subscription.', 'paid-memberships-pro' );
				$pmpro_msgt = 'pmpro_error';
			}
		}
	}
}

// Process editing a subscription.
if ( ! empty( $subscription ) && isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] ) {
	if ( ! empty( $_POST ) && check_admin_referer( 'edit', 'pmpro_subscriptions_nonce' ) ) {
		// Make sure all required fields are set.
		if ( empty( $_POST['user_id'] ) || empty( $_POST['membership_level_id'] ) ) {
			$pmpro_msg  = esc_html__( 'All fields are required.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the user ID is valid.
		if ( ! get_userdata( sanitize_text_field( $_POST['user_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid user ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Make sure that the membership level ID is valid.
		if ( ! pmpro_getLevel( sanitize_text_field( $_POST['membership_level_id'] ) ) ) {
			$pmpro_msg  = esc_html__( 'Invalid membership level ID.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_error';
		}

		// Update the subscription.
		if ( 'pmpro_error' !== $pmpro_msgt ) {
			$subscription->set( 'user_id', sanitize_text_field( $_POST['user_id'] ) );
			$subscription->set( 'membership_level_id', sanitize_text_field( $_POST['membership_level_id'] ) );
			$subscription->save();

			// Show a success message.
			$pmpro_msg  = esc_html__( 'Subscription updated successfully.', 'paid-memberships-pro' );
			$pmpro_msgt = 'pmpro_success';

			// Go back to the "view" page.
			unset( $_REQUEST['action'] );
		}
	}
}

require_once( dirname( __FILE__ ) . '/admin_header.php' );

?>
<hr class="wp-header-end">
<?php

if ( isset( $_REQUEST['action'] ) && 'link' === $_REQUEST['action'] ) {
	// Link a subscription.
	$subscription_transaction_id = ! empty( $_REQUEST['subscription_transaction_id'] ) ? sanitize_text_field( $_REQUEST['subscription_transaction_id'] ) : '';
	$gateway                    = ! empty( $_REQUEST['gateway'] ) ? sanitize_text_field( $_REQUEST['gateway'] ) : get_option( 'pmpro_gateway', '' );
	$gateway_environment		= ! empty( $_REQUEST['gateway_environment'] ) ? sanitize_text_field( $_REQUEST['gateway_environment'] ) : get_option( 'pmpro_gateway_environment', '' );
	$user_id                    = ! empty( $_REQUEST['user_id'] ) ? sanitize_text_field( $_REQUEST['user_id'] ) : '';
	$membership_level_id        = ! empty( $_REQUEST['membership_level_id'] ) ? sanitize_text_field( $_REQUEST['membership_level_id'] ) : '';
	?>
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Link Subscription', 'paid-memberships-pro' ); ?></h1>
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
	<form action="" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></th>
					<td>
						<input type="text" name="subscription_transaction_id" value="<?php echo esc_attr( $subscription_transaction_id ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></th>
					<td>
						<?php
						// Get all gateways.
						$gateways = pmpro_gateways();
						?>
						<select name="gateway">
							<?php
							foreach ( $gateways as $gateway_key => $gateway_label ) {
								?>
								<option value="<?php echo esc_attr( $gateway_key ); ?>" <?php selected( $gateway_key, $gateway ); ?>><?php echo esc_html( $gateway_label ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></th>
					<td>
						<select name="gateway_environment">
							<option value="sandbox" <?php selected( 'sandbox', $gateway_environment ); ?>><?php esc_html_e( 'Sandbox', 'paid-memberships-pro' ); ?></option>
							<option value="live" <?php selected( 'live', $gateway_environment ); ?>><?php esc_html_e( 'Live', 'paid-memberships-pro' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'User ID', 'paid-memberships-pro' ); ?></th>
					<td>
						<input type="text" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ); ?></th>
					<td>
						<?php
						// Get all membership levels.
						$levels = pmpro_getAllLevels( true, true );

						// Display a dropdown of membership levels.
						?>
						<select name="membership_level_id">
							<?php
							foreach ( $levels as $level ) {
								?>
								<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $level->id, $membership_level_id ); ?>><?php echo esc_html( $level->name ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'link', 'pmpro_subscriptions_nonce' ); ?>
		<input type="hidden" name="action" value="link" />
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Link Subscription', 'paid-memberships-pro' ); ?>" />
	</form>
	<?php
} elseif ( ! empty( $subscription ) && isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] ) {
	// Edit a subscription.
	$user_id = empty( $_REQUEST['user_id'] ) ? $subscription->get_user_id() : sanitize_text_field( $_REQUEST['user_id'] );
	$membership_level_id = empty( $_REQUEST['membership_level_id'] ) ? $subscription->get_membership_level_id() : sanitize_text_field( $_REQUEST['membership_level_id'] );
	?>
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Subscription', 'paid-memberships-pro' ); ?></h1>
	<a
		href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url('admin.php' ) ) ) ); ?>"
		title="<?php esc_attr_e( 'View Subscription', 'paid-memberships-pro' ); ?>" 
		class="page-title-action pmpro-has-icon pmpro-has-icon-visibility">
		<?php esc_html_e( 'View Subscription', 'paid-memberships-pro' ); ?>
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
	<form action="" method="post">
		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Subscription Information', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'ID', 'paid-memberships-pro' ); ?></th>
							<td><?php echo esc_html( $subscription->get_id() ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></th>
							<td>
								<?php echo esc_html( $subscription->get_subscription_transaction_id() ); ?>
								<p class="description"><?php esc_html_e( 'Generated by the gateway. Useful to cross reference subscriptions.', 'paid-memberships-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></th>
							<td><?php echo esc_html( $subscription->get_gateway() ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></th>
							<td><?php echo esc_html( $subscription->get_gateway_environment() ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'User ID', 'paid-memberships-pro' ); ?></th>
							<td>
								<input type="text" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ); ?></th>
							<td>
								<?php
								// Get all membership levels.
								$levels = pmpro_getAllLevels( true, true );

								// Display a dropdown of membership levels.
								?>
								<select name="membership_level_id">
									<?php
									foreach ( $levels as $level ) {
										?>
										<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $level->id, $membership_level_id ); ?>><?php echo esc_html( $level->name ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<p class="submit">
			<?php wp_nonce_field( 'edit', 'pmpro_subscriptions_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $subscription->get_id() ); ?>" />
			<input type="hidden" name="action" value="edit" />
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Update Subscription', 'paid-memberships-pro' ); ?>" />
		</p>
	</form>
	<?php
} elseif ( ! empty( $subscription ) ) {
	// View a subscription.
	?>
	<h1 class="wp-heading-inline"><?php esc_html_e( 'View Subscription', 'paid-memberships-pro' ); ?></h1>
	<?php

	// We have a subscription. Display all of its data.
	$sub_user = get_userdata( $subscription->get_user_id() );
	$sub_username = empty( $sub_user ) ? '' : $sub_user->display_name;

	$sub_membership_level_id   = $subscription->get_membership_level_id();
	$sub_membership_level      = pmpro_getLevel( $sub_membership_level_id );
	$sub_membership_level_name = empty( $sub_membership_level )
		? sprintf(
			/* translators: %d is the level ID. */
			esc_html__( 'Level ID: %d [deleted]', 'paid-memberships-pro' ),
			(int) $subscription->get_membership_level_id()
		)
		: $sub_membership_level->name;
	?>

	<a
		href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'action' => 'edit' ), admin_url('admin.php' ) ) ) ); ?>"
		title="<?php esc_attr_e( 'Edit Subscription', 'paid-memberships-pro' ); ?>" 
		class="page-title-action pmpro-has-icon pmpro-has-icon-edit">
		<?php esc_html_e( 'Edit Subscription', 'paid-memberships-pro' ); ?>
	</a>

	<?php
	$gateway_object = $subscription->get_gateway_object();
	if ( ! empty( $gateway_object ) && method_exists( $gateway_object, 'supports' ) && $gateway_object->supports( 'subscription_sync' ) ) {
	?>
		<a
			href="<?php echo ( esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'update' => '1' ), admin_url('admin.php' ) ), 'update', 'pmpro_subscriptions_nonce'  ) ) ); ?>"
			title="<?php esc_attr_e( 'Sync With Gateway', 'paid-memberships-pro' ); ?>" 
			class="page-title-action pmpro-has-icon pmpro-has-icon-update">
			<?php esc_html_e( 'Sync With Gateway', 'paid-memberships-pro' ); ?>
		</a>
	<?php } ?>

	<a
		href="javascript:void(0);"
		title="<?php esc_attr_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?>"
		class="page-title-action pmpro-has-icon pmpro-has-icon-no"
		<?php if ( 'active' != $subscription->get_status() ) { echo 'style="display: none"'; } ?>
		onclick="pmpro_askfirst('<?php esc_html_e( 'Please confirm that you want to cancel this subscription. This action stops any future charges at the gateway but does not cancel the corresponding membership level.', 'paid-memberships-pro' ); ?>', '<?php echo ( esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'cancel' => '1' ), admin_url('admin.php' ) ), 'cancel', 'pmpro_subscriptions_nonce'  ) ) ); ?>')">
		<?php esc_html_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?>
	</a>

	<?php
	// If this is a Stripe subscription and we have a customer for the user, show a link to edit the customer in Stripe.
	if ( 'stripe' === $subscription->get_gateway() ) {
		$stripe = new PMProGateway_Stripe();
 		$customer = $stripe->get_customer_for_user( $sub_user->ID );
		 if ( ! empty( $customer ) ) {
			?>
			<a
				target="_blank"
				class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users"
				href="<?php echo esc_url( 'https://dashboard.stripe.com/' . ( get_option( 'pmpro_gateway_environment' ) == 'sandbox' ? 'test/' : '' ) . 'customers/' . $customer->id ) ?>">
				<?php esc_html_e( 'Edit Customer in Stripe', 'paid-memberships-pro' ); ?>
			</a>
			<?php
		}
	}
	?>

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
									echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
							// give the option to move the subscription to another user level.
							if ( 'active' == $subscription->get_status() ) {
								// Get all of the user's membership levels.
								$user_membership_levels = pmpro_getMembershipLevelsForUser( $subscription->get_user_id() );
								$user_level_ids         = array_map( 'intval', wp_list_pluck( $user_membership_levels, 'ID' ) );

								// If the user does not have the level that this subscription is for, show a warning.
								if ( ! in_array( $sub_membership_level_id, $user_level_ids ) ) {
									?>
									<p class="description" style="color: red;"><?php esc_html_e( 'This user does not have the membership level that this subscription is for.', 'paid-memberships-pro' ); ?></p>
									<?php
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
						<td>
							<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-<?php echo esc_attr( $subscription->get_status() ); ?>">
								<?php echo esc_html( ucwords( $subscription->get_status() ) ); ?>
							</span>
							<?php
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
					<tr>
						<th scope="row"><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></th>
						<td>
							<?php
							// Display the number of orders for this subscription and link to the orders page filtered by this subscription.
							$orders_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = %s", $subscription->get_subscription_transaction_id() ) );
							?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $subscription->get_subscription_transaction_id() ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders for this subscription', 'paid-memberships-pro' ); ?>">
								<?php echo esc_html( sprintf( _n( 'View %s order', 'View %s orders', $orders_count, 'paid-memberships-pro' ), number_format_i18n( $orders_count ) ) ); ?>
							</a>
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
} else {
	?>
	<form id="subscriptions-list-form" method="get" action="">

		<h1 class="wp-heading-inline"><?php esc_html_e( 'Subscriptions', 'paid-memberships-pro' ); ?></h1>

		<a
			href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'action' => 'link' ), admin_url('admin.php' ) ) ) ); ?>"
			title="<?php esc_attr_e( 'Link Subscription', 'paid-memberships-pro' ); ?>" 
			class="page-title-action pmpro-has-icon pmpro-has-icon-plus">
			<?php esc_html_e( 'Link Subscription', 'paid-memberships-pro' ); ?>
		</a>

		<?php if ( ! empty( $pmpro_msg ) ) { ?>
			<div id="message" class="
			<?php
			if ( $pmpro_msgt == 'success' ) {
				echo 'updated fade';
			} else {
				echo 'error';
			}
			?>
			"><p><?php echo wp_kses_post( $pmpro_msg ); ?></p></div>
		<?php }
		$subscriptions_list_table = new PMPro_Subscriptions_List_Table();
		$subscriptions_list_table->prepare_items();
		$subscriptions_list_table->search_box( __( 'Search Subscriptions', 'paid-memberships-pro' ), 'paid-memberships-pro' );
		$subscriptions_list_table->display();

		?>
	</form>
	<?php
}

require_once( dirname( __FILE__ ) . '/admin_footer.php' );
