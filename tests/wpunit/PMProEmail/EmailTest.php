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
			],
			'cancel_admin'             => [
				'cancel_admin',
			],
			'checkout_check'           => [
				'checkout_check',
			],
			'checkout_express'         => [
				'checkout_express',
			],
			'checkout_free'            => [
				'checkout_free',
			],
			'checkout_freetrial'       => [
				'checkout_freetrial',
			],
			'checkout_paid'            => [
				'checkout_paid',
			],
			'checkout_trial'           => [
				'checkout_trial',
			],
			'checkout_check_admin'     => [
				'checkout_check_admin',
			],
			'checkout_express_admin'   => [
				'checkout_express_admin',
			],
			'checkout_free_admin'      => [
				'checkout_free_admin',
			],
			'checkout_freetrial_admin' => [
				'checkout_freetrial_admin',
			],
			'checkout_paid_admin'      => [
				'checkout_paid_admin',
			],
			'checkout_trial_admin'     => [
				'checkout_trial_admin',
			],
			'billing'                  => [
				'billing',
			],
			'billing_admin'            => [
				'billing_admin',
			],
			'billing_failure'          => [
				'billing_failure',
			],
			'billing_failure_admin'    => [
				'billing_failure_admin',
			],
			'credit_card_expiring'     => [
				'credit_card_expiring',
			],
			'invoice'                  => [
				'invoice',
			],
			'trial_ending'             => [
				'trial_ending',
			],
			'membership_expired'       => [
				'membership_expired',
			],
			'membership_expiring'      => [
				'membership_expiring',
			],
			'payment_action'           => [
				'payment_action',
			],
			'payment_action_admin'     => [
				'payment_action_admin',
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
		$user_id  = $this->factory()->user->create();
		$level_id = $this->factory()->pmpro_level->create();

		// Set current user.
		wp_set_current_user( $user_id );

		// Set up test email.
		$test_email           = new PMProEmail();
		$test_email->to       = 'dev@test.pmpro.local';
		$test_email->template = $template;

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
		$test_order->get_test_order();

		// Set up test user.
		$test_user                   = wp_get_current_user();
		$test_user->membership_level = pmpro_getLevel( $level_id );

		//force the template
		//add_filter( 'pmpro_email_filter', 'pmpro_email_templates_test_template', 5, 1 );

		global $test_catch_email;

		$test_catch_email = false;

		// Catch the email details.
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
		$this->assertEquals( $test_email->to, $test_catch_email['to'] );
		$this->assertContains( 'Content-Type: text/html', $test_catch_email['headers'] );
		$this->assertEquals( [], $test_catch_email['attachments'] );

		/*
		 * Snapshot testing.
		 */

		// Set up the driver for flexibility.
		$driver = new WPHtmlOutputDriver( home_url(), 'https://wordpress.test' );

		// Allow differences in IDs, generated emails, and the display name.
		$driver->setTolerableDifferences( [
			$test_user->user_email,
			$test_email->display_name,
			get_bloginfo( 'name' ),
			$level_id,
			$user_id,
		] );

		/*$this->assertMatchesSnapshot( $test_catch_email['subject'], $driver );
		$this->assertMatchesSnapshot( $test_catch_email['message'], $driver );*/

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
				$params     = [ $user->user_email, $order ];
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