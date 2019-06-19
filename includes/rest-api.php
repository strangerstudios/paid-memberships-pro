<?php

if ( class_exists( 'WP_REST_Controller' ) ) {
	class PMPro_REST_API_Routes extends WP_REST_Controller {
		public function pmpro_rest_api_register_routes() {
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
		}
		
		//Ex:http://example.com/wp-json/wp/v2/users/2/pmpro_membership_level
		function pmpro_rest_api_get_user_level($request) {
			$params = $request->get_params();
			
			$user_id = $params['id'];
			
			$level = pmpro_getMembershipLevelForUser($user_id);
			if ( ! empty( $level ) ) {
				$level = (array)$level;
			}
			return new WP_REST_Response($level, 200 );
		}
		
		//Ex: http://example.com/wp-json/wp/v2/posts/58/user_id/2/pmpro_has_membership_access
		function pmpro_rest_api_get_has_membership_access($request) {
			$params = $request->get_params();
			$post_id = $params['post_id'];
			$user_id = $params['user_id'];
			
			$has_access = pmpro_has_membership_access($post_id, $user_id);
			return $has_access;
		}
		
		function pmpro_rest_api_get_permissions_check($request)	{
			return current_user_can('edit_users');
		}
	}

	function pmpro_rest_api_register_custom_routes() {
		$pmpro_rest_api_routes = new PMPro_REST_API_Routes;
		$pmpro_rest_api_routes->pmpro_rest_api_register_routes();
	}

	add_action( 'rest_api_init', 'pmpro_rest_api_register_custom_routes', 5 );
}