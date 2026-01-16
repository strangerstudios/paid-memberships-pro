<?php
/**
 * Logic for CloudFlare Turnstile.
 */

/**
 * Show CloudFlare Turnstile on the checkout page.
 */
function pmpro_cloudflare_turnstile_get_html() {

	// If CloudFlare Turnstile is not enabled, bail.
	if ( empty( get_option( 'pmpro_cloudflare_turnstile' ) ) ) {
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
	?>
	<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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
	if ( empty( get_option( 'pmpro_cloudflare_turnstile' ) ) ) {
		return $okay;
	}

	// Don't show it more than once on a screen. This is for "PayPal Express".
	if ( pmpro_get_session_var( 'pmpro_cloudflare_turnstile_validated' ) ) {
		return $okay;
	}

	// If the Turnstile is not passed, show an error.
	if ( empty( $_POST['cf-turnstile-response'] ) ) {
		pmpro_setMessage( __( 'Please complete the security check.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	// Verify the turnstile check.
	$headers = array(
		'body' => array(
			'secret'   => get_option( 'pmpro_cloudflare_turnstile_secret_key', '' ),
			'response' => pmpro_getParam( 'cf-turnstile-response' ),
		),
	);
	$verify   = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', $headers );
	$verify   = wp_remote_retrieve_body( $verify );
	$response = json_decode( $verify );

	// If the check failed, show an error.
	if ( empty( $response->success ) ) {
		$error_messages    = pmpro_cloudflare_turnstile_get_error_message();
		$error_code        = $response->{'error-codes'}[0];
		$displayed_message = isset( $error_messages[ $error_code ] ) ? $error_messages[ $error_code ] : esc_html__( 'An error occurred while validating the security check.', 'paid-memberships-pro' );

		pmpro_setMessage( $displayed_message, 'pmpro_error' );
		$okay = false;
	}

	pmpro_set_session_var( 'pmpro_cloudflare_turnstile_validated', true );
	return $okay;
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
	$cloudflare_turnstile  = get_option( 'pmpro_cloudflare_turnstile', '0' );
	$cloudflare_site_key = get_option( 'pmpro_cloudflare_turnstile_site_key', '' );
	$cloudflare_secret_key = get_option( 'pmpro_cloudflare_turnstile_secret_key', '' );

	// If CloudFlare Turnstile is not enabled, hide some settings by default.
	$tr_style = empty( $cloudflare_turnstile ) ? 'display: none;' : '';

	// Output settings
	?>
	<tr>
		<th scope="row" valign="top">
			<label for="cloudflare_turnstile"><?php esc_html_e( 'Use CloudFlare Turnstile?', 'paid-memberships-pro' ); ?></label>
		</th>
		<td>
			<select id="cloudflare_turnstile" name="cloudflare_turnstile">
				<option value="0" <?php selected( $cloudflare_turnstile, 0 ); ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
				<option value="1" <?php selected( $cloudflare_turnstile, 1 ); ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'A free CloudFlare Turnstile key is required.', 'paid-memberships-pro' ); ?> <a href="https://www.cloudflare.com/products/turnstile/" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'Click here to signup for CloudFlare Turnstile', 'paid-memberships-pro' ); ?></a>.</p>
		</td>
	</tr>
   <tr class="pmpro_cloudflare_turnstile_settings" style="<?php echo esc_attr( $tr_style ); ?>">
		<th scope="row"><label for="cloudflare_turnstile_site_key"><?php esc_html_e( 'Turnstile Site Key', 'paid-memberships-pro' ); ?>:</label></th>
		<td>
			<input type="text" id="cloudflare_turnstile_site_key" name="cloudflare_turnstile_site_key" value="<?php echo esc_attr( $cloudflare_site_key ); ?>" class="regular-text code" />
		</td>
	</tr>
	<tr class="pmpro_cloudflare_turnstile_settings" style="<?php echo esc_attr( $tr_style ); ?>">
		<th scope="row"><label for="cloudflare_turnstile_secret_key"><?php esc_html_e( 'Turnstile Secret Key', 'paid-memberships-pro' ); ?>:</label></th>
		<td>
			<input type="text" id="cloudflare_turnstile_secret_key" name="cloudflare_turnstile_secret_key" value="<?php echo esc_attr( $cloudflare_secret_key ); ?>" class="regular-text code" />
		</td>
	</tr>
	<script>
		jQuery(document).ready(function() {
			jQuery('#cloudflare_turnstile').change(function() {
				if(jQuery(this).val() == '1') {
					jQuery('.pmpro_cloudflare_turnstile_settings').show();
				} else {
					jQuery('.pmpro_cloudflare_turnstile_settings').hide();
				}
			});
		});
	</script>
	<?php
}
add_action( 'pmpro_security_spam_fields', 'pmpro_cloudflare_turnstile_settings' );

/**
 * Save CloudFlare Turnstile settings on the PMPro settings page.
 *
 * @since 3.2
 */
function pmpro_cloudflare_turnstile_settings_save() {
	pmpro_setOption( 'cloudflare_turnstile', intval( $_POST['cloudflare_turnstile'] ) );
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