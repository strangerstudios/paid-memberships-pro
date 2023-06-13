<?php
/*
	Shortcode to show membership account information
*/
function pmpro_shortcode_account($atts, $content=null, $code="")
{
	global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $levels;

	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_account] [pmpro_account sections="membership,profile"/]

	extract(shortcode_atts(array(
		'section' => '',
		'sections' => 'membership,profile,invoices,links',
		'title' => null,
	), $atts));

	//did they use 'section' instead of 'sections'?
	if(!empty($section))
		$sections = $section;

	//Extract the user-defined sections for the shortcode
	$sections = array_map('trim',explode(",",$sections));
	ob_start();

	// If multiple sections are being shown, set title to null.
	// Titles can only be changed from the default if only one section is being shown.
	if ( count( $sections ) > 1 ) {
		$title = null;
	}

	//if a member is logged in, show them some info here (1. past invoices. 2. billing information with button to update.)
	$order = new MemberOrder();
	$order->getLastMemberOrder();
	$mylevels = pmpro_getMembershipLevelsForUser();
	$pmpro_levels = pmpro_getAllLevels(false, true); // just to be sure - include only the ones that allow signups
	$invoices = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(CONVERT_TZ(timestamp, '+00:00', @@global.time_zone)) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' AND status NOT IN('review', 'token', 'error') ORDER BY timestamp DESC LIMIT 6");
	?>
	<div id="pmpro_account">
		<?php if(in_array('membership', $sections) || in_array('memberships', $sections)) { ?>
			<div id="pmpro_account-membership" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_box', 'pmpro_account-membership' ) ); ?>">
				<?php
				if ( '' !== $title ) { // Check if title is being forced to not show.
					// If a custom title was not set, use the default. Otherwise, show the custom title.
					?>
					<h2><?php echo esc_html( null === $title ? __( 'My Memberships', 'paid-memberships-pro' ) : $title ); ?></h2>
					<?php
				}
				?>
				<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
					<thead>
						<tr>
							<th><?php esc_html_e("Level", 'paid-memberships-pro' );?></th>
							<th><?php esc_html_e("Billing", 'paid-memberships-pro' ); ?></th>
							<th><?php esc_html_e("Expiration", 'paid-memberships-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $mylevels ) ) { ?>
						<tr>
							<td colspan="3">
							<?php
							// Check to see if the user has a cancelled order
							$order = new MemberOrder();
							$order->getLastMemberOrder( $current_user->ID, array( 'cancelled', 'expired', 'admin_cancelled' ) );

							if ( isset( $order->membership_id ) && ! empty( $order->membership_id ) && empty( $level->id ) ) {
								$level = pmpro_getLevel( $order->membership_id );
							}

							// If no level check for a default level.
							if ( empty( $level ) || ! $level->allow_signups ) {
								$default_level_id = apply_filters( 'pmpro_default_level', 0 );
							}

							// Show the correct checkout link.
							if ( ! empty( $level ) && ! empty( $level->allow_signups ) ) {
								$url = pmpro_url( 'checkout', '?level=' . $level->id );
								echo wp_kses( sprintf( __( "Your membership is not active. <a href='%s'>Renew now.</a>", 'paid-memberships-pro' ), $url ), array( 'a' => array( 'href' => array() ) ) );
							} elseif ( ! empty( $default_level_id ) ) {
								$url = pmpro_url( 'checkout', '?level=' . $default_level_id );
								echo wp_kses( sprintf( __( "You do not have an active membership. <a href='%s'>Register here.</a>", 'paid-memberships-pro' ), $url ), array( 'a' => array( 'href' => array() ) ) );
							} else {
								$url = pmpro_url( 'levels' );
								echo wp_kses( sprintf( __( "You do not have an active membership. <a href='%s'>Choose a membership level.</a>", 'paid-memberships-pro' ), $url ), array( 'a' => array( 'href' => array() ) ) );
							}
							?>
							</td>
						</tr>
							<?php } else { ?>
							<?php
								foreach($mylevels as $level) {
							?>
							<tr>
								<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_account-membership-levelname' ) ); ?>">
									<?php echo esc_html( $level->name ); ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actionlinks' ) ); ?>">
										<?php do_action("pmpro_member_action_links_before"); ?>

										<?php
										// Build the links to return.
										$pmpro_member_action_links = array();

										if( array_key_exists($level->id, $pmpro_levels) && pmpro_isLevelExpiringSoon( $level ) ) {
											$pmpro_member_action_links['renew'] = '<a id="pmpro_actionlink-renew" href="' . esc_url( add_query_arg( 'level', $level->id, pmpro_url( 'checkout', '', 'https' ) ) ) . '" aria-label="' . esc_html__( sprintf( esc_html__( 'Renew %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Renew', 'paid-memberships-pro' ) . '</a>';
										}

										if((isset($order->status) && $order->status == "success") && (isset($order->gateway) && in_array($order->gateway, array("authorizenet", "paypal", "stripe", "braintree", "payflow", "cybersource"))) && pmpro_isLevelRecurring($level)) {
											$pmpro_member_action_links['update-billing'] = '<a id="pmpro_actionlink-update-billing" href="' . esc_url( pmpro_url( 'billing', '', 'https' ) ) . '" aria-label="' . esc_html__( sprintf( esc_html__( 'Update Billing Info for %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Update Billing Info', 'paid-memberships-pro' ) . '</a>';
										}

										//To do: Only show CHANGE link if this level is in a group that has upgrade/downgrade rules
										if(count($pmpro_levels) > 1 && !defined("PMPRO_DEFAULT_LEVEL")) {
											$pmpro_member_action_links['change'] = '<a id="pmpro_actionlink-change" href="' . esc_url( pmpro_url( 'levels' ) ) . '" aria-label="' . esc_html__( sprintf( esc_html__( 'Change %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Change', 'paid-memberships-pro' ) . '</a>';
										}

										$pmpro_member_action_links['cancel'] = '<a id="pmpro_actionlink-cancel" href="' . esc_url( add_query_arg( 'levelstocancel', $level->id, pmpro_url( 'cancel' ) ) ) . '" aria-label="' . esc_html__( sprintf( esc_html__( 'Cancel %1$s Membership', 'paid-memberships-pro' ), $level->name ) ) . '">' . esc_html__( 'Cancel', 'paid-memberships-pro' ) . '</a>';

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
										);
										echo wp_kses( implode( pmpro_actions_nav_separator(), $pmpro_member_action_links ), $allowed_html );
										?>

										<?php do_action("pmpro_member_action_links_after"); ?>
									</div> <!-- end pmpro_actionlinks -->
								</td>
								<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_account-membership-levelfee' ) ); ?>">
									<p><?php echo wp_kses_post( pmpro_getLevelCost($level, true, true) );?></p>
								</td>
								<td class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_account-membership-expiration' ) ); ?>">
									<?php
										$expiration_text = '<p>';
										if ( $level->enddate ) {
											$expiration_text .= date_i18n( get_option( 'date_format' ), $level->enddate );
											/**
											 * Filter to include the expiration time with expiration date
											 *
											 * @param bool $pmpro_show_time_on_expiration_date Show the expiration time with expiration date (default: false).
											 *
											 * @return bool $pmpro_show_time_on_expiration_date Whether to show the expiration time with expiration date.
											 *
											 */
											if ( apply_filters( 'pmpro_show_time_on_expiration_date', false ) ) {
												$expiration_text .= ' ' . date_i18n( get_option( 'time_format', __( 'g:i a' ) ), $level->enddate );
											}
										} else {
											$expiration_text .= esc_html_x( '&#8212;', 'A dash is shown when there is no expiration date.', 'paid-memberships-pro' );
										}
										$expiration_text .= '</p>';
										echo wp_kses_post( apply_filters( 'pmpro_account_membership_expiration_text', $expiration_text, $level ) );
									?>
								</td>
							</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
				<?php //Todo: If there are multiple levels defined that aren't all in the same group defined as upgrades/downgrades ?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actionlinks' ) ); ?>">
					<a id="pmpro_actionlink-levels" href="<?php echo esc_url( pmpro_url( "levels" ) ) ?>"><?php esc_html_e("View all Membership Options", 'paid-memberships-pro' );?></a>
				</div>

			</div> <!-- end pmpro_account-membership -->
		<?php } ?>

		<?php if(in_array('profile', $sections)) { ?>
			<div id="pmpro_account-profile" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_box', 'pmpro_account-profile' ) ); ?>">
				<?php
				if ( '' !== $title ) { // Check if title is being forced to not show.
					// If a custom title was not set, use the default. Otherwise, show the custom title.
					?>
					<h2><?php echo esc_html( null === $title ? __( 'My Account', 'paid-memberships-pro' ) : $title ); ?></h2>
					<?php
				}
				wp_get_current_user();
				?>
				<?php if($current_user->user_firstname) { ?>
					<p><?php echo esc_html( $current_user->user_firstname );?> <?php echo esc_html( $current_user->user_lastname );?></p>
				<?php } ?>
				<ul>
					<?php do_action('pmpro_account_bullets_top');?>
					<li><strong><?php esc_html_e("Username", 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->user_login ); ?></li>
					<li><strong><?php esc_html_e("Email", 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->user_email ); ?></li>
					<?php do_action('pmpro_account_bullets_bottom');?>
				</ul>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actionlinks' ) ); ?>">
					<?php
						// Get the edit profile and change password links if 'Member Profile Edit Page' is set.
						if ( ! empty( pmpro_getOption( 'member_profile_edit_page_id' ) ) ) {
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

						$pmpro_profile_action_links = apply_filters( 'pmpro_account_profile_action_links', $pmpro_profile_action_links );

						$allowed_html = array(
							'a' => array (
								'class' => array(),
								'href' => array(),
								'id' => array(),
								'target' => array(),
								'title' => array(),
							),
						);
						echo wp_kses( implode( pmpro_actions_nav_separator(), $pmpro_profile_action_links ), $allowed_html );
					?>
				</div>
			</div> <!-- end pmpro_account-profile -->
		<?php } ?>

		<?php if(in_array('invoices', $sections) && !empty($invoices)) { ?>
		<div id="pmpro_account-invoices" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_box', 'pmpro_account-invoices' ) ); ?>">
			<?php
			if ( '' !== $title ) { // Check if title is being forced to not show.
				// If a custom title was not set, use the default. Otherwise, show the custom title.
				?>
				<h2><?php echo esc_html( null === $title ? __( 'Past Invoices', 'paid-memberships-pro' ) : $title ); ?></h2>
				<?php
			}
			?>
			<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th><?php esc_html_e("Date", 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e("Level", 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e("Amount", 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e("Status", 'paid-memberships-pro'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					$count = 0;
					foreach($invoices as $invoice)
					{
						if($count++ > 4)
							break;

						//get an member order object
						$invoice_id = $invoice->id;
						$invoice = new MemberOrder;
						$invoice->getMemberOrderByID($invoice_id);
						$invoice->getMembershipLevel();

						if ( in_array( $invoice->status, array( '', 'success', 'cancelled' ) ) ) {
						    $display_status = esc_html__( 'Paid', 'paid-memberships-pro' );
						} elseif ( $invoice->status == 'pending' ) {
						    // Some Add Ons set status to pending.
						    $display_status = esc_html__( 'Pending', 'paid-memberships-pro' );
						} elseif ( $invoice->status == 'refunded' ) {
						    $display_status = esc_html__( 'Refunded', 'paid-memberships-pro' );
						}
						?>
						<tr id="pmpro_account-invoice-<?php echo esc_attr( $invoice->code ); ?>">
							<td><a href="<?php echo esc_url( pmpro_url( "invoice", "?invoice=" . $invoice->code ) ) ?>"><?php echo esc_html( date_i18n(get_option("date_format"), $invoice->getTimestamp()) )?></a></td>
							<td><?php if(!empty($invoice->membership_level)) echo esc_html( $invoice->membership_level->name ); else echo esc_html__("N/A", 'paid-memberships-pro' );?></td>
							<td><?php
								//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo pmpro_escape_price( pmpro_formatPrice($invoice->total) ); ?></td>
							<td><?php echo esc_html( $display_status ); ?></td>
						</tr>
						<?php
					}
				?>
				</tbody>
			</table>
			<?php if($count == 6) { ?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actionlinks' ) ); ?>"><a id="pmpro_actionlink-invoices" href="<?php echo esc_url( pmpro_url( "invoice" ) ); ?>"><?php esc_html_e("View All Invoices", 'paid-memberships-pro' );?></a></div>
			<?php } ?>
		</div> <!-- end pmpro_account-invoices -->
		<?php } ?>

		<?php if(in_array('links', $sections) && (has_filter('pmpro_member_links_top') || has_filter('pmpro_member_links_bottom'))) { ?>
		<div id="pmpro_account-links" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_box', 'pmpro_account-links' ) ); ?>">
			<?php
			if ( '' !== $title ) { // Check if title is being forced to not show.
				// If a custom title was not set, use the default. Otherwise, show the custom title.
				?>
				<h2><?php echo esc_html( null === $title ? __( 'Member Links', 'paid-memberships-pro' ) : $title ); ?></h2>
				<?php
			}
			?>
			<ul>
				<?php
					do_action("pmpro_member_links_top");
				?>

				<?php
					do_action("pmpro_member_links_bottom");
				?>
			</ul>
		</div> <!-- end pmpro_account-links -->
		<?php } ?>
	</div> <!-- end pmpro_account -->
	<?php

	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
add_shortcode('pmpro_account', 'pmpro_shortcode_account');
