<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PMPro_Members_List_Table extends WP_List_Table {
	/**
	 * The text domain of this plugin.
	 *
	 * @since 2.2.0
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
	 * @since 2.2.0
	 */
	public function __construct() {

		$this->plugin_text_domain = 'paid-memberships-pro';

		parent::__construct(
			array(
				'plural'   => 'members',
				// Plural value used for labels and the objects being listed.
				'singular' => 'member',
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
	 * @since 2.2.0
	 */
	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();
		$this->handle_table_actions();
		$this->items = $this->sql_table_data();

		// set the pagination arguments
		$items_per_page = $this->get_items_per_page( 'users_per_page' );
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
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			// 'cb'            => '<input type="checkbox" />',
			'ID'				=> 'ID',
			'username'			=> 'Username',
			'first_name'		=> 'First Name',
			'last_name'			=> 'Last Name',
			'display_name'		=> 'Display Name',
			'user_email'		=> 'Email',
			'address'			=> 'Billing Address',
			'membership'		=> 'Level',
			'membership_id'		=> 'Level ID',
			'fee'				=> 'Fee',
			'joindate'			=> 'Registered',
			'startdate'			=> 'Start Date',
			'enddate'			=> 'End Date',
		);

		if ( isset( $_REQUEST['l'] ) ) {
			$l = sanitize_text_field( $_REQUEST['l'] );
		} else {
			$l = false;
		}

		if ( 'oldmembers' === $l ) {
			$columns['enddate'] = 'Ended';
		} elseif ( 'expired' === $l ) {
			$columns['enddate'] = 'Expired';
		} elseif ( 'cancelled' === $l ) {
			$columns['enddate'] = 'Cancelled';
		}

		$columns = apply_filters( 'pmpro_memberslist_extra_cols', $columns );

		// Re-implementing old hook, will be deprecated.
		ob_start();
		do_action( 'pmpro_memberslist_extra_cols_header' );
		$extra_cols = ob_get_clean();
		preg_match_all( '/<th>(.*?)<\/th>/s', $extra_cols, $matches );
		$custom_field_num = 0;
		foreach ( $matches[1] as $match ) {
			$columns[ 'custom_field_' . $custom_field_num ] = $match;
			$custom_field_num++;
		}

		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		$hidden = array(
			'display_name',
			'membership_id',
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
	 * @since 2.2.0
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
			'username'     => array(
				'user_login',
				false,
			),
			'fee' => array(
				'fee',
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
			'joindate'       => array(
				'joindate',
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
		);
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby = 'ID';
		$order   = 'desc';

		// If orderby is set, use this as the sort column
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = $_GET['orderby'];
		}

		// If order is set use this as the order
		if ( ! empty( $_GET['order'] ) ) {
			$order = $_GET['order'];
		}

		if ( is_numeric( $a[ $orderby ] ) && is_numeric( $b[ $orderby ] ) ) {
			$result = intval( $a[ $orderby ] ) > intval( $b[ $orderby ] ) ? 1 : -1;
		} else {
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		}

		if ( $order === 'asc' ) {
			return $result;
		}

		return -$result;
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since 2.2.0
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
			$s = sanitize_text_field(trim($_REQUEST['s']));
		else
			$s = "";
		?>
		<p>
			<?php _e( 'No members found.', 'paid-memberships-pro' ); ?>
			<?php if ( $l ) { ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 's' => $s ) ) ); ?>"><?php _e( 'Search all levels', 'paid-memberships-pro' );?></a>
			<?php } ?>
		</p>
		<hr />
		<p><?php _e( 'You can also try searching:', 'paid-memberships-pro' ); ?>
		<ul class="ul-disc">
			<li><a href="<?php echo esc_url( add_query_arg( array( 's' => $s ), admin_url( 'users.php' ) ) ); ?>"><?php _e( 'All Users', 'paid-memberships-pro' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 'l' => 'cancelled', 's' => $s ) ) ); ?>"><?php _e( 'Cancelled Members', 'paid-memberships-pro' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 'l' => 'expired', 's' => $s ) ) ); ?>"><?php _e( 'Expired Members', 'paid-memberships-pro' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 'l' => 'oldmembers', 's' => $s ) ) ); ?>"><?php _e( 'Old Members', 'paid-memberships-pro' ); ?></a></li>
		</ul>
		<?php
	}

	/**
	 * Get the table data
	 *
	 * @return Array or integer if $count parameter = true
	 */
	private function sql_table_data( $count = false ) {
		global $wpdb;

		// some vars for the search
		if ( isset( $_REQUEST['l'] ) ) {
			$l = sanitize_text_field( $_REQUEST['l'] );
		} else {
			$l = false;
		}
		if(isset($_REQUEST['s']))
			$s = sanitize_text_field(trim($_REQUEST['s']));
		else
			$s = "";
		
		// some vars for ordering
		if(isset($_REQUEST['orderby'])) {
			$orderby = $this->sanitize_orderby( $_REQUEST['orderby'] );
			if( $_REQUEST['order'] == 'asc' ) {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}
		} else {
			if ( 'oldmembers' === $l || 'expired' === $l || 'cancelled' === $l ) {
				$orderby = 'enddate';
				$order = 'DESC';
			} else {
				$orderby = 'u.user_registered';
				$order = 'DESC';
			}
		}
		
		// some vars for pagination	
		if(isset($_REQUEST['paged']))
			$pn = intval($_REQUEST['paged']);
		else
			$pn = 1;
		
		$limit = $this->get_items_per_page( 'users_per_page' );

		$end = $pn * $limit;
		$start = $end - $limit;

		if ( $count ) {
			$sqlQuery = "SELECT COUNT( DISTINCT u.ID ) ";
		} else {
			$sqlQuery =
				"
				SELECT u.ID, u.user_login, u.user_email, u.display_name,
				UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, SUM(mu.initial_payment+ mu.billing_amount) as fee, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit,
				UNIX_TIMESTAMP(mu.startdate) as startdate,
				UNIX_TIMESTAMP(max(mu.enddate)) as enddate, m.name as membership
				";
		}
			
		$sqlQuery .= 
			"	
			FROM $wpdb->users u 
			LEFT JOIN $wpdb->pmpro_memberships_users mu
			ON u.ID = mu.user_id
			LEFT JOIN $wpdb->pmpro_membership_levels m
			ON mu.membership_id = m.id
			";
			
		if ( !empty( $s ) ) {
			$sqlQuery .= " LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id ";
		}

		if ( 'oldmembers' === $l || 'expired' === $l || 'cancelled' === $l ) {
				$sqlQuery .= " LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";
		}
		
		$sqlQuery .= ' WHERE mu.membership_id > 0 ';
		
		if ( ! empty( $s ) ) {
			$sqlQuery .= " AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%' OR u.display_name LIKE '%" . esc_sql($s) . "%') ";
		}

		if ( 'oldmembers' === $l ) {
			$sqlQuery .= " AND mu.status <> 'active' AND mu2.status IS NULL ";
		} elseif ( 'expired' === $l ) {
			$sqlQuery .= " AND mu.status = 'expired' AND mu2.status IS NULL ";
		} elseif ( 'cancelled' === $l ) {
			$sqlQuery .= " AND mu.status IN('cancelled', 'admin_cancelled') AND mu2.status IS NULL ";
		} elseif ( $l ) {
			$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql( $l ) . "' ";
		} else {
			$sqlQuery .= " AND mu.status = 'active' ";
		}
		
		if ( ! $count ) {
			$sqlQuery .= ' GROUP BY u.ID ';
			
			$sqlQuery .= " ORDER BY $orderby $order ";
			
			$sqlQuery .= " LIMIT $start, $limit ";
		}

		$sqlQuery = apply_filters("pmpro_members_list_sql", $sqlQuery);
		
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
			'ID' 				=> 'u.ID',
			'user_login' 		=> 'u.user_login',
			'display_name' 		=> 'u.display_name',
			'user_email' 		=> 'u.user_email',
			'membership' 		=> 'mu.membership_id',
			'membership_id' 	=> 'mu.membership_id',
			'fee' 				=> 'fee',
			'joindate' 			=> 'u.user_registered',
			'startdate' 		=> 'mu.startdate',
			'enddate' 			=> 'mu.enddate',
		);
		
		$allowed_orderbys = apply_filters('pmpro_memberslist_allowed_orderbys', $allowed_orderbys );
		
	 	if ( ! empty( $allowed_orderbys[$orderby] ) ) {
			$orderby = $allowed_orderbys[$orderby];
		} else {
			$orderby = false;
		}
		
		return $orderby;
	}

	/**
	 * Filter the table data based on the user search key
	 *
	 * @since 2.2.0
	 *
	 * @param array  $table_data
	 * @param string $search_key
	 * @return array
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
		$item = (array) apply_filters( 'pmpro_members_list_user', (object) $item );
		switch ( $column_name ) {
			case 'ID':
			case 'display_name':
			case 'user_email':
			case 'membership':
			case 'membership_id':
			case 'cycle_period':
			case 'cycle_number':
				return $item[ $column_name ];
			case 'username':
				$avatar = get_avatar( $item['ID'], 32 );
				$userlink = '<a href="user-edit.php?user_id=' . $item['ID'] . '">' . $item['user_login'] . '</a>';
				$userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, get_userdata( $item['ID'] ) );
				$output = $avatar . ' <strong>' . $userlink . '</strong><br />';

				// Set up the hover actions for this user
				$actions      = apply_filters( 'pmpro_memberslist_user_row_actions', array(), (object) $item );
				$action_count = count( $actions );
				$i            = 0;
				if ( $action_count ) {
					$output .= '<div class="row-actions">';
					foreach ( $actions as $action => $link ) {
						++$i;
						( $i == $action_count ) ? $sep = '' : $sep = ' | ';
						$output .= "<span class='$action'>$link$sep</span>";
					}
					$output .= '</div>';
				}
				return $output;
			case 'fee':
				$fee = '';
				// If there is no payment for the level, show a dash.
				if ( (float)$item['initial_payment'] <= 0 && (float)$item['billing_amount'] <= 0 ) {
					$fee .= esc_html_e( '&#8212;', 'paid-memberships-pro' );
				} else {
					// Display the member's initial payment.
					if ( (float)$item['initial_payment'] > 0 ) {
						$fee .= pmpro_formatPrice( $item['initial_payment'] );
					}
					// If there is a recurring payment, show a plus sign.
					if ( (float)$item['initial_payment'] > 0 && (float)$item['billing_amount'] > 0 ) {
						$fee .= esc_html( ' + ', 'paid-memberships-pro' );
					}
					// If there is a recurring payment, show the recurring payment amount and cycle.
					if ( (float)$item['billing_amount'] > 0 ) {
						$fee .= pmpro_formatPrice( $item['billing_amount'] );
						$fee .= esc_html( ' per ', 'paid-memberships-pro' );
						if ( $item['cycle_number'] > 1 ) {
							$fee .= $item['cycle_number'] . " " . $item['cycle_period'] . "s";
						} else {
							$fee .= $item['cycle_period'];
						}
					}
				}
				return $fee;
			case 'joindate':
				$joindate = $item[ $column_name ];
				return date_i18n( get_option('date_format'), $joindate );
			case 'startdate':
				$startdate = $item[ $column_name ];
				return date_i18n( get_option('date_format'), $startdate );
			case 'enddate':
				$user_object = get_userdata( $item['ID'] );
				if ( 0 == $item['enddate'] ) {
					return __( apply_filters( 'pmpro_memberslist_expires_column', 'Never', $user_object ), 'paid-memberships-pro');
				} else {
					return apply_filters( 'pmpro_memberslist_expires_column', date_i18n( get_option('date_format'), $item['enddate'] ), $user_object );
				}
			default:
				if ( isset( $item[$column_name] ) ) {
					return $item[$column_name];
				} elseif ( 0 === strpos( $column_name, 'custom_field_' ) ) {
					// Re-implementing old hook, will be deprecated.
					$user_object = get_userdata( $item['ID'] );
					ob_start();
					do_action( 'pmpro_memberslist_extra_cols_body', $user_object );
					$extra_cols = ob_get_clean();
					preg_match_all( '/<td>(.*?)<\/td>/s', $extra_cols, $matches );
					$custom_field_num = explode( 'custom_field_', $column_name )[1];
					if ( is_numeric( $custom_field_num ) && isset( $matches[1][ intval( $custom_field_num ) ] ) ) {
						return $matches[1][ intval( $custom_field_num ) ];
					}
				}
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
		return ( $user_object->first_name ?: '&#8212;' );
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
		return ( $user_object->last_name ?: '&#8212;' );
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
		// return ( $user_object->last_name ?: '---' );
	}

	public function get_some_actions() {
		if ( isset( $_REQUEST['l'] ) ) {
			$l = sanitize_text_field( $_REQUEST['l'] );
		} else {
			$l = false;
		}
		?>
		<ul class="subsubsub">
		<li>
			<?php _e( 'Show', 'paid-memberships-pro' ); ?>
			<select name="l" onchange="jQuery('#posts-filter').submit();">
				<option value="" 
				<?php
				if ( ! $l ) {
					?>
					selected="selected"<?php } ?>><?php _e( 'All Levels', 'paid-memberships-pro' ); ?></option>
				<?php
					$levels = $wpdb->get_results(
						"
						SELECT id, name 
						FROM $wpdb->pmpro_membership_levels 
						ORDER BY name
						"
					);
				foreach ( $levels as $level ) {
					?>
					<option value="<?php echo $level->id; ?>" 
					  <?php
						if ( $l == $level->id ) {
							?>
						selected="selected"<?php } ?>><?php echo $level->name; ?></option>
					<?php
				}
				?>
				<option value="cancelled" 
				<?php
				if ( $l == 'cancelled' ) {
					?>
					selected="selected"<?php } ?>><?php _e( 'Cancelled Members', 'paid-memberships-pro' ); ?></option>
				<option value="expired" 
				<?php
				if ( $l == 'expired' ) {
					?>
					selected="selected"<?php } ?>><?php _e( 'Expired Members', 'paid-memberships-pro' ); ?></option>
				<option value="oldmembers" 
				<?php
				if ( $l == 'oldmembers' ) {
					?>
					selected="selected"<?php } ?>><?php _e( 'Old Members', 'paid-memberships-pro' ); ?></option>
			</select>
		</li>
	</ul>
		<?php
	}

	/**
	 * Add extra markup in the toolbars before or after the list
	 *
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list array( '' => 'Select a Level' )
	 */
	function extra_tablenav( $which ) {
		global $membership_levels, $wpdb;
		if ( $which == 'top' ) {
			// The code that goes before the table is here
			if(isset($_REQUEST['l'])) {
				$l = sanitize_text_field($_REQUEST['l']);
			} else {
				$l = false;
			}
			_e('Show', 'paid-memberships-pro' );?>
			<select name="l" onchange="jQuery('#member-list-form').submit();">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php _e('All Levels', 'paid-memberships-pro' );?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
				<?php
					}
				?>
				<option value="cancelled" <?php if($l == "cancelled") { ?>selected="selected"<?php } ?>><?php _e('Cancelled Members', 'paid-memberships-pro' );?></option>
				<option value="expired" <?php if($l == "expired") { ?>selected="selected"<?php } ?>><?php _e('Expired Members', 'paid-memberships-pro' );?></option>
				<option value="oldmembers" <?php if($l == "oldmembers") { ?>selected="selected"<?php } ?>><?php _e('Old Members', 'paid-memberships-pro' );?></option>
			</select>
			<?php
			}
		if ( $which == 'bottom' ) {
			// The code that goes after the table is there
		}
	}

	/**
	 * Process actions triggered by the user
	 *
	 * @since 2.2.0
	 */
	public function handle_table_actions() {
		/**
		 * Note: Table bulk_actions can be identified by checking $_REQUEST['action'] and $_REQUEST['action2']
		 *
		 * action - is set if checkbox from top-most select-all is set, otherwise returns -1
		 * action2 - is set if checkbox the bottom-most select-all checkbox is set, otherwise returns -1
		 */

		// check for individual row actions
		$the_table_action = $this->current_action();

		if ( 'view_usermeta' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'view_usermeta_nonce' ) ) {
				$this->invalid_nonce_redirect();
			} else {
				$this->graceful_exit();
			}
		}

		// check for table bulk actions
		if ( ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'bulk-download' ) || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-download' ) ) {

			// verify the nonce.
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			/**
			 * Note: the nonce field is set by the parent class
			 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );
			 */
			if ( ! wp_verify_nonce( $nonce, 'bulk-users' ) ) {
				$this->invalid_nonce_redirect();
			} else {
				$this->page_bulk_download( $_REQUEST['users'] );
				$this->graceful_exit();
			}
		}
	}

	/**
	 * Stop execution and exit
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function graceful_exit() {
		exit;
	}

	/**
	 * Die when the nonce check fails.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function invalid_nonce_redirect() {
		wp_die(
			__( 'Invalid Nonce', $this->plugin_text_domain ),
			__( 'Error', $this->plugin_text_domain ),
			array(
				'response'  => 403,
				'back_link' => esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ), admin_url( 'pmpro-membershiplevels' ) ) ),
			)
		);
	}
}
