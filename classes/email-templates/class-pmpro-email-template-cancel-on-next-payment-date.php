<?php

class PMPro_Email_Template_Cancel_On_Next_Payment_Date extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 *  The level ID of the level that was cancelled.
	 *
	 * @var int
	 */
	protected $membership_level_id;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int $membership_level_id The ID of the level that was cancelled.
	 */
	public function __construct( WP_User $user,  int $membership_level_id ) {
		$this->user = $user;
		$this->membership_level_id = $membership_level_id;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 3.4
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'cancel_on_next_payment_date';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Cancelled Auto-Renewals', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'When a user cancels a membership with a recurring subscription, they will still have access until when their next payment would have been taken. This email is sent to the member to notify them of this change.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Your payment subscription at !!sitename!! has been CANCELLED', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Your payment subscription at !!sitename!! has been cancelled.</p>

<p>Account: !!display_name!! (!!user_email!!)</p>

<p>Membership Level: !!membership_level_name!!</p>

<p>Your access will expire on !!enddate!!.</p>', 'paid-memberships-pro' ) );
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
			'!!startdate!!' => esc_html__( 'The start date of the membership level.', 'paid-memberships-pro' ),
			'!!enddate!!' => esc_html__( 'The end date of the membership level.', 'paid-memberships-pro' ),
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
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$user = $this->user;
		$level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $this->membership_level_id );

		$email_template_variables = array(
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => $level->id,
			'membership_level_name' => $level->name,
			'startdate' => date_i18n( get_option( 'date_format' ), $level->startdate ),
			'enddate' => date_i18n( get_option( 'date_format' ), $level->enddate ),
		);
		return $email_template_variables;
	}

	/**
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since 3.5
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user, $pmpro_conpd_email_test_level;

		// Set up a mock level for the test email.
		$levels = pmpro_getAllLevels( true );
		$pmpro_conpd_email_test_level = current( $levels );
		add_filter( 'pmpro_get_membership_levels_for_user', function() {
			global $pmpro_conpd_email_test_level;
			$pmpro_conpd_email_test_level->startdate = strtotime( current_time( 'timestamp' ) );
 			$pmpro_conpd_email_test_level->enddate = strtotime( '+1 month' );
			return array( $pmpro_conpd_email_test_level->id => $pmpro_conpd_email_test_level );
		} );

		return array( $current_user, $pmpro_conpd_email_test_level->id );
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
function pmpro_email_templates_cancel_on_next_payment_date( $email_templates ) {
	$email_templates['cancel_on_next_payment_date'] = 'PMPro_Email_Template_Cancel_On_Next_Payment_Date';

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_cancel_on_next_payment_date' );