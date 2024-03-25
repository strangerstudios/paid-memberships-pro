<?php
if ( class_exists( 'ElementorPro\Modules\DisplayConditions\Module' ) ) {
	add_action( 'elementor/display_conditions/register', function ( $conditions_manager ) {
		$conditions = [
			'Membership_Level_Condition' => 'conditions/membership-level-condition.php',
		];
	
		foreach ( $conditions as $condition_name => $location ) {
			require_once( $location );
			$conditions_manager->register_condition_instance( new $condition_name() );
		}
	} );
	
	add_action( 'elementor/display_conditions/register_groups', function ( $conditions_manager ) {
		$conditions_manager->add_group( 'pmpro', [ 'label' => esc_html__( 'Paid Memberships Pro', 'paid-memberships-pro' ) ] );
	} );
}
