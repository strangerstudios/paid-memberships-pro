<?php
/**
 * Render the Membership Required block on the frontend.
 */
$output = '';
var_dump( $attributes['levels'] );
if ( ! array_key_exists( 'levels', $attributes ) || empty( $attributes['levels'] ) ) {
	// Assume require any membership level, and do not show to non-members.
	if ( pmpro_hasMembershipLevel() ) {
		$output = do_blocks( $content );
	}
} else {
	if ( pmpro_hasMembershipLevel( $attributes['levels'] ) ) {
		$output = do_blocks( $content );
	} elseif ( ! empty( $attributes['show_noaccess'] ) ) {
		$output = pmpro_get_no_access_message( NULL, $attributes['levels'] );
	}
}
echo $output;
