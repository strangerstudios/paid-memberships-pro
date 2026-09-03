<?php
/**
 * Register reCAPTCHA as an available captcha service.
 *
 * @since TBD
 *
 * @param array $services Captcha services as slug => label.
 * @return array $services Captcha services as slug => label.
 */
function pmpro_recaptcha_register_captcha_service( $services ) {
	$services['recaptcha'] = __( 'Google reCAPTCHA', 'paid-memberships-pro' );
	return $services;
}
add_filter( 'pmpro_captcha_services', 'pmpro_recaptcha_register_captcha_service' );

/**
 * Sets up our JS code to validate ReCAPTCHA on form submission if needed.
 */
function pmpro_init_recaptcha() {
	// If ReCAPTCHA is not enabled, don't do anything.
	// global $recaptcha for backwards compatibility.
	// TODO: Remove this in a future version.
	global $recaptcha;
	$recaptcha = ( 'recaptcha' === pmpro_captcha() ) ? 2 : false;
	if ( empty( $recaptcha ) ) {
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
			'error_message' => esc_attr__( 'ReCAPTCHA validation failed. Try again.', 'paid-memberships-pro' ),
			'public_key' => esc_html( get_option( 'pmpro_recaptcha_publickey' ) ),
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
 * Outputs the HTML needed to display ReCAPTCHA in a form.
 */
function pmpro_recaptcha_get_html() {
	static $already_shown = false;

	// Make sure that we only show the captcha once.
	if ( $already_shown ) {
		return;
	}

	// If ReCAPTCHA is not enabled, bail.
	if ( 'recaptcha' !== pmpro_captcha() ) {
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
	if(!empty($locale)) {
		$parts = explode("_", $locale);
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
		?>
	</div>
	<?php

	// If we are on the checkout page, run the deprecated pmpro_checkout_after_captcha action.
	if ( pmpro_is_checkout() ) {
		do_action_deprecated( 'pmpro_checkout_after_captcha', array(), '3.2', 'pmpro_checkout_before_submit_button' );
	}

	$already_shown = true;
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_recaptcha_get_html' );
add_action( 'pmpro_billing_before_submit_button', 'pmpro_recaptcha_get_html' );


/**
 * AJAX Method to Validate a ReCAPTCHA Response Token
 */
function pmpro_wp_ajax_validate_recaptcha() {
	require_once( PMPRO_DIR . '/includes/lib/recaptchalib.php' );
	
	$recaptcha_privatekey = get_option( 'pmpro_recaptcha_privatekey' );
	
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

	// If ReCAPTCHA is not enabled, return.
	if ( 'recaptcha' !== pmpro_captcha() ) {
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
	$recaptcha_version = get_option( 'pmpro_recaptcha_version' );
	$recaptcha_publickey = get_option( 'pmpro_recaptcha_publickey' );
	$recaptcha_privatekey = get_option( 'pmpro_recaptcha_privatekey' );

	$recaptcha_depends = array(
		array(
			'id'    => 'captcha',
			'value' => 'recaptcha',
		),
	);

	pmpro_build_settings_field( array(
		'name'        => 'recaptcha_version',
		'label'       => __( 'reCAPTCHA Version', 'paid-memberships-pro' ),
		'type'        => 'select',
		'value'       => $recaptcha_version,
		'row_class'   => 'pmpro_recaptcha_settings',
		'depends'     => $recaptcha_depends,
		'options'     => array(
			'2_checkbox'   => __( 'v2 - Checkbox', 'paid-memberships-pro' ),
			'3_invisible' => __( 'v3 - Invisible', 'paid-memberships-pro' ),
		),
		'description' => sprintf(
			/* translators: %s: Link to create a Google reCAPTCHA key. */
			__( 'Changing your version will require new API keys. A free reCAPTCHA key is required. <a href="%s" target="_blank" rel="nofollow noopener">Click here to signup for reCAPTCHA</a>.', 'paid-memberships-pro' ),
			'https://www.google.com/recaptcha/admin/create'
		),
	) );
	pmpro_build_settings_field( array(
		'name'      => 'recaptcha_publickey',
		'label'     => __( 'reCAPTCHA Site Key', 'paid-memberships-pro' ),
		'type'      => 'text',
		'class'     => 'regular-text code',
		'value'     => $recaptcha_publickey,
		'row_class' => 'pmpro_recaptcha_settings',
		'depends'   => $recaptcha_depends,
	) );
	pmpro_build_settings_field( array(
		'name'      => 'recaptcha_privatekey',
		'label'     => __( 'reCAPTCHA Secret Key', 'paid-memberships-pro' ),
		'type'      => 'text',
		'class'     => 'regular-text code',
		'value'     => $recaptcha_privatekey,
		'row_class' => 'pmpro_recaptcha_settings',
		'depends'   => $recaptcha_depends,
	) );
}
add_action( 'pmpro_security_spam_fields', 'pmpro_recaptcha_settings' );

/**
 * Save reCAPTCHA settings on the PMPro settings page.
 *
 * @since 3.2
 */
function pmpro_recaptcha_settings_save() {
	// Keep the legacy on/off option in sync with the captcha setting for backwards compatibility.
	update_option( 'pmpro_recaptcha', 'recaptcha' === pmpro_captcha() ? 2 : 0, false );
	pmpro_setOption( "recaptcha_version", sanitize_text_field( $_POST['recaptcha_version'] ) );
	pmpro_setOption( "recaptcha_publickey", sanitize_text_field( $_POST['recaptcha_publickey'] ) );
	pmpro_setOption( "recaptcha_privatekey", sanitize_text_field( $_POST['recaptcha_privatekey'] ) );
}
add_action( 'pmpro_save_security_settings', 'pmpro_recaptcha_settings_save' );

/**
 * Check whether login and password reset forms should be challenged with reCAPTCHA.
 *
 * @since TBD
 *
 * @return bool True if forms should be challenged.
 */
function pmpro_recaptcha_should_challenge_login() {
	// Only challenge if reCAPTCHA is the active captcha service.
	if ( 'recaptcha' !== pmpro_captcha() ) {
		return false;
	}

	// Don't challenge without keys. We couldn't render or verify the captcha,
	// and requiring a check that can't be completed would lock users out.
	if ( ! get_option( 'pmpro_recaptcha_publickey' ) || ! get_option( 'pmpro_recaptcha_privatekey' ) ) {
		return false;
	}

	// Only challenge IPs with recent suspicious activity, e.g. failed logins.
	return pmpro_captcha_has_recent_spam_activity();
}

/**
 * Verify a reCAPTCHA response token with Google.
 *
 * @since TBD
 *
 * @param string $token The g-recaptcha-response token to verify.
 * @return bool True if the token is valid.
 */
function pmpro_recaptcha_verify_token( $token ) {
	// An empty token means the user did not complete the challenge.
	if ( empty( $token ) ) {
		return false;
	}

	require_once( PMPRO_DIR . '/includes/lib/recaptchalib.php' );
	$reCaptcha = new pmpro_ReCaptcha( get_option( 'pmpro_recaptcha_privatekey' ) );
	$resp = $reCaptcha->verifyResponse( pmpro_get_ip(), sanitize_text_field( $token ) );

	return ! empty( $resp->success );
}

/**
 * Outputs the HTML needed to display reCAPTCHA in a login or password reset form.
 *
 * Unlike pmpro_recaptcha_get_html(), this does not use the checkout JS or the
 * AJAX/session validation flow. The token is submitted with the form and
 * verified server-side when the submission is processed.
 *
 * A widget is output for every form on the page since every form's submission
 * is validated server-side. Shared assets (scripts) are only output once.
 *
 * @since TBD
 */
function pmpro_recaptcha_get_login_html() {
	static $assets_shown = false;

	$recaptcha_publickey = get_option( 'pmpro_recaptcha_publickey' );

	// Figure out language.
	$locale = get_locale();
	if ( ! empty( $locale ) ) {
		$parts = explode( '_', $locale );
		$lang = $parts[0];
	} else {
		$lang = 'en';
	}
	/** This filter is documented in includes/recaptcha.php */
	$lang = apply_filters( 'pmpro_recaptcha_lang', $lang );

	// Check which version of reCAPTCHA we are using.
	$recaptcha_version = get_option( 'pmpro_recaptcha_version' );

	if ( $recaptcha_version == '3_invisible' ) {
		// One invisible widget container per form. The shared script below renders
		// each container explicitly with its own widget ID so that multiple forms
		// per page and other plugins' reCAPTCHA usage can coexist.
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_captcha' ) ); ?>">
			<div class="pmpro_recaptcha_login" data-sitekey="<?php echo esc_attr( $recaptcha_publickey ); ?>"></div>
		</div>
		<?php
		if ( ! $assets_shown ) {
			?>
			<script>
			(function() {
				var pmpro_recaptcha_login_setup_done = false;

				// Explicitly render an invisible widget in each form and intercept that
				// form's submission until reCAPTCHA has generated a token. If anything
				// fails, the form submits normally and the server rejects the tokenless
				// submission with an error the user can retry.
				function pmpro_recaptcha_login_setup() {
					if ( pmpro_recaptcha_login_setup_done || typeof grecaptcha === 'undefined' || typeof grecaptcha.render !== 'function' ) {
						return;
					}
					pmpro_recaptcha_login_setup_done = true;
					document.querySelectorAll( '.pmpro_recaptcha_login' ).forEach( function( el ) {
						var form = el.closest( 'form' );
						if ( ! form ) {
							return;
						}
						var hasToken = false;
						var widgetId = null;
						try {
							widgetId = grecaptcha.render( el, {
								'sitekey': el.getAttribute( 'data-sitekey' ),
								'size': 'invisible',
								'callback': function( token ) {
									hasToken = true;
									// Call the prototype method directly in case the form has an input named "submit".
									HTMLFormElement.prototype.submit.call( form );
								}
							} );
						} catch ( e ) {
							return;
						}
						form.addEventListener( 'submit', function( event ) {
							if ( hasToken || null === widgetId ) {
								return;
							}
							event.preventDefault();
							try {
								grecaptcha.execute( widgetId );
							} catch ( e ) {
								HTMLFormElement.prototype.submit.call( form );
							}
						} );
					} );
				}

				window.pmpro_recaptcha_login_onload = function() {
					pmpro_recaptcha_login_setup();
				};

				document.addEventListener( 'DOMContentLoaded', function() {
					// If another script on the page (e.g. PMPro checkout) already loaded the
					// reCAPTCHA API, reuse it rather than loading it twice.
					if ( window.grecaptcha && typeof window.grecaptcha.render === 'function' ) {
						pmpro_recaptcha_login_setup();
						return;
					}

					// Load the reCAPTCHA API if nothing else on the page is loading it.
					if ( ! document.querySelector( 'script[src*="google.com/recaptcha/api.js"]' ) ) {
						var script = document.createElement( 'script' );
						script.src = 'https://www.google.com/recaptcha/api.js?onload=pmpro_recaptcha_login_onload&render=explicit&hl=<?php echo esc_js( $lang ); ?>';
						script.async = true;
						document.head.appendChild( script );
						return;
					}

					// Another script tag is loading the API; wait for it to become available.
					var tries = 0;
					var timer = setInterval( function() {
						if ( window.grecaptcha && typeof window.grecaptcha.render === 'function' ) {
							clearInterval( timer );
							pmpro_recaptcha_login_setup();
						} else if ( ++tries > 100 ) {
							clearInterval( timer );
						}
					}, 100 );
				} );
			})();
			</script>
			<?php
			$assets_shown = true;
		}
	} else {
		// v2 checkbox: one widget container per form, auto-rendered by the API script.
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_captcha' ) ); ?>">
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_publickey ); ?>"></div>
		</div>
		<?php
		if ( ! $assets_shown ) {
			// defer (not async) so that every widget container on the page exists
			// before the API's auto-render pass runs.
			?>
			<script src="https://www.google.com/recaptcha/api.js?hl=<?php echo esc_attr( $lang ); ?>" defer></script>
			<?php
			$assets_shown = true;
		}
	}
}

/**
 * Show reCAPTCHA on login and lost password forms once a challenge is
 * active for the current IP.
 *
 * @since TBD
 */
function pmpro_recaptcha_login_forms_html() {
	if ( ! pmpro_recaptcha_should_challenge_login() ) {
		return;
	}

	pmpro_recaptcha_get_login_html();
}
add_action( 'login_form', 'pmpro_recaptcha_login_forms_html' );
add_action( 'lostpassword_form', 'pmpro_recaptcha_login_forms_html' );
add_action( 'pmpro_lost_password_before_submit_button', 'pmpro_recaptcha_login_forms_html' );

/**
 * Show reCAPTCHA inside forms built with wp_login_form() — including PMPro's
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
function pmpro_recaptcha_login_form_middle( $content, $args ) {
	ob_start();
	pmpro_recaptcha_login_forms_html();
	// Late priority and a (string) cast so that earlier callbacks that echo
	// and return null (instead of returning their content) can't wipe the widget.
	return (string) $content . ob_get_clean();
}
add_filter( 'login_form_middle', 'pmpro_recaptcha_login_form_middle', 100, 2 );

/**
 * Require a valid reCAPTCHA on login attempts once the current IP has failed a login.
 *
 * @since TBD
 *
 * @param WP_User|WP_Error|null $user     WP_User if the login is valid so far, otherwise WP_Error or null.
 * @param string                $username The username being used to log in.
 * @return WP_User|WP_Error|null $user
 */
function pmpro_recaptcha_login_check( $user, $username ) {
	if ( ! pmpro_recaptcha_should_challenge_login() ) {
		return $user;
	}

	$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';
	if ( ! pmpro_recaptcha_verify_token( $token ) ) {
		return new WP_Error( 'pmpro_captcha_failed', pmpro_captcha_failed_error_message() );
	}

	return $user;
}
add_filter( 'pmpro_authenticate_login_checks', 'pmpro_recaptcha_login_check', 10, 2 );

/**
 * Require a valid reCAPTCHA on lost password submissions once the current IP has failed a login.
 *
 * @since TBD
 *
 * @param WP_Error      $errors    Error object to add a captcha error to.
 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
 */
function pmpro_recaptcha_lostpassword_check( $errors, $user_data ) {
	// Only check submissions from the PMPro or wp-login.php lost password forms. This hook
	// also fires for other plugins that call retrieve_password() from their own forms,
	// which never displayed our captcha.
	if ( empty( $_REQUEST['pmpro_login_form_used'] ) && ! did_action( 'login_form_lostpassword' ) && ! did_action( 'login_form_retrievepassword' ) ) {
		return;
	}

	if ( ! pmpro_recaptcha_should_challenge_login() ) {
		return;
	}

	$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';
	if ( ! pmpro_recaptcha_verify_token( $token ) ) {
		$errors->add( 'pmpro_captcha_failed', pmpro_captcha_failed_error_message() );
	}
}
add_action( 'lostpassword_post', 'pmpro_recaptcha_lostpassword_check', 10, 2 );
