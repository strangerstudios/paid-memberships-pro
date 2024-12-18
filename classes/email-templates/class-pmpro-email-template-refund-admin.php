<?php

class PMPro_Email_Template_Refund_Admin extends PMPro_Email_Template {
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
		return 'refund_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Refund (admin)', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return __( 'This email is sent to the admin as confirmation of a refunded payment. The email is sent after your 
			membership site receives notification of a successful payment refund through your gateway.', 'paid-memberships-pro' );

	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return __( 'Order #!!order_id!! at !!sitename!! has been REFUNDED', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>Order #!!order_id!! at !!sitename!! has been refunded.</p>

		<p>Account: !!display_name!! (!!user_email!!)</p>
		<p>
			Order #!!order_id!! on !!order_date!!<br />
			Total Refunded: !!order_total!!
		</p>

		<p>Log in to your WordPress admin here: !!login_url!!</p>', 'paid-memberships-pro' );
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
			'!!user_login!!' => __( 'The login name of the user who requested the refund.', 'paid-memberships-pro' ),
			'!!user_email!!' => __( 'The email address of the user who requested the refund.', 'paid-memberships-pro' ),
			'!!display_name!!' => __( 'The display name of the user who requested the refund.', 'paid-memberships-pro' ),
			'!!membership_id!!' => __( 'The ID of the membership level that was refunded.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => __( 'The name of the membership level that was refunded.', 'paid-memberships-pro' ),
			'!!order_id!!' => __( 'The order ID of the refunded order.', 'paid-memberships-pro' ),
			'!!order_total!!' => __( 'The total amount refunded.', 'paid-memberships-pro' ),
			'!!order_date!!' => __( 'The date the refund was processed.', 'paid-memberships-pro' ),
			'!!billing_name!!' => __( 'The billing name associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_street!!' => __( 'The billing street address associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_street2!!' => __( 'The billing street address line 2 associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_city!!' => __( 'The billing city associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_state!!' => __( 'The billing state associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_zip!!' => __( 'The billing zip code associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_country!!' => __( 'The billing country associated with the refunded order.', 'paid-memberships-pro' ),
			'!!billing_phone!!' => __( 'The billing phone number associated with the refunded order.', 'paid-memberships-pro' ),
			'!!cardtype!!' => __( 'The card type used for the refunded order.', 'paid-memberships-pro' ),
			'!!accountnumber!!' => __( 'The last four digits of the account number used for the refunded order.', 'paid-memberships-pro' ),
			'!!expirationmonth!!' => __( 'The expiration month of the card used for the refunded order.', 'paid-memberships-pro' ),
			'!!expirationyear!!' => __( 'The expiration year of the card used for the refunded order.', 'paid-memberships-pro' ),
			'!!order_link!!' => __( 'The URL to the invoice for the refunded order.', 'paid-memberships-pro' ),
			'!!order_url!!' => __( 'The URL to the invoice for the refunded order.', 'paid-memberships-pro' ),
		);

	}
	
	/**
	 * Get the email address to send the email to.
	 *
	 * @since TBD
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return get_bloginfo( 'admin_email' );
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		//get user by email
		$user = get_user_by( 'email', $this->get_recipient_email() );
		return $user->display_name;
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {

		$user = $this->user;
		$order = $this->order;
		$level = pmpro_getLevel( $order->membership_id );

		$email_template_variables = array(
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'display_name' => $user->display_name,
			'membership_id' => $order->membership_id,
			'membership_level_name' => $level->name,
			'order_id' => $order->code,
			'order_total' => $order->total,
			'order_date' => date_i18n( get_option( 'date_format' ), $order->timestamp ),
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
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_refund_admin( $email_templates ) {
	$email_templates['refund_admin'] = 'PMPro_Email_Template_Refund_Admin';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_refund_admin' );
