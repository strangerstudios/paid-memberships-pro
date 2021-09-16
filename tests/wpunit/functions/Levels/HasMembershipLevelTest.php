<?php

namespace PMPro\Tests\Functions\Levels;

use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-levels
 */
class HasMembershipLevelTest extends TestCase {

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
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_null_levels_and_null_user_id() {
		$this->assertFalse( pmpro_hasMembershipLevel() );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_empty_array_levels_and_null_user_id() {
		$this->assertFalse( pmpro_hasMembershipLevel( [] ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_0_level_id_array_levels_and_null_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( [ 0, $this->level_id ] ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_0_levels_and_null_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( 0 ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_0_string_levels_and_null_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( '0' ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_empty_string_levels_and_null_user_id() {
		$this->assertFalse( pmpro_hasMembershipLevel( '' ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_negative_one_levels_and_null_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( - 1 ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_negative_one_levels_and_valid_user_id() {
		$this->assertFalse( pmpro_hasMembershipLevel( - 1, $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_null_levels_and_valid_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( null, $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_valid_levels_and_valid_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( $this->level_id, $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_valid_array_of_two_levels_and_valid_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( [ $this->level_id, $this->level_id_2 ], $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_valid_levels_and_null_user_id_and_valid_current_user() {
		wp_set_current_user( $this->user_id );

		$this->assertTrue( pmpro_hasMembershipLevel( $this->level_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_L_string_levels_and_valid_user_id_and_valid_current_user() {
		wp_set_current_user( $this->user_id );

		// Test L for logged-in user, it should match current user.
		$this->assertTrue( pmpro_hasMembershipLevel( 'L', $this->user_id ) );
		$this->assertTrue( pmpro_hasMembershipLevel( 'l', $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_L_string_levels_and_valid_user_id_and_different_current_user() {
		wp_set_current_user( $this->user_id_2 );

		// Test L for logged-in user, it should match current user.
		$this->assertFalse( pmpro_hasMembershipLevel( 'L', $this->user_id ) );
		$this->assertFalse( pmpro_hasMembershipLevel( 'l', $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_negative_L_string_levels_and_valid_user_id_and_valid_current_user() {
		wp_set_current_user( $this->user_id );

		// Test -L for non-logged-in user, it should not match current user.
		$this->assertFalse( pmpro_hasMembershipLevel( '-L', $this->user_id ) );
		$this->assertFalse( pmpro_hasMembershipLevel( '-l', $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_negative_L_string_levels_and_valid_user_id_and_different_current_user() {
		wp_set_current_user( $this->user_id_2 );

		// Test -L for non-logged-in user, it should not match current user.
		$this->assertTrue( pmpro_hasMembershipLevel( '-L', $this->user_id ) );
		$this->assertTrue( pmpro_hasMembershipLevel( '-l', $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_E_string_levels_and_valid_user_id() {
		// Test E for expired level.
		$this->assertFalse( pmpro_hasMembershipLevel( 'E', $this->user_id ) );
		$this->assertFalse( pmpro_hasMembershipLevel( 'e', $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_E_string_levels_and_expired_user_id() {
		// Test E for expired level.
		$this->assertTrue( pmpro_hasMembershipLevel( 'E', $this->user_id_2 ) );
		$this->assertTrue( pmpro_hasMembershipLevel( 'e', $this->user_id_2 ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_true_with_valid_name_string_levels_and_valid_user_id() {
		$this->assertTrue( pmpro_hasMembershipLevel( $this->level->name, $this->user_id ) );
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 */
	public function test_pmpro_hasMembershipLevel_is_false_with_invalid_name_string_levels_and_valid_user_id() {
		$this->assertFalse( pmpro_hasMembershipLevel( 'Not Level Name', $this->user_id ) );
	}
}