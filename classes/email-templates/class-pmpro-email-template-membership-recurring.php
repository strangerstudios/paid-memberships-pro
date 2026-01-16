<?php
class PMPro_Email_Template_Membership_Recurring extends PMPro_Email_Template {

	/**
	 * The subscription object relating to this transaction.
	 *
	 * @var PMPro_Subscription
	 */
	protected $subscription_obj;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param PMPro_Subscription $subscription_obj The PMPro subscription object.
	 */
	public function __construct( PMPro_Subscription $subscription_obj ) {
		$this->subscription_obj = $subscription_obj;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 3.4
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'membership_recurring';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Recurring Payment Reminder', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent when a subscription is approaching its renewal date. The additional placeholders !!renewaldate!! and !!billing_amount!! can be used to print the date that the subscription will renew and the renewal price.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( "Your membership at !!sitename!! will renew soon", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Thank you for your membership to !!sitename!!.</p>

<p>This is just a reminder that your !!membership_level_name!! membership will automatically renew on !!renewaldate!!.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>If for some reason you do not want to renew your membership you can cancel here: !!cancel_url!!</p>', 'paid-memberships-pro' ) );
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since 3.4
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		$user = get_userdata( $this->subscription_obj->get_user_id() );
		return $user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since 3.4
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		$user = get_userdata( $this->subscription_obj->get_user_id() );
		return $user->display_name;
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
			'!!membership_cost!!' => esc_html__( 'The cost of the membership level.', 'paid-memberships-pro' ),
			'!!billing_amount!!' => esc_html__( 'The amount billed for the subscription.', 'paid-memberships-pro' ),
			'!!renewaldate!!' => esc_html__( 'The date of the next payment.', 'paid-memberships-pro' ),
			'!!cancel_url!!' => esc_html__( 'The link to cancel the subscription.', 'paid-memberships-pro' ),
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
		$subscription_obj = $this->subscription_obj;
		$membership_id = $subscription_obj->get_membership_level_id();		
		$membership_level = pmpro_getLevel( $membership_id );
		$user = get_userdata( $subscription_obj->get_user_id() );

		return array(
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => $membership_id,
			'membership_level_name' => $membership_level->name,
			'membership_cost' => $subscription_obj->get_cost_text(),
			'billing_amount' =>  pmpro_formatPrice( $subscription_obj->get_billing_amount() ),
			'renewaldate' => date_i18n( get_option( 'date_format' ), $subscription_obj->get_next_payment_date() ),
			'cancel_link' => wp_login_url( pmpro_url( 'cancel' ) ),
			'cancel_url' => wp_login_url( pmpro_url( 'cancel' ) ),
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
		$test_user = $current_user;
		$all_levels = pmpro_getAllLevels( true );
		$test_user->membership_level = array_pop( $all_levels );
		$test_subscription = new PMPro_Subscription( array( 'user_id' => $test_user->ID, 'membership_level_id' => $test_user->membership_level->id, 'next_payment_date' => date( 'Y-m-d', strtotime( '+1 month' )  ) )  );
		return array( $test_subscription );
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
function pmpro_email_templates_membership_recurring( $email_templates ) {
	$email_templates['membership_recurring'] = 'PMPro_Email_Template_Membership_Recurring';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_membership_recurring' );