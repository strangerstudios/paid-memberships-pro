<?php
/**
 * Shared code for captcha services.
 *
 * Each captcha service (reCAPTCHA, Cloudflare Turnstile) implements its own
 * display and validation logic in its own includes file. This file only holds
 * the code that is common to all captcha services.
 */

/**
 * Get the registered captcha services.
 *
 * @since TBD
 *
 * @return array Captcha services as slug => label.
 */
function pmpro_get_captcha_services() {
	/**
	 * Filter the available captcha services.
	 *
	 * Captcha integrations should register themselves here as slug => label.
	 * A registered service should also hook the login, password reset, and
	 * checkout display and validation hooks and gate its logic on
	 * pmpro_captcha() returning its slug.
	 *
	 * @since TBD
	 *
	 * @param array $services Captcha services as slug => label.
	 */
	return apply_filters( 'pmpro_captcha_services', array() );
}

/**
 * Get which captcha service is enabled, if any.
 *
 * @since TBD
 *
 * @return string The slug of the enabled captcha service, or an empty string if no captcha is enabled.
 */
function pmpro_captcha() {
	$captcha = get_option( 'pmpro_captcha', false );

	// Backwards compatibility with the separate reCAPTCHA and Turnstile settings
	// used before the single captcha setting existed. Note: a saved value of ''
	// means "No" was chosen and should not fall back to the old settings.
	// These two legacy options are intentionally hardcoded here rather than run
	// through the captcha services registry: this shim is about the past and
	// will never need to cover additional services.
	if ( false === $captcha ) {
		if ( get_option( 'pmpro_recaptcha' ) ) {
			// If both were enabled, reCAPTCHA takes priority.
			$captcha = 'recaptcha';
		} elseif ( get_option( 'pmpro_cloudflare_turnstile' ) ) {
			$captcha = 'turnstile';
		} else {
			$captcha = '';
		}
	}

	// Only return registered captcha services. If the enabled service is no
	// longer registered (e.g. its plugin was deactivated), the site safely
	// reverts to having no captcha.
	if ( ! empty( $captcha ) && ! array_key_exists( $captcha, pmpro_get_captcha_services() ) ) {
		$captcha = '';
	}

	return $captcha;
}

/**
 * Check whether the current IP has recent failed login activity and should be
 * shown a captcha challenge on login and password reset forms.
 *
 * Uses the spam activity tracking in includes/spam.php.
 *
 * @since TBD
 *
 * @return bool True if the current IP has recent failed login activity.
 */
function pmpro_captcha_has_recent_failed_login() {
	$activity = pmpro_get_spam_activity();
	return ! empty( $activity );
}

/**
 * Get the error message to show when a captcha check fails on a login or password reset form.
 *
 * @since TBD
 *
 * @return string The error message. Escaped, may contain <strong> tags.
 */
function pmpro_captcha_failed_error_message() {
	return wp_kses( __( '<strong>Error:</strong> Captcha verification failed. Please try again.', 'paid-memberships-pro' ), array( 'strong' => array() ) );
}

/**
 * Check whether the current request includes a pmpro_captcha_failed error code
 * passed back to the login page in the URL.
 *
 * @since TBD
 *
 * @return bool True if the request includes a captcha failed error code.
 */
function pmpro_is_captcha_failed_request() {
	$error_params = array( 'action', 'errors', 'error' );
	foreach ( $error_params as $param ) {
		if ( empty( $_REQUEST[ $param ] ) ) {
			continue;
		}

		// The errors param may contain a comma-separated list of error codes.
		$codes = explode( ',', sanitize_text_field( $_REQUEST[ $param ] ) );
		if ( in_array( 'pmpro_captcha_failed', $codes, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Show a message on the frontend login page when a captcha check failed.
 *
 * @since TBD
 *
 * @param string $message The message to show.
 * @param string $msgt    The message type.
 * @return string $message The message to show.
 */
function pmpro_captcha_failed_login_message( $message, $msgt ) {
	if ( pmpro_is_captcha_failed_request() ) {
		$message = pmpro_captcha_failed_error_message();
	}

	return $message;
}
add_filter( 'pmpro_login_forms_handler_message', 'pmpro_captcha_failed_login_message', 10, 2 );

/**
 * Set the message type for the captcha failed message on the frontend login page.
 *
 * @since TBD
 *
 * @param string $msgt The message type.
 * @return string $msgt The message type.
 */
function pmpro_captcha_failed_login_msgt( $msgt ) {
	if ( pmpro_is_captcha_failed_request() ) {
		$msgt = 'pmpro_error';
	}

	return $msgt;
}
add_filter( 'pmpro_login_forms_handler_msgt', 'pmpro_captcha_failed_login_msgt' );
