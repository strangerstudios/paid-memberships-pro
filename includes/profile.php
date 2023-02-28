<?php
/**
 * Add the "membership level" field to the edit user/profile page,
 * along with other membership-related fields.
 */
function pmpro_membership_level_profile_fields($user)
{
	global $current_user;

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	global $wpdb;
	$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);

	$groups = pmpro_get_level_groups_in_order();
	if( empty ( $groups ) ) {
		return '';
	}

	// Get all membership levels for this user.
	$user_levels = pmpro_getMembershipLevelsForUser($user->ID);

	?>
	<h3><?php esc_html_e("Membership Levels", 'paid-memberships-pro' ); ?></h3>
	<table class="form-table">
	<?php
		$show_membership_level = true;
		$show_membership_level = apply_filters("pmpro_profile_show_membership_level", $show_membership_level, $user);
		if($show_membership_level) {
			foreach ( $groups as $group ) {
				// Get all the levels in this group.
				$levels_in_group = pmpro_get_levels_for_group( $group->id );
				if ( empty( $levels_in_group ) ) {
					continue;
				}

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

				// Set up the <tr> for this group.
				?>
				<tr>
					<th><?php echo esc_html( $group->name ); ?></th>
					<td>
						<?php
						// We are going to show different UIs depending on if users can have multiple levels from the group.
						if  ( empty( $group->allow_multiple_selections ) ) {
							// If the user somehow has multiple levels from this group, show a warning that non-selected levels will be removed on save.
							if ( count( $user_level_ids_in_group ) > 1 ) {
								?>
								<div class="pmpro_admin">
									<p class="pmpro_message pmpro_error">
										<?php
										esc_html_e( 'The user has multiple levels from this group. Saving this profile will remove all levels besides for the one selected below. The user\'s current levels from this group are:', 'paid-memberships-pro' );
										echo ' ' . esc_html( implode( ', ', wp_list_pluck( $user_levels_in_group, 'name' ) ) );
										?>
									</p>
									<br>
								</div>
								<?php
							}
							
							// Users can only have one level from this group.
							$shown_level = null;
							?>
							<select name="pmpro_group_level_select_<?php echo esc_attr( $group->id ) ?>" class="pmpro_group_level_select">
								<option value="0"><?php esc_html_e( 'None', 'paid-memberships-pro' ); ?></option>
								<?php
								foreach ( $levels_in_group as $level ) {
									if ( in_array( $level->id, $user_level_ids_in_group ) && empty( $shown_level ) ) {
										$shown_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $level->id );
									}
									?>
									<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( ! empty( $shown_level ) && $level->id == $shown_level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
									<?php
								}
								?>
							</select>
							<?php
							// Show checkbox to set whether the membership expires if a level is selected.
							$current_level_expires = false;
							$enddate_to_show = date( 'Y-m-d H:i', strtotime( '+1 year' ) );
							$shown_level_name_prefix = 'pmpro_membership_levels[]';
							if ( ! empty( $shown_level ) ) {
								$current_level_expires = ! empty( $shown_level->enddate );
								$enddate_to_show = ! empty( $shown_level->enddate ) ? date( 'Y-m-d H:i', $shown_level->enddate ) : $enddate_to_show;
								$shown_level_name_prefix = 'pmpro_membership_levels[' . $shown_level->id . ']';
							}
							?>
							<p <?php if ( empty( $shown_level ) ) { echo 'style="display: none"'; } ?>>
								<input type="checkbox" name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[expires]" value="1" class="pmpro_expires_checkbox" <?php checked( $current_level_expires ); ?> />
								<?php esc_html_e( 'Expires', 'paid-memberships-pro' ); ?>
								<input type="datetime-local" name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[expiration]" value="<?php echo esc_attr( $enddate_to_show ); ?>" <?php if ( ! $current_level_expires ) { echo 'style="display: none"'; } ?>>
							</p>
							<?php
							// If we have a shown level, we want to show some subscription data.
							if ( ! empty( $shown_level ) ) {
								$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $shown_level->id );
								if ( ! empty( $subscriptions ) ) {
									$subscription = $subscriptions[0];
									?>
									<p><?php echo esc_html__( 'Subscription', 'paid-memberships-pro' ) . ': ' . esc_html( $subscription->get_cost_text() ); ?></p>
									<?php
									// If the user has more than 1 subscription, show a warning message.
									if ( count( $subscriptions ) > 1 ) {
										?>
										<div class="pmpro_admin">
											<p class="pmpro_message pmpro_error">
												<?php
												esc_html_e( 'The user has multiple active subscriptions for this level. Old payment subscriptions should be cancelled using the Member History tool below.', 'paid-memberships-pro' );
												?>
											</p>
											<br>
										</div>
										<?php
									}
									// If the user changes levels, we are going to want to show a cancel subscription dropdown.
									?>
									<label for="<?php echo esc_attr( $shown_level_name_prefix ); ?>[subscription_action]" style="display: none">
										<select name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[subscription_action]">
											<option value="cancel"><?php esc_html_e( 'Cancel payment subscription (Reccomended)', 'paid-memberships-pro' ); ?></option>
											<option value="keep"><?php esc_html_e( 'Keep subscription active', 'paid-memberships-pro' ); ?></option>
										</select>
									</label>
									<br/>
									<?php
								}

								// Check if we can refund the last order for this level.
								$last_order = new MemberOrder();
								$last_order->getLastMemberOrder( $user->ID, array( 'success', 'refunded' ), $shown_level->id );
								if ( pmpro_allowed_refunds( $last_order ) ) {
									?>
									<label for="<?php echo esc_attr( $shown_level_name_prefix ); ?>[refund]" style="display: none">
										<input type="checkbox" name="<?php echo esc_attr( $shown_level_name_prefix ); ?>[refund]" value="1" />
										<?php printf( esc_html( 'Refund the last payment (%s).', 'paid-memberships-pro' ), pmpro_formatPrice( $last_order->total ) ); ?>
									</label>
									<?php
								}
							}
							// Loop through all levels again and create hidden fields for each.
							foreach ( $levels_in_group as $level ) {
								$name_prefix = 'pmpro_membership_levels[' . $level->id . ']';
								?>
								<input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[id]" value="<?php echo esc_attr( $level->id ); ?>" />
								<input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[has_level]" class="pmpro_has_level_hidden" value="<?php echo ( ! empty( $shown_level ) && $shown_level->id == $level->id ) ? 1 : 0; ?>" />
								<?php
								// Let's also check if the user has an active subscription for this level. If they do and it's not the shown level, we'll show a warning message.
								$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level->id );
								if ( ! empty( $subscriptions ) && ( empty( $shown_level ) || $shown_level->id != $level->id ) ) {
									?>
									<div class="pmpro_admin">
										<p class="pmpro_message pmpro_error">
											<?php
											printf( esc_html__( 'The user has an active subscription for the %s level. The subscription should be cancelled using the Member History tool below.', 'paid-memberships-pro' ), esc_html( $level->name ) );
											?>
										</p>
										<br>
									</div>
									<?php
								}
							}
						} else {
							// Users can have multiple levels from this group.
							?>
							<table>
								<thead>
									<tr>
										<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
										<th><?php esc_html_e( 'Expiration', 'paid-memberships-pro' ); ?></th>
										<th><?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?></th>
									</tr>
								</thead>
								<?php
								foreach ( $levels_in_group as $level ) {
									$has_level = in_array( $level->id, $user_level_ids_in_group );
									$name_prefix = 'pmpro_membership_levels[' . $level->id . ']';
									?>
									<tr>
										<td>
											<label for="<?php echo esc_attr( $name_prefix ); ?>[has_level]">
												<input type="checkbox" name="<?php echo esc_attr( $name_prefix ) ?>[has_level]" id="pmpro_membership_level_checkbox_<?php echo esc_attr( $level->id ); ?>" class="pmpro_has_level_checkbox" value="1" <?php checked( $has_level ); ?> />
												<input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[id]" value="<?php echo esc_attr( $level->id ); ?>" />
												<?php echo esc_html( $level->name ); ?>
											</label>
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
											?>
											<input type="checkbox" name="<?php echo esc_attr( $name_prefix ) ?>[expires]" class="pmpro_expires_checkbox" value="1" <?php checked( $has_enddate ); ?> <?php if ( ! $has_level ) { echo 'style="display: none"'; } ?> />
											<input type="datetime-local" name="<?php echo esc_attr( $name_prefix ) ?>[expiration]" value="<?php echo esc_attr( $enddate_to_show ); ?>" <?php if ( ! $has_enddate ) { echo 'style="display: none"'; } ?>>
										</td>
										<td class="pmpro_levels_subscription_data">
											<?php
											// If the user has  this level and they have a subscription for the level, let's show the subscription amount.
											if  ( $has_level  ) {
												$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level->id );
												if ( ! empty( $subscriptions ) ) {
													// If the user has more than 1 subscription, show a warning message.
													if ( count( $subscriptions ) > 1 ) {
														?>
														<div class="pmpro_admin">
															<p class="pmpro_message pmpro_error">
																<?php
																esc_html_e( 'The user has multiple active subscriptions for this level. Old payment subscriptions should be cancelled using the Member History tool below.', 'paid-memberships-pro' );
																?>
															</p>
															<br>
														</div>
														<?php
													}
													$subscription = $subscriptions[0];
													?>
													<p><?php echo esc_html( $subscription->get_cost_text() ); ?></p>
													<label for="<?php echo esc_attr( $name_prefix ); ?>[subscription_action]" style="display: none">
														<select name="<?php echo esc_attr( $name_prefix ); ?>[subscription_action]">
															<option value="cancel"><?php esc_html_e( 'Cancel payment subscription (Reccomended)', 'paid-memberships-pro' ); ?></option>
															<option value="keep"><?php esc_html_e( 'Keep subscription active', 'paid-memberships-pro' ); ?></option>
														</select>
													</label>
													<br/>
													<?php
												} else {
													?>
													<p><?php esc_html_e( 'No subscription found.', 'paid-memberships-pro' ); ?></p>
													<?php
												}
												// Check if we can refund the last order for this level.
												$last_order = new MemberOrder();
												$last_order->getLastMemberOrder( $user->ID, array( 'success', 'refunded' ), $level->id );
												if ( pmpro_allowed_refunds( $last_order ) ) {
													?>
													<label for="<?php echo esc_attr( $name_prefix ); ?>[refund]" style="display: none">
														<input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[refund]" value="1" />
														<?php printf( esc_html( 'Refund the last payment (%s).', 'paid-memberships-pro' ), pmpro_formatPrice( $last_order->total ) ); ?>
													</label>
													<?php
												}
											} else {
												// If the user doesn't have this level but has a subscription for the level, we should warn them.
												$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user->ID, $level->id );
												if ( ! empty( $subscriptions ) ) {
													?>
													<div class="pmpro_admin">
															<p class="pmpro_message pmpro_error">
																<?php
																esc_html_e( 'The user has a subscription for this level, but does not have a membership for this level. Old payment subscriptions should be cancelled using the Member History tool below.', 'paid-memberships-pro' );
																?>
															</p>
															<br>
														</div>
													<?php
												}
											}
											?>
										</td>
									</tr>
									<?php								
								}
								?>
							</table>
							<?php
						}
					// Close the <tr> and <td>.
					?>
					</td>
				</tr>
				<script>
					jQuery( document ).ready( function() {
						// Show/hide the expiration date field when the level select is changed.
						jQuery( '.pmpro_group_level_select' ).on( 'change', function() {
							// Set all sibling hidden fields to 0.
							jQuery( this ).siblings( '.pmpro_has_level_hidden' ).val( 0 );

							// Show/hide the "level change" options.
							jQuery( this ).siblings( 'label' ).show();
							jQuery( 'label[for="pmpro_membership_levels[' + jQuery( this ).val() + '][subscription_action]"' ).hide();
							jQuery( 'label[for="pmpro_membership_levels[' + jQuery( this ).val() + '][refund]"' ).hide();

							// Update fields for the current level.
							if ( jQuery( this ).val() > 0 ) {
								// Set the hidden field for the selected level to 1.
								jQuery( 'input[name="pmpro_membership_levels[' + jQuery( this ).val() + '][has_level]"' ).val( 1 );

								// Update the names for the expires and expiration fields.
								jQuery( this ).next( 'p' ).find( 'input[type="checkbox"]' ).attr( 'name', 'pmpro_membership_levels[' + jQuery( this ).val() + '][expires]' );
								jQuery( this ).next( 'p' ).find( 'input[type="datetime-local"]' ).attr( 'name', 'pmpro_membership_levels[' + jQuery( this ).val() + '][expiration]' );

								// Show the expiration fields.
								jQuery( this ).next( 'p' ).show();
							} else {
								// Hide the expiration fields.
								jQuery( this ).next( 'p' ).hide();
							}
						} );

						// Show/hide the expiration date field when the checkbox is clicked.
						jQuery( '.pmpro_expires_checkbox' ).on( 'change', function() {
							if ( jQuery( this ).is( ':checked' ) ) {
								jQuery( this ).next( 'input' ).show();
							} else {
								jQuery( this ).next( 'input' ).hide();
							}
						} );

						// Show/hide the expiration date and subscription fields when the "has level" checkbox is toggled.
						jQuery( '.pmpro_has_level_checkbox' ).on( 'change', function() {
							if ( jQuery( this ).is( ':checked' ) ) {
								// Show  the expiration checkbox.
								jQuery( this ).parent().parent().next().find('.pmpro_expires_checkbox').show();
								if ( jQuery( this ).parent().parent().next().find('.pmpro_expires_checkbox').is( ':checked' ) ) {
									// Show the expiration date field.
									jQuery( this ).parent().parent().next().find('.pmpro_expires_checkbox').next( 'input' ).show();
								}
								// Hide the subscription cancel fields.
								jQuery( this ).parent().parent().next().next().find('label').hide();
							} else {
								// Hide the expiration fields.
								jQuery( this ).parent().parent().next().find('input').hide();
								// Show the subscription cancel fields.
								jQuery( this ).parent().parent().next().next().find('label').show();
							}
						} );
					} );
				</script>
				<?php
			}
		}

		$tospage_id = pmpro_getOption( 'tospage' );
		$consent_log = pmpro_get_consent_log( $user->ID, true );

		if( !empty( $tospage_id ) || !empty( $consent_log ) ) {
		?>
		<tr>
			<th><label for="tos_consent_history"><?php esc_html_e("TOS Consent History", 'paid-memberships-pro' ); ?></label></th>
			<td id="tos_consent_history">
				<?php
					if ( ! empty( $consent_log ) ) {
						// Build the selectors for the invoices history list based on history count.
						$consent_log_classes = array();
						$consent_log_classes[] = "pmpro_consent_log";
						if ( count( $consent_log ) > 5 ) {
							$consent_log_classes[] = "pmpro_scrollable";
						}
						$consent_log_class = implode( ' ', array_unique( $consent_log_classes ) );
						echo '<ul class="' . esc_attr( $consent_log_class ) . '">';
						foreach( $consent_log as $entry ) {
							echo '<li>' . pmpro_consent_to_text( $entry ) . '</li>';
						}
						echo '</ul> <!-- end pmpro_consent_log -->';
					} else {
						echo __( 'N/A', 'paid-memberships-pro' );
					}
				?>
			</td>
		</tr>
		<?php
		}
		?>
	</table>
	<?php
	do_action("pmpro_after_membership_level_profile_fields", $user);
}

/*
	When applied, previous subscriptions won't be cancelled when changing membership levels.
	Use a function here instead of __return_false so we can easily turn add and remove it.
*/
function pmpro_cancel_previous_subscriptions_false()
{
	return false;
}

//save the fields on update
function pmpro_membership_level_profile_fields_update() {
	global $wpdb, $current_user;
	wp_get_current_user();

	$user_id = empty( $_REQUEST['user_id'] ) ? intval( $current_user->ID ) : intval( $_REQUEST['user_id'] );

	$membership_level_capability = apply_filters( 'pmpro_edit_member_capability', 'manage_options' );
	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	// Get the user's current membership levels.
	$user_levels = pmpro_getMembershipLevelsForUser( $user_id );
	$user_level_ids = wp_list_pluck( $user_levels, 'id' );

	// Set up some arrays to hold level changes.
	$levels_to_remove = array(); // Associative array of IDs and whether to cancel the associated subscription.
	$levels_to_add    = array(); // Array of IDs.
	$levels_to_update = array(); // Associative array of IDs and expiration dates.

	// Get the data submitted from the profile.
	if ( empty ( $_REQUEST['pmpro_membership_levels'] ) ) {
		return false;
	}
	
	// Key is level ID, value is array of user selections. Sanitize input.
	$submitted_levels = array();
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach( $_REQUEST['pmpro_membership_levels'] as $key => $values ) {
		$key = intval( $key );
		$values = array_map( 'sanitize_text_field', $values );
		$submitted_levels[$key] = $values;
	}	
	$submitted_levels = $_REQUEST['pmpro_membership_levels'];

	// Loop through the submitted levels.
	foreach ( $submitted_levels as $submitted_level => $submitted_level_data ) {
		// Get the data for the level.
		$selected   = ! empty( $submitted_level_data['has_level'] );
		$expiration = ( ! empty( $submitted_level_data['expires'] ) && ! empty( $submitted_level_data['expiration'] ) ) ? $submitted_level_data['expiration'] : null;
	
		if ( ! $selected && in_array( $submitted_level, $user_level_ids ) ) {
			// Level was removed.
			$levels_to_remove[ $submitted_level ] = empty( $submitted_level_data['subscription_action'] ) || 'cancel' === $submitted_level_data['subscription_action'];

			// Refund the order if needed.
			if ( ! empty( $submitted_level_data['refund'] ) ) {
				$last_order = new MemberOrder();
				$last_order->getLastMemberOrder( $user_id, array( 'success', 'refunded' ), $submitted_level );
				if ( ! empty( $last_order ) ) {
					pmpro_refund_order( $last_order );
				}
			}

		} elseif ( $selected && ! in_array( $submitted_level, $user_level_ids ) ) {
			// Level was added.
			$levels_to_add[] = $submitted_level;

			// We may also need to update the expiration date.
			if ( ! empty( $expiration ) ) {
				$levels_to_update[ $submitted_level ] = $expiration;
			}
		} elseif ( $selected && in_array( $submitted_level, $user_level_ids ) ) {
			// User may have changed the expiration date. Let's check.
			$current_expiration = pmpro_getMembershipLevelForUser( $user_id, $submitted_level )->enddate;

			if ( $current_expiration != strtotime( $expiration ) ) {
				// Update the expiration date.
				$levels_to_update[ $submitted_level ] = $expiration;
			}
		}
	}

	// Finally, we need to execute the level changes.
	// Remove levels.
	foreach ( $levels_to_remove as $level_to_remove => $cancel_subscription ) {
		if ( empty( $cancel_subscription ) ) {
			// Cancel the subscription.
			add_filter( 'pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false' );
		}
		pmpro_cancelMembershipLevel( $level_to_remove, $user_id, 'admin_changed' );
		remove_filter( 'pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false' );
	}

	// Add levels.
	foreach ( $levels_to_add as $level_to_add ) {
		pmpro_changeMembershipLevel( $level_to_add, $user_id, 'admin_changed' );
	}

	// Update levels.
	foreach ( $levels_to_update as $level_to_update => $expiration ) {
		if ( empty( $expiration ) ) {
			// Set level to not expire.
			$wpdb->update(
				$wpdb->pmpro_memberships_users,
				array( 'enddate' => NULL ),
				array(
					'status' => 'active',
					'membership_id' => $level_to_update,
					'user_id' => $user_id
				),
				array( NULL ),
				array( '%s', '%d', '%d' )
			);
		} else {
			// Set expiration date for level.
			$wpdb->update(
				$wpdb->pmpro_memberships_users,
				array( 'enddate' => date( 'Y-m-d H:i:s', strtotime( $expiration ) ) ),
				array(
					'status' => 'active',
					'membership_id' => $level_to_update,
					'user_id' => $user_id
				),
				array( '%s' ),
				array( '%s', '%d', '%d' )
			);
		}
	}
}
add_action( 'show_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'edit_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'personal_options_update', 'pmpro_membership_level_profile_fields_update' );
add_action( 'edit_user_profile_update', 'pmpro_membership_level_profile_fields_update' );


/**
 * Add the history view to the user profile
 *
 */
function pmpro_membership_history_profile_fields( $user ) {
	global $current_user;
	$membership_level_capability = apply_filters( 'pmpro_edit_member_capability', 'manage_options' );

	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	global $wpdb;

	//Show all invoices for user
	$invoices = $wpdb->get_results( $wpdb->prepare( "SELECT mo.*, UNIX_TIMESTAMP(mo.timestamp) as timestamp, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = %d ORDER BY mo.timestamp DESC", $user->ID ) );

	$subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_subscriptions WHERE user_id = %d ORDER BY startdate DESC", $user->ID ) );

	$levelshistory = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = %s ORDER BY id DESC", $user->ID ) );
	
	$totalvalue = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = %d AND status NOT IN('token','review','pending','error','refunded')", $user->ID ) );

	if ( $invoices || $subscriptions || $levelshistory ) { ?>
		<hr />
		<h3><?php esc_html_e( 'Member History', 'paid-memberships-pro' ); ?></h3>
		<p><strong><?php esc_html_e( 'Total Paid', 'paid-memberships-pro' ); ?></strong> <?php echo pmpro_formatPrice( $totalvalue ); ?></p>
		<ul id="member-history-filters" class="subsubsub">
			<li id="member-history-filters-orders"><a href="javascript:void(0);" class="current orders tab"><?php esc_html_e( 'Order History', 'paid-memberships-pro' ); ?></a> <span>(<?php echo count( $invoices ); ?>)</span></li>
			<li id="member-history-filters-subscriptions">| <a href="javascript:void(0);" class="tab"><?php esc_html_e( 'Subscription History', 'paid-memberships-pro' ); ?></a> <span>(<?php echo count( $subscriptions ); ?>)</span></li>
			<li id="member-history-filters-memberships">| <a href="javascript:void(0);" class="tab"><?php esc_html_e( 'Membership Levels History', 'paid-memberships-pro' ); ?></a> <span>(<?php echo count( $levelshistory ); ?>)</span></li>
		</ul>
		<br class="clear" />
		<?php
			// Build the selectors for the invoices history list based on history count.
			$invoices_classes = array();
			$invoices_classes[] = "widgets-holder-wrap";
			if ( ! empty( $invoices ) && count( $invoices ) > 2 ) {
				$invoices_classes[] = "pmpro_scrollable";
			}
			$invoice_class = implode( ' ', array_unique( $invoices_classes ) );
		?>
		<div id="member-history-orders" class="<?php echo esc_attr( $invoice_class ); ?>">
		<?php if ( $invoices ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					<?php do_action('pmpromh_orders_extra_cols_header');?>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $invoices as $invoice ) { 
					$level = pmpro_getLevel( $invoice->membership_id );
					?>
					<tr>
						<td>
							<?php
								echo esc_html( sprintf(
									// translators: %1$s is the date and %2$s is the time.
									__( '%1$s at %2$s', 'paid-memberships-pro' ),
									esc_html( date_i18n( get_option( 'date_format' ), $invoice->timestamp ) ),
									esc_html( date_i18n( get_option( 'time_format' ), $invoice->timestamp ) )
								) );
							?>
						</td>
						<td class="order_code column-order_code has-row-actions">
							<strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $invoice->code ); ?></a></strong>
							<div class="row-actions">
								<span class="id">
									<?php echo sprintf(
										// translators: %s is the Order ID.
										__( 'ID: %s', 'paid-memberships-pro' ),
										esc_attr( $invoice->id )
									); ?>
								</span> |
								<span class="edit">
									<a title="<?php esc_attr_e( 'Edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
								</span> |
								<span class="print">
									<a target="_blank" title="<?php esc_attr_e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $invoice->id ), admin_url('admin-ajax.php' ) ) ); ?>"><?php esc_html_e( 'Print', 'paid-memberships-pro' ); ?></a>
								</span>
								<?php if ( function_exists( 'pmpro_add_email_order_modal' ) ) { ?>
									 |
									<span class="email">
										<a title="<?php esc_attr_e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link" data-order="<?php echo esc_attr( $invoice->id ); ?>"><?php esc_html_e( 'Email', 'paid-memberships-pro' ); ?></a>
									</span>
								<?php } ?>
							</div> <!-- end .row-actions -->
						</td>
						<td>
							<?php
								if ( ! empty( $level ) ) {
									echo esc_html( $level->name );
								} elseif ( $invoice->membership_id > 0 ) { ?>
									[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
								<?php } else {
									esc_html_e( '&#8212;', 'paid-memberships-pro' );
								}
							?>
						</td>
						<td><?php echo pmpro_formatPrice( $invoice->total ); ?></td>
						<td><?php 
							if ( empty( $invoice->code_id ) ) {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							} else {
								$discountQuery = $wpdb->prepare( "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = %d LIMIT 1", $invoice->code_id );
								$discount_code = $wpdb->get_row( $discountQuery );
								echo '<a href="admin.php?page=pmpro-discountcodes&edit=' . esc_attr( $invoice->code_id ). '">'. esc_attr( $discount_code->code ) . '</a>';
							}
						?></td>
						<td>
							<?php
								if ( empty( $invoice->status ) ) {
									esc_html_e( '&#8212;', 'paid-memberships-pro' );
								} else { ?>
									<span class="pmpro_order-status pmpro_order-status-<?php esc_attr_e( $invoice->status ); ?>">
										<?php if ( in_array( $invoice->status, array( 'success', 'cancelled' ) ) ) {
											esc_html_e( 'Paid', 'paid-memberships-pro' );
										} else {
											esc_html_e( ucwords( $invoice->status ) );
										} ?>
									</span>
									<?php
								}
							?>
						</td>
						<?php do_action( 'pmpromh_orders_extra_cols_body', $invoice ); ?>
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
                        <td><?php esc_html_e( 'No membership orders found.', 'paid-memberships-pro' ); ?></td>
                    </tr>
                </tbody>
            </table>
		<?php } ?>
		</div> <!-- end #member-history-invoices -->
		<?php
			// Build the selectors for the subscription history list based on history count.
			$subscriptions_classes = array();
			$subscriptions_classes[] = "widgets-holder-wrap";
			if ( ! empty( $subscriptions ) && count( $subscriptions ) > 2 ) {
				$subscriptions_classes[] = "pmpro_scrollable";
			}
			$subscriptions_class = implode( ' ', array_unique( $subscriptions_classes ) );
		?>
		<div id="member-history-subscriptions" class="<?php echo esc_attr( $subscriptions_class ); ?>" style="display: none;">
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
			// Build the selectors for the membership levels history list based on history count.
			$levelshistory_classes = array();
			$levelshistory_classes[] = "widgets-holder-wrap";
			if ( ! empty( $levelshistory ) && count( $levelshistory ) > 4 ) {
				$levelshistory_classes[] = "pmpro_scrollable";
			}
			$levelshistory_class = implode( ' ', array_unique( $levelshistory_classes ) );
		?>
		<div id="member-history-memberships" class="<?php echo esc_attr( $levelshistory_class ); ?>" style="display: none;">
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
		<script>
			//tabs
			jQuery(document).ready(function() {
				jQuery('#member-history-filters a.tab').click(function() {
					//which tab?
					var tab = jQuery(this).parent().attr('id').replace('member-history-filters-', '');
					
					//un select tabs
					jQuery('#member-history-filters a.tab').removeClass('current');
					
					//select this tab
					jQuery('#member-history-filters-'+tab+' a').addClass('current');
					
					//show orders?
					if(tab == 'orders')
					{
						jQuery('#member-history-memberships').hide();
						jQuery('#member-history-subscriptions').hide();
						jQuery('#member-history-orders').show();
					}
					else if (tab == 'subscriptions' ) {
						jQuery('#member-history-memberships').hide();
						jQuery('#member-history-subscriptions').show();
						jQuery('#member-history-orders').hide();
					}
					else
					{
						jQuery('div#member-history-orders').hide();
						jQuery('#member-history-subscriptions').hide();
						jQuery('#member-history-memberships').show();

						<?php if ( count( $levelshistory ) > 5 ) { ?>
						jQuery('#member-history-memberships').css({'height': '150px', 'overflow': 'auto' });
						<?php } ?>
					}
				});
			});
		</script>
		<?php
	}	
}
add_action('edit_user_profile', 'pmpro_membership_history_profile_fields');
add_action('show_user_profile', 'pmpro_membership_history_profile_fields');


/**
 * Allow orders to be emailed from the member history section on user profile.
 *
 */
function pmpro_membership_history_email_modal() {
	$screen = get_current_screen();
	if ( $screen->base == 'user-edit' || $screen->base == 'profile' ) {
		// Require the core Paid Memberships Pro Admin Functions.
		if ( defined( 'PMPRO_DIR' ) ) {
			require_once( PMPRO_DIR . '/adminpages/functions.php' );
		}

		// Load the email order modal.
		if ( function_exists( 'pmpro_add_email_order_modal' ) ) {
			pmpro_add_email_order_modal();
		}
	}
}
add_action( 'in_admin_header', 'pmpro_membership_history_email_modal' );



/**
 * Display a frontend Member Profile Edit form and allow user to edit specific fields.
 *
 * @since 2.3
 */
function pmpro_member_profile_edit_form() {
	global $current_user;

	if ( ! is_user_logged_in() ) {
		echo '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_alert', 'pmpro_alert' ) . '"><a href="' . esc_url( pmpro_login_url() ) . '">' . esc_html__( 'Log in to edit your profile.', 'paid-memberships-pro' ) . '</a></div>';
		return;
	}

	// Saving profile updates.
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'update-profile' && $current_user->ID == $_POST['user_id'] && wp_verify_nonce( sanitize_key( $_POST['update_user_nonce'] ), 'update-user_' . $current_user->ID ) ) {
		$update           = true;
		$user     		  = new stdClass;
		$user->ID         = intval( $_POST[ 'user_id' ] );
		do_action( 'pmpro_personal_options_update', $user->ID );
	} else {
		$update = false;
	}

	if ( $update ) {

		$errors = array();

		// Get all values from the $_POST, sanitize them, and build the $user object.
		if ( isset( $_POST['user_email'] ) ) {
			$user->user_email = sanitize_text_field( wp_unslash( $_POST['user_email'] ) );
		}
		if ( isset( $_POST['first_name'] ) ) {
			$user->first_name = sanitize_text_field( $_POST['first_name'] );
		}
		if ( isset( $_POST['last_name'] ) ) {
			$user->last_name = sanitize_text_field( $_POST['last_name'] );
		}
		if ( isset( $_POST['display_name'] ) ) {
			$user->display_name = sanitize_text_field( $_POST['display_name'] );
			$user->nickname = $user->display_name;
		}

		// Validate display name.
		if ( empty( $user->display_name ) ) {
			$errors[] = __( 'Please enter a display name.', 'paid-memberships-pro' );
		}

		// Don't allow admins to change their email address.
		if ( current_user_can( 'manage_options' ) ) {
			$user->user_email = $current_user->user_email;
		}

		// Validate email address.
		if ( empty( $user->user_email ) ) {
			$errors[] = __( 'Please enter an email address.', 'paid-memberships-pro' );
		} elseif ( ! is_email( $user->user_email ) ) {
			$errors[] = __( 'The email address isn&#8217;t correct.', 'paid-memberships-pro' );
		} else {
			$owner_id = email_exists( $user->user_email );
			if ( $owner_id && ( ! $update || ( $owner_id != $user->ID ) ) ) {
				$errors[] = __( 'This email is already registered, please choose another one.', 'paid-memberships-pro' );
			}
		}

		/**
		 * Fires before member profile update errors are returned.
		 *
		 * @param $errors WP_Error object (passed by reference).
		 * @param $update Whether this is a user update.
		 * @param $user   User object (passed by reference).
		 */
		do_action_ref_array( 'pmpro_user_profile_update_errors', array( &$errors, $update, &$user ) );

		// Show error messages.
		if ( ! empty( $errors ) ) { ?>
			<div class="<?php echo pmpro_get_element_class( 'pmpro_message pmpro_error', 'pmpro_error' ); ?>">
				<?php
					foreach ( $errors as $key => $value ) {
						echo '<p>' . $value . '</p>';
					}
				?>
			</div>
		<?php } else {
			// Save updated profile fields.
			wp_update_user( $user );
			?>
			<div class="<?php echo pmpro_get_element_class( 'pmpro_message pmpro_success', 'pmpro_success' ); ?>">
				<?php _e( 'Your profile has been updated.', 'paid-memberships-pro' ); ?>
			</div>
		<?php }
	} else {
		// Doing this so fields are set to new values after being submitted.
		$user = $current_user;
	}
	?>
	<div class="<?php echo pmpro_get_element_class( 'pmpro_member_profile_edit_wrap' ); ?>">
		<form id="member-profile-edit" class="<?php echo pmpro_get_element_class( 'pmpro_form' ); ?>" action="" method="post"
			<?php
				/**
				 * Fires inside the member-profile-edit form tag in the pmpro_member_profile_edit_form function.
				 *
				 * @since 2.4.1
				 */
				do_action( 'pmpro_member_profile_edit_form_tag' );
			?>
		>

			<?php wp_nonce_field( 'update-user_' . $current_user->ID, 'update_user_nonce' ); ?>

			<?php
			$user_fields = apply_filters( 'pmpro_member_profile_edit_user_object_fields',
				array(
					'first_name'	=> __( 'First Name', 'paid-memberships-pro' ),
					'last_name'		=> __( 'Last Name', 'paid-memberships-pro' ),
					'display_name'	=> __( 'Display name publicly as', 'paid-memberships-pro' ),
					'user_email'	=> __( 'Email', 'paid-memberships-pro' ),
				)
			);
			?>

			<div class="<?php echo pmpro_get_element_class( 'pmpro_checkout_box-user' ); ?>">
				<div class="<?php echo pmpro_get_element_class( 'pmpro_member_profile_edit-fields' ); ?>">
				<?php foreach ( $user_fields as $field_key => $label ) { ?>
					<div class="<?php echo pmpro_get_element_class( 'pmpro_member_profile_edit-field pmpro_member_profile_edit-field- ' . $field_key, 'pmpro_member_profile_edit-field- ' . $field_key ); ?>">
						<label for="<?php echo esc_attr( $field_key ); ?>"><?php esc_html_e( $label ); ?></label>
						<?php if ( current_user_can( 'manage_options' ) && $field_key === 'user_email' ) { ?>
							<input type="text" readonly="readonly" name="user_email" id="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" class="<?php echo pmpro_get_element_class( 'input', 'user_email' ); ?>" />
							<p class="<?php echo pmpro_get_element_class( 'lite' ); ?>"><?php esc_html_e( 'Site administrators must use the WordPress dashboard to update their email address.', 'paid-memberships-pro' ); ?></p>
						<?php } else { ?>
							<input type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( stripslashes( $user->{$field_key} ) ); ?>" class="<?php echo pmpro_get_element_class( 'input', $field_key ); ?>" />
						<?php } ?>
	            	</div>
				<?php } ?>
				</div> <!-- end pmpro_member_profile_edit-fields -->
			</div> <!-- end pmpro_checkout_box-user -->

			<?php
				/**
				 * Fires after the default Your Member Profile fields.
				 *
				 * @since 2.3
				 *
				 * @param WP_User $current_user The current WP_User object.
				 */
				do_action( 'pmpro_show_user_profile', $current_user );
			?>
			<input type="hidden" name="action" value="update-profile" />
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ) ; ?>" />
			<div class="<?php echo pmpro_get_element_class( 'pmpro_submit' ); ?>">
				<hr />
				<input type="submit" name="submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ); ?>" value="<?php esc_attr_e( 'Update Profile', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ); ?>" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo pmpro_url( 'account'); ?>';" />
			</div>
		</form>
	</div> <!-- end pmpro_member_profile_edit_wrap -->
	<?php
}

/**
 * Process password updates.
 * Hooks into personal_options_update.
 * Doesn't need to hook into edit_user_profile_update since
 * our change password page is only for the current user.
 *
 * @since 2.3
 */
function pmpro_change_password_process() {
	global $current_user;

	// Make sure we're on the right page.
	if ( empty( $_POST['action'] ) || $_POST['action'] != 'change-password' ) {
		return;
	}

	// Only let users change their own password.
	if ( empty( $current_user ) || empty( $_POST['user_id'] ) || $current_user->ID != $_POST['user_id'] ) {
		return;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_POST['change_password_user_nonce'] ), 'change-password-user_' . $current_user->ID ) ) {
		return;
	}

	// Get all password values from the $_POST.
	if ( ! empty( $_POST['password_current'] ) ) {
		$password_current = sanitize_text_field( $_POST['password_current'] );
	} else {
		$password_current = '';
	}
	if ( ! empty( $_POST['pass1'] ) ) {
		$pass1 = sanitize_text_field( $_POST['pass1'] );
	} else {
		$pass1 = '';
	}
	if ( ! empty( $_POST['pass2'] ) ) {
		$pass2 = sanitize_text_field( $_POST['pass2'] );
	} else {
		$pass2 = '';
	}

	// Check that all password information is correct.
	$error = false;
	if ( isset( $password_current ) && ( empty( $pass1 ) || empty( $pass2 ) ) ) {
		$error = __( 'Please complete all fields.', 'paid-memberships-pro' );
	} elseif ( ! empty( $pass1 ) && empty( $password_current ) ) {
		$error = __( 'Please enter your current password.', 'paid-memberships-pro' );
	} elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
		$error = __( 'New passwords do not match.', 'paid-memberships-pro' );
	} elseif ( ! empty( $pass1 ) && ! wp_check_password( $password_current, $current_user->user_pass, $current_user->ID ) ) {
		$error = __( 'Your current password is incorrect.', 'paid-memberships-pro' );
	}

	// Change the password.
	if ( ! empty( $pass1 ) && empty( $error ) ) {
		wp_set_password( $pass1, $current_user->ID );

		//setting some cookies
		wp_set_current_user( $current_user->ID, $current_user->user_login );
		wp_set_auth_cookie( $current_user->ID, true, apply_filters( 'pmpro_checkout_signon_secure', force_ssl_admin() ) );

		pmpro_setMessage( __( 'Your password has been updated.', 'paid-memberships-pro' ), 'pmpro_success' );
	} else {
		pmpro_setMessage( $error, 'pmpro_error' );
	}
}
add_action( 'init', 'pmpro_change_password_process' );


/**
 * Display a frontend Change Password form and allow user to edit their password when logged in.
 *
 * @since 2.3
 */
function pmpro_change_password_form() {
	global $current_user, $pmpro_msg, $pmpro_msgt;
	?>
	<h2><?php esc_html_e( 'Change Password', 'paid-memberships-pro' ); ?></h2>
	<?php if ( ! empty( $pmpro_msg ) ) { ?>
		<div class="<?php echo pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ); ?>">
			<?php echo esc_html( $pmpro_msg ); ?>
		</div>
	<?php } ?>
	<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password_wrap' ); ?>">
		<form id="change-password" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'change-password' ); ?>" action="" method="post">

			<?php wp_nonce_field( 'change-password-user_' . $current_user->ID, 'change_password_user_nonce' ); ?>

			<div class="<?php echo pmpro_get_element_class( 'pmpro_checkout_box-password' ); ?>">
				<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-fields' ); ?>">
					<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-field pmpro_change_password-field-password_current', 'pmpro_change_password-field-password_current' ); ?>">
						<label for="password_current"><?php esc_html_e( 'Current Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="password_current" id="password_current" value="" class="<?php echo pmpro_get_element_class( 'input', 'password_current' ); ?>" />
						<span class="<?php echo pmpro_get_element_class( 'pmpro_asterisk' ); ?>"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_change_password-field-password_current -->
					<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-field pmpro_change_password-field-pass1', 'pmpro_change_password-field-pass1' ); ?>">
						<label for="pass1"><?php esc_html_e( 'New Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="pass1" id="pass1" value="" class="<?php echo pmpro_get_element_class( 'input pass1', 'pass1' ); ?>" autocomplete="off" />
						<span class="<?php echo pmpro_get_element_class( 'pmpro_asterisk' ); ?>"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
						<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php esc_html_e( 'Strength Indicator', 'paid-memberships-pro' ); ?></div>
						<p class="<?php echo pmpro_get_element_class( 'lite' ); ?>"><?php echo wp_get_password_hint(); ?></p>
					</div> <!-- end pmpro_change_password-field-pass1 -->
					<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-field pmpro_change_password-field-pass2', 'pmpro_change_password-field-pass2' ); ?>">
						<label for="pass2"><?php esc_html_e( 'Confirm New Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="pass2" id="pass2" value="" class="<?php echo pmpro_get_element_class( 'input', 'pass2' ); ?>" autocomplete="off" />
						<span class="<?php echo pmpro_get_element_class( 'pmpro_asterisk' ); ?>"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_change_password-field-pass2 -->
				</div> <!-- end pmpro_change_password-fields -->
			</div> <!-- end pmpro_checkout_box-password -->

			<input type="hidden" name="action" value="change-password" />
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ); ?>" />
			<div class="<?php echo pmpro_get_element_class( 'pmpro_submit' ); ?>">
				<hr />
				<input type="submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ); ?>" value="<?php esc_attr_e('Change Password', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ); ?>" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo esc_url( pmpro_url( 'account') ); ?>';" />
			</div>
		</form>
	</div> <!-- end pmpro_change_password_wrap -->
	<?php
}
