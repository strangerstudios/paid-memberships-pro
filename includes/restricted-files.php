<?php

/**
 * Set up restriction directories.
 *
 * @since TBD
 */
function pmpro_restricted_files_set_up_directories() {
	// Create wp-content/uploads/pmpro folder if it doesn't exist.
	$upload_dir = wp_upload_dir();
	$pmpro_dir = trailingslashit( $upload_dir['basedir'] ) . 'pmpro';
	if ( ! file_exists( $pmpro_dir ) ) {
		wp_mkdir_p( $pmpro_dir );
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
	file_put_contents( trailingslashit( $pmpro_dir ) . '.htaccess', $htaccess );
}
add_action( 'admin_init', 'pmpro_restricted_files_set_up_directories' );

/**
 * If a restricted file is requested, check if the user has access.
 * If so, serve the file.
 *
 * @since TBD
 */
function pmpro_restricted_files_check_request() {
	if ( empty( $_REQUEST['pmpro_restricted_file'] ) || empty( $_REQUEST['pmpro_restricted_file_dir'] ) ) {
		return;
	}

	// Get the requested file.
	$file = basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file'] ) ) );
	$file_dir = basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file_dir'] ) ) );

	/**
	 * Filter to check if a user can access a restricted file.
	 *
	 * @since TBD
	 *
	 * @param bool   $can_access Whether the user can access the file.
	 * @param string $file_dir   Directory of the restricted file.
	 * @param string $file       Name of the restricted file.
	 */
	if ( empty( apply_filters( 'pmpro_can_access_restricted_file', false, $file_dir, $file ) ) ) {
		wp_die( __( 'You do not have permission to access this file.', 'paid-memberships-pro' ), 403 );
	}

	// Serve the file.
	$file_path = trailingslashit( wp_upload_dir()['basedir'] ) . 'pmpro/' . $file_dir . '/' . $file;
	if ( file_exists( $file_path ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$content_type = finfo_file( $finfo, $file_path );
		finfo_close( $finfo );
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $file . '"' );
		readfile( $file_path );
		exit;
	} else {
		wp_die( __( 'File not found.', 'paid-memberships-pro' ), 404 );
	}
}
add_action( 'init', 'pmpro_restricted_files_check_request' );

/**
 * Add a filter to allow access to restricted files for core use-cases.
 *
 * @since TBD
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