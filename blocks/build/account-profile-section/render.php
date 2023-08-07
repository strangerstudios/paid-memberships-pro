<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$title = isset( $attributes['title'] ) ? $attributes['title'] : null;
$output = pmpro_shortcode_account( array( 'sections' => 'profile', 'title' => $title ) );

?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</p>
