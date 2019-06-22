<?php
/**
 * Test the pmpro_hasMembershipLevel() function.
 */
class Test_hasMembershipLevel extends WP_UnitTestCase {
	/**
	 * Give a user a level and test that they have it.
	 */
	function test_pmpro_give_user_level() {
		global $wpdb;
        
        // Create a user
        $userdata = array(
            'user_login'  =>  'test1',
            'user_pass'   =>  NULL  // When creating a new user, `user_pass` is expected.
        );
        $user_id = wp_insert_user( $userdata ) ;

        // Create a level
        // NOTE: We need an API for creaing PMPro levels.
        $wpdb->insert(
			$wpdb->pmpro_membership_levels,
			array(
				'id'=> 1,
				'name' => 'Test Level',
				'description' => 'Testing pmpro_hasMembershipLevel in a unit test.',
				'confirmation' => '',
				'initial_payment' => 1,
				'billing_amount' => 1,
				'cycle_number' => 1,
				'cycle_period' => 'Month',
				'billing_limit' => 0,
				'trial_amount' => 0,
				'trial_limit' => 0,
				'expiration_number' => 0,
				'expiration_period' => '',
				'allow_signups' => 1
			),
			array(
				'%d',		//id
				'%s',		//name
				'%s',		//description
				'%s',		//confirmation
				'%f',		//initial_payment
				'%f',		//billing_amount
				'%d',		//cycle_number
				'%s',		//cycle_period
				'%d',		//billing_limit
				'%f',		//trial_amount
				'%d',		//trial_limit
				'%d',		//expiration_number
				'%s',		//expiration_period
				'%d',		//allow_signups
			)
		);
        
        // Give the user a level
        pmpro_changeMembershipLevel( 1, $user_id );
        
        // Assert that the user has the level
        $this->assertTrue( pmpro_hasMembershipLevel( 1, $user_id ) );
	}
}