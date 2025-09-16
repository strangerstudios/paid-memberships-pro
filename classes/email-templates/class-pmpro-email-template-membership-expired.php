<?php
class PMPro_Email_Template_Membership_Expired extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The membership level ID that expired.
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
	 * @param int $membership_level_id The membership level id of the membership level that expired.
	 */
	public function __construct( WP_User $user, int $membership_level_id ) {
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
		return 'membership_expired';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Membership Expired', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 3.4
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent to the member when their membership expires.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Your membership at !!sitename!! has ended', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 3.4
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Your membership at !!sitename!! has ended.</p>

<p>Thank you for your support.</p>

<p>View our current membership offerings here: !!levels_url!!</p>

<p>Log in to manage your account here: !!login_url!!</p>', 'paid-memberships-pro' ) );
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
			'!!renew_url!!' => esc_html__( 'The URL of the Membership Checkout page for the expired level.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since 3.4
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		global $wpdb;
		$membership_level_id = $this->membership_level_id;
		// If we don't have a level ID, query the user's most recently expired level from the database.
		if ( empty( $membership_level_id ) ) {
			$membership_level_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT membership_id FROM $wpdb->pmpro_memberships_users
					WHERE user_id = %d
					AND status = 'expired'
					ORDER BY enddate DESC
					LIMIT 1",
					$this->user->ID
				)
			);

			// If we still don't have a level ID, set it to no level ID.
			if ( empty( $membership_level_id ) ) {
				$membership_level_id = 0;
			}
		}

		// Get the membership level object.
		$membership_level = pmpro_getLevel( $membership_level_id );

		return array(
			"subject" => $this->get_default_subject(),
			"name" => $this->user->display_name,
			"display_name" => $this->user->display_name,
			"user_login" => $this->user->user_login,
			"user_email" => $this->user->user_email,
			"membership_id" => ( ! empty( $membership_level ) && ! empty( $membership_level->id ) ) ? $membership_level->id : 0,
			"membership_level_name" => ( ! empty( $membership_level ) && ! empty( $membership_level->name ) ) ? $membership_level->name : '[' . esc_html( 'deleted', 'paid-memberships-pro' ) . ']',
			"renew_url" => ( ! empty( $membership_level ) && ! empty( $membership_level->id ) ) ? pmpro_url( 'checkout', '?pmpro_level=' . $membership_level->id ) : pmpro_url( 'levels' ),
		);
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

		$all_levels = pmpro_getAllLevels( true );
		$test_level = current( $all_levels );

		return array( $current_user, $test_level->id );
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
function pmpro_email_templates_membership_expired( $email_templates ) {
	$email_templates['membership_expired'] = 'PMPro_Email_Template_Membership_Expired';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_membership_expired' );