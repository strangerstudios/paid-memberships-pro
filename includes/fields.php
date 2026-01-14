<?php 
/**
 * Check if a variable is a PMPro_Field.
 * Also checks for PMProRH_Field.
 */
function pmpro_is_field( $var ) {
    if ( is_a( $var, 'PMPro_Field' ) || is_a( $var, 'PMProRH_Field' ) ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Add a field to the PMPro registration fields global
 *
 *	$where refers to various hooks in the PMPro checkout page and can be:
 *	- after_username
 *	- after_password
 *	- after_email
 *	- after_captcha
 *	- checkout_boxes
 *	- after_billing_fields
 *	- before_submit_button
 *	- just_profile (make sure you set the profile attr of the field to true or admins)
 */
function pmpro_add_user_field( $where, $field ) {
    /**
     * Filter the group to add the field to.
     * 
     * @since 2.9.3
	 * @deprecated 3.4
     * 
     * @param string $where The name of the group to add the field to.
     * @param PMPro_Field $field The field being added.
     */
    $where = apply_filters_deprecated( 'pmpro_add_user_field_where', array( $where, $field ), '3.4', 'pmpro_add_user_field' );

	// Get the field group.
	$field_group = PMPro_Field_Group::get( $where );

	// Add the field to the group.
	$field_group->add_field( $field );
}

/**
 * Add a new checkout box to the checkout_boxes section.
 * You can then use this as the $where parameter
 * to pmpro_add_user_field.
 *
 * Name must contain no spaces or special characters.
 */
function pmpro_add_field_group( $name, $label = NULL, $description = '', $order = NULL ) {
	return PMPro_Field_Group::add( $name, $label, $description );
}

/**
 * Add a new User Taxonomy. You can then use this as the user_taxonomny parameter to pmpro_add_user_field.
 *
 * @param string $name The singular name for the taxonomy object.
 * @param string $name_plural The plural name for the taxonomy object.
 *
 */
function pmpro_add_user_taxonomy( $name, $name_plural ) {
	global $pmpro_user_taxonomies;
	
	// Sanitize the taxonomy $name and make sure it is less than 32 characters.
	$safe_name = sanitize_key( $name );
	if ( strlen( $safe_name ) > 32 ) {
		$safe_name = substr( $safe_name, 0, 32 );
	}

	// Add to the global so we can keep track.
	$pmpro_user_taxonomies = (array) $pmpro_user_taxonomies;
	$pmpro_user_taxonomies[] = $safe_name;

	// Make sure name and plural name are less than 32 characters.
	if ( strlen( $name ) > 32 ) {
		$name = substr( $name, 0, 32 );
	}
	if ( strlen( $name_plural ) > 32 ) {
		$name_plural = substr( $name_plural, 0, 32 );
	}

	$pmpro_user_taxonomy_labels = array(
		'name'                       => ucwords( $name ),
		'singular_name'              => ucwords( $name ),
		'menu_name'                  => ucwords( $name_plural ),
		'search_items'               => sprintf( esc_html__( 'Search %s', 'paid-memberships-pro' ), ucwords( $name_plural ) ),
		'popular_items'              => sprintf( esc_html__( 'Popular %s', 'paid-memberships-pro' ), ucwords( $name_plural ) ),
		'all_items'                  => sprintf( esc_html__( 'All %s', 'paid-memberships-pro' ), ucwords( $name_plural ) ),
		'edit_item'                  => sprintf( esc_html__( 'Edit %s', 'paid-memberships-pro' ), ucwords( $name ) ),
		'update_item'                => sprintf( esc_html__( 'Update %s', 'paid-memberships-pro' ), ucwords( $name ) ),
		'add_new_item'               => sprintf( esc_html__( 'Add New %s', 'paid-memberships-pro' ), ucwords( $name ) ),
		'new_item_name'              => sprintf( esc_html__( 'New %s Name', 'paid-memberships-pro' ), ucwords( $name ) ),
		'separate_items_with_commas' => sprintf( esc_html__( 'Separate %s with commas', 'paid-memberships-pro' ), $name_plural ),
		'add_or_remove_items'        => sprintf( esc_html__( 'Add or remove %s', 'paid-memberships-pro' ), $name_plural ),
		'choose_from_most_used'      => sprintf( esc_html__( 'Choose from the most popular %s', 'paid-memberships-pro' ), $name_plural ),
	);

	$pmpro_user_taxonomy_args = array(
		'public'       => false,
		'labels'       => $pmpro_user_taxonomy_labels,
		'rewrite'      => false,
		'show_ui'      => true,
		'capabilities' => array(
			'manage_terms' => 'edit_users',
			'edit_terms'   => 'edit_users',
			'delete_terms' => 'edit_users',
			'assign_terms' => 'read',
		),
	);

	/**
	 * Filter the args passed to the user taxonomy created.
	 *
	 * @param array $pmpro_user_taxonomy_args The arguments passed to the register_taxonomy function.
	 * @param string $name The current taxonomy name.
	 *
	 */
	$pmpro_user_taxonomy_args = apply_filters( 'pmpro_user_taxonomy_args', $pmpro_user_taxonomy_args, $name );
	register_taxonomy( $safe_name, 'user', $pmpro_user_taxonomy_args );

	// Update the labels after the args are filtered.
	$pmpro_user_taxonomy_labels = $pmpro_user_taxonomy_args['labels'];

	/**
	 * Add admin page for the registered user taxonomies.
	 */
	add_action( 'admin_menu', function () use ( $pmpro_user_taxonomy_labels, $safe_name ) {
		add_users_page(
			esc_attr( $pmpro_user_taxonomy_labels['menu_name'] ),
			esc_attr( $pmpro_user_taxonomy_labels['menu_name'] ),
			'edit_users',
			'edit-tags.php?taxonomy=' . $safe_name
		);
	} );

	/**
	 * Update parent file name to fix the selected menu issue for a user taxonomy.
	 */
	add_filter( 'parent_file', function ( $parent_file ) use ( $safe_name ) {
		global $submenu_file;
		if (
			isset( $_GET['taxonomy'] ) &&
			$_GET['taxonomy'] == $safe_name &&
			$submenu_file == 'edit-tags.php?taxonomy=' . $safe_name
		) {
			$parent_file = 'users.php';
		}

		return $parent_file;
	} );
}

/**
 * Get a field group by name.
 */
function pmpro_get_field_group_by_name( $name ) {
	return PMPro_Field_Group::get( $name );
}

/**
 * Check if a user field is enabled for the current checkout level.
 */
function pmpro_check_field_for_level( $field, $scope = 'default', $args = NULL ) {
    global $pmpro_level, $pmpro_checkout_level_ids;
	if ( ! empty( $field->levels ) ) {
        if ( 'profile' === $scope ) {
			// Expecting the args to be the user id.
			if ( pmpro_hasMembershipLevel( $field->levels, $args ) ) {
				return true;
			} else {
                return false;
			}
		} else {			
			if ( empty( $pmpro_checkout_level_ids ) && ! empty( $pmpro_level ) && ! empty( $pmpro_level->id ) ) {
				$pmpro_checkout_level_ids = array( $pmpro_level->id );
			}
			if ( ! is_array( $field->levels ) ) {
				$field_levels = array( $field->levels );
			} else {
				$field_levels = $field->levels;
			}
			if ( ! empty( $pmpro_checkout_level_ids ) ) {
				// Check against $_REQUEST.
				return ( ! empty( array_intersect( $field_levels, $pmpro_checkout_level_ids ) ) );
			}
			return false;
		}
	}

	return true;
}

/**
 * Get a list of all fields that are only shown when creating a user at checkout.
 */
function pmpro_get_user_creation_field_groups() {
	return array(
		'after_username',
		'after_password',
		'after_email',
	);
}

/**
 * Find fields in a group and display them at checkout.
 * This function is only used for the following fields at checkout:
 * - after_username
 * - after_password
 * - after_email
 * - after_captcha
 * - checkout_boxes
 * - after_billing_fields
 * - before_submit_button
 * - after_tos_fields
 */
function pmpro_display_fields_in_group( $group, $scope = 'checkout' ) {
	$valid_groups = array(
		'after_username',
		'after_password',
		'after_pricing_fields',
		'after_email',
		'after_captcha',
		'after_billing_fields',
		'before_submit_button',
		'after_tos_fields',
	);
	if ( ! in_array( $group, $valid_groups ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The group %s should not be passed into %s. Use PMPro_Field_Group::display() instead.', 'paid-memberships-pro' ), esc_html( $group ), __FUNCTION__ ), '2.9.3' );
	}
	if ( $scope !== 'checkout' ) {
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The scope %s should not be passed into %s. Use PMPro_Field_Group::display() instead.', 'paid-memberships-pro' ), esc_html( $scope ), __FUNCTION__ ), '2.9.3' );
	}

    // Get the field group.
	$field_group = PMPro_Field_Group::get( $group );
	$field_group->display(
		array(
			'markup' => 'div',
			'scope' => 'checkout',
			'show_group_label' => false,
			'prefill_from_request' => true,
			'show_required' => true,	
		)
	);
}

/**
 * Cycle through extra fields. Show them at checkout.
 */
// after_username
function pmpro_checkout_after_username_fields() {
	pmpro_display_fields_in_group( 'after_username', 'checkout' );
}
add_action( 'pmpro_checkout_after_username', 'pmpro_checkout_after_username_fields' );

//after_password
function pmpro_checkout_after_password_fields() {
	pmpro_display_fields_in_group( 'after_password', 'checkout' );
}
add_action( 'pmpro_checkout_after_password', 'pmpro_checkout_after_password_fields' );

//after_email
function pmpro_checkout_after_email_fields() {
	pmpro_display_fields_in_group( 'after_email', 'checkout' );
}
add_action( 'pmpro_checkout_after_email', 'pmpro_checkout_after_email_fields' );

//after captcha
function pmpro_checkout_after_captcha_fields() {
	pmpro_display_fields_in_group( 'after_captcha', 'checkout' );
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_checkout_after_captcha_fields' );

//checkout boxes
function pmpro_checkout_boxes_fields() {
	// Get all field groups.
	$field_groups = PMPro_Field_Group::get_all();

	$checkout_level = pmpro_getLevelAtCheckout();
	$chekcout_level_id = ! empty( $checkout_level->id ) ? (int)$checkout_level->id : NULL;
	if ( empty( $chekcout_level_id ) ) {
		return;
	}

	// Cycle through the field groups.
	foreach( $field_groups as $field_group_name => $field_group ) {
		// If this is not a checkout box, skip it.
		if ( in_array( $field_group_name, array( 'after_username', 'after_password', 'after_email', 'after_captcha', 'after_pricing_fields', 'after_billing_fields', 'before_submit_button', 'after_tos_fields' ) ) ) {
			continue;
		}

		$field_group->display(
			array(
				'markup' => 'card',
				'scope' => 'checkout',
				'prefill_from_request' => true,
				'show_required' => true,
			)
		);
	}
}
add_action( 'pmpro_checkout_boxes', 'pmpro_checkout_boxes_fields' );

//after_pricing_fields
function pmpro_checkout_after_pricing_fields() {
	pmpro_display_fields_in_group( 'after_pricing_fields', 'checkout' );
}
add_action( 'pmpro_checkout_after_pricing_fields', 'pmpro_checkout_after_pricing_fields' );

//after_billing_fields
function pmpro_checkout_after_billing_fields() {
	pmpro_display_fields_in_group( 'after_billing_fields', 'checkout' );
}
add_action( 'pmpro_checkout_after_billing_fields', 'pmpro_checkout_after_billing_fields');

//before submit button
function pmpro_checkout_before_submit_button_fields() {
	pmpro_display_fields_in_group( 'before_submit_button', 'checkout' );
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_checkout_before_submit_button_fields');

// After tos fields.
function pmpro_checkout_after_tos_fields() {
	pmpro_display_fields_in_group( 'after_tos_fields', 'checkout' );
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_checkout_after_tos_fields', 6 );

/**
 * Update user creation fields at checkout after a user is created.
 *
 * Only runs for the after_username, after_email, and after_password field groups.
 *
 * @since 3.4
 *
 * @param int $user_id The ID of the user that was created.
 */
function pmpro_checkout_before_user_auth_save_fields( $user_id ) {
	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	$user_creation_field_groups = pmpro_get_user_creation_field_groups();
	foreach($field_groups as $group_name => $group) {
		if ( ! in_array( $group_name, $user_creation_field_groups ) ) {
			continue;
		}

		// Save the fields.
		$group->save_fields(
			array(
				'user_id' => $user_id,
				'scope' => 'checkout',
			)
		);
	}
}
add_action( 'pmpro_checkout_before_user_auth', 'pmpro_checkout_before_user_auth_save_fields' );

/**
 * Require required fields before creating a user at checkout.
 *
 * Only runs for the after_username, after_email, and after_password field groups.
 */
function pmpro_checkout_user_creation_checks_user_fields( $okay ) {
	// Arrays to store fields that were required and missed.
	$required = array();
    $required_labels = array();

	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	$user_creation_field_groups = pmpro_get_user_creation_field_groups();
	foreach($field_groups as $group_name => $group) {
		if ( ! in_array( $group_name, $user_creation_field_groups ) ) {
			continue;
		}

		// Loop through all the fields in the group.
		$fields = $group->get_fields_to_display(
			array(
				'scope' => 'checkout',
			)
		);
		foreach($fields as $field) {
			// If this is a file upload, check whether the file is allowed.
			if ( isset( $_FILES[ $field->name ] ) && ! empty( $_FILES[$field->name]['name'] ) ) {
				$upload_check = pmpro_check_upload( $field->name );
				if ( is_wp_error( $upload_check ) ) {
					pmpro_setMessage( $upload_check->get_error_message(), 'pmpro_error' );
					return false;
				}
			}

			// If the field was filled if needed, skip it.
			if ( $field->was_filled_if_needed() ) {
				continue;
			}

			// The field was not filled.
			$required[] = $field->name;
			$required_labels[] = $field->label;
		}
	}

	if(!empty($required))
	{
		$required = array_unique($required);

		//add them to error fields
		global $pmpro_error_fields;
		$pmpro_error_fields = array_merge((array)$pmpro_error_fields, $required);

		if( count( $required ) == 1 ) {
			$pmpro_msg = sprintf( esc_html__( 'The %s field is required.', 'paid-memberships-pro' ),  implode(", ", $required_labels) );
			$pmpro_msgt = 'pmpro_error';
		} else {
			$pmpro_msg = sprintf( esc_html__( 'The %s fields are required.', 'paid-memberships-pro' ),  implode(", ", $required_labels) );
			$pmpro_msgt = 'pmpro_error';
		}

		if($okay)
			pmpro_setMessage($pmpro_msg, $pmpro_msgt);

		return false;
	}

	//return whatever status was before
	return $okay;
}
add_filter( 'pmpro_checkout_user_creation_checks', 'pmpro_checkout_user_creation_checks_user_fields' );

/**
 * Update the fields after a checkout is completed.
 * 
 * Does not run for the after_username, after_email, and after_password field groups.
 *
 * @param int $user_id The ID of the user that was created.
 * @param object $order The order object.
 */
function pmpro_after_checkout_save_fields( $user_id, $order ) {
	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	$user_creation_field_groups = pmpro_get_user_creation_field_groups();
	foreach($field_groups as $group_name => $group) {
		if ( in_array( $group_name, $user_creation_field_groups ) ) {
			continue;
		}

		// Save the fields.
		$group->save_fields(
			array(
				'user_id' => $user_id,
				'scope' => 'checkout',
			)
		);
	}
}
add_action( 'pmpro_after_checkout', 'pmpro_after_checkout_save_fields', 10, 2 );
add_action( 'pmpro_before_send_to_paypal_standard', 'pmpro_after_checkout_save_fields', 20, 2 );	//for paypal standard we need to do this just before sending the user to paypal
add_action( 'pmpro_before_send_to_twocheckout', 'pmpro_after_checkout_save_fields', 20, 2 );	//for 2checkout we need to do this just before sending the user to 2checkout
add_action( 'pmpro_before_send_to_gourl', 'pmpro_after_checkout_save_fields', 20, 2 );	//for the GoURL Bitcoin Gateway Add On
add_action( 'pmpro_before_send_to_payfast', 'pmpro_after_checkout_save_fields', 20, 2 );	//for the Payfast Gateway Add On

/**
 * Require required fields before creating an order at checkout.
 *
 * Does not run for the after_username, after_email, and after_password field groups.
 */
function pmpro_registration_checks_for_user_fields( $okay ) {
	// Arrays to store fields that were required and missed.
	$required = array();
    $required_labels = array();

	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	$user_creation_field_groups = pmpro_get_user_creation_field_groups();
	foreach($field_groups as $group_name => $group) {
		if ( in_array( $group_name, $user_creation_field_groups ) ) {
			continue;
		}

		// Loop through all the fields in the group.
		$fields = $group->get_fields_to_display(
			array(
				'scope' => 'checkout',
			)
		);
		foreach($fields as $field) {
			// If this is a file upload, check whether the file is allowed.
			if ( isset( $_FILES[ $field->name ] ) && ! empty( $_FILES[$field->name]['name'] ) ) {
				$upload_check = pmpro_check_upload( $field->name );
				if ( is_wp_error( $upload_check ) ) {
					pmpro_setMessage( $upload_check->get_error_message(), 'pmpro_error' );
					return false;
				}
			}

			// If the field was filled if needed, skip it.
			if ( $field->was_filled_if_needed() ) {
				continue;
			}

			// The field was not filled.
			$required[] = $field->name;
			$required_labels[] = $field->label;
		}
	}

	if(!empty($required))
	{
		$required = array_unique($required);

		//add them to error fields
		global $pmpro_error_fields;
		$pmpro_error_fields = array_merge((array)$pmpro_error_fields, $required);

		if( count( $required ) == 1 ) {
			$pmpro_msg = sprintf( esc_html__( 'The %s field is required.', 'paid-memberships-pro' ),  implode(", ", $required_labels) );
			$pmpro_msgt = 'pmpro_error';
		} else {
			$pmpro_msg = sprintf( esc_html__( 'The %s fields are required.', 'paid-memberships-pro' ),  implode(", ", $required_labels) );
			$pmpro_msgt = 'pmpro_error';
		}

		if($okay)
			pmpro_setMessage($pmpro_msg, $pmpro_msgt);

		return false;
	}

	//return whatever status was before
	return $okay;
}
add_filter( 'pmpro_checkout_order_creation_checks', 'pmpro_registration_checks_for_user_fields' );

/**
 * Sessions vars for TwoCheckout. PayPal Express was updated to store in order meta.
 *
 * @deprecated 2.12.4 Use pmpro_after_checkout_save_fields instead to save fields immediately or pmpro_save_checkout_data_to_order for delayed checkouts.
 */
function pmpro_paypalexpress_session_vars_for_user_fields() {
	_deprecated_function( __FUNCTION__, '2.12.4', 'pmpro_after_checkout_save_fields' );

	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Loop through all the fields in the group.
		$fields = $group->get_fields();
		foreach($fields as $field)
		{
			if( ! pmpro_is_field( $field ) ) {
				continue;
			}
			
			if ( ! pmpro_check_field_for_level( $field ) ) {
				continue;
			}

			if( isset( $_REQUEST[$field->name] ) ) {
				$_SESSION[$field->name] = pmpro_sanitize( $_REQUEST[$field->name], $field ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} elseif ( isset( $_FILES[$field->name] ) ) {
				/*
					We need to save the file somewhere and save values in $_SESSION
				*/
				// Make sure the file is allowed.
				$upload_check = pmpro_check_upload( $field->name );
				if ( is_wp_error( $upload_check ) ) {
					continue;
				}

				// Get $file and $filetype.
				$file = array_map( 'sanitize_text_field', $_FILES[ $field->name ] );
				$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

				// Make sure file was uploaded during this page load.
				if ( ! is_uploaded_file( sanitize_text_field( $file['tmp_name'] ) ) ) {						
					continue;
				}

				//check for a register helper directory in wp-content
				$upload_dir = wp_upload_dir();
				$pmprorh_dir = $upload_dir['basedir'] . "/pmpro-register-helper/tmp/";

				//create the dir and subdir if needed
				if(!is_dir($pmprorh_dir))
				{
					wp_mkdir_p($pmprorh_dir);
				}

				//move file
				$new_filename = $pmprorh_dir . basename( sanitize_file_name( $file['name'] ) );
				move_uploaded_file( sanitize_text_field( $$file['tmp_name'] ), $new_filename );

				//update location of file
				$_FILES[$field->name]['tmp_name'] = $new_filename;

				//save file info in session
				$_SESSION[$field->name] = array_map( 'sanitize_text_field', $file );
			}
		}
	}
}

/**
 * Show user fields in profile.
 *
 * @deprecated 3.4
 */
function pmpro_show_user_fields_in_profile( $user, $withlocations = false ) {
	_deprecated_function( __FUNCTION__, '3.4', 'pmpro_show_user_fields_in_profile_with_locations' );
	if ( $withlocations ) {
		return pmpro_show_user_fields_in_profile_with_locations( $user );
	}
	$groups = PMPro_Field_Group::get_all();
	foreach( $groups as $group ) {
		$group->display(
			array(
				'markup' => 'table',
				'scope' => 'profile',
				'show_group_label' => $withlocations,
				'user_id' => $user->ID,
			)
		);
	}	
}

/**
 * Show user fields in the backend profile.
 */
function pmpro_show_user_fields_in_profile_with_locations( $user ) {
	$groups = PMPro_Field_Group::get_all();
	foreach( $groups as $group ) {
		$group->display(
			array(
				'markup' => 'table',
				'scope' => 'profile',
				'user_id' => $user->ID,
			)
		);
	}	
}
add_action( 'show_user_profile', 'pmpro_show_user_fields_in_profile_with_locations' );
add_action( 'edit_user_profile', 'pmpro_show_user_fields_in_profile_with_locations' );

/**
 * Show Profile fields on the frontend "Member Profile Edit" page.
 *
 * @since 2.3
 * @deprecated 3.4
 */
function pmpro_show_user_fields_in_frontend_profile( $user, $withlocations = false ) {
	_deprecated_function( __FUNCTION__, '3.4', 'pmpro_show_user_fields_in_frontend_profile_with_locations' );
	if ( $withlocations ) {
		return pmpro_show_user_fields_in_frontend_profile_with_locations( $user );
	}

	$groups = PMPro_Field_Group::get_all();
	foreach( $groups as $group ) {
		$group->display(
			array(
				'markup' => 'div',
				'scope' => 'profile',
				'show_group_label' => $withlocations,
				'user_id' => $user->ID,
			)
		);
	}	
}

/**
 * Show Profile fields on the frontend "Member Profile Edit" page.
 *
 * @since 2.3
 */
function pmpro_show_user_fields_in_frontend_profile_with_locations( $user ) {
	$groups = PMPro_Field_Group::get_all();
	foreach( $groups as $group ) {
		$group->display(
			array(
				'markup' => 'div',
				'scope' => 'profile',
				'user_id' => $user->ID,
			)
		);
	}	
}
add_action( 'pmpro_show_user_profile', 'pmpro_show_user_fields_in_frontend_profile_with_locations' );

/**
 * Show user fields on the Add Member form
 * when using the Add Member Admin Add On.
 */
// Add fields to form.
function pmpro_add_member_admin_fields( $user = null, $user_id = null) {
	$addmember_fields = array();
	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Loop through all the fields in the group.
		$fields = $group->get_fields();
		foreach($fields as $field)
		{
			if(pmpro_is_field($field) && isset($field->addmember) && !empty($field->addmember) && ( in_array( strtolower( $field->addmember ), array( 'true', 'yes' ) ) || true == $field->addmember ) )
			{
				$addmember_fields[] = $field;
			}
		}
    }


    //show the fields
    if(!empty($addmember_fields)) {
		//cycle through groups
		foreach($addmember_fields as $field)
		{
			if(empty($user_id) && !empty($user) && !empty($user->ID)) {
				$user_id = $user->ID;
			}

			if( ! pmpro_is_field( $field ) ) {
				continue;
			}

			if(metadata_exists("user", $user_id, $field->meta_key))
			{
				$value = get_user_meta($user_id, $field->meta_key, true);
			} else {
				$value = "";
			}
			?>
			<tr id="<?php echo esc_attr( $field->id );?>_tr">
				<th>
					<?php if ( ! empty( $field->showmainlabel ) ) { ?>
						<label for="<?php echo esc_attr($field->name);?>"><?php echo wp_kses_post( $field->label );?></label>
					<?php } ?>
				</th>
				<td>
					<?php
						if(current_user_can("edit_user", $user_id) && $field !== false)
							$field->display($value);
						else
							echo "<div>" . wp_kses_post( $field->displayValue($value) ) . "</div>";
					?>
					<?php if(!empty($field->hint)) { ?>
						<p class="description"><?php echo wp_kses_post( $field->hint );?></p>
					<?php } ?>
				</td>
			</tr>
			<?php	
		}
    }
}
add_action( 'pmpro_add_member_fields', 'pmpro_add_member_admin_fields', 10, 2 );

/**
 * Save user fields on the Add Member Admin form.
 * Hooks into pmpro_add_member_added.
 * @since 2.9
 * @param int $uid The user ID.
 * @param object $user The user object.
 * @return void
 */
function pmpro_add_member_admin_save_user_fields( $uid = null, $user = null ) {
	
	// Use the ID from the $user object if passed in.
	if ( ! empty( $user ) && is_object( $user ) ) {
		$user_id = $user->ID;
	}

	// Otherwise, let's use the $uid passed in.
	if ( !empty( $uid ) && ( empty( $user ) || !is_object( $user ) ) ) {
		$user_id = $uid;
	}

	// check whether the user login variable contains something useful
	if (empty($user_id)) {		

		pmpro_setMessage( esc_html__( 'Unable to add/update user fields for this member', 'paid-memberships-pro' ), 'pmpro_error' );

		return false;
	}

	$addmember_fields = array();
	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Loop through all the fields in the group.
		$fields = $group->get_fields();
		foreach($fields as $field)
		{
			if(pmpro_is_field($field) && isset($field->addmember) && !empty($field->addmember) && ( in_array( strtolower( $field->addmember ), array( 'true', 'yes' ) ) || true == $field->addmember ) )
			{
					$addmember_fields[] = $field;
			}
		}
    }

    //save our added fields in session while the user goes off to PayPal
    if(!empty($addmember_fields))
    {
        //cycle through fields
        foreach($addmember_fields as $field)
        {
            $field->save_field_for_user( $user_id );
        }
    }
}
add_action( 'pmpro_add_member_added', 'pmpro_add_member_admin_save_user_fields', 10, 2 );

/**
 * Get user fields which are set to show up in the Members List CSV Export.
 */
function pmpro_get_user_fields_for_csv() {
	$csv_fields = array();
	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Loop through all the fields in the group.
		$fields = $group->get_fields();
		foreach($fields as $field)
		{
			if(pmpro_is_field($field) && !empty($field->memberslistcsv) && ($field->memberslistcsv == "true"))
			{
				$csv_fields[] = $field;
			}
		}
	}

	return $csv_fields;
}

/**
 * Get user fields which are marked to show in the profile.
 * If a $user_id is passed in, get fields based on the user's level.
 *
 * @deprecated 3.4 Use PMPro_Field_Group::get_fields_to_display instead.
 */
function pmpro_get_user_fields_for_profile( $user_id, $withlocations = false ) {
	_deprecated_function( __FUNCTION__, '3.4', 'PMPro_Field_Group::get_fields_to_display' );
	$profile_fields = array();
	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Get the fields to display.
		$fields_to_display = $group->get_fields_to_display(
			array(
				'scope' => 'profile',
				'user_id' => $user_id,
			)
		);

		if ( empty( $fields_to_display ) ) {
			continue;
		}

		if ( $withlocations ) {
			$profile_fields[ $group_name ] = $fields_to_display;
		} else {
			$profile_fields = array_merge( $profile_fields, $fields_to_display );
		}
	}

	return $profile_fields;
}

/**
 * Change the enctype of the edit user form in case files need to be uploaded.
 */
function pmpro_user_edit_form_tag() {
	echo ' enctype="multipart/form-data"';
}
add_action( 'user_edit_form_tag', 'pmpro_user_edit_form_tag' );

/**
 * Save profile fields.
 */
function pmpro_save_user_fields_in_profile( $user_id )
{
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Save the fields.
		$group->save_fields(
			array(
				'scope' => 'profile',
				'user_id' => $user_id,
			)
		);
	}
}
add_action( 'personal_options_update', 'pmpro_save_user_fields_in_profile' );
add_action( 'edit_user_profile_update', 'pmpro_save_user_fields_in_profile' );
add_action( 'pmpro_personal_options_update', 'pmpro_save_user_fields_in_profile' );

/**
 * Add user fields to confirmation email.
 */
function pmpro_add_user_fields_to_email( $email ) {
	global $wpdb;

	//only update admin confirmation emails
	if ( ! empty( $email ) && strpos( $email->template, "checkout" ) !== false && strpos( $email->template, "admin" ) !== false ) {
		//get the user_id from the email
		$user_id = $wpdb->get_var( "SELECT ID FROM $wpdb->users WHERE user_email = '" . esc_sql( $email->data['user_email'] ) . "' LIMIT 1" );

		if ( ! empty( $user_id ) ) {
			//add to bottom of email
			$field_groups = PMPro_Field_Group::get_all();
			if ( ! empty( $field_groups ) ) {
				$fields_content = "<p>" . esc_html__( 'Extra Fields:', 'paid-memberships-pro' ) . "<br />";
				$added_field = false;
				// Loop through all the field groups.
				foreach( $field_groups as $group_name => $group ) {
					// Loop through all the fields in the group.
					$fields = $group->get_fields_to_display(
						array(
							'scope' => 'checkout',
							'user_id' => $user_id,
						)
					);
					foreach( $fields as $field ) {
						$fields_content .= "- " . esc_html( $field->label ) . ": ";
						$fields_content .= $field->displayValue( get_user_meta( $user_id, $field->name, true), false );
						$fields_content .= "<br />";
						$added_field = true;
					}
				}
				$fields_content .= "</p>";
				if ( $added_field ) {
					$email->body .= $fields_content;
				}
			}
		}
	}

	return $email;
}
add_filter( 'pmpro_email_filter', 'pmpro_add_user_fields_to_email', 10, 2 );

/**
 * Add CSV fields to the Member's List CSV Export.
 */
function pmpro_members_list_csv_extra_columns_for_user_fields($columns)
{
	$csv_cols = pmpro_get_user_fields_for_csv();
	foreach($csv_cols as $key => $value)
	{
		$columns[$value->meta_key] = "pmpro_csv_columns_for_user_fields";
	}

	return $columns;
}
add_filter( 'pmpro_members_list_csv_extra_columns', 'pmpro_members_list_csv_extra_columns_for_user_fields', 10 );

/**
 * Get user meta for the added CSV columns.
 */
function pmpro_csv_columns_for_user_fields( $user, $column ) {
	if(!empty($user->metavalues->{$column}))
	{
		// check for multiple values
		$value = maybe_unserialize($user->metavalues->{$column});
		if(is_array($value))
			$value = join(',', $value);

		return $value;
	}
	else
	{
		return "";
	}
}

/**
 * Get user fields from global.
 * @since 2.9.3
 * @deprecated 3.4
 */
function pmpro_get_user_fields() {
	_deprecated_function( __FUNCTION__, '3.4' );

    global $pmpro_user_fields;
        
    return (array)$pmpro_user_fields;
}

// Code for the user fields settings page.
/**
 * Get field group HTML for settings.
 */
function pmpro_get_field_group_html( $group = null ) {
    include( PMPRO_DIR . '/adminpages/user-fields/group-settings.php' );
}
 
/**
 * Get field HTML for settings.
 */
function pmpro_get_field_html( $field = null ) {
	include( PMPRO_DIR . '/adminpages/user-fields/field-settings.php' );
}

/**
 * Get user fields from options.
 *
 * This function will not return fields that are added through code.
 */
function pmpro_get_user_fields_settings() {
    $default_user_fields_settings = array(
        (object) array(
            'name' => esc_html__( 'More Information', 'paid-memberships-pro' ),
            'checkout' => 'yes',
            'profile' => 'yes',
            'description' => '',
            'levels' => array(),
            'fields' => array(),
        )
    );
    
    $settings = get_option( 'pmpro_user_fields_settings', $default_user_fields_settings );
    
    // Make sure all expected properties are set for each group.
	foreach ( $settings as $group ) {
		$group->name = ! empty( $group->name ) ? $group->name : '';
		$group->checkout = ! empty( $group->checkout ) ? $group->checkout : 'yes';
		$group->profile = ! empty( $group->profile ) ? $group->profile : 'yes';
		$group->description = ! empty( $group->description ) ? $group->description : '';
		$group->levels = ! empty( $group->levels ) ? $group->levels : array();
		$group->fields = ! empty( $group->fields ) ? $group->fields : array();

		// Make sure all expected properties are set for each field in the group.
		foreach( $group->fields as $field ) {
			$field->label = ! empty( $field->label ) ? $field->label : '';
			$field->name = ! empty( $field->name ) ? $field->name : '';
			$field->type = ! empty( $field->type ) ? $field->type : '';
			$field->required = ! empty( $field->required ) ? $field->required : false;
			$field->readonly = ! empty( $field->readonly ) ? $field->readonly : false;
			$field->profile = ! empty( $field->profile ) ? $field->profile : '';
			$field->wrapper_class = ! empty( $field->wrapper_class ) ? $field->wrapper_class : '';
			$field->element_class = ! empty( $field->element_class ) ? $field->element_class : '';
			$field->hint = ! empty( $field->hint ) ? $field->hint : '';
			$field->options = ! empty( $field->options ) ? $field->options : '';
			$field->default = ! empty( $field->default ) ? $field->default : '';
			$field->allowed_file_types = ! empty( $field->allowed_file_types ) ? $field->allowed_file_types : '';
			$field->max_file_size = ! empty( $field->max_file_size ) ? $field->max_file_size : '';
		}
	}
    
    return $settings;
}

/**
 * Load user field settings into the fields global var.
 */
function pmpro_load_user_fields_from_settings() {
    $settings_groups = pmpro_get_user_fields_settings();

    foreach ( $settings_groups as $group ) {
        $group_obj = PMPro_Field_Group::add( $group->name, $group->name, $group->description );
        
        // Figure out profile value. Change 2 settings values into 1 field value.
        if ( $group->checkout === 'yes' ) {
            if ( $group->profile === 'yes' ) {
                $group_profile = true;
            } elseif ( $group->profile === 'admins' ) {
                $group_profile = 'admin';
            } else {
                $group_profile = false;
            }
        } else {
            if ( $group->profile === 'yes' ) {
                $group_profile = 'only';
            } elseif ( $group->profile === 'admins' ) {
                $group_profile = 'only_admin';
            } else {
                // Hide from checkout AND profile? Okay, skip this group.
                continue;
            }
        }
        
        foreach ( $group->fields as $settings_field ) {
            // Figure out field profile from settings and group profile.
            if ( empty( $settings_field->profile ) || $settings_field->profile === '[Inherit Group Setting]' ) {
                $profile = $group_profile;
            } else {
                if ( $settings_field->profile === 'yes' ) {
                    $profile = true;
                } elseif ( $settings_field->profile === 'no' ) {
                    $profile = false;
                } elseif ( $settings_field->profile === 'admins' ) {
                    $profile = 'admin';
                } else {
                    // default to no
                    $profile = false;
                }
            }
            
            // Figure out options.
            $option_types = array( 'checkbox_grouped', 'radio', 'select', 'select2', 'multiselect' );
            if ( in_array( $settings_field->type, $option_types ) ) {
                $options = array();
                $settings_options = explode( "\n", $settings_field->options );
                foreach( $settings_options as $settings_option ) {
                    if ( strpos( $settings_option, ':' ) !== false ) {
                        $parts = explode( ':', $settings_option );
                        $options[trim( $parts[0] )] = trim( $parts[1] );
                    } else {
                        $options[] = trim( $settings_option );
                    }
                }
            } else {
                $options = false;
            }
            
            // Set field levels based on group.
            $levels = $group->levels;
            
            $field = new PMPro_Field(
                $settings_field->name,
                $settings_field->type,
                array(
                    'label' => $settings_field->label,                    
                    'required' => filter_var( $settings_field->required, FILTER_VALIDATE_BOOLEAN ),
                    'readonly' => filter_var( $settings_field->readonly, FILTER_VALIDATE_BOOLEAN ),
                    'profile' => $profile,
                    'class' => $settings_field->element_class,
                    'divclass' => $settings_field->wrapper_class,
                    'hint' => $settings_field->hint,
                    'options' => $options,
                    'levels' => $levels,
                    'memberslistcsv' => true,
                    'allowed_file_types' => $settings_field->allowed_file_types,
                    'max_file_size' => $settings_field->max_file_size,
                    'default' => $settings_field->default,
                )
            );
            $group_obj->add_field( $field );
        }
    }        
}
add_action( 'init', 'pmpro_load_user_fields_from_settings', 1 );

/**
 * Check if user is adding custom user fields with code.
 *
 * @since 2.9
 * @deprecated 3.4
 *
 * @return bool True if user is adding custom user fields with code.
 */
function pmpro_has_coded_user_fields() {
	_deprecated_function( __FUNCTION__, '3.4' );
	global $pmprorh_registration_fields;

	// Check if coded fields are being added using the PMPro Register Helper Add On active.
	if ( ! empty( $pmprorh_registration_fields ) ) {
		return true;
	}

	// Check if coded fields are being added using the PMPro Register Helper Add On inactive.
	$num_fields_from_settings = array_sum( array_map( function ($group) { return count( $group->fields ); }, pmpro_get_user_fields_settings() ) ); // Fields from UI settings page.
	$total_registered_fields = array_sum( array_map( function ($group) { return count( $group->get_fields() ); }, PMPro_Field_Group::get_all() ) ); // All registered fields.
	return $total_registered_fields > $num_fields_from_settings;
}

/**
 * Gets the label(s) for a passed user field value.
 *
 * @since 2.11
 * @deprecated 3.4 Use PMProField::displayValue instead.
 *
 * @param string $field_name  The name of the field that the value belongs to.
 * @param string|array $field_value The value to get the label for.
 *
 * @return string|array The label(s) for the passed value. Will be same type as $field_value.
 */
function pmpro_get_label_for_user_field_value( $field_name, $field_value ) {
	_deprecated_function( __FUNCTION__, '3.4', 'PMProField::displayValue' );

	// Loop through all the field groups.
	$field_groups = PMPro_Field_Group::get_all();
	foreach($field_groups as $group_name => $group) {
		// Loop through all the fields in the group.
		$fields = $group->get_fields();
		foreach( $fields as $user_field ) {
			// Check if this is the user field that we are displaying.
			if ( $user_field->name !== $field_name ) {
				continue;
			}

			// Make sure that we have a valid user field.
            if ( ! pmpro_is_field( $user_field ) ) {
                continue;
            }
            
            // Check if this is the user field that we are displaying.
            if ( empty( $user_field->options ) ) {
                continue;
            }
            
            // Make sure that $options is an array.
            if ( ! is_array( $user_field->options ) ) {
                continue;
            }
            
			// Replace meta values with their corresponding labels.
			$field_value = $user_field->displayValue( $field_value, false );
		}
	}
	return $field_value;
}

/**
 * Get a single user field.
 * @since 3.0
 * @deprecated 3.4
 * @param string $field_name The name of the field to get.
 * @return bool|object The field object if found, false otherwise.
 */
function pmpro_get_user_field( $field_name ) {
	_deprecated_function( __FUNCTION__, '3.4', 'PMPro_Field_Group::get_field' );
	$field = PMPro_Field_Group::get_field( $field_name );
	return empty( $field ) ? false : $field;
}
