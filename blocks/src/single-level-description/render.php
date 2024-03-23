<?php
/**
 * Render the Level Description on the frontend.
 */

// Get the level description.
$level = pmpro_getLevel( $attributes['selected_membership_level'] );
$level_description = apply_filters( 'pmpro_level_description', $level->description, $level );
$level_description = wp_kses_post( $level_description );

// Return if level description is empty.
if ( empty( $level_description ) ) {
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

// Echo the complete block with level description.
echo sprintf(
	'<%1$s %2$s>%3$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_kses_post( $level_description )
);
