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
