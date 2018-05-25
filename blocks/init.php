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
//require_once PMPRO_DIR . '/blocks/member/index.php';
//require_once PMPRO_DIR . '/blocks/membership/index.php';

/**
 * Enqueue block editor only JavaScript
 */
function pmpro_block_editor_scripts() {

	// Make paths variables so we don't write em twice.
	$block_path = '/../js/editor.blocks.js';

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'pmpro-blocks-js',
		plugins_url( $block_path, __FILE__ ),
		[ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ],
		filemtime( plugin_dir_path( __FILE__ ) . $block_path )
	);

}

// Hook scripts function into block editor hook.
add_action( 'enqueue_block_editor_assets', 'pmpro_block_editor_scripts' );


/**
 * Enqueue front end and editor JavaScript
 */
function pmpro_block_scripts() {
	$block_path = '/../js/frontend.blocks.js';

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'pmpro-blocks-frontend-js',
		plugins_url( $block_path, __FILE__ ),
		[ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ],
		filemtime( plugin_dir_path( __FILE__ ) . $block_path )
	);

}

// Hook scripts function into block editor hook.
add_action( 'enqueue_block_assets', 'pmpro_block_scripts' );
