<?php

/**
 * Check if email logging is enabled.
 *
 * @since 3.7
 *
 * @return bool Whether email logging is enabled.
 */
function pmpro_is_email_logging_enabled() {
	return (bool) get_option( 'pmpro_email_logging_enabled', 1 );
}

/**
 * Get or set stashed wp_mail data.
 *
 * The wp_mail filter stashes email data before sending so that
 * it is available in the wp_mail_failed callback, which only
 * receives a WP_Error (no email content).
 *
 * @since 3.7
 *
 * @param array|null|false $set Pass an array to stash, false to clear, or null to retrieve.
 * @return array|null The stashed data, or null if empty.
 */
function pmpro_stashed_mail_data( $set = null ) {
	static $data = null;
	if ( $set !== null ) {
		$data = $set === false ? null : $set;
	}
	return $data;
}

/**
 * Get or set PMPro-specific email metadata for the current send.
 *
 * Set via the pmpro_before_email_sent hook to stash the template
 * name and user_id before wp_mail() fires. The wp_mail filter
 * merges this into the stashed email data for logging. Cleared
 * via the pmpro_after_email_sent hook.
 *
 * @since 3.7
 *
 * @param array|null|false $set Pass an array to set, false to clear, or null to retrieve.
 * @return array|null The metadata array with 'template' and 'user_id', or null.
 */
function pmpro_email_sending_metadata( $set = null ) {
	static $metadata = null;
	if ( $set !== null ) {
		$metadata = $set === false ? null : $set;
	}
	return $metadata;
}

/**
 * Stash PMPro-specific metadata before wp_mail() is called.
 *
 * Extracts the template name and user_id from the PMProEmail
 * object so they are available when the wp_mail filter fires.
 *
 * @since 3.7
 *
 * @param PMProEmail $email The email object about to be sent.
 */
function pmpro_stash_email_metadata( $email ) {
	$user_id = 0;
	if ( ! empty( $email->data['user_id'] ) ) {
		$user_id = intval( $email->data['user_id'] );
	} elseif ( ! empty( $email->data['user_login'] ) ) {
		$user = get_user_by( 'login', $email->data['user_login'] );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}

	pmpro_email_sending_metadata( array(
		'template' => $email->template,
		'user_id'  => $user_id,
	) );
}
add_action( 'pmpro_before_email_sent', 'pmpro_stash_email_metadata' );

/**
 * Clear PMPro-specific metadata after wp_mail() returns.
 *
 * @since 3.7
 *
 * @param PMProEmail $email  The email object that was sent.
 * @param bool       $result Whether wp_mail() returned true.
 */
function pmpro_clear_email_metadata( $email, $result ) {
	pmpro_email_sending_metadata( false );
}
add_action( 'pmpro_after_email_sent', 'pmpro_clear_email_metadata', 10, 2 );

/**
 * Get or set the resolved From email and name from PHPMailer.
 *
 * Hooked to phpmailer_init to capture the final From/FromName
 * after WordPress applies defaults and filters. This ensures
 * accurate from data even when no From header was passed to wp_mail().
 *
 * @since 3.7
 *
 * @param array|null|false $set Pass an array to set, false to clear, or null to retrieve.
 * @return array|null Array with 'from' and 'from_name', or null.
 */
function pmpro_stashed_from_data( $set = null ) {
	static $data = null;
	if ( $set !== null ) {
		$data = $set === false ? null : $set;
	}
	return $data;
}

/**
 * Get the first recipient email address from a wp_mail recipient value.
 *
 * Supports arrays of recipients and strings that may include display names.
 *
 * @since 3.7
 *
 * @param string|array $recipients Recipient value passed to wp_mail().
 * @return string The first email address, or an empty string if none was found.
 */
function pmpro_get_primary_recipient_email( $recipients ) {
	if ( empty( $recipients ) ) {
		return '';
	}

	$recipient_string = is_array( $recipients ) ? implode( ', ', $recipients ) : $recipients;

	if ( preg_match( '/[A-Z0-9._%+\'+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $recipient_string, $matches ) ) {
		return sanitize_email( $matches[0] );
	}

	return sanitize_email( trim( $recipient_string ) );
}

/**
 * Capture the resolved From and FromName from PHPMailer.
 *
 * Fires after all wp_mail processing and filters, so these
 * values reflect the actual sender used for the email.
 *
 * @since 3.7
 *
 * @param PHPMailer $phpmailer The fully-configured PHPMailer instance.
 */
function pmpro_capture_phpmailer_from( $phpmailer ) {
	if ( pmpro_is_email_logging_enabled() ) {
		pmpro_stashed_from_data( array(
			'from'      => $phpmailer->From,
			'from_name' => $phpmailer->FromName,
		) );
	}
}
add_action( 'phpmailer_init', 'pmpro_capture_phpmailer_from', 9999 );

/**
 * Log an email to the database.
 *
 * Single unified function for logging both PMPro and non-PMPro emails.
 * For PMPro emails, the template name and user_id (the user the email
 * is about) are enriched from PMProEmail metadata. For non-PMPro emails,
 * user_id is resolved from the recipient address and template is empty.
 *
 * @since 3.7
 *
 * @param array  $mail_data {
 *     Email data from WordPress hooks.
 *
 *     @type string|array $to      Recipient(s).
 *     @type string       $subject Subject line.
 *     @type string       $message Email body.
 *     @type string|array $headers Email headers.
 * }
 * @param string $status        'sent' or 'failed'.
 * @param string $error_message Error message for failed emails.
 */
function pmpro_log_email( $mail_data, $status = 'sent', $error_message = '' ) {
	global $wpdb;

	if ( empty( $mail_data ) ) {
		return;
	}

	// Check if logging is enabled.
	if ( ! pmpro_is_email_logging_enabled() ) {
		return;
	}

	// Check if the email log table is available.
	if ( empty( $wpdb->pmpro_email_log ) ) {
		return;
	}

	// Check for PMPro-specific metadata (template, user_id).
	$metadata = ! empty( $mail_data['pmpro_metadata'] ) ? $mail_data['pmpro_metadata'] : null;
	$is_pmpro = ! empty( $metadata );

	$email_to = pmpro_get_primary_recipient_email( $mail_data['to'] );
	$headers  = ! empty( $mail_data['headers'] ) ? $mail_data['headers'] : '';
	$template = $is_pmpro ? $metadata['template'] : '';

	/**
	 * Filter whether a specific email should be logged.
	 *
	 * By default, only PMPro emails are logged. To log all WordPress
	 * emails, return true unconditionally:
	 *
	 *     add_filter( 'pmpro_should_log_email', '__return_true' );
	 *
	 * @since 3.7
	 *
	 * @param bool  $should_log Whether to log this email. Default true for PMPro emails, false otherwise.
	 * @param array $email_data {
	 *     Basic email data for filtering decisions.
	 *
	 *     @type string $email_to  Recipient email address.
	 *     @type string $subject   Email subject line.
	 *     @type string $template  PMPro email template name, or empty for non-PMPro emails.
	 * }
	 */
	if ( ! apply_filters( 'pmpro_should_log_email', $is_pmpro, array(
		'email_to' => $email_to,
		'subject'  => $mail_data['subject'],
		'template' => $template,
	) ) ) {
		return;
	}

	// Resolve user_id: use PMPro metadata if available, otherwise look up by email.
	$user_id = 0;
	if ( $is_pmpro && ! empty( $metadata['user_id'] ) ) {
		$user_id = intval( $metadata['user_id'] );
	} elseif ( ! empty( $email_to ) ) {
		$user = get_user_by( 'email', $email_to );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}

	// Parse headers.
	$parsed_headers = pmpro_parse_email_headers( $headers );

	// Use PHPMailer's resolved From/FromName as fallback when headers don't contain From.
	$from_data = pmpro_stashed_from_data();
	$email_from = ! empty( $parsed_headers['from'] ) ? $parsed_headers['from'] : ( ! empty( $from_data['from'] ) ? $from_data['from'] : '' );
	$from_name  = ! empty( $parsed_headers['from_name'] ) ? $parsed_headers['from_name'] : ( ! empty( $from_data['from_name'] ) ? $from_data['from_name'] : '' );

	// Store the full, unmodified recipient value for reference.
	$email_to_full = is_array( $mail_data['to'] ) ? implode( ', ', $mail_data['to'] ) : $mail_data['to'];

	// Prepare data for insertion.
	$log_data = array(
		'user_id'       => $user_id,
		'email_to'      => $email_to,
		'email_to_full' => $email_to_full,
		'email_from'    => $email_from,
		'from_name'     => $from_name,
		'subject'       => $mail_data['subject'],
		'body'          => $mail_data['message'],
		'template'      => $template,
		'headers'       => maybe_serialize( $headers ),
		'reply_to'      => $parsed_headers['reply_to'],
		'cc'            => $parsed_headers['cc'],
		'bcc'           => $parsed_headers['bcc'],
		'status'        => $status,
		'timestamp'     => current_time( 'mysql', true ),
		'error_message' => $error_message,
	);

	$wpdb->insert(
		$wpdb->pmpro_email_log,
		$log_data,
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}

/**
 * Stash email data from the wp_mail filter before sending.
 *
 * Needed because wp_mail_failed only provides a WP_Error, not
 * the email content. The stash makes the full email data
 * available for logging failed emails.
 *
 * @since 3.7
 *
 * @param array $args The wp_mail arguments (to, subject, message, headers, attachments).
 * @return array The unmodified arguments.
 */
function pmpro_stash_outgoing_email( $args ) {
	if ( pmpro_is_email_logging_enabled() ) {
		// Merge in PMPro metadata if this is a PMPro email.
		$metadata = pmpro_email_sending_metadata();
		if ( ! empty( $metadata ) ) {
			$args['pmpro_metadata'] = $metadata;
		}
		pmpro_stashed_mail_data( $args );
	}
	return $args;
}
add_filter( 'wp_mail', 'pmpro_stash_outgoing_email' );

/**
 * Handle a successful wp_mail send.
 *
 * Clears any stale error and logs the email.
 *
 * @since 3.7
 *
 * @param array $mail_data {
 *     The email data from WordPress.
 *
 *     @type string|array $to          Recipient(s).
 *     @type string       $subject     Subject line.
 *     @type string       $message     Email body.
 *     @type string|array $headers     Email headers.
 *     @type array        $attachments Attachments.
 * }
 */
function pmpro_handle_wp_mail_succeeded( $mail_data ) {
	pmpro_last_wp_mail_error( '' );

	// Use stashed data (which includes pmpro_metadata if applicable).
	$stashed = pmpro_stashed_mail_data();
	if ( ! empty( $stashed ) ) {
		pmpro_log_email( $stashed, 'sent' );
	}

	pmpro_stashed_mail_data( false );
	pmpro_stashed_from_data( false );
}
add_action( 'wp_mail_succeeded', 'pmpro_handle_wp_mail_succeeded' );

/**
 * Handle a failed wp_mail send.
 *
 * Captures the error message and logs the email using stashed data.
 *
 * @since 3.7
 *
 * @param WP_Error $error The error object.
 */
function pmpro_handle_wp_mail_failed( $error ) {
	pmpro_last_wp_mail_error( $error->get_error_message() );

	$stashed = pmpro_stashed_mail_data();
	if ( ! empty( $stashed ) ) {
		pmpro_log_email( $stashed, 'failed', $error->get_error_message() );
	}

	pmpro_stashed_mail_data( false );
	pmpro_stashed_from_data( false );
}
add_action( 'wp_mail_failed', 'pmpro_handle_wp_mail_failed' );

/**
 * Get or set the last wp_mail error message.
 *
 * @since 3.7
 *
 * @param string|null $set Pass a string to set the error, or null to just retrieve it.
 * @return string The last error message, or empty string if none.
 */
function pmpro_last_wp_mail_error( $set = null ) {
	static $last_error = '';
	if ( $set !== null ) {
		$last_error = $set;
	}
	return $last_error;
}

/**
 * Parse email headers to extract From, Reply-To, CC, and BCC.
 *
 * @since 3.7
 *
 * @param array|string $headers Email headers.
 * @return array Associative array with from, from_name, reply_to, cc, bcc.
 */
function pmpro_parse_email_headers( $headers ) {
	$parsed = array(
		'from'      => '',
		'from_name' => '',
		'reply_to'  => '',
		'cc'        => '',
		'bcc'       => '',
	);

	if ( empty( $headers ) ) {
		return $parsed;
	}

	// Convert to array if string.
	if ( ! is_array( $headers ) ) {
		$headers = explode( "\n", $headers );
	}

	foreach ( $headers as $header ) {
		if ( ! is_string( $header ) ) {
			continue;
		}

		$header = trim( $header );

		// Parse From (e.g. "Name <email>" or just "email").
		if ( stripos( $header, 'From:' ) === 0 ) {
			$from_value = trim( substr( $header, 5 ) );
			if ( preg_match( '/^(.+?)\s*<(.+?)>$/', $from_value, $matches ) ) {
				$parsed['from_name'] = trim( $matches[1] );
				$parsed['from']      = trim( $matches[2] );
			} else {
				$parsed['from'] = $from_value;
			}
		}

		// Parse Reply-To.
		if ( stripos( $header, 'Reply-To:' ) === 0 ) {
			$parsed['reply_to'] = trim( str_ireplace( 'Reply-To:', '', $header ) );
		}

		// Parse CC.
		if ( stripos( $header, 'Cc:' ) === 0 ) {
			$parsed['cc'] = trim( str_ireplace( 'Cc:', '', $header ) );
		}

		// Parse BCC.
		if ( stripos( $header, 'Bcc:' ) === 0 ) {
			$parsed['bcc'] = trim( str_ireplace( 'Bcc:', '', $header ) );
		}
	}

	return $parsed;
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
 * @since 3.7
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
							esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $log->timestamp ) ) ) ),
							esc_html( date_i18n( get_option( 'time_format' ), strtotime( get_date_from_gmt( $log->timestamp ) ) ) )
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
 * @since 3.7
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
