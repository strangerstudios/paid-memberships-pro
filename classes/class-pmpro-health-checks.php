<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Membership health checks.
 *
 * Each check confirms something that should always be true on a working membership site,
 * such as "every subscription payment that came due has a recorded result". Results are saved
 * in one option. They are shown on the Memberships Dashboard, counted on the Memberships menu,
 * and added to WordPress Site Health. The site admin is emailed when a check first becomes critical.
 *
 * Checks that only query the database run whenever the results are more than an hour old.
 * Checks that contact another server (Stripe or a loopback request) only run from the hourly
 * scheduled task or when an admin clicks "Check again".
 *
 * @since TBD
 */
class PMPro_Health_Checks {

	/**
	 * Option that stores the latest results.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const RESULTS_OPTION = 'pmpro_health_check_results';

	/**
	 * Gateways that report every subscription payment and payment failure back to the site.
	 * Subscriptions on other gateways are skipped by the renewals check to avoid false alarms.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	const RENEWAL_GATEWAYS = array( 'stripe', 'paypalexpress', 'paypalstandard', 'paypal' );

	/**
	 * Add hooks.
	 *
	 * @access public
	 * @since TBD
	 */
	public static function init() {
		add_action( 'pmpro_schedule_hourly', array( __CLASS__, 'run_scheduled_checks' ) );
		add_action( 'admin_post_pmpro_run_health_checks', array( __CLASS__, 'handle_run_request' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_count' ), 99 );
	}

	/**
	 * Get the checks that apply to this site.
	 *
	 * Each callback returns a result array (see run()), or null if the check does not apply.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array Check ID => array with 'label' (what is true when the check passes), 'callback', and 'remote' (whether it contacts another server).
	 */
	public static function get_checks() {
		return array(
			'renewals'            => array(
				'label'    => __( 'Subscription payments are being recorded', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_renewals' ),
				'remote'   => false,
			),
			'scheduled_tasks'     => array(
				'label'    => __( 'Scheduled tasks are running', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_scheduled_tasks' ),
				'remote'   => false,
			),
			'services_paused'     => array(
				'label'    => __( 'Membership services are running', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_services_paused' ),
				'remote'   => false,
			),
			'payment_environment' => array(
				'label'    => __( 'Payment gateway is in the right mode', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_payment_environment' ),
				'remote'   => false,
			),
			'stripe_connection'   => array(
				'label'    => __( 'Stripe accepts your connection', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_stripe_connection' ),
				'remote'   => false, // Reads the saved result of the daily Stripe connection test.
			),
			'stripe_webhook'      => array(
				'label'    => __( 'Stripe webhook events are reaching your site', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_stripe_webhook' ),
				'remote'   => true,
			),
			'expirations'         => array(
				'label'    => __( 'Expired memberships are being ended', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_expirations' ),
				'remote'   => false,
			),
			'email'               => array(
				'label'    => __( 'Membership emails are sending', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_email' ),
				'remote'   => false,
			),
			'restricted_files'    => array(
				'label'    => __( 'Protected member files are private', 'paid-memberships-pro' ),
				'callback' => array( __CLASS__, 'check_restricted_files' ),
				'remote'   => true,
			),
		);
	}

	/**
	 * Run the checks and save the results.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @param bool $include_remote Whether to run checks that contact another server. If false, their last saved result is kept.
	 * @return array The saved results. See get_results().
	 */
	public static function run( $include_remote = true ) {
		$previous = self::get_results();
		$now      = time();
		$checks   = array();

		foreach ( self::get_checks() as $id => $check ) {
			if ( $check['remote'] && ! $include_remote ) {
				// Keep the last result until the scheduled task or an admin runs this check again.
				if ( ! empty( $previous['checks'][ $id ] ) ) {
					$checks[ $id ] = $previous['checks'][ $id ];
				}
				continue;
			}

			try {
				$result = call_user_func( $check['callback'] );
			} catch ( \Throwable $e ) {
				$result = array(
					'status'  => 'unknown',
					/* translators: %s: Error message. */
					'message' => sprintf( __( 'This check could not run: %s', 'paid-memberships-pro' ), $e->getMessage() ),
				);
			}

			// The check does not apply to this site.
			if ( null === $result ) {
				continue;
			}

			$checks[ $id ] = array_merge(
				array(
					'status'       => 'unknown',
					'title'        => $check['label'],
					'message'      => '',
					'items'        => array(),
					'action_url'   => '',
					'action_label' => '',
				),
				$result,
				array( 'checked' => $now )
			);
		}

		$results = array(
			'checked' => $now,
			'checks'  => $checks,
		);
		update_option( self::RESULTS_OPTION, $results );

		self::maybe_send_alert( $previous, $results );

		return $results;
	}

	/**
	 * Get the saved results.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array|false Array with 'checked' (timestamp) and 'checks' (check ID => result), or false if the checks have never run.
	 */
	public static function get_results() {
		$results = get_option( self::RESULTS_OPTION );
		if ( ! is_array( $results ) || empty( $results['checked'] ) || ! isset( $results['checks'] ) || ! is_array( $results['checks'] ) ) {
			return false;
		}
		return $results;
	}

	/**
	 * Get results that are at most an hour old, running the database checks if needed.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The results. See get_results().
	 */
	public static function get_current_results() {
		$results = self::get_results();
		if ( empty( $results ) || $results['checked'] < time() - HOUR_IN_SECONDS ) {
			$results = self::run( false );
		}
		return $results;
	}

	/**
	 * Count the critical results.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @param array|false $results Results from get_results().
	 * @return int The number of critical results.
	 */
	public static function count_critical( $results ) {
		if ( empty( $results['checks'] ) ) {
			return 0;
		}
		return count( wp_list_filter( $results['checks'], array( 'status' => 'critical' ) ) );
	}

	/**
	 * Run every check from the hourly scheduled task.
	 *
	 * @access public
	 * @since TBD
	 */
	public static function run_scheduled_checks() {
		self::run( true );
	}

	/**
	 * Run every check when an admin clicks "Check again" on the dashboard.
	 *
	 * @access public
	 * @since TBD
	 */
	public static function handle_run_request() {
		if ( ! current_user_can( 'pmpro_dashboard' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'paid-memberships-pro' ) );
		}
		check_admin_referer( 'pmpro_run_health_checks' );

		// Also refresh the daily Stripe connection test so that fixed keys show as fixed.
		if ( 'stripe' === get_option( 'pmpro_gateway' ) && class_exists( 'PMProGateway_stripe' ) ) {
			PMProGateway_stripe::run_scheduled_connection_test();
		}

		self::run( true );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pmpro-dashboard' ), admin_url( 'admin.php' ) ) . '#pmpro_dashboard_health' );
		exit;
	}

	/**
	 * Email the site admin when a check becomes critical.
	 *
	 * A check that stays critical does not send another email. It sends again only if it
	 * recovers and then fails again.
	 *
	 * @access private
	 * @since TBD
	 *
	 * @param array|false $previous The results before this run.
	 * @param array       $current  The results from this run.
	 */
	private static function maybe_send_alert( $previous, $current ) {
		// A paused site is usually a staging copy. Don't email the real site's admin from it.
		if ( pmpro_is_paused() ) {
			return;
		}

		$new_problems = array();
		foreach ( $current['checks'] as $id => $result ) {
			if ( 'critical' !== $result['status'] ) {
				continue;
			}
			if ( ! empty( $previous['checks'][ $id ]['status'] ) && 'critical' === $previous['checks'][ $id ]['status'] ) {
				continue;
			}
			$new_problems[ $id ] = $result;
		}

		if ( empty( $new_problems ) ) {
			return;
		}

		$email = new PMPro_Email_Template_Health_Check_Admin( $new_problems );
		$email->send();
	}

	/**
	 * Show the number of critical problems on the Memberships menu.
	 *
	 * The count is added after the menu is registered so that it doesn't change the menu's screen IDs.
	 *
	 * @access public
	 * @since TBD
	 */
	public static function add_menu_count() {
		global $menu;

		if ( ! current_user_can( 'pmpro_dashboard' ) || empty( $menu ) ) {
			return;
		}

		$count = self::count_critical( self::get_results() );
		if ( empty( $count ) ) {
			return;
		}

		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && 'pmpro-dashboard' === $item[2] ) {
				$menu[ $key ][0] .= sprintf(
					' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
					$count,
					/* translators: %d: Number of critical membership health problems. */
					esc_html( sprintf( _n( '%d critical membership problem', '%d critical membership problems', $count, 'paid-memberships-pro' ), $count ) )
				);
				break;
			}
		}
	}

	/**
	 * Check that every subscription payment that came due has a recorded result.
	 *
	 * A successful payment moves the subscription's next payment date forward, a failed payment
	 * creates a pending order, and a cancellation changes the status. An active subscription
	 * that is past its next payment date with no order since then is one the site never heard about.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array|null The result, or null if no subscriptions are on a gateway that this check supports.
	 */
	public static function check_renewals() {
		global $wpdb;

		$environment = 'live' === get_option( 'pmpro_gateway_environment' ) ? 'live' : 'sandbox';
		$gateways    = self::RENEWAL_GATEWAYS;
		$gateway_sql = implode( ', ', array_fill( 0, count( $gateways ), '%s' ) );
		$now         = time();

		// Skip sites that have no subscriptions on a supported gateway.
		$has_subscriptions = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- PMPro custom table; the check needs current data.
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->pmpro_subscriptions} WHERE gateway IN ( $gateway_sql ) AND gateway_environment = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $gateway_sql is a list of %s placeholders.
				array_merge( $gateways, array( $environment ) )
			)
		);
		if ( empty( $has_subscriptions ) ) {
			return null;
		}

		// Active paid subscriptions that came due 3 to 30 days ago with no order of any status since a day before the due date.
		// Subscriptions still in a free trial are skipped because $0 invoices don't create orders.
		$silent = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- PMPro custom tables; the check needs current data.
			$wpdb->prepare(
				"SELECT s.id, s.user_id, s.membership_level_id, s.gateway, s.next_payment_date
				FROM {$wpdb->pmpro_subscriptions} s
				WHERE s.status = 'active'
					AND s.gateway IN ( $gateway_sql )
					AND s.gateway_environment = %s
					AND s.billing_amount > 0
					AND s.cycle_number > 0
					AND s.next_payment_date < %s
					AND s.next_payment_date >= %s
					AND NOT ( s.trial_limit > 0 AND s.trial_amount <= 0 AND (
						SELECT COUNT(*) FROM {$wpdb->pmpro_membership_orders} t
						WHERE t.subscription_transaction_id = s.subscription_transaction_id
							AND t.gateway = s.gateway
							AND t.gateway_environment = s.gateway_environment
							AND t.status = 'success'
					) <= s.trial_limit )
					AND NOT EXISTS (
						SELECT 1 FROM {$wpdb->pmpro_membership_orders} o
						WHERE o.subscription_transaction_id = s.subscription_transaction_id
							AND o.gateway = s.gateway
							AND o.gateway_environment = s.gateway_environment
							AND o.timestamp >= DATE_SUB( s.next_payment_date, INTERVAL 1 DAY )
					)
				ORDER BY s.next_payment_date DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $gateway_sql is a list of %s placeholders.
				array_merge(
					$gateways,
					array(
						$environment,
						gmdate( 'Y-m-d H:i:s', $now - 3 * DAY_IN_SECONDS ),
						gmdate( 'Y-m-d H:i:s', $now - 30 * DAY_IN_SECONDS ),
					)
				)
			)
		);

		if ( empty( $silent ) ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'Every subscription payment due in the last 30 days has a recorded payment, failure or cancellation.', 'paid-memberships-pro' ),
			);
		}

		// When did the site last record a renewal (an order for a subscription that already had an order)?
		// Only the last 31 days matter, since the silent subscriptions all came due in the last 30 days.
		$last_renewal = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- PMPro custom table; the check needs current data.
			$wpdb->prepare(
				"SELECT MAX( o.timestamp ) FROM {$wpdb->pmpro_membership_orders} o
				WHERE o.subscription_transaction_id <> ''
					AND o.gateway IN ( $gateway_sql )
					AND o.gateway_environment = %s
					AND o.status IN ( 'success', 'pending', 'refunded' )
					AND o.timestamp >= %s
					AND o.timestamp <= %s
					AND EXISTS (
						SELECT 1 FROM {$wpdb->pmpro_membership_orders} o2
						WHERE o2.subscription_transaction_id = o.subscription_transaction_id
							AND o2.gateway = o.gateway
							AND o2.gateway_environment = o.gateway_environment
							AND o2.id < o.id
					)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $gateway_sql is a list of %s placeholders.
				array_merge( $gateways, array( $environment, gmdate( 'Y-m-d H:i:s', $now - 31 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', $now ) ) )
			)
		);

		// Some Stripe renewals never create an order even though Stripe notified the site, such as a renewal that needs
		// the member to authenticate their card, or one with no card on file. If this site received any Stripe event after
		// such a subscription came due, the webhook is working, so that subscription doesn't count as a sign that it stopped.
		$last_stripe_event = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One value across many options; the check needs current data.
			$wpdb->prepare(
				"SELECT MAX( CAST( option_value AS UNSIGNED ) ) FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'pmpro_stripe_webhook_last_received_' . $environment . '_' ) . '%'
			)
		);

		// Subscriptions that came due after the last recorded renewal. If several did and none were recorded, notifications have probably stopped.
		$silent_since_last_renewal = array_filter(
			$silent,
			function ( $subscription ) use ( $last_renewal, $last_stripe_event ) {
				if ( ! empty( $last_renewal ) && $subscription->next_payment_date <= $last_renewal ) {
					return false;
				}
				if ( 'stripe' === $subscription->gateway && $last_stripe_event > strtotime( $subscription->next_payment_date . ' UTC' ) ) {
					return false;
				}
				return true;
			}
		);

		$items = array();
		foreach ( array_slice( $silent, 0, 5 ) as $subscription ) {
			$user    = get_userdata( $subscription->user_id );
			$level   = pmpro_getLevel( $subscription->membership_level_id );
			$items[] = array(
				'label' => sprintf(
					/* translators: 1: Member display name or user ID, 2: Membership level name, 3: Date the payment was due. */
					__( '%1$s (%2$s), due %3$s', 'paid-memberships-pro' ),
					empty( $user ) ? '#' . $subscription->user_id : $user->display_name,
					empty( $level ) ? '#' . $subscription->membership_level_id : $level->name,
					date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $subscription->next_payment_date ) ) )
				),
				'url'   => add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->id ), admin_url( 'admin.php' ) ),
			);
		}

		$count = count( $silent );
		if ( count( $silent_since_last_renewal ) >= 2 ) {
			return array(
				'status'       => 'critical',
				'title'        => __( 'Subscription payments are not being recorded', 'paid-memberships-pro' ),
				'message'      => empty( $last_renewal )
					/* translators: %d: Number of subscriptions. */
					? sprintf( _n( '%d subscription payment came due, and this site has no record of whether it was paid. Your payment gateway\'s notifications (webhooks or IPN) are probably not reaching this site, so renewals, failed payments and cancellations are not being processed.', '%d subscription payments came due, and this site has no record of whether any of them were paid. Your payment gateway\'s notifications (webhooks or IPN) are probably not reaching this site, so renewals, failed payments and cancellations are not being processed.', count( $silent_since_last_renewal ), 'paid-memberships-pro' ), count( $silent_since_last_renewal ) )
					: sprintf(
						/* translators: 1: Number of subscriptions, 2: Date of the last recorded renewal. */
						_n( '%1$d subscription payment came due since the last renewal this site recorded on %2$s, and there is no record of whether it was paid. Your payment gateway\'s notifications (webhooks or IPN) have probably stopped reaching this site.', '%1$d subscription payments came due since the last renewal this site recorded on %2$s, and there is no record of whether any of them were paid. Your payment gateway\'s notifications (webhooks or IPN) have probably stopped reaching this site.', count( $silent_since_last_renewal ), 'paid-memberships-pro' ),
						count( $silent_since_last_renewal ),
						date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $last_renewal ) ) )
					),
				'items'        => $items,
				'action_url'   => add_query_arg( array( 'page' => 'pmpro-paymentsettings' ), admin_url( 'admin.php' ) ),
				'action_label' => __( 'Review payment settings', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'       => 'warning',
			/* translators: %d: Number of subscriptions. */
			'title'        => sprintf( _n( '%d subscription payment has no recorded result', '%d subscription payments have no recorded result', $count, 'paid-memberships-pro' ), $count ),
			'message'      => _n( 'This subscription is past its next payment date, and this site has no record of a payment, failure or cancellation. This can happen when a renewal is waiting for the member to authenticate their card, or when there is no card on file. Open it and sync it with the payment gateway to see what happened.', 'These subscriptions are past their next payment date, and this site has no record of a payment, failure or cancellation. This can happen when a renewal is waiting for the member to authenticate their card, or when there is no card on file. Open each one and sync it with the payment gateway to see what happened.', $count, 'paid-memberships-pro' ),
			'items'        => $items,
			'action_url'   => add_query_arg( array( 'page' => 'pmpro-subscriptions' ), admin_url( 'admin.php' ) ),
			'action_label' => __( 'View subscriptions', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Check that PMPro's recurring scheduled tasks are running on time.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The result.
	 */
	public static function check_scheduled_tasks() {
		global $wpdb;

		// Pause mode stops scheduled tasks on purpose. The pause check reports it.
		if ( pmpro_is_paused() ) {
			return array(
				'status'  => 'unknown',
				'message' => __( 'Scheduled tasks are stopped while membership services are paused.', 'paid-memberships-pro' ),
			);
		}

		$action_scheduler_url = add_query_arg( array( 'page' => 'action-scheduler', 'status' => 'past-due' ), admin_url( 'tools.php' ) );

		$table_issues = class_exists( 'PMPro_Action_Scheduler' ) ? PMPro_Action_Scheduler::check_action_scheduler_table_health() : array();
		if ( ! empty( $table_issues ) ) {
			return array(
				'status'       => 'critical',
				'title'        => __( 'Scheduled tasks cannot run', 'paid-memberships-pro' ),
				/* translators: %s: List of database problems. */
				'message'      => sprintf( __( 'The database tables that run scheduled tasks are damaged, so expirations, reminders and other automatic tasks are not running. Problems found: %s', 'paid-memberships-pro' ), implode( '; ', $table_issues ) ),
				'action_url'   => add_query_arg( array( 'tab' => 'debug' ), admin_url( 'site-health.php' ) ),
				'action_label' => __( 'View Site Health info', 'paid-memberships-pro' ),
			);
		}

		// The recurring tasks run every 15 minutes or more. If one is more than an hour late, the task runner has stopped.
		$overdue = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Action Scheduler tables have no API for this query; the check needs current data.
			$wpdb->prepare(
				"SELECT COUNT(*) AS overdue, MIN( a.scheduled_date_gmt ) AS oldest
				FROM {$wpdb->prefix}actionscheduler_actions a
				INNER JOIN {$wpdb->prefix}actionscheduler_groups g ON a.group_id = g.group_id
				WHERE g.slug = %s
					AND a.status = %s
					AND a.scheduled_date_gmt < %s",
				'pmpro_recurring_tasks',
				'pending',
				gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
			)
		);

		if ( ! empty( $overdue->overdue ) ) {
			return array(
				'status'       => 'critical',
				'title'        => __( 'Scheduled tasks have stopped running', 'paid-memberships-pro' ),
				'message'      => sprintf(
					/* translators: %s: How long ago, for example "3 hours". */
					__( 'Paid Memberships Pro\'s scheduled tasks have not run for %s. Memberships are not being expired and reminder emails are not being sent. This usually means WP-Cron is disabled or blocked on your host.', 'paid-memberships-pro' ),
					human_time_diff( strtotime( $overdue->oldest . ' UTC' ), time() )
				),
				'action_url'   => $action_scheduler_url,
				'action_label' => __( 'View scheduled actions', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'  => 'pass',
			'message' => __( 'Expirations, reminders and other automatic tasks are running on schedule.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Check whether PMPro paused its services after the site URL changed.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The result.
	 */
	public static function check_services_paused() {
		if ( ! pmpro_is_paused() ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'Scheduled tasks and payment notifications are active.', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'       => 'critical',
			'title'        => __( 'Membership services are paused', 'paid-memberships-pro' ),
			'message'      => sprintf(
				/* translators: %s: The last known site URL. */
				__( 'Your site URL changed from %s, so Paid Memberships Pro paused scheduled tasks and other automatic services. If this is your live site, reactivate services. If this is a staging copy, leave them paused.', 'paid-memberships-pro' ),
				get_option( 'pmpro_last_known_url' )
			),
			'action_url'   => current_user_can( 'pmpro_manage_pause_mode' ) ? add_query_arg( array( 'page' => 'pmpro-dashboard', 'show_pause_notification' => 1, '_wpnonce' => wp_create_nonce( 'pmpro_show_pause_notification' ) ), admin_url( 'admin.php' ) ) : '',
			'action_label' => __( 'Show reactivation options', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Check that the gateway isn't in sandbox mode on a site that has been taking live payments.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array|null The result, or null if no gateway is set up.
	 */
	public static function check_payment_environment() {
		global $wpdb;

		$gateway = get_option( 'pmpro_gateway' );
		if ( empty( $gateway ) || 'check' === $gateway ) {
			return null;
		}

		if ( 'live' === get_option( 'pmpro_gateway_environment' ) ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'Your payment gateway is in live mode.', 'paid-memberships-pro' ),
			);
		}

		$last_live_payment = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- PMPro custom table; the check needs current data.
			$wpdb->prepare(
				"SELECT MAX( timestamp ) FROM {$wpdb->pmpro_membership_orders}
				WHERE gateway_environment = 'live'
					AND status = 'success'
					AND total > 0
					AND timestamp >= %s",
				gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS )
			)
		);

		if ( empty( $last_live_payment ) ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'Your payment gateway is in sandbox mode, and this site has not taken live payments in the last 90 days.', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'       => 'critical',
			'title'        => __( 'Checkout is in sandbox mode', 'paid-memberships-pro' ),
			'message'      => sprintf(
				/* translators: %s: Date of the last live payment. */
				__( 'Your payment gateway is in sandbox (test) mode, but this site took a live payment on %s. While in sandbox mode, new members are not charged. Switch back to live mode if you have finished testing.', 'paid-memberships-pro' ),
				date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( $last_live_payment ) ) )
			),
			'action_url'   => add_query_arg( array( 'page' => 'pmpro-paymentsettings' ), admin_url( 'admin.php' ) ),
			'action_label' => __( 'Review payment settings', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Check the saved result of the daily Stripe connection test.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array|null The result, or null if Stripe isn't the gateway.
	 */
	public static function check_stripe_connection() {
		if ( 'stripe' !== get_option( 'pmpro_gateway' ) || ! class_exists( 'PMProGateway_stripe' ) ) {
			return null;
		}

		$settings_url = add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'edit_gateway' => 'stripe' ), admin_url( 'admin.php' ) ) . '#pmpro_stripe_connection_test';

		$test_results = PMProGateway_stripe::get_connection_test_results();
		if ( empty( $test_results ) ) {
			return array(
				'status'  => 'unknown',
				'message' => __( 'The Stripe connection test has not run yet. It runs daily, or you can run it from the payment settings.', 'paid-memberships-pro' ),
			);
		}

		$failed = PMProGateway_stripe::get_failed_connection_tests( $test_results );
		if ( empty( $failed ) ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'Stripe accepted this site\'s keys in the last connection test.', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'       => 'critical',
			'title'        => __( 'Stripe is rejecting this site\'s connection', 'paid-memberships-pro' ),
			'message'      => sprintf(
				/* translators: %s: List of failed parts of the connection test, such as "Secret Key". */
				__( 'The last Stripe connection test failed for: %s. Checkouts and subscription updates will fail until this is fixed.', 'paid-memberships-pro' ),
				implode( ', ', array_map( array( 'PMProGateway_stripe', 'get_connection_test_label' ), $failed ) )
			),
			'action_url'   => $settings_url,
			'action_label' => __( 'Review the Stripe connection test', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Check that the Stripe webhook exists and that events Stripe sent were received.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array|null The result, or null if Stripe isn't the gateway or the connection test is failing.
	 */
	public static function check_stripe_webhook() {
		if ( 'stripe' !== get_option( 'pmpro_gateway' ) || ! class_exists( 'PMProGateway_stripe' ) ) {
			return null;
		}

		$webhook = PMProGateway_stripe::get_webhook_health();
		if ( empty( $webhook ) ) {
			return null;
		}

		$settings_url = add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'edit_gateway' => 'stripe' ), admin_url( 'admin.php' ) ) . '#pmpro_stripe_webhook';

		if ( 'missing' === $webhook['status'] || 'disabled' === $webhook['status'] ) {
			return array(
				'status'       => 'critical',
				'title'        => 'missing' === $webhook['status'] ? __( 'The Stripe webhook is not set up', 'paid-memberships-pro' ) : __( 'The Stripe webhook is disabled', 'paid-memberships-pro' ),
				'message'      => __( 'Without the webhook, this site doesn\'t hear about renewals, failed payments or cancellations in Stripe.', 'paid-memberships-pro' ),
				'action_url'   => $settings_url,
				'action_label' => __( 'Set up the webhook', 'paid-memberships-pro' ),
			);
		}

		if ( 'error' === $webhook['status'] ) {
			return array(
				'status'  => 'unknown',
				/* translators: %s: Error message from Stripe. */
				'message' => sprintf( __( 'Stripe could not be asked about the webhook: %s', 'paid-memberships-pro' ), $webhook['error'] ),
			);
		}

		if ( ! empty( $webhook['missed_events'] ) ) {
			return array(
				'status'       => 'critical',
				'title'        => __( 'Stripe webhook events are not reaching your site', 'paid-memberships-pro' ),
				'message'      => sprintf(
					/* translators: %s: List of Stripe event types. */
					__( 'Stripe sent events that this site never received: %s. Your host, firewall or a security plugin may be blocking Stripe.', 'paid-memberships-pro' ),
					implode( ', ', array_keys( $webhook['missed_events'] ) )
				),
				'action_url'   => $settings_url,
				'action_label' => __( 'View webhook history', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'  => 'pass',
			'message' => __( 'The webhook is enabled, and this site has received the latest event of each type that Stripe sent.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Check for active memberships that are more than a day past their end date.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The result.
	 */
	public static function check_expirations() {
		global $wpdb;

		$where = $wpdb->prepare(
			"FROM {$wpdb->pmpro_memberships_users}
			WHERE status = 'active'
				AND enddate IS NOT NULL
				AND enddate > %s
				AND enddate < %s",
			PMPro_Recurring_Actions::PMPRO_MAGIC_MIN_DATE,
			gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DAY_IN_SECONDS ) // Membership end dates are saved in site time.
		);

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) $where" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built with $wpdb->prepare() above. PMPro custom table; the check needs current data.
		if ( empty( $count ) ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'No member has access past their membership end date.', 'paid-memberships-pro' ),
			);
		}

		$memberships = $wpdb->get_results( "SELECT user_id, enddate $where ORDER BY enddate ASC LIMIT 5" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built with $wpdb->prepare() above. PMPro custom table; the check needs current data.
		$items       = array();
		foreach ( $memberships as $membership ) {
			$user    = get_userdata( $membership->user_id );
			$items[] = array(
				'label' => sprintf(
					/* translators: 1: Member display name or user ID, 2: Membership end date. */
					__( '%1$s, ended %2$s', 'paid-memberships-pro' ),
					empty( $user ) ? '#' . $membership->user_id : $user->display_name,
					date_i18n( get_option( 'date_format' ), strtotime( $membership->enddate ) )
				),
				'url'   => add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $membership->user_id ), admin_url( 'admin.php' ) ),
			);
		}

		return array(
			'status'  => 'warning',
			/* translators: %d: Number of memberships. */
			'title'   => sprintf( _n( '%d membership is past its end date but still active', '%d memberships are past their end date but still active', $count, 'paid-memberships-pro' ), $count ),
			'message' => _n( 'This member still has access after their membership ended. If scheduled tasks are running, custom code may be stopping the expiration.', 'These members still have access after their memberships ended. If scheduled tasks are running, custom code may be stopping the expirations.', $count, 'paid-memberships-pro' ),
			'items'   => $items,
		);
	}

	/**
	 * Check the email log for failed emails and whether a mail service is set up.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array The result.
	 */
	public static function check_email() {
		global $wpdb;

		if ( ! function_exists( 'pmpro_is_email_logging_enabled' ) || ! pmpro_is_email_logging_enabled() ) {
			return array(
				'status'  => 'unknown',
				'message' => __( 'Email logging is turned off, so failed emails can\'t be detected.', 'paid-memberships-pro' ),
			);
		}

		$counts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- PMPro custom table; the check needs current data.
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS total FROM {$wpdb->pmpro_email_log} WHERE timestamp >= %s GROUP BY status",
				gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS )
			),
			OBJECT_K
		);
		$failed = isset( $counts['failed'] ) ? (int) $counts['failed']->total : 0;
		$sent   = isset( $counts['sent'] ) ? (int) $counts['sent']->total : 0;

		$log_url = add_query_arg( array( 'page' => 'pmpro-reports', 'report' => 'email_log', 'status' => 'failed' ), admin_url( 'admin.php' ) );

		if ( $failed > 0 ) {
			// Mostly failing means mail is broken. A few failures are worth a look.
			$critical = $failed >= 3 && $failed >= $sent;
			return array(
				'status'       => $critical ? 'critical' : 'warning',
				'title'        => $critical ? __( 'Membership emails are failing', 'paid-memberships-pro' ) : __( 'Some membership emails failed', 'paid-memberships-pro' ),
				'message'      => sprintf(
					/* translators: 1: Number of failed emails, 2: Total number of emails. */
					_n( '%1$d of %2$d email sent in the last 7 days failed. Check the email log for the error message.', '%1$d of %2$d emails sent in the last 7 days failed. Check the email log for the error message.', $failed + $sent, 'paid-memberships-pro' ),
					$failed,
					$failed + $sent
				),
				'action_url'   => $log_url,
				'action_label' => __( 'View failed emails', 'paid-memberships-pro' ),
			);
		}

		// No failures, but PHP mail() can report success for mail that is never delivered.
		if ( function_exists( 'pmpro_detect_email_method' ) ) {
			$method = pmpro_detect_email_method();
			if ( ! empty( $method['method'] ) && 'default' === $method['method'] ) {
				return array(
					'status'       => 'warning',
					'title'        => __( 'No email delivery service detected', 'paid-memberships-pro' ),
					'message'      => __( 'Emails are sent with your server\'s default mail function. Many hosts accept these emails and never deliver them, so they look sent here but don\'t reach members. An SMTP plugin or email service makes delivery reliable.', 'paid-memberships-pro' ),
					'action_url'   => add_query_arg( array( 'page' => 'pmpro-emailsettings' ), admin_url( 'admin.php' ) ),
					'action_label' => __( 'Review email settings', 'paid-memberships-pro' ),
				);
			}
		}

		return array(
			'status'  => 'pass',
			/* translators: %d: Number of emails. */
			'message' => sprintf( _n( '%d email sent in the last 7 days with no failures.', '%d emails sent in the last 7 days with no failures.', $sent, 'paid-memberships-pro' ), $sent ),
		);
	}

	/**
	 * Check that files in the restricted files folder can't be downloaded directly.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return array|null The result, or null if there are no restricted files to test.
	 */
	public static function check_restricted_files() {
		if ( ! function_exists( 'pmpro_is_restricted_directory_protected' ) ) {
			return null;
		}

		$protected = pmpro_is_restricted_directory_protected();
		if ( null === $protected ) {
			return null;
		}

		if ( $protected ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'A test request for a protected file was blocked.', 'paid-memberships-pro' ),
			);
		}

		return array(
			'status'       => 'critical',
			'title'        => __( 'Protected member files can be downloaded by anyone', 'paid-memberships-pro' ),
			'message'      => __( 'A logged-out request downloaded a file from the restricted files folder. The folder is protected by an .htaccess file, which servers like Nginx ignore. Ask your host to block direct access to this folder.', 'paid-memberships-pro' ),
			'action_url'   => 'https://www.paidmembershipspro.com/documentation/admin/security-settings/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=documentation&utm_content=membership-health#restricted-files',
			'action_label' => __( 'Restricted file settings', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Show the health checks in the Membership Health box on the Memberships Dashboard.
	 *
	 * @access public
	 * @since TBD
	 */
	public static function show_dashboard_metabox() {
		$results = self::get_current_results();
		$checks  = self::get_checks();

		$problems = array();
		$others   = array();
		$order    = array( 'critical' => 0, 'warning' => 1 );
		foreach ( $results['checks'] as $id => $result ) {
			if ( isset( $order[ $result['status'] ] ) ) {
				$problems[ $id ] = $result;
			} else {
				$others[ $id ] = $result;
			}
		}
		uasort(
			$problems,
			function ( $a, $b ) use ( $order ) {
				return $order[ $a['status'] ] - $order[ $b['status'] ];
			}
		);
		$passed = count( wp_list_filter( $others, array( 'status' => 'pass' ) ) );
		$recheck_url = wp_nonce_url( add_query_arg( array( 'action' => 'pmpro_run_health_checks' ), admin_url( 'admin-post.php' ) ), 'pmpro_run_health_checks' );
		?>
		<div class="pmpro-health<?php echo empty( $problems ) ? ' pmpro-health-is-clear' : ''; ?>">
			<div class="pmpro-health-toolbar">
				<div class="pmpro-health-summary">
					<?php if ( empty( $problems ) ) { ?>
						<span class="pmpro_tag pmpro_tag-success pmpro_tag-has_icon"><?php esc_html_e( 'All clear', 'paid-memberships-pro' ); ?></span>
						<?php
						/* translators: %d: Number of checks. */
						echo esc_html( sprintf( _n( '%d check passed', 'All %d checks passed', $passed, 'paid-memberships-pro' ), $passed ) );
						?>
					<?php } else { ?>
						<?php
						$critical_count = count( wp_list_filter( $problems, array( 'status' => 'critical' ) ) );
						$warning_count  = count( $problems ) - $critical_count;
						if ( $critical_count ) {
							/* translators: %d: Number of critical problems. */
							echo '<span class="pmpro_tag pmpro_tag-error pmpro_tag-has_icon">' . esc_html( sprintf( _n( '%d critical', '%d critical', $critical_count, 'paid-memberships-pro' ), $critical_count ) ) . '</span> ';
						}
						if ( $warning_count ) {
							/* translators: %d: Number of warnings. */
							echo '<span class="pmpro_tag pmpro_tag-alert pmpro_tag-has_icon">' . esc_html( sprintf( _n( '%d warning', '%d warnings', $warning_count, 'paid-memberships-pro' ), $warning_count ) ) . '</span>';
						}
						?>
					<?php } ?>
				</div>
				<div class="pmpro-health-run">
					<span class="pmpro-health-checked">
						<?php
						/* translators: %s: How long ago, for example "5 mins". */
						echo esc_html( sprintf( __( 'Checked %s ago', 'paid-memberships-pro' ), human_time_diff( $results['checked'], time() ) ) );
						?>
					</span>
					<a class="button button-secondary" href="<?php echo esc_url( $recheck_url ); ?>"><?php esc_html_e( 'Check again', 'paid-memberships-pro' ); ?></a>
				</div>
			</div>
				<?php if ( ! empty( $problems ) ) { ?>
					<ul class="pmpro-health-problems">
						<?php foreach ( $problems as $id => $result ) { ?>
							<li class="pmpro-health-problem pmpro-health-<?php echo esc_attr( $result['status'] ); ?>">
								<div class="pmpro-health-problem-status">
									<?php if ( 'critical' === $result['status'] ) { ?>
										<span class="pmpro_tag pmpro_tag-error pmpro_tag-has_icon"><?php esc_html_e( 'Critical', 'paid-memberships-pro' ); ?></span>
									<?php } else { ?>
										<span class="pmpro_tag pmpro_tag-alert pmpro_tag-has_icon"><?php esc_html_e( 'Warning', 'paid-memberships-pro' ); ?></span>
									<?php } ?>
								</div>
								<div class="pmpro-health-problem-body">
									<h3><?php echo esc_html( $result['title'] ); ?></h3>
									<p><?php echo esc_html( $result['message'] ); ?></p>
									<?php if ( ! empty( $result['items'] ) ) { ?>
										<ul class="pmpro-health-items">
											<?php foreach ( $result['items'] as $item ) { ?>
												<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
											<?php } ?>
										</ul>
									<?php } ?>
								</div>
								<?php if ( ! empty( $result['action_url'] ) && ! empty( $result['action_label'] ) ) { ?>
									<div class="pmpro-health-problem-action">
										<a class="button <?php echo 'critical' === $result['status'] ? 'button-primary' : 'button-secondary'; ?>" href="<?php echo esc_url( $result['action_url'] ); ?>"><?php echo esc_html( $result['action_label'] ); ?></a>
									</div>
								<?php } ?>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
				<?php if ( ! empty( $others ) ) { ?>
					<details class="pmpro-health-others">
						<summary>
							<?php
							$not_checked = count( $others ) - $passed;
							if ( empty( $not_checked ) ) {
								/* translators: %d: Number of checks. */
								echo esc_html( sprintf( _n( 'Passed checks (%d)', 'Passed checks (%d)', $passed, 'paid-memberships-pro' ), $passed ) );
							} else {
								/* translators: 1: Number of passed checks, 2: Number of checks that could not run. */
								echo esc_html( sprintf( __( 'Passed checks (%1$d) and checks that could not run (%2$d)', 'paid-memberships-pro' ), $passed, $not_checked ) );
							}
							?>
						</summary>
						<ul>
							<?php foreach ( $others as $id => $result ) { ?>
								<li class="pmpro-health-<?php echo esc_attr( $result['status'] ); ?>">
									<span class="dashicons <?php echo 'pass' === $result['status'] ? 'dashicons-yes-alt' : 'dashicons-minus'; ?>" aria-hidden="true"></span>
									<strong><?php echo esc_html( isset( $checks[ $id ] ) ? $checks[ $id ]['label'] : $result['title'] ); ?></strong>
									<?php if ( 'pass' !== $result['status'] ) { ?>
										<em><?php esc_html_e( '(could not run)', 'paid-memberships-pro' ); ?></em>
									<?php } ?>
									<span class="pmpro-health-detail"><?php echo esc_html( $result['message'] ); ?></span>
								</li>
							<?php } ?>
						</ul>
					</details>
				<?php } ?>
		</div>
		<?php
	}

	/**
	 * Add each check to WordPress Site Health.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @param array $tests The Site Health tests.
	 * @return array The tests with membership health checks added.
	 */
	public static function add_site_health_tests( $tests ) {
		$results = self::get_current_results();
		foreach ( self::get_checks() as $id => $check ) {
			if ( empty( $results['checks'][ $id ] ) ) {
				continue;
			}
			$tests['direct'][ 'pmpro-health-' . $id ] = array(
				'label' => $check['label'],
				'test'  => function () use ( $id ) {
					return self::get_site_health_result( $id );
				},
			);
		}
		return $tests;
	}

	/**
	 * Convert a saved result to a Site Health test result.
	 *
	 * @access private
	 * @since TBD
	 *
	 * @param string $id The check ID.
	 * @return array The Site Health test result.
	 */
	private static function get_site_health_result( $id ) {
		$results  = self::get_current_results();
		$result   = $results['checks'][ $id ];
		$statuses = array(
			'critical' => 'critical',
			'warning'  => 'recommended',
		);

		$actions = '';
		if ( ! empty( $result['action_url'] ) && ! empty( $result['action_label'] ) ) {
			$actions = '<p><a href="' . esc_url( $result['action_url'] ) . '">' . esc_html( $result['action_label'] ) . '</a></p>';
		}

		return array(
			'label'       => $result['title'],
			'status'      => isset( $statuses[ $result['status'] ] ) ? $statuses[ $result['status'] ] : 'good',
			'badge'       => array(
				'label' => __( 'PMPro', 'paid-memberships-pro' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html( $result['message'] ) . '</p>',
			'actions'     => $actions,
			'test'        => 'pmpro-health-' . $id,
		);
	}

	/**
	 * Summarize the results for the Site Health Info tab.
	 *
	 * @access public
	 * @since TBD
	 *
	 * @return string The summary.
	 */
	public static function get_summary() {
		$results = self::get_results();
		if ( empty( $results ) ) {
			return __( 'Not checked yet', 'paid-memberships-pro' );
		}

		$counts = array_count_values( wp_list_pluck( $results['checks'], 'status' ) );
		$lines  = array();
		foreach ( $results['checks'] as $id => $result ) {
			$lines[] = $id . ': ' . $result['status'];
		}

		return sprintf(
			/* translators: 1: Number of critical problems, 2: Number of warnings, 3: Number of passed checks, 4: Date and time of the check, 5: Each check's status. */
			__( '%1$d critical, %2$d warnings, %3$d passed (checked %4$s) | %5$s', 'paid-memberships-pro' ),
			isset( $counts['critical'] ) ? $counts['critical'] : 0,
			isset( $counts['warning'] ) ? $counts['warning'] : 0,
			isset( $counts['pass'] ) ? $counts['pass'] : 0,
			gmdate( 'Y-m-d H:i', $results['checked'] ) . ' UTC',
			implode( ', ', $lines )
		);
	}
}
