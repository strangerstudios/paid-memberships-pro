<?php
foreach ( $attributes['groups'] as $group ) {
	$group_ids[] = intval( $group['id'] );
}
foreach ( $attributes['levels'] as $level ) {
	$level_ids[] = intval( $level['value'] );
}

$atts = array(
	'groups' => implode( ',', $group_ids ),
	'levels' => implode( ',', $level_ids ),
);


$output = pmpro_loadTemplate( 'levels', 'local', 'pages', 'php', $atts );

?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $output ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
