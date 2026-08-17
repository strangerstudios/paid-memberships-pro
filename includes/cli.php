<?php
/**
 * WP-CLI commands for Paid Memberships Pro.
 *
 * Thin wrappers around existing PMPro functions. One method per command.
 *
 *   wp pmpro member list|get|add-level|remove-level|change-level|cancel
 *   wp pmpro level list|get
 *   wp pmpro order list|get
 *   wp pmpro subscription list|get|sync
 *
 * @since TBD
 * @package PaidMembershipsPro
 */

defined( 'ABSPATH' ) || exit;

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

/**
 * PMPro WP-CLI commands.
 *
 * @since TBD
 */
class PMPro_CLI extends \WP_CLI_Command {

	/**
	 * List members (users who hold a membership).
	 *
	 * ## OPTIONS
	 *
	 * [--level=<ids>]
	 * : Only list members with these membership level IDs. Comma-separated.
	 *
	 * [--status=<statuses>]
	 * : Membership status(es) to match, or 'all' for any status. Comma-separated. Default: active.
	 *
	 * [--search=<term>]
	 * : Match users by login, email, or display name.
	 *
	 * [--number=<n>]
	 * : Maximum number of members to return. Default: 100.
	 *
	 * [--page=<n>]
	 * : Page of results to return. Default: 1.
	 *
	 * [--orderby=<column>]
	 * : Column to order by (id, user_login, user_email, display_name, membership_id, startdate, enddate, joindate). Default: id.
	 *
	 * [--order=<order>]
	 * : ASC or DESC. Default: DESC.
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
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member list --level=2 --status=active
	 *     wp pmpro member list --search=jane --format=json
	 *
	 * @when after_wp_load
	 */
	public function member_list( $args, $assoc_args ) {
		if ( isset( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			WP_CLI::error( 'The ids format is not supported for member list (a user can have multiple memberships). Use --fields=user_id --format=csv.' );
		}

		$number = $this->list_limit( $assoc_args );
		$page   = isset( $assoc_args['page'] ) ? max( 1, (int) $assoc_args['page'] ) : 1;

		$query = array(
			'limit'  => $number,
			'offset' => ( $page - 1 ) * $number,
		);

		if ( ! empty( $assoc_args['level'] ) ) {
			$query['membership_id'] = array_map( 'intval', explode( ',', $assoc_args['level'] ) );
		}
		if ( ! empty( $assoc_args['status'] ) ) {
			$statuses = array_map( 'trim', explode( ',', $assoc_args['status'] ) );
			$query['status'] = ( array( 'all' ) === $statuses ) ? 'all' : $statuses;
		}
		if ( ! empty( $assoc_args['search'] ) ) {
			$query['search'] = (string) $assoc_args['search'];
		}
		if ( ! empty( $assoc_args['orderby'] ) ) {
			$query['orderby'] = (string) $assoc_args['orderby'];
		}
		if ( ! empty( $assoc_args['order'] ) ) {
			$query['order'] = (string) $assoc_args['order'];
		}

		$items = array();
		foreach ( (array) pmpro_get_members( $query ) as $member ) {
			$items[] = array(
				'user_id'         => (int) $member['user_id'],
				'user_login'      => $member['user_login'],
				'user_email'      => $member['user_email'],
				'display_name'    => $member['display_name'],
				'membership_id'   => (int) $member['membership_id'],
				'membership_name' => $member['membership_name'],
				'status'          => $member['status'],
				'startdate'       => $member['startdate'],
				'enddate'         => $member['enddate'],
			);
		}

		$this->output_items( $items, array( 'user_id', 'user_login', 'user_email', 'display_name', 'membership_id', 'membership_name', 'status', 'startdate', 'enddate' ), $assoc_args );
	}

	/**
	 * Get the memberships held by a single member.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID.
	 *
	 * [--include-inactive]
	 * : Include inactive/cancelled/expired memberships.
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
	 *     wp pmpro member get 42
	 *
	 * @when after_wp_load
	 */
	public function member_get( $args, $assoc_args ) {
		$user_id = (int) $args[0];
		if ( ! get_userdata( $user_id ) ) {
			WP_CLI::error( sprintf( 'User %d not found.', $user_id ) );
		}

		$items = array();
		foreach ( (array) pmpro_getMembershipLevelsForUser( $user_id, ! empty( $assoc_args['include-inactive'] ) ) as $level ) {
			$items[] = array(
				'membership_id' => (int) $level->id,
				'name'          => $level->name,
				'status'        => isset( $level->status ) ? $level->status : '',
				'startdate'     => ! empty( $level->startdate ) ? gmdate( 'Y-m-d H:i:s', (int) $level->startdate ) : '',
				'enddate'       => ! empty( $level->enddate ) ? gmdate( 'Y-m-d H:i:s', (int) $level->enddate ) : '',
			);
		}

		if ( empty( $items ) ) {
			WP_CLI::log( sprintf( 'No memberships found for user %d.', $user_id ) );
			return;
		}

		$this->output_items( $items, array( 'membership_id', 'name', 'status', 'startdate', 'enddate' ), $assoc_args );
	}

	/**
	 * Add a membership level to a user.
	 *
	 * Other levels in the same exclusive group may be cancelled.
	 * Levels in other groups are kept.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID.
	 *
	 * --level=<id>
	 * : The membership level ID to add.
	 *
	 * [--dry-run]
	 * : Preview the change without applying it.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member add-level 42 --level=2
	 *
	 * @when after_wp_load
	 */
	public function member_add_level( $args, $assoc_args ) {
		$user_id  = $this->require_user_id( isset( $args[0] ) ? $args[0] : '' );
		$user     = $this->require_existing_user( $user_id );
		$level_id = $this->require_level_id( $assoc_args, true );
		$level    = pmpro_getLevel( $level_id );
		if ( empty( $level ) ) {
			WP_CLI::error( sprintf( 'Membership level %d not found.', $level_id ) );
		}

		$description = sprintf( 'Add level "%s" (#%d) to %s (#%d)?', $level->name, $level_id, $user->user_login, $user_id );
		if ( ! $this->confirm_or_dry_run( $description, $assoc_args ) ) {
			return;
		}

		$this->add_membership_level( $user_id, $level_id );
	}

	/**
	 * Remove a membership level from a user.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID.
	 *
	 * --level=<id>
	 * : The membership level ID to remove.
	 *
	 * [--dry-run]
	 * : Preview the change without applying it.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member remove-level 42 --level=2
	 *
	 * @when after_wp_load
	 */
	public function member_remove_level( $args, $assoc_args ) {
		$user_id  = $this->require_user_id( isset( $args[0] ) ? $args[0] : '' );
		$user     = $this->require_existing_user( $user_id );
		$level_id = $this->require_level_id( $assoc_args, true );

		$description = sprintf( 'Remove level #%d from %s (#%d)?', $level_id, $user->user_login, $user_id );
		if ( ! $this->confirm_or_dry_run( $description, $assoc_args ) ) {
			return;
		}

		$this->remove_membership_level( $user_id, $level_id );
	}

	/**
	 * Add a membership level (legacy name for add-level).
	 *
	 * Prefer `wp pmpro member add-level`. `--level=0` still cancels all levels.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID.
	 *
	 * --level=<id>
	 * : The membership level ID to add. Use 0 to cancel all of the member's levels.
	 *
	 * [--dry-run]
	 * : Preview the change without applying it.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member change-level 42 --level=2
	 *     wp pmpro member change-level 42 --level=0 --yes
	 *
	 * @when after_wp_load
	 */
	public function member_change_level( $args, $assoc_args ) {
		$user_id = $this->require_user_id( isset( $args[0] ) ? $args[0] : '' );
		$user    = $this->require_existing_user( $user_id );
		if ( ! isset( $assoc_args['level'] ) ) {
			WP_CLI::error( 'The --level=<id> argument is required.' );
		}
		if ( ! preg_match( '/^\d+$/', (string) $assoc_args['level'] ) ) {
			WP_CLI::error( 'The --level argument must be 0 or a positive membership level ID.' );
		}

		$level_id = (int) $assoc_args['level'];
		if ( $level_id > 0 ) {
			$level = pmpro_getLevel( $level_id );
			if ( empty( $level ) ) {
				WP_CLI::error( sprintf( 'Membership level %d not found.', $level_id ) );
			}
			$description = sprintf( 'Add level "%s" (#%d) to %s (#%d)?', $level->name, $level_id, $user->user_login, $user_id );
		} else {
			$description = sprintf( 'Remove ALL memberships for %s (#%d)?', $user->user_login, $user_id );
		}

		if ( ! $this->confirm_or_dry_run( $description, $assoc_args ) ) {
			return;
		}

		if ( $level_id > 0 ) {
			$this->add_membership_level( $user_id, $level_id );
		} else {
			$this->remove_all_membership_levels( $user_id );
		}
	}

	/**
	 * Cancel a member's membership (legacy name for remove-level).
	 *
	 * Prefer `wp pmpro member remove-level` when removing one level.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID.
	 *
	 * [--level=<id>]
	 * : A specific membership level ID to remove. Omit to remove all of the member's levels.
	 *
	 * [--dry-run]
	 * : Preview the cancellation without applying it.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member cancel 42
	 *     wp pmpro member cancel 42 --level=2
	 *
	 * @when after_wp_load
	 */
	public function member_cancel( $args, $assoc_args ) {
		$user_id = $this->require_user_id( isset( $args[0] ) ? $args[0] : '' );
		$user    = $this->require_existing_user( $user_id );

		$level_id = 0;
		if ( isset( $assoc_args['level'] ) ) {
			$level_id = $this->require_level_id( $assoc_args, true );
		}
		if ( $level_id > 0 ) {
			$description = sprintf( 'Remove level #%d from %s (#%d)?', $level_id, $user->user_login, $user_id );
		} else {
			$description = sprintf( 'Remove ALL memberships for %s (#%d)?', $user->user_login, $user_id );
		}

		if ( ! $this->confirm_or_dry_run( $description, $assoc_args ) ) {
			return;
		}

		if ( $level_id > 0 ) {
			$this->remove_membership_level( $user_id, $level_id );
		} else {
			$this->remove_all_membership_levels( $user_id );
		}
	}

	/**
	 * List membership levels.
	 *
	 * ## OPTIONS
	 *
	 * [--include-hidden]
	 * : Include levels that are not shown on the levels page.
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
	 *     wp pmpro level list
	 *
	 * @when after_wp_load
	 */
	public function level_list( $args, $assoc_args ) {
		$items = array();
		foreach ( (array) pmpro_getAllLevels( ! empty( $assoc_args['include-hidden'] ), true ) as $level ) {
			$items[] = $this->format_level( $level );
		}

		$this->output_items( $items, array( 'id', 'name', 'initial_payment', 'billing_amount', 'cycle_number', 'cycle_period', 'billing_limit', 'expiration_number', 'expiration_period', 'allow_signups' ), $assoc_args );
	}

	/**
	 * Get a single membership level.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The membership level ID.
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
	 *     wp pmpro level get 2
	 *
	 * @when after_wp_load
	 */
	public function level_get( $args, $assoc_args ) {
		$level = pmpro_getLevel( (int) $args[0] );
		if ( empty( $level ) ) {
			WP_CLI::error( sprintf( 'Membership level %d not found.', (int) $args[0] ) );
		}

		$item      = $this->format_level( $level );
		$formatter = new \WP_CLI\Formatter( $assoc_args, array_keys( $item ) );
		$formatter->display_item( $item );
	}

	/**
	 * List orders.
	 *
	 * ## OPTIONS
	 *
	 * [--user_id=<id>]
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
	 *     wp pmpro order list --user_id=42
	 *
	 * @when after_wp_load
	 */
	public function order_list( $args, $assoc_args ) {
		$number = $this->list_limit( $assoc_args );
		$page   = isset( $assoc_args['page'] ) ? max( 1, (int) $assoc_args['page'] ) : 1;

		$query = array(
			'limit'  => $number,
			'offset' => ( $page - 1 ) * $number,
		);

		if ( ! empty( $assoc_args['user_id'] ) ) {
			$query['user_id'] = (int) $assoc_args['user_id'];
		}
		if ( ! empty( $assoc_args['level'] ) ) {
			$query['membership_level_id'] = array_map( 'intval', explode( ',', $assoc_args['level'] ) );
		}
		if ( ! empty( $assoc_args['status'] ) ) {
			$query['status'] = array_map( 'trim', explode( ',', $assoc_args['status'] ) );
		}
		if ( ! empty( $assoc_args['gateway'] ) ) {
			$query['gateway'] = (string) $assoc_args['gateway'];
		}

		$items = array();
		foreach ( (array) MemberOrder::get_orders( $query ) as $order ) {
			$items[] = $this->format_order( $order );
		}

		$this->output_items( $items, array( 'id', 'code', 'user_id', 'membership_id', 'status', 'gateway', 'total', 'timestamp' ), $assoc_args );
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
	 * @when after_wp_load
	 */
	public function order_get( $args, $assoc_args ) {
		$identifier = $args[0];
		$order      = new MemberOrder( $identifier );

		if ( empty( $order->id ) ) {
			WP_CLI::error( sprintf( 'Order "%s" not found.', $identifier ) );
		}

		$item      = $this->format_order( $order );
		$formatter = new \WP_CLI\Formatter( $assoc_args, array_keys( $item ) );
		$formatter->display_item( $item );
	}

	/**
	 * List subscriptions.
	 *
	 * ## OPTIONS
	 *
	 * [--user_id=<id>]
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
	 *     wp pmpro subscription list --user_id=42
	 *
	 * @when after_wp_load
	 */
	public function subscription_list( $args, $assoc_args ) {
		$number = $this->list_limit( $assoc_args );
		$page   = isset( $assoc_args['page'] ) ? max( 1, (int) $assoc_args['page'] ) : 1;

		$query = array(
			'limit'  => $number,
			'offset' => ( $page - 1 ) * $number,
		);

		if ( ! empty( $assoc_args['user_id'] ) ) {
			$query['user_id'] = (int) $assoc_args['user_id'];
		}
		if ( ! empty( $assoc_args['level'] ) ) {
			$query['membership_level_id'] = array_map( 'intval', explode( ',', $assoc_args['level'] ) );
		}
		if ( ! empty( $assoc_args['status'] ) ) {
			$query['status'] = array_map( 'trim', explode( ',', $assoc_args['status'] ) );
		}
		if ( ! empty( $assoc_args['gateway'] ) ) {
			$query['gateway'] = (string) $assoc_args['gateway'];
		}

		$items = array();
		foreach ( (array) PMPro_Subscription::get_subscriptions( $query ) as $subscription ) {
			$items[] = $this->format_subscription( $subscription );
		}

		$this->output_items( $items, array( 'id', 'user_id', 'membership_level_id', 'status', 'gateway', 'billing_amount', 'cycle_number', 'cycle_period', 'startdate', 'next_payment_date' ), $assoc_args );
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
	 * @when after_wp_load
	 */
	public function subscription_get( $args, $assoc_args ) {
		$subscription = PMPro_Subscription::get_subscription( (int) $args[0] );
		if ( empty( $subscription ) ) {
			WP_CLI::error( sprintf( 'Subscription %d not found.', (int) $args[0] ) );
		}

		$item      = $this->format_subscription( $subscription );
		$formatter = new \WP_CLI\Formatter( $assoc_args, array_keys( $item ) );
		$formatter->display_item( $item );
	}

	/**
	 * Sync one or more subscriptions with their gateway.
	 *
	 * Pulls the latest subscription info from the gateway and saves it locally.
	 * Sync errors are stored in subscription meta (sync_error).
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more subscription IDs or gateway transaction IDs (e.g. sub_XXXX).
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
	 * @when after_wp_load
	 */
	public function subscription_sync( $args, $assoc_args ) {
		$subscriptions = array();
		foreach ( $args as $arg ) {
			if ( preg_match( '/^\d+$/', (string) $arg ) ) {
				$subscription = PMPro_Subscription::get_subscription( (int) $arg );
			} else {
				$matches = PMPro_Subscription::get_subscriptions(
					array(
						'subscription_transaction_id' => (string) $arg,
						'limit'                       => 2,
					)
				);
				if ( count( $matches ) > 1 ) {
					WP_CLI::error( sprintf( 'Multiple subscriptions found for transaction ID %s. Use the local subscription ID.', $arg ) );
				}
				$subscription = $matches ? reset( $matches ) : null;
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
					WP_CLI::log( sprintf( 'Synced subscription %d (status: %s, next payment: %s).', $id, $subscription->get_status(), $subscription->get_next_payment_date( 'Y-m-d H:i:s' ) ) );
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
	 * Output a list of items using WP-CLI's formatter.
	 *
	 * @param array $items      Rows as associative arrays.
	 * @param array $fields     Default columns.
	 * @param array $assoc_args Command flags, including --format and --fields.
	 */
	private function output_items( $items, $fields, $assoc_args ) {
		if ( isset( $assoc_args['format'] ) && 'count' === $assoc_args['format'] ) {
			WP_CLI::line( (string) count( $items ) );
			return;
		}

		$formatter = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_items( $items );
	}

	/**
	 * Require a positive integer user ID.
	 *
	 * @param mixed $value Raw positional argument.
	 * @return int
	 */
	private function require_user_id( $value ) {
		if ( ! preg_match( '/^\d+$/', (string) $value ) || (int) $value < 1 ) {
			WP_CLI::error( 'A valid user ID is required.' );
		}
		return (int) $value;
	}

	/**
	 * Require an existing WP user.
	 *
	 * @param int $user_id User ID.
	 * @return WP_User
	 */
	private function require_existing_user( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			WP_CLI::error( sprintf( 'User %d not found.', $user_id ) );
		}
		return $user;
	}

	/**
	 * Require --level as a positive integer.
	 *
	 * @param array $assoc_args Command flags.
	 * @param bool  $required   Whether the flag must be present.
	 * @return int
	 */
	private function require_level_id( $assoc_args, $required = true ) {
		if ( ! isset( $assoc_args['level'] ) ) {
			if ( $required ) {
				WP_CLI::error( 'The --level=<id> argument is required.' );
			}
			return 0;
		}
		if ( ! preg_match( '/^\d+$/', (string) $assoc_args['level'] ) || (int) $assoc_args['level'] < 1 ) {
			WP_CLI::error( 'The --level argument must be a positive membership level ID.' );
		}
		return (int) $assoc_args['level'];
	}

	/**
	 * Confirm unless --dry-run or --yes.
	 *
	 * @param string $description Confirmation text.
	 * @param array  $assoc_args  Command flags.
	 * @return bool False when this is a dry run (caller should return).
	 */
	private function confirm_or_dry_run( $description, $assoc_args ) {
		if ( ! empty( $assoc_args['dry-run'] ) ) {
			WP_CLI::log( '[dry-run] Would ' . lcfirst( $description ) );
			return false;
		}
		WP_CLI::confirm( $description, $assoc_args );
		return true;
	}

	/**
	 * Add a level via pmpro_changeMembershipLevel().
	 *
	 * @param int $user_id  User ID.
	 * @param int $level_id Level ID.
	 */
	private function add_membership_level( $user_id, $level_id ) {
		$result = pmpro_changeMembershipLevel( $level_id, $user_id, 'admin_changed' );
		if ( false === $result ) {
			WP_CLI::error( sprintf( 'Failed to add level %d for user %d.', $level_id, $user_id ) );
		}
		if ( null === $result ) {
			WP_CLI::success( sprintf( 'User %d already has level %d.', $user_id, $level_id ) );
			return;
		}
		WP_CLI::success( sprintf( 'Added level %d for user %d.', $level_id, $user_id ) );
	}

	/**
	 * Remove one level via pmpro_cancelMembershipLevel().
	 *
	 * @param int $user_id  User ID.
	 * @param int $level_id Level ID.
	 */
	private function remove_membership_level( $user_id, $level_id ) {
		if ( ! pmpro_cancelMembershipLevel( $level_id, $user_id, 'admin_cancelled' ) ) {
			WP_CLI::error( sprintf( 'Failed to remove level %d for user %d.', $level_id, $user_id ) );
		}
		WP_CLI::success( sprintf( 'Removed level %d for user %d.', $level_id, $user_id ) );
	}

	/**
	 * Remove every active level for a user.
	 *
	 * @param int $user_id User ID.
	 */
	private function remove_all_membership_levels( $user_id ) {
		$levels = (array) pmpro_getMembershipLevelsForUser( $user_id );
		if ( empty( $levels ) ) {
			WP_CLI::success( sprintf( 'User %d has no active memberships.', $user_id ) );
			return;
		}
		foreach ( $levels as $level ) {
			if ( ! pmpro_cancelMembershipLevel( $level->id, $user_id, 'admin_cancelled' ) ) {
				WP_CLI::error( sprintf( 'Failed to remove level %d for user %d.', $level->id, $user_id ) );
			}
		}
		WP_CLI::success( sprintf( 'Removed all memberships for user %d.', $user_id ) );
	}

	/**
	 * Positive --number for list commands. Default 100.
	 *
	 * @param array $assoc_args Command flags.
	 * @return int
	 */
	private function list_limit( $assoc_args ) {
		if ( ! isset( $assoc_args['number'] ) ) {
			return 100;
		}
		if ( ! preg_match( '/^\d+$/', (string) $assoc_args['number'] ) || (int) $assoc_args['number'] < 1 ) {
			WP_CLI::error( 'The --number argument must be a positive integer.' );
		}
		return (int) $assoc_args['number'];
	}

	/**
	 * Columns for a membership level.
	 *
	 * @param object $level Level object from pmpro_getLevel() / pmpro_getAllLevels().
	 * @return array
	 */
	private function format_level( $level ) {
		return array(
			'id'                => (int) $level->id,
			'name'              => $level->name,
			'description'       => isset( $level->description ) ? wp_strip_all_tags( $level->description ) : '',
			'initial_payment'   => $level->initial_payment,
			'billing_amount'    => $level->billing_amount,
			'cycle_number'      => (int) $level->cycle_number,
			'cycle_period'      => $level->cycle_period,
			'billing_limit'     => (int) $level->billing_limit,
			'trial_amount'      => $level->trial_amount,
			'trial_limit'       => (int) $level->trial_limit,
			'expiration_number' => (int) $level->expiration_number,
			'expiration_period' => $level->expiration_period,
			'allow_signups'     => (int) $level->allow_signups,
		);
	}

	/**
	 * Columns for an order. Deliberately omits billing address and card data.
	 *
	 * @param MemberOrder $order Order object.
	 * @return array
	 */
	private function format_order( $order ) {
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

	/**
	 * Columns for a subscription.
	 *
	 * @param PMPro_Subscription $subscription Subscription object.
	 * @return array
	 */
	private function format_subscription( $subscription ) {
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

$pmpro_cli = new PMPro_CLI();
WP_CLI::add_command( 'pmpro member list', array( $pmpro_cli, 'member_list' ) );
WP_CLI::add_command( 'pmpro member get', array( $pmpro_cli, 'member_get' ) );
WP_CLI::add_command( 'pmpro member add-level', array( $pmpro_cli, 'member_add_level' ) );
WP_CLI::add_command( 'pmpro member remove-level', array( $pmpro_cli, 'member_remove_level' ) );
WP_CLI::add_command( 'pmpro member change-level', array( $pmpro_cli, 'member_change_level' ) );
WP_CLI::add_command( 'pmpro member cancel', array( $pmpro_cli, 'member_cancel' ) );
WP_CLI::add_command( 'pmpro level list', array( $pmpro_cli, 'level_list' ) );
WP_CLI::add_command( 'pmpro level get', array( $pmpro_cli, 'level_get' ) );
WP_CLI::add_command( 'pmpro order list', array( $pmpro_cli, 'order_list' ) );
WP_CLI::add_command( 'pmpro order get', array( $pmpro_cli, 'order_get' ) );
WP_CLI::add_command( 'pmpro subscription list', array( $pmpro_cli, 'subscription_list' ) );
WP_CLI::add_command( 'pmpro subscription get', array( $pmpro_cli, 'subscription_get' ) );
WP_CLI::add_command( 'pmpro subscription sync', array( $pmpro_cli, 'subscription_sync' ) );

/**
 * Fires after PMPro registers its core WP-CLI commands, so Add Ons can register their own.
 *
 * @since TBD
 */
do_action( 'pmpro_cli_init' );
