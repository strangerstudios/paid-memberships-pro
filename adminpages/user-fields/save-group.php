<?php

global $pmpro_msg, $pmpro_msgt;

// Make sure that we have a group name being saved.
if ( empty( $_POST['name' ] ) ) {
	pmpro_setMessage( __( 'Name is required.', 'paid-memberships-pro' ), -1 );
}

// Get group attributes.
if ( -1 !== $pmpro_msgt ) {
	$group = new stdClass();
	$group->name = pmpro_getParam( 'name', 'POST' );
	$group->label = pmpro_getParam( 'label', 'POST' );
	$group->checkout = pmpro_getParam( 'checkout', 'POST' );
	$group->profile = pmpro_getParam( 'profile', 'POST' );
	$group->description = pmpro_getParam( 'description', 'POST', '', 'wp_kses_post' );
	$group->levels = array_map( 'intval', empty( $_POST['levels'] ) ? array() : $_POST['levels'] );
}

// If the name has changed but there is already another group with that name, show an error.
if ( -1 !== $pmpro_msgt && $group->name !== $_REQUEST['original_name'] ) {
	$all_groups = PMPro_Field_Group::get_all();
	if ( isset( $all_groups[ $group->name ] ) ) {
		pmpro_setMessage( __( 'A group with that name already exists.', 'paid-memberships-pro' ), -1 );
	}
}

// If the group is hidden from both the checkout and profile pages, show an error.
// This will prevent any fields from being loaded in pmpro_load_user_fields_from_settings().
if ( -1 !== $pmpro_msgt && 'no' === $group->checkout && 'no' === $group->profile ) {
	pmpro_setMessage( __( 'Group cannot be hidden from both the checkout and profile pages.', 'paid-memberships-pro' ), -1 );
}

// If there are no errors, save the group.
if ( -1 !== $pmpro_msgt ) {
	$current_settings = pmpro_get_user_fields_settings();
	$new_settings = array();
	$added = false;
	foreach ( $current_settings as $group_setting ) {
		if ( $group_setting->name === $_REQUEST['original_name'] ) {
			$group->fields = $group_setting->fields;
			$new_settings[] = $group;
			$added = true;
		} else {
			$new_settings[] = $group_setting;
		}
	}

	// If the group is new, add it to the settings.
	if ( ! $added ) {
		$new_settings[] = $group;
	}

	// Save the new settings.
	update_option( 'pmpro_user_fields_settings', $new_settings );

	// Set the field being edited.
	$_REQUEST['edit_group'] = $group->name;

	// Show a success message.
	pmpro_setMessage( __( 'Group saved.', 'paid-memberships-pro' ) . ' <a href="' . admin_url( 'admin.php?page=pmpro-userfields' ) . '">' . __( 'View All Fields.', 'paid-memberships-pro' ) . '</a>', 'pmpro_success' );

	// Redirect with javascript.
	?>
	<script>
		window.location.href = '?page=pmpro-userfields&success_message=<?php echo urlencode( __( 'Group saved.', 'paid-memberships-pro' ) ); ?>';
	</script>
	<?php
}
