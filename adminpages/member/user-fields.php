<?php
if ( ! empty( $user->ID ) && ! empty( $profile_user_fields ) ) {
	$user_field_loop_index = $user_field_tab_start;
	foreach ( $profile_user_fields as $group_name => $user_fields ) {
		?>
		<div id="pmpro-field-group-<?php echo esc_attr( $user_field_loop_index ) ?>-panel" role="tabpanel" tabindex="0" aria-labelledby="tab-<?php echo esc_attr( $user_field_loop_index ); ?>" hidden>
			<h2><?php echo esc_html( $group_name ); ?></h2>
			<table class="form-table">
				<?php
				foreach( $user_fields as $field ) {
					if ( pmpro_is_field( $field ) )
						$field->displayInProfile( $user->ID );
				}
				?>
			</table>
		</div>
		<?php
		$user_field_loop_index++;
	}
}