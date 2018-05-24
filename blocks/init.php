<?php
/**
 * Enqueues blocks in editor and dynamic blocks
 *
 * @package blocks
 */

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/* NOTE: Maybe move this into includes/blocks.php or /blocks/blocks.php */

/**
 * Dynamic Block Requires
 */
require_once PMPRO_DIR . '/blocks/checkout-button/index.php';
require_once PMPRO_DIR . '/blocks/account-page/index.php';
require_once PMPRO_DIR . '/blocks/billing-page/index.php';
require_once PMPRO_DIR . '/blocks/cancel-page/index.php';
require_once PMPRO_DIR . '/blocks/checkout-page/index.php';
require_once PMPRO_DIR . '/blocks/confirmation-page/index.php';
require_once PMPRO_DIR . '/blocks/invoice-page/index.php';
require_once PMPRO_DIR . '/blocks/levels-page/index.php';

/**
 * Enqueue block editor only JavaScript and CSS
 */
function pmpro_block_editor_scripts() {

	// Make paths variables so we don't write em twice.
	$block_path        = '/../js/editor.blocks.js';
	$editor_style_path = '/../css/blocks.editor.css';

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'pmpro-blocks-js',
		plugins_url( $block_path, __FILE__ ),
		[ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ],
		filemtime( plugin_dir_path( __FILE__ ) . $block_path )
	);

	// Enqueue optional editor only styles.
	wp_enqueue_style(
		'pmpro-editor-css',
		plugins_url( $editor_style_path, __FILE__ ),
		[ 'wp-blocks' ],
		filemtime( plugin_dir_path( __FILE__ ) . $editor_style_path )
	);

}

// Hook scripts function into block editor hook.
add_action( 'enqueue_block_editor_assets', 'pmpro_block_editor_scripts' );


/**
 * Enqueue front end and editor JavaScript and CSS
 */
function pmpro_block_scripts() {
	$block_path = '/../js/frontend.blocks.js';
	$style_path = '/../css/blocks.style.css';

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'pmpro-blocks-frontend-js',
		plugins_url( $block_path, __FILE__ ),
		[ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ],
		filemtime( plugin_dir_path( __FILE__ ) . $block_path )
	);

	// Enqueue frontend and editor block styles.
	wp_enqueue_style(
		'pmpro-blocks-css',
		plugins_url( $style_path, __FILE__ ),
		[ 'wp-blocks' ],
		filemtime( plugin_dir_path( __FILE__ ) . $style_path )
	);

}

// Hook scripts function into block editor hook.
add_action( 'enqueue_block_assets', 'pmpro_block_scripts' );
