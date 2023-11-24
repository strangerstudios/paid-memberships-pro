<?php 

/**
* Render the block content on the frontend based on content visibility attributes.
*
* @param array $attributes The block attributes.
* @param array  $content The block content.
* @return string the filtered output
* @since TBD
*/
function pmpro_filter_block_content( $attributes, $content ) {
	$output = '';

	if ( 'all' === $attributes['segment'] && ! empty( $attributes['levels'] ) ) {
		// Legacy setup for PMPro < 3.0.
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
	} else {
		// Setup for PMPro >= 3.0.
		switch ( $attributes['segment'] ) {
			case 'all':
				$levels_to_check = $attributes['invert_restrictions'] == '0' ? null : '0';
				break;
			case 'specific':
				// If inverting restrictions, we need to make all level IDs negative.
				$levels_to_check = array_map( function( $level ) use ( $attributes ) {
					return $attributes['invert_restrictions'] == '0' ? $level : '-' . $level;
				}, $attributes['levels'] );
				break;
			case 'logged_in	':
				$levels_to_check = $attributes['invert_restrictions'] == '0' ? 'L' : '-L';
				break;
		}

		if ( pmpro_hasMembershipLevel( $levels_to_check ) ) {
			$output = do_blocks( $content );
		} elseif ( ! empty( $attributes['show_noaccess'] ) && $attributes['invert_restrictions'] == '0' ) {
			$output = pmpro_get_no_access_message( NULL, $attributes['levels'] );
		}
	}
	return $output;
}
