<?php

add_action( 'init', 'pmpro_register_breakdance_conditions', 20 );

/**
 * Register the custom condition for the Oxygen V6+ Builder.
 *
 * @since TBD
 */
function pmpro_register_breakdance_conditions() {
	//Bail if Breakdance / Oxygen is not enabled.
	if ( ! function_exists( '\Breakdance\Themeless\registerCondition' ) ) {
		return;
	}
	\Breakdance\Themeless\registerCondition(
		[
			'supports'  => ['element_display', 'templating'],
			'availableForType' => ['ALL'],
			'slug'      => 'pmpro-membership-level',
			'label'     => __( 'Paid Memberships Pro Level', 'paid-memberships-pro' ),
			'category'  => __( 'Membership','paid-memberships-pro' ),
			'operands'  => [OPERAND_ONE_OF, OPERAND_NONE_OF],
			'values'    => 'pmpro_oxygen_build_condition',
			'callback'  => 'pmpro_oxygen_condition_callback',
			'templatePreviewableItems' => false,
		]
	);
}

/**
 * Function to build the custom condition options for the Oxygen Builder condition. 
 * See https://github.com/soflyy/breakdance-developer-docs/tree/master/conditions
 * 
 * @return array The options for the condition.
 * @since TBD
 */
function pmpro_oxygen_build_condition() {
	$pmpro_levels = pmpro_getAllLevels( true );

	$pmpro_levels_dropdown = [
		[
			'label' => __( 'Membership Levels', 'paid-memberships-pro' ),
			'items' => [
				[
					'text'  => __( 'Non-Members', 'paid-memberships-pro' ),
					'value' => '0'
				]
			]
		]
	];

	if ( ! empty( $pmpro_levels ) ) {
		foreach ( $pmpro_levels as $pmpro_level ) {
			$pmpro_levels_dropdown[0]['items'][] = [
				'text'  => '[' . $pmpro_level->id . '] ' . $pmpro_level->name,
				'value' => (string) $pmpro_level->id
			];
		}
	}

	return $pmpro_levels_dropdown;
}

/**
 * Callback for the Oxygen Builder condition. Runs the condition check in the frontend.
 *
 * @param string $operand The operand to use for the condition.
 * @param array $values The values to compare with the user's membership levels.
 * @return bool Whether the condition is met.
 * @since TBD
 */
function pmpro_oxygen_condition_callback( string $operand, array $values ) {
	// Get the user's membership levels.
	$user_levels = pmpro_getMembershipLevelsForUser( get_current_user_id() );

	// Handle non-logged-in users (false means no levels, so treat them as "Non-Members" with value '0').
	if ( ! is_array( $user_levels ) ) {
		$user_levels = [];
	}

	// Get the ids to compare with param values.
	$user_level_ids = array_map( function( $level ) {
		return (string) $level->id;
	}, $user_levels );

	// Include '0' if user has no membership levels.
	if ( empty( $user_level_ids ) ) {
		$user_level_ids[] = '0';
	}

	// If count > 0 this user has a level that matches the condition.
	$has_matching_level = count( array_intersect( $values, $user_level_ids ) ) > 0;

	//Let's see if the condition means the user must have or can't have the level.

	// If the operand is "one of"  User must have at least one of the levels.
	if ( $operand === OPERAND_ONE_OF ) {
		return $has_matching_level;
	}

	// If the operand is "none of" User must not have any of the levels.
	if ( $operand === OPERAND_NONE_OF ) {
		return ! $has_matching_level;
	}

	return false;
}
