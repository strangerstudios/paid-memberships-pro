<?php
/*
	PMPro Report
	Title: Email Log
	Slug: email_log

	For each report, write three functions:
	* pmpro_report_{slug}_register() to register the widget (slug and title).
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/
function pmpro_report_email_log_register( $pmpro_reports ) {
	// If email logging is disabled, do not register the report.
	$email_logging_disabled = get_option( 'pmpro_email_logging_disabled' );
	if ( ! empty( $email_logging_disabled ) ) {
		return $pmpro_reports;
	}

	$pmpro_reports['email_log'] = __( 'Email Log', 'paid-memberships-pro' );
	return $pmpro_reports;
}
add_filter( 'pmpro_registered_reports', 'pmpro_report_email_log_register' );

/**
 * Widget display for email log report
 */
function pmpro_report_email_log_widget() {
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

	// Get the last successful email log entry.
	$last_sent_log = $wpdb->get_row(
		"SELECT * FROM {$wpdb->pmpro_email_log} 
		 WHERE status = 'sent' 
		 ORDER BY timestamp DESC 
		 LIMIT 1"
	);

	// If there is a failed email log entry, get the last one.
	$last_failed_log = $wpdb->get_row(
		"SELECT * FROM {$wpdb->pmpro_email_log} 
		 WHERE status = 'failed' 
		 ORDER BY timestamp DESC 
		 LIMIT 1"
	);

	$email_log_purge_days = get_option( 'pmpro_email_log_purge_days', 90 );	
	?>
	<div class="pmpro_report-holder">
		<p>
			<?php
				// translators: %s is the total number of emails logged in the last 30 days.
				printf(
					__( 'Email activity: %s emails logged in the last 30 days.', 'paid-memberships-pro' ),
					'<strong>' . number_format_i18n( $total_sent + $total_failed ) . '</strong>'
				);

				if ( ! empty( $email_log_purge_days ) ) {
					echo ' ';
					// translators: %s is the number of days after which email log entries are automatically purged.
					printf(
						_n(
							'Entries are automatically purged after %s day.',
							'Entries are automatically purged after %s days.',
							$email_log_purge_days,
							'paid-memberships-pro'
						),
						'<strong>' . number_format_i18n( $email_log_purge_days ) . '</strong>'
					);
				}

				if ( current_user_can( 'manage_options' ) ) {
					echo ' <a href="' . esc_url( add_query_arg( 'page', 'pmpro-emailsettings#email-logging-settings', admin_url( 'admin.php' ) ) ) . '">';
					esc_html_e( 'Email Log Settings', 'paid-memberships-pro' );
					echo '</a>';
				}
			?>
		</p>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Last Activity', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Emails Sent Successfully', 'paid-memberships-pro' ); ?></td>
					<td>
						<?php if ( $total_sent > 0 ) { ?>
							<?php
							// Build the selectors for the status tag.
							$status_classes = array();
							$status_classes[] = 'pmpro_tag';
							$status_classes[] = 'pmpro_tag-has_icon';
							$status_classes[] = 'pmpro_tag-success';
							$status_class = implode( ' ', $status_classes );
							?>
							<span class="<?php echo esc_attr( $status_class ); ?>"><?php printf( __( '%s Sent', 'paid-memberships-pro' ), number_format_i18n( $total_sent ) ); ?></span>
						<?php } else { ?>
							<?php echo esc_html__( '&#8212;', 'paid-memberships-pro' ); ?>
						<?php } ?>
					</td>
					<td>
					<?php if ( $last_sent_log ) { ?>
						<?php echo esc_html( sprintf(
							// translators: %1$s is the date and %2$s is the time.
							__( '%1$s at %2$s', 'paid-memberships-pro' ),
							esc_html( date_i18n( get_option( 'date_format' ), strtotime( $last_sent_log->timestamp ) ) ),
							esc_html( date_i18n( get_option( 'time_format' ), strtotime( $last_sent_log->timestamp ) ) )
						) );
						?>
					<?php } else { ?>
						<?php esc_html_e( 'No successful emails sent in the last 30 days', 'paid-memberships-pro' ); ?>
					<?php } ?>
					</td>
					<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-reports', 'report' => 'email_log', 'status' => 'sent' ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_html_e( 'View Sent Email Log', 'paid-memberships-pro' ); ?>"><?php esc_html_e( 'View Log', 'paid-memberships-pro' ); ?></a></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Emails Failed to Send', 'paid-memberships-pro' ); ?></td>
					<td>
						<?php if ( $total_failed > 0 ) { ?>
							<?php
							// Build the selectors for the status tag.
							$status_classes = array();
							$status_classes[] = 'pmpro_tag';
							$status_classes[] = 'pmpro_tag-has_icon';
							$status_classes[] = 'pmpro_tag-error';
							$status_class = implode( ' ', $status_classes );
							?>
							<span class="<?php echo esc_attr( $status_class ); ?>"><?php printf( __( '%s Failed', 'paid-memberships-pro' ), number_format_i18n( $total_failed ) ); ?></span>
						<?php } else { ?>
							<?php echo esc_html__( '&#8212;', 'paid-memberships-pro' ); ?>
						<?php } ?>
					</td>
					<td>
					<?php if ( $last_failed_log ) { ?>
						<?php
						echo esc_html( sprintf(
							// translators: %1$s is the date and %2$s is the time.
							__( '%1$s at %2$s', 'paid-memberships-pro' ),
							esc_html( date_i18n( get_option( 'date_format' ), strtotime( $last_failed_log->timestamp ) ) ),
							esc_html( date_i18n( get_option( 'time_format' ), strtotime( $last_failed_log->timestamp ) ) )
						) );
						?>
					<?php } else { ?>
						<?php esc_html_e( 'No failed emails in the last 30 days', 'paid-memberships-pro' ); ?>
					<?php } ?>
					</td>
					<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-reports', 'report' => 'email_log', 'status' => 'failed' ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_html_e( 'View Failed Email Log', 'paid-memberships-pro' ); ?>"><?php esc_html_e( 'View Log', 'paid-memberships-pro' ); ?></a></td>
				</tr>
			</tbody>
		</table>

		<?php if ( function_exists( 'pmpro_report_email_log_page' ) ) { ?>
			<p class="pmpro_report-button">
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-reports', 'report' => 'email_log' ), admin_url( 'admin.php' ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View the full %s report', 'paid-memberships-pro' ), $pmpro_reports['email_log'] ) ); ?>"><?php esc_html_e( 'Details', 'paid-memberships-pro' );?></a>
			</p>
		<?php } ?>

	</div>
	<?php
}

/**
 * Main page display for email log report
 */
function pmpro_report_email_log_page() {
	global $wpdb, $pmpro_email_templates_defaults;

	// Handle deleting an email log.
	if ( isset( $_REQUEST['pmpro_delete_email_log'] ) ) {
		$log_id = intval( $_REQUEST['pmpro_delete_email_log'] );
		check_admin_referer( 'pmpro_delete_email_log_' . $log_id, 'pmpro_delete_email_log_nonce' );
		
		$wpdb->delete(
			$wpdb->pmpro_email_log,
			array( 'id' => $log_id ),
			array( '%d' )
		);
		
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Email log deleted successfully.', 'paid-memberships-pro' ); ?></p>
		</div>
		<?php
	}

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
		// Check if we are searching for a specific field.
		if ( strpos( $s, ':' ) !== false ) {
			$search_parts = explode( ':', $s );
			$search_key = trim( $search_parts[0] );
			$search_value = trim( $search_parts[1] );
			
			if ( $search_key == 'login' || $search_key == 'user_login' || $search_key == 'username' ) {
				$user = get_user_by( 'login', $search_value );
				if ( $user ) {
					$where_clauses[] = "user_id = %d";
					$where_values[] = $user->ID;
				} else {
					$where_clauses[] = "0=1";
				}
			} elseif ( $search_key == 'email' || $search_key == 'user_email' ) {
				$where_clauses[] = "email_to = %s";
				$where_values[] = $search_value;
			} elseif ( $search_key == 'id' || $search_key == 'user_id' ) {
				$where_clauses[] = "user_id = %d";
				$where_values[] = intval( $search_value );
			} else {
				// Default to general search if the key is not recognized.
				$where_clauses[] = "(email_to LIKE %s OR subject LIKE %s)";
				$search_term = '%' . $wpdb->esc_like( $s ) . '%';
				$where_values[] = $search_term;
				$where_values[] = $search_term;
			}
		} else {
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

	// Get log entries with pagination
	$log_query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->pmpro_email_log} 
				   WHERE {$where_sql} 
				   ORDER BY timestamp DESC 
				   LIMIT %d, %d";

	$query_values = array_merge( $where_values, array( $start, $limit ) );
	$entries = $wpdb->get_results( $wpdb->prepare( $log_query, $query_values ) );

	// Get available templates for filter dropdown
	$templates = $wpdb->get_col( 
		"SELECT DISTINCT template FROM {$wpdb->pmpro_email_log} 
		 WHERE template != '' 
		 ORDER BY template ASC" 
	);

	?>
	<form id="email-log-form" method="get" action="">
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Email Log', 'paid-memberships-pro' ); ?>
		</h1>

		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input">
				<?php esc_html_e( 'Search Email Log', 'paid-memberships-pro' ); ?>
			</label>
			<input type="hidden" name="page" value="pmpro-reports" />
			<input type="hidden" name="report" value="email_log" />
			<input id="post-search-input" type="search" value="<?php echo esc_attr( $s ); ?>" name="s" />
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Log', 'paid-memberships-pro' ); ?>" />
		</p>

		<p>
			<?php esc_html_e( 'This report shows a record of membership-related emails sent to members and site admins. You can search by email template, subject line, or recipient. Click "View Content" to see the full message for any entry.', 'paid-memberships-pro' ); ?>
			<?php if ( current_user_can( 'manage_options' ) ) { ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-emailsettings#email-logging-settings' ), admin_url( 'admin.php' ) ) ); ?>">
					<?php esc_html_e( 'Use the Email Settings page to manage how email logging works', 'paid-memberships-pro' ); ?>
				</a>
			<?php } ?>
		</p>

		<div class="pmpro_report-filters">
			<h3><?php esc_html_e( 'Customize Report', 'paid-memberships-pro' ); ?></h3>
			<div class="tablenav top">
				<label for="template" class="pmpro_report-filter-text">
					<?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?>
				</label>
				<select id="template" name="template" onchange="jQuery('#email-log-form').trigger('submit');">
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
				<select id="status" name="status" onchange="jQuery('#email-log-form').trigger('submit');">
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

		<?php if ( $entries ) { ?>
			<div class="tablenav top">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php printf( esc_html( _n( '%s email', '%s emails', $totalrows, 'paid-memberships-pro' ) ), number_format_i18n( $totalrows ) ); ?>
					</span>
				</div>
				<br class="clear" />
			</div>

			<table id="pmpro_report_email_log" class="widefat striped pmpro_responsive_table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'User', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $entries as $entry ) { 
						$user = ! empty( $entry->user_id ) ? get_userdata( $entry->user_id ) : null;
						?>
						<tr>
							<td data-colname="<?php esc_attr_e( 'Date', 'paid-memberships-pro' ); ?>">
								<?php
								echo esc_html( sprintf(
									// translators: %1$s is the date and %2$s is the time.
									__( '%1$s at %2$s', 'paid-memberships-pro' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry->timestamp ) ) ),
									esc_html( date_i18n( get_option( 'time_format' ), strtotime( $entry->timestamp ) ) )
								) );
								?>
								<div class="row-actions">
									<a href="#" class="pmpro-view-email-log" data-log-id="<?php echo esc_attr( $entry->id ); ?>">
										<?php esc_html_e( 'View Content', 'paid-memberships-pro' ); ?>
									</a>
									| 
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-reports', 'report' => 'email_log', 'pmpro_delete_email_log' => $entry->id ) ), 'pmpro_delete_email_log_' . strval( intval( $entry->id ) ), 'pmpro_delete_email_log_nonce' ) ); ?>" class="pmpro-delete-email-log" style="color: #a00;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this email log?', 'paid-memberships-pro' ); ?>');">
										<?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?>
									</a>
								</div>
							</td>
							<td data-colname="<?php esc_attr_e( 'To', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $entry->email_to ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Subject', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $entry->subject ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Template', 'paid-memberships-pro' ); ?>">
								<?php
									$description = '';
									if ( ! empty( $entry->template ) ) {
										if ( ! empty( $pmpro_email_templates_defaults[ $entry->template ]['description'] ) ) {
											$description = $pmpro_email_templates_defaults[ $entry->template ]['description'];
										} else {
											$description = ucwords( str_replace( array( '_', '-' ), ' ', $entry->template ) );
										}
									}

									echo $description ? esc_html( $description ) : esc_html__( '&#8212;', 'paid-memberships-pro' );
								?>
							</td>
							<td data-colname="<?php esc_attr_e( 'User', 'paid-memberships-pro' ); ?>">
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
							<td data-colname="<?php esc_attr_e( 'Status', 'paid-memberships-pro' ); ?>">
								<?php
									// Build the selectors for the status tag.
									$status_classes = array();
									$status_classes[] = 'pmpro_tag';
									$status_classes[] = 'pmpro_tag-has_icon';
									if ( $entry->status === 'sent' ) {
										$status_classes[] = 'pmpro_tag-success';
									} else {
										$status_classes[] = 'pmpro_tag-error';
									}
									$status_class = implode( ' ', $status_classes );
								?>
								<span class="<?php echo esc_attr( $status_class ); ?>"><?php esc_html_e( ucfirst( $entry->status ), 'paid-memberships-pro' ); ?></span>

								<?php if ( ! empty( $entry->error_message ) ) { ?>
									<br /><small><?php echo esc_html( $entry->error_message ); ?></small>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					// Pagination
					echo wp_kses_post( 
						pmpro_getPaginationString( 
							$pn, 
							$totalrows, 
							$limit, 
							1, 
							admin_url( "admin.php?page=pmpro-reports&report=email_log&s=" . urlencode( $s ) ), 
							"&template=" . urlencode( $template_filter ) . "&status=" . urlencode( $status_filter ) . "&limit=" . $limit . "&pn=", 
							__( 'Email Log Pagination', 'paid-memberships-pro' ) 
						) 
					);
					?>
				</div>
			</div>
		<?php } else { ?>
			<div class="pmpro_spacer"></div>
			<div class="pmpro_message pmpro_info">
				<p><?php esc_html_e( 'No email log entries found.', 'paid-memberships-pro' ); ?></p>
			</div>
			<div class="pmpro_spacer"></div>
		<?php } ?>
	</form>

	<?php
	// Render the email log modal
	echo pmpro_render_email_log_modal();
	?>

	<?php
}
