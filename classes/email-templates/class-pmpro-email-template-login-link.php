<?php
class PMPro_Email_Template_Login_Link extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The login link for the user to click to authenticate.
	 * 
	 * @var string
	 */
	protected $login_link;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param string $login_link The login link for the user to click to authenticate. (The full login link).
	 */
	public function __construct( WP_User $user, string $login_link  ) {
		$this->user = $user;
		$this->login_link = $login_link;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'login_link';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Login Link', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'Sends a one-time login token for quick and secure sign-in. Please note the !!login_link!! variable is required in order for this email to work properly.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( "Your Secure Login Link for !!sitename!!", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Click the secure link below to access your account:</p>

<p>!!login_link!!</p>

<p>This link can only be used once and will expire shortly.</p>', 'paid-memberships-pro' ) );
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

		$email_template_variables = array(
			'subject' => $this->get_default_subject(),
			'login_link' => $this->login_link,
			'sitename' => get_bloginfo( 'name' )
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
			'!!login_link!!'            => esc_html__( '(Required) The unique one-time login link.', 'paid-memberships-pro' )
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
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since 3.5
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user;
		return array( $current_user, add_query_arg( 'pmpro_email_login_token', pmpro_login_generate_login_token( $current_user->ID ), home_url() ) );
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
function pmpro_email_templates_login_link( $email_templates ) {
	$email_templates['login_link'] = 'PMPro_Email_Template_Login_Link';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_login_link' );
