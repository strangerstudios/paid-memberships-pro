<?php

// Get the group to delete
if ( isset( $_REQUEST['delete_name'] ) ) {
	$delete_name = sanitize_text_field( $_REQUEST['delete_name'] );

	// Get the current settings.
	$current_settings = pmpro_get_user_fields_settings();

	// Remove the group from the settings.
	$new_settings = array();
	$deleted = false;
	foreach ( $current_settings as $group_setting ) {
		if ( $group_setting->name === $delete_name ) {
			$deleted = true;
		} else {
			$new_settings[] = $group_setting;
		}
	}

	if ( $deleted ) {
		// Save the new settings.
		update_option( 'pmpro_user_fields_settings', $new_settings );

		// Show a success message.
		pmpro_setMessage( __( 'Group deleted.', 'paid-memberships-pro' ), 'success' );

		// Redirect with javascript.
		?>
		<script>
			window.location.href = '?page=pmpro-userfields&success_message=<?php echo urlencode( __( 'Group deleted.', 'paid-memberships-pro' ) ); ?>';
		</script>
		<?php
		exit;
	} else {
		pmpro_setMessage( __( 'Group not found.', 'paid-memberships-pro' ), -1 );
	}
}