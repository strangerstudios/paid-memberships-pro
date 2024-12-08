
<?php
class PMPro_Email_Template_Payment_Receipt extends PMPro_Email_Template {

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
		return 'invoice';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Recurring Payment Receipt', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return __( 'This email is sent to the member each time a new subscription payment is made.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return __( "Recurring payment receipt for !!sitename!! membership", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>Thank you for your membership to !!sitename!!. Below is a receipt for your most recent membership order.</p>

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
			<p>To view an online version of this order, click here: !!order_url!!</p>', 'paid-memberships-pro' );
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
			'!!membership_id!!' => __( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => __( 'The name of the membership level.', 'paid-memberships-pro' ),
			'!!user_login!!' => __( 'The username of the user.', 'paid-memberships-pro' ),
			'!!user_email!!' => __( 'The email address of the user.', 'paid-memberships-pro' ),
			'!!display_name!!' => __( 'The display name of the user.', 'paid-memberships-pro' ),
			'!!order_id!!' => __( 'The order ID.', 'paid-memberships-pro' ),
			'!!order_total!!' => __( 'The total amount of the order.', 'paid-memberships-pro' ),
			'!!order_date!!' => __( 'The date of the order.', 'paid-memberships-pro' ),
			'!!order_link!!' => __( 'The URL of the order.', 'paid-memberships-pro' ),
			'!!order_url!!' => __( 'The URL of the order.', 'paid-memberships-pro' ),
			'!!billing_name!!' => __( 'The name of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_street!!' => __( 'The street address of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_street2!!' => __( 'The second line of the street address of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_city!!' => __( 'The city of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_state!!' => __( 'The state of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_zip!!' => __( 'The ZIP code of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_country!!' => __( 'The country of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_phone!!' => __( 'The phone number of the billing contact.', 'paid-memberships-pro' ),
			'!!billing_address!!' => __( 'The formatted billing address.', 'paid-memberships-pro' ),
			'!!cardtype!!' => __( 'The type of credit card used for the order.', 'paid-memberships-pro' ),
			'!!accountnumber!!' => __( 'The last four digits of the credit card used for the order.', 'paid-memberships-pro' ),
			'!!expirationmonth!!' => __( 'The expiration month of the credit card used for the order.', 'paid-memberships-pro' ),
			'!!expirationyear!!' => __( 'The expiration year of the credit card used for the order.', 'paid-memberships-pro' ),
			'!!discount_code!!' => __( 'The discount code used for the order.', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => __( 'The expiration date of the membership level.', 'paid-memberships-pro' ),

			

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
		global $wpdb;
		$user = $this->user;
		$order = $this->order;
		$membership_level = pmpro_getLevel( $order->membership_id );
		
		//Get discount code if it exists
		$discount_code = "";
		if( $order->getDiscountCode() && !empty( $order->discount_code->code ) ) {
			$discount_code = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code->code . "</p>\n";
		} else {
			$discount_code = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code . "</p>\n";
		}

		//Get membership expiration date
		$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
		if( $enddate ) {
			$membership_expiration = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
		} else {
			$membership_expiration = "";
		}

		$email_template_variables = array(
			'subject' => $this->get_default_subject(),
			'header_name' => $this->get_recipient_name(),
			'name' => $this->get_recipient_name(),
			'display_name' => $this->get_recipient_name(),
			'user_login' => $user->user_login,
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'user_email' => $user->user_email,	
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
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_payment_receipt( $email_templates ) {
	$email_templates['invoice'] = 'PMPro_Email_Template_Payment_Receipt';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_payment_receipt' );