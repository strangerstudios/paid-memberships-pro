<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_emailsettings")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}	
	
	global $wpdb, $msg, $msgt;
	
	//get/set settings
	global $pmpro_pages;

	global $current_user;
	
	//check nonce for saving settings
	if (!empty($_REQUEST['savesettings']) && (empty($_REQUEST['pmpro_emailsettings_nonce']) || !check_admin_referer('savesettings', 'pmpro_emailsettings_nonce'))) {
		$msg = -1;
		$msgt = esc_html__("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset($_REQUEST['savesettings']);
	}	
	
	if(!empty($_REQUEST['savesettings']))
	{                   		
		//email options
		pmpro_setOption("from_email");
		pmpro_setOption("from_name");
		pmpro_setOption("only_filter_pmpro_emails");
		
		pmpro_setOption("email_admin_checkout");
		pmpro_setOption("email_admin_changes");
		pmpro_setOption("email_admin_cancels");
		pmpro_setOption("email_admin_billing");
		
		pmpro_setOption("email_member_notification");

		// Email logging settings
		pmpro_setOption( 'email_logging_enabled' );
		pmpro_setOption( 'email_log_purge_days', null, 'intval' );

		// Handle purge all logs action
		if ( ! empty( $_REQUEST['email_log_purge_all'] ) ) {
			global $wpdb;
			$wpdb->query( "TRUNCATE TABLE {$wpdb->pmpro_email_log}" );
			$msg = true;
			$msgt = esc_html__( 'All email log entries have been purged.', 'paid-memberships-pro' );
		} else {
			//assume success
			$msg = true;
			$msgt = esc_html__( "Your email settings have been updated.", 'paid-memberships-pro' );
		}		
	}
	
	$from_email = get_option( "pmpro_from_email");
	$from_name = get_option( "pmpro_from_name");
	$only_filter_pmpro_emails = get_option( "pmpro_only_filter_pmpro_emails");
	
	$email_admin_checkout = get_option( "pmpro_email_admin_checkout");
	$email_admin_changes = get_option( "pmpro_email_admin_changes");
	$email_admin_cancels = get_option( "pmpro_email_admin_cancels");
	$email_admin_billing = get_option( "pmpro_email_admin_billing");	
	
	$email_member_notification = get_option( "pmpro_email_member_notification");

	$email_logging_enabled = pmpro_is_email_logging_enabled();
	$email_log_purge_days = get_option( 'pmpro_email_log_purge_days', 90 );

	// Default to 90 only if the value is null or an empty string, but allow 0 as a valid value.
		if ( $email_log_purge_days === null || $email_log_purge_days === '' ) {
			$email_log_purge_days = 90;
		}

	if(empty($from_email))
	{
		$parsed = parse_url(home_url()); 
		$hostname = $parsed["host"];
		$host_parts = explode(".", $hostname);
		if ( count( $host_parts ) > 1 ) {
			$email_domain = $host_parts[count($host_parts) - 2] . "." . $host_parts[count($host_parts) - 1];
		} else {
			$email_domain = $parsed['host'];
		}		
		$from_email = "wordpress@" . $email_domain;
		pmpro_setOption("from_email", $from_email);
	}
	
	if(empty($from_name))
	{		
		$from_name = "WordPress";
		pmpro_setOption("from_name", $from_name);
	}
	
	// default from email wordpress@sitename
	$sitename = strtolower( sanitize_text_field( $_SERVER['SERVER_NAME'] ) );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}
	$default_from_email = 'wordpress@' . $sitename;

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data"> 
		<?php wp_nonce_field('savesettings', 'pmpro_emailsettings_nonce');?>
		<hr class="wp-header-end">
		<h1><?php esc_html_e( 'Email Settings', 'paid-memberships-pro' ); ?></h1>
		<p><?php
			$email_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Email Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/email-settings/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=documentation&utm_content=email-settings">' . esc_html__( 'Email Settings', 'paid-memberships-pro' ) . '</a>';
			// translators: %s: Link to Email Settings doc.
			printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $email_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?></p>
		<div id="send-emails-from-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Send Emails From', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php echo wp_kses_post( __( 'By default, system generated emails are sent from <em><strong>wordpress@yourdomain.com</strong></em>. You can update this from address using the fields below.', 'paid-memberships-pro' ) );?></p>
				<table class="form-table">
				<tbody>                
					<tr>
						<th scope="row" valign="top">
							<label for="from_email"><?php esc_html_e('From Email', 'paid-memberships-pro' );?>:</label>
						</th>
						<td>
							<input type="text" name="from_email" value="<?php echo esc_attr($from_email);?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="from_name"><?php esc_html_e('From Name', 'paid-memberships-pro' );?>:</label>
						</th>
						<td>
							<input type="text" name="from_name" value="<?php echo esc_attr( wp_unslash($from_name) );?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="only_filter_pmpro_emails"><?php esc_html_e('Only Filter PMPro Emails?', 'paid-memberships-pro' );?>:</label>
						</th>
						<td>
							<input type="checkbox" id="only_filter_pmpro_emails" name="only_filter_pmpro_emails" value="1" <?php if(!empty($only_filter_pmpro_emails)) { ?>checked="checked"<?php } ?> />
							<label for="only_filter_pmpro_emails"><?php printf( esc_html__('If unchecked, all emails from "WordPress &lt;%s&gt;" will be filtered to use the above settings.', 'paid-memberships-pro' ),  esc_html( $default_from_email ) );?></label>
						</td>
					</tr>
				</tbody>
				</table>
				<p class="submit"><input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" /></p>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="email-deliverability-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Email Deliverability', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
				$email_method = pmpro_detect_email_method();
				$email_method_tag_class = $email_method['source'] === 'default' ? 'inactive' : 'active';

				// Build some links for use in this section.
				$pmpro_transactional_email_docs_url = 'https://www.paidmembershipspro.com/documentation/hosting-docs/transactional-email/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=documentation';
				?>
				<p>
				<?php
					if ( $email_method['source'] === 'hosting' ) {
						// translators: %s: Link to Transactional Email doc.
						printf(
							esc_html__( 'Your PMPro Max plan includes transactional email delivery. This covers your password resets, payment receipts, and other system-generated membership notifications. Learn more about %s.', 'paid-memberships-pro' ),
							'<a title="' . esc_attr__( 'Paid Memberships Pro - Transactional Email', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="' . esc_url( $pmpro_transactional_email_docs_url ) . '">' . esc_html__( 'transactional email', 'paid-memberships-pro' ) . '</a>'
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						// translators: %s: Link to Transactional Email doc.
						printf(
							esc_html__( 'Transactional email sending is included with a PMPro Max plan or higher. Learn more about %s.', 'paid-memberships-pro' ),
							'<a title="' . esc_attr__( 'Paid Memberships Pro - Transactional Email', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="' . esc_url( $pmpro_transactional_email_docs_url ) . '">' . esc_html__( 'transactional email with Paid Memberships Pro', 'paid-memberships-pro' ) . '</a>'
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						echo ' ';

						// translators: %s: Link to the email troubleshooting guide.
						printf(
							esc_html__( 'Having trouble with email delivery? Read our %s.', 'paid-memberships-pro' ),
							'<a title="' . esc_attr__( 'Paid Memberships Pro - Email Troubleshooting', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/troubleshooting-email-issues-sending-sent-spam-delivery-delays/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=blog&utm_content=email-troubleshooting">' . esc_html__( 'email troubleshooting guide', 'paid-memberships-pro' ) . '</a>'
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</p>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<?php esc_html_e( 'Sending Method', 'paid-memberships-pro' ); ?>
							</th>
							<td>
								<div class="pmpro_tag pmpro_tag-has_icon pmpro_tag-<?php echo esc_attr( $email_method_tag_class ); ?>"><?php echo esc_html( $email_method['label'] ); ?></div>
								<?php if ( ! empty( $email_method['relay'] ) ) { ?>
									<code><?php echo esc_html( $email_method['relay'] ); ?></code>
								<?php } ?>
								<p class="description">
									<?php
									switch ( $email_method['source'] ) {
										case 'plugin':
											printf(
												esc_html__( 'We detected %s active on this site. This confirms a sending plugin is in place, but does not verify that emails are being delivered successfully.', 'paid-memberships-pro' ),
												'<strong>' . esc_html( $email_method['label'] ) . '</strong>'
											);
											break;

										case 'constant':
											esc_html_e( 'SMTP credentials are configured in your wp-config.php file. This indicates a sending service is in place, but does not verify that emails are being delivered successfully.', 'paid-memberships-pro' );
											break;

										case 'hosting':
											esc_html_e( 'Emails are being sent through the PMPro Max built-in transactional email service.', 'paid-memberships-pro' );
											break;

										case 'default':
											printf(
												esc_html__( 'Outbound email is using the default WordPress %s function, which relies on the server-level PHP mail configuration. Consider connecting a transactional email service for reliable delivery.', 'paid-memberships-pro' ),
												'<code>wp_mail()</code>'
											);
											break;
									}
									?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="other-email-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Other Email Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
				<tbody>            
					<tr>
						<th scope="row" valign="top">
							<label for="email_member_notification"><?php esc_html_e('Send members emails', 'paid-memberships-pro' );?>:</label>
						</th>
						<td>
							<input type="checkbox" id="email_member_notification" name="email_member_notification" value="1" <?php if(!empty($email_member_notification)) { ?>checked="checked"<?php } ?> />
							<label for="email_member_notification"><?php esc_html_e('Default WP notification email.', 'paid-memberships-pro' );?></label>
							<p class="description"><?php esc_html_e( 'Recommended: Leave unchecked. Members will still get an email confirmation from PMPro after checkout.', 'paid-memberships-pro' ); ?></p>
						</td>
					</tr>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->

		<div id="email-logging-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Email Logging', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p>
					<?php
					printf(
						esc_html__( 'Troubleshoot email delivery issues and track what emails have been sent. View entries in the %s.', 'paid-memberships-pro' ),
						'<a href="' . admin_url( 'admin.php?page=pmpro-reports&report=email_log' ) . '">' . esc_html__( 'Email Log Report', 'paid-memberships-pro' ) . '</a>'
					);
					?>
				</p>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<label for="email_logging_enabled"><?php esc_html_e( 'Email Logging', 'paid-memberships-pro' ); ?>:</label>
							</th>
							<td>
								<input type="checkbox" id="email_logging_enabled" name="email_logging_enabled" value="1" <?php checked( $email_logging_enabled ); ?> />
								<label for="email_logging_enabled"><?php esc_html_e( 'Enable email logging', 'paid-memberships-pro' ); ?></label>
								<p class="description">
									<?php esc_html_e( 'Check this to log emails to the database.', 'paid-memberships-pro' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="email_log_purge_days"><?php esc_html_e( 'Auto-Purge', 'paid-memberships-pro' ); ?>:</label>
							</th>
							<td>
								<input type="number" id="email_log_purge_days" name="email_log_purge_days" value="<?php echo esc_attr( $email_log_purge_days ); ?>" min="0" step="1" style="width: 100px;" />
								<label for="email_log_purge_days"><?php esc_html_e( 'days', 'paid-memberships-pro' ); ?></label>
								<p class="description">
									<?php esc_html_e( 'Automatically delete email log entries older than this many days. Set to 0 to disable auto-purge.', 'paid-memberships-pro' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label><?php esc_html_e( 'Purge All Entries', 'paid-memberships-pro' ); ?>:</label>
							</th>
							<td>
								<input type="checkbox" id="email_log_purge_all" name="email_log_purge_all" value="1" />
						<label for="email_log_purge_all"><?php esc_html_e( 'Purge all email log entries', 'paid-memberships-pro' ); ?></label>
								<p class="description">
									<?php esc_html_e( 'Check this and save to permanently delete all email log entries from the database. This action cannot be undone.', 'paid-memberships-pro' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<p class="submit">
			<input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
		</p>

	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
