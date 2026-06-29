<?php
/*
	Shortcode to show a link/button linking to the checkout page for a specific level
*/
function pmpro_checkout_button_shortcode($atts, $content=null, $code="")
{
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_checkout_button level="3"]
	//           [pmpro_checkout_button level="3" style="button"]

	extract(shortcode_atts(array(
		'level' => NULL,
		'text' => NULL,
		'class' => NULL,
		'style' => 'link', // 'link' (default) or 'button'.
	), $atts));

	// A custom "class" overrides the base class. Otherwise use the "style" attribute.
	if ( ! empty( $class ) ) {
		$classes = $class;
	} else {
		$classes = ( 'button' === $style ) ? 'pmpro_btn' : 'pmpro_link';
	}

	ob_start(); ?>
	<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro span_pmpro_checkout_button', 'span_pmpro_checkout_button' ) ); ?>">
		<?php
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo pmpro_getCheckoutButton($level, $text, $classes);
		?>
	</span>
 	<?php
 	return ob_get_clean();
}
add_shortcode("pmpro_button", "pmpro_checkout_button_shortcode");
add_shortcode("pmpro_checkout_button", "pmpro_checkout_button_shortcode");
