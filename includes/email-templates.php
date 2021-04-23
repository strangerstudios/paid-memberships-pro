<?php
/**
 * File used to setup default email templates data.
 */

 global $pmpro_email_templates_defaults;
 /**
 * Default email templates.
 */
$pmpro_email_templates_defaults = array(
	'default'                  => array(
		'subject'     => __( "An Email From !!sitename!!", 'paid-memberships-pro' ),
		'description' => __( 'Default Email', 'paid-memberships-pro')
	),
	'admin_change'             => array(
		'subject'     => __( "Your membership at !!sitename!! has been changed", 'paid-memberships-pro' ),
		'description' => __( 'Admin Change', 'paid-memberships-pro')
	),
	'admin_change_admin'       => array(
		'subject'     => __( "Membership for !!user_login!! at !!sitename!! has been changed", 'paid-memberships-pro' ),
		'description' => __('Admin Change (admin)', 'paid-memberships-pro')
	),
	'billing'                  => array(
		'subject'     => __( "Your billing information has been udpated at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing', 'paid-memberships-pro')
	),
	'billing_admin'            => array(
		'subject'     => __( "Billing information has been udpated for !!user_login!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing (admin)', 'paid-memberships-pro')
	),
	'billing_failure'          => array(
		'subject'     => __( "Membership Payment Failed at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing Failure', 'paid-memberships-pro')
	),
	'billing_failure_admin'    => array(
		'subject'     => __( "Membership Payment Failed For !!display_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing Failure (admin)', 'paid-memberships-pro')
	),
	'cancel'                   => array(
		'subject'     => __( "Your membership at !!sitename!! has been CANCELLED", 'paid-memberships-pro' ),
		'description' => __('Cancel', 'paid-memberships-pro')
	),
	'cancel_admin'             => array(
		'subject'     => __( "Membership for !!user_login!! at !!sitename!! has been CANCELLED", 'paid-memberships-pro' ),
		'description' => __('Cancel (admin)', 'paid-memberships-pro')
	),
	'checkout_check'           => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Check', 'paid-memberships-pro')
	),
	'checkout_check_admin'     => array(
		'subject'     => __( "Member Checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Check (admin)', 'paid-memberships-pro')
	),
	'checkout_express'         => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - PayPal Express', 'paid-memberships-pro')
	),
	'checkout_express_admin'   => array(
		'subject'     => __( "Member Checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - PayPal Express (admin)', 'paid-memberships-pro')
	),
	'checkout_free'            => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free', 'paid-memberships-pro')
	),
	'checkout_free_admin'      => array(
		'subject'     => __( "Member Checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free (admin)', 'paid-memberships-pro')
	),
	'checkout_freetrial'       => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free Trial', 'paid-memberships-pro')
	),
	'checkout_freetrial_admin' => array(
		'subject'     => __( "Member Checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free Trial (admin)', 'paid-memberships-pro')
	),
	'checkout_paid'            => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Paid', 'paid-memberships-pro')
	),
	'checkout_paid_admin'      => array(
		'subject'     => __( "Member Checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Paid (admin)', 'paid-memberships-pro')
	),
	'checkout_trial'           => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Trial', 'paid-memberships-pro')
	),
	'checkout_trial_admin'     => array(
		'subject'     => __( "Member Checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Trial (admin)', 'paid-memberships-pro')
	),
	'credit_card_expiring'     => array(
		'subject'     => __( "Credit Card on File Expiring Soon at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Credit Card Expiring', 'paid-memberships-pro')
	),
	'invoice'                  => array(
		'subject'     => __( "INVOICE for !!sitename!! membership", 'paid-memberships-pro' ),
		'description' => __('Invoice', 'paid-memberships-pro')
	),
	'membership_expired'       => array(
		'subject'     => __( "Your membership at !!sitename!! has ended", 'paid-memberships-pro' ),
		'description' => __('Membership Expired', 'paid-memberships-pro')
	),
	'membership_expiring'      => array(
		'subject'     => __( "Your membership at !!sitename!! will end soon", 'paid-memberships-pro' ),
		'description' => __('Membership Expiring', 'paid-memberships-pro')
	),
	'trial_ending'             => array(
		'subject'     => __( "Your trial at !!sitename!! is ending soon", 'paid-memberships-pro' ),
		'description' => __('Trial Ending', 'paid-memberships-pro')
	),
);

// add SCA payment action required emails if we're using PMPro 2.1 or later
if( defined( 'PMPRO_VERSION' ) && version_compare( PMPRO_VERSION, '2.1' ) >= 0 ) {
	$pmpro_email_templates_defaults = array_merge( $pmpro_email_templates_defaults, array(
		'payment_action'            => array(
			'subject'     => __( "Payment action required for your !!sitename!! membership", 'paid-memberships-pro' ),
			'description' => __('Payment Action Required', 'paid-memberships-pro')
		),
		'payment_action_admin'      => array(
			'subject'     => __( "Payment action required: membership for !!user_login!! at !!sitename!!", 'paid-memberships-pro' ),
			'description' => __('Payment Action Required (admin)', 'paid-memberships-pro')
		)
	));
}

/**
 * Filter default template settings and add new templates.
 *
 * @since 0.5.7
 */
$pmpro_email_templates_defaults = apply_filters( 'pmproet_templates', $pmpro_email_templates_defaults );


/*
 * Generates a test order on the fly for orders.
 */
function pmpro_test_order() {
	global $current_user;

	//make sure PMPro is activated
	if ( ! class_exists( '\MemberOrder' ) ) {
		return;
	}
	$test_order = new MemberOrder();
	$all_levels = pmpro_getAllLevels();
	
	if ( ! empty( $all_levels ) ) {
		$first_level                = array_shift( $all_levels );
		$test_order->membership_id  = $first_level->id;
		$test_order->InitialPayment = $first_level->initial_payment;
	} else {
		$test_order->membership_id  = 1;
		$test_order->InitialPayment = 1;
	}
	$test_order->user_id             = $current_user->ID;
	$test_order->cardtype            = "Visa";
	$test_order->accountnumber       = "4111111111111111";
	$test_order->expirationmonth     = date( 'm', current_time( 'timestamp' ) );
	$test_order->expirationyear      = ( intval( date( 'Y', current_time( 'timestamp' ) ) ) + 1 );
	$test_order->ExpirationDate      = $test_order->expirationmonth . $test_order->expirationyear;
	$test_order->CVV2                = '123';
	$test_order->FirstName           = 'Jane';
	$test_order->LastName            = 'Doe';
	$test_order->Address1            = '123 Street';
	$test_order->billing             = new stdClass();
	$test_order->billing->name       = 'Jane Doe';
	$test_order->billing->street     = '123 Street';
	$test_order->billing->city       = 'City';
	$test_order->billing->state      = 'ST';
	$test_order->billing->country    = 'US';
	$test_order->billing->zip        = '12345';
	$test_order->billing->phone      = '5558675309';
	$test_order->gateway_environment = 'sandbox';
	$test_order->notes               = __( 'This is a test order used with the PMPro Email Templates addon.', 'paid-memberships-pro' );

	return apply_filters( 'pmpro_test_order_data', $test_order );
}