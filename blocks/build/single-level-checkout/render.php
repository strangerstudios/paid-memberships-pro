<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$text      = 'Buy Now';
$level     = null;
$css_class = 'pmpro_btn';

if ( ! empty( $attributes['selected_level'] ) ) {
	$level = $attributes['selected_level'];
} else {
	$level = null;
}

$text = __( 'Buy Now', 'paid-memberships-pro' );

$output = ( "<span class=\"" . pmpro_get_element_class( 'span_pmpro_checkout_button' ) . "\">" . pmpro_getCheckoutButton( $level, $text, $css_class ) . "</span>" );
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</p>
