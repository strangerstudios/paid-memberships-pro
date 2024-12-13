<?php
abstract class PMPro_Email_Template {
	/**
	 * Get all email templates.
	 *
	 * @since TBD
	 *
	 * @return array All email templates (template slug => email template class name).
	 */
	final public static function get_all_email_templates() {
		/**
		 * Allow email templates to be registered.
		 *
		 * @since TBD
		 *
		 * @param array $email_templates All email templates (template slug => email template class name).
		 */
		return apply_filters( 'pmpro_email_templates', array() );
	}

	/**
	 * Get an email template by its slug.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	final public function send() {
		$pmpro_email           = new PMProEmail();
		$pmpro_email->email    = $this->get_recipient_email();
		$pmpro_email->subject  = $this->get_default_subject(); // This will be overridden if there is a subject saved in the database.
		$pmpro_email->body     = $this->get_default_body();
		$pmpro_email->data     = array_merge( $this->get_base_email_template_variables(), $this->get_email_template_variables() );
		$pmpro_email->template = apply_filters_deprecated( 'pmpro_email_template', array( $this->get_template_slug() ), 'TBD', 'pmpro_email_body' );
		return $pmpro_email->sendEmail();
	}

	/**
	 * Get the base email template variables that should be available for all emails.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	final protected function get_base_email_template_variables() {
		$base_email_template_variables = array(
			'sitename' => get_option( 'blogname' ),
			'siteemail' => get_option( 'pmpro_from_email' ),
			'site_url'  => home_url(),
			'levels_url' => pmpro_url( 'levels' ),
			'login_link' => pmpro_login_url(), 
			'login_url' => pmpro_login_url(),
			'header_name' => $this->get_recipient_name(),
		);

		return $base_email_template_variables;
	}

	/**
	 * Get the base email template variables that should be available for all emails paired with a description of the variable.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	final public static function get_base_email_template_variables_with_description() {
		$base_email_template_variables_with_description = array(
			'!!sitename!!' => __( 'The name of the site.', 'paid-memberships-pro' ),
			'!!siteemail!!' => __( 'The email address of the site.', 'paid-memberships-pro' ),
			'!!site_url!!'  => __( 'The URL of the site.', 'paid-memberships-pro' ),
			'!!levels_url!!' => __( 'The URL of the page where users can view available membership levels.', 'paid-memberships-pro' ),
			'!!login_link!!' => __( 'The URL of the login page.', 'paid-memberships-pro' ),
			'!!login_url!!' => __( 'The URL of the login page.', 'paid-memberships-pro' ),
			'!!header_name!!' => __( 'The name of the email recipient.', 'paid-memberships-pro' ),
		);

		return $base_email_template_variables_with_description;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	abstract public static function get_template_slug();

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	abstract public static function get_template_name();

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	abstract public static function get_template_description();

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	abstract public static function get_default_subject();

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	abstract public static function get_default_body();

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	abstract public static function get_email_template_variables_with_description();

	/**
	 * Get the email address to send the email to.
	 *
	 * @since TBD
	 *
	 * @return string The email address to send the email to.
	 */
	abstract public function get_recipient_email();

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	abstract public function get_recipient_name();

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	abstract public function get_email_template_variables();
}