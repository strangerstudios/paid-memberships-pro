<?php
/**
 * Paid Memberships Pro Dashboard Welcome Meta Box (side)
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 2.6.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_welcome_side_callback() { ?>
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
	<?php } elseif ( pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
		<p class="pmpro_message pmpro_alert"><?php echo wp_kses_post( sprintf(__('Your <strong>%1$s</strong> key is active. %1$s accounts include access to documentation and free downloads.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ) );?></p>
	<?php } else { ?>
		<p class="pmpro_message pmpro_success"><?php echo wp_kses_post( sprintf(__( '<strong>Thank you!</strong> A valid <strong>%s</strong> license key has been used to activate your support license on this site.', 'paid-memberships-pro' ), ucwords($pmpro_license_check['license'])));?></p>
	<?php } ?>

	<?php if ( ! pmpro_license_isValid() || pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
		<p><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?></p>
		<p><a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=pricing&utm_content=upgrade" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a>
	<?php } ?>
	<hr />
	<p><?php echo wp_kses_post( sprintf( __( 'Paid Memberships Pro and our Add Ons are distributed under the <a target="_blank" href="%s">GPLv2 license</a>. This means, among other things, that you may use the software on this site or any other site free of charge.', 'paid-memberships-pro' ), 'http://www.gnu.org/licenses/gpl-2.0.html' ) ); ?></p>

	<h3><?php esc_html_e( 'Get Involved', 'paid-memberships-pro' ); ?></h3>
	<p><?php esc_html_e( 'There are many ways you can help support Paid Memberships Pro.', 'paid-memberships-pro' ); ?></p>
	<p><?php esc_html_e( 'Get involved with our plugin development via GitHub.', 'paid-memberships-pro' ); ?> <a href="https://github.com/strangerstudios/paid-memberships-pro" target="_blank"><?php esc_html_e( 'View on GitHub', 'paid-memberships-pro' ); ?></a></p>
	<ul>
		<li><a href="https://www.youtube.com/channel/UCFtMIeYJ4_YVidi1aq9kl5g/" target="_blank"><i class="dashicons dashicons-format-video"></i> <?php esc_html_e( 'Subscribe to our YouTube Channel.', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://www.facebook.com/PaidMembershipsPro" target="_blank"><i class="dashicons dashicons-facebook"></i> <?php esc_html_e( 'Follow us on Facebook.', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://twitter.com/pmproplugin" target="_blank"><i class="dashicons dashicons-twitter"></i> <?php esc_html_e( 'Follow @pmproplugin on Twitter.', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://www.paidmembershipspro.com/submit-testimonial/" target="_blank"><i class="dashicons dashicons-star-filled"></i> <?php esc_html_e( 'Share an honest review.', 'paid-memberships-pro' ); ?></a></li>
	</ul>
<?php
}