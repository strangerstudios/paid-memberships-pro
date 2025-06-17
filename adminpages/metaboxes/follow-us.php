<?php
/**
 * Paid Memberships Pro Follow Us Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since TBD
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_follow_us_callback() { ?>
	<ul class="pmpro-dashboard-list-icons">
		<li><a href="https://github.com/strangerstudios/paid-memberships-pro" target="_blank"><img alt="" src="<?php echo esc_url( PMPRO_URL . '/images/github.svg' ); ?>" /><?php esc_html_e( 'GitHub', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://www.youtube.com/channel/UCFtMIeYJ4_YVidi1aq9kl5g/" target="_blank"><img alt="" src="<?php echo esc_url( PMPRO_URL . '/images/youtube.svg' ); ?>" /><?php esc_html_e( 'YouTube', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://www.facebook.com/PaidMembershipsPro" target="_blank"><img alt="" src="<?php echo esc_url( PMPRO_URL . '/images/facebook.svg' ); ?>" /><?php esc_html_e( 'Facebook', 'paid-memberships-pro' ); ?></a></li>
		<li><a href="https://x.com/pmproplugin" target="_blank"><img src="<?php echo esc_url( PMPRO_URL . '/images/twitter-x.svg' ); ?>" /><?php esc_html_e( 'Twitter/X', 'paid-memberships-pro' ); ?></a></li>
	</ul>
	<?php
}
