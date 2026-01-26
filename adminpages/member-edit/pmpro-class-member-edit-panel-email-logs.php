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
		global $wpdb;
		
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
		<div id="member-email-logs">
			<?php if ( empty( $logs ) ) { ?>
				<p><?php esc_html_e( 'No email logs found for this user.', 'paid-memberships-pro' ); ?></p>
			<?php } else { ?>
				<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date/Time', 'paid-memberships-pro' ); ?></th>
							<th><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></th>
							<th><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></th>
							<th><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
							<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'paid-memberships-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) { ?>
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
										<?php if ( ! empty( $log->error_message ) ) { ?>
											<br><small><?php echo esc_html( $log->error_message ); ?></small>
										<?php } ?>
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
				<p>
				<?php 
				printf( 
					esc_html__( 'Showing the %d most recent emails.', 'paid-memberships-pro' ),
					count( $logs ),
				);
				echo ' ';
				printf( 
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=pmpro-reports&report=email_logs&s=' . urlencode( $user->user_login ) ) ),
					esc_html__( 'View all email logs for this user.', 'paid-memberships-pro' )
				);
				?>
			</p>
			<?php } ?>
		</div>

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
