<?php

defined( 'ABSPATH' ) || exit;

/* NOTE: Maybe move this into includes/blocks.php or /blocks/blocks.php */

function pmpro_block_checkout_button_enqueue_block_editor_assets() {
	wp_enqueue_script(
		'pmpro-block-checkout-button',
		plugins_url( 'blocks/checkout-button/block.js', PMPRO_BASE_FILE ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'block.js' )
	);

	wp_enqueue_style(
		'pmpro-block-checkout-button',
		plugins_url( 'blocks/checkout-button/editor.css', PMPRO_BASE_FILE ),
		array( 'wp-edit-blocks' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'editor.css' )
	);
}
add_action( 'enqueue_block_editor_assets', 'pmpro_block_checkout_button_enqueue_block_editor_assets' );

function pmpro_block_checkout_button_enqueue_block_assets() {
	wp_enqueue_style(
		'pmpro-block-checkout-button',
		plugins_url( 'blocks/checkout-button/style.css', PMPRO_BASE_FILE ),
		array( 'wp-blocks' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'style.css' )
	);
}
add_action( 'enqueue_block_assets', 'pmpro_block_checkout_button_enqueue_block_assets' );
