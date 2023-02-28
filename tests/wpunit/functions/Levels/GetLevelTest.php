<?php

namespace PMPro\Tests\Functions\Levels;

use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-levels
 */
class GetLevelTest extends TestCase {

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_level_id_as_int() {
		$factory = new PMPro_LevelFactory();
		$level   = $factory->create_and_get();

		$this->assertEquals( $level, pmpro_getLevel( (int) $level->id ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_level_id_as_string() {
		$factory = new PMPro_LevelFactory();
		$level   = $factory->create_and_get();

		$this->assertEquals( $level, pmpro_getLevel( (string) $level->id ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_level_name() {
		$factory = new PMPro_LevelFactory();
		$level   = $factory->create_and_get();

		$this->assertEquals( $level, pmpro_getLevel( $level->name ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_level_object() {
		$factory = new PMPro_LevelFactory();
		$level   = $factory->create_and_get();

		$this->assertEquals( $level, pmpro_getLevel( $level ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_bad_level_id_as_int_is_null() {
		$this->assertNull( pmpro_getLevel( 9999999 ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_bad_level_id_as_string_is_null() {
		$this->assertNull( pmpro_getLevel( '9999999' ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_bad_level_name_is_false() {
		$this->assertFalse( pmpro_getLevel( 'Not a level' ) );
	}

	/**
	 * @covers ::pmpro_getLevel()
	 */
	public function test_pmpro_getLevel_by_bad_level_object_is_null() {
		$this->assertNull( pmpro_getLevel( (object) [
			'id' => 9999999,
		] ) );
	}
}