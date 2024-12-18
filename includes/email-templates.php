<?php

// File used to setup default email templates data.
global $pmpro_email_templates_defaults;

$check_gateway_label = get_option( 'pmpro_check_gateway_label' ) ? get_option( 'pmpro_check_gateway_label' ) : __( 'Check', 'paid-memberships-pro' );
 
// Default email templates.
$pmpro_email_templates_defaults = array(
	'default' => array(
		'subject' => __( "An email from !!sitename!!", 'paid-memberships-pro' ),
		'description' => __( 'Default Email', 'paid-memberships-pro'),
		'body' => __( '!!body!!', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent when there is a general message that needs to be communicated to the site administrator.', 'paid-memberships-pro' )
	),
	'footer' => array(
		'subject' => '',
		'description' => __( 'Email Footer', 'paid-memberships-pro'),
		'body' => __( '<p>Respectfully,<br />!!sitename!! </p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This is the closing message included in every email sent to members and the site administrator through Paid Memberships Pro.', 'paid-memberships-pro' )
	),
	'header' => array(
		'subject'     => '',
		'description' => __( 'Email Header', 'paid-memberships-pro'),
		'body' => __( '<p>Dear !!header_name!!,</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This is the opening message included in every email sent to members and the site administrator through Paid Memberships Pro.', 'paid-memberships-pro' )
	),
	'invoice'  => array(
		'subject' => __( "Recurring payment receipt for !!sitename!! membership", 'paid-memberships-pro' ),
		'description' => __('Recurring Payment Receipt', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. Below is a receipt for your most recent membership order.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>
	Order #!!order_id!! on !!order_date!!<br />
	Total Billed: !!order_total!!
</p>
<p>
	Billing Information:<br />
	!!billing_address!!
</p>

<p>
	!!cardtype!!: !!accountnumber!!<br />
	Expires: !!expirationmonth!!/!!expirationyear!!
</p>

<p>Log in to your membership account here: !!login_url!!</p>
<p>To view an online version of this order, click here: !!order_url!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the member each time a new subscription payment is made.', 'paid-memberships-pro' )
	),
	'membership_expired' => array(
		'subject' => __( "Your membership at !!sitename!! has ended", 'paid-memberships-pro' ),
		'description' => __('Membership Expired', 'paid-memberships-pro'),
		'body' => __( '<p>Your membership at !!sitename!! has ended.</p>

<p>Thank you for your support.</p>

<p>View our current membership offerings here: !!levels_url!!</p>

<p>Log in to manage your account here: !!login_url!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the member when their membership expires.', 'paid-memberships-pro' )
	),
	'membership_expiring' => array(
		'subject' => __( "Your membership at !!sitename!! will end soon", 'paid-memberships-pro' ),
		'description' => __('Membership Expiring', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!. This is just a reminder that your membership will end on !!enddate!!.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>

<p>Log in to your membership account here: !!login_url!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the member when their expiration date is approaching, at an interval based on the term of the membership.', 'paid-memberships-pro' )
	),
	'refund' => array(
		'subject' => __( 'Order #!!order_id!! at !!sitename!! has been REFUNDED', 'paid-memberships-pro' ),
		'description' => __('Refund', 'paid-memberships-pro'),
		'body' => __( '<p>Order #!!order_id!! at !!sitename!! has been refunded.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>
	Order #!!order_id!! on !!order_date!!<br />
	Total Refunded: !!order_total!!
</p>

<p>Log in to your membership account here: !!login_url!!</p>
<p>To view an online version of this order, click here: !!order_url!!</p>

<p>If you did not request this refund and would like more information please contact us at !!siteemail!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the member as confirmation of a refunded payment. The email is sent after your membership site receives notification of a successful payment refund through your gateway.', 'paid-memberships-pro' )
	),
	'refund_admin' => array(
		'subject' => __( 'Order #!!order_id!! at !!sitename!! has been REFUNDED', 'paid-memberships-pro' ),
		'description' => __('Refund (admin)', 'paid-memberships-pro'),
		'body' => __( '<p>Order #!!order_id!! at !!sitename!! has been refunded.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>
	Order #!!order_id!! on !!order_date!!<br />
	Total Refunded: !!order_total!!
</p>

<p>Log in to your WordPress admin here: !!login_url!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the admin as confirmation of a refunded payment. The email is sent after your membership site receives notification of a successful payment refund through your gateway.', 'paid-memberships-pro' )
	),
	'membership_recurring' => array(
		'subject' => __( "Your membership at !!sitename!! will renew soon", 'paid-memberships-pro' ),
		'description' => __('Recurring Payment Reminder', 'paid-memberships-pro'),
		'body' => __( '<p>Thank you for your membership to !!sitename!!.</p>

<p>This is just a reminder that your !!membership_level_name!! membership will automatically renew on !!renewaldate!!.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>If for some reason you do not want to renew your membership you can cancel by clicking here: !!cancel_link!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent when a subscription is approaching its renewal date. The additional placeholders !!renewaldate!! and !!billing_amount!! can be used to print the date that the subscription will renew and the renewal price.', 'paid-memberships-pro' )
	),
);
//we can hide the payment action required emails if default gateway isn't Stripe.
$default_gateway = get_option( 'pmpro_gateway' );
if( 'stripe' === $default_gateway ) {
	$pmpro_email_templates_defaults = array_merge( $pmpro_email_templates_defaults, array(
		'payment_action' => array(
			'subject' => __( "Payment action required for your !!sitename!! membership", 'paid-memberships-pro' ),
			'description' => __('Payment Action Required', 'paid-memberships-pro' ),
			'body' => __( '<p>Customer authentication is required to finish setting up your subscription at !!sitename!!.</p>

<p>Please complete the verification steps issued by your payment provider at the following link:</p>
<p>!!invoice_url!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the user when an attempted membership checkout requires additional customer authentication.', 'paid-memberships-pro' )
		),
		'payment_action_admin' => array(
			'subject' => __( "Payment action required: membership for !!user_login!! at !!sitename!!", 'paid-memberships-pro' ),
			'description' => __('Payment Action Required (admin)', 'paid-memberships-pro'),
			'body' => __( '<p>A payment at !!sitename!! for !!user_login!! requires additional customer authentication to complete.</p>
<p>Below is a copy of the email we sent to !!user_email!! to notify them that they need to complete their payment:</p>

<p>Customer authentication is required to finish setting up your subscription at !!sitename!!.</p>

<p>Please complete the verification steps issued by your payment provider at the following link:</p>
<p>!!invoice_url!!</p>', 'paid-memberships-pro' ),
		'help_text' => __( 'This email is sent to the site administrator when an attempted membership checkout requires additional customer authentication.', 'paid-memberships-pro' )
		)
	) );
}

// Add any templates registered via the PMPro_Email_Template class.
$registered_templates = PMPro_Email_Template::get_all_email_templates();
foreach ( $registered_templates as $registered_template_slug => $registered_template_class ) {
	$pmpro_email_templates_defaults[ $registered_template_slug ] = array(
		'subject'     => $registered_template_class::get_default_subject(),
		'description' => $registered_template_class::get_template_name(),
		'body'        => $registered_template_class::get_default_body(),
		'help_text'   => $registered_template_class::get_template_description(),
	);
}

/**
 * Filter default template settings and add new templates.
 *
 * @since 0.5.7
 */
$pmpro_email_templates_defaults = apply_filters( 'pmproet_templates', $pmpro_email_templates_defaults );
