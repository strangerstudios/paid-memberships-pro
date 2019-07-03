<?php
namespace PMP\Tests\Includes;

use PMP\Tests\Base;

class Functions extends Base {

	public function data_pmpro_hasMembershipLevel() {
		$level_id   = $this->factory->pmp_level->create();
		$level_id_2 = $this->factory->pmp_level->create();
		$user_id    = $this->factory->user->create();
		$user_id_2  = $this->factory->user->create();

		pmpro_changeMembershipLevel( $level_id, $user_id );

		$return = [
			[
				null,
				null,
				false,
				false,
			],
			[
				[],
				null,
				false,
				false,
			],
			[
				[ 0, $level_id ],
				null,
				false,
				true, // shouldn't be true?
			],
			[
				0,
				null,
				false,
				true, // this doesn't seem right. see line 774
			],
			[
				'0',
				null,
				false,
				true, // this doesn't seem right. see line 774
			],
			[
				'',
				null,
				false,
				false,
			],
			[
				-1,
				null,
				false,
				true, // shouldn't be true?
			],
			[
				-1,
				$user_id,
				false,
				false,
			],
			[
				null,
				$user_id,
				false,
				true, // shouldn't be true?
			],
			[
				$level_id,
				$user_id,
				false,
				true,
			],
			[
				[ $level_id, $level_id_2 ],
				$user_id,
				false,
				true,
			],
			[
				$level_id,
				null,
				$user_id,
				true,
			],
			[
				'L',
				$user_id_2,
				$user_id_2,
				true,
			],
			[
				'-L',
				$user_id_2,
				$user_id_2,
				false,
			],
			[
				'E',
				$user_id_2,
				$user_id_2,
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
	 * @param $current_user
	 * @param $compare
	 */
	public function test_pmpro_hasMembershipLevel( $levels, $user_id, $current_user, $compare ) {
		if ( $current_user ) {
			wp_set_current_user( $current_user );
		}

		$this->assertSame( $compare, pmpro_hasMembershipLevel( $levels, $user_id ) );
	}

}