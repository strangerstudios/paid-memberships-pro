<?php

/**
 * Initialize a PMPro_Banner_Notifier object for PMPro.
 * This replaces the old PMPro notifications system.
 *
 * @since 3.5
 */
function pmpro_get_pmpro_banner_notifier() {
	static $cached_notifier = null;

	// If we've already created the notifier, return it.
	if ( ! is_null( $cached_notifier ) ) {
		return $cached_notifier;
	}

	// Check if the PMPro_Banner_Notifier class already exists.
	if ( class_exists( 'PMPro_Banner_Notifier' ) ) {
		// This must have already been loaded elsewhere. Log a potential library conflict.
		$previously_loaded_class = new \ReflectionClass( 'PMPro_Banner_Notifier' );
		pmpro_track_library_conflict( 'gocodebox_banner_notifer', $previously_loaded_class->getFileName(), 'unknown' );
	} else {
		// Include the PMPro_Banner_Notifier class file.
		require_once( PMPRO_DIR . '/includes/lib/notifications.php' );
	}

	// Create a new instance of the PMPro_Banner_Notifier class.
	$cached_notifier = new PMPro_Banner_Notifier(
		array(
			'prefix'            => 'pmpro',
			'version'           => PMPRO_VERSION,
			'notifications_url' => 'https://notifications.paidmembershipspro.com/v2/notifications.json',
		)
	);

	// Return the notifier instance.
	return $cached_notifier;
}
add_action( 'admin_init', 'pmpro_get_pmpro_banner_notifier' );

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
