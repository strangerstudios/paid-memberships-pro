<?php
class PMPro_Email_Template_Membership_Churned extends PMPro_Email_Template {

	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The membership level that expired.
	 *
	 * @var int
	 */
	protected $membership_level_id;

	/**
	 * Constructor.
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int $membership_id The membership level id of the membership level that expired.
	 */
	public function __construct( WP_User $user, int $membership_level_id ) {
		$this->user = $user;
		$this->membership_level_id = $membership_level_id;
	}

	/**
	 * Get the email template slug.
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'membership_churned';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Membership Churned', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		$days = intval(get_option("pmpro_churned_email_days", 30));
		/* translators: %d: number of days after membership expiration */
		return sprintf(esc_html__( 'This email is sent to former members %d days after their membership expires (churned members).', 'paid-memberships-pro' ), $days);
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( 'We miss you at !!sitename!! - Special offer inside', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		$days = intval(get_option("pmpro_churned_email_days", 30));
		
		// Convert days to friendly text with flexible ranges
		$time_period = '';
		if ($days <= 7) {
			$time_period = 'a week';
		} elseif ($days <= 14) {
			$time_period = 'two weeks';
		} elseif ($days <= 31) {
			$time_period = 'a month';
		} elseif ($days <= 62) {
			$time_period = 'two months';
		} elseif ($days <= 93) {
			$time_period = 'three months';
		} elseif ($days <= 186) {
			$time_period = 'six months';
		} elseif ($days <= 366) {
			$time_period = 'a year';
		} else {
			$time_period = 'some time';
		}
		
		return wp_kses_post( sprintf( __( '<p>Hi !!display_name!!,</p>

<p>We noticed it has been %s since your membership at !!sitename!! expired, and we would love to have you back.</p>

<p>We value your past membership and would like to offer you the opportunity to rejoin our community.</p>

<p>View our current membership offerings here: !!levels_url!!</p>

<p>Log in to manage your account here: !!login_url!!</p>

<p>Thank you for considering rejoining us!</p>', 'paid-memberships-pro' ), $time_period ) );
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return $this->user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		return $this->user->display_name;
	}


	/**
	 * Get the email template variables for the email paired with a description of the variable.
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
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		global $wpdb;
		// If we don't have a level ID, query the user's most recently expired level from the database.
		if ( empty( $this->membership_id ) ) {
			$membership_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT membership_id FROM $wpdb->pmpro_memberships_users
					WHERE user_id = %d
					AND status = 'expired'
					ORDER BY enddate DESC
					LIMIT 1",
					$this->user->ID
				)
			);

			// If we still don't have a level ID, bail.
			if ( empty( $membership_id ) ) {
				$membership_id = 0;
			}
		}

		// Get the membership level object.
		$membership_level = pmpro_getLevel( $membership_id );

		return array(
			"subject" => $this->get_default_subject(),
			"name" => $this->user->display_name,
			"display_name" => $this->user->display_name,
			"user_login" => $this->user->user_login,
			"user_email" => $this->user->user_email,
			"membership_id" => ( ! empty( $membership_level ) && ! empty( $membership_level->id ) ) ? $membership_level->id : 0,
			"membership_level_name" => ( ! empty( $membership_level ) && ! empty( $membership_level->name ) ) ? $membership_level->name : '[' . esc_html( 'deleted', 'paid-memberships-pro' ) . ']',
		);
	}
}

/**
 * Register the email template.
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_templates_membership_churned( $email_templates ) {
	$email_templates['membership_churned'] = 'PMPro_Email_Template_Membership_Churned';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_templates_membership_churned' );
