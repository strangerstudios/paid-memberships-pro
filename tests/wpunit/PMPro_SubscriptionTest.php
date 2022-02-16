<?php

namespace PMPro\Tests;

use PMPro\Test_Support\TestCases\TestCase;
use PMPro_Subscription;
use PMProGateway;
use PMProGateway_check;

/**
 * @group pmpro-subscriptions
 */
class PMPro_SubscriptionTest extends TestCase {

	/**
	 * @covers PMPro_Subscription::__construct
	 */
	public function test___construct_with_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By integer.
		$subscription = new PMPro_Subscription( (int) $subscription_id );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By numeric string.
		$subscription = new PMPro_Subscription( (string) $subscription_id );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid string.
		$subscription = new PMPro_Subscription( 'something-' . $subscription_id );
		$this->assertEquals( 0, $subscription->get_id() );

		// By invalid string.
		$subscription = new PMPro_Subscription( 'something_' . $subscription_id );
		$this->assertEquals( 0, $subscription->get_id() );

		// By invalid string.
		$subscription = new PMPro_Subscription( 's' . $subscription_id );
		$this->assertEquals( 0, $subscription->get_id() );
	}

	/**
	 * @covers PMPro_Subscription::__construct
	 */
	public function test___construct_with_array() {
		// Basic test data, just enough to test with.
		$subscription_data = [
			'id'                  => '1234',
			'user_id'             => '2345',
			'membership_level_id' => '3456',
			'status'              => 'active',
		];

		// Confirm the data gets set.
		$subscription = new PMPro_Subscription( $subscription_data );
		$this->assertEquals( $subscription_data['id'], $subscription->get_id() );
		$this->assertEquals( $subscription_data['user_id'], $subscription->get_user_id() );
		$this->assertEquals( $subscription_data['membership_level_id'], $subscription->get_membership_level_id() );
		$this->assertEquals( $subscription_data['status'], $subscription->get_status() );

		// Confirm it casts the integers as expected.
		$this->assertInternalType( 'int', $subscription->get_id() );
		$this->assertInternalType( 'int', $subscription->get_user_id() );
		$this->assertInternalType( 'int', $subscription->get_membership_level_id() );
		$this->assertInternalType( 'string', $subscription->get_status() );
	}

	/**
	 * @covers PMPro_Subscription::__construct
	 */
	public function test___construct_with_object() {
		// Basic test data, just enough to test with.
		$subscription_data = (object) [
			'id'                  => '1234',
			'user_id'             => '2345',
			'membership_level_id' => '3456',
			'status'              => 'active',
		];

		// Confirm the data gets set.
		$subscription = new PMPro_Subscription( $subscription_data );
		$this->assertEquals( $subscription_data->id, $subscription->get_id() );
		$this->assertEquals( $subscription_data->user_id, $subscription->get_user_id() );
		$this->assertEquals( $subscription_data->membership_level_id, $subscription->get_membership_level_id() );
		$this->assertEquals( $subscription_data->status, $subscription->get_status() );

		// Confirm it casts the integers as expected.
		$this->assertInternalType( 'int', $subscription->get_id() );
		$this->assertInternalType( 'int', $subscription->get_user_id() );
		$this->assertInternalType( 'int', $subscription->get_membership_level_id() );
		$this->assertInternalType( 'string', $subscription->get_status() );
	}

	/**
	 * @covers PMPro_Subscription::__call
	 * @covers PMPro_Subscription::get_id
	 * @covers PMPro_Subscription::get_user_id
	 * @covers PMPro_Subscription::get_membership_level_id
	 * @covers PMPro_Subscription::get_gateway
	 * @covers PMPro_Subscription::get_gateway_environment
	 * @covers PMPro_Subscription::get_subscription_transaction_id
	 * @covers PMPro_Subscription::get_status
	 * @covers PMPro_Subscription::get_initial_payment
	 * @covers PMPro_Subscription::get_billing_amount
	 * @covers PMPro_Subscription::get_cycle_number
	 * @covers PMPro_Subscription::get_cycle_period
	 * @covers PMPro_Subscription::get_billing_limit
	 * @covers PMPro_Subscription::get_trial_amount
	 * @covers PMPro_Subscription::get_trial_limit
	 */
	public function test___call() {
		$user_id  = $this->factory()->user->create();
		$level_id = $this->factory()->pmpro_level->create();

		$subscription_data = [
			'user_id'                     => $user_id,
			'membership_level_id'         => $level_id,
			'gateway'                     => 'stripe',
			'gateway_environment'         => 'sandbox',
			'subscription_transaction_id' => 'sub_12345',
			'status'                      => 'active',
			'initial_payment'             => '12.34',
			'billing_amount'              => '23.45',
			'cycle_number'                => '7',
			'cycle_period'                => 'Day',
			'billing_limit'               => '14',
			'trial_amount'                => '34.56',
			'trial_limit'                 => '3',
		];

		$subscription_id = $this->factory()->pmpro_subscription->create( $subscription_data );

		$subscription = PMPro_Subscription::get_subscription( (int) $subscription_id );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );

		// Confirm the methods return the expected values.
		$this->assertEquals( $subscription_id, $subscription->get_id() );
		$this->assertEquals( $subscription_data['user_id'], $subscription->get_user_id() );
		$this->assertEquals( $subscription_data['membership_level_id'], $subscription->get_membership_level_id() );
		$this->assertEquals( $subscription_data['gateway'], $subscription->get_gateway() );
		$this->assertEquals( $subscription_data['gateway_environment'], $subscription->get_gateway_environment() );
		$this->assertEquals( $subscription_data['subscription_transaction_id'], $subscription->get_subscription_transaction_id() );
		$this->assertEquals( $subscription_data['status'], $subscription->get_status() );
		$this->assertEquals( $subscription_data['initial_payment'], $subscription->get_initial_payment() );
		$this->assertEquals( $subscription_data['billing_amount'], $subscription->get_billing_amount() );
		$this->assertEquals( $subscription_data['cycle_number'], $subscription->get_cycle_number() );
		$this->assertEquals( $subscription_data['cycle_period'], $subscription->get_cycle_period() );
		$this->assertEquals( $subscription_data['billing_limit'], $subscription->get_billing_limit() );
		$this->assertEquals( $subscription_data['trial_amount'], $subscription->get_trial_amount() );
		$this->assertEquals( $subscription_data['trial_limit'], $subscription->get_trial_limit() );

		// Confirm it returns the types as expected.
		$this->assertInternalType( 'int', $subscription->get_id() );
		$this->assertInternalType( 'int', $subscription->get_user_id() );
		$this->assertInternalType( 'int', $subscription->get_membership_level_id() );
		$this->assertInternalType( 'string', $subscription->get_gateway() );
		$this->assertInternalType( 'string', $subscription->get_gateway_environment() );
		$this->assertInternalType( 'string', $subscription->get_subscription_transaction_id() );
		$this->assertInternalType( 'string', $subscription->get_status() );
		$this->assertInternalType( 'float', $subscription->get_initial_payment() );
		$this->assertInternalType( 'float', $subscription->get_billing_amount() );
		$this->assertInternalType( 'int', $subscription->get_cycle_number() );
		$this->assertInternalType( 'string', $subscription->get_cycle_period() );
		$this->assertInternalType( 'int', $subscription->get_billing_limit() );
		$this->assertInternalType( 'float', $subscription->get_trial_amount() );
		$this->assertInternalType( 'int', $subscription->get_trial_limit() );
	}

	/**
	 * @covers PMPro_Subscription::get_subscription
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By numeric string.
		$subscription = PMPro_Subscription::get_subscription( (string) $subscription_id );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid strings.
		$this->assertNull( PMPro_Subscription::get_subscription( 'something-' . $subscription_id ) );
		$this->assertNull( PMPro_Subscription::get_subscription( 'something_' . $subscription_id ) );
		$this->assertNull( PMPro_Subscription::get_subscription( 's' . $subscription_id ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscription
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By membership level ID array.
		$subscription = PMPro_Subscription::get_subscription( [
			'user_id'             => $user_id,
			'membership_level_id' => [ $level_id ],
		] );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid membership level ID.
		$this->assertNull( PMPro_Subscription::get_subscription( [
			'user_id'             => $user_id,
			'membership_level_id' => 123456,
		] ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscription
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By subscription ID array.
		$subscription = PMPro_Subscription::get_subscription( [
			'id' => [ $subscription_id ],
		] );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid subscription ID.
		$this->assertNull( PMPro_Subscription::get_subscription( [
			'id' => 123456,
		] ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By user ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id' => [ $user_id ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid user ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'user_id' => 123456,
		] ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By membership level ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => [ $level_id ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid membership level ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'user_id'             => $user_id,
			'membership_level_id' => 123456,
		] ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

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

	/**
	 * @covers PMPro_Subscription::get_subscriptions
	 */
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
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By subscription ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions( [
			'id' => [ $subscription_id ],
		] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid subscription ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions( [
			'id' => 123456,
		] ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions_for_user
	 */
	public function test_get_subscriptions_for_user_with_user_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By user ID.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By user ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( [ $user_id ] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid user ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions_for_user( 123456 ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions_for_user
	 */
	public function test_get_subscriptions_for_user_with_current_user_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		wp_set_current_user( $user_id );

		// By using current user.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user();
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By using a different user.
		wp_set_current_user( 1 );

		$this->assertCount( 0, PMPro_Subscription::get_subscriptions_for_user() );

		// By using no current user.
		wp_set_current_user( 0 );

		$this->assertCount( 0, PMPro_Subscription::get_subscriptions_for_user() );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions_for_user
	 */
	public function test_get_subscriptions_for_user_with_membership_level_id() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
		] );

		// By membership level ID.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By membership level ID array.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, [ $level_id ] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid membership level ID.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions_for_user( $user_id, 123456 ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscriptions_for_user
	 */
	public function test_get_subscriptions_for_user_with_status() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'status'              => 'cancelled',
		] );

		// By status.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, 'cancelled' );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By status array.
		$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, [ 'cancelled' ] );
		$this->assertCount( 1, $subscriptions );
		$subscription = reset( $subscriptions ); // Get first subscription.
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By default, it uses active status and there should be with no match.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id ) );

		// By invalid status.
		$this->assertCount( 0, PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, 'status123456' ) );
	}

	/**
	 * @covers PMPro_Subscription::get_subscription_from_subscription_transaction_id
	 */
	public function test_get_subscription_from_subscription_transaction_id() {
		$subscription_transaction_id = 'sub_1111111';
		$gateway                     = 'stripe';
		$gateway_environment         = 'live';

		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id'         => $level_id,
			'user_id'                     => $user_id,
			'subscription_transaction_id' => $subscription_transaction_id,
			'gateway'                     => $gateway,
			'gateway_environment'         => $gateway_environment,
		] );

		// By subscription transaction ID.
		$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, $gateway_environment );
		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );
		$this->assertEquals( $subscription_id, $subscription->get_id() );

		// By invalid subscription transaction ID.
		$this->assertNull( PMPro_Subscription::get_subscription_from_subscription_transaction_id( 'sub_404', $gateway, $gateway_environment ) );

		// By different gateway.
		$this->assertNull( PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscription_transaction_id, 'paypal', $gateway_environment ) );

		// By different gateway environment.
		$this->assertNull( PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscription_transaction_id, $gateway, 'eh' ) );
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

	/**
	 * @covers PMPro_Subscription::get_next_payment_date
	 */
	public function test_get_next_payment_date() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'next_payment_date'   => '2021-01-02 03:04:05',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		$this->assertInstanceOf( PMPro_Subscription::class, $subscription );

		// Local timezone is UTC-5.

		// Timestamps should always be in UTC no matter what local parameter is set to.
		$this->assertEquals( 1609556645, $subscription->get_next_payment_date() );
		$this->assertEquals( 1609556645, $subscription->get_next_payment_date( 'timestamp', true ) );
		$this->assertEquals( 1609556645, $subscription->get_next_payment_date( 'timestamp', false ) );

		// Test WP date format.
		$this->assertEquals( 'January 1, 2021', $subscription->get_next_payment_date( 'date_format' ) );
		$this->assertEquals( 'January 2, 2021', $subscription->get_next_payment_date( 'date_format', false ) );

		// Test custom format.
		$this->assertEquals( '2021-01-01 22:04:05 YAY', $subscription->get_next_payment_date( 'Y-m-d H:i:s \Y\A\Y' ) );
		$this->assertEquals( '2021-01-02 03:04:05 YAY', $subscription->get_next_payment_date( 'Y-m-d H:i:s \Y\A\Y', false ) );
	}

	/**
	 * @covers PMPro_Subscription::get_startdate
	 */
	public function test_get_startdate() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'startdate'           => '2021-01-02 03:04:05',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		// Local timezone is UTC-5.

		// Timestamps should always be in UTC no matter what local parameter is set to.
		$this->assertEquals( 1609556645, $subscription->get_startdate() );
		$this->assertEquals( 1609556645, $subscription->get_startdate( 'timestamp', true ) );
		$this->assertEquals( 1609556645, $subscription->get_startdate( 'timestamp', false ) );

		// Test WP date format.
		$this->assertEquals( 'January 1, 2021', $subscription->get_startdate( 'date_format' ) );
		$this->assertEquals( 'January 2, 2021', $subscription->get_startdate( 'date_format', false ) );

		// Test custom format.
		$this->assertEquals( '2021-01-01 22:04:05 YAY', $subscription->get_startdate( 'Y-m-d H:i:s \Y\A\Y' ) );
		$this->assertEquals( '2021-01-02 03:04:05 YAY', $subscription->get_startdate( 'Y-m-d H:i:s \Y\A\Y', false ) );
	}

	/**
	 * @covers PMPro_Subscription::get_enddate
	 */
	public function test_get_enddate() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'enddate'             => '2021-01-02 03:04:05',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		// Local timezone is UTC-5.

		// Timestamps should always be in UTC no matter what local parameter is set to.
		$this->assertEquals( 1609556645, $subscription->get_enddate() );
		$this->assertEquals( 1609556645, $subscription->get_enddate( 'timestamp', true ) );
		$this->assertEquals( 1609556645, $subscription->get_enddate( 'timestamp', false ) );

		// Test WP date format.
		$this->assertEquals( 'January 1, 2021', $subscription->get_enddate( 'date_format' ) );
		$this->assertEquals( 'January 2, 2021', $subscription->get_enddate( 'date_format', false ) );

		// Test custom format.
		$this->assertEquals( '2021-01-01 22:04:05 YAY', $subscription->get_enddate( 'Y-m-d H:i:s \Y\A\Y' ) );
		$this->assertEquals( '2021-01-02 03:04:05 YAY', $subscription->get_enddate( 'Y-m-d H:i:s \Y\A\Y', false ) );
	}

	/**
	 * @covers PMPro_Subscription::get_gateway_object
	 */
	public function test_get_gateway_object_with_valid_gateway() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'gateway'             => 'check',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		// Confirm the class is exactly the class we expected.
		$gateway_object = $subscription->get_gateway_object();
		$this->assertInstanceOf( PMProGateway::class, $gateway_object );
		$this->assertEquals( PMProGateway_check::class, get_class( $gateway_object ) );
	}

	/**
	 * @covers PMPro_Subscription::get_gateway_object
	 */
	public function test_get_gateway_object_with_free_gateway() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'gateway'             => 'free',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		// Confirm the class is exactly the class we expected.
		$gateway_object = $subscription->get_gateway_object();
		$this->assertInstanceOf( PMProGateway::class, $gateway_object );
		$this->assertEquals( PMProGateway::class, get_class( $gateway_object ) );
	}

	/**
	 * @covers PMPro_Subscription::get_gateway_object
	 */
	public function test_get_gateway_object_with_invalid_gateway() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'gateway'             => 'gateway404',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		$this->assertNull( $subscription->get_gateway_object() );
	}

	/**
	 * @covers PMPro_Subscription::get_gateway_object
	 */
	public function test_get_gateway_object_with_no_gateway() {
		$user_id         = $this->factory()->user->create();
		$level_id        = $this->factory()->pmpro_level->create();
		$subscription_id = $this->factory()->pmpro_subscription->create( [
			'membership_level_id' => $level_id,
			'user_id'             => $user_id,
			'gateway'             => '',
		] );

		$subscription = PMPro_Subscription::get_subscription( $subscription_id );

		$this->assertNull( $subscription->get_gateway_object() );
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