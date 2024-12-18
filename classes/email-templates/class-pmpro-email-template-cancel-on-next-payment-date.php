<?php

class PMPro_Email_Template_Cancel_On_Next_Payment_Date extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 *  The level id object of the level that was cancelled.
	 *
	 * @var int
	 */
	protected $level_id;



	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int $level_id The id of the level that was cancelled.
	 */
	public function __construct( WP_User $user,  int $level_id ) {
		$this->user = $user;
		$this->level_id = $level_id;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'cancel_on_next_payment_date';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return __( 'Cancelled Auto-Renewals', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return __( 'When a user cancels a membership with a recurring subscription, they will still have access until when their next payment would have been taken. This email is sent to the member to notify them of this change.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return __( 'Your payment subscription at !!sitename!! has been CANCELLED', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return __( '<p>Your payment subscription at !!sitename!! has been cancelled.</p>
		
		<p>Account: !!display_name!! (!!user_email!!)</p>
		<p>Membership Level: !!membership_level_name!!</p>
		<p>Your access will expire on !!enddate!!.</p>', 'paid-memberships-pro' );
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
			'user_login' => __( 'The user\'s username.', 'paid-memberships-pro' ),
			'user_email' => __( 'The user\'s email address.', 'paid-memberships-pro' ),
			'display_name' => __( 'The user\'s display name.', 'paid-memberships-pro' ),
			'membership_id' => __( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'membership_level_name' => __( 'The name of the membership level.', 'paid-memberships-pro' ),
			'startdate' => __( 'The start date of the membership level.', 'paid-memberships-pro' ),
			'enddate' => __( 'The end date of the membership level.', 'paid-memberships-pro' ),
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
		$user = $this->user;
		$level = pmpro_getLevel( $this->level_id );

		$email_template_variables = array(
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'display_name' => $user->display_name,
			'membership_id' => $level->id,
			'membership_level_name' => $level->name,
			'startdate' => date_i18n( get_option( 'date_format' ), $level->startdate ),
			'enddate' => date_i18n( get_option( 'date_format' ), $level->enddate ),
		);
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
function pmpro_email_templates_cancel_on_next_payment_date( $email_templates ) {
	$email_templates['cancel_on_next_payment_date'] = 'PMPro_Email_Template_Cancel_On_Next_Payment_Date';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_cancel_on_next_payment_date' );