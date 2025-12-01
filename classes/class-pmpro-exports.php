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
	 * Default chunk size and async threshold. Filterable.
	 */
	protected $default_chunk_size = 5000;
	protected $default_async_threshold = 5000;

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
		add_action( 'pmpro_export_process_chunk', array( $this, 'process_chunk' ), 10, 1 );
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
			$export_id = isset( $_REQUEST['export_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['export_id'] ) ) : '';
			// Do not sanitize token; use raw-unslashed for HMAC compare.
			$token     = isset( $_REQUEST['token'] ) ? wp_unslash( $_REQUEST['token'] ) : '';
			$file      = isset( $_REQUEST['pmpro_restricted_file'] ) ? basename( sanitize_text_field( wp_unslash( $_REQUEST['pmpro_restricted_file'] ) ) ) : '';
			if ( ! empty( $export_id ) && ! empty( $token ) && ! empty( $file ) ) {
				if ( class_exists( 'PMPro_Exports' ) ) {
					$exports = PMPro_Exports::instance();
					$can_access = $exports->validate_file_access( get_current_user_id(), $export_id, $token, $file );
				}
			}
		}
		return $can_access;
	}

	/**
	 * Start an export. Routes to type-specific create routine.
	 * Decides sync vs async based on threshold.
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

		// Background: queue first chunk.
		$this->enqueue_next_chunk( $export['id'] );
		return $this->format_public_export_response( $export );
	}

	/**
	 * Enqueue the next chunk for Action Scheduler.
	 *
	 * @param string $export_id
	 * @return void
	 */
	protected function enqueue_next_chunk( $export_id ) {
		// If PMPro is paused, still queue (AS will not run async until resumed).
		// PMPro's Action Scheduler helper respects queue throttling/deduplication.
		PMPro_Action_Scheduler::instance()->maybe_add_task( 'pmpro_export_process_chunk', array( 'export_id' => $export_id ), 'pmpro_async_tasks' );
	}

	/**
	 * Process one chunk for the given export.
	 *
	 * @param array $args Must contain 'export_id'.
	 * @return void
	 */
	public function process_chunk( $args ) {
		if ( empty( $args['export_id'] ) ) {
			return;
		}

		$export = $this->get_export_record( $args['export_id'] );
		if ( ! $export ) {
			return; // Nothing to do.
		}
		if ( in_array( $export['status'], array( 'complete', 'error', 'cancelled' ), true ) ) {
			return;
		}

		$user_id = get_current_user_id(); // context user may be 0 during AS; rely on stored user.

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
					$this->enqueue_next_chunk( $export['id'] );
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
			'token'                     => $export['token'], // We pass the token; validated via filter.
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

	protected function create_export_record( $user_id, $type, $filters, $total, $chunk_size ) {
		$export_id = wp_generate_uuid4();
		// Generate a URL-safe token (alphanumeric only) to avoid reserved characters breaking query strings.
		$token     = wp_generate_password( 40, false, false );
		$token_hash = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
		$file_name = $this->build_file_name( $type, $export_id );

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

	protected function save_export_record( $record ) {
		$key = $this->get_export_meta_key( $record['id'] );
		// Do not store plaintext token in meta.
		$to_store = $record;
		unset( $to_store['token'] );
		update_user_meta( (int) $record['user_id'], $key, wp_json_encode( $to_store ) );
	}

	protected function get_export_record( $export_id ) {
		$user_id = get_current_user_id();
		// We need to search all users? No, export is tied to the creator; try current user first.
		// If running in Action Scheduler (no user), we need to locate the owner. We'll attempt to scan authorship from known pattern: user meta stored under creator.
		// We cannot cheaply scan all users; store owner id in an option? Keeping minimal: also try fetching from all roles is expensive.
		// Instead, we store a backref under a site option mapping export_id=>user_id to allow AS lookup. Keep minimal and avoid site-wide data per user request, so we encode owner within export_id key on all users is not feasible.
		// Practical approach: during AS context, set current user to export['user_id'] before use. So here, we try both current user and the stored owner if present.

		// Try reading under current user first.
		$key = $this->get_export_meta_key( $export_id );
		$raw = get_user_meta( $user_id, $key, true );
		if ( ! empty( $raw ) ) {
			$data = json_decode( (string) $raw, true );
			if ( is_array( $data ) ) {
				return $data;
			}
		}

		// If not found for current user, try to infer owner by scanning the active keys for this type for current user (won't help) —
		// Instead accept a fallback: look up owner id based on export id stored temporarily in an option map. To keep minimal footprint per requirements, skip broad searches.
		// If the AS context passes export_id only, we rely on the next_offset and file path that don't require user context for writing. We still need owner for meta I/O.

		// Attempt: find owner id in a transitory site cache specific to this export (set when creating record). If missing, we cannot load record here.
		$owner_map_key = 'pmpro_export_owner_' . $export_id;
		$owner_id = (int) get_transient( $owner_map_key );
		if ( $owner_id > 0 ) {
			$raw = get_user_meta( $owner_id, $key, true );
			if ( ! empty( $raw ) ) {
				$data = json_decode( (string) $raw, true );
				if ( is_array( $data ) ) {
					return $data;
				}
			}
		}

		return null;
	}

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

	protected function build_file_name( $type, $export_id ) {
		switch ( $type ) {
			case 'members':
			default:
				return 'members-' . $export_id . '.csv';
		}
	}

	protected function get_file_path( $file_name ) {
		return pmpro_get_restricted_file_path( 'exports', $file_name );
	}

	// ===== Members export implementation ===== //

	protected function sanitize_members_filters( $args ) {
		$filters = array();
		$filters['l'] = isset( $args['l'] ) ? sanitize_text_field( $args['l'] ) : '';
		$filters['s'] = isset( $args['s'] ) ? trim( sanitize_text_field( $args['s'] ) ) : '';
		return $filters;
	}

	protected function members_count_total( $filters ) {
		global $wpdb;

		$search_key = false;
		$s = isset( $filters['s'] ) ? $filters['s'] : '';
		if ( ! empty( $s ) && strpos( $s, ':' ) !== false ) {
			$parts = explode( ':', $s );
			$search_key = array_shift( $parts );
			$s = implode( ':', $parts );
		}
		$s = str_replace( '*', '%', $s );

		$l = isset( $filters['l'] ) ? $filters['l'] : '';

		$sql = "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u ";
		$search = '';

		if ( $s ) {
			if ( ! empty( $search_key ) ) {
				if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
					$key_column = 'u.user_' . $search_key;
					$search = " AND $key_column LIKE '%" . esc_sql( $s ) . "%' ";
				} elseif ( in_array( $search_key, array( 'discount', 'discount_code', 'dc' ), true ) ) {
					$user_ids = $wpdb->get_col( "SELECT dcu.user_id FROM {$wpdb->pmpro_discount_codes_uses} dcu LEFT JOIN {$wpdb->pmpro_discount_codes} dc ON dcu.code_id = dc.id WHERE dc.code = '" . esc_sql( $s ) . "'" );
					if ( empty( $user_ids ) ) { $user_ids = array(0); }
					$search = ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
				} else {
					$user_ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '" . esc_sql( $search_key ) . "' AND meta_value LIKE '%" . esc_sql( $s ) . "%'" );
					if ( empty( $user_ids ) ) { $user_ids = array(0); }
					$search = ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
				}
			} elseif ( function_exists( 'wp_is_large_user_count' ) && wp_is_large_user_count() ) {
				$search = " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
			} else {
				$sql .= " LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
				$search = " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
			}
		}

		$sql .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
		$sql .= " WHERE mu.membership_id > 0 ";

		$filter = null;
		if ( 'oldmembers' === $l ) {
			$filter = " AND mu.status <> 'active' ";
		}
		if ( 'expired' === $l || 'cancelled' === $l ) {
			$statuses = ( 'expired' === $l ) ? array( 'expired' ) : array( 'cancelled', 'admin_cancelled' );
			$filter = " AND mu.status IN ('" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "') ";
			$filter .= " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->pmpro_memberships_users} mu2 WHERE mu2.user_id = u.ID AND mu2.status = 'active' ) ";
		}
		if ( is_null( $filter ) && is_numeric( $l ) ) {
			$filter = " AND mu.status = 'active' AND mu.membership_id = " . (int) $l . " ";
		}
		if ( is_null( $filter ) ) {
			$filter = " AND mu.status = 'active' ";
		}
		if ( $s ) {
			$sql .= $search;
		}
		$sql .= $filter;

		// Allow manipulation of SQL if needed.
		$sql = apply_filters( 'pmpro_members_list_sql', $sql );
		$count = (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return max( 0, $count );
	}

	protected function members_fetch_ids_chunk( $filters, $offset, $limit ) {
		global $wpdb;

		$search_key = false;
		$s = isset( $filters['s'] ) ? $filters['s'] : '';
		if ( ! empty( $s ) && strpos( $s, ':' ) !== false ) {
			$parts = explode( ':', $s );
			$search_key = array_shift( $parts );
			$s = implode( ':', $parts );
		}
		$s = str_replace( '*', '%', $s );

		$l = isset( $filters['l'] ) ? $filters['l'] : '';

		$sql = "SELECT DISTINCT u.ID FROM {$wpdb->users} u ";
		$search = '';

		if ( $s ) {
			if ( ! empty( $search_key ) ) {
				if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
					$key_column = 'u.user_' . $search_key;
					$search = " AND $key_column LIKE '%" . esc_sql( $s ) . "%' ";
				} elseif ( in_array( $search_key, array( 'discount', 'discount_code', 'dc' ), true ) ) {
					$user_ids = $wpdb->get_col( "SELECT dcu.user_id FROM {$wpdb->pmpro_discount_codes_uses} dcu LEFT JOIN {$wpdb->pmpro_discount_codes} dc ON dcu.code_id = dc.id WHERE dc.code = '" . esc_sql( $s ) . "'" );
					if ( empty( $user_ids ) ) { $user_ids = array(0); }
					$search = ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
				} else {
					$user_ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '" . esc_sql( $search_key ) . "' AND meta_value LIKE '%" . esc_sql( $s ) . "%'" );
					if ( empty( $user_ids ) ) { $user_ids = array(0); }
					$search = ' AND u.ID IN(' . implode( ',', array_map( 'intval', $user_ids ) ) . ') ';
				}
			} elseif ( function_exists( 'wp_is_large_user_count' ) && wp_is_large_user_count() ) {
				$search = " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
			} else {
				$sql .= " LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
				$search = " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
			}
		}

		$sql .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
		$sql .= " WHERE mu.membership_id > 0 ";

		$filter = null;
		if ( 'oldmembers' === $l ) {
			$filter = " AND mu.status <> 'active' ";
		}
		if ( 'expired' === $l || 'cancelled' === $l ) {
			$statuses = ( 'expired' === $l ) ? array( 'expired' ) : array( 'cancelled', 'admin_cancelled' );
			$filter = " AND mu.status IN ('" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "') ";
			$filter .= " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->pmpro_memberships_users} mu2 WHERE mu2.user_id = u.ID AND mu2.status = 'active' ) ";
		}
		if ( is_null( $filter ) && is_numeric( $l ) ) {
			$filter = " AND mu.status = 'active' AND mu.membership_id = " . (int) $l . " ";
		}
		if ( is_null( $filter ) ) {
			$filter = " AND mu.status = 'active' ";
		}
		if ( $s ) {
			$sql .= $search;
		}
		$sql .= $filter;
		$sql .= ' ORDER BY u.ID ';
		$sql .= $wpdb->prepare( ' LIMIT %d, %d', (int) $offset, (int) $limit );

		$sql = apply_filters( 'pmpro_members_list_sql', $sql );
		$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
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
			$csv_header = 'id,username,firstname,lastname,email,membership,discount_code_id,discount_code,subscription_transaction_id,billing_amount,cycle_number,cycle_period,next_payment_date,joined,startdate';
			$csv_header .= ( isset( $export['filters']['l'] ) && 'oldmembers' === $export['filters']['l'] ) ? ',ended' : ',expires';
			if ( ! empty( $extra_columns ) ) {
				foreach ( $extra_columns as $heading => $callback ) {
					$csv_header .= ',' . $heading;
				}
			}
			$csv_header = apply_filters( 'pmpro_members_list_csv_heading', $csv_header );
			$csv_header .= "\n";
			fprintf( $fh, '%s', $csv_header );
		}

		// Build user rows with a single query similar to the original export for performance.
		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
		$filter = $this->build_members_filter_sql_fragment( $export['filters'] );
		$search = $this->build_members_search_sql_fragment( $export['filters'] );
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
			LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
			LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id
			LEFT JOIN {$wpdb->pmpro_membership_levels} m ON mu.membership_id = m.id
			WHERE u.ID in ( {$placeholders} ) AND mu.membership_id > 0 {$filter} {$search}
			GROUP BY u.ID, mu.membership_id
			ORDER BY u.ID
		";
		$userSql = call_user_func( array( $wpdb, 'prepare' ), $userSql, $user_ids );
		$usr_data = $wpdb->get_results( $userSql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

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

	protected function build_members_search_sql_fragment( $filters ) {
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
			// Note: when used, caller must have joined usermeta.
			return " AND ( u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%' OR u.display_name LIKE '%" . esc_sql( $s ) . "%' ) ";
		}
	}

	protected function csv_enclose( $s ) {
		$s = (string) $s;
		return '"' . str_replace( '"', '\\"', $s ) . '"';
	}
}