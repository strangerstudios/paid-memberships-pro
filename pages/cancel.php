<?php
/**
 * Template: Cancel
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
 *
 * @author Paid Memberships Pro
 */
global $pmpro_msg, $pmpro_msgt, $current_user, $wpdb;

// Get the user's current levels.
$user_levels = pmpro_getMembershipLevelsForUser( $current_user->ID );

?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<section id="pmpro_cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_cancel' ) ); ?>">
		<?php
		// Show a message if we have one.
		if ( $pmpro_msg ) {
			?>
			<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
			<?php
		}

		// In the body of this page, we either want to:
		// 1. Show the form to cancel levels.
		// 2. Show a table of levels that can be cancelled.
		// 3. Show links to account or home page.
		if ( empty( $_REQUEST['confirm'] ) && isset( $_REQUEST['levelstocancel'] ) ) {
			// Show the form to cancel levels.
			// Build dynamic message based on the levels being cancelled.
			if( $_REQUEST['levelstocancel'] !== 'all') {
				// Specific levels are being cancelled.

				// Odd input format here (1+2+3). These values are sanitized.
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				//convert spaces back to +
				$_REQUEST['levelstocancel'] = str_replace( array(' ', '%20'), '+', $_REQUEST['levelstocancel'] );

				// Get the IDs being cancelled.
				$old_level_ids = array_map('intval', explode("+", preg_replace("/[^0-9al\+]/", "", $_REQUEST['levelstocancel']))); // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Build messages.
				$level_names = array_map(function($level_id) use ($user_levels) {
					foreach ($user_levels as $level) {
						if ($level->id == $level_id) {
							return $level->name;
						}
					}
				}, $old_level_ids);
				$are_you_sure  = sprintf( _n( 'Are you sure you want to cancel your %s membership?', 'Are you sure you want to cancel your %s memberships?', count( $old_level_ids ), 'paid-memberships-pro' ), pmpro_implodeToEnglish( $level_names ) );
				$cancel_memberships_text = _n( 'Yes, cancel this membership', 'Yes, cancel these memberships', count( $old_level_ids ), 'paid-memberships-pro' );
				$keep_memberships_text = _n( 'No, keep this membership', 'No, keep these memberships', count( $old_level_ids ), 'paid-memberships-pro' );
			} else {
				// All levels are being cancelled.
				$old_level_ids = wp_list_pluck( $user_levels, 'id' );

				// Build messages.
				$are_you_sure = _n( 'Are you sure you want to cancel your membership?', 'Are you sure you want to cancel all of your memberships?', count( $user_levels ), 'paid-memberships-pro' );
				$cancel_memberships_text = _n( 'Yes, cancel my membership', 'Yes, cancel all of my memberships', count( $user_levels ), 'paid-memberships-pro' );
				$keep_memberships_text = _n( 'No, keep my membership', 'No, keep my memberships', count( $user_levels ), 'paid-memberships-pro' );
			}

			// Figure out which memberships will be cancelled immediately and which will be cancelled on the next payment date. Show a message.
			$conpd_levels = array();
			foreach ( $old_level_ids as $old_level_id ) {
				if ( apply_filters( 'pmpro_cancel_on_next_payment_date', true, $old_level_id, $current_user->ID ) ) {
					$conpd_levels[] = $old_level_id;
				}
			}
			$subscriptions = empty( $conpd_levels ) ? null : PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $conpd_levels );
			if ( count( $old_level_ids ) <= 1 ) {
				if ( ! empty( $subscriptions ) && empty( $subscriptions[0]->get_orders( array( 'status' => 'pending', 'limit' => 1 ) ) ) ) {
					// There is a subscription that does not have missed payments. Show the next payment date.
					$cancellation_behavior_text = sprintf( __( 'Your subscription will be cancelled. You will not be billed again. Your membership will remain active until %s. ', 'paid-memberships-pro' ), $subscriptions[0]->get_next_payment_date( get_option( 'date_format' ) ) );
				} else {
					// No subscription. Show a generic message.
					$cancellation_behavior_text = __( 'Your membership will be cancelled immediately.', 'paid-memberships-pro' );
				}
			} else {
				if ( ! empty( $subscriptions ) ) {
					// There is a subscription. Show a generic message.
					$cancellation_behavior_text = __( 'Some of the memberships you are cancelling have a recurring subscription. These subscriptions will be cancelled, you will not be billed again, and the associated memberships will remain active through the end of the current payment period.', 'paid-memberships-pro' );
				} else {
					// No subscription. Show a generic message.
					$cancellation_behavior_text = __( 'Your memberships will be cancelled immediately.', 'paid-memberships-pro' );
				}
			}

			// Output the form.
			?>
			<form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form pmpro_card' ) ); ?>" action="<?php echo esc_url( pmpro_url( 'cancel', '', 'https') ) ?>" method="post">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<p><?php echo esc_html( $are_you_sure ); ?></p>
					<p><?php echo esc_html( $cancellation_behavior_text ); ?></p>

					<?php
					/**
					 * Hook to add additional content to the cancel page.
					 *
					 * @since 3.0
					 *
					 * @param WP_User $user The user cancelling their membership.
					 * @param array   $old_level_ids The level IDs being cancelled.
					 */
					do_action( 'pmpro_cancel_before_submit', $current_user, $old_level_ids );
					?>

					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
						<input type="hidden" name="levelstocancel" value="<?php echo esc_attr( $_REQUEST['levelstocancel'] ); ?>" />
						<input type="hidden" name="confirm" value="1" />
						<?php wp_nonce_field( 'pmpro_cancel-nonce', 'pmpro_cancel-nonce' ); ?>
						<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ) ); ?>" value="<?php echo esc_attr( $cancel_memberships_text ); ?>" />
						<a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ) ); ?>" href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php echo esc_html( $keep_memberships_text ); ?></a>
					</div> <!-- end pmpro_form_submit -->
				</div> <!-- end pmpro_card_content -->
			</form> <!-- end pmpro_form -->
			<?php
		} elseif ( empty( $_REQUEST['confirm'] ) && ! empty( $user_levels ) ) {
			// Show a table of levels that can be cancelled.
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'My Memberships', 'paid-memberships-pro' ); ?></h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<p><?php esc_html_e( 'You have the following active memberships.', 'paid-memberships-pro' ); ?></p>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
					<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_table_cancel', 'pmpro_table_cancel' ) ); ?>">
						<thead>
							<tr>
								<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_cancel-level' ) ); ?>"><?php esc_html_e("Level", 'paid-memberships-pro' );?></th>
								<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_cancel-expiration' ) ); ?>"><?php esc_html_e("Expiration", 'paid-memberships-pro' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ( $user_levels as $level ) {
								?>
								<tr>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_cancel-level' ) ); ?>" data-title="<?php esc_attr_e( 'Level', 'paid-memberships-pro' ); ?>">
										<?php echo esc_html( $level->name );?>
									</th>
									<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_cancel-expiration' ) ); ?>" data-title="<?php esc_attr_e( 'Expiration', 'paid-memberships-pro' ); ?>">
									<?php
										echo wp_kses_post( pmpro_get_membership_expiration_text( $level, $current_user ) );
									?>
									</td>
									<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_cancel-action' ) ); ?>">
										<a href="<?php echo esc_url( pmpro_url( "cancel", "?levelstocancel=" . $level->id ) ) ?>"><?php esc_html_e("Cancel", 'paid-memberships-pro' );?></a>
									</td>
								</tr>
								<?php
								}
							?>
						</tbody>
					</table>
				</div> <!-- end pmpro_card_content -->
				<?php
					// Show a link to cancel all memberships if the user has more than one.
					if ( count( $user_levels ) > 1 ) {
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
							<a href="<?php echo esc_url( pmpro_url( "cancel", "?levelstocancel=all" ) ); ?>"><?php esc_html_e("Cancel All Memberships", 'paid-memberships-pro' );?></a>
						</div> <!-- end pmpro_card_actions -->
						<?php
					}
				?>
			</div> <!-- end pmpro_card -->
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
				<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'paid-memberships-pro' );?></a></span>
			</div>
			<?php
		} else {
			// Cancellation has been completed or no levels to show in cancellation table. Show links back to account or home page.
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
			<?php
				if ( empty( $user_levels ) ) {
					// The user cancelled all of their membership levels. Send them to the home page.
					?>
					<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cancel_return_home pmpro_actions_nav-right', 'pmpro_cancel_return_home' ) ); ?>"><a href="<?php echo esc_url( get_home_url() )?>"><?php esc_html_e( 'View the Homepage &rarr;', 'paid-memberships-pro' ); ?></a></span>
					<?php
				} else {
					// The user still has some membership levels. Send them to the account page.
					?>
					<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'paid-memberships-pro' );?></a></span>
					<?php
				}
			?>
			</div>
			<?php
		}
		?>
	</section> <!-- end pmpro_cancel -->
</div> <!-- end pmpro -->
