<?php
/**
 * Upgrade to version 3.8.5
 *
 * Remove the "Default WP notification email" setting. PMPro sends its own
 * confirmation email after checkout, so the WordPress new user notification
 * stays disabled by default. Sites that still want it can return true from the
 * pmpro_wp_new_user_notification filter.
 *
 * @since TBD
 */
function pmpro_upgrade_3_8_5() {
	delete_option( 'pmpro_email_member_notification' );
}
