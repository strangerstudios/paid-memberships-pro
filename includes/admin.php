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

	// Check if we should redirect to the wizard. This should only happen on new installs and once.
	if ( get_option( 'pmpro_wizard_redirect' ) ) {
		delete_option( 'pmpro_wizard_redirect' );	// Deleting right away to avoid redirect loops.
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

	$block_dashboard = get_option( 'pmpro_block_dashboard' );

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
 * @since 2.10
 */
function pmpro_site_url_check() {
	if ( pmpro_is_paused() ) {
		//We are paused, show a notice.
		add_action( 'admin_notices', 'pmpro_pause_mode_notice' );
	}
}
add_action( 'admin_init', 'pmpro_site_url_check' );

/**
 * Allows a user to deactivate pause mode and update the last known URL
 *
 * @since 2.10
 */
function pmpro_handle_pause_mode_actions() {

	// Can the current user view the dashboard?
	if ( current_user_can( 'pmpro_manage_pause_mode' ) ) {
		//We're attempting to reactivate all services.
		if( ! empty( $_REQUEST['pmpro-reactivate-services'] ) ) {			
			delete_option( 'pmpro_last_known_url' );
		}
	}

}
add_action( 'admin_init', 'pmpro_handle_pause_mode_actions' );

/**
 * Display a notice about pause mode being enabled
 *
 * @since 2.10
 */
function pmpro_pause_mode_notice() {
	global $current_user;
	if ( isset( $_REQUEST[ 'show_pause_notification' ] ) ) {
		$pmpro_show_pause_notification = (bool)$_REQUEST['show_pause_notification'];
	} else {
		$pmpro_show_pause_notification = false;
	}

	// Remove notice from dismissed user meta if URL parameter is set.
	$archived_notifications = get_user_meta( $current_user->ID, 'pmpro_archived_notifications', true );
	if ( ! is_array( $archived_notifications ) ) {
		$archived_notifications = array();
	}

	if ( array_key_exists( 'hide_pause_notification', $archived_notifications ) ) {
		$show_notice = false;
		if ( ! empty( $pmpro_show_pause_notification ) ) {
			unset( $archived_notifications['hide_pause_notification'] );
			update_user_meta( $current_user->ID, 'pmpro_archived_notifications', $archived_notifications );
			$show_notice = true;
		}
	} else {
		$show_notice = true;
	}

	if ( pmpro_is_paused() && ! empty( $show_notice ) ) {
		// Site is paused. Show the notice. ?>
		<div id="hide_pause_notification" class="notice notice-error pmpro_notification pmpro_notification-error">
			<button type="button" class="pmpro-notice-button notice-dismiss" value="hide_pause_notification"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></span></button>
			<div class="pmpro_notification-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="pmpro_notification-content">
				<h3><?php esc_html_e( 'Site URL Change Detected', 'paid-memberships-pro' ); ?></h3>
				<p><?php echo wp_kses_post( sprintf( __( '<strong>Warning:</strong> We have detected that your site URL has changed. All PMPro-related cron jobs and automated services have been disabled. Paid Memberships Pro considers %s to be the site URL.', 'paid-memberships-pro' ), '<code>' . esc_url( get_option( 'pmpro_last_known_url' ) ) . '</code>' ) ); ?></p>
				<?php if ( current_user_can( 'pmpro_manage_pause_mode' ) ) { ?>
				<p>
					<a href='#' id="hide_pause_notification_button" class='button' value="hide_pause_notification"><?php esc_html_e( 'Dismiss notice and keep all services paused', 'paid-memberships-pro' ); ?></a>
					<a href='<?php echo esc_url( admin_url( '?pmpro-reactivate-services=true' ) ); ?>' class='button button-secondary'><?php esc_html_e( 'Update my primary domain and reactivate all services', 'paid-memberships-pro' ); ?></a>
				</p>
				<?php } else { ?>
					<p><?php echo wp_kses_post( __( 'Only users with the <code>pmpro_manage_pause_mode</code> capability are able to deactivate pause mode.', 'paid-memberships-pro' ) ); ?></p>
				<?php } ?>
				</div>
		</div>
		<?php
	}
}

/**
 * Maybe display a notice about spam protection being disabled.
 *
 * @since 2.11
 */
function pmpro_spamprotection_notice() {
	global $current_user;

	// If spam protection is enabled, we are not on a PMPro settings page, or we are on the PMPro advanced settings page, don't show the notice.
	if (
		get_option( 'pmpro_spamprotection' ) ||
		! isset( $_REQUEST['page'] ) ||
		( isset( $_REQUEST['page'] ) && 'pmpro-' !== substr( $_REQUEST['page'], 0, 6 ) ) ||
		( isset( $_REQUEST['page'] ) && 'pmpro-advancedsettings' === $_REQUEST['page'] )
	) {
		return;
	}

	// Get notifications that have been archived.
	$archived_notifications = get_user_meta( $current_user->ID, 'pmpro_archived_notifications', true );

	// If the user hasn't dismissed the notice, show it.
	if ( ! is_array( $archived_notifications ) || ! array_key_exists( 'hide_spamprotection_notification', $archived_notifications ) ) {
		?>
		<div id="hide_spamprotection_notification" class="notice notice-error pmpro_notification pmpro_notification-error">
			<button type="button" class="pmpro-notice-button notice-dismiss" value="hide_spamprotection_notification"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></span></button>
			<div class="pmpro_notification-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="pmpro_notification-content">
				<h3><?php esc_html_e( 'Spam Protection Disabled', 'paid-memberships-pro' ); ?></h3>
				<p><?php esc_html_e( 'Spam protection is currently disabled. This is not recommended. Please enable spam protection on the Advanced Settings page.', 'paid-memberships-pro' ); ?></p>
				<p>
					<a href='<?php echo esc_url( admin_url( 'admin.php?page=pmpro-advancedsettings' ) ); ?>' class='button button-secondary'><?php esc_html_e( 'Go to Advanced Settings', 'paid-memberships-pro' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpro_spamprotection_notice' );

/**
 * Remove all WordPress admin notifications from our Wizard area as it's distracting.
 */
function pmpro_wizard_remove_admin_notices() {
	if ( is_admin() && ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro-wizard' ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}
}
add_action( 'in_admin_header', 'pmpro_wizard_remove_admin_notices', 11 );

/**
 * Adds the Paid Memberships Pro branded header to the PMPro settings and admin pages.
 *
 * @since 3.0
 */
function pmpro_admin_header() {
	// Assume we should not show our header.
	$show_header = false;

	// Show header on our settings pages.
	if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'pmpro-' ) === 0 ) {
		$show_header = true;
	}

	// Exclude the wizard.
	if ( ! empty( $_GET['page'] ) && 'pmpro-wizard' === $_GET['page'] ) {
		$show_header = false;
	}

	if ( empty( $show_header ) ) {
		return;
	} ?>
	<div class="pmpro_banner">
		<div class="pmpro_banner_wrapper">
			<div class="pmpro_logo">
				<h1>
					<span class="screen-reader-text"><?php esc_html_e( 'Paid Memberships Pro', 'paid-memberships-pro' ); ?></span>
					<a target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=homepage"><img src="<?php echo esc_url( PMPRO_URL . '/images/Paid-Memberships-Pro.png' ); ?>" width="300" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
				</h1>
				<span class="pmpro_version">v<?php echo esc_html( PMPRO_VERSION ); ?></span>
			</div>
			<div class="pmpro_meta">
				<a target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/documentation/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=documentation"><?php esc_html_e('Documentation', 'paid-memberships-pro' ); ?></a>
				<a target="_blank" href="https://www.paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=pricing&utm_content=get-support"><?php esc_html_e('Get Support', 'paid-memberships-pro' );?></a>

				<?php
					// Show notice if paused.
					if ( pmpro_is_paused() ) {
						// Link to reactivate the notification about pause mode if has cap.
						if ( current_user_can( 'pmpro_manage_pause_mode' ) ) { ?>
							<a class="pmpro_paused_tag" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-dashboard', 'show_pause_notification' => '1' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Services Paused', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<span class="pmpro_paused_tag"><?php esc_html_e( 'Crons Disabled', 'paid-memberships-pro' ); ?></span>
						<?php }
					}
				?>
				<?php if ( pmpro_license_isValid( null, pmpro_license_get_premium_types() ) ) { ?>
					<?php echo wp_kses_post( sprintf(__( '<a class="pmpro_license_tag pmpro_license_tag-valid" href="%s">Valid License</a>', 'paid-memberships-pro' ), esc_url( add_query_arg( array( 'page' => 'pmpro-license' ), admin_url( 'admin.php' ) ) ) ) ); ?>
				<?php } elseif ( ! defined( 'PMPRO_LICENSE_NAG' ) || PMPRO_LICENSE_NAG == true ) { ?>
					<?php echo wp_kses_post( sprintf(__( '<a class="pmpro_license_tag pmpro_license_tag-invalid" href="%s">No License</a>', 'paid-memberships-pro' ), esc_url( add_query_arg( array( 'page' => 'pmpro-license' ), admin_url( 'admin.php' ) ) ) ) ); ?>
				<?php } ?>
			</div> <!-- end pmpro_meta -->
		</div> <!-- end pmpro_banner_wrapper -->		
	</div> <!-- end pmpro_banner -->
	<?php
}
add_action( 'admin_notices', 'pmpro_admin_header', 1 );

/**
 * Add notice to rate us that replaces default WordPress footer text on PMPro pages.
 */
function pmpro_admin_footer_text( $text ) {
	global $current_screen;

	// Show footer on our pages in admin, but not on the block editor.
	if (
		! isset( $_REQUEST['page'] ) ||
		( isset( $_REQUEST['page'] ) && 'pmpro-' !== substr( $_REQUEST['page'], 0, 6 ) ) ||
		( isset( $_REQUEST['page'] ) && 'pmpro-advancedsettings' === $_REQUEST['page'] )
	) {
		return $text;
	}

	return sprintf(
		wp_kses(
			/* translators: $1$s - Paid Memberships Pro plugin name; $2$s - WP.org review link. */
			__( 'Please <a href="%1$s" target="_blank" rel="noopener noreferrer">rate us %2$s on WordPress.org</a> to help others find %3$s. Thank you from the %4$s team!', 'paid-memberships-pro' ),
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
				'p' => [
					'class'  => [],
				],
			]
		),
		'https://wordpress.org/support/plugin/paid-memberships-pro/reviews/?filter=5#new-post',
		'<span class="pmpro-rating-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>',
		'Paid Memberships Pro',
		'PMPro'
	);
}
add_filter( 'admin_footer_text', 'pmpro_admin_footer_text' );

/**
 * Hide non-PMPro notices from PMPro dashboard pages.
 * @since 3.0
 */
function pmpro_hide_non_pmpro_notices() {
    global $wp_filter;

	// Make sure we're on a PMPro page.
	if ( ! isset( $_REQUEST['page'] )
			|| substr( sanitize_text_field( $_REQUEST['page'] ), 0, 6 ) !== 'pmpro-' ) {
		return;
	}

	// Handle notices added through these hooks.
    $hooks = ['admin_notices', 'all_admin_notices'];

    foreach ($hooks as $hook) {
        // If no callbacks are registered, skip.
		if ( ! isset( $wp_filter[$hook] ) ) {
			continue;
		}

		// Loop through the callbacks and remove any that aren't PMPro.
		foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
			foreach ($callbacks as $key => $callback) {				
				if ( is_string( $callback['function' ] ) ) {
					// Check the function name.
					// Ex. add_action( 'admin_notices', 'pmpro_admin_notice' );
					$name_to_check = $callback['function'];
				} elseif ( is_array( $callback['function' ] ) && is_string( $callback['function'][0] ) ) {
					// Check the class name for the static method.
					// Ex. add_action( 'admin_notices', array( 'PMPro_Admin', 'admin_notice' ) );
					$name_to_check = $callback['function'][0];
				} elseif ( is_array( $callback['function' ] ) && is_object( $callback['function'][0] ) ) {
					// Check the class name for the non-static method.
					// Ex. add_action( 'admin_notices', array( $some_object, 'admin_notice' ) );
					$name_to_check = get_class( $callback['function'][0] );
				} else {
					// Ex. add_action( 'admin_notices', function() { echo 'Hello World'; } );
					// We don't use closures in PMPro, so we don't need to check for them.
					$name_to_check = '';
				}

				// Trim slashes for namespaces and lowercase the name.
				$name_to_check = strtolower( trim( $name_to_check, '\\' ) );

				// If the function name starts with 'pmpro', then we don't want to remove it.
				// Not checking for 'pmpro_' because we have class names like PMProGateway_stripe and want to keep notices from add ons.
				if ( strpos( $name_to_check, 'pmpro' ) !== 0 ) {
					unset( $wp_filter[$hook]->callbacks[$priority][$key] );
				}
			}
		}
    }
}
add_action( 'in_admin_header', 'pmpro_hide_non_pmpro_notices' );