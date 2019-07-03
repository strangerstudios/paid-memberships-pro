<?php
namespace PMP\Tests;

use PMP\Tests\Helpers\Factory\Level;

abstract class Base Extends \WP_UnitTestCase {

	function __get( $name ) {
		if ( 'factory' === $name ) {
			return $this->_pmp_factory();
		}
	}

	/**
	 * Fetches the factory object for generating WordPress & PMP fixtures.
	 *
	 * @return WP_UnitTest_Factory The fixture factory.
	 */
	protected function _pmp_factory() {
		$factory = self::factory();

		$factory->pmp_level = new Level( $this );

		return $factory;
	}

} // end of class

//EOF