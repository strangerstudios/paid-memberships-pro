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

	public function test_get_subscription_with_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By integer.
		$subscription = PMPro_Subscription::get_subscription( (int) $subscription_id );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By numeric string.
		$subscription = PMPro_Subscription::get_subscription( (string) $subscription_id );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid strings.
		$this->assertNull( PMPro_Subscription::get_subscription( 'something-' . $subscription_id ) );
		$this->assertNull( PMPro_Subscription::get_subscription( 'something_' . $subscription_id ) );
		$this->assertNull( PMPro_Subscription::get_subscription( 's' . $subscription_id ) );
	}

	public function test_get_subscription_with_args_with_membership_level_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By membership level ID.
		$subscription = PMPro_Subscription::get_subscription( [
			'user_id'             => $user_id,
			'membership_level_id' => $level_id,
		] );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By membership level ID array.
		$subscription = PMPro_Subscription::get_subscription( [
			'user_id'             => $user_id,
			'membership_level_id' => [ $level_id ],
		] );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid membership level ID.
		$this->assertNull( PMPro_Subscription::get_subscription( [
			'user_id'             => $user_id,
			'membership_level_id' => 123456, // Wrong level ID.
		] ) );
	}

	public function test_get_subscription_with_args_with_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By subscription ID.
		$subscription = PMPro_Subscription::get_subscription( [
			'id' => $subscription_id,
		] );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By subscription ID array.
		$subscription = PMPro_Subscription::get_subscription( [
			'id' => [ $subscription_id ],
		] );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid subscription ID.
		$this->assertNull( PMPro_Subscription::get_subscription( [
			'id' => 123456, // Wrong ID.
		] ) );
	}

	public function test_get_subscriptions_with_args_with_user_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By user ID.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id' => $user_id,
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By user ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id' => [ $user_id ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid user ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'user_id' => 123456,
		] ) );
	}

	public function test_get_subscriptions_with_args_with_membership_level_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By membership level ID.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => $level_id,
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By membership level ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => [ $level_id ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid membership level ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => 123456, // Wrong level ID.
		] ) );
	}

	public function test_get_subscriptions_with_args_gateway() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By gateway and gateway environment.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => $level_id,
			'gateway'             => 'check',
			'gateway_environment' => 'sandbox',
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By gateway and gateway environment arrays.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => $level_id,
			'gateway'             => [ 'check' ],
			'gateway_environment' => [ 'sandbox' ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid gateway and valid gateway environment.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => $level_id,
			'gateway'             => 'checkers', // Wrong gateway.
			'gateway_environment' => 'sandbox',
		] ) );

		// By valid gateway and invalid gateway environment.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => $level_id,
			'gateway'             => 'check',
			'gateway_environment' => 'sandybox', // Wrong gateway environment.
		] ) );
	}

	public function test_get_subscriptions_with_args_with_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By subscription ID.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'id' => $subscription_id,
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By subscription ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'id' => [ $subscription_id ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertAttributeEquals( $subscription_id, 'id', $subscription );

		// By invalid subscription ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'id' => 123456, // Wrong ID.
		] ) );
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