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

/**
 * Compare stored and current site URL
 *
 * @since TBD
 */
function pmpro_site_url_check() {

	// Can the current user view the dashboard?
	if ( current_user_can( 'manage_options' ) ) {

		//Checking if a stored site URL exists on first time installs
		if( empty( pmpro_getOption( 'site_url' ) ) ) {
			pmpro_setOption( 'site_url', get_site_url() );
		}

		//We're attempting to reactivate all services.
		if( ! empty( $_REQUEST['pmpro-reactivate-services'] ) ) {			
			pmpro_save_siteurl();
			pmpro_set_pause_mode( false );
			pmpro_maybe_schedule_crons();
		}

		//The WP_ENVIRONMENT_TYPE has been changed, we should pause everything
		if( ! pmpro_is_production_site() ) {
			//Site URL's don't match - enable pause mode
			pmpro_set_pause_mode( true );
			//Clear all crons in pause mode
			pmpro_clear_crons();
		}

		if( ! pmpro_get_pause_mode() ){
			//We aren't paused, check if the domains match
			if( ! pmpro_compare_siteurl() ) {
				//Site URL's don't match - enable pause mode
				pmpro_set_pause_mode( true );
				//Clear all crons in pause mode
				pmpro_clear_crons();
			} else {
				//Site URL's do match - disable pause mode
				pmpro_set_pause_mode( false );
				//Reschedule crons
				pmpro_maybe_schedule_crons();
			}
		} else {
			//We are paused, show a notice.
			add_action( 'admin_notices', 'pmpro_pause_mode_notice' );
		}

	}

}
add_action( 'admin_init', 'pmpro_site_url_check' );

/**
 * Display a notice about pause mode being enabled
 *
 * @since TBD
 */
function pmpro_pause_mode_notice() {

	if( pmpro_get_pause_mode() ) {

		?>
		<div class="notice notice-error">
		<p>
			<?php
				// translators: %s: Contains the URL to a blog post
				printf(
					__( '<strong>Warning:</strong> We have detected that your site URL has changed. All cron jobs and automated services have been disabled. Read more about this <a href="%s">here</a>', 'paid-memberships-pro' ), 'BLOG_POST_URL'
				);
			?>
		</p>
		<p>
			<a href='<?php echo admin_url( '?pmpro-reactivate-services=true' ); ?>' class='button'><?php _e( 'Update my primary domain and reactivate all services', 'paid-memberships-pro' ); ?></a>
		</p>
    	</div>
		<?php
	}

}