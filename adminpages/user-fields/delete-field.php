<?php

// Get the field to delete
if ( isset( $_REQUEST['delete_name'] ) ) {
	$delete_name = sanitize_text_field( $_REQUEST['delete_name'] );

	// Get the current settings.
	$current_settings = pmpro_get_user_fields_settings();

	// Remove the field from the settings.
	$new_settings = array();
	$deleted = false;
	foreach ( $current_settings as $group_setting ) {
		$new_fields = array();
		foreach ( $group_setting->fields as $field_setting ) {
			if ( $field_setting->name === $delete_name ) {
				$deleted = true;
			} else {
				$new_fields[] = $field_setting;
			}
		}
		$group_setting->fields = $new_fields;
		$new_settings[] = $group_setting;
	}

	if ( $deleted ) {
		// Save the new settings.
		update_option( 'pmpro_user_fields_settings', $new_settings );

		// Show a success message.
		pmpro_setMessage( __( 'Field deleted.', 'paid-memberships-pro' ), 'success' );

		// Redirect with javascript.
		?>
		<script>
			 window.location.href = '?page=pmpro-userfields&success_message=<?php echo urlencode( __( 'Field deleted.', 'paid-memberships-pro' ) ); ?>';
		</script>
		<?php
		exit;
	} else {
		pmpro_setMessage( __( 'Field not found.', 'paid-memberships-pro' ), -1 );
	}
}