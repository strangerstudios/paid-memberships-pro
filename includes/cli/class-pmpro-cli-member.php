<?php
/**
 * `wp pmpro member` commands.
 *
 * @since TBD
 * @package PaidMembershipsPro\CLI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage PMPro members.
 *
 * @since TBD
 */
class PMPro_CLI_Member extends PMPro_CLI_Command {

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
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member list --level=2 --status=active
	 *     wp pmpro member list --search=jane --format=json
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

		$levels = $this->list_arg( $assoc_args, 'level' );
		if ( $levels ) {
			$query['membership_id'] = array_map( 'intval', $levels );
		}

		$statuses = $this->list_arg( $assoc_args, 'status' );
		if ( $statuses ) {
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

		$members = pmpro_get_members( $query );

		$items = array();
		foreach ( (array) $members as $member ) {
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

		$this->output_items(
			$items,
			array( 'user_id', 'user_login', 'user_email', 'display_name', 'membership_id', 'membership_name', 'status', 'startdate', 'enddate' ),
			$assoc_args
		);
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
	 * @subcommand get
	 */
	public function get( $args, $assoc_args ) {
		$user_id = (int) $args[0];
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			WP_CLI::error( sprintf( 'User %d not found.', $user_id ) );
		}

		$include_inactive = ! empty( $assoc_args['include-inactive'] );
		$levels           = pmpro_getMembershipLevelsForUser( $user_id, $include_inactive );

		$items = array();
		foreach ( (array) $levels as $level ) {
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
	 * Change (or grant) a member's membership level.
	 *
	 * ## OPTIONS
	 *
	 * --user=<id>
	 * : The user ID.
	 *
	 * --level=<id>
	 * : The membership level ID to set. Use 0 to cancel all of the member's levels.
	 *
	 * [--dry-run]
	 * : Preview the change without applying it.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member change-level --user=42 --level=2
	 *     wp pmpro member change-level --user=42 --level=0 --yes
	 *
	 * @subcommand change-level
	 */
	public function change_level( $args, $assoc_args ) {
		$user_id = $this->int_arg( $assoc_args, 'user', 0 );
		if ( empty( $user_id ) ) {
			WP_CLI::error( 'The --user=<id> argument is required.' );
		}
		if ( ! isset( $assoc_args['level'] ) ) {
			WP_CLI::error( 'The --level=<id> argument is required.' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			WP_CLI::error( sprintf( 'User %d not found.', $user_id ) );
		}

		$level_id = (int) $assoc_args['level'];
		if ( $level_id > 0 ) {
			$level = pmpro_getLevel( $level_id );
			if ( empty( $level ) ) {
				WP_CLI::error( sprintf( 'Membership level %d not found.', $level_id ) );
			}
			$description = sprintf( 'Change %s (#%d) to level "%s" (#%d)?', $user->user_login, $user_id, $level->name, $level_id );
		} else {
			$description = sprintf( 'Cancel ALL memberships for %s (#%d)?', $user->user_login, $user_id );
		}

		if ( ! empty( $assoc_args['dry-run'] ) ) {
			WP_CLI::log( '[dry-run] Would ' . lcfirst( $description ) );
			return;
		}

		WP_CLI::confirm( $description, $assoc_args );

		$result = pmpro_changeMembershipLevel( $level_id, $user_id );
		if ( $result ) {
			WP_CLI::success( sprintf( 'Updated membership for user %d.', $user_id ) );
		} else {
			WP_CLI::error( sprintf( 'Failed to change membership level for user %d.', $user_id ) );
		}
	}

	/**
	 * Cancel a member's membership.
	 *
	 * ## OPTIONS
	 *
	 * --user=<id>
	 * : The user ID.
	 *
	 * [--level=<id>]
	 * : A specific membership level ID to cancel. Omit to cancel all of the member's levels.
	 *
	 * [--dry-run]
	 * : Preview the cancellation without applying it.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro member cancel --user=42
	 *     wp pmpro member cancel --user=42 --level=2
	 *
	 * @subcommand cancel
	 */
	public function cancel( $args, $assoc_args ) {
		$user_id = $this->int_arg( $assoc_args, 'user', 0 );
		if ( empty( $user_id ) ) {
			WP_CLI::error( 'The --user=<id> argument is required.' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			WP_CLI::error( sprintf( 'User %d not found.', $user_id ) );
		}

		$level_id = isset( $assoc_args['level'] ) ? (int) $assoc_args['level'] : 0;
		if ( $level_id > 0 ) {
			$description = sprintf( 'Cancel level #%d for %s (#%d)?', $level_id, $user->user_login, $user_id );
		} else {
			$description = sprintf( 'Cancel ALL memberships for %s (#%d)?', $user->user_login, $user_id );
		}

		if ( ! empty( $assoc_args['dry-run'] ) ) {
			WP_CLI::log( '[dry-run] Would ' . lcfirst( $description ) );
			return;
		}

		WP_CLI::confirm( $description, $assoc_args );

		if ( $level_id > 0 ) {
			$result = pmpro_cancelMembershipLevel( $level_id, $user_id );
		} else {
			$result = pmpro_changeMembershipLevel( 0, $user_id );
		}

		if ( $result ) {
			WP_CLI::success( sprintf( 'Cancelled membership(s) for user %d.', $user_id ) );
		} else {
			WP_CLI::error( sprintf( 'Failed to cancel membership for user %d.', $user_id ) );
		}
	}
}
