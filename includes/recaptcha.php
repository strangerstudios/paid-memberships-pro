<?php
/**
 * Sets up our JS code to validate ReCAPTCHA on form submission if needed.
 */
function pmpro_init_recaptcha() {
	// If ReCAPTCHA is not enabled, don't do anything.
	// global $recaptcha for backwards compatbility.
	// TODO: Remove this in a future version.
	global $recaptcha;
	$recaptcha = pmpro_getOption( 'recaptcha' );
	if ( empty( $recaptcha ) ) {
		return;
	}

	// If ReCAPTCHA has already been validated, return.
	if ( true === pmpro_recaptcha_is_validated() ) {
		return;
	}	

	// Set up form submission JS code.
	$recaptcha_version = pmpro_getOption( 'recaptcha_version' );
	if( $recaptcha_version == '3_invisible' ) {
		wp_register_script( 'pmpro-recaptcha-v3', plugins_url( 'js/pmpro-recaptcha-v3.js', PMPRO_BASE_FILE ), array( 'jquery' ), PMPRO_VERSION );
		$localize_vars = array(
			'admin_ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'error_message' => esc_attr__( 'ReCAPTCHA validation failed. Try again.', 'paid-memberships-pro' ),
			'public_key' => esc_html( pmpro_getOption( 'recaptcha_publickey' ) ),
		);
		wp_localize_script( 'pmpro-recaptcha-v3', 'pmpro_recaptcha_v3', $localize_vars );
		wp_enqueue_script( 'pmpro-recaptcha-v3' );
	} else {
		wp_register_script( 'pmpro-recaptcha-v2', plugins_url( 'js/pmpro-recaptcha-v2.js', PMPRO_BASE_FILE ), array( 'jquery' ), PMPRO_VERSION );
		$localize_vars = array(
			'error_message' => esc_attr__( 'Please check the ReCAPTCHA box to confirm you are not a bot.', 'paid-memberships-pro' )
		);
		wp_localize_script( 'pmpro-recaptcha-v2', 'pmpro_recaptcha_v2', $localize_vars );
		wp_enqueue_script( 'pmpro-recaptcha-v2' );
	}

	// Adding $recaptcha_publickey and $recaptcha_privatekey globals for outdated page templates.
	// Setting to string 'global deprecated' to avoid a couple API calls.
	// TODO: Remove this in a future version.
	global $recaptcha_publickey, $recaptcha_privatekey;
	$recaptcha_publickey = 'global deprecated';
	$recaptcha_privatekey = 'global deprecated';

	// For templates using the old recaptcha_get_html. 
	// TODO: Remove this in a future version.
	if ( ! function_exists( 'recaptcha_get_html' ) ) {
		function recaptcha_get_html() {
			_deprecated_function( 'recaptcha_get_html', '2.12.3', 'pmpro_recaptcha_get_html');
			return pmpro_recaptcha_get_html();
		}
	}
}
add_action( 'pmpro_checkout_preheader', 'pmpro_init_recaptcha' );
add_action( 'pmpro_billing_preheader', 'pmpro_init_recaptcha', 9 ); // Run before the Stripe class loads pmpro-stripe.js

/**
 * Outputs the HTML needed in the checkout form to display the ReCAPTCHA.
 */
function pmpro_recaptcha_get_html() {
	// If ReCAPTCHA has already been validated, return.
	if ( true === pmpro_recaptcha_is_validated() ) {
		return;
	}

	$recaptcha_publickey = pmpro_getOption( 'recaptcha_publickey' );
	// Make sure we have a public key.
	if ( empty( $recaptcha_publickey ) ) {
		return;
	}

	// Figure out language.
	$locale = get_locale();
	if(!empty($locale)) {
		$parts = explode("_", $locale);
		$lang = $parts[0];
	} else {
		$lang = "en";	
	}
	$lang = apply_filters( 'pmpro_recaptcha_lang', $lang );

	// Check which version of ReCAPTCHA we are using.
	$recaptcha_version = pmpro_getOption( 'recaptcha_version' ); 
	if( $recaptcha_version == '3_invisible' ) { ?>
		<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_publickey );?>" data-size="invisible" data-callback="onSubmit"></div>
			<script type="text/javascript"
				src="https://www.google.com/recaptcha/api.js?onload=pmpro_recaptcha_onloadCallback&hl=<?php echo esc_attr( $lang );?>&render=explicit" async defer>
			</script>
	<?php } else { ?>
		<div class="g-recaptcha" data-callback="pmpro_recaptcha_validatedCallback" data-expired-callback="pmpro_recaptcha_expiredCallback" data-sitekey="<?php echo esc_attr( $recaptcha_publickey );?>"></div>
		<script type="text/javascript"
			src="https://www.google.com/recaptcha/api.js?hl=<?php echo esc_attr( $lang );?>">
		</script>
	<?php }				
}

/**
 * AJAX Method to Validate a ReCAPTCHA Response Token
 */
function pmpro_wp_ajax_validate_recaptcha() {
	require_once( PMPRO_DIR . '/includes/lib/recaptchalib.php' );
	
	$recaptcha_privatekey = pmpro_getOption( 'recaptcha_privatekey' );
	
	$reCaptcha = new pmpro_ReCaptcha( $recaptcha_privatekey );
	$resp      = $reCaptcha->verifyResponse( pmpro_get_ip(), sanitize_text_field( $_REQUEST['g-recaptcha-response'] ) );
	if ( $resp->success ) {
	    pmpro_set_session_var( 'pmpro_recaptcha_validated', true );
		echo "1";
	} else {
		echo "0";
	}
	
	exit;	
} 
add_action( 'wp_ajax_nopriv_pmpro_validate_recaptcha', 'pmpro_wp_ajax_validate_recaptcha' );
add_action( 'wp_ajax_pmpro_validate_recaptcha', 'pmpro_wp_ajax_validate_recaptcha' );

function pmpro_after_checkout_reset_recaptcha() {
    pmpro_unset_session_var( 'pmpro_recaptcha_validated' );
}
add_action( 'pmpro_after_checkout', 'pmpro_after_checkout_reset_recaptcha' );
add_action( 'pmpro_after_update_billing', 'pmpro_after_checkout_reset_recaptcha' );

/**
 * Check if ReCAPTCHA is validated.
 *
 * @return true|string True if validated, error message if not.
 */
function pmpro_recaptcha_is_validated() {
	// Check if the user has already been validated.
	$recaptcha_validated = pmpro_get_session_var( 'pmpro_recaptcha_validated' );
	if ( ! empty( $recaptcha_validated ) ) {
		return true;
	}

	// Get the ReCAPTCHA private key.
	$recaptcha_privatekey = pmpro_getOption( 'recaptcha_privatekey' );

	// Check if the user has completed a ReCAPTCHA challenge.
	if ( isset( $_POST["recaptcha_challenge_field"] ) ) {
		// Using older recaptcha lib. Google needs the raw POST data.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$resp = recaptcha_check_answer( $recaptcha_privatekey,
			pmpro_get_ip(),
			$_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"] );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$recaptcha_valid  = $resp->is_valid;
		$recaptcha_errors = $resp->error;
	} elseif ( isset( $_POST["g-recaptcha-response"] ) ) {
		//using newer recaptcha lib
		// NOTE: In practice, we don't execute this code because
		// we use AJAX to send the data back to the server and set the
		// pmpro_recaptcha_validated session variable, which is checked
		// earlier. We should remove/refactor this code.
		require_once(PMPRO_DIR . '/includes/lib/recaptchalib.php' );
		$reCaptcha = new pmpro_ReCaptcha( $recaptcha_privatekey );
		$resp      = $reCaptcha->verifyResponse( pmpro_get_ip(), $_POST["g-recaptcha-response"] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$recaptcha_valid  = $resp->success;
		$recaptcha_errors = $resp->errorCodes;
	} else {
		return __( 'ReCAPTCHA not submitted.', 'paid-memberships-pro' );
	}

	if ( $recaptcha_valid ) {
		pmpro_set_session_var( 'pmpro_recaptcha_validated', true );
		return true;
	} else {
		return $recaptcha_errors;
	}
}