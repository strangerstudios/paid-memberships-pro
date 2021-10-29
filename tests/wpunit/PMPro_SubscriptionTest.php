<?php

namespace PMPro\Tests;

use PMPro\Test_Support\TestCases\TestCase;
use PMPro_Subscription;

/**
 * @group pmpro-subscriptions
 */
class PMPro_SubscriptionTest extends TestCase {

	public function test_construct() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function __construct( $subscription = null );
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_subscription() {
		$this->markTestIncomplete( 'Still building this out' );

		// public static function get_subscription( $args = [] );
		$result = PMPro_Subscription::get_subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_subscriptions() {
		$this->markTestIncomplete( 'Still building this out' );

		// public static function get_subscriptions( array $args = [] );
		$result = PMPro_Subscription::get_subscriptions();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_subscriptions_for_user() {
		$this->markTestIncomplete( 'Still building this out' );

		// public static function get_subscriptions_for_user( $user_id = null, $membership_level_id = null, $status = [ 'active' ] );
		$result = PMPro_Subscription::get_subscriptions_for_user();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_subscription_from_subscription_transaction_id() {
		$this->markTestIncomplete( 'Still building this out' );

		// public static function get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment );
		$result = PMPro_Subscription::get_subscription_from_subscription_transaction_id();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_create_subscription() {
		$this->markTestIncomplete( 'Still building this out' );

		// public static function create_subscription( $user_id, $membership_level_id, $subscription_transaction_id, $gateway, $gateway_environment );
		$result = PMPro_Subscription::create_subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_update_from_gateway() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function update_from_gateway();
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_next_payment_date() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function get_next_payment_date( $format = 'timestamp', $local_time = true );
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_startdate() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function get_startdate( $format = 'timestamp', $local_time = true );
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_enddate() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function get_enddate( $format = 'timestamp', $local_time = true );
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_get_gateway_object() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function get_gateway_object();
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_save() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function save();
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}

	public function test_cancel() {
		$this->markTestIncomplete( 'Still building this out' );

		// public function cancel();
		$subscription = new PMPro_Subscription();

		// Do the setup needed here.

		// Add assertions here.
	}


}