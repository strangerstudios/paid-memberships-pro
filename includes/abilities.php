<?php
/**
 * Register Paid Memberships Pro abilities for the WordPress Abilities API.
 *
 * @package PaidMembershipsPro
 * @since 3.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_abilities_api_categories_init', 'pmpro_abilities_register_category' );
add_action( 'wp_abilities_api_init', 'pmpro_abilities_register_abilities' );

/**
 * Check whether the WordPress Abilities API is available.
 *
 * @since 3.8.3
 *
 * @return bool
 */
function pmpro_abilities_is_abilities_api_available() {
	return function_exists( 'wp_register_ability' )
		&& function_exists( 'wp_register_ability_category' )
		&& class_exists( 'WP_Ability' );
}

/**
 * Check whether PMPro can register abilities.
 *
 * @since 3.8.3
 *
 * @return bool
 */
function pmpro_abilities_can_boot() {
	/**
	 * Filter whether PMPro should register Abilities API categories and abilities.
	 *
	 * @since 3.8.3
	 *
	 * @param bool $enabled Whether ability registration is enabled.
	 */
	return pmpro_abilities_is_abilities_api_available()
		&& apply_filters( 'pmpro_enable_abilities_api', true );
}

/**
 * Register the PMPro ability category.
 *
 * @return void
 */
function pmpro_abilities_register_category() {
	if ( ! pmpro_abilities_can_boot() ) {
		return;
	}

	if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'pmpro' ) ) {
		return;
	}

	wp_register_ability_category(
		'pmpro',
		array(
			'label'       => __( 'Paid Memberships Pro', 'paid-memberships-pro' ),
			'description' => __( 'Membership management and commerce abilities provided by Paid Memberships Pro.', 'paid-memberships-pro' ),
		)
	);
}

/**
 * Register PMPro abilities.
 *
 * @return void
 */
function pmpro_abilities_register_abilities() {
	if ( ! pmpro_abilities_can_boot() ) {
		return;
	}

	$abilities = array(
		'pmpro/levels-query'              => pmpro_abilities_get_levels_query_definition(),
		'pmpro/member-memberships-get'    => pmpro_abilities_get_member_memberships_definition(),
		'pmpro/member-membership-change'  => pmpro_abilities_get_member_membership_change_definition(),
		'pmpro/member-membership-cancel'  => pmpro_abilities_get_member_membership_cancel_definition(),
		'pmpro/orders-query'              => pmpro_abilities_get_orders_query_definition(),
		'pmpro/order-get'                 => pmpro_abilities_get_order_get_definition(),
		'pmpro/subscriptions-query'       => pmpro_abilities_get_subscriptions_query_definition(),
		'pmpro/subscription-get'          => pmpro_abilities_get_subscription_get_definition(),
		'pmpro/search-query'              => pmpro_abilities_get_search_query_definition(),
	);

	foreach ( $abilities as $name => $args ) {
		wp_register_ability( $name, $args );
	}
}

/**
 * Get the shared public MCP meta.
 *
 * @param array $annotations Ability annotations.
 * @return array
 */
function pmpro_abilities_get_public_meta( $annotations = array() ) {
	return array(
		'show_in_rest' => true,
		'annotations'  => wp_parse_args(
			$annotations,
			array(
				'readonly'    => null,
				'destructive' => null,
				'idempotent'  => null,
			)
		),
		'mcp'          => array(
			'public' => true,
			'type'   => 'tool',
		),
	);
}

/**
 * Get the generic pagination schema.
 *
 * @param array $item_schema Item schema.
 * @return array
 */
function pmpro_abilities_get_list_output_schema( $item_schema ) {
	return array(
		'type'       => 'object',
		'properties' => array(
			'items'  => array(
				'type'  => 'array',
				'items' => $item_schema,
			),
			'total'  => array(
				'type' => 'integer',
			),
			'page'   => array(
				'type' => 'integer',
			),
			'limit'  => array(
				'type' => 'integer',
			),
		),
	);
}

/**
 * Get the normalized level schema.
 *
 * @return array
 */
function pmpro_abilities_get_level_schema() {
	return array(
		'type'       => 'object',
		'properties' => array(
			'id'                => array( 'type' => 'integer' ),
			'name'              => array( 'type' => 'string' ),
			'description'       => array( 'type' => 'string' ),
			'allow_signups'     => array( 'type' => 'boolean' ),
			'initial_payment'   => array( 'type' => 'number' ),
			'billing_amount'    => array( 'type' => 'number' ),
			'cycle_number'      => array( 'type' => 'integer' ),
			'cycle_period'      => array( 'type' => 'string' ),
			'billing_limit'     => array( 'type' => 'integer' ),
			'trial_amount'      => array( 'type' => 'number' ),
			'trial_limit'       => array( 'type' => 'integer' ),
			'expiration_number' => array( 'type' => 'integer' ),
			'expiration_period' => array( 'type' => 'string' ),
		),
	);
}

/**
 * Get the normalized member level schema.
 *
 * @return array
 */
function pmpro_abilities_get_member_level_schema() {
	return array(
		'type'       => 'object',
		'properties' => array(
			'id'                => array( 'type' => 'integer' ),
			'name'              => array( 'type' => 'string' ),
			'description'       => array( 'type' => 'string' ),
			'status'            => array( 'type' => 'string' ),
			'startdate'         => array( 'type' => array( 'string', 'null' ) ),
			'enddate'           => array( 'type' => array( 'string', 'null' ) ),
			'initial_payment'   => array( 'type' => 'number' ),
			'billing_amount'    => array( 'type' => 'number' ),
			'cycle_number'      => array( 'type' => 'integer' ),
			'cycle_period'      => array( 'type' => 'string' ),
			'billing_limit'     => array( 'type' => 'integer' ),
			'trial_amount'      => array( 'type' => 'number' ),
			'trial_limit'       => array( 'type' => 'integer' ),
			'expiration_number' => array( 'type' => 'integer' ),
			'expiration_period' => array( 'type' => 'string' ),
		),
	);
}

/**
 * Get the normalized order schema.
 *
 * @return array
 */
function pmpro_abilities_get_order_schema() {
	return array(
		'type'       => 'object',
		'properties' => array(
			'id'                  => array( 'type' => 'integer' ),
			'code'                => array( 'type' => 'string' ),
			'user_id'             => array( 'type' => 'integer' ),
			'membership_level_id' => array( 'type' => 'integer' ),
			'status'              => array( 'type' => 'string' ),
			'total'               => array( 'type' => 'number' ),
			'subtotal'            => array( 'type' => 'number' ),
			'tax'                 => array( 'type' => 'number' ),
			'gateway'             => array( 'type' => 'string' ),
			'gateway_environment' => array( 'type' => 'string' ),
			'timestamp'           => array( 'type' => array( 'string', 'null' ) ),
		),
	);
}

/**
 * Get the normalized subscription schema.
 *
 * @return array
 */
function pmpro_abilities_get_subscription_schema() {
	return array(
		'type'       => 'object',
		'properties' => array(
			'id'                  => array( 'type' => 'integer' ),
			'user_id'             => array( 'type' => 'integer' ),
			'membership_level_id' => array( 'type' => 'integer' ),
			'status'              => array( 'type' => 'string' ),
			'gateway'             => array( 'type' => 'string' ),
			'startdate'           => array( 'type' => array( 'string', 'null' ) ),
			'enddate'             => array( 'type' => array( 'string', 'null' ) ),
			'next_payment_date'   => array( 'type' => array( 'string', 'null' ) ),
			'billing_amount'      => array( 'type' => 'number' ),
			'cycle_number'        => array( 'type' => 'integer' ),
			'cycle_period'        => array( 'type' => 'string' ),
			'billing_limit'       => array( 'type' => 'integer' ),
			'trial_amount'        => array( 'type' => 'number' ),
			'trial_limit'         => array( 'type' => 'integer' ),
		),
	);
}

/**
 * Build the memberships query definition.
 *
 * @return array
 */
function pmpro_abilities_get_levels_query_definition() {
	return array(
		'label'               => __( 'Query Membership Levels', 'paid-memberships-pro' ),
		'description'         => __( 'Query Paid Memberships Pro membership levels with pagination and optional text filtering.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_levels_query',
		'permission_callback' => 'pmpro_abilities_can_query_levels',
		'input_schema'        => array(
			'type'       => 'object',
			'default'    => array(
				'include_hidden' => false,
				'query'          => '',
				'limit'          => 20,
				'page'           => 1,
			),
			'properties' => array(
				'include_hidden' => array(
					'type' => 'boolean',
				),
				'query'          => array(
					'type' => 'string',
				),
				'limit'          => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 50,
				),
				'page'           => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		),
		'output_schema'       => pmpro_abilities_get_list_output_schema( pmpro_abilities_get_level_schema() ),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the member memberships get definition.
 *
 * @return array
 */
function pmpro_abilities_get_member_memberships_definition() {
	return array(
		'label'               => __( 'Get Member Memberships', 'paid-memberships-pro' ),
		'description'         => __( 'Retrieve the current membership assignments for a specific user.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_member_memberships_get',
		'permission_callback' => 'pmpro_abilities_can_manage_members',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'user_id' => array(
					'type' => 'integer',
				),
			),
			'required'   => array( 'user_id' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'user_id'      => array( 'type' => 'integer' ),
				'memberships'  => array(
					'type'  => 'array',
					'items' => pmpro_abilities_get_member_level_schema(),
				),
				'membership_count' => array( 'type' => 'integer' ),
			),
		),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the member membership change definition.
 *
 * @return array
 */
function pmpro_abilities_get_member_membership_change_definition() {
	return array(
		'label'               => __( 'Change Member Membership', 'paid-memberships-pro' ),
		'description'         => __( 'Administratively reassign a membership level directly through PMPro without running checkout or payment collection.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_member_membership_change',
		'permission_callback' => 'pmpro_abilities_can_manage_members',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'user_id'          => array( 'type' => 'integer' ),
				'level_id'         => array( 'type' => 'integer' ),
				'old_level_status' => array( 'type' => 'string' ),
			),
			'required'   => array( 'user_id', 'level_id' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'success'              => array( 'type' => 'boolean' ),
				'status'               => array( 'type' => 'string' ),
				'user_id'              => array( 'type' => 'integer' ),
				'level_id'             => array( 'type' => 'integer' ),
				'previous_memberships' => array(
					'type'  => 'array',
					'items' => pmpro_abilities_get_member_level_schema(),
				),
				'current_memberships'  => array(
					'type'  => 'array',
					'items' => pmpro_abilities_get_member_level_schema(),
				),
				'message'              => array( 'type' => 'string' ),
			),
		),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => false,
				'destructive' => true,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the member membership cancel definition.
 *
 * @return array
 */
function pmpro_abilities_get_member_membership_cancel_definition() {
	return array(
		'label'               => __( 'Cancel Member Membership', 'paid-memberships-pro' ),
		'description'         => __( 'Administratively cancel a specific active membership level for a user.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_member_membership_cancel',
		'permission_callback' => 'pmpro_abilities_can_manage_members',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'user_id' => array( 'type' => 'integer' ),
				'level_id' => array( 'type' => 'integer' ),
			),
			'required'   => array( 'user_id', 'level_id' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'success'              => array( 'type' => 'boolean' ),
				'status'               => array( 'type' => 'string' ),
				'user_id'              => array( 'type' => 'integer' ),
				'level_id'             => array( 'type' => 'integer' ),
				'previous_memberships' => array(
					'type'  => 'array',
					'items' => pmpro_abilities_get_member_level_schema(),
				),
				'current_memberships'  => array(
					'type'  => 'array',
					'items' => pmpro_abilities_get_member_level_schema(),
				),
				'message'              => array( 'type' => 'string' ),
			),
		),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => false,
				'destructive' => true,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the orders query definition.
 *
 * @return array
 */
function pmpro_abilities_get_orders_query_definition() {
	return array(
		'label'               => __( 'Query Orders', 'paid-memberships-pro' ),
		'description'         => __( 'Query PMPro orders with structured filters and pagination.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_orders_query',
		'permission_callback' => 'pmpro_abilities_can_manage_orders',
		'input_schema'        => array(
			'type'       => 'object',
			'default'    => array(
				'limit' => 20,
				'page'  => 1,
			),
			'properties' => array(
				'user_id'             => array( 'type' => 'integer' ),
				'membership_level_id' => array( 'type' => 'integer' ),
				'status'              => array( 'type' => 'string' ),
				'gateway'             => array( 'type' => 'string' ),
				'limit'               => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 50,
				),
				'page'                => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		),
		'output_schema'       => pmpro_abilities_get_list_output_schema( pmpro_abilities_get_order_schema() ),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the order get definition.
 *
 * @return array
 */
function pmpro_abilities_get_order_get_definition() {
	return array(
		'label'               => __( 'Get Order', 'paid-memberships-pro' ),
		'description'         => __( 'Retrieve a single PMPro order by internal ID or order code.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_order_get',
		'permission_callback' => 'pmpro_abilities_can_manage_orders',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'order_id'   => array( 'type' => 'integer' ),
				'order_code' => array( 'type' => 'string' ),
			),
		),
		'output_schema'       => pmpro_abilities_get_order_schema(),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the subscriptions query definition.
 *
 * @return array
 */
function pmpro_abilities_get_subscriptions_query_definition() {
	return array(
		'label'               => __( 'Query Subscriptions', 'paid-memberships-pro' ),
		'description'         => __( 'Query PMPro subscriptions with safe, redacted outputs suitable for REST and MCP surfaces.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_subscriptions_query',
		'permission_callback' => 'pmpro_abilities_can_manage_members',
		'input_schema'        => array(
			'type'       => 'object',
			'default'    => array(
				'limit' => 20,
				'page'  => 1,
			),
			'properties' => array(
				'user_id'             => array( 'type' => 'integer' ),
				'membership_level_id' => array( 'type' => 'integer' ),
				'status'              => array( 'type' => 'string' ),
				'gateway'             => array( 'type' => 'string' ),
				'limit'               => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 50,
				),
				'page'                => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		),
		'output_schema'       => pmpro_abilities_get_list_output_schema( pmpro_abilities_get_subscription_schema() ),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the subscription get definition.
 *
 * @return array
 */
function pmpro_abilities_get_subscription_get_definition() {
	return array(
		'label'               => __( 'Get Subscription', 'paid-memberships-pro' ),
		'description'         => __( 'Retrieve a single PMPro subscription using a privacy-filtered output contract.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_subscription_get',
		'permission_callback' => 'pmpro_abilities_can_manage_members',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'subscription_id' => array( 'type' => 'integer' ),
			),
			'required'   => array( 'subscription_id' ),
		),
		'output_schema'       => pmpro_abilities_get_subscription_schema(),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Build the search query definition.
 *
 * @return array
 */
function pmpro_abilities_get_search_query_definition() {
	return array(
		'label'               => __( 'Search PMPro Data', 'paid-memberships-pro' ),
		'description'         => __( 'Search PMPro users, subscriptions, orders, reports, levels, and discount codes using the same scoped buckets as the PMPro quick-search UI.', 'paid-memberships-pro' ),
		'category'            => 'pmpro',
		'execute_callback'    => 'pmpro_abilities_execute_search_query',
		'permission_callback' => 'pmpro_abilities_can_run_search_query',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'query' => array(
					'type' => 'string',
				),
				'scope' => array(
					'type' => 'string',
					'enum' => array( 'all', 'users', 'subscriptions', 'orders', 'reports', 'levels', 'discount_codes' ),
				),
				'limit' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 20,
				),
			),
			'required'   => array( 'query', 'scope' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'query'   => array( 'type' => 'string' ),
				'scope'   => array( 'type' => 'string' ),
				'results' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'scope' => array( 'type' => 'string' ),
							'label' => array( 'type' => 'string' ),
							'items' => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'id'          => array( 'type' => 'string' ),
										'title'       => array( 'type' => 'string' ),
										'url'         => array( 'type' => 'string' ),
										'entity_type' => array( 'type' => 'string' ),
										'summary'     => array( 'type' => 'object' ),
									),
								),
							),
						),
					),
				),
			),
		),
		'meta'                => pmpro_abilities_get_public_meta(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			)
		),
	);
}

/**
 * Check member-management permissions.
 *
 * @return bool
 */
function pmpro_abilities_can_manage_members() {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return false;
	}

	$capability = function_exists( 'pmpro_get_edit_member_capability' ) ? pmpro_get_edit_member_capability() : 'pmpro_edit_members';

	return current_user_can( $capability ) || current_user_can( 'manage_options' );
}

/**
 * Check level-management permissions.
 *
 * @return bool
 */
function pmpro_abilities_can_query_levels() {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return false;
	}

	return current_user_can( 'pmpro_membershiplevels' ) || current_user_can( 'manage_options' );
}

/**
 * Check order-management permissions.
 *
 * @return bool
 */
function pmpro_abilities_can_manage_orders() {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return false;
	}

	return current_user_can( 'pmpro_orders' ) || current_user_can( 'manage_options' );
}

/**
 * Check search permissions based on requested scope.
 *
 * @param array $input Search input.
 * @return bool
 */
function pmpro_abilities_can_run_search_query( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return false;
	}

	$scope = isset( $input['scope'] ) ? $input['scope'] : 'all';

	$scope_map = array(
		'users'          => pmpro_abilities_can_manage_members(),
		'subscriptions'  => pmpro_abilities_can_manage_members(),
		'orders'         => pmpro_abilities_can_manage_orders(),
		'reports'        => current_user_can( 'pmpro_reports' ) || current_user_can( 'manage_options' ),
		'levels'         => current_user_can( 'pmpro_membershiplevels' ) || current_user_can( 'manage_options' ),
		'discount_codes' => current_user_can( 'pmpro_discountcodes' ) || current_user_can( 'manage_options' ),
	);

	if ( 'all' === $scope ) {
		return in_array( true, array_values( $scope_map ), true );
	}

	return ! empty( $scope_map[ $scope ] );
}

/**
 * Normalize an input array with defaults.
 *
 * @param array $input    Ability input.
 * @param array $defaults Default values.
 * @return array
 */
function pmpro_abilities_parse_input( $input, $defaults = array() ) {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	return wp_parse_args( $input, $defaults );
}

/**
 * Paginate an array of items.
 *
 * @param array $items Items.
 * @param int   $page  Page number.
 * @param int   $limit Page size.
 * @return array
 */
function pmpro_abilities_slice_items( array $items, $page, $limit ) {
	$offset = max( 0, ( $page - 1 ) * $limit );
	return array_slice( $items, $offset, $limit );
}

/**
 * Normalize a UNIX timestamp to ISO 8601.
 *
 * @param mixed $timestamp Timestamp.
 * @return string|null
 */
function pmpro_abilities_normalize_unix_timestamp( $timestamp ) {
	if ( empty( $timestamp ) || ! is_numeric( $timestamp ) ) {
		return null;
	}

	return gmdate( 'c', (int) $timestamp );
}

/**
 * Normalize a membership level object.
 *
 * @param object $level Level object.
 * @return array
 */
function pmpro_abilities_normalize_level( $level ) {
	return array(
		'id'                => isset( $level->id ) ? (int) $level->id : 0,
		'name'              => isset( $level->name ) ? (string) $level->name : '',
		'description'       => isset( $level->description ) ? wp_strip_all_tags( $level->description ) : '',
		'allow_signups'     => ! empty( $level->allow_signups ),
		'initial_payment'   => isset( $level->initial_payment ) ? (float) $level->initial_payment : 0.0,
		'billing_amount'    => isset( $level->billing_amount ) ? (float) $level->billing_amount : 0.0,
		'cycle_number'      => isset( $level->cycle_number ) ? (int) $level->cycle_number : 0,
		'cycle_period'      => isset( $level->cycle_period ) ? (string) $level->cycle_period : '',
		'billing_limit'     => isset( $level->billing_limit ) ? (int) $level->billing_limit : 0,
		'trial_amount'      => isset( $level->trial_amount ) ? (float) $level->trial_amount : 0.0,
		'trial_limit'       => isset( $level->trial_limit ) ? (int) $level->trial_limit : 0,
		'expiration_number' => isset( $level->expiration_number ) ? (int) $level->expiration_number : 0,
		'expiration_period' => isset( $level->expiration_period ) ? (string) $level->expiration_period : '',
	);
}

/**
 * Normalize a member level assignment.
 *
 * @param object $membership Membership object.
 * @return array
 */
function pmpro_abilities_normalize_member_level( $membership ) {
	return array(
		'id'                => isset( $membership->id ) ? (int) $membership->id : 0,
		'name'              => isset( $membership->name ) ? (string) $membership->name : '',
		'description'       => isset( $membership->description ) ? wp_strip_all_tags( $membership->description ) : '',
		'status'            => isset( $membership->status ) ? (string) $membership->status : 'active',
		'startdate'         => isset( $membership->startdate ) ? pmpro_abilities_normalize_unix_timestamp( $membership->startdate ) : null,
		'enddate'           => isset( $membership->enddate ) ? pmpro_abilities_normalize_unix_timestamp( $membership->enddate ) : null,
		'initial_payment'   => isset( $membership->initial_payment ) ? (float) $membership->initial_payment : 0.0,
		'billing_amount'    => isset( $membership->billing_amount ) ? (float) $membership->billing_amount : 0.0,
		'cycle_number'      => isset( $membership->cycle_number ) ? (int) $membership->cycle_number : 0,
		'cycle_period'      => isset( $membership->cycle_period ) ? (string) $membership->cycle_period : '',
		'billing_limit'     => isset( $membership->billing_limit ) ? (int) $membership->billing_limit : 0,
		'trial_amount'      => isset( $membership->trial_amount ) ? (float) $membership->trial_amount : 0.0,
		'trial_limit'       => isset( $membership->trial_limit ) ? (int) $membership->trial_limit : 0,
		'expiration_number' => isset( $membership->expiration_number ) ? (int) $membership->expiration_number : 0,
		'expiration_period' => isset( $membership->expiration_period ) ? (string) $membership->expiration_period : '',
	);
}

/**
 * Normalize an order object.
 *
 * @param MemberOrder $order Order object.
 * @return array
 */
function pmpro_abilities_normalize_order( $order ) {
	return array(
		'id'                  => (int) $order->id,
		'code'                => (string) $order->code,
		'user_id'             => (int) $order->user_id,
		'membership_level_id' => (int) $order->membership_id,
		'status'              => (string) $order->status,
		'total'               => (float) $order->total,
		'subtotal'            => (float) $order->subtotal,
		'tax'                 => (float) $order->tax,
		'gateway'             => (string) $order->gateway,
		'gateway_environment' => (string) $order->gateway_environment,
		'timestamp'           => (int) $order->timestamp > 0 ? gmdate( 'c', (int) $order->timestamp ) : null,
	);
}

/**
 * Normalize a subscription object.
 *
 * @param PMPro_Subscription $subscription Subscription object.
 * @return array
 */
function pmpro_abilities_normalize_subscription( $subscription ) {
	return array(
		'id'                  => (int) $subscription->get_id(),
		'user_id'             => (int) $subscription->get_user_id(),
		'membership_level_id' => (int) $subscription->get_membership_level_id(),
		'status'              => (string) $subscription->get_status(),
		'gateway'             => (string) $subscription->get_gateway(),
		'startdate'           => $subscription->get_startdate( 'c' ),
		'enddate'             => $subscription->get_enddate( 'c' ),
		'next_payment_date'   => $subscription->get_next_payment_date( 'c' ),
		'billing_amount'      => (float) $subscription->get_billing_amount(),
		'cycle_number'        => (int) $subscription->get_cycle_number(),
		'cycle_period'        => (string) $subscription->get_cycle_period(),
		'billing_limit'       => (int) $subscription->get_billing_limit(),
		'trial_amount'        => (float) $subscription->get_trial_amount(),
		'trial_limit'         => (int) $subscription->get_trial_limit(),
	);
}

/**
 * Validate and fetch a user.
 *
 * @param int $user_id User ID.
 * @return WP_User|WP_Error
 */
function pmpro_abilities_get_user_or_error( $user_id ) {
	$user = get_userdata( (int) $user_id );

	if ( ! $user ) {
		return new WP_Error(
			'pmpro_abilities_user_not_found',
			__( 'User not found.', 'paid-memberships-pro' ),
			array( 'status' => 404 )
		);
	}

	return $user;
}

/**
 * Execute memberships query.
 *
 * @param array $input Ability input.
 * @return array
 */
function pmpro_abilities_execute_levels_query( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$input          = pmpro_abilities_parse_input(
		$input,
		array(
			'include_hidden' => false,
			'query'          => '',
			'limit'          => 20,
			'page'           => 1,
		)
	);
	$include_hidden = ! empty( $input['include_hidden'] );
	$query          = strtolower( trim( (string) $input['query'] ) );
	$limit          = max( 1, min( 50, (int) $input['limit'] ) );
	$page           = max( 1, (int) $input['page'] );
	$levels         = array_values( pmpro_getAllLevels( $include_hidden, true ) );

	if ( '' !== $query ) {
		$levels = array_values(
			array_filter(
				$levels,
				static function( $level ) use ( $query ) {
					$haystack = strtolower( wp_strip_all_tags( trim( $level->name . ' ' . $level->description ) ) );
					return false !== strpos( $haystack, $query );
				}
			)
		);
	}

	$total = count( $levels );
	$items = array_map( 'pmpro_abilities_normalize_level', pmpro_abilities_slice_items( $levels, $page, $limit ) );

	return array(
		'items' => $items,
		'total' => $total,
		'page'  => $page,
		'limit' => $limit,
	);
}

/**
 * Execute member memberships get.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_member_memberships_get( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$user = pmpro_abilities_get_user_or_error( (int) $input['user_id'] );
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$memberships = pmpro_getMembershipLevelsForUser( $user->ID, true );
	if ( ! is_array( $memberships ) ) {
		$memberships = array();
	}

	return array(
		'user_id'          => (int) $user->ID,
		'memberships'      => array_map( 'pmpro_abilities_normalize_member_level', $memberships ),
		'membership_count' => count( $memberships ),
	);
}

/**
 * Execute membership change.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_member_membership_change( $input ) {
	global $pmpro_error;

	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$user = pmpro_abilities_get_user_or_error( (int) $input['user_id'] );
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$level = pmpro_getLevel( (int) $input['level_id'] );
	if ( empty( $level ) ) {
		return new WP_Error( 'pmpro_abilities_level_not_found', __( 'Membership level not found.', 'paid-memberships-pro' ), array( 'status' => 404 ) );
	}

	$previous_memberships = pmpro_getMembershipLevelsForUser( $user->ID, true );
	if ( ! is_array( $previous_memberships ) ) {
		$previous_memberships = array();
	}

	$old_level_status = ! empty( $input['old_level_status'] ) ? (string) $input['old_level_status'] : 'admin_changed';
	$result           = pmpro_changeMembershipLevel( (int) $level->id, (int) $user->ID, $old_level_status );

	$current_memberships = pmpro_getMembershipLevelsForUser( $user->ID, true );
	if ( ! is_array( $current_memberships ) ) {
		$current_memberships = array();
	}

	if ( false === $result ) {
		return new WP_Error(
			'pmpro_abilities_membership_change_failed',
			! empty( $pmpro_error ) ? $pmpro_error : __( 'PMPro could not change the membership level.', 'paid-memberships-pro' ),
			array( 'status' => 400 )
		);
	}

	$status  = null === $result ? 'unchanged' : 'changed';
	$message = 'unchanged' === $status
		? __( 'The user already has the requested membership level.', 'paid-memberships-pro' )
		: __( 'Membership level changed successfully. PMPro side effects and integrations were triggered.', 'paid-memberships-pro' );

	return array(
		'success'              => true,
		'status'               => $status,
		'user_id'              => (int) $user->ID,
		'level_id'             => (int) $level->id,
		'previous_memberships' => array_map( 'pmpro_abilities_normalize_member_level', $previous_memberships ),
		'current_memberships'  => array_map( 'pmpro_abilities_normalize_member_level', $current_memberships ),
		'message'              => $message,
	);
}

/**
 * Execute membership cancel.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_member_membership_cancel( $input ) {
	global $pmpro_error;

	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$user = pmpro_abilities_get_user_or_error( (int) $input['user_id'] );
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$level = pmpro_getLevel( (int) $input['level_id'] );
	if ( empty( $level ) ) {
		return new WP_Error( 'pmpro_abilities_level_not_found', __( 'Membership level not found.', 'paid-memberships-pro' ), array( 'status' => 404 ) );
	}

	$previous_memberships = pmpro_getMembershipLevelsForUser( $user->ID, true );
	if ( ! is_array( $previous_memberships ) ) {
		$previous_memberships = array();
	}

	$result = pmpro_cancelMembershipLevel( (int) $level->id, (int) $user->ID, 'admin_cancelled' );

	$current_memberships = pmpro_getMembershipLevelsForUser( $user->ID, true );
	if ( ! is_array( $current_memberships ) ) {
		$current_memberships = array();
	}

	if ( false === $result ) {
		return new WP_Error(
			'pmpro_abilities_membership_cancel_failed',
			! empty( $pmpro_error ) ? $pmpro_error : __( 'PMPro could not cancel the membership level.', 'paid-memberships-pro' ),
			array( 'status' => 400 )
		);
	}

	return array(
		'success'              => true,
		'status'               => 'cancelled',
		'user_id'              => (int) $user->ID,
		'level_id'             => (int) $level->id,
		'previous_memberships' => array_map( 'pmpro_abilities_normalize_member_level', $previous_memberships ),
		'current_memberships'  => array_map( 'pmpro_abilities_normalize_member_level', $current_memberships ),
		'message'              => __( 'Membership level cancelled successfully.', 'paid-memberships-pro' ),
	);
}

/**
 * Execute orders query.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_orders_query( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$input = pmpro_abilities_parse_input(
		$input,
		array(
			'limit' => 20,
			'page'  => 1,
		)
	);
	$page   = max( 1, (int) $input['page'] );
	$limit  = max( 1, min( 50, (int) $input['limit'] ) );
	$offset = ( $page - 1 ) * $limit;

	$query_args = array();
	foreach ( array( 'user_id', 'membership_level_id', 'status', 'gateway' ) as $key ) {
		if ( isset( $input[ $key ] ) && '' !== $input[ $key ] && null !== $input[ $key ] ) {
			$query_args[ $key ] = $input[ $key ];
		}
	}

	$total = (int) MemberOrder::get_orders(
		array_merge(
			$query_args,
			array(
				'return_count' => true,
			)
		)
	);

	$orders = MemberOrder::get_orders(
		array_merge(
			$query_args,
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		)
	);

	return array(
		'items' => array_map( 'pmpro_abilities_normalize_order', $orders ),
		'total' => $total,
		'page'  => $page,
		'limit' => $limit,
	);
}

/**
 * Execute order get.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_order_get( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$order = null;
	if ( ! empty( $input['order_id'] ) ) {
		$order = MemberOrder::get_order( (int) $input['order_id'] );
	} elseif ( ! empty( $input['order_code'] ) ) {
		$order = MemberOrder::get_order( (string) $input['order_code'] );
	}

	if ( empty( $order ) || empty( $order->id ) ) {
		return new WP_Error( 'pmpro_abilities_order_not_found', __( 'Order not found.', 'paid-memberships-pro' ), array( 'status' => 404 ) );
	}

	return pmpro_abilities_normalize_order( $order );
}

/**
 * Execute subscriptions query.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_subscriptions_query( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$input = pmpro_abilities_parse_input(
		$input,
		array(
			'limit' => 20,
			'page'  => 1,
		)
	);
	$page   = max( 1, (int) $input['page'] );
	$limit  = max( 1, min( 50, (int) $input['limit'] ) );
	$offset = ( $page - 1 ) * $limit;

	$query_args = array();
	foreach ( array( 'user_id', 'membership_level_id', 'status', 'gateway' ) as $key ) {
		if ( isset( $input[ $key ] ) && '' !== $input[ $key ] && null !== $input[ $key ] ) {
			$query_args[ $key ] = $input[ $key ];
		}
	}

	$total = (int) PMPro_Subscription::get_subscriptions(
		array_merge(
			$query_args,
			array(
				'return_count' => true,
			)
		)
	);

	$subscriptions = PMPro_Subscription::get_subscriptions(
		array_merge(
			$query_args,
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		)
	);

	return array(
		'items' => array_map( 'pmpro_abilities_normalize_subscription', $subscriptions ),
		'total' => $total,
		'page'  => $page,
		'limit' => $limit,
	);
}

/**
 * Execute subscription get.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_subscription_get( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	$subscription = new PMPro_Subscription( (int) $input['subscription_id'] );
	if ( empty( $subscription->get_id() ) ) {
		return new WP_Error( 'pmpro_abilities_subscription_not_found', __( 'Subscription not found.', 'paid-memberships-pro' ), array( 'status' => 404 ) );
	}

	return pmpro_abilities_normalize_subscription( $subscription );
}

/**
 * Execute search query.
 *
 * @param array $input Ability input.
 * @return array|WP_Error
 */
function pmpro_abilities_execute_search_query( $input ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return new WP_Error( 'pmpro_abilities_missing_pmpro', __( 'Paid Memberships Pro is not active.', 'paid-memberships-pro' ), array( 'status' => 503 ) );
	}

	if ( ! class_exists( 'PMPro_REST_API_Routes' ) ) {
		return new WP_Error( 'pmpro_abilities_search_unavailable', __( 'PMPro quick search is unavailable.', 'paid-memberships-pro' ), array( 'status' => 500 ) );
	}

	$scope_map = array(
		'all'            => 'all',
		'users'          => 'users',
		'subscriptions'  => 'subscriptions',
		'orders'         => 'orders',
		'reports'        => 'reports',
		'levels'         => 'levels',
		'discount_codes' => 'discounts',
	);

	$scope      = isset( $scope_map[ $input['scope'] ] ) ? $scope_map[ $input['scope'] ] : 'all';
	$limit      = isset( $input['limit'] ) ? max( 1, min( 20, (int) $input['limit'] ) ) : 10;
	$request    = new WP_REST_Request( 'GET', '/pmpro/v1/quick_search' );
	$request->set_param( 'search', (string) $input['query'] );
	$request->set_param( 'type', $scope );
	$controller = new PMPro_REST_API_Routes();
	$response   = $controller->pmpro_rest_api_quick_search( $request, true, $limit );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$data = $response instanceof WP_REST_Response ? $response->get_data() : array();
	if ( isset( $data['error'] ) ) {
		return new WP_Error( 'pmpro_abilities_search_failed', (string) $data['error'], array( 'status' => $response->get_status() ) );
	}

	$allowed_groups = array( 'users', 'subscriptions', 'orders', 'reports', 'levels', 'discounts' );
	$results        = array();

	foreach ( $allowed_groups as $group_key ) {
		if ( empty( $data[ $group_key ]['items'] ) || ! is_array( $data[ $group_key ]['items'] ) ) {
			continue;
		}

		$normalized_scope = 'discounts' === $group_key ? 'discount_codes' : $group_key;
		$items            = array();

		foreach ( array_slice( $data[ $group_key ]['items'], 0, $limit ) as $item ) {
			$url   = ! empty( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
			$title = ! empty( $item['label'] ) ? wp_strip_all_tags( (string) $item['label'] ) : '';
			$items[] = array(
				'id'          => $url ? $url : md5( $normalized_scope . '|' . $title ),
				'title'       => $title,
				'url'         => $url,
				'entity_type' => $normalized_scope,
				'summary'     => (object) array(),
			);
		}

		$results[] = array(
			'scope' => $normalized_scope,
			'label' => isset( $data[ $group_key ]['label'] ) ? wp_strip_all_tags( (string) $data[ $group_key ]['label'] ) : ucfirst( str_replace( '_', ' ', $normalized_scope ) ),
			'items' => $items,
		);
	}

	return array(
		'query'   => (string) $input['query'],
		'scope'   => (string) $input['scope'],
		'results' => $results,
	);
}
