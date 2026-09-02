<?php
/**
 * Logic for CloudFlare Turnstile.
 */

/**
 * Register Cloudflare Turnstile as an available captcha service.
 *
 * @since TBD
 *
 * @param array $services Captcha services as slug => label.
 * @return array $services Captcha services as slug => label.
 */
function pmpro_cloudflare_turnstile_register_captcha_service( $services ) {
	$services['turnstile'] = __( 'Cloudflare Turnstile', 'paid-memberships-pro' );
	return $services;
}
add_filter( 'pmpro_captcha_services', 'pmpro_cloudflare_turnstile_register_captcha_service' );

/**
 * Show CloudFlare Turnstile on the checkout page.
 */
function pmpro_cloudflare_turnstile_get_html() {
	static $script_shown = false;

	// If CloudFlare Turnstile is not enabled, bail.
	if ( 'turnstile' !== pmpro_captcha() ) {
		return;
	}

	/**
	 * Filter the CloudFlare Turnstile theme.
	 *
	 * @param string $style - The CloudFlare Turnstile theme style. Either 'light' or 'dark'.
	 */
	$cf_theme = apply_filters( 'pmpro_cloudflare_turnstile_theme', 'light' );
	if ( $cf_theme !== 'light' ) {
		$cf_theme = 'dark';
	}

	// Only load the Turnstile API script once per page. A widget is output on
	// every call since Turnstile renders every .cf-turnstile element on the page.
	if ( ! $script_shown ) {
		?>
		<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
		<?php
		$script_shown = true;
	}
	?>
	<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( get_option( 'pmpro_cloudflare_turnstile_site_key' ) ); ?>" data-theme="<?php echo esc_attr( $cf_theme ); ?>"></div>
	<?php

}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_cloudflare_turnstile_get_html' );
add_action( 'pmpro_billing_before_submit_button', 'pmpro_cloudflare_turnstile_get_html' );

/**
 * Registration check to make sure the Turnstile passes.
 *
 * @return void
 */
function pmpro_cloudflare_turnstile_validation( $okay ) {
	// If checkout is already halted, bail.
	if ( ! $okay ) {
		return $okay;
	}

	// If CloudFlare Turnstile is not enabled, bail.
	if ( 'turnstile' !== pmpro_captcha() ) {
		return $okay;
	}

	// Don't show it more than once on a screen. This is for "PayPal Express".
	if ( pmpro_get_session_var( 'pmpro_cloudflare_turnstile_validated' ) ) {
		return $okay;
	}

	// Verify the turnstile token. If the check failed, show an error.
	$valid = pmpro_cloudflare_turnstile_verify_token( pmpro_getParam( 'cf-turnstile-response' ) );
	if ( true !== $valid ) {
		pmpro_setMessage( $valid, 'pmpro_error' );
		return false;
	}

	// Only remember successful validations.
	pmpro_set_session_var( 'pmpro_cloudflare_turnstile_validated', true );
	return $okay;
}

/**
 * Verify a CloudFlare Turnstile response token.
 *
 * @since TBD
 *
 * @param string $token The cf-turnstile-response token to verify.
 * @return true|string True if the token is valid, or an error message to display if not.
 */
function pmpro_cloudflare_turnstile_verify_token( $token ) {
	// An empty token means the user did not complete the challenge.
	if ( empty( $token ) ) {
		return __( 'Please complete the security check.', 'paid-memberships-pro' );
	}

	// Verify the turnstile check.
	$headers = array(
		'body' => array(
			'secret'   => get_option( 'pmpro_cloudflare_turnstile_secret_key', '' ),
			'response' => $token,
		),
	);
	$verify   = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', $headers );
	$verify   = wp_remote_retrieve_body( $verify );
	$response = json_decode( $verify );

	// If the check failed, return an error message.
	if ( empty( $response->success ) ) {
		$error_messages    = pmpro_cloudflare_turnstile_get_error_message();
		$error_code        = isset( $response->{'error-codes'}[0] ) ? $response->{'error-codes'}[0] : '';
		return isset( $error_messages[ $error_code ] ) ? $error_messages[ $error_code ] : esc_html__( 'An error occurred while validating the security check.', 'paid-memberships-pro' );
	}

	return true;
}
add_action( 'pmpro_checkout_checks', 'pmpro_cloudflare_turnstile_validation' );
add_action( 'pmpro_billing_update_checks', 'pmpro_cloudflare_turnstile_validation' );

/**
 * CloudFlare Turnstile Security Settings
 *
 * @return void
 */
function pmpro_cloudflare_turnstile_settings() {
	// Get the options
	$cloudflare_site_key = get_option( 'pmpro_cloudflare_turnstile_site_key', '' );
	$cloudflare_secret_key = get_option( 'pmpro_cloudflare_turnstile_secret_key', '' );

	$cloudflare_turnstile_depends = array(
		array(
			'id'    => 'captcha',
			'value' => 'turnstile',
		),
	);

	// Output settings
	pmpro_build_settings_field( array(
		'name'      => 'cloudflare_turnstile_site_key',
		'label'     => __( 'Turnstile Site Key', 'paid-memberships-pro' ),
		'type'      => 'text',
		'class'     => 'regular-text code',
		'value'     => $cloudflare_site_key,
		'row_class' => 'pmpro_cloudflare_turnstile_settings',
		'depends'   => $cloudflare_turnstile_depends,
		'description' => sprintf(
			/* translators: %s: Link to CloudFlare Turnstile. */
			__( 'A free CloudFlare Turnstile key is required. <a href="%s" target="_blank" rel="nofollow noopener">Click here to signup for CloudFlare Turnstile</a>.', 'paid-memberships-pro' ),
			'https://www.cloudflare.com/products/turnstile/'
		),
	) );
	pmpro_build_settings_field( array(
		'name'      => 'cloudflare_turnstile_secret_key',
		'label'     => __( 'Turnstile Secret Key', 'paid-memberships-pro' ),
		'type'      => 'text',
		'class'     => 'regular-text code',
		'value'     => $cloudflare_secret_key,
		'row_class' => 'pmpro_cloudflare_turnstile_settings',
		'depends'   => $cloudflare_turnstile_depends,
	) );
}
add_action( 'pmpro_security_spam_fields', 'pmpro_cloudflare_turnstile_settings' );

/**
 * Save CloudFlare Turnstile settings on the PMPro settings page.
 *
 * @since 3.2
 */
function pmpro_cloudflare_turnstile_settings_save() {
	// Keep the legacy on/off option in sync with the captcha setting for backwards compatibility.
	update_option( 'pmpro_cloudflare_turnstile', 'turnstile' === pmpro_captcha() ? 1 : 0, false );
	pmpro_setOption( 'cloudflare_turnstile_site_key', sanitize_text_field( $_POST['cloudflare_turnstile_site_key'] ) );
	pmpro_setOption( 'cloudflare_turnstile_secret_key', sanitize_text_field( $_POST['cloudflare_turnstile_secret_key'] ) );
}
add_action( 'pmpro_save_security_settings', 'pmpro_cloudflare_turnstile_settings_save' );

/**
 * Get human readable error messages for CloudFlare response.
 *
 * @since 3.2
 */
function pmpro_cloudflare_turnstile_get_error_message() {
	$error_messages = array(
		'missing-input-secret'   => esc_html__( 'The secret parameter was not passed.', 'paid-memberships-pro' ),
		'invalid-input-secret'   => esc_html__( 'The secret parameter was invalid or did not exist.', 'paid-memberships-pro' ),
		'missing-input-response' => esc_html__( 'The response parameter (token) was not passed.', 'paid-memberships-pro' ),
		'invalid-input-response' => esc_html__( 'The response parameter (token) is invalid or has expired. Most of the time, this means a fake token has been used. If the error persists, contact customer support.', 'paid-memberships-pro' ),
		'bad-request'            => esc_html__( 'The request was rejected because it was malformed.', 'paid-memberships-pro' ),
		'timeout-or-duplicate'   => esc_html__( 'The response parameter (token) has already been validated before. This means that the token was issued five minutes ago and is no longer valid, or it was already redeemed.', 'paid-memberships-pro' ),
		'internal-error'         => esc_html__( 'An internal error happened while validating the response. The request can be retried.', 'paid-memberships-pro' ),
	);

	return $error_messages;
}

/**
 * Clear the CloudFlare Turnstile session variable after checkout.
 * @since 3.3.3
 */
function pmpro_after_checkout_reset_cloudflare_turnstile() {
    pmpro_unset_session_var( 'pmpro_cloudflare_turnstile_validated' );
}
add_action( 'pmpro_after_checkout', 'pmpro_after_checkout_reset_cloudflare_turnstile' );
add_action( 'pmpro_after_update_billing', 'pmpro_after_checkout_reset_cloudflare_turnstile' );

/**
 * Check whether login and password reset forms should be challenged with Turnstile.
 *
 * @since TBD
 *
 * @return bool True if forms should be challenged.
 */
function pmpro_cloudflare_turnstile_should_challenge_login() {
	// Only challenge if Turnstile is the active captcha service.
	if ( 'turnstile' !== pmpro_captcha() ) {
		return false;
	}

	// Don't challenge without keys. We couldn't render or verify the captcha,
	// and requiring a check that can't be completed would lock users out.
	if ( ! get_option( 'pmpro_cloudflare_turnstile_site_key' ) || ! get_option( 'pmpro_cloudflare_turnstile_secret_key' ) ) {
		return false;
	}

	// Only challenge IPs with recent suspicious activity, e.g. failed logins.
	return pmpro_captcha_has_recent_spam_activity();
}

/**
 * Show Turnstile on login and lost password forms once a challenge is
 * active for the current IP.
 *
 * A widget is output for every form on the page since every form's submission
 * is validated server-side; pmpro_cloudflare_turnstile_get_html() only loads
 * the API script once.
 *
 * @since TBD
 */
function pmpro_cloudflare_turnstile_login_forms_html() {
	if ( ! pmpro_cloudflare_turnstile_should_challenge_login() ) {
		return;
	}

	pmpro_cloudflare_turnstile_get_html();
}
add_action( 'login_form', 'pmpro_cloudflare_turnstile_login_forms_html' );
add_action( 'lostpassword_form', 'pmpro_cloudflare_turnstile_login_forms_html' );
add_action( 'pmpro_lost_password_before_submit_button', 'pmpro_cloudflare_turnstile_login_forms_html' );

/**
 * Show Turnstile inside forms built with wp_login_form() — including PMPro's
 * own login forms — once a challenge is active. These are exactly the forms
 * that post to wp-login.php and are validated by pmpro_authenticate_login_checks,
 * so every form that is validated also displays the challenge.
 *
 * @since TBD
 *
 * @param string $content Content to display. Default empty.
 * @param array  $args    Array of login form arguments.
 * @return string $content Content to display.
 */
function pmpro_cloudflare_turnstile_login_form_middle( $content, $args ) {
	ob_start();
	pmpro_cloudflare_turnstile_login_forms_html();
	// Late priority and a (string) cast so that earlier callbacks that echo
	// and return null (instead of returning their content) can't wipe the widget.
	return (string) $content . ob_get_clean();
}
add_filter( 'login_form_middle', 'pmpro_cloudflare_turnstile_login_form_middle', 100, 2 );

/**
 * Require a valid Turnstile token on login attempts once the current IP has failed a login.
 *
 * @since TBD
 *
 * @param WP_User|WP_Error|null $user     WP_User if the login is valid so far, otherwise WP_Error or null.
 * @param string                $username The username being used to log in.
 * @return WP_User|WP_Error|null $user
 */
function pmpro_cloudflare_turnstile_login_check( $user, $username ) {
	if ( ! pmpro_cloudflare_turnstile_should_challenge_login() ) {
		return $user;
	}

	if ( true !== pmpro_cloudflare_turnstile_verify_token( pmpro_getParam( 'cf-turnstile-response' ) ) ) {
		return new WP_Error( 'pmpro_captcha_failed', pmpro_captcha_failed_error_message() );
	}

	return $user;
}
add_filter( 'pmpro_authenticate_login_checks', 'pmpro_cloudflare_turnstile_login_check', 10, 2 );

/**
 * Require a valid Turnstile token on lost password submissions once the current IP has failed a login.
 *
 * @since TBD
 *
 * @param WP_Error      $errors    Error object to add a captcha error to.
 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
 */
function pmpro_cloudflare_turnstile_lostpassword_check( $errors, $user_data ) {
	// Only check submissions from the PMPro or wp-login.php lost password forms. This hook
	// also fires for other plugins that call retrieve_password() from their own forms,
	// which never displayed our captcha.
	if ( empty( $_REQUEST['pmpro_login_form_used'] ) && ! did_action( 'login_form_lostpassword' ) && ! did_action( 'login_form_retrievepassword' ) ) {
		return;
	}

	if ( ! pmpro_cloudflare_turnstile_should_challenge_login() ) {
		return;
	}

	if ( true !== pmpro_cloudflare_turnstile_verify_token( pmpro_getParam( 'cf-turnstile-response' ) ) ) {
		$errors->add( 'pmpro_captcha_failed', pmpro_captcha_failed_error_message() );
	}
}
add_action( 'lostpassword_post', 'pmpro_cloudflare_turnstile_lostpassword_check', 10, 2 );
