<?php
/**
 * Include the Wizard Save Steps File in the admin init hook when using the Wizard.
 * This is to save and handle data and redirects.
 *
 * @since TBD
 */
function pmpro_init_save_wizard_data() {
	// Bail if not on the wizard page.
	if ( empty( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'pmpro-wizard' ) {
		return;
	}

	// Only run the code on submit.
	if ( empty( $_REQUEST['submit'] ) ) {
		return;
	}

	/**
	 * Step 1 - Update settings and generate anything we may need based off settings.
	 */
	if ( $_REQUEST['wizard-action'] == 'step-1' ) {
		

		/// Throw a nonce error.
		if ( ! wp_verify_nonce( $_REQUEST['pmpro_wizard_step_1_nonce'], 'pmpro_wizard_step_1_nonce' ) ) {
			return;
		}

		// Save the type of membership site. May be saved as "Blank"?
		update_option( 'pmpro_site_type', sanitize_text_field( $_REQUEST['membership_site_type'] ), false );

		// Update license key value
		if ( ! empty( $_REQUEST['pmpro_license_key'] ) ) {
			update_option( 'pmpro_license_key', sanitize_text_field( $_REQUEST['pmpro_license_key'] ), false );
		}

	

		// Generate pages
		if ( ! empty( $_REQUEST['createpages'] ) ) {
			global $pmpro_pages;

			// If any pages are assigned already, let's not generate the pages.
			if ( $pmpro_pages['account'] ||
				 $pmpro_pages['billing'] ||
				 $pmpro_pages['cancel'] ||
				 $pmpro_pages['checkout'] ||
				 $pmpro_pages['confirmation'] ||
				 $pmpro_pages['invoice'] ||
				 $pmpro_pages['levels'] ||
				 $pmpro_pages['member_profile_edit'] ) {
				$generate_pages = false;
			} else {
				$generate_pages = true;
			}

			// Should we generate pages or not based on other settings.
			if ( $generate_pages ) {
				$pages = array();

				$pages['account']             = __( 'Membership Account', 'paid-memberships-pro' );
				$pages['billing']             = __( 'Membership Billing', 'paid-memberships-pro' );
				$pages['cancel']              = __( 'Membership Cancel', 'paid-memberships-pro' );
				$pages['checkout']            = __( 'Membership Checkout', 'paid-memberships-pro' );
				$pages['confirmation']        = __( 'Membership Confirmation', 'paid-memberships-pro' );
				$pages['invoice']             = __( 'Membership Invoice', 'paid-memberships-pro' );
				$pages['levels']              = __( 'Membership Levels', 'paid-memberships-pro' );
				$pages['login']               = __( 'Log In', 'paid-memberships-pro' );
				$pages['member_profile_edit'] = __( 'Your Profile', 'paid-memberships-pro' );

				pmpro_generatePages( $pages );
			}
		}

		// Figure out if we have to skip over step 3 or not.
		if ( ! empty( $_REQUEST['collect_payments'] ) ) {
			$collect_payments_settings = true;
		} else {
			$collect_payments_settings = false;
		}

		update_option( 'pmpro_wizard_collect_payments', $collect_payments_settings );

		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => 'memberships',
			),
			admin_url( 'admin.php' )
		);

		// Save the step should they come back at a later stage.
		// update_option( 'pmpro_wizard_step', '2' );
		wp_redirect( $next_step );
	}

	/**
	 * Memberships Step
	 */
	if ( $_REQUEST['wizard-action'] == 'step-2' ) {
		global $wpdb;

		if ( ! wp_verify_nonce( $_REQUEST['pmpro_wizard_step_2_nonce'], 'pmpro_wizard_step_2_nonce' ) ) {
			return;
		}

		// Let's get the values and insert them.
		$levels_array = array();

		// If free level option is enabled, then get data.
		if ( ! empty( $_REQUEST['pmpro-wizard__free-level'] ) ) {

			$free_level_name = ! empty( $_REQUEST['pmpro-wizard__free-level-name'] ) ? sanitize_text_field( $_REQUEST['pmpro-wizard__free-level-name'] ) : 'Free';

			$levels_array['free'] = array(
				'id'                => -1,
				'name'              => $free_level_name,
				'description'       => '',
				'confirmation'      => '',
				'initial_payment'   => '',
				'billing_amount'    => '',
				'cycle_number'      => '',
				'cycle_period'      => '',
				'billing_limit'     => '',
				'trial_amount'      => '',
				'trial_limit'       => '',
				'expiration_number' => '',
				'expiration_period' => '',
				'allow_signups'     => 1,
			);
		}

		if ( ! empty( $_REQUEST['pmpro-wizard__paid-level'] ) ) {

			$paid_level_name = ! empty( $_REQUEST['pmpro-wizard__paid-level-name'] ) ? sanitize_text_field( $_REQUEST['pmpro-wizard__paid-level-name'] ) : 'Paid';
			$amount          = ! empty( $_REQUEST['pmpro-wizard__paid-level-amount'] ) ? floatval( $_REQUEST['pmpro-wizard__paid-level-amount'] ) : 10.00;
			$period          = ! empty( $_REQUEST['cycle_period'] ) ? sanitize_text_field( $_REQUEST['cycle_period'] ) : 'Month';

			$levels_array['paid'] = array(
				'id'                => -1,
				'name'              => $paid_level_name,
				'description'       => '',
				'confirmation'      => '',
				'initial_payment'   => $amount,
				'billing_amount'    => $amount,
				'cycle_number'      => '1',
				'cycle_period'      => $period,
				'billing_limit'     => '',
				'trial_amount'      => '',
				'trial_limit'       => '',
				'expiration_number' => '',
				'expiration_period' => '',
				'allow_signups'     => 1,
			);
		}

		if ( ! empty( $levels_array ) ) {
			foreach ( $levels_array as $type => $level_data ) {
                pmpro_insert_or_replace(
                    $wpdb->pmpro_membership_levels,
                    $level_data,
                    array(
                        '%d',       // id
                        '%s',       // name
                        '%s',       // description
                        '%s',       // confirmation
                        '%f',       // initial_payment
                        '%f',       // billing_amount
                        '%d',       // cycle_number
                        '%s',       // cycle_period
                        '%d',       // billing_limit
                        '%f',       // trial_amount
                        '%d',       // trial_limit
                        '%d',       // expiration_number
                        '%s',       // expiration_period
                        '%d',       // allow_signups
                    )
                );
			}
		}

        $collect_payments = get_option( 'pmpro_wizard_collect_payments' );

        if ( $collect_payments ) {
            $step = 'payments';
        } else {
            $step = 'advanced';
        }

		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => $step,
			),
			admin_url( 'admin.php' )
		);

		// Save the step should they come back at a later stage.
		// update_option( 'pmpro_wizard_step', '3' );
		wp_redirect( $next_step );

		// Now we can redirect to the next step we might need.
	} // End of step 2.

	/**
	 * Payment Settings Step
	 */
	if ( $_REQUEST['wizard-action'] == 'step-3' ) {

		if ( ! wp_verify_nonce( $_REQUEST['pmpro_wizard_step_3_nonce'], 'pmpro_wizard_step_3_nonce' ) ) {
			return;
		}

		$pmpro_currency = sanitize_text_field( $_REQUEST['currency'] );		
		pmpro_setOption( 'currency', $pmpro_currency );

		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => 'advanced',
			),
			admin_url( 'admin.php' )
		);

		// Save the step should they come back at a later stage.
		// update_option( 'pmpro_wizard_step', '4' );
		wp_redirect( $next_step );

	}

	/**
	 * Advanced Settings Step
	 */
	if ( $_REQUEST['wizard-action'] == 'step-4' ) {
		if ( ! wp_verify_nonce( $_REQUEST['pmpro_wizard_step_4_nonce'], 'pmpro_wizard_step_4_nonce' ) ) {
			return;
		}

		// Get the data and store it in an option.
		$filterqueries = ! empty( $_REQUEST['filterqueries'] ) ? sanitize_text_field( $_REQUEST['filterqueries'] ) : 0;
		$showexcerpts = ! empty( $_REQUEST['showexcerpts'] ) ? sanitize_text_field( $_REQUEST['showexcerpts'] ) : 0;
		$spamprotection = ! empty( $_REQUEST['spamprotection'] ) ? sanitize_text_field( $_REQUEST['spamprotection'] ) : 0;
		$wisdom_opt_in = ! empty( $_REQUEST['wisdom_opt_in'] ) ? sanitize_text_field( $_REQUEST['wisdom_opt_in'] ) : 1; //Reversed logic here for the Wisdom Tracker, Advanced Settings does it differently. If selected, option is 0 otherwise option is 1;

		// Updated the options. Set the values as above to cater for cases where the REQUEST variables are empty for blank checkboxes.
		pmpro_setOption( 'filterqueries', $filterqueries );
		pmpro_setOption( 'showexcerpts', $showexcerpts );
		pmpro_setOption( 'spamprotection', $spamprotection );
		pmpro_setOption( 'wisdom_opt_out', $wisdom_opt_in );
		

		// Redirect to next step
		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => 'done',
			),
			admin_url( 'admin.php' )
		);

		/// Remove the option? or set it to complete?
		// delete_option( 'pmpro_wizard_step' );
		wp_redirect( $next_step );
	}

	/**
	 * Final Step, completed. ///MIGHT NOT NEED THIS.
	 */
	if ( $_REQUEST['wizard-action'] == 'step-5' ) {
		// Do stuff
		delete_option( 'pmpro_wizard_collect_payments' );
	}

}
add_action( 'admin_init', 'pmpro_init_save_wizard_data' );
