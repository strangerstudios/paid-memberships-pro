<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_emailsettings")))
	{
		die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}	
	
	global $wpdb, $msg, $msgt;
	
	//get/set settings
	global $pmpro_pages;

	global $pmpro_email_templates_defaults, $current_user;	
				
	require_once(dirname(__FILE__) . "/admin_header.php");		
?>
<form action="" method="post" enctype="multipart/form-data"> 
	<?php wp_nonce_field('savesettings', 'pmpro_emailsettings_nonce');?>
	
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Email Templates', 'paid-memberships-pro' ); ?></h1>
	<hr class="wp-header-end">
	<p><?php esc_html_e( 'Select an email template from the dropdown below to customize the subject and body of emails sent through your membership site. You can also disable a specific email or send a test version through this admin page.', 'paid-memberships-pro' ); ?> <a href="https://www.paidmembershipspro.com/documentation/member-communications/list-of-pmpro-email-templates/" target="_blank"><?php esc_html_e( 'Click here for a description of each email sent to your members and admins at different stages of the member experience.', 'paid-memberships-pro'); ?></a></p>

	<div class="pmpro_admin_section pmpro_admin_section-email-templates-content">

		<table class="form-table">
			<tr class="status hide-while-loading" style="display:none;">
				<th scope="row" valign="top"></th>
				<td>
					<div id="message" class="status_message_wrapper">
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
						<option value="" selected="selected"><?php echo '--- ' . esc_html__( 'Select a Template to Edit', 'paid-memberships-pro' ) . ' ---'; ?></option>

					<?php foreach ( $pmpro_email_templates_defaults as $key => $template ): ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $template['description'] ); ?></option>

					<?php endforeach; ?>
					</select>
					<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="pmproet-spinner" style="display:none;"/>

					<p id="email_template_help_text" class="description"></p>
				</td>
			</tr>
			<tr class="hide-while-loading">
				<th scope="row" valign="top"></th>
				<td>
					<label><input id="email_template_disable" name="email_template_disable" type="checkbox"/><span
							id="disable_label"><?php esc_html_e( 'Disable this email?', 'paid-memberships-pro' ); ?></span></label>


					<p id="disable_description" class="description"><?php esc_html_e( 'Emails with this template will not be sent.', 'paid-memberships-pro' ); ?></p>

				</td>
			</tr>
			<tr class="hide-while-loading">
				<th scope="row" valign="top"><label for="email_template_subject"><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></label></th>

				<td>
					<input id="email_template_subject" name="email_template_subject" type="text" size="100"/>
				</td>
			</tr>
			<tr class="hide-while-loading">
				<th scope="row" valign="top"><label for="email_template_body"><?php esc_html_e( 'Body', 'paid-memberships-pro' ); ?></label></th>

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
					<input id="send_test_email" class="button" name="send_test_email" value="<?php esc_attr_e( 'Save Template and Send Email', 'paid-memberships-pro' ); ?>"

						type="button"/>

					<p class="description">
						<?php esc_html_e( 'Your current membership will be used for any membership level data.', 'paid-memberships-pro' ); ?>
					</p>
				</td>
			</tr>
			<tr class="controls hide-while-loading">
				<th scope="row" valign="top"></th>
				<td>
					<p class="submit">
						<input id="submit_template_data" name="save_template" type="button" class="button-primary"
							value="<?php esc_attr_e( 'Save Template', 'paid-memberships-pro' ); ?>"/>

						<input id="reset_template_data" name="reset_template" type="button" class="button"
							value="<?php esc_attr_e( 'Reset Template', 'paid-memberships-pro' ); ?>"/>

					</p>
				</td>
			</tr>
		</table>

		<hr />

		<div class="pmpro-email-templates-variable-reference">
			<h1><?php esc_html_e('Variable Reference', 'paid-memberships-pro');?></h1>
			<p><?php esc_html_e( 'Use the placeholder variables below to customize your member and admin emails with specific user or membership data.', 'paid-memberships-pro' ); ?></p>
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><?php esc_html_e('General Settings / Membership Info', 'paid-memberships-pro'); ?></th>
					<td>
						<table class="widefat striped">
							<tbody>
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
									<td><?php esc_html_e('Site Email Address (General Settings > Email OR Memberships > Settings > Email Settings)', 'paid-memberships-pro');?></td>
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
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Billing Information', 'paid-memberships-pro' ); ?></th>
					<td>
						<table class="widefat striped">
							<tbody>
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
							</tbody>
						</table>
					</td>
				</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro-email-templates-variable-reference -->

		<?php wp_nonce_field( 'pmproet', 'security' ); ?>

	</div> <!-- end pmpro_admin_section-email-templates-content -->
</form>
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
