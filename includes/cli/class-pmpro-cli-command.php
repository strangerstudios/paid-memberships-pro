<?php
/**
 * Base class for PMPro WP-CLI commands.
 *
 * @since TBD
 * @package PaidMembershipsPro\CLI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for PMPro WP-CLI commands.
 *
 * @since TBD
 */
abstract class PMPro_CLI_Command {

	/**
	 * Output a set of associative-array items using the requested WP-CLI format.
	 *
	 * Honors --format ( table, csv, json, yaml, ids, count ) and --fields.
	 *
	 * @param array $items  The items to output (array of associative arrays).
	 * @param array $fields The default columns to display.
	 * @param array $assoc_args The command's associative arguments.
	 */
	protected function output_items( $items, $fields, $assoc_args ) {
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		if ( 'count' === $format ) {
			WP_CLI::line( (string) count( $items ) );
			return;
		}

		$formatter = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_items( $items );
	}

	/**
	 * Read an integer flag from the associative arguments.
	 *
	 * @param array  $assoc_args The associative arguments.
	 * @param string $key        The flag name.
	 * @param int    $default    The default value.
	 * @return int
	 */
	protected function int_arg( $assoc_args, $key, $default = 0 ) {
		return isset( $assoc_args[ $key ] ) ? (int) $assoc_args[ $key ] : $default;
	}

	/**
	 * Split a comma-separated flag into an array of trimmed values.
	 *
	 * @param array  $assoc_args The associative arguments.
	 * @param string $key        The flag name.
	 * @return array
	 */
	protected function list_arg( $assoc_args, $key ) {
		if ( empty( $assoc_args[ $key ] ) ) {
			return array();
		}
		return array_filter( array_map( 'trim', explode( ',', (string) $assoc_args[ $key ] ) ) );
	}
}
