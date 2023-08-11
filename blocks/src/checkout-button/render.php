<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$text  = 'Buy Now';
$level     = null;
$css_class = 'pmpro_btn';

if ( ! empty( $attributes['level'] ) ) {
	$level = $attributes['level'];
} else {
	$level = null;
}

if ( ! empty( $attributes['text'] ) ) {
	$text = $attributes['text'];
} else {
	$text = __( 'Buy Now', 'paid-memberships-pro' );
}

if ( ! empty( $attributes['css_class'] ) ) {
	$css_class = $attributes['css_class'];
} else {
	$css_class = null;
}

$output = ( "<span class=\"" . pmpro_get_element_class( 'span_pmpro_checkout_button' ) . "\">" . pmpro_getCheckoutButton( $level, $text, $css_class ) . "</span>" );
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</p>
