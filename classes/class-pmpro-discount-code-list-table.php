<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PMPro_Discount_Code_List_Table extends WP_List_Table {
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
	 * @since 2.11
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
	 * @since 2.11
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
            'id'        => __( 'ID', 'paid-memberships-pro' ),
			//We cant use 'code' as is because it formats the column as code
			'discount_code' => __( 'Code', 'paid-memberships-pro' ),
			'starts'    => __( 'Starts', 'paid-memberships-pro' ),
			'expires'   => __( 'Expires', 'paid-memberships-pro' ),
            'uses'      => __( 'Uses', 'paid-memberships-pro' ),
			'levels'      => __( 'Levels', 'paid-memberships-pro' ),
		);

		// Re-implementing old hook, will be deprecated.
		ob_start();
		do_action( 'pmpro_discountcodes_extra_cols_header' );
		$extra_cols = ob_get_clean();
		preg_match_all( '/<th>(.*?)<\/th>/s', $extra_cols, $matches );
		$custom_field_num = 0;
		foreach ( $matches[1] as $match ) {
			$columns[ 'custom_field_' . $custom_field_num ] = $match;
			$custom_field_num++;
		}

		// Shortcut for editing columns in default discount code list location.
		$current_screen = get_current_screen();
		if ( ! empty( $current_screen ) && strpos( $current_screen->id, "pmpro-discountcodes" ) !== false ) {
			$columns = apply_filters( 'pmpro_manage_discountcodes_columns', $columns );
		}


		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		
		return array();
		
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
			'id' => array( 'id', true ),
			'discount_code' => array( 'code', false ),
			'starts' => array( 'starts', false ),
			'expires' => array( 'expires', false ),
			'uses' => array( 'used', false ),
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
			
		esc_html_e( 'No discount codes found.', 'paid-memberships-pro' );

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
		if( isset( $_REQUEST['paged'] ) ) {
			$pn = intval( $_REQUEST['paged'] );
		} else {
			$pn = 1;
		}
		
		$limit = $this->get_items_per_page( 'discount_codes_per_page' );

		$end = $pn * $limit;
		$start = $end - $limit;

		if ( $count ) {
			$sqlQuery = "SELECT COUNT( DISTINCT id ) FROM $wpdb->pmpro_discount_codes ";
		} else {
			//Includes uses for each discount code as 'used' so we can sort by it later
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(CONVERT_TZ(starts, '+00:00', @@global.time_zone)) as starts, UNIX_TIMESTAMP(CONVERT_TZ(expires, '+00:00', @@global.time_zone)) as expires, (SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = do.id) as used FROM $wpdb->pmpro_discount_codes as do ";			
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

		if( $count ) {
			$sql_table_data = $wpdb->get_var( $sqlQuery );
		} else {
			$sql_table_data = $wpdb->get_results( $sqlQuery );
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
			'discount_code'	=> 'code',
			'starts' 	=> 'starts',
			'finishes' 	=> 'finishes',
			'uses' 		=> 'uses',
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
		
		$item = (array) apply_filters( 'pmpro_discount_code_list_item', (object) $item );

		if ( isset( $item[ $column_name ] ) ) {
			// If the user is adding content via the "pmpro_members_list_user" filter.
			echo( esc_html( $item[ $column_name ] ) );
		} elseif ( 0 === strpos( $column_name, 'custom_field_' ) ) {
			
			// If the user is adding content via the "pmpro_discountcodes_extra_cols_body" hook.
			// Re-implementing old hook, will be deprecated.			
			ob_start();
			do_action( 'pmpro_discountcodes_extra_cols_body', (object) $item );
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
			do_action( 'pmpro_manage_discount_code_list_custom_column', $column_name, $item['id'] );
		}
		
	}

	/**
	 * Render the columns discount code value
	 *
	 * @param array  $item
	 *
	 * @return mixed
	 */
	public function column_discount_code( $item ) {

		?>
		<strong><a title="<?php echo esc_attr( sprintf( __( 'Edit Code: %s', 'paid-memberships-pro' ), $item->id ) ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-discountcodes', 'edit' => $item->id ), admin_url('admin.php' ) ) ); ?>"><?php echo $item->code; ?></a></strong>
		<div class="row-actions">
		<?php
			$delete_text = esc_html(
				sprintf(
					// translators: %s is the Discount Code.
					__( 'Are you sure you want to delete the %s discount code? The subscriptions for existing users will not change, but new users will not be able to use this code anymore.', 'paid-memberships-pro' ),
					$item->code
				)
			);

			$delete_nonce_url = wp_nonce_url(
				add_query_arg(
					[
						'page'    => 'pmpro-discountcodes',
						'delete'  => $item->id,
						's' 	  => isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
						'orderby' => isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : null,
						'order'   => isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : null,
					],
					admin_url( 'admin.php' )
				),
				'delete',
				'pmpro_discountcodes_nonce'
			);

			$actions = [
				'edit'   => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'Edit', 'paid-memberships-pro' ),
					esc_url(
						add_query_arg(
							[
								'page' => 'pmpro-discountcodes',
								'edit' => $item->id,
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
								'copy' => $item->id,
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

			if ( 0 < (int) $item->used ) {
				$actions['orders'] = sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'View Orders', 'paid-memberships-pro' ),
					esc_url(
						add_query_arg(
							[
								'page'          => 'pmpro-orders',
								'discount-code' => $item->id,
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
			 * @since 2.11
			 *
			 * @param array  $actions The list of actions.
			 * @param object $item    The discount code data.
			 */
			$actions = apply_filters( 'pmpro_discountcodes_row_actions', $actions, $item );

			$actions_html = [];

			foreach ( $actions as $action => $link ) {
				$actions_html[] = sprintf(
					'<span class="%1$s">%2$s</span>',
					esc_attr( $action ),
					$link // Escaped above.
				);
			}

			if ( ! empty( $actions_html ) ) {
				echo implode( ' | ', $actions_html );
			}
		?>
		</div>
		<?php
	}

	/**
	 * Render the discount uses value
	 *
	 * @param array  $item
	 *
	 * @return mixed
	 */
	public function column_uses( $item ) {
		
		global $wpdb;
		
		if( $item->uses > 0 ) {
			echo "<strong>" . (int)$item->used . "</strong>/" . (int)$item->uses;
		} else {
			echo "<strong>" . (int)$item->used . "</strong>/" . esc_html__( 'unlimited', 'paid-memberships-pro' );
		}

	}

	/**
	 * Render the discount codes start value
	 *
	 * @param array  $item
	 *
	 * @return mixed
	 */
	public function column_starts( $item ) {

		return date_i18n( get_option( 'date_format' ), $item->starts );

	}

	/**
	 * Render the discount codes expiration value
	 *
	 * @param array  $item
	 *
	 * @return mixed
	 */
	public function column_expires( $item ) {

		return date_i18n( get_option( 'date_format' ), $item->expires );
		
	}

	/**
	 * Render the level that the discount code applies to
	 *
	 * @param array  $item
	 *
	 * @return mixed
	 */
	public function column_levels( $item ) {

		global $wpdb, $pmpro_pages;

		$sqlQuery = $wpdb->prepare("
			SELECT l.id, l.name
			FROM $wpdb->pmpro_membership_levels l
			LEFT JOIN $wpdb->pmpro_discount_codes_levels cl
			ON l.id = cl.level_id
			WHERE cl.code_id = %d",
			esc_sql( $item->id )
		);
		$levels = $wpdb->get_results($sqlQuery);

		$level_names = array();
		foreach( $levels as $level ) {
			if ( ! empty( $pmpro_pages['checkout'] ) ) {
				$level_names[] = '<a target="_blank" href="' . esc_url( pmpro_url( 'checkout', '?level=' . $level->id . '&discount_code=' . $item->code ) ) . '">' . esc_html( $level->name ) . '</a>';
			} else {
				$level_names[] = $level->name;
			}
		}
		if( $level_names ) {
			// Echo imploded level names and escape allowing links.
			echo wp_kses( implode( ', ', $level_names ), array( 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) );
		} else {
			esc_html_e( 'None', 'paid-memberships-pro' );
		}
	}
	
}
