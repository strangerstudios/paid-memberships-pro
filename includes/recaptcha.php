<?php
/**
 * Sets up our JS code to validate ReCAPTCHA on form submission if needed.
 * 
 * @param string $submit_button_id The ID of the submit button to attach the ReCAPTCHA validation to, this helps for compatibility for different areas.
 */
function pmpro_init_recaptcha( $submit_button_id ) {
	// If ReCAPTCHA is not enabled, don't do anything.
	// global $recaptcha for backwards compatibility.
	// TODO: Remove this in a future version.
	global $recaptcha;
	$recaptcha = pmpro_captcha();
		
	if ( empty( $recaptcha ) || $recaptcha !== 'recaptcha' ) {
		return;
	}

	// If ReCAPTCHA has already been validated, return.
	if ( true === pmpro_recaptcha_is_validated() ) {
		return;
	}	

	// Set up form submission JS code.
	$recaptcha_version = get_option( 'pmpro_recaptcha_version' );
		
	if( $recaptcha_version == '3_invisible' ) {
		wp_register_script( 'pmpro-recaptcha-v3', plugins_url( 'js/pmpro-recaptcha-v3.js', PMPRO_BASE_FILE ), array( 'jquery' ), PMPRO_VERSION );
		$localize_vars = array(
			'admin_ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'error_message' => esc_attr__( 'ReCAPTCHA validation failed. Please try again.', 'paid-memberships-pro' ),
			'public_key' => esc_html( get_option( 'pmpro_recaptcha_publickey' ) ),
			'submit_button_id' => esc_html( $submit_button_id )
		);
		wp_localize_script( 'pmpro-recaptcha-v3', 'pmpro_recaptcha_v3', $localize_vars );
		wp_enqueue_script( 'pmpro-recaptcha-v3' );
	} else {
		wp_register_script( 'pmpro-recaptcha-v2', plugins_url( 'js/pmpro-recaptcha-v2.js', PMPRO_BASE_FILE ), array( 'jquery' ), PMPRO_VERSION );
		$localize_vars = array(
			'error_message' => esc_attr__( 'Please check the ReCAPTCHA box to confirm you are not a bot.', 'paid-memberships-pro' ),
			'submit_button_id' => esc_html( $submit_button_id )
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

/**
 * Outputs the HTML needed to display ReCAPTCHA in a form.
 * 
 * @param $submit_button_id The ID of the submit button to attach the ReCAPTCHA validation to, this helps for compatibility for different areas.
 * 
 */
function pmpro_recaptcha_get_html( $submit_button_id = 'pmpro_btn-submit-checkout' ) {
	static $already_shown = false;

	// If it's not a string let's set it to a string.
	if ( ! is_string( $submit_button_id ) ) {
		$submit_button_id = 'pmpro_btn-submit-checkout';
	}

	// Initialize the reCAPTCHA? Do we _really_ need this I think so. /// FIX eventually.
	pmpro_init_recaptcha( $submit_button_id );
	
	// Make sure that we only show the captcha once.
	if ( $already_shown ) {
		return;
	}

	// If ReCAPTCHA is not enabled, bail.
	if ( pmpro_captcha() !== 'recaptcha' ) {
		return;
	}

	// If ReCAPTCHA has already been validated, return.
	if ( true === pmpro_recaptcha_is_validated() ) {
		return;
	}

	$recaptcha_publickey = get_option( 'pmpro_recaptcha_publickey' );
	// Make sure we have a public key.
	if ( empty( $recaptcha_publickey ) ) {
		return;
	}

	// Figure out language.
	$locale = get_locale();
	if ( ! empty( $locale ) ) {
		$parts = explode( "_", $locale );
		$lang = $parts[0];
	} else {
		$lang = "en";	
	}
	$lang = apply_filters( 'pmpro_recaptcha_lang', $lang );

	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_captcha' ) ); ?>">
		<?php

		// Check which version of ReCAPTCHA we are using.
		$recaptcha_version = get_option( 'pmpro_recaptcha_version' ); 
		if ( $recaptcha_version == '3_invisible' ) { ?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_publickey );?>" data-size="invisible" data-callback="onSubmit"></div>
				<script type="text/javascript"
					src="https://www.google.com/recaptcha/api.js?onload=pmpro_recaptcha_onloadCallback&hl=<?php echo esc_attr( $lang );?>&render=explicit" async defer>
				</script>
		<?php } else { ?>
			<div class="g-recaptcha" data-callback="pmpro_recaptcha_validatedCallback" data-expired-callback="pmpro_recaptcha_expiredCallback" data-sitekey="<?php echo esc_attr( $recaptcha_publickey ); ?>"></div>
			<script type="text/javascript"
				src="https://www.google.com/recaptcha/api.js?hl=<?php echo esc_attr( $lang );?>">
			</script>
		<?php }
		?>
	</div>
	<?php

	// If we are on the checkout page, run the deprecated pmpro_checkout_after_captcha action.
	if ( pmpro_is_checkout() ) {
		do_action_deprecated( 'pmpro_checkout_after_captcha', array(), '3.2', 'pmpro_checkout_before_submit_button' );
	}

	$already_shown = true;
}

/**
 * Load the reCAPTCHA HTML and logic on the checkout and billing pages.
 *
 * @param object $level The membership level object.
 * @return string $recaptcha_html The HTML for the ReCAPTCHA.
 */
function pmpro_checkout_form_recaptcha( $level ) {
	// If ReCAPTCHA is not enabled, don't do anything.
	if ( pmpro_captcha() !== 'recaptcha' ) {
		return;
	}

	// If ReCAPTCHA has already been validated, return.
	if ( true === pmpro_recaptcha_is_validated() ) {
		return;
	}

	// Output the ReCAPTCHA HTML.
	pmpro_recaptcha_get_html( 'abcdef' );
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_recaptcha_get_html' );
add_action( 'pmpro_billing_before_submit_button', 'pmpro_recaptcha_get_html' );

/**
 * Adds reCAPTCHA to the PMPro login form
 *
 * @since TBD
 */
function pmpro_login_form_recaptcha( $login_form, $args ) {

	// Let's bail if we're not loading our version of the login form.
	if ( ! isset( $args['pmpro_login_form_used'] ) ) {
		return $login_form;
	}

	// If this isn't reCAPTCHA, don't load it.
	if ( pmpro_captcha() !== 'recaptcha' ) {
		return $login_form;
	}


	ob_start();
	pmpro_recaptcha_get_html( 'wp-submit' );
	$pmpro_recaptcha = ob_get_contents();
	ob_end_clean();
	return $login_form . $pmpro_recaptcha;
		
}
add_filter( 'login_form_middle', 'pmpro_login_form_recaptcha', 10, 2 );

/**
 * Adds reCAPTCHA to the WP login form
 *
 * @since TBD
 */
function pmpro_wp_login_form_recaptcha() {

	// Enable reCAPTCHA
	if ( pmpro_captcha() === 'recaptcha' ) {
		pmpro_recaptcha_get_html( 'wp-submit' );
	}
}
add_action( 'login_form', 'pmpro_wp_login_form_recaptcha', 10 );
add_action( 'lostpassword_form', 'pmpro_wp_login_form_recaptcha', 10 );

// This is for PMPro Lost Password form, since the button is different.
function pmpro_lost_password_form_recaptcha() {

	// Enable reCAPTCHA
	if ( pmpro_captcha() === 'recaptcha' ) {
		pmpro_recaptcha_get_html( 'pmpro_btn-submit' );
	}
}
add_action( 'pmpro_lost_password_before_submit_button', 'pmpro_lost_password_form_recaptcha', 10 );

/**
 * Apply custom CSS to the ReCAPTCHA element on the WP login page. For V2 reCAPTCHA only.
 * This is used to fix the alignment of the ReCAPTCHA element on the default login and password reset page.
 * 
 * @since TBD
 * 
 */
function pmpro_wp_login_style_v2_recaptcha() {
	// If ReCAPTCHA is not enabled, don't do anything.
	if ( pmpro_captcha() !== 'recaptcha' ) {
		return;
	}
	
	// No need to load this for V3 reCAPTCHA.
	$recaptcha_version = get_option( 'pmpro_recaptcha_version' ); 
	if ( $recaptcha_version === '3_invisible' ) {
		return;
	}

	// Style the RECAPTCHA.
	?>
	<style>
		.pmpro_captcha {
			margin-left: -15px !important;
			margin-bottom: 10px;
		}
	</style>
	<?php
}
add_action( 'login_head', 'pmpro_wp_login_style_v2_recaptcha' );

/**
 * AJAX Method to Validate a ReCAPTCHA Response Token
 */
function pmpro_wp_ajax_validate_recaptcha() {

	// Try to get the repsone.
	$response = isset( $_REQUEST['g-recaptcha-response'] ) ? pmpro_validate_recaptcha( $_REQUEST['g-recaptcha-response'] ) : false;

	if ( $response ) {
		echo '1';
	} else {
		echo '0';
	}

	exit;	
} 
add_action( 'wp_ajax_nopriv_pmpro_validate_recaptcha', 'pmpro_wp_ajax_validate_recaptcha' );
add_action( 'wp_ajax_pmpro_validate_recaptcha', 'pmpro_wp_ajax_validate_recaptcha' );

/**
 * Validates the reCAPTCHA response
 * 
 * @since TBD
 */
function pmpro_validate_recaptcha( $response ) {

	// If the user has already been validated, return true.
	if ( pmpro_get_session_var( 'pmpro_recaptcha_validated' ) ) {
		return true;
	}

	// An empty response means the user did not complete the reCAPTCHA challenge.
	if ( empty( $response ) ) {
		return false;
	}

	require_once( PMPRO_DIR . '/includes/lib/recaptchalib.php' );
	
	$recaptcha_privatekey = get_option( 'pmpro_recaptcha_privatekey' );
	
	$reCaptcha = new pmpro_ReCaptcha( $recaptcha_privatekey );
	$resp	  = $reCaptcha->verifyResponse( pmpro_get_ip(), sanitize_text_field( $response ) );
		
	if ( $resp->success ) {
		pmpro_set_session_var( 'pmpro_recaptcha_validated', true );
		return true;
	} else {
		return false;
	}

}

/**
 * Clear the reCAPTCHA validation session variable after checkout or billing update or login.
 */
function pmpro_after_checkout_reset_recaptcha() {
	pmpro_unset_session_var( 'pmpro_recaptcha_validated' );
}
add_action( 'pmpro_after_checkout', 'pmpro_after_checkout_reset_recaptcha' );
add_action( 'pmpro_after_update_billing', 'pmpro_after_checkout_reset_recaptcha' );
add_action( 'wp_login', 'pmpro_after_checkout_reset_recaptcha' );

/**
 * Check if ReCAPTCHA is validated.
 *
 * @return true|string True if validated, error message if not.
 */
function pmpro_recaptcha_is_validated() {
	
	// Check if the user has already been validated. Let's return true and clear the session variable.
	$recaptcha_validated = pmpro_get_session_var( 'pmpro_recaptcha_validated' );
	if ( ! empty( $recaptcha_validated ) ) {
		return true;
	}

	// Get the ReCAPTCHA private key.
	$recaptcha_privatekey = get_option( 'pmpro_recaptcha_privatekey' );

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
		$resp	  = $reCaptcha->verifyResponse( pmpro_get_ip(), $_POST["g-recaptcha-response"] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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

/**
 * Stop form submission if ReCAPTCHA is not validated.
 *
 * @since 3.2
 *
 * @param bool $continue Whether to continue with form submission.
 */
function pmpro_recaptcha_validation_check( $continue = true ) {
	// If the form is already not going to be submitted, return.
	if ( ! $continue ) {
		return false;
	}

	$captcha = pmpro_captcha();

	// If ReCAPTCHA is not enabled, return.
	if ( $captcha !== 'recaptcha' ) {
		return true;
	}

	// Check if reCAPTCHA is validated.
	$recaptcha_valid = pmpro_recaptcha_is_validated();

	if ( true === $recaptcha_valid ) {
		return true;
	} else {
		pmpro_setMessage( sprintf( __( 'reCAPTCHA failed. (%s) Please try again.', 'paid-memberships-pro' ), $recaptcha_valid ), 'pmpro_error' );
		return false;
	}
}
add_filter( 'pmpro_checkout_checks', 'pmpro_recaptcha_validation_check', 10, 1 );
add_filter( 'pmpro_billing_update_checks', 'pmpro_recaptcha_validation_check', 10, 1 );

/**
 * Show reCAPTCHA settings on the PMPro settings page.
 *
 * @since 3.2
 */
function pmpro_recaptcha_settings() {
	// Get the current options.
	$recaptcha = pmpro_captcha();
	$recaptcha_version = get_option( 'pmpro_recaptcha_version' );
	$recaptcha_publickey = get_option( 'pmpro_recaptcha_publickey' );
	$recaptcha_privatekey = get_option( 'pmpro_recaptcha_privatekey' );

	// If reCAPTCHA is not enabled, hide some settings by default.
	$tr_style = ( $recaptcha !== 'recaptcha' ) ? 'display: none;' : '';

	// Output settings fields.
	?>
	
	<tr class='pmpro_recaptcha_settings' style='<?php esc_attr_e( $tr_style); ?>'>
		<th scope="row" valign="top"><label for="recaptcha_version"><?php esc_html_e( 'reCAPTCHA Version', 'paid-memberships-pro' );?>:</label></th>
		<td>					
			<select id="recaptcha_version" name="recaptcha_version">
				<option value="2_checkbox" <?php selected( '2_checkbox', $recaptcha_version ); ?>><?php esc_html_e( ' v2 - Checkbox', 'paid-memberships-pro' ); ?></option>
				<option value="3_invisible" <?php selected( '3_invisible', $recaptcha_version ); ?>><?php esc_html_e( 'v3 - Invisible', 'paid-memberships-pro' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Changing your version will require new API keys. A free reCAPTCHA key is required.', 'paid-memberships-pro' );?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="nofollow noopener"><?php esc_html_e('Click here to signup for reCAPTCHA', 'paid-memberships-pro' );?></a>.</p>			
		</td>
	</tr>
	<tr class='pmpro_recaptcha_settings' style='<?php esc_attr_e( $tr_style); ?>'>
		<th scope="row"><label for="recaptcha_publickey"><?php esc_html_e('reCAPTCHA Site Key', 'paid-memberships-pro' );?>:</label></th>
		<td>
			<input type="text" id="recaptcha_publickey" name="recaptcha_publickey" value="<?php echo esc_attr($recaptcha_publickey);?>" class="regular-text code" />
		</td>
	</tr>
	<tr class='pmpro_recaptcha_settings' style='<?php esc_attr_e( $tr_style); ?>'>
		<th scope="row"><label for="recaptcha_privatekey"><?php esc_html_e('reCAPTCHA Secret Key', 'paid-memberships-pro' );?>:</label></th>
		<td>
			<input type="text" id="recaptcha_privatekey" name="recaptcha_privatekey" value="<?php echo esc_attr($recaptcha_privatekey);?>" class="regular-text code" />
		</td>
	</tr>
	<script>
		jQuery(document).ready(function() {
			jQuery('#captcha').change(function() {
				if(jQuery(this).val() == 'recaptcha') {
					jQuery('.pmpro_recaptcha_settings').show();
				} else {
					jQuery('.pmpro_recaptcha_settings').hide();
				}
			});
		});
	</script>
	<?php
}
add_action( 'pmpro_security_spam_fields', 'pmpro_recaptcha_settings' );

/**
 * Save reCAPTCHA settings on the PMPro settings page.
 *
 * @since 3.2
 */
function pmpro_recaptcha_settings_save() {
	pmpro_setOption( "recaptcha", intval( $_POST['recaptcha'] ) );
	pmpro_setOption( "recaptcha_version", sanitize_text_field( $_POST['recaptcha_version'] ) );
	pmpro_setOption( "recaptcha_publickey", sanitize_text_field( $_POST['recaptcha_publickey'] ) );
	pmpro_setOption( "recaptcha_privatekey", sanitize_text_field( $_POST['recaptcha_privatekey'] ) );
}
add_action( 'pmpro_save_security_settings', 'pmpro_recaptcha_settings_save' );