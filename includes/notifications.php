<?php

require_once PMPRO_DIR . '/libraries/banner-notifications/banner-notifications.php';

$GLOBALS['pmpro_banner_notifications'] = new Gocodebox_Banner_Notifier(
	array(
		'prefix'            => 'pmpro',
		'version'           => PMPRO_VERSION,
		'notifications_url' => 'https://notifications.paidmembershipspro.com/v2/notifications.json',
	)
);

/**
 * Show Powered by Paid Memberships Pro comment (only visible in source) in the footer.
 */
function pmpro_link() {
	?>Memberships powered by Paid Memberships Pro v<?php echo esc_html( PMPRO_VERSION ); ?>.<?php
}

function pmpro_footer_link() {
	if ( ! get_option( 'pmpro_hide_footer_link' ) ) { ?>
		<!-- <?php pmpro_link()?> -->
	<?php }
}
add_action( 'wp_footer', 'pmpro_footer_link' );
