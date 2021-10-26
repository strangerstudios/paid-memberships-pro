<?php

namespace PMPro\Tests\Functions\Levels;

use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-levels
 */
class ChangeMembershipLevelTest extends TestCase {

	/**
	 * @covers ::pmpro_changeMembershipLevel()
	 */
	public function test_pmpro_changeMembershipLevel_is_false_with_empty_level_and_invalid_user() {
		$this->assertFalse( pmpro_changeMembershipLevel( null ) );
		$this->assertFalse( pmpro_changeMembershipLevel( 0 ) );
		$this->assertFalse( pmpro_changeMembershipLevel( '' ) );
		$this->assertFalse( pmpro_changeMembershipLevel( [] ) );
	}

	/**
	 * @covers ::pmpro_changeMembershipLevel()
	 */
	public function test_pmpro_changeMembershipLevel_is_null_with_invalid_level_and_no_user_and_valid_current_user() {
		$user_id = $this->factory->user->create();

		wp_set_current_user( $user_id );

		$this->assertNull( pmpro_changeMembershipLevel( null ) );
		$this->assertNull( pmpro_changeMembershipLevel( 0 ) );
		$this->assertNull( pmpro_changeMembershipLevel( '' ) );
		$this->assertNull( pmpro_changeMembershipLevel( [] ) );
	}

	/**
	 * @covers ::pmpro_changeMembershipLevel()
	 */
	public function test_pmpro_changeMembershipLevel_is_null_with_invalid_level_and_valid_user() {
		$user_id = $this->factory()->user->create();

		$this->assertNull( pmpro_changeMembershipLevel( null, $user_id ) );
		$this->assertNull( pmpro_changeMembershipLevel( 0, $user_id ) );
		$this->assertNull( pmpro_changeMembershipLevel( '', $user_id ) );
		$this->assertNull( pmpro_changeMembershipLevel( [], $user_id ) );
	}

	/**
	 * @covers ::pmpro_changeMembershipLevel()
	 */
	public function test_pmpro_changeMembershipLevel_is_true_with_valid_level_and_valid_user() {
		$level_id = $this->factory()->pmpro_level->create();
		$user_id  = $this->factory()->user->create();

		$this->assertTrue( pmpro_changeMembershipLevel( $level_id, $user_id ) );
	}
}