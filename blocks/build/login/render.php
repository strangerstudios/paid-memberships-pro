<?php
/**
 * Render the Login Form block on the frontend.
 */
if ( isset( $attributes['display_if_logged_in'] ) ) {
	$attributes['display_if_logged_in'] = filter_var( $attributes['display_if_logged_in'], FILTER_VALIDATE_BOOLEAN );
}
if ( isset( $attributes['show_menu'] ) ) {
	$attributes['show_menu'] = filter_var( $attributes['show_menu'], FILTER_VALIDATE_BOOLEAN );
}
if ( isset( $attributes['show_logout_link'] ) ) {
	$attributes['show_logout_link'] = filter_var( $attributes['show_logout_link'], FILTER_VALIDATE_BOOLEAN );
}

$output = ( pmpro_login_forms_handler(
	isset( $attributes['show_menu'] ) ? $attributes['show_menu'] : true,
	isset( $attributes['show_logout_link'] ) ? $attributes['show_logout_link'] : true,
	isset( $attributes['display_if_logged_in'] ) ? $attributes['display_if_logged_in'] : true,
	'',
	false
) );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $output ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
