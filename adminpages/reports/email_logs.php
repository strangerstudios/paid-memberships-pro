<?php
/*
	PMPro Report
	Title: Email Logs
	Slug: email_logs

	For each report, write three functions:
	* pmpro_report_{slug}_register() to register the widget (slug and title).
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/
function pmpro_report_email_logs_register( $pmpro_reports ) {
	// If email logging is disabled, do not register the report.
	$email_logging_disabled = get_option( 'pmpro_email_logging_disabled' );
	if ( ! empty( $email_logging_disabled ) ) {
		return $pmpro_reports;
	}

	$pmpro_reports['email_logs'] = __( 'Email Logs', 'paid-memberships-pro' );
	return $pmpro_reports;
}
add_filter( 'pmpro_registered_reports', 'pmpro_report_email_logs_register' );

/**
 * Widget display for email logs report
 */
function pmpro_report_email_logs_widget() {
	global $wpdb;
	
	// Get total emails sent in last 30 days
	$thirty_days_ago = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
	$total_sent = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->pmpro_email_log} 
			 WHERE status = 'sent' AND timestamp >= %s",
			$thirty_days_ago
		)
	);
	
	// Get total failed in last 30 days
	$total_failed = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->pmpro_email_log} 
			 WHERE status = 'failed' AND timestamp >= %s",
			$thirty_days_ago
		)
	);
	
	?>
	<div class="pmpro_report-holder">
		<p><?php printf( __( '%s emails logged in the last 30 days', 'paid-memberships-pro' ), number_format_i18n( $total_sent ) ); ?></p>
		<?php if ( $total_failed > 0 ) { ?>
			<p class="pmpro_error"><?php printf( __( '%s emails failed in the last 30 days', 'paid-memberships-pro' ), number_format_i18n( $total_failed ) ); ?></p>
		<?php } ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=email_logs' ) ); ?>">
			<?php esc_html_e( 'View Full Report', 'paid-memberships-pro' ); ?>
		</a>
	</div>
	<?php
}

/**
 * Main page display for email logs report
 */
function pmpro_report_email_logs_page() {
	global $wpdb;
	
	// Get search parameter
	$s = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
	
	// Get template filter
	$template_filter = isset( $_REQUEST['template'] ) ? sanitize_text_field( $_REQUEST['template'] ) : '';
	
	// Get status filter
	$status_filter = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
	
	// Pagination
	$pn = isset( $_REQUEST['pn'] ) ? intval( $_REQUEST['pn'] ) : 1;
	$limit = isset( $_REQUEST['limit'] ) ? intval( $_REQUEST['limit'] ) : 15;
	$end = $pn * $limit;
	$start = $end - $limit;
	
	// Build the query
	$where_clauses = array( '1=1' );
	$where_values = array();
	
	// Search filter
	if ( ! empty( $s ) ) {
		// Check if a full email address or login name was provided.
		$user = get_user_by( 'login', $s );
		if ( ! $user ) {
			$user = get_user_by( 'email', $s );
		}

		$where_clauses[] = "(email_to LIKE %s OR subject LIKE %s " . ( $user ? "OR user_id = %d" : "" ) . ")";
		$search_term = '%' . $wpdb->esc_like( $s ) . '%';
		$where_values[] = $search_term;
		$where_values[] = $search_term;
		if ( $user ) {
			$where_values[] = $user->ID;
		}
	}
	
	// Template filter
	if ( ! empty( $template_filter ) && $template_filter !== 'all' ) {
		$where_clauses[] = "template = %s";
		$where_values[] = $template_filter;
	}
	
	// Status filter
	if ( ! empty( $status_filter ) && $status_filter !== 'all' ) {
		$where_clauses[] = "status = %s";
		$where_values[] = $status_filter;
	}
	
	$where_sql = implode( ' AND ', $where_clauses );
	
	// Get total count
	$count_query = "SELECT COUNT(*) FROM {$wpdb->pmpro_email_log} WHERE {$where_sql}";
	if ( ! empty( $where_values ) ) {
		$count_query = $wpdb->prepare( $count_query, $where_values );
	}
	$totalrows = $wpdb->get_var( $count_query );
	
	// Get logs with pagination
	$logs_query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->pmpro_email_log} 
				   WHERE {$where_sql} 
				   ORDER BY timestamp DESC 
				   LIMIT %d, %d";
	
	$query_values = array_merge( $where_values, array( $start, $limit ) );
	$logs = $wpdb->get_results( $wpdb->prepare( $logs_query, $query_values ) );
	
	// Get available templates for filter dropdown
	$templates = $wpdb->get_col( 
		"SELECT DISTINCT template FROM {$wpdb->pmpro_email_log} 
		 WHERE template != '' 
		 ORDER BY template ASC" 
	);
	
	?>
	<form id="email-logs-form" method="get" action="">
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Email Logs', 'paid-memberships-pro' ); ?>
		</h1>
		<?php if ( current_user_can( 'manage_options' ) ) { ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailsettings#email-logging-settings' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Email Settings', 'paid-memberships-pro' ); ?>
			</a>
		<?php } ?>
		
		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input">
				<?php esc_html_e( 'Search Email Logs', 'paid-memberships-pro' ); ?>:
			</label>
			<input type="hidden" name="page" value="pmpro-reports" />
			<input type="hidden" name="report" value="email_logs" />
			<input id="post-search-input" type="text" value="<?php echo esc_attr( $s ); ?>" name="s" placeholder="<?php esc_attr_e( 'Search by email, user, or subject.', 'paid-memberships-pro' ); ?>" />
			<input class="button" type="submit" value="<?php esc_attr_e( 'Search', 'paid-memberships-pro' ); ?>" />
		</p>
		
		<div class="pmpro_report-filters">
			<h3><?php esc_html_e( 'Customize Report', 'paid-memberships-pro' ); ?></h3>
			<div class="tablenav top">
				<label for="template" class="pmpro_report-filter-text">
					<?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?>
				</label>
				<select id="template" name="template" onchange="jQuery('#email-logs-form').trigger('submit');">
					<option value="all" <?php selected( $template_filter, 'all' ); ?>>
						<?php esc_html_e( 'All Templates', 'paid-memberships-pro' ); ?>
					</option>
					<?php foreach ( $templates as $template ) { ?>
						<option value="<?php echo esc_attr( $template ); ?>" <?php selected( $template_filter, $template ); ?>>
							<?php echo esc_html( ucwords( str_replace( array( '_', '-' ), ' ', $template ) ) ); ?>
						</option>
					<?php } ?>
				</select>
				
				<label for="status" class="pmpro_report-filter-text">
					<?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?>
				</label>
				<select id="status" name="status" onchange="jQuery('#email-logs-form').trigger('submit');">
					<option value="all" <?php selected( $status_filter, 'all' ); ?>>
						<?php esc_html_e( 'All Statuses', 'paid-memberships-pro' ); ?>
					</option>
					<option value="sent" <?php selected( $status_filter, 'sent' ); ?>>
						<?php esc_html_e( 'Sent', 'paid-memberships-pro' ); ?>
					</option>
					<option value="failed" <?php selected( $status_filter, 'failed' ); ?>>
						<?php esc_html_e( 'Failed', 'paid-memberships-pro' ); ?>
					</option>
				</select>
			</div>
		</div>
		
		<?php if ( $logs ) { ?>
			<div class="tablenav top">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php printf( esc_html( _n( '%s email', '%s emails', $totalrows, 'paid-memberships-pro' ) ), number_format_i18n( $totalrows ) ); ?>
					</span>
				</div>
				<br class="clear" />
			</div>
			
			<table id="pmpro_report_email_logs" class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date/Time', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'User', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'paid-memberships-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) { 
						$user = ! empty( $log->user_id ) ? get_userdata( $log->user_id ) : null;
						?>
						<tr>
							<td>
								<?php 
								echo esc_html( 
									date_i18n( 
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), 
										strtotime( $log->timestamp ) 
									) 
								); 
								?>
							</td>
							<td><?php echo esc_html( $log->email_to ); ?></td>
							<td>
								<?php 
								if ( $user ) {
									// Link to the "email templates" panel of the member edit screen.
									echo '<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-member&user_id=' . $user->ID ) ) . '">';
									echo esc_html( $user->user_login );
									echo '</a>';
								} else {
									echo '—';
								}
								?>
							</td>
							<td><?php echo esc_html( $log->subject ); ?></td>
							<td>
								<?php 
								if ( ! empty( $log->template ) ) {
									echo esc_html( ucwords( str_replace( array( '_', '-' ), ' ', $log->template ) ) );
								} else {
									echo '—';
								}
								?>
							</td>
							<td>
								<?php if ( $log->status === 'sent' ) { ?>
									<span style="color: green;">✓ <?php esc_html_e( 'Sent', 'paid-memberships-pro' ); ?></span>
								<?php } else { ?>
									<span style="color: red;">✗ <?php esc_html_e( 'Failed', 'paid-memberships-pro' ); ?></span>
								<?php } ?>
							</td>
							<td>
								<a href="#" class="pmpro-view-email-log" data-log-id="<?php echo esc_attr( $log->id ); ?>">
									<?php esc_html_e( 'View', 'paid-memberships-pro' ); ?>
								</a>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			
			<?php
			// Pagination
			echo wp_kses_post( 
				pmpro_getPaginationString( 
					$pn, 
					$totalrows, 
					$limit, 
					1, 
					admin_url( "admin.php?page=pmpro-reports&report=email_logs&s=" . urlencode( $s ) ), 
					"&template=" . urlencode( $template_filter ) . "&status=" . urlencode( $status_filter ) . "&limit=" . $limit . "&pn=", 
					__( 'Email Logs Pagination', 'paid-memberships-pro' ) 
				) 
			);
			?>
		<?php } else { ?>
			<p><?php esc_html_e( 'No email logs found.', 'paid-memberships-pro' ); ?></p>
		<?php } ?>
	</form>

	<?php
	// Render the email log modal
	echo pmpro_render_email_log_modal();
	?>

	<?php
}
