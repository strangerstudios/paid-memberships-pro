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
		// Right now, we don't have any default hidden columns.
		if ( ! $hidden ) {
			$hidden = array();
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
		$l = isset( $_REQUEST['l'] ) ? intval( $_REQUEST['l'] ) : false;
		$discount_code = isset( $_REQUEST['discount-code'] ) ? intval( $_REQUEST['discount-code'] ) : false;
		$start_month = isset( $_REQUEST['start-month'] ) ? intval( $_REQUEST['start-month'] ) : '1';
		$start_day = isset( $_REQUEST['start-day'] ) ? intval( $_REQUEST['start-day'] ) : '1';
		$start_year = isset( $_REQUEST['start-year'] ) ? intval( $_REQUEST['start-year'] ) : date( 'Y', $now );
		$end_month = isset( $_REQUEST['end-month'] ) ? intval( $_REQUEST['end-month'] ) : date( 'n', $now );
		$end_day = isset( $_REQUEST['end-day'] ) ? intval( $_REQUEST['end-day'] ) : date( 'j', $now );
		$end_year = isset( $_REQUEST['end-year'] ) ? intval( $_REQUEST['end-year'] ) : date( 'Y', $now );
		$predefined_date = isset( $_REQUEST['predefined-date'] ) ? sanitize_text_field( $_REQUEST['predefined-date'] ) : 'This Month';
		$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
		$filter = isset( $_REQUEST['filter'] ) ? sanitize_text_field( $_REQUEST['filter'] ) : 'all';
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
		
		// filters
		if ( empty( $filter ) || $filter === 'all' ) {
			$condition = '1=1';
			$filter    = 'all';
		} elseif ( $filter == 'within-a-date-range' ) {
			$start_date = $start_year . '-' . $start_month . '-' . $start_day;
			$end_date   = $end_year . '-' . $end_month . '-' . $end_day;
		
			// add times to dates and localize
			$start_date = get_gmt_from_date( $start_date . ' 00:00:00' );
			$end_date   = get_gmt_from_date( $end_date . ' 23:59:59' );
		
			$condition = "o.timestamp BETWEEN '" . esc_sql( $start_date ) . "' AND '" . esc_sql( $end_date ) . "'";
		} elseif ( $filter == 'predefined-date-range' ) {
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
		
			// add times to dates and localize
			$start_date = get_gmt_from_date( $start_date . ' 00:00:00' );
			$end_date   = get_gmt_from_date( $end_date . ' 23:59:59' );
		
			$condition = "o.timestamp BETWEEN '" . esc_sql( $start_date ) . "' AND '" . esc_sql( $end_date ) . "'";
		} elseif ( $filter == 'within-a-level' ) {
			$condition = 'o.membership_id = ' . esc_sql( $l );
		} elseif ( $filter == 'with-discount-code' ) {
			$condition = 'dc.code_id = ' . esc_sql( $discount_code );
		} elseif ( $filter == 'within-a-status' ) {
			$condition = "o.status = '" . esc_sql( $status ) . "' ";
		} elseif ( $filter == 'only-paid' ) {
			$condition = "o.total > 0";
		} elseif( $filter == 'only-free' ) {
			$condition = "o.total = 0";
		} else {
			$condition = "";
		}
		
		$condition = apply_filters( 'pmpro_admin_orders_query_condition', $condition, $filter );

		$orderby = '';

		if( ! empty( $_REQUEST['order'] ) && ! empty( $_REQUEST['orderby'] ) && ! $count ) {

			$order = strtoupper( esc_sql( $_REQUEST['order'] ) );
			$orderby = ( $_REQUEST['orderby'] );

			if( $orderby == 'total' ) {
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
			$calculation_function = 'COUNT(*), ';
		} else {
			$calculation_function = 'SQL_CALC_FOUND_ROWS';
		}

		$sqlQuery = "SELECT $calculation_function o.id, CASE WHEN o.status = 'success' THEN 'Paid' WHEN o.status = 'cancelled' THEN '$paid_string' WHEN o.status = 'refunded' THEN '$refunded_string' WHEN o.status = 'token' THEN '$token_string' WHEN o.status = 'review' THEN '$review_string' WHEN o.status = 'pending' THEN '$pending_string' WHEN o.status = 'error' THEN '$error_string' ELSE '$cancelled_string' END as `status_label` FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_membership_levels ml ON o.membership_id = ml.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID ";

		if ( $s ) {

			$join_with_usermeta = apply_filters( 'pmpro_orders_search_usermeta', false );
			
			if ( $join_with_usermeta ) {
				$sqlQuery .= "LEFT JOIN $wpdb->usermeta um ON o.user_id = um.user_id ";
			}

			if ( $filter === 'with-discount-code' ) {
				$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
			}

			$sqlQuery .= 'WHERE (1=2 ';

			$fields = array(
				'o.id',
				'o.code',
				'o.billing_name',
				'o.billing_street',
				'o.billing_city',
				'o.billing_state',
				'o.billing_zip',
				'o.billing_phone',
				'o.payment_type',
				'o.cardtype',
				'o.accountnumber',
				'o.status',
				'o.gateway',
				'o.gateway_environment',
				'o.payment_transaction_id',
				'o.subscription_transaction_id',
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

			//Not escaping here because we escape the values in the condition statement
			$sqlQuery .= 'AND ' . $condition . ' ';

			if( ! $count ) {
				$sqlQuery .= 'GROUP BY o.id ORDER BY o.id DESC, o.timestamp DESC ';
			}
			
		} else {

			if ( $filter === 'with-discount-code' ) {
				$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
			}
			//Not escaping here because we escape the values in the condition statement
			$sqlQuery .= "WHERE " . $condition . ' ' . $order_query . ' ';

		}

		if( $count ) {
			return $wpdb->get_var( $sqlQuery );    
		} else {
			$sqlQuery .= "LIMIT " . esc_sql( $start ) . "," . esc_sql( $limit );
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
	 * Add extra markup in the toolbars before or after the list
	 *
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list array( '' => 'Select a Level' )
	 */
	function extra_tablenav( $which ) {

		if ( $which == 'top' ) {

			global $wpdb;

			$now = current_time( 'timestamp' );

			if ( isset( $_REQUEST['l'] ) ) {
				$l = intval( $_REQUEST['l'] );
			} else {
				$l = false;
			}         

			if ( isset( $_REQUEST['discount-code'] ) ) {
				$discount_code = intval( $_REQUEST['discount-code'] );
			} else {
				$discount_code = false;
			}

			if ( isset( $_REQUEST['start-month'] ) ) {
				$start_month = intval( $_REQUEST['start-month'] );
			} else {
				$start_month = '1';
			}

			if ( isset( $_REQUEST['start-day'] ) ) {
				$start_day = intval( $_REQUEST['start-day'] );
			} else {
				$start_day = '1';
			}

			if ( isset( $_REQUEST['start-year'] ) ) {
				$start_year = intval( $_REQUEST['start-year'] );
			} else {
				$start_year = date( 'Y', $now );
			}

			if ( isset( $_REQUEST['end-month'] ) ) {
				$end_month = intval( $_REQUEST['end-month'] );
			} else {
				$end_month = date( 'n', $now );
			}

			if ( isset( $_REQUEST['end-day'] ) ) {
				$end_day = intval( $_REQUEST['end-day'] );
			} else {
				$end_day = date( 'j', $now );
			}

			if ( isset( $_REQUEST['end-year'] ) ) {
				$end_year = intval( $_REQUEST['end-year'] );
			} else {
				$end_year = date( 'Y', $now );
			}

			if ( isset( $_REQUEST['predefined-date'] ) ) {
				$predefined_date = sanitize_text_field( $_REQUEST['predefined-date'] );
			} else {
				$predefined_date = 'This Month';
			}

			if ( isset( $_REQUEST['status'] ) ) {
				$status = sanitize_text_field( $_REQUEST['status'] );
			} else {
				$status = '';
			}

			if ( isset( $_REQUEST['filter'] ) ) {
				$filter = sanitize_text_field( $_REQUEST['filter'] );
			} else {
				$filter = 'all';
			}

			// filters
			if ( empty( $filter ) || $filter === 'all' ) {
				$filter    = 'all';
			}
		
			// The code that goes before the table is here
			if ( ! empty( $pmpro_msg ) ) { ?>
				<div id="message" class="
				<?php
				if ( $pmpro_msgt == 'success' ) {
					echo 'updated fade';
				} else {
					echo 'error';
				}
				?>
				"><p><?php echo esc_html( $pmpro_msg ); ?></p></div>
			<?php } ?>

			<div class="tablenav top">
				<?php esc_html_e( 'Show', 'paid-memberships-pro' ); ?>
				<select id="filter" name="filter">
					<option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All', 'paid-memberships-pro' ); ?></option>
					<option
						value="within-a-date-range" <?php selected( $filter, 'within-a-date-range' ); ?>><?php esc_html_e( 'Within a Date Range', 'paid-memberships-pro' ); ?></option>
					<option
						value="predefined-date-range" <?php selected( $filter, 'predefined-date-range' ); ?>><?php esc_html_e( 'Predefined Date Range', 'paid-memberships-pro' ); ?></option>
					<option
						value="within-a-level" <?php selected( $filter, 'within-a-level' ); ?>><?php esc_html_e( 'Within a Level', 'paid-memberships-pro' ); ?></option>
					<option
						value="with-discount-code" <?php selected( $filter, 'with-discount-code' ); ?>><?php esc_html_e( 'With a Discount Code', 'paid-memberships-pro' ); ?></option>
					<option
						value="within-a-status" <?php selected( $filter, 'within-a-status' ); ?>><?php esc_html_e( 'Within a Status', 'paid-memberships-pro' ); ?></option>
					<option
						value="only-paid" <?php selected( $filter, 'only-paid' ); ?>><?php esc_html_e( 'Only Paid Orders', 'paid-memberships-pro' ); ?></option>
					<option
						value="only-free" <?php selected( $filter, 'only-free' ); ?>><?php esc_html_e( 'Only Free Orders', 'paid-memberships-pro' ); ?></option>

					<?php $custom_filters = apply_filters( 'pmpro_admin_orders_filters', array() ); ?>
					<?php foreach( $custom_filters as $value => $name ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter, $value ); ?>><?php echo esc_html( $name ); ?></option>
					<?php } ?>
				</select>

				<span id="from"><?php esc_html_e( 'From', 'paid-memberships-pro' ); ?></span>

				<select id="start-month" name="start-month">
					<?php for ( $i = 1; $i < 13; $i ++ ) { ?>
						<option
							value="<?php echo esc_attr( $i ); ?>" <?php selected( $start_month, $i ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ) ); ?></option>
					<?php } ?>
				</select>

				<input id='start-day' name="start-day" type="text" size="2"
						value="<?php echo esc_attr( $start_day ); ?>"/>
				<input id='start-year' name="start-year" type="text" size="4"
						value="<?php echo esc_attr( $start_year ); ?>"/>


				<span id="to"><?php esc_html_e( 'To', 'paid-memberships-pro' ); ?></span>

				<select id="end-month" name="end-month">
					<?php for ( $i = 1; $i < 13; $i ++ ) { ?>
						<option
							value="<?php echo esc_attr( $i ); ?>" <?php selected( $end_month, $i ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) ) ); ?></option>
					<?php } ?>
				</select>


				<input id='end-day' name="end-day" type="text" size="2" value="<?php echo esc_attr( $end_day ); ?>"/>
				<input id='end-year' name="end-year" type="text" size="4" value="<?php echo esc_attr( $end_year ); ?>"/>

				<span id="filterby"><?php esc_html_e( 'filter by ', 'paid-memberships-pro' ); ?></span>

				<select id="predefined-date" name="predefined-date">

					<option
						value="<?php echo 'This Month'; ?>" <?php selected( $predefined_date, 'This Month' ); ?>><?php esc_html_e( 'This Month', 'paid-memberships-pro' ); ?></option>
					<option
						value="<?php echo 'Last Month'; ?>" <?php selected( $predefined_date, 'Last Month' ); ?>><?php esc_html_e( 'Last Month', 'paid-memberships-pro' ); ?></option>
					<option
						value="<?php echo 'This Year'; ?>" <?php selected( $predefined_date, 'This Year' ); ?>><?php esc_html_e( 'This Year', 'paid-memberships-pro' ); ?></option>
					<option
						value="<?php echo 'Last Year'; ?>" <?php selected( $predefined_date, 'Last Year' ); ?>><?php esc_html_e( 'Last Year', 'paid-memberships-pro' ); ?></option>

				</select>

				<?php
				// Note: only orders belonging to current levels can be filtered. There is no option for orders belonging to deleted levels
				$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );
				?>
				<select id="l" name="l">
					<?php foreach ( $levels as $level ) { ?>
						<option
							value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $l, $level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
					<?php } ?>

				</select>

				<?php
				$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->pmpro_discount_codes ";
				$sqlQuery .= "ORDER BY id DESC ";
				$codes = $wpdb->get_results($sqlQuery, OBJECT);
				if ( ! empty( $codes ) ) { ?>
				<select id="discount-code" name="discount-code">
					<?php foreach ( $codes as $code ) { ?>
						<option
							value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
					<?php } ?>
				</select>
				<?php } ?>

				<?php
					$statuses = pmpro_getOrderStatuses();
				?>
				<select id="status" name="status">
					<?php foreach ( $statuses as $the_status ) { ?>
						<option
							value="<?php echo esc_attr( $the_status ); ?>" <?php selected( $the_status, $status ); ?>><?php echo esc_html( $the_status ); ?></option>
					<?php } ?>
				</select>
				<input type="hidden" name="page" value="pmpro-orders"/>
				<input id="submit" class="button" type="submit" value="<?php esc_attr_e( 'Filter', 'paid-memberships-pro' ); ?>"/>

			<script>
				//update month/year when period dropdown is changed
				jQuery(document).ready(function () {
					jQuery('#filter').on('change',function () {
						pmpro_ShowMonthOrYear();
					});
				});

				function pmpro_ShowMonthOrYear() {
					var filter = jQuery('#filter').val();
					if (filter == 'all') {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').hide();
						jQuery('#status').hide();
						jQuery('#l').hide();
						jQuery('#discount-code').hide();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#submit').show();
						jQuery('#filterby').hide();
					}
					else if (filter == 'within-a-date-range') {
						jQuery('#start-month').show();
						jQuery('#start-day').show();
						jQuery('#start-year').show();
						jQuery('#end-month').show();
						jQuery('#end-day').show();
						jQuery('#end-year').show();
						jQuery('#predefined-date').hide();
						jQuery('#status').hide();
						jQuery('#l').hide();
						jQuery('#discount-code').hide();
						jQuery('#submit').show();
						jQuery('#from').show();
						jQuery('#to').show();
						jQuery('#filterby').hide();
					}
					else if (filter == 'predefined-date-range') {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').show();
						jQuery('#status').hide();
						jQuery('#l').hide();
						jQuery('#discount-code').hide();
						jQuery('#submit').show();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#filterby').show();
					}
					else if (filter == 'within-a-level') {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').hide();
						jQuery('#status').hide();
						jQuery('#l').show();
						jQuery('#discount-code').hide();
						jQuery('#submit').show();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#filterby').show();
					}
					else if (filter == 'with-discount-code') {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').hide();
						jQuery('#status').hide();
						jQuery('#l').hide();
						jQuery('#discount-code').show();
						jQuery('#submit').show();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#filterby').show();
					}
					else if (filter == 'within-a-status') {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').hide();
						jQuery('#status').show();
						jQuery('#l').hide();
						jQuery('#discount-code').hide();
						jQuery('#submit').show();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#filterby').show();
					}
					else if(filter == 'only-paid' || filter == 'only-free' ) {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').hide();
						jQuery('#status').hide();
						jQuery('#l').hide();
						jQuery('#discount-code').hide();
						jQuery('#submit').show();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#filterby').hide();
					}  else {
						jQuery('#start-month').hide();
						jQuery('#start-day').hide();
						jQuery('#start-year').hide();
						jQuery('#end-month').hide();
						jQuery('#end-day').hide();
						jQuery('#end-year').hide();
						jQuery('#predefined-date').hide();
						jQuery('#status').hide();
						jQuery('#l').hide();
						jQuery('#discount-code').hide();
						jQuery('#submit').show();
						jQuery('#from').hide();
						jQuery('#to').hide();
						jQuery('#filterby').hide();
					}
				}

				pmpro_ShowMonthOrYear();


			</script>
			<?php
		}

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
			'order_id' 		=> 'id',
			'level'	        => 'name',
			'total' 	    => 'total',
			'status' 	    => 'status_label',
			'date' 		    => 'timestamp',
		);

	 	if ( ! empty( $allowed_orderbys[$orderby] ) ) {
			$orderby = $allowed_orderbys[$orderby];
		} else {
			$orderby = false;
		}

		return $allowed_orderbys;
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
		<strong><a href="admin.php?page=pmpro-orders&order=<?php echo esc_attr( $item->id ); ?>"><?php echo esc_html( $item->code ); ?></a></strong>
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
						'order'  => isset( $_REQUEST['order'] ) ? intval( $_REQUEST['order'] ) : null,
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
				'edit'   => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'Edit', 'paid-memberships-pro' ),
					esc_url(
						add_query_arg(
							[
								'page'  => 'pmpro-orders',
								'order' => $item->id,
							],
							admin_url( 'admin.php' )
						)
					),
					esc_html__( 'Edit', 'paid-memberships-pro' )
				),
				'copy'   => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'Copy', 'paid-memberships-pro' ),
					esc_url(
						add_query_arg(
							[
								'page'  => 'pmpro-orders',
								'order' => - 1,
								'copy'  => $item->id,
							],
							admin_url( 'admin.php' )
						)
					),
					esc_html__( 'Copy', 'paid-memberships-pro' )
				),
				'delete'  => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'Delete', 'paid-memberships-pro' ),
					'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( $delete_nonce_url ) . '\'); void(0);',
					esc_html__( 'Delete', 'paid-memberships-pro' )
				),
				'print'   => sprintf(
					'<a title="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
					esc_attr__( 'Print', 'paid-memberships-pro' ),
					esc_url(
						add_query_arg(
							[
								'action' => 'pmpro_orders_print_view',
								'order'  => $item->id,
							],
							admin_url( 'admin-ajax.php' )
						)
					),
					esc_html__( 'Print', 'paid-memberships-pro' )
				),
				'email'   => sprintf(
					'<a title="%1$s" href="%2$s" data-order="%3$s" class="thickbox email_link">%4$s</a>',
					esc_attr__( 'Email', 'paid-memberships-pro' ),
					'#TB_inline?width=600&height=200&inlineId=email_invoice',
					esc_attr( $item->id ),
					esc_html__( 'Email', 'paid-memberships-pro' )
				),
			];

			if( pmpro_allowed_refunds( $item ) ) {
				$actions['refund'] = sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'Refund', 'paid-memberships-pro' ),
					esc_js( 'javascript:pmpro_askfirst(' . wp_json_encode( $refund_text ) . ', ' . wp_json_encode( $refund_nonce_url ) . '); void(0);' ),
					esc_html__( 'Refund', 'paid-memberships-pro' )
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
			echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$item->user->ID ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $item->user->user_login ) . '</a><br />';
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
			echo '['. esc_html( 'deleted', 'paid-memberships-pro' ).']';
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

		echo pmpro_escape_price( pmpro_formatPrice( $item->total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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

		if ( ! empty( $item->billing->name ) ) {
			$r .= esc_html( $item->billing->name ) . '<br />';
		}

		if ( ! empty( $item->billing->street ) ) {
			$r .= esc_html( $item->billing->street ) . '<br />';
		}

		if ( $item->billing->city && $item->billing->state ) {
			$r .= esc_html( $item->billing->city ) . ', ';
			$r .= esc_html( $item->billing->state ) . ' ';
			$r .= esc_html( $item->billing->zip ) . ' ';
			if ( ! empty( $item->billing->country ) ) {
				$r .= esc_html( $item->billing->country );
			}
		}

		if ( ! empty( $item->billing->phone ) ) {
			$r .= '<br />' . esc_html( formatPhone( $item->billing->phone ) );
		}

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
				! empty( $subscription ) ? '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url('admin.php' ) ) ) . '">' . esc_html( $item->subscription_transaction_id ) . '</a>' : esc_html( $item->subscription_transaction_id )
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
