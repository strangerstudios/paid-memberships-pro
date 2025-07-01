<?php
/**
 * Paid Memberships Pro Get Involved Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 3.5
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_get_involved_callback() { ?>
	<p><?php esc_html_e( 'Join the PMPro Slack community to connect with other PMPro users and developers.', 'paid-memberships-pro' ); ?></p>
	<p><span class="dashicons dashicons-external"></span> <a href="https://www.paidmembershipspro.com/slack/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=slack&utm_content=join-community" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Join the Community', 'paid-memberships-pro' ); ?></a></p>
	<?php
}
