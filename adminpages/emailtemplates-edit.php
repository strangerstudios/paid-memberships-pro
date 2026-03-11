<?php
	// Get the template data.
	$template_data['body'] = get_option( 'pmpro_email_' . $edit . '_body' );
	$template_data['subject'] = get_option( 'pmpro_email_' . $edit . '_subject' );
	$template_data['disabled'] = get_option( 'pmpro_email_' . $edit . '_disabled' );
	$template_data['to'] = get_option( 'pmpro_email_' . $edit . '_to' );
	$template_data['cc'] = get_option( 'pmpro_email_' . $edit . '_cc' );
	$template_data['bcc'] = get_option( 'pmpro_email_' . $edit . '_bcc' );

	// If not found, load template from defaults.
	if ( empty( $template_data['body'] ) ) {
		$template_data['body'] = pmpro_email_templates_get_template_body( $edit );
	}
	if ( empty( $template_data['subject'] ) && ! in_array( $edit, array( 'header', 'footer' ) ) ) {
		$template_data['subject'] = $pmpro_email_templates_defaults[$edit]['subject'];
	}

	// Get template description and help text from defaults.
	$template_data['description'] = $pmpro_email_templates_defaults[$edit]['description'];
	$template_data['help_text'] = $pmpro_email_templates_defaults[$edit]['help_text'];

	// Email variables.
	$email_variables = [
		esc_html__( 'General Settings / Membership Info', 'paid-memberships-pro' ) => [
			'!!name!!'                  => esc_html__( 'Display Name (Profile/Edit User > Display name publicly as)', 'paid-memberships-pro' ),
			'!!user_login!!'            => esc_html__( 'Username', 'paid-memberships-pro' ),
			'!!sitename!!'              => esc_html__( 'Site Title', 'paid-memberships-pro' ),
			'!!siteemail!!'             => esc_html__( 'Site Email Address (General Settings > Email OR Memberships > Settings > Email Settings)', 'paid-memberships-pro' ),
			'!!membership_id!!'         => esc_html__( 'Membership Level ID', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => esc_html__( 'Membership Level Name', 'paid-memberships-pro' ),
			'!!membership_change!!'     => esc_html__( 'Membership Level Change', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => esc_html__( 'Membership Level Expiration', 'paid-memberships-pro' ),
			'!!startdate!!'             => esc_html__( 'Membership Start Date', 'paid-memberships-pro' ),
			'!!enddate!!'               => esc_html__( 'Membership End Date', 'paid-memberships-pro' ),
			'!!display_name!!'          => esc_html__( 'Display Name (Profile/Edit User > Display name publicly as)', 'paid-memberships-pro' ),
			'!!user_email!!'            => esc_html__( 'User Email', 'paid-memberships-pro' ),
			'!!login_url!!'            => esc_html__( 'Login URL', 'paid-memberships-pro' ),
			'!!levels_url!!'           => esc_html__( 'Membership Levels Page URL', 'paid-memberships-pro' ),
		],
		esc_html__( 'Billing Information', 'paid-memberships-pro' ) => [
			'!!billing_address!!' => esc_html__( 'Billing Info Complete Address', 'paid-memberships-pro' ),
			'!!billing_name!!'    => esc_html__( 'Billing Info Name', 'paid-memberships-pro' ),
			'!!billing_street!!'  => esc_html__( 'Billing Info Street Address', 'paid-memberships-pro' ),
			'!!billing_city!!'    => esc_html__( 'Billing Info City', 'paid-memberships-pro' ),
			'!!billing_state!!'   => esc_html__( 'Billing Info State', 'paid-memberships-pro' ),
			'!!billing_zip!!'     => esc_html__( 'Billing Info ZIP Code', 'paid-memberships-pro' ),
			'!!billing_country!!' => esc_html__( 'Billing Info Country', 'paid-memberships-pro' ),
			'!!billing_phone!!'   => esc_html__( 'Billing Info Phone #', 'paid-memberships-pro' ),
			'!!cardtype!!'        => esc_html__( 'Credit Card Type', 'paid-memberships-pro' ),
			'!!accountnumber!!'   => esc_html__( 'Credit Card Number (last 4 digits)', 'paid-memberships-pro' ),
			'!!expirationmonth!!' => esc_html__( 'Credit Card Expiration Month (mm format)', 'paid-memberships-pro' ),
			'!!expirationyear!!'  => esc_html__( 'Credit Card Expiration Year (yyyy format)', 'paid-memberships-pro' ),
			'!!membership_cost!!' => esc_html__( 'Membership Level Cost Text', 'paid-memberships-pro' ),
			'!!instructions!!'    => esc_html__( 'Payment Instructions (used in Checkout - Email Template)', 'paid-memberships-pro' ),
			'!!order_id!!'        => esc_html__( 'Order ID', 'paid-memberships-pro' ),
			'!!order_total!!'   => esc_html__( 'Order Total', 'paid-memberships-pro' ),
			'!!order_date!!'      => esc_html__( 'Order Date', 'paid-memberships-pro' ),
			'!!order_url!!'       => esc_html__( 'Order Page URL', 'paid-memberships-pro' ),
			'!!discount_code!!'   => esc_html__( 'Discount Code Applied', 'paid-memberships-pro' ),
			'!!membership_level_confirmation_message!!' => esc_html__( 'Custom Level Confirmation Message', 'paid-memberships-pro' ),
		]
	];

	// If we have a PMPro_Email_Template class for this template, use those variables instead.
	$email_template_class = PMPro_Email_Template::get_email_template( $edit );
	if ( $email_template_class ) {
		$email_variables = array(
			esc_html__( 'Global Variables', 'paid-memberships-pro' ) => PMPro_Email_Template::get_base_email_template_variables_with_description(),
			sprintf( esc_html__( '%s Variables', 'paid-memberships-pro' ), $email_template_class::get_template_name() ) => $email_template_class::get_email_template_variables_with_description(),
		);
	} elseif ( in_array( $edit, array( 'header', 'footer' ) ) ) {
		// The header and footer templates are special cases. Just show the globals.
		$email_variables = array(
			esc_html__( 'Global Variables', 'paid-memberships-pro' ) => PMPro_Email_Template::get_base_email_template_variables_with_description(),
		);
	}
?>
<hr class="wp-header-end">
<div id="message" class="status_message_wrapper">
	<p class="status_message"></p>
</div>
<form action="" method="post" enctype="multipart/form-data">
	<input id="edit" name="edit" type="hidden" value="<?php echo esc_attr( $edit ); ?>" />
	<input type="hidden" name="action" value="save_emailtemplate" />
	<?php wp_nonce_field('savesettings', 'pmpro_emailsettings_nonce');?>
	<h1 class="wp-heading-inline">
	<?php
		echo sprintf(
			// translators: %s is the Email Template Description.
			esc_html__('Edit Email Template: %s', 'paid-memberships-pro'),
			esc_attr( $template_data['description'] )
		);
		?>
	</h1>
	<a class="page-title-action" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-emailtemplates' ), admin_url( 'admin.php' ) ) );?>"><?php esc_html_e('View All Email Templates', 'paid-memberships-pro' ); ?></a>

	<div class="pmpro_two_col pmpro_two_col-right">
		<div class="pmpro_main">
			<div class="pmpro_section">
				<div class="pmpro_section_inside">
					<table class="form-table">
						<tr>
							<th scope="row" valign="top"><?php esc_html_e( 'Template', 'paid-memberships-pro' ); ?></th>
							<td>
								<strong><?php echo esc_html( $template_data['description'] ); ?></strong>
								<p class="description"><?php echo esc_html( $template_data['help_text'] ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top"><label for="pmpro_email_template_status"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></label></th>
							<td>
								<?php
									if ( filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ) {
										echo '<span class="pmpro_tag pmpro_tag-alert">' . esc_html__( 'Disabled', 'paid-memberships-pro' ) . '</span>';
									} else {
										echo '<span class="pmpro_tag pmpro_tag-success">' . esc_html__( 'Enabled', 'paid-memberships-pro' ) . '</span>';
									}
								?>
								<br />
								<label for="pmpro_email_template_disable">
									<input id="pmpro_email_template_disable" name="pmpro_email_template_disable" type="checkbox" <?php checked( $template_data['disabled'], 'true' ); ?> />
									<span id="disable_label">
										<?php
											if ( $edit === 'header' ) {
												echo esc_html__( 'Disable email header for all PMPro emails?', 'paid-memberships-pro' );
											} else if ( $edit === 'footer' ) {
												echo esc_html__( 'Disable email footer for all PMPro emails?', 'paid-memberships-pro' );
											} else {
												esc_html_e( 'Disable this email?', 'paid-memberships-pro' );

											}
										?>
									</span>
								</label>
								<?php
									if ( ! in_array( $edit, array( 'header', 'footer' ) ) ) {
										?>
										<p id="disable_description" class="description"><?php esc_html_e( 'Check this box to disable this email template. Emails with a disabled template will not be sent.', 'paid-memberships-pro' ); ?></p>
										<?php
									}
								?>
							</td>
						</tr>
						<?php
							if ( ! in_array( $edit, array( 'header', 'footer' ) ) ) {
								// Determine if this is a member email or admin email.
								$is_admin_email = ( strpos( $edit, '_admin' ) !== false );
								?>
								<tr>
									<th scope="row" valign="top"><label for="pmpro_email_template_to"><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></label></th>
									<td>
										<input id="pmpro_email_template_to" name="pmpro_email_template_to" type="text" value="<?php echo esc_attr( $template_data['to'] ); ?>" <?php echo filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ? 'disabled' : ''; ?> />
										<?php if ( $is_admin_email ) { ?>
											<p class="description"><?php printf(
												/* translators: %s: link to General Settings */
												esc_html__( 'The default recipient for this email is the administration email address set in %s.', 'paid-memberships-pro' ),
												'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '" target="_blank">' . esc_html__( 'Settings > General', 'paid-memberships-pro' ) . '</a>'
											); ?></p>
										<?php } else { ?>
											<p class="description"><?php esc_html_e( 'The default recipient for this email is the member. It is not recommended to change this.', 'paid-memberships-pro' ); ?></p>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<th scope="row" valign="top"><label for="pmpro_email_template_subject"><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></label></th>
									<td>
										<input id="pmpro_email_template_subject" name="pmpro_email_template_subject" type="text" value="<?php echo esc_attr( $template_data['subject'] ); ?>" <?php echo filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ? 'disabled' : ''; ?> />
									</td>
								</tr>
								<tr>
									<th scope="row" valign="top"><label for="pmpro_email_template_cc"><?php esc_html_e( 'CC', 'paid-memberships-pro' ); ?></label></th>
									<td>
										<input id="pmpro_email_template_cc" name="pmpro_email_template_cc" type="text" value="<?php echo esc_attr( $template_data['cc'] ); ?>" <?php echo filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ? 'disabled' : ''; ?> />
										<p class="description"><?php esc_html_e( 'Add one or more email addresses separated by commas.', 'paid-memberships-pro' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row" valign="top"><label for="pmpro_email_template_bcc"><?php esc_html_e( 'BCC', 'paid-memberships-pro' ); ?></label></th>
									<td>
										<input id="pmpro_email_template_bcc" name="pmpro_email_template_bcc" type="text" value="<?php echo esc_attr( $template_data['bcc'] ); ?>" <?php echo filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ? 'disabled' : ''; ?> />
										<p class="description"><?php esc_html_e( 'Add one or more email addresses separated by commas.', 'paid-memberships-pro' ); ?></p>
									</td>
								</tr>
								<?php
							}
						?>
						<tr>
							<th scope="row" valign="top"><label for="pmpro_email_template_body"><?php esc_html_e( 'Body', 'paid-memberships-pro' ); ?></label></th>
							<td>
								<div id="template_editor_container">
									<textarea rows="15" name="pmpro_email_template_body" id="pmpro_email_template_body" <?php echo filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ? 'disabled' : ''; ?>><?php echo esc_textarea( stripslashes( $template_data['body'] ) ); ?></textarea>
								</div>
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<input id="pmpro_submit_template_data" name="pmpro_save_template" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Template', 'paid-memberships-pro' ); ?>"/>
								<input id="pmpro_reset_template_data" name="pmpro_reset_template" type="button" class="button" value="<?php esc_attr_e( 'Reset Template', 'paid-memberships-pro' ); ?>"/>
							</td>
						</tr>
					</table>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			<div id="email-variable-reference" class="pmpro_section" data-visibility="shown" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
						<?php esc_html_e( 'Variable Reference', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside">
					<p><?php esc_html_e( 'Use the placeholder variables below to customize your member and admin emails with specific user or membership data.', 'paid-memberships-pro' ); ?></p>
					<?php
					foreach ( $email_variables as $section => $variables ) {
						?>
						<h3><?php echo esc_html( $section ); ?></h3>
						<table class="widefat fixed striped">
							<tbody>
								<?php
									foreach ( $variables as $email_variable => $description ) {
										?>
										<tr>
											<th><pre><?php echo esc_html( $email_variable ); ?></pre></th>
											<td><?php echo esc_html( $description ); ?></td>
										</tr>
										<?php
									}
								?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			<div id="liquid-reference" class="pmpro_section" data-visibility="hidden" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="false">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
						<?php esc_html_e( 'Advanced Email Template Logic', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside" style="display: none;">
					<p><?php esc_html_e( 'PMPro supports advanced email template logic using Liquid. Liquid is a simple templating syntax that lets your email templates format values, display dynamic values, and show different content based on conditions.', 'paid-memberships-pro' ); ?></p>
					<p>
						<?php
							$liquid_documentation_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Liquid Syntax for Email Templates Documentation', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/member-communications/liquid-syntax?utm_source=plugin&utm_medium=pmpro-emailtemplates&utm_campaign=documentation">' . esc_html__( 'Liquid Syntax documentation', 'paid-memberships-pro' ) . '</a>';
							// translators: %s: Link to Liquid Syntax documentation.
							printf( esc_html__('For advanced usage including filters, operators, and more examples, see the %s.', 'paid-memberships-pro' ), $liquid_documentation_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</p>
					<hr />
					<h3><?php esc_html_e( 'Examples of Liquid Syntax', 'paid-memberships-pro' ); ?></h3>
					<table class="widefat fixed striped">
						<tbody>
							<tr>
								<td><pre>{{ display_name }}</pre></td>
								<td><?php esc_html_e( 'Output the value of a variable.', 'paid-memberships-pro' ); ?></td>
							</tr>
							<tr>
								<td><pre>{{ display_name | upcase }}</pre></td>
								<td><?php esc_html_e( 'Apply a filter to transform the output.', 'paid-memberships-pro' ); ?></td>
							</tr>
							<tr>
								<td><pre>{{ display_name | default: "member" }}</pre></td>
								<td><?php esc_html_e( 'Use a default value if the variable is empty.', 'paid-memberships-pro' ); ?></td>
							</tr>
							<tr>
								<td><pre>{&#37; if discount_code_name &#37;}
&lt;p&gt;<?php echo esc_html__( 'Discount Code:', 'paid-memberships-pro' ); ?> {{ discount_code_name }}&lt;/p&gt;
{&#37; endif &#37;}</pre></td>
								<td><?php esc_html_e( 'Show content if a certain variable is not empty.', 'paid-memberships-pro' ); ?></td>
							</tr>
							<tr>
								<td><pre>{&#37; if order_total_raw &gt;= 250 &#37;}
&lt;p&gt;<?php echo esc_html__( 'Here is your personalized meal plan!', 'paid-memberships-pro' ); ?>&lt;/p&gt;
{&#37; else &#37;}
&lt;p&gt;<?php echo esc_html__( 'Thank you for becoming a member!', 'paid-memberships-pro' ); ?>&lt;/p&gt;
{&#37; endif &#37;}</pre></td>
								<td><?php esc_html_e( 'Show different content based on the value of a variable.', 'paid-memberships-pro' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
		</div> <!-- end pmpro_main -->
		<div class="pmpro_sidebar">
			<div class="pmpro_section">
				<div class="pmpro_section_inside">
					<div class="pmpro_send_test_email">
						<?php $pmpro_template_can_send_test_email =  $email_template_class && method_exists( $email_template_class, 'get_test_email_constructor_args' ); ?>
						<label for="test_email_address"><?php esc_html_e( 'Send a test email to', 'paid-memberships-pro' ); ?></label>
						<input id="test_email_address" name="test_email_address" type="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" <?php if ( ! $pmpro_template_can_send_test_email ) { ?> disabled="disabled" <?php } ?> />
						<input id="send_test_email" class="button" name="send_test_email" value="<?php esc_attr_e( 'Save Template and Send Email', 'paid-memberships-pro' ); ?>" type="button" <?php if ( ! $pmpro_template_can_send_test_email ) { ?> disabled="disabled" <?php } ?> />
						<?php
						if ( ! $pmpro_template_can_send_test_email ) {
							?>
							<p class="description"><?php esc_html_e( 'This template does not support test emails.', 'paid-memberships-pro' ); ?></p>
							<?php
						}
					?>
					</div> <!-- end pmpro_send_test_email -->
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
		</div> <!-- end pmpro_sidebar -->

		<?php wp_nonce_field( 'pmproet', 'security' ); ?>

	</div> <!-- end pmpro_two_col -->

</form>
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
