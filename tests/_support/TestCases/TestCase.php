<?php

namespace PMPro\Test_Support\TestCases;

use Codeception\TestCase\WPTestCase;
use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\Factories\PMPro_OrderFactory;
use PMPro\Test_Support\Factories\PMPro_SubscriptionFactory;

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
		$this->factory()->pmpro_order = new PMPro_OrderFactory();
		$this->factory()->pmpro_subscription = new PMPro_SubscriptionFactory();

		// Reset the user to visitor before each test.
		wp_set_current_user( 0 );
	}

	/**
	 *
	 */
	public function tearDown() : void {
		// Do anything we need to do to tear down the environment for these tests.

		parent::tearDown();
	}
}
