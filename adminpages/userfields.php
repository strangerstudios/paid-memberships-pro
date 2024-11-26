<?php
	global $pmpro_msg, $pmpro_msgt;
	
	// Only admins can get this.
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_userfields' ) ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Process form submissions.

	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : false;
	if ( ! empty( $action ) && ( empty( sanitize_key( $_REQUEST['pmpro_userfields_nonce'] ) ) || ! check_admin_referer( $action, 'pmpro_userfields_nonce' ) ) ) {
		pmpro_setMessage( __( 'Are you sure you want to do that? Try again.', 'paid-memberships-pro' ), -1 );
		$action = false;
	} elseif ( ! empty( $_REQUEST['success_message'] ) ) {
		pmpro_setMessage( sanitize_text_field( $_REQUEST['success_message'] ), 1 );
	}
	switch ( $action ) {
		case 'save_field':
			include_once( PMPRO_DIR . '/adminpages/user-fields/save-field.php' );
			break;
		case 'delete_field':
			include_once( PMPRO_DIR . '/adminpages/user-fields/delete-field.php' );
			break;
		case 'save_group':
			include_once( PMPRO_DIR . '/adminpages/user-fields/save-group.php' );
			break;
		case 'delete_group':
			include_once( PMPRO_DIR . '/adminpages/user-fields/delete-group.php' );
			break;
	}


	// Show header.
	require_once(dirname(__FILE__) . "/admin_header.php");
	
	// Show page contents.
	if ( isset( $_REQUEST['edit'] ) ) {
		// Editing a field.
		$edit = sanitize_text_field( $_REQUEST['edit'] );
		require_once( PMPRO_DIR . '/adminpages/user-fields/edit-field.php' );
	} elseif ( isset( $_REQUEST['edit_group'] ) ) {
		// Editing a group.
		$edit_group = sanitize_text_field( $_REQUEST['edit_group'] );
		require_once( PMPRO_DIR . '/adminpages/user-fields/edit-group.php' );
	} else {
		// Showing the fields list.
		$groups = PMPro_Field_Group::get_all();

		// Order the groups based on how they would show at checkout.
		$ordered_groups = array();
		$pre_checkout_field_locations = array(
			'after_username',
			'after_password',
			'after_pricing_fields',
			'after_email',
		);
		$post_checkout_field_locations = array(
			'after_billing_fields',
			'after_captcha',
			'before_submit_button',
			'after_tos_fields',
		);

		// Add all of the "pre-checkout" field groups to the ordered groups array.
		foreach ( $pre_checkout_field_locations as $location ) {
			if ( array_key_exists( $location, $groups ) ) {
				$ordered_groups[] = $groups[ $location ];
			}
		}

		// Add all of the "checkout" field groups to the ordered groups array.
		foreach ( $groups as $group ) {
			if ( ! in_array( $group->name, $pre_checkout_field_locations ) && ! in_array( $group->name, $post_checkout_field_locations ) ) {
				$ordered_groups[] = $group;
			}
		}

		// Add all of the "post-checkout" field groups to the ordered groups array.
		foreach ( $post_checkout_field_locations as $location ) {
			if ( array_key_exists( $location, $groups ) ) {
				$ordered_groups[] = $groups[ $location ];
			}
		}

		// Get lists of all groups and fields that were created via UI.
		$ui_settings = pmpro_get_user_fields_settings();
		$ui_groups   = array();
		$ui_fields   = array();
		if ( is_array( $ui_settings ) ) {
			foreach ( $ui_settings as $group_setting ) {
				$ui_groups[] = $group_setting->name;
				foreach ( $group_setting->fields as $field ) {
					$ui_fields[] = $field->name;
				}
			}
		}

		?>
		<script>
			jQuery(document).ready(function($) {

				// Return a helper with preserved width of cells
				// from http://www.foliotek.com/devblog/make-table-rows-sortable-using-jquery-ui-sortable/
				var fixHelper = function(e, ui) {
					ui.children().each(function() {
						$(this).width($(this).width());
					});
					return ui;
				};

				$("table.has-sortable-fields tbody").sortable({
					axis: "y",
					helper: fixHelper,
					placeholder: 'testclass',
					forcePlaceholderSize: true,
					update: update_level_order,
					items: "tr.sortable-field"
				});

				function update_level_order(event, ui) {
					// Create an array of the field names in the new order.
					field_order = [];
					ui.item.parent().find('tr.sortable-field').each(function() {
						field_order.push( $("td:first", this).text());
					});

					data = {
						action: 'pmpro_update_field_order',
						group: ui.item.closest('.pmpro_section').find('.pmpro-userfields-settings-group-name').val(),
						ordered_fields: field_order,
						nonce: '<?php echo esc_attr( wp_create_nonce( 'pmpro_update_field_order' ) ); ?>'
					};

					$.post(ajaxurl, data, function(response) {
					});
				}

				$('.pmpro_section-sort-button-move-up').on('click',function(){
					var current = $(this).closest('.pmpro_section');
					// Check if the group before this one is sortable.
					if ( current.prev().hasClass('pmpro_section_sortable') ) {
						current.prev().before(current);
						update_level_group_order();
					}
				});
				$('.pmpro_section-sort-button-move-down').on('click',function(){
					var current = $(this).closest('.pmpro_section');
					// Check if the group after this one is sortable.
					if ( current.next().hasClass('pmpro_section_sortable') ) {
						current.next().after(current);
						update_level_group_order();
					}
				});

				function update_level_group_order(event, ui) {
					group_order = [];
					$(".pmpro_section_sortable").each(function() {
						group_order.push( $(this).find('.pmpro-userfields-settings-group-name').val());
					});
					console.log(group_order);

					data = {
						action: 'pmpro_update_field_group_order',
						ordered_groups: group_order,
						nonce: '<?php echo esc_attr( wp_create_nonce( 'pmpro_update_field_group_order' ) ); ?>'
					};

					$.post(ajaxurl, data, function(response) {
					});
				}
			});
		</script>
		<?php


		// Check if there are multiple fields with the same name. If so, show an error.
		// TODO

		?>
		<hr class="wp-header-end">
		<?php if( count( $ordered_groups ) === 0 ) { ?>
			<div class="pmpro-new-install">
				<h2><?php esc_html_e( 'No Field Groups Found', 'paid-memberships-pro' ); ?></h2>
				<a href="#" class="button-primary"><?php esc_html_e( 'Create a Group', 'paid-memberships-pro' ); ?></a>
			</div> <!-- end pmpro-new-install -->
		<?php } else { ?>
			<h1 class="wp-heading-inline"><?php esc_html_e( 'User Fields', 'paid-memberships-pro' ); ?></h1>

			<?php
				// Build the page action links to return.
				$pmpro_membershiplevels_page_action_links = array();

				// Add New Group link
				$pmpro_membershiplevels_page_action_links['add-new-group'] = array(
					'url' => add_query_arg( array( 'edit_group' => '-1' ), admin_url( 'admin.php?page=pmpro-userfields' ) ),
					'name' => __( 'Add New Group', 'paid-memberships-pro' ),
					'icon' => 'plus'
				);

				// Display the links.
				foreach ( $pmpro_membershiplevels_page_action_links as $pmpro_membershiplevels_page_action_link ) {
					
					// If the value is not an array, it is not in the correct format. Continue.
					if ( ! is_array( $pmpro_membershiplevels_page_action_link ) ) {
						continue;
					}

					// Figure out CSS classes for the links.
					$classes = array();
					$classes[] = 'page-title-action';
					if ( ! empty( $pmpro_membershiplevels_page_action_link['icon'] ) ) {
						$classes[] = 'pmpro-has-icon';
						$classes[] = 'pmpro-has-icon-' . esc_attr( $pmpro_membershiplevels_page_action_link['icon'] );
					}
					if ( ! empty( $pmpro_membershiplevels_page_action_link['classes'] ) ) {
						$classes[] = $pmpro_membershiplevels_page_action_link['classes'];
					}
					$class = implode( ' ', array_unique( $classes ) );
					?>
					<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo $pmpro_membershiplevels_page_action_link['url']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo esc_html( $pmpro_membershiplevels_page_action_link['name'] ); ?></a>
					<?php
				}
			?>
			<p><?php esc_html_e('Drag and drop fields within the group to reorder them. Reorder groups using the up/down arrows.', 'paid-memberships-pro' ); ?></p>
			<?php
			// Show the settings page message.
			if (!empty($pmpro_msg)) { ?>
				<div class="inline notice notice-large <?php echo $pmpro_msgt > 0 ? 'notice-success' : 'notice-error'; ?>">
					<p><?php echo wp_kses_post( $pmpro_msg ); ?></p>
				</div>
			<?php }
			?>
			<div id="pmpro-userfields-groups">
				<?php
				foreach ( $ordered_groups as $group ) {
					$group_can_be_moved = ! in_array( $group->name, $pre_checkout_field_locations ) && ! in_array( $group->name, $post_checkout_field_locations ) && in_array( $group->name, $ui_groups );
					?>
					<div id="pmpro-userfields-settings-group-div-<?php echo esc_attr( $group->name ); ?>" class="pmpro_section <?php echo $group_can_be_moved ? 'pmpro_section_sortable' : ''; ?>" data-visibility="shown" data-activated="true">
						<div class="pmpro_section_toggle">
							<?php
							// Enable moving groups for those that allow it.
							if ( $group_can_be_moved ) {
								?>
									<div class="pmpro_section-sort">
									<button type="button" aria-disabled="false" class="pmpro_section-sort-button pmpro_section-sort-button-move-up" aria-label="<?php esc_attr_e( 'Move up', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-up-alt2"></span>
									</button>
									<span class="pmpro_section-sort-button-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

									<button type="button" aria-disabled="false" class="pmpro_section-sort-button pmpro_section-sort-button-move-down" aria-label="<?php esc_attr_e( 'Move down', 'paid-memberships-pro' ); ?>">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
									<span id="pmpro_section-sort-button-description-2" class="pmpro_section-sort-button-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
								</div> <!-- end pmpro_section-sort -->
								<?php
							} else {
								// Show a lock icon for groups that are not sortable.
								?>
								<div class="pmpro_section-sort">
									<span class="dashicons dashicons-lock"></span>
								</div>
								<?php
							}
							?>
							<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
								<span class="dashicons dashicons-arrow-up-alt2"></span>
								<input type="hidden" class="pmpro-userfields-settings-group-name" value="<?php echo esc_attr( $group->name ); ?>" />
								<?php echo esc_html( $group->label ) . '<small>'. sprintf( esc_html__( 'Name: %s', 'paid-memberships-pro' ), esc_html( $group->name ) ) . '</small>'; ?>
							</button>
						</div>
						<div class="pmpro_section_inside">
							<?php
							// Check if we have settings for this group.
							if ( ! empty( $group->description ) ) {
								?>
								<p><?php echo wp_kses_post( $group->description ); ?></p>
								<?php
							}
							if ( in_array( $group->name, $ui_groups ) ) {
								?>
								<p class="description"><?php esc_html_e( 'You can edit if fields in this group are shown at checkout and their level restrictions by clicking the "Edit Group" button.', 'paid-memberships-pro' ); ?></p>
								<?php
							} else {
								?>
								<p class="description"><?php esc_html_e( 'This group was added via custom code.', 'paid-memberships-pro' ); ?></p>
								<?php
							}

							// Get the fields for this group.
							$group_fields = $group->get_fields();
							$has_sortable_fields = count( array_intersect( $ui_fields, wp_list_pluck( $group_fields, 'name' ) ) ) > 1;
							?>
							<table class="widefat <?php if ( $has_sortable_fields  ) { ?> has-sortable-fields<?php } ?>">
								<thead>
									<tr>
										<th><?php esc_html_e('Name', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Label', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Show in Profile?', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Show at Checkout?', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Required at Checkout?', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Level Restrictions', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Preview', 'paid-memberships-pro' );?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									if ( empty( $group_fields ) ) {
										?>
										<tr>
											<td colspan="3">
												<?php
												esc_html_e( 'No user fields found.', 'paid-memberships-pro' );
												?>
											</td>
										</tr>
										<?php
									}
									foreach ( $group_fields as $field ) {
									?>
									<tr class="<?php if ( in_array( $field->name, $ui_fields ) ) { ?>sortable-field<?php } ?>">
										<td><?php echo esc_html( $field->name );?></td>
										<?php
										if ( in_array( $field->name, $ui_fields ) ) {
											?>
											<td class="has-row-actions">
												<span class="field-label"><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-userfields', 'edit' => $field->name ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $field->label ); ?></a></span>
												<div class="row-actions">
													<?php
													$delete_text = esc_html(
														sprintf(
															// translators: %s is the Level Name.
															__( "Are you sure you want to the %s field?", 'paid-memberships-pro' ),
															$field->label
														)
													);

													$delete_nonce_url = wp_nonce_url(
														add_query_arg(
															[
																'page'   => 'pmpro-userfields',
																'action' => 'delete_field',
																'delete_name' => $field->name,
															],
															admin_url( 'admin.php' )
														),
														'delete_field',
														'pmpro_userfields_nonce'
													);

													$actions = [
														'edit'   => sprintf(
															'<a title="%1$s" href="%2$s">%3$s</a>',
															esc_attr__( 'Edit', 'paid-memberships-pro' ),
															esc_url(
																add_query_arg(
																	[
																		'page' => 'pmpro-userfields',
																		'edit' => $field->name,
																	],
																	admin_url( 'admin.php' )
																)
															),
															esc_html__( 'Edit', 'paid-memberships-pro' )
														),
														'delete' => sprintf(
															'<a title="%1$s" href="%2$s">%3$s</a>',
															esc_attr__( 'Delete', 'paid-memberships-pro' ),
															'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( $delete_nonce_url ) . '\'); void(0);',
															esc_html__( 'Delete', 'paid-memberships-pro' )
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
											<?php
										} else {
											?>
											<td><?php echo esc_html( $field->label ); ?></td>
											<?php
										}
										?>
										<td>
											<?php
											if ( in_array( $field->profile, array( true, 'only' ), true ) ) {
												esc_html_e( 'Yes', 'paid-memberships-pro' );
											} elseif ( in_array( $field->profile, array( 'admin', 'only_admin' ), true ) ) {
												esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' );
											} else {
												esc_html_e( 'No', 'paid-memberships-pro' );
											}
											?>
										</td>
										<td>
											<?php
											if ( in_array( $field->profile, array( 'only', 'only_admin' ), true ) ) {
												esc_html_e( 'No', 'paid-memberships-pro' );
											} else {
												esc_html_e( 'Yes', 'paid-memberships-pro' );
											}
											?>
										</td>
										<td><?php echo $field->required ? 'Yes' : 'No';?></td>
										<td>
											<?php
											if ( empty( $field->levels ) ) {
												esc_html_e( 'No Level Restrictions', 'paid-memberships-pro' );
											} elseif ( 3 >= count( $field->levels ) ) {
												$level_names = array();
												foreach ( $field->levels as $level_id ) {
													$level = pmpro_getLevel( $level_id );
													if ( ! empty( $level ) ) {
														$level_names[] = $level->name;
													}
												}
												echo esc_html( implode( ', ', $level_names ) );
											} else {
												// Show a preview with a button to expand and show all levels.
												$level_names = array();
												foreach ( $field->levels as $level_id ) {
													$level = pmpro_getLevel( $level_id );
													if ( ! empty( $level ) ) {
														$level_names[] = $level->name;
													}
												}
												$preview_levels = array_slice( $level_names, 0, 3 );
												echo esc_html( implode( ', ', $preview_levels ) ) . '...';
												?>
												<span class="pmpro-level-restrictions-preview">
													<a href="#" class="pmpro-level-restrictions-preview-button"><?php esc_html_e( 'Show All', 'paid-memberships-pro' ); ?></a>
													<span class="pmpro-level-restrictions-preview-list" style="display: none;">
														<?php echo esc_html( implode( ', ', $level_names ) ); ?>
													</span>
												</span>
												<?php
											}
											?>
										</td>
										<td>
											<?php
											$field->display( empty( $field->default ) ? null : $field->default );
											if(!empty($field->hint)) {
												?>
												<p class="description"><?php echo wp_kses_post( $field->hint );?></p>
												<?php
											}
											?>
										</td>
									</tr>
									<?php
										}
									?>
								</tbody>
							</table>
							<p class="text-center">
								<a class="button button-primary button-hero" href="<?php echo esc_url( add_query_arg( array( 'edit' => '-1', 'group' => $group->name ), admin_url( 'admin.php?page=pmpro-userfields' ) ) ); ?>">
									<?php
										/* translators: a plus sign dashicon */
										printf( esc_html__( '%s Add New Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
								</a>
							</p>
							<?php
							// If the group was added via UI, show the group actions.
							if ( in_array( $group->name, $ui_groups ) ) {
								?>
								<div class="pmpro_section_actions">
									<a class="button-secondary pmpro-has-icon pmpro-has-icon-edit" href="<?php echo esc_url( add_query_arg( array( 'edit_group' => $group->name ), admin_url( 'admin.php?page=pmpro-userfields' ) ) ); ?>" ><?php esc_html_e( 'Edit Group', 'paid-memberships-pro' ) ?></a>
									<?php
										// Show a button to delete the group (disabled if there are fields in group).
										$disabled_button = empty( $group_fields) ? '' : 'disabled=disabled';
										$disabled_message = empty( $group_fields) ? '' : '<span class="description"><em>' . __( 'Delete fields to enable group deletion.', 'paid-memberships-pro' ) . '</em></span>';
										$delete_link = '#';
										if ( empty( $group_fields) ) {
											$delete_url = empty( $group_fields) ? wp_nonce_url( add_query_arg( array( 'delete_name' => $group->name, 'action' => 'delete_group' ), admin_url( 'admin.php?page=pmpro-userfields' ) ), 'delete_group', 'pmpro_userfields_nonce' ) : '#';
											$delete_text = esc_html(
												sprintf(
													// translators: %s is the Group Name.
													__( "Are you sure you want to delete user field group: %s?", 'paid-memberships-pro' ),
													$group->label
												)
											);
											$delete_link = 'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( esc_url( $delete_url ) ) . '\'); void(0);';
										}
									?>
									<a <?php echo esc_attr( $disabled_button ); ?> class="button is-destructive pmpro-has-icon pmpro-has-icon-trash" href="<?php echo esc_attr( $delete_link ); ?>" ><?php esc_html_e( 'Delete Group', 'paid-memberships-pro' ) ?></a>
									<?php echo wp_kses_post( $disabled_message ); ?>
								</div>
								<?php
							}
							?>
						</div> <!-- end .pmpro_section_inside -->
					</div> <!-- end .pmpro_section -->
				<?php }  // Close group loop ?>
			</div>
			<p class="text-center">
				<a class="button button-secondary button-hero" href="<?php echo esc_url( add_query_arg( array( 'edit_group' => '-1' ), admin_url( 'admin.php?page=pmpro-userfields' ) ) ); ?>">
					<?php
						/* translators: a plus sign dashicon */
						printf( esc_html__( '%s Add New Group', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
				</a>
			</p>
			<?php 
		}
	}

	// Show footer.
	require_once(dirname(__FILE__) . "/admin_footer.php");
