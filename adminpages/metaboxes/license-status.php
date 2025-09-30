<?php
/**
 * Paid Memberships Pro Dashboard License Status Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 3.5
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_license_status_callback() { ?>
	<?php
		// Get saved license.
		$key = get_option( 'pmpro_license_key', '' );
		$pmpro_license_check = get_option( 'pmpro_license_check', array( 'license' => false, 'enddate' => 0 ) );
	?>
	<?php if ( ! pmpro_license_isValid() && empty( $key ) ) { ?>
		<p class="pmpro_message pmpro_error">
			<strong><?php esc_html_e( 'No support license key found.', 'paid-memberships-pro' ); ?></strong><br />
			<?php echo wp_kses_post( sprintf(__( '<a href="%s">Enter your key here</a>', 'paid-memberships-pro' ), esc_url( admin_url( 'admin.php?page=pmpro-license' ) ) ) );?>
		</p>
	<?php } elseif ( ! pmpro_license_isValid() ) { ?>
		<p class="pmpro_message pmpro_alert">
			<strong><?php esc_html_e( 'Your license is invalid or expired.', 'paid-memberships-pro' ); ?></strong><br />
			<?php echo wp_kses_post( sprintf(__( '<a href="%s">View your membership account</a> to verify your license key.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-dashboard%26utm_campaign%3Dmembership-account%26utm_content%3Dverify-license-key' ) );?>
		</p>
		<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a></p>
	<?php } elseif ( pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
		<p class="pmpro_message pmpro_alert"><?php echo wp_kses_post( sprintf(__('Your <strong>%1$s</strong> key is active. %1$s accounts include access to documentation and free downloads.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ) );?></p>
		<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a></p>
	<?php } else { ?>
		<p class="pmpro_message pmpro_success"><?php echo wp_kses_post( sprintf(__( '<strong>Thank you!</strong> A valid <strong>%s</strong> license key has been used to activate your support license on this site.', 'paid-memberships-pro' ), ucwords($pmpro_license_check['license'])));?></p>
		<p><a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage My Account', 'paid-memberships-pro' ); ?></a></p>
		<p><a class="button button-hero" href="https://www.paidmembershipspro.com/login/?redirect_to=%2Fnew-topic%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dsupport%26utm_content%3Dnew-support-ticket" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Support Ticket', 'paid-memberships-pro' ); ?></a></p>
	<?php } ?>

	<?php if ( ! pmpro_license_isValid() || pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
		<hr />
		<p><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?></p>
		<p><a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=pricing&utm_content=upgrade" target="_blank" rel="noopener noreferrer" class="button button-hero"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a></p>
	<?php } ?>
<?php
}