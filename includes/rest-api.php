<?php

if ( class_exists( 'WP_REST_Controller' ) ) {
	class PMPro_REST_API_Routes extends WP_REST_Controller {
		
		public function pmpro_rest_api_register_routes() {

			// ================ DEPRECATED ================ //
			$namespace = 'wp/v2';
			register_rest_route( $namespace, '/users/(?P<id>\d+)'.'/pmpro_membership_level' , 
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'pmpro_rest_api_get_membership_level_for_user' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),));
			
			register_rest_route( $namespace, '/posts/(?P<post_id>\d+)'.'/user_id/(?P<user_id>\d+)/pmpro_has_membership_access' , 
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'pmpro_rest_api_get_has_membership_access' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),));
			// ================================================  //

			$pmpro_namespace = 'pmpro/v1';

			/**
			 * Get user access for a specific post.
			 * @since 2.3
			 * Example: https://example.com/wp-json/pmpro/v1/has_membership_access
			 */
			register_rest_route( $pmpro_namespace, '/has_membership_access',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'pmpro_rest_api_get_has_membership_access'),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				)));

			/**
			 * Get a membership level for a user.
			 * @since 2.3
			 * Example: https://example.com/wp-json/pmpro/v1/get_membership_level_for_user
			 */
			 register_rest_route( $pmpro_namespace, '/get_membership_level_for_user', 
			 array(
				 array(
					 'methods'         => WP_REST_Server::READABLE,
					 'callback'        => array( $this, 'pmpro_rest_api_get_membership_level_for_user' ),
					 'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			 ),));

			 /**
			 * Get a membership level for a user.
			 * @since 2.3
			 * Example: https://example.com/wp-json/pmpro/v1/get_membership_levels_for_user
			 */
			 register_rest_route( $pmpro_namespace, '/get_membership_levels_for_user', 
			 array(
				 array(
					 'methods'         => WP_REST_Server::READABLE,
					 'callback'        => array( $this, 'pmpro_rest_api_get_membership_levels_for_user' ),
					 'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			 ),));


		/**
		 * Change a user's membership level. This also supports to cancel a membership if you pass through 0.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/change_membership_level
		 */
		register_rest_route( $pmpro_namespace, '/change_membership_level',
			array(
				array(
					'methods'	=> 'POST,PUT,PATCH',
					'callback'	=> array( $this, 'pmpro_rest_api_change_membership_level' ),
					'args'	=> array(
						'user_id' => array(),
						'level_id' => array(),
						'email' => array(),
						'first_name' => array(),
						'last_name' => array(),
						'user_url' => array(),
						'user_login' => array(),
						'description' => array(),
						'create_user' => array(),
					),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				)			
			)
		);

		/**
		 * Cancel a membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/cancel_membership_level
		 */
		register_rest_route( $pmpro_namespace, '/cancel_membership_level',
			array(
				array(
					'methods'	=> 'POST,PUT,PATCH',
					'callback'	=> array( $this, 'pmpro_rest_api_cancel_membership_level' ),
					'args'	=> array(
						'user_id' => array(),
						'level_id' => array()
					),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				)			
			)
		);

		/**
		 * Delete/Retrieve/Update/Create a Membership Level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/membership_level
		 */
		register_rest_route( $pmpro_namespace, '/membership_level' , 
		array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'pmpro_rest_api_get_membership_level' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),
			array(
				'methods'         => 'POST,PUT,PATCH',
				'callback'        => array( $this, 'pmpro_rest_api_set_membership_level' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),
			array(
				'methods' 		=> 'DELETE',
				'callback'        => array( $this, 'pmpro_rest_api_delete_membership_level' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			)
		));

		/**
		 * Create/Retrieve discount code.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/discount_code
		 */
		register_rest_route( $pmpro_namespace, '/discount_code', 
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'pmpro_rest_api_get_discount_code' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
			array(
				'methods' => 'POST,PUT,PATCH',
				'callback' => array( $this, 'pmpro_rest_api_set_discount_code' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
		));

		/**
		 * Retrieve an order.
		 * @since 2.8
		 * Example: https://example.com/wp-json/pmpro/v1/order
		 */
		register_rest_route( $pmpro_namespace, '/order', 
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'pmpro_rest_api_get_order' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
		));
		add_filter( 'pmpro_rest_api_permissions', array( $this, 'pmpro_rest_api_permissions_get_order' ), 10, 2 );

		/**
		 * Get membership level after checkout options are applied.
		 * @since 2.4
		 * Example: https://example.com/wp-json/pmpro/v1/checkout_level
		 */
		register_rest_route( $pmpro_namespace, '/checkout_level', 
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'pmpro_rest_api_get_checkout_level' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
		));

		/**
		 * Get membership levels after checkout options are applied.
		 * Example: https://example.com/wp-json/pmpro/v1/checkout_levels
		 */
		register_rest_route( $pmpro_namespace, '/checkout_levels', 
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'pmpro_rest_api_get_checkout_levels' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
		));

		/**
		 * Authentication route for Zapier integration.
		 *
		 * Used to do authentication when connecting Zapier to PMPro.
		 *
		 * @since 2.6.0
		 */
		register_rest_route( $pmpro_namespace, '/me', 
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'validate_me' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
				),
			)
		);

		/**
		 * Get the last couple of membership levels/members.
		 *
		 * @since 2.6.0
		 */	
		register_rest_route( $pmpro_namespace, '/recent_memberships',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'pmpro_rest_api_recent_memberships' ),
					'args'	=> array(
						'status' => array(),
					),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				)
		));

		register_rest_route( $pmpro_namespace, '/recent_orders',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'pmpro_rest_api_recent_orders' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				)
			)
		);
		}
		
		/**
		 * Get user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/get_membership_level_for_user?user_id=1
		 */
		function pmpro_rest_api_get_membership_level_for_user($request) {
			$params = $request->get_params();
			
			$user_id = isset( $params['user_id'] ) ? intval( $params['user_id'] ) : null;

			// Param id was used instead (old style endpoint).
			if ( empty( $user_id ) && !empty( $params['id'] ) ) {
				$user_id = intval( $params['id'] );
			}
			
			// Query by email.
			if ( empty( $user_id ) && !empty( $params['email'] ) ) {
				$user = get_user_by( 'email', sanitize_email( $params['email'] ) );
				$user_id = $user->ID;
			}
			
			if ( ! empty( $user_id ) ) {
				$level = pmpro_getMembershipLevelForUser( $user_id );
			} else {
				$level = false;
			}

			return new WP_REST_Response( $level, 200 );
		}

		/**
		 * Get user's membership levels. (MMPU)
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/get_membership_levels_for_user?user_id=1
		 */
		 function pmpro_rest_api_get_membership_levels_for_user($request) {
			$params = $request->get_params();
			
			$user_id = isset( $params['user_id'] ) ? intval( $params['user_id'] ) : null;

			// Param id was used instead.
			if ( empty( $user_id ) && !empty( $params['id'] ) ) {
				$user_id = intval( $params['id'] );
			}

			// Param email was used instead.
			if ( empty( $user_id ) && !empty( $params['email'] ) ) {
				$user = get_user_by( 'email', sanitize_email( $params['email'] ) );
				$user_id = $user->ID;
			}
			
			if ( ! empty( $user_id ) ) {
				$levels = pmpro_getMembershipLevelsForUser( $user_id );
			} else {
				$levels = false;
			}	

			return new WP_REST_Response( $levels, 200 );
		}
		
		/**
		 * Get user's access status for a specific post.
		 * @since 2.3
		 * Example: https://example.com/wp-json/wp/v2/posts/58/user_id/2/pmpro_has_membership_access
		 * Example: https://example.com/wp-json/pmpro/v1/has_membership_access?post_id=58&user_id=2
		 */
		function pmpro_rest_api_get_has_membership_access($request) {
			$params = $request->get_params();
			$post_id = isset( $params['post_id'] ) ? intval( $params['post_id'] ) : null;
			$user_id = isset( $params['user_id'] ) ? intval( $params['user_id'] ) : null;

			if ( empty( $user_id ) ) {
				// see if they sent an email
				if ( ! empty( $params['email'] ) ) {
					$user = get_user_by( 'email', sanitize_email( $params['email'] ) );
					$user_id = $user->ID;
				} else {
					return new WP_REST_Response( 'No user information passed through.', 404 );
				}
			}
			
			if ( ! empty( $user_id ) ) {
				$has_access = pmpro_has_membership_access( $post_id, $user_id );
			} else {
				// No good user, so say no.
				// Technically this will make public posts look restricted.
				$has_access = false;
			}
			
			return new WP_REST_Response( $has_access, 200 );
		}

		/**
		 * Change a user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/change_membership_level
		 */
		function pmpro_rest_api_change_membership_level( $request ) {
			$params = $request->get_params();
			$user_id = isset( $params['user_id'] ) ? (int) $params['user_id'] : null;
			$level_id = isset( $params['level_id'] ) ? (int) $params['level_id'] : null;
			$email = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : null;
			$first_name = isset( $params['first_name'] ) ? sanitize_text_field( $params['first_name'] ) : '';
			$last_name = isset( $params['last_name'] ) ? sanitize_text_field( $params['last_name'] ) : '';
			$username = isset( $params['user_login'] ) ? sanitize_text_field( $params['user_login'] ) : $email;
			$user_url = isset( $params['user_url'] ) ? sanitize_text_field( $params['user_url'] ) : '';
			$description = isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '';
			$create_user = isset( $params['create_user'] ) ? filter_var( $params['create_user'], FILTER_VALIDATE_BOOLEAN ) : false;
			$response_type = isset( $params['response_type'] ) ? sanitize_text_field( $params['response_type'] ) : null;

			if ( empty( $user_id ) ) {

				// see if they sent an email
				if ( ! empty( $email ) ) {

					$user = get_user_by( 'email', $email );
					
					// Assume the user doesn't already exist.
					if ( $create_user && ! $user ) {

						/**
						 * Filter the user data arguments for wp_insert_user when creating the user via the REST API.
						 * @since 2.7.4
						 * @param array $user_data An associative array with user arguments when creating the user. See https://developer.wordpress.org/reference/functions/wp_insert_user/ for reference.
						 */
						$user_data = apply_filters( 'pmpro_api_new_user_array', array(
								'user_pass' => wp_generate_password(),
								'user_email' => $email,
								'user_login' => $username,
								'first_name' => $first_name,
								'last_name' => $last_name,
								'user_url' => $user_url,
								'description' => $description
							)
						);

						$user_id = wp_insert_user( $user_data );
						
						if ( is_wp_error( $user_id ) ) {
							$error = $user_id->get_error_message();
							return new WP_REST_Response( $error, 500 ); // Assume it failed and return a 500 error occured like core WordPress.
						}
						
						pmpro_maybe_send_wp_new_user_notification( $user_id, $level_id );
					} else {
						$user_id = $user->ID;
					}
					
				} else {

					if ( 'json' === $response_type ) {
						wp_send_json_error( array( 'email' => $email, 'error' => 'No user information passed through.' ) );
					}
					return new WP_REST_Response( 'No user information passed through.', 404 );
				}
			}

			if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
				if ( 'json' === $response_type ) {
					wp_send_json_error( array( 'email' => $email, 'error' => 'Paid Memberships Pro function not found.' ) );
				}
				return new WP_REST_Response( 'Paid Memberships Pro function not found.', 404 );
			}

			// Make sure we have a user_id by now.
			if ( empty( $user_id ) ) {
				if ( 'json' === $response_type ) {
					wp_send_json_error( array( 'email' => $email, 'error' => 'No user found with that email address. Try the create_user parameter.' ) );
				}				
				return new WP_REST_Response( 'No user found with that email address. Try the create_user parameter.', 404 );
			}
			
			/**
			 * Filter to allow admin levels to be changed via the REST API or Zapier application.
			 * Defaults to false to prevent admin users from having their level changed via API.
			 * @since 2.7.4
			 * @param boolean $can_change_admin_users Should API calls change admin account membership levels.
			 */
			$can_change_admin_users = apply_filters( 'pmpro_api_change_membership_level_for_admin_users', false );
			if ( ! $can_change_admin_users && user_can( $user_id, 'manage_options' ) ) {
				if ( 'json' === $response_type ) {
					wp_send_json_error( array( 'email' => $email, 'error' => 'Sorry, you are not allowed to edit admin accounts.' ) );
				}
				return new WP_REST_Response( 'Sorry, you are not allowed to edit admin accounts.', 403 );
			}
						
			$response = pmpro_changeMembershipLevel( $level_id, $user_id );
						
			if ( 'json' === $response_type ) {
				wp_send_json_success( array( 'user_id' => $user_id, 'level_changed' => $level_id, 'response' => $response, 'status' => 200 ) );
			}
			return new WP_REST_Response( $response, 200 );
		}

		/**
		 * Cancel a user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/cancel_membership_level
		 */
		function pmpro_rest_api_cancel_membership_level( $request ) {
			$params = $request->get_params();
			$user_id = isset( $params['user_id'] ) ? intval( $params['user_id'] ) : null;
			$level_id = isset( $params['level_id'] ) ? intval( $params['level_id'] ) : null;
			$email = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : null;
			$response_type = isset( $params['response_type'] ) ? sanitize_text_field( $params['response_type'] ) : null;

			if ( empty( $user_id ) ) {
				// see if they sent an email
				if ( ! empty( $email ) ) {
					$user = get_user_by( 'email', $email );
					$user_id = $user->ID;
				} else {
					if ( 'json' === $response_type ) {
						wp_send_json_error( array( 'email' => $email ) );
					}

					return new WP_REST_Response( 'No user information passed through.', 404 );
				}
			}
			
			if ( empty( $level_id ) ) {
				if ( 'json' === $response_type ) {
					wp_send_json_error( array( 'email' => $email ) );
				}

				return new WP_REST_Response( 'No membership level ID data.', 400 );
			}

			if ( ! function_exists( 'pmpro_cancelMembershipLevel' ) ) {
				if ( 'json' === $response_type ) {
					wp_send_json_error( array( 'email' => $email ) );
				}

				return new WP_REST_Response( 'Paid Memberships Pro function not found.', 404 );
			}
			
			if ( ! empty( $user_id ) ) {
				$response = pmpro_cancelMembershipLevel( $level_id, $user_id, 'inactive' );
			} else {
				$response = false;
			}

			if ( 'json' === $response_type ) {
				wp_send_json_success( array( 'email' => $email ) );
			}

			return new WP_REST_Response( $response, 200 );
		}
		
		/**
		 * Endpoint to get membership level data
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/membership_level/
		 */
		function pmpro_rest_api_get_membership_level( $request ) {

			if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro level class not found.', 404 );
			}

			$params = $request->get_params();
			$id = isset( $params['id'] ) ? intval( $params['id'] ) : null;
			$response_type = isset( $params['response_type'] ) ? sanitize_text_field( $params['response_type'] ) : false;

			if ( empty( $id ) ) {
				return new WP_REST_Response( 'ID not passed through', 400 );
			}

			$level = new PMPro_Membership_Level( $id );
			
			// Hide confirmation message if not an admin or member.
			if ( ! empty( $level->confirmation ) 
				 && ! pmpro_hasMembershipLevel( $id )
				 && ! current_user_can( 'pmpro_edit_memberships' ) ) {				
					 $level->confirmation = '';					
			}

			if ( 'json' === $response_type ) {
				wp_send_json_success( array( 'level' => $level ) );
			}

			return new WP_REST_Response( $level, 200 );
		}

		/**
		 * Create/Update a Membership Level
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/membership_level/
		 */
		function pmpro_rest_api_set_membership_level( $request ) {

			if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro level class not found.', 404 );
			}

			$params = $request->get_params();
			$method = $request->get_method();

			$id = isset( $params['id'] ) ? intval( $params['id'] ) : '';
			$response_type = isset( $params['response_type'] ) ? sanitize_text_field( $params['response_type'] ) : null;

			// Pass through an ID only for PUT/PATCH methods. POST treats it as a brand new level.
			if ( ! empty( $id ) && ( $method === 'PUT' || $method === 'PATCH' ) ) {
				$level = new PMPro_Membership_Level( $id );
			} elseif ( empty( $id ) && ( $method === 'PUT' || $method === 'PATCH' ) ) {
				return false; // Error trying to update
			} elseif ( $method === 'POST' ) {
				$level = new PMPro_Membership_Level();
			}

			$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : $level->name;
			$description = isset( $params['description'] ) ? sanitize_text_field( $params['description'] ) : $level->description;
			$confirmation = isset( $params['confirmation'] ) ? sanitize_text_field( $params['confirmation'] ) : $level->confirmation;
			$initial_payment = isset( $params['initial_payment'] ) ? floatval( $params['initial_payment'] ) : $level->initial_payment;
			$billing_amount = isset( $params['billing_amount'] ) ? floatval( $params['billing_amount'] ) : $level->billing_amount;
			$cycle_number = isset( $params['cycle_number'] ) ? intval( $params['cycle_number'] ) : $level->cycle_number;
			$cycle_period = isset( $params['cycle_period'] ) ? pmpro_sanitize_period( $params['cycle_period'] ) : $level->cycle_period;
			$billing_limit = isset( $params['billing_limit'] ) ? sanitize_text_field( $params['billing_limit'] ) : $level->billing_limit;
			$trial_amount = isset( $params['trial_amount'] ) ? floatval( $params['trial_amount'] ) : $level->trial_amount;
			$trial_limit = isset( $params['trial_limit'] ) ? floatval( $params['trial_limit'] ) : $level->trial_limit;
			$allow_signups = isset( $params['allow_signups'] ) ? intval( $params['allow_signups'] ) : $level->allow_signups;
			$expiration_number = isset( $params['expiration_number'] ) ? intval( $params['expiration_number'] ) : $level->expiration_number;
			$expiration_period = isset( $params['expiration_period'] ) ? intval( $params['expiration_period'] ) : $level->expiration_period;
			$categories = isset( $params['categories'] ) ? PMPro_REST_API_Routes::pmpro_rest_api_convert_to_array( sanitize_text_field( $params['categories'] ) ) : $level->categories;
			
			// Set Level Object and save it.
			$level->name = $name;
			$level->description = $description;
			$level->confirmation = $confirmation;
			$level->initial_payment = $initial_payment;
			$level->billing_amount = $billing_amount;
			$level->cycle_number = $cycle_number;
			$level->billing_limit = $billing_limit;
			$level->trial_amount = $trial_amount;
			$level->allow_signups = $allow_signups;
			$level->expiration_number = $expiration_number;
			$level->expiration_period = $expiration_period;
			$level->categories = $categories;
			$level->save();	

			if ( 'json' === $response_type ) {
				wp_send_json_success( array( "level" => $level ) );
			}

			return new WP_REST_Response( $level, 200 );

		}

		/**
		 * Delete membership level and remove users from level. (And cancel their subscription.)
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/membership_level/
		 */
		function pmpro_rest_api_delete_membership_level( $request ) {

			if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro level class not found.', 404 );
			}

			$params = $request->get_params();
			$id = isset( $params['id'] ) ? intval( $params['id'] ) : '';

			if ( empty( $id ) ) {
				return new WP_REST_Response( 'ID not passed through.', 400 );
			}
			
			$level = new PMPro_Membership_Level( $id );

			return new WP_REST_Response( $level->delete(), 200 );
		}

		/**
		 * Get a discount code
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/discount_code
		 */
		function pmpro_rest_api_get_discount_code( $request ) {
			if ( ! class_exists( 'PMPro_Discount_Code' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro discount code class not found.', 404 );
			}

			$params = $request->get_params();
			$code = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : null;

			if ( empty( $code ) ) {
				return new WP_REST_Response( 'No discount code sent.', 400 );
			}

			return new WP_REST_Response( new PMPro_Discount_Code( $code ), 200 );
					
		}

		/**
		 * Create/update a discount code.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/discount_code
		 */
		function pmpro_rest_api_set_discount_code( $request ) {

			if ( ! class_exists( 'PMPro_Discount_Code' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro discount code class not found.', 404 );
			}

			$params = $request->get_params();
			$method = $request->get_method();
			$code = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';
			$uses = isset( $params['uses'] ) ? intval( $params['uses'] ) : '';
			$starts = isset( $params['starts'] ) ? sanitize_text_field( $params['starts'] ) : '';
			$expires = isset( $params['expires'] ) ? sanitize_text_field( $params['expires'] ) : '';
			$levels = isset( $params['levels'] ) ? sanitize_text_field( $params['levels'] ) : null;

			if ( ! empty( $levels ) ) {
				$levels = json_decode( $levels, true );

				if ( is_array( $levels ) ) {
					$levels_array = array();
					foreach( $levels as $level ){
						$levels_array[$level['level']] = array(
							'initial_payment' => isset( $level['initial_payment'] ) ? $level['initial_payment'] : null,
							'billing_amount' => isset( $level['billing_amount'] ) ? $level['billing_amount'] : null,
							'cycle_number' => isset( $level['cycle_number'] ) ? $level['cycle_number'] : null,
							'cycle_period' => isset( $level['cycle_period'] ) ? $level['cycle_period'] : null,
							'billing_limit' => isset( $level['cycle_period'] ) ? $level['cycle_period'] : null,
							'custom_trial' => isset( $level['custom_trial'] ) ? $level['custom_trial'] : null,
							'trial_amount' => isset( $level['trial_amount'] ) ? $level['trial_amount'] : null,
							'trial_limit' => isset( $level['trial_limit'] ) ? $level['trial_limit'] : null,
							'expiration_number' => isset( $level['expiration_number'] ) ?  : null,
							'expiration_period' => isset( $level['expiration_period'] ) ?  : null
						);
					}
				}
			}
			
			$discount_code = new PMPro_Discount_Code();

			// See if code already exists when POSTING.
			if ( $method == 'POST' && ! empty( $code ) ) {
				// See if discount code exists.
				if ( is_numeric( $code ) ) {
					$discount_code->get_discount_code_by_id( $code );
				} else {
					$discount_code->get_discount_code_by_code( $code );
				}

				if ( ! empty( $discount_code->id ) ) {
					return new WP_REST_Response( 'Discount code already exists.', 400 );
				}
			}

			$discount_code->code = $code;
			$discount_code->starts = $starts;
			$discount_code->expires = $expires;
			$discount_code->uses = $uses;
			$discount_code->levels = !empty( $levels_array ) ? $levels_array : $levels;
			$discount_code->save();

			return new WP_REST_Response( $discount_code, 200 );
		}

		/**
		 * Get an order.
		 * @since 2.8
		 * Example: https://example.com/wp-json/pmpro/v1/order
		 */
		function pmpro_rest_api_get_order( $request ) {
			if ( ! class_exists( 'MemberOrder' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro order class not found.', 404 );
			}

			$params = $request->get_params();
			$code   = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : null;

			if ( empty( $code ) ) {
				return new WP_REST_Response( 'No order code sent.', 400 );
			}

			// Build the order object to return.
			// Need to do this because the order object now has private properties.
			$order = new MemberOrder( $code );
			$order_data_to_return = new stdClass();
			if ( ! empty( $order->id ) ) {
				$order_data_to_return->id = $order->id;
				$order_data_to_return->code = $order->code;
				$order_data_to_return->user_id = $order->user_id;
				$order_data_to_return->membership_id = $order->membership_id;
				$order_data_to_return->billing = $order->billing;
				$order_data_to_return->subtotal = $order->subtotal;
				$order_data_to_return->tax = $order->tax;
				$order_data_to_return->total = $order->total;
				$order_data_to_return->payment_type = $order->payment_type;
				$order_data_to_return->cardtype = $order->cardtype;
				$order_data_to_return->accountnumber = $order->accountnumber;
				$order_data_to_return->expirationmonth = $order->expirationmonth;
				$order_data_to_return->expirationyear = $order->expirationyear;
				$order_data_to_return->status = $order->status;
				$order_data_to_return->gateway = $order->gateway;
				$order_data_to_return->gateway_environment = $order->gateway_environment;
				$order_data_to_return->payment_transaction_id = $order->payment_transaction_id;
				$order_data_to_return->subscription_transaction_id = $order->subscription_transaction_id;
				$order_data_to_return->timestamp = $order->timestamp;
				$order_data_to_return->affiliate_id = $order->affiliate_id;
				$order_data_to_return->affiliate_subid = $order->affiliate_subid;
				$order_data_to_return->notes = $order->notes;
				$order_data_to_return->checkout_id = $order->checkout_id;
			}

			return new WP_REST_Response( $order_data_to_return, 200 );
		}

		/**
		 * Make sure that users can GET their own orders.
		 * @since 2.8
		 */
		function pmpro_rest_api_permissions_get_order( $permission, $request ) {
			$method = $request->get_method();
			$route  = $request->get_route();

			// Check if the user does not have access but is trying to get an order.
			if ( ! $permission && 'GET' === $method && '/pmpro/v1/order' === $route ) {
				// Check if the order belongs to the user.
				$params = $request->get_params();
				$code   = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : null;

				if ( ! empty( $code ) ) {
					$order = new MemberOrder( $code );

					if ( $order->user_id == get_current_user_id() ) {
						return true;
					}
				}
			}
			return $permission;
		}

		/**
		 * Get a membership level at checkout.
		 * Note: Not compatible with MMPU.
		 * @since 2.4
		 * Example: https://example.com/wp-json/pmpro/v1/checkout_level
		 */
		function pmpro_rest_api_get_checkout_level( $request ) {
			$params = $request->get_params();

			if ( isset( $params['level_id'] ) ) {
				$level_id = intval( $params['level_id'] );
			} elseif ( isset( $params['level'] ) ) {
				$level_id = intval( $params['level'] );
			}

			if ( empty( $level_id ) ) {
				return new WP_REST_Response( 'No level found.', 400 );
			}

			$discount_code = isset( $params['discount_code'] ) ? sanitize_text_field( $params['discount_code'] ) : null;
			$checkout_level = pmpro_getLevelAtCheckout( $level_id, $discount_code );
			
			// Hide confirmation message if not an admin or member.
			if ( ! empty( $checkout_level->confirmation ) 
				 && ! pmpro_hasMembershipLevel( $level_id )
				 && ! current_user_can( 'pmpro_edit_memberships' ) ) {				
					 $checkout_level->confirmation = '';					
			}
			
			return new WP_REST_Response( $checkout_level );
		}

		/**
		 * Get membership levels at checkout.
		 * Example: https://example.com/wp-json/pmpro/v1/checkout_levels
		 */
		function pmpro_rest_api_get_checkout_levels( $request ) {
			$params = $request->get_params();

			global $pmpro_checkout_level_ids;
			if ( ! empty( $pmpro_checkout_level_ids ) ) {
				// MMPU Compatibility...
				$level_ids = $pmpro_checkout_level_ids;
			} elseif ( isset( $params['level_id'] ) ) {
				$level_ids = explode( '+', intval( $params['level_id'] ) );
			} elseif ( isset( $params['level'] ) ) {
				$level_ids = explode( '+', intval( $params['level'] ) );
			}

			if ( empty( $level_ids ) ) {
				return new WP_REST_Response( 'No levels found.', 400 );
			}
			$discount_code = isset( $params['discount_code'] ) ? sanitize_text_field( $params['discount_code'] ) : null;

			$r = array();
			$r['initial_payment'] = 0.00;
			foreach ( $level_ids as $level_id ) {
				$r[ $level_id ] = pmpro_getLevelAtCheckout( $level_id, $discount_code );
				if ( ! empty( $r[ $level_id ]->initial_payment ) ) {
					$r['initial_payment'] += floatval( $r[ $level_id ]->initial_payment );
				}
			}
			$r['initial_payment_formatted'] = pmpro_formatPrice( $r['initial_payment'] );
			return new WP_REST_Response( $r );
		}


		/// ZAPIER TRIGGERS
		/**
		 * Handle authentication testing for the Zapier API.
		 *
		 * @since 2.6.0
		 *
		 * @param WP_REST_Request $request The REST request.
		 */
		public function validate_me( $request ) {

			$params = $request->get_params();

			if ( is_user_logged_in() ) {
			  $me = wp_get_current_user()->display_name;
			} else {
				$me = false;
			}
		
			wp_send_json_success( array( 'username' => $me ) );
		}

		/**
		 * Handle requests for the list of recent memberships.
		 *
		 * @since 2.6.0
		 *
		 * @param WP_REST_Request $request The REST request.
		 *
		 * @return WP_REST_Response The REST response.
		 */
		public function pmpro_rest_api_recent_memberships( $request ) {
			$params = $request->get_params();
			if ( isset($params['limit']) ) {
				$limit = intval( $params['limit'] );
			} else {
				$limit = 1;
			}
			/**
			 * Allow filtering the total number of recent members to show in the /recent_memberships PMPro endpoint.
			 *
			 * @param int $limit The total number of recent members to show.
			 */
			$limit = apply_filters( 'pmpro_trigger_recent_members_limit', $limit );
			
			if ( empty( $params['level_status'] ) ) {
				$level_status = [ 'active' ];
			} else {
				$level_status = sanitize_text_field( trim( $params['level_status'] ) );

				// Force it into an array so we can implode it in the query itself.
				$level_status = explode( ',', $level_status );
			}

			// Set up values to prepare.
			$prepare   = $level_status;
			$prepare[] = $limit;

			// Set up the placeholders we want to use.
			$level_status_placeholders = implode( ', ', array_fill( 0, count( $level_status ), '%s' ) );

			// Grab the useful information.
			global $wpdb;

			$sql = "
				SELECT
					`mu`.`user_id` as `id`,
					`u`.`user_email`,
					`u`.`user_nicename`,
					`mu`.`membership_id`,
					`ml`.`name` as membership_name,
					`mu`.`status`,
					`mu`.`modified`
				FROM `{$wpdb->pmpro_memberships_users}` AS `mu`
				LEFT JOIN `{$wpdb->users}` AS `u`
					ON `mu`.`user_id` = `u`.`id`
				LEFT JOIN `{$wpdb->pmpro_membership_levels}` AS `ml`
					ON `ml`.`id` = `mu`.`membership_id`
				WHERE
					`mu`.`status` IN ( {$level_status_placeholders} ) 
				ORDER BY
					`mu`.`modified` DESC
				LIMIT %d
			";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $prepare ) );

			// Let's format the date to ISO8601
			$results[0]->modified = pmpro_format_date_iso8601( $results[0]->modified );

			return new WP_REST_Response( $results, 200 );

		}

		/**
		 * Handle requests for the list of recent orders.
		 *
		 * @since 2.6.0
		 *
		 * @param WP_REST_Request $request The REST request.
		 *
		 * @return WP_REST_Response The REST response.
		 */
		public function pmpro_rest_api_recent_orders( $request ) {
			$params = $request->get_params();
			
			if ( isset($params['limit']) ) {
				$orders_limit = intval( $params['limit'] );
			} else {
				$orders_limit = 1;
			}

			$limit = apply_filters( 'pmpro_trigger_recent_orders_limit', $orders_limit );

			global $wpdb;

			$sql = "
				SELECT
					`o`.`id`,
					`o`.`code`,
					`u`.`ID` AS `user_id`,
					`u`.`user_email`,
					`u`.`user_nicename`,
					`o`.`membership_id`,
					`o`.`billing_name`,
					`o`.`billing_street`,
					`o`.`billing_city`,
					`o`.`billing_state`,
					`o`.`billing_zip`,
					`o`.`billing_country`,
					`o`.`billing_phone`,
					`o`.`subtotal`,
					`o`.`tax`,
					`o`.`total`,
					`o`.`status`,
					`o`.`gateway`,
					`o`.`gateway_environment`,
					`o`.`timestamp`
				FROM `{$wpdb->pmpro_membership_orders}` AS `o`
				LEFT JOIN `{$wpdb->users}` AS `u`
					ON `o`.`user_id` = `u`.`ID`
				ORDER BY
					`o`.`timestamp` DESC
				LIMIT %d
			";
			
			$results = $wpdb->get_results( $wpdb->prepare( $sql, $limit ) );

			$results[0]->timestamp = pmpro_format_date_iso8601( $results[0]->timestamp );

			return new WP_REST_Response( $results, 200 );
		}

		/**
		 * Default permissions check for endpoints/routes.
		 * Defaults to 'subscriber' for all GET requests and 
		 * 'administrator' for any other type of request.
		 *
		 * @since 2.3
		 */
		 function pmpro_rest_api_get_permissions_check( $request ) {

			$method = $request->get_method();
			$route = $request->get_route();

			// Default to requiring pmpro_edit_memberships capability.
			$permission = current_user_can( 'pmpro_edit_memberships' );

			// Check other caps for some routes.
			$route_caps = array(
				'/pmpro/v1/has_membership_access' => 'pmpro_edit_memberships',
				'/pmpro/v1/get_membership_level_for_user' => 'pmpro_edit_memberships',
				'/pmpro/v1/get_membership_levels_for_user' => 'pmpro_edit_memberships',
				'/pmpro/v1/change_membership_level' => 'pmpro_edit_memberships',
				'/pmpro/v1/cancel_membership_level' => 'pmpro_edit_memberships',
				'/pmpro/v1/membership_level' => true,
				'/pmpro/v1/discount_code' => 'pmpro_discountcodes',
				'/pmpro/v1/order' => 'pmpro_orders',
				'/pmpro/v1/checkout_level' => true,
				'/pmpro/v1/checkout_levels' => true,
				'/pmpro/v1/me' => true,
				'/pmpro/v1/recent_memberships' => 'pmpro_edit_memberships',
				'/pmpro/v1/recent_orders' => 'pmpro_orders'
			);
			$route_caps = apply_filters( 'pmpro_rest_api_route_capabilities', $route_caps, $request );			
			
			if ( isset( $route_caps[$route] ) ) {
				if ( $route_caps[$route] === true ) {
					// public
					$permission = true;
				} else {									
					$permission = current_user_can( $route_caps[$route] );					
				}				
			}

			// Is the request method allowed? We disable DELETE by default.
			if ( ! in_array( $method, pmpro_get_rest_api_methods( $route ) ) ) {
				$permission = false;
			}

			$permission = apply_filters( 'pmpro_rest_api_permissions', $permission, $request );
			return $permission;
		}

		/** 
		 *	Helper function to convert comma separated items to an array.
		 * @since 2.3
		 */
		function pmpro_rest_api_convert_to_array( $string ) {
			return explode( ',', $string );
		}
	} // End of class

	/**
	 * Register the routes for Paid Memberships Pro.
	 * @since 2.3
	 */
	function pmpro_rest_api_register_custom_routes() {
		$pmpro_rest_api_routes = new PMPro_REST_API_Routes;
		$pmpro_rest_api_routes->pmpro_rest_api_register_routes();
	}

	add_action( 'rest_api_init', 'pmpro_rest_api_register_custom_routes', 5 );
}


/**
 * Get the allowed methods for PMPro REST API endpoints.
 * To enable DELETE, hook into this filter.
 * @since 2.3
 */
function pmpro_get_rest_api_methods( $route = NULL ) {
	$methods = array( 'GET', 'POST', 'PUT', 'PATCH' );
	$methods = apply_filters( 'pmpro_rest_api_methods', $methods, $route );
	return $methods;
}
