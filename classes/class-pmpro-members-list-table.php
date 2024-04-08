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
	 * Sets up screen options for the members list table.
	 *
	 * @since 3.0
	 */
	public static function hook_screen_options() {
		$list_table = new PMPro_Members_List_Table();
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'label'   => __( 'Members per page', 'paid-memberships-pro' ),
				'option'  => 'pmpro_members_per_page',
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
		if ( 'pmpro_members_per_page' === $option ) {
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
	 * @since 2.2.0
	 */
	public function prepare_items() {
		// Get the columns and set the _column_headers for the list table.
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// Get the data for the list table.
		$this->items = $this->sql_table_data();

		// Set the pagination arguments.
		$items_per_page = $this->get_items_per_page( 'pmpro_members_per_page' );
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
			'username'      => __( 'Username', 'paid-memberships-pro' ),
			'ID'            => __( 'ID', 'paid-memberships-pro' ),
			'first_name'    => __( 'First Name', 'paid-memberships-pro' ),
			'last_name'     => __( 'Last Name', 'paid-memberships-pro' ),
			'display_name'  => __( 'Display Name', 'paid-memberships-pro' ),
			'user_email'    => __( 'Email', 'paid-memberships-pro' ),
			'membership'    => __( 'Level', 'paid-memberships-pro' ),
			'membership_id' => __( 'Level ID', 'paid-memberships-pro' ),
			'subscription'  => __( 'Subscription', 'paid-memberships-pro' ),
			'joindate'      => __( 'Registered', 'paid-memberships-pro' ),
			'startdate'     => __( 'Start Date', 'paid-memberships-pro' ),
			'enddate'       => __( 'End Date', 'paid-memberships-pro' ),
		);

		if ( isset( $_REQUEST['l'] ) ) {
			$l = sanitize_text_field( $_REQUEST['l'] );
		} else {
			$l = false;
		}

		if ( 'oldmembers' === $l ) {
			$columns['enddate'] = __( 'Ended', 'paid-memberships-pro' );
		} elseif ( 'expired' === $l ) {
			$columns['enddate'] = __( 'Expired', 'paid-memberships-pro' );
		} elseif ( 'cancelled' === $l ) {
			$columns['enddate'] = __( 'Cancelled', 'paid-memberships-pro' );
		}

		// Should be deprecated in favor of "pmpro_manage_memberslist_columns".
		// Is applied to all members lists, regardless of screen.
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

		// Shortcut for editing columns in default memberslist location.
		$current_screen = get_current_screen();
		if ( ! empty( $current_screen ) && strpos( $current_screen->id, "pmpro-memberslist" ) !== false ) {
			$columns = apply_filters( 'pmpro_manage_memberslist_columns', $columns );
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
				'ID',
				'first_name',
				'last_name',
				'membership_id',
				'joindate',
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
			$s = trim( sanitize_text_field( $_REQUEST['s'] ) );
		else
			$s = "";
		?>
		<p>
			<?php esc_html_e( 'No members found.', 'paid-memberships-pro' ); ?>
			<?php if ( $l ) { ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 's' => $s ) ) ); ?>"><?php esc_html_e( 'Search all levels', 'paid-memberships-pro' );?></a>
			<?php } ?>
		</p>
		<hr />
		<p><?php esc_html_e( 'You can also try searching:', 'paid-memberships-pro' ); ?>
		<ul class="ul-disc">
			<li><a href="<?php echo esc_url( add_query_arg( array( 's' => $s ), admin_url( 'users.php' ) ) ); ?>"><?php esc_html_e( 'All Users', 'paid-memberships-pro' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 'l' => 'cancelled', 's' => $s ) ) ); ?>"><?php esc_html_e( 'Cancelled Members', 'paid-memberships-pro' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 'l' => 'expired', 's' => $s ) ) ); ?>"><?php esc_html_e( 'Expired Members', 'paid-memberships-pro' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-memberslist', 'l' => 'oldmembers', 's' => $s ) ) ); ?>"><?php esc_html_e( 'Old Members', 'paid-memberships-pro' ); ?></a></li>
		</ul>
		<?php
	}

	/**
	 * Get the table data
	 *
	 * @return Array|integer if $count parameter = true
	 */
	private function sql_table_data( $count = false ) {
		global $wpdb;

		// some vars for the search
		if ( isset( $_REQUEST['l'] ) ) {
			$l = sanitize_text_field( $_REQUEST['l'] );
		} else {
			$l = false;
		}

		$search_key = false;
		if( isset( $_REQUEST['s'] ) ) {
			$s = trim( sanitize_text_field( $_REQUEST['s'] ) );
		} else {
			$s = '';
		}

		// If there's a colon in the search, let's split it out.
		if( ! empty( $s ) && strpos( $s, ':' ) !== false ) {
			$parts = explode( ':', $s );
		$search_key = array_shift( $parts );
		$s = implode( ':', $parts );
		}

		// Treat * as wild cards.
		$s = str_replace( '*', '%', $s );

		// some vars for ordering
		if(isset($_REQUEST['orderby'])) {
			$orderby = $this->sanitize_orderby( sanitize_text_field( $_REQUEST['orderby'] ) );
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

		$limit = $this->get_items_per_page( 'pmpro_members_per_page' );

		$end = $pn * $limit;
		$start = $end - $limit;

		if ( $count ) {
			$sqlQuery = "SELECT COUNT( DISTINCT u.ID, mu.membership_id ) ";
		} else {
			$sqlQuery =
				"
				SELECT u.ID, u.user_login, u.user_email, u.display_name,
				UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate, mu.membership_id,
				UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate,
				UNIX_TIMESTAMP(CONVERT_TZ(max(mu.enddate), '+00:00', @@global.time_zone)) as enddate, m.name as membership
				";
		}

		$sqlQuery .=
			"	
			FROM $wpdb->users u 
			LEFT JOIN $wpdb->pmpro_memberships_users mu
			ON u.ID = mu.user_id
			LEFT JOIN $wpdb->pmpro_membership_levels m
			ON mu.membership_id = m.id
			LEFT JOIN $wpdb->pmpro_subscriptions s
			ON mu.user_id = s.user_id
			";

		if ( !empty( $s ) ) {
			if ( ! empty( $search_key ) ) {
				// If there's a colon in the search string, make the search smarter.
				if( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
					$key_column = 'u.user_' . $search_key; // All search key options above are safe for use in a query.
					$search_query = " AND $key_column LIKE '%" . esc_sql( $s ) . "%' ";
				} elseif ( $search_key === 'discount' || $search_key === 'discount_code' || $search_key === 'dc' ) {
					$user_ids = $wpdb->get_col( "SELECT dcu.user_id FROM $wpdb->pmpro_discount_codes_uses dcu LEFT JOIN $wpdb->pmpro_discount_codes dc ON dcu.code_id = dc.id WHERE dc.code = '" . esc_sql( $s ) . "'" );
					if ( empty( $user_ids ) ) {
						$user_ids = array(0);	// Avoid warning, but ensure 0 results.
					}
					$search_query = " AND u.ID IN(" . implode( ",", $user_ids ) . ") ";					
				} elseif ( $search_key === 'subscription_transaction_id' ) {
					$search_query = " AND s.subscription_transaction_id LIKE '%" . esc_sql( $s ) . "%' AND mu.membership_id = s.membership_level_id AND mu.status = 'active' ";
				} else {
					$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '" . esc_sql( $search_key ) . "' AND meta_value LIKE '%" . esc_sql( $s ) . "%'" );
					if ( empty( $user_ids ) ) {
						$user_ids = array(0);	// Avoid warning, but ensure 0 results.
					}
					$search_query = " AND u.ID IN(" . implode( ",", $user_ids ) . ") ";
				}
			} elseif( function_exists( 'wp_is_large_user_count' ) && wp_is_large_user_count() ) {
				// Don't check user meta at all on big sites.
				$search_query = " AND ( u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR u.display_name LIKE '%" . esc_sql($s) . "%' ) ";
			} else {
				// Default search checks a few fields.
				$sqlQuery .= " LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id ";
				$search_query = " AND ( u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%' OR u.display_name LIKE '%" . esc_sql($s) . "%' OR ( s.subscription_transaction_id LIKE '%" . esc_sql( $s ) . "%' AND mu.membership_id = s.membership_level_id AND s.status = 'active' ) ) ";
			}
		}

		if ( 'oldmembers' === $l || 'expired' === $l || 'cancelled' === $l ) {
				$sqlQuery .= " LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";
		}

		$sqlQuery .= ' WHERE mu.membership_id > 0 ';

		if ( ! empty( $s ) ) {
			$sqlQuery .= $search_query;
		}

		if ( 'oldmembers' === $l ) {
			$sqlQuery .= " AND mu.status <> 'active' AND mu2.status IS NULL ";
		} elseif ( 'expired' === $l ) {
			$sqlQuery .= " AND mu.status = 'expired' AND mu2.status IS NULL ";
		} elseif ( 'cancelled' === $l ) {
			$sqlQuery .= " AND mu.status IN('cancelled', 'admin_cancelled') AND mu2.status IS NULL ";
		} elseif ( $l ) {
			$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . (int) $l . "' ";
		} else {
			$sqlQuery .= " AND mu.status = 'active' ";
		}

		if ( ! $count ) {
			$sqlQuery .= ' GROUP BY u.ID, mu.membership_id ';

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
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		$item = (array) apply_filters( 'pmpro_members_list_user', (object) $item );
		if ( isset( $item[ $column_name ] ) ) {
			// If the user is adding content via the "pmpro_members_list_user" filter.
			echo( esc_html( $item[ $column_name ] ) );
		} elseif ( 0 === strpos( $column_name, 'custom_field_' ) ) {
			// If the user is adding content via the "pmpro_memberslist_extra_cols_body" hook.
			// Re-implementing old hook, will be deprecated.
			$user_object = get_userdata( $item['ID'] );
			ob_start();
			do_action( 'pmpro_memberslist_extra_cols_body', $user_object );
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
			/**
			 * Fill in columns that don't have a built-in method.
			 *
			 * @param string $column_name The name of the column.
			 * @param int    $user_id     The ID of the user.
			 * @param array  $item        The membership data being shown.
			 */
			do_action( 'pmpro_manage_memberslist_custom_column', $column_name, $item['ID'], $item );
		}
	}

	/**
	 * Get value for ID column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_ID( $item ) {
		return $item['ID'];
	}

	/**
	 * Get value for username column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_username( $item ) {
		$avatar   = get_avatar( $item['ID'], 32 );
		
		if ( current_user_can( pmpro_get_edit_member_capability() ) ) {
			$userlink = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$item['ID'] ), admin_url( 'admin.php' ) ) ) . '">' . $item['user_login'] . '</a>';
		} else {
			// If the user can't edit members, don't link to the edit page.
			$userlink = $item['user_login'];
		}
		$userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, get_userdata( $item['ID'] ) );
		$output   = $avatar . ' <strong>' . $userlink . '</strong><br />';

		// Set up the hover actions for this user.
		$actions = array();

		if ( current_user_can( pmpro_get_edit_member_capability() ) ) {
			// Add the "Edit Member" action.
			$actions['editmember'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int)$item['ID'] ), admin_url( 'admin.php' ) ) ) . '">' . __( 'Edit Member', 'paid-memberships-pro' ) . '</a>';
		}

		if ( current_user_can( 'edit_users' ) ) {
			// Add the "Edit User" action.
			$actions['edituser'] = '<a href="' . esc_url( add_query_arg( array( 'user_id' => (int)$item['ID'] ), admin_url( 'user-edit.php' ) ) ) . '">' . __( 'Edit User', 'paid-memberships-pro' ) . '</a>';
		}

		$actions = apply_filters( 'pmpro_memberslist_user_row_actions', $actions, (object) $item );

		$action_count = count( $actions );
		$i = 0;
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
	}

	/**
	 * Get value for first name column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_first_name( $item ) {
		$user_object = get_userdata( $item['ID'] );
		return ( $user_object->first_name ?: '&#8212;' );
	}

	/**
	 * Get value for last name column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_last_name( $item ) {
		$user_object = get_userdata( $item['ID'] );
		return ( $user_object->last_name ?: '&#8212;' );
	}

	/**
	 * Get value for display_name column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed insdisplay_namee the column <td>.
	 */
	public function column_display_name( $item ) {
		return $item['display_name'];
	}

	/**
	 * Get value for user_email column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed insuser_emaile the column <td>.
	 */
	public function column_user_email( $item ) {
		return $item['user_email'];
	}

	/**
	 * Get value for membership column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed insmembershipe the column <td>.
	 */
	public function column_membership( $item ) {
		return $item['membership'];
	}

	/**
	 * Get value for membership_id column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed insmembership_ide the column <td>.
	 */
	public function column_membership_id( $item ) {
		return $item['membership_id'];
	}

	/**
	 * Get value for subscription column.
	 *
	 * @param object $item A row's data.
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_subscription( $item ) {
		// Check if we have subscriptions for this user.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $item['ID'], $item['membership_id'] );
		if ( ! empty( $subscriptions ) ) {
			// If the user has more than 1 subscription, show a warning message.
			if ( count( $subscriptions ) > 1 ) {
				?>
				<div class="pmpro_message pmpro_error">
					<p>
						<?php
						echo wp_kses_post( sprintf(
							// translators: %1$d is the number of subscriptions and %2$s is the link to view subscriptions.
							_n(
								'This user has %1$d active subscription for this level. %2$s',
								'This user has %1$d active subscriptions for this level. %2$s',
								count( $subscriptions ),
								'paid-memberships-pro'
							),
							count( $subscriptions ),
							sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $item['ID'], 'pmpro_member_edit_panel' => 'subscriptions' ), admin_url( 'admin.php' ) ) ),
								esc_html__( 'View Subscriptions', 'paid-memberships-pro' )
							)
						) ); ?>
					</p>
				</div>
				<?php
			}
			$subscription = $subscriptions[0];
			echo esc_html( $subscription->get_cost_text() );
			$actions = [
				'view'   => sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( add_query_arg( array( 'page' => 'pmpro-subscriptions', 'id' => $subscription->get_id() ), admin_url('admin.php' ) ) ),
					esc_html__( 'View Details', 'paid-memberships-pro' )
				)
			];

			$actions_html = [];

			foreach ( $actions as $action => $link ) {
				$actions_html[] = sprintf(
					'<span class="%1$s">%2$s</span>',
					esc_attr( $action ),
					$link
				);
			}

			if ( ! empty( $actions_html ) ) { ?>
				<div class="row-actions">
					<?php echo implode( ' | ', $actions_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php
			}
		} else {
			echo '&#8212;';
		}
	}

	/**
	 * Get value for joindate column.
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_joindate( $item ) {
		$joindate = $item[ 'joindate' ];
		if ( empty( $joindate ) ) {
			return;
		}
		return date_i18n( get_option( 'date_format' ), strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $joindate ) ) ) );
	}

	/**
	 * Get value for startdate column.
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_startdate( $item ) {
		$startdate = $item[ 'startdate' ];
		if ( empty( $startdate ) ) {
			return;
		}
		return date_i18n( get_option('date_format'), $startdate );
	}

	/**
	 * Get value for enddate column.
	 *
	 * @param object $item A row's data
	 * @return string Text to be placed inside the column <td>.
	 */
	public function column_enddate( $item ) {
		if ( isset( $_REQUEST['l'] ) && ! empty( pmpro_sanitize_with_safelist( $_REQUEST['l'] , array( 'oldmembers', 'expired', 'cancelled' ) ) ) ) {
			// If viewing removed levels, show the end date for the membership that was removed.
			return date_i18n( get_option( 'date_format' ), $item['enddate'] );
		}

		return pmpro_get_membership_expiration_text( $item['membership_id'], $item['ID'] );
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
			esc_html_e('Show', 'paid-memberships-pro' );?>
			<select name="l" onchange="jQuery('#current-page-selector').val('1'); jQuery('#member-list-form').trigger('submit');">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php esc_html_e('All Levels', 'paid-memberships-pro' );?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo esc_attr( $level->id ) ?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo esc_html( $level->name )?></option>
				<?php
					}
				?>
				<option value="cancelled" <?php if($l == "cancelled") { ?>selected="selected"<?php } ?>><?php esc_html_e('Cancelled Members', 'paid-memberships-pro' );?></option>
				<option value="expired" <?php if($l == "expired") { ?>selected="selected"<?php } ?>><?php esc_html_e('Expired Members', 'paid-memberships-pro' );?></option>
				<option value="oldmembers" <?php if($l == "oldmembers") { ?>selected="selected"<?php } ?>><?php esc_html_e('Old Members', 'paid-memberships-pro' );?></option>
			</select>
			<?php
			}
		if ( $which == 'bottom' ) {
			// The code that goes after the table is there
		}
	}
}
