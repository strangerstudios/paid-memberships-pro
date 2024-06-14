<?php
/**
 * Render the Level Name on the frontend.
 */
// Return if level name is empty.
if ( empty( $content ) ) {
	return;
}

// Set tag name.
$tag_name = 'h2';
if ( isset( $attributes['level'] ) ) {
	$tag_name = 'h' . $attributes['level'];
}

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

// Echo the complete block with level name.
echo sprintf(
	'<%1$s %2$s>%3$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html( $content )
);
