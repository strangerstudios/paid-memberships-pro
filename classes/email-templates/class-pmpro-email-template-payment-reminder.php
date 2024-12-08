<?php
class PMPro_Email_Template_Payment_Reminder extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var PMPro_Subscription
	 */
	protected $subscription_obj;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int $membership_id The membership level id of the membership level that expired.
	 */
	public function __construct( PMPro_Subscription $subscription_obj ) {
		$this->subscription_obj = $subscription_obj;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'membership_recurring';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Membership Recurring', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return __( 'This email is sent when a subscription is approaching its renewal date. The additional placeholders !!renewaldate!! and !!billing_amount!! can be used to print the date that the subscription will renew and the renewal price.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return __( "Your membership at !!sitename!! will end soon", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>Thank you for your membership to !!sitename!!.</p>

		<p>This is just a reminder that your !!membership_level_name!! membership will automatically renew on !!renewaldate!!.</p>

		<p>Account: !!display_name!! (!!user_email!!)</p>

		<p>If for some reason you do not want to renew your membership you can cancel by clicking here: !!cancel_link!!</p>', 'paid-memberships-pro' );
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
			'membership_level_name' => __( 'The name of the membership level.', 'paid-memberships-pro' ),
			'renewaldate' => __( 'The date of the next payment date.', 'paid-memberships-pro' ),
			'display_name' => __( 'The display name of the user.', 'paid-memberships-pro' ),
			'user_email' => __( 'The email address of the user.', 'paid-memberships-pro' ),
			'cancel_link' => __( 'The link to cancel the subscription.', 'paid-memberships-pro' ),
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
		$subscription_obj = $this->subscription_obj;
		$membership_id = $subscription_obj->get_membership_level_id();		
		$membership_level = pmpro_getLevel( $membership_id );
		$user = get_userdata( $subscription_obj->get_user_id() );

		return array(
			'membership_level_name' => $membership_level->name,
			'renewaldate' => date_i18n( get_option( 'date_format' ), $subscription_obj->get_next_payment_date() ),
			'display_name' => $user->display_name,
			'user_email' => $user->user_email,
			'cancel_link' => wp_login_url( pmpro_url( 'cancel' ) ),
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
function pmpro_email_templates_membership_recurring( $email_templates ) {
	$email_templates['membership_recurring'] = 'PMPro_Email_Template_Payment_Reminder';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_membership_recurring' );