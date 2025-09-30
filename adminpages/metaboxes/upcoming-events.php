<?php
/**
 * Paid Memberships Pro Upcoming Events Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 3.5
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_events_callback() { ?>
	<p><?php esc_html_e( 'Join us for a masterclass, open office hours, or virtual Q&A session to get help building and growing your membership business.', 'paid-memberships-pro' ); ?></p>
	<p><span class="dashicons dashicons-external"></span> <a href="https://www.paidmembershipspro.com/live/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=slack&utm_content=events" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View the Event Calendar', 'paid-memberships-pro' ); ?></a></p>
	<?php
}
