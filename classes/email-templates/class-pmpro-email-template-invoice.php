<?php
class PMPro_Email_Template_Invoice extends PMPro_Email_Template {

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
		return 'invoice';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Recurring Payment Receipt', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent to the member each time a new subscription payment is made.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( "Recurring payment receipt for !!sitename!! membership", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Thank you for your membership to !!sitename!!. Below is a receipt for your most recent membership order.</p>

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

<p>View an online version of this order here: !!order_url!!</p>', 'paid-memberships-pro' ) );
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
			'!!order_url!!' => esc_html__( 'The URL of the order.', 'paid-memberships-pro' ),
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
			'!!discount_code!!' => esc_html__( 'The discount code used for the order.', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => esc_html__( 'The expiration date of the membership level.', 'paid-memberships-pro' ),
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
		global $wpdb;
		$user = $this->user;
		$order = $this->order;
		//If user is not the same one from order, let's get the user from order to fill the email
		if( $order->user_id != $user->ID ) {
			$user = get_userdata( $order->user_id );
		}

		$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $order->membership_id );
 		if ( empty( $membership_level ) ) {
 			$membership_level = pmpro_getLevel( $order->membership_id );
 		}
		
		//Get discount code if it exists
		$discount_code = "";
		if( $order->getDiscountCode() && !empty( $order->discount_code->code ) ) {
			$discount_code = "<p>" . esc_html__("Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code->code . "</p>\n";
		} else {
			$discount_code = "<p>" . esc_html__("Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code . "</p>\n";
		}

		//Get membership expiration date
		$membership_expiration = '';
		if( ! empty( $membership_level->enddate ) ) {
			$membership_expiration = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $membership_level->enddate)) . "</p>\n";
		}

		$email_template_variables = array(
			'subject' => $this->get_default_subject(),
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,	
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'order_id' => $order->code,
			'order_total' => $order->get_formatted_total(),
			'order_date' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
			'order_link' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
			'order_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
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
			'discount_code' => $discount_code,
			'membership_expiration' => $membership_expiration
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
function pmpro_email_templates_invoice( $email_templates ) {
	$email_templates['invoice'] = 'PMPro_Email_Template_Invoice';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_invoice' );