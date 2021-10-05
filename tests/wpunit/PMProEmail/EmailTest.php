<?php

namespace PMPro\Tests\PMProEmail;

use MemberOrder;
use PMPro\Test_Support\TestCases\TestCase;
use PMProEmail;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use WP_User;

/**
 * @group pmpro-email
 */
class EmailTest extends TestCase {

	use MatchesSnapshots;

	/**
	 * Data provider for email templates to be tested.
	 */
	public function data_test_templates() {
		return [
			// 'Name of data set' => [
			// $template,
			// $extra_params,
			// ],
			'cancel'                   => [
				'cancel',
				[
					'to' => 'member',
				],
			],
			'cancel_admin'             => [
				'cancel_admin',
				[
					'to' => 'admin',
				],
			],
			'checkout_check'           => [
				'checkout_check',
				[
					'to' => 'member',
				],
			],
			'checkout_express'         => [
				'checkout_express',
				[
					'to' => 'member',
				],
			],
			'checkout_free'            => [
				'checkout_free',
				[
					'to' => 'member',
				],
			],
			'checkout_freetrial'       => [
				'checkout_freetrial',
				[
					'to' => 'member',
				],
			],
			'checkout_paid'            => [
				'checkout_paid',
				[
					'to' => 'member',
				],
			],
			'checkout_trial'           => [
				'checkout_trial',
				[
					'to' => 'member',
				],
			],
			'checkout_check_admin'     => [
				'checkout_check_admin',
				[
					'to' => 'admin',
				],
			],
			'checkout_express_admin'   => [
				'checkout_express_admin',
				[
					'to' => 'admin',
				],
			],
			'checkout_free_admin'      => [
				'checkout_free_admin',
				[
					'to' => 'admin',
				],
			],
			'checkout_freetrial_admin' => [
				'checkout_freetrial_admin',
				[
					'to' => 'admin',
				],
			],
			'checkout_paid_admin'      => [
				'checkout_paid_admin',
				[
					'to' => 'admin',
				],
			],
			'checkout_trial_admin'     => [
				'checkout_trial_admin',
				[
					'to' => 'admin',
				],
			],
			'billing'                  => [
				'billing',
				[
					'to' => 'member',
				],
			],
			'billing_admin'            => [
				'billing_admin',
				[
					'to' => 'admin',
				],
			],
			'billing_failure'          => [
				'billing_failure',
				[
					'to' => 'member',
				],
			],
			'billing_failure_admin'    => [
				'billing_failure_admin',
				[
					'to' => 'admin',
				],
			],
			'credit_card_expiring'     => [
				'credit_card_expiring',
				[
					'to' => 'member',
				],
			],
			'invoice'                  => [
				'invoice',
				[
					'to' => 'member',
				],
			],
			'trial_ending'             => [
				'trial_ending',
				[
					'to' => 'member',
				],
			],
			'membership_expired'       => [
				'membership_expired',
				[
					'to' => 'member',
				],
			],
			'membership_expiring'      => [
				'membership_expiring',
				[
					'to' => 'member',
				],
			],
			'payment_action'           => [
				'payment_action',
				[
					'to' => 'member',
				],
			],
			'payment_action_admin'     => [
				'payment_action_admin',
				[
					'to' => 'admin',
				],
			],
			'custom_email_template'    => [
				'custom_email_template',
				[
					'subject' => 'Custom email subject',
					'body'    => 'Custom email content',
				],
			],
		];
	}

	/**
	 * Test the PMProEmail templates.
	 *
	 * @dataProvider data_test_templates
	 *
	 * @covers       PMProEmail::sendCancelEmail()
	 * @covers       PMProEmail::sendCancelAdminEmail()
	 * @covers       PMProEmail::sendCheckoutEmail()
	 * @covers       PMProEmail::sendCheckoutAdminEmail()
	 * @covers       PMProEmail::sendBillingEmail()
	 * @covers       PMProEmail::sendBillingAdminEmail()
	 * @covers       PMProEmail::sendBillingFailureEmail()
	 * @covers       PMProEmail::sendBillingFailureAdminEmail()
	 * @covers       PMProEmail::sendCreditCardExpiringEmail()
	 * @covers       PMProEmail::sendInvoiceEmail()
	 * @covers       PMProEmail::sendTrialEndingEmail()
	 * @covers       PMProEmail::sendMembershipExpiredEmail()
	 * @covers       PMProEmail::sendMembershipExpiringEmail()
	 * @covers       PMProEmail::sendPaymentActionRequiredEmail()
	 * @covers       PMProEmail::sendPaymentActionRequiredAdminEmail()
	 * @covers       PMProEmail::sendEmail()
	 */
	public function test_send_email_template( $template, $params = [] ) {
		// Set up test data.
		$user_id     = $this->factory()->user->create();
		$level_id    = $this->factory()->pmpro_level->create();
		$admin_email = get_bloginfo( 'admin_email' );

		// Set the membership level for the user.
		pmpro_changeMembershipLevel( $level_id, $user_id );

		// Set current user.
		wp_set_current_user( $user_id );

		// Set up test email.
		$test_email           = new PMProEmail();
		$test_email->template = $template;

		// Set the default to address as the admin email.
		$test_email->to    = $admin_email;
		$test_email->email = $admin_email;

		// Check for subject override.
		if ( ! empty( $params['subject'] ) ) {
			$test_email->subject = $params['subject'];
		}

		// Check for body override.
		if ( ! empty( $params['body'] ) ) {
			$test_email->body = $params['body'];
		}

		// Set up test order.
		$test_order = new MemberOrder();
		$test_order->get_test_order( [
			'membership_id' => $level_id,
		] );

		// Set up test user.
		$test_user = wp_get_current_user();

		// Catch the email details.
		global $test_catch_email;

		$test_catch_email = false;

		add_filter( 'pre_wp_mail', static function ( $return, $atts ) {
			$GLOBALS['test_catch_email'] = $atts;

			return true;
		}, 10, 2 );

		// Send the email.
		$this->assertTrue( $this->send_test_email( $test_email, $test_user, $test_order ) );

		// Debug output for confirming tests are working.
		codecept_debug( var_export( $test_catch_email, true ) );

		// Confirm we received the email data.
		$this->assertIsArray( $test_catch_email );

		// Basic tests on the email itself.
		$this->assertContains( 'Content-Type: text/html', $test_catch_email['headers'] );
		$this->assertEquals( [], $test_catch_email['attachments'] );

		// Check the email was sent to the correct person.
		if ( isset( $params['to'] ) && 'member' === $params['to'] ) {
			$this->assertEquals( $test_user->user_email, $test_catch_email['to'] );
		} else {
			$this->assertEquals( $admin_email, $test_catch_email['to'] );
		}

		/*
		 * Snapshot testing.
		 */

		// Set up the driver for flexibility.
		$driver = new WPHtmlOutputDriver();

		// Allow differences in IDs, generated emails, and the display name.
		$driver->setTolerableDifferences( [
			$test_user->user_email,
			$test_user->display_name,
			$admin_email,
			get_bloginfo( 'name' ),
			$level_id,
			$user_id,
		] );

		$this->assertMatchesSnapshot( $test_catch_email['subject'], $driver );
		$this->assertMatchesSnapshot( $test_catch_email['message'], $driver );
	}

	/**
	 * Handle sending test emails.
	 */
	private function send_test_email( PMProEmail $email, WP_User $user, MemberOrder $order ) {
		// Set up the method and params to use for sending the email.
		switch ( $email->template ) {
			case 'cancel':
				$send_email = 'sendCancelEmail';
				$params     = [ $user ];
				break;
			case 'cancel_admin':
				$send_email = 'sendCancelAdminEmail';
				$params     = [ $user, $user->membership_level->id ];
				break;
			case 'checkout_check':
			case 'checkout_express':
			case 'checkout_free':
			case 'checkout_freetrial':
			case 'checkout_paid':
			case 'checkout_trial':
				$send_email = 'sendCheckoutEmail';
				$params     = [ $user, $order ];
				break;
			case 'checkout_check_admin':
			case 'checkout_express_admin':
			case 'checkout_free_admin':
			case 'checkout_freetrial_admin':
			case 'checkout_paid_admin':
			case 'checkout_trial_admin':
				$send_email = 'sendCheckoutAdminEmail';
				$params     = [ $user, $order ];
				break;
			case 'billing':
				$send_email = 'sendBillingEmail';
				$params     = [ $user, $order ];
				break;
			case 'billing_admin':
				$send_email = 'sendBillingAdminEmail';
				$params     = [ $user, $order ];
				break;
			case 'billing_failure':
				$send_email = 'sendBillingFailureEmail';
				$params     = [ $user, $order ];
				break;
			case 'billing_failure_admin':
				$send_email = 'sendBillingFailureAdminEmail';
				$params     = [ get_bloginfo( 'admin_email' ), $order ];
				break;
			case 'credit_card_expiring':
				$send_email = 'sendCreditCardExpiringEmail';
				$params     = [ $user, $order ];
				break;
			case 'invoice':
				$send_email = 'sendInvoiceEmail';
				$params     = [ $user, $order ];
				break;
			case 'trial_ending':
				$send_email = 'sendTrialEndingEmail';
				$params     = [ $user ];
				break;
			case 'membership_expired';
				$send_email = 'sendMembershipExpiredEmail';
				$params     = [ $user ];
				break;
			case 'membership_expiring';
				$send_email = 'sendMembershipExpiringEmail';
				$params     = [ $user ];
				break;
			case 'payment_action':
				$send_email = 'sendPaymentActionRequiredEmail';
				$params     = [ $user, $order, 'http://www.example-notification-url.com/not-a-real-site' ];
				break;
			case 'payment_action_admin':
				$send_email = 'sendPaymentActionRequiredAdminEmail';
				$params     = [ $user, $order, 'http://www.example-notification-url.com/not-a-real-site' ];
				break;
			default:
				$send_email = 'sendEmail';
				$params     = [];
		}

		return call_user_func_array( [ $email, $send_email ], $params );
	}
}
