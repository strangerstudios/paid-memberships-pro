<?php

global $pmpro_msg, $pmpro_msgt;

// Make sure that we have a field name being saved.
if ( empty( $_POST['name' ] ) ) {
	pmpro_setMessage( __( 'Name is required.', 'paid-memberships-pro' ), -1 );
}

// Get field attributes and make sure there's a type.
if ( -1 !== $pmpro_msgt ) {
	$field = new stdClass();
	$field->name = pmpro_getParam( 'name', 'POST' );
	$field->label = pmpro_getParam( 'label', 'POST' );
	$field->type = pmpro_getParam( 'type', 'POST' );
	$field->required = pmpro_getParam( 'required', 'POST' );
	$field->readonly = pmpro_getParam( 'readonly', 'POST' );
	$field->profile = pmpro_getParam( 'profile', 'POST' );
	$field->wrapper_class = pmpro_getParam( 'wrapper_class', 'POST' );
	$field->element_class = pmpro_getParam( 'element_class', 'POST' );
	$field->hint = pmpro_getParam( 'hint', 'POST', '', 'wp_kses_post' );
	$field->options = pmpro_getParam( 'options', 'POST', '', 'sanitize_textarea_field' );
	$field->allowed_file_types = pmpro_getParam( 'allowed_file_types', 'POST' );
	$field->max_file_size = pmpro_getParam( 'max_file_size', 'POST' );
	$field->default = pmpro_getParam( 'default', 'POST' );

	if ( empty( $field->type ) ) {
		pmpro_setMessage( __( 'Type is required.', 'paid-memberships-pro' ), -1 );
	}
}

// Make sure that there was a group passed.
if ( -1 !== $pmpro_msgt ) {
	$group = pmpro_getParam( 'group', 'POST' );
	if ( empty( $group ) ) {
		pmpro_setMessage( __( 'Group is required.', 'paid-memberships-pro' ), -1 );
	}
}

// Make sure that the group is valid.
if ( -1 !== $pmpro_msgt ) {
	$current_settings = pmpro_get_user_fields_settings();
	$group_obj = null;
	foreach ( $current_settings as $group_setting ) {
		if ( $group_setting->name === $group ) {
			$group_obj = $group_setting;
			break;
		}
	}
	if ( empty( $group_obj ) ) {
		pmpro_setMessage( __( 'Invalid group.', 'paid-memberships-pro' ), -1 );
	}
}

// If there are no errors, save the field.
if ( -1 !== $pmpro_msgt ) {
	// If the field is already in the group, update it.
	$found = false;
	$new_fields = array();
	foreach ( $group_obj->fields as $key => $field_setting ) {
		if ( $field_setting->name === $field->name ) {
			$new_fields[] = $field;
			$found = true;
		} else {
			$new_fields[] = $field_setting;
		}
	}

	// If the field was not found, add it.
	if ( ! $found ) {
		$new_fields[] = $field;
	}
	$group_obj->fields = $new_fields;

	// Delete fields with this name from other groups and update the current group.
	$new_settings = array();
	foreach ( $current_settings as $group_setting ) {
		if ( $group_setting->name === $group ) {
			$new_settings[] = $group_obj;
		} else {
			$new_fields = array();
			foreach ( $group_setting->fields as $field_setting ) {
				if ( $field_setting->name !== $field->name ) {
					$new_fields[] = $field_setting;
				}
			}
			$group_setting->fields = $new_fields;
			$new_settings[] = $group_setting;
		}
	}

	// Save the new settings.
	update_option( 'pmpro_user_fields_settings', $new_settings );

	// Set the field being edited.
	$_REQUEST['edit'] = $field->name;

	// Show a success message.
	pmpro_setMessage( __( 'Field saved.', 'paid-memberships-pro' ) . ' <a href="' . admin_url( 'admin.php?page=pmpro-userfields' ) . '">' . __( 'View All Fields.', 'paid-memberships-pro' ) . '</a>', 'pmpro_success' );

	// Redirect with javascript.
	?>
	<script>
		window.location.href = '?page=pmpro-userfields&success_message=<?php echo urlencode( __( 'Field saved.', 'paid-memberships-pro' ) ); ?>';
	</script>
	<?php
}
