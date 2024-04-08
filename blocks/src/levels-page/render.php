<?php
/**
 * Render the Membership Levels and Pricing Table block on the frontend.
 */
$output = pmpro_loadTemplate( 'levels', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $output ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
