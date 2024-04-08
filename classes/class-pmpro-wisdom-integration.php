<?php

/**
 * Wisdom Integration class for PMPro.
 *
 * @see PMPro_Wisdom_Tracker
 *
 * @since 2.8
 */
class PMPro_Wisdom_Integration {

	/**
	 * The current object instance.
	 *
	 * @since 2.8
	 *
	 * @var self
	 */
	public static $instance;

	/**
	 * The plugin slug to use with Wisdom.
	 *
	 * @since 2.8
	 *
	 * @var string
	 */
	public $plugin_slug = 'paid-memberships-pro';

	/**
	 * The plugin option to send to Wisdom.
	 *
	 * @since 2.8
	 *
	 * @var string
	 */
	public $plugin_option = 'pmpro_wisdom_opt_out';

	/**
	 * The plugin settings pages to include Wisdom notices on.
	 *
	 * @since 2.8
	 *
	 * @var array
	 */
	public $plugin_pages = [
		'pmpro-membershiplevels' => true,
		'pmpro-discountcodes'    => true,
		'pmpro-pagesettings'     => true,
		'pmpro-paymentsettings'  => true,
		'pmpro-emailsettings'    => true,
		'pmpro-emailtemplates'   => true,
		'pmpro-advancedsettings' => true,
	];

	/**
	 * The Wisdom Tracker object.
	 *
	 * @since 2.8
	 *
	 * @var PMPro_Wisdom_Tracker
	 */
	public $wisdom_tracker;

	/**
	 * Set up and return the class instance.
	 *
	 * @since 2.8
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Prevent new public instances by having a private constructor.
	 */
	private function __construct() {
		// Nothing to do here.
	}

	/**
	 * Set up the Wisdom tracker.
	 *
	 * @since 2.8
	 */
	public function setup_wisdom() {
		require_once PMPRO_DIR . '/classes/class-pmpro-wisdom-tracker.php';

		// Sync the options together.
		add_action( 'add_option_wisdom_allow_tracking', [ $this, 'sync_wisdom_setting_to_plugin' ], 10, 2 );
		add_action( 'update_option_wisdom_allow_tracking', [ $this, 'sync_wisdom_setting_to_plugin' ], 10, 2 );
		add_action( 'add_option_' . $this->plugin_option, [ $this, 'sync_plugin_setting_to_wisdom' ], 10, 2 );
		add_action( 'update_option_' . $this->plugin_option, [ $this, 'sync_plugin_setting_to_wisdom' ], 10, 2 );

		// Additional Wisdom customizations.
		add_filter( 'wisdom_is_local_' . $this->plugin_slug, [ $this, 'bypass_local_tracking' ] );
		add_filter( 'wisdom_notice_text_' . $this->plugin_slug, [ $this, 'override_notice' ] );
		add_filter( 'wisdom_tracker_data_' . $this->plugin_slug, [ $this, 'add_stats' ] );

		// Set up the tracker object.
		$this->wisdom_tracker = new PMPro_Wisdom_Tracker(
			PMPRO_BASE_FILE,
			$this->plugin_slug,
			'https://asimov.paidmembershipspro.com',
			[
				$this->plugin_option,
			],
			true,
			false,
			1
		);

		// Adjust tracking hooks.
		$this->remove_wisdom_notices_from_non_plugin_screens();
	}

	/**
	 * When the Wisdom setting for tracking is changed, sync the plugin setting to match.
	 *
	 * @since 2.8
	 *
	 * @param array|null $old_value The old value of the option.
	 * @param array      $value     The new value of the option.
	 */
	public function sync_wisdom_setting_to_plugin( $old_value, $value ) {
		$opt_out = ! empty( $value[ $this->plugin_slug ] ) ? 0 : 1;		
		update_option( $this->plugin_option, $opt_out );
	}

	/**
	 * When the plugin setting for tracking is changed, sync the Wisdom setting to match.
	 *
	 * @since 2.8
	 *
	 * @param array|null $old_value The old value of the option.
	 * @param array      $value     The new value of the option.
	 */
	public function sync_plugin_setting_to_wisdom( $old_value, $value ) {
		// Only handle opt in when needed.
		if ( ! isset( $value ) ) {
			return;
		}

		// Only update when changing the value.
		if ( isset( $old_value ) && (int) $old_value === (int) $value ) {
			return;
		}

		$opt_out = filter_var( $value, FILTER_VALIDATE_BOOLEAN );

		// Update opt-in.
		$this->wisdom_tracker->set_is_tracking_allowed( ! $opt_out, $this->plugin_slug );
		$this->wisdom_tracker->set_can_collect_email( ! $opt_out, $this->plugin_slug );
	}

	/**
	 * Bypass local tracking for additional local URLs.
	 *
	 * @since 2.8
	 *
	 * @param bool $is_local Whether the site is recognized as a local site.
	 *
	 * @return bool Whether the site is recognized as a local site.
	 */
	public function bypass_local_tracking( $is_local = false ) {
		if ( true === $is_local || ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) ) {
			return true;
		}

		$url = network_site_url( '/' );

		$url       = strtolower( trim( $url ) );
		$url_parts = parse_url( $url );
		$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;
		$port      = ! empty( $url_parts['port'] ) ? $url_parts['port'] : false;

		if ( empty( $host ) ) {
			return $is_local;
		}

		if( 8888 === $port ){
			return true;
		}

		if ( 'localhost' === $host ) {
			return true;
		}

		if ( false !== ip2long( $host ) && ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return true;
		}

		if (
			// localhost
			$this->host_starts_with( $host, 'localhost' ) ||

			// domains starting with
			$this->host_starts_with( $host, 'stage' ) ||
			$this->host_starts_with( $host, 'staging' ) ||

			// subdomains
			$this->host_starts_with( $host, 'dev.' ) ||
			$this->host_starts_with( $host, 'test.' ) ||
			$this->host_starts_with( $host, 'testing.' ) ||

			// local tlds
			$this->host_ends_with( $host, '.test' ) ||
			$this->host_ends_with( $host, '.local' ) ||

			// common testing/staging tlds and third party hosts
			$this->host_ends_with( $host, '.bigscoots-staging.com' ) ||
			$this->host_ends_with( $host, '.closte.com' ) ||
			$this->host_ends_with( $host, '.cloudwaysapp.com' ) ||
			$this->host_ends_with( $host, '.e.wpstage.net' ) ||
			$this->host_ends_with( $host, '.kinsta.cloud' ) ||
			$this->host_ends_with( $host, '.onrocket.site' ) ||
			$this->host_ends_with( $host, '.pantheonsite.io' ) ||
			$this->host_ends_with( $host, '.pressdns.com' ) ||
			$this->host_ends_with( $host, '.runcloud.link' ) ||
			$this->host_ends_with( $host, '.servebolt.com' ) ||
			$this->host_ends_with( $host, '.sg-host.com' ) ||
			$this->host_ends_with( $host, '.stacks.run' ) ||
			$this->host_ends_with( $host, '.wpengine.com' )
		) {
			return true;
		}

		return $is_local;
	}

	/**
	 * PHP8.0 str_starts_with() polyfill.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	protected function host_starts_with( $haystack, $needle ) {
		return 0 === strncmp( $haystack, $needle, \strlen( $needle ) );
	}

	/**
	 * PHP8.0 str_starts_with() polyfill.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	protected function host_ends_with( $haystack, $needle ) {
		if ( '' === $needle || $needle === $haystack ) {
			return true;
		}

		if ( '' === $haystack ) {
			return false;
		}

		$needleLength = \strlen( $needle );

		return $needleLength <= \strlen( $haystack ) && 0 === substr_compare( $haystack, $needle, - $needleLength );
	}

	/**
	 * Override the notice for the Wisdom Tracker opt-in.
	 *
	 * @since 2.8
	 *
	 * @return string
	 */
	public function override_notice() {
		$message = esc_html__( 'Share your usage data to help us improve Paid Memberships Pro. We use this data to analyze how our plugin is meeting your needs and identify new opportunities to help you create a thriving membership business. You can always visit the Advanced Settings and change this preference.', 'paid-memberships-pro' );
		$link = '<a href="https://www.paidmembershipspro.com/privacy-policy/usage-tracking/" target="_blank" rel="nofollow noopener">' . esc_html__( 'Read more about what data we collect.', 'paid-memberships-pro' ) . '</a>';
		return $message . ' ' . $link;
	}

	/**
	 * Remove Wisdom notices from non-plugin screens.
	 *
	 * @since 2.8
	 */
	public function remove_wisdom_notices_from_non_plugin_screens() {
		$settings_page = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		// Check if we are on a settings page using isset() which is faster than in_array().
		if ( isset( $this->plugin_pages[ $settings_page ] ) ) {
			return;
		}

		// Remove the notices from the non-plugin settings pages.
		remove_action( 'admin_notices', [ $this->wisdom_tracker, 'optin_notice' ] );
		remove_action( 'admin_notices', [ $this->wisdom_tracker, 'marketing_notice' ] );
	}

	/**
	 * Add custom stats for the plugin to the data being tracked.
	 *
	 * @since 2.8
	 *
	 * @param array $stats The data to be sent to the Wisdom plugin site.
	 *
	 * @return array The data to be sent to the Wisdom plugin site.
	 */
	public function add_stats( $stats ) {
		global $wpdb;

		// License info.
		$license_check = get_option( 'pmpro_license_check', 'No Value' );

		$license_plan = 'No Value';

		if ( is_array( $license_check ) && isset( $license_check['license'] ) ) {
			$license_plan = $license_check['license'];
		}

		$stats['plugin_options_fields']['pmpro_license_key']  = get_option( 'pmpro_license_key', 'No Value' );
		$stats['plugin_options_fields']['pmpro_license_plan'] = $license_plan;

		// Gateway info.
		$stats['plugin_options_fields'] = array_merge( $stats['plugin_options_fields'], $this->get_gateway_info() );

		// Levels info.
		$levels_info = $this->get_levels_info();
		$stats['plugin_options_fields']['pmpro_level_count']    = $levels_info['pmpro_level_count'];
		$stats['plugin_options_fields']['pmpro_level_setups']   = $levels_info['pmpro_level_setups'];
		$stats['plugin_options_fields']['pmpro_has_free_level'] = $levels_info['pmpro_has_free_level'];
		$stats['plugin_options_fields']['pmpro_has_paid_level'] = $levels_info['pmpro_has_paid_level'];

		// Members info.
		$stats['plugin_options_fields']['pmpro_members_count']           = pmpro_getSignups( 'all time' );
		$stats['plugin_options_fields']['pmpro_members_cancelled_count'] = pmpro_getCancellations( 'all time' );

		// Orders info.
		$stats['plugin_options_fields']['pmpro_orders_count'] = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->pmpro_membership_orders}`" );

		// Features.
		$stats['plugin_options_fields']['pmpro_hide_toolbar']  = get_option( 'pmpro_hide_toolbar', 'No Value' );
		$stats['plugin_options_fields']['pmpro_block_dashboard']  = get_option( 'pmpro_block_dashboard', 'No Value' );
		$stats['plugin_options_fields']['pmpro_filterqueries']  = get_option( 'pmpro_filterqueries', 'No Value' );
		$stats['plugin_options_fields']['pmpro_showexcerpts']   = get_option( 'pmpro_showexcerpts', 'No Value' );
		$stats['plugin_options_fields']['pmpro_spamprotection'] = get_option( 'pmpro_spamprotection', 'No Value' );
		$stats['plugin_options_fields']['pmpro_recaptcha'] = get_option( 'pmpro_recaptcha', 'No Value' );
		$stats['plugin_options_fields']['pmpro_maxnotificationpriority'] = get_option( 'pmpro_maxnotificationpriority', 'No Value' );
		$stats['plugin_options_fields']['pmpro_activity_email_frequency'] = get_option( 'pmpro_activity_email_frequency', 'No Value' );
		$stats['plugin_options_fields']['pmpro_hideads'] = get_option( 'pmpro_hideads', 'No Value' );
		$stats['plugin_options_fields']['pmpro_redirecttosubscription'] = get_option( 'pmpro_redirecttosubscription', 'No Value' );
		$stats['plugin_options_fields']['pmpro_only_filter_pmpro_emails'] = get_option( 'pmpro_only_filter_pmpro_emails', 'No Value' );
		$stats['plugin_options_fields']['pmpro_email_member_notification'] = get_option( 'pmpro_email_member_notification', 'No Value' );
		$stats['plugin_options_fields']['pmpro_use_ssl'] = get_option( 'pmpro_pmpro_use_ssl', 'No Value' );
		$ssl_seal = get_option( 'pmpro_sslseal', '' );
		if ( ! empty( $ssl_seal ) ) {
			$stats['plugin_options_fields']['pmpro_sslseal'] = 'Yes';
		} else {
			$stats['plugin_options_fields']['pmpro_sslseal'] = 'No';
		}
		$stats['plugin_options_fields']['pmpro_nuclear_HTTPS'] = get_option( 'pmpro_nuclear_HTTPS', 'No Value' );

		// Add Ons.
		$addons_info = $this->get_addons_info();

		$stats['plugin_options_fields']['addons_active']           = $addons_info['addons_active'];
		$stats['plugin_options_fields']['addons_inactive']         = $addons_info['addons_inactive'];
		$stats['plugin_options_fields']['addons_update_available'] = $addons_info['addons_update_available'];

		// Flatten the arrays.
		foreach ( $stats['plugin_options_fields'] as $option => $value ) {
			if ( is_object( $value ) || is_array( $value ) ) {
				$value = maybe_serialize( $value );
			}

			$stats['plugin_options_fields'][ $option ] = $value;
		}
		return $stats;
	}

	/**
	 * Get the gateway information to track.
	 *
	 * @since 2.8
	 *
	 * @return array The gateway information to track.
	 */
	public function get_gateway_info() {
		$stats = [];

		// Gateway info.
		$stats['pmpro_gateway']             = get_option( 'pmpro_gateway', 'No Value' );
		$stats['pmpro_gateway_environment'] = get_option( 'pmpro_gateway_environment', 'No Value' );
		$stats['pmpro_currency']            = get_option( 'pmpro_currency', 'No Value' );

		// Get Stripe gateway info for other stats below.
		$stripe_using_legacy_keys       = PMProGateway_stripe::using_legacy_keys();
		$stripe_has_connect_credentials = PMProGateway_stripe::has_connect_credentials( 'live' ) || PMProGateway_stripe::has_connect_credentials( 'sandbox' );

		// Append the Stripe gateway qualifiers.
		if ( 'stripe' === $stats['pmpro_gateway'] ) {
			// Add Legacy Keys text if using Legacy Keys.
			if ( $stripe_using_legacy_keys ) {
				$stats['pmpro_gateway'] .= ' (' . __( 'Legacy Keys', 'paid-memberships-pro' ) . ')';
			}

			// Add Stripe Connect text if using Stripe Connect.
			if ( $stripe_has_connect_credentials ) {
				$stats['pmpro_gateway'] .= ' (' . __( 'Stripe Connect', 'paid-memberships-pro' ) . ')';
			}

			$stats['pmpro_gateway'] = strtolower( $stats['pmpro_gateway'] );
		}

		// Detect any gateway settings.
		$gateway_settings_detected = [
			'authorizenet'   => get_option( 'pmpro_loginname' ),
			'braintree'      => get_option( 'pmpro_braintree_merchantid' ),
			'cybersource'    => get_option( 'pmpro_cybersource_merchantid' ),
			'payflowpro'     => get_option( 'pmpro_payflow_user' ),
			'paypal'         => get_option( 'pmpro_apiusername' ),
			'paypalexpress'  => get_option( 'paypalexpress_skip_confirmation' ),
			'paypalstandard' => get_option( 'gateway_email' ),
			'stripe'         => $stripe_using_legacy_keys || $stripe_has_connect_credentials,
			'stripe_sandbox' => get_option( 'sandbox_stripe_connect_user_id' ),
			'twocheckout'    => get_option( 'twocheckout_accountnumber' ),
		];

		// Remove any gateway settings that are not set or are empty.
		$gateway_settings_detected = array_map( static function( $value ) {
			return false !== $value && '' !== $value;
		}, $gateway_settings_detected );
		$gateway_settings_detected = array_filter( $gateway_settings_detected );

		// Fill in the gateway count/detected info.
		$stats['pmpro_gateways_count']    = count( $gateway_settings_detected );
		$stats['pmpro_gateways_detected'] = implode( ', ', array_keys( $gateway_settings_detected ) );

		return $stats;
	}

	/**
	 * Get the level information for all levels to track.
	 *
	 * @since 2.8
	 *
	 * @return array The level information for all levels to track.
	 */
	public function get_levels_info() {
		global $wpdb;

		$stats = array(
			'pmpro_level_setups'   => array(),
			'pmpro_has_free_level' => 'no',
			'pmpro_has_paid_level' => 'no',
			'pmpro_level_count'    => 0,
		);

		// Get the levels.
		$levels = pmpro_getAllLevels( true );

		// Update the level count.
		$stats['pmpro_level_count'] = count( $levels );

		// Loop through the levels.
		foreach ( $levels as $level_id => $level_data ) {
			// Remove sensitive info.
			unset( $level_data->name );
			unset( $level_data->description );
			unset( $level_data->confirmation );

			// Add Set Expiration Date/Subscription Delay info.
			$level_data->set_expiration_date = get_option( 'pmprosed_' . $level_id , '' );
			$level_data->subscription_delay  = get_option( 'pmpro_subscription_delay_' . $level_id , '' );

			// Add if a category is set.
			$categories = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT category_id
					FROM $wpdb->pmpro_memberships_categories
					WHERE membership_id = %d",
					$level_id
				)
			);
			$level_data->has_categories = ! empty( $categories ) ? 'yes' : 'no';

			// Add level info.
			$stats['pmpro_level_setups'][ $level_id ] = $level_data;

			// Update whether or not we have a free/paid level yet.
			if ( pmpro_isLevelFree( $level_data ) ) {
				$stats['pmpro_has_free_level'] = 'yes';
			} else {
				$stats['pmpro_has_paid_level'] = 'yes';
			}
		}
		return $stats;
	}

	/**
	 * Get the list of Add Ons categorized by active, inactive, and update available.
	 *
	 * @since 2.8
	 *
	 * @return array The list of Add Ons categorized by active, inactive, and update available.
	 */
	public function get_addons_info() {
		// This file only is usually only required when is_admin().
		require_once( PMPRO_DIR . '/includes/addons.php' );
		
		// Build the list of Add Ons data to track.
		$addons      = pmpro_getAddons();
		$plugin_info = get_site_transient( 'update_plugins' );

		// Split Add Ons into groups for filtering
		$addons_active           = [];
		$addons_inactive         = [];
		$addons_update_available = [];

		// Build array of Visible, Hidden, Active, Inactive, Installed, and Not Installed Add Ons.
		foreach ( $addons as $addon ) {			
			$plugin_file     = $addon['Slug'] . '/' . $addon['Slug'] . '.php';
			$plugin_file_abs = WP_PLUGIN_DIR . '/' . $plugin_file;
			
			if ( file_exists( $plugin_file_abs ) ) {
				$plugin_data = get_plugin_data( $plugin_file_abs );
			} else {
				// Plugin is not on the site.
				continue;
			}

			// Build Active and Inactive arrays - exclude hidden Add Ons that are not installed.
			if ( is_plugin_active( $plugin_file ) ) {
				$addons_active[ $addon['Slug'] ] = $plugin_data['Version'];
			} else {
				$addons_inactive[ $addon['Slug'] ] = $plugin_data['Version'];
			}

			// Build array of Add Ons that have an update available.
			if ( isset( $plugin_info->response[ $plugin_file ] ) ) {
				$addons_update_available[ $addon['Slug'] ] = $plugin_data['Version'];
			}
		}

		return [
			'addons_active'           => $addons_active,
			'addons_inactive'         => $addons_inactive,
			'addons_update_available' => $addons_update_available,
		];
	}

}
