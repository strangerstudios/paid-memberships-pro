<?php

/**
 * Filter the normal avatar data and show our avatar if set.
 *
 * @since TBD
 *
 * @param array $args        Arguments passed to get_avatar_data(), after processing.
 * @param mixed $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
 *                           user email, WP_User object, WP_Post object, or WP_Comment object.
 * @return array             The filtered avatar data.
 */
function pmpro_get_avatar_data( $args, $id_or_email ) {
	if ( ! empty( $args['force_default'] ) ) {
		return $args;
	}

	// Determine if we received an ID or string. Then, set the $user_id variable.
	if ( is_numeric( $id_or_email ) && 0 < $id_or_email ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) && 0 < $id_or_email->user_id ) {
		$user_id = $id_or_email->user_id;
	} elseif ( is_object( $id_or_email ) && isset( $id_or_email->ID ) && isset( $id_or_email->user_login ) && 0 < $id_or_email->ID ) {
		$user_id = $id_or_email->ID;
	} elseif ( is_string( $id_or_email ) && false !== strpos( $id_or_email, '@' ) ) {
		$_user = get_user_by( 'email', $id_or_email );

		if ( ! empty( $_user ) ) {
			$user_id = $_user->ID;
		}
	}

	if ( empty( $user_id ) ) {
		return $args;
	}

	$user_avatar_url = null;

	// Get the user's local avatar from usermeta.
	$avatar_value = get_user_meta( $user_id, 'pmpro_avatar', true );

	if ( empty( $avatar_value ) ) {
		// TODO: Maybe try to pull from other avatar plugins.
	}

	$size = (int) $args['size'];

	// Double the size for retina displays.
	$size_2x = $size * 2;

	// Generate a new size
	if ( empty( $avatar_value[ 'resized_' . $size_2x ] ) && ! empty( $avatar_value['fullpath'] ) ) {

		$upload_path      = wp_upload_dir();
		$avatar_full_path = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $avatar_value['fullpath'] );
		$image            = wp_get_image_editor( $avatar_full_path );
		$image_sized      = null;

		if ( ! is_wp_error( $image ) ) {
			$image->resize( $size_2x, $size_2x, true );
			$image_sized = $image->save( );
		}

		// Deal with original being >= to original image (or lack of sizing ability).
		if ( empty( $image_sized ) || is_wp_error( $image_sized ) ) {
			$avatar_value[ 'resized_' . $size_2x ] = $avatar_value['fullpath'];
		} else {
			$avatar_value[ 'resized_' . $size_2x ] = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $image_sized['path'] );
			update_user_meta( $user_id, 'pmpro_avatar', $avatar_value );
		}

		// Save updated avatar sizes
		update_user_meta( $user_id, 'pmpro_avatar', $avatar_value );

	} elseif ( ! empty( $avatar_value[ 'resized_' . $size_2x ] ) && substr( $avatar_value[ 'resized_' . $size_2x ], 0, 4 ) != 'http' ) {
		$avatar_value[ 'resized_' . $size_2x ] = home_url( $avatar_value[ 'resized_' . $size_2x ] );
	}

	if ( ! empty( $avatar_value[ 'resized_' . $size_2x ] ) && is_ssl() ) {
		$avatar_value[ 'resized_' . $size_2x ] = str_replace( 'http:', 'https:', $avatar_value[ 'resized_' . $size_2x ] );
	}

	$user_avatar_url = empty( $avatar_value[ 'resized_' . $size_2x ] ) ? null : $avatar_value[ 'resized_' . $size_2x ];

	if ( $user_avatar_url ) {
		$args['url'] = $user_avatar_url;
		$args['found_avatar'] = true;
	}

	return $args;
}
add_filter( 'get_avatar_data', 'pmpro_get_avatar_data', 100, 2 );

/**
 * Set up the pmpro_avatar user field.
 *
 * @since TBD
 */
function pmpro_set_up_avatar_field() {
	// Avoid setting up this user field on User Fields settings page.
	// The only reason that we are doing this is to avoid the "This website has additional user fields that are set up with code." warning.
	if ( is_admin() && isset( $_GET['page'] ) && 'pmpro-userfields' === $_GET['page'] ) {
		return;
	}

	// Set up the user field group.
	$field_group = PMPro_Field_Group::add( 'user_avatar', esc_html__( 'User Avatar', 'paid-memberships-pro' ) );

	// Add the avatar field.
	$field_group->add_field(
		new PMPro_Field(
			'pmpro_avatar',
			'file',
			array(
				'allowed_file_types' => 'png,jpg,jpeg,jpe,gif',
				'max_file_size' => 1024 * 1024 * 2, // 2MB.
				'preview' => true,
				'profile' => false, // Whenever we use this field, we're going to weave it into the user info sections.
			)
		)
	);
}
add_action( 'init', 'pmpro_set_up_avatar_field' );

/**
 * Display the pmpro_avatar field for a user.
 *
 * @since TBD
 *
 * @param int $user_id The ID of the user to display the avatar for.
 * @param string $markup Whether to show the field in "div" or "tr" format.
 */
function pmpro_display_avatar_field( $user_id, $markup ) {
	// Get the current field value.
	$avatar_value = get_user_meta( $user_id, 'pmpro_avatar', true );

	// Show the markup before the label and field.
	if ( 'tr' === $markup ) {
		echo '<tr><th>';
	} else {
		echo '<div class="' . pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-file pmpro_form_field-avatar' ) . '">';
	}

	// Show the label.
	echo '<label class="' . pmpro_get_element_class( 'pmpro_form_label' ) . '" for="pmpro_avatar">' . esc_html__( 'Profile Picture', 'paid-memberships-pro' ) . '</label>';

	// Show the markup between the label and field.
	if ( 'tr' === $markup ) {
		echo '</th><td>';
	}

	// Display the field.
	PMPro_Field_Group::get_field( 'pmpro_avatar' )->display( $avatar_value );

	// Show the markup after the field.
	if ( 'tr' === $markup ) {
		echo '</td></tr>';
	} else {
		echo '</div>';
	}
}

/**
 * Save the pmpro_avatar field for a user.
 *
 * @since TBD
 *
 * @param int $user_id The ID of the user to save the avatar for.
 * @return true|string True if the change was made, an error message if the new value is invalid.
 */
function pmpro_save_avatar_field( $user_id ) {
	// If a new avatar was submitted and the new file is not valid, return an error.
	if ( ! empty( $_FILES['pmpro_avatar'] ) && ! empty( $_FILES['pmpro_avatar']['name'] ) ) {
		// Check if the file is valid.
		$upload_check = pmpro_check_upload( 'pmpro_avatar' );
		if ( is_wp_error( $upload_check ) ) {
			return $upload_check->get_error_message();
		}
	}

	// Before a user switches avatars, we should make sure all custom-sized versions of the previous avatar are unlinked and deleted.
	$avatar_value = get_user_meta( $user_id, 'pmpro_avatar', true );
	if ( ! empty($avatar_value ) && is_array( $avatar_value ) ) {
		$upload_path = wp_upload_dir();
		foreach ( $avatar_value as $key => $value ) {
			// Only delete resized images.
			if ( substr( $key, 0, 8 ) !== 'resized_' ) {
				continue;
			}

			// If this is somehow the same file as the fullpath, don't delete it.
			if ( $value === $avatar_value['fullpath'] ) {
				continue;
			}

			// Delete the file.
			@unlink( str_replace( $upload_path['baseurl'], $upload_path['basedir'], $value ) );
			unset( $avatar_value[ $key ] );
		}

		// Save the updated avatar array in case the rest of the update fails.
		update_user_meta( $user_id, 'pmpro_avatar', $avatar_value );
	}

	// Save the field.
	PMPro_Field_Group::get_field( 'pmpro_avatar' )->save_field_for_user( $user_id );

	return true;
}
