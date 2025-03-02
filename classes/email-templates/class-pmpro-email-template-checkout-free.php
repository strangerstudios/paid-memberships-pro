<?php

class PMPro_Email_Template_Checkout_Free extends PMPro_Email_Template {

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
		return 'checkout_free';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Checkout - Free', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		return esc_html__( 'This is a membership confirmation welcome email sent to a new member or to existing members that change their level when the level has no charge.', 'paid-memberships-pro' );
	}

	/**
	 * Get the email subject.
	 *
	 * @since 3.4
	 *
	 * @return string The email subject.
	 */
	public static function get_default_subject() {
		return sprintf( esc_html__( 'Your membership confirmation for %s', 'paid-memberships-pro' ), get_option( 'blogname' ) );
	}

	/**
	 * Get the email body.
	 *
	 * @since 3.4
	 *
	 * @return string The email body.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Thank you for your membership to !!sitename!!. Your membership account is now active.</p>

!!membership_level_confirmation_message!!

<p>Below are details about your membership account.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>Membership Level: !!membership_level_name!!</p>
!!membership_expiration!! !!discount_code!!

<p>Log in to your membership account here: !!login_url!!</p>', 'paid-memberships-pro' ) );
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
			'!!subject!!' => esc_html__( 'The default subject for the email. This will be removed in a future version.', 'paid-memberships-pro' ),
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
			'name' => $this->get_recipient_name(),
			'display_name' => $this->get_recipient_name(),
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => $membership_level->id,
			'membership_level_name' => $membership_level->name,
			'membership_level_confirmation_message' => $confirmation_message,
			'membership_cost' => pmpro_getLevelCost( $membership_level ),
			'membership_expiration' => $membership_expiration,
			'order_id' => $order->code,
			'order_date' => date_i18n( get_option( 'date_format' ), $order->getTimestamp() ),
			'discount_code' => $discount_code,
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
function pmpro_email_templates_checkout_free( $email_templates ) {
	$email_templates['checkout_free'] = 'PMPro_Email_Template_Checkout_Free';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_checkout_free' );