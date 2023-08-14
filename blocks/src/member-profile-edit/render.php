<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
if ( function_exists( 'apply_shortcodes' ) ) {
	$output = apply_shortcodes( '[pmpro_member_profile_edit]' );
} else {
	$output = do_shortcode( '[pmpro_member_profile_edit]' );
}
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</p>
