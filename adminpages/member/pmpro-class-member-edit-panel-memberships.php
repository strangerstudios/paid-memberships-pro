<?php

class PMPro_Member_Edit_Panel_Memberships extends PMPro_Member_Edit_Panel {
	public function get_title( $user_id ) {
		return __( 'Memberships', 'paid-memberships-pro' );
	}

	public function display( $user_id ) {
		global $wpdb;

		$user = get_userdata( $user_id );

		$groups = pmpro_get_level_groups_in_order();
		if ( empty ( $groups ) ) {
			return '';
		}

		// Get all membership levels for this user.
		$user_levels = pmpro_getMembershipLevelsForUser($user->ID);

		$show_membership_level = true;
		$show_membership_level = apply_filters( 'pmpro_profile_show_membership_level', $show_membership_level, $user );
		if ( $show_membership_level ) {
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
				<?php if ( empty( $group->allow_multiple_selections ) ) {
					// If the user somehow has multiple levels from this group, show a warning that non-selected levels will be removed on save.
					if ( count( $user_level_ids_in_group ) > 1 ) { ?>
						<div class="pmpro_message pmpro_error">
							<p>
								<?php
								esc_html_e( 'The user has multiple levels from this group. Saving this profile will remove all levels besides for the one selected below. The user\'s current levels from this group are:', 'paid-memberships-pro' );
								echo ' ' . esc_html( implode( ', ', wp_list_pluck( $user_levels_in_group, 'name' ) ) );
								?>
							</p>
						</div>
						<?php
						}
					?>
					<p><?php esc_html_e( 'Users can only hold one level from this group.', 'paid-memberships-pro' ); ?></p>
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
							foreach ( $user_level_ids_in_group as $user_level_id ) {
								$shown_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $user_level_id );
								if ( ! empty( $shown_level ) ) {
									$shown_level_name_prefix = 'pmpro_membership_levels[' . $shown_level->id . ']';
								} else {
									$shown_level_name_prefix = 'pmpro_membership_levels[0]';
								}
								?>
								<tr id="pmpro-level-<?php echo esc_attr( $level->id ); ?>">
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
													'<a href="%1$s">%2$s</a>',
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
												echo implode( ' | ', $actions_html );
											}
										?>
										</div>
									</td>
									<td>
										<?php
											// Get the expiration date to show for this level.
											$enddate_to_show = $shown_level->enddate;
											if ( empty( $enddate_to_show ) ) {
												esc_html_e( 'Never', 'paid-memberships-pro' );
											} else {
												echo esc_html( date_i18n( get_option( 'date_format'), $enddate_to_show ) );
											}
										?>
									</td>
									<td class="pmpro_levels_subscription_data">
										<?php
											$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $shown_level->id );
											if ( ! empty( $subscriptions ) ) {
												// If the user has more than 1 subscription, show a warning message.
												if ( count( $subscriptions ) > 1 ) {
													?>
													<div class="pmpro_message pmpro_error">
														<p>
															<?php
															esc_html_e( 'The user has multiple active subscriptions for this level. Old payment subscriptions should be cancelled from the Subscriptions tab.', 'paid-memberships-pro' );
															?>
														</p>
													</div>
													<?php
												}
												$subscription = $subscriptions[0];
												$billing_amount = $subscription->get_billing_amount();
												$cycle_number   = $subscription->get_cycle_number();
												$cycle_period   = $subscription->get_cycle_period();

												if ( $cycle_number == 1 ) {
													$cost_text = sprintf( esc_html__( '%1$s per %2$s', 'paid-memberships-pro' ), pmpro_formatPrice( $billing_amount ), $cycle_period );
												} else {
													$cost_text = sprintf( esc_html__( '%1$s every %2$s %3$ss', 'paid-memberships-pro' ), pmpro_formatPrice( $billing_amount ), $cycle_number, $cycle_period );
												}
												?>
												<p><?php echo esc_html( $cost_text ); ?></p>
												<?php
											} else {
												?>
												<p><?php esc_html_e( 'No subscription found.', 'paid-memberships-pro' ); ?></p>
												<?php
											}
										?>
									</td>
								</tr>
								<tr id="pmpro-level-<?php echo esc_attr( $level->id ); ?>-edit" style="display: none;">
									<td colspan="3">
										.. actions slide out here.
									</td>
								</tr>
								<?php
							}
						?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="3"><a class="button-secondary pmpro-has-icon pmpro-has-icon-image-rotate pmpro-member-change-level" href="#"><?php esc_html_e( 'Change Membership', 'paid-memberships-pro' ); ?></a></td>
							</tr>
							<tr id="pmpro-level-change" style="display: none;">
								<td colspan="3">
									<div>
										<label for="membership_level"><?php esc_html_e( 'Current Level', 'paid-memberships-pro' ); ?></label></th>
										<select name="membership_level">
											<option value="" <?php if ( empty( $shown_level->ID ) ) { ?>selected="selected"<?php } ?>>-- <?php _e("None", 'paid-memberships-pro' );?> --</option>
										<?php foreach ( $levels_in_group as $level ) { ?>
											<option value="<?php echo esc_attr( $level->id ) ?>" <?php selected($level->id, (isset($shown_level->ID) ? $shown_level->ID : 0 )); ?>><?php echo esc_html( $level->name ); ?></option>
										<?php } ?>
										</select>
										<p id="cancel_description" class="description hidden"><?php esc_html_e("This will not change the subscription at the gateway unless the 'Cancel' checkbox is selected below.", 'paid-memberships-pro' ); ?></p>
									</div>

									<?php
										// Show checkbox to set whether the membership expires if a level is selected.
										$current_level_expires = false;
										$enddate_to_show = date( 'Y-m-d H:i', strtotime( '+1 year' ) );
										$shown_level_name_prefix = 'pmpro_membership_levels[]';
										if ( ! empty( $shown_level ) ) {
											$current_level_expires = ! empty( $shown_level->enddate );
											$enddate_to_show = ! empty( $shown_level->enddate ) ? date( 'Y-m-d H:i', $shown_level->enddate ) : $enddate_to_show;
										}
									?>
									<div class="more_level_options" <?php if ( empty( $shown_level ) ) { ?>style="display: none;"<?php } ?>>
										<input type="checkbox" name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[expires]" id="<?php echo esc_attr( $shown_level_name_prefix ); ?>[expires]" value="1" class="pmpro_expires_checkbox" <?php checked( $current_level_expires ); ?> />
										<label for="<?php echo esc_attr( $shown_level_name_prefix ); ?>[expires]"><?php esc_html_e( 'Set Expiration', 'paid-memberships-pro' ); ?></label>
										<input type="datetime-local" name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[expiration]" value="<?php echo esc_attr( $enddate_to_show ); ?>" <?php if ( ! $current_level_expires ) { echo 'style="display: none"'; } ?>>
									</div>
								
									<?php
										// Get the last member order and see if we can refund it.
										if ( ! empty( $shown_level ) ) {
											$last_order = new MemberOrder();
											$last_order->getLastMemberOrder( $user->ID, array( 'success', 'refunded' ), $shown_level->id );
											if ( pmpro_allowed_refunds( $last_order ) ) { ?>
												<div class="more_level_options">
													<label for="<?php echo esc_attr( $shown_level_name_prefix ); ?>[refund]">
														<input type="checkbox" name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[refund]" value="1" />
														<?php printf( esc_html( 'Refund the last payment (%s).', 'paid-memberships-pro' ), pmpro_formatPrice( $last_order->total ) ); ?>
													</label>
												</div>
												<?php
											}
										}
									?>

									<?php if ( ! empty( $shown_level ) && ! empty( $subscriptions ) ) { ?>
										<div class="more_level_options">
											<label for="<?php echo esc_attr( $shown_level_name_prefix ); ?>[subscription_action]">
												<select name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[subscription_action]">
													<option value="cancel"><?php esc_html_e( 'Cancel payment subscription (Recommended)', 'paid-memberships-pro' ); ?></option>
													<option value="keep"><?php esc_html_e( 'Keep subscription active', 'paid-memberships-pro' ); ?></option>
												</select>
											</label>
										</div>
									<?php } ?>

									<div id="pmpro_level_change_options" style="display: none;">
										<label for="pmpro_send_change_email">
											<input type="checkbox" id="pmpro_send_change_email" name="pmpro_send_change_email" value="1" />
											<?php esc_html_e( 'Send membership change email to member.', 'paid-memberships-pro' ); ?>
										</label>
									</div>

									<div class="submit">
										<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">					
										<input type="button" value="Save Changes" class="button button-primary">
										<input type="button" name="cancel" value="Cancel" class="button button-secondary">
									</div>

									<script>
										// Show/hide the expiration date field when the checkbox is clicked.
										jQuery( '.pmpro_expires_checkbox' ).on( 'change', function() {
											if ( jQuery( this ).is( ':checked' ) ) {
												jQuery( this ).next().next( 'input' ).show();
											} else {
												jQuery( this ).next( 'input' ).hide();
											}

											// Show the "level change" options.
											jQuery( '#pmpro_level_change_options' ).show();
										} );
									</script>
								</td>
							</tr>
						</tfoot>
					</table>
				<?php } else {
					// Users can have multiple levels from this group.
					?>
					<p><?php esc_html_e( 'Users can hold multiple levels from this group.', 'paid-memberships-pro' ); ?></p>
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
							foreach ( $levels_in_group as $level ) {
								$has_level = in_array( $level->id, $user_level_ids_in_group );
								$name_prefix = 'pmpro_membership_levels[' . $level->id . ']';
								if ( empty( $has_level ) ) {
									// Only show levels the user has, unless they have a subscription for a level that they do not have.
									$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level->id );
									if ( empty( $subscriptions ) ) {
										continue;
									}
								}
								?>
								<tr id="pmpro-level-<?php echo esc_attr( $level->id ); ?>">
									<td class="has-row-actions">
										<?php echo esc_html( $level->name ); ?>
										<div class="row-actions">
										<?php
											$actions = [
												'edit'   => sprintf(
													'<a class="pmpro-member-edit-level" href="%1$s">%2$s</a>',
													'#',
													esc_html__( 'Edit', 'paid-memberships-pro' )
												),
												'cancel' => sprintf(
													'<a class="pmpro-member-edit-level" href="%1$s">%2$s</a>',
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
												echo implode( ' | ', $actions_html );
											}
										?>
										</div>
									</td>
									<td>
										<?php
											// Get the expiration date to show for this level.
											$enddate_to_show = date( 'Y-m-d H:i', strtotime( '+1 year' ) );
											$has_enddate = false;
											if ( ! empty( $user_levels_in_group[ $level->id ] ) ) {
												$has_enddate = ! empty( $user_levels_in_group[ $level->id ]->enddate );
												$enddate_to_show = $has_enddate ? date( 'Y-m-d H:i', $user_levels_in_group[ $level->id ]->enddate ) : $enddate_to_show;
											}

											// Show the end date for this level.
											if ( ! $has_enddate ) {
												esc_html_e( 'Never', 'paid-memberships-pro' );
											} else {
												echo esc_html( date_i18n( get_option( 'date_format'), $enddate_to_show ) );
											}
										?>
									</td>
									<td class="pmpro_levels_subscription_data">
										<?php
										// If the user has this level and they have a subscription for the level, let's show the subscription amount.
										if ( $has_level  ) {
											$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level->id );
											if ( ! empty( $subscriptions ) ) {
												// If the user has more than 1 subscription, show a warning message.
												if ( count( $subscriptions ) > 1 ) {
													?>
													<div class="pmpro_message pmpro_error">
														<p>
															<?php
															esc_html_e( 'The user has multiple active subscriptions for this level. Old payment subscriptions should be cancelled using the Member History tool below.', 'paid-memberships-pro' );
															?>
														</p>
													</div>
													<?php
												}
												$subscription = $subscriptions[0];
												$billing_amount = $subscription->get_billing_amount();
												$cycle_number   = $subscription->get_cycle_number();
												$cycle_period   = $subscription->get_cycle_period();

												if ( $cycle_number == 1 ) {
													$cost_text = sprintf( esc_html__( '%1$s per %2$s', 'paid-memberships-pro' ), pmpro_formatPrice( $billing_amount ), $cycle_period );
												} else {
													$cost_text = sprintf( esc_html__( '%1$s every %2$s %3$ss', 'paid-memberships-pro' ), pmpro_formatPrice( $billing_amount ), $cycle_number, $cycle_period );
												}
												?>
												<p><?php echo esc_html( $cost_text ); ?></p>
												<?php
											} else {
												?>
												<p><?php esc_html_e( 'No subscription found.', 'paid-memberships-pro' ); ?></p>
												<?php
											}
											// Check if we can refund the last order for this level.
											$last_order = new MemberOrder();
											$last_order->getLastMemberOrder( $user->ID, array( 'success', 'refunded' ), $level->id );
										} else {
											// If the user doesn't have this level but has a subscription for the level, we should warn them.
											$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level->id );
											if ( ! empty( $subscriptions ) ) {
												?>
												<div class="pmpro_message pmpro_error">
														<p>
															<?php
															esc_html_e( 'The user has a subscription for this level, but does not have a membership for this level. Old payment subscriptions should be cancelled using the Member History tool below.', 'paid-memberships-pro' );
															?>
														</p>
													</div>
												<?php
											}
										}
										?>
									</td>
								</tr>
								<tr id="pmpro-level-<?php echo esc_attr( $level->id ); ?>-edit" style="display: none;">
									<td colspan="3">
											.. actions slide out here.
									</td>
								</tr>
								<?php								
							}
						?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="3"><a class="button-secondary pmpro-has-icon pmpro-has-icon-plus pmpro-member-add-level" href="#"><?php esc_html_e( 'Add Membership', 'paid-memberships-pro' ); ?></a></td>
							</tr>
							<tr id="pmpro-level-new" style="display: none;">
								<td colspan="3">
									.. add new level action slides out here.
								</td>
							</tr>
						</tfoot>
					</table>
				<?php }
			}
		}
	?>
	<script>
		// Row actions to change or cancel membership.
		jQuery('a.pmpro-member-edit-level').on('click', function (event) {
			event.preventDefault();
			jQuery(this).closest('tr').next('tr').show();
			jQuery(this).closest('tr').hide();
		});

		// Button to change membership in "one per" groups.
		jQuery('a.pmpro-member-change-level').on('click', function (event) {
			event.preventDefault();
			jQuery(this).closest('tr').next('tr').show();
			jQuery(this).closest('table').find('thead').hide();
			jQuery(this).closest('table').find('tbody').hide();
			jQuery(this).closest('tr').hide();
		});

		// Button to add membership in "multiple" groups.
		jQuery('a.pmpro-member-add-level').on('click', function (event) {
			event.preventDefault();
			jQuery(this).closest('tr').next('tr').show();
			jQuery(this).closest('tr').hide();
		});
	</script>
	<?php
		// Show all membership history for user.
		$levelshistory = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = %s ORDER BY id DESC", $user->ID ) );

		// Build the selectors for the membership levels history list based on history count.
		$levelshistory_classes = array();
		if ( ! empty( $levelshistory ) && count( $levelshistory ) > 10 ) {
			$levelshistory_classes[] = "pmpro_scrollable";
		}
		$levelshistory_class = implode( ' ', array_unique( $levelshistory_classes ) );
	?>
	<h3><?php esc_html_e( 'Membership History', 'paid-memberships-pro' ); ?></h3>
	<div id="member-history-memberships" class="<?php echo esc_attr( $levelshistory_class ); ?>">
	<?php if ( $levelshistory ) { ?>
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
					<td><?php if ( ! empty( $level ) ) { echo $level->id; } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
					<td><?php if ( ! empty( $level ) ) { echo $level->name; } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
					<td><?php echo ( $levelhistory->startdate === '0000-00-00 00:00:00' ? __('N/A', 'paid-memberships-pro') : date_i18n( get_option( 'date_format' ), strtotime( $levelhistory->startdate ) ) ); ?></td>
					<td><?php echo date_i18n( get_option( 'date_format'), strtotime( $levelhistory->modified ) ); ?></td>
					<td><?php echo esc_html( $levelhistory->enddate ); ?></td>
					<td><?php echo pmpro_getLevelCost( $levelhistory, true, true ); ?></td>
					<td>
						<?php 
							if ( empty( $levelhistory->status ) ) {
								echo '-';
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
		<?php } else { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'No membership history found.', 'paid-memberships-pro'); ?></td>
					</tr>
				</tbody>
			</table>
		<?php } ?>
	</div> <!-- end #member-history-memberships -->
	<?php		
	}
}