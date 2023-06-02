<?php

/**
 * Set up a user taxonomy to track users who were created during the PMPro
 * checkout process but did not complete the checkout.
 *
 * @since TBD
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
add_action( 'init', 'pmpro_abandoned_signups_taxonomy', 10 );

/**
 * Add the abandoned signup taxonomy to the user object when they are created during
 * the PMPro checkout process.
 *
 * @since TBD
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
 * @since TBD
 *
 * @param int $user_id The user ID.
 */
function pmpro_remove_abandoned_signup_taxonomy( $user_id ) {
	// Bail if the user ID is empty.
	if ( empty( $user_id ) ) {
		return;
	}

	// Bail if the user doesn't have the adbandoned signup taxonomy.
	if ( ! has_term( 'abandoned-signup', 'pmpro_abandoned_signup', $user_id ) ) {
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
 * @since TBD
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

	// Bail if the user doesn't have the adbandoned signup taxonomy.
	if ( ! has_term( 'abandoned-signup', 'pmpro_abandoned_signup', $user_id ) ) {
		return;
	}

	// The user did something after being created. Remove the abandoned signup taxonomy from the user.
	wp_remove_object_terms( $user_id, 'abandoned-signup', 'pmpro_abandoned_signup' );
}
add_action( 'wp', 'pmpro_remove_abandoned_signup_taxonomy_on_page_load' );

/**
 * Filter the views on the Users page to include a view for abandoned signups.
 *
 * @since TBD
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
		__( 'Incomplete Membership Checkouts', 'paid-memberships-pro' ),
		count( $abandoned_signup_users )
	);
	
	return $views;
}
add_filter( 'views_users', 'pmpro_add_users_table_view_abandoned_signups' );

/**
 * If the users table is being filtered by abandoned signups, add the user registered
 * column to the table.
 *
 * @since TBD
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
 * @since TBD
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
 * @since TBD
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