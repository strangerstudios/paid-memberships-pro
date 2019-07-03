<?php
namespace PMP\Tests\Helpers;

class Factory extends \WP_UnitTest_Factory {

	/**
	 * @var PMP\Tests\Helpers\Factory\Level
	 */
	public $level;

	public function __construct() {
		parent::__construct();

		$this->level = new Level( $this );
	}
}