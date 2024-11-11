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
	 * @since TBD
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 */
	public function __construct( WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'admin_change';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Admin Change', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return __( 'The site administrator can manually update a user\'s membership through the WordPress admin. This email notifies the member of the level update.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return __( "Membership for !!header_name!! at !!site_name!! has been changed", "paid-memberships-pro" );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>An administrator at !!sitename!! has changed your membership level.</p>

<p>!!membership_change!!</p>

<p>If you did not request this membership change and would like more information please contact us at !!siteemail!!</p>

<p>Log in to your membership account here: !!login_url!!</p>', 'paid-memberships-pro' );
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
		// If the user no longer has a membership level, set the membership_change text to "Membership has been cancelled."
		if ( ! pmpro_hasMembershipLevel( null,  $this->user->ID ) ) {
			$membership_changed = __( 'Your membership has been cancelled.', 'paid-memberships-pro' );
		} else {
			$membership_changed = __( 'You can view your current memberships by logging in and visiting your membership account page.', 'paid-memberships-pro' );
		}

		$email_template_variables = array(
			'membership_changed' => $membership_changed,
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
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'!!membership_change!!' => __( 'Membership Level Change', 'paid-memberships-pro' ),
			'!!subject!!' => __( 'The default subject for the email. This will be removed in a future version.', 'paid-memberships-pro' ),
			'!!name!!' => __( 'The display name of the user whose membership was changed.', 'paid-memberships-pro' ),
			'!!display_name!!' => __( 'The display name of the user whose membership was changed.', 'paid-memberships-pro' ),
			'!!user_login!!' => __( 'The login name of the user whose membership was changed.', 'paid-memberships-pro' ),
			'!!user_email!!' => __( 'The email address of the user whose membership was changed.', 'paid-memberships-pro' ),
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
}

/**
 * Register the email template.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_change( $email_templates ) {
	$email_templates['admin_change'] = 'PMPro_Email_Template_Admin_Change';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_change' );
