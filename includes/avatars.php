<?php
/**
 * PMPro Avatar functionality.
 *
 * Handles custom profile picture uploads for members.
 *
 * @since TBD
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display "enable profile pictures" checkbox on membership level edit page.
 *
 * @since TBD
 *
 * @param object $level The membership level being edited.
 */
function pmpro_membership_level_after_other_settings_avatar( $level ) {
	$enabled = ! empty( get_pmpro_membership_level_meta( $level->id, 'enable_avatars', true ) );
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label><?php esc_html_e( 'Enable Profile Pictures', 'paid-memberships-pro' ); ?></label></th>
				<td>
					<input id="enable_avatars" name="enable_avatars" type="checkbox" value="1" <?php checked( $enabled ); ?> />
					<label for="enable_avatars"><?php esc_html_e( 'Check to enable custom profile picture support for users with this membership level.', 'paid-memberships-pro' ); ?></label>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmpro_membership_level_after_other_settings_avatar', 10, 1 );

/**
 * Save "enable local avatars" checkbox on membership level edit page.
 *
 * @since TBD
 *
 * @param int $level_id The ID of the membership level being saved.
 */
function pmpro_save_membership_level_avatar( $level_id ) {
	if ( ! empty( $_REQUEST['enable_avatars'] ) ) {
		update_pmpro_membership_level_meta( $level_id, 'enable_avatars', 1 );
	} else {
		delete_pmpro_membership_level_meta( $level_id, 'enable_avatars' );
	}
}
add_action( 'pmpro_save_membership_level', 'pmpro_save_membership_level_avatar', 10, 1 );

/**
 * Get all membership level IDs with avatars enabled.
 *
 * Uses a request-level static cache to avoid repeated DB queries.
 *
 * @since TBD
 *
 * @return array Array of level IDs with avatars enabled.
 */
function pmpro_avatar_get_enabled_levels() {
	static $enabled_levels = null;

	if ( null === $enabled_levels ) {
		global $wpdb;

		$enabled_levels = $wpdb->get_col( "
			SELECT DISTINCT pmpro_membership_level_id
			FROM {$wpdb->pmpro_membership_levelmeta}
			WHERE meta_key = 'enable_avatars'
			AND meta_value = 1
		" );

		// Ensure we have an array even if query returns nothing.
		if ( ! is_array( $enabled_levels ) ) {
			$enabled_levels = array();
		}
	}

	return $enabled_levels;
}

/**
 * Check whether a user has a level with "user avatars" enabled.
 *
 * @since TBD
 *
 * @param int $user_id The ID of the user to check.
 * @return bool        Whether the user has a level with "user avatars" enabled.
 */
function pmpro_user_has_avatar_level( $user_id ) {
	// Get cached enabled levels.
	$enabled_levels = pmpro_avatar_get_enabled_levels();

	// Check if the user has any of these levels.
	$has_avatar_level = ( ! empty( $enabled_levels ) && pmpro_hasMembershipLevel( $enabled_levels, $user_id ) );

	/**
	 * Filter whether a user has a level with "user avatars" enabled.
	 *
	 * @since TBD
	 *
	 * @param bool $has_avatar_level Whether the user has a level with "user avatars" enabled.
	 * @param int  $user_id          The ID of the user to check.
	 */
	return apply_filters( 'pmpro_user_has_avatar_level', $has_avatar_level, $user_id );
}

/**
 * Get the allowed file types for avatar uploads.
 *
 * @since TBD
 *
 * @return array Array of allowed file extensions.
 */
function pmpro_avatar_get_allowed_file_types() {
	/**
	 * Filter the allowed file types for avatar uploads.
	 *
	 * @since TBD
	 *
	 * @param array $allowed_types Array of allowed file extensions.
	 */
	return apply_filters( 'pmpro_avatar_allowed_file_types', array( 'png', 'jpg', 'jpeg', 'jpe', 'gif', 'webp' ) );
}

/**
 * Get the maximum file size for avatar uploads in bytes.
 *
 * @since TBD
 *
 * @return int Maximum file size in bytes.
 */
function pmpro_avatar_get_max_file_size() {
	/**
	 * Filter the maximum file size for avatar uploads.
	 *
	 * @since TBD
	 *
	 * @param int $max_size Maximum file size in bytes. Default 2MB.
	 */
	return apply_filters( 'pmpro_avatar_max_file_size', 2 * 1024 * 1024 );
}

/**
 * Get the maximum dimensions for the base stored avatar.
 *
 * We store a "full" version that is cropped and scaled to this max size.
 *
 * @since TBD
 *
 * @return int Maximum width/height in pixels.
 */
function pmpro_avatar_get_max_dimension() {
	/**
	 * Filter the maximum dimension for stored avatars.
	 *
	 * @since TBD
	 *
	 * @param int $max_dimension Maximum width/height in pixels. Default 1024.
	 */
	return apply_filters( 'pmpro_avatar_max_dimension', 1024 );
}

/**
 * Get the allowed bucket sizes for avatar resizing.
 *
 * These are the only sizes we generate and cache.
 * Requested sizes are rounded to the nearest bucket.
 *
 * @since TBD
 *
 * @return array Array of allowed bucket sizes in pixels.
 */
function pmpro_avatar_get_bucket_sizes() {
	/**
	 * Filter the allowed bucket sizes for avatar resizing.
	 *
	 * @since TBD
	 *
	 * @param array $bucket_sizes Array of allowed bucket sizes.
	 */
	return apply_filters( 'pmpro_avatar_bucket_sizes', array( 96, 256, 512 ) );
}

/**
 * Round a requested size to the nearest bucket size.
 *
 * For retina support, we double the requested size first,
 * then round to the nearest bucket.
 *
 * @since TBD
 *
 * @param int $size The requested size in pixels.
 * @return int      The bucketed size to use.
 */
function pmpro_avatar_get_bucketed_size( $size ) {
	// Double for retina.
	$size_2x = $size * 2;

	$buckets = pmpro_avatar_get_bucket_sizes();
	$max_bucket = max( $buckets );

	// If requested 2x size is larger than max bucket, use max bucket.
	if ( $size_2x >= $max_bucket ) {
		return $max_bucket;
	}

	// Find the smallest bucket that is >= the 2x size.
	sort( $buckets );
	foreach ( $buckets as $bucket ) {
		if ( $bucket >= $size_2x ) {
			return $bucket;
		}
	}

	// Fallback to max bucket.
	return $max_bucket;
}

/**
 * Get the path to the avatars upload directory.
 *
 * @since TBD
 *
 * @param int    $user_id Optional. User ID for user-specific path.
 * @param string $file    Optional. Filename to append.
 * @return string         Directory path.
 */
function pmpro_avatar_get_upload_dir( $user_id = 0, $file = '' ) {
	$upload_dir = wp_upload_dir();
	$avatar_dir = trailingslashit( $upload_dir['basedir'] ) . 'pmpro-avatars/';

	if ( $user_id ) {
		$avatar_dir .= $user_id . '/';
	}

	if ( $file ) {
		$avatar_dir .= $file;
	}

	return $avatar_dir;
}

/**
 * Get the URL to the avatars upload directory.
 *
 * @since TBD
 *
 * @param int    $user_id Optional. User ID for user-specific path.
 * @param string $file    Optional. Filename to append.
 * @return string         Directory URL.
 */
function pmpro_avatar_get_upload_url( $user_id = 0, $file = '' ) {
	$upload_dir = wp_upload_dir();
	$avatar_url = trailingslashit( $upload_dir['baseurl'] ) . 'pmpro-avatars/';

	if ( $user_id ) {
		$avatar_url .= $user_id . '/';
	}

	if ( $file ) {
		$avatar_url .= $file;
	}

	// Ensure SSL if needed.
	if ( is_ssl() ) {
		$avatar_url = str_replace( 'http:', 'https:', $avatar_url );
	}

	return $avatar_url;
}

/**
 * Set up the avatars directory.
 *
 * @since TBD
 */
function pmpro_avatar_setup_directory() {
	$avatar_dir = pmpro_avatar_get_upload_dir();

	// Create directory if it doesn't exist.
	if ( ! file_exists( $avatar_dir ) ) {
		wp_mkdir_p( $avatar_dir );
	}
}

/**
 * Validate an avatar upload.
 *
 * @since TBD
 *
 * @param string $file_key The key in $_FILES array.
 * @return true|WP_Error   True if valid, WP_Error if not.
 */
function pmpro_avatar_validate_upload( $file_key = 'pmpro_avatar' ) {
	// Check if file was uploaded.
	if ( empty( $_FILES[ $file_key ] ) || empty( $_FILES[ $file_key ]['name'] ) ) {
		return new WP_Error( 'pmpro_avatar_error', __( 'No file was uploaded.', 'paid-memberships-pro' ) );
	}

	$file = $_FILES[ $file_key ];

	// Check for upload errors.
	if ( ! empty( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
		$error_messages = array(
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive.', 'paid-memberships-pro' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive.', 'paid-memberships-pro' ),
			UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', 'paid-memberships-pro' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'paid-memberships-pro' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', 'paid-memberships-pro' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'paid-memberships-pro' ),
			UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', 'paid-memberships-pro' ),
		);
		$error_message = isset( $error_messages[ $file['error'] ] ) ? $error_messages[ $file['error'] ] : __( 'Unknown upload error.', 'paid-memberships-pro' );
		return new WP_Error( 'pmpro_avatar_upload_error', $error_message );
	}

	// Verify this is actually an uploaded file (security check).
	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'pmpro_avatar_error', __( 'Invalid file upload.', 'paid-memberships-pro' ) );
	}

	// Check file size.
	$max_size = pmpro_avatar_get_max_file_size();
	if ( $file['size'] > $max_size ) {
		return new WP_Error(
			'pmpro_avatar_size_error',
			sprintf(
				/* translators: %s: maximum file size */
				__( 'File size is too large. Please upload a file smaller than %s.', 'paid-memberships-pro' ),
				size_format( $max_size )
			)
		);
	}

	// Validate file type using WordPress functions.
	$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

	if ( empty( $filetype['ext'] ) || empty( $filetype['type'] ) ) {
		return new WP_Error( 'pmpro_avatar_type_error', __( 'Invalid file type.', 'paid-memberships-pro' ) );
	}

	// Check against allowed types.
	$allowed_types = pmpro_avatar_get_allowed_file_types();
	if ( ! in_array( strtolower( $filetype['ext'] ), $allowed_types, true ) ) {
		return new WP_Error(
			'pmpro_avatar_type_error',
			sprintf(
				/* translators: %s: allowed file types */
				__( 'Invalid file type. Allowed types: %s', 'paid-memberships-pro' ),
				implode( ', ', $allowed_types )
			)
		);
	}

	// Verify it's actually an image by checking contents.
	$image_info = @getimagesize( $file['tmp_name'] );
	if ( false === $image_info ) {
		return new WP_Error( 'pmpro_avatar_error', __( 'The uploaded file is not a valid image.', 'paid-memberships-pro' ) );
	}

	// Check that the mime type from getimagesize matches expected image types.
	$valid_image_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
	if ( ! in_array( $image_info['mime'], $valid_image_mimes, true ) ) {
		return new WP_Error( 'pmpro_avatar_error', __( 'The uploaded file is not a valid image type.', 'paid-memberships-pro' ) );
	}

	return true;
}

/**
 * Get the file extension to use for saved avatars.
 *
 * @since TBD
 *
 * @param string $original_ext The original file extension.
 * @return string              The extension to use for saving.
 */
function pmpro_avatar_get_save_extension( $original_ext ) {
	// Normalize jpeg variations to jpg.
	$original_ext = strtolower( $original_ext );
	if ( in_array( $original_ext, array( 'jpeg', 'jpe' ), true ) ) {
		return 'jpg';
	}

	// Keep png, gif, webp as-is.
	if ( in_array( $original_ext, array( 'png', 'gif', 'webp' ), true ) ) {
		return $original_ext;
	}

	// Default to jpg for unknown types.
	return 'jpg';
}

/**
 * Process and save an avatar upload for a user.
 *
 * Stores the processed avatar as avatar.{ext} and pre-generates bucket sizes.
 * The original uploaded file is discarded after processing.
 *
 * @since TBD
 *
 * @param int    $user_id  The user ID.
 * @param string $file_key The key in $_FILES array.
 * @return true|WP_Error   True on success, WP_Error on failure.
 */
function pmpro_avatar_process_upload( $user_id, $file_key = 'pmpro_avatar' ) {
	// Validate the upload.
	$validation = pmpro_avatar_validate_upload( $file_key );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$file = $_FILES[ $file_key ];
	$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

	// Determine the extension for saved files.
	$save_ext = pmpro_avatar_get_save_extension( $filetype['ext'] );

	// Set up the upload directory.
	pmpro_avatar_setup_directory();
	$user_dir = pmpro_avatar_get_upload_dir( $user_id );
	if ( ! file_exists( $user_dir ) ) {
		wp_mkdir_p( $user_dir );
	}

	// Delete old avatar files before saving new one.
	pmpro_avatar_delete_files( $user_id );

	// Process the image - crop to square and resize to max dimension.
	$max_dimension = pmpro_avatar_get_max_dimension();
	$image = wp_get_image_editor( $file['tmp_name'] );

	if ( is_wp_error( $image ) ) {
		return new WP_Error( 'pmpro_avatar_error', __( 'Unable to process the uploaded image.', 'paid-memberships-pro' ) );
	}

	// Get original dimensions.
	$size = $image->get_size();
	$orig_width = $size['width'];
	$orig_height = $size['height'];

	// Crop to square from center.
	$min_dimension = min( $orig_width, $orig_height );
	$crop_x = ( $orig_width - $min_dimension ) / 2;
	$crop_y = ( $orig_height - $min_dimension ) / 2;

	$image->crop( $crop_x, $crop_y, $min_dimension, $min_dimension );

	// Resize to max dimension if larger.
	if ( $min_dimension > $max_dimension ) {
		$image->resize( $max_dimension, $max_dimension, true );
	}

	// Set quality.
	$image->set_quality( 90 );

	// Save the base avatar with deterministic filename.
	$base_filename = 'avatar.' . $save_ext;
	$base_path = $user_dir . $base_filename;
	$saved = $image->save( $base_path );

	if ( is_wp_error( $saved ) ) {
		return new WP_Error( 'pmpro_avatar_error', __( 'Unable to save the processed image.', 'paid-memberships-pro' ) );
	}

	// Pre-generate bucket sizes from the saved base image.
	$bucket_sizes = pmpro_avatar_get_bucket_sizes();
	$base_size = $image->get_size();
	$base_dimension = $base_size['width']; // It's square, so width = height.

	foreach ( $bucket_sizes as $bucket ) {
		// Only generate buckets smaller than the base.
		if ( $bucket < $base_dimension ) {
			$bucket_image = wp_get_image_editor( $saved['path'] );
			if ( ! is_wp_error( $bucket_image ) ) {
				$bucket_image->resize( $bucket, $bucket, true );
				$bucket_image->set_quality( 90 );
				$bucket_filename = sprintf( 'avatar-%dx%d.%s', $bucket, $bucket, $save_ext );
				$bucket_path = $user_dir . $bucket_filename;
				$bucket_image->save( $bucket_path );
			}
		}
	}

	// Build minimal avatar data for user meta.
	// We only need to track the extension since filenames are deterministic.
	$avatar_data = array(
		'extension' => $save_ext,
		'uploaded'  => time(),
	);

	// Save avatar data to user meta.
	update_user_meta( $user_id, 'pmpro_avatar', $avatar_data );

	/**
	 * Fires after an avatar has been successfully uploaded and saved.
	 *
	 * @since TBD
	 *
	 * @param int   $user_id     The user ID.
	 * @param array $avatar_data The saved avatar data.
	 */
	do_action( 'pmpro_avatar_uploaded', $user_id, $avatar_data );

	return true;
}

/**
 * Delete avatar files for a user.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 */
function pmpro_avatar_delete_files( $user_id ) {
	$user_dir = pmpro_avatar_get_upload_dir( $user_id );

	if ( ! is_dir( $user_dir ) ) {
		return;
	}

	// Delete all avatar files in the user directory.
	// Match avatar.* and avatar-*x*.* patterns.
	$files = glob( $user_dir . 'avatar*' );
	if ( ! empty( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}
}

/**
 * Delete a user's avatar completely.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 * @return bool        True on success.
 */
function pmpro_avatar_delete( $user_id ) {
	// Delete the files.
	pmpro_avatar_delete_files( $user_id );

	// Delete the user meta.
	delete_user_meta( $user_id, 'pmpro_avatar' );

	/**
	 * Fires after an avatar has been deleted.
	 *
	 * @since TBD
	 *
	 * @param int $user_id The user ID.
	 */
	do_action( 'pmpro_avatar_deleted', $user_id );

	return true;
}

/**
 * Get a user's avatar data.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 * @return array|false Avatar data array or false if not set.
 */
function pmpro_avatar_get( $user_id ) {
	$avatar_data = get_user_meta( $user_id, 'pmpro_avatar', true );

	if ( empty( $avatar_data ) || ! is_array( $avatar_data ) ) {
		return false;
	}

	// Get the extension from stored data.
	$ext = ! empty( $avatar_data['extension'] ) ? $avatar_data['extension'] : 'jpg';
	$base_filename = 'avatar.' . $ext;
	$base_path = pmpro_avatar_get_upload_dir( $user_id, $base_filename );

	// Verify the base file still exists.
	if ( ! file_exists( $base_path ) ) {
		return false;
	}

	return $avatar_data;
}

/**
 * Get a sized avatar URL for a user.
 *
 * Uses bucketed sizes for efficiency. Generates missing sizes on demand.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 * @param int $size    The desired size in pixels.
 * @return string|false Avatar URL or false if not available.
 */
function pmpro_avatar_get_url( $user_id, $size = 96 ) {
	$avatar_data = pmpro_avatar_get( $user_id );

	if ( ! $avatar_data ) {
		return false;
	}

	$ext = ! empty( $avatar_data['extension'] ) ? $avatar_data['extension'] : 'jpg';
	$max_dimension = pmpro_avatar_get_max_dimension();

	// Get the bucketed size (includes 2x for retina).
	$bucketed_size = pmpro_avatar_get_bucketed_size( $size );

	// If bucketed size >= max dimension, use the base avatar.
	if ( $bucketed_size >= $max_dimension ) {
		$filename = 'avatar.' . $ext;
	} else {
		$filename = sprintf( 'avatar-%dx%d.%s', $bucketed_size, $bucketed_size, $ext );
	}

	$file_path = pmpro_avatar_get_upload_dir( $user_id, $filename );

	// Check if the sized file exists.
	if ( ! file_exists( $file_path ) ) {
		// Try to generate it from the base avatar.
		$base_path = pmpro_avatar_get_upload_dir( $user_id, 'avatar.' . $ext );

		if ( ! file_exists( $base_path ) ) {
			return false;
		}

		$image = wp_get_image_editor( $base_path );
		if ( is_wp_error( $image ) ) {
			// Fall back to base avatar URL if we can't resize.
			$filename = 'avatar.' . $ext;
		} else {
			$image->resize( $bucketed_size, $bucketed_size, true );
			$image->set_quality( 90 );
			$saved = $image->save( $file_path );

			if ( is_wp_error( $saved ) ) {
				// Fall back to base avatar URL if save failed.
				$filename = 'avatar.' . $ext;
			}
		}
	}

	$url = pmpro_avatar_get_upload_url( $user_id, $filename );

	// Ensure SSL if needed.
	if ( is_ssl() ) {
		$url = str_replace( 'http:', 'https:', $url );
	}

	// Add cache-busting query parameter using upload timestamp.
	if ( ! empty( $avatar_data['uploaded'] ) ) {
		$url = add_query_arg( 'v', $avatar_data['uploaded'], $url );
	}

	return $url;
}

/**
 * Filter the normal avatar data and show our avatar if set.
 *
 * @since TBD
 *
 * @param array $args        Arguments passed to get_avatar_data(), after processing.
 * @param mixed $id_or_email The avatar to retrieve.
 * @return array             The filtered avatar data.
 */
function pmpro_get_avatar_data( $args, $id_or_email ) {
	if ( ! empty( $args['force_default'] ) ) {
		return $args;
	}

	// Determine the user ID.
	$user_id = pmpro_avatar_get_user_id_from_identifier( $id_or_email );

	if ( empty( $user_id ) || ! pmpro_user_has_avatar_level( $user_id ) ) {
		return $args;
	}

	// Get the avatar URL for the requested size.
	$size = ! empty( $args['size'] ) ? (int) $args['size'] : 96;
	$avatar_url = pmpro_avatar_get_url( $user_id, $size );

	if ( $avatar_url ) {
		$args['url'] = $avatar_url;
		$args['found_avatar'] = true;
	}

	return $args;
}
add_filter( 'get_avatar_data', 'pmpro_get_avatar_data', 100, 2 );

/**
 * Get user ID from various avatar identifier types.
 *
 * @since TBD
 *
 * @param mixed $id_or_email User ID, email, WP_User, WP_Post, or WP_Comment object.
 * @return int|false         User ID or false if not found.
 */
function pmpro_avatar_get_user_id_from_identifier( $id_or_email ) {
	$user_id = false;

	if ( is_numeric( $id_or_email ) && $id_or_email > 0 ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) ) {
		if ( isset( $id_or_email->user_id ) && $id_or_email->user_id > 0 ) {
			// WP_Comment object.
			$user_id = (int) $id_or_email->user_id;
		} elseif ( isset( $id_or_email->ID ) && isset( $id_or_email->user_login ) ) {
			// WP_User object.
			$user_id = (int) $id_or_email->ID;
		} elseif ( isset( $id_or_email->post_author ) ) {
			// WP_Post object.
			$user_id = (int) $id_or_email->post_author;
		}
	} elseif ( is_string( $id_or_email ) && strpos( $id_or_email, '@' ) !== false ) {
		// Email address.
		$user = get_user_by( 'email', $id_or_email );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}

	return $user_id;
}

/**
 * Display the avatar field for a user.
 *
 * @since TBD
 *
 * @param int  $user_id      The user ID.
 * @param bool $show_default Whether to show the default avatar as preview when no custom avatar is set.
 */
function pmpro_display_avatar_field( $user_id, $show_default = true ) {
	if ( empty( $user_id ) || ! pmpro_user_has_avatar_level( $user_id ) ) {
		return;
	}

	$avatar_data = pmpro_avatar_get( $user_id );
	$has_avatar = ! empty( $avatar_data );

	// Determine the current file label and preview URL.
	$preview_url = '';
	$current_file_label = '';
	$label_has_link = false;

	if ( $has_avatar ) {
		// User has a custom PMPro avatar.
		$preview_url = pmpro_avatar_get_url( $user_id, 150 );
		$full_avatar_url = pmpro_avatar_get_url( $user_id, 1024 ); // Get the full-size avatar URL.
		$label_has_link = true;
		$current_file_label = '<a href="' . esc_url( $full_avatar_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Profile Picture', 'paid-memberships-pro' ) . '</a>';
	} elseif ( $show_default ) {
		// Show the default avatar (Gravatar or site default).
		$preview_url = get_avatar_url( $user_id, array( 'size' => 150 ) );

		// Check if this is a Gravatar URL.
		if ( $preview_url && strpos( $preview_url, 'gravatar.com' ) !== false ) {
			$label_has_link = true;
			$current_file_label = '<a href="https://gravatar.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'via Gravatar', 'paid-memberships-pro' ) . '</a>';
		} else {
			$current_file_label = __( 'Site Default', 'paid-memberships-pro' );
		}
	}

	// Generate the nonce field.
	$nonce_field = wp_nonce_field( 'pmpro_avatar_upload_' . $user_id, 'pmpro_avatar_nonce', true, false );

	// Start the field.
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-file pmpro_form_field-pmpro_avatar', 'pmpro_form_field-pmpro_avatar' ) ); ?>">
		<?php
		// Preview area (only show if there's a preview URL).
		if ( $preview_url ) {
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-preview' ) ); ?>">
				<img id="pmpro_avatar_preview" src="<?php echo esc_url( $preview_url ); ?>" alt="<?php esc_attr_e( 'Profile Picture', 'paid-memberships-pro' ); ?>">
			</div>
			<?php
		}

		// Current file label (show for both custom avatar and default).
		if ( $current_file_label ) {
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-name pmpro_file_pmpro_avatar_name' ) ); ?>">
				<?php
				if ( $label_has_link ) {
					// Label contains HTML link, use wp_kses.
					echo wp_kses(
						sprintf(
							/* translators: %s: file source (linked text or plain text) */
							__( 'Current File: %s', 'paid-memberships-pro' ),
							$current_file_label
						),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					);
				} else {
					printf(
						/* translators: %s: file name or source */
						esc_html__( 'Current File: %s', 'paid-memberships-pro' ),
						esc_html( $current_file_label )
					);
				}
				?>
			</div>
			<?php
		}

		// Actions (only if user has a custom avatar - can delete/replace).
		if ( $has_avatar ) {
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-actions' ) ); ?>">
				<button type="button" class="<?php echo esc_attr( pmpro_get_element_class( 'button is-destructive pmpro_btn pmpro_btn-delete' ) ); ?>" id="pmpro_delete_file_pmpro_avatar_button" onclick="return false;"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></button>
				<button type="button" class="<?php echo esc_attr( pmpro_get_element_class( 'button button-secondary pmpro_btn pmpro_btn-secondary' ) ); ?>" id="pmpro_replace_file_pmpro_avatar_button" onclick="return false;"><?php esc_html_e( 'Replace', 'paid-memberships-pro' ); ?></button>
				<button type="button" class="<?php echo esc_attr( pmpro_get_element_class( 'button button-secondary pmpro_btn pmpro_btn-cancel' ) ); ?>" id="pmpro_cancel_change_file_pmpro_avatar_button" style="display: none;" onclick="return false;"><?php esc_html_e( 'Cancel', 'paid-memberships-pro' ); ?></button>
				<input type="hidden" id="pmpro_delete_file_pmpro_avatar_field" name="pmpro_avatar_delete" value="0">
			</div>
			<script>
				jQuery(document).ready(function($) {
					// Delete button - mark for deletion with strikethrough.
					$('#pmpro_delete_file_pmpro_avatar_button').on('click', function() {
						$('#pmpro_delete_file_pmpro_avatar_field').val('1');
						$('.pmpro_file_pmpro_avatar_name').css('text-decoration', 'line-through');
						$('#pmpro_cancel_change_file_pmpro_avatar_button').show();
						$('#pmpro_delete_file_pmpro_avatar_button').hide();
						$('#pmpro_replace_file_pmpro_avatar_button').hide();
						$('#pmpro_file_pmpro_avatar_upload').hide();
					});

					// Replace button - show file input.
					$('#pmpro_replace_file_pmpro_avatar_button').on('click', function() {
						$('#pmpro_delete_file_pmpro_avatar_field').val('1');
						$('.pmpro_file_pmpro_avatar_name').css('text-decoration', 'line-through');
						$('#pmpro_cancel_change_file_pmpro_avatar_button').show();
						$('#pmpro_delete_file_pmpro_avatar_button').hide();
						$('#pmpro_replace_file_pmpro_avatar_button').hide();
						$('#pmpro_file_pmpro_avatar_upload').show();
					});

					// Cancel button - reset everything.
					$('#pmpro_cancel_change_file_pmpro_avatar_button').on('click', function() {
						$('#pmpro_delete_file_pmpro_avatar_field').val('0');
						$('#pmpro_avatar').val('');
						$('.pmpro_file_pmpro_avatar_name').css('text-decoration', 'none');
						$('#pmpro_delete_file_pmpro_avatar_button').show();
						$('#pmpro_replace_file_pmpro_avatar_button').show();
						$('#pmpro_cancel_change_file_pmpro_avatar_button').hide();
						$('#pmpro_file_pmpro_avatar_upload').hide();
					});
				});
			</script>
			<?php
		}

		// File upload input.
		$upload_style = $has_avatar ? 'display: none;' : '';
		?>
		<div id="pmpro_file_pmpro_avatar_upload" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-upload' ) ); ?>" style="<?php echo esc_attr( $upload_style ); ?>">
			<label class="screen-reader-text"><?php esc_html_e( 'Upload Profile Picture', 'paid-memberships-pro' ); ?></label>
			<input type="file" id="pmpro_avatar" name="pmpro_avatar" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-file' ) ); ?>" accept="image/png,image/jpeg,image/gif,image/webp">
			<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>">
				<?php
				printf(
					/* translators: 1: allowed file types, 2: max file size */
					esc_html__( 'Allowed types: %1$s. Max size: %2$s.', 'paid-memberships-pro' ),
					esc_html( implode( ', ', pmpro_avatar_get_allowed_file_types() ) ),
					esc_html( size_format( pmpro_avatar_get_max_file_size() ) )
				);
				?>
			</p>
		</div>
		<?php

		// Nonce field.
		echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// JavaScript for form enctype.
		?>
		<script>
			jQuery(document).ready(function($) {
				$('#pmpro_avatar').closest('form').attr('enctype', 'multipart/form-data');
			});
		</script>
	</div> <!-- end pmpro_form_field-pmpro_avatar -->
	<?php
}

/**
 * Save the avatar field for a user.
 *
 * @since TBD
 *
 * @param int $user_id The user ID.
 * @return bool|WP_Error True on success, false if not applicable, WP_Error on failure.
 */
function pmpro_save_avatar_field( $user_id ) {
	// Check if avatars are enabled for this user.
	if ( empty( $user_id ) || ! pmpro_user_has_avatar_level( $user_id ) ) {
		return false;
	}

	// Verify nonce.
	if ( ! isset( $_POST['pmpro_avatar_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pmpro_avatar_nonce'] ), 'pmpro_avatar_upload_' . $user_id ) ) {
		// If there's no nonce, this might be a form that doesn't include the avatar field.
		// Only return error if there's avatar-related data submitted.
		if ( ! empty( $_FILES['pmpro_avatar']['name'] ) || ! empty( $_POST['pmpro_avatar_delete'] ) ) {
			return new WP_Error( 'pmpro_avatar_error', __( 'Security check failed.', 'paid-memberships-pro' ) );
		}
		return false;
	}

	// Handle deletion request.
	if ( ! empty( $_POST['pmpro_avatar_delete'] ) && $_POST['pmpro_avatar_delete'] === '1' ) {
		// Only delete if a new file isn't being uploaded.
		if ( empty( $_FILES['pmpro_avatar']['name'] ) ) {
			pmpro_avatar_delete( $user_id );
			return true;
		}
	}

	// Handle new upload.
	if ( ! empty( $_FILES['pmpro_avatar'] ) && ! empty( $_FILES['pmpro_avatar']['name'] ) ) {
		$result = pmpro_avatar_process_upload( $user_id, 'pmpro_avatar' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return true;
	}

	return true;
}

/**
 * Clean up avatar files when a user is deleted.
 *
 * @since TBD
 *
 * @param int $user_id The user ID being deleted.
 */
function pmpro_avatar_cleanup_on_user_delete( $user_id ) {
	pmpro_avatar_delete_files( $user_id );

	// Also try to remove the user's avatar directory if empty.
	$user_dir = pmpro_avatar_get_upload_dir( $user_id );
	if ( is_dir( $user_dir ) ) {
		@rmdir( $user_dir );
	}
}
add_action( 'delete_user', 'pmpro_avatar_cleanup_on_user_delete' );
add_action( 'wpmu_delete_user', 'pmpro_avatar_cleanup_on_user_delete' );

/**
 * Allow avatar file uploads through pmpro_check_upload validation.
 *
 * @since TBD
 *
 * @param bool  $allow_upload Whether to allow the upload.
 * @param array $file         The file info.
 * @param array $filetype     The file type info.
 * @return bool               Whether to allow the upload.
 */
function pmpro_avatar_allow_upload( $allow_upload, $file, $filetype ) {
	// Check if this is an avatar upload.
	if ( isset( $_FILES['pmpro_avatar'] ) && $file['name'] === $_FILES['pmpro_avatar']['name'] ) {
		// Validate against our allowed types.
		$allowed_types = pmpro_avatar_get_allowed_file_types();
		if ( in_array( strtolower( $filetype['ext'] ), $allowed_types, true ) ) {
			// Check file size.
			$max_size = pmpro_avatar_get_max_file_size();
			if ( $file['size'] <= $max_size ) {
				return true;
			}
		}
	}

	return $allow_upload;
}
add_filter( 'pmpro_allow_uploading_non_user_field_file', 'pmpro_avatar_allow_upload', 10, 3 );

/**
 * Process the Change Avatar form submission.
 *
 * Hooked to init to process the form before headers are sent.
 *
 * @since TBD
 */
function pmpro_change_avatar_process() {
	global $current_user, $pmpro_msg, $pmpro_msgt;

	// Make sure we're on the right page.
	if ( empty( $_POST['action'] ) || $_POST['action'] !== 'change-avatar' ) {
		return;
	}

	// Only let users change their own avatar.
	if ( empty( $current_user ) || empty( $_POST['user_id'] ) || $current_user->ID != $_POST['user_id'] ) {
		return;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_POST['pmpro_avatar_nonce'] ), 'pmpro_avatar_upload_' . $current_user->ID ) ) {
		pmpro_setMessage( __( 'Security check failed.', 'paid-memberships-pro' ), 'pmpro_error' );
		return;
	}

	// Check if user has avatar level.
	if ( ! pmpro_user_has_avatar_level( $current_user->ID ) ) {
		pmpro_setMessage( __( 'You do not have permission to change your profile picture.', 'paid-memberships-pro' ), 'pmpro_error' );
		return;
	}

	// Handle deletion request.
	if ( ! empty( $_POST['pmpro_avatar_delete'] ) && $_POST['pmpro_avatar_delete'] === '1' ) {
		// Only delete if a new file isn't being uploaded.
		if ( empty( $_FILES['pmpro_avatar']['name'] ) ) {
			pmpro_avatar_delete( $current_user->ID );
			pmpro_setMessage( __( 'Your profile picture has been removed.', 'paid-memberships-pro' ), 'pmpro_success' );
			return;
		}
	}

	// Handle new upload.
	if ( ! empty( $_FILES['pmpro_avatar'] ) && ! empty( $_FILES['pmpro_avatar']['name'] ) ) {
		$result = pmpro_avatar_process_upload( $current_user->ID, 'pmpro_avatar' );
		if ( is_wp_error( $result ) ) {
			pmpro_setMessage( $result->get_error_message(), 'pmpro_error' );
			return;
		}
		pmpro_setMessage( __( 'Your profile picture has been updated.', 'paid-memberships-pro' ), 'pmpro_success' );
		return;
	}

	// No action taken.
	pmpro_setMessage( __( 'Please select an image to upload.', 'paid-memberships-pro' ), 'pmpro_error' );
}
add_action( 'init', 'pmpro_change_avatar_process' );

/**
 * Display a frontend Change Avatar form.
 *
 * @since TBD
 */
function pmpro_change_avatar_form() {
	global $current_user, $pmpro_msg, $pmpro_msgt;

	// Must be logged in.
	if ( ! is_user_logged_in() ) {
		echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_alert', 'pmpro_alert' ) ) . '"><a href="' . esc_url( pmpro_login_url() ) . '">' . esc_html__( 'Log in to change your profile picture.', 'paid-memberships-pro' ) . '</a></div>';
		return;
	}

	// Check if user has avatar level.
	if ( ! pmpro_user_has_avatar_level( $current_user->ID ) ) {
		echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_alert', 'pmpro_alert' ) ) . '">' . esc_html__( 'You do not have permission to change your profile picture.', 'paid-memberships-pro' ) . '</div>';
		return;
	}

	$avatar_data = pmpro_avatar_get( $current_user->ID );
	$has_avatar = ! empty( $avatar_data );

	// Get preview URL and current file label.
	$preview_url = '';
	$current_file_label = '';
	$label_has_link = false;

	if ( $has_avatar ) {
		$preview_url = pmpro_avatar_get_url( $current_user->ID, 150 );
		$full_avatar_url = pmpro_avatar_get_url( $current_user->ID, 1024 );
		$label_has_link = true;
		$current_file_label = '<a href="' . esc_url( $full_avatar_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Profile Picture', 'paid-memberships-pro' ) . '</a>';
	} else {
		$preview_url = get_avatar_url( $current_user->ID, array( 'size' => 150 ) );
		if ( $preview_url && strpos( $preview_url, 'gravatar.com' ) !== false ) {
			$label_has_link = true;
			$current_file_label = '<a href="https://gravatar.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'via Gravatar', 'paid-memberships-pro' ) . '</a>';
		} else {
			$current_file_label = __( 'Site Default', 'paid-memberships-pro' );
		}
	}
	?>
	<?php if ( ! empty( $pmpro_msg ) ) { ?>
		<div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo esc_html( $pmpro_msg ); ?>
			<?php if ( $pmpro_msgt === 'pmpro_success' ) { ?>
				<a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>"><?php esc_html_e( 'View Your Membership Account &rarr;', 'paid-memberships-pro' ); ?></a>
			<?php } ?>
		</div>
	<?php } ?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<section id="pmpro_change_avatar" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_change_avatar' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
				<form id="change-avatar" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'change-avatar' ) ); ?>" action="" method="post" enctype="multipart/form-data">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
								<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
									<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Change Profile Picture', 'paid-memberships-pro' ); ?></h2>
								</legend>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
									<?php wp_nonce_field( 'pmpro_avatar_upload_' . $current_user->ID, 'pmpro_avatar_nonce' ); ?>

									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-file pmpro_form_field-pmpro_avatar', 'pmpro_form_field-pmpro_avatar' ) ); ?>">
										<?php if ( $preview_url ) { ?>
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-preview' ) ); ?>">
												<img id="pmpro_avatar_preview" src="<?php echo esc_url( $preview_url ); ?>" alt="<?php esc_attr_e( 'Profile Picture', 'paid-memberships-pro' ); ?>">
											</div>
										<?php } ?>

										<?php if ( $current_file_label ) { ?>
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-name pmpro_file_pmpro_avatar_name' ) ); ?>">
												<?php
												if ( $label_has_link ) {
													echo wp_kses(
														sprintf(
															/* translators: %s: file source */
															__( 'Current File: %s', 'paid-memberships-pro' ),
															$current_file_label
														),
														array(
															'a' => array(
																'href'   => array(),
																'target' => array(),
																'rel'    => array(),
															),
														)
													);
												} else {
													printf(
														/* translators: %s: file name or source */
														esc_html__( 'Current File: %s', 'paid-memberships-pro' ),
														esc_html( $current_file_label )
													);
												}
												?>
											</div>
										<?php } ?>

										<?php if ( $has_avatar ) { ?>
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-actions' ) ); ?>">
												<button type="button" class="<?php echo esc_attr( pmpro_get_element_class( 'button is-destructive pmpro_btn pmpro_btn-delete' ) ); ?>" id="pmpro_delete_file_pmpro_avatar_button" onclick="return false;"><?php esc_html_e( 'Delete', 'paid-memberships-pro' ); ?></button>
												<button type="button" class="<?php echo esc_attr( pmpro_get_element_class( 'button button-secondary pmpro_btn pmpro_btn-secondary' ) ); ?>" id="pmpro_replace_file_pmpro_avatar_button" onclick="return false;"><?php esc_html_e( 'Replace', 'paid-memberships-pro' ); ?></button>
												<button type="button" class="<?php echo esc_attr( pmpro_get_element_class( 'button button-secondary pmpro_btn pmpro_btn-cancel' ) ); ?>" id="pmpro_cancel_change_file_pmpro_avatar_button" style="display: none;" onclick="return false;"><?php esc_html_e( 'Cancel', 'paid-memberships-pro' ); ?></button>
												<input type="hidden" id="pmpro_delete_file_pmpro_avatar_field" name="pmpro_avatar_delete" value="0">
											</div>
											<script>
												jQuery(document).ready(function($) {
													$('#pmpro_delete_file_pmpro_avatar_button').on('click', function() {
														$('#pmpro_delete_file_pmpro_avatar_field').val('1');
														$('.pmpro_file_pmpro_avatar_name').css('text-decoration', 'line-through');
														$('#pmpro_cancel_change_file_pmpro_avatar_button').show();
														$('#pmpro_delete_file_pmpro_avatar_button').hide();
														$('#pmpro_replace_file_pmpro_avatar_button').hide();
														$('#pmpro_file_pmpro_avatar_upload').hide();
													});

													$('#pmpro_replace_file_pmpro_avatar_button').on('click', function() {
														$('#pmpro_delete_file_pmpro_avatar_field').val('1');
														$('.pmpro_file_pmpro_avatar_name').css('text-decoration', 'line-through');
														$('#pmpro_cancel_change_file_pmpro_avatar_button').show();
														$('#pmpro_delete_file_pmpro_avatar_button').hide();
														$('#pmpro_replace_file_pmpro_avatar_button').hide();
														$('#pmpro_file_pmpro_avatar_upload').show();
													});

													$('#pmpro_cancel_change_file_pmpro_avatar_button').on('click', function() {
														$('#pmpro_delete_file_pmpro_avatar_field').val('0');
														$('#pmpro_avatar').val('');
														$('.pmpro_file_pmpro_avatar_name').css('text-decoration', 'none');
														$('#pmpro_delete_file_pmpro_avatar_button').show();
														$('#pmpro_replace_file_pmpro_avatar_button').show();
														$('#pmpro_cancel_change_file_pmpro_avatar_button').hide();
														$('#pmpro_file_pmpro_avatar_upload').hide();
													});
												});
											</script>
										<?php } ?>

										<?php $upload_style = $has_avatar ? 'display: none;' : ''; ?>
										<div id="pmpro_file_pmpro_avatar_upload" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-file-upload' ) ); ?>" style="<?php echo esc_attr( $upload_style ); ?>">
											<label for="pmpro_avatar" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Upload New Profile Picture', 'paid-memberships-pro' ); ?></label>
											<input type="file" id="pmpro_avatar" name="pmpro_avatar" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-file' ) ); ?>" accept="image/png,image/jpeg,image/gif,image/webp">
											<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>">
												<?php
												printf(
													/* translators: 1: allowed file types, 2: max file size */
													esc_html__( 'Allowed types: %1$s. Max size: %2$s.', 'paid-memberships-pro' ),
													esc_html( implode( ', ', pmpro_avatar_get_allowed_file_types() ) ),
													esc_html( size_format( pmpro_avatar_get_max_file_size() ) )
												);
												?>
											</p>
										</div>
									</div> <!-- end pmpro_form_field-pmpro_avatar -->
								</div> <!-- end pmpro_form_fields -->
							</fieldset> <!-- end pmpro_form_fieldset -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
								<input type="hidden" name="action" value="change-avatar" />
								<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ); ?>" />
								<input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit pmpro_btn-submit-change-avatar', 'pmpro_btn-submit-change-avatar' ) ); ?>" value="<?php esc_attr_e( 'Save Changes', 'paid-memberships-pro' ); ?>" />
								<input type="button" name="cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ) ); ?>" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' ); ?>" onclick="location.href='<?php echo esc_url( pmpro_url( 'account' ) ); ?>';" />
							</div> <!-- end pmpro_form_submit -->
						</div> <!-- end pmpro_card_content -->
					</div> <!-- end pmpro_card -->
				</form> <!-- end change-avatar -->
			</div> <!-- end pmpro_section_content -->
		</section> <!-- end pmpro_change_avatar -->
	</div> <!-- end pmpro -->
	<?php
}

/**
 * Add "Change Profile Picture" link to the account page profile actions.
 *
 * @since TBD
 *
 * @param array $links The profile action links.
 * @return array       The modified profile action links.
 */
function pmpro_avatar_add_account_action_link( $links ) {
	global $current_user;

	// Only add link if user has an avatar-enabled level.
	if ( empty( $current_user->ID ) || ! pmpro_user_has_avatar_level( $current_user->ID ) ) {
		return $links;
	}

	// Only add if we have a member profile edit page set.
	if ( empty( get_option( 'pmpro_member_profile_edit_page_id' ) ) ) {
		return $links;
	}

	$change_avatar_url = add_query_arg( 'view', 'change-avatar', pmpro_url( 'member_profile_edit' ) );

	// Insert after change-password if it exists, otherwise after edit-profile.
	$new_links = array();
	foreach ( $links as $key => $link ) {
		$new_links[ $key ] = $link;
		if ( $key === 'change-password' || ( $key === 'edit-profile' && ! isset( $links['change-password'] ) ) ) {
			$new_links['change-avatar'] = sprintf(
				'<a id="pmpro_actionlink-change-avatar" href="%s">%s</a>',
				esc_url( $change_avatar_url ),
				esc_html__( 'Change Profile Picture', 'paid-memberships-pro' )
			);
		}
	}

	return $new_links;
}
add_filter( 'pmpro_account_profile_action_links', 'pmpro_avatar_add_account_action_link' );
