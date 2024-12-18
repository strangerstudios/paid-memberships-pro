<?php
/**
 * Email Template: Checkout - Free
 *
 * @since TBD
 */
class PMPro_Email_Template_Checkout_Paid extends PMPro_Email_Template {

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
		return 'checkout_paid';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Checkout - Paid', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		return __( 'This is a membership confirmation welcome email sent to a new member or to existing members that change their level and complete a paid checkout on the site.', 'paid-memberships-pro' );
	}	

	/**
	 * Get the email subject.
	 *
	 * @since TBD
	 *
	 * @return string The email subject.
	 */
	public static function get_default_subject() {
		return __( "Your membership confirmation for !!sitename!!", 'paid-memberships-pro' );
	}

	/**
	 * Get the email body.
	 *
	 * @since TBD
	 *
	 * @return string The email body.
	 */
	public static function get_default_body() {
		return  __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>
		!!membership_level_confirmation_message!!
		<p>Below are details about your membership account and a receipt for your initial membership order.</p>
		
		<p>Account: !!display_name!! (!!user_email!!)</p>
		<p>Membership Level: !!membership_level_name!!</p>
		<p>Membership Fee: !!membership_cost!!</p>
		!!membership_expiration!! !!discount_code!!
		
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
		
		<p>Log in to your membership account here: !!login_url!!</p>', 'paid-memberships-pro' );
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
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		global $wpdb;
		$order = $this->order;
		$user = $this->user;
		$membership_level = pmpro_getLevel( $order->membership_id );

		$confirmation_in_email = get_pmpro_membership_level_meta( $membership_level->id, 'confirmation_in_email', true );
			if ( ! empty( $confirmation_in_email ) ) {
				$confirmation_message = $membership_level->confirmation;
			} else {
				$confirmation_message = '';
			}

		$membership_expiration = '';
		$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
		if( $enddate ) {
			$membership_expiration = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
		}

		$discount_code = '';
		if( $order->getDiscountCode() ) {
			$discount_code = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code->code . "</p>\n";
		}

		$email_template_variables = array(
			'subject' => $this->get_default_subject(),
			'name' => $this->get_recipient_name(),
			'display_name' => $this->get_recipient_name(),
			'user_login' => $user->user_login,
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'membership_level_confirmation_message' => $confirmation_message,
			'membership_cost' => pmpro_getLevelCost($membership_level),
			'user_email' => $user->user_email,
			'order_id' => $order->code,
			'order_total' => $order->get_formatted_total(),
			'order_date' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
			'billing_address' => pmpro_formatAddress( $order->billing->name,
														 $order->billing->street,
														 $order->billing->street2,
														 $order->billing->city,
														 $order->billing->state,
														 $order->billing->zip,
														 $order->billing->country,
														 $order->billing->phone ),
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
			'membership_expiration' => $membership_expiration,
			'discount_code' => $discount_code,


		);

		return $email_template_variables;
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
			'!!subject!!' => __( 'The subject of the email.', 'paid-memberships-pro' ),
			'!!name!!' => __( 'The name of the email recipient.', 'paid-memberships-pro' ),
			'!!display_name!!' => __( 'The name of the email recipient.', 'paid-memberships-pro' ),
			'!!user_login!!' => __( 'The login name of the email recipient.', 'paid-memberships-pro' ),
			'!!membership_id!!' => __( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => __( 'The name of the membership level.', 'paid-memberships-pro' ),
			'!!confirmation_message!!' => __( 'The confirmation message for the membership level.', 'paid-memberships-pro' ),
			'!!membership_cost!!' => __( 'The cost of the membership level.', 'paid-memberships-pro' ),
			'!!user_email!!' => __( 'The email address of the email recipient.', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => __( 'The expiration date of the membership level.', 'paid-memberships-pro' ),
			'!!discount_code!!' => __( 'The discount code used for the membership level.', 'paid-memberships-pro' ),
			'!!order_id!!' => __( 'The ID of the order.', 'paid-memberships-pro' ),
			'!!order_date!!' => __( 'The date of the order.', 'paid-memberships-pro' ),
			'!!order_total!!' => __( 'The total cost of the order.', 'paid-memberships-pro' ),
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
}

/**
 * Register the email template.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_checkout_paid( $email_templates ) {
	$email_templates['checkout_paid'] = 'PMPro_Email_Template_Checkout_Paid';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_checkout_paid' );