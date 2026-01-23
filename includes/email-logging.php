<?php

/**
 * Log an email.
 *
 * @since TBD
 * 
 * @param PMProEmail $email The email object
 * @param bool $result Result of wp_mail()
 */
function pmpro_update_email_log_status( $email, $result ) {
	global $wpdb;

	// Make sure we have an email object.
	if ( empty( $email ) ) {
		return $email;
	}

	// Check if logging is disabled
	if ( pmpro_getOption( 'email_logging_disabled' ) == '1' ) {
		return;
	}
	
	// Extract user_id from email data or lookup by email address
	$user_id = 0;
	if ( ! empty( $email->data['user_id'] ) ) {
		$user_id = intval( $email->data['user_id'] );
	} elseif( ! empty( $email->data['user_login'] ) ) {
		$user = get_user_by( 'login', $email->data['user_login'] );
		if ( $user ) {
			$user_id = $user->ID;
		}
	} elseif ( ! empty( $email->email ) ) {
		$user = get_user_by( 'email', $email->email );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}
	
	// Parse headers to extract reply-to, CC, BCC
	$parsed_headers = pmpro_parse_email_headers( $email->headers );
	
	// Prepare data for insertion
	$log_data = array(
		'user_id'       => $user_id,
		'email_to'      => ! empty( $email->email ) ? $email->email : '',
		'email_from'    => ! empty( $email->from ) ? $email->from : '',
		'from_name'     => ! empty( $email->fromname ) ? $email->fromname : '',
		'subject'       => ! empty( $email->subject ) ? $email->subject : '',
		'body'          => ! empty( $email->body ) ? $email->body : '',
		'template'      => ! empty( $email->template ) ? $email->template : '',
		'headers'       => maybe_serialize( $email->headers ),
		'reply_to'      => $parsed_headers['reply_to'],
		'cc'            => $parsed_headers['cc'],
		'bcc'           => $parsed_headers['bcc'],
		'status'        => $result ? 'sent' : 'failed',
		'timestamp'     => current_time( 'mysql' )
	);
	
	// Insert log entry
	$wpdb->insert(
		$wpdb->pmpro_email_log,
		$log_data,
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}
add_action( 'pmpro_after_email_sent', 'pmpro_update_email_log_status', 10, 2 );

/**
 * Parse email headers to extract reply-to, CC, BCC
 * 
 * @param array|string $headers Email headers
 * @return array Associative array with reply_to, cc, bcc
 */
function pmpro_parse_email_headers( $headers ) {
	$parsed = array(
		'reply_to' => '',
		'cc'       => '',
		'bcc'      => ''
	);
	
	if ( empty( $headers ) ) {
		return $parsed;
	}
	
	// Convert to array if string
	if ( ! is_array( $headers ) ) {
		$headers = explode( "\n", $headers );
	}
	
	foreach ( $headers as $header ) {
		if ( is_string( $header ) ) {
			$header = trim( $header );
			
			// Parse Reply-To
			if ( stripos( $header, 'Reply-To:' ) === 0 ) {
				$parsed['reply_to'] = trim( str_ireplace( 'Reply-To:', '', $header ) );
			}
			
			// Parse CC
			if ( stripos( $header, 'Cc:' ) === 0 ) {
				$parsed['cc'] = trim( str_ireplace( 'Cc:', '', $header ) );
			}
			
			// Parse BCC
			if ( stripos( $header, 'Bcc:' ) === 0 ) {
				$parsed['bcc'] = trim( str_ireplace( 'Bcc:', '', $header ) );
			}
		}
	}
	
	return $parsed;
}

/**
 * Auto-purge old email logs based on settings
 */
function pmpro_auto_purge_email_logs() {
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
add_action( 'pmpro_schedule_daily', 'pmpro_auto_purge_email_logs' );

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

	ob_start();
	?>
	<div class="pmpro-email-log-details">
		<h3 class="pmpro-email-log-info-header">
			<?php esc_html_e( 'Email Information', 'paid-memberships-pro' ); ?>
		</h3>
		<table class="widefat">
			<tr>
				<th><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->email_to ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'From', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->from_name . ' <' . $log->email_from . '>' ); ?></td>
			</tr>
			<?php if ( ! empty( $log->reply_to ) ) { ?>
			<tr>
				<th><?php esc_html_e( 'Reply-To', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->reply_to ); ?></td>
			</tr>
			<?php } ?>
			<?php if ( ! empty( $log->cc ) ) { ?>
			<tr>
				<th><?php esc_html_e( 'CC', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->cc ); ?></td>
			</tr>
			<?php } ?>
			<?php if ( ! empty( $log->bcc ) ) { ?>
			<tr>
				<th><?php esc_html_e( 'BCC', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->bcc ); ?></td>
			</tr>
			<?php } ?>
			<tr>
				<th><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->subject ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( $log->template ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( ucfirst( $log->status ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Timestamp', 'paid-memberships-pro' ); ?>:</th>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->timestamp ) ) ); ?></td>
			</tr>
			<?php if ( ! empty( $log->error_message ) ) { ?>
			<tr>
				<th><?php esc_html_e( 'Error', 'paid-memberships-pro' ); ?>:</th>
				<td class="pmpro-email-log-error"><?php echo esc_html( $log->error_message ); ?></td>
			</tr>
			<?php } ?>
		</table>

		<h3>
			<?php esc_html_e( 'Email Body', 'paid-memberships-pro' ); ?>
			<label class="pmpro-email-log-toggle-label">
				<input type="checkbox" class="pmpro-email-body-view-toggle">
				<?php esc_html_e( 'View Raw HTML', 'paid-memberships-pro' ); ?>
			</label>
		</h3>
		<div class="pmpro-email-body-container">
			<div class="pmpro-email-body-formatted">
				<?php echo wp_kses_post( $log->body ); ?>
			</div>
			<div class="pmpro-email-body-raw">
				<pre><?php echo esc_html( $log->body ); ?></pre>
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
