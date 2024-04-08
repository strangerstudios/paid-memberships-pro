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
		
		//assume success
		$msg = true;
		$msgt = "Your email settings have been updated.";		
	}
	
	$from_email = get_option( "pmpro_from_email");
	$from_name = get_option( "pmpro_from_name");
	$only_filter_pmpro_emails = get_option( "pmpro_only_filter_pmpro_emails");
	
	$email_admin_checkout = get_option( "pmpro_email_admin_checkout");
	$email_admin_changes = get_option( "pmpro_email_admin_changes");
	$email_admin_cancels = get_option( "pmpro_email_admin_cancels");
	$email_admin_billing = get_option( "pmpro_email_admin_billing");	
	
	$email_member_notification = get_option( "pmpro_email_member_notification");
	
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
							<input type="text" name="from_name" value="<?php echo esc_attr($from_name);?>" class="regular-text" />
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
				<p><?php
					$allowed_email_troubleshooting_html = array (
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
							'rel' => array(),
						),
						'em' => array(),
					);
					echo sprintf( wp_kses( __( 'If you are having issues with email delivery from your server, <a href="%s" title="Paid Memberships Pro - Subscription Delays Add On" target="_blank" rel="nofollow noopener">please read our email troubleshooting guide</a>. As an alternative, Paid Memberships Pro offers built-in integration for SendWP. <em>Optional: SendWP is a third-party service for transactional email in WordPress. <a href="%s" title="Documentation on SendWP and Paid Memberships Pro" target="_blank" rel="nofollow noopener">Click here to learn more about SendWP and Paid Memberships Pro</a></em>.', 'paid-memberships-pro' ), $allowed_email_troubleshooting_html ), 'https://www.paidmembershipspro.com/troubleshooting-email-issues-sending-sent-spam-delivery-delays/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=blog&utm_content=email-troubleshooting', 'https://www.paidmembershipspro.com/documentation/member-communications/email-delivery-sendwp/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=documentation&utm_content=sendwp' );
				?></p>

				<?php
					// Check to see if connected or not.
					$sendwp_connected = function_exists( 'sendwp_client_connected' ) && sendwp_client_connected() ? true : false;

					if ( ! $sendwp_connected ) { ?>
						<p><button id="pmpro-sendwp-connect" class="button"><?php esc_html_e( 'Connect to SendWP', 'paid-memberships-pro' ); ?></button></p>
					<?php } else { ?>
						<p><button id="pmpro-sendwp-disconnect" class="button-primary"><?php esc_html_e( 'Disconnect from SendWP', 'paid-memberships-pro' ); ?></button></p>
						<?php
						// Update SendWP status to see if email forwarding is enabled or not.
						$sendwp_email_forwarding = function_exists( 'sendwp_forwarding_enabled' ) && sendwp_forwarding_enabled() ? true : false;
						
						// Messages for connected or not.
						$connected = esc_html__( 'Your site is connected to SendWP.', 'paid-memberships-pro' ) . " <a href='https://app.sendwp.com/dashboard/' target='_blank' rel='nofollow noopener'>" . __( 'View Your SendWP Account', 'paid-memberships-pro' ) . "</a>";
						$disconnected = ' ' . sprintf( esc_html__( 'Please enable email sending inside %s.', 'paid-memberships-pro' ), '<a href="' . esc_url( admin_url('tools.php?page=sendwp') ) . '">SendWP Settings</a>' );
						?>
						<p class="description" id="pmpro-sendwp-description"><?php echo wp_kses_post( $sendwp_email_forwarding ? $connected : $disconnected ); ?></p>
					<?php }
				?>
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
				<p class="submit">            
					<input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
				</p>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
