<?php
/**
 * Render the Membership Cancel block on the frontend.
 */
$output = pmpro_loadTemplate( 'cancel', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</div>
