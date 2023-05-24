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
    var_dump( wp_set_object_terms( $user_id, 'abandoned-signup', 'pmpro_abandoned_signup' ) );
}
add_action( 'pmpro_checkout_before_user_auth', 'pmpro_set_abandoned_signup_taxonomy', 10 );

/**
 * After a checkout is complete, remove the abandoned signup taxonomy from the user.
 *
 * @param int $user_id The user ID.
 */
function pmpro_remove_abandoned_signup_taxonomy( $user_id ) {
	// Bail if the user ID is empty.
    if ( empty( $user_id ) ) {
        return;
    }

    // Remove the abandoned signup taxonomy from the user.
    wp_remove_object_terms( $user_id, 'abandoned-signup', 'pmpro_abandoned_signup' );
}
add_action( 'pmpro_after_checkout', 'pmpro_remove_abandoned_signup_taxonomy' );
add_action( 'deleted_user', 'pmpro_remove_abandoned_signup_taxonomy' );

/**
 * Admin page for the 'pmpro_abandoned_signup' taxonomy.
 *
 * @since TBD
 */
function pmpro_abandoned_signups_menu_item() {
    // Get the taxonomy.
    $tax = get_taxonomy( 'pmpro_abandoned_signup' );

    // Get the ID for the 'abandoned-signup' term.
    $abandoned_signup_term = get_term_by( 'slug', 'abandoned-signup', 'pmpro_abandoned_signup' );
    if ( empty( $abandoned_signup_term ) ) {
        return $query_args;
    }

    // Get all users with the 'abandoned-signup' term.
    $abandoned_signup_users = get_objects_in_term( $abandoned_signup_term->term_id, 'pmpro_abandoned_signup' );

    // If there are no users with the 'abandoned-signup' term, bail.
    if ( empty( $abandoned_signup_users ) ) {
        return;
    }

    add_users_page(
        esc_attr__( 'Incomplete Membership Checkouts', 'paid-memberships-pro' ),
        esc_attr__( 'Incomplete Membership Checkouts', 'paid-memberships-pro' ),
        $tax->cap->manage_terms,
        'users.php?pmpro-abandoned-signups=1'
    );
}
add_action( 'admin_menu', 'pmpro_abandoned_signups_menu_item' );

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
    // Bail if we are not on the Users page.
    if ( empty( $_REQUEST['pmpro-abandoned-signups'] ) ) {
        return $query_args;
    }

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