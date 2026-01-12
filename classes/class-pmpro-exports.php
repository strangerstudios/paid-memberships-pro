<?php
/**
 *
 * Paid Memberships Pro — Exports via Action Scheduler (AS).
 *
 * This class provides an interface to schedule and manage export tasks.
 * It leverages our Action Scheduler class to handle background processing.
 *
 * @package pmpro_plugin
 * @subpackage classes
 * @since TBD
 */

class PMPro_Exports {
	/**
	 * Singleton instance.
	 *
	 * @var PMPro_Exports|null
	 */
	protected static $instance = null;

	/**
	 * Default chunk size.
	 *
	 * How many items (members, orders, etc.) are fetched/written per async batch when processing an export.
	 */
	protected $default_chunk_size = 250;

	/**
	 * Default async threshold. Filterable.
	 *
	 * The total count above which the export switches from synchronous (in the request) to asynchronous (Action Scheduler chunks).
	 */
	protected $default_async_threshold = 499;

	/** Default export file expiration time (in seconds).
	 *
	 * How long to keep export files available for download after generation.
	 */
	protected $default_export_exp = 6 * HOUR_IN_SECONDS;

	/**
	 * Get singleton instance.
	 *
	 * @return PMPro_Exports
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor sets up Action Scheduler hook.
	 */
	public function __construct() {
		// Background chunk processor.
		add_action( 'pmpro_export_process_chunk', array( $this, 'process_chunk' ), 10, 2 );
		// Scheduled file deletion handler.
		add_action( 'pmpro_export_delete_file', array( $this, 'delete_file_task' ), 10, 1 );
		// File access filter.
		add_filter( 'pmpro_can_access_restricted_file', array( $this, 'export_can_access_restricted_files' ), 10, 2 );
	}

	/**
	 * Allow access to export files for the requesting user if token/export_id validate.
	 *
	 * @param bool   $can_access Whether the user can access the file.
	 * @param string $file_dir   Directory of the restricted file.
	 * @return bool              Whether the user can access the file.
	 */
	public function export_can_access_restricted_files( $can_access, $file_dir ) {
		// Allow access to export files for the requesting user if token/export_id validate.
		if ( 'exports' === $file_dir ) {
			$current_user_id = get_current_user_id();
			$export_id       = isset( $_REQUEST['export_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['export_id'] ) ) : '';

			// Do not sanitize token; use raw-unslashed for proper HMAC compare.
			$token = isset( $_REQUEST['token'] ) ? wp_unslash( $_REQUEST['token'] ) : '';
			$file  = isset( $_REQUEST['pmpro_restricted_file'] ) ? basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file'] ) ) ) : '';

			// Nonce verification.
			if ( isset( $_REQUEST['_wpnonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'pmpro_export_download_' . $export_id ) ) {
					return $can_access;
				}
			}
			if ( ! empty( $export_id ) && ! empty( $token ) && ! empty( $file ) ) {
				$can_access = $this->validate_file_access( $current_user_id, $export_id, $token, $file );
				// If access is granted, perform cleanup and schedule file deletion.
				if ( $can_access ) {
					$this->cleanup_after_download( $current_user_id, $export_id );
					$this->schedule_file_deletion( $file, $this->default_export_exp, $current_user_id, $export_id );
				}
			}
		}
		return $can_access;
	}

	/**
	 * Cleanup export state after a successful download.
	 * Clears the active pointer and ephemeral transients and removes the stored export record.
	 * Leaves the file on disk.
	 *
	 * @param int    $current_user_id
	 * @param string $export_id
	 * @return void
	 */
	protected function cleanup_after_download( $current_user_id, $export_id, $type = '' ) {
		if ( empty( $current_user_id ) ) {
			return;
		}
		if ( empty( $type ) ) {
			$record = $this->get_export_record_for_user( $export_id, $current_user_id );
			if ( ! empty( $record['type'] ) ) {
				$type = $record['type'];
			}
		}
		// Delete the active export pointer for this type.
		if ( ! empty( $type ) ) {
			delete_user_meta( $current_user_id, $this->get_active_meta_key( $type ) );
		}
		// Delete the export record stored under the owner.
		$key = $this->get_export_meta_key( $export_id );
		delete_user_meta( $current_user_id, $key );
		// Clear transients.
		delete_transient( 'pmpro_export_owner_' . $export_id );
		delete_transient( 'pmpro_export_token_' . $export_id );
	}

	/**
	 * Schedule deletion of the exported file via Action Scheduler.
	 * Also passes user ID and export ID for post-deletion export record cleanup.
	 *
	 * @param string $file_name The file name within the exports restricted dir.
	 * @param int    $delay     Time from now (in seconds) to delete the file.
	 * @param int    $current_user_id The user ID of the export owner.
	 * @param string $export_id The export ID.
	 *
	 * @return void
	 */
	protected function schedule_file_deletion( $file_name, $delay, $current_user_id, $export_id ) {
		if ( empty( $file_name ) || ! is_string( $file_name ) || empty( $current_user_id ) || empty( $export_id ) ) {
			return;
		}
		$timestamp = time() + max( 0, (int) $delay );
		$data      = array(
			'filename'  => $file_name,
			'user_id'   => $current_user_id,
			'export_id' => $export_id,
		);
		PMPro_Action_Scheduler::instance()->maybe_add_task( 'pmpro_export_delete_file', $data, 'pmpro_async_tasks', $timestamp );
	}

	/**
	 * Action Scheduler task: delete a previously exported file.
	 *
	 * @param array $args Should contain 'file'.
	 * @return void
	 */
	public function delete_file_task( $data ) {
		if ( empty( $data['filename'] ) || ! is_string( $data['filename'] ) ) {
			return;
		}
		$file_path = $this->get_file_path( basename( $data['filename'] ) );
		if ( $file_path && file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
			if ( ! empty( $data['user_id'] ) && ! empty( $data['export_id'] ) ) {
				$this->cleanup_after_download( $data['user_id'], $data['export_id'] );
			}
		}
	}


	/**
	 * Export type configurations.
	 *
	 * @return array
	 */
	protected function get_export_type_configs() {

		$configs = array(
			'members' => array(
				'capabilities'           => array( 'pmpro_memberslistcsv', 'manage_options' ),
				'sanitize_filters'       => 'sanitize_members_filters',
				'count_total'            => 'members_count_total',
				'fetch_ids'              => 'members_fetch_ids_chunk',
				'write_rows'             => 'members_write_rows',
				'chunk_size_filter'      => 'pmpro_members_export_chunk_size',
				'async_threshold_filter' => 'pmpro_members_export_async_threshold',
				'file_prefix'            => 'members',
			),
			'orders'  => array(
				'capabilities'           => array( 'pmpro_orderscsv', 'manage_options' ),
				'sanitize_filters'       => 'sanitize_orders_filters',
				'count_total'            => 'orders_count_total',
				'fetch_ids'              => 'orders_fetch_ids_chunk',
				'write_rows'             => 'orders_write_rows',
				'chunk_size_filter'      => 'pmpro_orders_export_chunk_size',
				'async_threshold_filter' => 'pmpro_orders_export_async_threshold',
				'file_prefix'            => 'orders',
			),
		);

		return apply_filters( 'pmpro_export_type_configs', $configs );
	}

	/**
	 * Return config for a type if available.
	 *
	 * @param string $type
	 * @return array|null
	 */
	protected function get_type_config( $type ) {
		$configs = $this->get_export_type_configs();
		return isset( $configs[ $type ] ) ? $configs[ $type ] : null;
	}

	/**
	 * Check if a user can export the given type.
	 *
	 * @param string $type
	 * @param int    $user_id
	 * @return bool
	 */
	public function user_can_export( $type, $user_id = 0 ) {
		$config = $this->get_type_config( $type );
		if ( empty( $config ) ) {
			return false;
		}
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( empty( $user_id ) ) {
			return false;
		}
		if ( empty( $config['capabilities'] ) || ! is_array( $config['capabilities'] ) ) {
			return user_can( $user_id, 'manage_options' );
		}

		foreach ( $config['capabilities'] as $capability ) {
			if ( user_can( $user_id, $capability ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get chunk size for a type.
	 *
	 * @param string $type
	 * @return int
	 */
	protected function get_chunk_size_for_type( $type ) {
		$config     = $this->get_type_config( $type );
		$chunk_size = $this->default_chunk_size;

		if ( $config && ! empty( $config['chunk_size_filter'] ) ) {
			$chunk_size = (int) apply_filters( $config['chunk_size_filter'], $chunk_size );
		}

		// Legacy filter for orders export loop size.
		if ( 'orders' === $type ) {
			$chunk_size = (int) apply_filters( 'pmpro_set_max_orders_per_export_loop', $chunk_size );
		}

		if ( $chunk_size < 1 ) {
			$chunk_size = $this->default_chunk_size;
		}
		return $chunk_size;
	}

	/**
	 * Get async threshold for a type.
	 *
	 * @param string $type
	 * @return int
	 */
	protected function get_async_threshold_for_type( $type ) {
		$config    = $this->get_type_config( $type );
		$threshold = $this->default_async_threshold;

		if ( $config && ! empty( $config['async_threshold_filter'] ) ) {
			$threshold = (int) apply_filters( $config['async_threshold_filter'], $threshold );
		}

		if ( $threshold < 1 ) {
			$threshold = $this->default_async_threshold;
		}
		return $threshold;
	}

	/**
	 * Sanitize filters for a given type.
	 *
	 * @param string $type
	 * @param array  $args
	 * @return array
	 */
	protected function sanitize_filters_for_type( $type, $args ) {
		$config = $this->get_type_config( $type );
		if ( empty( $config ) || empty( $config['sanitize_filters'] ) ) {
			return array();
		}

		$callback = $config['sanitize_filters'];
		if ( is_string( $callback ) && method_exists( $this, $callback ) ) {
			return $this->{$callback}( $args );
		}

		if ( is_callable( $callback ) ) {
			return call_user_func( $callback, $args );
		}

		return array();
	}

	/**
	 * Count total rows for a type.
	 *
	 * @param string $type
	 * @param array  $filters
	 * @return int
	 */
	protected function count_total_for_type( $type, $filters ) {
		$config = $this->get_type_config( $type );
		if ( empty( $config ) || empty( $config['count_total'] ) ) {
			return 0;
		}
		$callback = $config['count_total'];

		if ( is_string( $callback ) && method_exists( $this, $callback ) ) {
			return (int) $this->{$callback}( $filters );
		}
		if ( is_callable( $callback ) ) {
			return (int) call_user_func( $callback, $filters );
		}
		return 0;
	}

	/**
	 * Fetch IDs for a type chunk.
	 *
	 * @param string $type
	 * @param array  $filters
	 * @param int    $offset
	 * @param int    $limit
	 * @return array|\WP_Error
	 */
	protected function fetch_ids_for_type( $type, $filters, $offset, $limit ) {
		$config = $this->get_type_config( $type );
		if ( empty( $config ) || empty( $config['fetch_ids'] ) ) {
			return array();
		}
		$callback = $config['fetch_ids'];

		if ( is_string( $callback ) && method_exists( $this, $callback ) ) {
			return $this->{$callback}( $filters, $offset, $limit );
		}
		if ( is_callable( $callback ) ) {
			return call_user_func( $callback, $filters, $offset, $limit );
		}
		return array();
	}

	/**
	 * Write rows for a type chunk.
	 *
	 * @param string $type
	 * @param array  $export
	 * @param array  $ids
	 * @param bool   $write_header
	 * @return int|\WP_Error
	 */
	protected function write_rows_for_type( $type, $export, $ids, $write_header ) {
		$config = $this->get_type_config( $type );
		if ( empty( $config ) || empty( $config['write_rows'] ) ) {
			return 0;
		}
		$callback = $config['write_rows'];

		if ( is_string( $callback ) && method_exists( $this, $callback ) ) {
			return $this->{$callback}( $export, $ids, $write_header );
		}
		if ( is_callable( $callback ) ) {
			return call_user_func( $callback, $export, $ids, $write_header );
		}
		return 0;
	}

	/**
	 * Build a file name for a type/export id.
	 *
	 * @param string $type
	 * @param string $export_id
	 * @param array  $filters
	 * @return string
	 */
	protected function get_file_name_for_type( $type, $export_id, $filters = array() ) {
		$config      = $this->get_type_config( $type );
		$file_prefix = $type;
		if ( $config && ! empty( $config['file_prefix'] ) ) {
			$file_prefix = $config['file_prefix'];
		}
		$file_name = $file_prefix . '-' . $export_id . '.csv';
		$file_name = apply_filters( 'pmpro_export_file_name', $file_name, $type, $export_id, $filters );
		return sanitize_file_name( $file_name );
	}

	/**
	 * Start an export. Routes to type-specific create routine.
	 * Decides sync (original) vs async (new) method based on threshold.
	 *
	 * @param string $type Export type (currently supports 'members').
	 * @param array  $args Filters, e.g., ['l' => ..., 's' => ...].
	 * @param bool   $force_async Force background processing (for testing).
	 * @return array Result including export_id, status, counts, and optional download_url or error.
	 */
	public function start_export( $type, $args = array(), $force_async = false ) {
		$config = $this->get_type_config( $type );
		if ( empty( $config ) ) {
			return array( 'error' => __( 'Unsupported export type.', 'paid-memberships-pro' ) );
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return array( 'error' => __( 'Not logged in.', 'paid-memberships-pro' ) );
		}

		if ( ! $this->user_can_export( $type, $user_id ) ) {
			return array( 'error' => __( 'You do not have permission to export this data.', 'paid-memberships-pro' ) );
		}

		// If an active export for this type exists for the user, return it instead of creating a new one.
		$active = $this->get_active_export_for_user( $user_id, $type );
		if ( $active && ! in_array( $active['status'], array( 'complete', 'error', 'cancelled' ), true ) ) {
			return $this->format_public_export_response( $active );
		}

		// Ensure restricted dir exists.
		pmpro_set_up_restricted_files_directory();

		// Normalize and sanitize args.
		$filters = $this->sanitize_filters_for_type( $type, $args );

		// Determine counts and chunk sizes.
		$total      = $this->count_total_for_type( $type, $filters );
		$chunk_size = $this->get_chunk_size_for_type( $type );
		$threshold  = $this->get_async_threshold_for_type( $type );

		// Create export record in user meta.
		$export = $this->create_export_record( $user_id, $type, $filters, $total, $chunk_size );

		// Decide sync vs async.
		$should_async = $force_async || ( $total > $threshold );

		if ( ! $should_async ) {
			// Synchronous: process all chunks in this request for smaller datasets.
			$export['status'] = 'running';
			$this->save_export_record( $export );

			while ( 'complete' !== $export['status'] && 'error' !== $export['status'] ) {
				$result = $this->process_export_chunk_record( $export );
				if ( is_wp_error( $result ) ) {
					$export['status'] = 'error';
					$export['error']  = $result->get_error_message();
					$this->save_export_record( $export );
					break;
				}
				// Safety: avoid tight loop if no rows were written.
				if ( 0 === $result && $export['processed_count'] >= $export['total_count'] ) {
					$export['status'] = 'complete';
					$this->save_export_record( $export );
					break;
				}
			}

			return $this->format_public_export_response( $export );
		}

		// Async: mark as running right away so status polling doesn't get stuck on "queued".
		$export['status'] = 'running';
		$this->save_export_record( $export );

		// Optionally process the first chunk immediately to provide progress on first poll.
		$first_result = $this->process_export_chunk_record( $export );
		if ( is_wp_error( $first_result ) ) {
			$export['status'] = 'error';
			$export['error']  = $first_result->get_error_message();
			$this->save_export_record( $export );
			return $this->format_public_export_response( $export );
		}

		// If the first chunk finished the export, return early.
		if ( 'complete' === $export['status'] ) {
			return $this->format_public_export_response( $export );
		}

		// Async: queue first chunk, include owner to avoid lookup issues in async context.
		$this->enqueue_next_chunk( $export['id'], $user_id );

		// Return initial response.
		return $this->format_public_export_response( $export );
	}

	/**
	 * Enqueue the next chunk for Action Scheduler.
	 *
	 * @param string $export_id
	 * @return void
	 */
	protected function enqueue_next_chunk( $export_id, $user_id = 0 ) {
		// If PMPro is paused, still queue (AS will not run async until resumed).
		// PMPro's Action Scheduler helper respects queue throttling/deduplication.
		$payload = array(
			'export_id' => $export_id,
		);
		if ( ! empty( $user_id ) ) {
			$payload['user_id'] = (int) $user_id;
		}
		// Wrap payload so Action Scheduler passes it as a single argument to the hook.
		PMPro_Action_Scheduler::instance()->maybe_add_task( 'pmpro_export_process_chunk', array( $payload ), 'pmpro_async_tasks' );
	}

	/**
	 * Process one chunk for the given export.
	 *
	 * @param array $args Must contain 'export_id'.
	 * @return void
	 */
	public function process_chunk( $args, $user_id = 0 ) {
		// Normalize Action Scheduler args: previously we passed an associative array directly,
		// which AS delivers as individual params. Accept both shapes.
		if ( is_string( $args ) ) {
			$args = array(
				'export_id' => $args,
			);
		} elseif ( is_array( $args ) && isset( $args[0] ) && is_array( $args[0] ) && isset( $args[0]['export_id'] ) ) {
			$args = $args[0];
		}
		if ( ! isset( $args['user_id'] ) && ! empty( $user_id ) ) {
			$args['user_id'] = (int) $user_id;
		}

		if ( empty( $args['export_id'] ) ) {
			return;
		}

		// Prefer explicit owner passed via args to avoid transient lookup issues in async context.
		if ( ! empty( $args['user_id'] ) ) {
			$export = $this->get_export_record_for_user( $args['export_id'], (int) $args['user_id'] );
		} else {
			$export = $this->get_export_record( $args['export_id'] );
		}
		if ( ! $export ) {
			return; // Nothing to do.
		}
		if ( in_array( $export['status'], array( 'complete', 'error', 'cancelled' ), true ) ) {
			return;
		}

		// Mark as running to reflect progress in status polling.
		if ( 'queued' === $export['status'] ) {
			$export['status'] = 'running';
			$this->save_export_record( $export );
		}

		try {
			$result = $this->process_export_chunk_record( $export );
			if ( is_wp_error( $result ) ) {
				$export['status'] = 'error';
				$export['error']  = $result->get_error_message();
				$this->save_export_record( $export );
				return;
			}

			// Queue next chunk if not complete.
			if ( 'complete' !== $export['status'] ) {
				$this->enqueue_next_chunk( $export['id'], $export['user_id'] );
			}
		} catch ( \Throwable $e ) {
			$export['status'] = 'error';
			$export['error']  = $e->getMessage();
			$this->save_export_record( $export );
		}
	}

	/**
	 * Process a single chunk for an export record.
	 *
	 * @param array $export Export record (will be mutated/saved).
	 * @return int|\WP_Error Rows written or WP_Error.
	 */
	protected function process_export_chunk_record( &$export ) {
		$total      = (int) $export['total_count'];
		$offset     = (int) $export['next_offset'];
		$chunk_size = (int) $export['chunk_size'];
		$remaining  = max( 0, $total - $offset );

		if ( $remaining <= 0 ) {
			$export['status'] = 'complete';
			$this->save_export_record( $export );
			return 0;
		}

		$limit = min( $chunk_size, $remaining );

		$ids = $this->fetch_ids_for_type( $export['type'], $export['filters'], $offset, $limit );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		if ( empty( $ids ) ) {
			$export['status'] = 'complete';
			$this->save_export_record( $export );
			return 0;
		}

		$write_header = ( 0 === $offset );
		$written      = $this->write_rows_for_type( $export['type'], $export, $ids, $write_header );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$processed                  = (int) $written;
		$export['processed_count'] += $processed;
		$export['next_offset']      = $offset + count( $ids );
		$export['status']           = ( $export['processed_count'] >= $export['total_count'] ) ? 'complete' : 'running';
		$this->save_export_record( $export );

		if ( 'complete' === $export['status'] && 'orders' === $export['type'] ) {
			do_action( 'pmpro_after_order_csv_export', $export );
		}

		return $processed;
	}

	/**
	 * Build a public response structure for REST/UI.
	 */
	protected function format_public_export_response( $export ) {
		$resp = array(
			'export_id'       => $export['id'],
			'type'            => $export['type'],
			'status'          => $export['status'],
			'total_count'     => (int) $export['total_count'],
			'processed_count' => (int) $export['processed_count'],
		);
		if ( 'complete' === $export['status'] ) {
			$resp['download_url'] = $this->get_download_url( $export );
		}
		if ( ! empty( $export['error'] ) ) {
			$resp['error'] = $export['error'];
		}
		return $resp;
	}

	/**
	 * Get status for an export. If export_id omitted, return active export for current user and type.
	 */
	public function get_status( $type, $export_id = '' ) {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return array( 'error' => __( 'Not logged in.', 'paid-memberships-pro' ) );
		}

		$export = null;
		if ( ! empty( $export_id ) ) {
			$export = $this->get_export_record( $export_id );
		} else {
			$export = $this->get_active_export_for_user( $user_id, $type );
		}
		if ( empty( $export ) || (int) $export['user_id'] !== (int) $user_id || $export['type'] !== $type ) {
			return array( 'error' => __( 'No export found.', 'paid-memberships-pro' ) );
		}
		if ( ! $this->user_can_export( $type, $user_id ) ) {
			return array( 'error' => __( 'You do not have permission to export this data.', 'paid-memberships-pro' ) );
		}

		// Self-heal: if export is queued/running but no chunk task is pending, enqueue it.
		if ( in_array( $export['status'], array( 'queued', 'running' ), true ) && class_exists( '\ActionScheduler' ) ) {
			$args   = array(
				array(
					'export_id' => $export['id'],
					'user_id'   => (int) $export['user_id'],
				),
			);
			$exists = PMPro_Action_Scheduler::instance()->has_existing_task( 'pmpro_export_process_chunk', $args, 'pmpro_async_tasks' );
			if ( $exists && 'queued' === $export['status'] ) {
				$export['status'] = 'running';
				$this->save_export_record( $export );
			}
			// If no task exists, process a chunk inline (to surface progress) and enqueue the next chunk.
			if ( ! $exists ) {
				$result = $this->process_export_chunk_record( $export );
				if ( is_wp_error( $result ) ) {
					$export['status'] = 'error';
					$export['error']  = $result->get_error_message();
					$this->save_export_record( $export );
					return $this->format_public_export_response( $export );
				}
				if ( 'complete' !== $export['status'] ) {
					$this->enqueue_next_chunk( $export['id'], $export['user_id'] );
				}
			}
		}
		return $this->format_public_export_response( $export );
	}

	/**
	 * Secure download URL for export file.
	 */
	protected function get_download_url( $export ) {
		$query = array(
			'pmpro_restricted_file_dir' => 'exports',
			'pmpro_restricted_file'     => $export['file_name'],
			'export_id'                 => $export['id'],
			// Token is stored transiently; fallback to record if present.
			'token'                     => isset( $export['token'] ) ? $export['token'] : get_transient( 'pmpro_export_token_' . $export['id'] ),
			'_wpnonce'                  => wp_create_nonce( 'pmpro_export_download_' . $export['id'] ),
		);
		return add_query_arg( $query, home_url( '/' ) );
	}

	/**
	 * Validate access to an export file for current user.
	 *
	 * @param int    $user_id
	 * @param string $export_id
	 * @param string $token
	 * @param string $file File name requested.
	 * @return bool
	 */
	public function validate_file_access( $user_id, $export_id, $token, $file ) {
		$export = $this->get_export_record_for_user( $export_id, $user_id );
		if ( empty( $export ) ) {
			return false;
		}
		if ( (int) $export['user_id'] !== (int) $user_id ) {
			return false;
		}
		if ( 'complete' !== $export['status'] ) {
			return false;
		}
		if ( $export['file_name'] !== $file ) {
			return false;
		}
		// Validate token via HMAC compare.
		$calc = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
		if ( empty( $token ) || ! hash_equals( $export['token_hash'], $calc ) ) {
			return false;
		}
		// Capability check: must have permission to export this type.
		if ( ! $this->user_can_export( $export['type'], $user_id ) ) {
			return false;
		}
		return true;
	}

	// ===== Storage helpers (User Meta) ===== //

	/**
	 * Get the export meta key for a given export id.
	 *
	 * @param string $export_id Export ID.
	 * @return string User meta key storing the export record.
	 */
	protected function get_export_meta_key( $export_id ) {
		return 'pmpro_export_' . $export_id;
	}

	/**
	 * Get the active export meta key for a given type.
	 *
	 * @param string $type Export type key.
	 * @return string User meta key storing active export id.
	 */
	protected function get_active_meta_key( $type ) {
		return 'pmpro_export_active_' . $type;
	}

	/**
	 * Create and persist a new export record.
	 *
	 * @param int    $user_id
	 * @param string $type
	 * @param array  $filters
	 * @param int    $total
	 * @param int    $chunk_size
	 * @return array The created export record.
	 */
	protected function create_export_record( $user_id, $type, $filters, $total, $chunk_size, $file_name = '' ) {
		$export_id = wp_generate_uuid4();
		// Generate a URL-safe token (alphanumeric only) to avoid reserved characters breaking query strings.
		$token      = wp_generate_password( 40, false, false );
		$token_hash = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
		$file_name  = empty( $file_name ) ? $this->get_file_name_for_type( $type, $export_id, $filters ) : $file_name;

		$record = array(
			'id'              => $export_id,
			'user_id'         => (int) $user_id,
			'type'            => $type,
			'status'          => 'queued',
			'filters'         => $filters,
			'total_count'     => (int) $total,
			'processed_count' => 0,
			'chunk_size'      => (int) $chunk_size,
			'file_name'       => $file_name,
			'created_at'      => time(),
			'next_offset'     => 0,
			'error'           => '',
			'token_hash'      => $token_hash,
			// We keep the plaintext token only in memory for the response; not persisted.
			'token'           => $token,
		);
		$this->save_export_record( $record );
		// Mark active for this user/type.
		update_user_meta( $user_id, $this->get_active_meta_key( $type ), $export_id );
		// Store a transient so async Action Scheduler contexts can resolve the owner.
		set_transient( 'pmpro_export_owner_' . $export_id, (int) $user_id, DAY_IN_SECONDS );
		return $record;
	}

	/**
	 * Save an export record to user meta.
	 *
	 * @param array $record
	 * @return void
	 */
	protected function save_export_record( $record ) {
		$key = $this->get_export_meta_key( $record['id'] );
		// Do not store plaintext token in meta.
		$to_store = $record;
		if ( isset( $record['token'] ) ) {
			// Persist token so download_url can be built after async completion.
			set_transient( 'pmpro_export_token_' . $record['id'], $record['token'], DAY_IN_SECONDS );
		}
		unset( $to_store['token'] );
		update_user_meta( (int) $record['user_id'], $key, wp_json_encode( $to_store ) );
	}

	/**
	 * Get export record (in user context).
	 *
	 * @param string $export_id
	 * @return array|null
	 */
	protected function get_export_record( $export_id ) {
		$user_id = get_current_user_id();
		$key     = $this->get_export_meta_key( $export_id );
		$raw     = get_user_meta( $user_id, $key, true );
		if ( ! empty( $raw ) ) {
			$data = json_decode( (string) $raw, true );
			if ( is_array( $data ) ) {
				return $data;
			}
		}

		return null;
	}

	/**
	 * Get export record for a specific user.
	 *
	 * @param string $export_id
	 * @param int    $user_id
	 * @return array|null
	 */
	protected function get_active_export_for_user( $user_id, $type ) {
		$active_id = get_user_meta( $user_id, $this->get_active_meta_key( $type ), true );
		if ( empty( $active_id ) ) {
			return null;
		}
		$key = $this->get_export_meta_key( $active_id );
		$raw = get_user_meta( $user_id, $key, true );
		if ( empty( $raw ) ) {
			return null;
		}
		$data = json_decode( (string) $raw, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Get full file path for an export file.
	 *
	 * @param string $file_name
	 * @return string Full file path.
	 */
	protected function get_file_path( $file_name ) {
		return pmpro_get_restricted_file_path( 'exports', $file_name );
	}

	/**
	 * Load an export record for a known owner id.
	 *
	 * @param string $export_id
	 * @param int    $user_id
	 * @return array|null
	 */
	protected function get_export_record_for_user( $export_id, $user_id ) {
		if ( empty( $export_id ) || empty( $user_id ) ) {
			return null;
		}
		$key = $this->get_export_meta_key( $export_id );
		$raw = get_user_meta( (int) $user_id, $key, true );
		if ( empty( $raw ) ) {
			return null;
		}
		$data = json_decode( (string) $raw, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Enclose a value for CSV output.
	 *
	 * @param mixed $s
	 * @return string
	 */
	protected function csv_enclose( $s ) {
		$s = (string) $s;
		return '"' . str_replace( '"', '\\"', $s ) . '"';
	}

	/**
	 * Open file handle for an export file.
	 *
	 * @param array $export
	 * @return resource|\WP_Error
	 */
	protected function open_export_file_handle( $export ) {
		$file_path = $this->get_file_path( $export['file_name'] );
		$fh        = fopen( $file_path, 'a' );
		if ( false === $fh ) {
			return new \WP_Error( 'pmpro_export_file_write_error', __( 'Unable to write to export file.', 'paid-memberships-pro' ) );
		}
		return $fh;
	}

	/**
	 * Write a header row to the CSV if needed.
	 *
	 * @param resource     $fh          File handle.
	 * @param string|array $header_row Header row as array or pre-built string.
	 * @return void
	 */
	protected function write_csv_header_row( $fh, $header_row ) {
		if ( empty( $fh ) || empty( $header_row ) ) {
			return;
		}
		$line = is_array( $header_row ) ? implode( ',', $header_row ) : (string) $header_row;
		fprintf( $fh, "%s\n", $line );
	}

	// ===== Members Exports ===== //

	/**
	 * Sanitize members export filters.
	 *
	 * @param array $args Raw filter args from request.
	 * @return array Sanitized filters array with keys 'l' and 's'.
	 */
	protected function sanitize_members_filters( $args ) {
		$filters      = array();
		$filters['l'] = isset( $args['l'] ) ? sanitize_text_field( $args['l'] ) : '';
		$filters['s'] = isset( $args['s'] ) ? trim( sanitize_text_field( $args['s'] ) ) : '';
		return $filters;
	}

	/**
	 * Count total members matching filters.
	 *
	 * @param array $filters Sanitized filters.
	 * @return int Total matching member count.
	 */
	protected function members_count_total( $filters ) {
		global $wpdb;

		$query               = "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u ";
		$needs_usermeta_join = false;
		$search              = $this->build_members_search_sql_fragment( $filters, $needs_usermeta_join );
		if ( $needs_usermeta_join ) {
			$query .= " LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
		}
		$query .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
		$query .= ' WHERE mu.membership_id > 0 ';

		$filter = $this->build_members_filter_sql_fragment( $filters );
		$query .= $search . $filter;

		// Allow manipulation of SQL if needed.
		$query = apply_filters( 'pmpro_members_list_sql', $query );
		$count = (int) $wpdb->get_var( $query );
		return max( 0, $count );
	}

	/**
	 * Fetch a chunk of member IDs matching filters.
	 *
	 * @param array $filters Sanitized filters.
	 * @param int   $offset  Row offset.
	 * @param int   $limit   Max rows.
	 * @return array<int> List of user IDs.
	 */
	protected function members_fetch_ids_chunk( $filters, $offset, $limit ) {
		global $wpdb;

		$query               = "SELECT DISTINCT u.ID FROM {$wpdb->users} u ";
		$needs_usermeta_join = false;
		$search              = $this->build_members_search_sql_fragment( $filters, $needs_usermeta_join );
		if ( $needs_usermeta_join ) {
			$query .= " LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
		}
		$query .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
		$query .= ' WHERE mu.membership_id > 0 ';

		$filter = $this->build_members_filter_sql_fragment( $filters );
		$query .= $search . $filter;
		$query .= ' ORDER BY u.ID ';
		$query .= $wpdb->prepare( ' LIMIT %d, %d', (int) $offset, (int) $limit );

		$query = apply_filters( 'pmpro_members_list_sql', $query );
		$ids   = $wpdb->get_col( $query );
		if ( empty( $ids ) ) {
			return array();
		}
		return array_map( 'intval', $ids );
	}

	/**
	 * Write member rows to the export file.
	 *
	 * @param array $export       Export record.
	 * @param array $user_ids     User IDs to write.
	 * @param bool  $write_header Whether to write header.
	 * @return int|\WP_Error Rows written or WP_Error on failure.
	 */
	protected function members_write_rows( $export, $user_ids, $write_header ) {
		global $wpdb;
		if ( empty( $user_ids ) ) {
			return 0;
		}

		// File path and handle.
		$fh = $this->open_export_file_handle( $export );
		if ( is_wp_error( $fh ) ) {
			return $fh;
		}

		// Columns and header (reuse existing filters to stay compatible).
		$dateformat = apply_filters( 'pmpro_memberslist_csv_dateformat', 'Y-m-d' );

		$default_columns = array(
			array( 'theuser', 'ID' ),
			array( 'theuser', 'user_login' ),
			array( 'metavalues', 'first_name' ),
			array( 'metavalues', 'last_name' ),
			array( 'theuser', 'user_email' ),
			array( 'theuser', 'membership' ),
			array( 'discount_code', 'id' ),
			array( 'discount_code', 'code' ),
		);
		$default_columns = apply_filters( 'pmpro_members_list_csv_default_columns', $default_columns );
		$extra_columns   = apply_filters( 'pmpro_members_list_csv_extra_columns', array() );

		// Build and write header if needed.
		if ( $write_header ) {
			$heading_map = array(
				'theuser|ID'            => 'id',
				'theuser|user_login'    => 'username',
				'metavalues|first_name' => 'firstname',
				'metavalues|last_name'  => 'lastname',
				'theuser|user_email'    => 'email',
				'theuser|membership'    => 'membership',
				'discount_code|id'      => 'discount_code_id',
				'discount_code|code'    => 'discount_code',
			);
			$headers     = array();
			foreach ( $default_columns as $col ) {
				$key       = $col[0] . '|' . $col[1];
				$headers[] = isset( $heading_map[ $key ] ) ? $heading_map[ $key ] : $col[1];
			}
			$headers = array_merge(
				$headers,
				array(
					'subscription_transaction_id',
					'billing_amount',
					'cycle_number',
					'cycle_period',
					'next_payment_date',
					'joined',
					'startdate',
					( isset( $export['filters']['l'] ) && 'oldmembers' === $export['filters']['l'] ) ? 'ended' : 'expires',
				)
			);
			if ( ! empty( $extra_columns ) ) {
				foreach ( $extra_columns as $heading => $callback ) {
					$headers[] = $heading;
				}
			}
			$csv_header = implode( ',', $headers );
			$csv_header = apply_filters( 'pmpro_members_list_csv_heading', $csv_header );
			$this->write_csv_header_row( $fh, $csv_header );
		}

		// Build user rows with a single query similar to the original export for performance.
		$placeholders        = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
		$filter              = $this->build_members_filter_sql_fragment( $export['filters'] );
		$needs_usermeta_join = false;
		$search              = $this->build_members_search_sql_fragment( $export['filters'], $needs_usermeta_join );
		if ( ! empty( $search ) ) {
			$search = str_replace( '%', '%%', $search ); // Escape for prepare.
		}

		$user_sql = "
			SELECT DISTINCT
				u.ID,
				u.user_login,
				u.user_email,
				UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate,
				u.user_nicename,
				u.user_url,
				u.user_registered,
				u.user_status,
				u.display_name,
				mu.membership_id,
				UNIX_TIMESTAMP(CONVERT_TZ(min(mu.startdate), '+00:00', @@global.time_zone)) as startdate,
				UNIX_TIMESTAMP(CONVERT_TZ(max(mu.enddate), '+00:00', @@global.time_zone)) as enddate,
				m.name as membership
			FROM {$wpdb->users} u
			" . ( $needs_usermeta_join ? "LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id" : '' ) . "
			LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id
			LEFT JOIN {$wpdb->pmpro_membership_levels} m ON mu.membership_id = m.id
			WHERE u.ID in ( {$placeholders} ) AND mu.membership_id > 0 {$filter} {$search}
			GROUP BY u.ID, mu.membership_id
			ORDER BY u.ID
		";
		$user_sql = call_user_func( array( $wpdb, 'prepare' ), $user_sql, $user_ids );
		// Query is already prepared above; safe to execute.
		$usr_data = $wpdb->get_results( $user_sql );

		$rows_written = 0;

		foreach ( $usr_data as $theuser ) {
			// Load user meta values once per user into object.
			$metavalues = new \stdClass();
			$um_values  = get_user_meta( $theuser->ID );
			foreach ( $um_values as $key => $value ) {
				$metavalues->{$key} = isset( $value[0] ) ? $value[0] : null;
			}
			$theuser->metavalues = $metavalues;

			// Discount code used (latest).
			$dis_sql       = $wpdb->prepare(
				"
				SELECT c.id, c.code
				FROM {$wpdb->pmpro_discount_codes_uses} cu
				LEFT JOIN {$wpdb->pmpro_discount_codes} c ON cu.code_id = c.id
				WHERE cu.user_id = %d
				ORDER BY c.id DESC
				LIMIT 1",
				$theuser->ID
			);
			$discount_code = $wpdb->get_row( $dis_sql );
			if ( empty( $discount_code ) ) {
				$discount_code = (object) array(
					'id'   => '',
					'code' => '',
				);
			}

			// Default columns
			$csvoutput = array();
			foreach ( $default_columns as $col ) {
				$val         = isset( ${$col[0]}->{$col[1]} ) ? ${$col[0]}->{$col[1]} : null;
				$csvoutput[] = $this->csv_enclose( $val );
			}

			// Subscription info
			$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $theuser->ID, $theuser->membership_id );
			$csvoutput[]   = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_subscription_transaction_id() );
			$csvoutput[]   = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_billing_amount() );
			$csvoutput[]   = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_cycle_number() );
			$csvoutput[]   = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_cycle_period() );
			$csvoutput[]   = $this->csv_enclose( empty( $subscriptions ) ? '' : date_i18n( $dateformat, $subscriptions[0]->get_next_payment_date() ) );

			// Dates
			$csvoutput[] = $this->csv_enclose( date_i18n( $dateformat, $theuser->joindate ) );
			$csvoutput[] = $this->csv_enclose( $theuser->startdate ? date_i18n( $dateformat, $theuser->startdate ) : __( 'N/A', 'paid-memberships-pro' ) );
			$csvoutput[] = $this->csv_enclose( $theuser->enddate ? date_i18n( $dateformat, $theuser->enddate ) : __( 'N/A', 'paid-memberships-pro' ) );

			// Extra columns
			if ( ! empty( $extra_columns ) ) {
				foreach ( $extra_columns as $heading => $callback ) {
					$val         = call_user_func( $callback, $theuser, $heading );
					$val         = ( is_string( $val ) || ! empty( $val ) ) ? $val : null;
					$csvoutput[] = $this->csv_enclose( $val );
				}
			}

			$line = implode( ',', $csvoutput ) . "\n";
			fprintf( $fh, '%s', $line );
			++$rows_written;
		}

		fclose( $fh );
		return $rows_written;
	}

	/**
	 * Build the membership filter SQL fragment for member queries.
	 *
	 * @param array $filters Sanitized filters.
	 * @return string SQL fragment beginning with " AND ".
	 */
	protected function build_members_filter_sql_fragment( $filters ) {
		global $wpdb;
		$l      = isset( $filters['l'] ) ? $filters['l'] : '';
		$filter = '';
		if ( 'oldmembers' === $l ) {
			$filter = " AND mu.status <> 'active' ";
		}
		if ( 'expired' === $l || 'cancelled' === $l ) {
			$statuses = ( 'expired' === $l ) ? array( 'expired' ) : array( 'cancelled', 'admin_cancelled' );
			$filter   = " AND mu.status IN ('" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "') ";
			$filter  .= " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->pmpro_memberships_users} mu2 WHERE mu2.user_id = u.ID AND mu2.status = 'active' ) ";
		}
		if ( empty( $filter ) && is_numeric( $l ) ) {
			$filter = " AND mu.status = 'active' AND mu.membership_id = " . (int) $l . ' ';
		}
		if ( empty( $filter ) ) {
			$filter = " AND mu.status = 'active' ";
		}
		return $filter;
	}

	/**
	 * Build the search SQL fragment for member queries.
	 *
	 * @param array $filters             Sanitized filters.
	 * @param bool  $needs_usermeta_join Set true if usermeta JOIN needed.
	 * @return string SQL fragment beginning with " AND ".
	 */
	protected function build_members_search_sql_fragment( $filters, &$needs_usermeta_join = false ) {
		global $wpdb;
		$s = isset( $filters['s'] ) ? $filters['s'] : '';
		if ( empty( $s ) ) {
			return '';
		}
		$search_key = false;
		if ( strpos( $s, ':' ) !== false ) {
			$parts      = explode( ':', $s );
			$search_key = array_shift( $parts );
			$s          = implode( ':', $parts );
		}
		$s = str_replace( '*', '%', $s );
		if ( ! empty( $search_key ) ) {
			if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
				$key_column = 'u.user_' . $search_key;
				return " AND $key_column LIKE '%" . esc_sql( $s ) . "%' ";
			} elseif ( in_array( $search_key, array( 'discount', 'discount_code', 'dc' ), true ) ) {
				$user_ids = $wpdb->get_col( "SELECT dcu.user_id FROM {$wpdb->pmpro_discount_codes_uses} dcu LEFT JOIN {$wpdb->pmpro_discount_codes} dc ON dcu.code_id = dc.id WHERE dc.code = '" . esc_sql( $s ) . "'" );
				if ( empty( $user_ids ) ) {
					$user_ids = array( 0 ); }
				return ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
			} else {
				$user_ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '" . esc_sql( $search_key ) . "' AND meta_value LIKE '%" . esc_sql( $s ) . "%'" );
				if ( empty( $user_ids ) ) {
					$user_ids = array( 0 ); }
				return ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
			}
		} elseif ( function_exists( 'wp_is_large_user_count' ) && wp_is_large_user_count() ) {
			return " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
		} else {
			$needs_usermeta_join = true;
			return " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
		}
	}

	// ===== Orders Exports ===== //

	/**
	 * Sanitize orders export filters.
	 *
	 * @param array $args
	 * @return array
	 */
	protected function sanitize_orders_filters( $args ) {
		$filters                    = array();
		$filters['s']               = isset( $args['s'] ) ? trim( sanitize_text_field( $args['s'] ) ) : '';
		$filters['filter']          = isset( $args['filter'] ) ? sanitize_text_field( $args['filter'] ) : 'all';
		$filters['l']               = isset( $args['l'] ) ? intval( $args['l'] ) : 0;
		$filters['discount_code']   = isset( $args['discount-code'] ) ? intval( $args['discount-code'] ) : 0;
		$filters['status']          = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';
		$filters['predefined_date'] = isset( $args['predefined-date'] ) ? sanitize_text_field( $args['predefined-date'] ) : 'This Month';
		$filters['start_month']     = isset( $args['start-month'] ) ? intval( $args['start-month'] ) : 1;
		$filters['start_day']       = isset( $args['start-day'] ) ? intval( $args['start-day'] ) : 1;
		$filters['start_year']      = isset( $args['start-year'] ) ? intval( $args['start-year'] ) : (int) date_i18n( 'Y' );
		$filters['end_month']       = isset( $args['end-month'] ) ? intval( $args['end-month'] ) : (int) date_i18n( 'n' );
		$filters['end_day']         = isset( $args['end-day'] ) ? intval( $args['end-day'] ) : (int) date_i18n( 'j' );
		$filters['end_year']        = isset( $args['end-year'] ) ? intval( $args['end-year'] ) : (int) date_i18n( 'Y' );
		$filters['limit']           = isset( $args['limit'] ) ? intval( $args['limit'] ) : 0;
		$filters['pn']              = isset( $args['pn'] ) ? intval( $args['pn'] ) : 1;

		if ( $filters['limit'] < 0 ) {
			$filters['limit'] = 0;
		}
		if ( $filters['pn'] < 1 ) {
			$filters['pn'] = 1;
		}

		$filters['offset'] = ! empty( $filters['limit'] ) ? max( 0, ( $filters['pn'] * $filters['limit'] ) - $filters['limit'] ) : 0;

		return $filters;
	}

	/**
	 * Build the base filter condition for orders queries.
	 *
	 * @param array $filters
	 * @return array{sql:string,params:array,joins:array}
	 */
	protected function build_orders_filter_condition( $filters ) {
		global $wpdb;

		$filter    = isset( $filters['filter'] ) ? $filters['filter'] : 'all';
		$sql       = '1=1';
		$params    = array();
		$join      = array();
		$now       = current_time( 'timestamp' );
		$startdate = '';
		$enddate   = '';

		switch ( $filter ) {
			case 'within-a-date-range':
				$startdate = sprintf( '%04d-%02d-%02d', max( 1, (int) $filters['start_year'] ), max( 1, (int) $filters['start_month'] ), max( 1, (int) $filters['start_day'] ) );
				$enddate   = sprintf( '%04d-%02d-%02d', max( 1, (int) $filters['end_year'] ), max( 1, (int) $filters['end_month'] ), max( 1, (int) $filters['end_day'] ) );
				$startdate = get_gmt_from_date( $startdate . ' 00:00:00' );
				$enddate   = get_gmt_from_date( $enddate . ' 23:59:59' );
				$sql       = 'o.timestamp BETWEEN %s AND %s';
				$params    = array( $startdate, $enddate );
				break;
			case 'predefined-date-range':
				$predefined = isset( $filters['predefined_date'] ) ? $filters['predefined_date'] : 'This Month';
				if ( 'Last Month' === $predefined ) {
					$startdate = date_i18n( 'Y-m-d', strtotime( 'first day of last month', $now ) );
					$enddate   = date_i18n( 'Y-m-d', strtotime( 'last day of last month', $now ) );
				} elseif ( 'This Year' === $predefined ) {
					$year      = date_i18n( 'Y', $now );
					$startdate = date_i18n( 'Y-m-d', strtotime( "first day of January $year", $now ) );
					$enddate   = date_i18n( 'Y-m-d', strtotime( "last day of December $year", $now ) );
				} elseif ( 'Last Year' === $predefined ) {
					$year      = (int) date_i18n( 'Y', $now ) - 1;
					$startdate = date_i18n( 'Y-m-d', strtotime( "first day of January $year", $now ) );
					$enddate   = date_i18n( 'Y-m-d', strtotime( "last day of December $year", $now ) );
				} else {
					$startdate = date_i18n( 'Y-m-d', strtotime( 'first day of this month', $now ) );
					$enddate   = date_i18n( 'Y-m-d', strtotime( 'last day of this month', $now ) );
				}
				$startdate = get_gmt_from_date( $startdate . ' 00:00:00' );
				$enddate   = get_gmt_from_date( $enddate . ' 23:59:59' );
				$sql       = 'o.timestamp BETWEEN %s AND %s';
				$params    = array( $startdate, $enddate );
				break;
			case 'within-a-level':
				$sql    = 'o.membership_id = %d';
				$params = array( (int) $filters['l'] );
				break;
			case 'with-discount-code':
				$sql    = 'dc.code_id = %d';
				$params = array( (int) $filters['discount_code'] );
				$join[] = "LEFT JOIN {$wpdb->pmpro_discount_codes_uses} dc ON o.id = dc.order_id";
				break;
			case 'within-a-status':
				$sql    = 'o.status = %s';
				$params = array( sanitize_text_field( $filters['status'] ) );
				break;
			case 'only-paid':
				$sql = 'o.total > 0';
				break;
			case 'only-free':
				$sql = 'o.total = 0';
				break;
		}

		return array(
			'sql'    => $sql,
			'params' => $params,
			'joins'  => $join,
		);
	}

	/**
	 * Build the search fragment for orders queries.
	 *
	 * @param array $filters
	 * @return array{sql:string,params:array,joins:array}
	 */
	protected function build_orders_search_fragment( $filters ) {
		global $wpdb;

		$search = isset( $filters['s'] ) ? trim( $filters['s'] ) : '';
		if ( '' === $search ) {
			return array(
				'sql'    => '',
				'params' => array(),
				'joins'  => array(),
			);
		}

		$joins      = array();
		$params     = array();
		$search_sql = '';

		if ( strpos( $search, ':' ) !== false ) {
			$parts      = explode( ':', $search );
			$search_key = array_shift( $parts );
			$search_val = implode( ':', $parts );
			$like       = '%' . $wpdb->esc_like( $search_val ) . '%';

			if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
				$column = 'u.user_' . $search_key;
			} else {
				$column = 'o.' . preg_replace( '/[^a-zA-Z0-9_]/', '', $search_key );
			}
			if ( 'o.' === $column || empty( $column ) ) {
				$column = 'o.id';
			}
			$search_sql = "{$column} LIKE %s";
			$params[]   = $like;
		} else {
			$join_usermeta = apply_filters( 'pmpro_orders_search_usermeta', false );

			if ( ! empty( $join_usermeta ) ) {
				$joins[] = "LEFT JOIN {$wpdb->usermeta} um ON o.user_id = um.user_id";
			}

			$fields = array(
				'o.id',
				'o.code',
				'o.billing_name',
				'o.billing_street',
				'o.billing_street2',
				'o.billing_city',
				'o.billing_state',
				'o.billing_zip',
				'o.billing_country',
				'o.billing_phone',
				'o.payment_type',
				'o.cardtype',
				'o.accountnumber',
				'o.status',
				'o.gateway',
				'o.gateway_environment',
				'o.payment_transaction_id',
				'o.subscription_transaction_id',
				'o.notes',
				'u.user_login',
				'u.user_email',
				'u.display_name',
				'ml.name',
			);

			if ( ! empty( $join_usermeta ) ) {
				$fields[] = 'um.meta_value';
			}

			$fields  = apply_filters( 'pmpro_orders_search_fields', $fields );
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$clauses = array();

			foreach ( $fields as $field ) {
				$field = preg_replace( '/[^a-zA-Z0-9\._]/', '', $field );
				if ( empty( $field ) ) {
					continue;
				}
				$clauses[] = "{$field} LIKE %s";
				$params[]  = $like;
			}

			if ( ! empty( $clauses ) ) {
				$search_sql = '( ' . implode( ' OR ', $clauses ) . ' )';
			}
		}

		return array(
			'sql'    => $search_sql,
			'params' => $params,
			'joins'  => $joins,
		);
	}

	/**
	 * Combine filter/search parts into a query definition.
	 *
	 * @param array $filters
	 * @return array{joins:array,where:string,params:array}
	 */
	protected function build_orders_query_components( $filters ) {
		global $wpdb;

		$condition = $this->build_orders_filter_condition( $filters );
		$search    = $this->build_orders_search_fragment( $filters );

		$joins = array(
			"LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID",
			"LEFT JOIN {$wpdb->pmpro_membership_levels} ml ON o.membership_id = ml.id",
		);

		if ( ! empty( $condition['joins'] ) ) {
			$joins = array_merge( $joins, (array) $condition['joins'] );
		}
		if ( ! empty( $search['joins'] ) ) {
			$joins = array_merge( $joins, (array) $search['joins'] );
		}

		$joins       = array_values( array_unique( $joins ) );
		$where_parts = array();
		if ( ! empty( $condition['sql'] ) ) {
			$where_parts[] = $condition['sql'];
		}
		if ( ! empty( $search['sql'] ) ) {
			$where_parts[] = $search['sql'];
		}

		$where = ! empty( $where_parts ) ? implode( ' AND ', $where_parts ) : '1=1';

		$params = array_merge(
			isset( $condition['params'] ) ? $condition['params'] : array(),
			isset( $search['params'] ) ? $search['params'] : array()
		);

		return array(
			'joins'  => $joins,
			'where'  => $where,
			'params' => $params,
		);
	}

	/**
	 * Count total orders for filters.
	 *
	 * @param array $filters
	 * @return int
	 */
	protected function orders_count_total( $filters ) {
		global $wpdb;

		$parts  = $this->build_orders_query_components( $filters );
		$joins  = implode( ' ', $parts['joins'] );
		$where  = $parts['where'];
		$params = $parts['params'];

		$sql_base = "FROM {$wpdb->pmpro_membership_orders} AS o {$joins} WHERE {$where}";
		if ( ! empty( $filters['limit'] ) ) {
			$sql      = "SELECT COUNT(*) FROM ( SELECT DISTINCT o.id {$sql_base} ORDER BY o.id DESC, o.timestamp DESC LIMIT %d, %d ) AS counts";
			$params[] = isset( $filters['offset'] ) ? (int) $filters['offset'] : 0;
			$params[] = (int) $filters['limit'];
		} else {
			$sql = "SELECT COUNT(DISTINCT o.id) {$sql_base}";
		}

		if ( ! empty( $params ) ) {
			$sql = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $params ) );
		}

		$count = (int) $wpdb->get_var( $sql );
		return max( 0, $count );
	}

	/**
	 * Fetch a chunk of order IDs matching filters.
	 *
	 * @param array $filters
	 * @param int   $offset
	 * @param int   $limit
	 * @return array<int>
	 */
	protected function orders_fetch_ids_chunk( $filters, $offset, $limit ) {
		global $wpdb;

		if ( $limit < 1 ) {
			return array();
		}

		$parts  = $this->build_orders_query_components( $filters );
		$joins  = implode( ' ', $parts['joins'] );
		$where  = $parts['where'];
		$params = $parts['params'];

		$base_offset  = isset( $filters['offset'] ) ? (int) $filters['offset'] : 0;
		$query_offset = $base_offset + (int) $offset;

		if ( ! empty( $filters['limit'] ) ) {
			$remaining = (int) $filters['limit'] - (int) $offset;
			if ( $remaining <= 0 ) {
				return array();
			}
			$limit = min( $limit, $remaining );
		}

		$sql      = "SELECT DISTINCT o.id FROM {$wpdb->pmpro_membership_orders} AS o {$joins} WHERE {$where} ORDER BY o.id DESC, o.timestamp DESC LIMIT %d, %d";
		$params[] = $query_offset;
		$params[] = (int) $limit;

		$sql = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $params ) );
		$ids = $wpdb->get_col( $sql );
		$ids = is_array( $ids ) ? array_map( 'intval', $ids ) : array();
		return $ids;
	}

	/**
	 * Write order rows to CSV.
	 *
	 * @param array $export
	 * @param array $order_ids
	 * @param bool  $write_header
	 * @return int|\WP_Error
	 */
	protected function orders_write_rows( $export, $order_ids, $write_header ) {
		global $wpdb;

		if ( empty( $order_ids ) ) {
			return 0;
		}

		$fh = $this->open_export_file_handle( $export );
		if ( is_wp_error( $fh ) ) {
			return $fh;
		}

		$csv_file_header_array = array(
			'id',
			'code',
			'user_id',
			'user_login',
			'first_name',
			'last_name',
			'user_email',
			'billing_name',
			'billing_street',
			'billing_city',
			'billing_state',
			'billing_zip',
			'billing_country',
			'billing_phone',
			'membership_id',
			'level_name',
			'subtotal',
			'tax',
			'total',
			'payment_type',
			'cardtype',
			'accountnumber',
			'expirationmonth',
			'expirationyear',
			'status',
			'gateway',
			'gateway_environment',
			'payment_transaction_id',
			'subscription_transaction_id',
			'discount_code_id',
			'discount_code',
			'timestamp',
		);

		$default_columns = array(
			array( 'order', 'id' ),
			array( 'order', 'code' ),
			array( 'user', 'ID' ),
			array( 'user', 'user_login' ),
			array( 'user', 'first_name' ),
			array( 'user', 'last_name' ),
			array( 'user', 'user_email' ),
			array( 'order', 'billing', 'name' ),
			array( 'order', 'billing', 'street' ),
			array( 'order', 'billing', 'city' ),
			array( 'order', 'billing', 'state' ),
			array( 'order', 'billing', 'zip' ),
			array( 'order', 'billing', 'country' ),
			array( 'order', 'billing', 'phone' ),
			array( 'order', 'membership_id' ),
			array( 'level', 'name' ),
			array( 'order', 'subtotal' ),
			array( 'order', 'tax' ),
			array( 'order', 'total' ),
			array( 'order', 'payment_type' ),
			array( 'order', 'cardtype' ),
			array( 'order', 'accountnumber' ),
			array( 'order', 'expirationmonth' ),
			array( 'order', 'expirationyear' ),
			array( 'order', 'status' ),
			array( 'order', 'gateway' ),
			array( 'order', 'gateway_environment' ),
			array( 'order', 'payment_transaction_id' ),
			array( 'order', 'subscription_transaction_id' ),
			array( 'discount_code', 'id' ),
			array( 'discount_code', 'code' ),
		);

		$default_columns       = apply_filters( 'pmpro_order_list_csv_default_columns', $default_columns );
		$csv_file_header_array = apply_filters( 'pmpro_order_list_csv_export_header_array', $csv_file_header_array );
		$dateformat            = apply_filters( 'pmpro_order_list_csv_dateformat', get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		$extra_columns = apply_filters( 'pmpro_orders_csv_extra_columns', array() );
		$extra_columns = apply_filters( 'pmpro_order_list_csv_extra_columns', $extra_columns );

		if ( $write_header ) {
			if ( ! empty( $extra_columns ) ) {
				foreach ( $extra_columns as $heading => $callback ) {
					$csv_file_header_array[] = $heading;
				}
			}
			$csv_header = implode( ',', $csv_file_header_array );
			$this->write_csv_header_row( $fh, $csv_header );
			do_action( 'pmpro_before_order_list_csv_export', $order_ids );
		}

		$rows_written = 0;

		foreach ( $order_ids as $order_id ) {
			$csvoutput = array();

			$order            = new MemberOrder();
			$order->nogateway = true;
			$order->getMemberOrderByID( $order_id );

			if ( empty( $order->id ) ) {
				continue;
			}

			if ( empty( $order->billing ) || ! is_object( $order->billing ) ) {
				$order->billing = new \stdClass();
			}

			$user  = get_userdata( $order->user_id );
			$level = $order->getMembershipLevel();
			if ( ! $user ) {
				$user = new \stdClass();
			}
			if ( empty( $level ) ) {
				$level = new \stdClass();
			}

			$sqlQuery = $wpdb->prepare(
				"
				SELECT c.id, c.code
				FROM {$wpdb->pmpro_discount_codes_uses} AS cu
					LEFT JOIN {$wpdb->pmpro_discount_codes} AS c
					ON cu.code_id = c.id
				WHERE cu.order_id = %s
				LIMIT 1",
				$order_id
			);

			$discount_code = $wpdb->get_row( $sqlQuery );
			if ( empty( $discount_code ) ) {
				$discount_code = (object) array(
					'id'   => '',
					'code' => '',
				);
			}

			if ( ! empty( $default_columns ) ) {
				foreach ( $default_columns as $col ) {
					switch ( count( $col ) ) {
						case 3:
							$val = isset( ${$col[0]}->{$col[1]}->{$col[2]} ) ? ${$col[0]}->{$col[1]}->{$col[2]} : null;
							break;

						case 2:
							$val = isset( ${$col[0]}->{$col[1]} ) ? ${$col[0]}->{$col[1]} : null;
							break;

						default:
							$val = null;
					}

					$csvoutput[] = $this->csv_enclose( $val );
				}
			}

			$ts          = date_i18n( $dateformat, $order->getTimestamp() );
			$csvoutput[] = $this->csv_enclose( $ts );

			if ( ! empty( $extra_columns ) ) {
				foreach ( $extra_columns as $heading => $callback ) {
					$val         = call_user_func( $callback, $order );
					$val         = ! empty( $val ) ? $val : null;
					$csvoutput[] = $this->csv_enclose( $val );
				}
			}

			$line = implode( ',', $csvoutput ) . "\n";
			fprintf( $fh, '%s', $line );

			++$rows_written;
		}

		fclose( $fh );
		return $rows_written;
	}
}
