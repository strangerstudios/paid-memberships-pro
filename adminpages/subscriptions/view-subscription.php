<?php
/**
 * View a single subscription in the admin.
 *
 * @since TBD
 */

global $wpdb;

// We have a subscription. Display all of its data.
$sub_user = get_userdata( $subscription->get_user_id() );
$sub_membership_level_id = $subscription->get_membership_level_id();
?>

<div class="pmpro_two_col pmpro_two_col-right">

	<div class="pmpro_main">
		<div id="pmpro_subscription-view" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php printf( esc_html__( 'Subscription # %s', 'paid-memberships-pro' ), esc_html( $subscription->get_id() ) ); ?>
					<span class="pmpro_tag pmpro_tag-<?php echo esc_attr( $subscription->get_status() ); ?>">
						<?php echo esc_html( ucwords( $subscription->get_status() ) ); ?>
					</span>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
					// Show warning if the subscription had an error when trying to sync.
					$sync_error = get_pmpro_subscription_meta( $subscription->get_id(), 'sync_error', true );
					if ( ! empty( $sync_error ) ) {
						?>
						<div class="pmpro_message pmpro_error">
							<p><strong><?php echo esc_html( __( 'Sync Error', 'paid-memberships-pro' ) ); ?></strong>: <?php echo esc_html( $sync_error ); ?></p>
						</div>
						<?php
					}

					// If the subscription is active and the user does not have this level, show a warning.
					if ( 'active' == $subscription->get_status() ) {
						$user_membership_levels = pmpro_getMembershipLevelsForUser( $subscription->get_user_id() );
						$user_level_ids         = array_map( 'intval', wp_list_pluck( $user_membership_levels, 'ID' ) );
						if ( ! in_array( $sub_membership_level_id, $user_level_ids ) ) {
							?>
							<div class="pmpro_message pmpro_error">
								<p><?php esc_html_e( 'This user does not have the membership level that this subscription is for.', 'paid-memberships-pro' ); ?></p>
							</div>
							<?php
						}
					}
				?>
				<ul class="pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2">
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ); ?></span>
						<?php
							$level = pmpro_getLevel( $sub_membership_level_id );
							if ( ! empty( $level ) ) {
								echo esc_html( $level->name );
							} elseif ( $sub_membership_level_id > 0 ) {
								/* translators: %d is the level ID */
								printf(
									esc_html__( 'Level ID: %d [deleted]', 'paid-memberships-pro' ),
									(int) $sub_membership_level_id
								);
							} else {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							}
						?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></span>
						<?php echo esc_html( $subscription->get_cost_text() ); ?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Created', 'paid-memberships-pro' ); ?></span>
						<?php
							echo esc_html( sprintf(
								// translators: %1$s is the date and %2$s is the time.
								__( '%1$s at %2$s', 'paid-memberships-pro' ),
								$subscription->get_startdate( get_option( 'date_format' ) ),
								$subscription->get_startdate( get_option( 'time_format' ) )
							) );
						?>
					</li>
					<?php if ( 'active' == $subscription->get_status() ) { ?>
						<li class="pmpro_list_item">
							<span class="pmpro_list_item_label"><?php esc_html_e( 'Next Payment Date', 'paid-memberships-pro' ); ?></span>
							<?php
								echo esc_html( sprintf(
									// translators: %1$s is the date and %2$s is the time.
									__( '%1$s at %2$s', 'paid-memberships-pro' ),
									$subscription->get_next_payment_date( get_option( 'date_format' ) ),
									$subscription->get_next_payment_date( get_option( 'time_format' ) )
								) );
							?>
						</li>
					<?php } ?>
					<?php if ( 'cancelled' == $subscription->get_status() ) { ?>
						<li class="pmpro_list_item">
							<span class="pmpro_list_item_label"><?php esc_html_e( 'Ended', 'paid-memberships-pro' ); ?></span>
							<?php 
								echo ! empty( $subscription->get_enddate() ) 
									? esc_html( sprintf(
										// translators: %1$s is the date and %2$s is the time.
										__( '%1$s at %2$s', 'paid-memberships-pro' ),
										esc_html( $subscription->get_enddate( get_option( 'date_format' ) ) ),
										esc_html( $subscription->get_enddate( get_option( 'time_format' ) ) )
									) )
									: esc_html__( '&#8212;', 'paid-memberships-pro' );
							?>
						</li>
					<?php } ?>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Orders', 'paid-memberships-pro' ); ?></span>
						<?php
							// Display the number of orders for this subscription and link to the orders page filtered by this subscription.
							$orders_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE subscription_transaction_id = %s", $subscription->get_subscription_transaction_id() ) );
							if ( (int) $orders_count === 0 ) {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							} else { ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $subscription->get_subscription_transaction_id() ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders for this subscription', 'paid-memberships-pro' ); ?>">
									<?php echo esc_html( sprintf( _n( 'View %s order', 'View %s orders', $orders_count, 'paid-memberships-pro' ), number_format_i18n( $orders_count ) ) ); ?>
								</a>
								<?php
							}
						?>
					</li>
				</ul>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->

		<div id="pmpro_subscription-view-gateway" class="pmpro_section" data-visibility="shown" data-activated="true">
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
						<?php echo esc_html( pmpro_get_gateway_nicename( $subscription->get_gateway() ) ); ?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Environment', 'paid-memberships-pro' ); ?></span>
						<?php echo esc_html( $subscription->get_gateway_environment() ); ?>
					</li>
					<li class="pmpro_list_item">
						<span class="pmpro_list_item_label"><?php esc_html_e( 'Subscription ID', 'paid-memberships-pro' ); ?></span>
						<code><?php echo esc_html( $subscription->get_subscription_transaction_id() ); ?></code>
					</li>
				</ul>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
	</div> <!-- end pmpro_main -->

	<div class="pmpro_sidebar">
		<div id="pmpro_subscription-view-member" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Member Information', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php if ( ! empty( $sub_user ) ) { ?>
					<div class="pmpro_member-box">
						<div class="pmpro_member-box-avatar">
							<?php echo get_avatar( (int) $subscription->get_user_id(), 64 ); ?>
						</div>
						<div class="pmpro_member-box-info">
							<h2><strong><?php echo esc_html( $sub_user->display_name ); ?></strong></h2>
							<div class="pmpro_member-box-actions">
								<?php
								$actions = array(
									'edit_member' => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $subscription->get_user_id() ), admin_url( 'admin.php' ) ) ),
										esc_html__( 'Edit Member', 'paid-memberships-pro' )
									),
									'edit_user'   => sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( add_query_arg( array( 'user_id' => (int) $subscription->get_user_id() ), admin_url( 'user-edit.php' ) ) ),
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
					<p><?php esc_html_e( 'The user ID associated with this subscription is not a valid user.', 'paid-memberships-pro' ); ?></p>
				<?php } ?>
			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->

		<div id="pmpro_subscription-view-actions" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Subscription Actions', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
					// Define the subscription actions.
					$subscription_actions = array();

					// Edit Subscription.
					$subscription_actions['edit'] = array(
						'title' => esc_attr( sprintf( __( 'Edit subscription #%s', 'paid-memberships-pro' ), $subscription->get_id() ) ),
						'href'  => esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'action' => 'edit' ), admin_url( 'admin.php' ) ) ),
						'class' => 'button button-secondary pmpro-has-icon pmpro-has-icon-edit',
						'label' => esc_html__( 'Edit Subscription', 'paid-memberships-pro' ),
					);

					// Sync With Gateway (if supported).
					$gateway_object = $subscription->get_gateway_object();
					if ( ! empty( $gateway_object ) && method_exists( $gateway_object, 'supports' ) && $gateway_object->supports( 'subscription_sync' ) ) {
						$subscription_actions['sync'] = array(
							'title' => esc_attr( sprintf( __( 'Sync subscription #%s with gateway', 'paid-memberships-pro' ), $subscription->get_id() ) ),
							'href'  => esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id(), 'update' => '1' ), admin_url( 'admin.php' ) ), 'update', 'pmpro_subscriptions_nonce' ) ),
							'class' => 'button button-secondary pmpro-has-icon pmpro-has-icon-update',
							'label' => esc_html__( 'Sync With Gateway', 'paid-memberships-pro' ),
						);
					}

					// Edit Customer in Stripe (if Stripe subscription).
					if ( 'stripe' === $subscription->get_gateway() && ! empty( $sub_user ) ) {
						$stripe = new PMProGateway_Stripe();
						$customer = $stripe->get_customer_for_user( $sub_user->ID );
						if ( ! empty( $customer ) ) {
							$subscription_actions['stripe_customer'] = array(
								'title'  => esc_attr__( 'Edit Customer in Stripe', 'paid-memberships-pro' ),
								'href'   => esc_url( 'https://dashboard.stripe.com/' . ( get_option( 'pmpro_gateway_environment' ) == 'sandbox' ? 'test/' : '' ) . 'customers/' . $customer->id ),
								'target' => '_blank',
								'class'  => 'button button-secondary pmpro-has-icon pmpro-has-icon-admin-users',
								'label'  => esc_html__( 'Edit Customer in Stripe', 'paid-memberships-pro' ),
							);
						}
					}

					// Cancel Subscription (if active).
					if ( 'active' == $subscription->get_status() ) {
						$cancel_text = esc_html__( 'Please confirm that you want to cancel this subscription. This action stops any future charges at the gateway but does not cancel the corresponding membership level.', 'paid-memberships-pro' );
						$cancel_nonce_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'   => 'pmpro-subscriptions',
									'id'     => $subscription->get_id(),
									'cancel' => '1',
								),
								admin_url( 'admin.php' )
							),
							'cancel',
							'pmpro_subscriptions_nonce'
						);
						$subscription_actions['cancel'] = array(
							'title' => esc_attr( sprintf( __( 'Cancel subscription #%s', 'paid-memberships-pro' ), $subscription->get_id() ) ),
							'href'  => esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $cancel_text ) . ', ' . wp_json_encode( $cancel_nonce_url ) . '); void(0);' ),
							'class' => 'button is-destructive pmpro-has-icon pmpro-has-icon-no',
							'label' => esc_html__( 'Cancel Subscription', 'paid-memberships-pro' ),
						);
					}

					/**
					 * Allow filtering of actions on the single subscription view admin screen.
					 *
					 * @since TBD
					 *
					 * @param array $subscription_actions The array of subscription actions.
					 * @param PMPro_Subscription $subscription The subscription object.
					 *
					 * @return array The filtered array of subscription actions.
					 */
					$subscription_actions = apply_filters( 'pmpro_subscription_view_actions', $subscription_actions, $subscription );

					// Output the actions.
					foreach ( $subscription_actions as $key => $action ) {
						?>
						<a
							<?php if ( ! empty( $action['title'] ) ) : ?>title="<?php echo esc_attr( $action['title'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['href'] ) ) : ?>href="<?php echo esc_attr( $action['href'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['target'] ) ) : ?>target="<?php echo esc_attr( $action['target'] ); ?>"<?php endif; ?>
							<?php if ( ! empty( $action['class'] ) ) : ?>class="<?php echo esc_attr( $action['class'] ); ?>"<?php endif; ?>
						>
							<?php echo esc_html( $action['label'] ); ?>
						</a>
						<?php
					}
				?>
			</div><!-- .pmpro_section_inside -->
		</div><!-- .pmpro_section -->
	</div> <!-- end pmpro_sidebar -->

</div> <!-- end pmpro_two_col -->
