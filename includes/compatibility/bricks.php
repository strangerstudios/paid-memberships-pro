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
				'==' => esc_html__( 'Show content to', 'paid-memberships-pro' ),
				'!=' => esc_html__( 'Hide content from', 'paid-memberships-pro' ),
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

	$levels_options_array = array();

	// Add some custom levels_options_array/values to the level ID array.
	$levels_options_array[-1] = esc_html__( 'All Members', 'paid-memberships-pro' ); // This gets pushed to the bottom no matter what we do. TODO: Bricks must fix this.
	$levels_options_array[0] = esc_html__( 'Non Members', 'paid-memberships-pro' );

	// Add the membership levels to the levels_options_array.
	if ( ! empty( $pmpro_levels ) ) {
		foreach ( $pmpro_levels as $pmpro_level ) {
			$levels_options_array[ (int) $pmpro_level->id ] = esc_html( $pmpro_level->name );
		}
	};

	return $levels_options_array;
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

	// Get the conditional comparison operator.
	$compare = '==';
	if ( isset( $condition['compare'] ) ) {
		$compare = $condition['compare'];
	}

	// Get the level ID of the condition.
	$condition_level_id = '';
	if ( isset( $condition['value'] ) ) {
		$condition_level_id = $condition['value'];
	}

	// Figure out if the member has the relevant condition.
	return pmpro_bricks_condition_checker( $condition_level_id, $compare );
}

/**
 * Helper function to figure out if the condition is met or not.
 *
 * @param string $condition_level_id The level ID to check against.
 * @param string $compare The comparison operator, in this case it's either '==' or '!='.
 * @return boolean $condition_met Whether the condition is met.
 */
function pmpro_bricks_condition_checker( $condition_level_id, $compare ) {

	if ( $condition_level_id > 0 ) {
		$user_has_level = pmpro_hasMembershipLevel( $condition_level_id );
	} elseif ( $condition_level_id === 0 ) {
		$user_has_level = ! pmpro_hasMembershipLevel();
	} elseif ( $condition_level_id === -1 ) {
		$user_has_level = pmpro_hasMembershipLevel();
	}

	// Compare the condition we're looking for.
	if ( $compare === '==' ) {
		$condition_met = $user_has_level;
	} elseif ( $compare === '!=' ) {
		$condition_met = ! $user_has_level;
	}

	/**
	 * Filter to allow bypassing of the condition check for Bricks Builder condition.
	 * 
	 * @since TBD
	 * 
	 * @param boolean $condition_met Whether the condition is met.
	 * @param string $condition_level_id The level ID to check against.
	 * @param string $compare The comparison operator.
	 */
	return apply_filters( 'pmpro_bricks_condition_access', $condition_met, $condition_level_id, $compare );
}