<?php
namespace PMP\Tests\Includes;

use PMP\Tests\Base;

class Functions extends Base {

	public function data_pmpro_getMembershipLevelsForUser() {
		return [
			[],
		];
	}

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 * @dataProvider data_pmpro_getMembershipLevelsForUser
	 *
	 * @param null $user_id
	 * @param bool $include_inactive
	 * @param bool $compare
	 */
	public function test_pmpro_getMembershipLevelsForUser( $user_id = null, $include_inactive = false, $compare = false ) {
		$this->assertSame( $compare, pmpro_getMembershipLevelsForUser( $user_id, $include_inactive ) );
	}

	public function data_pmpro_changeMembershipLevel() {
		$level_id   = $this->factory->pmp_level->create();
		$level_id_2 = $this->factory->pmp_level->create();
		$user_id    = $this->factory->user->create();

		return [
			[
				null,
			],
			[
				null,
				$user_id,
			],
			[
				$level_id,
				$user_id,
				'inactive',
				null,
				'assertTrue',
			],
		];
	}

	/**
	 * @covers ::pmpro_changeMembershipLevel()
	 * @dataProvider data_pmpro_changeMembershipLevel
	 *
	 * @param        $level
	 * @param null   $user_id
	 * @param string $old_level_status
	 * @param null   $cancel_level
	 * @param string $assert
	 */
	public function test_pmpro_changeMembershipLevel( $level, $user_id = null, $old_level_status = 'inactive', $cancel_level = null, $assert = 'assertFalse' ) {
		$this->$assert( pmpro_changeMembershipLevel( $level, $user_id, $old_level_status, $cancel_level ) );
	}

	public function data_pmpro_hasMembershipLevel() {
		$level_id   = $this->factory->pmp_level->create();
		$level_id_2 = $this->factory->pmp_level->create();
		$user_id    = $this->factory->user->create();
		$user_id_2  = $this->factory->user->create();

		pmpro_changeMembershipLevel( $level_id, $user_id );

		return [
			[
			],
			[
				[],
			],
			[
				[ 0, $level_id ],
				null,
				false,
				'assertTrue', // shouldn't be true?
			],
			[
				0,
				null,
				false,
				'assertTrue', // this doesn't seem right. see line 774
			],
			[
				'0',
				null,
				false,
				'assertTrue', // this doesn't seem right. see line 774
			],
			[
				'',
			],
			[
				-1,
				null,
				false,
				'assertTrue', // shouldn't be true?
			],
			[
				-1,
				$user_id,
			],
			[
				null,
				$user_id,
				false,
				'assertTrue', // shouldn't be true?
			],
			[
				$level_id,
				$user_id,
				false,
				'assertTrue',
			],
			[
				[ $level_id, $level_id_2 ],
				$user_id,
				false,
				'assertTrue',
			],
			[
				$level_id,
				null,
				$user_id,
				'assertTrue',
			],
			[
				'L',
				$user_id_2,
				$user_id_2,
				'assertTrue',
			],
			[
				'-L',
				$user_id_2,
				$user_id_2,
			],
			[
				'E',
				$user_id_2,
				$user_id_2,
			],
		];
	}

	/**
	 * @covers ::pmpro_hasMembershipLevel()
	 * @dataProvider data_pmpro_hasMembershipLevel
	 *
	 * @param $levels
	 * @param $user_id
	 * @param $current_user
	 * @param $assert
	 */
	public function test_pmpro_hasMembershipLevel( $levels = null, $user_id = null, $current_user = false, $assert = 'assertFalse' ) {
		if ( $current_user ) {
			wp_set_current_user( $current_user );
		}

		$this->$assert( pmpro_hasMembershipLevel( $levels, $user_id ) );
	}

}