<?php
//only let admins get here
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_license') ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

//updating license?
if ( ! empty( $_REQUEST['pmpro-verify-submit'] ) ) {
	$key = preg_replace("/[^a-zA-Z0-9]/", "", sanitize_text_field( $_REQUEST['pmpro-license-key'] ) );
	
	// Check key.
	$pmpro_license_check = pmpro_license_check_key( $key );
	$r = pmpro_license_isValid( $key );
	
	// Update key.
	update_option( 'pmpro_license_key', $key, 'no' );
}

// Get values from options if not updating.
if ( empty( $key ) ) {
	$key = get_option( 'pmpro_license_key', '' );
}

if ( empty( $pmpro_license_check ) ) {
	$pmpro_license_check = get_option( 'pmpro_license_check', array( 'license' => false, 'enddate' => 0 ) );
}

// Allowed strings for kses checks below.
$allowed_pmpro_license_strings_html = array (
	'a' => array (
		'href' => array(),
		'target' => array(),
		'title' => array()
	),
	'strong' => array(),
	'em' => array()
);

// HTML for license settings page.
if ( defined( 'PMPRO_DIR' ) ) {
	require_once( PMPRO_DIR . '/adminpages/admin_header.php' );
} ?>
	<hr class="wp-header-end">
	<h1><?php esc_html_e('Paid Memberships Pro Support License', 'paid-memberships-pro' );?></h1>
	<div class="about-wrap">
		<div class="about-text">
			<?php if ( is_wp_error( $pmpro_license_check ) ) { ?>
				<p class="pmpro_message pmpro_error"><strong><?php echo esc_html( sprintf( __( 'There was an issue validating your license key: %s', 'paid-memberships-pro' ), $pmpro_license_check->get_error_message() ) );?></strong> <?php echo wp_kses( sprintf( __('Visit the PMPro <a href="%s" target="_blank">Membership Account</a> page to confirm that your account is active and to find your license key.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dkey-not-valid' ), $allowed_pmpro_license_strings_html );?></p>
			<?php } elseif( ! pmpro_license_isValid() && empty( $key ) ) { ?>
				<p class="pmpro_message pmpro_error"><?php echo wp_kses( sprintf( __( '<strong>Enter your support license key</strong>. Your license key can be found in your membership email receipt or in your <a href="%s" target="_blank">Membership Account</a>.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dno-key' ), $allowed_pmpro_license_strings_html );?></p>
			<?php } elseif( ! pmpro_license_isValid() ) { ?>
				<p class="pmpro_message pmpro_error"><strong><?php esc_html_e('Your license is invalid or expired.', 'paid-memberships-pro' );?></strong> <?php echo wp_kses( sprintf( __( 'Visit the PMPro <a href="%s" target="_blank">Membership Account</a> page to confirm that your account is active and to find your license key.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dkey-not-valid' ), $allowed_pmpro_license_strings_html );?></p>
			<?php } elseif ( pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
					<p class="pmpro_message pmpro_alert"><?php echo wp_kses( sprintf( __( 'Your <strong>%1$s</strong> key is active. %1$s accounts include access to documentation and free downloads.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ), $allowed_pmpro_license_strings_html );?></p>
			<?php } else { ?>
				<p class="pmpro_message pmpro_success"><?php echo wp_kses( sprintf( __('<strong>Thank you!</strong> A valid <strong>%s</strong> license key has been used to activate your support license on this site.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ), $allowed_pmpro_license_strings_html );?></p>
			<?php } ?>

			<form action="" method="post">
				<label for="pmpro-license-key"><?php esc_html_e( 'License Key', 'paid-memberships-pro' ); ?></label>
				<div id="pmpro-settings-key-box">
					<input type="text" name="pmpro-license-key" id="pmpro-license-key" value="<?php echo esc_attr($key);?>" placeholder="<?php _e('Enter license key here...', 'paid-memberships-pro' );?>" size="40" />
					<?php wp_nonce_field( 'pmpro-key-nonce', 'pmpro-key-nonce' ); ?>
					<?php submit_button( __( 'Validate Key', 'paid-memberships-pro' ), 'primary', 'pmpro-verify-submit', false ); ?>
				</div>
			</form>

			<p>
				<?php if ( ! pmpro_license_isValid() ) { ?>
					<a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-license&utm_campaign=pricing&utm_content=view-plans-pricing" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a>
				<?php } else { ?>
					<a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a>
					<?php if ( pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
						<a class="button button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fnew-topic%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dsupport%26utm_content%3Dnew-support-ticket" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Support Ticket', 'paid-memberships-pro' ); ?></a>
					<?php } else { ?>
						<a class="button button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fpricing%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dpricing%26utm_content%3Dupgrade-for-support" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade For Support', 'paid-memberships-pro' ); ?></a>
					<?php }
				} ?>
			</p>

			<hr />
			
			<div class="clearfix"></div>

			<img class="pmpro_icon alignright" src="<?php echo PMPRO_URL?>/images/Paid-Memberships-Pro_icon.png" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" />			

			<?php
				echo '<p>' . sprintf( wp_kses( __( 'Paid Memberships Pro and our Add Ons are distributed under the <a href="%s" target="_blank">GPLv2 license</a>. This means, among other things, that you may use the software on this site or any other site free of charge.', 'paid-memberships-pro' ), $allowed_pmpro_license_strings_html ), 'https://www.paidmembershipspro.com/features/paid-memberships-pro-is-100-gpl/?utm_source=plugin&utm_medium=pmpro-license&utm_campaign=documentation&utm_content=gpl' ) . '</p>';
			?>

			<?php
				echo '<p>' . wp_kses( __( '<strong>Paid Memberships Pro offers plans for automatic updates of Add Ons and premium support.</strong> These plans include a license key which we recommend for all public websites running Paid Memberships Pro. A license key allows you to automatically install Add Ons included in your plan and update active Add Ons included in your plan when a new security, bug fix, or feature enhancement is released.', 'paid-memberships-pro' ), $allowed_pmpro_license_strings_html ) . '</p>';
			?>

			<?php
				echo '<p>' . wp_kses( __( '<strong>Need help?</strong> Your license allows you to open new tickets in our private support area. Purchases are backed by a 30 day, no questions asked refund policy.', 'paid-memberships-pro' ), $allowed_pmpro_license_strings_html ) . '</p>';
			?>

			<p><a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-license&utm_campaign=pricing&utm_content=view-license-options" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Support License Options', 'paid-memberships-pro' ); ?></a></p>

		</div> <!-- end about-text -->
	</div> <!-- end about-wrap -->

<?php

require_once(dirname(__FILE__) . "/admin_footer.php");
?>
