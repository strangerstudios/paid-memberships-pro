<?php
/**
 * Prep the ReCAPTCHA library if needed.
 * Fires on the wp hook.
 */
function pmpro_init_recaptcha() {
	//don't load if setting is off
	global $recaptcha, $recaptcha_validated, $pmpro_pages;
	$recaptcha = pmpro_getOption( 'recaptcha' );
	if ( empty( $recaptcha ) ) {
		return;
	}
	
	//don't load unless we're on the checkout or billing page
	$is_billing = ! empty( $pmpro_pages['billing'] ) && is_page( $pmpro_pages['billing'] );
	if ( ! pmpro_is_checkout() && ! $is_billing ) {
		return;
	}
	
	//check for validation
	$recaptcha_validated = pmpro_get_session_var( 'pmpro_recaptcha_validated' );
	if ( ! empty( $recaptcha_validated ) ) {
	    $recaptcha = false;
    }

	//captcha is needed. set up recaptcha keys and include the lib
	if($recaptcha) {
		global $recaptcha_publickey, $recaptcha_privatekey;
		
		require_once(PMPRO_DIR . '/includes/lib/recaptchalib.php' );
		
		$recaptcha_publickey = pmpro_getOption( 'recaptcha_publickey' );
		$recaptcha_privatekey = pmpro_getOption( 'recaptcha_privatekey' );
	}
}
add_action( 'wp', 'pmpro_init_recaptcha', 1 );

/**
 * AJAX Method to Validate a ReCAPTCHA Response Token
 */
function pmpro_wp_ajax_validate_recaptcha() {
	require_once( PMPRO_DIR . '/includes/lib/recaptchalib.php' );
	
	$recaptcha_privatekey = pmpro_getOption( 'recaptcha_privatekey' );
	
	$reCaptcha = new pmpro_ReCaptcha( $recaptcha_privatekey );
	$resp      = $reCaptcha->verifyResponse( $_SERVER['REMOTE_ADDR'], $_REQUEST['g-recaptcha-response'] );
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
