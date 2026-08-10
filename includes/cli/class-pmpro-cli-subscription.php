<?php
/**
 * `wp pmpro subscription` commands.
 *
 * @since TBD
 * @package PaidMembershipsPro\CLI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage PMPro subscriptions.
 *
 * @since TBD
 */
class PMPro_CLI_Subscription extends PMPro_CLI_Command {

	/**
	 * Default columns shown for a subscription.
	 *
	 * @var array
	 */
	private $default_fields = array( 'id', 'user_id', 'membership_level_id', 'status', 'gateway', 'billing_amount', 'cycle_number', 'cycle_period', 'startdate', 'next_payment_date' );

	/**
	 * List subscriptions.
	 *
	 * ## OPTIONS
	 *
	 * [--user=<id>]
	 * : Only list subscriptions for this user ID.
	 *
	 * [--level=<ids>]
	 * : Only list subscriptions for these membership level IDs. Comma-separated.
	 *
	 * [--status=<statuses>]
	 * : Only list subscriptions with these statuses. Comma-separated.
	 *
	 * [--gateway=<gateway>]
	 * : Only list subscriptions for this gateway.
	 *
	 * [--number=<n>]
	 * : Maximum number of subscriptions to return. Default: 100.
	 *
	 * [--page=<n>]
	 * : Page of results to return. Default: 1.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro subscription list --status=active
	 *     wp pmpro subscription list --user=42
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$number = $this->int_arg( $assoc_args, 'number', 100 );
		$page   = max( 1, $this->int_arg( $assoc_args, 'page', 1 ) );

		$query = array(
			'limit'  => $number,
			'offset' => ( $page - 1 ) * $number,
		);

		if ( ! empty( $assoc_args['user'] ) ) {
			$query['user_id'] = (int) $assoc_args['user'];
		}
		$levels = $this->list_arg( $assoc_args, 'level' );
		if ( $levels ) {
			$query['membership_level_id'] = array_map( 'intval', $levels );
		}
		$statuses = $this->list_arg( $assoc_args, 'status' );
		if ( $statuses ) {
			$query['status'] = $statuses;
		}
		if ( ! empty( $assoc_args['gateway'] ) ) {
			$query['gateway'] = (string) $assoc_args['gateway'];
		}

		$subscriptions = PMPro_Subscription::get_subscriptions( $query );

		$items = array();
		foreach ( (array) $subscriptions as $subscription ) {
			$items[] = $this->normalize_subscription( $subscription );
		}

		$this->output_items( $items, $this->default_fields, $assoc_args );
	}

	/**
	 * Get a single subscription by ID.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The subscription ID.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro subscription get 55
	 *
	 * @subcommand get
	 */
	public function get( $args, $assoc_args ) {
		$subscription = PMPro_Subscription::get_subscription( (int) $args[0] );
		if ( empty( $subscription ) ) {
			WP_CLI::error( sprintf( 'Subscription %d not found.', (int) $args[0] ) );
		}

		$item   = $this->normalize_subscription( $subscription );
		$fields = ! empty( $assoc_args['fields'] ) ? $this->list_arg( $assoc_args, 'fields' ) : array_keys( $item );

		$formatter = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_item( $item );
	}

	/**
	 * Sync one or more subscriptions with their gateway.
	 *
	 * Pulls the latest subscription info from the gateway and saves it locally.
	 * Sync errors are stored in subscription meta ( sync_error ).
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more subscription IDs or gateway transaction IDs ( e.g. sub_XXXX ).
	 *
	 * [--dry-run]
	 * : Preview which subscriptions would be synced without making changes.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro subscription sync 55
	 *     wp pmpro subscription sync sub_1RXa2b3C4d
	 *     wp pmpro subscription sync 55 56 57 --yes
	 *
	 * @subcommand sync
	 */
	public function sync( $args, $assoc_args ) {
		$subscriptions = array();
		foreach ( $args as $arg ) {
			if ( is_numeric( $arg ) ) {
				$subscription = PMPro_Subscription::get_subscription( (int) $arg );
			} else {
				// Assume a gateway transaction ID, e.g. "sub_XXXX".
				$subscription = PMPro_Subscription::get_subscription( array( 'subscription_transaction_id' => (string) $arg ) );
			}
			if ( empty( $subscription ) ) {
				WP_CLI::error( sprintf( 'Subscription %s not found.', $arg ) );
			}
			$subscriptions[ $subscription->get_id() ] = $subscription;
		}

		if ( ! empty( $assoc_args['dry-run'] ) ) {
			WP_CLI::log( sprintf( '[dry-run] Would sync %d subscription(s) with their gateway: %s', count( $subscriptions ), implode( ', ', array_keys( $subscriptions ) ) ) );
			return;
		}

		WP_CLI::confirm( sprintf( 'Sync %d subscription(s) with their gateway?', count( $subscriptions ) ), $assoc_args );

		$failed = 0;
		foreach ( $subscriptions as $id => $subscription ) {
			if ( $subscription->update() ) {
				$sync_error = get_pmpro_subscription_meta( $id, 'sync_error', true );
				if ( empty( $sync_error ) ) {
					WP_CLI::log( sprintf( 'Synced subscription %d ( status: %s, next payment: %s ).', $id, $subscription->get_status(), $subscription->get_next_payment_date( 'Y-m-d H:i:s' ) ) );
				} else {
					$failed++;
					WP_CLI::warning( sprintf( 'Subscription %d saved, but the gateway sync reported an error: %s', $id, $sync_error ) );
				}
			} else {
				$failed++;
				WP_CLI::warning( sprintf( 'Failed to sync subscription %d.', $id ) );
			}
		}

		if ( $failed ) {
			WP_CLI::error( sprintf( '%d of %d subscription(s) failed to sync.', $failed, count( $subscriptions ) ) );
		}
		WP_CLI::success( sprintf( 'Synced %d subscription(s).', count( $subscriptions ) ) );
	}

	/**
	 * Normalize a PMPro_Subscription object to an associative array.
	 *
	 * @param PMPro_Subscription $subscription The subscription object.
	 * @return array
	 */
	private function normalize_subscription( $subscription ) {
		return array(
			'id'                          => (int) $subscription->get_id(),
			'user_id'                     => (int) $subscription->get_user_id(),
			'membership_level_id'         => (int) $subscription->get_membership_level_id(),
			'status'                      => (string) $subscription->get_status(),
			'gateway'                     => (string) $subscription->get_gateway(),
			'subscription_transaction_id' => (string) $subscription->get_subscription_transaction_id(),
			'billing_amount'              => (float) $subscription->get_billing_amount(),
			'cycle_number'                => (int) $subscription->get_cycle_number(),
			'cycle_period'                => (string) $subscription->get_cycle_period(),
			'startdate'                   => $subscription->get_startdate( 'Y-m-d H:i:s' ),
			'enddate'                     => $subscription->get_enddate( 'Y-m-d H:i:s' ),
			'next_payment_date'           => $subscription->get_next_payment_date( 'Y-m-d H:i:s' ),
		);
	}
}
