<?php
namespace PMPro\Tests\Includes;

use PMPro\Tests\Base;

/**
 * @todo Move these tests over.
 */
class Functions extends Base {

    /**
     * Skip all tests for now.
     */
    function setUp() {
        $this->markTestSkipped( 'Tests need work -- skipping for now.' );
    }

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
	 * @param bool $expects
	 */
	public function test_pmpro_getMembershipLevelsForUser( $user_id = null, $include_inactive = false, $expects = false ) {
		$this->assertSame( $expects, pmpro_getMembershipLevelsForUser( $user_id, $include_inactive ) );
	}

	public function data_pmpro_changeMembershipLevel() {
		$level_id   = $this->factory->pmpro_level->create();
		$level_id_2 = $this->factory->pmpro_level->create();
		$user_id    = $this->factory->user->create();

		return [
			[ // #0
				null,
			],
			[ // #1
				null,
				$user_id,
			],
			[ // #2
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
		$level_id   = $this->factory->pmpro_level->create();
		$level_id_2 = $this->factory->pmpro_level->create();
		$level      = pmpro_getLevel( $level_id );

		$user_id   = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();

		pmpro_changeMembershipLevel( $level_id, $user_id );

		return [
			[ // #0
				null,
			],
			[ // #1
				[],
			],
			[ // #2
				[ 0, $level_id ],
				null,
				false,
				'assertTrue', // true?
			],
			[ // #3
				0,
				null,
				false,
				'assertTrue', // true? see line 774
			],
			[ // #4
				'0',
				null,
				false,
				'assertTrue', // true?see line 774
			],
			[ // #5
				'',
			],
			[ // #6
				-1,
				null,
				false,
				'assertTrue', // true?
			],
//			[ // #7
//				-1,
//				$user_id,
//				false,
//				'assertTrue', // true?
//			],
			[ // #8
				null,
				$user_id,
				false,
				'assertTrue', // true?
			],
			[ // #9
				$level_id,
				$user_id,
				false,
				'assertTrue',
			],
			[ // #10
				[ $level_id, $level_id_2 ],
				$user_id,
				false,
				'assertTrue',
			],
			[ // #11
				$level_id,
				null,
				$user_id,
				'assertTrue',
			],
			[ // #12
				'L',
				$user_id,
				$user_id,
				'assertTrue',
			],
			[ // #13
				'L',
				$user_id_2,
				$user_id_2,
				'assertTrue',
			],
			[ // #14
				'-L',
				$user_id_2,
				$user_id_2,
			],
			[ // #15
				'-L',
				$user_id,
				$user_id_2,
				'assertTrue',
			],
			[ // #16
				'E',
				$user_id,
				$user_id_2,
			],
			[ // #17
				'E',
				$user_id_2,
				$user_id_2,
			],
			[ // #18
				$level->name,
				null,
				$user_id,
				'assertTrue',
			],
			[ // #19
				'Not Level Name',
				null,
				$user_id,
				'assertFalse',
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

		$global_current_user = $GLOBALS['current_user'];

		if ( $current_user ) {
			wp_set_current_user( $current_user );
		}

		$this->$assert( pmpro_hasMembershipLevel( $levels, $user_id ) );

		$GLOBALS['current_user'] = $global_current_user;

	}

}