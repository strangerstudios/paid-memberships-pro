<?php

/**
 * Set up a user taxonomy to track users who were created during the PMPro
 * checkout process but did not complete the checkout.
 *
 * @since 2.11
 */
function pmpro_abandoned_signups_taxonomy() {
	// Register the taxonomy.
	register_taxonomy(
		'pmpro_abandoned_signup',
		'user',
		array(
			'public'            => false,
			'default_term'      => array(
				'slug' => 'abandoned-signup',
			),
		)
	);
}
add_action( 'init', 'pmpro_abandoned_signups_taxonomy', 1 );

/**
 * Add the abandoned signup taxonomy to the user object when they are created during
 * the PMPro checkout process.
 *
 * @since 2.11
 *
 * @param int $user_id The user ID.
 */
function pmpro_set_abandoned_signup_taxonomy( $user_id ) {
	// Bail if the user ID is empty.
	if ( empty( $user_id ) ) {
		return;
	}

	// Add the abandoned signup taxonomy to the user.
	wp_set_object_terms( $user_id, 'abandoned-signup', 'pmpro_abandoned_signup' );
}
add_action( 'pmpro_checkout_before_user_auth', 'pmpro_set_abandoned_signup_taxonomy', 10 );

/**
 * After a checkout is complete, remove the abandoned signup taxonomy from the user.
 *
 * @since 2.11
 *
 * @param int $user_id The user ID.
 */
function pmpro_remove_abandoned_signup_taxonomy( $user_id ) {
	// Bail if the user ID is empty.
	if ( empty( $user_id ) ) {
		return;
	}

	// Bail if the user doesn't have the adbandoned signup taxonomy.
	if ( ! is_object_in_term( $user_id, 'pmpro_abandoned_signup', 'abandoned-signup' ) ) {		
		return;
	}

	// Remove the abandoned signup taxonomy from the user.
	wp_remove_object_terms( $user_id, 'abandoned-signup', 'pmpro_abandoned_signup' );
}
add_action( 'pmpro_after_checkout', 'pmpro_remove_abandoned_signup_taxonomy' );
add_action( 'deleted_user', 'pmpro_remove_abandoned_signup_taxonomy' );

/**
 * If a user loads any non-checkout page after their account is created
 * during the checkout process, remove the abandoned signup taxonomy from
 * the user.
 *
 * @since 2.11
 */
function pmpro_remove_abandoned_signup_taxonomy_on_page_load() {
	$user_id = get_current_user_id();
	
	// Bail if the user ID is empty.	
	if ( empty( $user_id ) ) {
		return;
	}

	// Bail if the user is on the checkout page.
	if ( pmpro_is_checkout() ) {
		return;
	}

	// Remove the abandoned signup taxonomy from the user.
	pmpro_remove_abandoned_signup_taxonomy( $user_id );
}
add_action( 'wp', 'pmpro_remove_abandoned_signup_taxonomy_on_page_load' );

/**
 * Get the number of days that an abandoned signup should be kept before deletion.
 *
 * @since TBD
 *
 * @return int The number of days to keep abandoned signups.
 */
function pmpro_get_abandoned_signup_deletion_days() {
	/**
	 * Filter the number of days that an abandoned signup should be kept before deletion.
	 *
	 * @since TBD
	 *
	 * @param int $days The number of days to keep abandoned signups. Default 30.
	 */
	$days = apply_filters( 'pmpro_abandoned_signup_deletion_days', 30 );

	return max( 1, absint( $days ) );
}

/**
 * Add the automatic abandoned signup deletion setting to the Advanced Settings page.
 *
 * @since TBD
 *
 * @param array $settings The custom Advanced Settings fields.
 * @return array The custom Advanced Settings fields.
 */
function pmpro_add_abandoned_signup_advanced_setting( $settings ) {
	$settings['auto_delete_abandoned_signups'] = array(
		'field_name'    => 'auto_delete_abandoned_signups',
		'field_type'    => 'select',
		'options'       => array(
			0 => __( 'No', 'paid-memberships-pro' ),
			1 => __( 'Yes', 'paid-memberships-pro' ),
		),
		'is_associative' => true,
		'label'         => __( 'Automatically Delete Abandoned Checkout Users', 'paid-memberships-pro' ),
		'description'   => sprintf(
			/* translators: %d: Number of days before an abandoned checkout user is deleted. */
			__( 'Permanently delete user accounts that were created during checkout and remain marked as abandoned for more than %d days. This cannot be undone.', 'paid-memberships-pro' ),
			pmpro_get_abandoned_signup_deletion_days()
		),
	);

	return $settings;
}
add_filter( 'pmpro_custom_advanced_settings', 'pmpro_add_abandoned_signup_advanced_setting' );

/**
 * Delete a batch of old abandoned signup users.
 *
 * @since TBD
 *
 * @return void
 */
function pmpro_delete_abandoned_signups() {
	global $wpdb;

	// This destructive cleanup must be explicitly enabled by the site administrator.
	if ( ! get_option( 'pmpro_auto_delete_abandoned_signups', 0 ) ) {
		return;
	}

	$abandoned_signup_term = get_term_by( 'slug', 'abandoned-signup', 'pmpro_abandoned_signup' );
	if ( empty( $abandoned_signup_term ) || is_wp_error( $abandoned_signup_term ) ) {
		return;
	}

	$deletion_days = pmpro_get_abandoned_signup_deletion_days();
	$cutoff_date   = gmdate( 'Y-m-d H:i:s', time() - ( $deletion_days * DAY_IN_SECONDS ) );
	$batch_size    = defined( 'PMPRO_CRON_LIMIT' ) ? absint( PMPRO_CRON_LIMIT ) : 250;

	if ( empty( $batch_size ) ) {
		return;
	}

	// Query only one batch so a large bot-driven spike cannot exhaust the scheduled action.
	// Users with any membership history are skipped as a safeguard, since this deletion cannot be undone.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT users.ID
			FROM {$wpdb->users} AS users
			INNER JOIN {$wpdb->term_relationships} AS relationships ON users.ID = relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS taxonomy ON relationships.term_taxonomy_id = taxonomy.term_taxonomy_id
			WHERE taxonomy.taxonomy = %s
				AND taxonomy.term_id = %d
				AND users.user_registered < %s
				AND NOT EXISTS ( SELECT 1 FROM {$wpdb->pmpro_memberships_users} AS memberships WHERE memberships.user_id = users.ID )
			ORDER BY users.ID ASC
			LIMIT %d",
			'pmpro_abandoned_signup',
			$abandoned_signup_term->term_id,
			$cutoff_date,
			$batch_size
		)
	);

	if ( empty( $user_ids ) ) {
		return;
	}

	// wp_delete_user() and wpmu_delete_user() are not loaded automatically during scheduled actions.
	require_once ABSPATH . 'wp-admin/includes/user.php';
	if ( is_multisite() ) {
		require_once ABSPATH . 'wp-admin/includes/ms.php';
	}

	foreach ( $user_ids as $user_id ) {
		$user_id = (int) $user_id;

		// Re-check right before deleting in case the user completed checkout after the query ran.
		if ( ! is_object_in_term( $user_id, 'pmpro_abandoned_signup', 'abandoned-signup' ) ) {
			continue;
		}

		// On multisite, wp_delete_user() only removes the user from the current site. Delete the account
		// from the network instead, unless the user also belongs to another site.
		if ( is_multisite() && count( get_blogs_of_user( $user_id, true ) ) <= 1 ) {
			wpmu_delete_user( $user_id );
		} else {
			wp_delete_user( $user_id, null );
		}
	}
}
add_action( 'pmpro_schedule_daily', 'pmpro_delete_abandoned_signups' );

/**
 * Filter the views on the Users page to include a view for abandoned signups.
 *
 * @since 2.11
 *
 * @param array $views The views on the Users page.
 */
function pmpro_add_users_table_view_abandoned_signups( $views ) {
	// Get the ID for the 'abandoned-signup' term.
	$abandoned_signup_term = get_term_by( 'slug', 'abandoned-signup', 'pmpro_abandoned_signup' );
	if ( empty( $abandoned_signup_term ) ) {
		return $views;
	}

	// Get all users with the 'abandoned-signup' term.
	$abandoned_signup_users = get_objects_in_term( $abandoned_signup_term->term_id, 'pmpro_abandoned_signup' );

	// If there are no users with the 'abandoned-signup' term, bail.
	if ( empty( $abandoned_signup_users ) ) {
		return $views;
	}

	// Add the view for abandoned signups.
	$views['pmpro-abandoned-signups'] = sprintf(
		'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
		esc_url( add_query_arg( 'pmpro-abandoned-signups', '1', admin_url( 'users.php' ) ) ),
		empty( $_REQUEST['pmpro-abandoned-signups'] ) ? '' : ' class="current"',
		__( 'Potential Spam Checkouts', 'paid-memberships-pro' ),
		count( $abandoned_signup_users )
	);
	
	return $views;
}
add_filter( 'views_users', 'pmpro_add_users_table_view_abandoned_signups' );

/**
 * If the users table is being filtered by abandoned signups, add the user registered
 * column to the table.
 *
 * @since 2.11
 *
 * @param array $columns The columns in the users table.
 * @return array The updated columns in the users table.
 */
function pmpro_add_users_table_user_registered_column( $columns ) {
	// Bail if we are not on the Users page or not filtering by abandoned signups.
	if ( empty( $_REQUEST['pmpro-abandoned-signups'] ) ) {
		return $columns;
	}

	// Add the registered column to the users table.
	$columns['user_registered'] = __( 'Registered', 'paid-memberships-pro' );
	return $columns;
}
add_filter( 'manage_users_columns', 'pmpro_add_users_table_user_registered_column' );

/**
 * Make the registered column sortable.
 */
function pmpro_make_users_table_user_registered_column_sortable( $columns ) {
	// Bail if we are not on the Users page or not filtering by abandoned signups.
	if ( empty( $_REQUEST['pmpro-abandoned-signups'] ) ) {
		return $columns;
	}

	// Make the registered column sortable.
	$columns['user_registered'] = 'user_registered';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'pmpro_make_users_table_user_registered_column_sortable' );

/**
 * Output the registered column for abandoned signups.
 *
 * @since 2.11
 *
 * @param string $output The output for the registered column.
 * @param string $column_name The name of the column.
 * @param int $user_id The user ID.
 * @return string The updated output for the registered column.
 */
function pmpro_add_users_table_user_registered_column_output( $output, $column_name, $user_id ) {
	// Bail if we are not on the Users page or not filtering by abandoned signups.
	if ( empty( $_REQUEST['pmpro-abandoned-signups'] ) ) {
		return $output;
	}

	// Bail if we are not on the registered column.
	if ( 'user_registered' !== $column_name ) {
		return $output;
	}

	// Get the user.
	$user = get_userdata( $user_id );

	// Get the registered date.
	$registered_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $user->user_registered ) );

	// Update the output for the registered column.
	$output = $registered_date;
	return $output;
}
add_filter( 'manage_users_custom_column', 'pmpro_add_users_table_user_registered_column_output', 10, 3 );

/**
 * Filter the users list table query args to only include users with the
 * 'abandoned-signup' term if the 'pmpro-abandoned-signups' query arg is set.
 *
 * @since 2.11
 *
 * @param array $query_args The query args for the users list table.
 * @return array The updated query args for the users list table.
 */
function pmpro_abandoned_signups_users_list_table_query_args( $query_args ) {    
	// Bail if we are not on the Users page or not filtering by abandoned signups.
	if ( empty( $_REQUEST['pmpro-abandoned-signups'] ) ) {
		return $query_args;
	}

	// Remove the role query arg so that we are querying all users.
	unset( $query_args['role'] );

	// Get the ID for the 'abandoned-signup' term.
	$abandoned_signup_term = get_term_by( 'slug', 'abandoned-signup', 'pmpro_abandoned_signup' );
	if ( empty( $abandoned_signup_term ) ) {
		return $query_args;
	}

	// Get all users with the 'abandoned-signup' term.
	$abandoned_signup_users = get_objects_in_term( $abandoned_signup_term->term_id, 'pmpro_abandoned_signup' );

	// Update the user query to only include users with the 'abandoned-signup' term.
	$query_args['include'] = $abandoned_signup_users;

	return $query_args;
}
add_action( 'users_list_table_query_args', 'pmpro_abandoned_signups_users_list_table_query_args' );

/**
 * Show a description on the Users page when filtering by abandoned signups.
 *
 * @since 2.11
 */
function pmpro_abandoned_signups_users_list_table_description() {
	global $current_screen;

	// Bail if we are not on the Users page or not filtering by abandoned signups.
	if ( ! is_admin() || empty( $current_screen->id ) || 'users' !== $current_screen->id || empty( $_REQUEST['pmpro-abandoned-signups'] ) ) {
		return;
	}

	// Get the ID for the 'abandoned-signup' term.
	$abandoned_signup_term = get_term_by( 'slug', 'abandoned-signup', 'pmpro_abandoned_signup' );
	if ( empty( $abandoned_signup_term ) ) {
		return;
	}

	// Get all users with the 'abandoned-signup' term.
	$abandoned_signup_users = get_objects_in_term( $abandoned_signup_term->term_id, 'pmpro_abandoned_signup' );

	// Bail if there are no users with the 'abandoned-signup' term.
	if ( empty( $abandoned_signup_users ) ) {
		return;
	}

	// Use JavaScript to add the description box to the users table.	
	?>
	<script>
		jQuery( document ).ready( function( $ ) {
			$( '.tablenav.top' ).after( '<div class="pmpro-abandoned-signup-description"><p><?php esc_html_e( "These are users who were created during the Paid Memberships Pro checkout process but haven't yet completed checkout or performed any other action on your site. You should periodically delete users from this list if the Registered date is more than a few days old.", 'paid-memberships-pro' ); ?></p></div>' );
		} );
	</script>
	<?php
}
add_action( 'admin_head', 'pmpro_abandoned_signups_users_list_table_description' );
