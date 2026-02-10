<?php

/**
 * Log an email via wp_mail_succeeded.
 *
 * @since TBD
 * 
 * @param array $mail_data The email data.
 */
function pmpro_log_email_succeeded( $mail_data ) {
	pmpro_log_email_from_mail_data( $mail_data, true );
}
add_action( 'wp_mail_succeeded', 'pmpro_log_email_succeeded' );

/**
 * Log an email via wp_mail_failed.
 *
 * @since TBD
 * 
 * @param WP_Error $error The error object.
 */
function pmpro_log_email_failed( $error ) {
	$mail_data = $error->get_error_data( 'wp_mail_failed' );
	pmpro_log_email_from_mail_data( $mail_data, false, $error->get_error_message() );
}
add_action( 'wp_mail_failed', 'pmpro_log_email_failed' );

/**
 * Log an email from the mail data.
 *
 * @since TBD
 * 
 * @param array $mail_data The email data.
 * @param bool $success Whether the email was sent successfully.
 * @param string $error_message The error message if the email failed.
 */
function pmpro_log_email_from_mail_data( $mail_data, $success, $error_message = '' ) {
	global $wpdb;

	// Check if logging is disabled
	if ( pmpro_getOption( 'email_logging_disabled' ) == '1' ) {
		return;
	}

	// Normalize headers to array
	$headers = isset( $mail_data['headers'] ) ? $mail_data['headers'] : array();
	if ( ! is_array( $headers ) ) {
		$headers = explode( "\n", $headers );
	}

	// Extract data from X-PMPro tracking headers.
	// Note: WordPress's wp_mail() strips standard headers (From, Reply-To, CC, BCC)
	// before firing wp_mail_succeeded, so we use custom X-PMPro-* headers instead.
	$template = '';
	$user_id = 0;
	$email_from = '';
	$from_name = '';
	$reply_to = '';
	$cc = '';
	$bcc = '';
	$clean_headers = array(); // Headers without our internal tracking headers

	// Map of X-PMPro header names to their extraction callbacks.
	$pmpro_headers = array(
		'X-PMPro-Template'   => 'template',
		'X-PMPro-User-ID'    => 'user_id',
		'X-PMPro-From'       => 'email_from',
		'X-PMPro-From-Name'  => 'from_name',
		'X-PMPro-Reply-To'   => 'reply_to',
		'X-PMPro-CC'         => 'cc',
		'X-PMPro-BCC'        => 'bcc',
	);

	foreach ( $headers as $key => $value ) {
		// Handle associative array (Key => Value)
		if ( is_string( $key ) ) {
			$matched = false;
			foreach ( $pmpro_headers as $header_name => $var_name ) {
				if ( strcasecmp( $key, $header_name ) === 0 ) {
					$$var_name = trim( $value );
					$matched = true;
					break;
				}
			}
			if ( ! $matched ) {
				$clean_headers[ $key ] = $value;
			}
			continue;
		}

		// Handle numeric array (String "Header: Value")
		if ( empty( trim( $value ) ) ) {
			continue;
		}

		$matched = false;
		foreach ( $pmpro_headers as $header_name => $var_name ) {
			$prefix = $header_name . ':';
			if ( stripos( $value, $prefix ) === 0 ) {
				$$var_name = trim( substr( $value, strlen( $prefix ) ) );
				$matched = true;
				break;
			}
		}
		if ( ! $matched ) {
			$clean_headers[] = $value;
		}
	}

	// Ensure user_id is an integer.
	$user_id = intval( $user_id );

	// If there is no template, don't log as it's likely not from PMPro. Remove this if we want to log all emails regardless of source, but for now we want to limit to PMPro-generated emails.
	if ( empty( $template ) ) {
		return;
	}

	// If user_id not in headers, try to find by email
	// Use normalized email_to for lookup
	$email_to = isset( $mail_data['to'] ) ? $mail_data['to'] : '';
	if ( is_array( $email_to ) ) {
		$email_to = implode( ',', $email_to );
	}

	if ( empty( $user_id ) && ! empty( $email_to ) ) {
		// Handle comma-separated emails? Just take the first one for lookup
		$emails = explode( ',', $email_to );
		$email_address = trim( $emails[0] );
		
		// Strip name if present "Name <email>"
		if ( preg_match( '/<([^>]+)>/', $email_address, $matches ) ) {
			$email_address = $matches[1];
		}

		$user = get_user_by( 'email', $email_address );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}

	// Prepare data for insertion
	$log_data = array(
		'user_id'       => $user_id,
		'email_to'      => $email_to,
		'email_from'    => $email_from,
		'from_name'     => $from_name,
		'subject'       => isset( $mail_data['subject'] ) ? $mail_data['subject'] : '',
		'body'          => isset( $mail_data['message'] ) ? $mail_data['message'] : '',
		'template'      => $template,
		'headers'       => maybe_serialize( $clean_headers ),
		'reply_to'      => $reply_to,
		'cc'            => $cc,
		'bcc'           => $bcc,
		'status'        => $success ? 'sent' : 'failed',
		'error_message' => $error_message,
		'timestamp'     => current_time( 'mysql' )
	);

	// Insert log entry
	$wpdb->insert(
		$wpdb->pmpro_email_log,
		$log_data,
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}


/**
 * Auto-purge old email log entries based on settings
 */
function pmpro_auto_purge_email_log_entries() {
	$purge_days = intval( get_option( 'pmpro_email_log_purge_days', 90 ) );

	// If set to 0, purge is disabled
	if ( empty( $purge_days ) ) {
		return;
	}

	global $wpdb;

	$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$purge_days} days" ) );

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->pmpro_email_log} WHERE timestamp < %s",
			$cutoff_date
		)
	);
}
add_action( 'pmpro_schedule_daily', 'pmpro_auto_purge_email_log_entries' );

/**
 * Render email log details HTML for modal display
 *
 * @since TBD
 * @param object $log Email log object from database
 * @return string HTML output
 */
function pmpro_render_email_log_details( $log ) {
	if ( ! $log ) {
		return '';
	}

	global $pmpro_email_templates_defaults;

	ob_start();
	?>
	<div class="pmpro_scrollable">
		<div class="pmpro-email-log-details">
			<div class="pmpro-email-log-header">
				<?php esc_html_e( 'Email Information', 'paid-memberships-pro' ); ?>
			</div>
			<table class="wp-list-table widefat striped">
				<tr>
					<th><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></th>
					<td><?php echo esc_html( $log->email_to ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'From', 'paid-memberships-pro' ); ?></th>
					<td><?php echo esc_html( $log->from_name . ' <' . $log->email_from . '>' ); ?></td>
				</tr>
				<?php if ( ! empty( $log->reply_to ) ) { ?>
				<tr>
					<th><?php esc_html_e( 'Reply-To', 'paid-memberships-pro' ); ?></th>
					<td><?php echo esc_html( $log->reply_to ); ?></td>
				</tr>
				<?php } ?>
				<?php if ( ! empty( $log->cc ) ) { ?>
				<tr>
					<th><?php esc_html_e( 'CC', 'paid-memberships-pro' ); ?></th>
					<td><?php echo esc_html( $log->cc ); ?></td>
				</tr>
				<?php } ?>
				<?php if ( ! empty( $log->bcc ) ) { ?>
				<tr>
					<th><?php esc_html_e( 'BCC', 'paid-memberships-pro' ); ?></th>
					<td><?php echo esc_html( $log->bcc ); ?></td>
				</tr>
				<?php } ?>
				<tr>
					<th><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></th>
					<td><?php echo esc_html( $log->subject ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
					<td>
						<?php
							if ( ! empty( $log->template ) && ! empty( $pmpro_email_templates_defaults[$log->template ]['description'] ) ) {
								echo esc_html( $pmpro_email_templates_defaults[$log->template ]['description'] );
								echo ' <code>' . esc_html( $log->template ) . '</code>';
							} else {
								echo '—';
							}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					<td>
						<?php
							// Build the selectors for the status tag.
							$status_classes = array();
							$status_classes[] = 'pmpro_tag';
							$status_classes[] = 'pmpro_tag-has_icon';
							if ( $log->status === 'sent' ) {
								$status_classes[] = 'pmpro_tag-success';
							} else {
								$status_classes[] = 'pmpro_tag-error';
							}
							$status_class = implode( ' ', $status_classes );
						?>
						<span class="<?php echo esc_attr( $status_class ); ?>"><?php esc_html_e( ucfirst( $log->status ), 'paid-memberships-pro' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Timestamp', 'paid-memberships-pro' ); ?></th>
					<td>
						<?php
						echo esc_html( sprintf(
							// translators: %1$s is the date and %2$s is the time.
							__( '%1$s at %2$s', 'paid-memberships-pro' ),
							esc_html( date_i18n( get_option( 'date_format' ), strtotime( $log->timestamp ) ) ),
							esc_html( date_i18n( get_option( 'time_format' ), strtotime( $log->timestamp ) ) )
						) );
						?>
					</td>
				</tr>
				<?php if ( ! empty( $log->error_message ) ) { ?>
				<tr>
					<th><?php esc_html_e( 'Error', 'paid-memberships-pro' ); ?></th>
					<td class="pmpro-email-log-error"><?php echo esc_html( $log->error_message ); ?></td>
				</tr>
				<?php } ?>
			</table>

			<div class="pmpro_spacer"></div>

			<div class="pmpro-email-log-header">
				<?php esc_html_e( 'Email Body', 'paid-memberships-pro' ); ?>
				<label for="pmpro-email-body-view-toggle" class="pmpro-email-log-toggle-label">
					<input type="checkbox" id="pmpro-email-body-view-toggle" class="pmpro-email-body-view-toggle">
					<?php esc_html_e( 'View Raw HTML', 'paid-memberships-pro' ); ?>
				</label>
			</div>
			<div class="pmpro-email-body-container">
				<div class="pmpro-email-body-formatted">
					<?php echo wp_kses_post( $log->body ); ?>
				</div>
				<div class="pmpro-email-body-raw">
					<pre><?php echo esc_html( $log->body ); ?></pre>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render email log modal HTML structure
 *
 * @since TBD
 * @return string HTML output
 */
function pmpro_render_email_log_modal() {
	ob_start();
	?>
	<!-- Modal for viewing email details -->
	<div id="pmpro-popup" class="pmpro-popup-overlay">
		<span class="pmpro-popup-helper"></span>
		<div class="pmpro-popup-wrap pmpro-popup-email-log">
			<span id="pmpro-popup-inner">
				<a class="pmproPopupCloseButton" href="#" title="<?php esc_attr_e( 'Close Popup', 'paid-memberships-pro' ); ?>"><span class="dashicons dashicons-no"></span></a>
				<div id="pmpro-email-log-content">
					
				</div>
			</span>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
