<?php

// File used to setup default email templates data.
global $pmpro_email_templates_defaults;

$check_gateway_label = get_option( 'pmpro_check_gateway_label' ) ? get_option( 'pmpro_check_gateway_label' ) : esc_html__( 'Check', 'paid-memberships-pro' );
 
// Default email templates.
$pmpro_email_templates_defaults = array(
	'default' => array(
		'subject' => esc_html__( "An email from !!sitename!!", 'paid-memberships-pro' ),
		'description' => esc_html__( 'Default Email', 'paid-memberships-pro'),
		'body' => esc_html__( '!!body!!', 'paid-memberships-pro' ),
		'help_text' => esc_html__( 'This email is sent when there is a general message that needs to be communicated to the site administrator.', 'paid-memberships-pro' )
	),
	'footer' => array(
		'subject' => '',
		'description' => esc_html__( 'Email Footer', 'paid-memberships-pro'),
		'body' => wp_kses_post( '<p>' . esc_html__( 'Respectfully,', 'paid-memberships-pro' ) . '<br />!!sitename!! </p>' ),
		'help_text' => esc_html__( 'This is the closing message included in every email sent to members and the site administrator through Paid Memberships Pro.', 'paid-memberships-pro' )
	),
	'header' => array(
		'subject'     => '',
		'description' => esc_html__( 'Email Header', 'paid-memberships-pro'),
		'body' => wp_kses_post( sprintf( '<p>%s</p>', esc_html__( 'Dear !!header_name!!,', 'paid-memberships-pro' ) ) ),
		'help_text' => esc_html__( 'This is the opening message included in every email sent to members and the site administrator through Paid Memberships Pro.', 'paid-memberships-pro' )
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
