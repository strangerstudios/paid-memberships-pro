<?php

/**
 * Set up restriction directories.
 *
 * @since 3.5
 */
function pmpro_set_up_restricted_files_directory() {
	// Create restricted folder if it doesn't exist.
	$restricted_file_directory = pmpro_get_restricted_file_path();
	if ( ! file_exists( $restricted_file_directory ) ) {
		wp_mkdir_p( $restricted_file_directory );
	}

	// Create/update .htaccess file for apache servers.
	$htaccess = '<FilesMatch ".*">' . "\n" .
		'  <IfModule !mod_authz_core.c>' . "\n" .
		'    Order allow,deny' . "\n" .
		'    Deny from all' . "\n" .
		'  </IfModule>' . "\n" .
		'  <IfModule mod_authz_core.c>' . "\n" .
		'    Require all denied' . "\n" .
		'  </IfModule>' . "\n" .
		'</FilesMatch>';
	file_put_contents( trailingslashit( $restricted_file_directory ) . '.htaccess', $htaccess );
}

/**
 * If a restricted file is requested, check if the user has access.
 * If so, serve the file.
 *
 * @since 3.5
 */
function pmpro_restricted_files_check_request() {
	if ( empty( $_REQUEST['pmpro_restricted_file'] ) || empty( $_REQUEST['pmpro_restricted_file_dir'] ) ) {
		return;
	}

	// Get the requested file.
	$file = basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file'] ) ) );
	$file_dir = basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file_dir'] ) ) );

	/* 
 		Remove ../-like strings from the URI. 
 		Actually removes any combination of two or more ., /, and \. 
 		This will prevent traversal attacks and loading hidden files. 
 	*/
	$file = preg_replace("/[\.\/\\\\]{2,}/", "", $file);
	$file_dir = preg_replace("/[\.\/\\\\]{2,}/", "", $file_dir);

	/**
	 * Filter to check if a user can access a restricted file.
	 *
	 * @since 3.5
	 *
	 * @param bool   $can_access Whether the user can access the file.
	 * @param string $file_dir   Directory of the restricted file.
	 * @param string $file       Name of the restricted file.
	 */
	if ( empty( apply_filters( 'pmpro_can_access_restricted_file', false, $file_dir, $file ) ) ) {
		wp_die( esc_html__( 'You do not have permission to access this file.', 'paid-memberships-pro' ), 403 );
	}

	// Serve the file.
	$file_path = pmpro_get_restricted_file_path( $file_dir, $file );
	if ( file_exists( $file_path ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$content_type = finfo_file( $finfo, $file_path );

		/**
		 * Filter the content disposition for the restricted file. 
		 * This automatically defaults to inline for images and attachments for non-images. 
		 * 
		 * @since 3.6
		 * 
		 * @param $is_valid_image boolean This is true for image/* and false for anything else.
		 * @param string $file Name of the restricted file.
		 * @param string $file_dir Directory of the restricted file.
		 * @param string $file_path Path to the restricted file.
		 * 
		 * @return string $content_disposition "inline" for image/* types, "attachment" for other file types.
		 */
		$content_disposition = apply_filters( 'pmpro_restricted_file_content_disposition', wp_getimagesize( $file_path ) ? 'inline' : 'attachment', $file, $file_dir, $file_path );
		if ( $content_disposition !== 'inline' && $content_disposition !== 'attachment' ) {
			$content_disposition = 'attachment'; // Default to attachment if not inline and not attachment.
		}

		finfo_close( $finfo );
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: ' . $content_disposition . '; filename="' . $file . '"' );
		readfile( $file_path );
		exit;
	} else {
		wp_die(	esc_html__( 'File not found.', 'paid-memberships-pro' ), 404 );
	}
}
add_action( 'init', 'pmpro_restricted_files_check_request' );

/**
 * Add a filter to allow access to restricted files for core use-cases.
 *
 * @since 3.5
 *
 * @param  bool   $can_access Whether the user can access the file.
 * @param  string $file_dir   Directory of the restricted file.
 * @return bool               Whether the user can access the file.
 */
function pmpro_can_access_restricted_file( $can_access, $file_dir ) {
	if ( 'logs' === $file_dir ) {
		return current_user_can( 'manage_options' );
	}

	return $can_access;
}
add_filter( 'pmpro_can_access_restricted_file', 'pmpro_can_access_restricted_file', 10, 2 );

/**
 * Get the path to a restricted file.
 *
 * @since 3.5
 *
 * @param  string $file_dir Directory of the restricted file.
 * @param  string $file     Name of the restricted file.
 * @return string           Path to the restricted file.
 */
function pmpro_get_restricted_file_path( $file_dir = '', $file = '' ) {
	// Get a random string to prevent directory traversal attacks.
	$random_string = get_option( 'pmpro_restricted_files_random_string', '' );
	if ( empty( $random_string ) ) {
		$random_string = substr( md5( rand() ), 0, 10 );
		update_option( 'pmpro_restricted_files_random_string', $random_string );
	}

	// Get the directory path.
	$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] );
	$restricted_file_path = $uploads_dir . 'pmpro-' . $random_string . '/';
	if ( ! empty( $file_dir ) ) {
		$restricted_file_path .= $file_dir . '/';

		// Create the directory if it doesn't exist.
		if ( ! file_exists( $restricted_file_path ) ) {
			wp_mkdir_p( $restricted_file_path );
		}

		// Get the file path.
		if ( ! empty( $file ) ) {
			$restricted_file_path .= $file;
		}
	}
	return $restricted_file_path;
}
