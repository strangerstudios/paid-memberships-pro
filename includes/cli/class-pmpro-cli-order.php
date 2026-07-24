<?php
/**
 * `wp pmpro order` commands.
 *
 * @since TBD
 * @package PaidMembershipsPro\CLI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage PMPro orders.
 *
 * @since TBD
 */
class PMPro_CLI_Order extends PMPro_CLI_Command {

	/**
	 * Default columns shown for an order.
	 *
	 * @var array
	 */
	private $default_fields = array( 'id', 'code', 'user_id', 'membership_id', 'status', 'gateway', 'total', 'timestamp' );

	/**
	 * List orders.
	 *
	 * ## OPTIONS
	 *
	 * [--user=<id>]
	 * : Only list orders for this user ID.
	 *
	 * [--level=<ids>]
	 * : Only list orders for these membership level IDs. Comma-separated.
	 *
	 * [--status=<statuses>]
	 * : Only list orders with these statuses. Comma-separated.
	 *
	 * [--gateway=<gateway>]
	 * : Only list orders for this gateway.
	 *
	 * [--number=<n>]
	 * : Maximum number of orders to return. Default: 100.
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
	 *     wp pmpro order list --status=success --number=20
	 *     wp pmpro order list --user=42
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

		$orders = MemberOrder::get_orders( $query );

		$items = array();
		foreach ( (array) $orders as $order ) {
			$items[] = $this->normalize_order( $order );
		}

		$this->output_items( $items, $this->default_fields, $assoc_args );
	}

	/**
	 * Get a single order by ID or code.
	 *
	 * ## OPTIONS
	 *
	 * <id-or-code>
	 * : The order ID (numeric) or order code.
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
	 *     wp pmpro order get 123
	 *     wp pmpro order get abc123def
	 *
	 * @subcommand get
	 */
	public function get( $args, $assoc_args ) {
		$identifier = $args[0];
		$order      = is_numeric( $identifier ) ? MemberOrder::get_order( (int) $identifier ) : MemberOrder::get_order( (string) $identifier );

		if ( empty( $order ) || empty( $order->id ) ) {
			WP_CLI::error( sprintf( 'Order "%s" not found.', $identifier ) );
		}

		$item   = $this->normalize_order( $order );
		$fields = ! empty( $assoc_args['fields'] ) ? $this->list_arg( $assoc_args, 'fields' ) : array_keys( $item );

		$formatter = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_item( $item );
	}

	/**
	 * Normalize a MemberOrder object to an associative array.
	 *
	 * @param MemberOrder $order The order object.
	 * @return array
	 */
	private function normalize_order( $order ) {
		return array(
			'id'                          => (int) $order->id,
			'code'                        => $order->code,
			'user_id'                     => (int) $order->user_id,
			'membership_id'               => (int) $order->membership_id,
			'status'                      => $order->status,
			'gateway'                     => $order->gateway,
			'gateway_environment'         => $order->gateway_environment,
			'subtotal'                    => $order->subtotal,
			'tax'                         => $order->tax,
			'total'                       => $order->total,
			'payment_transaction_id'      => $order->payment_transaction_id,
			'subscription_transaction_id' => $order->subscription_transaction_id,
			'timestamp'                   => ! empty( $order->timestamp ) ? gmdate( 'Y-m-d H:i:s', (int) $order->timestamp ) : '',
		);
	}
}
