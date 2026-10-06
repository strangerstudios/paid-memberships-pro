<?php
/**
 * Upgrade to version 3.8.8
 *
 * New user field upload folders get an empty index.html file so that servers with
 * directory listing enabled do not list their contents. This upgrade adds the index
 * file to folders created before 3.8.8 using an Action Scheduler task.
 *
 * @since 3.8.8
 */
function pmpro_upgrade_3_8_8() {
	// Sites without user field uploads have nothing to update.
	$upload_dir = wp_upload_dir();
	if ( empty( $upload_dir['basedir'] ) || ! is_dir( $upload_dir['basedir'] . '/pmpro-register-helper' ) ) {
		return;
	}

	// Action Scheduler is not initialized yet while the upgrade check runs.
	add_action( 'action_scheduler_init', function() {
		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmpro_add_user_field_upload_index_files',
			array(),
			'pmpro_async_tasks'
		);
	} );
}

/**
 * Add empty index files to user field upload folders that do not have one.
 *
 * Covers the pmpro-register-helper folder and each folder directly inside it (user folders
 * and the checkout tmp folder). Scheduled by pmpro_upgrade_3_8_8() and re-queued until
 * every folder has an index file.
 *
 * @since 3.8.8
 */
function pmpro_add_user_field_upload_index_files() {
	$upload_dir = wp_upload_dir();
	if ( empty( $upload_dir['basedir'] ) ) {
		return;
	}
	$root = $upload_dir['basedir'] . '/pmpro-register-helper/';
	if ( ! is_dir( $root ) ) {
		return;
	}

	// Find folders that still need an index file, one batch at a time.
	$batch_size = 1000;
	$folders    = array();
	if ( ! file_exists( $root . 'index.html' ) && is_writable( $root ) ) {
		$folders[] = $root;
	}
	$handle = opendir( $root );
	if ( false === $handle ) {
		return;
	}
	while ( count( $folders ) <= $batch_size && false !== ( $entry = readdir( $handle ) ) ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$folder = $root . $entry . '/';
		// Skip folders we can't write to so that they don't keep the task re-queuing.
		if ( is_dir( $folder ) && ! is_link( $root . $entry ) && ! file_exists( $folder . 'index.html' ) && is_writable( $folder ) ) {
			$folders[] = $folder;
		}
	}
	closedir( $handle );

	// If there are more folders than this batch can handle, queue the next run first so a timeout doesn't end the chain.
	if ( count( $folders ) > $batch_size ) {
		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmpro_add_user_field_upload_index_files',
			array(),
			'pmpro_async_tasks',
			'+1 minute'
		);
		$folders = array_slice( $folders, 0, $batch_size );
	}

	foreach ( $folders as $folder ) {
		pmpro_create_user_field_upload_dir( $folder );
	}
}
