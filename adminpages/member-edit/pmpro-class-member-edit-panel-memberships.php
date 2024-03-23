<?php

class PMPro_Member_Edit_Panel_Memberships extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$this->slug = 'memberships';
		$this->title = __( 'Memberships', 'paid-memberships-pro' );
	}

	/**
	 * Display the panel contents.
	 *
	 * @since 3.0
	 */
	protected function display_panel_contents() {
		global $wpdb;

		$user = self::get_user();

		$groups = pmpro_get_level_groups_in_order();
		if ( empty ( $groups ) ) {
			return '';
		}

		// Get all membership levels for this user.
		$user_levels = pmpro_getMembershipLevelsForUser($user->ID);

		foreach ( $groups as $group ) {
			// Get all the levels in this group.
			$levels_in_group = pmpro_get_levels_for_group( $group->id );

			// Get all the levels in this group that the user has.
			$user_level_ids_in_group = array();
			$user_levels_in_group    = array();
			foreach ( $levels_in_group as $level ) {
				foreach ( $user_levels as $user_level ) {
					if ( $level->id === $user_level->id ) {
						$user_level_ids_in_group[]          = $level->id;
						$user_levels_in_group[ $level->id ] = $user_level;
					}
				}
			}

			// Set up the table for this group.
			?>
			<h3><?php echo esc_html( $group->name ); ?></h3>
			<?php

			// Show any errors for this group.
			$error_text = '';
			if ( empty( $group->allow_multiple_selections ) && count( $user_level_ids_in_group ) > 1 ) {
				$error_text = __( 'The user has multiple levels from this group. Saving this profile will remove all levels besides for the one selected below. The user\'s current levels from this group are:', 'paid-memberships-pro' );
				$error_text .= ' ' . implode( ', ', wp_list_pluck( $user_levels_in_group, 'name' ) );
			}
			if ( ! empty( $error_text) ) {
				?>
				<div class="pmpro_message pmpro_error">
					<p><?php echo esc_html( $error_text ); ?></p>
				</div>
				<?php
			}

			// If this group does not have any levels, show a message to create a new level and move on to the next group.
			if ( empty( $levels_in_group ) ) {
				?>
				<p><?php esc_html_e( 'There are no membership levels in this group.', 'paid-memberships-pro' ); ?></p>
				<a class="button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-membershiplevels' ), admin_url( 'admin.php' ) ) ) ?>"><?php esc_html_e( 'Edit Membership Levels', 'paid-memberships-pro' ) ?></a>
				<?php
				continue;
			}

			// State whether users can have multiple levels from this group.
			echo '<p>' . ( empty( $group->allow_multiple_selections ) ? esc_html__( 'Users can only hold one level from this group.', 'paid-memberships-pro' ) : esc_html__( 'Users can hold multiple levels from this group.', 'paid-memberships-pro' ) ) . '</p>';

			// Show the table for this group.
			?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Expiration', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Erase data from previous loop iterations.
					$shown_level = null;

					foreach ( $user_level_ids_in_group as $user_level_id ) {
						$shown_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $user_level_id );
						if ( empty( $shown_level ) ) {
							continue;
						}

						$shown_level_name_prefix = 'pmpro_membership_levels[' . $shown_level->id . ']';

						?>
						<tr id="pmpro-level-<?php echo esc_attr( $shown_level->id ); ?>">
							<td class="has-row-actions">
								<?php echo esc_html( $shown_level->name ); ?>
								<div class="row-actions">
								<?php
									$actions = [
										'edit'   => sprintf(
											'<a class="pmpro-member-edit-level" href="%1$s">%2$s</a>',
											'#',
											esc_html__( 'Edit', 'paid-memberships-pro' )
										),
										'cancel' => sprintf(
											'<a class="pmpro-member-cancel-level" href="%1$s">%2$s</a>',
											'#',
											esc_html__( 'Cancel', 'paid-memberships-pro' )
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
							</td>
							<td>
								<?php echo wp_kses_post( pmpro_get_membership_expiration_text( $shown_level, $user ) ); ?>
							</td>
							<td class="pmpro_levels_subscription_data has-row-actions">
								<?php
									$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $shown_level->id );
									if ( ! empty( $subscriptions ) ) {
										// If the user has more than 1 subscription, show a warning message.
										if ( count( $subscriptions ) > 1 ) {
											?>
											<div class="pmpro_message pmpro_error">
												<p>
													<?php
													echo wp_kses_post( sprintf(
														// translators: %1$d is the number of subscriptions and %2$s is the link to view subscriptions.
														_n(
															'This user has %1$d active subscription for this level. %2$s',
															'This user has %1$d active subscriptions for this level. %2$s',
															count( $subscriptions ),
															'paid-memberships-pro'
														),
														count( $subscriptions ),
														sprintf(
															'<a href="%1$s">%2$s</a>',
															esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $user->ID, 'pmpro_member_edit_panel' => 'subscriptions' ), admin_url( 'admin.php' ) ) ),
															esc_html__( 'View Subscriptions', 'paid-memberships-pro' )
														)
													) ); ?>
												</p>
											</div>
											<?php
										}
										$subscription = $subscriptions[0];
										echo esc_html( $subscription->get_cost_text() );
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

										if ( ! empty( $actions_html ) ) { ?>
											<div class="row-actions">
												<?php echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</div>
											<?php
										}
									} else {
										?>
										<p><?php esc_html_e( 'No subscription found.', 'paid-memberships-pro' ); ?></p>
										<?php
									}
								?>
							</td>
						</tr>
						<tr id="pmpro-level-<?php echo esc_attr( $shown_level->id ); ?>-edit" class="pmpro-level_change" style="display: none;">
							<td colspan="3">
								<div class="pmpro-level_change-actions">
									<?php
									$edit_level_input_name_base = 'pmpro-member-edit-memberships-panel-edit_level_' . $shown_level->id;
									?>
									<div class="pmpro-level_change-action-header">
										<h4><?php esc_html_e( 'Edit Membership', 'paid-memberships-pro' ); ?></h4>
										<p><?php printf( esc_html( 'You are editing the following membership level: %s.', 'paid-memberships-pro' ), '<strong>' . esc_html( $shown_level->name ) . '</strong>' ); ?></p>
									</div>

									<div class="pmpro-level_change-action">
										<span class="pmpro-level_change-action-label"><?php esc_html_e( 'Level Expiration', 'paid-memberships-pro' ); ?></span>
										<span class="pmpro-level_change-action-field">
											<?php
											$expiration_input_enddate = date( 'Y-m-d H:i', strtotime( '+1 year' ) ); // Default to 1 year in the future.
											if ( ! empty( $shown_level->enddate ) ) {
												// If the user's membership already has an end date, use that.
												$expiration_input_enddate = date( 'Y-m-d H:i', $shown_level->enddate );
											} elseif ( ! empty( $subscriptions ) ) {
												// If the user has a subscription, default to the subscription's next payment date.
												$expiration_input_enddate = $subscriptions[0]->get_next_payment_date('Y-m-d H:i');
												$expiration_input_next_payment_date = $expiration_input_enddate;
											}
											?>
											
											<label>
												<input type="checkbox" name="<?php echo esc_attr( $edit_level_input_name_base ); ?>[expires]" id="<?php echo esc_attr( $edit_level_input_name_base ); ?>[expires]" value="1" class="pmpro_expires_checkbox" <?php checked( ! empty( $shown_level->enddate ) ) ?>/>
												<?php esc_html_e( 'Click to set the level expiration date.', 'paid-memberships-pro' ); ?>
												<input type="datetime-local" name="<?php echo esc_attr( $edit_level_input_name_base ); ?>[expiration]" value="<?php echo esc_attr( $expiration_input_enddate ); ?>" <?php echo ( ! empty( $shown_level->enddate ) ? '' : 'style="display: none"' ); ?>>	
											</label>
											<?php
												// Show the next payment date for this member if available.
												if ( ! empty( $expiration_input_next_payment_date ) ) {
													?>
													<p class="description" style="display: none;">
													<?php
														printf(
															// translators: %s is the next payment date.
															esc_html__( 'Note: The next payment date for this level is %s.', 'paid-memberships-pro' ),
															esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expiration_input_next_payment_date ) ) )
														);
													?>
													</p>
													<?php
												}
											?>
										</span>
									</div>

									<?php
									// If the user has a subscription, show a checkbox to cancel the subscription.
									if ( ! empty( $subscriptions ) ) {
										?>
										<div class="pmpro-level_change-action">
											<span class="pmpro-level_change-action-label">
												<?php esc_html_e( 'Cancel Subscription', 'paid-memberships-pro' ); ?>
											</span>
											<span class="pmpro-level_change-action-field">
												<label>
													<input type="checkbox" id="<?php echo esc_attr( $edit_level_input_name_base ); ?>[cancel_subscription]" name="<?php echo esc_attr( $edit_level_input_name_base ); ?>[cancel_subscription]" value="1" />
													<?php esc_html_e( 'Cancel the user\'s subscription for this level.', 'paid-memberships-pro' ); ?>
												</label>
											</span>
										</div>
										<?php
									}
									?>

									<div class="pmpro-level_change-action">
										<span class="pmpro-level_change-action-label">
											<?php esc_html_e( 'Member Communication', 'paid-memberships-pro' ); ?>
										</span>
										<span class="pmpro-level_change-action-field">
											<label>
												<input type="checkbox" name="<?php echo esc_attr( $edit_level_input_name_base ); ?>[send_change_email]" value="1" />
												<?php esc_html_e( 'Send membership change email to member.', 'paid-memberships-pro' ); ?>
											</label>
										</span>
									</div>

									<div class="pmpro-level_change-action-footer">
										<button type="submit" name="pmpro-member-edit-memberships-panel-edit_level" class="button button-primary" value="<?php echo (int)$shown_level->id; ?>"><?php esc_html_e( 'Edit Membership', 'paid-memberships-pro' ) ?></button>
										<input type="button" name="cancel-edit-level" value="<?php esc_attr_e( 'Close', 'paid-memberships-pro' ); ?>" class="button button-secondary">
									</div>
								</div> <!-- end pmpro-level_change-actions -->
							</td>
						</tr>
						<tr id="pmpro-level-<?php echo esc_attr( $shown_level->id ); ?>-cancel" class="pmpro-level_change" style="display: none;">
							<td colspan="3">
								<div class="pmpro-level_change-actions">
									<?php
									$cancel_level_input_name_base = 'pmpro-member-edit-memberships-panel-cancel_level_' . $shown_level->id;
									?>
									<div class="pmpro-level_change-action-header">
										<h4><?php esc_html_e( 'Cancel Membership', 'paid-memberships-pro' ); ?></h4>
										<p><?php printf( esc_html( 'This change will permanently cancel the following membership level: %s.', 'paid-memberships-pro' ),  '<strong>' . esc_html( $shown_level->name ) . '</strong>' ); ?></p>
									</div>

									<?php
									$last_order = new MemberOrder();
									$last_order->getLastMemberOrder( $user->ID, array( 'success', 'refunded' ), $shown_level->id );
									if ( pmpro_allowed_refunds( $last_order ) ) { ?>
										<div class="pmpro-level_change-action">
											<span class="pmpro-level_change-action-label"><?php esc_html_e( 'Refund Payment', 'paid-memberships-pro' ); ?></span>
											<span class="pmpro-level_change-action-field">
												<label>
													<input type="checkbox" name="<?php echo esc_attr( $cancel_level_input_name_base ); ?>[refund]" value="<?php echo (int)$last_order->id; ?>" />
													<?php printf( esc_html( 'Refund the last payment (%s).', 'paid-memberships-pro' ), pmpro_escape_price( pmpro_formatPrice( $last_order->total ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</label>
											</span>
										</div>
										<?php
									}

									// If this group only allows users to have a single level and the user has a subscription, show the option to cancel it.
									if ( ! empty( $subscriptions ) ) {
										?>
										<div class="pmpro-level_change-action">
											<span class="pmpro-level_change-action-label">
												<label for="<?php echo esc_attr( $cancel_level_input_name_base ); ?>[subscription_action]">
													<?php esc_html_e( 'Current Subscription', 'paid-memberships-pro' ); ?>
												</label>
											</span>
											<span class="pmpro-level_change-action-field">
												<select id="<?php echo esc_attr( $cancel_level_input_name_base ); ?>[subscription_action]" name="<?php echo esc_attr( $cancel_level_input_name_base ); ?>[subscription_action]">
													<option value="cancel"><?php esc_html_e( 'Cancel payment subscription (Recommended)', 'paid-memberships-pro' ); ?></option>
													<option value="keep"><?php esc_html_e( 'Keep subscription active', 'paid-memberships-pro' ); ?></option>
												</select>
											</span>
										</div>
										<?php
									}
									?>

									<div class="pmpro-level_change-action">
										<span class="pmpro-level_change-action-label">
											<?php esc_html_e( 'Member Communication', 'paid-memberships-pro' ); ?>
										</span>
										<span class="pmpro-level_change-action-field">
											<label>
												<input type="checkbox" name="<?php echo esc_attr( $cancel_level_input_name_base ); ?>[send_change_email]" value="1" />
												<?php esc_html_e( 'Send membership change email to member.', 'paid-memberships-pro' ); ?>
											</label>
										</span>
									</div>

									<div class="pmpro-level_change-action-footer">
										<button type="submit" name="pmpro-member-edit-memberships-panel-cancel_level" class="button button-primary" value="<?php echo (int)$shown_level->id; ?>"><?php esc_html_e( 'Cancel Membership', 'paid-memberships-pro' ) ?></button>
										<input type="button" name="cancel-cancel-level" value="<?php esc_attr_e( 'Close', 'paid-memberships-pro' ); ?>" class="button button-secondary">
									</div>
								</div> <!-- end pmpro-level_change-actions -->
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>

				<?php
					// Only show actions if there are levels the user can have or change to.
					if ( empty( $group->allow_multiple_selections ) || count( $levels_in_group ) !== count( $user_level_ids_in_group ) ) { ?>
						<tfoot>
							<tr>
								<?php
								$js_target_class = empty( $group->allow_multiple_selections ) ? 'pmpro-member-change-level' : 'pmpro-member-add-level';
								$is_single_membership = count( $user_level_ids_in_group ) === 0;
								$link_icon = ( $group->allow_multiple_selections || $is_single_membership ) ? 'plus' : 'image-rotate';
								$link_text = $group->allow_multiple_selections || $is_single_membership ? __( 'Add Membership', 'paid-memberships-pro' ) : __( 'Change Membership', 'paid-memberships-pro' );
								?>
								<td colspan="3"><a class="button-secondary pmpro-has-icon pmpro-has-icon-<?php echo esc_attr( $link_icon ) . ' ' . esc_attr( $js_target_class ); ?>" href="#"><?php echo esc_html( $link_text ); ?></a></td>
							</tr>
							<tr class="pmpro-level_change" style="display: none;">
								<td colspan="3">
									<div class="pmpro-level_change-actions">
										<?php
											$add_level_to_group_input_name_base = 'pmpro-member-edit-memberships-panel-add_level_to_group_' . $group->id;
										?>
										<div class="pmpro-level_change-action-header">
											<h4><?php echo esc_html( $link_text ); ?></h4>
											<?php
											// If the group only allows a single level and the user already has a level, note the level that will be removed.
											if ( empty( $group->allow_multiple_selections ) && ! empty( $shown_level ) ) {
												?>
												<p><?php printf( esc_html( 'This change will remove the following membership level: %s.', 'paid-memberships-pro' ), '<strong>' . esc_html( $shown_level->name ) . '</strong>' ); ?></p>
												<?php
											}
											?>
										</div>

										<div class="pmpro-level_change-action">
											<span class="pmpro-level_change-action-label">
												<label for="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[level_id]">
													<?php esc_html_e( 'New Membership Level', 'paid-memberships-pro' ); ?>
												</label>
											</span>
											<span class="pmpro-level_change-action-field">
												<select id="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[level_id]" name="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[level_id]">
													<option value="">-- <?php esc_html_e( 'Choose Level', 'paid-memberships-pro' );?> --</option>
													<?php
													foreach ( $levels_in_group as $level ) {
														// If the user already has this level, don't allow them to add it.
														if ( in_array( $level->id, $user_level_ids_in_group ) ) {
															continue;
														}
														?>
														<option value="<?php echo esc_attr( $level->id ) ?>" <?php selected($level->id, (isset($shown_level->ID) ? $shown_level->ID : 0 )); ?>><?php echo esc_html( $level->name ); ?></option>
														<?php
													}
													?>
												</select>
											</span>
										</div>

										<div class="pmpro-level_change-action">
											<span class="pmpro-level_change-action-label"><?php esc_html_e( 'Level Expiration', 'paid-memberships-pro' ); ?></span>
											<span class="pmpro-level_change-action-field">
												<label>
													<input type="checkbox" name="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[expires]" id="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[expires]" value="1" class="pmpro_expires_checkbox" />
													<?php esc_html_e( 'Click to set the level expiration date.', 'paid-memberships-pro' ); ?>
													<input type="datetime-local" id="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[expiration]" name="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[expiration]" value="<?php echo esc_attr( date( 'Y-m-d H:i', strtotime( '+1 year' ) ) ); ?>" style="display: none" >
												</label>
											</span>
										</div>
									
										<?php
										// If this group only allows users to have a single level, get the last member order and see if we can refund it.
										if ( empty( $group->allow_multiple_selections ) && ! empty( $shown_level ) ) {
											$last_order = new MemberOrder();
											$last_order->getLastMemberOrder( $user->ID, array( 'success', 'refunded' ), $shown_level->id );
											if ( pmpro_allowed_refunds( $last_order ) ) { ?>
												<div class="pmpro-level_change-action">
														<span class="pmpro-level_change-action-label"><?php esc_html_e( 'Refund Payment', 'paid-memberships-pro' ); ?></span>
														<span class="pmpro-level_change-action-field">
														<label>
															<input type="checkbox" name="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[refund]" value="<?php echo (int)$last_order->id; ?>" />
															<?php printf( esc_html( 'Refund the last payment (%s).', 'paid-memberships-pro' ), pmpro_escape_price( pmpro_formatPrice( $last_order->total ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
														</label>
													</span>
												</div>
												<?php
											}
										}

										// If this group only allows users to have a single level and the user has a subscription, show the option to cancel it.
										if ( empty( $group->allow_multiple_selections ) && ! empty( $shown_level ) && ! empty( $subscriptions ) ) {
											?>
											<div class="pmpro-level_change-action">
												<span class="pmpro-level_change-action-label">
													<label for="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[subscription_action]">
														<?php esc_html_e( 'Current Subscription', 'paid-memberships-pro' ); ?>
													</label>
												</span>
												<span class="pmpro-level_change-action-field">
													<select id="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[subscription_action]" name="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[subscription_action]">
														<option value="cancel"><?php esc_html_e( 'Cancel payment subscription (Recommended)', 'paid-memberships-pro' ); ?></option>
														<option value="keep"><?php esc_html_e( 'Keep subscription active', 'paid-memberships-pro' ); ?></option>
													</select>
												</span>
											</div>
											<?php
										}
										?>

										<div class="pmpro-level_change-action">
											<span class="pmpro-level_change-action-label">
												<?php esc_html_e( 'Member Communication', 'paid-memberships-pro' ); ?>
											</span>
											<span class="pmpro-level_change-action-field">
												<label>
													<input type="checkbox" name="<?php echo esc_attr( $add_level_to_group_input_name_base ); ?>[send_change_email]" value="1" />
													<?php esc_html_e( 'Send membership change email to member.', 'paid-memberships-pro' ); ?>
												</label>
											</span>
										</div>

										<div class="pmpro-level_change-action-footer">
											<button type="submit" name="pmpro-member-edit-memberships-panel-add_level_to_group" class="button button-primary" value="<?php echo (int)$group->id; ?>"><?php echo esc_html( $link_text ) ?></button>
											<input type="button" name="cancel-add-level" value="<?php esc_attr_e( 'Close', 'paid-memberships-pro' ); ?>" class="button button-secondary">
										</div>

									</div> <!-- end pmpro-level_change-actions -->
								</td>
							</tr>
						</tfoot>
						<?php
					}
				?>
			</table>
			<?php
		}
	?>
	<script>
		// Button to show edit membership.
		jQuery('#pmpro-member-edit-memberships-panel a.pmpro-member-edit-level').on('click', function (event) {
			event.preventDefault();
			var currentRow = jQuery(this).closest('tr');
			currentRow.next('tr').show();
			currentRow.closest('table').find('tfoot').hide();
			currentRow.hide();

			// Add muted class to all other <tr> elements in the same table.
			currentRow.closest('table').find('tr:not(.pmpro-level_change)').addClass('pmpro_opaque');
		});

		// Button to cancel editing membership.
		jQuery('#pmpro-member-edit-memberships-panel input[name="cancel-edit-level"]').on('click', function (event) {
			event.preventDefault();
			var currentRow = jQuery(this).closest('tr');
			currentRow.prev('tr').show();
			currentRow.closest('table').find('tfoot').show();
			currentRow.hide();

			// Remove pmpro_opaque class from all <tr> elements.
			var table = jQuery(this).closest('table');
			table.find('tr').removeClass('pmpro_opaque');
		});

		// Button to show cancel membership.
		jQuery('#pmpro-member-edit-memberships-panel a.pmpro-member-cancel-level').on('click', function (event) {
			event.preventDefault();
			var currentRow = jQuery(this).closest('tr');
			currentRow.next('tr').next('tr').show();
			currentRow.closest('table').find('tfoot').hide();
			currentRow.hide();

			// Add muted class to all other <tr> elements in the same table.
			currentRow.closest('table').find('tr:not(.pmpro-level_change)').addClass('pmpro_opaque');
		});

		// Button to cancel cancelling membership.
		jQuery('#pmpro-member-edit-memberships-panel input[name="cancel-cancel-level"]').on('click', function (event) {
			event.preventDefault();
			var currentRow = jQuery(this).closest('tr');
			currentRow.prev('tr').prev('tr').show();
			currentRow.closest('table').find('tfoot').show();
			currentRow.hide();

			// Remove pmpro_opaque class from all <tr> elements.
			var table = jQuery(this).closest('table');
			table.find('tr').removeClass('pmpro_opaque');
		});

		// Button to change membership in "one per" groups.
		jQuery('#pmpro-member-edit-memberships-panel a.pmpro-member-change-level').on('click', function (event) {
			event.preventDefault();
			var currentRow = jQuery(this).closest('tr');
			currentRow.next('tr').show();
			currentRow.closest('table').find('tbody').hide();
			currentRow.closest('table').find('thead').hide();
			currentRow.hide();

			// Add muted class to all other <tr> elements in the same table.
			currentRow.closest('table').find('tr:not(.pmpro-level_change)').addClass('pmpro_opaque');
		});

		// Button to add membership in "multiple" groups.
		jQuery('#pmpro-member-edit-memberships-panel a.pmpro-member-add-level').on('click', function (event) {
			event.preventDefault();
			var currentRow = jQuery(this).closest('tr');
			currentRow.next('tr').show();
			currentRow.hide();

			// Add muted class to all other <tr> elements in the same table.
			currentRow.closest('table').find('tr:not(.pmpro-level_change)').addClass('pmpro_opaque');
		});

		// Button to cancel adding membership.
		jQuery('#pmpro-member-edit-memberships-panel input[name="cancel-add-level"]').on('click', function (event) {
			event.preventDefault();
			var table = jQuery(this).closest('table');
			table.find('thead').show();
			table.find('tbody').show();
			table.find('tfoot').find('tr').show();
			table.find('tfoot').find('tr').next('tr').hide();

			// Remove pmpro_opaque class from all <tr> elements.
			table.find('tr').removeClass('pmpro_opaque');
		});

		// Show/hide the expiration date field when the checkbox is clicked.
		jQuery( '#pmpro-member-edit-memberships-panel .pmpro_expires_checkbox' ).on( 'change', function() {
			var checkbox = jQuery(this);
			if (checkbox.is(':checked')) {
				checkbox.next('input').show();
				checkbox.closest('.pmpro-level_change-action-field').find('p.description').show();
			} else {
				checkbox.next('input').hide();
				checkbox.closest('.pmpro-level_change-action-field').find('p.description').hide();
			}
		} );
	</script>
	<?php
		// Show all membership history for user.
		$levelshistory = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = %s ORDER BY id DESC", $user->ID ) );

		if ( $levelshistory ) { ?>
			<div class="pmpro_section" data-visibility="hidden" data-activated="false">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="false">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
					<?php esc_html_e( 'Membership History', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside" style="display: none;">
			<?php
				// Build the selectors for the membership levels history list based on history count.
				$levelshistory_classes = array();
				if ( ! empty( $levelshistory ) && count( $levelshistory ) > 10 ) {
					$levelshistory_classes[] = "pmpro_scrollable";
				}
				$levelshistory_class = implode( ' ', array_unique( $levelshistory_classes ) );
				?>
				<div id="member-history-memberships" class="<?php echo esc_attr( $levelshistory_class ); ?>">
					<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Level ID', 'paid-memberships-pro' ); ?>
								<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Start Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Date Modified', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'End Date', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Level Cost', 'paid-memberships-pro' ); ?></th>
								<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
								<?php do_action( 'pmpromh_member_history_extra_cols_header' ); ?>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach ( $levelshistory as $levelhistory ) {
								$level = pmpro_getLevel( $levelhistory->membership_id );

								if ( $levelhistory->enddate === null || $levelhistory->enddate == '0000-00-00 00:00:00' ) {
									$levelhistory->enddate = __( 'Never', 'paid-memberships-pro' );
								} else {
									$levelhistory->enddate = date_i18n( get_option( 'date_format'), strtotime( $levelhistory->enddate ) );
								} ?>
								<tr>
									<td><?php if ( ! empty( $level ) ) { echo esc_html( $level->id ); } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
									<td><?php if ( ! empty( $level ) ) { echo esc_html( $level->name ); } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
									<td><?php echo esc_html( ( $levelhistory->startdate === '0000-00-00 00:00:00' ? __('N/A', 'paid-memberships-pro') : date_i18n( get_option( 'date_format' ), strtotime( $levelhistory->startdate ) ) ) ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format'), strtotime( $levelhistory->modified ) ) ); ?></td>
									<td><?php echo esc_html( $levelhistory->enddate ); ?></td>
									<td><?php echo wp_kses_post( pmpro_getLevelCost( $levelhistory, true, true ) ); ?></td>
									<td>
										<?php 
											if ( empty( $levelhistory->status ) ) {
												esc_html_e( '&#8212;', 'paid-memberships-pro' );
											} else {
												echo esc_html( $levelhistory->status ); 
											}
										?>
									</td>
									<?php do_action( 'pmpromh_member_history_extra_cols_body', $user, $level ); ?>
								</tr>
								<?php
							}
						?>
						</tbody>
					</table>
				</div>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<?php
		}
	}

	/**
	 * Save the panel.
	 *
	 * @since 3.0
	 */
	public function save() {
		global $wpdb;

		if ( ! current_user_can( pmpro_get_edit_member_capability() ) ) {
			pmpro_setMessage( __( "You do not have permission to update this user's membership levels.", 'paid-memberships-pro' ), 'pmpro_error' );
			return;
		}

		// Get the user that we are editing.
		$user = self::get_user();
		if ( empty( $user->ID ) ) {
			pmpro_setMessage( __( 'User not found.', 'paid-memberships-pro' ), 'pmpro_error' );
			return;
		}

		if ( ! empty( $_REQUEST[ 'pmpro-member-edit-memberships-panel-add_level_to_group' ] ) ) {
			// Get the group that we are adding a level to.
			$group_id = intval( $_REQUEST[ 'pmpro-member-edit-memberships-panel-add_level_to_group' ] );
			if ( empty( $group_id ) ) {
				pmpro_setMessage( __( 'Please pass a group to add a level for.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Get the data for the level to add.
			$level_data = empty( $_REQUEST[ 'pmpro-member-edit-memberships-panel-add_level_to_group_' . $group_id ] ) ? null : $_REQUEST[ 'pmpro-member-edit-memberships-panel-add_level_to_group_' . $group_id ];
			if ( empty( $level_data ) ) {
				// At the very least, 'level_id' should be set.
				pmpro_setMessage( __( 'Please pass level data to add.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Sanitize all the data.
			$level_data = array_map( 'sanitize_text_field', $level_data );

			// Get the level ID to add.
			$level_id = empty( $level_data[ 'level_id' ] ) ? null : intval( $level_data[ 'level_id' ] );
			if ( empty( $level_id ) ) {
				pmpro_setMessage( __( 'Please pass a level ID to add.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Get the expiration date to set.
			$expiration = ( ! empty( $level_data[ 'expires' ] ) && ! empty( $level_data[ 'expiration' ] ) ) ? $level_data[ 'expiration' ] : null;

			// Check if we should refund the last order.
			$refund_last_order = ! empty( $level_data[ 'refund' ] ) ? intval( $level_data[ 'refund' ] ) : null;

			// Check if we should keep the subscription active.
			$keep_subscription = ! empty( $level_data[ 'subscription_action' ] ) && 'keep' === $level_data[ 'subscription_action' ];

			// Check if we should send the change email.
			$send_change_email = ! empty( $level_data[ 'send_change_email' ] );

			// Execute changes.
			// Start with refunds.
			if ( $refund_last_order ) {
				$refund_order = new MemberOrder( $refund_last_order );

				// Make sure the order belongs to the user.
				if ( (int)$refund_order->user_id !== (int)$user->ID ) {
					pmpro_setMessage( __( 'The order to refund does not belong to this user.', 'paid-memberships-pro' ), 'pmpro_error' );
					return;
				}

				// Process the refund.
				pmpro_refund_order( $refund_order );
			}

			// If we need to keep the subscription, add the filter.
			if ( $keep_subscription ) {
				add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );
			}

			// Add the membership level.
			$level_to_add = array(
				'user_id'         => $user->ID,
				'membership_id'   => $level_id,
				'code_id'         => '',
				'initial_payment' => 0,
				'billing_amount'  => 0,
				'cycle_number'    => 0,
				'cycle_period'    => 'month',
				'billing_limit'   => 0,
				'trial_amount'    => 0,
				'trial_limit'     => 0,
				'startdate'       => current_time( 'mysql' ),
				'enddate'         => empty( $expiration ) ? 'NULL' : date( 'Y-m-d H:i:s', strtotime( $expiration ) )
			);
			$change_successful = pmpro_changeMembershipLevel( $level_to_add, $user->ID, 'admin_changed' );

			// Remove the filter.
			if ( $keep_subscription ) {
				remove_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );
			}

			// Check if the change was successful.
			if ( ! $change_successful ) {
				pmpro_setMessage( __( 'Error changing membership level.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// If we need to send the change email, do so.
			if ( ! empty( $send_change_email ) ) {
				// Send an email to the user.
				$myemail = new PMProEmail();
				$myemail->sendAdminChangeEmail( $user );

				// Send an email to the admin.
				$myemail = new PMProEmail();
				$myemail->sendAdminChangeAdminEmail( $user );
			}
		} elseif ( ! empty( $_REQUEST[ 'pmpro-member-edit-memberships-panel-edit_level' ] ) ) {
			// Get the level that we are editing.
			$level_id = intval( $_REQUEST[ 'pmpro-member-edit-memberships-panel-edit_level' ] );
			if ( empty( $level_id ) ) {
				pmpro_setMessage( __( 'Please pass a level to edit.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Get the data for the level to edit.
			$level_data = empty( $_REQUEST[ 'pmpro-member-edit-memberships-panel-edit_level_' . $level_id ] ) ? null : $_REQUEST[ 'pmpro-member-edit-memberships-panel-edit_level_' . $level_id ];
			if ( empty( $level_data ) ) {
				// At the very least, 'expiration' should be set even if the checkbox is empty.
				pmpro_setMessage( __( 'Please pass level data to edit.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Sanitize all the data.
			$level_data = array_map( 'sanitize_text_field', $level_data );

			// Update the expiration date.
			$expiration = ( ! empty( $level_data[ 'expires' ] ) && ! empty( $level_data[ 'expiration' ] ) ) ? $level_data[ 'expiration' ] : 'NULL';
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->pmpro_memberships_users SET enddate = %s WHERE user_id = %d AND membership_id = %d AND status = 'active'", $expiration, $user->ID, $level_id ) );

			// If the expiration query failed, set an error.
			if ( $wpdb->last_error ) {
				pmpro_setMessage( __( 'Error updating expiration date.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Check if we should cancel the subscription.
			$cancel_subscription = ! empty( $level_data[ 'cancel_subscription' ] );
			if ( $cancel_subscription ) {
				// Get all subscriptions for this user's membership.
				$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level_id );
				foreach( $subscriptions as $subscription ) {
					// Cancel the subscription.
					$subscription->cancel_at_gateway();
				}
			}

			// Check if we should send the change email.
			$send_change_email = ! empty( $level_data[ 'send_change_email' ] );
			if ( $send_change_email ) {
				// Send an email to the user.
				$myemail = new PMProEmail();
				$myemail->sendAdminChangeEmail( $user );

				// Send an email to the admin.
				$myemail = new PMProEmail();
				$myemail->sendAdminChangeAdminEmail( $user );
			}

		} elseif ( ! empty( $_REQUEST[ 'pmpro-member-edit-memberships-panel-cancel_level' ] ) ) {
			// Get the level that we are cancelling.
			$level_id = intval( $_REQUEST[ 'pmpro-member-edit-memberships-panel-cancel_level' ] );
			if ( empty( $level_id ) ) {
				pmpro_setMessage( __( 'Please pass a level to cancel.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Get the data for the level to cancel.
			$level_data = empty( $_REQUEST[ 'pmpro-member-edit-memberships-panel-cancel_level_' . $level_id ] ) ? null : $_REQUEST[ 'pmpro-member-edit-memberships-panel-cancel_level_' . $level_id ];
			if ( empty( $level_data ) ) {
				// It is possible that no data is passed if the user is cancelling a level that has no subscription. In this case, we will just cancel the level.
				$level_data = array();
			}

			// Sanitize all the data.
			$level_data = array_map( 'sanitize_text_field', $level_data );

			// Check if we should refund the last order.
			$refund_last_order = ! empty( $level_data[ 'refund' ] ) ? intval( $level_data[ 'refund' ] ) : null;
			if ( $refund_last_order ) {
				$refund_order = new MemberOrder( $refund_last_order );

				// Make sure the order belongs to the user.
				if ( (int)$refund_order->user_id !== (int)$user->ID ) {
					pmpro_setMessage( __( 'The order to refund does not belong to this user.', 'paid-memberships-pro' ), 'pmpro_error' );
					return;
				}

				// Process the refund.
				pmpro_refund_order( $refund_order );
			}

			// Check if we should keep the subscription active.
			$keep_subscription = ! empty( $level_data[ 'subscription_action' ] ) && 'keep' === $level_data[ 'subscription_action' ];

			// If we need to keep the subscription, add the filter.
			if ( $keep_subscription ) {
				add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );
			}

			// Cancel the membership level.
			$change_successful = pmpro_cancelMembershipLevel( $level_id, $user->ID, 'admin_cancelled' );

			// Remove the filter.
			if ( $keep_subscription ) {
				remove_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );
			}

			// Check if the change was successful.
			if ( ! $change_successful ) {
				pmpro_setMessage( __( 'Error cancelling membership level.', 'paid-memberships-pro' ), 'pmpro_error' );
				return;
			}

			// Check if we should send the change email.
			$send_change_email = ! empty( $level_data[ 'send_change_email' ] );
			if ( $send_change_email ) {
				// Send an email to the user.
				$myemail = new PMProEmail();
				$myemail->sendCancelEmail( $user );

				// Send an email to the admin.
				$myemail = new PMProEmail();
				$myemail->sendCancelAdminEmail( $user );
			}
		} else {
			pmpro_setMessage( __( 'Membership action not found.', 'paid-memberships-pro' ), 'pmpro_error');
			return;
		}

		// Clear the level cache.
		pmpro_clear_level_cache_for_user( $user->ID );

		pmpro_setMessage( __( 'Memberships updated.', 'paid-memberships-pro' ), 'pmpro_success' );
	}
}