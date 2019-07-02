<?php
namespace PMP\Tests\Includes;

use PMP\Tests\Base;

class Functions extends Base {

	public function data_pmpro_hasMembershipLevel() {
		$return = [
			[
				null,
				null,
				false,
			],
		];

		return $return;
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 * @dataProvider data_pmpro_hasMembershipLevel
	 *
	 * @param $levels
	 * @param $user_id
	 * @param $compare
	 */
	public function test_pmpro_hasMembershipLevel( $levels, $user_id, $compare ) {
//		var_dump( $this->factory->level->create() ); die;
		$this->assertSame( $compare, pmpro_hasMembershipLevel( $levels, $user_id ) );
	}

}