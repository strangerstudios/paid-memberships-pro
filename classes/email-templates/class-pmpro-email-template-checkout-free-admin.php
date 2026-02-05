<?php
class PMPro_Email_Template_Checkout_Free_Admin extends PMPro_Email_Template {

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
		return 'checkout_free_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Checkout - Free (admin)', 'paid-memberships-pro' );
	}


	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		return esc_html__( 'This is the membership confirmation email sent to the site administrator for every membership checkout that has no charge.', 'paid-memberships-pro' );

	}

	/**
	 * Get the email subject.
	 *
	 * @since 3.4
	 *
	 * @return string The email subject.
	 */
	public static function get_default_subject() {
		return esc_html__( "Member checkout for !!membership_level_name!! at !!sitename!!", 'paid-memberships-pro' );
	}

	/**
	 * Get the email body.
	 *
	 * @since 3.4
	 *
	 * @return string The email body.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>There was a new member checkout at !!sitename!!.</p>

<p>Below are details about the new membership account.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>Membership Level: !!membership_level_name!!</p>
!!membership_expiration!! !!discount_code!!

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
			'!!membership_level_confirmation_message!!' => esc_html__( 'The confirmation message for the membership level.', 'paid-memberships-pro' ),
			'!!membership_cost!!' => esc_html__( 'The cost of the membership level.', 'paid-memberships-pro' ),
			'!!membership_expiration!!' => esc_html__( 'The expiration date of the membership level.', 'paid-memberships-pro' ),
			'!!order_id!!' => esc_html__( 'The ID of the order.', 'paid-memberships-pro' ),
			'!!order_date!!' => esc_html__( 'The date of the order.', 'paid-memberships-pro' ),
			'!!order_url!!' => esc_html__( 'The URL of the order.', 'paid-memberships-pro' ),
			'!!discount_code!!' => esc_html__( 'The discount code used for the order.', 'paid-memberships-pro' ),
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
		$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $order->membership_id );
		if ( empty( $membership_level ) ) {
			$membership_level = pmpro_getLevel( $order->membership_id );
		}

		$confirmation_message = '';
		$confirmation_in_email = get_pmpro_membership_level_meta( $membership_level->id, 'confirmation_in_email', true );
		if ( ! empty( $confirmation_in_email ) ) {
			$confirmation_message = $membership_level->confirmation;
		}

		$membership_expiration = '';
		if( ! empty( $membership_level->enddate ) ) {
			$membership_expiration = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $membership_level->enddate)) . "</p>\n";
		}

		$discount_code = '';
		if( $order->getDiscountCode() ) {
			$discount_code = "<p>" . esc_html__("Discount Code", 'paid-memberships-pro' ) . ": " . $order->discount_code->code . "</p>\n";
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
			'membership_cost' => pmpro_getLevelCost( $membership_level ),
			'membership_expiration' => $membership_expiration,
			'order_id' => $order->code,
			'order_date' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
			'order_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
			'discount_code' => $discount_code,
		);

		return $email_template_variables;
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
function pmpro_email_templates_checkout_free_admin( $email_templates ) {
	$email_templates['checkout_free_admin'] = 'PMPro_Email_Template_Checkout_Free_Admin';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_checkout_free_admin' );