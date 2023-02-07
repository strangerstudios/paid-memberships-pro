<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PMPro_Discount_Code_List_Table extends WP_List_Table {
	/**
	 * The text domain of this plugin.
	 *
	 * @since TBD
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
	 * @since TBD
	 */
	public function __construct() {

		$this->plugin_text_domain = 'paid-memberships-pro';

		parent::__construct(
			array(
				'plural'   => 'discount codes',
				// Plural value used for labels and the objects being listed.
				'singular' => 'discount code',
				// Singular label for an object being listed, e.g. 'post'.
				'ajax'     => false,
				// If true, the parent class will call the _js_vars() method in the footer
			)
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
	 *
	 * @since TBD
	 */
	public function prepare_items() {
		
		$columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->items = $this->sql_table_data();

		$items_per_page = $this->get_items_per_page( 'discount_codes_per_page' );
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
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
            'id'        => __( 'ID', 'paid-memberships-pro' ),
			'code'      => __( 'Code', 'paid-memberships-pro' ),
			'starts'    => __( 'Starts', 'paid-memberships-pro' ),
			'expires'   => __( 'Expires', 'paid-memberships-pro' ),
            'uses'      => __( 'Uses', 'paid-memberships-pro' ),
		);
		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		
		return array(
			// 'used'
		);
		
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since TBD
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
			'id' => array( 'id', false ),
			'code' => array( 'code', false ),
			'starts' => array( 'starts', false ),
			'expires' => array( 'expires', false ),
			'uses' => array( 'uses', false ),
		);
		// var_dump($sortable_columns);
		return $sortable_columns;
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function no_items() {
		if ( isset( $_REQUEST['l'] ) ) {
			$l = sanitize_text_field( $_REQUEST['l'] );
		} else {
			$l = false;
		}
		if(isset($_REQUEST['s']))
			$s = trim( sanitize_text_field( $_REQUEST['s'] ) );
		else
			$s = "";
		?>
		<p>
			<?php _e( 'No discount codes found.', 'paid-memberships-pro' ); ?>
			<?php if ( $l ) { ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-discountcodes', 's' => $s ) ) ); ?>"><?php esc_html_e( 'Search all levels', 'paid-memberships-pro' );?></a>
			<?php } ?>
		</p>
		
		<?php
	}

	/**
	 * Get the table data
	 *
	 * @return Array|integer if $count parameter = true
	 */
	private function sql_table_data( $count = false ) {

		global $wpdb;

		if( isset( $_REQUEST['s'] ) ) {
			$s = trim( sanitize_text_field( $_REQUEST['s'] ) );
		} else {
			$s = '';
		}

		// some vars for pagination
		if(isset($_REQUEST['paged']))
			$pn = intval($_REQUEST['paged']);
		else
			$pn = 1;

		$limit = $this->get_items_per_page( 'discount_codes_per_page' );

		$end = $pn * $limit;
		$start = $end - $limit;

		if ( $count ) {
			$sqlQuery = "SELECT COUNT( DISTINCT id ) FROM $wpdb->pmpro_discount_codes ";
		} else {
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(CONVERT_TZ(starts, '+00:00', @@global.time_zone)) as starts, UNIX_TIMESTAMP(CONVERT_TZ(expires, '+00:00', @@global.time_zone)) as expires FROM $wpdb->pmpro_discount_codes ";
		}

		if ( ! empty( $s ) ) {
			$sqlQuery .= "WHERE code LIKE '%" . esc_sql( $s ) . "%' ";
		}

		if ( ! $count ) {

			if( isset( $_REQUEST['orderby'] ) ) {
				$orderby = sanitize_text_field( $_REQUEST['orderby'] );
			} else {
				$orderby = 'id';
			}

			if( isset( $_REQUEST['order'] ) ) {
				$order = strtoupper( sanitize_text_field( $_REQUEST['order'] ) );
			} else {
				$order = 'DESC';
			}

			//Ordering needs to happen here
			$sqlQuery .= "ORDER BY `$orderby` $order ";
			
			$sqlQuery .= "LIMIT " . esc_sql( $start ) . "," .  esc_sql( $limit );
		}

		// $sqlQuery = apply_filters("pmpro_discount_code_list_sql", $sqlQuery);
		if( $count ) {
			$sql_table_data = $wpdb->get_var( $sqlQuery );
		} else {
			$sql_table_data = $wpdb->get_results( $sqlQuery, ARRAY_A );
			
		}

		return $sql_table_data;
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
			'id' 		=> 'id',
			'code' 		=> 'code',
			'starts' 	=> 'starts',
			'finishes' 	=> 'finishes',
			'used' 		=> 'uses', //Order by how many have actually been used
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
		
		return $item[ $column_name ];
		
	}

	public function column_code( $item ) {

		?>
		<strong><a title="<?php echo esc_attr( sprintf( __( 'Edit Code: %s', 'paid-memberships-pro' ), $item['id'] ) ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-discountcodes', 'edit' => $item['id'] ), admin_url('admin.php' ) ) ); ?>"><?php echo $item['code']; ?></a></strong>
		<div class="row-actions">
		<?php
		$delete_text = esc_html(
			sprintf(
				// translators: %s is the Discount Code.
				__( 'Are you sure you want to delete the %s discount code? The subscriptions for existing users will not change, but new users will not be able to use this code anymore.', 'paid-memberships-pro' ),
				$item['code']
			)
		);

		$delete_nonce_url = wp_nonce_url(
			add_query_arg(
				[
					'page'   => 'pmpro-discountcodes',
					'delete' => $item['id'],
				],
				admin_url( 'admin.php' )
			),
			'delete',
			'pmpro_discountcodes_nonce'
		);

		$actions = [
			'id'	 => sprintf(
				// translators: %s is the Order ID.
				__( 'ID: %s', 'paid-memberships-pro' ),
				esc_attr( $item['id'] )
			),
			'edit'   => sprintf(
				'<a title="%1$s" href="%2$s">%3$s</a>',
				esc_attr__( 'Edit', 'paid-memberships-pro' ),
				esc_url(
					add_query_arg(
						[
							'page' => 'pmpro-discountcodes',
							'edit' => $item['id'],
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
							'page' => 'pmpro-discountcodes',
							'edit' => - 1,
							'copy' => $item['id'],
						],
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Copy', 'paid-memberships-pro' )
			),
			'delete' => sprintf(
				'<a title="%1$s" href="%2$s">%3$s</a>',
				esc_attr__( 'Delete', 'paid-memberships-pro' ),
				'javascript:pmpro_askfirst(\'' . esc_js( $delete_text ) . '\', \'' . esc_js( $delete_nonce_url ) . '\'); void(0);',
				esc_html__( 'Delete', 'paid-memberships-pro' )
			),
		];

		if ( 0 < (int) $item['uses'] ) {
			$actions['orders'] = sprintf(
				'<a title="%1$s" href="%2$s">%3$s</a>',
				esc_attr__( 'View Orders', 'paid-memberships-pro' ),
				esc_url(
					add_query_arg(
						[
							'page'          => 'pmpro-orders',
							'discount-code' => $item['id'],
							'filter'        => 'with-discount-code',
						],
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Orders', 'paid-memberships-pro' )
			);
		}

		/**
		 * Filter the extra actions for this discount code.
		 *
		 * @since 2.6.2
		 *
		 * @param array  $actions The list of actions.
		 * @param object $code    The discount code data.
		 */
		$actions = apply_filters( 'pmpro_discountcodes_row_actions', $actions, $item );

		$actions_html = [];

		foreach ( $actions as $action => $link ) {
			$actions_html[] = sprintf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( $action ),
				$link
			);
		}

		if ( ! empty( $actions_html ) ) {
			echo implode( ' | ', $actions_html );
		}
		?>
		</div>
		<?php
	}

	public function column_uses( $item ) {
		
		global $wpdb;
		
		$uses = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = %d", esc_sql( $item['id'] ) ) );

		$max_uses_string = ( $item['uses'] > 0 ) ? $item['uses'] : __( 'unlimited', 'paid-memberships-pro' );

		return "<strong>$uses</strong>/".$max_uses_string;

	}

	public function column_starts( $item ) {

		return date_i18n(get_option('date_format'), $item['starts'] );

	}

	public function column_expires( $item ) {

		return date_i18n(get_option('date_format'), $item['expires'] );
		
	}
	
}
