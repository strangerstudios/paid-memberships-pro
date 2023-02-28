<?php

namespace PMPro\Tests\Settings\Levels;

use AcceptanceTester;

class AddLevelCest {

	public function _before( AcceptanceTester $I ) {
		$I->haveOptionInDatabase( 'active_plugins', [ 'paid-memberships-pro/paid-memberships-pro.php' ] );

		$I->loginAsAdmin();

		$I->amOnAdminPage( '/admin.php?page=pmpro-membershiplevels' );

		$I->see( 'Add new level', 'a.page-title-action' );
		$I->click( 'a.page-title-action' );

		//$I->see( 'Create a Membership Level', '.pmpro-new-install a.button-primary' );
		//$I->click( '.pmpro-new-install a.button-primary' );
	}

	/**
	 * Get the default content used for testing.
	 *
	 * @return string[] The default content.
	 */
	private function get_default_content() {
		return [
			'name'                  => '',
			'description'           => '',
			'confirmation'          => '',
			'confirmation_in_email' => '',
			'initial_payment'       => '0',
			'recurring'             => '',
			'billing_amount'        => '0',
			'cycle_number'          => '1',
			'cycle_period'          => 'Month',
			'billing_limit'         => '',
			'custom_trial'          => '',
			'trial_amount'          => '0',
			'trial_limit'           => '',
			'disable_signups'       => '',
			'expiration'            => '',
			'expiration_number'     => '',
			'expiration_period'     => 'Hour',
			'pbc_setting'           => '0',
			'pbc_renewal_days'      => '',
			'pbc_reminder_days'     => '',
			'pbc_cancel_days'       => '',
			'membershipcategory_1'  => '',
		];
	}

	/**
	 * Fill in the add new level form.
	 *
	 * @param AcceptanceTester $I       The tester instance.
	 * @param array            $content The content to fill in.
	 */
	private function fill_in_form( AcceptanceTester $I, array $content = [] ) {
		$content = array_merge( $this->get_default_content(), $content );

		$I->fillField( 'input[name="name"]', $content['name'] );
		$I->fillField( 'textarea[name="description"]', $content['description'] );
		$I->fillField( 'textarea[name="confirmation"]', $content['confirmation'] );

		if ( 'yes' === $content['confirmation_in_email'] ) {
			$I->checkOption( 'input[name="confirmation_in_email"]' );
		}

		$I->fillField( 'input[name="initial_payment"]', $content['initial_payment'] );

		$I->cantSeeElement( 'tr.recurring_info' );

		if ( 'yes' === $content['recurring'] ) {
			$I->checkOption( 'input[name="recurring"]' );

			$I->canSeeElement( 'tr.recurring_info' );

			$I->fillField( 'input[name="billing_amount"]', $content['billing_amount'] );
			$I->fillField( 'input[name="cycle_number"]', $content['cycle_number'] );
			$I->selectOption( 'select[name="cycle_period"]', $content['cycle_period'] );

			$I->cantSeeElement( 'tr.trial_info' );

			if ( 'yes' === $content['custom_trial'] ) {
				$I->checkOption( 'input[name="custom_trial"]' );

				$I->canSeeElement( 'tr.trial_info' );

				$I->fillField( 'input[name="trial_amount"]', $content['trial_amount'] );
				$I->fillField( 'input[name="trial_limit"]', $content['trial_limit'] );
			}
		}

		if ( 'yes' === $content['disable_signups'] ) {
			$I->checkOption( 'input[name="disable_signups"]' );
		}

		$I->cantSeeElement( 'tr.expiration_info' );

		if ( 'yes' === $content['expiration'] ) {
			$I->checkOption( 'input[name="expiration"]' );

			$I->canSeeElement( 'tr.expiration_info' );

			$I->fillField( 'input[name="expiration_number"]', $content['expiration_number'] );
			$I->selectOption( 'select[name="expiration_period"]', $content['expiration_period'] );
		}

		/*$I->cantSeeElement( 'tr.pbc_recurring_field' );

		if ( '1' === $content['pbc_setting'] || '2' === $content['pbc_setting'] ) {
			$I->selectOption( 'select[name="pbc_setting"]', $content['pbc_setting'] );

			$I->canSeeElement( 'tr.pbc_recurring_field' );

			$I->fillField( 'input[name="pbc_renewal_days"]', $content['pbc_renewal_days'] );
			$I->fillField( 'input[name="pbc_reminder_days"]', $content['pbc_reminder_days'] );
			$I->fillField( 'input[name="pbc_cancel_days"]', $content['pbc_cancel_days'] );
		}*/

		if ( 'yes' === $content['membershipcategory_1'] ) {
			$I->checkOption( 'input[name="membershipcategory_1"]' );
		}
	}

	/**
	 * See in the add new level form.
	 *
	 * @param AcceptanceTester $I       The tester instance.
	 * @param array            $content The content to look for (leave empty to use defaults).
	 */
	private function see_in_form( AcceptanceTester $I, array $content = [] ) {
		$content = array_merge( $this->get_default_content(), $content );

		$I->seeInField( 'input[name="name"]', $content['name'] );
		$I->seeInField( 'textarea[name="description"]', $content['description'] );
		$I->seeInField( 'textarea[name="confirmation"]', $content['confirmation'] );

		if ( 'yes' === $content['confirmation_in_email'] ) {
			$I->seeCheckboxIsChecked( 'input[name="confirmation_in_email"]' );
		} else {
			$I->dontSeeCheckboxIsChecked( 'input[name="confirmation_in_email"]' );
		}

		$I->seeInField( 'input[name="initial_payment"]', $content['initial_payment'] );

		if ( 'yes' === $content['recurring'] ) {
			$I->canSeeElement( 'tr.recurring_info' );
			$I->seeCheckboxIsChecked( 'input[name="recurring"]' );
		} else {
			$I->cantSeeElement( 'tr.recurring_info' );
			$I->dontSeeCheckboxIsChecked( 'input[name="recurring"]' );
		}

		$I->seeInField( 'input[name="billing_amount"]', $content['billing_amount'] );
		$I->seeInField( 'input[name="cycle_number"]', $content['cycle_number'] );
		$I->seeInField( 'select[name="cycle_period"]', $content['cycle_period'] );

		if ( 'yes' === $content['custom_trial'] ) {
			$I->canSeeElement( 'tr.trial_info' );
			$I->seeCheckboxIsChecked( 'input[name="custom_trial"]' );
		} else {
			$I->cantSeeElement( 'tr.trial_info' );
			$I->dontSeeCheckboxIsChecked( 'input[name="custom_trial"]' );
		}

		$I->seeInField( 'input[name="trial_amount"]', $content['trial_amount'] );
		$I->seeInField( 'input[name="trial_limit"]', $content['trial_limit'] );

		if ( 'yes' === $content['disable_signups'] ) {
			$I->seeCheckboxIsChecked( 'input[name="disable_signups"]' );
		} else {
			$I->dontSeeCheckboxIsChecked( 'input[name="disable_signups"]' );
		}

		if ( 'yes' === $content['expiration'] ) {
			$I->canSeeElement( 'tr.expiration_info' );
			$I->seeCheckboxIsChecked( 'input[name="expiration"]' );
		} else {
			$I->cantSeeElement( 'tr.expiration_info' );
			$I->dontSeeCheckboxIsChecked( 'input[name="expiration"]' );
		}

		$I->seeInField( 'input[name="expiration_number"]', $content['expiration_number'] );
		$I->seeInField( 'select[name="expiration_period"]', $content['expiration_period'] );

		/*if ( '1' === $content['pbc_setting'] || '2' === $content['pbc_setting'] ) {
			$I->canSeeElement( 'tr.pbc_recurring_field' );
			$I->seeInField( 'select[name="pbc_setting"]', $content['pbc_setting'] );
		} else {
			$I->cantSeeElement( 'tr.pbc_recurring_field' );
			$I->seeInField( 'select[name="pbc_setting"]', $content['pbc_setting'] );
		}

		$I->seeInField( 'input[name="pbc_renewal_days"]', $content['pbc_renewal_days'] );
		$I->seeInField( 'input[name="pbc_reminder_days"]', $content['pbc_reminder_days'] );
		$I->seeInField( 'input[name="pbc_cancel_days"]', $content['pbc_cancel_days'] );*/

		if ( 'yes' === $content['membershipcategory_1'] ) {
			$I->seeCheckboxIsChecked( 'input[name="membershipcategory_1"]' );
		} else {
			$I->dontSeeCheckboxIsChecked( 'input[name="membershipcategory_1"]' );
		}
	}

	/**
	 * It should allow adding new level.
	 *
	 * @param AcceptanceTester $I The tester instance.
	 */
	public function should_allow_adding_new_level( AcceptanceTester $I ) {
		$content = [
			'name'                  => 'My new test level',
			'description'           => 'My test level description',
			'confirmation'          => 'You now have access to this level',
			'confirmation_in_email' => 'yes',
			'initial_payment'       => 'You now have access to this level',
			'recurring'             => 'yes',
			'billing_amount'        => '10',
			'cycle_number'          => '2',
			'cycle_period'          => 'Year',
			'custom_trial'          => 'yes',
			'trial_amount'          => '1',
			'trial_limit'           => '1',
			'disable_signups'       => 'yes',
			'expiration'            => 'yes',
			'expiration_number'     => '2',
			'expiration_period'     => 'Year',
			'pbc_setting'           => '2',
			'pbc_renewal_days'      => '7',
			'pbc_reminder_days'     => '7',
			'pbc_cancel_days'       => '14',
			'membershipcategory_1'  => 'yes',
		];

		$this->fill_in_form( $I, $content );

		$I->click( 'input[name="save"]' );

		$I->see( $content['name'], 'table.membership-levels' );
	}

	/**
	 * It should allow cancelling add new level.
	 *
	 * @param AcceptanceTester $I The tester instance.
	 */
	public function should_allow_cancelling_add_new_level( AcceptanceTester $I ) {
		$content = [
			'name'                  => 'My new cancelled test level',
			'description'           => 'My test level description',
			'confirmation'          => 'You now have access to this level',
			'confirmation_in_email' => 'yes',
			'initial_payment'       => 'You now have access to this level',
			'recurring'             => 'yes',
			'billing_amount'        => '10',
			'cycle_number'          => '2',
			'cycle_period'          => 'Year',
			'custom_trial'          => 'yes',
			'trial_amount'          => '1',
			'trial_limit'           => '1',
			'disable_signups'       => 'yes',
			'expiration'            => 'yes',
			'expiration_number'     => '2',
			'expiration_period'     => 'Year',
			'pbc_setting'           => '2',
			'pbc_renewal_days'      => '7',
			'pbc_reminder_days'     => '7',
			'pbc_cancel_days'       => '14',
			'membershipcategory_1'  => 'yes',
		];

		$this->fill_in_form( $I, $content );

		$I->click( 'input[name="cancel"]' );

		$I->dontSee( $content['name'], 'table.membership-levels' );
	}

	/**
	 * It should show default values in fields.
	 *
	 * @param AcceptanceTester $I The tester instance.
	 */
	public function should_show_default_values_in_fields( AcceptanceTester $I ) {
		$this->see_in_form( $I );
	}
}
