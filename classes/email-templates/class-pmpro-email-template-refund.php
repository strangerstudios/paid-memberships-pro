<?php

class PMPro_Email_Template_Refund extends PMPro_Email_Template {
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
		return 'refund';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Refund', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent to the member as confirmation of a refunded payment. The email is sent after your membership site receives notification of a successful payment refund through your gateway.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Order #!!order_id!! at !!sitename!! has been REFUNDED', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return  wp_kses_post( __( '<p>Order #!!order_id!! at !!sitename!! has been refunded.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>
	Order #!!order_id!! refunded on !!refund_date!!<br />
	Total Refunded: !!order_total!!
</p>

<p>Log in to your membership account here: !!login_url!!</p>

<p>View an online version of this order here: !!order_url!!</p>

<p>If you did not request this refund and would like more information please contact us at !!siteemail!!</p>', 'paid-memberships-pro' ) );
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
			'!!order_id!!' => esc_html__( 'The order ID.', 'paid-memberships-pro' ),
			'!!order_total!!' => esc_html__( 'The total amount of the order.', 'paid-memberships-pro' ),
			'!!order_date!!' => esc_html__( 'The date of the order.', 'paid-memberships-pro' ),
			'!!refund_date!!' => esc_html__( 'The refund date of the order.', 'paid-memberships-pro' ),
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
			'!!order_url!!' => esc_html__( 'The URL of the order.', 'paid-memberships-pro' ),			
		);

	}
	/**
	 * Get the email address to send the email to.
	 *
	 * @since 3.4
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return $this->user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since 3.4
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		return $this->user->display_name;
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {

		$user = $this->user;
		$order = $this->order;
		$level = pmpro_getLevel( $order->membership_id );

		$email_template_variables = array(
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => $order->membership_id,
			'membership_level_name' => $level->name,
			'order_id' => $order->code,
			'order_total' => $order->total,
			'order_date' => date_i18n( get_option( 'date_format' ), $order->timestamp ),
			'refund_date' => date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ),
			'billing_name' => $order->billing->name,
			'billing_street' => $order->billing->street,
			'billing_street2' => $order->billing->street2,
			'billing_city' => $order->billing->city,
			'billing_state' => $order->billing->state,
			'billing_zip' => $order->billing->zip,
			'billing_country' => $order->billing->country,
			'billing_phone' => $order->billing->phone,
			'cardtype' => $order->cardtype,
			'accountnumber' => hideCardNumber( $order->accountnumber ),
			'expirationmonth' => $order->expirationmonth,
			'expirationyear' => $order->expirationyear,
			'order_link' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
			'order_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
			'billing_address' => pmpro_formatAddress(
				$order->billing->name,
				$order->billing->street,
				$order->billing->street2,
				$order->billing->city,
				$order->billing->state,
				$order->billing->zip,
				$order->billing->country,
				$order->billing->phone
			),
		);

		return $email_template_variables;

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
function pmpro_email_templates_refund( $email_templates ) {
	$email_templates['refund'] = 'PMPro_Email_Template_Refund';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_refund' );