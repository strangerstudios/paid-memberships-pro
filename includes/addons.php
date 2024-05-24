<?php
/**
 * Some of the code in this library was borrowed from the TGM Updater class by Thomas Griffin. (https://github.com/thomasgriffin/TGM-Updater)
 */


/**
 * Setup plugins api filters
 *
 * @since 1.8.5
 */
function pmpro_setupAddonUpdateInfo() {
	add_action( 'update_option_pmpro_license_key', 'pmpro_reset_update_plugins_cache', 10, 2 );
}

add_action( 'init', 'pmpro_setupAddonUpdateInfo' );

/**
 * Get addon information from PMPro server.
 *
 * @since  1.8.5
 */
function pmpro_getAddons() {
	// check if forcing a pull from the server
	$addons = get_option( 'pmpro_addons', array() );
	$addons_timestamp = get_option( 'pmpro_addons_timestamp', 0 );

	// if no addons locally, we need to hit the server
	if ( empty( $addons ) || ! empty( $_REQUEST['force-check'] ) || current_time( 'timestamp' ) > $addons_timestamp + 86400 ) {
		/**
		 * Filter to change the timeout for this wp_remote_get() request.
		 *
		 * @since 1.8.5.1
		 *
		 * @param int $timeout The number of seconds before the request times out
		 */
		$timeout = apply_filters( 'pmpro_get_addons_timeout', 5 );

		// get em
		$remote_addons = wp_remote_get( PMPRO_LICENSE_SERVER . 'addons/', $timeout );

		// make sure we have at least an array to pass back
		if ( empty( $addons ) ) {
			$addons = array();
		}

		// test response
		if ( is_wp_error( $remote_addons ) ) {
			// error
			pmpro_setMessage( 'Could not connect to the PMPro License Server to update addon information. Try again later.', 'error' );
		} elseif ( ! empty( $remote_addons ) && $remote_addons['response']['code'] == 200 ) {
			// update addons in cache
			$addons = json_decode( wp_remote_retrieve_body( $remote_addons ), true );
			foreach ( $addons as $key => $value ) {
				$addons[$key]['ShortName'] = trim( str_replace( array( 'Add On', 'Paid Memberships Pro - ' ), '', $addons[$key]['Title'] ) );
			}
			// Alphabetize the list by ShortName.
			$short_names = array_column( $addons, 'ShortName' );
			array_multisort( $short_names, SORT_ASC, SORT_STRING | SORT_FLAG_CASE, $addons );

			delete_option( 'pmpro_addons' );
			add_option( 'pmpro_addons', $addons, null, 'no' );
		}

		// save timestamp of last update
		delete_option( 'pmpro_addons_timestamp' );
		add_option( 'pmpro_addons_timestamp', current_time( 'timestamp' ), null, 'no' );
	}

	return $addons;
}

/**
 * Find a PMPro addon by slug.
 *
 * @since 1.8.5
 *
 * @param object $slug  The identifying slug for the addon (typically the directory name)
 * @return object $addon containing plugin information or false if not found
 */
function pmpro_getAddonBySlug( $slug ) {
	$addons = pmpro_getAddons();

	if ( empty( $addons ) ) {
		return false;
	}

	foreach ( $addons as $addon ) {
		if ( $addon['Slug'] == $slug ) {
			return $addon;
		}
	}

	return false;
}


/**
 * Get the Add On slugs for each category we identify.
 *
 * @since 2.8.x
 *
 * @return array $addon_cats An array of plugin categories and plugin slugs within each.
 */
function pmpro_get_addon_categories() {
	return array(
		'popular' => array(
			'pmpro-advanced-levels-shortcode',
			'pmpro-woocommerce',
			'pmpro-courses',
			'pmpro-member-directory',
			'pmpro-subscription-delays',
			'pmpro-roles',
			'pmpro-approvals',
			'pmpro-add-paypal-express',
			'pmpro-group-accounts',
			'pmpro-signup-shortcode',
			'pmpro-set-expiration-dates',
			'pmpro-import-users-from-csv'
		),
		'association' => array(
			'pmpro-member-directory',
			'pmpro-membership-manager-role',
			'pmpro-import-users-from-csv',
			'pmpro-approvals',
			'basic-user-avatars',
			'pmpro-add-member-admin',
			'pmpro-add-name-to-checkout',
			'pmpro-donations',
			'pmpro-events',
			'pmpro-group-accounts',
			'pmpro-pay-by-check',
			'pmpro-set-expiration-dates'
		),
		'premium_content' => array(
			'pmpro-email-confirmation',
			'pmpro-cpt',
			'pmpro-series',
			'pmpro-events',
			'pmpro-addon-packages',
			'seriously-simple-podcasting',
			'pmpro-user-pages'
		),
		'community' => array(
			'pmpro-approvals',
			'pmpro-bbpress',
			'pmpro-buddypress',
			'pmpro-discord-add-on',
			'pmpro-invite-only',
			'pmpro-email-confirmation',
			'pmpro-import-users-from-csv'
		),
		'courses' => array(
			'pmpro-courses',
			'pmpro-approvals',
			'pmpro-cpt',
			'pmpro-user-pages',
			'pmpro-member-badges',
			'pmpro-multiple-memberships-per-user'
		),
		'directory' => array(
			'basic-user-avatars',
			'pmpro-member-badges',
			'pmpro-member-directory',
			'pmpro-membership-maps',
			'pmpro-approvals'
		),
		'newsletter' => array(
			'MailPoet-Paid-Memberships-Pro-Add-on',
			'pmpro-mailchimp',
			'pmpro-aweber',
			'convertkit-for-paid-memberships-pro'
		),
		'podcast' => array(
			'seriously-simple-podcasting',
			'pmpro-akismet',
			'pmpro-events',
			'pmpro-invite-only',
			'pmpro-email-confirmation'
		),
		'video' => array(
			'pmpro-cpt',
			'pmpro-email-confirmation',
			'pmpro-events',
			'pmpro-invite-only',
			'pmpro-addon-packages'
		),
	);
}

/**
 * Get the Add On icon from the plugin slug.
 *
 * @since 2.8.x
 *
 * @param string $slug The identifying slug for the addon (typically the directory name).
 * @return string $plugin_icon_src The src URL for the plugin icon.
 */
function pmpro_get_addon_icon( $slug ) {
	if ( file_exists( PMPRO_DIR . '/images/add-ons/' . $slug . '.png' ) ) {
		$plugin_icon_src = PMPRO_URL . '/images/add-ons/' . $slug . '.png';
	} else {
		$plugin_icon_src = PMPRO_URL . '/images/add-ons/default-icon.png';
	}
	return $plugin_icon_src;
}

/**
 * Force update of plugin update data when the PMPro License key is updated
 *
 * @since 1.8
 *
 * @param array  $args  Array of request args.
 * @param string $url  The URL to be pinged.
 * @return array $args Amended array of request args.
 */
function pmpro_reset_update_plugins_cache( $old_value, $value ) {
	delete_option( 'pmpro_addons_timestamp' );
	delete_site_transient( 'update_themes' );
}

/**
 * Detect when trying to update a PMPro Plus plugin without a valid license key.
 *
 * @since 1.9
 */
function pmpro_admin_init_updating_plugins() {
	// if user can't edit plugins, then WP will catch this later
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	// updating one or more plugins via Dashboard -> Upgrade
	if ( basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'update.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'update-selected' && ! empty( $_REQUEST['plugins'] ) ) {
		// figure out which plugins we are updating
		$plugins = explode( ',', stripslashes( sanitize_text_field( $_GET['plugins'] ) ) );
		$plugins = array_map( 'urldecode', $plugins );

		// look for addons
		$premium_addons = array();
		$premium_plugins = array();
		foreach ( $plugins as $plugin ) {
			$slug = str_replace( '.php', '', basename( $plugin ) );
			$addon = pmpro_getAddonBySlug( $slug );
			if ( ! empty( $addon ) && pmpro_license_type_is_premium( $addon['License'] ) ) {
				if ( ! isset( $premium_addons[$addon['License']] ) ) {
					$premium_addons[$addon['License']] = array();
					$premium_plugins[$addon['License']] = array();
				}
				$premium_addons[$addon['License']][] = $addon['Name'];
				$premium_plugins[$addon['License']][] = $plugin;
			}
		}
		unset( $plugin );

		// if Plus addons found, check license key
		if ( ! empty( $premium_plugins ) ) {			
			foreach( $premium_plugins as $license_type => $premium_plugin ) {				
				// if they have a good license, skip the error				
				if ( pmpro_can_download_addon_with_license( $license_type ) ) {
					continue;
				}
				
				// show error
				$msg = wp_kses( sprintf( __( 'You must have a <a target="_blank" href="https://www.paidmembershipspro.com/pricing/?utm_source=wp-admin&utm_pluginlink=bulkupdate">valid PMPro %s License Key</a> to update PMPro %s add ons. The following plugins will not be updated:', 'paid-memberships-pro' ), ucwords( $license_type ), ucwords( $license_type ) ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) );
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<div class="error"><p>' . $msg . ' <strong>' . esc_html( implode( ', ', $premium_addons[$license_type] ) ) . '</strong></p></div>';
			}			
		}

		// can exit out of this function now
		return;
	}

	// upgrading just one or plugin via an update.php link
	if ( basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'update.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'upgrade-plugin' && ! empty( $_REQUEST['plugin'] ) ) {
		// figure out which plugin we are updating
		$plugin = urldecode( trim( sanitize_text_field( $_REQUEST['plugin'] ) ) );

		$slug = str_replace( '.php', '', basename( $plugin ) );
		$addon = pmpro_getAddonBySlug( $slug );
		
		if ( ! empty( $addon ) && pmpro_license_type_is_premium( $addon['License'] ) && ! pmpro_can_download_addon_with_license( $addon['License'] ) ) {
			require_once( ABSPATH . 'wp-admin/admin-header.php' );

			echo '<div class="wrap"><h2>' . esc_html__( 'Update Plugin' ) . '</h2>';

			$msg = sprintf( __( 'You must have a <a href="https://www.paidmembershipspro.com/pricing/?utm_source=wp-admin&utm_pluginlink=addon_update">valid PMPro %s License Key</a> to update PMPro %s add ons.', 'paid-memberships-pro' ), ucwords( $addon['License'] ), ucwords( $addon['License'] ) );
			echo '<div class="error"><p>' . wp_kses_post( $msg ) . '</p></div>';

			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ) . '" target="_parent">' . esc_html__( 'Return to the PMPro Add Ons page', 'paid-memberships-pro' ) . '</a></p>';

			echo '</div>';

			include( ABSPATH . 'wp-admin/admin-footer.php' );

			// can exit WP now
			exit;
		}
	}

	// updating via AJAX on the plugins page
	if ( basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'admin-ajax.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'update-plugin' && ! empty( $_REQUEST['plugin'] ) ) {
		// figure out which plugin we are updating
		$plugin = urldecode( trim( sanitize_text_field( $_REQUEST['plugin'] ) ) );

		$slug = str_replace( '.php', '', basename( $plugin ) );
		$addon = pmpro_getAddonBySlug( $slug );
		if ( ! empty( $addon ) && pmpro_license_type_is_premium( $addon['License'] ) && ! pmpro_can_download_addon_with_license( $addon['License'] ) ) {
			$msg = sprintf( __( 'You must enter a valid PMPro %s License Key under Settings > PMPro License to update this add on.', 'paid-memberships-pro' ), ucwords( $addon['License'] ) );
			echo '<div class="error"><p>' . esc_html( $msg ) . '</p></div>';

			// can exit WP now
			exit;
		}
	}
}
add_action( 'admin_init', 'pmpro_admin_init_updating_plugins' );

/**
 * Check if an add on can be downloaded based on it's license.
 * @since 2.7.4
 * @param string $addon_license The license type of the add on to check.
 * @return bool True if the user's license key can download that add on,
 *              False if the user's license key cannot download it.
 */
function pmpro_can_download_addon_with_license( $addon_license ) {
	// The wordpress.org and free types can always be downloaded.
	if ( $addon_license === 'wordpress.org' || $addon_license === 'free' ) {
		return true;
	}
	
	// Check premium license types.
	if ( $addon_license === 'standard' ) {
		$types_to_check = array( 'standard', 'plus', 'builder' );
	}
	if ( $addon_license === 'plus' ) {
		$types_to_check = array( 'plus', 'builder' );
	}
	if ( $addon_license === 'builder' ) {
		$types_to_check = array( 'builder' );
	}
	
	// Some unknown license?
	if ( empty( $types_to_check ) ) {
		return false;
	}
	
	return pmpro_license_isValid( null, $types_to_check );		
}

/**
 *  Show a notification if Update Manager Add On isn't installed
 * 
 * @since TBD
 */
function pmpro_update_manager_notices() {
	global $pagenow;
	$allowed_pages = array( "update-core.php", "plugins.php" );
	$manager_update_slug = 'pmpro-update-manager/pmpro-update-manager.php';
	
	$is_pmpro_page = isset( $_REQUEST['page'] ) 
	&& substr( sanitize_text_field( $_REQUEST['page'] ), 0, 6 ) == 'pmpro-';

	//if $page_name is in $allowed_pages var below is true
	$show_notice = in_array( $pagenow, $allowed_pages ) || $is_pmpro_page;

	//Bail if we shouldn't show notice according to the page
	if ( ! $show_notice ) {
		return;
	}


	//Get addons
	$addons = pmpro_getAddons();

	$installed_plugins = array_keys( get_plugins() );

	$pmpro_license_server_addons = array();

	foreach ( $addons as $addon ) {
		//Get Add On file name
		$plugin_file = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
		//Get Abs Add On file name
		$plugin_file_abs = ABSPATH . 'wp-content/plugins/' . $plugin_file;
		// Which one have 'License' != 'wordpress.org' AND are installed?
		if ( $addon['License'] != 'wordpress.org' && in_array( $plugin_file, $installed_plugins ) ) {
			$pmpro_license_server_addons[] = $addon;
		}
	}

	//If no addons bail
	if ( empty( $pmpro_license_server_addons ) ) {
		return;
	}

	//If pmpro update manager is active bail
	if ( is_plugin_active( $manager_update_slug ) ) {
		return;
	}

	$is_update_manager_installed = in_array( $manager_update_slug, $installed_plugins );

	
	//If not installed show a notice is not installed
	if ( ! $is_update_manager_installed ) {
		//Word the notice for the plugin isn't installed
		$notice_message = esc_html__( 'The Paid Memberships Pro Update Manager plugin is not installed. 
			You need to install and activate it to properly download and install PMPro Add Ons.', 'paid-memberships-pro' );
		$link_text = esc_html__( 'Click here to install.', 'paid-memberships-pro' );
		$link_url = wp_nonce_url(
			self_admin_url(
				add_query_arg( array(
					'action' => 'install-plugin',
					'plugin' => $manager_update_slug
				),
				'update.php'
				)
			),
			'install-plugin_' . $manager_update_slug
		);
	} else {
		//Word the notice for the plugin is installed but not active
		$notice_message = esc_html__( 'The Paid Memberships Pro Update Manager plugin is installed but not active. 
			You need to activate it to properly download and install PMPro Add Ons.', 'paid-memberships-pro' );
			$link_text = esc_html__( 'Click here to activate.', 'paid-memberships-pro' );
		$link_url = wp_nonce_url(
			self_admin_url(
				add_query_arg( array(
					'action' => 'activate',
					'plugin' => $manager_update_slug,
				),
				'plugins.php'
			)
			),
			'activate-plugin_' . $manager_update_slug
		);
	}
	//Add the div with the message to display
	?>
	 <div class="notice notice-warning is-dismissible">
	 		<p>
				<?php echo esc_html( sprintf( __( '%s' , 'paid-memberships-pro'), $notice_message ) );
				?>
				<a href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $link_text ); ?></a>
			</p>
	</div>
	<?php 	
}

add_action( 'admin_init', 'pmpro_update_manager_notices' );