<?php
/**
 * `wp pmpro level` commands.
 *
 * @since TBD
 * @package PaidMembershipsPro\CLI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage PMPro membership levels.
 *
 * @since TBD
 */
class PMPro_CLI_Level extends PMPro_CLI_Command {

	/**
	 * Default columns shown for a level.
	 *
	 * @var array
	 */
	private $default_fields = array( 'id', 'name', 'initial_payment', 'billing_amount', 'cycle_number', 'cycle_period', 'billing_limit', 'expiration_number', 'expiration_period', 'allow_signups' );

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
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$include_hidden = ! empty( $assoc_args['include-hidden'] );
		$levels         = pmpro_getAllLevels( $include_hidden, true );

		$items = array();
		foreach ( (array) $levels as $level ) {
			$items[] = $this->normalize_level( $level );
		}

		$this->output_items( $items, $this->default_fields, $assoc_args );
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
	 * @subcommand get
	 */
	public function get( $args, $assoc_args ) {
		$level = pmpro_getLevel( (int) $args[0] );
		if ( empty( $level ) ) {
			WP_CLI::error( sprintf( 'Membership level %d not found.', (int) $args[0] ) );
		}

		$item   = $this->normalize_level( $level );
		$fields = ! empty( $assoc_args['fields'] ) ? $this->list_arg( $assoc_args, 'fields' ) : array_keys( $item );
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		$assoc_args['format'] = $format;
		$formatter            = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_item( $item );
	}

	/**
	 * Normalize a level object to an associative array.
	 *
	 * @param object $level The level object.
	 * @return array
	 */
	private function normalize_level( $level ) {
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
}
