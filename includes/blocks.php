<?php
/**
 * Add new block category for Paid Memberships Pro blocks.
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
			array(
				'slug' => 'pmpro-pages',
				'title' => esc_html__( 'Paid Memberships Pro Pages', 'paid-memberships-pro' ),
			),
		)
	);
}
add_filter( 'block_categories_all', 'pmpro_block_categories' );

/**
 * Register block types for the block editor.
 */
function pmpro_register_block_types() {
	register_block_type(
		PMPRO_DIR . '/blocks/build/account-invoices-section'
	);
}
add_action( 'init', 'pmpro_register_block_types' );

