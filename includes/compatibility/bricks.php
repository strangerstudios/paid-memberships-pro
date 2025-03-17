<?php
/**
 * Bricks Builder Compatibility for Paid Memberships Pro (PMPro).
 *
 * @since TBD
 */

add_filter( 'bricks/conditions/groups', 'pmpro_bricks_add_condition_group', 5 ); // Lower priority to move to top
add_filter( 'bricks/conditions/options', 'pmpro_bricks_add_custom_condition' );
add_filter( 'bricks/conditions/result', 'pmpro_bricks_condition_result', 10, 3 );

/**
 * Add the PMPro condition group to the Bricks condition groups.
 *
 * @param array $groups The existing condition groups.
 * @return array The modified condition groups.
 */
function pmpro_bricks_add_condition_group( $groups ) {
	$new_group = [
		'name'  => 'pmpro',
		'label' => esc_html__( 'Paid Memberships Pro', 'paid-memberships-pro' ),
	];

	array_unshift( $groups, $new_group ); // Insert at the beginning

	return $groups;
}

/**
 * Add the PMPro membership level condition to the Bricks conditions.
 *
 * @param array $options The existing condition options.
 * @return array The modified condition options.
 * @since TBD
 */
function pmpro_bricks_add_custom_condition( $options ) {
	$options[] = [
		'key'   => 'pmpro_membership_level',
		'label' => esc_html__( 'PMPro Membership Level', 'paid-memberships-pro' ),
		'group' => 'pmpro',
		'compare' => [
			'type'        => 'select',
			'options'     =>  [
				'==' => __( 'Has Membership Level', 'paid-memberships-pro' ),
				'!=' => __( 'Does Not Have Membership Level', 'paid-memberships-pro' ),
			],
			'placeholder' => esc_html__( 'Select Comparison', 'paid-memberships-pro' ),
		],
		'value'   => [
			'type'        => 'select',
			'options'     => pmpro_bricks_get_membership_levels(),
		],
	];

	return $options;
}

/**
 * Get the membership levels for the PMPro condition.
 *
 * @return array The membership levels.
 * @since TBD
 */
function pmpro_bricks_get_membership_levels() {
	$pmpro_levels = pmpro_getAllLevels( true );

	$options = [];

	if ( ! empty( $pmpro_levels ) ) {
		foreach ( $pmpro_levels as $pmpro_level ) {
			$options[ (string) $pmpro_level->id ] = $pmpro_level->name;
		}
	}

	$options['0'] = __( 'Non-Members', 'paid-memberships-pro' );

	return $options;
}

/**
 * Check the result of the PMPro membership level condition.
 *
 * @param bool $result The existing result.
 * @param string $condition_key The condition key.
 * @param array $condition The condition.
 * @return bool The modified result.
 * @since TBD
 */
function pmpro_bricks_condition_result( $result, $condition_key, $condition ) {
	if ( $condition_key !== 'pmpro_membership_level' ) {
		return $result;
	}

	$compare = isset( $condition['compare'] ) ? $condition['compare'] : '==';
	$user_value = isset( $condition['value'] ) ? (string) $condition['value'] : '';

	$user_levels = pmpro_getMembershipLevelsForUser( get_current_user_id() );

	// If the user has no levels, treat them as non-members.
	if ( ! is_array( $user_levels ) ) {
		$user_levels = [];
	}

	
	$user_level_ids = array_map( function( $level ) {
		return (string) $level->id;
	}, $user_levels );

	// If the user has no levels, treat them as non-members.
	if ( empty( $user_level_ids ) ) {
		$user_level_ids[] = '0'; // Treat non-members as '0'
	}

	// Initialize the condition met variable.
	$condition_met = false;

	// Check if the user has the level.
	$has_level = in_array( $user_value, $user_level_ids );

	//$compare can be either '==' or '!='. Determine if the condition is met based on the compare value.
	$condition_met = $compare === '==' ? $has_level : ! $has_level;

	return $condition_met;
}
