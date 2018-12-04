<?php
/**
 * Deprecated hooks, filters and functions
 *
 * @since  2.0
 */

/**
 * Handle renamed hooks
 */
global $pmpro_map_deprecated_hooks;

$pmpro_map_deprecated_hooks = array(
	'pmpro_getfile_extension_blocklist'    => 'pmpro_getfile_extension_blacklist',
);

// anonymous function used below is only supported in php 5.3+
foreach ( $pmpro_map_deprecated_hooks as $new => $old ) {
	// assumes hooks with no parameters
	if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
		// Using anonmyous functions for PHP 5.3+
		$func = function() use ( $new, $old ) {
			pmpro_maybe_show_deprecated_hook_message( $new, $old );
		};
	} else {
		// Using create_function for PHP 5.2
		$func = create_function( '', "pmpro_maybe_show_deprecated_hook_message( '$new', '$old' );" );
	}
	add_action( $new, $func );
}

function pmpro_maybe_show_deprecated_hook_message( $new, $old ) {
	global $wp_filter;
	if ( has_filter( $old ) ) {
		/* translators: 1: the old hook name, 2: the new or replacement hook name */
		trigger_error( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro. Please use the %2$s hook instead.', 'paid-memberships-pro' ), $old, $new ) );
		
		foreach( $wp_filter[$old]->callbacks as $priority => $callbacks ) {
			foreach( $callbacks as $callback ) {
				add_filter( $new, $callback['function'], $priority, $callback['accepted_args'] ); 
			}
		}
	}
}
