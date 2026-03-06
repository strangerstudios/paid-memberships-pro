<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PMPro_Orders_List_Table extends WP_List_Table {
	/**
	 * The text domain of this plugin.
	 *
	 * @since 2.11
	 *
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	protected $plugin_text_domain;

	/**
	 * Call the parent constructor to override the defaults $args
	 *
	 * @param string $plugin_text_domain    Text domain of the plugin.
	 *
	 * @since 2.11
	 */
	public function __construct() {

		$this->plugin_text_domain = 'paid-memberships-pro';

		parent::__construct(
			array(
				'plural'   => 'orders',
				// Plural value used for labels and the objects being listed.
				'singular' => 'order',
				// Singular label for an object being listed, e.g. 'post'.
				'ajax'     => false,
				// If true, the parent class will call the _js_vars() method in the footer
			)
		);
	}

	/**
	 * Sets up screen options for the orders list table.
	 *
	 * @since 3.0
	 */
	public static function hook_screen_options() {
		// If we're viewing a single order, bail.
		if ( ! empty( $_REQUEST['id'] ) ) {
			return;
		}

		$list_table = new PMPro_Orders_List_Table();
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'label'   => __( 'Orders per page', 'paid-memberships-pro' ),
				'option'  => 'pmpro_orders_per_page',
			)
		);
		add_filter(
			'screen_settings',
			array(
				$list_table,
				'screen_controls',
			),
			10,
			2
		);
		add_filter(
			'set-screen-option',
			array(
				$list_table,
				'set_screen_option',
			),
			10,
			3
		);
		set_screen_options();
	}

	/**
	 * Sets the screen options.
	 *
	 * @param string $dummy   Unused.
	 * @param string $option  Screen option name.
	 * @param string $value   Screen option value.
	 * @return string
	 */
	public function set_screen_option( $dummy, $option, $value ) {
		if ( 'pmpro_orders_per_page' === $option ) {
			return $value;
		} else {
			return $dummy;
		}
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
	 *
	 * @since 2.11
	 */
	public function prepare_items() {
		
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$items_per_page = $this->get_items_per_page( 'pmpro_orders_per_page' );
		/**
		 * Filter to set the default number of items to show per page
		 * on the Orders page in the admin.
		 *
		 * @since 1.8.4.5
		 *
		 * @param int $limit The number of items to show per page.
		 */
		$items_per_page = apply_filters( 'pmpro_orders_per_page', $items_per_page );
		
		$this->items = $this->sql_table_data( false );		
		$total_items = $this->sql_table_data( true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $items_per_page,
				'total_pages' => ceil( $total_items / $items_per_page ),
			)
		);
		
	}

	/**
	 * Get a list of columns.
	 *
	 * The format is: 'internal-name' => 'Title'
	 *
	 * @since 2.11
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'order_id'      => __( 'ID', 'paid-memberships-pro' ),
			//We cant use 'code' as is because it formats the column as code
			'order_code'    => __( 'Code', 'paid-memberships-pro' ),
			'user'          => __( 'User', 'paid-memberships-pro' ),
			'level'         => __( 'Level', 'paid-memberships-pro' ),
			'total'         => __( 'Total', 'paid-memberships-pro' ),
			'billing'       => __( 'Billing', 'paid-memberships-pro' ),
			'gateway'       => __( 'Gateway', 'paid-memberships-pro' ),
			'transaction_ids'   => __( 'Transaction IDs', 'paid-memberships-pro' ),
			'order_status'        => __( 'Status', 'paid-memberships-pro' ),
			'date'          => __( 'Date', 'paid-memberships-pro' ),
		);

		// Re-implementing old hook, will be deprecated.
		ob_start();
		do_action( 'pmpro_orders_extra_cols_header' );
		$extra_cols = ob_get_clean();
		preg_match_all( '/<th>(.*?)<\/th>/s', $extra_cols, $matches );
		$custom_field_num = 0;
		foreach ( $matches[1] as $match ) {
			$columns[ 'custom_field_' . $custom_field_num ] = $match;
			$custom_field_num++;
		}

		// Shortcut for editing columns in default discount code list location.
		$current_screen = get_current_screen();
		if ( ! empty( $current_screen ) && strpos( $current_screen->id, "pmpro-orders" ) !== false ) {
			$columns = apply_filters( 'pmpro_manage_orderslist_columns', $columns );
		}


		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		$user = wp_get_current_user();
		if ( ! $user ) {
			return array();
		}

		// Check whether the current user has changed screen options or not.
		$hidden = get_user_meta( $user->ID, 'manage' . $this->screen->id . 'columnshidden', true );

		// If user meta is not found, add the default hidden columns.
		if ( ! is_array( $hidden ) ) {
			$hidden = array(
				'order_id',
			);
			update_user_meta( $user->ID, 'manage' . $this->screen->id . 'columnshidden', $hidden );
		}

		return $hidden;
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since 2.11
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		/**
		 * actual sorting still needs to be done by prepare_items.
		 * specify which columns should have the sort icon.
		 *
		 * key => value
		 * column name_in_list_table => columnname in the db
		 */
		$sortable_columns = array(
			'order_id' => array( 'id', true ),
			'level' => array( 'name', false ),
			'total' => array( 'total', false ),
			'order_status' => array( 'status_label', false ),
			'date' => array( 'timestamp', false ),
		);		
		return $sortable_columns;
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since 2.11
	 *
	 * @return void
	 */
	public function no_items() {

		esc_html_e( 'No orders found.', 'paid-memberships-pro' );

	}

	/**
	 * Get the table data
	 *
	 * @return Array|integer if $count parameter = true
	 */
	private function sql_table_data( $count = false, $limit = 15 ) {

		global $wpdb;
		$now = current_time( 'timestamp' );

		$s = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( $_REQUEST['s'] ) ) : '';
		$pn = isset( $_REQUEST['paged'] ) ? intval( $_REQUEST['paged'] ) : 1;

		$items_per_page = $this->get_items_per_page( 'pmpro_orders_per_page' );
		/**
		 * Filter to set the default number of items to show per page
		 * on the Orders page in the admin.
		 *
		 * @since 1.8.4.5
		 *
		 * @param int $limit The number of items to show per page.
		 */
		$limit = apply_filters( 'pmpro_orders_per_page', $items_per_page );

		$end   = $pn * $limit;
		$start = $end - $limit;

		// Build filter conditions. Supports multiple simultaneous filters combined with AND.
		$conditions = array();
		$needs_discount_code_join = false;

		// Level filter.
		$l = isset( $_REQUEST['l'] ) ? intval( $_REQUEST['l'] ) : 0;
		if ( ! empty( $l ) ) {
			$conditions[] = $wpdb->prepare( 'o.membership_id = %d', $l );
		}

		// Status filter.
		$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
		if ( ! empty( $status ) ) {
			$conditions[] = $wpdb->prepare( "o.status = %s", $status );
		}

		// Discount code filter.
		$discount_code = isset( $_REQUEST['discount-code'] ) ? intval( $_REQUEST['discount-code'] ) : 0;
		if ( ! empty( $discount_code ) ) {
			$conditions[] = $wpdb->prepare( 'dc.code_id = %d', $discount_code );
			$needs_discount_code_join = true;
		}

		// Date filter (predefined or custom range).
		$predefined_date = isset( $_REQUEST['predefined-date'] ) ? sanitize_text_field( $_REQUEST['predefined-date'] ) : '';
		$start_date_input = isset( $_REQUEST['start-date'] ) ? sanitize_text_field( $_REQUEST['start-date'] ) : '';
		$end_date_input = isset( $_REQUEST['end-date'] ) ? sanitize_text_field( $_REQUEST['end-date'] ) : '';

		if ( ! empty( $predefined_date ) ) {
			if ( $predefined_date == 'Last Month' ) {
				$start_date = date( 'Y-m-d', strtotime( 'first day of last month', $now ) );
				$end_date   = date( 'Y-m-d', strtotime( 'last day of last month', $now ) );
			} elseif ( $predefined_date == 'This Month' ) {
				$start_date = date( 'Y-m-d', strtotime( 'first day of this month', $now ) );
				$end_date   = date( 'Y-m-d', strtotime( 'last day of this month', $now ) );
			} elseif ( $predefined_date == 'This Year' ) {
				$year       = date( 'Y', $now );
				$start_date = date( 'Y-m-d', strtotime( "first day of January $year", $now ) );
				$end_date   = date( 'Y-m-d', strtotime( "last day of December $year", $now ) );
			} elseif ( $predefined_date == 'Last Year' ) {
				$year       = date( 'Y', $now ) - 1;
				$start_date = date( 'Y-m-d', strtotime( "first day of January $year", $now ) );
				$end_date   = date( 'Y-m-d', strtotime( "last day of December $year", $now ) );
			}

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$start_date = get_gmt_from_date( $start_date . ' 00:00:00' );
				$end_date   = get_gmt_from_date( $end_date . ' 23:59:59' );
				$conditions[] = $wpdb->prepare( "o.timestamp BETWEEN %s AND %s", $start_date, $end_date );
			}
		} elseif ( ! empty( $start_date_input ) && ! empty( $end_date_input ) ) {
			$start_date = get_gmt_from_date( $start_date_input . ' 00:00:00' );
			$end_date   = get_gmt_from_date( $end_date_input . ' 23:59:59' );
			$conditions[] = $wpdb->prepare( "o.timestamp BETWEEN %s AND %s", $start_date, $end_date );
		}

		// Gateway filter.
		$gateway = isset( $_REQUEST['gateway'] ) ? sanitize_text_field( $_REQUEST['gateway'] ) : '';
		if ( ! empty( $gateway ) ) {
			$conditions[] = $wpdb->prepare( "o.gateway = %s", $gateway );
		}

		// Total filter.
		$total_filter = isset( $_REQUEST['total'] ) ? sanitize_text_field( $_REQUEST['total'] ) : '';
		if ( $total_filter === 'paid' ) {
			$conditions[] = "o.total > 0";
		} elseif ( $total_filter === 'free' ) {
			$conditions[] = "o.total = 0";
		}

		// Combine conditions with AND.
		if ( empty( $conditions ) ) {
			$condition = '1=1';
		} else {
			$condition = implode( ' AND ', $conditions );
		}

		// Backward-compatible hook. Pass combined condition and legacy filter value.
		$legacy_filter = ! empty( $_REQUEST['filter'] ) ? sanitize_text_field( $_REQUEST['filter'] ) : 'all';
		$condition = apply_filters( 'pmpro_admin_orders_query_condition', $condition, $legacy_filter );

		$orderby = '';

		if( ! empty( $_REQUEST['orderby'] ) && ! $count ) {

			if ( isset( $_REQUEST['orderby'] ) ) {
				$orderby = $this->sanitize_orderby( sanitize_text_field( $_REQUEST['orderby'] ) );
			} else {
				$orderby = 'id';
			}

			if ( $_REQUEST['order'] == 'asc' ) {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}

			if ( $orderby == 'total' ) {
				$orderby = 'total + 0'; //This pads the number and allows it to sort correctly
			}

			$order_query = "ORDER BY $orderby $order";
		} else {
			$order_query = 'ORDER BY id DESC';
		}

		$paid_string = __( 'Paid', 'paid-memberships-pro' );
		$cancelled_string = __( 'Cancelled', 'paid-memberships-pro' );
		$refunded_string = __( 'Refunded', 'paid-memberships-pro' );
		$token_string = __( 'Token', 'paid-memberships-pro' );
		$review_string = __( 'Review', 'paid-memberships-pro' );
		$pending_string = __( 'Pending', 'paid-memberships-pro' );
		$error_string = __( 'Error', 'paid-memberships-pro' );

		if( $count ) {
			$sqlQuery = 'SELECT COUNT(DISTINCT o.id) ';
		} else {
			$sqlQuery = "SELECT o.id, CASE WHEN o.status = 'success' THEN 'Paid' WHEN o.status = 'cancelled' THEN '$paid_string' WHEN o.status = 'refunded' THEN '$refunded_string' WHEN o.status = 'token' THEN '$token_string' WHEN o.status = 'review' THEN '$review_string' WHEN o.status = 'pending' THEN '$pending_string' WHEN o.status = 'error' THEN '$error_string' ELSE '$cancelled_string' END as `status_label` ";
		}

		$sqlQuery .= "FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_membership_levels ml ON o.membership_id = ml.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID ";

		// If we are filtering by discount code, we need to pull that information into the query.
		if ( $needs_discount_code_join ) {
			$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
		}

		if ( $s ) {
			// Check if we are searching by a specific key or generally.
			if ( strpos( $s, ':' ) !== false ) {
				// Get the search key and value.
				$parts = explode( ':', $s );
				$search_key = array_shift( $parts );
				$s = implode( ':', $parts );

				$sqlQuery .= 'WHERE (1=2 ';
				// If there's a colon in the search string, make the search smarter.
				if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
					$key_column = 'u.user_' . $search_key; // All search key options above are safe for use in a query.
					$sqlQuery .= " OR $key_column LIKE '%" . esc_sql( $s ) . "%' ";
				} else {
					// Assume order table column.
					$sqlQuery .= " OR o.$search_key LIKE '%" . esc_sql( $s ) . "%' ";
				}
				$sqlQuery .= ') ';
			} else {
				$join_with_usermeta = apply_filters( 'pmpro_orders_search_usermeta', false );

				if ( $join_with_usermeta ) {
					$sqlQuery .= "LEFT JOIN $wpdb->usermeta um ON o.user_id = um.user_id ";
				}


				$sqlQuery .= 'WHERE (1=2 ';

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

				if ( $join_with_usermeta ) {
					$fields[] = 'um.meta_value';
				}

				$fields = apply_filters( 'pmpro_orders_search_fields', $fields );

				foreach ( $fields as $field ) {
					$sqlQuery .= ' OR ' . esc_sql( $field ) . " LIKE '%" . esc_sql( $s ) . "%' ";
				}
				$sqlQuery .= ') ';
			}

			//Not escaping here because we escape the values in the condition statement
			$sqlQuery .= 'AND ' . $condition . ' ';
			
		} else {

			//Not escaping here because we escape the values in the condition statement
			$sqlQuery .= "WHERE " . $condition . ' ';

		}

		if( $count ) {
			return $wpdb->get_var( $sqlQuery );    
		} else {
			$sqlQuery .= 'GROUP BY o.id ' . $order_query . " LIMIT " . esc_sql( $start ) . "," . esc_sql( $limit );
			$order_ids = $wpdb->get_col( $sqlQuery );
			$order_data = array();
			foreach ( $order_ids as $order_id ) {
				$order            = new MemberOrder();
				$order->nogateway = true;
				$order->getMemberOrderByID( $order_id );
				$order->getUser();

				$order_data[] = $order;
			}            
			return $order_data;
		}
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 * Renders the stackable filter UI.
	 *
	 * @since TBD
	 *
	 * @param string $which 'top' or 'bottom'.
	 */
	function extra_tablenav( $which ) {

		if ( $which !== 'top' ) {
			return;
		}

		global $wpdb;

		// Read current filter values from request.
		$l              = isset( $_REQUEST['l'] ) ? intval( $_REQUEST['l'] ) : 0;
		$status         = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
		$discount_code  = isset( $_REQUEST['discount-code'] ) ? intval( $_REQUEST['discount-code'] ) : 0;
		$predefined_date = isset( $_REQUEST['predefined-date'] ) ? sanitize_text_field( $_REQUEST['predefined-date'] ) : '';
		$start_date     = isset( $_REQUEST['start-date'] ) ? sanitize_text_field( $_REQUEST['start-date'] ) : '';
		$end_date       = isset( $_REQUEST['end-date'] ) ? sanitize_text_field( $_REQUEST['end-date'] ) : '';
		$gateway        = isset( $_REQUEST['gateway'] ) ? sanitize_text_field( $_REQUEST['gateway'] ) : '';
		$total_filter   = isset( $_REQUEST['total'] ) ? sanitize_text_field( $_REQUEST['total'] ) : '';

		// Count active filters for the toggle button badge.
		$active_filter_count = 0;
		if ( ! empty( $l ) ) {
			$active_filter_count++;
		}
		if ( ! empty( $status ) ) {
			$active_filter_count++;
		}
		if ( ! empty( $discount_code ) ) {
			$active_filter_count++;
		}
		if ( ! empty( $predefined_date ) || ( ! empty( $start_date ) && ! empty( $end_date ) ) ) {
			$active_filter_count++;
		}
		if ( ! empty( $gateway ) ) {
			$active_filter_count++;
		}
		if ( ! empty( $total_filter ) ) {
			$active_filter_count++;
		}

		// Prepare data for filter value selectors.
		$levels   = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );
		$statuses = array_filter( pmpro_getOrderStatuses(), 'strlen' ); // Remove empty string status.
		$codes    = $wpdb->get_results( "SELECT id, code FROM $wpdb->pmpro_discount_codes ORDER BY id DESC", OBJECT );

		// Get gateways that have been used in orders.
		$used_gateway_slugs = $wpdb->get_col( "SELECT DISTINCT gateway FROM $wpdb->pmpro_membership_orders WHERE gateway != ''" );
		$known_gateways     = pmpro_gateways();
		$gateway_options    = array();
		foreach ( $used_gateway_slugs as $gw_slug ) {
			$gateway_options[ $gw_slug ] = isset( $known_gateways[ $gw_slug ] ) ? $known_gateways[ $gw_slug ] : $gw_slug;
		}

		// Determine the date mode for the date filter.
		$has_date_filter = ! empty( $predefined_date ) || ( ! empty( $start_date ) && ! empty( $end_date ) );
		if ( ! $has_date_filter ) {
			$date_mode = '';
		} elseif ( ! empty( $predefined_date ) ) {
			$date_mode = 'predefined';
		} else {
			$date_mode = 'custom';
		}
		?>

		<input type="hidden" name="page" value="pmpro-orders" />

		<div id="pmpro-orders-filter-panel" class="pmpro_section" style="display: none;">
			<div class="pmpro-orders-sidebar-header">
				<h3><?php esc_html_e( 'Filters', 'paid-memberships-pro' ); ?></h3>
				<button type="button" id="pmpro-orders-close-filters" class="pmpro-orders-sidebar-close" aria-label="<?php esc_attr_e( 'Close filters', 'paid-memberships-pro' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>

			<div class="pmpro-orders-sidebar-body">
				<?php // Level filter. ?>
				<div class="pmpro-orders-filter-section">
					<label for="pmpro-filter-level"><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></label>
					<select id="pmpro-filter-level" name="l">
						<option value=""><?php esc_html_e( 'All Levels', 'paid-memberships-pro' ); ?></option>
						<?php foreach ( $levels as $level_obj ) { ?>
							<option value="<?php echo esc_attr( $level_obj->id ); ?>" <?php selected( $l, $level_obj->id ); ?>><?php echo esc_html( $level_obj->name ); ?></option>
						<?php } ?>
					</select>
				</div>

				<?php // Status filter. ?>
				<div class="pmpro-orders-filter-section">
					<label for="pmpro-filter-status"><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></label>
					<select id="pmpro-filter-status" name="status">
						<option value=""><?php esc_html_e( 'All Statuses', 'paid-memberships-pro' ); ?></option>
						<?php foreach ( $statuses as $the_status ) { ?>
							<option value="<?php echo esc_attr( $the_status ); ?>" <?php selected( $status, $the_status ); ?>><?php echo esc_html( $the_status ); ?></option>
						<?php } ?>
					</select>
				</div>

				<?php // Date filter. ?>
				<div class="pmpro-orders-filter-section">
					<label><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></label>
					<select id="pmpro-filter-date-mode" class="pmpro-orders-date-mode-select">
						<option value="" <?php selected( $date_mode, '' ); ?>><?php esc_html_e( 'All Time', 'paid-memberships-pro' ); ?></option>
						<option value="predefined" <?php selected( $date_mode, 'predefined' ); ?>><?php esc_html_e( 'Predefined', 'paid-memberships-pro' ); ?></option>
						<option value="custom" <?php selected( $date_mode, 'custom' ); ?>><?php esc_html_e( 'Custom Range', 'paid-memberships-pro' ); ?></option>
					</select>
					<div class="pmpro-orders-date-predefined" <?php echo $date_mode !== 'predefined' ? 'style="display:none;"' : ''; ?>>
						<select name="predefined-date" <?php echo $date_mode !== 'predefined' ? 'disabled' : ''; ?>>
							<option value="This Month" <?php selected( $predefined_date, 'This Month' ); ?>><?php esc_html_e( 'This Month', 'paid-memberships-pro' ); ?></option>
							<option value="Last Month" <?php selected( $predefined_date, 'Last Month' ); ?>><?php esc_html_e( 'Last Month', 'paid-memberships-pro' ); ?></option>
							<option value="This Year" <?php selected( $predefined_date, 'This Year' ); ?>><?php esc_html_e( 'This Year', 'paid-memberships-pro' ); ?></option>
							<option value="Last Year" <?php selected( $predefined_date, 'Last Year' ); ?>><?php esc_html_e( 'Last Year', 'paid-memberships-pro' ); ?></option>
						</select>
					</div>
					<div class="pmpro-orders-date-custom" <?php echo $date_mode !== 'custom' ? 'style="display:none;"' : ''; ?>>
						<input type="date" name="start-date" value="<?php echo esc_attr( $start_date ); ?>" <?php echo $date_mode !== 'custom' ? 'disabled' : ''; ?> />
						<span><?php esc_html_e( 'to', 'paid-memberships-pro' ); ?></span>
						<input type="date" name="end-date" value="<?php echo esc_attr( $end_date ); ?>" <?php echo $date_mode !== 'custom' ? 'disabled' : ''; ?> />
					</div>
				</div>

				<?php // Discount code filter (only show if codes exist). ?>
				<?php if ( ! empty( $codes ) ) { ?>
					<div class="pmpro-orders-filter-section">
						<label for="pmpro-filter-discount-code"><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></label>
						<select id="pmpro-filter-discount-code" name="discount-code">
							<option value=""><?php esc_html_e( 'All Codes', 'paid-memberships-pro' ); ?></option>
							<?php foreach ( $codes as $code ) { ?>
								<option value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
							<?php } ?>
						</select>
					</div>
				<?php } ?>

				<?php // Gateway filter. ?>
				<?php if ( ! empty( $gateway_options ) ) { ?>
					<div class="pmpro-orders-filter-section">
						<label for="pmpro-filter-gateway"><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?></label>
						<select id="pmpro-filter-gateway" name="gateway">
							<option value=""><?php esc_html_e( 'All Gateways', 'paid-memberships-pro' ); ?></option>
							<?php foreach ( $gateway_options as $gw_slug => $gw_name ) { ?>
								<option value="<?php echo esc_attr( $gw_slug ); ?>" <?php selected( $gateway, $gw_slug ); ?>><?php echo esc_html( $gw_name ); ?></option>
							<?php } ?>
						</select>
					</div>
				<?php } ?>

				<?php // Total filter. ?>
				<div class="pmpro-orders-filter-section">
					<label for="pmpro-filter-total"><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></label>
					<select id="pmpro-filter-total" name="total">
						<option value=""><?php esc_html_e( 'All Orders', 'paid-memberships-pro' ); ?></option>
						<option value="paid" <?php selected( $total_filter, 'paid' ); ?>><?php esc_html_e( 'Paid Orders (> $0)', 'paid-memberships-pro' ); ?></option>
						<option value="free" <?php selected( $total_filter, 'free' ); ?>><?php esc_html_e( 'Free Orders ($0)', 'paid-memberships-pro' ); ?></option>
					</select>
				</div>

			</div>

			<div class="pmpro-orders-sidebar-actions">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Apply Filters', 'paid-memberships-pro' ); ?>" />
				<?php if ( $active_filter_count > 0 ) { ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-orders' ) ); ?>" class="pmpro-orders-clear-filters"><?php esc_html_e( 'Clear All', 'paid-memberships-pro' ); ?></a>
				<?php } ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			if ( typeof window.pmproInitFilterSidebar !== 'function' ) {
				return;
			}

			window.pmproInitFilterSidebar({
				panelSelector: '#pmpro-orders-filter-panel',
				layoutSelector: '#pmpro-orders-layout',
				toggleButtonSelector: '#pmpro-orders-toggle-filters',
				closeButtonSelector: '#pmpro-orders-close-filters',
				select2ExcludeSelector: '.pmpro-orders-date-mode-select',
				dateModeSelector: '#pmpro-filter-date-mode',
				datePredefinedSelector: '.pmpro-orders-date-predefined',
				dateCustomSelector: '.pmpro-orders-date-custom'
			});
		});
		</script>
		<?php
	}

	/**
	 * Sanitize the orderby value.
	 * Only allow fields we want to order by.
	 * Make sure we append the correct table prefix.
	 * Make sure there is no other SQL in the value.
	 * @param string $orderby The column to order by.
	 * @return string The sanitized value.
	 */
	function sanitize_orderby( $orderby ) {

		$allowed_orderbys = array(
			'id'           => 'id',
			'name'	       => 'membership_id',
			'total'        => 'total',
			'status_label' => 'status',
			'timestamp'    => 'timestamp',
		);

	 	if ( ! empty( $allowed_orderbys[$orderby] ) ) {
			$orderby = $allowed_orderbys[$orderby];
		} else {
			$orderby = false;
		}

		return $orderby;
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		
		$column_item = (array) apply_filters( 'pmpro_order_list_item', $item );

		if ( isset( $column_item[ $column_name ] ) ) {
			// If the user is adding content via the "pmpro_order_list_item" filter.
			echo( esc_html( $column_item[ $column_name ] ) );
		} elseif ( 0 === strpos( $column_name, 'custom_field_' ) ) {
			// If the user is adding content via the "pmpro_orders_extra_cols_body" hook.
			// Re-implementing old hook, will be deprecated.			
			ob_start();            
			do_action( 'pmpro_orders_extra_cols_body', $item );
			$extra_cols = ob_get_clean();            
			preg_match_all( '/<td>(.*?)<\/td>/s', $extra_cols, $matches );
			
			$custom_field_num_arr = explode( 'custom_field_', $column_name );
			$custom_field_num     = $custom_field_num_arr[1];			
			if ( is_numeric( $custom_field_num ) && isset( $matches[1][ intval( $custom_field_num ) ] ) ) {
				// If the escaping here breaks your old column body, use the new filters.
				echo( wp_kses_post( $matches[1][ intval( $custom_field_num ) ] ) );
			}
		} else {
			// The preferred ways of doing things.
			do_action( 'pmpro_manage_orderlist_custom_column', $column_name, $item->id );
		}
		
	}

	/**
	 * Renders the columns order ID value
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_order_id( $item ) {

		return $item->id;

	}

	/**
	 * Renders the columns order code value
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_order_code( $item ) {
		?>
		<strong><a title="<?php echo esc_attr( sprintf( __( 'View order # %s', 'paid-memberships-pro' ), $item->code ) ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'id' => $item->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $item->code ); ?></a></strong>
		<div class="row-actions">
			<?php
			$delete_text = esc_html(
				sprintf(
					// translators: %s is the Order Code.
					__( 'Deleting orders is permanent and can affect active users. Are you sure you want to delete order %s?', 'paid-memberships-pro' ),
					str_replace( "'", '', $item->code )
				)
			);

			$delete_nonce_url = wp_nonce_url(
				add_query_arg(
					[
						'page'   => 'pmpro-orders',
						'action' => 'delete_order',
						'delete' => $item->id,
						'id'  => isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : null,
						'orderby' => isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : null,
						's' => isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
						'filter' => isset( $_REQUEST['filter'] ) ? sanitize_text_field( $_REQUEST['filter'] ) : null,
						'start-month' => isset( $_REQUEST['start-month'] ) ? intval( $_REQUEST['start-month'] ) : null,
						'start-day' => isset( $_REQUEST['start-day'] ) ? intval( $_REQUEST['start-day'] ) : null,
						'start-year' => isset( $_REQUEST['start-year'] ) ? intval( $_REQUEST['start-year'] ) : null,
						'end-month' => isset( $_REQUEST['end-month'] ) ? intval( $_REQUEST['end-month'] ) : null,
						'end-day' => isset( $_REQUEST['end-day'] ) ? intval( $_REQUEST['end-day'] ) : null,
						'end-year' => isset( $_REQUEST['end-year'] ) ? intval( $_REQUEST['end-year'] ) : null,
						'predefined-date' => isset( $_REQUEST['predefined-date'] ) ? sanitize_text_field( $_REQUEST['predefined-date'] ) : null,
						'l' => isset( $_REQUEST['l'] ) ? sanitize_text_field( $_REQUEST['l'] ) : null,
						'status' => isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : null,
						'discount-code' => isset( $_REQUEST['discount-code'] ) ? sanitize_text_field( $_REQUEST['discount-code'] ) : null,
					],
					admin_url( 'admin.php' )
				),
				'delete_order',
				'pmpro_orders_nonce'
			);

			$refund_text = esc_html(
				sprintf(
					// translators: %s is the Order Code.
					__( 'Refund order %s at the payment gateway. This action is permanent. The user and admin will receive an email confirmation after the refund is processed. Are you sure you want to refund this order?', 'paid-memberships-pro' ),
					str_replace( "'", '', $item->code )
				)
			);

			$refund_nonce_url = wp_nonce_url(
				add_query_arg(
					[
						'page'   => 'pmpro-orders',
						'action' => 'refund_order',
						'refund' => $item->id,
						'id'     => $item->id,
					],
					admin_url( 'admin.php' )
				),
				'refund_order',
				'pmpro_orders_nonce'
			);

			$actions = [
				'id'	 => sprintf(
					// translators: %s is the Order ID.
					__( 'ID: %s', 'paid-memberships-pro' ),
					esc_attr( $item->id )
				),
				'view'   => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr(
						sprintf(
							/* translators: %s is the Order Code. */
							__( 'View order # %s', 'paid-memberships-pro' ),
							$item->code
						)
					),
					esc_url(
						add_query_arg(
							[
								'page'  => 'pmpro-orders',
								'id' => $item->id,
							],
							admin_url( 'admin.php' )
						)
					),
					esc_html__( 'View', 'paid-memberships-pro' )
				),
				'copy'   => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr(
						sprintf(
							/* translators: %s is the Order Code. */
							__( 'Copy order # %s', 'paid-memberships-pro' ),
							$item->code
						)
					),
					esc_url(
						add_query_arg(
							[
								'page'  => 'pmpro-orders',
								'id' => -1,
								'edit'  => 1,
								'copy'  => $item->id,

							],
							admin_url('admin.php' )
						)
					),
					esc_html__( 'Copy', 'paid-memberships-pro' )
				),
				'delete'  => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr(
						sprintf(
							/* translators: %s is the Order Code. */
							__( 'Delete order # %s', 'paid-memberships-pro' ),
							$item->code
						)
					),
					'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( $delete_nonce_url ) . '\'); void(0);',
					esc_html__( 'Delete', 'paid-memberships-pro' )
				),
				'print'   => sprintf(
					'<a title="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
					esc_attr(
						sprintf(
							/* translators: %s is the Order Code. */
							__( 'Print or save order # %s as PDF', 'paid-memberships-pro' ),
							$item->code
						)
					),
					esc_url(
						add_query_arg(
							[
								'action' => 'pmpro_orders_print_view',
								'id'  => $item->id,
							],
							admin_url( 'admin-ajax.php' )
						)
					),
					esc_html__( 'Print', 'paid-memberships-pro' )
				),
				'email'   => sprintf(
					'<a title="%1$s" href="%2$s" data-order="%3$s" class="thickbox email_link">%4$s</a>',
					esc_attr(
						sprintf(
							/* translators: %s is the Order Code. */
							__( 'Send order # %s via email', 'paid-memberships-pro' ),
							$item->code
						)
					),
					'#TB_inline?width=600&height=200&inlineId=email_order',
					esc_attr( $item->id ),
					esc_html__( 'Email', 'paid-memberships-pro' )
				),
			];

			if ( $item->status === 'pending' && $item->payment_type === 'Check' ) {
				$mark_paid_text = esc_html(
					sprintf(
						// translators: %s is the Order Code.
						__( 'Mark the payment for order %s as received. The user and admin may receive an email confirmation after the order update is processed. Are you sure you want to mark this order as paid?', 'paid-memberships-pro' ),
						str_replace( "'", '', $item->code )
					)
				);
				$mark_paid_nonce_url = wp_nonce_url(
					add_query_arg(
						array(
							'page'       => 'pmpro-orders',
							'action'     => 'mark_payment_received',
							'paid_order' => $item->id,
							'order'      => $item->id,
							'id'         => $item->id,
						),
						admin_url( 'admin.php' )
					),
					'mark_payment_received',
					'pmpro_orders_nonce'
				);
				$actions['mark_order_paid'] = sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr(
						sprintf(
							/* translators: %s is the Order Code. */
							__( 'Mark order # %s as paid', 'paid-memberships-pro' ),
							$item->code
						)
					),
					esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $mark_paid_text ) . ', ' . wp_json_encode( $mark_paid_nonce_url ) . '); void(0);' ),
					esc_html__( 'Mark Paid', 'paid-memberships-pro' )
				);
			}

			if ( pmpro_allowed_refunds( $item ) ) {
				$actions['refund'] = sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr( sprintf( __( 'Refund order # %s', 'paid-memberships-pro' ), $item->code ) ),
					esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $refund_text ) . ', ' . wp_json_encode( $refund_nonce_url ) . '); void(0);' ),
					esc_html__( 'Refund', 'paid-memberships-pro' )
				);
			}

			// If the order is in token status and the gateway allows verifying completion, show the action.
			// Checking for the status first to avoid loading the gateway object unnecessarily.
			if ( 'token' === $item->status && pmpro_can_check_token_order_for_completion( $item->id ) ) {
				$actions['check_token_order'] = sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr( sprintf( __( 'Recheck payment status for order # %s', 'paid-memberships-pro' ), $item->code ) ),
					esc_url(
						wp_nonce_url(
							add_query_arg(
								[
									'page'   => 'pmpro-orders',
									'action' => 'check_token_order',
									'token_order' => $item->id,
								],
								admin_url( 'admin.php' )
							),
							'check_token_order',
							'pmpro_orders_nonce'
						)
					),
					esc_html__( 'Recheck', 'paid-memberships-pro' )
				);
			}

			/**
			 * Filter the extra actions for this user on this order.
			 *
			 * @param array       $actions The list of actions.
			 * @param object      $user    The user data.
			 * @param MemberOrder $order   The current order.
			 */
			$actions = apply_filters( 'pmpro_orders_user_row_actions', $actions, $item->user, $item );

			$actions_html = [];

			foreach ( $actions as $action => $link ) {
				$actions_html[] = sprintf(
					'<span class="%1$s">%2$s</span>',
					esc_attr( $action ),
					$link
				);
			}

			if ( ! empty( $actions_html ) ) {
				echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the columns user associated with the order
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_user( $item ) {
		if ( ! empty( $item->user ) ) {
			echo '<a title="' . esc_attr( sprintf( __( 'Edit member %s', 'paid-memberships-pro' ), $item->user->user_login ) ) . '" href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$item->user->ID ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $item->user->user_login ) . '</a><br />';
			echo esc_html( $item->user->user_email );
		 } elseif ( $item->user_id > 0 ) {
			echo '['. esc_html__( 'deleted', 'paid-memberships-pro' ) . ']';
		} else {
			echo '['. esc_html__( 'none', 'paid-memberships-pro' ) . ']';
		}
	}

	/**
	 * Renders the columns level
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_level( $item ) {

		$level = pmpro_getLevel( $item->membership_id );
		if ( ! empty( $level ) ) {
			echo esc_html( $level->name );
		} elseif ( $item->membership_id > 0 ) {
			echo '['. esc_html__( 'deleted', 'paid-memberships-pro' ).']';
		} else {
			esc_html_e( '&#8212;', 'paid-memberships-pro' );
		}
		
	}

	/**
	 * Renders the columns order total
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_total( $item ) {

		echo pmpro_escape_price( $item->get_formatted_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// If there is a discount code, show it.
		if ( $item->getDiscountCode() ) {
			?>
			<a class="pmpro_discount_code-tag" title="<?php esc_attr_e('edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-discountcodes', 'edit' => $item->discount_code->id ), admin_url('admin.php' ) ) ); ?>"><?php echo esc_html( $item->discount_code->code ); ?></a>
			<?php
		}
	}

	/**
	 * Renders the columns order billing information
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_billing( $item ) {

		// Build our return variable.
		$r = '';

		if ( ! empty( $item->payment_type ) ) {
			if ( in_array( $item->payment_type, array( 'PayPal Standard', 'PayPal Express' ) ) ) {
				$r .= esc_html__( 'PayPal', 'paid-memberships-pro' );
			} else {
				$r .= esc_html( ucwords( $item->payment_type ) );
			}
			$r .= '<br />';
		}

		if ( ! empty( $item->accountnumber ) ) {
			$r .= esc_html( $item->cardtype ) . ': x' . esc_html( last4( $item->accountnumber ) ) . '<br />';
		}

		$name = empty( $item->billing->name ) ? '' : $item->billing->name;
		$street = empty( $item->billing->street ) ? '' : $item->billing->street;
		$street2 = empty( $item->billing->street2 ) ? '' : $item->billing->street2;
		$city = empty( $item->billing->city ) ? '' : $item->billing->city;
		$state = empty( $item->billing->state ) ? '' : $item->billing->state;
		$zip = empty( $item->billing->zip ) ? '' : $item->billing->zip;
		$country = empty( $item->billing->country ) ? '' : $item->billing->country;
		$phone = empty( $item->billing->phone ) ? '' : $item->billing->phone;
		$r .= pmpro_formatAddress( $name, $street, $street2, $city, $state, $zip, $country, $phone );

		// If this column is completely empty, set $r to a dash.
		if ( empty( $r ) ) {
			$r .= esc_html__( '&#8212;', 'paid-memberships-pro' );
		}

		// Echo the data for this column.
		echo $r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		
	}

	/**
	 * Renders the columns order gateway value
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_gateway( $item ) {

		global $pmpro_gateways;

		if ( ! empty( $item->gateway ) ) {
			if ( ! empty( $pmpro_gateways[$item->gateway] ) ) {
				echo esc_html( $pmpro_gateways[$item->gateway] );
			} else {
				echo esc_html( ucwords( $item->gateway ) );
			}
			if ( $item->gateway_environment == 'sandbox' ) {
				echo ' (test)';
			}
		} else {
			esc_html_e( '&#8212;', 'paid-memberships-pro' );
		}

	}

	/**
	 * Renders the columns order transaction IDs
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_transaction_ids( $item ) {

		// Build our return variable.
		$column_value = array();

		// If there is a payment transaction ID, add it to the return variable.
		if ( ! empty( $item->payment_transaction_id ) ) {
			$column_value['payment_transaction_id'] = sprintf(
				// translators: %s is the payment transaction ID.
				__( 'Payment: %s', 'paid-memberships-pro' ),
				esc_html( $item->payment_transaction_id )
			);
		}

		// If there is a subscription transaction ID, add it to the return variable.
		if ( ! empty( $item->subscription_transaction_id ) ) {
			$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $item->subscription_transaction_id, $item->gateway, $item->gateway_environment );
			$column_value['subscription_transaction_id'] = sprintf(
				// translators: %s is the subscription transaction ID.
				__( 'Subscription: %s', 'paid-memberships-pro' ),
				! empty( $subscription ) ? '<a title="' . esc_attr( sprintf( __( 'View subscription: %s', 'paid-memberships-pro' ), $item->subscription_transaction_id ) ) . '" href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url('admin.php' ) ) ) . '">' . esc_html( $item->subscription_transaction_id ) . '</a>' : esc_html( $item->subscription_transaction_id )
			);
		}

		// If there is no transaction IDs, set $column_value to a dash.
		if ( empty( $column_value ) ) {
			$column_value['none'] = esc_html__( '&#8212;', 'paid-memberships-pro' );
		}

		// Echo the data for this column.
		foreach( $column_value as $key => $value ) {
			echo '<p>' . wp_kses_post( $value ) . '</p>';
		}
	}

	/**
	 * Renders the columns order status
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_order_status( $item ) {     
		
		?>
		<span class="pmpro_order-status pmpro_order-status-<?php echo esc_attr( $item->status ); ?>">
			<?php if ( in_array( $item->status, array( 'success', 'cancelled' ) ) ) {
				esc_html_e( 'Paid', 'paid-memberships-pro' );
			} else {
				echo esc_html( ucwords( $item->status ) );
			} ?>
		</span>
		<?php if ( $item->is_renewal() ) { ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $item->subscription_transaction_id ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'View all orders for this subscription', 'paid-memberships-pro' ); ?>" class="pmpro_order-renewal"><?php esc_html_e( 'Renewal', 'paid-memberships-pro' ); ?></a>
		<?php }
	}

	/**
	 * Renders the columns order date
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		echo esc_html( sprintf(
			// translators: %1$s is the date and %2$s is the time.
			__( '%1$s at %2$s', 'paid-memberships-pro' ),
			esc_html( date_i18n( get_option( 'date_format' ), $item->getTimestamp() ) ),
			esc_html( date_i18n( get_option( 'time_format' ), $item->getTimestamp() ) )
		) );
	}

}
