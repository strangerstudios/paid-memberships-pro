<?php

namespace PMPro\Tests\Functions;

use PHP_CodeSniffer\Generators\HTML;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 */
class PriceTest extends TestCase {

	protected $original_precision;
	protected $original_serialize_precision;

	/**
	 *
	 */
	public function setUp() : void {
		parent::setUp();

		$this->original_precision           = ini_get( 'precision' );
		$this->original_serialize_precision = ini_get( 'serialize_precision' );

		ini_set( 'precision', 14  );
		ini_set( 'serialize_precision', 17 );
	}

	/**
	 *
	 */
	public function tearDown() : void {
		ini_set( 'precision', $this->original_precision );
		ini_set( 'serialize_precision', $this->original_serialize_precision );

		parent::tearDown();
	}

	/**
	 * @covers ::pmpro_round_price()
	 */
	public function test_precision_issue_with_float() {
		$this->assertEquals( '19.899999999999999', json_encode( 19.9 ) );
		$this->assertEquals( '19.890000000000001', json_encode( 19.89 ) );
	}

	/**
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_round_price_with_float_has_precision_issue() {
		$this->assertEquals( 19.9, pmpro_round_price( 19.9 ) );
		$this->assertEquals( '19.899999999999999', json_encode( pmpro_round_price( 19.9 ) ) );
	}

	/**
	 * @covers ::pmpro_round_price_as_string()
	 * @covers ::pmpro_get_price_info()
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_round_price_as_string_with_non_numeric_amount() {
		$this->assertEquals( '0', pmpro_round_price_as_string( 'non-numeric' ) );
	}

	/**
	 * @covers ::pmpro_round_price_as_string()
	 * @covers ::pmpro_get_price_info()
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_round_price_as_string_with_integer() {
		$this->assertEquals( '5.00', pmpro_round_price_as_string( 5 ) );
		$this->assertEquals( '"5.00"', json_encode( pmpro_round_price_as_string( 5 ) ) );
	}

	/**
	 * @covers ::pmpro_round_price_as_string()
	 * @covers ::pmpro_get_price_info()
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_round_price_as_string_with_float() {
		$this->assertEquals( '19.90', pmpro_round_price_as_string( 19.9 ) );
		$this->assertEquals( '"19.90"', json_encode( pmpro_round_price_as_string( 19.9 ) ) );
	}

	/**
	 * @covers ::pmpro_get_price_info()
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_get_price_info_with_non_numeric_amount() {
		$this->assertFalse( pmpro_get_price_info( 'non-numeric' ) );
	}

	/**
	 * @covers ::pmpro_get_price_info()
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_get_price_info_with_integer() {
		$currency_info = pmpro_get_currency();

		$expected = [
			// The amount represented as a float.
			'amount'        => 5,
			// The flat amount represent (example: 1.99 would be 199).
			'amount_flat'   => 500,
			// The amount as a string.
			'amount_string' => '5.00',
			'parts'         => [
				// The whole number part of the amount (example: 1.99 would be 1).
				'number'         => 5,
				// The decimal part of the amount (example: 1.99 would be 99, 1.00 would be 0).
				'decimal'        => 0,
				// The decimal part of the amount as a string (example: 1.99 would be 99, 1.00 would be 00).
				'decimal_string' => '00',
			],
			// The currency information.
			'currency'      => $currency_info,
		];

		$actual = pmpro_get_price_info( 5 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers ::pmpro_get_price_info()
	 * @covers ::pmpro_round_price()
	 */
	public function test_pmpro_get_price_info_with_float() {
		$currency_info = pmpro_get_currency();

		$expected = [
			// The amount represented as a float.
			'amount'        => 19.9,
			// The flat amount represent (example: 1.99 would be 199).
			'amount_flat'   => 1990,
			// The amount as a string.
			'amount_string' => '19.90',
			'parts'         => [
				// The whole number part of the amount (example: 1.99 would be 1).
				'number'         => 19,
				// The decimal part of the amount (example: 1.99 would be 99, 1.00 would be 0).
				'decimal'        => 90,
				// The decimal part of the amount as a string (example: 1.99 would be 99, 1.00 would be 00).
				'decimal_string' => '90',
			],
			// The currency information.
			'currency'      => $currency_info,
		];

		$actual = pmpro_get_price_info( 19.9 );

		$this->assertEquals( $expected, $actual );
	}
}