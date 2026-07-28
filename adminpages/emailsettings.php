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
		<?php
		pmpro_build_settings_section( array(
			'id'     => 'send-emails-from-settings',
			'title'  => __( 'Send Emails From', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'html' => '<p>' . wp_kses_post( __( 'By default, system generated emails are sent from <em><strong>wordpress@yourdomain.com</strong></em>. You can update this from address using the fields below.', 'paid-memberships-pro' ) ) . '</p>',
				),
				array(
					'name'  => 'from_email',
					'label' => __( 'From Email', 'paid-memberships-pro' ),
					'type'  => 'text',
					'value' => $from_email,
				),
				array(
					'name'  => 'from_name',
					'label' => __( 'From Name', 'paid-memberships-pro' ),
					'type'  => 'text',
					'value' => wp_unslash( $from_name ),
				),
				array(
					'name'           => 'only_filter_pmpro_emails',
					'label'          => __( 'Only Filter PMPro Emails?', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'value'          => $only_filter_pmpro_emails,
					'checkbox_label' => sprintf(
						// translators: %s is the default "from" email address.
						__( 'If unchecked, all emails from "WordPress <%s>" will be filtered to use the above settings.', 'paid-memberships-pro' ),
						$default_from_email
					),
				),
				array(
					'type'  => 'submit',
					'label' => __( 'Save All Settings', 'paid-memberships-pro' ),
					'class' => 'button-primary',
				),
			),
		) );

		$email_method                  = pmpro_detect_email_method();
		$email_method_tag_class        = $email_method['source'] === 'default' ? 'inactive' : 'active';
		$transactional_email_docs_url  = 'https://www.paidmembershipspro.com/documentation/hosting-docs/transactional-email/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=documentation';
		$email_troubleshooting_doc_url = 'https://www.paidmembershipspro.com/troubleshooting-email-issues-sending-sent-spam-delivery-delays/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=blog&utm_content=email-troubleshooting';

		pmpro_build_settings_section( array(
			'id'     => 'email-deliverability-settings',
			'title'  => __( 'Email Deliverability', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'html' => function() use ( $email_method, $transactional_email_docs_url, $email_troubleshooting_doc_url ) {
						?>
						<p>
							<?php
							if ( $email_method['source'] === 'hosting' ) {
								// translators: %s: Link to Transactional Email doc.
								printf(
									esc_html__( 'Your PMPro Max plan includes transactional email delivery. This covers your password resets, payment receipts, and other system-generated membership notifications. Learn more about %s.', 'paid-memberships-pro' ),
									'<a title="' . esc_attr__( 'Paid Memberships Pro - Transactional Email', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="' . esc_url( $transactional_email_docs_url ) . '">' . esc_html__( 'transactional email', 'paid-memberships-pro' ) . '</a>'
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								// translators: %s: Link to Transactional Email doc.
								printf(
									esc_html__( 'Transactional email sending is included with a PMPro Max plan or higher. Learn more about %s.', 'paid-memberships-pro' ),
									'<a title="' . esc_attr__( 'Paid Memberships Pro - Transactional Email', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="' . esc_url( $transactional_email_docs_url ) . '">' . esc_html__( 'transactional email with Paid Memberships Pro', 'paid-memberships-pro' ) . '</a>'
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								echo ' ';

								// translators: %s: Link to the email troubleshooting guide.
								printf(
									esc_html__( 'Having trouble with email delivery? Read our %s.', 'paid-memberships-pro' ),
									'<a title="' . esc_attr__( 'Paid Memberships Pro - Email Troubleshooting', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="' . esc_url( $email_troubleshooting_doc_url ) . '">' . esc_html__( 'email troubleshooting guide', 'paid-memberships-pro' ) . '</a>'
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</p>
						<?php
					},
				),
				array(
					'label'    => __( 'Sending Method', 'paid-memberships-pro' ),
					'type'     => 'callback',
					'callback' => function() use ( $email_method, $email_method_tag_class ) {
						?>
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
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									break;
							}
							?>
						</p>
						<?php
					},
				),
			),
		) );

		pmpro_build_settings_section( array(
			'id'     => 'other-email-settings',
			'title'  => __( 'Other Email Settings', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'name'           => 'email_member_notification',
					'label'          => __( 'Send members emails', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'value'          => $email_member_notification,
					'checkbox_label' => __( 'Default WP notification email.', 'paid-memberships-pro' ),
					'description'    => __( 'Recommended: Leave unchecked. Members will still get an email confirmation from PMPro after checkout.', 'paid-memberships-pro' ),
				),
			),
		) );

		pmpro_build_settings_section( array(
			'id'     => 'email-logging-settings',
			'title'  => __( 'Email Logging', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'html' => '<p>' . sprintf(
						// translators: %s is a link to the Email Log Report.
						esc_html__( 'Troubleshoot email delivery issues and track what emails have been sent. View entries in the %s.', 'paid-memberships-pro' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-reports&report=email_log' ) ) . '">' . esc_html__( 'Email Log Report', 'paid-memberships-pro' ) . '</a>'
					) . '</p>',
				),
				array(
					'name'           => 'email_logging_enabled',
					'label'          => __( 'Email Logging', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'value'          => $email_logging_enabled,
					'checkbox_label' => __( 'Enable email logging', 'paid-memberships-pro' ),
					'description'    => __( 'Check this to log emails to the database.', 'paid-memberships-pro' ),
				),
				array(
					'label'       => __( 'Auto-Purge', 'paid-memberships-pro' ),
					'type'        => 'composite',
					'description' => __( 'Automatically delete email log entries older than this many days. Set to 0 to disable auto-purge.', 'paid-memberships-pro' ),
					'fields'      => array(
						array(
							'name'  => 'email_log_purge_days',
							'type'  => 'number',
							'value' => $email_log_purge_days,
							'attrs' => array(
								'min'  => 0,
								'step' => 1,
							),
						),
						__( 'days', 'paid-memberships-pro' ),
					),
				),
				array(
					'name'           => 'email_log_purge_all',
					'label'          => __( 'Purge All Entries', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'value'          => 0,
					'checkbox_label' => __( 'Purge all email log entries', 'paid-memberships-pro' ),
					'description'    => __( 'Check this and save to permanently delete all email log entries from the database. This action cannot be undone.', 'paid-memberships-pro' ),
				),
			),
		) );
		?>

		<p class="submit">
			<input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
		</p>

	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
