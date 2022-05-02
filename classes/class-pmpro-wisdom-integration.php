<?php

/**
 * Wisdom Integration class for PMPro.
 *
 * @see PMPro_Wisdom_Tracker
 *
 * @since TBD
 */
class PMPro_Wisdom_Integration {

	/**
	 * The current object instance.
	 *
	 * @since TBD
	 *
	 * @var self
	 */
	public static $instance;

	/**
	 * The plugin slug to use with Wisdom.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $plugin_slug = 'paid-memberships-pro';

	/**
	 * The plugin option to send to Wisdom.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $plugin_option = 'pmpro_wisdom_opt_out';

	/**
	 * The plugin settings pages to include Wisdom notices on.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @var PMPro_Wisdom_Tracker
	 */
	public $wisdom_tracker;

	/**
	 * Set up and return the class instance.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param array|null $old_value The old value of the option.
	 * @param array      $value     The new value of the option.
	 */
	public function sync_wisdom_setting_to_plugin( $old_value, $value ) {
		$opt_out = ! empty( $value[ $this->plugin_slug ] ) ? 0 : 1;		
		pmpro_setOption( $this->plugin_option, $opt_out );
	}

	/**
	 * When the plugin setting for tracking is changed, sync the Wisdom setting to match.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param bool $is_local Whether the site is recognized as a local site.
	 *
	 * @return bool Whether the site is recognized as a local site.
	 */
	public function bypass_local_tracking( $is_local = false ) {
		if ( true === $is_local || 'production' !== wp_get_environment_type() ) {
			return $is_local;
		}

		$url = network_site_url( '/' );

		$url       = strtolower( trim( $url ) );
		$url_parts = parse_url( $url );
		$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

		if ( empty( $host ) ) {
			return $is_local;
		}

		if ( 'localhost' === $host ) {
			return true;
		}

		if ( false !== ip2long( $host ) && ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return true;
		}

		$tlds_to_check = [
			'.local',
			'.test',
		];

		foreach ( $tlds_to_check as $tld ) {
			$minus_tld = strlen( $host ) - strlen( $tld );

			if ( $minus_tld === strpos( $host, $tld ) ) {
				return true;
			}
		}

		return $is_local;
	}

	/**
	 * Override the notice for the Wisdom Tracker opt-in.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function override_notice() {
		return __( 'Share your usage data to help us improve Paid Memberships Pro. We use this data to analyze how our plugin is meeting your needs and identify new opportunities to help you create a thriving membership business. You can always visit the Advanced Settings and change this preference. <a href="https://www.paidmembershipspro.com/privacy-policy/usage-tracking/">Read more about what data we collect</a>.', 'paid-memberships-pro' );
	}

	/**
	 * Remove Wisdom notices from non-plugin screens.
	 *
	 * @since TBD
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
	 * @since TBD
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
		$stats['plugin_options_fields'] = array_merge( $stats['plugin_options_fields'], $this->get_levels_info() );

		// Members info.
		$stats['plugin_options_fields']['pmpro_members_count']           = pmpro_getSignups( 'all time' );
		$stats['plugin_options_fields']['pmpro_members_cancelled_count'] = pmpro_getCancellations( 'all time' );

		// Orders info.
		$stats['plugin_options_fields']['pmpro_orders_count'] = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->pmpro_membership_orders}`" );

		// Features.
		$stats['plugin_options_fields']['pmpro_filterqueries']  = get_option( 'pmpro_filterqueries', 'No Value' );
		$stats['plugin_options_fields']['pmpro_showexcerpts']   = get_option( 'pmpro_showexcerpts', 'No Value' );
		$stats['plugin_options_fields']['pmpro_spamprotection'] = get_option( 'pmpro_spamprotection', 'No Value' );

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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return array The level information for all levels to track.
	 */
	public function get_levels_info() {
		$stats = [];

		$levels = pmpro_getAllLevels( true );

		$stats['pmpro_levels_count'] = count( $levels );

		$range_groups = [
			'0'  => [
				'range' => [ 0, 0 ],
				'count' => 0,
			],
			'0.01_to_10'  => [
				'range' => [ 0.01, 10 ],
				'count' => 0,
			],
			'10.01_to_25' => [
				'range' => [ 10.01, 25 ],
				'count' => 0,
			],
		];

		$billing_amount_prices = wp_list_pluck( $levels, 'billing_amount' );
		$billing_amount_prices = array_unique( $billing_amount_prices );

		foreach ( $billing_amount_prices as $billing_amount_price ) {
			foreach ( $range_groups as $key => $group ) {
				// Zero price range handling.
				if ( 0 === $group['range'][0] && 0 === $group['range'][1] && 0 === $billing_amount_price ) {
					$range_groups[ $key ]['count'] ++;

					break;
				}

				// Check if price is within the range group constraints.
				if ( $group['range'][0] <= $billing_amount_price && $billing_amount_price <= $group['range'][1] ) {
					$range_groups[ $key ]['count'] ++;

					break;
				}
			}
		}

		foreach ( $range_groups as $key => $group ) {
			$stats['pmpro_levels_price_ranges_' . $key ] = $group['count'];
		}

		return $stats;
	}

	/**
	 * Get the list of Add Ons categorized by active, inactive, and update available.
	 *
	 * @since TBD
	 *
	 * @return array The list of Add Ons categorized by active, inactive, and update available.
	 */
	public function get_addons_info() {
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
