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
	// Clear things up on the completed page.
	if ( $_REQUEST['step'] === 'done' ) {
		delete_option( 'pmpro_wizard_collect_payment' );
		update_option( 'pmpro_wizard_step', 'done' ); // Update it to be completed as we've reached this page.
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
			pmpro_setOption( 'wizard_collect_payment', true );
			$step = 'payments';
		} else {
			pmpro_setOption( 'wizard_collect_payment', false );
			$step = 'memberships';
		}

		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => $step,
			),
			admin_url( 'admin.php' )
		);
		// Before redirecting to next step, save the step we're redirecting to.
		update_option( 'pmpro_wizard_step', $step, false );
		wp_redirect( $next_step );
	}

	/**
	 * Payment Settings Step
	 */
	if ( $_REQUEST['wizard-action'] == 'step-2' ) {

		if ( ! wp_verify_nonce( $_REQUEST['pmpro_wizard_step_2_nonce'], 'pmpro_wizard_step_2_nonce' ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['currency'] ) ) {
			$pmpro_currency = sanitize_text_field( $_REQUEST['currency'] );
			pmpro_setOption( 'currency', $pmpro_currency );
		}

		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => 'memberships',
			),
			admin_url( 'admin.php' )
		);

		// If Stripe is not already set up, and the user wants to use Stripe, then redirect them to Stripe Connect.
		$environment = apply_filters( 'pmpro_wizard_stripe_environment', 'live' );
		if ( ! empty( $_REQUEST['gateway'] ) && 'stripe' === $_REQUEST['gateway'] && ! PMProGateway_Stripe::has_connect_credentials( $environment ) && ! PMProGateway_Stripe::using_legacy_keys() ) {
			$connect_url_base = apply_filters( 'pmpro_stripe_connect_url', 'https://connect.paidmembershipspro.com' );
			$connect_url = add_query_arg(
				array(
					'action' => 'authorize',
					'gateway_environment' => $environment,
					'return_url' => rawurlencode( $next_step ),
				),
				$connect_url_base
			);
			wp_redirect( $connect_url );
			exit;
		}

		// Save the step should they come back at a later stage.
		update_option( 'pmpro_wizard_step', 'memberships', false );
		wp_redirect( $next_step );

	}



	/**
	 * Memberships Step
	 */
	if ( $_REQUEST['wizard-action'] == 'step-3' ) {
		global $wpdb;

		if ( ! wp_verify_nonce( $_REQUEST['pmpro_wizard_step_3_nonce'], 'pmpro_wizard_step_3_nonce' ) ) {
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

		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => 'advanced',
			),
			admin_url( 'admin.php' )
		);

		// Save the step should they come back at a later stage.
		update_option( 'pmpro_wizard_step', 'advanced', false );
		wp_redirect( $next_step );

		// Now we can redirect to the next step we might need.
	} // End of step 2.

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
		$wisdom_opt_out = ! empty( $_REQUEST['wisdom_opt_out'] ) ? sanitize_text_field( $_REQUEST['wisdom_opt_out'] ) : 1; //Reversed logic here for the Wisdom Tracker as initially coded.

		// Updated the options. Set the values as above to cater for cases where the REQUEST variables are empty for blank checkboxes.
		pmpro_setOption( 'filterqueries', $filterqueries );
		pmpro_setOption( 'showexcerpts', $showexcerpts );
		pmpro_setOption( 'spamprotection', $spamprotection );
		pmpro_setOption( 'wisdom_opt_out', $wisdom_opt_out );
		

		// Redirect to next step
		$next_step = add_query_arg(
			array(
				'page' => 'pmpro-wizard',
				'step' => 'done',
			),
			admin_url( 'admin.php' )
		);

		// Set option to complete right before redirect (in case something goes wrong or they quit during this process for some reason.)
		update_option( 'pmpro_wizard_step', 'done', false );
		wp_redirect( $next_step );
	}

	// Final step is handled further up as no form submission is needed, but rather clean things up on page load.

}
add_action( 'admin_init', 'pmpro_init_save_wizard_data' );
