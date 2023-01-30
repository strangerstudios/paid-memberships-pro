<?php
/**
 * Add a widget to the dashboard.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'pmpro_dashboard_report_recent_members',
		__( 'Recent Members', 'paid-memberships-pro' ),
		'pmpro_dashboard_report_recent_members_callback',
		'toplevel_page_pmpro-dashboard',
		'side'
	);
} );

/**
 * Callback function for pmpro_dashboard_report_recent_members meta box to show last 5 recent members and a link to the Members List.
 */
function pmpro_dashboard_report_recent_members_callback() {
	global $wpdb;

	$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP( CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone) ) as startdate, UNIX_TIMESTAMP( CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone) ) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE mu.membership_id > 0 AND mu.status = 'active' GROUP BY u.ID ORDER BY u.user_registered DESC LIMIT 5";

	$sqlQuery = apply_filters( 'pmpro_members_list_sql', $sqlQuery );

	$theusers = $wpdb->get_results( $sqlQuery ); ?>
    <span id="pmpro_report_members" class="pmpro_report-holder">
    	<table class="wp-list-table widefat fixed striped">
    		<thead>
    			<tr>
    				<th><?php esc_html_e( 'Username', 'paid-memberships-pro' ); ?></th>
    				<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
    				<th><?php esc_html_e( 'Joined', 'paid-memberships-pro' ); ?></th>
    				<th><?php esc_html_e( 'Expires', 'paid-memberships-pro' ); ?></th>
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
				    $theuser = get_userdata( $auser->ID ); ?>
                    <tr>
    					<td class="username column-username">
    						<?php echo get_avatar( $theuser->ID, 32 ) ?>
    						<strong>
    							<?php
							    $userlink = '<a href="' . get_edit_user_link( $theuser->ID ) . '">' . esc_attr( $theuser->user_login ) . '</a>';
							    $userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, $theuser );
							    echo $userlink;
							    ?>
    						</strong>
    					</td>
    					<td><?php echo esc_html( $auser->membership ); ?></td>
    					<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $theuser->user_registered ), current_time( 'timestamp' ) ) ); ?></td>
    					<td>
    						<?php
						    if ( $auser->enddate ) {
							    echo apply_filters( "pmpro_memberslist_expires_column", date_i18n( get_option( 'date_format' ), $auser->enddate ), $auser );
						    } else {
							    echo __( apply_filters( "pmpro_memberslist_expires_column", "Never", $auser ), "pmpro" );
						    }
						    ?>
    					</td>
    				</tr>
				    <?php
			    }
		    }
		    ?>
    		</tbody>
    	</table>
    </span>
	<?php if ( ! empty( $theusers ) ) { ?>
        <p class="text-center"><a class="button button-primary"
                                  href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-memberslist' ) ); ?>"><?php esc_html_e( 'View All Members ', 'paid-memberships-pro' ); ?></a>
        </p>
	<?php } ?>
	<?php
}

