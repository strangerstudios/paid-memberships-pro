<?php

namespace PMPro\Tests\Functions\Levels;

use PMPro\Test_Support\Factories\PMPro_LevelFactory;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-levels
 */
class GetMembershipLevelsForUserTest extends TestCase {

	/**
	 * @covers ::pmpro_getMembershipLevelsForUser()
	 *
	 * @param null $user_id
	 * @param bool $include_inactive
	 * @param bool $expects
	 */
	public function test_pmpro_getMembershipLevelsForUser( $user_id = null, $include_inactive = false, $expects = false ) {
		$this->assertFalse( pmpro_getMembershipLevelsForUser( null, false ) );
	}
}