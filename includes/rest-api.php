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
						'level_id' => array()
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
				'permissions_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
			array(
				'methods' => 'POST,PUT,PATCH',
				'callback' => array( $this, 'pmpro_rest_api_set_discount_code' ),
				'permissions_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
		));

		}
		
		/**
		 * Get user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/get_membership_level_for_user?user_id=1
		 */
		function pmpro_rest_api_get_membership_level_for_user($request) {
			$params = $request->get_params();
			
			$user_id = isset( $params['user_id'] ) ? $params['user_id'] : null;

			if ( empty( $user_id ) && !empty( $params['email'] ) ) {
				$user = get_user_by_email( $params['email'] );
				$user_id = $user->ID;
			}
			
			$level = pmpro_getMembershipLevelForUser( $user_id );

			return new WP_REST_Response( $level, 200 );
		}

		/**
		 * Get user's membership levels. (MMPU)
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/get_membership_levels_for_user?user_id=1
		 */
		 function pmpro_rest_api_get_membership_levels_for_user($request) {
			$params = $request->get_params();
			
			$user_id = isset( $params['user_id'] ) ? $params['user_id'] : null;

			if ( empty( $user_id ) && !empty( $params['email'] ) ) {
				$user = get_user_by_email( $params['email'] );
				$user_id = $user->ID;
			}
			
			$levels = pmpro_getMembershipLevelsForUser( $user_id );

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
			$post_id = isset( $params['post_id'] ) ? $params['post_id'] : null;
			$user_id = isset( $params['user_id'] ) ? $params['user_id'] : null;

			if ( empty( $user_id ) ) {
				// see if they sent an email
				if ( ! empty( $params['email'] ) ) {
					$user = get_user_by_email( $params['email'] );
					$user_id = $user->ID;
				} else {
					return new WP_REST_Response( 'No user information passed through.', 404 );
				}
			}
			
			$has_access = pmpro_has_membership_access( $post_id, $user_id );
			return new WP_REST_Response( $has_access, 200 );
		}

		/**
		 * Change a user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/change_membership_level
		 */
		function pmpro_rest_api_change_membership_level( $request ) {
			$params = $request->get_params();
			$user_id = isset( $params['user_id'] ) ? $params['user_id'] : null;
			$level_id = isset( $params['level_id'] ) ? $params['level_id'] : null;

			if ( empty( $user_id ) ) {
				// see if they sent an email
				if ( ! empty( $params['email'] ) ) {
					$user = get_user_by_email( $params['email'] );
					$user_id = $user->ID;
				} else {
					return new WP_REST_Response( 'No user information passed through.', 404 );
				}
			}

			if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro function not found.', 404 );
			}

			return new WP_REST_Response( pmpro_changeMembershipLevel( $level_id, $user_id ), 200 );
		}

		/**
		 * Cancel a user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/cancel_membership_level
		 */
		function pmpro_rest_api_cancel_membership_level( $request ) {
			$params = $request->get_params();
			$user_id = isset( $params['user_id'] ) ? $params['user_id'] : null;
			$level_id = isset( $params['level_id'] ) ? $params['level_id'] : null;

			if ( empty( $user_id ) ) {
				// see if they sent an email
				if ( ! empty( $params['email'] ) ) {
					$user = get_user_by_email( $params['email'] );
					$user_id = $user->ID;
				} else {
					return new WP_REST_Response( 'No user information passed through.', 404 );
				}
			}
			
			if ( empty( $level_id ) ) {
				return new WP_REST_Response( 'No membership level ID data.', 400 );
			}

			if ( ! function_exists( 'pmpro_cancelMembershipLevel' ) ) {
				return new WP_REST_Response( 'Paid Memberships Pro function not found.', 404 );
			}

			return new WP_REST_Response( pmpro_cancelMembershipLevel( $level_id, $user_id, 'inactive' ), 200 );
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

			if ( empty( $id ) ) {
				return new WP_REST_Response( 'ID not passed through', 400 );
			}

			return new WP_REST_Response( new PMPro_Membership_Level( $id ), 200 );
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
			$cycle_period = isset( $params['cycle_period'] ) ? sanitize_text_field( $params['cycle_period'] ) : $level->cycle_period;
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
			$code = isset( $params['code'] ) ? $params['code'] : null;

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
			$levels = isset( $params['levels'] ) ? $params['levels'] : null;

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
			$discount_code->ends = $expires;
			$discount_code->uses = $uses;
			$discount_code->levels = !empty( $levels_array ) ? $levels_array : $levels;
			$discount_code->save();

			return new WP_REST_Response( $discount_code, 200 );
		}

		/**
		 * Default permissions check for endpoints/routes.
		 * Defaults to 'subscriber' for all GET requests and 
		 * 'administrator' for any other type of request.
		 *
		 * @since 2.3
		 */
		 function pmpro_rest_api_get_permissions_check($request) {

			$method = $request->get_method();
			$endpoint = $request->get_endpoint();
			
			// default permissions to 'read' (subscriber)
			$permissions = current_user_can('read');			
			if ( $method != 'GET' ) {
				$permissions = current_user_can('pmpro_edit_memberships'); //Assume they can edit membership levels.
			}

			// Is the request method allowed?
			if ( ! in_array( $method, pmpro_get_rest_api_methods( $endpoint ) ) ) {
				$permissions = false;
			}

			$permissions = apply_filters( 'pmpro_rest_api_permissions', $permissions, $request );

			return $permissions;
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
function pmpro_get_rest_api_methods( $endpoint = NULL ) {
	$methods = array( 'GET', 'POST', 'PUT', 'PATCH' );
	$methods = apply_filters( 'pmpro_rest_api_methods', $methods, $endpoint );
	return $methods;
}