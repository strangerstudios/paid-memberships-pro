<?php
/**
 * Sets up billing-page block, does not format frontend
 *
 * @package blocks/billing-page
 **/

namespace PMPro\Blocks;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// Only load if Gutenberg is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

add_action( 'init', __NAMESPACE__ . '\pmpro_billing_page_register_dynamic_block' );
/**
 * Register the dynamic block.
 *
 * @since 2.1.0
 *
 * @return void
 */
function pmpro_billing_page_register_dynamic_block() {
	// Hook server side rendering into render callback.
	register_block_type( 'pmpro/billing-page', [
		'render_callback' => __NAMESPACE__ . '\pmpro_billing_page_render_dynamic_block',
	] );
}

/**
 * Server rendering for /blocks/examples/12-dynamic
 *
 * @param array $attributes contains text, level, and css_class strings.
 * @return string
 **/
function pmpro_billing_page_render_dynamic_block( $attributes ) {
	return pmpro_loadTemplate( 'billing', 'local', 'pages' );
}
