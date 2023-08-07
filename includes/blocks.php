<?php
/**
 * Add new block category for Restrict With Stripe blocks.
 *
 * @since 1.0
 *
 * @param array $categories Array of block categories.
 * @return array Array of block categories.
 */
function rwstripe_block_categories( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'rwstripe',
				'title' => esc_html__( 'Restrict With Stripe', 'restrict-with-stripe' ),
			),
		)
	);
}
add_filter( 'block_categories_all', 'rwstripe_block_categories' );

/**
 * Register block types for the block editor.
 */
function rwstripe_register_block_types() {
	register_block_type(
		RWSTRIPE_DIR . '/blocks/build/customer-portal',
		array(
			'render_callback' => 'rwstripe_render_customer_portal_block',
		)
	);
}
add_action( 'init', 'rwstripe_register_block_types' );

/**
 * Render the customer portal block. Also used as the render callback for
 * the customer portal shortcode.
 *
 * @since 1.0
 *
 * @return string HTML for link to Stripe Customer Portal.
 */
function rwstripe_render_customer_portal_block() {
	$content_pre = '<div class="rwstripe_customer_portal_shortcode">';
	$content_post = '</div>';

	if ( is_user_logged_in() ) {
		$content = '<button type="button" class="rwstripe-customer-portal-button">' . esc_html__( 'Manage Purchases', 'restrict-with-stripe' ) . '</button>';
	} else {
		$content = '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Please log in to manage your purchases.', 'restrict-with-stripe' ) . '</a>';
	}

	return $content_pre . $content . $content_post;
}
add_shortcode( 'rwstripe_customer_portal', 'rwstripe_render_customer_portal_block' );