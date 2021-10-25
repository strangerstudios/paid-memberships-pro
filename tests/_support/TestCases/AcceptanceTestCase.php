<?php

namespace PMPro\Test_Support\TestCases;

use AcceptanceTester;
use PMPro\Test_Support\Factories\PMPro_LevelFactory;

/**
 * Class AcceptanceTestCase
 */
class AcceptanceTestCase {

	/**
	 *
	 */
	public function _before( AcceptanceTester $I  ) {
		// Do anything we need to do to set up the environment for these tests.

		$I->factory()->pmpro_level = new PMPro_LevelFactory();
	}
}
