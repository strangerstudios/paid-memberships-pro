<?php
/**
 * Render the Membership Confirmation block on the frontend.
 */
$output = pmpro_loadTemplate( 'confirmation', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</div>
