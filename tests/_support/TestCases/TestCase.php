<?php

namespace PMPro\Test_Support\TestCases;

use Codeception\TestCase\WPTestCase;
use PMPro\Test_Support\Factories\PMPro_LevelFactory;

/**
 * Class TestCase
 */
class TestCase extends WPTestCase {

	/**
	 *
	 */
	public function setUp() : void {
		parent::setUp();

		// Do anything we need to do to set up the environment for these tests.

		$this->factory()->pmpro_level = new PMPro_LevelFactory();
	}

	/**
	 *
	 */
	public function tearDown() : void {
		// Do anything we need to do to tear down the environment for these tests.

		parent::tearDown();
	}
}
