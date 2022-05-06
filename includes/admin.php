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
 * Displays a warning notice regarding outdated templates
 *
 * @since TBD
 *
 * @return mixed|string - Empty, or the HTML containing the notice
 */
function pmpro_template_notices() {

	//Only show this notice on PMPro admin pages
	if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false  ) {
		return;
	}

	/**
	 * Permanently disable any template version notices
	 *
	 * @param bool To permanently hide template version notices
	 * 
	 * @since TBD
	 *
	 */
	$hide_template_notices = apply_filters( 'pmpro_hide_template_version_notices', false );

	if( $hide_template_notices ) {
		return;
	}
	
	$templates = pmpro_compare_template_versions();

	$affected_templates = array();

	if( !empty( $templates ) ) {
		foreach( $templates as $slug => $versions ) {
			$affected_templates[] = $slug;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<?php
					// translators: %s: The list of affected template names.
					printf(
						__( 'Outdated page templates have been detected in your theme or a plugin. You should <strong>update the following templates to ensure compatibility</strong> with the Paid Memberships Pro plugin: <em><strong>%s</strong></em>. <a href="%s">Learn How To Fix This</a>', 'paid-memberships-pro' ),
						implode( ', ', $affected_templates ),
						'TODO: LINK TO BLOG POST' //TODO: LINK TO BLOG POST/ARTICLE
					);
				?>
			</p>
			</div>
		<?php
	}	

}
add_action( 'admin_notices', 'pmpro_template_notices' );