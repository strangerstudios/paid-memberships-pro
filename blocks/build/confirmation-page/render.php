<?php
/**
 * Render the Membership Confirmation block on the frontend.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$output = pmpro_loadTemplate( 'confirmation', 'local', 'pages' );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $output ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
