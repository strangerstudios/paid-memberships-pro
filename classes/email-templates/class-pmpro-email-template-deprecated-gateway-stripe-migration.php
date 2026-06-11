<?php

class PMPro_Email_Template_Deprecated_Gateway_Stripe_Migration extends PMPro_Email_Template {
	/**
	 * The Stripe subscription.
	 *
	 * @var PMPro_Subscription
	 */
	protected $subscription;

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
	 * @param PMPro_Subscription $subscription The Stripe subscription.
	 */
	public function __construct( PMPro_Subscription $subscription ) {
		$this->subscription = $subscription;
		$this->user = get_userdata( $subscription->get_user_id() );
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'deprecated_gateway_stripe_migration';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Deprecated Gateway Stripe Migration', 'paid-memberships-pro' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The help text.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent when an administrator migrates a deprecated gateway subscription to Stripe. It asks the member to add billing information to the new Stripe subscription before its next payment date. Members who do not add a payment method by that date will have their membership cancelled.', 'paid-memberships-pro' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Action required: update your billing information at {{ sitename }}', 'paid-memberships-pro' );
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

<p>Your membership remains active, and your next payment is scheduled for {{ next_payment_date }}.</p>

<p><strong>Please update your billing information before {{ next_payment_date }}. If no payment method is on file by that date, your membership will be cancelled.</strong></p>

<p><a href="{{ billing_update_url }}">Update Billing Information</a></p>', 'paid-memberships-pro' ) );
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
			'{{ next_payment_date }}' => esc_html__( 'The next payment date for the new Stripe subscription.', 'paid-memberships-pro' ),
			'{{ billing_update_url }}' => esc_html__( 'The URL where the member can update billing information for the new Stripe subscription.', 'paid-memberships-pro' ),
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
		// The user may have been deleted since the subscription was created.
		return empty( $this->user->user_email ) ? '' : $this->user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name.
	 */
	public function get_recipient_name() {
		// The user may have been deleted since the subscription was created.
		return empty( $this->user->display_name ) ? '' : $this->user->display_name;
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
		$level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $this->subscription->get_membership_level_id() );
		if ( empty( $level ) ) {
			$level = pmpro_getLevel( $this->subscription->get_membership_level_id() );
		}

		return array(
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'membership_id' => ! empty( $level->id ) ? $level->id : $this->subscription->get_membership_level_id(),
			'membership_level_name' => ! empty( $level->name ) ? $level->name : '',
			'next_payment_date' => $this->subscription->get_next_payment_date( get_option( 'date_format' ) ),
			'billing_update_url' => pmpro_url( 'billing', 'pmpro_subscription_id=' . (int) $this->subscription->get_id(), 'https' ),
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
		global $current_user;

		$levels = pmpro_getAllLevels( true );
		$level = current( $levels );
		if ( empty( $level ) ) {
			$level = (object) array(
				'id'   => 1,
				'name' => __( 'Membership Level', 'paid-memberships-pro' ),
			);
		}

		$subscription = new PMPro_Subscription(
			array(
				'id'                          => 0,
				'user_id'                     => $current_user->ID,
				'membership_level_id'         => $level->id,
				'gateway'                     => 'stripe',
				'gateway_environment'         => get_option( 'pmpro_gateway_environment', 'sandbox' ),
				'subscription_transaction_id' => 'TEST',
				'status'                      => 'active',
				'next_payment_date'           => gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) ),
			)
		);

		return array( $subscription );
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
function pmpro_deprecated_gateway_register_stripe_migration_email_template( $email_templates ) {
	// Keep this template registered after gateway cleanup while migrated
	// subscriptions are still waiting for a payment method, since it is
	// also sent in place of their recurring payment reminders.
	if (
		( function_exists( 'pmpro_has_undeprecated_gateways' ) && pmpro_has_undeprecated_gateways() ) ||
		( function_exists( 'pmpro_deprecated_gateway_get_needs_payment_method_count' ) && pmpro_deprecated_gateway_get_needs_payment_method_count() > 0 )
	) {
		$email_templates['deprecated_gateway_stripe_migration'] = 'PMPro_Email_Template_Deprecated_Gateway_Stripe_Migration';
	}

	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_deprecated_gateway_register_stripe_migration_email_template' );
