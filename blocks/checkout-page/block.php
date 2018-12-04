<?php
/**
 * Sets up checkout-page block, does not format frontend
 *
 * @package blocks/checkout-page
 **/

namespace PMPro\blocks\checkout_page;

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
	register_block_type( 'pmpro/checkout-page', [
		'render_callback' => __NAMESPACE__ . '\render_dynamic_block',
	] );
}

/**
 * Server rendering for checkout-page block.
 *
 * @param array $attributes contains level.
 * @return string
 **/
function render_dynamic_block( $attributes ) {
	/*$atts = '';
	if ( ! empty( $attributes['level'] ) ) {
		$atts = [ 'level' => intval( $attributes['level'] ) ];
	}*/
	// TO DO: Apply the specificed Level ID to the checkout page. 
	// d ( $atts );
	return pmpro_loadTemplate( 'checkout', 'local', 'pages' );
}
