<?php
abstract class PMPro_Email_Template {
	/**
	 * Get all email templates.
	 *
	 * @since 3.4
	 *
	 * @return array All email templates (template slug => email template class name).
	 */
	final public static function get_all_email_templates() {
		/**
		 * Allow email templates to be registered.
		 *
		 * @since 3.4
		 *
		 * @param array $email_templates All email templates (template slug => email template class name).
		 */
		return apply_filters( 'pmpro_email_templates', array() );
	}

	/**
	 * Get an email template by its slug.
	 *
	 * @since 3.4
	 *
	 * @param string $template_slug The email template slug.
	 * @return string|null The email template class name, or null if the email template is not found.
	 */
	final public static function get_email_template( string $template_slug ) {
		$email_templates = self::get_all_email_templates();
		return isset( $email_templates[ $template_slug ] ) ? $email_templates[ $template_slug ] : null;
	}

	/**
	 * Send the email.
	 *
	 * @since 3.4
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	final public function send() {
		$pmpro_email           = new PMProEmail();
		$pmpro_email->email    = $this->get_recipient_email();
		$pmpro_email->subject  = $this->get_default_subject(); // This will be overridden if there is a subject saved in the database.
		$pmpro_email->body     = $this->get_default_body();
		$pmpro_email->data     = array_merge( $this->get_base_email_template_variables(), $this->get_email_template_variables() );
		$pmpro_email->template = apply_filters_deprecated( 'pmpro_email_template', array( $this->get_template_slug(), $pmpro_email ), '3.4', 'pmpro_email_body' );
		return $pmpro_email->sendEmail();
	}

	/**
	 * Get the base email template variables that should be available for all emails.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	final protected function get_base_email_template_variables() {
		$base_email_template_variables = array(
			'sitename' => get_option( 'blogname' ),
			'siteemail' => get_option( 'pmpro_from_email' ),
			'site_url'  => home_url(),
			'levels_url' => pmpro_url( 'levels' ),
			'levels_link' => pmpro_url( 'levels' ),
			'login_link' => pmpro_login_url(), 
			'login_url' => pmpro_login_url(),
			'header_name' => $this->get_recipient_name(),
		);

		return $base_email_template_variables;
	}

	/**
	 * Get the base email template variables that should be available for all emails paired with a description of the variable.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	final public static function get_base_email_template_variables_with_description() {
		$base_email_template_variables_with_description = array(
			'!!sitename!!' => esc_html__( 'The name of the site.', 'paid-memberships-pro' ),
			'!!siteemail!!' => esc_html__( 'The email address of the site.', 'paid-memberships-pro' ),
			'!!site_url!!'  => esc_html__( 'The URL of the site.', 'paid-memberships-pro' ),
			'!!levels_url!!' => esc_html__( 'The URL of the page where users can view available membership levels.', 'paid-memberships-pro' ),
			'!!login_url!!' => esc_html__( 'The URL of the login page.', 'paid-memberships-pro' ),
			'!!header_name!!' => esc_html__( 'The name of the email recipient.', 'paid-memberships-pro' ),
		);

		return $base_email_template_variables_with_description;
	}

	/**
	 * Send a test email.
	 *
	 * @since 3.5
	 *
	 * @param string $test_email_recipient The email address to send the test email to.
	 * @return bool Whether the email was sent successfully.
	 */
	final public static function send_test( $test_email_recipient ) {
		// Get the test parameters needed to construct the test email (ex test user, order, level, etc).
		
		$class_name = get_called_class();
		//if we haven't implemented the get_test_email_constructor_args method, log something and  return false
		if ( ! method_exists( $class_name, 'get_test_email_constructor_args' ) ) {
			error_log( 'The ' . $class_name . ' class did not implement the get_test_email_constructor_args method yet.' ); 
			return false;
		}
		$constructor_args = $class_name::get_test_email_constructor_args();

		// Construct the test email using the test parameters.
		$email = new static( ...$constructor_args );

		// Set the recipient email to the test email address and the body to include a test message.
		$pmpro_email_recipient_function = function() use ( $test_email_recipient ) {
			return $test_email_recipient;
		};
		$pmpro_email_templates_test_body = function( $body, $email ) {
			return pmpro_email_templates_test_body( $body );
		};

		// Add the test filters.
		add_filter( 'pmpro_email_recipient', $pmpro_email_recipient_function );
		add_filter('pmpro_email_body', $pmpro_email_templates_test_body, 10, 2);

		// Send the email.
		$result = $email->send();

		// Remove the filters.
		remove_filter('pmpro_email_recipient', $pmpro_email_recipient_function );
		remove_filter('pmpro_email_body', $pmpro_email_templates_test_body, 10, 2 );

		// Return the result.
		return $result;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 3.4
	 *
	 * @return string The email template slug.
	 */
	abstract public static function get_template_slug();

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	abstract public static function get_template_name();

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	abstract public static function get_template_description();

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	abstract public static function get_default_subject();

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	abstract public static function get_default_body();

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	abstract public static function get_email_template_variables_with_description();

	/**
	 * Get the email address to send the email to.
	 *
	 * @since 3.4
	 *
	 * @return string The email address to send the email to.
	 */
	abstract public function get_recipient_email();

	/**
	 * Get the name of the email recipient.
	 *
	 * @since 3.4
	 *
	 * @return string The name of the email recipient.
	 */
	abstract public function get_recipient_name();

	/**
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	abstract public function get_email_template_variables();
}
