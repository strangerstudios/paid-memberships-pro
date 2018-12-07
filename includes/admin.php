<?php
/*
	Admin code.
*/

/**
 * Redirect to Dashboard tab if the user hasn't been there yet.
 *
 * @since 1.10
 */
function pmpro_admin_init_redirect_to_dashboard() {
	// Can the current user view the dashboard?
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if we should redirect to the dashboard
	$pmpro_dashboard_version = get_option( 'pmpro_dashboard_version', 0 );
	if ( version_compare( $pmpro_dashboard_version, PMPRO_VERSION ) < 0 ) {
		update_option( 'pmpro_dashboard_version', PMPRO_VERSION, 'no' );
		wp_redirect( admin_url( 'admin.php?page=pmpro-dashboard' ) );
		exit;
	}
}
add_action( 'admin_init', 'pmpro_admin_init_redirect_to_dashboard' );

/**
 * Runs only when the plugin is activated.
 *
 * @since 1.10
 */
function pmpro_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmpro-admin-notice', true, 5 );
}
//register_activation_hook( PMPRO_BASE_FILE, 'pmpro_admin_notice_activation_hook' );

/**
 * Admin Notice on Activation.
 *
 * @since 1.10
 */
function pmpro_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmpro-admin-notice' ) ) { ?>
		<div id="message" class="updated notice">
			<p><?php _e( '<strong>Welcome to Paid Memberships Pro</strong> &mdash; We&lsquo;re here to help you #GetPaid.', 'paid-memberships-rpo' ); ?></p>
			<p class="submit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-dashboard' ) ); ?>" class="button-primary"><?php _e( 'Get Started Using Paid Memberships Pro', 'paid-memberships-pro' ); ?></a></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmpro-admin-notice' );
	}
}
//add_action( 'admin_notices', 'pmpro_admin_notice' );
