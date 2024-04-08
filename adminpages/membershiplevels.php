<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_membershiplevels")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	// Process form submissions.
	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : false;
	if ( ! empty( $action ) && ( empty( sanitize_key( $_REQUEST['pmpro_membershiplevels_nonce'] ) ) || ! check_admin_referer( $action, 'pmpro_membershiplevels_nonce' ) ) ) {
		$page_msg = -1;
		$page_msgt = __( 'Are you sure you want to do that? Try again.', 'paid-memberships-pro' );
		$action = false;
	}
	switch ( $action ) {
		case 'save_membershiplevel':
			include_once( PMPRO_DIR . '/adminpages/levels/save-level.php' );
			break;
		case 'delete_membership_level':
			include_once( PMPRO_DIR . '/adminpages/levels/delete-level.php' );
			break;
		case 'save_group':
			include_once( PMPRO_DIR . '/adminpages/groups/save-group.php' );
			break;
		case 'delete_group':
			include_once( PMPRO_DIR . '/adminpages/groups/delete-group.php' );
			break;
	}

	// Show header.
	require_once(dirname(__FILE__) . "/admin_header.php");
	
	// Show page contents.
	if ( isset( $_REQUEST['edit'] ) ) {
		// Editing a membership level.
		$edit = intval( $_REQUEST['edit'] );
		require_once( PMPRO_DIR . '/adminpages/levels/edit-level.php' );
	} elseif ( isset( $_REQUEST['edit_group'] ) ) {
		// Editing a group.
		$edit_group = intval( $_REQUEST['edit_group'] );
		require_once( PMPRO_DIR . '/adminpages/groups/edit-group.php' );
	} else {
		// Showing the levels list.
		global $wpdb, $pmpro_pages;

		if(isset($_REQUEST['s']))
			$s = sanitize_text_field($_REQUEST['s']);
		else
			$s = "";

		$level_templates = pmpro_edit_level_templates();
		$pmpro_level_order = get_option( 'pmpro_level_order');

		// Get level groups in order.
		$level_groups = pmpro_get_level_groups_in_order();

		$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
		if($s)
			$sqlQuery .= "WHERE name LIKE '%" . esc_sql( $s ) . "%' ";
			$sqlQuery .= "ORDER BY id ASC";

			$levels = $wpdb->get_results($sqlQuery, OBJECT);

        if(empty($_REQUEST['s']) && !empty($pmpro_level_order)) {
            //reorder levels
            $order = explode(',', $pmpro_level_order);

			//put level ids in their own array
			$level_ids = array();
			foreach($levels as $level)
				$level_ids[] = $level->id;

			//remove levels from order if they are gone
			foreach($order as $key => $level_id)
				if(!in_array($level_id, $level_ids))
					unset($order[$key]);

			//add levels to the end if they aren't in the order array
			foreach($level_ids as $level_id)
				if(!in_array($level_id, $order))
					$order[] = $level_id;

			//remove dupes
			$order = array_unique($order);

			//save the level order
			pmpro_setOption('level_order', implode(',', $order));

			//reorder levels here
            $reordered_levels = array();
            foreach ($order as $level_id) {
                foreach ($levels as $level) {
                    if ($level_id == $level->id)
                        $reordered_levels[] = $level;
                }
            }
        }
		else
			$reordered_levels = $levels;

		if(empty($_REQUEST['s']) && count($reordered_levels) > 1)
		{
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

		            $("table.has-sortable-membership-levels tbody").sortable({
		                axis: "y",
		                helper: fixHelper,
		                placeholder: 'testclass',
		                forcePlaceholderSize: true,
		                update: update_level_order
		            });

		            function update_level_order(event, ui) {
		                level_order = [];
		                $("table.has-sortable-membership-levels tbody tr").each(function() {
		                    level_order.push(parseInt( $("td:first", this).text()));
		                });

		                data = {
		                    action: 'pmpro_update_level_order',
		                    level_order: level_order,
		                    nonce: '<?php echo esc_attr( wp_create_nonce( 'pmpro_update_level_order' ) ); ?>'
		                };

		                $.post(ajaxurl, data, function(response) {
		                });
		            }

					$('.pmpro_section-sort-button-move-up').on('click',function(){
						var current = $(this).closest('.pmpro_section');
						current.prev().before(current);
						update_level_group_order();
					});
					$('.pmpro_section-sort-button-move-down').on('click',function(){
						var current = $(this).closest('.pmpro_section');
						current.next().after(current);
						update_level_group_order();
					});

					function update_level_group_order(event, ui) {
						level_group_order = [];
						$(".pmpro_section").each(function() {
							level_group_order.push(parseInt( $(".pmpro-level-settings-group-id", this).val()));
						});

						data = {
							action: 'pmpro_update_level_group_order',
							level_group_order: level_group_order
						};

						$.post(ajaxurl, data, function(response) {
						});
					}
		        });
		    </script>
			<?php
		}

		// Fix orphaned levels.
		foreach ( $reordered_levels as $reordered_level ) {
			if ( empty( pmpro_get_group_id_for_level( $reordered_level->id ) )  && ! empty( $level_groups ) ) {
				pmpro_add_level_to_group( $reordered_level->id, reset( $level_groups )->id );
			}
		}

		// For each group, make sure that each level in the group still exists. If not, remove the link.
		foreach ( $level_groups as $level_group ) {
			$group_level_ids = pmpro_get_level_ids_for_group( $level_group->id );
			foreach ( $group_level_ids as $group_level_id ) {
				$level_exists = false;
				foreach ( $reordered_levels as $reordered_level ) {
					if ( $group_level_id === $reordered_level->id ) {
						$level_exists = true;
						break;
					}
				}
				if ( ! $level_exists ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->pmpro_membership_levels_groups WHERE `group` = %d AND `level` = %d", $level_group->id, $group_level_id ) );
				}
			}
		}

		// Check if we have any MMPU incompatible Add Ons.
		$mmpu_incompatible_add_ons = pmpro_get_mmpu_incompatible_add_ons();

		// Check if the current setup has multiple level groups or a group that allows a user to have multiple levels.
		$is_mmpu_setup = false;
		if ( count( $level_groups ) > 1 ) {
			$is_mmpu_setup = true;
		} else {
			foreach ( $level_groups as $level_group ) {
				if ( $level_group->allow_multiple_selections ) {
					$is_mmpu_setup = true;
					break;
				}
			}
		}

		?>
		<hr class="wp-header-end">
		<?php if( empty( $s ) && count( $reordered_levels ) === 0 ) { ?>
			<div class="pmpro-new-install">
				<h2><?php esc_html_e( 'No Membership Levels Found', 'paid-memberships-pro' ); ?></h2>
				<a href="javascript:addLevel();" class="button-primary"><?php esc_html_e( 'Create a Membership Level', 'paid-memberships-pro' ); ?></a>
				<a href="https://www.paidmembershipspro.com/documentation/initial-plugin-setup/step-1-add-new-membership-level/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=documentation&utm_content=step-1-add-new-membership-level" target="_blank" rel="nofollow noopener" class="button"><?php esc_html_e( 'Video: Membership Levels', 'paid-memberships-pro' ); ?></a>
			</div> <!-- end pmpro-new-install -->
		<?php } else { ?>
			<form id="membership-level-list-form" method="get" action="">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Membership Levels', 'paid-memberships-pro' ); ?></h1>

				<?php
					// Build the page action links to return.
					$pmpro_membershiplevels_page_action_links = array();

					// Add New Level link
					$pmpro_membershiplevels_page_action_links['add-new'] = array(
						'url' => 'javascript:addLevel();',
						'name' => __( 'Add New Level', 'paid-memberships-pro' ),
						'icon' => 'plus'
					);

					// Add New Group link
					$pmpro_membershiplevels_page_action_links['add-new-group'] = array(
						'url' => add_query_arg( array( 'edit_group' => '-1' ), admin_url( 'admin.php?page=pmpro-membershiplevels' ) ),
						'name' => __( 'Add New Group', 'paid-memberships-pro' ),
						'icon' => 'plus'
					);

					/**
					 * Filter the Membership Levels page title action links.
					 *
					 * @since 2.9
					 * @since 3.0 Deprecating strings as $pmpro_membershiplevels_page_action_links values.
					 *
					 * @param array $pmpro_membershiplevels_page_action_links Page action links.
					 * @return array $pmpro_membershiplevels_page_action_links Page action links.
					 */
					$pmpro_membershiplevels_page_action_links = apply_filters( 'pmpro_membershiplevels_page_action_links', $pmpro_membershiplevels_page_action_links );

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

						// Allow some JS for the URL. Otherwise esc_url.
						$allowed_js_in_urls = array( 'javascript:addLevel();', 'javascript:void(0);' );
						if ( ! in_array( $pmpro_membershiplevels_page_action_link['url'], $allowed_js_in_urls ) ) {
							$pmpro_membershiplevels_page_action_link['url'] = esc_url( $pmpro_membershiplevels_page_action_link['url'] );
						}
						?>
						<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo $pmpro_membershiplevels_page_action_link['url']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo esc_html( $pmpro_membershiplevels_page_action_link['name'] ); ?></a>
						<?php
					}
				?>
				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php esc_html_e('Search Levels', 'paid-memberships-pro' );?></label>
					<input type="hidden" name="page" value="pmpro-membershiplevels" />
					<input id="post-search-input" type="text" value="<?php echo esc_attr($s); ?>" name="s" size="30" />
					<input class="button" type="submit" value="<?php esc_attr_e('Search Levels', 'paid-memberships-pro' );?>" id="search-submit" />
				</p>

			</form>

			<?php if(empty($_REQUEST['s']) && count($reordered_levels) > 1) { ?>
				<p><?php esc_html_e('Drag and drop membership levels within the group to reorder them on the Membership Levels page. Reorder groups using the up/down arrows.', 'paid-memberships-pro' ); ?></p>
			<?php } ?>

			<?php
				if ( ! empty( $mmpu_incompatible_add_ons ) && $is_mmpu_setup ) {
					?>
					<div class="pmpro_message pmpro_error">
						<p>
							<?php
							echo sprintf(
								// translators: %s is the list of incompatible add ons.
								esc_html__( 'The following active Add Ons are not compatible with your membership level setup: %s', 'paid-memberships-pro' ),
								'<strong>' . esc_html( implode( ', ', $mmpu_incompatible_add_ons ) ) . '.</strong>'
							);
							?>
						</p>
						<p>
							<?php
							esc_html_e( 'This warning is shown because you have more than one level group or a level group that allows multiple selections. To continue using these Add Ons, you should move all levels to a single "one level per" group.', 'paid-memberships-pro' );
							?>
						</p>
					</div>
					<?php
				}
			?>
			<div id="pmpro-edit-levels-groups"><?php
				foreach ( $level_groups as $level_group ) {
					$group_level_ids = pmpro_get_level_ids_for_group( $level_group->id );
					$group_levels_to_show = array();
					foreach ( $reordered_levels as $reordered_level ) {
						if ( in_array( $reordered_level->id, $group_level_ids ) ) {
							$group_levels_to_show[] = $reordered_level;
						}
					}
					$section_visibility = 'shown';
					$section_activated = 'true';
					?>
					<div id="pmpro-level-settings-group-div-<?php echo esc_attr( $level_group->id ); ?>" class="pmpro_section" data-visibility="<?php echo esc_attr( $section_visibility ); ?>" data-activated="<?php echo esc_attr( $section_activated ); ?>">
						<div class="pmpro_section_toggle">
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
							<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
								<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
								<input type="hidden" class="pmpro-level-settings-group-id" value="<?php echo esc_attr( $level_group->id ); ?>" />
								<?php echo esc_html( $level_group->name ) ?>
							</button>
						</div>
						<div class="pmpro_section_inside">
							<p>
								<?php
									if ( $level_group->allow_multiple_selections ) {
										esc_html_e( 'Users can choose multiple levels from this group.', 'paid-memberships-pro' );
									} else {
										esc_html_e( 'Users can only choose one level from this group.', 'paid-memberships-pro' );
									}
								?>
							</p>
							<table class="widefat membership-levels<?php if ( count( $group_levels_to_show ) > 1 && empty( $s ) ) { ?> has-sortable-membership-levels<?php } ?>">
								<thead>
									<tr>
										<th><?php esc_html_e('ID', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Name', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Level Cost', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Expiration', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('Allow Signups', 'paid-memberships-pro' );?></th>
										<?php do_action( 'pmpro_membership_levels_table_extra_cols_header', $reordered_levels ); ?>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $group_levels_to_show ) ) { ?>
									<tr>
										<td colspan="5">
											<?php if ( ! empty( $s ) ) {
												printf(
													// translators: %s is the search term.
													esc_html__( 'No membership levels found for search term: "%s".', 'paid-memberships-pro' ),
													esc_html( $s )
												);
											} else {
												esc_html_e( 'No membership levels found.', 'paid-memberships-pro' );
											} ?>
										</td>
									</tr>
									<?php } ?>
									<?php
										foreach ( $group_levels_to_show as $level ) {
									?>
									<tr class="<?php if(!$level->allow_signups) { ?>pmpro_gray<?php } ?> <?php if(!pmpro_checkLevelForStripeCompatibility($level) || !pmpro_checkLevelForBraintreeCompatibility($level) || !pmpro_checkLevelForPayflowCompatibility($level) || !pmpro_checkLevelForTwoCheckoutCompatibility($level)) { ?>pmpro_error<?php } ?>">
										<td><?php echo esc_html( $level->id );?></td>
										<td class="level_name has-row-actions">
											<span class="level-name"><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-membershiplevels', 'edit' => $level->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $level->name ); ?></a></span>
											<div class="row-actions">
												<?php
												$delete_text = esc_html(
													sprintf(
														// translators: %s is the Level Name.
														__( "Are you sure you want to delete membership level %s? Any gateway subscriptions or third-party connections with a member's account will remain active.", 'paid-memberships-pro' ),
														$level->name
													)
												);

												$delete_nonce_url = wp_nonce_url(
													add_query_arg(
														[
															'page'   => 'pmpro-membershiplevels',
															'action' => 'delete_membership_level',
															'deleteid' => $level->id,
														],
														admin_url( 'admin.php' )
													),
													'delete_membership_level',
													'pmpro_membershiplevels_nonce'
												);

												$actions = [
													'edit'   => sprintf(
														'<a title="%1$s" href="%2$s">%3$s</a>',
														esc_attr__( 'Edit', 'paid-memberships-pro' ),
														esc_url(
															add_query_arg(
																[
																	'page' => 'pmpro-membershiplevels',
																	'edit' => $level->id,
																],
																admin_url( 'admin.php' )
															)
														),
														esc_html__( 'Edit', 'paid-memberships-pro' )
													),
													'copy'   => sprintf(
														'<a title="%1$s" href="%2$s">%3$s</a>',
														esc_attr__( 'Copy', 'paid-memberships-pro' ),
														esc_url(
															add_query_arg(
																[
																	'page' => 'pmpro-membershiplevels',
																	'edit' => - 1,
																	'copy' => $level->id,
																],
																admin_url( 'admin.php' )
															)
														),
														esc_html__( 'Copy', 'paid-memberships-pro' )
													),
													'delete' => sprintf(
														'<a title="%1$s" href="%2$s">%3$s</a>',
														esc_attr__( 'Delete', 'paid-memberships-pro' ),
														'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( $delete_nonce_url ) . '\'); void(0);',
														esc_html__( 'Delete', 'paid-memberships-pro' )
													),
												];

												/**
												 * Filter the extra actions for this level.
												 *
												 * @since 2.6.2
												 *
												 * @param array  $actions The list of actions.
												 * @param object $level   The membership level data.
												 */
												$actions = apply_filters( 'pmpro_membershiplevels_row_actions', $actions, $level );

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
											<?php if(pmpro_isLevelFree($level)) { ?>
												<?php esc_html_e( 'Free', 'paid-memberships-pro' ); ?>
											<?php } else { ?>
												<?php echo wp_kses_post( str_replace( 'The price for membership is', '', pmpro_getLevelCost($level) ) ); ?>
											<?php } ?>
										</td>
										<td>
											<?php if(!pmpro_isLevelExpiring($level)) {
												esc_html_e( '&#8212;', 'paid-memberships-pro' );
											} else { ?>
												<?php esc_html_e('After', 'paid-memberships-pro' );?> <?php echo esc_html( $level->expiration_number );?> <?php echo esc_html( sornot($level->expiration_period,$level->expiration_number) );?>
											<?php } ?>
										</td>
										<td><?php
											if($level->allow_signups) {
												if ( ! empty( $pmpro_pages['checkout'] ) ) {
													?><a target="_blank" href="<?php echo esc_url( add_query_arg( 'pmpro_level', $level->id, pmpro_url("checkout") ) );?>"><?php esc_html_e('Yes', 'paid-memberships-pro' );?></a><?php
												} else {
													esc_html_e('Yes', 'paid-memberships-pro' );
												}
											} else {
												esc_html_e('No', 'paid-memberships-pro' );
											}
											?></td>
										<?php do_action( 'pmpro_membership_levels_table_extra_cols_body', $level ); ?>
									</tr>
									<?php
										}
									?>
								</tbody>
							</table>
							<p class="text-center">
								<a class="button button-primary button-hero" href="javascript:addLevel(<?php echo esc_js( $level_group->id ); ?>);">
									<?php
										/* translators: a plus sign dashicon */
										printf( esc_html__( '%s Add New Level', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
								</a>
							</p>
							<div class="pmpro_section_actions">
								<a class="button-secondary pmpro-has-icon pmpro-has-icon-edit" href="<?php echo esc_url( add_query_arg( array( 'edit_group' => $level_group->id ), admin_url( 'admin.php?page=pmpro-membershiplevels' ) ) ); ?>" ><?php esc_html_e( 'Edit Group', 'paid-memberships-pro' ) ?></a>
								<?php
									// Show a button to delete the group (disabled if there are levels in group).
									$disabled_button = empty( $group_levels_to_show) ? '' : 'disabled=disabled';
									$disabled_message = empty( $group_levels_to_show) ? '' : '<span class="description"><em>' . __( 'Move levels to another group to enable group deletion.', 'paid-memberships-pro' ) . '</em></span>';
									$delete_link = '#';
									if ( empty( $group_levels_to_show) ) {
										$delete_url = empty( $group_levels_to_show) ? wp_nonce_url( add_query_arg( array( 'group_id' => $level_group->id, 'action' => 'delete_group' ), admin_url( 'admin.php?page=pmpro-membershiplevels' ) ), 'delete_group', 'pmpro_membershiplevels_nonce' ) : '#';
										$delete_text = esc_html(
											sprintf(
												// translators: %s is the Group Name.
												__( "Are you sure you want to delete membership level group: %s?", 'paid-memberships-pro' ),
												$level_group->name
											)
										);
										$delete_link = 'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( esc_url( $delete_url ) ) . '\'); void(0);';
									}
								?>
								<a <?php echo esc_attr( $disabled_button ); ?> class="button is-destructive pmpro-has-icon pmpro-has-icon-trash" href="<?php echo esc_attr( $delete_link ); ?>" ><?php esc_html_e( 'Delete Group', 'paid-memberships-pro' ) ?></a>
								<?php echo wp_kses_post( $disabled_message ); ?>
							</div>
						</div> <!-- end .pmpro_section_inside -->
					</div> <!-- end .pmpro_section -->
				<?php }  // Close group loop ?>
			</div>
			<p class="text-center">
				<a class="button button-secondary button-hero" href="<?php echo esc_url( add_query_arg( array( 'edit_group' => '-1' ), admin_url( 'admin.php?page=pmpro-membershiplevels' ) ) ); ?>">
					<?php
						/* translators: a plus sign dashicon */
						printf( esc_html__( '%s Add New Group', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
				</a>
			</p>
			<?php 
		}

		// Add new level popup.
		?>
		<div id="pmpro-popup" class="pmpro-popup-overlay">
			<span class="pmpro-popup-helper"></span>
			<div class="pmpro-popup-wrap">
				<span id="pmpro-popup-inner">
					<a class="pmproPopupCloseButton" href="#" title="<?php esc_attr_e( 'Close Popup', 'paid-memberships-pro' ); ?>"><span class="dashicons dashicons-no"></span></a>
					<h1><?php esc_html_e( 'What type of membership level do you want to create?', 'paid-memberships-pro' ); ?></h1>
					<div class="pmpro_level_templates">
						<?php
							foreach ( $level_templates as $key => $value ) {
								// Build the selectors for the level item.
								$classes = array();
								$classes[] = 'pmpro_level_template';
								if ( $key === 'approvals' && ! defined( 'PMPRO_APP_DIR' ) ) {
									$classes[] = 'inactive';
								} elseif ( $key === 'gift' && ! defined( 'PMPROGL_VERSION' ) ) {
									$classes[] = 'inactive';
								} elseif ( $key === 'invite' && ! defined( 'PMPROIO_CODES' ) ) {
									$classes[] = 'inactive';
								}
								$class = implode( ' ', array_unique( $classes ) );

								if ( in_array( 'inactive', $classes ) ) { ?>
									<a class="<?php echo esc_attr( $class ); ?>" target="_blank" rel="nofollow noopener" href="<?php echo esc_url( $value['external-link'] ); ?>">
										<span class="label"><?php esc_html_e( 'Add On', 'paid-memberships-pro' ); ?></span>
										<span class="template"><?php echo esc_html( $value['name'] ); ?></span>
										<p><?php echo esc_html( $value['description'] ); ?></p>
									</a>
									<?php
								} else { ?>
									<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-membershiplevels', 'edit' => -1, 'template' => esc_attr( $key ) ), admin_url( 'admin.php' ) ) ); ?>">
										<span class="template"><?php echo esc_html( $value['name'] ); ?></span>
										<p><?php echo esc_html( $value['description'] ); ?></p>
									</a>
									<?php
								}
							}
						?>
					</div> <!-- end pmpro_level_templates -->
				</span>
			</div>
		</div> <!-- end pmpro-popup -->
		<script>
			jQuery( document ).ready( function() {
				jQuery('.pmproPopupCloseButton').on('click',function() {
					jQuery('.pmpro-popup-overlay').hide();
				});
				<?php if( ! empty( $_REQUEST['showpopup'] ) ) { ?>addLevel();<?php } ?>
			} );
			function addLevel( group_id ) {
				if ( typeof group_id !== undefined ) {
					jQuery('a.pmpro_level_template').each(function(){
						this.href += '&level_group=' + group_id;
					});
				}
				jQuery('.pmpro-popup-overlay').show();
			}
			// Hide the popup banner if "ESC" is pressed.
			jQuery(document).keyup(function (e) {
				if (e.key === 'Escape') {
					jQuery('.pmpro-popup-overlay').hide();
				}
			});
		</script>
		<?php
	}

	// Show footer.
	require_once(dirname(__FILE__) . "/admin_footer.php");
