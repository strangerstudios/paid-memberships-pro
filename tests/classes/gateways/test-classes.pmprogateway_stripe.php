<?php

namespace PMPro\Tests\Classes\Gateways;

use \PHPUnit\Framework\TestCase;

/**
 * @testdox PMProGateway_stripe
 * @covers \PMProGateway_stripe
 */
class PMProGateway_stripe extends TestCase {

	private static $mock_api_initialized;
	private static $gateway;
	private static $order;

	/**
	 * Set up mock API for running tests.
	 *
	 * @beforeClass
	 */
	public static function setup_mock_stripe_api() {

		// Set API key and base.
		\Stripe\Stripe::setApiKey( 'sk_test_123' );
		\Stripe\Stripe::$apiBase = 'http://api.stripe.com';

		// set up your tweaked Curl client
		$curl = new \Stripe\HttpClient\CurlClient( [ CURLOPT_PROXY => 'localhost:12111' ] );
		// tell Stripe to use the tweaked client
		\Stripe\ApiRequestor::setHttpClient( $curl );
		
		// Test the gateway.
		try {
			$charge = \Stripe\Charge::retrieve( 'ch_123' );
			if ( ! empty( $charge->id ) ) {
				self::$mock_api_initialized = true;
			}
		} catch ( \Exception $e ) {
			self::$mock_api_initialized = false;
		}
	}

	//*********************************************
	// Miscellaneous Tests
	//*********************************************
	 
	 /**
	  * Data provider for getCustomer() test.
	  */
	function data_getCustomer() {

		// Order with PaymentMethod
		$order1 = new \MemberOrder();
		$order1->setGateway( 'stripe' );
		$order1->stripeToken = 'pm_12345';
		$order1->Email       = 'test@example.com';

		// Order with Customer ID
		$order2 = new \MemberOrder();
		$order2->setGateway( 'stripe' );
		$order2->Gateway->customer = 'cus_12345';

		return [
			// 'Name of data set' => [
			// $order,
			// $force,
			// $expected
			// ],
			'Order with PaymentMethod - force'     => [
				$order1,
				true,
				'cus_',
			],
			"Order with Customer ID - don't force" => [
				$order2,
				false,
				'cus_12345',
			],
		];
	}
	
	 /**
	  * Test the getCustomer() method of the PMProGateway_stripe class.
	  *
	  * @testdox can get Customer
	  * @dataProvider data_getCustomer
	  */
	function test_getCustomer( $order, $force, $expected ) {
		
		if ( ! self::$mock_api_initialized ) {
			$this->markTestSkipped( 'Unable to use stripe-mock server.' );
		}

		$gateway = $order->Gateway;

		// Try to get customer from order.
		$gateway->customer = $gateway->getCustomer( $order, $force );
		$result            = $gateway->customer;

		if ( ! empty( $result->id ) ) {
			// If a Customer was returned, check the ID.
			$this->assertContains( $expected, $result->id );
		} else {
			// If a Customer ID was returned, make sure it's the same.
			$this->assertEquals( $expected, $result );
		}
	}
	
	//*********************************************
	// Checkout Tests
	//*********************************************
	 
	/**
	  * Test the set_payment_intent() method of the PMProGateway_stripe class.
	  *
	  * @testdox can set PaymentIntent
	  */
	function test_set_payment_intent() {
		$this->markTestIncomplete();
	}
	
	/**
	  * Test the update_payment_intent() method of the PMProGateway_stripe class.
	  *
	  * @testdox can update PaymentIntent
	  */
	function test_update_payment_intent() {
		$this->markTestIncomplete();
	}
	
	/**
	  * Test the create_payment_intent() method of the PMProGateway_stripe class.
	  *
	  * @testdox can create PaymentIntent
	  */
	function test_create_payment_intent() {
		$this->markTestIncomplete();
	}
	
	/**
	  * Test the confirm_payment_intent() method of the PMProGateway_stripe class.
	  *
	  * @testdox can confirm PaymentIntent
	  */
	function test_confirm_payment_intent() {
		$this->markTestIncomplete();
	}
	
	/**
	  * Test the set_setup_intent() method of the PMProGateway_stripe class.
	  *
	  * @testdox can set SetupIntent
	  */
	function test_set_setup_intent() {
		$this->markTestIncomplete();
	}
	
	/**
	  * Test the set_payment_method() method of the PMProGateway_stripe class.
	  *
	  * @testdox can set PaymentMethod
	  */
	function test_set_payment_method() {
		$this->markTestIncomplete();
	}
	
	/**
	  * Test the attach_payment_method() method of the PMProGateway_stripe class.
	  *
	  * @testdox can attach PaymentMethod to Customer
	  */
	function test_attach_payment_method() {
		$this->markTestIncomplete();
	}
	
}
