<?php
/**
 * Add new block category for Restrict With Stripe blocks.
 *
 * @since 1.0
 *
 * @param array $categories Array of block categories.
 * @return array Array of block categories.
 */
function pmpro_block_categories( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'pmpro',
				'title' => esc_html__( 'Paid Memberships Pro', 'paid-memberships-pro' ),
			),
		)
	);
}
add_filter( 'block_categories_all', 'pmpro_block_categories' );

/**
 * Register block types for the block editor.
 */
/*
function rwstripe_register_block_types() {
	register_block_type(
		RWSTRIPE_DIR . '/blocks/build/customer-portal',
		array(
			'render_callback' => 'rwstripe_render_customer_portal_block',
		)
	);
}
add_action( 'init', 'rwstripe_register_block_types' );
*/
