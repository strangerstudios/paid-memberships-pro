<?php
/**
 * Enqueues blocks in editor and dynamic blocks
 *
 * @package blocks
 */
defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Dynamic Block Requires
 */
require_once( 'checkout-button/block.php' );
require_once( 'account-page/block.php' );
require_once( 'account-membership-section/block.php' );
require_once( 'account-profile-section/block.php' );
require_once( 'account-invoices-section/block.php' );
require_once( 'account-links-section/block.php' );
require_once( 'billing-page/block.php' );
require_once( 'cancel-page/block.php' );
require_once( 'checkout-page/block.php' );
require_once( 'confirmation-page/block.php' );
require_once( 'invoice-page/block.php' );
require_once( 'levels-page/block.php' );
require_once( 'membership/block.php' );

/**
 * Add PMPro block category
 */
function pmpro_place_blocks_in_panel( $categories, $post ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'pmpro',
				'title' => __( 'Paid Memberships Pro', 'paid-memberships-pro' ),
			),
		)
	);
}
add_filter( 'block_categories', 'pmpro_place_blocks_in_panel', 10, 2 );

/**
 * Enqueue block editor only JavaScript and CSS
 */
function pmpro_block_editor_scripts() {
	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'pmpro-blocks-editor-js',
		plugins_url( 'js/editor.blocks.js', PMPRO_BASE_FILE ),
		array('wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api', 'wp-editor', 'pmpro_admin'),
		PMPRO_VERSION
	);

	// Enqueue optional editor only styles.
	wp_enqueue_style(
		'pmpro-blocks-editor-css',
		plugins_url( 'css/blocks.editor.css', PMPRO_BASE_FILE ),
		array(),
		PMPRO_VERSION
	);
}
add_action( 'enqueue_block_editor_assets', 'pmpro_block_editor_scripts' );
