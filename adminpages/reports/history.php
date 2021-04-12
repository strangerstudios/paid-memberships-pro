<?php

global $pmpro_reports;
$pmpro_reports[ 'member_value_history' ] = __( 'Member Value Report', 'paid-memberships-pro' );
function pmpro_report_member_value_history_widget() {	
	global $wpdb;
	
	$top_ten_members = get_transient( 'pmpro_member_history_top_ten_members', false );
	
	if ( empty( $top_ten_members ) ) {
		$sqlQuery = $wpdb->prepare("
			SELECT user_id, SUM(total) as totalvalue
			FROM $wpdb->pmpro_membership_orders
			WHERE membership_id > 0
				AND gateway_environment = %s
				AND status NOT IN('token','review','pending','error','refunded')
			GROUP BY user_id ORDER BY totalvalue DESC
			LIMIT 10
			", pmpro_getOption( 'gateway_environment' ) );
		$top_ten_members = $wpdb->get_results( $sqlQuery );
		set_transient( 'pmpro_member_history_top_ten_members', $top_ten_members, 3600 );
	}
	
	if ( empty ( $top_ten_members ) ) {
		esc_html_e( 'No paying members found.', 'paid-memberships-pro' );
	} else {
		esc_html_e( 'Your Top 10 Members', 'paid-memberships-pro' );
		?>
		<span id="pmpro_report_member_value" class="pmpro_report-holder">
		<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Member', 'paid-memberships-pro' ) ;?></th>
				<th scope="col"><?php esc_html_e( 'Start Date', 'paid-memberships-pro' ) ;?></th>
				<th scope="col"><?php esc_html_e( 'Total Value', 'paid-memberships-pro' ) ;?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
				foreach ( $top_ten_members as $member ) {
					$totalvalue = $member->totalvalue;
					$theuser = get_userdata( $member->user_id );
					?>
					<tr>
						<th scope="row">
							<?php if ( ! empty( $theuser ) ) { ?>
								<a title="<?php esc_html_e( 'Edit User', 'paid-memberships-pro' ); ?>" href="<?php echo get_edit_user_link( $theuser->ID ); ?>"><?php echo $theuser->display_name; ?></a>
							<?php } elseif ( $member->user_id > 0 ) { ?>
								[<?php _e( 'deleted', 'paid-memberships-pro' ); ?>]
							<?php } else { ?>
								[<?php _e( 'none', 'paid-memberships-pro' ); ?>]
							<?php } ?>
						</th>
						<th>
							<?php if ( ! empty( $theuser ) ) { ?>
								<?php echo date_i18n( get_option( 'date_format' ), strtotime( $theuser->user_registered, current_time( 'timestamp' ) ) ); ?>
							<?php } else { ?>
								-
							<?php } ?>
						</th>
						<th><?php echo pmpro_formatPrice( $totalvalue ); ?></th>
					</tr>
					<?php
				}
			?>
		</tbody>
		</table>
		<?php if ( function_exists( 'pmpro_report_member_value_history_page' ) ) { ?>
			<p class="pmpro_report-button">
				<a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=pmpro-reports&report=member_value_history' ); ?>"><?php esc_html_e('Details', 'paid-memberships-pro' );?></a>
			</p>
		<?php } ?>
		</span>
		<?php
	}
}

/**
 * Display a custom report for Member Value.
 *
 */
function pmpro_report_member_value_history_page() {
?>
<h2><?php esc_html_e( 'Member Value Report', 'paid-memberships-pro'); ?></h2>
<?php
	//vars
	global $wpdb;
	if ( isset( $_REQUEST['s'] ) ) {
		$s = sanitize_text_field( trim( $_REQUEST['s'] ) );
	} else {
		$s = '';
	}

	if ( isset( $_REQUEST['l'] ) ) {
		$l = sanitize_text_field( $_REQUEST['l'] );
	} else {
		$l = false;
	}
?>
	<form id="posts-filter" method="get" action="">
	<ul class="subsubsub">
		<li>
			<?php _e( 'Show', 'paid-memberships-pro') ;?>
			<select name="l" onchange="jQuery( '#posts-filter' ).submit();">
				<option value="" <?php if( ! $l ) { ?>selected="selected"<?php } ?>><?php _e( 'All Levels', 'paid-memberships-pro'); ?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach( $levels as $level ) { ?>
						<option value="<?php echo $level->id; ?>" <?php if ( $l == $level->id ) { ?>selected="selected"<?php } ?>><?php echo esc_html( $level->name ); ?></option>
					<?php } ?>
			</select>
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input"><?php _e( 'Search Members', 'paid-memberships-pro' );?>:</label>
		<input type="hidden" name="page" value="pmpro-reports" />
		<input type="hidden" name="report" value="member_value_history" />
		<input id="post-search-input" type="text" value="<?php echo esc_attr( $s ); ?>" name="s" />
		<input class="button" type="submit" value="<?php esc_html_e( 'Search Members', 'paid-memberships-pro' ); ?>" />
	</p>
	<?php
		//some vars for the search
		if ( isset( $_REQUEST['pn'] ) ) {
			$pn = intval( $_REQUEST['pn'] );
		} else {
			$pn = 1;
		}

		if ( isset( $_REQUEST['limit'] ) ) {
			$limit = intval( $_REQUEST['limit'] );
		} else {
			/**
			 * Filter to set the default number of items to show per page
			 * on the Members List page in the admin.
			 *
			 * @since 1.8.4.5
			 *
			 * @param int $limit The number of items to show per page.
			 */
			$limit = apply_filters( 'pmpro_memberslist_per_page', 15 );
		}

		$end = $pn * $limit;
		$start = $end - $limit;

		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership, SUM(mo.total) as totalvalue FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id LEFT JOIN $wpdb->pmpro_membership_orders mo ON u.ID = mo.user_id LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id";

		if ( $s ) {
			$sqlQuery .= " WHERE mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%') ";
		} else {
			$sqlQuery .= " WHERE mu.membership_id > 0  ";
		}
		

		if( $l ) {
			$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql( $l ) . "' ";
		} else {
			$sqlQuery .= " AND mu.status = 'active' ";
		}
		
		$sqlQuery .= " AND mo.gateway_environment = '" . pmpro_getOption( 'gateway_environment' ) . "' ";
		$sqlQuery .= " AND mo.status NOT IN('token','review','pending','error','refunded') ";
		
		$sqlQuery .= "GROUP BY u.ID ORDER BY totalvalue DESC ";

		$sqlQuery .= "LIMIT $start, $limit";

		$sqlQuery = apply_filters( 'pmpro_members_list_sql', $sqlQuery );

		$theusers = $wpdb->get_results( $sqlQuery );

		// var_dump( $theusers );

		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");
		
		if ( $theusers ) { ?>
			<p class="clear"><?php printf(__( '%d members found.', 'paid-memberships-pro' ), $totalrows ); ?></span></p>
		<?php } ?>
	<table class="widefat striped">
		<thead>
			<tr class="thead">
				<th><?php esc_html_e( 'ID', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Username', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Name', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Current Membership', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Joined', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'paid-memberships-pro' ); ?></th>
				<th><?php esc_html_e( 'Total Paid', 'paid-memberships-pro' ); ?></th>
			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">
			<?php
				foreach( $theusers as $auser ) {
					//get meta
					$theuser = get_userdata( $auser->ID );
					
					//get total value
					$totalvalue2 = $wpdb->get_var("SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = '$auser->ID' AND status NOT IN('review','pending','error','refunded')");
					?>
						<tr>
							<td><?php echo esc_html( $theuser->ID ); ?></td>
							<td class="username column-username">
								<?php echo get_avatar( $theuser->ID, 32 ); ?>
								<strong>
									<?php
										$userlink = '<a href="user-edit.php?user_id=' . esc_attr( $theuser->ID ) . '">' . esc_html( $theuser->user_login ) . '</a>';
										$userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, $theuser );
										echo $userlink;
									?>
								</strong>
							</td>
							<td>
								<?php echo esc_html( $theuser->first_name ); ?> <?php echo esc_html( $theuser->last_name ); ?>
								<?php if ( ! empty( $theuser->first_name ) ) echo '<br />'; ?>
								<a href="mailto:<?php echo esc_attr( $theuser->user_email ); ?>"><?php echo $theuser->user_email; ?></a>
							</td>
							<td><?php echo esc_html( $auser->membership ); ?></td>
							<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $theuser->user_registered, current_time( 'timestamp' ) ) ); ?></td>
							<td>
								<?php
									if ( $auser->enddate ) {
										echo apply_filters( 'pmpro_memberslist_expires_column', date_i18n( get_option( 'date_format' ), $auser->enddate ), $auser );
									} else {
										echo __( apply_filters( 'pmpro_memberslist_expires_column', 'Never', $auser ), 'paid-memberships-pro' );
									} ?>
							</td>
							<td>
								<?php echo pmpro_formatPrice( $totalvalue2 ); ?>
							</td>
						</tr>
					<?php
				}

				if( ! $theusers ) { ?>
				<tr>
					<td colspan="9"><p><?php esc_html_e( 'No members found.', 'paid-memberships-pro'); ?> <?php if( $l ) { ?><a href="?page=pmpro-reports&report=member_value&s=<?php echo esc_attr( $s );?>"><?php _e( 'Search all levels', 'paid-memberships-pro' ); ?></a>.<?php } ?></p></td>
				</tr>
				<?php } ?>
		</tbody>
	</table>
	</form>

	<?php
	echo pmpro_getPaginationString( $pn, $totalrows, $limit, 1, add_query_arg( array( 's' => urlencode( $s ), 'l' => $l, 'limit' => $limit ) ) );
	?>

<?php
}