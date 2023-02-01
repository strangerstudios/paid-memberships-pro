<?php
/*
	Admin code.
*/
// Wizard pre-header
include( PMPRO_DIR . '/adminpages/wizard/save-steps.php' );
require_once( PMPRO_DIR . '/includes/lib/SendWP/sendwp.php' );

/**
 * Redirect to Setup Wizard if the user hasn't been there yet.
 *
 * @since 1.10
 * @since 2.10 Redirects to the Setup Wizard instead.
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
		wp_redirect( admin_url( 'admin.php?page=pmpro-wizard' ) );
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

/**
 * Compare stored and current site URL and decide if we should go into pause mode
 *
 * @since TBD
 */
function pmpro_site_url_check() {

	//Checking if a stored site URL exists on first time installs
	if( empty( pmpro_getOption( 'last_known_url' ) ) ) {
		pmpro_setOption( 'last_known_url', get_site_url() );
	}

	//The WP_ENVIRONMENT_TYPE has been changed, we should pause everything
	//But only if we're not forcing pause mode to be turned off
	//local forces the WP_ENVIRONMENT_TYPE to be set to local
	if( ! pmpro_is_production_site() && ! pmpro_getOption( 'pause_mode_override' ) ) {
		//Site URL's don't match - enable pause mode
		pmpro_setOption( 'pause_mode', true );
	}

	if ( ! pmpro_is_production_site() && ! empty( $_REQUEST['pmpro-reactivate-services'] ) ) {
		//We're on a staging site but want to activate services
		pmpro_setOption( 'pause_mode_override', true ); 
		pmpro_setOption( 'pause_mode', false );	
	}

	if( ! pmpro_is_paused() ){
		//We aren't paused, check if the domains match
		if( ! pmpro_compare_siteurl() ) {
			//Site URL's don't match - enable pause mode
			pmpro_setOption( 'pause_mode', true );				
		} else {
			//Site URL's do match - disable pause mode
			pmpro_setOption( 'pause_mode', false );				
		}
	} else {
		//We are paused, show a notice.
		add_action( 'admin_notices', 'pmpro_pause_mode_notice' );
	}

}
add_action( 'admin_init', 'pmpro_site_url_check' );

/**
 * Allows a user to deactivate pause mode and update the last known URL
 *
 * @since TBD
 */
function pmpro_handle_pause_mode_actions() {

	// Can the current user view the dashboard?
	if ( current_user_can( 'pmpro_manage_pause_mode' ) ) {
		//We're attempting to reactivate all services.
		if( ! empty( $_REQUEST['pmpro-reactivate-services'] ) ) {			
			pmpro_setOption( 'last_known_url', get_site_url() );
			pmpro_setOption( 'pause_mode', false );			
		}
	}

}
add_action( 'admin_init', 'pmpro_handle_pause_mode_actions' );
/**
 * Display a notice about pause mode being enabled
 *
 * @since TBD
 */
function pmpro_pause_mode_notice() {

	if ( pmpro_is_paused() ) { ?>
		<div class="notice notice-error pmpro_notification pmpro_notification-error">
			<div class="pmpro_notification-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="pmpro_notification-content">
				<h3><?php esc_html_e( 'Site URL Change Detected', 'paid-memberships-pro' ); ?></h3>
				<p><?php
					// translators: %s: Contains the URL to a blog post
					printf(
						__( '<strong>Warning:</strong> We have detected that your site URL has changed. All cron jobs and automated services have been disabled.', 'paid-memberships-pro' ), ''
					);
				?></p>
				<?php if ( current_user_can( 'pmpro_manage_pause_mode' ) ) { ?>
				<p>
					<a href='<?php echo admin_url( '?pmpro-reactivate-services=true' ); ?>' class='button'><?php _e( 'Update my primary domain and reactivate all services', 'paid-memberships-pro' ); ?></a>
				</p>
				<?php } else { ?>
					<p><?php _e( 'Only users with the <code>pmpro_manage_pause_mode</code> capability are able to deactivate pause mode.', 'paid-memberships-pro' ); ?></p>
				<?php } ?>
				</div>
		</div>
		<?php
	}

}

