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
	protected $default_chunk_size = 500;

	/**
	 * Default async threshold. Filterable.
	 *
	 * The total count above which the export switches from synchronous (in the request) to asynchronous (Action Scheduler chunks).
	 */
	protected $default_async_threshold = 2000;

	/** Default export file expiration time (in seconds). */
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
			$export_id = isset( $_REQUEST['export_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['export_id'] ) ) : '';
			// Do not sanitize token; use raw-unslashed for HMAC compare.
			$token     = isset( $_REQUEST['token'] ) ? wp_unslash( $_REQUEST['token'] ) : '';
			$file      = isset( $_REQUEST['pmpro_restricted_file'] ) ? basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file'] ) ) ) : '';
			if ( ! empty( $export_id ) && ! empty( $token ) && ! empty( $file ) ) {
				if ( class_exists( 'PMPro_Exports' ) ) {
					$exports = PMPro_Exports::instance();
					$can_access = $exports->validate_file_access( $current_user_id, $export_id, $token, $file );
					// If access is granted, perform cleanup and schedule file deletion.
					if ( $can_access ) {
						$exports->cleanup_after_download( $current_user_id, $export_id );
						$exports->schedule_file_deletion( $file, $this->default_export_exp );
					}
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
	protected function cleanup_after_download( $current_user_id, $export_id ) {
		if ( empty( $current_user_id ) ) {
			return;
		}
		// Delete the active export pointer for members.
		delete_user_meta( $current_user_id, $this->get_active_meta_key( 'members' ) );
		// Delete the export record stored under the owner.
		$key = $this->get_export_meta_key( $export_id );
		delete_user_meta( $current_user_id, $key );
		// Clear transients.
		delete_transient( 'pmpro_export_owner_' . $export_id );
		delete_transient( 'pmpro_export_token_' . $export_id );
	}

	/**
	 * Schedule deletion of the exported file via Action Scheduler.
	 *
	 * @param string $file_name The file name within the exports restricted dir.
	 * @param int    $delay     Seconds from now to delete the file.
	 * @return void
	 */
	protected function schedule_file_deletion( $file_name, $delay ) {
		$timestamp = time() + max( 0, (int) $delay );
		PMPro_Action_Scheduler::instance()->maybe_add_task( 'pmpro_export_delete_file', array( 'file' => $file_name ), 'pmpro_async_tasks', $timestamp );
	}

	/**
	 * Action Scheduler task: delete an export file from disk.
	 *
	 * @param array $args Should contain 'file'.
	 * @return void
	 */
	public function delete_file_task( $args ) {
		if ( empty( $args['file'] ) ) {
			return;
		}
		$file_path = $this->get_file_path( basename( $args['file'] ) );
		if ( $file_path && file_exists( $file_path ) ) {
			@unlink( $file_path );
		}
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
		if ( 'members' !== $type ) {
			return array( 'error' => __( 'Unsupported export type.', 'paid-memberships-pro' ) );
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return array( 'error' => __( 'Not logged in.', 'paid-memberships-pro' ) );
		}

		// If an active export for this type exists for the user, return it instead of creating a new one.
		$active = $this->get_active_export_for_user( $user_id, $type );
		if ( $active && ! in_array( $active['status'], array( 'complete', 'error', 'cancelled' ), true ) ) {
			return $this->format_public_export_response( $active );
		}

		// Ensure restricted dir exists.
		pmpro_set_up_restricted_files_directory();

		// Normalize and sanitize args.
		$filters = $this->sanitize_members_filters( $args );

		// Determine counts.
		$total = $this->members_count_total( $filters );
		$chunk_size = (int) apply_filters( 'pmpro_members_export_chunk_size', $this->default_chunk_size );
		if ( $chunk_size < 1 ) {
			$chunk_size = $this->default_chunk_size;
		}
		$threshold = (int) apply_filters( 'pmpro_members_export_async_threshold', $this->default_async_threshold );
		if ( $threshold < 1 ) {
			$threshold = $this->default_async_threshold;
		}

		// Create export record in user meta.
		$export = $this->create_export_record( $user_id, $type, $filters, $total, $chunk_size );

		// Decide sync vs async.
		$should_async = $force_async || ( $total > $threshold );

		if ( ! $should_async ) {
			// Synchronous: process all chunks quickly in this request.
			// Chunked helps with memory usage.
			$offset = 0;
			$write_header = true;
			while ( $offset < $total ) {
				$remaining = $total - $offset;
				$limit     = min( $chunk_size, $remaining );
				$ids       = $this->members_fetch_ids_chunk( $filters, $offset, $limit );
				$written   = $this->members_write_rows( $export, $ids, $write_header );
				$write_header = false; // Only on first chunk
				$offset   += $limit;
				$export['processed_count'] += $written;
				$export['next_offset'] = $offset;
				$this->save_export_record( $export );
			}
			// Finalize.
			$export['status'] = 'complete';
			$this->save_export_record( $export );
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

		// Process chunk by type.
		if ( 'members' === $export['type'] ) {
			$total       = (int) $export['total_count'];
			$offset      = (int) $export['next_offset'];
			$chunk_size  = (int) $export['chunk_size'];
			$remaining   = max( 0, $total - $offset );
			$limit       = min( $chunk_size, $remaining );

			try {
				if ( $remaining > 0 ) {
					$ids = $this->members_fetch_ids_chunk( $export['filters'], $offset, $limit );
					$write_header = ( $offset === 0 );
					$written = $this->members_write_rows( $export, $ids, $write_header );

					$export['processed_count'] += $written;
					$export['next_offset']      = $offset + $limit;
					$export['status']           = ( $export['processed_count'] >= $total ) ? 'complete' : 'running';
					$this->save_export_record( $export );
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
	}

	/**
	 * Build a public response structure for REST/UI.
	 */
	protected function format_public_export_response( $export ) {
		$percent = 0;
		if ( (int) $export['total_count'] > 0 ) {
			$percent = (int) round( ( (int) $export['processed_count'] / (int) $export['total_count'] ) * 100 );
		} elseif ( 'complete' === $export['status'] ) {
			$percent = 100;
		}
		$resp = array(
			'export_id'       => $export['id'],
			'type'            => $export['type'],
			'status'          => $export['status'],
			'total_count'     => (int) $export['total_count'],
			'processed_count' => (int) $export['processed_count'],
			'percent'         => $percent,
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

		// Self-heal: if export is queued/running but no chunk task is pending, enqueue it.
		if ( in_array( $export['status'], array( 'queued', 'running' ), true ) && class_exists( '\ActionScheduler' ) ) {
			$args = array(
				array(
					'export_id' => $export['id'],
					'user_id'   => (int) $export['user_id'],
				),
			);
			$exists = PMPro_Action_Scheduler::instance()->has_existing_task( 'pmpro_export_process_chunk', $args, 'pmpro_async_tasks' );
			if ( ! $exists ) {
				$this->enqueue_next_chunk( $export['id'], $export['user_id'] );
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
		$export = $this->get_export_record( $export_id );
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
		// Capability check: must have permission to export members.
		if ( ! current_user_can( 'pmpro_memberslistcsv' ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	// ===== Storage helpers (User Meta) ===== //

	protected function get_export_meta_key( $export_id ) {
		return 'pmpro_export_' . $export_id;
	}

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
	protected function create_export_record( $user_id, $type, $filters, $total, $chunk_size ) {
		$export_id = wp_generate_uuid4();
		// Generate a URL-safe token (alphanumeric only) to avoid reserved characters breaking query strings.
		$token     = wp_generate_password( 40, false, false );
		$token_hash = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
		$file_name = $type . '-' . $export_id; '.csv';

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
		$key = $this->get_export_meta_key( $export_id );
		$raw = get_user_meta( $user_id, $key, true );
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

	// ===== Members Exports ===== //

	protected function sanitize_members_filters( $args ) {
		$filters = array();
		$filters['l'] = isset( $args['l'] ) ? sanitize_text_field( $args['l'] ) : '';
		$filters['s'] = isset( $args['s'] ) ? trim( sanitize_text_field( $args['s'] ) ) : '';
		return $filters;
	}

	protected function members_count_total( $filters ) {
		global $wpdb;

		$sql = "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u ";
		$needs_usermeta_join = false;
		$search = $this->build_members_search_sql_fragment( $filters, $needs_usermeta_join );
		if ( $needs_usermeta_join ) {
			$sql .= " LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
		}
		$sql .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
		$sql .= " WHERE mu.membership_id > 0 ";

		$filter = $this->build_members_filter_sql_fragment( $filters );
		$sql    .= $search . $filter;

		// Allow manipulation of SQL if needed.
		$sql = apply_filters( 'pmpro_members_list_sql', $sql );
		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql ) );
		return max( 0, $count );
	}

	protected function members_fetch_ids_chunk( $filters, $offset, $limit ) {
		global $wpdb;

		$sql = "SELECT DISTINCT u.ID FROM {$wpdb->users} u ";
		$needs_usermeta_join = false;
		$search = $this->build_members_search_sql_fragment( $filters, $needs_usermeta_join );
		if ( $needs_usermeta_join ) {
			$sql .= " LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
		}
		$sql .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
		$sql .= " WHERE mu.membership_id > 0 ";

		$filter = $this->build_members_filter_sql_fragment( $filters );
		$sql    .= $search . $filter;
		$sql .= ' ORDER BY u.ID ';
		$sql .= $wpdb->prepare( ' LIMIT %d, %d', (int) $offset, (int) $limit );

		$sql = apply_filters( 'pmpro_members_list_sql', $sql );
		$ids = $wpdb->get_col( $wpdb->prepare( $sql ) );
		if ( empty( $ids ) ) {
			return array();
		}
		return array_map( 'intval', $ids );
	}

	protected function members_write_rows( &$export, $user_ids, $write_header ) {
		global $wpdb;
		if ( empty( $user_ids ) ) {
			return 0;
		}

		// File path and handle.
		$file_path = $this->get_file_path( $export['file_name'] );
		$fh = fopen( $file_path, 'a' );
		if ( false === $fh ) {
			throw new \RuntimeException( __( 'Unable to write to export file.', 'paid-memberships-pro' ) );
		}

		// Columns and header (reuse existing filters to stay compatible).
		$dateformat = apply_filters( 'pmpro_memberslist_csv_dateformat', 'Y-m-d' );

		$default_columns = array(
			array('theuser', 'ID'),
			array('theuser', 'user_login'),
			array('metavalues', 'first_name'),
			array('metavalues', 'last_name'),
			array('theuser', 'user_email'),
			array('theuser', 'membership'),
			array('discount_code', 'id'),
			array('discount_code', 'code'),
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
			$headers = array();
			foreach ( $default_columns as $col ) {
				$key = $col[0] . '|' . $col[1];
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
			fprintf( $fh, "%s\n", $csv_header );
		}

		// Build user rows with a single query similar to the original export for performance.
		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
		$filter = $this->build_members_filter_sql_fragment( $export['filters'] );
		$needs_usermeta_join = false;
		$search = $this->build_members_search_sql_fragment( $export['filters'], $needs_usermeta_join );
		if ( ! empty( $search ) ) {
			$search = str_replace( '%', '%%', $search ); // escape for prepare
		}

		$userSql = "
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
		$userSql = call_user_func( array( $wpdb, 'prepare' ), $userSql, $user_ids );
		// Query is already prepared above; safe to execute.
		$usr_data = $wpdb->get_results( $userSql );

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
			$disSql = $wpdb->prepare( "
				SELECT c.id, c.code
				FROM {$wpdb->pmpro_discount_codes_uses} cu
				LEFT JOIN {$wpdb->pmpro_discount_codes} c ON cu.code_id = c.id
				WHERE cu.user_id = %d
				ORDER BY c.id DESC
				LIMIT 1", $theuser->ID );
			$discount_code = $wpdb->get_row( $disSql );
			if ( empty( $discount_code ) ) {
				$discount_code = (object) array( 'id' => '', 'code' => '' );
			}

			// Default columns
			$csvoutput = array();
			foreach ( $default_columns as $col ) {
				$val = isset( ${$col[0]}->{$col[1]} ) ? ${$col[0]}->{$col[1]} : null;
				$csvoutput[] = $this->csv_enclose( $val );
			}

			// Subscription info
			$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $theuser->ID, $theuser->membership_id );
			$csvoutput[] = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_subscription_transaction_id() );
			$csvoutput[] = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_billing_amount() );
			$csvoutput[] = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_cycle_number() );
			$csvoutput[] = $this->csv_enclose( empty( $subscriptions ) ? '' : $subscriptions[0]->get_cycle_period() );
			$csvoutput[] = $this->csv_enclose( empty( $subscriptions ) ? '' : date_i18n( $dateformat, $subscriptions[0]->get_next_payment_date() ) );

			// Dates
			$csvoutput[] = $this->csv_enclose( date_i18n( $dateformat, $theuser->joindate ) );
			$csvoutput[] = $this->csv_enclose( $theuser->startdate ? date_i18n( $dateformat, $theuser->startdate ) : __( 'N/A', 'paid-memberships-pro' ) );
			$csvoutput[] = $this->csv_enclose( $theuser->enddate ? date_i18n( $dateformat, $theuser->enddate ) : __( 'N/A', 'paid-memberships-pro' ) );

			// Extra columns
			if ( ! empty( $extra_columns ) ) {
				foreach ( $extra_columns as $heading => $callback ) {
					$val = call_user_func( $callback, $theuser, $heading );
					$val = ( is_string( $val ) || ! empty( $val ) ) ? $val : null;
					$csvoutput[] = $this->csv_enclose( $val );
				}
			}

			$line = implode( ',', $csvoutput ) . "\n";
			fprintf( $fh, '%s', $line );
			$rows_written++;
		}

		fclose( $fh );
		return $rows_written;
	}

	protected function build_members_filter_sql_fragment( $filters ) {
		global $wpdb;
		$l = isset( $filters['l'] ) ? $filters['l'] : '';
		$filter = '';
		if ( 'oldmembers' === $l ) {
			$filter = " AND mu.status <> 'active' ";
		}
		if ( 'expired' === $l || 'cancelled' === $l ) {
			$statuses = ( 'expired' === $l ) ? array( 'expired' ) : array( 'cancelled', 'admin_cancelled' );
			$filter = " AND mu.status IN ('" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "') ";
			$filter .= " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->pmpro_memberships_users} mu2 WHERE mu2.user_id = u.ID AND mu2.status = 'active' ) ";
		}
		if ( empty( $filter ) && is_numeric( $l ) ) {
			$filter = " AND mu.status = 'active' AND mu.membership_id = " . (int) $l . " ";
		}
		if ( empty( $filter ) ) {
			$filter = " AND mu.status = 'active' ";
		}
		return $filter;
	}

	/**
	 * Build the search SQL fragment for member queries.
	 *
	 * @param array $filters               Filters from the request.
	 * @param bool  $needs_usermeta_join   Set to true if a LEFT JOIN on usermeta is required for this search.
	 * @return string                      SQL fragment beginning with " AND ..."
	 */
	protected function build_members_search_sql_fragment( $filters, &$needs_usermeta_join = false ) {
		global $wpdb;
		$s = isset( $filters['s'] ) ? $filters['s'] : '';
		if ( empty( $s ) ) {
			return '';
		}
		$search_key = false;
		if ( strpos( $s, ':' ) !== false ) {
			$parts = explode( ':', $s );
			$search_key = array_shift( $parts );
			$s = implode( ':', $parts );
		}
		$s = str_replace( '*', '%', $s );
		if ( ! empty( $search_key ) ) {
			if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
				$key_column = 'u.user_' . $search_key;
				return " AND $key_column LIKE '%" . esc_sql( $s ) . "%' ";
			} elseif ( in_array( $search_key, array( 'discount', 'discount_code', 'dc' ), true ) ) {
				$user_ids = $wpdb->get_col( "SELECT dcu.user_id FROM {$wpdb->pmpro_discount_codes_uses} dcu LEFT JOIN {$wpdb->pmpro_discount_codes} dc ON dcu.code_id = dc.id WHERE dc.code = '" . esc_sql( $s ) . "'" );
				if ( empty( $user_ids ) ) { $user_ids = array(0); }
				return ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
			} else {
				$user_ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '" . esc_sql( $search_key ) . "' AND meta_value LIKE '%" . esc_sql( $s ) . "%'" );
				if ( empty( $user_ids ) ) { $user_ids = array(0); }
				return ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
			}
		} elseif ( function_exists( 'wp_is_large_user_count' ) && wp_is_large_user_count() ) {
			return " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
		} else {
			$needs_usermeta_join = true;
			return " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
		}
	}

	}
