<?php
/**
 * Paid Memberships Pro Dashboard Recent Members Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 2.6.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_report_recent_members_callback() {
	global $wpdb;

	// Check if we have a cache.
	if ( false === ( $theusers = get_transient( 'pmpro_dashboard_report_recent_members' ) ) ) {
		// No cached value. Get the users.
		$sqlQuery = "SELECT
			u.ID,
			u.user_login,
			u.user_email,
			UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate,
			mu.membership_id,
			mu.initial_payment,
			mu.billing_amount,
			mu.cycle_period,
			mu.cycle_number,
			mu.billing_limit,
			mu.trial_amount,
			mu.trial_limit,
			UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate,
			UNIX_TIMESTAMP(CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone)) as enddate,
			m.name as membership
		FROM $wpdb->users u
		INNER JOIN $wpdb->pmpro_memberships_users mu
			ON u.ID = mu.user_id
			AND mu.status = 'active'
		INNER JOIN $wpdb->pmpro_membership_levels m
			ON mu.membership_id = m.id
		INNER JOIN (
			SELECT user_id, MAX(startdate) AS max_startdate
			FROM $wpdb->pmpro_memberships_users
			WHERE status = 'active'
			GROUP BY user_id
		) mu2 ON mu.user_id = mu2.user_id AND mu.startdate = mu2.max_startdate
		ORDER BY u.user_registered DESC
		LIMIT 5";
		$sqlQuery = apply_filters( 'pmpro_members_list_sql', $sqlQuery );
		$theusers = $wpdb->get_results( $sqlQuery );
		set_transient( 'pmpro_dashboard_report_recent_members', $theusers, 12 * HOUR_IN_SECONDS ); 
	}
	?>
	<span id="pmpro_report_members" class="pmpro_report-holder">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th colspan="2"><?php esc_html_e( 'Username', 'paid-memberships-pro' );?></th>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' );?></th>
					<th><?php esc_html_e( 'Joined', 'paid-memberships-pro' );?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $theusers ) ) { ?>
				<tr>
					<td colspan="4"><p><?php esc_html_e( 'No members found.', 'paid-memberships-pro' ); ?></p></td>
				</tr>
			<?php } else {
				foreach ( $theusers as $auser ) {
					$auser = apply_filters( 'pmpro_members_list_user', $auser );
					//get meta
					$theuser = get_userdata( $auser->ID ); 
					
					// Lets check again if the user exists as it may be pulling "old data" from the transient.
					if ( ! isset( $theuser->ID ) ) {
						continue;
					}
					?>
					<tr>
						<td class="username column-username" colspan="2">
							<?php echo get_avatar($theuser->ID, 32)?>
							<strong>
								<?php
									$userlink = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $theuser->ID ), admin_url( 'admin.php' ) ) ) . '">' . esc_attr( $theuser->user_login ) . '</a>';
									$userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, $theuser );
									echo wp_kses_post( $userlink );
								?>
							</strong>
						</td>
						<td><?php echo esc_html( $auser->membership ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $theuser->user_registered ), current_time( 'timestamp' ) ) ) ); ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</span>
	<?php
}