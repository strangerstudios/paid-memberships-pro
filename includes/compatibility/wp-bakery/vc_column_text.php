<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $el_class
 * @var $el_id
 * @var $css_animation
 * @var $css
 * @var $content - shortcode content
 * Shortcode class
 * @var WPBakeryShortCode_Vc_Column_text $this
 */
$el_class = $el_id = $css = $css_animation = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
extract( $atts );

$class_to_filter = 'wpb_text_column wpb_content_element ' . $this->getCSSAnimation( $css_animation );
$class_to_filter .= vc_shortcode_custom_css_class( $css, ' ' ) . $this->getExtraClass( $el_class );
$css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $class_to_filter, $this->settings['base'], $atts );
$wrapper_attributes = array();
if ( ! empty( $el_id ) ) {
	$wrapper_attributes[] = 'id="' . esc_attr( $el_id ) . '"';
}
$output = '
	<div class="' . esc_attr( $css_class ) . '" ' . implode( ' ', $wrapper_attributes ) . '>
		<div class="wpb_wrapper">
			' . wpb_js_remove_wpautop( $content, true ) . '
		</div>
	</div>
';

/**
 * Paid Memberships Pro will check to see if any levels are required, and show/hide the content as expected.
 */
if( ! empty( $atts['pmpro_levels'] ) ) {

	$protected_content = "";

	$restricted_levels = $atts['pmpro_levels'];
	$show_no_access_message = $atts['pmpro_no_access_message'];

	// Just bail if the content isn't restricted at all.
	if ( ! $restricted_levels ) {
		$protected_content = $content;
	}

	$levels_array = explode( ",", $restricted_levels );
	
	if ( ! pmpro_hasMembershipLevel( $levels_array ) ) {
		$access = false;
	} else {
		$access = true;
	}

	$access = apply_filters( 'pmpro_wpbakery_has_access', $access, $content, $restricted_levels, $atts );

	if ( ! $access ) {
		// Show no content message here or not
		if ( $show_no_access_message === 'Yes' ) {
			$protected_content = pmpro_get_no_access_message( NULL, $restricted_levels );
		}
	} else {
		$protected_content = $content;
	}

}

echo $protected_content;
