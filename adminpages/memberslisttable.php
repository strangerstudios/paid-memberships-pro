<?php

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Plugin Name: PMPro Members List Table
 *
 * Class for displaying registered WordPress Members
 * in a WordPress-like Admin Table with row actions to
 * perform user meta opeations
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PMPro_Members_List_Table extends WP_List_Table {
	/**
	 * The text domain of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	protected $plugin_text_domain;
	protected $total_users;

	/**
	 * Call the parent constructor to override the defaults $args
	 *
	 * @param string $plugin_text_domain    Text domain of the plugin.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->plugin_text_domain = 'paid-memberships-pro';
		parent::__construct(
			array(
				'plural'   => __( 'members', $this->plugin_text_domain ),
				'singular' => __( 'member', $this->plugin_text_domain ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
	 *
	 * @since   2.0.0
	 */
	protected function get_views() {
		$existing_levels = $this->get_levels_object();
		$status_links    = '<a href="' . admin_url( '/admin.php?page=pmpro-memberslisttable' ) . '">All</a>';
		foreach ( $existing_levels as $key => $value ) {
			$status_links .= ' | <a href="' . admin_url( '/admin.php?page=pmpro-memberslisttable' ) . '&s=' . $value->name . '">' . $value->name . '</a>';
		}
		return $status_links;
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
	 *
	 * @since   2.0.0
	 */
	public function prepare_items() {
		// check if a search was performed.
		$table_search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// fetch table data
		$table_data = $this->sql_table_data();
		usort( $table_data, array( $this, 'sort_data' ) );

		// filter the data in case of a search.
		if ( $table_search_key ) {
			$table_data = $this->filter_table_data( $table_data, $table_search_key );
		}

		// required for pagination
		$users_per_page = $this->get_items_per_page( 'users_per_page' );
		$table_page     = $this->get_pagenum();

		// provide the ordered data to the List Table.
		// we need to manually slice the data based on the current pagination.
		$this->items = array_slice( $table_data, ( ( $table_page - 1 ) * $users_per_page ), $users_per_page );

		// set the pagination arguments
		$total_users = $this->total_users = count( $table_data );
		$this->set_pagination_args(
			array(
				'total_items' => $total_users,
				'per_page'    => $users_per_page,
				'total_pages' => ceil( $total_users / $users_per_page ),
			)
		);
	}

	/**
	 * Get a list of columns.
	 *
	 * The format is: 'internal-name' => 'Title'
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			// 'cb'            => '<input type="checkbox" />',
			'ID'             => 'ID',
			'user_login'     => 'Username',
			'first_name'     => 'First Name',
			'last_name'      => 'Last Name',
			'display_name'   => 'Display Name',
			'user_email'     => 'Email',
			'address'        => 'Address',
			'membership'     => 'Membership',
			'membership_id'  => 'Level ID',
			'billing_amount' => 'Fee',
			'startdate'      => 'Joined',
			'joindate'       => 'Initial Date',
			'enddate'        => 'Expires',
		);
		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		$hidden = array(
			// 'first_name',
			// 'last_name',
			// 'address',
			'membership_id',
			'display_name',
			'joindate',
		);
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
	 * @since 1.1.0
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
		return array(
			'ID'             => array(
				'ID',
				false,
			),
			'user_login'     => array(
				'user_login',
				false,
			),
			'first_name'     => array(
				'first_name',
				false,
			),
			'last_name'      => array(
				'last_name',
				false,
			),
			'billing_amount' => array(
				'billing_amount',
				false,
			),
			'display_name'   => array(
				'display_name',
				false,
			),
			'user_email'     => array(
				'user_email',
				false,
			),
			'membership'     => array(
				'membership',
				false,
			),
			'membership_id'  => array(
				'membership_id',
				false,
			),
			'startdate'      => array(
				'startdate',
				false,
			),
			'enddate'        => array(
				'enddate',
				false,
			),
			'joindate'       => array(
				'joindate',
				false,
			),
		);
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby = 'startdate';
		$order   = 'asc';

		// If orderby is set, use this as the sort column
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = $_GET['orderby'];
		}

		// If order is set use this as the order
		if ( ! empty( $_GET['order'] ) ) {
			$order = $_GET['order'];
		}

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		if ( $order === 'asc' ) {
			return $result;
		}

		return -$result;
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since   2.0.0
	 *
	 * @return void
	 */
	public function no_items() {
		_e( 'No members match this criteria.', $this->plugin_text_domain );
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function sql_table_data() {
		global $wpdb;
		$sql_table_data = array();
		$mysqli_query   =
			"
			SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, u.user_nicename, u.display_name, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership 
			FROM $wpdb->users u 
			LEFT JOIN $wpdb->usermeta umh 
			ON umh.meta_key = 'pmpromd_hide_directory' 
			AND u.ID = umh.user_id 
			LEFT JOIN $wpdb->pmpro_memberships_users mu 
			ON u.ID = mu.user_id 
			LEFT JOIN $wpdb->pmpro_membership_levels m 
			ON mu.membership_id = m.id
			WHERE mu.status = 'active' 
			AND (umh.meta_value IS NULL 
			OR umh.meta_value <> '1') 
			AND mu.membership_id > 0 ";

		$sql_table_data = $wpdb->get_results( $mysqli_query, ARRAY_A );
		return $sql_table_data;
	}

	/**
	 * Filter the table data based on the user search key
	 *
	 * @since 2.0.0
	 *
	 * @param array  $table_data
	 * @param string $search_key
	 * @returns array
	 */
	public function filter_table_data( $table_data, $search_key ) {
		$filtered_table_data = array_values(
			array_filter(
				$table_data,
				function( $row ) use ( $search_key ) {
					foreach ( $row as $row_val ) {
						if ( stripos( $row_val, $search_key ) !== false ) {
							return true;
						}
					}
				}
			)
		);
		return $filtered_table_data;
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
		switch ( $column_name ) {
			case 'ID':
			case 'user_login':
			case 'display_name':
			case 'billing_amount':
			case 'user_email':
			case 'membership':
			case 'membership_id':
			case 'cycle_period':
			case 'cycle_number':
				return $item[ $column_name ];
			case 'startdate':
				$startdate = $item[ $column_name ];
				return date( 'Y-m-d', $startdate );
			case 'enddate':
				if ( 0 == $item[ $column_name ] ) {
					return 'Never';
				} else {
					return date( 'Y-m-d', $item[ $column_name ] );
				}
			case 'joindate':
				if ( $item['startdate'] == $item['joindate'] ) {
					return 'Join = Start';
				} else {
					return date( 'Y-m-d', $item[ $column_name ] );
				}

			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Get value for checkbox column.
	 *
	 * The special 'cb' column
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<label class="screen-reader-text" for="user_' . $item['ID'] . '">' . sprintf( __( 'Select %s' ), $item['user_login'] ) . '</label>'
			. "<input type='checkbox' name='users[]' id='user_{$item['ID']}' value='{$item['ID']}' />"
		);
	}

	/**
	 * Get value for first name column.
	 *
	 * The special 'first_name' column
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_first_name( $item ) {
		$user_object = get_userdata( $item['ID'] );
		return ( $user_object->first_name ?: '---' );
	}

	/**
	 * Get value for last name column.
	 *
	 * The special 'last_name' column
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_last_name( $item ) {
		$user_object = get_userdata( $item['ID'] );
		return ( $user_object->last_name ?: '---' );
	}

	/**
	 * Get value for Address column.
	 *
	 * The special 'address' column
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_address( $item ) {
		$user_object = get_userdata( $item['ID'] );
		return pmpro_formatAddress( trim( $user_object->pmpro_bfirstname . ' ' . $user_object->pmpro_blastname ), $user_object->pmpro_baddress1, $user_object->pmpro_baddress2, $user_object->pmpro_bcity, $user_object->pmpro_bstate, $user_object->pmpro_bzipcode, $user_object->pmpro_bcountry, $user_object->pmpro_bphone );
	}

	public function get_levels_object() {
		global $wpdb;
		$existing_levels = $wpdb->get_results(
			"
			SELECT id, name 
			FROM $wpdb->pmpro_membership_levels 
			ORDER BY id
			"
		);
		$count           = count( $existing_levels );
		if ( has_filter( 'add_to_levels_array' ) ) {
			$added_levels = apply_filters( 'add_to_levels_array', '' );
			foreach ( $added_levels as $key => $value ) {
				$existing_levels[] = (object) [
					'id'   => $count + 1,
					'name' => $value,
				];
				$count ++;
			}
		}
		return $existing_levels;
	}

	public function get_levels_dropdown() {
		$existing_levels = $this->get_levels_object();
		?>
		<select name="requested-level" id="filter-memberslisttable">
			<option value=" ">Select a Level</option>
			<?php
			foreach ( $existing_levels as $key => $value ) {
				?>
				<option value="<?php esc_attr_e( $value->name ); ?>" <?php selected( $value->name ); ?>><?php esc_attr_e( $value->name ); ?></option>
		<?php } ?>
			<p class="search-filtered" style="float:left;">
				<input type="hidden" name="page" value="pmpro-memberslisttable">
				<a id="redraw-table" class="button-primary" href=" ">Filter Levels</a>
			</p>
		</select>
		<?php
	}

	public function get_levels_ajax_dropdown( $selector ) {
		$existing_levels = $this->get_levels_object();
		if ( 1 < count( $existing_levels ) ) {
			$pmpro_levels_dropdown  = '<select name="' . $selector . '" id="' . $selector . '">';
			$pmpro_levels_dropdown .= '<option value="">Select a Level</option>';
			foreach ( $existing_levels as $key => $value ) {
				$pmpro_levels_dropdown .= '<option value="' . $value->name . '">' . $value->id . ' => ' . $value->name . '</option>';
			}
			$pmpro_levels_dropdown .= '</select>';
		}
		return $pmpro_levels_dropdown;
	}

	/**
	 * Add extra markup in the toolbars before or after the list
	 *
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	function extra_tablenav( $which ) {
		if ( $which == 'top' ) {
			echo $this->get_levels_dropdown();
			echo '<br><b> ' . $this->total_users . ' members queried</b>';
		}
		if ( $which == 'bottom' ) {
			echo '<b> ' . $this->total_users . ' members queried</b>';
		}
	}

	/**
	 * Stop execution and exit
	 *
	 * @since    2.0.0
	 *
	 * @return void
	 */
	public function graceful_exit() {
		exit;

	}

	/**
	 * Die when the nonce check fails.
	 *
	 * @since    2.0.0
	 *
	 * @return void
	 */
	public function invalid_nonce_redirect() {
		wp_die(
			__( 'Invalid Nonce', $this->plugin_text_domain ),
			__( 'Error', $this->plugin_text_domain ),
			array(
				'response'  => 403,
				'back_link' => esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ), admin_url( 'admin.php?page=pmpro-memberslisttable' ) ) ),
			)
		);
	}
}

