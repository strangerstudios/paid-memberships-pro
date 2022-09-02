<?php
// Variables that may exist prior to running the Setup Wizard.
$pmpro_license_key = get_option( 'pmpro_license_key' );
$site_type         = get_option( 'pmpro_site_type' );
$collect_payment   = get_option( 'pmpro_wizard_collect_payment' );

// Check if we should allow page generation or not.
global $pmpro_pages;
	if ( $pmpro_pages['account'] ||
		$pmpro_pages['billing'] ||
		$pmpro_pages['cancel'] ||
		$pmpro_pages['checkout'] ||
		$pmpro_pages['confirmation'] ||
		$pmpro_pages['invoice'] ||
		$pmpro_pages['levels'] ||
		$pmpro_pages['member_profile_edit'] ) { 
			$member_pages_exist = true;
	} else {
		$member_pages_exist = false;
	}
?>

<div class="pmpro-wizard__step pmpro-wizard__step-1">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Welcome to Your New Membership Site', 'paid-memberships-pro' ); ?></h1>
					<p><?php esc_html_e( 'Tell us about your membership site to get up and running in 5 easy steps.', 'paid-memberships-pro' ); ?></p>
				</div>
				<form action="" method="post">
					<div class="pmpro-wizard__field">
						<label class="pmpro-wizard__label-block">
							<?php esc_html_e( 'What type of membership site are you creating?', 'paid-memberships-pro' ); ?>
						</label>
						<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Choose the answer that best fits the primary value of your membership site.', 'paid-memberships-pro' ); ?></p>
						<select id="membership_site_type" name="membership_site_type" class="pmpro-wizard__field-block">
							<option value=""><?php esc_html_e( '-- Select --', 'paid-memberships-pro' ); ?></option>
							<option value="association" <?php selected( 'association', $site_type, true ); ?>><?php esc_html_e( 'Association', 'paid-memberships-pro' ); ?></option>
							<option value="community" <?php selected( 'community', $site_type, true ); ?>><?php esc_html_e( 'Community', 'paid-memberships-pro' ); ?></option>
							<option value="courses" <?php selected( 'courses', $site_type, true ); ?>><?php esc_html_e( 'Courses', 'paid-memberships-pro' ); ?></option>
							<option value="digital_downloads" <?php selected( 'digital_downloads', $site_type, true ); ?>><?php esc_html_e( 'Digital Downloads', 'paid-memberships-pro' ); ?></option>
							<option value="directory" <?php selected( 'directory', $site_type, true ); ?>><?php esc_html_e( 'Directory/Profiles', 'paid-memberships-pro' ); ?></option>
							<option value="physical_products" <?php selected( 'physical_products', $site_type, true ); ?>><?php esc_html_e( 'Physical Products', 'paid-memberships-pro' ); ?></option>
							<option value="premium_content" <?php selected( 'premium_content', $site_type, true ); ?>><?php esc_html_e( 'Premium Content', 'paid-memberships-pro' ); ?></option>
							<option value="other" <?php selected( 'other', $site_type, true ); ?>><?php esc_html_e( 'Other', 'paid-memberships-pro' ); ?></option>
						</select>
					</div>
					<div class="pmpro-wizard__field">
						<label class="pmpro-wizard__label-block">
							<input type="checkbox" name="createpages" id="createpages" value="1" <?php disabled( true, $member_pages_exist); ?>>
							<label for="createpages"><?php esc_html_e( 'Yes, generate the required plugin pages for me.', 'paid-memberships-pro' ); ?></label><br/>
							<?php if ( $member_pages_exist ) {
								echo '<span style="font-size:12px;">' . esc_html__( 'We detected you have pages assigned for Paid Memberships Pro, this option is disabled.', 'paid-memberships-pro' ) . '</span>';
							} ?>
							<br><br>
							<input type="checkbox" name="collect_payments" id="collect_payments" value="1" <?php checked( true, $collect_payment ); ?>>
							<label for="collect_payments"><?php esc_html_e( 'Yes, I will be collecting payments for your memberships?', 'paid-memberships-pro' ); ?></label><br/>
						</label>
					</div>
					<div class="pmpro-wizard__field">
						<label class="pmpro-wizard__label-block">
							<?php esc_html_e( 'Enter Your Support License Key (optional)', 'paid-memberships-pro' ); ?>
						</label>
						<p class="pmpro-wizard__field-description"><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?> <a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-wizard&utm_campaign=pricing&utm_content=view-plans-pricing" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a></p>
						<input type="text" name="pmpro_license_key" id="pmpro_license_key" class="pmpro-wizard__field-block" value="<?php esc_attr_e( $pmpro_license_key ); ?>">
					</div>
					<p class="pmpro_wizard__submit">
						<?php wp_nonce_field( 'pmpro_wizard_step_1_nonce', 'pmpro_wizard_step_1_nonce' ); ?>
						<input type="hidden" name="wizard-action" value="step-1"/>
						<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
						<span class="pmpro_wizard__skip" onclick="window.location.href='/wp-admin/admin.php?page=pmpro-wizard&step=payments';"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></span>
					</p>
				</form>
			</div> <!-- end pmpro-wizard__step-1 -->
			<!-- onclick="window.location.href = '/wp-admin/admin.php?page=pmpro-wizard&step=memberships'; " -->
