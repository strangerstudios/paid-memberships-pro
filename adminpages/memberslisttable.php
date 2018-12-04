<?php

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );
/**
 * Plugin Name: PMPro Members List Table
 *
 * Class for displaying registered WordPress Members
 * in a WordPress-like Admin Table with row actions to
 * perform user meta opeations
 */
class Dev_Members_List_Table extends WP_List_Table {
	/**
	 * The text domain of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	protected $plugin_text_domain;

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

		$this->_column_headers = $this->get_column_info();

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
		$total_users = count( $table_data );
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
			'ID'            => 'ID',
			'display_name'  => 'Display Name',
			'user_email'    => 'Email',
			'membership'    => 'Level Name',
			'membership_id' => 'Level ID',
			'startdate'     => 'Subscribe Date',
			'enddate'       => 'End Date',
			'joindate'      => 'Initial Date',
		);
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
			'ID'            => array(
				'ID',
				false,
			),
			'display_name'  => array(
				'display_name',
				false,
			),
			'user_email'    => array(
				'user_email',
				false,
			),
			'membership'    => array(
				'membership',
				false,
			),
			'membership_id' => array(
				'membership_id',
				false,
			),
			'startdate'     => array(
				'startdate',
				false,
			),
			'enddate'       => array(
				'enddate',
				false,
			),
			'joindate'      => array(
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
			case 'display_name':
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
					return 'Recurring';
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

	public function get_levels_dropdown( $selector ) {
		$existing_levels = $this->get_levels_object();
		if ( 1 < count( $existing_levels ) ) {
			$pmpro_levels_dropdown  = '<select name="' . $selector . '" id="' . $selector . '">';
			$pmpro_levels_dropdown .= '<option value="">Select a Level</option>';
			foreach ( $existing_levels as $key => $value ) {
				$pmpro_levels_dropdown .= '<option value="' . $value->name . '">' . $value->id . ' => ' . $value->name . '</option>';
			}
			$pmpro_levels_dropdown .= '<select>';
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
			echo '<div id="researching-levels" style="float:left;">';
			echo $existing_levels = $this->get_levels_dropdown( 'dropdown-levels' );
			echo '</div>';
			$views = $this->get_views();
			echo '<div style="float:left;padding: 0.4rem 1rem 1rem;">';
			print_r( $views );
			echo '</div>';
		}
		if ( $which == 'bottom' ) {
			echo '<h4>Having trouble with AJAX selection of the level</h4>';
			echo '<span id="return-selected"> return-selected </span>';
		}
	}

	public function pmpro_add_in_class_admin_scripts() {
		wp_register_script( 'selected-level', plugins_url( '/js/selected-level.js', __FILE__ ), array( 'jquery' ), time() );
		wp_localize_script(
			'selected-level',
			'selected_level_object',
			array(
				'selected_ajaxurl' => admin_url( 'admin-ajax.php' ),
				'selected_nonce'   => wp_create_nonce( 'selected-nonce' ),
			)
		);
		wp_enqueue_script( 'selected-level' );
	}

	/**
	 * Stop execution and exit
	 *
	 * @since    2.0.0
	 *
	 * @return void
	 */
	public function pmpro_in_class_function() {
		$return_data = $_POST;
		echo json_encode( $return_data );
		// echo '<pre>';
		// print_r( $return_data );
		// echo '</pre>';
		exit();
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
				'back_link' => esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ), admin_url( 'pmpro-dashboard' ) ) ),
			)
		);
	}
}

function members_list_research_levels( $added_levels ) {
	$added_levels = array(
		__( 'Cancelled', 'paid-memberships-pro' ),
		__( 'Expired', 'paid-memberships-pro' ),
		__( 'Old Members', 'paid-memberships-pro' ),
	);
	return $added_levels;
}

add_action( 'admin_footer', 'tabbed_diagnostic_message' );
function tabbed_diagnostic_message() {
	global $current_user;
	?>
	<style type="text/css">

#footer-diagnostic {
	display: grid;
	grid-template-columns: 16% 1fr 1fr 1fr;
}

#footer-diagnostic pre {
 white-space: pre-wrap;       /* css-3 */
 white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
 white-space: -pre-wrap;      /* Opera 4-6 */
 white-space: -o-pre-wrap;    /* Opera 7 */
 word-wrap: break-word;       /* Internet Explorer 5.5+ */
}
#footer-diagnostic .full {
	grid-column: 1 / -1;
	text-align: center;
}
#toplevel_page_pmpro-beta  .xdebug-error.xe-warning {
	margin-left: 12rem;
}
	</style>
	<?php
		echo '<div id="footer-diagnostic">';
		echo '<div>$user <pre>';
		echo '$current_user ' . $current_user->ID . '<br>';
	if ( ! empty( $_REQUEST['user'] ) ) {
		$user_id = intval( $_REQUEST['user'] );
		$user    = get_userdata( $user_id );
		if ( empty( $user->ID ) ) {
			$user_id = false;
		}
	} else {
		$user = get_userdata( 0 );
	}

	if ( isset( $user->ID ) ) {
		echo '$user->ID  ' . $user->ID . '<br>';
	} elseif ( isset( $user_id ) ) {
		echo '$user_id ' . $user_id . '<br>';
	} else {
		echo 'wtf';
	}
		echo '</div><div>$_GET <pre>';
		print_r( $_GET );
		echo '</pre></div>';
		echo '<div>$_REQUEST <pre>';
		print_r( $_REQUEST );
		echo '</pre></div>';
		echo '<div>$_POST <pre>';
		print_r( $_POST );
		echo '</pre></div>';
		echo '<div class="full">';
		echo __FUNCTION__;
		echo '<br>Line ' . __LINE__ . '</div>';
		echo '</div>';

}

function pmpro_add_admin_function() {
	$return_data = $_POST;
	add_query_arg( 's', $return_data['filter'] );

	echo '<pre>';
	print_r( $return_data );
	echo '</pre>';

	exit();
}

function pmpro_add_admin_scripts() {
	wp_register_script( 'selected-level', plugins_url( '/js/selected-level.js', __FILE__ ), array( 'jquery' ), time() );
	wp_localize_script(
		'selected-level',
		'selected_level_object',
		array(
			'selected_ajaxurl' => admin_url( 'admin-ajax.php' ),
			'selected_nonce'   => wp_create_nonce( 'selected-nonce' ),
		)
	);
	wp_enqueue_script( 'selected-level' );
}

add_filter( 'add_to_levels_array', 'members_list_research_levels' );
add_action( 'admin_enqueue_scripts', 'pmpro_add_admin_scripts' );
add_action( 'wp_ajax_selected_level_request', 'pmpro_add_admin_function' );


