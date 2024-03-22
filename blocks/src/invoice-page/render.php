<?php
/**
 * Render the Membership Invoice block on the frontend.
 */
$output = pmpro_loadTemplate( 'invoice', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $output ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
