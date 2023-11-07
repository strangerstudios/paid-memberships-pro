<?php
/**
 * Render the Membership Billing block on the frontend.
 */
$output = pmpro_loadTemplate( 'billing', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
    <?php echo $output; ?>
</div>
