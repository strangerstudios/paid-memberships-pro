<?php

class PMPro_Email_Template_Checkout_Check_Admin extends PMPro_Email_Template {

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
		return 'checkout_check_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Checkout - Pay by Check (admin)', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		$check_gateway_label = get_option( 'pmpro_check_gateway_label' ) ? get_option( 'pmpro_check_gateway_label' ) : esc_html__( 'Check', 'paid-memberships-pro' );
		return sprintf( esc_html__('This is the membership confirmation email sent to the site administrator for every membership checkout using the "%s (Pay by Check)" gateway.', 'paid-memberships-pro' ),  $check_gateway_label );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Member checkout for !!membership_level_name!! at !!sitename!!', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body for the email.
	 */
	public static function get_default_body() {

		return wp_kses_post( __( '<p>There was a new member checkout at !!sitename!!.</p>

<p><strong>They have chosen to pay by !!check_gateway_label!!.</strong></p>

<p>Below are details about the new membership account and a receipt for the initial membership order.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>Membership Level: !!membership_level_name!!</p>

<p>Membership Fee: !!membership_cost!!</p>
!!membership_expiration!! !!discount_code!!

<p>
	Order #!!order_id!! on !!order_date!!<br />
	Total Billed: !!order_total!!
</p>

<p>Log in to your WordPress admin here: !!login_url!!</p>','paid-memberships-pro' ) );
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
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$order = $this->order;
		$user = $this->user;
		$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $order->membership_id );
		if ( empty( $membership_level ) ) {
			$membership_level = pmpro_getLevel( $order->membership_id );
		}

		$confirmation_in_email = get_pmpro_membership_level_meta( $membership_level->id, 'confirmation_in_email', true );
		if ( ! empty( $confirmation_in_email ) ) {
			$confirmation_message = $membership_level->confirmation;
		} else {
			$confirmation_message = '';
		}

		$membership_expiration = '';
		if( ! empty( $membership_level->enddate ) ) {
			$membership_expiration = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $membership_level->enddate)) . "</p>\n";
		}

		if( $order->getDiscountCode() ) {
			$discount_code = "<p>" . esc_html__( "Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code->code . "</p>\n";
		} else {
			$discount_code = "";
		}

		$email_template_variables = array(
			'subject' => $this->get_default_subject(),
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'membership_level_confirmation_message' => $confirmation_message,
			'membership_cost' => pmpro_getLevelCost($membership_level),
			'membership_expiration' => $membership_expiration,
			'order_id' => $order->code,
			'order_total' => $order->get_formatted_total(),
			'order_date' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
			'order_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
			'discount_code' => $discount_code,
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
			'check_gateway_label' => get_option( 'pmpro_check_gateway_label' ) ? get_option( 'pmpro_check_gateway_label' ) : esc_html__( 'Check', 'paid-memberships-pro' )
		);

		return $email_template_variables;
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
			'!!membership_level_confirmation_message!!' => esc_html__( 'The confirmation message for the membership level.', 'paid-memberships-pro' ),
			'!!membership_cost!!' => esc_html__( 'The cost of the membership level.', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => esc_html__( 'The expiration date of the membership level.', 'paid-memberships-pro' ),
			'!!order_id!!' => esc_html__( 'The ID of the order.', 'paid-memberships-pro' ),
			'!!order_total!!' => esc_html__( 'The total cost of the order.', 'paid-memberships-pro' ),
			'!!order_date!!' => esc_html__( 'The date of the order.', 'paid-memberships-pro' ),
			'!!order_url!!' => esc_html__( 'The URL of the order.', 'paid-memberships-pro' ),
			'!!discount_code!!' => esc_html__( 'The discount code used for the order.', 'paid-memberships-pro' ),
			'!!billing_address!!' => esc_html__( 'The complete billing address of the order.', 'paid-memberships-pro' ),
			'!!billing_name!!' => esc_html__( 'The billing name of the order.', 'paid-memberships-pro' ),
			'!!billing_street!!' => esc_html__( 'The billing street of the order.', 'paid-memberships-pro' ),
			'!!billing_street2!!' => esc_html__( 'The billing street line 2 of the order.', 'paid-memberships-pro' ),
			'!!billing_city!!' => esc_html__( 'The billing city of the order.', 'paid-memberships-pro' ),
			'!!billing_state!!' => esc_html__( 'The billing state of the order.', 'paid-memberships-pro' ),
			'!!billing_zip!!' => esc_html__( 'The billing ZIP code of the order.', 'paid-memberships-pro' ),
			'!!billing_country!!' => esc_html__( 'The billing country of the order.', 'paid-memberships-pro' ),
			'!!billing_phone!!' => esc_html__( 'The billing phone number of the order.', 'paid-memberships-pro' ),
			'!!check_gateway_label!!' => esc_html__( 'The default or custom label for the check gateway.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since 3.5
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user;
		//Create test order
		$test_order = new MemberOrder();

		return array( $current_user, $test_order->get_test_order() );
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
function pmpro_email_templates_checkout_check_admin( $email_templates ) {
	$email_templates['checkout_check_admin'] = 'PMPro_Email_Template_Checkout_Check_Admin';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_checkout_check_admin' );