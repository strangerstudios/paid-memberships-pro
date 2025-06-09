<?php

/**
 * The functionality that includes PMPro data within Site Health information.
 *
 * @since 2.6.2
 */
class PMPro_Site_Health {

	/**
	 * The current object instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Initialize class object and use it for future init calls.
	 *
	 * @since 2.6.2
	 *
	 * @return self The class object.
	 */
	public static function init() {
		if ( ! is_object( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add hooks needed for functionality.
	 *
	 * @since 2.6.2
	 */
	public function hook() {
		add_filter( 'debug_information', [ $this, 'debug_information' ] );
	}

	/**
	 * Remove hooks needed for functionality.
	 *
	 * @since 2.6.2
	 */
	public function unhook() {
		remove_filter( 'debug_information', [ $this, 'debug_information' ] );
	}

	/**
	 * Add our data to Site Health information.
	 *
	 * @since 2.6.2
	 *
	 * @param array $info The Site Health information.
	 *
	 * @return array The updated Site Health information.
	 */
	public function debug_information( $info ) {
		$info['pmpro'] = [
			'label'       => 'Paid Memberships Pro',
			'description' => __( 'This debug information for your Paid Memberships Pro installation can assist you in getting support.', 'paid-memberships-pro' ),
			'fields'      => [
				'pmpro-cron-jobs'            => [
					'label' => __( 'Cron Job Status', 'paid-memberships-pro' ),
					'value' => self::get_cron_jobs(),
				],
				'pmpro-gateway'              => [
					'label' => __( 'Payment Gateway', 'paid-memberships-pro' ),
					'value' => self::get_gateway(),
				],
				'pmpro-gateway-env'          => [
					'label' => __( 'Payment Gateway Environment', 'paid-memberships-pro' ),
					'value' => self::get_gateway_env(),
				],
				'pmpro-orders'               => [
					'label' => __( 'Orders', 'paid-memberships-pro' ),
					'value' => self::get_orders(),
				],
				'pmpro-discount-codes'       => [
					'label' => __( 'Discount Codes', 'paid-memberships-pro' ),
					'value' => self::get_discount_codes(),
				],
				'pmpro-sessions'       => [
					'label' => __( 'PHP Sessions', 'paid-memberships-pro' ),
					'value' => self::test_sessions(),
				],
				'pmpro-membership-levels'    => [
					'label' => __( 'Membership Levels', 'paid-memberships-pro' ),
					'value' => self::get_levels(),
				],
				'pmpro-level-groups'         => [
					'label' => __( 'Level Groups', 'paid-memberships-pro' ),
					'value' => self::get_level_groups(),
				],
				'pmpro-custom-templates'     => [
					'label' => __( 'Custom Templates', 'paid-memberships-pro' ),
					'value' => self::get_custom_templates(),
				],
				'pmpro-getfile-usage'        => [
					'label' => __( 'getfile.php Usage', 'paid-memberships-pro' ),
					'value' => self::get_getfile_usage(),
				],
				'pmpro-htaccess-cache-usage' => [
					'label' => __( '.htaccess Cache Usage', 'paid-memberships-pro' ),
					'value' => self::get_htaccess_cache_usage(),
				],
				'pmpro-pages' => [
					'label' => __( 'Membership Pages', 'paid-memberships-pro' ),
					'value' => self::get_pmpro_pages(),
				],
				'pmpro-library-conflicts' => [
					'label' => __( 'Library Conflicts', 'paid-memberships-pro' ),
					'value' => self::get_library_conflicts(),
				],
				'pmpro-add-ons-incorrect-folder-names' => [
					'label' => __( 'Incorrect Add On Folder Names', 'paid-memberships-pro' ),
					'value' => self::get_add_ons_with_incorrect_folder_name(),
        ],
				'pmpro-current-site-url' => [
					'label' => __( 'Current Site URL', 'paid-memberships-pro' ),
					'value' => get_site_url(),
				],
				'pmpro-recorded-site-url' => [
					'label' => __( 'Last Known Site URL', 'paid-memberships-pro' ),
					'value' => get_option( 'pmpro_last_known_url' ),
				],
				'pmpro-pause-mode' => [
					'label' => __( 'Pause Mode', 'paid-memberships-pro' ),
					'value' => self::get_pause_mode_state(),
				],
			],
		];

		// Automatically add information about constants set.
		$info['pmpro']['fields'] = array_merge( $info['pmpro']['fields'], self::get_constants() );

		if ( function_exists( 'pmpro_add_site_health_info_2_10_6' ) ) {
			// If the 2.10.6 update cleaned up sensitive order meta data, we want to show that.
			$info = pmpro_add_site_health_info_2_10_6( $info );
		}

		return $info;
	}

	/**
	 * Gets the level information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The level information.
	 */
	public function get_levels() {
		$membership_levels = pmpro_getAllLevels( true, true );

		if ( ! $membership_levels ) {
			return __( 'No Levels Found', 'paid-memberships-pro' );
		}

		foreach ( $membership_levels as &$membership_level ) {
			$membership_level->meta = get_pmpro_membership_level_meta( $membership_level->id );

			$membership_level = apply_filters( 'pmpro_site_health_info_membership_level', $membership_level );
		}

		return wp_json_encode( $membership_levels, JSON_PRETTY_PRINT );
	}

	/**
	 * Get the level group information.
	 *
	 * @since 3.0.2
	 *
	 * @return string The level group information.
	 */
	public function get_level_groups() {
		$level_groups = pmpro_get_level_groups();

		if ( ! $level_groups ) {
			return __( 'No Level Groups Found', 'paid-memberships-pro' );
		}

		// Add the level IDs to the group objects.
		foreach ( $level_groups as $group_id => $group ) {
			$group->level_ids = pmpro_get_level_ids_for_group( $group_id );
		}

		return wp_json_encode( $level_groups, JSON_PRETTY_PRINT );
	}

	/**
	 * Get the discount code information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The discount code information.
	 */
	public function get_discount_codes() {
		global $wpdb;

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->pmpro_discount_codes`" );

		// translators: %d: The total count of discount codes.
		return sprintf( _n( '%d discount code', '%d discount codes', $count, 'paid-memberships-pro' ), $count );
	}

	/**
	 * Get the order information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The order information.
	 */
	public function get_orders() {
		global $wpdb;

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->pmpro_membership_orders`" );

		// translators: %d: The total count of orders.
		return sprintf( _n( '%d order', '%d orders', $count, 'paid-memberships-pro' ), $count );
	}

	/**
	 * Get the payment gateway information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The payment gateway information.
	 */
	public function get_gateway() {
		$gateway  = get_option( 'pmpro_gateway' );
		$gateways = pmpro_gateways();

		// Check if gateway is registered.
		if ( ! isset( $gateways[ $gateway ] ) ) {
			// translators: %s: The gateway name that is not registered.
			return sprintf( __( '%s (gateway not registered)', 'paid-memberships-pro' ), $gateway );
		}

		$gateway_text = $gateways[ $gateway ];

		// Custom Stripe gateway information.
		if ( 'stripe' === $gateway ) {
			$stripe = new PMProGateway_stripe();

			$legacy  = $stripe->using_legacy_keys();
			$api = $stripe->using_api_keys();
			$connect = $stripe->has_connect_credentials();

			if ( defined( 'PMPRO_STRIPE_API_VERSION' ) ) {
				$gateway_text .= ' (' . __( 'API Version', 'paid-memberships-pro' ) . ': ' . PMPRO_STRIPE_API_VERSION . ')';
			}

			if ( $legacy ) {
				$gateway_text .= ' (' . __( 'Legacy Keys', 'paid-memberships-pro' ) . ')';
				return $gateway_text . ' [' . $gateway . ':legacy-keys]';
			}

			if ( $api ) {
				$gateway_text .= ' (' . __( 'API Keys', ' paid-memberships-pro' ) . ')';
				return $gateway_text . ' [' . $gateway . ':api-keys ]';
			}

			if ( $connect ) {
				$gateway_text .= ' (' . __( 'Stripe Connect', 'paid-memberships-pro' ) . ')';
				return $gateway_text . ' [' . $gateway . ':stripe-connect]';
			}

		}

		return $gateway_text . ' [' . $gateway . ']';
	}

	/**
	 * Get the payment gateway environment information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The payment gateway environment information.
	 */
	public function get_gateway_env() {
		$environment  = get_option( 'pmpro_gateway_environment' );
		$environments = [
			'sandbox' => __( 'Sandbox/Testing', 'paid-memberships-pro' ),
			'live'    => __( 'Live/Production', 'paid-memberships-pro' ),
		];

		// Check if environment is registered.
		if ( ! isset( $environments[ $environment ] ) ) {
			// translators: %s: The environment name that is not registered.
			return sprintf( __( '%s (environment not registered)', 'paid-memberships-pro' ), $environment );
		}

		return $environments[ $environment ] . ' [' . $environment . ']';
	}

	/**
	 * Tests if PHP sessions are enabled
	 *
	 * @since 2.9
	 *
	 * @return string The PHP Session data.
	 */
	public function test_sessions() {

		$session_data = array();

		$php_session_status = session_status();

		if ( $php_session_status !== 0 || $php_session_status !== PHP_SESSIONS_DISABLED ) {
			$session_data['session_status'] = __( 'Active', 'paid-memberships-pro' );
		} else {
			$session_data['session_status'] = __( 'Inactive', 'paid-memberships-pro' );
		}

		if ( defined( 'PANTHEON_SESSIONS_VERSION' ) ) {
			$session_data['wp_native_sessions'] = __( 'Active', 'paid-memberships-pro' );
		}

		return $session_data;

	}

	/**
	 * Get the custom template information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The custom template information.
	 */
	public function get_custom_templates() {
		// Create a $template => $path array of all default page templates.
		$default_templates = array(
			'account' => PMPRO_DIR . '/pages/account.php',
			'billing' => PMPRO_DIR . '/pages/billing.php',
			'cancel' => PMPRO_DIR . '/pages/cancel.php',
			'checkout' => PMPRO_DIR . '/pages/checkout.php',
			'confirmation' => PMPRO_DIR . '/pages/confirmation.php',
			'invoice' => PMPRO_DIR . '/pages/invoice.php',
			'levels' => PMPRO_DIR . '/pages/levels.php',
			'login' => PMPRO_DIR . '/pages/login.php',
			'member_profile_edit' => PMPRO_DIR . '/pages/member_profile_edit.php',
		);

		// Filter $default_templates so that Add Ons can add their own templates.
		$default_templates = apply_filters( 'pmpro_default_page_templates', $default_templates );

		// Loop through each template. For each, if a custom page template is being loaded, store:
		// - The custom path being loaded.
		// - The version of the default template.
		// - The version of the custom template.
		$custom_templates = array(); // Array of $template => array( 'default_version' => $default_version, 'loaded_version' => $loaded_version, 'loaded_path' => $loaded_path ).
		foreach ( $default_templates as $template => $path ) {
			// Gather information about the default and loaded templates.
			$default_version = pmpro_get_version_for_page_template_at_path( $path );
			$custom_path = pmpro_get_template_path_to_load( $template );
			$custom_version = pmpro_get_version_for_page_template_at_path( $custom_path );

			// If the $path and $loaded_path are different, a custom template is being loaded.
			if ( $path !== $custom_path ) {
				$custom_templates[ $template ] = 'Default Version: ' . $default_version . ' | Custom Version: ' . $custom_version . ' | Custom Path: ' . $custom_path . ' | Action: ';
				$use_custom_page_template = get_option( 'pmpro_use_custom_page_template_' . $template );
				switch( $use_custom_page_template ) {
					case 'yes':
						$custom_templates[ $template ] .= 'Custom';
						break;
					case 'no':
						$custom_templates[ $template ] .= 'Core';
						break;
					default:
						$custom_templates[ $template ] .= 'Fallback';
						break;
				}
				$custom_templates[ $template ] = esc_html( $custom_templates[ $template] );
			}
		}

		return $custom_templates;
	}

	/**
	 * Get the cron job information.
	 *
	 * @since 2.6.2
	 *
	 * @return string The cron job information.
	 */
	public function get_cron_jobs() {
		$crons = _get_cron_array();

		$cron_times = [];

		// These are our crons.
		$expected_crons = array_keys( pmpro_get_crons() );

		// Find any of our crons and when their next run is.
		if ( $crons ) {
			foreach ( $crons as $time => $cron ) {
				$keys    = array_keys( $cron );
				$matches = array_intersect( $expected_crons, $keys );

				foreach ( $matches as $cron_hook ) {
					$cron_times[ $cron_hook ] = date( 'Y-m-d H:i:s', $time );
				}
			}
		}

		$missing_crons = array_diff( $expected_crons, array_keys( $cron_times ) );

		$cron_information = [];

		foreach ( $missing_crons as $cron_hook ) {
			$cron_information[] = $cron_hook . ' (' . __( 'missing', 'paid-memberships-pro' ) . ')';
		}

		// Build the information of what crons are missing and what crons are going to run.
		foreach ( $cron_times as $cron_hook => $next_run ) {
			$cron_information[] = $cron_hook . ' (' . $next_run . ')';
		}

		return implode( " | \n", $cron_information );
	}

	/**
	 * Get the assigned Member pages and their URL's
	 *
	 * @since 2.7.3
	 *
	 * @return string|string[] The member page information
	 */
	public function get_pmpro_pages() {

		global $pmpro_pages;

		$page_information = array();

		if( !empty( $pmpro_pages ) ){

			foreach( $pmpro_pages as $key => $val ){

				$permalink = get_the_permalink( (int)$val );

				if( empty( $permalink ) ){
					$page_information[$key] = 'Not Set'; //Not translating this
				} else {
					$page_information[$key] = $permalink;
				}

			}

		} else {

			return __( 'No Membership Pages Found', 'paid-memberships-pro' );

		}

		return $page_information;

	}

	/**
	 * Get the .htaccess services/getfile.php usage information.
	 *
	 * @since 2.6.4
	 *
	 * @return string The .htaccess services/getfile.php usage information.
	 */
	public function get_getfile_usage() {
		if ( ! defined( 'PMPRO_GETFILE_ENABLED' ) ) {
			return __( 'PMPRO_GETFILE_ENABLED is not set', 'paid-memberships-pro' );
		}

		if ( ! PMPRO_GETFILE_ENABLED ) {
			return __( 'PMPRO_GETFILE_ENABLED is off', 'paid-memberships-pro' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		/**
		 * @var $wp_filesystem WP_Filesystem_Base
		 */
		global $wp_filesystem;

		WP_Filesystem();

		if ( ! $wp_filesystem ) {
			return __( 'Unable to access .htaccess file', 'paid-memberships-pro' );
		}

		if ( ! $wp_filesystem->exists( ABSPATH . '/.htaccess' ) ) {
			return __( 'Off - No .htaccess file', 'paid-memberships-pro' );
		}

		$htaccess_contents = $wp_filesystem->get_contents( ABSPATH . '/.htaccess' );

		if ( false === strpos( $htaccess_contents, '/services/getfile.php' ) ) {
			return __( 'Off', 'paid-memberships-pro' );
		}

		return __( 'On - .htaccess contains services/getfile.php usage', 'paid-memberships-pro' );
	}

	/**
	 * Get the .htaccess cache usage information.
	 *
	 * @since 2.6.4
	 *
	 * @return string The .htaccess cache usage information.
	 */
	public function get_htaccess_cache_usage() {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		/**
		 * @var $wp_filesystem WP_Filesystem_Base
		 */
		global $wp_filesystem;

		WP_Filesystem();

        if ( ! $wp_filesystem || ! is_object($wp_filesystem) || ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->has_errors() ) ) {
			return __( 'Unable to access .htaccess file', 'paid-memberships-pro' );
		}

		if ( ! $wp_filesystem->exists( ABSPATH . '/.htaccess' ) ) {
			return __( 'Off - No .htaccess file', 'paid-memberships-pro' );
		}

		$htaccess_contents = $wp_filesystem->get_contents( ABSPATH . '/.htaccess' );

		if ( false !== strpos( $htaccess_contents, 'ExpiresByType text/html' ) ) {
			return __( 'On - Browser cache enabled for HTML (ExpiresByType text/html), this may interfere with Content Restriction after Login. Remove that line from your .htaccess to resolve this problem.', 'paid-memberships-pro' );
		} elseif ( false !== strpos( $htaccess_contents, 'ExpiresDefault' ) ) {
			return __( 'On - Browser cache enabled for HTML (ExpiresDefault), this may interfere with Content Restriction after Login. Remove that line from your .htaccess to resolve this problem.', 'paid-memberships-pro' );
		}

		return __( 'Off', 'paid-memberships-pro' );
	}

	/**
	 * Get library conflicts.
	 *
	 * @since 2.8
	 *
	 * @return string|string[] The member page information
	 */
	function get_library_conflicts() {
		// Get the current list of library conflicts.
		$library_conflicts = get_option( 'pmpro_library_conflicts' );

		// If there are no library conflicts, return a message.
		if ( empty( $library_conflicts ) ) {
			return __( 'No library conflicts detected.', 'paid-memberships-pro' );
		}

		// Format data to be displayed in site health.
		$return_arr = array();

		// Loop through all libraries that have conflicts.
		foreach ( $library_conflicts as $library_name => $conflicting_plugins ) {
			$conflict_strings = array();
			// Loop through all plugins that have conflicts with this library.
			foreach ( $conflicting_plugins as $conflicting_plugin_path => $conflicting_plugin_data ) {
				$conflict_strings[] = 'v' . $conflicting_plugin_data['version'] . ' (' . $conflicting_plugin_data['timestamp'] . ')' . ' - ' . $conflicting_plugin_path;
			}
			$return_arr[ $library_name ] = implode( ' | ', $conflict_strings );
		}
		return $return_arr;
	}

	function get_add_ons_with_incorrect_folder_name() {
		// Get the current list of Add Ons with the wrong name.
		$incorrect_folder_names = pmpro_get_add_ons_with_incorrect_folder_names();

		// Build error messages for each Add On with the wrong name.
		$errors = array();
		foreach ( $incorrect_folder_names as $installed_name => $addon ) {
			$errors[] = "{$addon['Name']} ( {$addon['plugin']} => {$installed_name} )";
		}

		return empty( $errors ) ? __( 'No add ons with incorrect folder names detected.', 'paid-memberships-pro' ) : implode( " | \n", $errors );
	}

	/**
	 * Get the constants site health information.
	 *
	 * @since 2.6.4
	 *
	 * @return array The constants site health information.
	 */
	public function get_constants() {
		$constants = [
			'PMPRO_CRON_LIMIT'                => __( 'Cron Limit', 'paid-memberships-pro' ),
			'PMPRO_DEFAULT_LEVEL'             => __( 'Default Membership Level', 'paid-memberships-pro' ),
			'PMPRO_USE_SESSIONS'              => __( 'Use Sessions', 'paid-memberships-pro' ),
		];

		$gateway_specific_constants = [
			'authorizenet' => [
				'PMPRO_AUTHNET_SILENT_POST_DEBUG' => __( 'Authorize.net Silent Post Debug Mode', 'paid-memberships-pro' ),
			],
			'braintree' => [
				'PMPRO_BRAINTREE_WEBHOOK_DEBUG'   => __( 'Braintree Webhook Debug Mode', 'paid-memberships-pro' ),
			],
			'paypal' => [
				'PMPRO_IPN_DEBUG'                 => __( 'PayPal IPN Debug Mode', 'paid-memberships-pro' ),
			],
			'paypalexpress' => [
				'PMPRO_IPN_DEBUG'                 => __( 'PayPal IPN Debug Mode', 'paid-memberships-pro' ),
			],
			'paypalstandard' => [
				'PMPRO_IPN_DEBUG'                 => __( 'PayPal IPN Debug Mode', 'paid-memberships-pro' ),
			],
			'stripe' => [
				'PMPRO_STRIPE_WEBHOOK_DEBUG'      => __( 'Stripe Webhook Debug Mode', 'paid-memberships-pro' ),
			],
			'twocheckout' => [
				'PMPRO_INS_DEBUG'                 => __( '2Checkout INS Debug Mode', 'paid-memberships-pro' ),
			],
		];

		$gateway = get_option( 'pmpro_gateway' );

		if ( $gateway && isset( $gateway_specific_constants[ $gateway ] ) ) {
			$constants = array_merge( $constants, $gateway_specific_constants[ $gateway ] );
		}

		/**
		 * Allow filtering the supported Site Health constants by other add ons.
		 *
		 * @since 2.6.4
		 *
		 * @param array  $constants The list of constants to show in Site Health.
		 * @param string $gateway   The current payment gateway.
		 */
		$constants = apply_filters( 'pmpro_site_health_constants', $constants, $gateway );

		// Get and format constant information.
		$constants_formatted = [];

		foreach ( $constants as $constant => $label ) {
			// Only get site health info for constants that are set.
			if ( ! defined( $constant ) ) {
				continue;
			}

			$constants_formatted[ 'pmpro-constants-' . $constant ] = [
				'label' => $label . ' (' . $constant . ')',
				'value' => var_export( constant( $constant ), true ),
			];
		}

		return $constants_formatted;
	}

	/**
	 * Get the pause mode state
	 *
	 * @since 2.10
	 *
	 * @return string What state is pause mode in 
	 */
	public function get_pause_mode_state() {

		$pause_mode = pmpro_is_paused();

		if( $pause_mode ) {
			return __( 'Enabled', 'paid-memberships-pro' );
		}

		return __( 'Disabled', 'paid-memberships-pro' );

	}
}
