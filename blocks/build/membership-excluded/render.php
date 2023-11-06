<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$output = '';
if ( ! array_key_exists( 'levels', $attributes ) || ! is_array( $attributes['levels'] ) ) {
	$output = do_blocks( $content );
} else {
	if ( pmpro_hasMembershipLevel( $attributes['levels'] ) ) {
		if ( ! empty( $attributes['show_noaccess'] ) ) {
			$output = pmpro_get_no_access_message( NULL, $attributes['levels'] );
		}
	} else {
		$output = do_blocks( $content );
	}
}

?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</p>
