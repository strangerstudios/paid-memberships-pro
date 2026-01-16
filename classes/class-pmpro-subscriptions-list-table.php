<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PMPro_Subscriptions_List_Table extends WP_List_Table {
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
				'plural'   => 'subscriptions',
				// Plural value used for labels and the objects being listed.
				'singular' => 'subscription',
				// Singular label for an object being listed, e.g. 'post'.
				'ajax'     => false,
				// If true, the parent class will call the _js_vars() method in the footer
			)
		);
	}

	/**
	 * Sets up screen options for the subscriptions list table.
	 *
	 * @since 3.0
	 */
	public static function hook_screen_options() {
		$list_table = new PMPro_Subscriptions_List_Table();
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'label'   => __( 'Subscriptions per page', 'paid-memberships-pro' ),
				'option'  => 'pmpro_subscriptions_per_page',
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
		if ( 'pmpro_subscriptions_per_page' === $option ) {
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

		$items_per_page = $this->get_items_per_page( 'pmpro_subscriptions_per_page' );
		/**
		 * Filter to set the default number of items to show per page
		 * on the Subscriptions page in the admin.
		 *
		 * @since 1.8.4.5
		 *
		 * @param int $limit The number of items to show per page.
		 */
		$items_per_page = apply_filters( 'pmpro_subscriptions_per_page', $items_per_page );
		
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
			'id'                          => __( 'Subscription ID', 'paid-memberships-pro' ),
			'user'                        => __( 'User', 'paid-memberships-pro' ),
			'level'                       => __( 'Level', 'paid-memberships-pro' ),
			'fee'                         => __( 'Fee', 'paid-memberships-pro' ),
			'gateway'                     => __( 'Gateway', 'paid-memberships-pro' ),
			'status'                      => __( 'Status', 'paid-memberships-pro' ),
			'startdate'                   => __( 'Created', 'paid-memberships-pro' ),
			'next_payment_date'           => __( 'Next Payment', 'paid-memberships-pro' ),
			'enddate'                     => __( 'Ended', 'paid-memberships-pro' ),
			'orders'                      => __( 'Orders', 'paid-memberships-pro' ),
		);

		// If we are filtering by status, we either want to remove the next_payment_date or the enddate column.
		if ( ! empty( $_REQUEST['status'] ) ) {
			if ( $_REQUEST['status'] == 'active' ) {
				unset( $columns['enddate'] );
			} elseif ( $_REQUEST['status'] == 'cancelled' ) {
				unset( $columns['next_payment_date'] );
			}
		}

		// Shortcut for editing columns in default discount code list location.
		$current_screen = get_current_screen();
		if ( ! empty( $current_screen ) && strpos( $current_screen->id, "pmpro-subscriptions" ) !== false ) {
			$columns = apply_filters( 'pmpro_manage_subscriptionslist_columns', $columns );
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
				'startdate',
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
	 * The second format will make the initial sorting subscription by descending
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
			'id'    => array( 's.id', true ),
			'user' => array( 's.user_id', false ),
			'startdate' => array( 's.startdate', true ),
			'next_payment_date' => array( 's.next_payment_date', false ),
			'enddate' => array( 's.enddate', true ),
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
		esc_html_e( 'No subscriptions found.', 'paid-memberships-pro' );
	}

	/**
	 * Get the table data
	 *
	 * @return Array|integer if $count parameter = true
	 */
	private function sql_table_data( $count = false, $limit = 15 ) {
		global $wpdb;

		$s = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( $_REQUEST['s'] ) ) : '';
		$level = isset( $_REQUEST['level'] ) ? intval( $_REQUEST['level'] ) : false;
		$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
		$pn = isset( $_REQUEST['paged'] ) ? intval( $_REQUEST['paged'] ) : 1;
		$items_per_page = $this->get_items_per_page( 'pmpro_subscriptions_per_page' );
		/**
		 * Filter to set the default number of items to show per page
		 * on the Subscriptions page in the admin.
		 *
		 * @since 1.8.4.5
		 *
		 * @param int $limit The number of items to show per page.
		 */
		$limit = apply_filters( 'pmpro_subscriptions_per_page', $items_per_page );

		$end   = $pn * $limit;
		$start = $end - $limit;

		// Filters.
		$condition = '1=1';
		if ( ! empty( $level ) ) {
			$condition .= ' AND s.membership_level_id = ' . intval( $level );
		}
		if ( ! empty( $status ) ) {
			if ( $status === 'sync_error' ) {
				$condition .= ' AND sm.meta_value IS NOT NULL';
			} else {
				$condition .= ' AND s.status = "' . esc_sql( $status ) . '"';
			}
		}

		$orderby = '';

		if ( ! empty( $_REQUEST['order'] ) && ! empty( $_REQUEST['orderby'] ) && ! $count ) {
			$order         = $_REQUEST['order'] == 'asc' ? 'ASC' : 'DESC';
			$orderby       = $this->sanitize_orderby( sanitize_text_field( $_REQUEST['orderby'] ) );
			$orderby_query = "ORDER BY $orderby $order";
		} else {
			$orderby_query = 'ORDER BY id DESC';
		}

		if( $count ) {
			$calculation_function = 'COUNT(*), ';
		} else {
			$calculation_function = 'SQL_CALC_FOUND_ROWS';
		}

		$sqlQuery = "SELECT $calculation_function s.id FROM $wpdb->pmpro_subscriptions s LEFT JOIN $wpdb->pmpro_membership_levels ml ON s.membership_level_id = ml.id LEFT JOIN $wpdb->users u ON s.user_id = u.ID LEFT JOIN $wpdb->pmpro_subscriptionmeta sm ON s.id = sm.pmpro_subscription_id AND sm.meta_key = 'sync_error' ";

		if ( $s ) {
			$sqlQuery .= 'WHERE (1=2 ';

			$fields = array(
				's.id',
				's.status',
				's.billing_amount',
				's.cycle_period',
				's.subscription_transaction_id',
				'u.user_login',
				'u.user_email',
				'u.display_name',
				'ml.name',
			);

			foreach ( $fields as $field ) {
				$sqlQuery .= ' OR ' . esc_sql( $field ) . " LIKE '%" . esc_sql( $s ) . "%' ";
			}
			$sqlQuery .= ') ';

			//Not escaping here because we escape the values in the condition statement
			$sqlQuery .= 'AND ' . $condition . ' ';

			if ( ! $count ) {
				$sqlQuery .= 'GROUP BY s.id ' . $orderby_query . ' ';
			}
		} else {
			//Not escaping here because we escape the values in the condition statement
			$sqlQuery .= "WHERE " . $condition . ' ' .  $orderby_query . ' ';

		}

		if( $count ) {
			return $wpdb->get_var( $sqlQuery );    
		} else {
			$sqlQuery .= "LIMIT " . esc_sql( $start ) . "," . esc_sql( $limit );
			$subscription_ids = $wpdb->get_col( $sqlQuery );
			$subscription_data = array();
			foreach ( $subscription_ids as $subscription_id ) {
				$subscription = PMPro_Subscription::get_subscription( $subscription_id );
				if ( ! empty( $subscription ) ) {
					$subscription_data[] = $subscription;
				}
			}
			return $subscription_data;
		}
	}

	/**
	 * Add extra markup in the toolbars before or after the list
	 *
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list array( '' => 'Select a Level' )
	 */
	function extra_tablenav( $which ) {
		if ( $which == 'top' ) {

			if ( isset( $_REQUEST['level'] ) ) {
				$l = intval( $_REQUEST['level'] );
			} else {
				$l = false;
			}

			if ( isset( $_REQUEST['status'] ) ) {
				$status = sanitize_text_field( $_REQUEST['status'] );
			} else {
				$status = '';
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
				"><p><?php echo wp_kses_post( $pmpro_msg ); ?></p></div>
			<?php } ?>
				<?php
				// Note: Only subscriptions belonging to current levels can be filtered. There is no option for subscriptions belonging to deleted levels.
				$levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ) );
				?>
				<?php esc_html_e( 'Show', 'paid-memberships-pro' ); ?>
				<select id="level" name="level">
					<option value=""><?php esc_html_e( 'All Levels', 'paid-memberships-pro' ); ?></option>
					<?php foreach ( $levels as $level ) { ?>
						<option
							value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $l, $level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
					<?php } ?>
				</select>
				<span id="filterby"><?php esc_html_e( 'filter by ', 'paid-memberships-pro' ); ?></span>
				<select id="status" name="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'paid-memberships-pro' ); ?></option>
					<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'paid-memberships-pro' ); ?></option>
					<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'paid-memberships-pro' ); ?></option>
					<option value="sync_error" <?php selected( $status, 'sync_error' ); ?>><?php esc_html_e( 'Sync Error', 'paid-memberships-pro' ); ?></option>
				</select>
				<input type="hidden" name="page" value="pmpro-subscriptions"/>
				<input id="submit" class="button" type="submit" value="<?php esc_attr_e( 'Filter', 'paid-memberships-pro' ); ?>"/>
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
		$allowed_orderbys = array( 's.id','s.user_id','s.startdate','s.enddate','s.next_payment_date' );

		// Sanitize the orderby value to support one of our predefined orderby values OR default to next_payment_date.
		if ( ! in_array( $orderby, $allowed_orderbys, true ) ) {
			$orderby = 's.next_payment_date';
		}

		return $orderby;
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param object  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		do_action( 'pmpro_manage_subscriptionlist_custom_column', $column_name, $item );
	}

	/**
	 * Renders the columns subscription ID value
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_id( $item ) {
		?>
		<strong><a href="admin.php?page=pmpro-subscriptions&id=<?php echo esc_attr( $item->get_id() ); ?>"><?php echo esc_html( $item->get_subscription_transaction_id() ); ?></a></strong>
		<div class="row-actions">
			<?php
			$actions = [
				'view' => sprintf(
					'<a title="%1$s" href="%2$s">%3$s</a>',
					esc_attr__( 'View Details', 'paid-memberships-pro' ),
					esc_url(
						add_query_arg(
							[
								'page'  => 'pmpro-subscriptions',
								'id' => $item->get_id(),
							],
							admin_url( 'admin.php' )
						)
					),
					esc_html__( 'View Details', 'paid-memberships-pro' )
				),
			];

			$actions_html = [];
			foreach ( $actions as $action => $link ) {
				$actions_html[] = sprintf(
					'<span class="%1$s">%2$s</span>',
					esc_attr( $action ),
					$link
				);
			}

			if ( ! empty( $actions_html ) ) {
				echo wp_kses_post( implode( ' | ', $actions_html ) );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the columns user associated with the subscription
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_user( $item ) {
		$user = get_userdata( $item->get_user_id() );
		if ( ! empty( $user ) ) { 
			echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$user->ID ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $user->user_login ) . '</a><br />';
			echo esc_html( $user->user_email );
		 } elseif ( $item->get_user_id() > 0 ) {
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

		$level = pmpro_getLevel( $item->get_membership_level_id() );
		if ( ! empty( $level ) ) {
			echo esc_html( $level->name );
		} elseif ( $item->get_membership_level_id() > 0 ) {
			echo '['. esc_html__( 'deleted', 'paid-memberships-pro' ).']';
		} else {
			esc_html_e( '&#8212;', 'paid-memberships-pro' );
		}

		// If the subscription is active and the user does not have the level that the subscription is for, show a message.
		if ( 'active' === $item->get_status() ) {
			if( $item->get_membership_level_id() > 0 ) {
				$user_levels    = pmpro_getMembershipLevelsForUser( $item->get_user_id() );
				$user_level_ids = wp_list_pluck( $user_levels, 'id' );
				if ( ! in_array( $item->get_membership_level_id(), $user_level_ids ) ) {
					?>
					<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">
						<?php esc_html_e( 'Membership Ended', 'paid-memberships-pro' ); ?>
					</span>
					<?php
				}
			} else {
				?>
				<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">
					<?php esc_html_e( 'No Level', 'paid-memberships-pro' ); ?>
				</span>
				<?php
			}
		}
		
	}

	/**
	 * Renders the columns subscription fee information
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_fee( $item ) {
		echo esc_html( $item->get_cost_text() );
	}

	/**
	 * Renders the columns gateway information
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_gateway( $item ) {
		global $pmpro_gateways;
		if ( ! empty( $item->get_gateway() ) ) {
			if ( ! empty( $pmpro_gateways[ $item->get_gateway() ] ) ) {
				echo esc_html( $pmpro_gateways[ $item->get_gateway() ] );
			} else {
				echo esc_html( ucwords( $item->get_gateway() ) );
			}
			if ( $item->get_gateway_environment() == 'sandbox' ) {
				echo ' (test)';
			}
		}
	}

	/**
	 * Renders the columns subscription status
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {     
		$status = ( 'active' === $item->get_status() ) ? __( 'Active', 'paid-memberships-pro' ) : __( 'Cancelled', 'paid-memberships-pro' );
		?>
		<span class="pmpro_subscription-status pmpro_subscription-status-<?php echo esc_attr( $item->get_status() ); ?>">
			<?php echo esc_html( $status ); ?>
		</span>
		<?php
		$sync_error = get_pmpro_subscription_meta( $item->get_id(), 'sync_error', true );
		if ( ! empty( $sync_error ) ) {
			// Show the sync error message.
			?>
			<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">
				<?php esc_html_e( 'Sync Error:', 'paid-memberships-pro' ); ?>
				<?php echo esc_html( $sync_error ); ?>
			</span>
			<?php
		}
	}

	/**
	 * Renders the columns subscription start date.
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_startdate( $item ) {
		echo esc_html( sprintf(
			// translators: %1$s is the date and %2$s is the time.
			__( '%1$s at %2$s', 'paid-memberships-pro' ),
			esc_html( $item->get_startdate( get_option( 'date_format' ) ) ),
			esc_html( $item->get_startdate( get_option( 'time_format' ) ) )
		) );
	}

	/**
	 * Renders the columns subscription next payment date.
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_next_payment_date( $item ) {
		$date_to_show = $item->get_next_payment_date( get_option( 'date_format' ) );
		$time_to_show = $item->get_next_payment_date( get_option( 'time_format' ) );
		if ( ! empty( $date_to_show ) ) {
			echo esc_html(
				sprintf(
					// translators: %1$s is the date and %2$s is the time.
					__( '%1$s at %2$s', 'paid-memberships-pro' ),
					esc_html( $date_to_show ),
					esc_html( $time_to_show )
				)
			);
		} else {
			esc_html_e( '&#8212;', 'paid-memberships-pro' );
		}
	}

	/**
	 * Renders the columns subscription end date.
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_enddate( $item ) {
		$date_to_show = $item->get_enddate( get_option( 'date_format' ) );
		$time_to_show = $item->get_enddate( get_option( 'time_format' ) );
		if ( ! empty( $date_to_show ) ) {
			echo esc_html(
				sprintf(
					// translators: %1$s is the date and %2$s is the time.
					__( '%1$s at %2$s', 'paid-memberships-pro' ),
					esc_html( $date_to_show ),
					esc_html( $time_to_show )
				)
			);
		} else {
			esc_html_e( '&#8212;', 'paid-memberships-pro' );
		}
	}

	/**
	 * Renders the columns subscription orders.
	 *
	 * @param object  $item
	 *
	 * @return string
	 */
	public function column_orders( $item ) {
		$orders = $item->get_orders();
		if ( ! empty( $orders ) ) {
			// Print the number of orders and link to the order page filtered by this subscription transaction ID.
			echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $item->get_subscription_transaction_id() ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( count( $orders ) ) . '</a>';
		} else {
			esc_html_e( '&#8212;', 'paid-memberships-pro' );
		}
	}
}
