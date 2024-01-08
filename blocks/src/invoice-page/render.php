<?php
/**
 * Render the Membership Invoice block on the frontend.
 */
$output = pmpro_loadTemplate( 'invoice', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $output; ?>
</div>
