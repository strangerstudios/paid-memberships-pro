<?php
/**
 * Email Logs panel for Member Edit page
 *
 * @package Paid Memberships Pro
 * @subpackage Admin
 * @since TBD
 */

class PMPro_Member_Edit_Panel_Email_Logs extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel
	 */
	public function __construct() {
		$this->slug = 'email-logs';
		$this->title = __( 'Email Logs', 'paid-memberships-pro' );
		// No submit button needed - this is read-only
		$this->submit_text = '';
	}
	
	/**
	 * Check if panel should show
	 * Only show if user has manage_options capability
	 */
	public function should_show() {
		return current_user_can( 'manage_options' );
	}
	
	/**
	 * Display the panel contents
	 */
	protected function display_panel_contents() {
		global $wpdb, $pmpro_email_templates_defaults;
		
		$user = self::get_user();
		
		if ( empty( $user->ID ) ) {
			echo '<p>' . esc_html__( 'Save the user first to view email logs.', 'paid-memberships-pro' ) . '</p>';
			return;
		}
		
		// Get all email logs for this user (limited to 10 most recent)
		$logs = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->pmpro_email_log} 
			 WHERE user_id = %d OR email_to = %s 
			 ORDER BY timestamp DESC 
			 LIMIT 10",
			$user->ID,
			$user->user_email
		) );
		
		?>
		<?php if ( empty( $logs ) ) { ?>
			<p><?php esc_html_e( 'No email logs found for this user.', 'paid-memberships-pro' ); ?></p>
		<?php } else { ?>
			<table class="widefat striped pmpro_responsive_table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) { ?>
						<tr>
							<td data-colname="<?php esc_attr_e( 'Date', 'paid-memberships-pro' ); ?>">
								<?php
								echo esc_html( sprintf(
									// translators: %1$s is the date and %2$s is the time.
									__( '%1$s at %2$s', 'paid-memberships-pro' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $log->timestamp ) ) ),
									esc_html( date_i18n( get_option( 'time_format' ), strtotime( $log->timestamp ) ) )
								) );
								?>
								<div class="row-actions">
									<a href="#" class="pmpro-view-email-log" data-log-id="<?php echo esc_attr( $log->id ); ?>">
										<?php esc_html_e( 'View Content', 'paid-memberships-pro' ); ?>
									</a>
								</div>
							</td>
							<td data-colname="<?php esc_attr_e( 'To', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $log->email_to ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Subject', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $log->subject ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Template', 'paid-memberships-pro' ); ?>">
								<?php
									$description = '';
									if ( ! empty( $log->template ) ) {
										if ( ! empty( $pmpro_email_templates_defaults[ $log->template ]['description'] ) ) {
											$description = $pmpro_email_templates_defaults[ $log->template ]['description'];
										} else {
											$description = ucwords( str_replace( array( '_', '-' ), ' ', $log->template ) );
										}
									}

									echo $description ? esc_html( $description ) : esc_html__( '&#8212;', 'paid-memberships-pro' );
								?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Status', 'paid-memberships-pro' ); ?>">
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

								<?php if ( ! empty( $log->error_message ) ) { ?>
									<br /><small><?php echo esc_html( $log->error_message ); ?></small>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<p>
			<?php
			/* translators: %d is the number of email logs being shown. */
			printf(
				esc_html__( 'Showing the %d most recent emails.', 'paid-memberships-pro' ),
				count( $logs )
			);
			echo ' ';
			printf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'page' => 'pmpro-reports', 'report' => 'email_logs', 's' => urlencode( $user->user_login ) ), admin_url( 'admin.php' ) ) ),
				esc_html__( 'View all email logs for this user.', 'paid-memberships-pro' )
			);
			?>
		</p>
		<?php } ?>

		<?php
		// Render the email log modal
		echo pmpro_render_email_log_modal();
		?>

		<?php
	}
}

// Register the panel
add_filter( 'pmpro_member_edit_panels', function( $panels ) {
	// If email logging is disabled, do not register the panel.
	$email_logging_disabled = get_option( 'pmpro_email_logging_disabled' );
	if ( ! empty( $email_logging_disabled ) ) {
		return $panels;
	}

	$panels[] = new PMPro_Member_Edit_Panel_Email_Logs();
	return $panels;
} );
