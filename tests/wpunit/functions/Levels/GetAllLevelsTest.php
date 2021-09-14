<?php

namespace PMPro\Tests\Functions\Levels;

use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @todo Add tests for $use_cache=true usage.
 *
 * @group pmpro-functions
 * @group pmpro-levels
 */
class GetAllLevelTest extends TestCase {

	/**
	 * @covers ::pmpro_getAllLevels()
	 */
	public function test_pmpro_getAllLevels_that_excluding_hidden_and_not_cached() {
		$factory = new PMPro_LevelFactory();

		$level        = $factory->create_and_get();
		$hidden_level = $factory->create_and_get( [
			'allow_signups' => '0',
		] );

		$all_levels = pmpro_getAllLevels( false, false, true );

		$level_ids = wp_list_pluck( $all_levels, 'id' );

		$this->assertContains( $level->id, $level_ids );
		$this->assertNotContains( $hidden_level->id, $level_ids );
	}

	/**
	 * @covers ::pmpro_getAllLevels()
	 */
	public function test_pmpro_getAllLevels_that_including_hidden_and_not_cached() {
		$factory = new PMPro_LevelFactory();

		$level        = $factory->create_and_get();
		$hidden_level = $factory->create_and_get( [
			'allow_signups' => '0',
		] );

		$all_levels = pmpro_getAllLevels( true, false, true );

		$level_ids = wp_list_pluck( $all_levels, 'id' );

		$this->assertContains( $level->id, $level_ids );
		$this->assertContains( $hidden_level->id, $level_ids );
	}
}