<?php

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Class for managing PMPro Addons.
 */
class PMPro_AddOns {

	/**
	 * The single instance of the class.
	 *
	 * @var PMPro_AddOns
	 * @access protected
	 * @since 3.6
	 */
	protected static $instance = null;

	/**
	 * Array of Add Ons.
	 *
	 * @since 1.8
	 * @var array
	 */
	public $addons = array();

	/**
	 * Timestamp of last Add Ons check.
	 *
	 * @since 3.6
	 * @var int
	 */
	public $addons_timestamp = 0;

	/**
	 * Cache of plugin information to reduce calls to get_plugins().
	 *
	 * @since 3.6
	 * @var array|null
	 */
	private $cached_plugins = null;

	public function __construct() {
		$this->addons           = get_option( 'pmpro_addons', array() );
		$this->addons_timestamp = get_option( 'pmpro_addons_timestamp', false );

		add_action( 'admin_init', array( $this, 'admin_hooks' ), 0 ); // Priority 0 to run before other admin_init hooks.
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @access public
	 * @since 3.6
	 * @return PMPro_AddOns
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent the instance from being cloned.
	 *
	 * @access public
	 * @since 3.6
	 * @return void
	 * @throws Exception If the instance is cloned.
	 */
	public function __clone() {
		throw new Exception( esc_html__( 'PMPro_AddOns instance cannot be cloned', 'paid-memberships-pro' ) );
	}

	/**
	 * Prevent the instance from being unserialized.
	 *
	 * @access public
	 * @since 3.6
	 * @return void
	 * @throws Exception If the instance is unserialized.
	 */
	public function __wakeup() {
		throw new Exception( esc_html__( 'PMPro_AddOns instance cannot be unserialized', 'paid-memberships-pro' ) );
	}

	/**
	 * Admin hooks for managing Add Ons.
	 */
	public function admin_hooks() {
		$this->check_when_updating_plugins();
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_plugins_filter' ) );
		add_filter( 'http_request_args', array( $this, 'http_request_args_for_addons' ), 10, 2 );
		add_action( 'update_option_pmpro_license_key', array( $this, 'reset_update_plugins_cache' ), 10, 2 );
		// Register AJAX endpoints for add-on actions.
		$this->register_ajax_endpoints();
	}

	/**
	 * Get the Add On slugs for each category we identify.
	 *
	 * @since 2.8.x
	 *
	 * @return array $addon_cats An array of plugin categories and plugin slugs within each.
	 */
	public function get_addon_categories() {
		return array(
			'popular'         => array(
				'pmpro-abandoned-cart-recovery',
				'pmpro-add-paypal-express',
				'pmpro-advanced-levels-shortcode',
				'pmpro-approvals',
				'pmpro-courses',
				'pmpro-cpt',
				'pmpro-group-accounts',
				'pmpro-import-users-from-csv',
				'pmpro-member-directory',
				'pmpro-nav-menus',
				'pmpro-roles',
				'pmpro-set-expiration-dates',
				'pmpro-signup-shortcode',
				'pmpro-subscription-delays',
			),
			'association'     => array(
				'basic-user-avatars',
				'pmpro-add-name-to-checkout',
				'pmpro-approvals',
				'pmpro-donations',
				'pmpro-events',
				'pmpro-group-accounts',
				'pmpro-import-users-from-csv',
				'pmpro-member-directory',
				'pmpro-membership-card',
				'pmpro-membership-manager-role',
				'pmpro-pay-by-check',
				'pmpro-set-expiration-dates',
				'pmpro-shipping',
				'pmpro-subscription-delays',
			),
			'premium_content' => array(
				'pmpro-abandoned-cart-recovery',
				'pmpro-cpt',
				'pmpro-email-confirmation',
				'pmpro-events',
				'pmpro-google-analytics',
				'pmpro-series',
				'pmpro-user-pages',
			),
			'community'       => array(
				'pmpro-abandoned-cart-recovery',
				'pmpro-approvals',
				'pmpro-bbpress',
				'pmpro-buddypress',
				'pmpro-email-confirmation',
				'pmpro-import-users-from-csv',
				'pmpro-invite-only',
				'pmpro-membership-card',
			),
			'courses'         => array(
				'lifterlms',
				'pmpro-abandoned-cart-recovery',
				'pmpro-approvals',
				'pmpro-courses',
				'pmpro-cpt',
				'pmpro-google-analytics',
				'pmpro-member-badges',
				'pmpro-member-homepages',
				'pmpro-testimonials',
				'pmpro-user-pages',
			),
			'directory'       => array(
				'basic-user-avatars',
				'pmpro-approvals',
				'pmpro-member-badges',
				'pmpro-member-directory',
				'pmpro-shipping',
				'pmpro-testimonials',
			),
			'newsletter'      => array(
				'mailpoet-paid-memberships-pro-add-on',
				'convertkit-for-paid-memberships-pro',
				'pmpro-add-name-to-checkout',
				'pmpro-aweber',
				'pmpro-google-analytics',
				'pmpro-keap',
				'pmpro-mailchimp',
				'pmpro-testimonials',
			),
			'podcast'         => array(
				'pmpro-akismet',
				'pmpro-email-confirmation',
				'pmpro-events',
				'pmpro-google-analytics',
				'pmpro-invite-only',
				'pmpro-testimonials',
				'seriously-simple-podcasting',
			),
			'video'           => array(
				'pmpro-cpt',
				'pmpro-email-confirmation',
				'pmpro-events',
				'pmpro-google-analytics',
				'pmpro-invite-only',
				'pmpro-testimonials',
			),
		);
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
	public function reset_update_plugins_cache( $old_value, $value ) {
		delete_option( 'pmpro_addons_timestamp' );
		delete_site_transient( 'update_themes' );
		delete_site_transient( 'update_plugins' );
	}

	/**
	 * Disables SSL verification to prevent download package failures.
	 *
	 * @since 1.8.5
	 *
	 * @param array  $args  Array of request args.
	 * @param string $url  The URL to be pinged.
	 * @return array $args Amended array of request args.
	 */
	public function http_request_args_for_addons( $args, $url ) {
		// If this is an SSL request and we are performing an upgrade routine, disable SSL verification.
		if ( strpos( $url, 'https://' ) !== false && strpos( $url, PMPRO_LICENSE_SERVER ) !== false && strpos( $url, 'download' ) !== false ) {
			$args['sslverify'] = false;
		}

		return $args;
	}

	/**
	 * Get a list of installed Add Ons with incorrect folder names.
	 *
	 * @since 3.1
	 *
	 * @return array $incorrect_folder_names An array of Add Ons with incorrect folder names. The key is the installed folder name, the value is the Add On data.
	 */
	public function get_add_ons_with_incorrect_folder_names() {
		// Make an easily searchable array of installed plugins to reduce computational complexity.
		// The key of the array is the plugin filename, the value is the folder name.
		$installed_plugins = array();

		// Get the cached list of installed plugins.
		$cached_plugins = $this->get_installed_plugins();

		foreach ( $cached_plugins as $plugin_name => $plugin_data ) {
			// Skip plugins that are not in a folder.
			if ( false === strpos( $plugin_name, '/' ) ) {
				continue;
			}

			// Add the plugin to the $installed_plugins array.
			list( $plugin_folder, $plugin_filename ) = explode( '/', $plugin_name, 2 );
			$installed_plugins[ $plugin_filename ]   = $plugin_folder;
		}

		// Set up an array to track Add Ons with wrong folder names.
		// The key of the array is the equivalent of $plugin_name above, the value is the Add On data.
		$incorrect_folder_names = array();
		foreach ( $this->get_addons() as $addon ) {
			// Get information about the Add On.
			list( $addon_folder, $addon_filename ) = explode( '/', $addon['plugin'], 2 );

			// Check if the Add On is installed with an incorrect folder name.
			if ( array_key_exists( $addon_filename, $installed_plugins ) && $addon_folder !== $installed_plugins[ $addon_filename ] ) {
				// The Add On is installed with the wrong folder name. Add it to the array.
				$installed_name                            = $installed_plugins[ $addon_filename ] . '/' . $addon_filename;
				$incorrect_folder_names[ $installed_name ] = $addon;
			}
		}

		return $incorrect_folder_names;
	}

	/**
	 * Find a PMPro addon by slug.
	 *
	 * @since 1.8.5
	 *
	 * @param object $slug  The identifying slug for the addon (typically the directory name)
	 * @return object $addon containing plugin information or false if not found
	 */
	public function get_addon_by_slug( $slug ) {
		$addons = $this->get_addons();

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
	 * Infuse plugin update details when WordPress runs its update checker.
	 *
	 * @since 1.8.5
	 *
	 * @param object $value  The WordPress update object.
	 * @return object $value Amended WordPress update object on success, default if object is empty.
	 */
	public function update_plugins_filter( $value ) {

		// If no update object exists, return early.
		if ( empty( $value ) ) {
			return $value;
		}

		// Get Add On information
		$addons = $this->get_addons();

		// No Add Ons?
		if ( empty( $addons ) ) {
			return $value;
		}

		// Check Add Ons
		foreach ( $addons as $addon ) {
			// Skip for wordpress.org plugins
			if ( empty( $addon['License'] ) || $addon['License'] == 'wordpress.org' ) {
				continue;
			}

			// Get data for plugin
			$plugin_file     = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
			$plugin_file_abs = WP_PLUGIN_DIR . '/' . $plugin_file;

			// Couldn't find plugin? Skip
			if ( ! file_exists( $plugin_file_abs ) ) {
				continue;
			} else {
				$plugin_data = get_plugin_data( $plugin_file_abs, false, true );
			}

			// Compare versions
			if ( version_compare( $plugin_data['Version'], $addon['Version'], '<' ) ) {
				$value->response[ $plugin_file ]              = $this->get_plugin_API_object_from_addon( $addon );
				$value->response[ $plugin_file ]->new_version = $addon['Version'];

				// If we have an icon to show, add it to the response. Otherwise let it show the default icon.
				$icon = $this->get_addon_icon( $addon['Slug'] );
				if ( ! empty( $icon ) ) {
					$value->response[ $plugin_file ]->icons = array( 'default' => esc_url( $icon ) );
				}
			} else {
				$value->no_update[ $plugin_file ] = $this->get_plugin_API_object_from_addon( $addon );
			}
		}

		// Return the update object.
		return $value;
	}

	/**
	 * Get the list of available addons.
	 *
	 * @param bool $force_check Whether to force a check for new addons.
	 *
	 * @return array
	 * @since 3.6
	 */
	public function get_addons( $force_check = false ) {
		$addons           = $this->addons;
		$addons_timestamp = $this->addons_timestamp;
		// Check if forcing a pull from the server
		$force_check = ! empty( $_REQUEST['force-check'] ) || $force_check;

		// if no addons locally, we need to hit the server
		if ( empty( $addons ) || $force_check || current_time( 'timestamp' ) > $addons_timestamp + 86400 ) {

			$addons = $this->get_remote_addons();

		}

		return $addons;
	}

	/**
	 * Install an Add On by slug using WordPress core upgraders.
	 *
	 * @since 3.2.0
	 *
	 * @param string $slug The add on slug (directory name in the repo).
	 * @return array|WP_Error Result array on success or WP_Error on failure.
	 */
	public function install( $slug = '' ) {
		$slug = sanitize_key( (string) $slug );
		if ( empty( $slug ) ) {
			return new WP_Error( 'pmpro_addon_install_invalid_slug', __( 'Invalid Add On slug.', 'paid-memberships-pro' ) );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error( 'pmpro_addon_install_cap', __( 'You do not have permission to install plugins.', 'paid-memberships-pro' ) );
		}

		$addon = $this->get_addon_by_slug( $slug );
		if ( empty( $addon ) ) {
			return new WP_Error( 'pmpro_addon_not_found', __( 'Add On not found.', 'paid-memberships-pro' ) );
		}

		// License/access check for premium add ons.
		if ( isset( $addon['License'] ) && pmpro_license_type_is_premium( $addon['License'] ) && ! $this->can_download_addon_with_license( $addon['License'] ) ) {
			return new WP_Error( 'pmpro_addon_license_required', sprintf( __( 'A valid PMPro %s license is required to install this Add On.', 'paid-memberships-pro' ), ucwords( $addon['License'] ) ) );
		}

		$package = $this->get_download_package_url( $slug );
		if ( is_wp_error( $package ) ) {
			return $package;
		}

		// Prepare filesystem and upgrader.
		$fs_ready = $this->ensure_filesystem();
		if ( is_wp_error( $fs_ready ) ) {
			return $fs_ready;
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$skin     = $this->get_upgrader_skin();
		$upgrader = new Plugin_Upgrader( $skin );

		$result = $upgrader->install( $package );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! $result ) {
			return new WP_Error( 'pmpro_addon_install_failed', __( 'Installation failed.', 'paid-memberships-pro' ) );
		}

		// Best-effort resolve installed plugin file.
		$plugin_file = $this->resolve_plugin_file( $slug );
		return array(
			'success'     => true,
			'action'      => 'install',
			'plugin_file' => $plugin_file,
			'message'     => __( 'Add On installed.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Activate an Add On.
	 *
	 * @since 3.2.0
	 *
	 * @param string $slug_or_plugin Slug (folder) or plugin file (folder/file.php).
	 * @return array|WP_Error Result array or WP_Error.
	 */
	public function activate( $slug_or_plugin = '' ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( empty( $slug_or_plugin ) ) {
			return new WP_Error( 'pmpro_addon_activate_invalid', __( 'Invalid Add On.', 'paid-memberships-pro' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
				return new WP_Error( 'pmpro_addon_activate_cap', __( 'You do not have permission to activate plugins.', 'paid-memberships-pro' ) );
		}

		$plugin_file = $this->resolve_plugin_file( $slug_or_plugin );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		if ( is_plugin_active( $plugin_file ) ) {
			return array(
				'success'     => true,
				'action'      => 'activate',
				'plugin_file' => $plugin_file,
				'message'     => __( 'Add On already active.', 'paid-memberships-pro' ),
			);
		}

		$activate = activate_plugin( $plugin_file, '', false );
		if ( is_wp_error( $activate ) ) {
			return $activate;
		}

		return array(
			'success'     => true,
			'action'      => 'activate',
			'plugin_file' => $plugin_file,
			'message'     => __( 'Add On activated.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Deactivate an Add On.
	 *
	 * @since 3.2.0
	 *
	 * @param string $slug_or_plugin Slug (folder) or plugin file (folder/file.php).
	 * @return array|WP_Error Result array or WP_Error.
	 */
	public function deactivate( $slug_or_plugin = '' ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( empty( $slug_or_plugin ) ) {
			return new WP_Error( 'pmpro_addon_deactivate_invalid', __( 'Invalid Add On.', 'paid-memberships-pro' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
				return new WP_Error( 'pmpro_addon_deactivate_cap', __( 'You do not have permission to deactivate plugins.', 'paid-memberships-pro' ) );
		}

		$plugin_file = $this->resolve_plugin_file( $slug_or_plugin );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		if ( ! is_plugin_active( $plugin_file ) ) {
			return array(
				'success'     => true,
				'action'      => 'deactivate',
				'plugin_file' => $plugin_file,
				'message'     => __( 'Add On already inactive.', 'paid-memberships-pro' ),
			);
		}

		deactivate_plugins( array( $plugin_file ), false, false );
		if ( is_plugin_active( $plugin_file ) ) {
			return new WP_Error( 'pmpro_addon_deactivate_failed', __( 'Deactivation failed.', 'paid-memberships-pro' ) );
		}

		return array(
			'success'     => true,
			'action'      => 'deactivate',
			'plugin_file' => $plugin_file,
			'message'     => __( 'Add On deactivated.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Update an Add On.
	 *
	 * @since 3.6
	 *
	 * @param string $slug_or_plugin Slug (folder) or plugin file (folder/file.php).
	 * @return array|WP_Error Result array or WP_Error.
	 */
	public function update( $slug_or_plugin = '' ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( empty( $slug_or_plugin ) ) {
			return new WP_Error( 'pmpro_addon_update_invalid', __( 'Invalid Add On.', 'paid-memberships-pro' ) );
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return new WP_Error( 'pmpro_addon_update_cap', __( 'You do not have permission to update plugins.', 'paid-memberships-pro' ) );
		}

		$plugin_file = $this->resolve_plugin_file( $slug_or_plugin );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		// Ensure WordPress has the latest update data before attempting an upgrade.
		// When multiple updates are triggered sequentially over AJAX, the in-memory
		// update transient can be stale after the first upgrade completes. Refreshing
		// here prevents false negatives from Plugin_Upgrader::upgrade().
		$this->refresh_update_data();

		// License gating when applicable.
		$slug  = $this->maybe_extract_slug( $slug_or_plugin );
		$addon = $slug ? $this->get_addon_by_slug( $slug ) : false;
		if ( ! empty( $addon ) && isset( $addon['License'] ) && pmpro_license_type_is_premium( $addon['License'] ) && ! $this->can_download_addon_with_license( $addon['License'] ) ) {
			return new WP_Error( 'pmpro_addon_license_required', sprintf( __( 'A valid PMPro %s license is required to update this Add On.', 'paid-memberships-pro' ), ucwords( $addon['License'] ) ) );
		}

		$fs_ready = $this->ensure_filesystem();
		if ( is_wp_error( $fs_ready ) ) {
			return $fs_ready;
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$skin     = $this->get_upgrader_skin();
		$upgrader = new Plugin_Upgrader( $skin );

		$result = $upgrader->upgrade( $plugin_file );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( false === $result ) {
			// Try one more time after a hard refresh of update data.
			$this->refresh_update_data();
			$result = $upgrader->upgrade( $plugin_file );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( false === $result ) {
				// As a last resort for PMPro-hosted add ons, attempt a direct reinstall
				// using the package URL. This can occur if the transient briefly lacks
				// the response entry even though an update is available.
				if ( ! empty( $addon ) && ! empty( $slug ) ) {
					$package = $this->get_download_package_url( $slug );
					if ( ! is_wp_error( $package ) && ! empty( $package ) ) {
						$install_result = $upgrader->install( $package );
						if ( $install_result ) {
							return array(
								'success'     => true,
								'action'      => 'update',
								'plugin_file' => $plugin_file,
								'message'     => __( 'Add On updated.', 'paid-memberships-pro' ),
							);
						}
					}
				}
				return new WP_Error( 'pmpro_addon_update_failed', __( 'Update failed.', 'paid-memberships-pro' ) );
			}
		}

		return array(
			'success'     => true,
			'action'      => 'update',
			'plugin_file' => $plugin_file,
			'message'     => __( 'Add On updated.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Refresh the core plugin update data and PMPro add-on responses.
	 *
	 * @since 3.6
	 * @return void
	 */
	private function refresh_update_data() {
		// Make sure helper functions are loaded in AJAX context.
		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		// Force a fresh check from WordPress.org and our filters.
		wp_version_check( array(), true );
		wp_update_plugins();
	}

	/**
	 * Delete (uninstall) an Add On.
	 *
	 * @since 3.6
	 *
	 * @param string $slug_or_plugin Slug (folder) or plugin file (folder/file.php).
	 * @return array|WP_Error Result array or WP_Error.
	 */
	public function delete( $slug_or_plugin = '' ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( empty( $slug_or_plugin ) ) {
			return new WP_Error( 'pmpro_addon_delete_invalid', __( 'Invalid Add On.', 'paid-memberships-pro' ) );
		}

		if ( ! current_user_can( 'delete_plugins' ) ) {
			return new WP_Error( 'pmpro_addon_delete_cap', __( 'You do not have permission to delete plugins.', 'paid-memberships-pro' ) );
		}

		$plugin_file = $this->resolve_plugin_file( $slug_or_plugin );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		// Deactivate before deleting.
		if ( is_plugin_active( $plugin_file ) ) {
			deactivate_plugins( array( $plugin_file ), false, false );
		}

		$fs_ready = $this->ensure_filesystem();
		if ( is_wp_error( $fs_ready ) ) {
			return $fs_ready;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$deleted = delete_plugins( array( $plugin_file ) );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		if ( ! $deleted ) {
			return new WP_Error( 'pmpro_addon_delete_failed', __( 'Delete failed.', 'paid-memberships-pro' ) );
		}

		return array(
			'success'     => true,
			'action'      => 'delete',
			'plugin_file' => $plugin_file,
			'message'     => __( 'Add On deleted.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Attempt to initialize the WordPress filesystem API for upgrader operations.
	 * Returns WP_Error when credentials are required and not provided.
	 *
	 * @since 3.6
	 *
	 * @return true|WP_Error
	 */
	private function ensure_filesystem() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		// Attempt to initialize using any available method (Direct, etc.).
		if ( WP_Filesystem() ) {
			return true;
		}
		// If we cannot initialize, credentials are likely required.
		return new WP_Error( 'pmpro_fs_credentials', __( 'Filesystem credentials are required to continue.', 'paid-memberships-pro' ) );
	}

	/**
	 * Get a quiet upgrader skin to capture output nicely for programmatic use.
	 *
	 * @since 3.6
	 *
	 * @return Automatic_Upgrader_Skin
	 */
	private function get_upgrader_skin() {
		// Use the core automatic skin to avoid direct output.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$args = array(
			'skip_header' => true,
			'url'         => admin_url( 'plugins.php' ),
			'nonce'       => wp_create_nonce( 'updates' ),
			'title'       => __( 'PMPro Add On Update', 'paid-memberships-pro' ),
		);
		return new Automatic_Upgrader_Skin( $args );
	}

	/**
	 * Resolve plugin file (folder/file.php) from a slug or plugin identifier.
	 *
	 * @since 3.6
	 *
	 * @param string $slug_or_plugin Slug or plugin file.
	 * @return string|WP_Error Plugin file or WP_Error if not found.
	 */
	private function resolve_plugin_file( $slug_or_plugin ) {
		$slug_or_plugin = (string) $slug_or_plugin;
		if ( false !== strpos( $slug_or_plugin, '/' ) && false !== strpos( $slug_or_plugin, '.php' ) ) {
			return $slug_or_plugin; // Already a plugin file.
		}

		$slug    = sanitize_key( $slug_or_plugin );
		$default = $slug . '/' . $slug . '.php';
		$abs     = WP_PLUGIN_DIR . '/' . $default;
		if ( file_exists( $abs ) ) {
			return $default;
		}

		// Search installed plugins for a folder matching the slug.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$installed = array_keys( $this->get_installed_plugins() );
		foreach ( $installed as $plugin_file ) {
			if ( 0 === strpos( $plugin_file, $slug . '/' ) ) {
				return $plugin_file;
			}
		}

		return new WP_Error( 'pmpro_plugin_file_not_found', __( 'Plugin file could not be resolved.', 'paid-memberships-pro' ) );
	}

	/**
	 * Maybe extract a slug from a slug or plugin file string.
	 *
	 * @since 3.6
	 *
	 * @param string $slug_or_plugin Input string.
	 * @return string Slug or empty string if not detected.
	 */
	private function maybe_extract_slug( $slug_or_plugin ) {
		$slug_or_plugin = (string) $slug_or_plugin;
		if ( false !== strpos( $slug_or_plugin, '/' ) ) {
			list( $slug ) = explode( '/', $slug_or_plugin );
			return sanitize_key( $slug );
		}
		return sanitize_key( $slug_or_plugin );
	}

	/**
	 * Get the download/package URL for an Add On by slug.
	 *
	 * @since 3.6
	 *
	 * @param string $slug The Add On slug.
	 * @return string|WP_Error Package URL or WP_Error.
	 */
	private function get_download_package_url( $slug ) {
		$slug = sanitize_key( $slug );
		// Ensure the plugins_api() function is available (not always loaded during admin-ajax requests).
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$api = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
		if ( is_wp_error( $api ) || empty( $api ) || empty( $api->download_link ) ) {
			return new WP_Error( 'pmpro_addon_package_missing', __( 'Could not determine download URL for this Add On.', 'paid-memberships-pro' ) );
		}
		return esc_url_raw( $api->download_link );
	}

	/**
	 * Register AJAX endpoints for add-on operations.
	 *
	 * @since 3.6
	 */
	public function register_ajax_endpoints() {
		add_action( 'wp_ajax_pmpro_addon_install', array( $this, 'ajax_install_addon' ) );
		add_action( 'wp_ajax_pmpro_addon_update', array( $this, 'ajax_update_addon' ) );
		add_action( 'wp_ajax_pmpro_addon_activate', array( $this, 'ajax_activate_addon' ) );
		add_action( 'wp_ajax_pmpro_addon_deactivate', array( $this, 'ajax_deactivate_addon' ) );
		add_action( 'wp_ajax_pmpro_addon_delete', array( $this, 'ajax_delete_addon' ) );
	}

	/**
	 * AJAX: Install.
	 *
	 * @since 3.6
	 * @return void
	 */
	public function ajax_install_addon() {
		check_ajax_referer( 'pmpro_addons_actions', 'nonce' );
		$slug   = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$result = $this->install( $slug );
		$this->send_ajax_result( $result );
	}

	/**
	 * AJAX: Update Add On
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	public function ajax_update_addon() {
		check_ajax_referer( 'pmpro_addons_actions', 'nonce' );
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$result = $this->update( $target );
		$this->send_ajax_result( $result );
	}

	/**
	 * AJAX: Activate Add On
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	public function ajax_activate_addon() {
		check_ajax_referer( 'pmpro_addons_actions', 'nonce' );
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$result = $this->activate( $target );
		$this->send_ajax_result( $result );
	}

	/**
	 * AJAX: Deactivate Add On
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	public function ajax_deactivate_addon() {
		check_ajax_referer( 'pmpro_addons_actions', 'nonce' );
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$result = $this->deactivate( $target );
		$this->send_ajax_result( $result );
	}

	/**
	 * AJAX: Delete Add On
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	public function ajax_delete_addon() {
		check_ajax_referer( 'pmpro_addons_actions', 'nonce' );
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$result = $this->delete( $target );
		$this->send_ajax_result( $result );
	}

	/**
	 * Helper to send standardized AJAX responses.
	 *
	 * @since 3.6
	 *
	 * @param array|WP_Error $result Operation result.
	 */
	private function send_ajax_result( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				)
			);
		}
		wp_send_json_success( $result );
	}


	/**
	 * Get the Add On icon from the plugin slug.
	 *
	 * @since 2.8.x
	 *
	 * @param string $slug The identifying slug for the addon (typically the directory name).
	 * @return string|false $plugin_icon_src The src URL for the plugin icon or false if PMPRO_DIR is not defined.
	 */
	public function get_addon_icon( $slug ) {
		// If PMPRO_DIR is not defined, bail. This may happen in the Update Manager Add On.
		if ( ! defined( 'PMPRO_DIR' ) ) {
			return false;
		}

		if ( file_exists( PMPRO_DIR . '/images/add-ons/' . $slug . '.png' ) ) {
			$plugin_icon_src = PMPRO_URL . '/images/add-ons/' . $slug . '.png';
		} else {
			$plugin_icon_src = PMPRO_URL . '/images/add-ons/default-icon.png';
		}
		return $plugin_icon_src;
	}

	/**
	 * Setup plugin updaters
	 *
	 * @since  1.8.5
	 */
	public function plugins_api( $api, $action = '', $args = null ) {
		// Not even looking for plugin information? Or not given slug?
		if ( 'plugin_information' != $action || empty( $args->slug ) ) {
			return $api;
		}

		// get addon information
		$addon = $this->get_addon_by_slug( $args->slug );

		// no addons?
		if ( empty( $addon ) ) {
			return $api;
		}

		// handled by wordpress.org?
		if ( empty( $addon['License'] ) || $addon['License'] == 'wordpress.org' ) {
			return $api;
		}

		// Create a new stdClass object and populate it with our plugin information.
		$api = $this->get_plugin_API_object_from_addon( $addon );
		return $api;
	}

	/**
	 * Detect when trying to update a PMPro Plus plugin without a valid license key.
	 *
	 * @since 1.9
	 */
	public function check_when_updating_plugins() {
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
			$premium_addons  = array();
			$premium_plugins = array();
			foreach ( $plugins as $plugin ) {
				$slug  = str_replace( '.php', '', basename( $plugin ) );
				$addon = $this->get_addon_by_slug( $slug );
				if ( ! empty( $addon ) && pmpro_license_type_is_premium( $addon['License'] ) ) {
					if ( ! isset( $premium_addons[ $addon['License'] ] ) ) {
						$premium_addons[ $addon['License'] ]  = array();
						$premium_plugins[ $addon['License'] ] = array();
					}
					$premium_addons[ $addon['License'] ][]  = $addon['Name'];
					$premium_plugins[ $addon['License'] ][] = $plugin;
				}
			}
			unset( $plugin );

			// if Plus addons found, check license key
			if ( ! empty( $premium_plugins ) ) {
				foreach ( $premium_plugins as $license_type => $premium_plugin ) {
					// if they have a good license, skip the error
					if ( $this->can_download_addon_with_license( $license_type ) ) {
						continue;
					}

					// show error
					$msg = wp_kses(
						sprintf( __( 'You must have a <a target="_blank" href="https://www.paidmembershipspro.com/pricing/?utm_source=wp-admin&utm_pluginlink=bulkupdate">valid PMPro %1$s License Key</a> to update PMPro %2$s add ons. The following plugins will not be updated:', 'paid-memberships-pro' ), ucwords( $license_type ), ucwords( $license_type ) ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<div class="error"><p>' . $msg . ' <strong>' . esc_html( implode( ', ', $premium_addons[ $license_type ] ) ) . '</strong></p></div>';
				}
			}

			// can exit out of this function now
			return;
		}

		// upgrading just one or plugin via an update.php link
		if ( basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'update.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'upgrade-plugin' && ! empty( $_REQUEST['plugin'] ) ) {
			// figure out which plugin we are updating
			$plugin = urldecode( trim( sanitize_text_field( $_REQUEST['plugin'] ) ) );

			$slug  = str_replace( '.php', '', basename( $plugin ) );
			$addon = $this->get_addon_by_slug( $slug );

			if ( ! empty( $addon ) && pmpro_license_type_is_premium( $addon['License'] ) && ! $this->can_download_addon_with_license( $addon['License'] ) ) {
				require_once ABSPATH . 'wp-admin/admin-header.php';

				$msg = sprintf(
					__( 'You must have a <a href="https://www.paidmembershipspro.com/pricing/?utm_source=wp-admin&utm_pluginlink=addon_update">valid PMPro %1$s License Key</a> to update PMPro %2$s add ons.', 'paid-memberships-pro' ),
					ucwords( $addon['License'] ),
					ucwords( $addon['License'] )
				);

				$html  = '<div class="wrap"><h2>' . esc_html__( 'Update Plugin', 'paid-memberships-pro' ) . '</h2>';
				$html .= '<div class="error"><p>' . wp_kses_post( $msg ) . '</p></div>';
				$html .= '<p><a href="' . esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ) . '" target="_parent">' . esc_html__( 'Return to the PMPro Add Ons page', 'paid-memberships-pro' ) . '</a></p>';
				$html .= '</div>';

				echo wp_kses_post( $html );

				include ABSPATH . 'wp-admin/admin-footer.php';

				// can exit WP now
				exit;
			}
		}

		// updating via AJAX on the plugins page
		if ( basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'admin-ajax.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'update-plugin' && ! empty( $_REQUEST['plugin'] ) ) {
			// figure out which plugin we are updating
			$plugin = urldecode( trim( sanitize_text_field( $_REQUEST['plugin'] ) ) );

			$slug  = str_replace( '.php', '', basename( $plugin ) );
			$addon = $this->get_addon_by_slug( $slug );
			if ( ! empty( $addon ) && pmpro_license_type_is_premium( $addon['License'] ) && ! $this->can_download_addon_with_license( $addon['License'] ) ) {
				$msg = sprintf( __( 'You must enter a valid PMPro %s License Key in the PMPro Settings to update this Add On.', 'paid-memberships-pro' ), ucwords( $addon['License'] ) );
				echo '<div class="error"><p>' . esc_html( $msg ) . '</p></div>';

				// can exit WP now
				exit;
			}
		}
	}

	/**
	 * Check if an add on can be downloaded based on it's license.
	 *
	 * @since 2.7.4
	 * @param string $addon_license The license type of the add on to check.
	 * @return bool True if the user's license key can download that add on,
	 *              False if the user's license key cannot download it.
	 */
	public function can_download_addon_with_license( $addon_license ) {
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
	 * Get remote addons from the License Server.
	 *
	 * @return array
	 * @since 3.6
	 */
	private function get_remote_addons() {

		$addons = array();

		/**
		 * Filter to change the timeout for this wp_remote_get() request.
		 *
		 * @since 1.8.5.1
		 *
		 * @param int $timeout The number of seconds before the request times out
		 */
		$timeout = apply_filters( 'pmpro_get_addons_timeout', 5 );

		// Get Add Ons from the License Server
		$remote_addons = wp_remote_get( PMPRO_LICENSE_SERVER . 'addons/', array( 'timeout' => (int) $timeout ) );

		// Check for errors and if we're okay, save the addons formatted
		if ( is_wp_error( $remote_addons ) ) {
			pmpro_setMessage( 'Could not connect to the PMPro License Server to update addon information. Try again later.', 'error' );
			// Return cached addons if available
			return $this->addons ? : array();
		} elseif ( ! empty( $remote_addons ) && $remote_addons['response']['code'] == 200 ) {

			// Update the timestamp
			update_option( 'pmpro_addons_timestamp', current_time( 'timestamp' ), 'no' );

			$addons = json_decode( wp_remote_retrieve_body( $remote_addons ), true );

			// If for some reason the addons are not formatted correctly
			if ( empty( $addons ) ) {
				pmpro_setMessage( 'No addons found.', 'error' );
				return $addons;
			}

			// Create a short name for each Add On.
			foreach ( $addons as $key => $value ) {
				$addons[ $key ]['ShortName'] = trim( str_replace( array( 'Add On', 'Paid Memberships Pro - ' ), '', $addons[ $key ]['Title'] ) );
			}
			$short_names = array_column( $addons, 'ShortName' );
			// Sort the addons by short name.
			array_multisort( $short_names, SORT_ASC, SORT_STRING | SORT_FLAG_CASE, $addons );

			// Update addons in cache.
			update_option( 'pmpro_addons', $addons, 'no' );
		}

		return $addons;
	}

	/**
	 * Convert the format from get_addons() to that needed for plugins_api
	 *
	 * @param array $addon The addon information.
	 *
	 * @since  1.8.5
	 */
	private function get_plugin_API_object_from_addon( $addon ) {
		$api = new stdClass();

		if ( empty( $addon ) ) {
			return $api;
		}

		// add info
		$api->name           = isset( $addon['Name'] ) ? $addon['Name'] : '';
		$api->slug           = isset( $addon['Slug'] ) ? $addon['Slug'] : '';
		$api->plugin         = isset( $addon['plugin'] ) ? $addon['plugin'] : '';
		$api->version        = isset( $addon['Version'] ) ? $addon['Version'] : '';
		$api->author         = isset( $addon['Author'] ) ? $addon['Author'] : '';
		$api->author_profile = isset( $addon['AuthorURI'] ) ? $addon['AuthorURI'] : '';
		$api->requires       = isset( $addon['Requires'] ) ? $addon['Requires'] : '';
		$api->tested         = isset( $addon['Tested'] ) ? $addon['Tested'] : '';
		$api->last_updated   = isset( $addon['LastUpdated'] ) ? $addon['LastUpdated'] : '';
		$api->homepage       = isset( $addon['URI'] ) ? $addon['URI'] : '';
		$api->download_link  = isset( $addon['Download'] ) ? $addon['Download'] : '';
		$api->package        = isset( $addon['Download'] ) ? $addon['Download'] : '';

		// add sections
		if ( ! empty( $addon['Description'] ) ) {
			$api->sections['description'] = $addon['Description'];
		}
		if ( ! empty( $addon['Installation'] ) ) {
			$api->sections['installation'] = $addon['Installation'];
		}
		if ( ! empty( $addon['FAQ'] ) ) {
			$api->sections['faq'] = $addon['FAQ'];
		}
		if ( ! empty( $addon['Changelog'] ) ) {
			$api->sections['changelog'] = $addon['Changelog'];
		}

		// get license key if one is available
		$key = get_option( 'pmpro_license_key', '' );
		if ( ! empty( $key ) && ! empty( $api->download_link ) ) {
			$api->download_link = add_query_arg( 'key', $key, $api->download_link );
		}
		if ( ! empty( $key ) && ! empty( $api->package ) ) {
			$api->package = add_query_arg( 'key', $key, $api->package );
		}

		if ( empty( $api->upgrade_notice ) && pmpro_license_type_is_premium( $addon['License'] ) ) {
			if ( ! pmpro_license_isValid( null, $addon['License'] ) ) {
				$api->upgrade_notice = sprintf( __( 'Important: This plugin requires a valid PMPro %s license key to update.', 'paid-memberships-pro' ), ucwords( $addon['License'] ) );
			}
		}

		return $api;
	}

	/**
	 * Get installed plugins with caching.
	 *
	 * @since 3.6
	 *
	 * @return array Installed plugins.
	 */
	private function get_installed_plugins() {
		if ( null === $this->cached_plugins ) {
			$this->cached_plugins = get_plugins();
		}
		return $this->cached_plugins;
	}
}
