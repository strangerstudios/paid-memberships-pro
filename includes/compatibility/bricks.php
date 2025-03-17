<?php
/**
 * Bricks Builder Compatibility for Paid Memberships Pro (PMPro).
 *
 * Adds custom conditions for Bricks Builder based on PMPro membership levels.
 *
 * @since TBD
 *
 * @link https://academy.bricksbuilder.io/article/element-conditions/
 */

add_filter( 'bricks/conditions/groups', 'pmpro_bricks_add_condition_group', 5 ); // Lower priority to move to top
add_filter( 'bricks/conditions/options', 'pmpro_bricks_add_custom_condition' );
add_filter( 'bricks/conditions/result', 'pmpro_bricks_condition_result', 10, 3 );

/**
 * Add the PMPro condition group to the Bricks condition groups.
 *
 * Inserts the Paid Memberships Pro (PMPro) condition group at the top of the list.
 *
 * @since TBD
 *
 * @param array $groups The existing condition groups.
 * @return array The modified condition groups.
 */
function pmpro_bricks_add_condition_group( $groups ) {
	$new_group = array(
		'name'  => 'pmpro',
		'label' => esc_html__( 'Paid Memberships Pro', 'paid-memberships-pro' ),
	);

	array_unshift( $groups, $new_group ); // Insert at the beginning.

	return $groups;
}

/**
 * Add the PMPro membership level condition to the Bricks conditions.
 *
 * Registers a new condition that allows users to check if a user
 * has a specific membership level in Paid Memberships Pro (PMPro).
 *
 * @since TBD
 *
 * @param array $options The existing condition options.
 * @return array The modified condition options.
 */
function pmpro_bricks_add_custom_condition( $options ) {
	$options[] = array(
		'key'   => 'pmpro_membership_level',
		'label' => esc_html__( 'PMPro Membership Level', 'paid-memberships-pro' ),
		'group' => 'pmpro',
		'compare' => array(
			'type'        => 'select',
			'options'     => array(
				'==' => esc_html__( 'Has Membership Level', 'paid-memberships-pro' ),
				'!=' => esc_html__( 'Does Not Have Membership Level', 'paid-memberships-pro' ),
			),
			'placeholder' => esc_html__( 'Select Comparison', 'paid-memberships-pro' ),
		),
		'value'   => array(
			'type'        => 'select',
			'options'     => pmpro_bricks_get_membership_levels(),
		),
	);

	return $options;
}

/**
 * Get the membership levels for the PMPro condition.
 *
 * Retrieves all PMPro membership levels and structures them for the Bricks Builder dropdown.
 * Includes a "Non-Members" option for users who are not members.
 *
 * @since TBD
 *
 * @see pmpro_getAllLevels()
 * @return array The membership levels formatted for Bricks conditions.
 */
function pmpro_bricks_get_membership_levels() {
	$pmpro_levels = pmpro_getAllLevels( true );

	$options = array();

	// Add the membership levels to the options.
	if ( ! empty( $pmpro_levels ) ) {
		foreach ( $pmpro_levels as $pmpro_level ) {
			$options[ (string) $pmpro_level->id ] = esc_html__( $pmpro_level->name );
		}
	}

	//Add a non-members option.
	$options['0'] = esc_html__( 'Non Members', 'paid-memberships-pro' );

	return $options;
}

/**
 * Check the result of the PMPro membership level condition.
 *
 * Evaluates whether the current user meets the selected membership level condition.
 * Treats non-members as having a level of "0".
 *
 * @since TBD
 *
 * @see pmpro_getMembershipLevelsForUser()
 * @param bool   $result        The existing result.
 * @param string $condition_key The condition key.
 * @param array  $condition     The condition details, including 'compare' and 'value'.
 * @return bool The modified result based on the membership level comparison.
 */
function pmpro_bricks_condition_result( $result, $condition_key, $condition ) {
	if ( $condition_key !== 'pmpro_membership_level' ) {
		return $result;
	}

	$compare = '==';
	if ( isset( $condition['compare'] ) ) {
		$compare = $condition['compare'];
	}

	$user_value = '';
	if ( isset( $condition['value'] ) ) {
		$user_value = $condition['value'];
	}

	$user_levels = pmpro_getMembershipLevelsForUser( get_current_user_id() );

	// If the user has no levels, treat them as non-members.
	if ( ! is_array( $user_levels ) ) {
		$user_levels = array();
	}

	$user_level_ids = array_map( function( $level ) {
		return (string) $level->id;
	}, $user_levels );

	// If the user has no levels, treat them as non-members.
	if ( empty( $user_level_ids ) ) {
		// Use array_push to add the non-members level to the user level ids.
		array_push( $user_level_ids, '0' );
	}

	// Check if the user has the level.
	$has_level = in_array( $user_value, $user_level_ids );

	// Determine if the condition is met based on the compare value.
	$condition_met = false;
	if ( $compare === '==' ) {
		$condition_met = $has_level;
	} elseif ( $compare === '!=' ) {
		$condition_met = ! $has_level;
	}

	return $condition_met;
}
