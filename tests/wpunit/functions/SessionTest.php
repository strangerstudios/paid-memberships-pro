<?php

namespace PMPro\Tests\Functions;

use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-sessions
 */
class SessionTest extends TestCase {

	/**
	 * Data provider for session var tests.
	 */
	public function data_session_vars() {
		return [
			// 'Name of data set' => [
			// $key,
			// $value,
			// ],
			"\$_SESSION['abc'] = '123'" => [
				'abc',
				'123',
			],
			"\$_SESSION['abc'] = 0"     => [
				'abc',
				0,
			],
		];
	}

	/**
	 * Test the pmpro_set_session_var() function.
	 *
	 * @testdox      pmpro_set_session_var()
	 *
	 * @dataProvider data_session_vars
	 *
	 * @covers ::pmpro_set_session_var()
	 */
	public function test_pmpro_set_session_var( $key, $value ) {
		pmpro_set_session_var( $key, $value );

		$result = $_SESSION[ $key ];

		$this->assertEquals( $value, $result );
	}

	/**
	 * Test the pmpro_get_session_var() function.
	 *
	 * @testdox      pmpro_get_session_var()
	 *
	 * @dataProvider data_session_vars
	 *
	 * @covers ::pmpro_get_session_var()
	 */
	public function test_pmpro_get_session_var( $key, $value ) {
		$_SESSION[ $key ] = $value;

		$result = pmpro_get_session_var( $key );

		$this->assertEquals( $value, $result );
	}

	/**
	 * Test the pmpro_unset_session_var() function.
	 *
	 * @testdox      pmpro_unset_session_var()
	 *
	 * @dataProvider data_session_vars
	 *
	 * @covers ::pmpro_unset_session_var()
	 */
	public function test_pmpro_unset_session_var( $key, $value ) {
		$_SESSION[ $key ] = $value;

		pmpro_unset_session_var( $key );

		$this->assertArrayNotHasKey( $key, $_SESSION );
	}
}