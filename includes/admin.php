<?php
/*
	Admin code.
*/

require_once( PMPRO_DIR . '/includes/lib/SendWP/sendwp.php' );
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
 * Block Subscibers from accessing the WordPress Dashboard.
 *
 * @since 2.3.4
 */
function pmpro_block_dashboard_redirect() {
	if ( pmpro_block_dashboard() ) {
		wp_redirect( pmpro_url( 'account' ) );
		exit;
	}
}
add_action( 'admin_init', 'pmpro_block_dashboard_redirect', 9 );

/**
 * Is the current user blocked from the dashboard
 * per the advanced setting.
 *
 * @since 2.3
 */
function pmpro_block_dashboard() {
	global $current_user, $pagenow;

	$block_dashboard = pmpro_getOption( 'block_dashboard' );

	if (
		! wp_doing_ajax()
		&& 'admin-post.php' !== $pagenow
		&& ! empty( $block_dashboard )
		&& ! current_user_can( 'manage_options' )
		&& ! current_user_can( 'edit_users' )
		&& ! current_user_can( 'edit_posts' )
		&& in_array( 'subscriber', (array) $current_user->roles )
	) {
		$block = true;
	} else {
		$block = false;
	}
	$block = apply_filters( 'pmpro_block_dashboard', $block );

	/**
	 * Allow filtering whether to block Dashboard access.
	 *
	 * @param bool $block Whether to block Dashboard access.
	 */
	return apply_filters( 'pmpro_block_dashboard', $block );
}

/**
 * Initialize our Site Health integration and add hooks.
 *
 * @since 2.6.2
 */
function pmpro_init_site_health_integration() {
	$site_health = PMPro_Site_Health::init();
	$site_health->hook();
}

add_action( 'admin_init', 'pmpro_init_site_health_integration' );

function pmpro_template_notices() {

	$templates = pmpro_compare_template_versions();

	$affected_template_string = "";

	if( !empty( $templates ) ) {
		foreach( $templates as $slug => $versions ) {
			$affected_template_string .= "<li>".sprintf( __('%s - Core Version: %s, Your Version: %s.'), $slug, $versions['our_version'], $versions['your_version'] )."</li>";
		}

		?>
		<div id="pmpro_notifications">		
			<div class="pmpro_notification">
				<button type="button" class="pmpro-notice-button notice-dismiss" value="20003"><span class="screen-reader-text">Dismiss this notice.</span></button>
					<div class="pmpro_notification-general">
				<h3><span class="dashicons dashicons-warning"></span> <?php _e('Paid Memberships Pro - Outdated Templates Detected', 'paid-memberships-pro'); ?></h3>
				<p><?php _e( 'It looks like you are using outdated versions of templates  that may cause issues with the latest functionality of Paid Memberships Pro. Here\'s a list of affected templates:', 'paid-memberships-pro' ); ?></p>
				<ul><?php echo $affected_template_string; ?></ul>
				<p><a class="button button-primary" target="blank" href="#">Learn How To Fix This</a></p>			</div>
		</div>
		</div>
		<?php
	}	

}
add_action( 'admin_notices', 'pmpro_template_notices' );