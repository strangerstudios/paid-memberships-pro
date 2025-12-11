<?php
/**
 * Enqueue login scripts needed for tweaking styling and functionality of the email login button on PMPro login pages and wp-login.php
 * 
 * @since TBD
 *
 */
function pmpro_login_email_login_scripts() {

	// Make sure we're on the PMPro login page or wp-login.php The pmpro_is_login_page function also checks for wp-login.php. 
	if ( ! pmpro_is_login_page() ) {
		return;
	}

	wp_enqueue_style( 'pmpro-email-login', PMPRO_URL . '/css/frontend/pmpro-email-login.css', array(), PMPRO_VERSION );
	wp_enqueue_script( 'pmpro-email-login', PMPRO_URL . '/js/pmpro-email-login.js', array( 'jquery' ), PMPRO_VERSION, true );

	// Create the login URL with the magic login action. The nonce will be sent via POST, not in the URL.
	$login_url = add_query_arg( array( 'action' => 'pmpro_magic_login' ), wp_login_url() );

	// Localize some variables for this JS File.
	$login_js_args = array(
		'login_url'      => $login_url,
		'nonce'          => wp_create_nonce( 'pmpro_email_login' )
	);

	wp_localize_script( 'pmpro-email-login', 'pmpro_email_login_js', $login_js_args );
}
add_action( 'login_enqueue_scripts', 'pmpro_login_email_login_scripts' );
add_action( 'wp_enqueue_scripts', 'pmpro_login_email_login_scripts' );

/**
 * Adds the "Send me the login link" button to the login form (wp-login.php) and frontend PMPro login form (login_form_middle)
 * 
 * @since TBD
 *
 */
function pmpro_login_add_login_email_button() {
	// Determine which hook we're on to set the correct button class.
	$button_class = 'pmpro_btn pmpro_btn-primary';
	if ( current_filter() === 'login_form' ) {
		$button_class = 'button button-primary button-hero';
	}
	?>
	<div id="pmpro-email-login">
		<span class="pmpro-login-or-separator"></span>
		<button type="button" value="#" class="<?php echo esc_attr( pmpro_get_element_class( $button_class ) ); ?>" id="pmpro-email-login-button"><?php esc_html_e( 'Email Me a Login Link', 'paid-memberships-pro' ); ?></button>
	</div>
	<?php
}
add_action( 'login_form', 'pmpro_login_add_login_email_button' );
add_action( 'login_form_middle', 'pmpro_login_add_login_email_button' );

/**
 * Email login link button has been submitted.
 *
 * @since TBD
 */
function pmpro_login_email_show_form_submitted() {

	// Failed to process the nonce and validation, bail.
	if ( pmpro_login_process_form_submission() === false ) {
		return;
	}

	// Show a message to the user.
	add_action(
		'login_message',
		function() {
			return sprintf(
				'<p class="message">%s <a href="%s">%s</a></p>',
				esc_html__( "If an account exists for this email address, you'll receive a login link shortly. This link will expire in 15 minutes.", 'paid-memberships-pro' ),
				esc_url( wp_login_url() ),
				esc_html__( 'Click here to go back to login.', 'paid-memberships-pro' )
			);
		}
	);

    // Hide the default login form and elements since we are showing a 'confirmation' message.
	add_action( 'login_head', function() {
		?>
		<style>
			#loginform,
			#nav,
			#backtoblog,
			#login_error {
				display: none !important;
			}
		</style>
		<?php
	} );
}
add_action( 'login_form_pmpro_magic_login', 'pmpro_login_email_show_form_submitted' );

/**
 * Replace the login confirmation for PMPro forms when clicking "Send me an Email link" from the frontend.
 * 
 * @since TBD
 *
 */
function pmpro_login_process_form_submission_pmpro_login() {
	if ( isset( $_REQUEST['pmpro_login_form_used'] ) && pmpro_login_process_form_submission() ) {
	?>
		<script>
			jQuery(document).ready(function($) {
				// remove the login form DOM content.
				$('#loginform').remove();
				$('#pmpro-email-login').remove();
				$('.pmpro_card_actions').remove();

				// Now add the success message inside the .pmpro_card_content div.
				$('.pmpro_card_content').prepend('<div class="pmpro_message" id="pmpro_email_login_confirmation"><?php echo esc_js( "If an account exists for this email address, you\'ll receive a login link shortly. This link will expire in 15 minutes.", "paid-memberships-pro" ); ?> <a href="<?php echo esc_url( pmpro_login_url() ); ?>"><?php echo esc_js( "Click here to go back to login.", "paid-memberships-pro" ); ?></a></div>');
			});
		</script>
	<?php
	}
}
add_action( 'wp_footer', 'pmpro_login_process_form_submission_pmpro_login' );

/**
 * Helper function to process the login link click creation, generate the token and email it to the member.
 * 
 * @since TBD
 *
 * @return boolean True if processed, false if not.
 */
function pmpro_login_process_form_submission() {

	if ( isset( $_GET['pmpro_email_login'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['pmpro_email_login'] ) ), 'pmpro_email_login' ) ) {

		// Get the user from either the email or username field.
		$login_input = isset($_REQUEST['log']) ? sanitize_text_field(wp_unslash($_REQUEST['log'])) : '';

		// Figure out if it's an email or username.
		if ( is_email( $login_input ) ) {
			$user = get_user_by( 'email', sanitize_email( wp_unslash( $login_input ) ) );
		} else {
			$user = get_user_by( 'login', sanitize_text_field( wp_unslash( $login_input ) ) );
		}

		// No user found, bail.
		$user_id = $user ? $user->ID : 0;
		if ( $user_id <= 0 ) {
			return false;
		}
		
		// If there is a cached login token, don't send another email. Bail and return true.
		if ( get_transient( 'pmpro_email_login_sent_' . $user_id ) === sanitize_text_field( wp_unslash( $_GET['pmpro_email_login'] ) ) ) {
			return true;
		}

		// Rate limiting: Only allow one email per user per 5 minutes.
		$last_sent = get_transient( 'pmpro_email_login_last_sent_' . $user_id );
		if ( $last_sent && ( time() - $last_sent < 5 * MINUTE_IN_SECONDS ) ) {
			// Optionally, you could log or show a message here.
			return false;
		}
		// Update the last sent time.
		set_transient( 'pmpro_email_login_last_sent_' . $user_id, time(), 5 * MINUTE_IN_SECONDS );
		// Generate the login token for the user to email it to them.
		$login_token = pmpro_login_generate_login_token( $user_id );
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

		// Create the login link from token + redirect_to to let them verify and redirect later.
		$login_link = add_query_arg( 'pmpro_email_login_token', $login_token, home_url() );
		if ( ! empty( $redirect_to ) ) {
			$login_link = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_link );
		}

		// Send email with login link.
		$pmpro_email = new PMProEmail();
		$pmpro_email->send_email_login_link( $user, $login_link );

		set_transient( 'pmpro_email_login_sent_' . $user_id, sanitize_text_field( wp_unslash( $_GET['pmpro_email_login'] ) ), 5 * MINUTE_IN_SECONDS );
		return true;
	}

	return false;

}

/**
 * Generate a new login token for the user and return the token.
 *
 * @param int $user_id The user ID to generate a login token for.
 * @return string The generated login token.
 */
function pmpro_login_generate_login_token( $user_id ) {

	// User ID must be valid, otherwise return empty string.
	if ( $user_id <= 0 ) {
		return '';
	}

	$code = wp_generate_password( 20, false );
	$login_token = substr( hash_hmac( 'sha256', $code, $user_id ), 0, 30 ); // Generate a shorter code (30 chars).
	$login_expires = time() + ( 15 * MINUTE_IN_SECONDS ); // Token valid for 15 minutes.

	// Store the login token and expiration in user meta. Separated this out so it's easier to work with and query. 
	update_user_meta( $user_id, 'pmpro_email_login_token', $login_token );
	update_user_meta( $user_id, 'pmpro_email_login_expires', $login_expires );

	return $login_token;
}

/**
 * Authenticate user via email token.
 * 
 * @since TBD
 *
 */
function pmpro_login_authenticate_via_email_login(){
	
	// Process the authentication and log the user in.
	if ( isset( $_GET['pmpro_email_login_token'] ) && ! is_user_logged_in() ) {
		$token = sanitize_text_field( wp_unslash( $_GET['pmpro_email_login_token'] ) );
		
		// Find user by token.
		$user_query = new WP_User_Query( array(
			'meta_key'     => 'pmpro_email_login_token',
			'meta_value'   => $token,
			'number'       => 1,
			'count_total'  => false,
		) );

		$users = $user_query->get_results();
		if ( empty( $users ) ) {
			wp_die( esc_html__( 'Invalid login link.', 'paid-memberships-pro' ) );
		}

		$user = $users[0];
		$user_id = $user->ID;

		// Check if the token is expired.
		$expires = get_user_meta( $user_id, 'pmpro_email_login_expires', true );
		if ( time() > (int) $expires ) {
			delete_user_meta( $user_id, 'pmpro_email_login_token' );
			delete_user_meta( $user_id, 'pmpro_email_login_expires' );
			wp_die( esc_html__( 'Login link has expired. Please generate a new token.', 'paid-memberships-pro' ) );
		}

		// Log the user in and set the authentication cookie.
		wp_set_auth_cookie( $user_id, true, is_ssl() );		
		
		// Clean up the used token.
		delete_user_meta( $user_id, 'pmpro_email_login_token' );
		delete_user_meta( $user_id, 'pmpro_email_login_expires' );
		delete_transient( 'pmpro_email_login_sent_' . $user_id );

		// Add the default wp_login hook for 2FA, reCAPTCHA or anything else that may want to intercept logins before redirecting.
		do_action( 'wp_login', $user->user_login, $user );

		// Figure out the redirect URL the user should be redirected to. Use the default login behavior of PMPro.
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
		$login_redirect = pmpro_login_redirect( $redirect_to, '', $user );

		// Redirect the user to the appropriate page.
		wp_safe_redirect( $login_redirect );
		exit;
	}
}
add_action( 'init', 'pmpro_login_authenticate_via_email_login' );

/**
 * Clean up all expired login tokens using Action Scheduler that runs every 15 minutes.
 * Might not be needed, but think it's okay to run in the background.
 * 
 * @since TBD
 * 
 */
function pmpro_login_cleanup_expired_login_tokens() {

	// Query all user meta for pmpro_email_login_expires that are expired.
	$meta_query = new WP_User_Query( array(
		'meta_key'     => 'pmpro_email_login_expires',
		'meta_value'   => time() + 15 * MINUTE_IN_SECONDS,
		'meta_compare' => '<',
		'number'       => 50,
		'count_total'  => false,
	) );

	$users = $meta_query->get_results();

	foreach ( $users as $user ) {
		$user_id = $user->ID;
		delete_user_meta( $user_id, 'pmpro_email_login_token' );
		delete_user_meta( $user_id, 'pmpro_email_login_expires' );
		delete_transient( 'pmpro_email_login_sent_' . $user_id );
	}
}
add_action( 'pmpro_schedule_quarter_hourly', 'pmpro_login_cleanup_expired_login_tokens' );
