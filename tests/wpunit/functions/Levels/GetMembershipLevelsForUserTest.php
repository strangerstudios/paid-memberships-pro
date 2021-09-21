<?php

namespace PMPro\Tests\Functions\Levels;

use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-levels
 */
class GetMembershipLevelsForUserTest extends TestCase {

	private $level_id;
	private $level_id_2;

	private $user_id;
	private $user_id_2;

	private $level;

	public function setUp() : void {
		parent::setUp();

		$this->level_id   = $this->factory()->pmpro_level->create();
		$this->level_id_2 = $this->factory()->pmpro_level->create();

		$this->user_id   = $this->factory()->user->create();
		$this->user_id_2 = $this->factory()->user->create();

		$this->level = pmpro_getLevel( $this->level_id );

		// Set a valid level.
		pmpro_changeMembershipLevel( $this->level_id, $this->user_id );

		// Set a valid level, then set status as expired.
		pmpro_changeMembershipLevel( $this->level_id, $this->user_id_2 );

		global $wpdb;

		$wpdb->update( $wpdb->pmpro_memberships_users, [ 'status' => 'expired' ], [ 'user_id' => $this->user_id_2 ], [ '%s' ], [ '%d' ] );
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 */
	public function test_pmpro_getMembershipLevelsForUser_is_false_with_null_user_id() {
		$this->assertFalse( pmpro_getMembershipLevelsForUser() );
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 */
	public function test_pmpro_getMembershipLevelsForUser_is_false_with_zero_user_id() {
		$this->assertFalse( pmpro_getMembershipLevelsForUser( 0 ) );
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 */
	public function test_pmpro_getMembershipLevelsForUser_is_false_with_valid_user_id() {
		$levels = pmpro_getMembershipLevelsForUser( $this->user_id );

		$this->assertIsArray( $levels );
		$this->assertEquals( $this->level_id, current( $levels )->ID );
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 */
	public function test_pmpro_getMembershipLevelsForUser_is_level_array_set_with_null_user_id_and_valid_current_user() {
		wp_set_current_user( $this->user_id );

		$levels = pmpro_getMembershipLevelsForUser();

		$this->assertIsArray( $levels );
		$this->assertEquals( $this->level_id, current( $levels )->ID );
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 */
	public function test_pmpro_getMembershipLevelsForUser_is_empty_array_with_valid_user_id_with_no_levels() {
		$this->assertEquals( [], pmpro_getMembershipLevelsForUser( $this->user_id_2 ) );
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 */
	public function test_pmpro_getMembershipLevelsForUser_is_empty_array_with_valid_user_id_with_no_levels_and_different_current_user() {
		wp_set_current_user( $this->user_id );

		$this->assertEquals( [], pmpro_getMembershipLevelsForUser( $this->user_id_2 ) );
	}
}