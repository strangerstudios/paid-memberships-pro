<?php

class PMPro_Email_Template_Billing_Failure_Admin extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The {@link MemberOrder} object of the order that was updated.
	 *
	 * @var MemberOrder
	 */
	protected $order;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param MemberOrder $order The order object that is associated to the member.
	 */
	public function __construct( WP_User $user,  MemberOrder $order ) {
		$this->user = $user;
		$this->order = $order;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 3.4
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'billing_failure_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Payment Failure (admin)', 'paid-memberships-pro' );
	}

	/**
	 * Get the email template description.
	 *
	 * @since 3.4
	 *
	 * @return string The email template description.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent to the site admin when a member\'s payment fails.', 'paid-memberships-pro' );
	}

	/**
	 * Get the email subject.
	 *
	 * @since 3.4
	 *
	 * @return string The email subject.
	 */
	public static function get_default_subject() {
		return esc_html__( "Membership payment failed for !!display_name!! at !!sitename!!", 'paid-memberships-pro' );
	}

	/**
	 * Get the email body.
	 *
	 * @since 3.4
	 *
	 * @return string The email body.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>The subscription payment for !!user_login!! for level !!membership_level_name!! at !!sitename!! has failed.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>Membership Level: !!membership_level_name!!</p>

<p>Log in to your WordPress admin here: !!login_url!!</p>', 'paid-memberships-pro' ) );
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since 3.4
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return get_bloginfo( 'admin_email' );
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since 3.4
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		//get user by email
		$user = get_user_by( 'email', $this->get_recipient_email() );
		return empty( $user->display_name ) ? esc_html__( 'Admin', 'paid-memberships-pro' ) : $user->display_name;	
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'!!display_name!!' => esc_html__( 'The display name of the user.', 'paid-memberships-pro' ),
			'!!user_login!!' => esc_html__( 'The username of the user.', 'paid-memberships-pro' ),
			'!!user_email!!' => esc_html__( 'The email address of the user.', 'paid-memberships-pro' ),
			'!!membership_id!!' => esc_html__( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => esc_html__( 'The name of the membership level.', 'paid-memberships-pro' ),
			'!!billing_address!!' => esc_html__( 'The complete billing address of the order.', 'paid-memberships-pro' ),
			'!!billing_name!!' => esc_html__( 'The billing name of the order.', 'paid-memberships-pro' ),
			'!!billing_street!!' => esc_html__( 'The billing street of the order.', 'paid-memberships-pro' ),
			'!!billing_street2!!' => esc_html__( 'The billing street line 2 of the order.', 'paid-memberships-pro' ),
			'!!billing_city!!' => esc_html__( 'The billing city of the order.', 'paid-memberships-pro' ),
			'!!billing_state!!' => esc_html__( 'The billing state of the order.', 'paid-memberships-pro' ),
			'!!billing_zip!!' => esc_html__( 'The billing ZIP code of the order.', 'paid-memberships-pro' ),
			'!!billing_country!!' => esc_html__( 'The billing country of the order.', 'paid-memberships-pro' ),
			'!!billing_phone!!' => esc_html__( 'The billing phone number of the order.', 'paid-memberships-pro' ),
			'!!cardtype!!' => esc_html__( 'The type of credit card used.', 'paid-memberships-pro' ),
			'!!accountnumber!!' => esc_html__( 'The last four digits of the credit card number.', 'paid-memberships-pro' ),
			'!!expirationmonth!!' => esc_html__( 'The expiration month of the credit card.', 'paid-memberships-pro' ),
			'!!expirationyear!!' => esc_html__( 'The expiration year of the credit card.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$order = $this->order;
		$user = $this->user;
		$membership_level = pmpro_getLevel( $order->membership_id );
		return array(
			'subject' => $this->get_default_subject(),
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'billing_name' => $order->billing->name,
			'billing_street' => $order->billing->street,
			'billing_street2' => $order->billing->street2,
			'billing_city' => $order->billing->city,
			'billing_state' => $order->billing->state,
			'billing_zip' => $order->billing->zip,
			'billing_country' => $order->billing->country,
			'billing_phone' => $order->billing->phone,
			'billing_address'=> pmpro_formatAddress( $order->billing->name,
				$order->billing->street,
				$order->billing->street2,
				$order->billing->city,
				$order->billing->state,
				$order->billing->zip,
				$order->billing->country,
				$order->billing->phone ),
			'cardtype' => $order->cardtype,
			'accountnumber' => hideCardNumber( $order->accountnumber ),
			'expirationmonth' => $order->expirationmonth,
			'expirationyear' => $order->expirationyear,
		);
	}
}

/**
 * Register the email template.
 *
 * @since 3.4
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_billing_failure_admin( $email_templates ) {
	$email_templates['billing_failure_admin'] = 'PMPro_Email_Template_Billing_Failure_Admin';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_billing_failure_admin' );


