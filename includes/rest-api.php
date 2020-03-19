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
					'callback'        => array( $this, 'pmpro_rest_api_get_user_level' ),
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

			/**
			 * Get user access for a specific post.
			 * @since 2.3
			 * Example: https://example.com/wp-json/pmpro/v1/posts/58/user_id/1/pmpro_has_membership_access
			 */
			$pmpro_namespace = 'pmpro/v1';
			register_rest_route( $pmpro_namespace, '/posts/(?P<post_id>\d+)'.'/user_id/(?P<user_id>\d+)/pmpro_has_membership_access' , 
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'pmpro_rest_api_get_has_membership_access' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),));
			
			/**
			 * Get a membership level for a user.
			 * @since 2.3
			 * Example: https://example.com/wp-json/pmpro/v1/users/1/pmpro_membership_level 
			 */
			register_rest_route( $pmpro_namespace, '/users/(?P<id>\d+)'.'/pmpro_membership_level' , 
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'pmpro_rest_api_get_user_level' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),));

			/**
			 * Get/Delete a membership level (shorthand)
			 * @since 2.3
			 * Example: https://example.com/wp-json/pmpro/v1/membership_level/1
			 */
			register_rest_route( $pmpro_namespace, '/membership_level/(?P<id>\d+)' , 
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'pmpro_rest_api_get_membership_level' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),
				array(
					'methods' 		=> 'DELETE',
					'callback'        => array( $this, 'pmpro_rest_api_delete_membership_level' ),
					'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				)
		));

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
				'methods'         => 'GET,POST,PUT,PATCH',
				'callback'        => array( $this, 'pmpro_rest_api_set_membership_level' ),
				'args' => array(
					'id' => array(),
					'name' => array(),
					'description' => array(),
					'confirmation' => array(),
					'initial_payment' => array(),
					'billing_amount' => array(),
					'cycle_number' => array(),
					'billing_limit' => array(),
					'trial_amount' => array(),
					'trial_limit' => array(),
					'allow_signups' => array(),
					'expiration_number' => array(),
					'expiration_period' => array(),
					'categories' => array()
				),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
			),
			array(
				'methods' 		=> 'DELETE',
				'callback'        => array( $this, 'pmpro_rest_api_delete_membership_level' ),
				'permission_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' ),
				'args' => array(
					'id' => array(),
				)
			)
		));

		register_rest_route( $pmpro_namespace, '/discount_code', 
		array(
			array(
				'methods' => 'GET,POST,PUT,PATCH',
				'callback' => array( $this, 'pmpro_rest_api_discount_code' ),
				'permissions_callback' => array( $this, 'pmpro_rest_api_get_permissions_check' )
			),
		));

		}
		
		/**
		 * Get user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/wp/v2/users/2/pmpro_membership_level
		 */
		function pmpro_rest_api_get_user_level($request) {
			$params = $request->get_params();
			
			$user_id = $params['id'];
			
			$level = pmpro_getMembershipLevelForUser($user_id);
			if ( ! empty( $level ) ) {
				$level = (array)$level;
			}
			return new WP_REST_Response($level, 200 );
		}
		
		/**
		 * Get user's access status for a specific post.
		 * @since 2.3
		 * Example: https://example.com/wp-json/wp/v2/posts/58/user_id/2/pmpro_has_membership_access
		 */
		function pmpro_rest_api_get_has_membership_access($request) {
			$params = $request->get_params();
			$post_id = $params['post_id'];
			$user_id = $params['user_id'];
			
			$has_access = pmpro_has_membership_access($post_id, $user_id);
			return $has_access;
		}

		/**
		 * Change a user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/change_membership_level
		 */
		function pmpro_rest_api_change_membership_level( $request ) {
			$params = $request->get_params();
			$user_id = $params['user_id'];
			$level_id = $params['level_id'];

			if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
				return false;
			}

			return pmpro_changeMembershipLevel( $level_id, $user_id );
		}

		/**
		 * Cancel a user's membership level.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/cancel_membership_level
		 */
		function pmpro_rest_api_cancel_membership_level( $request ) {
			$params = $request->get_params();
			$user_id = $params['user_id'];
			$level_id = $params['level_id'];

			if ( ! function_exists( 'pmpro_cancelMembershipLevel' ) ) {
				return false;
			}

			return pmpro_cancelMembershipLevel( $level_id, $user_id, 'inactive' );
		}
		
		/**
		 * Endpoint to get membership level data
		 * @since 2.3
		 * Example: https://example.com/wp-json/wp/v2/pmpro/membership_level/1
		 */
		function pmpro_rest_api_get_membership_level( $request ) {

			if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
				return false;
			}
			
			$params = $request->get_params();
			$id = intval( $params['id'] );
			return new PMPro_Membership_Level( $id );
		}

		/**
		 * Create/Update a Membership Level
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/membership_level/
		 */
		function pmpro_rest_api_set_membership_level( $request ) {

			if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
				return false;
			}

			$params = $request->get_params();
			$method = $request->get_method();

			$id = isset( $params['id'] ) ? intval( $params['id'] ) : '';

				// Just return level object if method is GET, otherwise assume POST,PUT or PATCH.
			if ( $method === 'GET' ) {
				return new PMPro_Membership_Level( $id );
			} else {
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

			return $level;
			}

		}

		/** 
		 *	Helper function to convert comma separated items to an array.
		 * @since 2.3
		 */
		function pmpro_rest_api_convert_to_array( $string ) {
			return explode( ',', $string );
		}

		/**
		 * Delete membership level and remove users from level. (And cancel their subscription.)
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/membership_level/
		 */
		function pmpro_rest_api_delete_membership_level( $request ) {

			if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
				return false;
			}

			$params = $request->get_params();
			$id = intval( $params['id'] );
			
			$level = new PMPro_Membership_Level( $id );
			return $level->delete();
		}


		/**
		 * Retrieve/Create a discount code.
		 * @since 2.3
		 * Example: https://example.com/wp-json/pmpro/v1/discount_code
		 */
		function pmpro_rest_api_discount_code( $request ) {

			if ( ! class_exists( 'PMPro_Discount_Code' ) ) {
				return false;
			}

			$params = $request->get_params();
			$method = $request->get_method();
			$code = $params['code'];
			$uses = $params['uses'];
			$starts = $params['starts'];
			$levels = $params['levels'];

			// If it's a GET request, return the discount code object.
			if ( $method == 'GET' ) {
				return new PMPro_Discount_Code( $code );
			}

			if ( ! empty( $levels ) ) {
				$levels = json_decode( $levels, true );

				if ( is_array( $levels ) ) {
					$levels_array = array();
					foreach( $levels as $level ){
						$levels_array[$level['level']] = array(
							'initial_payment' => $level['initial_payment'],
							'billing_amount' => $level['billing_amount'],
							'cycle_number' => $level['cycle_number'],
							'cycle_period' => $level['cycle_period'],
							'billing_limit' => $level['cycle_period'],
							'custom_trial' => $level['custom_trial'],
							'trial_amount' => $level['trial_amount'],
							'trial_limit' => $level['trial_limit'],
							'expiration_number' => $level['expiration_number'],
							'expiration_period' => $level['expiration_period']
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
					return "Discount code already exists";
				}
			}

			$discount_code->code = isset( $code ) ? sanitize_text_field( $code ) : '';
			$discount_code->starts = isset( $starts ) ? sanitize_text_field( $starts ) : '';
			$discount_code->ends = isset( $ends ) ? sanitize_text_field( $ends ) : '';
			$discount_code->uses = isset( $uses ) ? intval( $uses ) : '';
			$discount_code->levels = !empty( $levels_array ) ? $levels_array : $levels;
			$discount_code->save();

			return $discount_code;
		}

		/**
		 * Default permissions check for endpoints/routes. Defaults to 'subscriber' for all GET requests and 'administrator' for any other type of request.
		 * @since 2.3
		 */
		 function pmpro_rest_api_get_permissions_check($request)	{

			// default permissions to 'read' (subscriber)
			$permissions = current_user_can('read');
			$method = $request->get_method();
			if ( $method != 'GET' ) {
				$permissions = current_user_can('pmpro_edit_memberships'); //Assume they can edit membership levels.
			}

			$permissions = apply_filters( 'pmpro_rest_api_permissions', $permissions, $request );

			return $permissions;
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