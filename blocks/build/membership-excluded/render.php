<?php
/**
 * Render the Membership Excluded block on the frontend.
 */
$output = '';
if ( ! array_key_exists( 'levels', $attributes ) || empty( $attributes['levels'] ) ) {
	$output = do_blocks( $content );
} else {
	if ( pmpro_hasMembershipLevel( $attributes['levels'] ) ) {
		if ( ! empty( $attributes['show_noaccess'] ) ) {
			$output = pmpro_get_no_access_message( NULL, $attributes['levels'] );
		}
	} else {
		$output = do_blocks( $content );
	}
}
echo $output;
