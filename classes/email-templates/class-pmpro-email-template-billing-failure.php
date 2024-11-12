<?php

class PMPro_Email_Template_Billing_Failure extends PMPro_Email_Template {

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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'billing_failure';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Payment Failure', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		return __( 'This email is sent out if a recurring payment has failed, usually due to an expired or cancelled credit card. This email is sent to the member to allowing them time to update payment information without a disruption in access to your site.', 'paid-memberships-pro' );
	}

	/**
	 * Get the email subject.
	 *
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public static function get_default_subject() {
		return __( "Membership payment failed at !!sitename!!", 'paid-memberships-pro' );
	}

	/**
	 * Get the email body.
	 *
	 * @since TBD
	 *
	 * @return string The email body.
	 */
	public static function get_default_body() {
		return __( '<p>The current subscription payment for level !!membership_level_name!! at !!sitename!! membership has failed. <strong>Please click the following link to log in and update your billing information to avoid account suspension.</strong><br/> !!login_url!! </p>
		<p>Account: !!display_name!! (!!user_email!!)</p>', 'paid-memberships-pro' );
	}

		/**
	 * Get the email address to send the email to.
	 *
	 * @since TBD
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return $this->user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		return $this->user->display_name;
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'!!subject!!' => __( 'The default subject for the email. This will be removed in a future version.', 'paid-memberships-pro' ),
			'!!header_name!!' => __( 'The name of the email recipient.', 'paid-memberships-pro' ),
			'!!name!!' => __( 'The display name of the user.', 'paid-memberships-pro' ),
			'!!user_login!!' => __( 'The username of the user.', 'paid-memberships-pro' ),
			'!!user_email!!' => __( 'The email address of the user billing failed', 'paid-memberships-pro' ),
			'!!display_name!!' => __( 'The display name of the user billing failed', 'paid-memberships-pro' ),
			'!!membership_id!!' => __( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => __( 'The name of the membership level.', 'paid-memberships-pro' ),
			'!!billing_name!!' => __( 'Billing Info Name', 'paid-memberships-pro' ),
			'!!billing_street!!' => __( 'Billing Info Street', 'paid-memberships-pro' ),
			'!!billing_street2!!' => __( 'Billing Info Street 2', 'paid-memberships-pro' ),
			'!!billing_city!!' => __( 'Billing Info City', 'paid-memberships-pro' ),
			'!!billing_state!!' => __( 'Billing Info State', 'paid-memberships-pro' ),
			'!!billing_zip!!' => __( 'Billing Info Zip', 'paid-memberships-pro' ),
			'!!billing_country!!' => __( 'Billing Info Country', 'paid-memberships-pro' ),
			'!!billing_phone!!' => __( 'Billing Info Phone', 'paid-memberships-pro' ),
			'!!billing_address!!' => __( 'Billing Info Complete Address', 'paid-memberships-pro' ),
			'!!cardtype!!' => __( 'Credit Card Type', 'paid-memberships-pro' ),
			'!!accountnumber!!' => __( 'Credit Card Number (last 4 digits)', 'paid-memberships-pro' ),
			'!!expirationmonth!!' => __( 'Credit Card Expiration Month (mm format)', 'paid-memberships-pro' ),
			'!!expirationyear!!' => __( 'Credit Card Expiration Year (yyyy format)', 'paid-memberships-pro' ),

		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$order = $this->order;
		$user = $this->user;
		$membership_level = pmpro_getLevel( $order->membership_id );
		return array(
			'subject' => $this->get_default_subject(),
			'header_name' => $user->display_name,
			'name' => $user->display_name,
			'user_login' => $user->user_login,
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'display_name' => $user->display_name,
			'user_email' => $user->user_email,
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
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_billing_failure( $email_templates ) {
	$email_templates['billing_failure'] = 'PMPro_Email_Template_Billing_Failure';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_billing_failure' );