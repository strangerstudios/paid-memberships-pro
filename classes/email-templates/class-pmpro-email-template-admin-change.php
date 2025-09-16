<?php

class PMPro_Email_Template_Admin_Change  extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 */
	public function __construct( WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 3.4
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'admin_change';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Admin Change', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'The site administrator can manually update a user\'s membership through the WordPress admin. This email notifies the member of the level update.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( "Your membership at !!sitename!! has been changed", "paid-memberships-pro" );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>An administrator at !!sitename!! has changed your membership level.</p>

<p>!!membership_change!!</p>

<p>If you did not request this membership change and would like more information please contact us at !!siteemail!!</p>

<p>Log in to your membership account here: !!login_url!!</p>', 'paid-memberships-pro' ) );
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
		// If the user no longer has a membership level, set the membership_change text to "Membership has been cancelled."
		if ( ! pmpro_hasMembershipLevel( null,  $this->user->ID ) ) {
			$membership_change = esc_html__( 'Your membership has been cancelled.', 'paid-memberships-pro' );
		} else {
			$membership_change = esc_html__( 'You can view your current memberships by logging in and visiting your membership account page.', 'paid-memberships-pro' );
		}

		$email_template_variables = array(
			'membership_change' => $membership_change,
			'subject' => $this->get_default_subject(),
			'name' => $user->display_name, 
			'display_name' => $user->display_name, 
			'user_login' => $user->user_login, 
			'user_email' => $user->user_email, 
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
			'!!membership_change!!' => esc_html__( 'A message indicating the change in membership.', 'paid-memberships-pro' ),
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
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since 3.5
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user;

		return array( $current_user );
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
function pmpro_email_templates_change( $email_templates ) {
	$email_templates['admin_change'] = 'PMPro_Email_Template_Admin_Change';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_change' );
