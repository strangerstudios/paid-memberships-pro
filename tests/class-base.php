<?php
namespace PMP\Tests;

use PMP\Tests\Helpers\Factory\Level;

abstract class Base Extends \WP_UnitTestCase {

	public function setUp() {
		$this->factory->level = new Level( $this );
	}

} // end of class

//EOF