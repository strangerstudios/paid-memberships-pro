<?php
/**
 * Sets up singlelevel block, does not format frontend
 *
 * @package blocks/singlelevel
 **/

namespace PMPro\blocks\singlelevelprice;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// Only load if Gutenberg is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

add_action( 'init', __NAMESPACE__ . '\register_dynamic_block' );
/**
 * Register the dynamic block.
 *
 * @since 2.1.0
 *
 * @return void
 */
function register_dynamic_block() {

	// Hook server side rendering into render callback.
	register_block_type( 'pmpro/single-level-price', [
		'render_callback' => __NAMESPACE__ . '\render_dynamic_block',
	] );
}

/**
 * Server rendering for singlelevel block.
 *
 * @param array $attributes contains text, level, and css_class strings.
 * @return string
 **/
function render_dynamic_block( $attributes, $content ) {
	    
    global $pmpro_levels;

    $selected_level = ( ! empty( $attributes['selected_level'] ) ) ? intval( $attributes['selected_level'] ) : 0;

    if( $selected_level === 0 ) {
        return;
    }

    if( ! empty( $pmpro_levels[$selected_level] ) ) {

        return trim( pmpro_no_quotes( pmpro_getLevelCost( $pmpro_levels[$selected_level], array( '"', "'", "\n", "\r" ) ) ) );

    }

}
