<?php

class PMPro_Email_Template_Cancel extends PMPro_Email_Template {
	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The IDs of the membership levels that were cancelled.
	 *
	 * @var int
	 */
	protected $cancelled_level_ids;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int|array|null $cancelled_level_ids The ID or array of IDs of the membership levels that were cancelled. If null, "all" levels were cancelled.
	 */
	public function __construct( WP_User $user, $cancelled_level_ids ) {
		$this->user = $user;
		$this->cancelled_level_ids = $cancelled_level_ids;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'cancel';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Cancel', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return __( 'The site administrator can manually cancel a user\'s membership through the WordPress admin or the member can cancel their own membership through your site. This email is sent to the member as confirmation of a cancelled membership.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return __( 'Your membership at !!sitename!! has been CANCELLED', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>Your membership at !!sitename!! has been cancelled.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>
<p>Membership Level: !!membership_level_name!!</p>

<p>If you did not request this cancellation and would like more information please contact us at !!siteemail!!</p>', 'paid-memberships-pro' );
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
			'!!user_email!!' => __( 'The email address of the user who cancelled their membership.', 'paid-memberships-pro' ),
			'!!display_name!!' => __( 'The display name of the user who cancelled their membership.', 'paid-memberships-pro' ),
			'!!user_login!!' => __( 'The login name of the user who cancelled their membership.', 'paid-memberships-pro' ),
			'!!membership_id!!' => __( 'The ID of the membership level that was cancelled.', 'paid-memberships-pro' ),
			'!!membership_level_name!!' => __( 'The name of the membership level that was cancelled.', 'paid-memberships-pro' ),
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
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		global $wpdb;

		$email_template_variables = array(
			'user_email' => $this->user->user_email,
			'display_name' => $this->user->display_name,
			'user_login' => $this->user->user_login,
		);

		if ( empty( $this->cancelled_level_ids ) ) {
			$email_template_variables['membership_id'] = '';
			$email_template_variables['membership_level_name'] = __( 'All Levels', 'paid-memberships-pro' );
		} elseif ( is_array( $this->cancelled_level_ids ) ) {
			$email_template_variables['membership_id'] = $this->cancelled_level_ids[0]; // Pass just the first as the level id.
			$email_template_variables['membership_level_name'] = pmpro_implodeToEnglish( $wpdb->get_col( "SELECT name FROM $wpdb->pmpro_membership_levels WHERE id IN('" . implode( "','", $this->cancelled_level_ids ) . "')" ) );
		} else {
			$email_template_variables['membership_id'] = $this->cancelled_level_ids;
			$email_template_variables['membership_level_name'] = pmpro_implodeToEnglish( $wpdb->get_col( "SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '" . $this->cancelled_level_ids . "'" ) );
		}

		return $email_template_variables;
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
function pmpro_email_templates_cancel( $email_templates ) {
	$email_templates['cancel'] = 'PMPro_Email_Template_Cancel';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_cancel' );
