<?php 
// Global to store field groups and user fields.
global $pmpro_field_groups, $pmpro_user_fields;
$pmpro_user_fields = array();

// Add default group.
$cb = new stdClass();
$cb->name = 'checkout_boxes';
$cb->label = apply_filters( 'pmpro_default_field_group_label', __( 'More Information','paid-memberships-pro' ) );
$cb->order = 0;
$pmpro_field_groups = array( 'checkout_boxes' => $cb );

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
 * Add a field to the PMProRH regisration fields global
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
	global $pmpro_user_fields;
	
    /**
     * Filter the group to add the field to.
     * 
     * @since 2.9.3
     * 
     * @param string $where The name of the group to add the field to.
     * @param PMProField $field The field being added.
     */
    $where = apply_filters( 'pmpro_add_user_field_where', $where, $field );
    
    /**
     * Filter the field to add.
     * 
     * @since 2.9.3
     *             
     * @param PMProField $field The field being added.
     * @param string $where The name of the group to add the field to.
     */
    $field = apply_filters( 'pmpro_add_user_field', $field, $where );
    
    if(empty($pmpro_user_fields[$where])) {
		$pmpro_user_fields[$where] = array();
	}
	if ( ! empty( $field ) && pmpro_is_field( $field ) ) {
		$pmpro_user_fields[$where][] = $field;
		return true;
	}

	return false;
}

/**
 * Add a new checkout box to the checkout_boxes section.
 * You can then use this as the $where parameter
 * to pmprorh_add_registration_field.
 *
 * Name must contain no spaces or special characters.
 */
function pmpro_add_field_group( $name, $label = NULL, $description = '', $order = NULL ) {
	global $pmpro_field_groups;

	$temp = new stdClass();
	$temp->name = $name;
	$temp->label = $label;
	$temp->description = $description;
	$temp->order = $order;

	//defaults
	if( empty( $temp->label ) ) {
        $temp->label = ucwords($temp->name);
    }
	if( ! isset( $order ) ) {
		$lastbox = pmpro_array_end( $pmpro_field_groups );
		$temp->order = $lastbox->order + 1;
	}

	$pmpro_field_groups[$name] = $temp;
	usort( $pmpro_field_groups, 'pmpro_sort_by_order' );

	return true;
}

/**
 * Add a new User Taxonomy. You can then use this as the user_taxonomny parameter to pmprorh_add_registration_field.
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
		'name' => ucwords( $name ),
		'singular_name' => ucwords( $name ),
		'menu_name' => ucwords( $name_plural ),
		'search_items' => sprintf( __( 'Search %s', 'paid-memberships-pro' ), ucwords( $name_plural ) ),
		'popular_items' => sprintf( __( 'Popular %s', 'paid-memberships-pro' ), ucwords( $name_plural ) ),
		'all_items' => sprintf( __( 'All %s', 'paid-memberships-pro' ), ucwords( $name_plural ) ),
		'edit_item' => sprintf( __( 'Edit %s', 'paid-memberships-pro' ), ucwords( $name ) ),
		'update_item' => sprintf( __( 'Update %s', 'paid-memberships-pro' ), ucwords( $name ) ),
		'add_new_item' => sprintf( __( 'Add New %s', 'paid-memberships-pro' ), ucwords( $name ) ),
		'new_item_name' => sprintf( __( 'New %s Name', 'paid-memberships-pro' ), ucwords( $name ) ),
		'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'paid-memberships-pro' ), $name_plural ),
		'add_or_remove_items' => sprintf( __( 'Add or remove %s', 'paid-memberships-pro' ), $name_plural ),
		'choose_from_most_used' => sprintf( __( 'Choose from the most popular %s', 'paid-memberships-pro' ), $name_plural ),
	);
	
	/**
	 * Filter the args passed to the user taxonomy created.
	 *
	 * @param array $pmpro_user_taxonomy_args The arguments passed to the register_taxonomy function.
	 *
	 */
	$pmpro_user_taxonomy_args = apply_filters( 'pmpro_user_taxonomy_args', array(
			'public' => false,
			'labels' => $pmpro_user_taxonomy_labels,
			'rewrite' => false,
			'show_ui' => true,
			'capabilities' => array(
				'manage_terms' => 'edit_users',
				'edit_terms'   => 'edit_users',
				'delete_terms' => 'edit_users',
				'assign_terms' => 'read',
			),
		)
	);
	register_taxonomy( $safe_name, 'user', $pmpro_user_taxonomy_args );

	/**
	 * Add admin page for the registered user taxonomies.
	 */
	add_action( 'admin_menu', function() use ( $pmpro_user_taxonomy_labels, $safe_name ) {
		add_users_page(
			esc_attr( $pmpro_user_taxonomy_labels['menu_name'] ),
			esc_attr( $pmpro_user_taxonomy_labels['menu_name'] ),
			'edit_users',
			'edit-tags.php?taxonomy=' . $safe_name
		);
	} );

	/**
	 * Update parent file name to fix the selected menu issue for a user taaxonomy.
	 */
	add_filter( 'parent_file', function( $parent_file ) use ( $safe_name ) {
		global $submenu_file;
		if (
			isset($_GET['taxonomy']) &&
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
	global $pmpro_field_groups;
	if( ! empty( $pmpro_field_groups ) ) {
		foreach( $pmpro_field_groups as $group ) {
			if( $group->name == $name ) {
                return $group;
            }
		}
	}
	return false;
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
 * Find fields in a group and display them at checkout.
 */
function pmpro_display_fields_in_group( $group, $scope = 'checkout' ) {
    global $pmpro_user_fields;

	if( ! empty( $pmpro_user_fields[$group] ) ) {
		foreach( $pmpro_user_fields[$group] as $field ) {
			if ( ! pmpro_is_field( $field ) ) {
                continue;
            }
            
            if ( ! pmpro_check_field_for_level( $field ) ) {
                continue;
            }
            
            if ( $scope == 'checkout' ) {
                if( ! isset( $field->profile ) || $field->profile !== 'only' && $field->profile !== 'only_admin' ) {
    				$field->displayAtCheckout();
    			}
            }
		}
	}
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
add_action( 'pmpro_checkout_after_captcha', 'pmpro_checkout_after_captcha_fields' );

//checkout boxes
function pmpro_checkout_boxes_fields() {
	global $pmpro_user_fields, $pmpro_field_groups;

	foreach($pmpro_field_groups as $cb)
	{
		//how many fields to show at checkout?
		$n = 0;
		if(!empty($pmpro_user_fields[$cb->name]))
			foreach($pmpro_user_fields[$cb->name] as $field)
				if(pmpro_is_field($field) && pmpro_check_field_for_level($field) && (!isset($field->profile) || (isset($field->profile) && $field->profile !== "only" && $field->profile !== "only_admin")))		$n++;

		if($n > 0) {
			?>
			<div id="pmpro_checkout_box-<?php echo sanitize_title( $cb->name ); ?>" class="pmpro_checkout">
				<hr />
				<h2>
					<span class="pmpro_checkout-h2-name"><?php echo wp_kses_post( $cb->label );?></span>
				</h2>
				<div class="pmpro_checkout-fields">
				<?php if(!empty($cb->description)) { ?>
					<div class="pmpro_checkout_decription"><?php echo wp_kses_post( $cb->description ); ?></div>
				<?php } ?>

				<?php
					foreach($pmpro_user_fields[$cb->name] as $field) {
						if( pmpro_is_field($field) && pmpro_check_field_for_level($field) && (!isset($field->profile) || (isset($field->profile) && $field->profile !== "only" && $field->profile !== "only_admin"))) {
							$field->displayAtCheckout();
						}
					}
				?>
				</div> <!-- end pmpro_checkout-fields -->
			</div> <!-- end pmpro_checkout_box-name -->
			<?php
		}
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
add_action( 'pmpro_checkout_after_tos_fields', 'pmpro_checkout_after_tos_fields' );

/**
 * Update the fields at checkout.
 */
function pmpro_after_checkout_save_fields( $user_id, $order ) {
	global $pmpro_user_fields;

	//any fields?
	if(!empty($pmpro_user_fields))
	{
		//cycle through groups
		foreach($pmpro_user_fields as $where => $fields)
		{
			//cycle through fields
			foreach($fields as $field)
			{
                if( ! pmpro_is_field( $field ) ) {
                    continue;
                }
                
                if ( ! pmpro_check_field_for_level( $field, "profile", $user_id ) ) {
                    continue;
                }

				if(!empty($field->profile) && ($field->profile === "only" || $field->profile === "only_admin")) {
                    continue;	//wasn't shown at checkout
                }

				//assume no value
				$value = NULL;

				// Where are we getting the value from? We sanitize $value right after this.
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if(isset($_REQUEST[$field->name]))
				{
					//request
					$value = $_REQUEST[$field->name];
				}
				elseif(isset($_REQUEST[$field->name . '_checkbox']) && $field->type == 'checkbox')
				{
					//unchecked checkbox
					$value = 0;
				}
				elseif(!empty($_POST[$field->name . "_checkbox"]) && in_array( $field->type, array( 'checkbox', 'checkbox_grouped', 'select2' ) ) )	//handle unchecked checkboxes
				{
					//unchecked checkbox
					$value = array();
				}
				elseif(isset($_SESSION[$field->name]))
				{
					//file or value?
					if(is_array($_SESSION[$field->name]) && isset($_SESSION[$field->name]['name']))
					{
						//add to files global
						$_FILES[$field->name] = $_SESSION[$field->name];

						//set value to name
						$value = $_SESSION[$field->name]['name'];
					}
					else
					{
						//session
						$value = $_SESSION[$field->name];
					}

					//unset
					unset($_SESSION[$field->name]);
				}
				elseif(isset($_FILES[$field->name]))
				{
					//file
					$value = $_FILES[$field->name]['name'];
				}
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				//update user meta
				if(isset($value))
				{
					if ( ! empty( $field->sanitize ) ) {
						$value = pmpro_sanitize( $value, $field );
                    }

					//callback?
					if(!empty($field->save_function))
						call_user_func( $field->save_function, $user_id, $field->name, $value, $order );
					else
						update_user_meta($user_id, $field->meta_key, $value);
				}
			}
		}
	}
}
add_action( 'pmpro_after_checkout', 'pmpro_after_checkout_save_fields', 10, 2 );
add_action( 'pmpro_before_send_to_paypal_standard', 'pmpro_after_checkout_save_fields', 20, 2 );	//for paypal standard we need to do this just before sending the user to paypal
add_action( 'pmpro_before_send_to_twocheckout', 'pmpro_after_checkout_save_fields', 20, 2 );	//for 2checkout we need to do this just before sending the user to 2checkout
add_action( 'pmpro_before_send_to_gourl', 'pmpro_after_checkout_save_fields', 20, 2 );	//for the GoURL Bitcoin Gateway Add On
add_action( 'pmpro_before_send_to_payfast', 'pmpro_after_checkout_save_fields', 20, 2 );	//for the Payfast Gateway Add On

/**
 * Require required fields.
 */
function pmpro_registration_checks_for_user_fields( $okay ) {
	global $current_user;

	//arrays to store fields that were required and missed
	$required = array();
    $required_labels = array();

	//any fields?
	global $pmpro_user_fields;
	if(!empty($pmpro_user_fields))
	{
		//cycle through groups
		foreach($pmpro_user_fields as $where => $fields)
		{
			//cycle through fields
			foreach($fields as $field)
			{
                //handle arrays
                $field->name = preg_replace('/\[\]$/', '', $field->name);

				//if the field is not for this level, skip it
                if( ! pmpro_is_field( $field ) ) {
                    continue;
                }
                
                if ( ! pmpro_check_field_for_level( $field ) ) {
                    continue;
                }

				if(!empty($field->profile) && ($field->profile === "only" || $field->profile === "only_admin")) {
                    continue;	//wasn't shown at checkout
                }

				// If this is a file upload, check whether the file is allowed.
				if ( isset( $_FILES[ $field->name ] ) && ! empty( $_FILES[$field->name]['name'] ) ) {
					$upload_check = pmpro_check_upload( $field->name );
					if ( is_wp_error( $upload_check ) ) {
						pmpro_setMessage( $upload_check->get_error_message(), 'pmpro_error' );
						return false;
					}
				}

				if( ! $field->was_filled_if_needed() ) {
					$required[] = $field->name;
                    $required_labels[] = $field->label;
				}
			}
		}
	}

	if(!empty($required))
	{
		$required = array_unique($required);

		//add them to error fields
		global $pmpro_error_fields;
		$pmpro_error_fields = array_merge((array)$pmpro_error_fields, $required);

		if( count( $required ) == 1 ) {
			$pmpro_msg = sprintf( __( 'The %s field is required.', 'paid-memberships-pro' ),  implode(", ", $required_labels) );
			$pmpro_msgt = 'pmpro_error';
		} else {
			$pmpro_msg = sprintf( __( 'The %s fields are required.', 'paid-memberships-pro' ),  implode(", ", $required_labels) );
			$pmpro_msgt = 'pmpro_error';
		}

		if($okay)
			pmpro_setMessage($pmpro_msg, $pmpro_msgt);

		return false;
	}

	//return whatever status was before
	return $okay;
}
add_filter( 'pmpro_registration_checks', 'pmpro_registration_checks_for_user_fields' );

/**
 * Sessions vars for TwoCheckout. PayPal Express was updated to store in order meta.
 *
 * @deprecated 2.12.4 Use pmpro_after_checkout_save_fields instead to save fields immediately or pmpro_save_checkout_data_to_order for delayed checkouts.
 */
function pmpro_paypalexpress_session_vars_for_user_fields() {
	global $pmpro_user_fields;

	_deprecated_function( __FUNCTION__, '2.12.4', 'pmpro_after_checkout_save_fields' );

	//save our added fields in session while the user goes off to PayPal
	if(!empty($pmpro_user_fields))
	{
		//cycle through groups
		foreach($pmpro_user_fields as $where => $fields)
		{
			//cycle through fields
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
}

/**
 * Show user fields in profile.
 */
function pmpro_show_user_fields_in_profile( $user, $withlocations = false ) {
	global $pmpro_user_fields;

	//which fields are marked for the profile
	$profile_fields = pmpro_get_user_fields_for_profile($user->ID, $withlocations);

	//show the fields
	if(!empty($profile_fields) && $withlocations)
	{
		foreach($profile_fields as $where => $fields)
		{
			$box = pmpro_get_field_group_by_name($where);

			if ( !empty($box->label) ) {
				?>
				<h2><?php echo wp_kses_post( $box->label ); ?></h2>
				<?php
				if ( ! empty( $box->description ) ) {
					?>
					<p><?php echo wp_kses_post( $box->description ); ?></p>
					<?php
				}
			}
			?>
			

			<table class="form-table">
			<?php
			//cycle through groups
			foreach($fields as $field)
			{
				if ( pmpro_is_field( $field ) )
					$field->displayInProfile($user->ID);
			}
			?>
			</table>
			<?php
		}
	}
	elseif(!empty($profile_fields))
	{
		?>
		<table class="form-table">
		<?php
		//cycle through groups
		foreach($profile_fields as $field)
		{
			if ( pmpro_is_field( $field ) ) {
                $field->displayInProfile($user->ID);
            }
		}
		?>
		</table>
		<?php
	}
}
function pmpro_show_user_fields_in_profile_with_locations( $user ) {
	pmpro_show_user_fields_in_profile($user, true);
}
add_action( 'show_user_profile', 'pmpro_show_user_fields_in_profile_with_locations' );
add_action( 'edit_user_profile', 'pmpro_show_user_fields_in_profile_with_locations' );

/**
 * Show Profile fields on the frontend "Member Profile Edit" page.
 *
 * @since 2.3
 */
function pmpro_show_user_fields_in_frontend_profile( $user, $withlocations = false ) {
	global $pmpro_user_fields;

	//which fields are marked for the profile
	$profile_fields = pmpro_get_user_fields_for_profile($user->ID, $withlocations);

	//show the fields
	if ( ! empty( $profile_fields ) && $withlocations ) {
		foreach( $profile_fields as $where => $fields ) {
			$box = pmpro_get_field_group_by_name( $where );

			// Only show on front-end if there are fields to be shown.
			$show_fields = false;
			foreach( $fields as $key => $field ) {
				if ( $field->profile !== 'only_admin' ) {
					$show_fields = true;
				}
			}

			// Bail if there are no fields to show on the front-end profile.
			if ( ! $show_fields ) {
				continue;
			}
			?>

			<div class="pmpro_checkout_box-<?php echo sanitize_title( $where ); ?>">
				<?php if ( ! empty( $box->label ) ) { ?>
					<h2><?php echo wp_kses_post( $box->label ); ?></h2>
				<?php } ?>

				<div class="pmpro_member_profile_edit-fields">
					<?php if ( ! empty( $box->description ) ) { ?>
						<div class="pmpro_checkout_description"><?php echo wp_kses_post( $box->description ); ?></div>
					<?php } ?>

					<?php
						 // Cycle through groups.
						foreach( $fields as $field ) {
							if ( pmpro_is_field( $field ) && $field->profile !== 'only_admin' ) {
								$field->displayAtCheckout( $user->ID );
							}
						}
					?>
				</div> <!-- end pmpro_member_profile_edit-fields -->
			</div> <!-- end pmpro_checkout_box-name -->
			<?php
		}
	} elseif ( ! empty( $profile_fields ) ) { ?>
		<div class="pmpro_member_profile_edit-fields">
			<?php
				 // Cycle through groups.
				foreach( $profile_fields as $field ) {
					if ( pmpro_is_field( $field ) && $field->profile !== 'only_admin' ) {
						$field->displayAtCheckout( $user->ID );
					}
				}
			?>
		</div> <!-- end pmpro_member_profile_edit-fields -->
		<?php
	}
}
function pmpro_show_user_fields_in_frontend_profile_with_locations( $user ) {
	pmpro_show_user_fields_in_frontend_profile($user, true);
}
add_action( 'pmpro_show_user_profile', 'pmpro_show_user_fields_in_frontend_profile_with_locations' );

/**
 * Show user fields on the Add Member form
 * when using the Add Member Admin Add On.
 */
// Add fields to form.
function pmpro_add_member_admin_fields( $user = null, $user_id = null)
{
    global $pmpro_user_fields;

    $addmember_fields = array();
    if(!empty($pmpro_user_fields))
    {
        //cycle through groups
        foreach($pmpro_user_fields as $where => $fields)
        {
            //cycle through fields
            foreach($fields as $field)
            {
	            if(pmpro_is_field($field) && isset($field->addmember) && !empty($field->addmember) && ( in_array( strtolower( $field->addmember ), array( 'true', 'yes' ) ) || true == $field->addmember ) )
                {
                        $addmember_fields[] = $field;
                }
            }
        }
    }


    //show the fields
    if(!empty($addmember_fields))
    {
        ?>
            <?php
            //cycle through groups
            foreach($addmember_fields as $field)
            {
				if(empty($user_id) && !empty($user) && !empty($user->ID)) {
					$user_id = $user->ID;
				}

		    		if( pmpro_is_field( $field ) ) {
                        $field->displayInProfile($user_id);
                    }
					
            }
            ?>
    <?php
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
	global $pmpro_user_fields;
	
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

		pmpro_setMessage( __( 'Unable to add/update user fields for this member', 'paid-memberships-pro' ), 'pmpro_error' );

		return false;
	}

    $addmember_fields = array();
    if(!empty($pmpro_user_fields))
    {
        //cycle through groups
        foreach($pmpro_user_fields as $where => $fields)
        {
            //cycle through fields
            foreach($fields as $field)
            {
	            if(pmpro_is_field($field) && isset($field->addmember) && !empty($field->addmember) && ( in_array( strtolower( $field->addmember ), array( 'true', 'yes' ) ) || true == $field->addmember ) )
                {
                        $addmember_fields[] = $field;
                }
            }
        }
    }

    //save our added fields in session while the user goes off to PayPal
    if(!empty($addmember_fields))
    {
        //cycle through fields
        foreach($addmember_fields as $field)
        {
            if(pmpro_is_field($field) && isset($_POST[$field->name]) || isset($_FILES[$field->name]))
            {
	            // Sanitize by default, or not. Some fields may have custom save functions/etc.
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( ! empty( $field->sanitize ) && isset( $_POST[ $field->name ] ) ) {
		            $value = pmpro_sanitize( $_POST[ $field->name ], $field );
	            } elseif( isset($_POST[$field->name]) ) {
	                $value = $_POST[ $field->name ];
                } else {
                	$value = $_FILES[$field->name];
                }
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                //callback?
                if(!empty($field->save_function))
                    call_user_func($field->save_function, $user_id, $field->name, $value );
                else
                    update_user_meta($user_id, $field->meta_key, $value );
            }
            elseif(pmpro_is_field($field) && !empty($_POST[$field->name . "_checkbox"]) && $field->type == 'checkbox')	//handle unchecked checkboxes
            {
                //callback?
                if(!empty($field->save_function))
                    call_user_func($field->save_function, $user_id, $field->name, 0);
                else
                    update_user_meta($user_id, $field->meta_key, 0);
			}
			elseif(!empty($_POST[$field->name . "_checkbox"]) && in_array( $field->type, array( 'checkbox', 'checkbox_grouped', 'select2' ) ) )	//handle unchecked checkboxes
			{
				//callback?
				if(!empty($field->save_function))
					call_user_func($field->save_function, $user_id, $field->name, array());
				else
					update_user_meta($user_id, $field->meta_key, array());
			}
        }
    }
}
add_action( 'pmpro_add_member_added', 'pmpro_add_member_admin_save_user_fields', 10, 2 );

/**
 * Get RH fields which are set to showup in the Members List CSV Export.
 */
function pmpro_get_user_fields_for_csv() {
	global $pmpro_user_fields;

	$csv_fields = array();
	if(!empty($pmpro_user_fields))
	{
		//cycle through groups
		foreach($pmpro_user_fields as $where => $fields)
		{
			//cycle through fields
			foreach($fields as $field)
			{
				if(pmpro_is_field($field) && !empty($field->memberslistcsv) && ($field->memberslistcsv == "true"))
				{
					$csv_fields[] = $field;
				}

			}
		}
	}

	return $csv_fields;
}

/**
 * Get user fields which are marked to show in the profile.
 * If a $user_id is passed in, get fields based on the user's level.
 */
function pmpro_get_user_fields_for_profile( $user_id, $withlocations = false ) {
	global $pmpro_user_fields;

	$profile_fields = array();
	if(!empty($pmpro_user_fields))
	{
		//cycle through groups
		foreach($pmpro_user_fields as $where => $fields)
		{
			//cycle through fields
			foreach($fields as $field)
			{
				if( ! pmpro_is_field( $field ) ) {
                    continue;
                }
                
                if ( ! pmpro_check_field_for_level( $field, "profile", $user_id ) ) {
                    continue;
                }

				if(!empty($field->profile) && ($field->profile === "admins" || $field->profile === "admin" || $field->profile === "only_admin"))
				{
					if( current_user_can( 'manage_options' ) || current_user_can( 'pmpro_membership_manager' ) )
					{
						if($withlocations)
							$profile_fields[$where][] = $field;
						else
							$profile_fields[] = $field;
					}
				}
				elseif(!empty($field->profile))
				{
					if($withlocations)
						$profile_fields[$where][] = $field;
					else
						$profile_fields[] = $field;
				}
			}
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

	$profile_fields = pmpro_get_user_fields_for_profile($user_id);

	//save our added fields in session while the user goes off to PayPal
	if(!empty($profile_fields))
	{
		//cycle through fields
		foreach($profile_fields as $field)
		{
            if( ! pmpro_is_field( $field ) ) {
                continue;
            }

			if(isset($_POST[$field->name]) || isset($_FILES[$field->name]))
			{
				// Sanitize by default, or not. Some fields may have custom save functions/etc.
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( ! empty( $field->sanitize ) && isset( $_POST[ $field->name ] ) ) {
					$value = pmpro_sanitize( $_POST[ $field->name ], $field );
				} elseif( isset($_POST[$field->name]) ) {
				    $value = $_POST[ $field->name ];
                } else {
                	$value = $_FILES[$field->name];
                }
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				//callback?
				if(!empty($field->save_function))
					call_user_func($field->save_function, $user_id, $field->name, $value);
				else
					update_user_meta($user_id, $field->meta_key, $value);
			}
			elseif(!empty($_POST[$field->name . "_checkbox"]) && $field->type == 'checkbox')	//handle unchecked checkboxes
			{
				//callback?
				if(!empty($field->save_function))
					call_user_func($field->save_function, $user_id, $field->name, 0);
				else
					update_user_meta($user_id, $field->meta_key, 0);
			}
			elseif(!empty($_POST[$field->name . "_checkbox"]) && in_array( $field->type, array( 'checkbox', 'checkbox_grouped', 'select2' ) ) )	//handle unchecked checkboxes
			{
				//callback?
				if(!empty($field->save_function))
					call_user_func($field->save_function, $user_id, $field->name, array());
				else
					update_user_meta($user_id, $field->meta_key, array());
			}
		}
	}
}
add_action( 'personal_options_update', 'pmpro_save_user_fields_in_profile' );
add_action( 'edit_user_profile_update', 'pmpro_save_user_fields_in_profile' );
add_action( 'pmpro_personal_options_update', 'pmpro_save_user_fields_in_profile' );

/**
 * Add user fields to confirmation email.
 */
function pmpro_add_user_fields_to_email( $email ) {
	global $wpdb, $pmpro_user_fields, $pmpro_field_groups;

	//only update admin confirmation emails
	if ( ! empty( $email ) && strpos( $email->template, "checkout" ) !== false && strpos( $email->template, "admin" ) !== false ) {
		//get the user_id from the email
		$user_id = $wpdb->get_var( "SELECT ID FROM $wpdb->users WHERE user_email = '" . esc_sql( $email->data['user_email'] ) . "' LIMIT 1" );
		$level_id = empty( $email->data['membership_id'] ) ? null : intval( $email->data['membership_id'] );

		if ( ! empty( $user_id ) ) {


			//add to bottom of email
			if ( ! empty( $pmpro_field_groups ) ) {
				$fields_content = "<p>" . __( 'Extra Fields:', 'paid-memberships-pro' ) . "<br />";
				$added_field = false;
				//cycle through groups
				foreach( $pmpro_field_groups as $group ) {

					// Get the groups name so we can grab it from the associative array.
					$group_name = $group->name;

					// Skip if there are no fields in this group.
					if ( empty( $pmpro_user_fields[$group_name] ) ) {
						continue;
					}
					
					//cycle through groups and fields associated with that group.
					foreach( $pmpro_user_fields[$group_name] as $field ) {

						if ( ! pmpro_is_field( $field ) ) {
							continue;
						}

						// If the field is showing only in the profile or to admins we can skip it.
						if ( ! empty( $field->profile ) && ( $field->profile === "only" || $field->profile === "only_admin" ) ) {
							continue;
						}

						// Let's make sure the field level ID's are the same as the one they checked out for.
						if ( ! empty( $field->levels ) &&  ( empty( $level_id) || ! in_array( $level_id, $field->levels ) ) ) {
							continue;
						}

						$fields_content .= "- " . esc_html( $field->label ) . ": ";
						$value = get_user_meta( $user_id, $field->name, true);

						// Get the label value for field types that have labels.
						$value = pmpro_get_label_for_user_field_value( $field->name, $value );

						if ( $field->type == "file" && is_array( $value ) && ! empty( $value['fullurl'] ) ) {
							$fields_content .= pmpro_sanitize( $value['fullurl'], $field );
						} elseif( is_array( $value  ) ) {
							$fields_content .= implode(", ", pmpro_sanitize( $value, $field ) );
						} else {
							$fields_content .= pmpro_sanitize( $value, $field );
						}

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
 * Delete old files in wp-content/uploads/pmpro-register-helper/tmp every day.
 */
function pmpro_cron_delete_tmp() {
	$upload_dir = wp_upload_dir();
	$pmprorh_dir = $upload_dir['basedir'] . "/paid-memberships-pro/tmp/";

	if(file_exists($pmprorh_dir) && $handle = opendir($pmprorh_dir))
	{
		while(false !== ($file = readdir($handle)))
		{
			$file = $pmprorh_dir . $file;
			$filelastmodified = filemtime($file);
			if(is_file($file) && (time() - $filelastmodified) > 3600)
			{
				unlink($file);
			}
		}

		closedir($handle);
	}

	exit;
}
add_action( 'pmpro_cron_delete_tmp', 'pmpro_cron_delete_tmp' );

/**
 * Get user fields from global.
 * @since 2.9.3
 */
function pmpro_get_user_fields() {
    global $pmpro_user_fields;
        
    return (array)$pmpro_user_fields;
}

// Code for the user fields settings page.
/**
 * Get field group HTML for settings.
 */
function pmpro_get_field_group_html( $group = null ) {
    if ( ! empty( $group ) ) {
        // Assume group stdClass in format we save to settings.
        $group_name = $group->name;
    	$group_show_checkout = $group->checkout;
    	$group_show_profile = $group->profile;
    	$group_description = $group->description;    	
    	$group_levels = $group->levels;
        $group_fields = $group->fields;
    } else {
        // Default group settings.
        $group_name = '';
    	$group_show_checkout = 'yes';
    	$group_show_profile = 'yes';
    	$group_description = '';    	
    	$group_levels = array();
        $group_fields = array();
    }
    
    // Other vars
	$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );

    // Render field group HTML.
    ?>
    <div class="pmpro_userfield-group">
        <div class="pmpro_userfield-group-header">
            <div class="pmpro_userfield-group-buttons">
                <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-move-up" aria-label="<?php esc_attr_e( 'Move up', 'paid-memberships-pro' ); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
                <span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Up', 'paid-memberships-pro' ); ?></span>

                <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-move-down" aria-label="<?php esc_attr_e( 'Move down', 'paid-memberships-pro' ); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <span id="pmpro_userfield-group-buttons-description-2" class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Group Down', 'paid-memberships-pro' ); ?></span>
            </div> <!-- end pmpro_userfield-group-buttons -->
            <h3>
                <label>                    
                    <?php esc_html_e( 'Group Name', 'paid-memberships-pro' ); ?>
                    <input type="text" name="pmpro_userfields_group_name" placeholder="<?php esc_attr_e( 'Group Name', 'paid-memberships-pro' ); ?>" value="<?php echo esc_attr( $group_name ); ?>" />
                </label>                
            </h3>
            <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-group-buttons-button-toggle-group" aria-label="<?php esc_attr_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?>">
                <span class="dashicons dashicons-arrow-up"></span>
            </button>
            <span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Expand and Edit Group', 'paid-memberships-pro' ); ?></span>
        </div> <!-- end pmpro_userfield-group-header -->

        <div class="pmpro_userfield-inside">
			<div class="pmpro_userfield-field-settings">
				
				<div class="pmpro_userfield-field-setting">
					<label>
                        <?php esc_html_e( 'Show fields at checkout?', 'paid-memberships-pro' ); ?><br />
    					<select name="pmpro_userfields_group_checkout">
    						<option value="yes" <?php selected( $group_show_checkout, 'yes' ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
    						<option value="no" <?php selected( $group_show_checkout, 'no' ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
    					</select>
                    </label>
				</div> <!-- end pmpro_userfield-field-setting -->
				
				<div class="pmpro_userfield-field-setting">
					<label>
                        <?php esc_html_e( 'Show fields on user profile?', 'paid-memberships-pro' ); ?><br />
                        <select name="pmpro_userfields_group_profile">
    						<option value="yes" <?php selected( $group_show_profile, 'yes' ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
    						<option value="admins" <?php selected( $group_show_profile, 'admins' ); ?>><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
    						<option value="no" <?php selected( $group_show_profile, 'no' ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
    					</select>
                    </label>
				</div> <!-- end pmpro_userfield-field-setting -->
				
				<div class="pmpro_userfield-field-setting">
					<label>
                        <?php esc_html_e( 'Description (optional, visible to users)', 'paid-memberships-pro' ); ?><br />
					    <textarea name="pmpro_userfields_group_description"><?php echo esc_textarea( $group_description );?></textarea>
                    </label>
				</div> <!-- end pmpro_userfield-field-setting -->
				
				<div class="pmpro_userfield-field-setting">
                    <?php esc_html_e( 'Restrict Fields for Membership Levels', 'paid-memberships-pro' ); ?><br />
                    <div class="pmpro_checkbox_box" <?php if ( count( $levels ) > 3 ) { ?>style="height: 90px; overflow: auto;"<?php } ?>>
						<?php foreach( $levels as $level ) { ?>
							<div class="pmpro_clickable">
                                <label>
                                    <input type="checkbox" id="pmpro_userfields_group_membership_<?php echo esc_attr( $level->id); ?>" name="pmpro_userfields_group_membership[]" <?php checked( true, in_array( $level->id, $group_levels ) );?>>
                                    <?php echo esc_html( $level->name ); ?>
                                </label>
                            </div>
						<?php } ?>
					</div>
				</div> <!-- end pmpro_userfield-field-setting -->
			
			</div> <!-- end pmpro_userfield-field-settings -->
			
			<h3><?php esc_html_e( 'Manage Fields in This Group', 'paid-memberships-pro' ); ?></h3>
			
			<ul class="pmpro_userfield-group-thead">
				<li class="pmpro_userfield-group-column-order"><?php esc_html_e( 'Order', 'paid-memberships-pro'); ?></li>
				<li class="pmpro_userfield-group-column-label"><?php esc_html_e( 'Label', 'paid-memberships-pro'); ?></li>
				<li class="pmpro_userfield-group-column-name"><?php esc_html_e( 'Name', 'paid-memberships-pro'); ?></li>
				<li class="pmpro_userfield-group-column-type"><?php esc_html_e( 'Type', 'paid-memberships-pro'); ?></li>
			</ul>
			
			<div class="pmpro_userfield-group-fields">
				<?php
					if ( ! empty( $group->fields ) ) {
						foreach ( $group->fields as $field ) {
							echo pmpro_get_field_html( $field );
						}
					}
                ?>
                
                <!-- end pmpro_userfield-group-fields -->
            
            </div> <!-- end pmpro_userfield-inside -->

			<div class="pmpro_userfield-group-actions">
				<button name="pmpro_userfields_add_field" class="button button-secondary button-hero">
					<?php
						/* translators: a plus sign dashicon */
						printf( esc_html__( '%s Add Field', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
				</button>
                <button name="pmpro_userfields_delete_group" class="button button-secondary is-destructive">
                    <?php esc_html_e( 'Delete Group', 'paid-memberships-pro' ); ?>
                </button>
			</div> <!-- end pmpro_userfield-group-actions -->

		</div> <!-- end pmpro_userfield-group -->
    </div> <!-- end inside -->
    <?php
}
 
/**
 * Get field HTML for settings.
 */
function pmpro_get_field_html( $field = null ) {
    if ( ! empty( $field ) ) {
        // Assume field stdClass in format we save to settings.
        $field_label = $field->label;
        $field_name = $field->name;
        $field_type = $field->type;
        $field_required = $field->required;
        $field_readonly = $field->readonly;     	
        $field_profile = $field->profile;
        $field_wrapper_class = $field->wrapper_class;
        $field_element_class = $field->element_class;
        $field_hint = $field->hint;
        $field_options = $field->options;
    } else {
        // Default field values
        $field_label = '';
        $field_name = '';
        $field_type = '';
        $field_required = false;
        $field_readonly = false;
        $field_profile = '';
        $field_wrapper_class = '';
        $field_element_class = '';
        $field_hint = '';
        $field_options = '';
    }
    
	// Other vars
	$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );
	?>
    <div class="pmpro_userfield-group-field pmpro_userfield-group-field-collapse">
        <ul class="pmpro_userfield-group-tbody">
            <li class="pmpro_userfield-group-column-order">
                <div class="pmpro_userfield-group-buttons">
                    <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-field-buttons-button-move-up" aria-label="<?php esc_attr_e( 'Move up', 'paid-memberships-pro' ); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Up', 'paid-memberships-pro' ); ?></span>

                    <button type="button" aria-disabled="false" class="pmpro_userfield-group-buttons-button pmpro_userfield-field-buttons-button-move-down" aria-label="<?php esc_attr_e( 'Move down', 'paid-memberships-pro' ); ?>">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <span class="pmpro_userfield-group-buttons-description"><?php esc_html_e( 'Move Field Down', 'paid-memberships-pro' ); ?></span>
                </div> <!-- end pmpro_userfield-group-buttons -->
            </li>
            <li class="pmpro_userfield-group-column-label">
                <span class="pmpro_userfield-label"><?php echo strip_tags( wp_kses_post( $field_label ) );?></span>
                <div class="pmpro_userfield-field-options">
                    <a class="edit-field" title="<?php esc_attr_e( 'Edit field', 'paid-memberships-pro' ); ?>" href="javascript:void(0);"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a> |
                    <a class="duplicate-field" title="<?php esc_attr_e( 'Duplicate field', 'paid-memberships-pro' ); ?>" href="javascript:void(0);"><?php esc_html_e( 'Duplicate', 'paid-memberships-pro' ); ?></a> |
                    <a class="delete-field" title="<?php esc_attr_e( 'Delete field', 'paid-memberships-pro' ); ?>" href="javascript:void(0);"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></a>
                </div> <!-- end pmpro_userfield-group-options -->
            </li>
            <li class="pmpro_userfield-group-column-name"><?php echo esc_html( $field_name); ?></li>
            <li class="pmpro_userfield-group-column-type"><?php echo esc_html( $field_type); ?></li>
        </ul>

        <div class="pmpro_userfield-field-settings" style="display: none;">

            <div class="pmpro_userfield-field-setting">
                <label>
                    <?php esc_html_e( 'Label', 'paid-memberships-pro' ); ?><br />
                    <input type="text" name="pmpro_userfields_field_label" value="<?php echo esc_attr( $field_label );?>" />                    
                </label>                
                <span class="description"><?php esc_html_e( 'Brief descriptive text for the field. Shown on user forms.', 'paid-memberships-pro' ); ?></span>
            </div> <!-- end pmpro_userfield-field-setting -->

            <div class="pmpro_userfield-field-setting">
                <label>
                    <?php esc_html_e( 'Name', 'paid-memberships-pro' ); ?><br />
                    <input type="text" name="pmpro_userfields_field_name" value="<?php echo esc_attr( $field_name );?>" />
                </label>                
                <span class="description"><?php esc_html_e( 'Single word with no spaces. Underscores are allowed.', 'paid-memberships-pro' ); ?></span>
            </div> <!-- end pmpro_userfield-field-setting -->

            <div class="pmpro_userfield-field-setting">
                <label>
                    <?php esc_html_e( 'Type', 'paid-memberships-pro' ); ?><br />
                    <select name="pmpro_userfields_field_type" />
                        <option value="text" <?php selected( $field_type, 'text' ); ?>><?php esc_html_e( 'Text', 'paid-memberships-pro' ); ?></option>
                        <option value="textarea" <?php selected( $field_type, 'textarea' ); ?>><?php esc_html_e( 'Text Area', 'paid-memberships-pro' ); ?></option>
                        <option value="checkbox" <?php selected( $field_type, 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'paid-memberships-pro' ); ?></option>
                        <option value="radio" <?php selected( $field_type, 'radio' ); ?>><?php esc_html_e( 'Radio', 'paid-memberships-pro' ); ?></option>
                        <option value="select" <?php selected( $field_type, 'select' ); ?>><?php esc_html_e( 'Select / Dropdown', 'paid-memberships-pro' ); ?></option>
                        <option value="select2" <?php selected( $field_type, 'select2' ); ?>><?php esc_html_e( 'Select2 / Autocomplete', 'paid-memberships-pro' ); ?></option>
                        <option value="multiselect" <?php selected( $field_type, 'multiselect' ); ?>><?php esc_html_e( 'Multi Select', 'paid-memberships-pro' ); ?></option>
                        <option value="file" <?php selected( $field_type, 'file' ); ?>><?php esc_html_e( 'File', 'paid-memberships-pro' ); ?></option>
                        <option value="number" <?php selected( $field_type, 'number' ); ?>><?php esc_html_e( 'Number', 'paid-memberships-pro' ); ?></option>
                        <option value="date" <?php selected( $field_type, 'date' ); ?>><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></option>
                        <option value="readonly" <?php selected( $field_type, 'readonly' ); ?>><?php esc_html_e( 'Read-Only', 'paid-memberships-pro' ); ?></option>
                        <option value="hidden" <?php selected( $field_type, 'hidden' ); ?>><?php esc_html_e( 'Hidden', 'paid-memberships-pro' ); ?></option>
                    </select>
                </label>                
            </div> <!-- end pmpro_userfield-field-setting -->

            <div class="pmpro_userfield-field-setting pmpro_userfield-field-setting-dual">
                <div class="pmpro_userfield-field-setting">
                    <label>
                        <?php esc_html_e( 'Required at Checkout?', 'paid-memberships-pro' ); ?><br />
                        <select name="pmpro_userfields_field_required">
                            <option value="no" <?php selected( $field_required, 'no' );?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
                            <option value="yes" <?php selected( $field_required, 'yes' );?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
                        </select>
                    </label>                    
                </div> <!-- end pmpro_userfield-field-setting -->

                <div class="pmpro_userfield-field-setting">
                    <label>
                        <?php esc_html_e( 'Read Only?', 'paid-memberships-pro' ); ?><br />
                        <select name="pmpro_userfields_field_readonly">
                            <option value="no" <?php selected( $field_readonly, 'no' );?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
                            <option value="yes" <?php selected( $field_readonly, 'yes' );?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
                        </select>
                    </label>                    
                </div> <!-- end pmpro_userfield-field-setting -->
            </div> <!-- end pmpro_userfield-field-setting-dual -->

            <div class="pmpro_userfield-field-setting">
                <label>
                    <?php esc_html_e( 'Show field on user profile?', 'paid-memberships-pro' ); ?><br />
                    <select name="pmpro_userfields_field_profile">
                        <option value="" <?php selected( empty( $field_profile ), 0);?>><?php esc_html_e( '[Inherit Group Setting]', 'paid-memberships-pro' ); ?></option>
                        <option value="yes" <?php selected( $field_profile, 'yes' );?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
                        <option value="admins" <?php selected( $field_profile, 'admins' );?>><?php esc_html_e( 'Yes (only admins)', 'paid-memberships-pro' ); ?></option>
                        <option value="no" <?php selected( $field_profile, 'no' );?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
                    </select>
                </label>                
            </div> <!-- end pmpro_userfield-field-setting -->

            <div class="pmpro_userfield-field-setting pmpro_userfield-field-setting-dual">
                <div class="pmpro_userfield-field-setting">
                    <label>
                        <?php esc_html_e( 'Field Wrapper Class (optional)', 'paid-memberships-pro' ); ?><br />
                        <input type="text" name="pmpro_userfields_field_class" value="<?php echo esc_attr( $field_wrapper_class );?>" />
                    </label>
                    <span class="description"><?php esc_html_e( 'Assign a custom CSS selector to the field\'s wrapping div', 'paid-memberships-pro' ); ?>.</span>
                </div> <!-- end pmpro_userfield-field-setting -->

                <div class="pmpro_userfield-field-setting">
                    <label>
                        <?php esc_html_e( 'Field Element Class (optional)', 'paid-memberships-pro' ); ?><br />
                        <input type="text" name="pmpro_userfields_field_divclass" value="<?php echo esc_attr( $field_element_class );?>" />
                    </label>                
                    <span class="description"><?php esc_html_e( 'Assign a custom CSS selector to the field', 'paid-memberships-pro' ); ?></span>
                </div> <!-- end pmpro_userfield-field-setting -->
            </div> <!-- end pmpro_userfield-field-setting-dual -->

            <div class="pmpro_userfield-field-setting">
                <label>
                    <?php esc_html_e( 'Hint (optional)', 'paid-memberships-pro' ); ?><br />
                    <textarea name="pmpro_userfields_field_hint" /><?php echo esc_textarea( $field_hint );?></textarea>
                </label>                
                <span class="description"><?php esc_html_e( 'Descriptive text for users or admins submitting the field.', 'paid-memberships-pro' ); ?></span>
            </div> <!-- end pmpro_userfield-field-setting -->
            
            <div class="pmpro_userfield-field-setting">
                <label>
                    <?php esc_html_e( 'Options', 'paid-memberships-pro' ); ?><br />
                    <textarea name="pmpro_userfields_field_options" /><?php echo esc_textarea( $field_options );?></textarea>
                </label>
                <span class="description"><?php esc_html_e( 'One option per line. To set separate values and labels, use value:label.', 'paid-memberships-pro' ); ?></span>
            </div> <!-- end pmpro_userfield-field-setting -->
            
            <div class="pmpro_userfield-field-actions">            
                <button name="pmpro_userfields_close_field" class="button button-secondary pmpro_userfields_close_field">
                    <?php esc_html_e( 'Close Field', 'paid-memberships-pro' ); ?>
                </button> 
				<button name="pmpro_userfields_delete_field" class="button button-secondary is-destructive">
                    <?php _e( 'Delete Field', 'paid-memberships-pro' ); ?>
                </button>           
            </div> <!-- end pmpro_userfield-field-actions -->
        </div> <!-- end pmpro_userfield-field-settings -->        
    </div> <!-- end pmpro_userfield-group-field -->
    <?php
}

/**
 * Get user fields from options.
 *
 * This function will not return fields that are added through code.
 */
function pmpro_get_user_fields_settings() {
    $default_user_fields_settings = array(
        (object) array(
            'name' => __( 'More Information', 'paid-memberships-pro' ),
            'checkout' => 'yes',
            'profile' => 'yes',
            'description' => '',
            'levels' => array(),
            'fields' => array(),
        )
    );
    
    $settings = get_option( 'pmpro_user_fields_settings', $default_user_fields_settings );
    
    // TODO: Might want to validate the format the settings are in here.
    
    return $settings;
}

/**
 * Load user field settings into the fields global var.
 */
function pmpro_load_user_fields_from_settings() {
    global $pmpro_user_fields, $pmpro_field_groups;
    $settings_groups = pmpro_get_user_fields_settings();

    foreach ( $settings_groups as $group ) {
        pmpro_add_field_group( $group->name, $group->name, $group->description );
        
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
            $option_types = array( 'radio', 'select', 'select2', 'multiselect' );
            if ( in_array( $settings_field->type, $option_types ) ) {
                $options = array();
                $settings_options = explode( "\n", $settings_field->options );
                foreach( $settings_options as $settings_option ) {
                    if ( strpos( $settings_option, ':' ) !== false ) {
                        $parts = explode( ':', $settings_option );
                        $options[trim( $parts[0] )] = trim( $parts[1] );
                    } else {
                        $options[] = $settings_option;
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
                )
            );
            pmpro_add_user_field( $group->name, $field );
        }
    }        
}
add_action( 'init', 'pmpro_load_user_fields_from_settings', 1 );

/**
 * Check if user is adding custom user fields with code.
 *
 * @since 2.9
 *
 * @return bool True if user is adding custom user fields with code.
 */
function pmpro_has_coded_user_fields() {
	global $pmpro_user_fields, $pmprorh_registration_fields;

	// Check if coded fields are being added using the PMPro Register Helper Add On active.
	if ( ! empty( $pmprorh_registration_fields ) ) {
		return true;
	}

	// Check if coded fields are being added using the PMPro Register Helper Add On inactive.
	$num_db_fields = array_sum( array_map( function ($group) { return count( $group->fields ); }, pmpro_get_user_fields_settings() ) ); // Fields from UI settings page.
	$num_global_fields = array_sum( array_map( 'count', $pmpro_user_fields ) ); // Total loaded fields.
	return $num_global_fields > $num_db_fields;
}

/**
 * Gets the label(s) for a passed user field value.
 *
 * @since 2.11
 *
 * @param string $field_name  The name of the field that the value belongs to.
 * @param string|array $field_value The value to get the label for.
 *
 * @return string|array The label(s) for the passed value. Will be same type as $field_value.
 */
function pmpro_get_label_for_user_field_value( $field_name, $field_value ) {
	global $pmpro_user_fields;
	foreach ( $pmpro_user_fields as $user_field_group ) { // Loop through each user field group.
		foreach ( $user_field_group as $user_field ) { // Loop through each user field in the group.
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
			if ( is_array( $field_value ) ) {
				foreach ( $field_value as $key => $value ) {
					if ( isset( $user_field->options[ $value ] ) ) {
						$field_value[ $key ] = $user_field->options[ $value ];
					}
				}
			} else {
				if ( isset( $user_field->options[ $field_value ] ) ) {
					$field_value = $user_field->options[ $field_value ];
				}
			}
		}
	}
	return $field_value;
}
