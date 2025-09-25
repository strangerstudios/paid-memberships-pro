<?php
// This is a copy of the Gocodebox_Banner_Notifier class with the prefix replaced with PMPro_ to avoid conflicts.
// https://github.com/gocodebox/banner-notifications

/**
 * Example usage inside your plugin bootstrap:
 *
 * $notifier = new PMPro_Banner_Notifier( array(
 *     'prefix'            => 'myplugin',               // will hook wp_ajax_myplugin_notifications, etc.
 *     'version'           => '1.0.0',                  // used for transient key separation
 *     'notifications_url' => 'https://example.com/notifications.json',
 * ) );
 */
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'PMPro_Banner_Notifier' ) ) {
	return;
}

class PMPro_Banner_Notifier {

	/** @var string slug used in hooks, filters, option / meta keys, etc. */
	private $prefix;

	/** @var string plugin / module version (needed for transient-key separation) */
	private $version;

	/** @var string remote JSON feed for notifications */
	private $notifications_url;

	/**
	 * Constructor.
	 *
	 * @param array $args {
	 *     Optional. Either a string (used as the prefix) or an associative array.
	 *
	 *     @type string $prefix Unique slug for hooks, options, transients, etc.
	 *     @type string $notifications_url Feed URL.
	 * }
	 *
	 * @throws Exception
	 */
	public function __construct( $args = array() ) {

		// Accept just a string prefix for convenience.
		if ( is_string( $args ) ) {
			$args = array( 'prefix' => $args );
		}

		$defaults = array(
			'prefix'            => '',
			'notifications_url' => '',
			'version'           => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( ! $args['prefix'] || ! $args['notifications_url'] || ! $args['version'] ) {
			throw new Exception( 'Missing required arguments: prefix, version and/or notifications_url.' );
		}

		$this->prefix            = sanitize_key( $args['prefix'] );
		$this->version           = sanitize_title( $args['version'] );
		$this->notifications_url = sanitize_url( $args['notifications_url'] );

		add_action( "wp_ajax_{$this->prefix}_notifications", array( $this, 'notifications' ) );

		add_action( "wp_ajax_{$this->prefix}_hide_notice", array( $this, 'hide_notice' ) );

		// Add filters for standard checks.
		add_filter( "{$this->prefix}_notification_test_plugins_active", array( $this, 'notification_test_plugins_active' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_check_plugin_version", array( $this, 'notification_test_check_plugin_version' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_license", array( $this, 'notification_test_pmpro_license' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_num_members", array( $this, 'notification_test_pmpro_num_members' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_num_levels", array( $this, 'notification_test_pmpro_num_levels' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_num_discount_codes", array( $this, 'notification_test_pmpro_num_discount_codes' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_revenue", array( $this, 'notification_test_pmpro_revenue' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_num_orders", array( $this, 'notification_test_pmpro_num_orders' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_pmpro_setting", array( $this, 'notification_test_pmpro_setting' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_llms_setting", array( $this, 'notification_test_llms_setting' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_llms_revenue", array( $this, 'notification_test_llms_revenue' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_llms_num_orders", array( $this, 'notification_test_llms_num_orders' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_site_url_match", array( $this, 'notification_test_site_url_match' ), 10, 2 );
		add_filter( "{$this->prefix}_notification_test_check_option", array( $this, 'notification_test_check_option' ), 10, 2 );
	}

	/**
	 * This code calls the server at $this->notifications_url
	 * to see if there are any notifications to display to the user.
	 * Runs on the wp_ajax_{$this->prefix}_notifications hook.
	 * Note we exit instead of returning because this is loaded via AJAX.
	 */
	function notifications() {
		if ( ! current_user_can( 'manage_options' ) ) {
			exit;
		}

		$notification = $this->get_next_notification();

		if ( empty( $notification ) ) {
			exit;
		}

		$paused = $this->notifications_pause();

		if ( $paused && empty( $_REQUEST[ "{$this->prefix}_notification" ] ) && $notification->priority !== 1 ) {
			exit;
		}

		?>
			<div class="<?php echo esc_attr( $this->prefix ); ?>_notification <?php echo esc_attr( $this->prefix ); ?>_notification-<?php echo esc_attr( $notification->type ); ?>" id="<?php echo esc_attr( $notification->id ); ?>">
				<?php if ( ( isset( $notification->dismissable ) && $notification->dismissable ) || ( isset( $notification->dismissible ) && $notification->dismissible ) ) { ?>
					<button type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( $this->prefix . '_notification_dismiss_' . $notification->id ) ); ?>" class="<?php echo esc_html( $this->prefix ); ?>-notice-button notice-dismiss" value="<?php echo esc_attr( $notification->id ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'gocodebox-banner-notifications' ); ?></span></button>
				<?php } ?>
			<div class="<?php echo esc_attr( $this->prefix ); ?>_notification-icon"><span class="dashicons dashicons-<?php echo esc_attr( $notification->dashicon ); ?>"></span></div>
			<div class="<?php echo esc_attr( $this->prefix ); ?>_notification-content">
				<h3><?php echo esc_html( $notification->title ); ?></h3>
				<?php
				$allowed_html = array(
					'a'      => array(
						'class'  => array(),
						'href'   => array(),
						'target' => array(),
						'title'  => array(),
					),
					'p'      => array(
						'class' => array(),
					),
					'b'      => array(
						'class' => array(),
					),
					'em'     => array(
						'class' => array(),
					),
					'br'     => array(),
					'strike' => array(),
					'strong' => array(),
				);
				echo wp_kses( $notification->content, $allowed_html );
				?>
			</div> <!-- end <?php echo esc_html( $this->prefix ); ?>_notification-content -->
			</div> <!-- end <?php echo esc_html( $this->prefix ); ?>_notification -->
			<?php

			exit;
	}

	/**
	 * Get the highest priority applicable notification from the list.
	 */
	function get_next_notification() {
		global $current_user;
		if ( empty( $current_user->ID ) ) {
			return false;
		}

		// If debugging, clear the transient and get a specific notification.
		if ( ! empty( $_REQUEST[ "{$this->prefix}_notification" ] ) ) {
			delete_transient( "{$this->prefix}_notifications_{$this->version}" );
			$notifications = $this->get_all_notifications();

			if ( ! empty( $notifications ) ) {
				foreach ( $notifications as $notification ) {
					if ( $notification->id == $_REQUEST[ "{$this->prefix}_notification" ] ) {
						return $notification;
					}
				}

				return false;
			} else {
				return false;
			}
		}

		// Get all applicable notifications.
		$notifications = $this->get_all_notifications();
		if ( empty( $notifications ) ) {
			return false;
		}

		// Filter out archived notifications.
		$filtered_notifications = array();
		$archived_notifications = get_user_meta( $current_user->ID, "{$this->prefix}_archived_notifications", true );
		foreach ( $notifications as $notification ) {
			if ( ( is_array( $archived_notifications ) && array_key_exists( $notification->id, $archived_notifications ) ) ) {
				continue;
			}

			$filtered_notifications[] = $notification;
		}

		// Return the first one.
		if ( ! empty( $filtered_notifications ) ) {
			$next_notification = $filtered_notifications[0];
		} else {
			$next_notification = false;
		}

		return $next_notification;
	}

	/**
	 * Get notifications from the notification server.
	 */
	function get_all_notifications() {
		$notifications = get_transient( "{$this->prefix}_notifications_{$this->version}" );

		if ( empty( $notifications ) ) {
			// Set to NULL in case the below times out or fails, this way we only check once a day.
			set_transient( "{$this->prefix}_notifications_{$this->version}", 'NULL', 86400 );

			// We use the filter to hit our testing servers.
			$notification_url = apply_filters( "{$this->prefix}_notifications_url", esc_url( $this->notifications_url ) );

			// Get notifications.
			$remote_notifications = wp_remote_get( $notification_url );
			$notifications        = json_decode( wp_remote_retrieve_body( $remote_notifications ) );

			// Update transient if we got something.
			if ( ! empty( $notifications ) ) {
				set_transient( "{$this->prefix}_notifications_{$this->version}", $notifications, 86400 );
			}
		}

		if ( ! is_array( $notifications ) ) {
			$notifications = array();
		}

		// Filter notifications by start/end date.
		$active_notifications = array();
		foreach ( $notifications as $notification ) {
			$active_notifications[] = $notification;
		}

		// Filter out notifications based on show/hide rules.
		$applicable_notifications = array();
		foreach ( $active_notifications as $notification ) {
			if ( $this->is_notification_applicable( $notification ) ) {
				$applicable_notifications[] = $notification;
			}
		}

		// Sort by priority.
		$applicable_notifications = wp_list_sort( $applicable_notifications, 'priority' );

		return $applicable_notifications;
	}

	/**
	 * Check rules for a notification.
	 *
	 * @param object $notification The notification object.
	 * @returns bool true if notification should be shown, false if not.
	 */
	function is_notification_applicable( $notification ) {
		// If one is specified by URL parameter, it's allowed.
		if ( ! empty( $_REQUEST[ "{$this->prefix}_notification" ] ) && $notification->id == intval( $_REQUEST[ "{$this->prefix}_notification" ] ) ) {
			return true;
		}

		// Hide if today's date is before notification start date.
		// TODO: Potentially switch as current_time( 'timestamp' ) is deprecated.
		if ( date( 'Y-m-d', current_time( 'timestamp' ) ) < $notification->starts ) {
			return false;
		}

		// Hide if today's date is after end date.
		// TODO: Potentially as current_time( 'timestamp' ) is deprecated.
		if ( date( 'Y-m-d', current_time( 'timestamp' ) ) > $notification->ends ) {
			return false;
		}

		// Check priority, e.g. if only security notifications should be shown.
		if ( $notification->priority > $this->get_max_notification_priority() ) {
			return false;
		}

		if ( ! $this->should_show_notification( $notification ) ) {
			return false;
		}

		if ( $this->should_hide_notification( $notification ) ) {
			return false;
		}

		// If we get here, show it.
		return true;
	}

	/**
	 * Check a notification to see if we should show it
	 * based on the rules set.
	 * Shows if ALL rules are true. (AND)
	 *
	 * @param object $notification The notification object.
	 */
	function should_show_notification( $notification ) {
		// default to showing.
		$show = true;

		if ( ! empty( $notification->show_if ) ) {
			foreach ( $notification->show_if as $test => $data ) {
				$test_filter = $this->prefix . '_notification_test_' . $test;
				$show        = apply_filters( $test_filter, false, $data );
				if ( ! $show ) {
					// one test failed, let's not show
					break;
				}
			}
		}

		return $show;
	}

	/**
	 * Check a notification to see if we should hide it
	 * based on the rules set.
	 * Hides if ANY rule is true. (OR)
	 *
	 * @param object $notification The notification object.
	 */
	function should_hide_notification( $notification ) {
		// default to NOT hiding.
		$hide = false;

		if ( ! empty( $notification->hide_if ) ) {
			foreach ( $notification->hide_if as $test => $data ) {
				$test_filter = $this->prefix . '_notification_test_' . $test;
				$hide        = apply_filters( $test_filter, false, $data );
				if ( $hide ) {
					// one test passes, let's hide
					break;
				}
			}
		}

		return $hide;
	}

	/**
	 * Plugins active test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $plugins An array of plugin paths and filenames to check.
	 * @returns bool true if ALL of the plugins are active (AND), false otherwise.
	 */
	function notification_test_plugins_active( $value, $plugins ) {
		if ( ! is_array( $plugins ) ) {
			$plugins = array( $plugins );
		}

		foreach ( $plugins as $plugin ) {
			if ( ! is_plugin_active( $plugin ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Plugin version test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from notification with plugin_file, comparison, and version to check.
	 * @returns bool true if plugin is active and version comparison is true, false otherwise.
	 */
	function notification_test_check_plugin_version( $value, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		if ( ! isset( $data[0] ) || ! isset( $data[1] ) || ! isset( $data[2] ) ) {
			return false;
		}

		// TODO: Use get_plugin_data()?
		$plugin_file = $data[0];
		$comparison  = $data[1];
		$version     = $data[2];

		// Make sure data to check is in a good format.
		if ( empty( $plugin_file ) || empty( $comparison ) || ! isset( $version ) ) {
			return false;
		}

		// Get plugin data.
		$full_plugin_file_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		if ( is_file( $full_plugin_file_path ) ) {
			$plugin_data = get_plugin_data( $full_plugin_file_path, false, true );
		}

		// Return false if there is no plugin data.
		if ( empty( $plugin_data ) || empty( $plugin_data['Version'] ) ) {
			return false;
		}

		// Check version.
		if ( version_compare( $plugin_data['Version'], $version, $comparison ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * PMPro license type test.
	 *
	 * @param bool   $value The current test value.
	 * @param string $license PMPro license type to check for.
	 * @returns bool true if the PMPro license type matches.
	 */
	function notification_test_pmpro_license( $value, $license_type ) {
		if ( ! function_exists( 'pmpro_license_isValid' ) ) {
			return false;
		}

		if ( empty( $license_type ) ) {
			// If no license type, check they DON'T have a valid license key
			$valid = ! pmpro_license_isValid();
		} else {
			// Check if they have a valid key of the type specified
			$valid = pmpro_license_isValid( null, $license_type );
		}

		return $valid;
	}

	/**
	 * PMPro number of members test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] number of members.
	 * @returns bool true if there are as many members as specified.
	 */
	function notification_test_pmpro_num_members( $value, $data ) {
		global $wpdb;
		static $num_members;

		if ( ! function_exists( 'pmpro_int_compare' ) ) {
			return false;
		}

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $num_members ) ) {
			$sqlQuery    = "SELECT COUNT(*) FROM ( SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE status = 'active' GROUP BY user_id ) t1";
			$num_members = $wpdb->get_var( $sqlQuery );
		}

		return pmpro_int_compare( $num_members, $data[1], $data[0] );
	}

	/**
	 * PMPro number of levels test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] number of levels.
	 * @returns bool true if there are as many levels as specified.
	 */
	function notification_test_pmpro_num_levels( $value, $data ) {
		global $wpdb;
		static $num_levels;

		if ( ! function_exists( 'pmpro_int_compare' ) ) {
			return false;
		}

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $num_levels ) ) {
			$sqlQuery   = "SELECT COUNT(*) FROM $wpdb->pmpro_membership_levels";
			$num_levels = $wpdb->get_var( $sqlQuery );
		}

		return pmpro_int_compare( $num_levels, $data[1], $data[0] );
	}

	/**
	 * PMPro number of discount codes test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] number of discount codes.
	 * @returns bool true if there are as many discount codes as specified.
	 */
	function notification_test_pmpro_num_discount_codes( $value, $data ) {
		global $wpdb;
		static $num_codes;

		if ( ! function_exists( 'pmpro_int_compare' ) ) {
			return false;
		}

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $num_codes ) ) {
			$sqlQuery  = "SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes";
			$num_codes = $wpdb->get_var( $sqlQuery );
		}

		return pmpro_int_compare( $num_codes, $data[1], $data[0] );
	}

	/**
	 * PMPro revenue test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] revenue.
	 * Optionally $data can contain a third parameter to also check the currency code.
	 * @returns bool true if there is as much revenue as specified.
	 */
	function notification_test_pmpro_revenue( $value, $data ) {
		global $wpdb;
		static $revenue;

		if ( ! function_exists( 'pmpro_int_compare' ) ) {
			return false;
		}

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $revenue ) ) {
			$sqlQuery = "SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE gateway_environment = 'live' AND status NOT IN('refunded', 'review', 'token', 'error')";
			$revenue  = $wpdb->get_var( $sqlQuery );
		}

		return pmpro_int_compare( $revenue, $data[1], $data[0] );
	}

	/**
	 * LifterLMS revenue test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] revenue.
	 * Optionally $data can contain a third parameter to also check the currency code.
	 * @returns bool true if there is as much revenue as specified.
	 */
	function notification_test_llms_revenue( $value, $data ) {
		global $wpdb;
		static $revenue;

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $revenue ) ) {
			$sql_query = "SELECT SUM(sales.meta_value - COALESCE(refunds.meta_value, 0)) AS amount
						FROM {$wpdb->posts} AS txns
						JOIN {$wpdb->postmeta} AS sales ON sales.post_id = txns.ID AND sales.meta_key = '_llms_amount'
						LEFT JOIN {$wpdb->postmeta} AS refunds ON refunds.post_id = txns.ID AND refunds.meta_key = '_llms_refund_amount'
						WHERE
						        ( txns.post_status = 'llms-txn-succeeded' OR txns.post_status = 'llms-txn-refunded' )
						    AND txns.post_type = 'llms_transaction'
						;";
			$revenue   = $wpdb->get_var( $sql_query );
		}

		return $this->int_compare( $revenue, $data[1], $data[0] );
	}

	/**
	 * LifterLMS number of orders test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] number of orders.
	 * @returns bool true if there are as many orders as specified.
	 */
	function notification_test_llms_num_orders( $value, $data ) {
		global $wpdb;
		static $num_orders;

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $num_orders ) ) {
			$sql_query  = "SELECT COUNT(*)
							FROM {$wpdb->posts} AS orders
							WHERE post_status IN ('llms-active', 'llms-completed', 'llms-on-hold', 'llms-pending=cancel', 'llms-cancelled', 'llms-expired')
							  AND post_type = 'llms_order'";
			$num_orders = $wpdb->get_var( $sql_query );
		}

		return $this->int_compare( $num_orders, $data[1], $data[0] );
	}

	/**
	 * PMPro number of orders test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] comparison operator and [1] number of orders.
	 * @returns bool true if there are as many orders as specified.
	 */
	function notification_test_pmpro_num_orders( $value, $data ) {
		global $wpdb;
		static $num_orders;

		if ( ! function_exists( 'pmpro_int_compare' ) ) {
			return false;
		}

		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		if ( ! isset( $num_orders ) ) {
			$sqlQuery   = "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE gateway_environment = 'live' AND status NOT IN('refunded', 'review', 'token', 'error')";
			$num_orders = $wpdb->get_var( $sqlQuery );
		}

		return pmpro_int_compare( $num_orders, $data[1], $data[0] );
	}

	/**
	 * LifterLMS setting test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] setting name to check [1] value to check for.
	 * @returns bool true if an option if found with the specified name and value.
	 */
	function notification_test_llms_setting( $value, $data ) {
		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		// remove the pmpro_ prefix if given
		if ( strpos( $data[0], 'lifterlms_' ) === 0 ) {
			$data[0] = substr( $data[0], 6, strlen( $data[0] ) - 6 );
		}

		$option_value = get_option( 'lifterlms_' . $data[0] );
		if ( isset( $option_value ) && $option_value == $data[1] ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * PMPro setting test.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from the notification with [0] setting name to check [1] value to check for.
	 * @returns bool true if an option if found with the specified name and value.
	 */
	function notification_test_pmpro_setting( $value, $data ) {
		if ( ! is_array( $data ) || ! isset( $data[0] ) || ! isset( $data[1] ) ) {
			return false;
		}

		// remove the pmpro_ prefix if given
		if ( strpos( $data[0], 'pmpro_' ) === 0 ) {
			$data[0] = substr( $data[0], 6, strlen( $data[0] ) - 6 );
		}

		$option_value = get_option( 'pmpro_' . $data[0] );
		if ( isset( $option_value ) && $option_value == $data[1] ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Site URL test.
	 *
	 * @param bool   $value The current test value.
	 * @param string $string String or array of strings to look for in the site URL
	 * @returns bool true if the string shows up in the site URL
	 */
	function notification_test_site_url_match( $value, $string ) {
		if ( ! empty( $string ) ) {
			$strings_to_check = (array) $string;
			foreach ( $strings_to_check as $check ) {
				if ( strpos( get_bloginfo( 'url' ), $check ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check against the WordPress options table.
	 *
	 * @param bool  $value The current test value.
	 * @param array $data Array from notification with plugin_file, comparison, and version to check. $data[0] is the option name, $data[1] is the comparison operator, and $data[2] is the value to compare against.
	 * @returns bool true if plugin is active and version comparison is true, false otherwise.
	 */
	function notification_test_check_option( $value, $data ) {
		// Ensure data is a valid array with at least three elements.
		if ( ! is_array( $data ) || count( $data ) < 3 ) {
			return false;
		}

		// Assign the option value, check type, and check value to variables for readability.
		$option_to_check = $data[0];
		$check_type      = $data[1];
		$check_value     = $data[2];

		// Get the option value.
		if ( strpos( $option_to_check, ':' ) === false ) {
			// This is the straightforward case where there are no sub-options to check.
			$option_value = get_option( $option_to_check );
		} else {
			// This is the case where we need to dig deeper into the array or object.
			while ( ! empty( $option_to_check ) ) {
				// Split the option_to_check into the top level option names and sub-options.
				list( $current_option_to_check, $option_keys_to_check ) = explode( ':', $option_to_check, 2 );

				// Get the option_value for this layer of the array or object.
				if ( ! isset( $option_value ) ) {
					// We have not yet retrieved the top level option value. Do it now.
					$option_value = get_option( $current_option_to_check );
				} elseif ( is_array( $option_value ) && isset( $option_value[ $current_option_to_check ] ) ) {
					// We have the sub_option we want to check.
					$option_value = $option_value[ $current_option_to_check ];
				} elseif ( is_object( $option_value ) && isset( $option_value->$current_option_to_check ) ) {
					// We have the sub_option we want to check.
					$option_value = $option_value->$current_option_to_check;
				} else {
					// If the sub_option doesn't exist, set the option_value to null and break out of the loop.
					$option_value = null;
					break;
				}

				// Update the option_to_check to the sub option or option_key.
				$option_to_check = $option_keys_to_check;
			}
		}

		return $this->notification_check_option_helper( $option_value, $check_type, $check_value );
	}

	/**
	 * Helper function to check if a value is greater than, less than, greater than or equal to, or less than or equal to another value.
	 *
	 * @since 1.0.1
	 *
	 * @param mixed  $option_value The option value to compare.
	 * @param string $check_type The comparison operator.
	 * @param mixed  $check_value The value to compare against.
	 * @return bool True if the comparison is true, false otherwise.
	 */
	function notification_check_option_helper( $option_value, $check_type, $check_value ) {

		// If the option value is an array, check each element individually and return true if any of them match.
		if ( is_array( $option_value ) || is_object( $option_value ) ) {
			foreach ( $option_value as $value ) {
				if ( $this->notification_check_option_helper( $value, $check_type, $check_value ) ) {
					return true;
				}
			}

			return false;
		}

		// We have a single value to compare. Let's do it.
		switch ( $check_type ) {
			case '=':
			case '==':
				return $option_value == $check_value;
			case '!=':
				return $option_value != $check_value;
			case '>':
			case '<':
			case '>=':
			case '<=':
				return pmpro_int_compare( $option_value, $check_value, $check_type );
			case 'contains':
				// Only proceed if $option_value is a string
				return is_string( $option_value ) && strpos( $option_value, $check_value ) !== false;
			case 'notcontains':
				// If $option_value is not a string and it doesn't contain $check_value, return true.
				return ! ( is_string( $option_value ) && strpos( $option_value, $check_value ) !== false );
			case 'empty':
				return empty( $option_value );
			case 'notempty':
				return ! empty( $option_value );
			default:
				return false;
		}
	}

	/**
	 * Get the max notification priority allowed on this site.
	 * Priority is a value from 1 to 5, or 0.
	 * 0: No notifications at all.
	 * 1: Security notifications.
	 * 2: Core plugin updates.
	 * 3: Updates to plugins already installed.
	 * 4: Suggestions based on existing plugins and settings.
	 * 5: Informative.
	 */
	function get_max_notification_priority() {
		static $max_priority = null;

		if ( ! isset( $max_priority ) ) {
			$max_priority = get_option( "{$this->prefix}_maxnotificationpriority" );

			if ( empty( $max_priority ) ) {
				$max_priority = 5;
			}

			// filter allows for max priority 0 to turn them off entirely.
			$max_priority = apply_filters( "{$this->prefix}_max_notification_priority", $max_priority );
		}

		return $max_priority;
	}


	/**
	 * Have we shown too many notifications recently.
	 * By default we limit to 1 notification per 12 hour period
	 * and 3 notifications per week.
	 */
	function notifications_pause() {
		global $current_user;

		// No user? Pause.
		if ( empty( $current_user ) ) {
			return true;
		}

		$archived_notifications = get_user_meta( $current_user->ID, "{$this->prefix}_archived_notifications", true );
		if ( ! is_array( $archived_notifications ) ) {
			// If the user has not yet archived a notification, assume that this is a new install or that they are a new admin.
			// Either way, we want to delay their first notification.
			// We can do this by creating a "delay" archived notification with an archive day 7 days in the future.
			update_user_meta( $current_user->ID, "{$this->prefix}_archived_notifications", array( 'initial_notification_delay' => date_i18n( 'c', strtotime( '+7 days' ) ) ) );
			return true;
		}
		$archived_notifications = array_values( $archived_notifications );
		$num                    = count( $archived_notifications );
		// TODO: Switch as current_time( 'timestamp' ) is deprecated.
		$now = current_time( 'timestamp' );

		// No archived (dismissed) notifications? Don't pause.
		if ( empty( $archived_notifications ) ) {
			return false;
		}

		// Last notification was dismissed < 12 hours ago. Pause.
		$last_notification_date = $archived_notifications[ $num - 1 ];
		if ( strtotime( $last_notification_date, $now ) > ( $now - 3600 * 12 ) ) {
			return true;
		}

		// If we have < 3 archived notifications. Don't pause.
		if ( $num < 3 ) {
			return false;
		}

		// If we've shown 3 this week already. Pause.
		$third_last_notification_date = $archived_notifications[ $num - 3 ];
		if ( strtotime( $third_last_notification_date, $now ) > ( $now - 3600 * 24 * 7 ) ) {
			return true;
		}

		// If we've gotten here, don't pause.
		return false;
	}

	/**
	 * Move the top notice to the archives if dismissed.
	 */
	function hide_notice() {
		global $current_user;

		if ( empty( $current_user ) ) {
			exit;
		}

		if ( ! isset( $_POST['notification_id'], $_POST['nonce'] ) ) {
			exit;
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], $this->prefix . '_notification_dismiss_' . $_POST['notification_id'] ) ) {
			exit;
		}

		$notification_id = sanitize_text_field( $_POST['notification_id'] );

		$archived_notifications = get_user_meta( $current_user->ID, "{$this->prefix}_archived_notifications", true );

		if ( ! is_array( $archived_notifications ) ) {
			$archived_notifications = array();
		}

		$archived_notifications[ $notification_id ] = date_i18n( 'c' );

		update_user_meta( $current_user->ID, "{$this->prefix}_archived_notifications", $archived_notifications );
		exit;
	}


	/**
	 * Compare two integers using parameters similar to the version_compare function.
	 * This allows us to pass in a comparison character via the notification rules
	 * and get a true/false result.
	 *
	 * @since 1.1.0
	 *
	 * @param int    $a First integer to compare.
	 * @param int    $b Second integer to compare.
	 * @param string $operator Operator to use, e.g. >, <, >=, <=, =, !=.
	 * @return bool true or false based on the operator passed in. Returns null for invalid operators.
	 */
	function int_compare( $a, $b, $operator ) {
		switch ( $operator ) {
			case '>':
				$r = (int) $a > (int) $b;
				break;
			case '<':
				$r = (int) $a < (int) $b;
				break;
			case '>=':
				$r = (int) $a >= (int) $b;
				break;
			case '<=':
				$r = (int) $a <= (int) $b;
				break;
			case '=':
			case '==':
				$r = (int) $a == (int) $b;
				break;
			case '!=':
			case '<>':
				$r = (int) $a != (int) $b;
				break;
			default:
				$r = null;
		}

		return $r;
	}
}