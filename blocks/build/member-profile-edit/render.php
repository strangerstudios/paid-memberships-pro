<?php
/**
 * Render the Member Profile Edit block on the frontend.
 */
if ( function_exists( 'apply_shortcodes' ) ) {
	$output = apply_shortcodes( '[pmpro_member_profile_edit]' );
} else {
	$output = do_shortcode( '[pmpro_member_profile_edit]' );
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php echo $output ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
