<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_emailsettings")))
	{
		die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}	
	
	global $wpdb, $msg, $msgt;
	
	//get/set settings
	global $pmpro_pages;

	// Email Templates
	global $pmproet_test_order_id, $pmproet_email_defaults, $current_user;
	$pmproet_test_order_id = get_option( 'pmproet_test_order_id' );
	
	//check nonce for saving settings
	if (!empty($_REQUEST['savesettings']) && (empty($_REQUEST['pmpro_emailsettings_nonce']) || !check_admin_referer('savesettings', 'pmpro_emailsettings_nonce'))) {
		$msg = -1;
		$msgt = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
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
	
	$from_email = pmpro_getOption("from_email");
	$from_name = pmpro_getOption("from_name");
	$only_filter_pmpro_emails = pmpro_getOption("only_filter_pmpro_emails");
	
	$email_admin_checkout = pmpro_getOption("email_admin_checkout");
	$email_admin_changes = pmpro_getOption("email_admin_changes");
	$email_admin_cancels = pmpro_getOption("email_admin_cancels");
	$email_admin_billing = pmpro_getOption("email_admin_billing");	
	
	$email_member_notification = pmpro_getOption("email_member_notification");
	
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
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}
	$default_from_email = 'wordpress@' . $sitename;
				
	require_once(dirname(__FILE__) . "/admin_header.php");		
?>

	<form action="" method="post" enctype="multipart/form-data"> 
		<?php wp_nonce_field('savesettings', 'pmpro_emailsettings_nonce');?>
		
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Email Settings', 'paid-memberships-pro' ); ?></h1>
		<hr class="wp-header-end">
		<h2><?php _e( 'Send Emails From', 'paid-memberships-pro' ); ?></h2>
		<p><?php _e('By default, system generated emails are sent from <em><strong>wordpress@yourdomain.com</strong></em>. You can update this from address using the fields below.', 'paid-memberships-pro' );?></p>

		<table class="form-table">
		<tbody>                
			<tr>
				<th scope="row" valign="top">
					<label for="from_email"><?php _e('From Email', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<input type="text" name="from_email" value="<?php echo esc_attr($from_email);?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="from_name"><?php _e('From Name', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<input type="text" name="from_name" value="<?php echo esc_attr($from_name);?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="only_filter_pmpro_emails"><?php _e('Only Filter PMPro Emails?', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<input type="checkbox" id="only_filter_pmpro_emails" name="only_filter_pmpro_emails" value="1" <?php if(!empty($only_filter_pmpro_emails)) { ?>checked="checked"<?php } ?> />
					<label for="only_filter_pmpro_emails"><?php printf( __('If unchecked, all emails from "WordPress &lt;%s&gt;" will be filtered to use the above settings.', 'paid-memberships-pro' ),  $default_from_email );?></label>
				</td>
			</tr>
		</tbody>
		</table>
		<p class="submit"><input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" /></p>
		<hr />
		<div class="pmpro_admin_section pmpro_admin_section-email-deliverability">
			<h2><?php _e( 'Email Deliverability', 'paid-memberships-pro' ); ?></h2>

			<p><?php
				$allowed_email_troubleshooting_html = array (
					'a' => array (
						'href' => array(),
						'target' => array(),
						'title' => array(),
					),
					'em' => array(),
				);
				echo sprintf( wp_kses( __( 'If you are having issues with email delivery from your server, <a href="%s" title="Paid Memberships Pro - Subscription Delays Add On" target="_blank">please read our email troubleshooting guide</a>. As an alternative, Paid Memberships Pro offers built-in integration for SendWP. <em>Optional: SendWP is a third-party service for transactional email in WordPress. <a href="%s" title="Documentation on SendWP and Paid Memberships Pro" target="_blank">Click here to learn more about SendWP and Paid Memberships Pro</a></em>.', 'paid-memberships-pro' ), $allowed_email_troubleshooting_html ), 'https://www.paidmembershipspro.com/troubleshooting-email-issues-sending-sent-spam-delivery-delays/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=blog&utm_content=email-troubleshooting', 'https://www.paidmembershipspro.com/documentation/member-communications/email-delivery-sendwp/?utm_source=plugin&utm_medium=pmpro-emailsettings&utm_campaign=documentation&utm_content=sendwp' );
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
					$connected = __( 'Your site is connected to SendWP.', 'paid-memberships-pro' ) . " <a href='https://sendwp.com/account/' target='_blank' rel='nofollow'>" . __( 'View Your SendWP Account', 'paid-memberships-pro' ) . "</a>";
					$disconnected = ' ' . sprintf( __( 'Please enable email sending inside %s.', 'paid-memberships-pro' ), '<a href="' . admin_url('/tools.php?page=sendwp') . '">SendWP Settings</a>' );
					?>
					<p class="description" id="pmpro-sendwp-description"><?php echo $sendwp_email_forwarding ? $connected : $disconnected; ?></p>
				<?php }
			?>
		</div> <!-- end pmpro_admin_section-email-deliverability -->
		<hr />
		<h2 class="title"><?php esc_html_e( 'Other Email Settings', 'paid-memberships-pro' ); ?></h2>
		<table class="form-table">
		<tbody>                
			<tr>
				<th scope="row" valign="top">
					<label for="email_admin"><?php _e('Send the site admin emails', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<input type="checkbox" id="email_admin_checkout" name="email_admin_checkout" value="1" <?php if(!empty($email_admin_checkout)) { ?>checked="checked"<?php } ?> />
					<label for="email_admin_checkout"><?php _e('when a member checks out.', 'paid-memberships-pro' );?></label>
					<br />
					<input type="checkbox" id="email_admin_changes" name="email_admin_changes" value="1" <?php if(!empty($email_admin_changes)) { ?>checked="checked"<?php } ?> />
					<label for="email_admin_changes"><?php _e('when an admin changes a user\'s membership level through the dashboard.', 'paid-memberships-pro' );?></label>
					<br />
					<input type="checkbox" id="email_admin_cancels" name="email_admin_cancels" value="1" <?php if(!empty($email_admin_cancels)) { ?>checked="checked"<?php } ?> />
					<label for="email_admin_cancels"><?php _e('when a user cancels his or her account.', 'paid-memberships-pro' );?></label>
					<br />
					<input type="checkbox" id="email_admin_billing" name="email_admin_billing" value="1" <?php if(!empty($email_admin_billing)) { ?>checked="checked"<?php } ?> />
					<label for="email_admin_billing"><?php _e('when a user updates his or her billing information.', 'paid-memberships-pro' );?></label>
				</td>
			</tr>               
			<tr>
				<th scope="row" valign="top">
					<label for="email_member_notification"><?php _e('Send members emails', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<input type="checkbox" id="email_member_notification" name="email_member_notification" value="1" <?php if(!empty($email_member_notification)) { ?>checked="checked"<?php } ?> />
					<label for="email_member_notification"><?php _e('Default WP notification email.', 'paid-memberships-pro' );?></label>
					<p class="description"><?php _e( 'Recommended: Leave unchecked. Members will still get an email confirmation from PMPro after checkout.', 'paid-memberships-pro' ); ?></p>
				</td>
			</tr>
		</tbody>
		</table>
		<hr/>
		<div class="pmpro_admin_section pmpro_admin_section-email-content">
			<h2><?php esc_html_e( 'Customize Email Templates', 'paid-memberships-pro' ); ?></h2>
		<table class="form-table">
		<tr class="status hide-while-loading" style="display:none;">
			<th scope="row" valign="top"></th>
			<td>
				<div id="message">
					<p class="status_message"></p>
				</div>

			</td>
		</tr>
		<tr>
		<th scope="row" valign="top">
			<label for="pmpro_email_template_switcher"><?php esc_html_e( 'Email Template', 'paid-memberships-pro' ); ?></label>
		</th>
		<td>
		<select name="pmpro_email_template_switcher" id="pmpro_email_template_switcher">
		<option value="" selected="selected"><?php echo '--- ' . __( 'Select a Template to Edit', 'paid-memberships-pro' ) . ' ---'; ?></option>
		<option value="header"><?php _e('Email Header', 'paid-memberships-pro'); ?></option>
		<option value="footer"><?php _e('Email Footer', 'paid-memberships-pro'); ?></option>
		<?php foreach ( $pmproet_email_defaults as $key => $template ): ?>
		<option value="<?php echo $key; ?>"><?php echo $template['description']; ?></option>
		<?php endforeach; ?>
		</select>
		<img src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" id="pmproet-spinner" style="display:none;"/>
		<hr>
		</td>
		</tr>
		<tr class="hide-while-loading">
			<th scope="row" valign="top"></th>
			<td>
				<label><input id="email_template_disable" name="email_template_disable" type="checkbox"/><span
						id="disable_label"><?php esc_html_e('Disable this email?', 'paid-memberships-pro');?></span></label>

				<p id="disable_description" class="description small"><?php esc_html_e('Emails with this template will not be sent.', 'paid-memberships-pro');?></p>
			</td>
		</tr>
		<tr class="hide-while-loading">
			<th scope="row" valign="top"><label for="email_template_subject"><?php esc_html_e('Subject', 'paid-memberships-pro');?></label></th>
			<td>
				<input id="email_template_subject" name="email_template_subject" type="text" size="100"/>
			</td>
		</tr>
		<tr class="hide-while-loading">
			<th scope="row" valign="top"><label for="email_template_body"><?php esc_html_e('Body', 'paid-memberships-pro');?></label></th>
			<td>
				<div id="template_editor_container">
					<textarea rows="10" cols="80" name="email_template_body" id="email_template_body"></textarea>
				</div>
			</td>
		</tr>
		<tr class="hide-while-loading">
			<th scope="row" valign="top"></th>
			<td>
				<?php esc_html_e( 'Send a test email to ', 'paid-memberships-pro' ); ?>
				<input id="test_email_address" name="test_email_address" type="text"
					value="<?php echo esc_attr( $current_user->user_email ); ?>"/>
				<input id="send_test_email" class="button" name="send_test_email" value="<?php esc_attr_e('Save Template and Send Email', 'paid-memberships-pro');?>"
					type="button"/>

				<p class="description">
					<a href="<?php echo add_query_arg( array( 'page'  => 'pmpro-orders',
															'order' => $pmproet_test_order_id
					), admin_url( 'admin.php' ) ); ?>"
					target="_blank"><?php esc_html_e( 'Click here to edit the order used for test emails.', 'paid-memberships-pro' ); ?></a>
					<?php esc_html_e( 'Your current membership will be used for any membership level data.', 'paid-memberships-pro' ); ?>
				</p>
			</td>
		</tr>
		<tr class="controls hide-while-loading">
			<th scope="row" valign="top"></th>
			<td>
				<p class="submit">
					<input id="submit_template_data" name="save_template" type="button" class="button-primary"
						value="Save Template"/>
					<input id="reset_template_data" name="reset_template" type="button" class="button"
						value="Reset Template"/>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"></th>
			<td>
				<h3><?php esc_html_e('Variable Reference', 'paid-memberships-pro');?></h3>
				<div id="template_reference" style="overflow:scroll;height:250px;width:800px;;">
					<table class="widefat striped">
						<tr>
							<th colspan=2><?php esc_html_e('General Settings / Membership Info', 'paid-memberships-pro');?></th>
						</tr>
						<tr>
							<td>!!name!!</td>
							<td><?php esc_html_e('Display Name (Profile/Edit User > Display name publicly as)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!user_login!!</td>
							<td><?php esc_html_e('Username', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!sitename!!</td>
							<td><?php esc_html_e('Site Title', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!siteemail!!</td>
							<td><?php esc_html_e('Site Email Address (General Settings > Email OR Memberships > Email Settings)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!membership_id!!</td>
							<td><?php esc_html_e('Membership Level ID', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!membership_level_name!!</td>
							<td><?php esc_html_e('Membership Level Name', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!membership_change!!</td>
							<td><?php esc_html_e('Membership Level Change', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!membership_expiration!!</td>
							<td><?php esc_html_e('Membership Level Expiration', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!display_name!!</td>
							<td><?php esc_html_e('Display Name (Profile/Edit User > Display name publicly as)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!enddate!!</td>
							<td><?php esc_html_e('User Subscription End Date', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!user_email!!</td>
							<td><?php esc_html_e('User Email', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!login_link!!</td>
							<td><?php esc_html_e('Login URL', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!levels_link!!</td>
							<td><?php esc_html_e('Membership Levels Page URL', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<th colspan=2>Billing Information</th>
						</tr>
						<tr>
							<td>!!billing_address!!</td>
							<td><?php esc_html_e('Billing Info Complete Address', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_name!!</td>
							<td><?php esc_html_e('Billing Info Name', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_street!!</td>
							<td><?php esc_html_e('Billing Info Street Address', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_city!!</td>
							<td><?php esc_html_e('Billing Info City', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_state!!</td>
							<td><?php esc_html_e('Billing Info State', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_zip!!</td>
							<td><?php esc_html_e('Billing Info ZIP Code', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_country!!</td>
							<td><?php esc_html_e('Billing Info Country', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!billing_phone!!</td>
							<td><?php esc_html_e('Billing Info Phone #', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!cardtype!!</td>
							<td><?php esc_html_e('Credit Card Type', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!accountnumber!!</td>
							<td><?php esc_html_e('Credit Card Number (last 4 digits)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!expirationmonth!!</td>
							<td><?php esc_html_e('Credit Card Expiration Month (mm format)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!expirationyear!!</td>
							<td><?php esc_html_e('Credit Card Expiration Year (yyyy format)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!membership_cost!!</td>
							<td><?php esc_html_e('Membership Level Cost Text', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!instructions!!</td>
							<td><?php esc_html_e('Payment Instructions (used in Checkout - Email Template)', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!invoice_id!!</td>
							<td><?php esc_html_e('Invoice ID', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!invoice_total!!</td>
							<td><?php esc_html_e('Invoice Total', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!invoice_date!!</td>
							<td><?php esc_html_e('Invoice Date', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!discount_code!!</td>
							<td><?php esc_html_e('Discount Code Applied', 'paid-memberships-pro');?></td>
						</tr>
						<tr>
							<td>!!invoice_link!!</td>
							<td><?php esc_html_e('Invoice Page URL', 'paid-memberships-pro');?></td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		</table>
		<?php wp_nonce_field( 'pmproet', 'security' ); ?>

		</div> <!-- end pmpro_admin_section-email-content -->
		
		<p class="submit">            
			<input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
		</p> 
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
