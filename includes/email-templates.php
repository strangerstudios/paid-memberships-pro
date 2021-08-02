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
		'subject'     => __( "An email from !!sitename!!", 'paid-memberships-pro' ),
		'description' => __( 'Default Email', 'paid-memberships-pro'),
		'body' => __( '!!body!!', 'paid-memberships-pro' )
	),
	'footer'                  => array(
		'subject'     => __( '', 'paid-memberships-pro' ),
		'description' => __( 'Email Footer', 'paid-memberships-pro'),
		'body' => __( '<p>
	Respectfully,<br />
	!!sitename!!
</p>', 'paid-memberships-pro' )
	),
	'header'                  => array(
		'subject'     => __( '', 'paid-memberships-pro' ),
		'description' => __( 'Email Header', 'paid-memberships-pro'),
		'body' => __( '<p>Dear !!name!!,</p>', 'paid-memberships-pro' )
	),
	'admin_change'             => array(
		'subject'     => __( "Your membership at !!sitename!! has been changed", 'paid-memberships-pro' ),
		'description' => __( 'Admin Change', 'paid-memberships-pro'),
		'body' => __( '<p>An administrator at !!sitename!! has changed your membership level.</p>
		
<p>!!membership_change!!.</p>
		
<p>If you did not request this membership change and would like more information please contact us at !!siteemail!!</p>
		
<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'admin_change_admin'       => array(
		'subject'     => __( "Membership for !!user_login!! at !!sitename!! has been changed", 'paid-memberships-pro' ),
		'description' => __('Admin Change (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>An administrator at !!sitename!! has changed a membership level for !!name!!.</p>

<p>!!membership_change!!.</p>

<p>Log in to your WordPress admin here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'billable_invoice' => array(
		'subject' => __( 'Invoice for order #: !!order_code!!', 'paid-memberships-pro' ),
        'description' => __( 'Billable Invoice', 'paid-membershps-pro' ),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Below is your invoice for order #: !!order_code!!</p>

!!invoice!!

<p>Log in to your membership account here: !!login_link!!</p>

<p>To view an online version of this invoice, click here: !!invoice_link!!</p>', 'paid-memberships-pro' )
	),
	'billing'	=> array(
		'subject'     => __( "Your billing information has been udpated at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing', 'paid-memberships-pro'),
		'body' => __( '<p>Your billing information at !!sitename!! has been changed.</p><p>Account: !!display_name!! (!!user_email!!)</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p><p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p><p>If you did not request a billing information change please contact us at !!siteemail!!</p><p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'billing_admin'            => array(
		'subject'     => __( "Billing information has been udpated for !!user_login!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>The billing information for !!display_name!! at !!sitename!! has been changed.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>
	Billing Information:<br />
	!!billing_name!!<br />
	!!billing_street!!<br />
	!!billing_city!!, !!billing_state!! !!billing_zip!!	!!billing_country!!
	!!billing_phone!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your WordPress dashboard here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'billing_failure'          => array(
		'subject'     => __( "Membership payment failed at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing Failure', 'paid-memberships-pro'),
		'body' => __( '<p>The current subscription payment for your !!sitename!! membership has failed. <strong>Please click the following link to log in and update your billing information to avoid account suspension. !!login_link!!</strong></p>

<p>Account: !!display_name!! (!!user_email!!)</p>', 'paid-memberships-pro' )
	),
	'billing_failure_admin'    => array(
		'subject'     => __( "Membership payment failed for !!display_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Billing Failure (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>The subscription payment for !!user_login!! at !!sitename!! has failed.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>

<p>Log in to your WordPress admin here: !!login_link!!</p>
', 'paid-memberships-pro' )
	),
	'cancel'                   => array(
		'subject'     => __( "Your membership at !!sitename!! has been CANCELLED", 'paid-memberships-pro' ),
		'description' => __('Cancel', 'paid-memberships-pro'),
		'body' => __( '<p>Your membership at !!sitename!! has been cancelled.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>

<p>If you did not request this cancellation and would like more information please contact us at !!siteemail!!</p>', 'paid-memberships-pro' )
	),
	'cancel_admin'             => array(
		'subject'     => __( "Membership for !!user_login!! at !!sitename!! has been CANCELLED", 'paid-memberships-pro' ),
		'description' => __('Cancel (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>The membership for !!user_login!! at !!sitename!! has been cancelled.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Start Date: !!startdate!!</p>
<p>Cancellation Date: !!enddate!!</p>

<p>Log in to your WordPress admin here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_check'           => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Check', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>

!!membership_level_confirmation_message!!

!!instructions!!

<p>Below are details about your membership account and a receipt for your initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_check_admin'     => array(
		'subject'     => __( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Check (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>There was a new member checkout at !!sitename!!.</p>

<p><strong>They have chosen to pay by check.</strong></p>

<p>Below are details about the new membership account and a receipt for the initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: $!!invoice_total!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_express'         => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - PayPal Express', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>
!!membership_level_confirmation_message!!
<p>Below are details about your membership account and a receipt for your initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>
', 'paid-memberships-pro' )
	),
	'checkout_express_admin'   => array(
		'subject'     => __( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - PayPal Express (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>There was a new member checkout at !!sitename!!.</p>
<p>Below are details about the new membership account and a receipt for the initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>
', 'paid-memberships-pro' )
	),
	'checkout_free'            => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>
!!membership_level_confirmation_message!!
<p>Below are details about your membership account.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
!!membership_expiration!! !!discount_code!!

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_free_admin'      => array(
		'subject'     => __( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>There was a new member checkout at !!sitename!!.</p>
<p>Below are details about the new membership account.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
!!membership_expiration!! !!discount_code!!

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_freetrial'       => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free Trial', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>
!!membership_level_confirmation_message!!
<p>Below are details about your membership account.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Billing Information on File:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_freetrial_admin' => array(
		'subject'     => __( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Free Trial (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>There was a new member checkout at !!sitename!!.</p>
<p>Below are details about the new membership account and a receipt for the initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Billing Information on File:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_paid'            => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Paid', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>
!!membership_level_confirmation_message!!
<p>Below are details about your membership account and a receipt for your initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_paid_admin'      => array(
		'subject'     => __( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Paid (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>There was a new member checkout at !!sitename!!.</p>
<p>Below are details about the new membership account and a receipt for the initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_trial'           => array(
		'subject'     => __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Trial', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>
!!membership_level_confirmation_message!!
<p>Below are details about your membership account and a receipt for your initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'checkout_trial_admin'     => array(
		'subject'     => __( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Checkout - Trial (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>There was a new member checkout at !!sitename!!.</p>
<p>Below are details about the new membership account and a receipt for the initial membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>
<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'credit_card_expiring'     => array(
		'subject'     => __( "Credit card on file expiring soon at !!sitename!!", 'paid-memberships-pro' ),
		'description' => __('Credit Card Expiring', 'paid-memberships-pro'),
		'body' => __( '<p>The payment method used for your membership at !!sitename!! will expire soon. <strong>Please click the following link to log in and update your billing information to avoid account suspension. !!login_link!!</strong></p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>The most recent account information we have on file is:</p>

<p>!!billing_name!!</br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>', 'paid-memberships-pro' )
	),
	'invoice'                  => array(
		'subject'     => __( "Invoice for !!sitename!! membership", 'paid-memberships-pro' ),
		'description' => __('Invoice', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Below is a receipt for your most recent membership invoice.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>
	Invoice #!!invoice_id!! on !!invoice_date!!<br />
	Total Billed: !!invoice_total!!
</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_link!!</p>
<p>To view an online version of this invoice, click here: !!invoice_link!!</p>', 'paid-memberships-pro' )
	),
	'membership_expired'       => array(
		'subject'     => __( "Your membership at !!sitename!! has ended", 'paid-memberships-pro' ),
		'description' => __('Membership Expired', 'paid-memberships-pro'),
		'body' => __( '<p>Your membership at !!sitename!! has ended.</p>

<p>Thank you for your support.</p>

<p>View our current membership offerings here: !!levels_link!!</p>

<p>Log in to manage your account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'membership_expiring'      => array(
		'subject'     => __( "Your membership at !!sitename!! will end soon", 'paid-memberships-pro' ),
		'description' => __('Membership Expiring', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. This is just a reminder that your membership will end on !!enddate!!.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),
	'trial_ending'             => array(
		'subject'     => __( "Your trial at !!sitename!! is ending soon", 'paid-memberships-pro' ),
		'description' => __('Trial Ending', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Your trial period is ending on !!trial_end!!.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>

<p>Your fee will be changing from !!trial_amount!! to !!billing_amount!! every !!cycle_number!! !!cycle_period!!(s).</p>

<p>Log in to your membership account here: !!login_link!!</p>', 'paid-memberships-pro' )
	),

);

// add SCA payment action required emails if we're using PMPro 2.1 or later
if( defined( 'PMPRO_VERSION' ) && version_compare( PMPRO_VERSION, '2.1' ) >= 0 ) {
	$pmpro_email_templates_defaults = array_merge( $pmpro_email_templates_defaults, array(
		'payment_action'            => array(
			'subject'     => __( "Payment action required for your !!sitename!! membership", 'paid-memberships-pro' ),
			'description' => __('Payment Action Required', 'paid-memberships-pro' ),
			'body' => __( '<p>Customer authentication is required to finish setting up your subscription at !!sitename!!.</p>

<p>Please complete the verification steps issued by your payment provider at the following link:</p>
<p>!!invoice_url!!</p>', 'paid-memberships-pro' )
		),
		'payment_action_admin'      => array(
			'subject'     => __( "Payment action required: membership for !!user_login!! at !!sitename!!", 'paid-memberships-pro' ),
			'description' => __('Payment Action Required (admin)', 'paid-memberships-pro'),
			'body' => __( '<p>A payment at !!sitename!! for !!user_login!! requires additional authentication customer authentication to complete.</p>
<p>Below is a copy of the email we sent to !!user_email!! to notify them that they need to complete their payment:</p>

<p>Customer authentication is required to finish setting up your subscription at !!sitename!!.</p>

<p>Please complete the verification steps issued by your payment provider at the following link:</p>
<p>!!invoice_url!!</p>', 'paid-memberships-pro' )
		)
	));
}

/**
 * Filter default template settings and add new templates.
 *
 * @since 0.5.7
 */
$pmpro_email_templates_defaults = apply_filters( 'pmproet_templates', $pmpro_email_templates_defaults );
