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
	global $current_user;

	$block_dashboard = pmpro_getOption( 'block_dashboard' );

	if ( ! wp_doing_ajax()
			&& ! empty( $block_dashboard )
			&& ! current_user_can( 'manage_options' )
			&& ! current_user_can( 'edit_users' )
			&& ! current_user_can( 'edit_posts' )
			&& in_array( 'subscriber', (array) $current_user->roles ) ) {
		$block = true;
	} else {
		$block = false;
	}	
	$block = apply_filters( 'pmpro_block_dashboard', $block );

	return $block;
}
