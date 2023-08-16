<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$str_atts = '';
	if ( ! empty( $attributes['membership'] ) ) {
		$str_atts .= 'membership, ';
	}
	if ( ! empty( $attributes['profile'] ) ) {
		$str_atts .= 'profile, ';
	}
	if ( ! empty( $attributes['invoices'] ) ) {
		$str_atts .= 'invoices, ';
	}
	if ( ! empty( $attributes['links'] ) ) {
		$str_atts .= 'links, ';
	}
	if ( strlen( $str_atts ) >= 2 ) {
		$str_atts = substr( $str_atts, 0, -2 );
	}
	$atts = [ 'sections' => $str_atts ];
	$output = pmpro_shortcode_account( $atts );
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</p>
