<?php
	// Get the template data.
	$template_data['body'] = get_option( 'pmpro_email_' . $edit . '_body' );
	$template_data['subject'] = get_option( 'pmpro_email_' . $edit . '_subject' );
	$template_data['disabled'] = get_option( 'pmpro_email_' . $edit . '_disabled' );

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
		'general' => [
			'!!name!!'                  => __( 'Display Name (Profile/Edit User > Display name publicly as)', 'paid-memberships-pro' ),
			'!!user_login!!'            => __( 'Username', 'paid-memberships-pro' ),
			'!!sitename!!'              => __( 'Site Title', 'paid-memberships-pro' ),
			'!!siteemail!!'             => __( 'Site Email Address (General Settings > Email OR Memberships > Settings > Email Settings)', 'paid-memberships-pro' ),
			'!!membership_id!!'         => __( 'Membership Level ID', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => __( 'Membership Level Name', 'paid-memberships-pro' ),
			'!!membership_change!!'     => __( 'Membership Level Change', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => __( 'Membership Level Expiration', 'paid-memberships-pro' ),
			'!!startdate!!'             => __( 'Membership Start Date', 'paid-memberships-pro' ),
			'!!enddate!!'               => __( 'Membership End Date', 'paid-memberships-pro' ),
			'!!display_name!!'          => __( 'Display Name (Profile/Edit User > Display name publicly as)', 'paid-memberships-pro' ),
			'!!user_email!!'            => __( 'User Email', 'paid-memberships-pro' ),
			'!!login_url!!'            => __( 'Login URL', 'paid-memberships-pro' ),
			'!!levels_url!!'           => __( 'Membership Levels Page URL', 'paid-memberships-pro' ),
		],
		'billing' => [
			'!!billing_address!!' => __( 'Billing Info Complete Address', 'paid-memberships-pro' ),
			'!!billing_name!!'    => __( 'Billing Info Name', 'paid-memberships-pro' ),
			'!!billing_street!!'  => __( 'Billing Info Street Address', 'paid-memberships-pro' ),
			'!!billing_city!!'    => __( 'Billing Info City', 'paid-memberships-pro' ),
			'!!billing_state!!'   => __( 'Billing Info State', 'paid-memberships-pro' ),
			'!!billing_zip!!'     => __( 'Billing Info ZIP Code', 'paid-memberships-pro' ),
			'!!billing_country!!' => __( 'Billing Info Country', 'paid-memberships-pro' ),
			'!!billing_phone!!'   => __( 'Billing Info Phone #', 'paid-memberships-pro' ),
			'!!cardtype!!'        => __( 'Credit Card Type', 'paid-memberships-pro' ),
			'!!accountnumber!!'   => __( 'Credit Card Number (last 4 digits)', 'paid-memberships-pro' ),
			'!!expirationmonth!!' => __( 'Credit Card Expiration Month (mm format)', 'paid-memberships-pro' ),
			'!!expirationyear!!'  => __( 'Credit Card Expiration Year (yyyy format)', 'paid-memberships-pro' ),
			'!!membership_cost!!' => __( 'Membership Level Cost Text', 'paid-memberships-pro' ),
			'!!instructions!!'    => __( 'Payment Instructions (used in Checkout - Email Template)', 'paid-memberships-pro' ),
			'!!order_id!!'        => __( 'Order ID', 'paid-memberships-pro' ),
			'!!order_total!!'   => __( 'Order Total', 'paid-memberships-pro' ),
			'!!order_date!!'      => __( 'Order Date', 'paid-memberships-pro' ),
			'!!order_url!!'       => __( 'Order Page URL', 'paid-memberships-pro' ),
			'!!discount_code!!'   => __( 'Discount Code Applied', 'paid-memberships-pro' ),
			'!!membership_level_confirmation_message!!' => __( 'Custom Level Confirmation Message', 'paid-memberships-pro' ),
		]
	];
?>
<hr class="wp-header-end">
<div id="poststuff">
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

		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content" class="edit-form-section">
				<div class="pmpro_section">
					<div class="pmpro_section_inside">
						<fieldset>
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
									?>
									<tr>
										<th scope="row" valign="top"><label for="pmpro_email_template_subject"><?php esc_html_e( 'Subject', 'paid-memberships-pro' ); ?></label></th>
										<td>
											<input id="pmpro_email_template_subject" name="pmpro_email_template_subject" type="text" value="<?php echo esc_attr( $template_data['subject'] ); ?>" <?php echo filter_var( $template_data['disabled'], FILTER_VALIDATE_BOOLEAN ) ? 'disabled' : ''; ?> />
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
						<h3><?php esc_html_e('General Settings / Membership Info', 'paid-memberships-pro'); ?></h3>
						<table class="widefat fixed striped">
							<tbody>
								<?php
									foreach ( $email_variables['general'] as $email_variable => $description ) {
										?>
										<tr>
											<th><code><?php echo esc_html( $email_variable ); ?></code></th>
											<td><?php echo esc_html( $description ); ?></td>
										</tr>
										<?php
								}
								?>
							</tbody>
						</table>

						<h3><?php esc_html_e( 'Billing Information', 'paid-memberships-pro' ); ?></h3>
						<table class="widefat fixed striped">
							<tbody>
								<?php
									foreach ( $email_variables['billing'] as $email_variable => $description ) {
										?>
										<tr>
											<th>
												<code><?php echo esc_html( $email_variable ); ?></code>
											</th>
											<td><?php echo esc_html( $description ); ?></td>
										</tr>
										<?php
									}
								?>
							</tbody>
						</table>
					</div> <!-- end pmpro_section_inside -->
				</div> <!-- end pmpro_section -->
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="pmpro_section">
					<div class="pmpro_section_inside">
						<div class="pmpro_send_test_email">
							<label for="test_email_address"><?php esc_html_e( 'Send a test email to', 'paid-memberships-pro' ); ?></label>
							<input id="test_email_address" name="test_email_address" type="email" value="<?php echo esc_attr( $current_user->user_email ); ?> "/>
							<input id="send_test_email" class="button" name="send_test_email" value="<?php esc_attr_e( 'Save Template and Send Email', 'paid-memberships-pro' ); ?>" type="button" />
							<p class="description">
								<?php esc_html_e( 'Your current membership will be used for any membership level data.', 'paid-memberships-pro' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>
		
		<?php wp_nonce_field( 'pmproet', 'security' ); ?>

		</div>

	</form>
</div>
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
