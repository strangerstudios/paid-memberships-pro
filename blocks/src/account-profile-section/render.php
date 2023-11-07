<?php
/**
 * Render the Membership Account: Profile block on the frontend.
 */
$title = isset( $attributes['title'] ) ? $attributes['title'] : null;
$output = pmpro_shortcode_account( array( 'sections' => 'profile', 'title' => $title ) );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</div>
