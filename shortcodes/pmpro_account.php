<?php
/*
	Shortcode to show membership account information
*/
function pmpro_shortcode_account($atts, $content=null, $code="")
{
	global $wpdb, $current_user;

	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_account] [pmpro_account sections="membership,profile"/]

	extract(shortcode_atts(array(
		'section' => '',
		'sections' => 'membership,profile,invoices,links',
		'title' => null,
	), $atts));

	// Did they use 'section' instead of 'sections'?
	if ( ! empty( $section ) ) {
		$sections = $section;
	}

	// Extract the user-defined sections for the shortcode.
	$sections = array_map( 'trim', explode( ',', $sections ) );

	// Start the output buffer to capture the content of the shortcode.
	ob_start();

	// If multiple sections are being shown, set title to null.
	// Titles can only be changed from the default if only one section is being shown.
	if ( count( $sections ) > 1 ) {
		$title = null;
	}

	// We want to show the actual levels for admins.
	add_filter( 'pmpro_disable_admin_membership_access', '__return_true', 15 );

	// Get the current user's membership levels.
	$mylevels = pmpro_getMembershipLevelsForUser();

	// Sort the levels by the levels order.
	if ( ! empty( $mylevels ) ) {
		$mylevels = pmpro_sort_levels_by_order( $mylevels );
	}

	// Remove the filter so we don't mess up other stuff.
	remove_filter( 'pmpro_disable_admin_membership_access', '__return_true', 15 ); 

	// Just to be sure, only inclue the levels that allow signups.
	$pmpro_levels = pmpro_getAllLevels();
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<?php if ( in_array( 'profile', $sections ) ) {
			?>
			<section id="pmpro_account-profile" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_account-profile' ) ); ?>">
				<?php
					if ( '' !== $title ) { // Check if title is being forced to not show.
						// If a custom title was not set, use the default. Otherwise, show the custom title.
						?>
						<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>"><?php echo esc_html( null === $title ? __( 'My Account', 'paid-memberships-pro' ) : $title ); ?></h2>
						<?php
					}
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<?php
						// Get the current user.
						wp_get_current_user();
					?>
					<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large pmpro_heading-with-avatar' ) ); ?>">
						<?php echo get_avatar( $current_user->ID, 48 ); ?>
						<?php
							/* translators: the current user's display name */
							printf( esc_html__( 'Welcome, %s', 'paid-memberships-pro' ), esc_html( $current_user->display_name ) );
						?>
					</h3>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ); ?>">
							<?php do_action('pmpro_account_bullets_top');?>
							<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>"><strong><?php esc_html_e( 'Username', 'paid-memberships-pro' ); ?>:</strong> <?php echo esc_html( $current_user->user_login ); ?></li>
							<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>"><strong><?php esc_html_e( 'Email', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->user_email ); ?></li>
							<?php do_action('pmpro_account_bullets_bottom');?>
						</ul>
					</div> <!-- end pmpro_card_content -->
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
						<?php
							// Get the edit profile and change password links if 'Member Profile Edit Page' is set.
							if ( ! empty( get_option( 'pmpro_member_profile_edit_page_id' ) ) ) {
								$edit_profile_url = pmpro_url( 'member_profile_edit' );
								$change_password_url = add_query_arg( 'view', 'change-password', pmpro_url( 'member_profile_edit' ) );
							} elseif ( ! pmpro_block_dashboard() ) {
								$edit_profile_url = admin_url( 'profile.php' );
								$change_password_url = admin_url( 'profile.php' );
							}

							// Build the links to return.
							$pmpro_profile_action_links = array();
							if ( ! empty( $edit_profile_url) ) {
								$pmpro_profile_action_links['edit-profile'] = sprintf( '<a id="pmpro_actionlink-profile" href="%s">%s</a>', esc_url( $edit_profile_url ), esc_html__( 'Edit Profile', 'paid-memberships-pro' ) );
							}

							if ( ! empty( $change_password_url ) ) {
								$pmpro_profile_action_links['change-password'] = sprintf( '<a id="pmpro_actionlink-change-password" href="%s">%s</a>', esc_url( $change_password_url ), esc_html__( 'Change Password', 'paid-memberships-pro' ) );
							}

							$pmpro_profile_action_links['logout'] = sprintf( '<a id="pmpro_actionlink-logout" href="%s">%s</a>', esc_url( wp_logout_url() ), esc_html__( 'Log Out', 'paid-memberships-pro' ) );

							// Wrap each action link item in a <span>
							$pmpro_profile_action_links = array_map( function( $link ) {
								return '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_action' ) ) . '">' . $link . '</span>';
							}, $pmpro_profile_action_links );

							
							/**
							 * Filter the profile action links.
							 *
							 * @param array $pmpro_profile_action_links Profile action links.
							 * @return array $pmpro_profile_action_links Profile action links.
							 */
							$pmpro_profile_action_links = apply_filters( 'pmpro_account_profile_action_links', $pmpro_profile_action_links );

							$allowed_html = array(
								'a' => array (
									'class' => array(),
									'href' => array(),
									'id' => array(),
									'target' => array(),
									'title' => array(),
								),
								'span' => array(
									'class' => array(),
								),
							);
							echo wp_kses( implode( '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_action_separator' ) ) . '">' . pmpro_actions_nav_separator() . '</span>', $pmpro_profile_action_links ), $allowed_html );
						?>
					</div> <!-- end pmpro_card_actions -->
				</div> <!-- end pmpro_card -->
			</section> <!-- end pmpro_account-profile -->
		<?php } ?>

		<?php if ( in_array( 'membership', $sections) || in_array( 'memberships', $sections ) ) {
			?>
			<section id="pmpro_account-membership" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_account-membership' ) ); ?>">
				<?php
					if ( '' !== $title ) { // Check if title is being forced to not show.
						// If a custom title was not set, use the default. Otherwise, show the custom title.
						?>
						<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>"><?php echo esc_html( null === $title ? __( 'My Memberships', 'paid-memberships-pro' ) : $title ); ?></h2>
						<?php
					}
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
					<?php if ( empty( $mylevels ) ) {
						$url = pmpro_url( 'levels' );
						?>
						<div id="pmpro_account-membership-none" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
								<p><?php echo wp_kses( sprintf( __( "You do not have an active membership. <a href='%s'>Choose a membership level.</a>", 'paid-memberships-pro' ), $url ), array( 'a' => array( 'href' => array() ) ) ); ?></p>
							</div> <!-- end pmpro_card_content -->
						</div> <!-- end pmpro_card -->
						<?php
					} else {
						foreach ( $mylevels as $level ) {
							?>
							<div id="pmpro_account-membership-<?php echo esc_attr( $level->ID ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">

								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php echo esc_html( $level->name ); ?></h3>

								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">

									<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-3' ) ); ?>">

									<?php
										// Show information about the first active subscription for this level.
										$subscription = null;
										$subscriptions =  PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $level->id );
										if ( ! empty( $subscriptions ) ) {
											$subscription = $subscriptions[0];
											?>
											<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?></span>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo esc_html( $subscription->get_cost_text() ); ?></span>
											</li>
											<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Next payment on', 'paid-memberships-pro' ); ?></span>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo esc_html( $subscription->get_next_payment_date( get_option( 'date_format' ) ) ); ?></span>
											</li>
											<?php
										}

										$expiration_text = pmpro_get_membership_expiration_text( $level, $current_user, '' );
										if ( ! empty( $expiration_text ) ) {
											?>
											<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Expires', 'paid-memberships-pro' ); ?></span>
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo wp_kses_post( $expiration_text ); ?></span>
											</li>
											<?php
										}
										?>
									</ul> <!-- end pmpro_list -->
								</div> <!-- end pmpro_card_content -->

								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">

									<?php
										/**
										 * Fires before the member action links.
										 */
										do_action( 'pmpro_member_action_links_before' );
									?>

									<?php
										// Build the links to return.
										$pmpro_member_action_links = array();

										if ( array_key_exists($level->id, $pmpro_levels) && pmpro_isLevelExpiringSoon( $level ) ) {
											$pmpro_member_action_links['renew'] = '<a id="pmpro_actionlink-renew" href="' . esc_url( add_query_arg( 'pmpro_level', $level->id, pmpro_url( 'checkout', '', 'https' ) ) ) . '" aria-label="' . esc_attr( sprintf( esc_html__( 'Renew %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Renew', 'paid-memberships-pro' ) . '</a>';

										}

										// Check if we should show the update billing link.
										if ( ! empty( $subscription ) ) {
											// Check if this subscription is for the default gateaway (we can currently only update billing info for the default gateway).
											if ( $subscription->get_gateway() == get_option( 'pmpro_gateway' ) ) {
												// Check if the gateway supports updating billing info.
												$gateway_obj = $subscription->get_gateway_object();
												if ( ! empty( $gateway_obj ) && method_exists( $gateway_obj, 'supports' ) && $gateway_obj->supports( 'payment_method_updates' ) ) {
													// Make sure that the subscription has an order, which is necessary to update.
													$newest_orders = $subscription->get_orders( array( 'limit' => 1 ) );
													if ( ! empty( $newest_orders ) ) {
														$pmpro_member_action_links['update-billing'] = sprintf( '<a id="pmpro_actionlink-update-billing" href="%s">%s</a>', pmpro_url( 'billing', 'pmpro_subscription_id=' . $subscription->get_id(), 'https' ), esc_html__( 'Update Billing Info', 'paid-memberships-pro' ) );
													}
												}
											}
										}

										// Check if we should show the change membership level link.
										$show_change_link = false;

										// Get the group for this level.
										$level_group_id = pmpro_get_group_id_for_level( $level->ID );
										$level_group = pmpro_get_level_group( $level_group_id );

										// Show the link if there is more than one level available.
										if ( count( $pmpro_levels ) > 1 ) {
											$show_change_link = true;
										}

										// Do not show the link if the group does not allow multiple selections.
										if ( ! empty( $level_group ) && ! empty( $level_group->allow_multiple_selections ) ) {
											$show_change_link = false;
										}

										if ( ! empty( $show_change_link ) ) {
											$pmpro_member_action_links['change'] = '<a id="pmpro_actionlink-change" href="' . esc_url( pmpro_url( 'levels' ) ) . '" aria-label="' . esc_attr( sprintf( esc_html__( 'Change %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Change', 'paid-memberships-pro' ) . '</a>';

										}

										$pmpro_member_action_links['cancel'] = '<a id="pmpro_actionlink-cancel" href="' . esc_url( add_query_arg( 'levelstocancel', $level->id, pmpro_url( 'cancel' ) ) ) . '" aria-label="' . esc_attr( sprintf( esc_html__( 'Cancel %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Cancel', 'paid-memberships-pro' ) . '</a>';

										// Wrap each action link item in a <span>
										$pmpro_member_action_links = array_map( function( $link ) {
											return '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_action' ) ) . '">' . $link . '</span>';
										}, $pmpro_member_action_links );

										/**
										 * Filter the member action links.
										 *
										 * @param array $pmpro_member_action_links Member action links.
										 * @param int   $level->id The ID of the membership level.
										 * @return array $pmpro_member_action_links Member action links.
										 */
										$pmpro_member_action_links = apply_filters( 'pmpro_member_action_links', $pmpro_member_action_links, $level->id );

										$allowed_html = array(
											'a' => array (
												'class' => array(),
												'href' => array(),
												'id' => array(),
												'target' => array(),
												'title' => array(),
												'aria-label' => array(),
											),
											'span' => array(
												'class' => array(),
											),
										);
										echo wp_kses( implode( '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_action_separator' ) ) . '">' . pmpro_actions_nav_separator() . '</span>', $pmpro_member_action_links ), $allowed_html );
									?>

									<?php
										/**
										 * Fires after the member action links.
										 */
										do_action( 'pmpro_member_action_links_after' );
									?>

								</div> <!-- end pmpro_card_actions -->
							</div> <!-- end pmpro_card -->
						<?php } ?>
					<?php } ?>
				</div> <!-- end pmpro_section_content -->
			</section> <!-- end pmpro_account-membership -->
		<?php } ?>

		<?php if ( in_array( 'invoices', $sections ) ) {
			// Get the last 6 orders for the current user.
			$orders = MemberOrder::get_orders(
				array(
					'limit' => 6,
					'status' => array( 'pending', 'refunded', 'success' ),
					'user_id' => $current_user->ID,
				)
			);

			// Show the orders section if there are orders.
			if ( ! empty( $orders ) ) {
				?>
				<section id="pmpro_account-orders" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_account-orders' ) ); ?>">
					<?php
						if ( '' !== $title ) { // Check if title is being forced to not show.
							// If a custom title was not set, use the default. Otherwise, show the custom title.
							?>
							<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>"><?php echo esc_html( null === $title ? __( 'Order History', 'paid-memberships-pro' ) : $title ); ?></h2>
							<?php
						}
					?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_table_orders', 'pmpro_table_orders' ) ); ?>">
								<thead>
									<tr>
										<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-date' ) ); ?>"><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
										<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-level' ) ); ?>"><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
										<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-total' ) ); ?>"><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
										<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-status' ) ); ?>"><?php esc_html_e( 'Status', 'paid-memberships-pro'); ?></th>
									</tr>
								</thead>
								<tbody>
								<?php
									$count = 0;
									foreach ( $orders as $order ) {
										// Only show the first 5 orders.
										if ( $count++ > 4 ) {
											break;
										}

										// Get a member order object.
										$order_id = $order->id;
										$order = new MemberOrder;
										$order->getMemberOrderByID($order_id);
										$order->getMembershipLevel();

										// Set the display status and tag style.
										if ( in_array( $order->status, array( '', 'success', 'cancelled' ) ) ) {
											$display_status = esc_html__( 'Paid', 'paid-memberships-pro' );
											$tag_style = 'success';
										} elseif ( $order->status == 'pending' ) {
											// Some Add Ons set status to pending.
											$display_status = esc_html__( 'Pending', 'paid-memberships-pro' );
											$tag_style = 'alert';
										} elseif ( $order->status == 'refunded' ) {
											$display_status = esc_html__( 'Refunded', 'paid-memberships-pro' );
											$tag_style = 'error';
										}
										?>
										<tr id="pmpro_table_order-<?php echo esc_attr( $order->code ); ?>">
											<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-date' ) ); ?>" data-title="<?php esc_attr_e( 'Date', 'paid-memberships-pro' ); ?>"><a href="<?php echo esc_url( pmpro_url( "invoice", "?invoice=" . $order->code ) ) ?>"><?php echo esc_html( date_i18n(get_option("date_format"), $order->getTimestamp()) )?></a></th>
											<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-level' ) ); ?>" data-title="<?php esc_attr_e( 'Level', 'paid-memberships-pro' ); ?>"><?php if(!empty($order->membership_level)) echo esc_html( $order->membership_level->name ); else echo esc_html__("N/A", 'paid-memberships-pro' );?></td>
											<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-amount' ) ); ?>" data-title="<?php esc_attr_e( 'Amount', 'paid-memberships-pro' ); ?>"><?php
												//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												echo pmpro_escape_price( $order->get_formatted_total() ); ?></td>
											<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table_order-status' ) ); ?>" data-title="<?php esc_attr_e( 'Status', 'paid-memberships-pro' ); ?>">
												<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-' . $tag_style ) ); ?>"><?php echo esc_html( $display_status ); ?></span>
											</td>
										</tr>
										<?php
									}
								?>
								</tbody>
							</table>
						</div> <!-- end pmpro_card_content -->
						<?php
							// Show the "View All Orders" link if there are more than 5 orders.
							if ( $count == 6 ) {
								?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
									<a href="<?php echo esc_url( pmpro_url( 'invoice' ) ); ?>"><?php esc_html_e( 'View All Orders &rarr;', 'paid-memberships-pro' );?></a>
								</div>
								<?php
							}
						?>
					</div> <!-- end pmpro_card -->
				</section> <!-- end pmpro_account-orders -->
			<?php } ?>
		<?php } ?>

		<?php if ( in_array('links', $sections ) && ( has_filter( 'pmpro_member_links_top' ) || has_filter( 'pmpro_member_links_bottom' ) ) ) { ?>
			<section id="pmpro_account-links" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_account-links' ) ); ?>">
				<?php
					if ( '' !== $title ) { // Check if title is being forced to not show.
						// If a custom title was not set, use the default. Otherwise, show the custom title.
						?>
						<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>"><?php echo esc_html( null === $title ? __( 'Member Links', 'paid-memberships-pro' ) : $title ); ?></h2>
						<?php
					}
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list' ) ); ?>">
							<?php
								do_action("pmpro_member_links_top");
							?>

							<?php
								do_action("pmpro_member_links_bottom");
							?>
						</ul>
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</section> <!-- end pmpro_account-links -->
		<?php } ?>
	</div> <!-- end pmpro -->
	<?php

	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
add_shortcode('pmpro_account', 'pmpro_shortcode_account');
