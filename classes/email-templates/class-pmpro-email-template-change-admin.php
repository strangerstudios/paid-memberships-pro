<?php

class PMPro_Email_Template_Change_Admin extends PMPro_Email_Template {

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
	 * @param int|array|null $cancelled_level_ids The ID or array of IDs of the membership levels that were cancelled. If null, "all" levels were cancelled.
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
		return 'change_admin';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Admin Change (admin)', 'paid-memberships-pro' );
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
		return __( "Membership for !!display_name!! at !!sitename!! has been changed", 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>An administrator at !!sitename!! has changed a membership level for !!display_name!!.</p>
		<p>!!membership_change!!</p>
		<p>Log in to your WordPress admin here: !!login_url!!</p>', 'paid-memberships-pro' );
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		// If the user no longer has a membership level, set the membership_change text to "Membership has been cancelled."
		if ( ! pmpro_hasMembershipLevel( null,  $this->user->ID ) ) {
			$membership_changed = __( 'The user\'s membership has been cancelled.', 'paid-memberships-pro' );
		} else {
			$membership_changed = __( 'You can view the user\'s current memberships from their Edit Member page.', 'paid-memberships-pro' );
		}

		$email_template_variables = array(
			'membership_changed' => $membership_changed,
			'display_name' => $this->user->display_name,

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
			'!!display_name!!' => __( 'The email address of the user that admin changed their membership.', 'paid-memberships-pro' ),
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
		return get_bloginfo( 'admin_email' );
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		//get user by email
		$user = get_user_by( 'email', $this->get_recipient_email() );
		return $user->display_name;
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
	function pmpro_email_templates_change_admin( $email_templates ) {
		$email_templates['admin_change_admin'] = 'PMPro_Email_Template_Change_Admin';

		return $email_templates;
	}

add_filter( 'pmpro_email_templates', 'pmpro_email_templates_change_admin' );
