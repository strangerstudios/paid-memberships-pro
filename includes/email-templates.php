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
);

// Add any templates registered via the PMPro_Email_Template class.
$registered_templates = PMPro_Email_Template::get_all_email_templates();
$default_gateway = get_option( 'pmpro_gateway' );
// if gateway is not stripe, remove the payment action emails
if( 'stripe' !== $default_gateway ) {
	unset( $registered_templates['payment_action'] );
	unset( $registered_templates['payment_action_admin'] );
}
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
