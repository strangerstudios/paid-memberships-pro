<?php

// Make sure PMPro is loaded.
if ( ! class_exists( 'PMProEmail' ) ) {
	return;
}

/**
 * Class to send Admin Activity Email
 */
class PMPro_Admin_Activity_Email extends PMProEmail {
	private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new PMPro_Admin_Activity_Email();
		}

		return self::$instance;
	}

	/**
	 * Send admin an email summarizing membership site activity.
	 *
	 * @param string $frequency to send emails at. Determines length of time reported.
	 */
	public function sendAdminActivity( $frequency = '' ) {
		global $wpdb, $pmpro_levels;

		if ( ! in_array( $frequency, array( 'day', 'week', 'month', 'never' ), true ) ) {
			$frequency = pmpro_getOption( 'activity_email_frequency' );
		}

		if ( 'never' === $frequency ) {
			return;
		}

		if ( empty( $frequency ) ) {
			$frequency = 'week';
		}

		ob_start();

		?>
		<div style="margin:0;padding:30px;width:100%;background-color:#333333;">
		<center>
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border:0;max-width:600px!important;background-color:#FFFFFF;">
				<tbody>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:center;">
							<h2 style="color:#2997c8;font-size: 30px;margin:0px 0px 20px 0px;padding:0px;"><?php get_bloginfo( 'name' ); ?></h2>
							<?php
							$term_list = array(
								'day'   => __( 'yesterday', 'paid-memberships-pro' ),
								'week'  => __( 'last week', 'paid-memberships-pro' ),
								'month' => __( 'last month', 'paid-memberships-pro' ),
							);
							$term      = $term_list[ $frequency ];
							?>
							<p style="font-size: 20px;line-height: 30px;margin:0px;padding:0px;"><?php printf( __( "Here's a summary of what happened in your Paid Memberships Pro site %s:", 'paid-memberships-pro' ), esc_html( $term ) ); ?></p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #EFEFEF;font-family:Helvetica,Arial,sans-serif;font-size: 20px;line-height: 30px;color:#444444;padding: 15px;text-align:center;">
							<?php
							// Get dates that the report covers
							// Start and end dates in YYYY-MM-DD formats.
							if ( 'day' === $frequency ) {
								$report_start_date = date( 'Y-m-d', strtotime( 'yesterday' ) );
								$report_end_date   = $report_start_date;
							} elseif ( 'week' === $frequency ) {
								$report_start_date = date( 'Y-m-d', strtotime( '-7 day' ) );
								$report_end_date   = date( 'Y-m-d', strtotime( '-1 day' ) );
							} elseif ( 'month' === $frequency ) {
								$report_start_date = date( 'Y-m-d', strtotime( 'first day of last month' ) );
								$report_end_date   = date( 'Y-m-d', strtotime( 'last day of last month' ) );
							}
							$date_range = date_i18n( get_option( 'date_format' ), strtotime( $report_start_date ) );
							if ( $report_start_date !== $report_end_date ) {
								$date_range .= ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $report_end_date ) );
							}

							?>
							<p style="margin:0px;padding:0px;"><strong><?php echo( esc_html( $date_range ) ); ?></strong></p> <!--Show date range this covers here in their site's date format-->
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:center;">
							<?php
							$revenue = pmpro_get_revenue_between_dates( $report_start_date, $report_end_date );
							if ( $revenue > 0 ) {
								?>
								<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'Sales and Revenue', 'paid-memberships-pro' ); ?></h3>
								<p style="margin:0px 0px 15px 0px;padding:0px;"><?php printf( __( 'Your membership site made <strong>%1$s</strong> in revenue %2$s.', 'paid-memberships-pro' ), esc_html( pmpro_formatPrice( $revenue ) ), esc_html( $term ) ); ?></p>
							<?php } else { ?>
								<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'Signups and Cancellations', 'paid-memberships-pro' ); ?></h3>
							<?php } ?>
							<table align="center" border="0" cellpadding="0" cellspacing="5" width="100%" style="border:0;background-color:#FFFFFF;text-align: center;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height: 25px;color:#444444;">
								<tr>
									<?php
									$num_joined    = $wpdb->get_var( "SELECT COUNT( DISTINCT user_id ) FROM {$wpdb->pmpro_memberships_users} WHERE startdate >= '" . esc_sql( $report_start_date ) . " 00:00:00' AND startdate <= '" . esc_sql( $report_end_date ) . " 00:00:00'" );
									$num_expired   = $wpdb->get_var( "SELECT COUNT( DISTINCT user_id ) FROM {$wpdb->pmpro_memberships_users} WHERE status IN ('expired') AND enddate >= '" . esc_sql( $report_start_date ) . " 00:00:00' AND enddate <= '" . esc_sql( $report_end_date ) . " 00:00:00'" );
									$num_cancelled = $wpdb->get_var( "SELECT COUNT( DISTINCT user_id ) FROM {$wpdb->pmpro_memberships_users} WHERE status IN ('inactive', 'cancelled', 'admin_cancelled') AND enddate >= '" . esc_sql( $report_start_date ) . " 00:00:00' AND enddate <= '" . esc_sql( $report_end_date ) . " 00:00:00'" );

									$num_joined_link    = admin_url( 'admin.php?page=pmpro-memberslist' );
									$num_expired_link   = admin_url( 'admin.php?page=pmpro-memberslist&l=expired' );
									$num_cancelled_link = admin_url( 'admin.php?page=pmpro-memberslist&l=cancelled' );
									?>
									<td width="33%" style="border: 8px solid #dff0d8;color:#3c763d;padding:10px;"><a style="color:#3c763d;display:block;text-decoration: none;" href="<?php echo( esc_url( $num_joined_link ) ); ?>" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;"><?php echo( esc_html( $num_joined ) ); ?></div>Joined</a></td>
									<td width="33%" style="border: 8px solid #fcf8e3;color:#8a6d3b;padding:10px;"><a style="color:#8a6d3b;display:block;text-decoration: none;" href="<?php echo( esc_url( $num_expired_link ) ); ?>" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;"><?php echo( esc_html( $num_expired ) ); ?></div>Expired</a></td>
									<td width="33%" style="border: 8px solid #f2dede;color:#a94442;padding:10px;"><a style="color:#a94442;display:block;text-decoration: none;" href="<?php echo( esc_url( $num_cancelled_link ) ); ?>" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;"><?php echo( esc_html( $num_cancelled ) ); ?></div>Cancelled</a></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #EFEFEF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:left;">
							<?php
							$total_members = $wpdb->get_var( "SELECT COUNT( DISTINCT user_id ) FROM {$wpdb->pmpro_memberships_users} WHERE status IN ('active')" );
							?>
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px;padding:0px;"><span style="background: #2997c8;color:#FFFFFF;padding:10px;"><?php echo( esc_html( $total_members ) ); ?></span><?php esc_html_e( ' Total Members&mdash;great work!', 'paid-memberships-pro' ); ?></h3>
							<?php
							$members_per_level = $wpdb->get_results(
								"
								SELECT ml.name, COUNT(mu.id) as num_members
								FROM $wpdb->pmpro_membership_levels ml
								LEFT JOIN $wpdb->pmpro_memberships_users mu
								ON ml.id = mu.membership_id
								WHERE mu.status = 'active'
								GROUP BY ml.name
								ORDER BY num_members DESC
								"
							);

							$num_levels_to_show = 5;
							if ( count( $members_per_level ) > $num_levels_to_show ) {
								echo( '<p>' . sprintf( __( 'Here is a summary of your top %s most popular levels:</p>', 'paid-memberships-pro' ), esc_html( $num_levels_to_show ) ) . '</p>' );
							}
							?>
							<ul>
							<?php
							$levels_outputted = 0;
							foreach ( $members_per_level as $members_per_level_element ) {
								echo( '<li>' . esc_html( $members_per_level_element->name ) . ': {' . esc_html( $members_per_level_element->num_members ) . '}</li>' );
								if ( ++$levels_outputted >= $num_levels_to_show ) {
									break;
								}
							}
							?>
							</ul>
							<p style="margin:0px;padding:0px;"><a style="color:#2997c8;" href="<?php echo( esc_url( admin_url( 'admin.php?page=pmpro-reports&report=memberships' ) ) ); ?>" target="_blank"><?php esc_html_e( 'View Signups and Cancellations Report &raquo;', 'paid-memberships-pro' ); ?></a></p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:left;">
							<div style="border: 8px dashed #EFEFEF;padding:30px;margin: 0px 0px 30px 0px;text-align:center;">
								<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'Discount Code Usage', 'paid-memberships-pro' ); ?></h3>
								<?php
								$num_orders_with_discount_code  = $wpdb->get_var( "SELECT COUNT( * ) FROM {$wpdb->pmpro_discount_codes_uses} WHERE timestamp >= '" . esc_sql( $report_start_date ) . " 00:00:00' AND timestamp <= '" . esc_sql( $report_end_date ) . " 00:00:00'" );
								if ( $num_orders_with_discount_code > 0 ) {
									$orders_per_discount_code = $wpdb->get_results(
										"
											SELECT dc.code, COUNT(dcu.id) as uses
											FROM $wpdb->pmpro_discount_codes dc
											LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
											ON dc.id = dcu.code_id
											WHERE dcu.timestamp >= '" . esc_sql( $report_start_date ) . " 00:00:00'
											AND dcu.timestamp <= '" . esc_sql( $report_end_date ) . " 00:00:00'
											GROUP BY dc.code
											ORDER BY uses DESC
										"
									);
									?>
									<p style="margin:0px 0px 15px 0px;padding:0;"><?php printf( __( '<strong>%1$d orders</strong> used a <a %2$s>Discount Code</a> at checkout. Here is a breakdown of your most used codes:', 'paid-memberships-pro' ), esc_html( $num_orders_with_discount_code ), 'style="color:#2997c8;" target="_blank" href="' . esc_url( admin_url( 'admin.php?page=pmpro-discountcodes' ) ) . '"' ); ?></p>
										<?php
										$codes_left_to_show = 5;
										foreach ( $orders_per_discount_code as $orders_per_discount_code_element ) {
											if ( $codes_left_to_show <= 0 || $orders_per_discount_code_element->uses <= 0 ) {
												break;
											}
											echo( '<p style="margin:0px 0px 15px 0px;padding:0;"><span style="background-color:#fcf8e3;font-weight:900;padding:5px;">' . esc_html( $orders_per_discount_code_element->code ) . '</span> ' . esc_html( $orders_per_discount_code_element->uses ) . ' ' . esc_html( __( 'Orders', 'paid-memberships-pro' ) ) . '</p>' );
											$codes_left_to_show--;
										}
								} else {
									?>
									<p style="margin:0px 0px 15px 0px;padding:0;"><?php printf( __( 'No <a %1$s>Discount Codes</a> were used %2$s.', 'paid-memberships-pro' ), 'style="color:#2997c8;" target="_blank" href="' . esc_url( admin_url( 'admin.php?page=pmpro-discountcodes' ) ) . '"', esc_html( $term ) ); ?></p>
									<?php
								}
								?>
							</div>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #EFEFEF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:center;">
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'Active Add Ons', 'paid-memberships-pro' ); ?></h3>
							<table align="center" border="0" cellpadding="0" cellspacing="15" width="100%" style="border:0;background-color:#EFEFEF;text-align: center;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height: 25px;color:#444444;">
								<tr>
									<?php
									// Get addon statistics.
									$free_addons   = 0;
									$plus_addons   = 0;
									$update_addons = 0;
									require_once( PMPRO_DIR . '/includes/addons.php' );
									$addons        = pmpro_getAddons();
									$plugin_info   = get_site_transient( 'update_plugins' );
									foreach ( $addons as $addon ) {
										$plugin_file     = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
										$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;
										include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); // To load is_plugin_active().
										if ( is_plugin_active( $plugin_file ) ) {
											if ( 'plus' === $addon['License'] ) {
												$plus_addons++;
											} else {
												$free_addons++;
											}
										}
										if ( isset( $plugin_info->response[ $plugin_file ] ) ) {
											$update_addons++;
										}
									}
									?>
									<td width="33%" style="background:#FFFFFF;padding:10px;"><div style="font-size:50px;font-weight:900;line-height:65px;"><?php echo( esc_html( $free_addons ) ); ?></div><?php esc_html_e( 'Free Add Ons', 'paid-memberships-pro' ); ?></td>
									<td width="33%" style="background:#FFFFFF;padding:10px;"><div style="font-size:50px;font-weight:900;line-height:65px;"><?php echo( esc_html( $plus_addons ) ); ?></div><?php esc_html_e( 'Plus Add Ons', 'paid-memberships-pro' ); ?></td>
									<td width="33%" style="background:#f2dede;padding:10px;"><a style="color:#a94442;display:block;text-decoration: none;" href="<?php echo( esc_url( admin_url( 'admin.php?page=pmpro-addons&plugin_status=update' ) ) ); ?>" target="_blank"><div style="font-size:50px;font-weight:900;line-height:65px;"><?php echo( esc_html( $update_addons ) ); ?></div><?php esc_html_e( 'Required Updates', 'paid-memberships-pro' ); ?></a></td>
								</tr>
							</table>
							<p style="margin:0px;padding:0px;"><?php printf( __( 'It is important to keep all Add Ons up to date to take advantage of security improvements, bug fixes, and expanded features. Add On updates can be made <a href="%s" target="_blank">via the WordPress Dashboard</a>.', 'paid-memberships-pro' ), esc_url( admin_url( 'update-core.php' ) ) ); ?></p>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding: 30px;text-align:left;">
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'Membership Site Administration', 'paid-memberships-pro' ); ?></h3>
							<ul>
								<?php
								$roles_to_list = array(
									'administrator' => __( 'Administrators', 'paid-memberships-pro' ),
									'pmpro_membership_manager' => __( 'Membership Managers', 'paid-memberships-pro' ),
								);
								foreach ( $roles_to_list as $role => $role_name ) {
									$users_with_role = get_users(
										array(
											'role' => $role,
										)
									);
									if ( 0 < count( $users_with_role ) ) {
										echo( '<li>' . count( $users_with_role ) . ' ' . esc_html( $role_name ) . ': ' );
										$users_with_role_formatted = array();
										foreach ( $users_with_role as $user_with_role ) {
											$users_with_role_formatted[] = '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_with_role->ID ) . '">' . $user_with_role->data->user_login . '</a>';
										}
										echo( implode( ', ', $users_with_role_formatted ) );
									}
								}
								?>
							</ul>
							<p style="margin:0px;padding:0px;"><?php esc_html_e( 'Note: It is important to review users with access to your membership site data since they control settings and can modify member accounts.', 'paid-memberships-pro' ); ?></p>

							<?php
							$key = get_option( 'pmpro_license_key', '' );
							if ( ! pmpro_license_isValid( $key, null ) ) {
								?>
							<hr style="background-color: #EFEFEF;border: 0;height: 4px;margin: 30px 0px 30px 0px;" />
							<!--Show section below only if there is no license key. -->
							<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'License Status: None', 'paid-memberships-pro' ); ?></h3> 
							<p style="margin:0px;padding:0px;"><?php printf( __( '...and that is perfectly OK! PMPro is free to use for as long as you want for membership sites of all sizes. Interested in unlimited support, access to over 70 featured-enhancing Add Ons and instant installs and updates? <a %s>Check out our paid plans to learn more</a>.', 'paid-memberships-pro' ), ' style="color:#2997c8;" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-admin-activity-email&utm_campaign=pricing&utm_content=license-section" target="_blank"' ); ?></p>
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background-color:#FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#444444;padding:0;text-align:left;">
							<table align="center" border="0" cellpadding="0" cellspacing="10" width="100%" style="border:0;background-color:#FFFFFF;text-align:left;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height: 25px;color:#444444;">
								<tr valign="top">
									<td width="60%" style="background-color:#EFEFEF;padding:15px;">
										<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;">Recent Articles</h3>
										<!-- look to this for example: https://github.com/strangerstudios/paid-memberships-pro/blob/dev/adminpages/dashboard.php#L351-L354; show max 1 time per week? if daily, exclude it? show one? needs discussion -->
										<!-- Sample links had href="#/?utm_source=plugin&utm_medium=pmpro-admin-activity-email&utm_campaign=blog&utm_content=recent-articles-section", are these params important? -->
										<!--Pull in via RSS Feed from specific category on our blog. Last 3? How do we make sure there isn’t the same thing sent twice? Could this be dynamic and we choose to occasionally send something different? -->
									<?php
									// Get RSS Feed(s).
									include_once ABSPATH . WPINC . '/feed.php';

									// Get a SimplePie feed object from the specified feed source.
									$rss       = fetch_feed( 'https://www.paidmembershipspro.com/feed/' );
									$max_items = 0;
									if ( ! is_wp_error( $rss ) ) { // Checks that the object is created correctly
											// Figure out how many total items there are, but limit it to 3.
											$max_items = $rss->get_item_quantity( 3 );
											// Build an array of all the items, starting with element 0 (first element).
											$rss_items = $rss->get_items( 0, $max_items );
									}
									if ( $max_items <= 0 ) {
										echo( '<p style="margin:0px 0px 15px 0px;padding:0;">' . __( 'No news found.', 'paid-memberships-pro' ) . '</p>' );
									} else {
										foreach ( $rss_items as $item ) {
											echo( '<p style="margin:0px 0px 15px 0px;padding:0;"><a style="color:#2997c8;" href=" ' . esc_url( $item->get_permalink() ) . ' " target="_blank">' . esc_html( $item->get_title() ) . '</a> ' . esc_html( $item->get_date( get_option( 'date_format' ) ) ) . '</p>' );
										}
									}
									?>
									</td>
									<td width="40%" style="background-color:#EFEFEF;padding:15px;"> 
										<h3 style="color:#2997c8;font-size: 20px;line-height: 30px;margin:0px 0px 15px 0px;padding:0px;"><?php _e( 'PMPro Stats', 'paid-memberships-pro' ); ?></h3>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><?php printf( __( '%s Sites Use PMPro', 'paid-memberships-pro' ), '<strong>80,000</strong>' ); ?></p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><?php printf( __( '%s Years in Development', 'paid-memberships-pro' ), '<strong>8+</strong>' ); ?></p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><a style="color:#2997c8;" href="https://twitter.com/pmproplugin" target="_blank"><?php esc_html_e( 'Follow @pmproplugin on Twitter', 'paid-memberships-pro' ); ?></a></p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><a style="color:#2997c8;" href="https://www.facebook.com/PaidMembershipsPro/" target="_blank"><?php esc_html_e( 'Like Us on Facebook', 'paid-memberships-pro' ); ?></p></p>
										<p style="margin:0px 0px 15px 0px;padding:0px;"><a style="color:#2997c8;" href="https://www.youtube.com/user/strangerstudiostv" target="_blank"><?php esc_html_e( 'Subscribe to Our YouTube Channel', 'paid-memberships-pro' ); ?></a></p>
										<!-- Show Plus signups count? Rating? -->
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background: #333333;font-family:Helvetica,Arial,sans-serif;font-size:20px;line-height:30px;color:#FFFFFF;padding: 30px;text-align:center;">
							<p style="margin:0px 0px 15px 0px;padding:0px;"><?php esc_html_e( 'This email is automatically generated by your WordPress site and sent to your Administration Email Address set under Settings > General in your WordPress dashboard.', 'paid-memberships-pro' ); ?></p>
							<p style="margin:0px;padding:0px;"><?php printf( __( 'To adjust the frequency of this message or disable these emails completely, you can <a %s>update the "Activity Email Frequency" setting here</a>.', 'paid-memberships-pro' ), ' style="color:#FFFFFF;" href="' . admin_url( 'admin.php?page=pmpro-advancedsettings' ) . '" target="_blank"' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</center>
		</div>
		<?php

		$admin_activity_email_body = ob_get_contents();

		ob_end_clean();

		$this->email    = get_bloginfo( 'admin_email' );
		$this->subject  = sprintf( __( '[%1$s] Paid Memberships Pro Activity for %2$s - %3$s', 'paid-memberships-pro' ), get_bloginfo( 'name' ), $term, $date_range );
		$this->template = 'admin_activity_email';
		$this->body     = $admin_activity_email_body;
		$this->from     = pmpro_getOption( 'from' );
		$this->fromname = pmpro_getOption( 'from_name' );
		return $this->sendEmail();
	}

}
PMPro_Admin_Activity_Email::get_instance();
