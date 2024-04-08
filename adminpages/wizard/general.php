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
		<h2><?php esc_html_e( 'Welcome to Your New Membership Site', 'paid-memberships-pro' ); ?></h2>
		<p><?php esc_html_e( 'Tell us about your membership site to get up and running in 5 easy steps.', 'paid-memberships-pro' ); ?></p>
	</div>
	<form action="" method="post">
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block" for="membership_site_type">
				<?php esc_html_e( 'What type of membership site are you creating?', 'paid-memberships-pro' ); ?>
			</label>
			<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Choose the answer that best fits the primary value of your membership site.', 'paid-memberships-pro' ); ?></p>
			<select id="membership_site_type" name="membership_site_type" class="pmpro-wizard__field-block">
				<option value=""><?php esc_html_e( '-- Select --', 'paid-memberships-pro' ); ?></option>
				<?php
				$site_types = pmpro_wizard_get_site_types();
				foreach ( $site_types as $site_type_key => $name ) {
					?>
					<option value="<?php echo esc_attr( $site_type_key ); ?>" <?php selected( $site_type_key, $site_type ); ?>><?php echo esc_html( $name ); ?></option>
					<?php
				}
				?>
			</select>
		</div>
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block" for="createpages">
				<input type="checkbox" name="createpages" id="createpages" value="1" checked <?php disabled( true, $member_pages_exist); ?>>
				<?php esc_html_e( 'Yes, generate the required plugin pages for me. (Recommended)', 'paid-memberships-pro' ); ?>
			</label>
			<?php if ( $member_pages_exist ) {
				echo '<p class="pmpro-wizard__field-description">' . esc_html__( 'We detected you have pages assigned for Paid Memberships Pro, this option is disabled.', 'paid-memberships-pro' ) . '</p>';
			} else {
				echo '<p class="pmpro-wizard__field-description">' . esc_html__( 'We will automatically create frontend pages for your levels, checkout, account management, and more.', 'paid-memberships-pro' ) . '</p>';
			} ?>
		</div>
		<div class="pmpro-wizard__field">
			<label class="pmpro-wizard__label-block" for="collect_payments">
				<input type="checkbox" name="collect_payments" id="collect_payments" value="1" <?php checked( true, $collect_payment ); ?>>
				<?php esc_html_e( 'Yes, I will be collecting payments for my memberships.', 'paid-memberships-pro' ); ?>
			</label>
		</div>
		<div class="pmpro-wizard__field pmpro_admin">
			<label class="pmpro-wizard__label-block" for="pmpro_license_key">
				<?php esc_html_e( 'Enter Your Support License Key (optional)', 'paid-memberships-pro' ); ?>
			</label>
			<?php
			// Check if the user tried to submit a license key, but is still on this page.
			// If so, the license wasn't valid. Show an error.
			if ( ! empty( $_REQUEST['pmpro_license_key'] ) ) {
				?>
				<p class="pmpro_message pmpro_error"><?php esc_html_e( 'The license key you entered is invalid. Please try again.', 'paid-memberships-pro' ); ?></p>
				<?php
			}
			?>
			<p class="pmpro-wizard__field-description"><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?> <a aria-label="<?php esc_attr_e( 'View plans and pricing for Paid Memberships Pro optional licenses in a new tab', 'paid-memberships-pro' ); ?>" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-wizard&utm_campaign=pricing&utm_content=view-plans-pricing" target="_blank"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a></p>
			<input type="text" name="pmpro_license_key" id="pmpro_license_key" class="pmpro-wizard__field-block" value="<?php echo esc_attr( $pmpro_license_key ); ?>">
		</div>
		<p class="pmpro_wizard__submit">
			<?php wp_nonce_field( 'pmpro_wizard_step_1_nonce', 'pmpro_wizard_step_1_nonce' ); ?>
			<input type="hidden" name="wizard-action" value="step-1"/>
			<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
			<a class="pmpro_wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=payments' ) );?>"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></a>
		</p>
	</form>
</div> <!-- end pmpro-wizard__step-1 -->
