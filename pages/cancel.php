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

if(isset($_REQUEST['levelstocancel']) && $_REQUEST['levelstocancel'] !== 'all') {
	// Odd input format here (1+2+3). These values are sanitized.
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	//convert spaces back to +
	$_REQUEST['levelstocancel'] = str_replace(array(' ', '%20'), '+', $_REQUEST['levelstocancel']);

	//get the ids
	$old_level_ids = array_map('intval', explode("+", preg_replace("/[^0-9al\+]/", "", $_REQUEST['levelstocancel'])));
	// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
} elseif(isset($_REQUEST['levelstocancel']) && $_REQUEST['levelstocancel'] == 'all') {
	$old_level_ids = 'all';
} else {
	$old_level_ids = false;
}

// Get the user's current levels.
$user_levels = pmpro_getMembershipLevelsForUser( $current_user->ID );
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<section id="pmpro_cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_cancel' ) ); ?>">
		<?php
			if ( $pmpro_msg ) {
				?>
				<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
				<?php
			}
		?>
		<?php
			if ( empty( $_REQUEST['confirm'] ) ) {
				if ( $old_level_ids ) {
					?>
					<form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form pmpro_card' ) ); ?>" action="<?php echo esc_url( pmpro_url( 'cancel', '', 'https') ) ?>" method="post">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<p>
								<?php
									// Set up some variables for the message.
									$show_notice_about_subscriptions = false;
									$next_payment_date = false;

									// Show a different message if they are cancelling all levels or specific level(s).
									if ( is_string( $old_level_ids ) && $old_level_ids == 'all' ) {
										// Do any of their levels have an active subscription?
										$subscriptions =  PMPro_Subscription::get_subscriptions_for_user( $current_user->ID );
										if ( ! empty( $subscriptions ) ) {
											$show_notice_about_subscriptions = true;
										}

										if ( count( $user_levels ) > 1 ) {
											esc_html_e( 'Are you sure you want to cancel all of your memberships?', 'paid-memberships-pro' );
										} else {
											esc_html_e( 'Are you sure you want to cancel your membership?', 'paid-memberships-pro' );

											// Get the next payment date for their single level.
											if ( $show_notice_about_subscriptions ) {
												$subscription = $subscriptions[0];
												$next_payment_date = $subscription->get_next_payment_date( get_option( 'date_format' ) );
											}
										}
									} else {
										$level_names = array_map(function($level_id) use ($user_levels) {
											foreach ($user_levels as $level) {
												if ($level->id == $level_id) {
													return $level->name;
												}
											}
										}, $old_level_ids);
										echo esc_html( sprintf( _n( 'Are you sure you want to cancel your %s membership?', 'Are you sure you want to cancel your %s memberships?', count( $level_names ), 'paid-memberships-pro' ), pmpro_implodeToEnglish( $level_names ) ) );

										// Do they have a subscription for any of the levels being cancelled?
										$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $old_level_ids );
										if ( ! empty( $subscriptions ) ) {
											$show_notice_about_subscriptions = true;

											// Get the next payment date if they are cancelling a single level.
											if ( count( $old_level_ids ) === 1 ) {
												$subscription = $subscriptions[0];
												$next_payment_date = $subscription->get_next_payment_date( get_option( 'date_format' ) );
											}
										}
									}
								?>
							</p>

							<?php
								if ( $show_notice_about_subscriptions ) {
									?>
									<p>
										<?php
											if ( $next_payment_date ) {
												// We have a single level with a subscription to cancel.
												echo sprintf( esc_html__( 'Your subscription will be cancelled. You will not be billed again. Your membership will remain active until %s. ', 'paid-memberships-pro' ), esc_html( $next_payment_date ) );
											} else {
												// We have multiple levels with subscriptions to cancel.
												esc_html_e( 'Some of the memberships you are cancelling have a recurring subscription. These subscriptions will be cancelled, you will not be billed again, and membership will remain active through the end of the current payment period.', 'paid-memberships-pro' );
											}
										?>
									</p>
									<?php			
								}
							?>

							<?php
							/**
							 * Hook to add additional content to the cancel page.
							 *
							 * @since 3.0
							 *
							 * @param WP_User $user The user cancelling their membership.
							 * @param array|string   $old_level_ids The level IDs being cancelled or 'all'.
							 */
							do_action( 'pmpro_cancel_before_submit', $current_user, $old_level_ids );
							?>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
								<?php
									if ( is_string( $old_level_ids ) && $old_level_ids == 'all' && count( $user_levels ) > 1 ) {
										$cancel_memberships_text = __( 'Yes, cancel all of my memberships', 'paid-memberships-pro' );
										$keep_memberships_text = __( 'No, keep my memberships', 'paid-memberships-pro' );
									} elseif ( ! is_string( $old_level_ids ) && count( $old_level_ids ) > 1 ) {
										$cancel_memberships_text = __( 'Yes, cancel these memberships', 'paid-memberships-pro' );
										$keep_memberships_text = __( 'No, keep these memberships', 'paid-memberships-pro' );
									} else {
										$cancel_memberships_text = __( 'Yes, cancel this membership', 'paid-memberships-pro' );
										$keep_memberships_text = __( 'No, keep this membership', 'paid-memberships-pro' );
									}
								?>
								<input type="hidden" name="levelstocancel" value="<?php echo esc_attr( $_REQUEST['levelstocancel'] ); ?>" />
								<input type="hidden" name="confirm" value="1" />
								<?php wp_nonce_field( 'pmpro_cancel-nonce', 'pmpro_cancel-nonce' ); ?>
								<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ) ); ?>" value="<?php echo esc_attr( $cancel_memberships_text ); ?>" />
								<a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ) ); ?>" href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php echo esc_html( $keep_memberships_text ); ?></a>
							</div> <!-- end pmpro_form_submit -->
						</div> <!-- end pmpro_card_content -->
					</form> <!-- end pmpro_form -->
					<?php
				}
				else
				{
					if ( ! empty( $user_levels ) ) {
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
					}
				}
			}
			else
			{
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
				<?php
					if ( ! pmpro_getMembershipLevelsForUser() ) {
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
