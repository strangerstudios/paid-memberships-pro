<?php
/**
 * Bricks Builder Compatibility for Paid Memberships Pro (PMPro).
 *
 * Adds custom conditions for Bricks Builder based on PMPro membership levels.
 *
 * @since 3.5
 *
 * @link https://academy.bricksbuilder.io/article/element-conditions/
 */

add_filter( 'bricks/conditions/groups', 'pmpro_bricks_add_condition_group' );
add_filter( 'bricks/conditions/options', 'pmpro_bricks_add_custom_condition' );
add_filter( 'bricks/conditions/result', 'pmpro_bricks_condition_result', 10, 3 );

/**
 * Add the PMPro condition group to the Bricks condition groups.
 *
 * Inserts the Paid Memberships Pro (PMPro) condition group at the top of the list.
 *
 * @since 3.5
 *
 * @param array $groups The existing condition groups.
 * @return array The modified condition groups.
 */
function pmpro_bricks_add_condition_group( $groups ) {
	$groups[] = array(
		'name'  => 'pmpro',
		'label' => esc_html__( 'Paid Memberships Pro', 'paid-memberships-pro' ),
	);

	return $groups;
}

/**
 * Add the PMPro membership level condition to the Bricks conditions.
 *
 * Registers a new condition that allows users to check if a user
 * has a specific membership level in Paid Memberships Pro (PMPro).
 *
 * @since 3.5
 *
 * @param array $options The existing condition options.
 * @return array The modified condition options.
 */
function pmpro_bricks_add_custom_condition( $options ) {
	// All Members
	$options[] = array(
		'key'   => 'pmpro_membership_level_all_members',
		'label' => esc_html__( 'All Members', 'paid-memberships-pro' ),
		'group' => 'pmpro',
		'compare' => array(
			'type'        => 'select',
			'options'     => array(
				'==' => esc_html__( 'Show content to All Members', 'paid-memberships-pro' ),
				'!=' => esc_html__( 'Hide content from All Members', 'paid-memberships-pro' ),
			),
			'placeholder' => esc_html__( 'Select Comparison', 'paid-memberships-pro' ),
		),
		'value'   => array(
		),
	);

	// Show for specific members.
	$options[] = array(
		'key'   => 'pmpro_membership_level_specific_levels',
		'label' => esc_html__( 'Specific Membership Levels', 'paid-memberships-pro' ),
		'group' => 'pmpro',
		'compare' => array(
			'type'        => 'select',
			'options'     => array(
				'==' => esc_html__( 'Show content to Specific Membership Levels', 'paid-memberships-pro' ),
				'!=' => esc_html__( 'Hide content from Specific Membership Levels', 'paid-memberships-pro' ),
			),
			'placeholder' => esc_html__( 'Select Comparison', 'paid-memberships-pro' ),
		),
		'value'   => array(
			'type'        => 'select',
			'options'     => wp_list_pluck( pmpro_getAllLevels( true ), 'name', 'id' ),
			'multiple'    => true,
		),
	);

	// Non-members
	$options[] = array(
		'key'   => 'pmpro_membership_level_logged_in_users',
		'label' => esc_html__( 'Logged-in users', 'paid-memberships-pro' ),
		'group' => 'pmpro',
		'compare' => array(
			'type'        => 'select',
			'options'     => array(
				'==' => esc_html__( 'Show content to Logged-in users', 'paid-memberships-pro' ),
				'!=' => esc_html__( 'Hide content from Logged-in users', 'paid-memberships-pro' ),
			),
			'placeholder' => esc_html__( 'Select Comparison', 'paid-memberships-pro' ),
		),
		'value'   => array(
		),
	);

	return $options;
}

/**
 * Check if the user or member should or should not have access to the content.
 *
 * @since 3.5
 *
 * @param bool   $result        The existing result.
 * @param string $condition_key The condition key.
 * @param array  $condition     The condition details, including 'compare' and 'value'.
 * @return bool The modified result based on the membership level comparison.
 */
function pmpro_bricks_condition_result( $result, $condition_key, $condition ) {

	// Check if the condition is a pmpro_ prefixed condition. If not let's bail.
	if ( strpos( $condition_key, 'pmpro_' ) !== 0 ) {
		return $result;
	}
	
	// Set up some default variables here.
	$comparison = 1;
	$condition_operator = $condition['compare'];
	$has_access = false;

	// Check for all members.
	if ( $condition_key === 'pmpro_membership_level_all_members' ) {
		$user_value = pmpro_hasMembershipLevel();
		$has_access = pmpro_int_compare( $user_value, $comparison, $condition_operator );
	}

	// Check for logged-in non-members.
	if ( $condition_key === 'pmpro_membership_level_logged_in_users' ) {
		$user_value = pmpro_hasMembershipLevel( 'L' );
		$has_access = pmpro_int_compare( $user_value, $comparison, $condition_operator );
	}
	
	// Check for specific membership levels.
	if ( $condition_key === 'pmpro_membership_level_specific_levels' ) {
		$user_value = pmpro_hasMembershipLevel( $condition['value'] );
		$has_access = pmpro_int_compare( $user_value, $comparison, $condition_operator );
	}

	// Return the access to the bricks builder. To filter this, use the `pmpro_has_membership_level` filter.
	return $has_access;
}
