<?php
/**
 * Render the Level Price on the frontend.
 */

// Get the level cost.
$level = pmpro_getLevel( $attributes['selected_membership_level'] );
$level_cost = pmpro_getLevelCost($level, true, true); 

// Return if level cost is empty.
if ( empty( $level_cost ) ) {
	return;
}

// Set tag name.
$tag_name = 'div';

// Get the additional block classes to add to wrapper attributes.
$classes = array();
if ( isset( $attributes['textAlign'] ) ) {
	$classes[] = 'has-text-align-' . $attributes['textAlign'];
}
if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
	$classes[] = 'has-link-color';
}

// Get the wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

// Echo the complete block with level cost.
echo sprintf(
	'<%1$s %2$s>%3$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_kses_post( $level_cost )
);
