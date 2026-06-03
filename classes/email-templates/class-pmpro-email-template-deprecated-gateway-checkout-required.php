<?php

class PMPro_Email_Template_Deprecated_Gateway_Checkout_Required extends PMPro_Email_Template {
	/**
	 * The user object of the user to send the email to.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The level ID.
	 *
	 * @var int
	 */
	protected $membership_level_id;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user The user object of the user to send the email to.
	 * @param int     $membership_level_id The ID of the membership level.
	 */
	public function __construct( WP_User $user, int $membership_level_id ) {
		$this->user = $user;
		$this->membership_level_id = $membership_level_id;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'deprecated_gateway_checkout_required';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Deprecated Gateway Checkout Required', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent when an administrator cancels a deprecated gateway subscription and sets the member\'s expiration date to the old next payment date. It tells the member they will need to check out again to continue their membership.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Action required: renew your membership at {{ sitename }}', 'paid-memberships-pro' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Your saved payment method for {{ membership_level_name }} at {{ sitename }} is no longer valid because the payment processor that stored it is no longer supported.</p>

<p>Your membership remains active until {{ expiration_date }}.</p>

<p>To continue your membership, you will need to check out again. Checking out before {{ expiration_date }} may replace your remaining access time.</p>

<p><a href="{{ checkout_url }}">Check Out Again</a></p>', 'paid-memberships-pro' ) );
	}

	/**
	 * Get the email template variables for the email paired with a description.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email.
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'{{ display_name }}' => esc_html__( 'The display name of the user.', 'paid-memberships-pro' ),
			'{{ user_login }}' => esc_html__( 'The username of the user.', 'paid-memberships-pro' ),
			'{{ user_email }}' => esc_html__( 'The email address of the user.', 'paid-memberships-pro' ),
			'{{ membership_id }}' => esc_html__( 'The ID of the membership level.', 'paid-memberships-pro' ),
			'{{ membership_level_name }}' => esc_html__( 'The name of the membership level.', 'paid-memberships-pro' ),
			'{{ expiration_date }}' => esc_html__( 'The date when the member\'s current access expires.', 'paid-memberships-pro' ),
			'{{ checkout_url }}' => esc_html__( 'The checkout URL for the member\'s membership level.', 'paid-memberships-pro' ),
		);
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since TBD
	 *
	 * @return string The email address.
	 */
	public function get_recipient_email() {
		return $this->user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name.
	 */
	public function get_recipient_name() {
		return $this->user->display_name;
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables.
	 */
	public function get_email_template_variables() {
		$user = $this->user;
		$level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $this->membership_level_id );
		if ( empty( $level ) ) {
			$level = pmpro_getLevel( $this->membership_level_id );
		}

		$expiration_date = '';
		if ( ! empty( $level->enddate ) ) {
			$expiration_timestamp = is_numeric( $level->enddate ) ? $level->enddate : strtotime( $level->enddate );
			if ( ! empty( $expiration_timestamp ) ) {
				$expiration_date = date_i18n( get_option( 'date_format' ), $expiration_timestamp );
			}
		}

		return array(
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => ! empty( $level->id ) ? $level->id : $this->membership_level_id,
			'membership_level_name' => ! empty( $level->name ) ? $level->name : '',
			'expiration_date' => $expiration_date,
			'checkout_url' => pmpro_url( 'checkout', 'pmpro_level=' . (int) $this->membership_level_id ),
		);
	}

	/**
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since TBD
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user, $pmpro_deprecated_gateway_checkout_required_email_test_level;

		$levels = pmpro_getAllLevels( true );
		$pmpro_deprecated_gateway_checkout_required_email_test_level = current( $levels );
		if ( empty( $pmpro_deprecated_gateway_checkout_required_email_test_level ) ) {
			$pmpro_deprecated_gateway_checkout_required_email_test_level = (object) array(
				'id'   => 1,
				'name' => __( 'Membership Level', 'paid-memberships-pro' ),
			);
		}

		add_filter(
			'pmpro_get_membership_levels_for_user',
			function() {
				global $pmpro_deprecated_gateway_checkout_required_email_test_level;
				$pmpro_deprecated_gateway_checkout_required_email_test_level->enddate = strtotime( '+1 month' );
				return array( $pmpro_deprecated_gateway_checkout_required_email_test_level->id => $pmpro_deprecated_gateway_checkout_required_email_test_level );
			}
		);

		return array( $current_user, (int) $pmpro_deprecated_gateway_checkout_required_email_test_level->id );
	}
}

/**
 * Register the email template.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates.
 * @return array The modified email templates array.
 */
function pmpro_deprecated_gateway_sunset_register_checkout_required_email_template( $email_templates ) {
	if ( function_exists( 'pmpro_has_undeprecated_gateways' ) && pmpro_has_undeprecated_gateways() ) {
		$email_templates['deprecated_gateway_checkout_required'] = 'PMPro_Email_Template_Deprecated_Gateway_Checkout_Required';
	}

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_deprecated_gateway_sunset_register_checkout_required_email_template' );
