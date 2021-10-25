<?php

namespace PMPro\Tests\Settings\Orders;

use AcceptanceTester;
use PMPro\Test_Support\TestCases\AcceptanceTestCase;

/**
 * @group pmpro-email
 */
class EmailInvoiceCest extends AcceptanceTestCase {

	public function _before( AcceptanceTester $I ) {
		$I->haveOptionInDatabase( 'active_plugins', [ 'paid-memberships-pro/paid-memberships-pro.php' ] );

		$I->loginAsAdmin();

		$I->amOnAdminPage( '/admin.php?page=pmpro-orders' );

		$I->see( 'Add New Order', '#posts-filter a.page-title-action' );
		$I->click( '#posts-filter a.page-title-action' );
	}

	/**
	 * Get the default content used for testing.
	 *
	 * @return string[] The default content.
	 */
	private function get_default_content() {
		return [
			'code'                        => '',
			'user_id'                     => '',
			'membership_id'               => '',
			'billing_name'                => '',
			'billing_street'              => '',
			'billing_city'                => '',
			'billing_state'               => '',
			'billing_zip'                 => '',
			'billing_country'             => '',
			'billing_phone'               => '',
			'subtotal'                    => '',
			'tax'                         => '',
			'total'                       => '',
			'payment_type'                => '',
			'cardtype'                    => '',
			'accountnumber'               => '',
			'expirationmonth'             => '',
			'expirationyear'              => '',
			'status'                      => 'success',
			'gateway'                     => '',
			'gateway_environment'         => 'sandbox',
			'payment_transaction_id'      => '',
			'subscription_transaction_id' => '',
			'ts_month'                    => '',
			'ts_day'                      => '',
			'ts_year'                     => '',
			'ts_hour'                     => '',
			'ts_minute'                   => '',
			'notes'                       => '',
		];
	}

	/**
	 * Fill in the add new form.
	 *
	 * @param AcceptanceTester $I       The tester instance.
	 * @param array            $content The content to fill in.
	 */
	private function fill_in_form( AcceptanceTester $I, array $content = [] ) {
		$content = array_merge( $this->get_default_content(), $content );

		// If code is empty, just check for a non-empty value.
		if ( '' === $content['code'] ) {
			// Code will default to a newly random generated code so this is a workaround.
			$I->dontSeeInField( '.form-table input[name="code"]', '' );
		} else {
			$I->fillField( '.form-table input[name="code"]', $content['code'] );
		}

		$I->fillField( '.form-table input[name="user_id"]', $content['user_id'] );
		$I->fillField( '.form-table input[name="membership_id"]', $content['membership_id'] );
		$I->fillField( '.form-table input[name="billing_name"]', $content['billing_name'] );
		$I->fillField( '.form-table input[name="billing_street"]', $content['billing_street'] );
		$I->fillField( '.form-table input[name="billing_city"]', $content['billing_city'] );
		$I->fillField( '.form-table input[name="billing_state"]', $content['billing_state'] );
		$I->fillField( '.form-table input[name="billing_zip"]', $content['billing_zip'] );
		$I->fillField( '.form-table input[name="billing_country"]', $content['billing_country'] );
		$I->fillField( '.form-table input[name="billing_phone"]', $content['billing_phone'] );
		$I->fillField( '.form-table input[name="subtotal"]', $content['subtotal'] );
		$I->fillField( '.form-table input[name="tax"]', $content['tax'] );
		$I->fillField( '.form-table input[name="total"]', $content['total'] );
		$I->fillField( '.form-table input[name="payment_type"]', $content['payment_type'] );
		$I->fillField( '.form-table input[name="cardtype"]', $content['cardtype'] );
		$I->fillField( '.form-table input[name="accountnumber"]', $content['accountnumber'] );
		$I->fillField( '.form-table input[name="expirationmonth"]', $content['expirationmonth'] );
		$I->fillField( '.form-table input[name="expirationyear"]', $content['expirationyear'] );
		$I->selectOption( '.form-table select[name="status"]', $content['status'] );
		$I->selectOption( '.form-table select[name="gateway"]', $content['gateway'] );
		$I->selectOption( '.form-table select[name="gateway_environment"]', $content['gateway_environment'] );
		$I->fillField( '.form-table input[name="payment_transaction_id"]', $content['payment_transaction_id'] );
		$I->fillField( '.form-table input[name="subscription_transaction_id"]', $content['subscription_transaction_id'] );

		// If month is empty, just check for a non-empty value.
		if ( '' === $content['ts_month'] ) {
			// Date defaults to current date/time but that's tricky to test for so this is a workaround.
			$I->dontSeeInField( '.form-table select[name="ts_month"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_day"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_year"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_hour"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_minute"]', '' );
		} else {
			$I->selectOption( '.form-table select[name="ts_month"]', $content['ts_month'] );
			$I->fillField( '.form-table input[name="ts_day"]', $content['ts_day'] );
			$I->fillField( '.form-table input[name="ts_year"]', $content['ts_year'] );
			$I->fillField( '.form-table input[name="ts_hour"]', $content['ts_hour'] );
			$I->fillField( '.form-table input[name="ts_minute"]', $content['ts_minute'] );
		}

		$I->fillField( '.form-table textarea[name="notes"]', $content['notes'] );
	}

	/**
	 * See in the add new form.
	 *
	 * @param AcceptanceTester $I       The tester instance.
	 * @param array            $content The content to look for (leave empty to use defaults).
	 */
	private function see_in_form( AcceptanceTester $I, array $content = [] ) {
		$content = array_merge( $this->get_default_content(), $content );

		// If code is empty, just check for a non-empty value.
		if ( '' === $content['code'] ) {
			// Code will default to a newly random generated code so this is a workaround.
			$I->dontSeeInField( '.form-table input[name="code"]', '' );
		} else {
			$I->seeInField( '.form-table input[name="code"]', $content['code'] );
		}

		$I->seeInField( '.form-table input[name="user_id"]', $content['user_id'] );
		$I->seeInField( '.form-table input[name="membership_id"]', $content['membership_id'] );
		$I->seeInField( '.form-table input[name="billing_name"]', $content['billing_name'] );
		$I->seeInField( '.form-table input[name="billing_street"]', $content['billing_street'] );
		$I->seeInField( '.form-table input[name="billing_city"]', $content['billing_city'] );
		$I->seeInField( '.form-table input[name="billing_state"]', $content['billing_state'] );
		$I->seeInField( '.form-table input[name="billing_zip"]', $content['billing_zip'] );
		$I->seeInField( '.form-table input[name="billing_country"]', $content['billing_country'] );
		$I->seeInField( '.form-table input[name="billing_phone"]', $content['billing_phone'] );
		$I->seeInField( '.form-table input[name="subtotal"]', $content['subtotal'] );
		$I->seeInField( '.form-table input[name="tax"]', $content['tax'] );
		$I->seeInField( '.form-table input[name="total"]', $content['total'] );
		$I->seeInField( '.form-table input[name="payment_type"]', $content['payment_type'] );
		$I->seeInField( '.form-table input[name="cardtype"]', $content['cardtype'] );
		$I->seeInField( '.form-table input[name="accountnumber"]', $content['accountnumber'] );
		$I->seeInField( '.form-table input[name="expirationmonth"]', $content['expirationmonth'] );
		$I->seeInField( '.form-table input[name="expirationyear"]', $content['expirationyear'] );
		$I->seeInField( '.form-table select[name="status"]', $content['status'] );
		$I->seeInField( '.form-table select[name="gateway"]', $content['gateway'] );
		$I->seeInField( '.form-table select[name="gateway_environment"]', $content['gateway_environment'] );
		$I->seeInField( '.form-table input[name="payment_transaction_id"]', $content['payment_transaction_id'] );
		$I->seeInField( '.form-table input[name="subscription_transaction_id"]', $content['subscription_transaction_id'] );

		// If month is empty, just check for a non-empty value.
		if ( '' === $content['ts_month'] ) {
			// Date defaults to current date/time but that's tricky to test for so this is a workaround.
			$I->dontSeeInField( '.form-table select[name="ts_month"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_day"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_year"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_hour"]', '' );
			$I->dontSeeInField( '.form-table input[name="ts_minute"]', '' );
		} else {
			$I->seeInField( '.form-table select[name="ts_month"]', $content['ts_month'] );
			$I->seeInField( '.form-table input[name="ts_day"]', $content['ts_day'] );
			$I->seeInField( '.form-table input[name="ts_year"]', $content['ts_year'] );
			$I->seeInField( '.form-table input[name="ts_hour"]', $content['ts_hour'] );
			$I->seeInField( '.form-table input[name="ts_minute"]', $content['ts_minute'] );
		}

		$I->seeInField( '.form-table textarea[name="notes"]', $content['notes'] );
	}

	/**
	 * It should show default values in fields.
	 *
	 * @param AcceptanceTester $I The tester instance.
	 */
	public function should_show_default_values_in_fields( AcceptanceTester $I ) {
		$this->see_in_form( $I );
	}

	/**
	 * It should allow emailing invoice.
	 *
	 * @param AcceptanceTester $I The tester instance.
	 */
	public function should_allow_emailing_invoice( AcceptanceTester $I ) {
		$user_id  = $I->factory()->user->create();
		$level_id = $I->factory()->pmpro_level->create();

		$test_user = get_userdata( $user_id );

		$content = [
			'code'                        => 'ABCDEFG',
			'user_id'                     => $user_id,
			'membership_id'               => $level_id,
			'billing_name'                => 'Jason Toleman',
			'billing_street'              => '123 Strange St',
			'billing_city'                => 'Studios',
			'billing_state'               => 'PA',
			'billing_zip'                 => '12333',
			'billing_country'             => 'US',
			'billing_phone'               => '123-456-7890',
			'subtotal'                    => '10.00',
			'tax'                         => '1.23',
			'total'                       => '11.23',
			'payment_type'                => 'Credit Card',
			'cardtype'                    => 'Mastercard',
			'accountnumber'               => '************1234',
			'expirationmonth'             => '10',
			'expirationyear'              => '2031',
			'status'                      => 'success',
			'gateway'                     => 'stripe',
			'gateway_environment'         => 'sandbox',
			'payment_transaction_id'      => 'ch_12345678',
			'subscription_transaction_id' => 'sub_23456',
			'ts_month'                    => '10',
			'ts_day'                      => '2',
			'ts_year'                     => '2006',
			'ts_hour'                     => '6',
			'ts_minute'                   => '30',
			'notes'                       => 'Test order for Email Invoice testing',
		];

		$this->fill_in_form( $I, $content );

		$I->click( 'input[name="save"]' );

		$I->see( $content['code'], 'table tbody#orders td.column-order_code' );
		$I->see( 'Email', 'table tbody#orders td.column-order_code .row-actions span.email a' );

		$I->click( 'table tbody#orders td.column-order_code .row-actions span.email a' );

		try {
			$I->waitForElement( '#TB_window #TB_ajaxContent' );
		} catch ( \Exception $exception ) {
			// Some weird case?
		}

		$I->see( 'Email Invoice', '#TB_window #TB_ajaxContent h3' );

		$I->seeInField( '#TB_window #TB_ajaxContent input[name="email"]', $test_user->user_email );

		$I->see( 'Send Email', '#TB_window #TB_ajaxContent button.button-primary' );

		// @todo Handle Mail Catcher stuff once it's implemented in docker container setup.
		return;

		$I->resetEmails();

		$I->click( '#TB_window #TB_ajaxContent button.button-primary' );

		$I->seeEmailCount( 1 );

		$test_catch_email = $I->grabLastEmail();

		/*
		 * Snapshot testing.
		 */

		// Set up the driver for flexibility.
		$driver = new WPHtmlOutputDriver();

		// Allow differences in IDs, generated emails, and the display name.
		$driver->setTolerableDifferences( [
			$test_user->user_email,
			$test_user->display_name,
			get_bloginfo( 'admin_email' ),
			get_bloginfo( 'name' ),
			/*date_i18n( get_option( 'date_format' ) ),
			date_i18n( get_option( 'date_format' ), strtotime( '+1 day' ) ),
			date_i18n( get_option( 'date_format' ), strtotime( '-1 day' ) ),*/
			$level_id,
			$user_id,
		] );

		$this->assertMatchesSnapshot( $test_catch_email['subject'], $driver );
		$this->assertMatchesSnapshot( $test_catch_email['message'], $driver );
	}
}
