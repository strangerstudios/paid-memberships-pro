<?php
/**
 * Are we on the login page?
 * Checks for WP default, TML, and PMPro login page.
 */
function pmpro_is_login_page() {
	return ( in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) || is_page( 'login' ) || ( get_option( 'pmpro_login_page_id' ) && is_page( get_option( 'pmpro_login_page_id' ) ) ) );
}

/**
 * If no redirect_to is set
 * then redirect members to the account page
 * and redirect non-members to the levels page.
 */
function pmpro_login_redirect( $redirect_to, $request = NULL, $user = NULL ) {
	global $wpdb;

	$is_logged_in = ! empty( $user ) && ! empty( $user->ID );

	if ( $is_logged_in && empty( $redirect_to ) ) {
		// Can't use the pmpro_hasMembershipLevel function because it won't be defined yet.
		$is_member = $wpdb->get_var( "SELECT membership_id FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND user_id = '" . esc_sql( $user->ID ) . "' LIMIT 1" );
		if ( $is_member ) {
			$redirect_to = pmpro_url( 'account' );
		} else {
			$redirect_to = pmpro_url( 'levels' );
		}
	}

	// Custom redirect filters should use the core WordPress login_redirect filter instead of this one.
	// This filter is left in place for PMPro versions dating back to 2014.
	return apply_filters( 'pmpro_login_redirect_url', $redirect_to, $request, $user );
}
add_filter( 'login_redirect','pmpro_login_redirect', 10, 3 );

/**
 * Where is the sign up page? Levels page or default multisite page.
 */
function pmpro_wp_signup_location( $location ) {
	if ( is_multisite() && get_option("pmpro_redirecttosubscription") ) {
		$location = pmpro_url("levels");
	}

	return apply_filters( 'pmpro_wp_signup_location', $location );
}
add_filter('wp_signup_location', 'pmpro_wp_signup_location');

/**
 * Redirect from default login pages to PMPro.
 */
function pmpro_login_head() {
	global $pagenow;

	$login_redirect = apply_filters("pmpro_login_redirect", true);

	if ( ( pmpro_is_login_page() || is_page("login") ) && $login_redirect ) {
		//redirect registration page to levels page
		if ( isset ($_REQUEST['action'] ) && $_REQUEST['action'] == "register" ||
			isset($_REQUEST['registration']) && $_REQUEST['registration'] == "disabled" ) {

				// don't redirect if in admin.
				if ( is_admin() ) {
					return;
				}

				//redirect to levels page unless filter is set.
				$link = apply_filters("pmpro_register_redirect", pmpro_url( 'levels' ));
				if(!empty($link)) {
					wp_redirect($link);
					exit;
				}

			} else {
				return; //don't redirect if pmpro_register_redirect filter returns false or a blank URL
			}
	 	}
}
add_action('wp', 'pmpro_login_head');
add_action('login_init', 'pmpro_login_head');

/**
 * If a redirect_to value is passed into /login/ and you are logged in already, just redirect there
 *
 * @since 1.7.14
 */
function pmpro_redirect_to_logged_in() {
	// Fixes Site Health loopback test.
	
	if( ( pmpro_is_login_page() || is_page("login") )
		&& ! empty( $_REQUEST['redirect_to'] )
		&& is_user_logged_in()
		&& ( empty( $_REQUEST['action'] ) || $_REQUEST['action'] == 'login' )
		&& empty( $_REQUEST['reauth']) ) {

		wp_safe_redirect( esc_url_raw( $_REQUEST['redirect_to'] ) );
		exit;
	}
}
add_action("template_redirect", "pmpro_redirect_to_logged_in", 15);
add_action("login_init", "pmpro_redirect_to_logged_in", 5);

/**
 * Redirect to the login page for member login.
 * This filter is added on wp_loaded in the pmpro_wp_loaded_login_setup() function.
 *
 * @since 2.3
 */
function pmpro_login_url_filter( $login_url='', $redirect='' ) {

	// Don't filter when specifically on wp-login.php.
	if ( $_SERVER['SCRIPT_NAME'] === '/wp-login.php' ) {
		return $login_url;
	}

	// Check for a PMPro Login page.
	$login_page_permalink = pmpro_url( 'login' );
	if ( ! $login_page_permalink ) {
		return $login_url;
	}

	// We have a PMPro login page permalink. Use it instead.
	$login_url = $login_page_permalink;
	
	if ( ! empty( $redirect ) ) {
		$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url ) ;
	}

	return $login_url;
}

/**
 * Add the filter for login_url after WordPress is loaded.
 * This avoids errors with certain setups that may call wp_login_url() very early.
 *
 * @since 2.4
 *
 */
function pmpro_wp_loaded_login_setup() {
	add_filter( 'login_url', 'pmpro_login_url_filter', 50, 2 );
}
add_action( 'wp_loaded', 'pmpro_wp_loaded_login_setup' );

/**
 * Make sure confirm_admin_email actions go to the default WP login page.
 * Our login page is not set up to handle them.
 */
function pmpro_use_default_login_for_confirm_admin_email( $location ) {
	if ( isset($location) && strpos( $location, 'action=confirm_admin_email' ) !== false ) {
		$login_url = wp_login_url();

		remove_filter( 'login_url', 'pmpro_login_url_filter', 50, 2 );
		$default_login_url = wp_login_url();
		add_filter( 'login_url', 'pmpro_login_url_filter', 50, 2 );

		if ( $login_url != $default_login_url ) {
			$location = str_replace( $login_url, $default_login_url, $location );
		}
	}

	return $location;
}
add_filter( 'wp_redirect', 'pmpro_use_default_login_for_confirm_admin_email' );

/**
 * Get a link to the PMPro login page.
 * Or fallback to WP default.
 * @since 2.3
 *
 * @param string $login_url    The login URL. Not HTML-encoded.
 * @param string $redirect     The path to redirect to on login, if supplied.
 * @param bool   $force_reauth Whether to force reauthorization, even if a cookie is present.
 */
function pmpro_login_url( $redirect = '', $force_reauth = false ) {
	global $pmpro_pages;

	if ( empty( $pmpro_pages['login'] ) ) {
		// skip everything, including filter below
		return wp_login_url( $redirect, $force_reauth );
	}

	$login_url = get_permalink( $pmpro_pages['login'] );

    if ( ! empty( $redirect ) ) {
        $login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
    }

    if ( $force_reauth ) {
        $login_url = add_query_arg( 'reauth', '1', $login_url );
    }

    /**
     * Filters the login URL.
     *
     * @since 2.3
     *
     * @param string $login_url    The login URL. Not HTML-encoded.
     * @param string $redirect     The path to redirect to on login, if supplied.
     * @param bool   $force_reauth Whether to force reauthorization, even if a cookie is present.
     */
    return apply_filters( 'pmpro_login_url', $login_url, $redirect, $force_reauth );
}

/**
 * Get a link to the PMPro lostpassword page.
 * Or fallback to the WP default.
 * @since 2.3
 *
 * @param string $redirect     The path to redirect to on login, if supplied.
 */
function pmpro_lostpassword_url( $redirect = '' ) {
    global $pmpro_pages;

	if ( empty( $pmpro_pages['login'] ) ) {
		// skip everything, including filter below
		return wp_lostpassword_url( $redirect );
	}

	$args = array( 'action' => 'reset_pass' );
    if ( ! empty( $redirect ) ) {
        $args['redirect_to'] = urlencode( $redirect );
    }

    $lostpassword_url = add_query_arg( $args, get_permalink( $pmpro_pages['login'] ) );

    /**
     * Filters the Lost Password URL.
     *
     * @since 2.3
     *
     * @param string $lostpassword_url The lost password page URL.
     * @param string $redirect         The path to redirect to on login.
     */
    return apply_filters( 'pmpro_lostpassword_url', $lostpassword_url, $redirect );
}

/**
 * Add a hidden field to our login form
 * so we can identify it.
 * Hooks into the WP core filter login_form_top.
 */
function pmpro_login_form_hidden_field( $html ) {
	$html .= '<input type="hidden" name="pmpro_login_form_used" value="1" />';

	return $html;
}

/**
 * Filter the_title based on the form action of the Log In Page assigned to $pmpro_pages['login'].
 *
 * @since 2.3
 */
function pmpro_login_the_title( $title, $id = NULL ) {
	global $pmpro_pages, $wp_query;

	if ( is_admin() ) {
		return $title;
	}

	if ( isset( $wp_query ) && ( ! is_main_query() || ! is_page( $id ) ) ) {
		return $title;
	}

	if ( empty( $pmpro_pages ) || empty( $pmpro_pages['login'] ) || ! is_page( $pmpro_pages['login'] ) ) {
		return $title;
	}

	if ( is_user_logged_in() ) {
		$title = esc_html__( 'Welcome', 'paid-memberships-pro' );
	} elseif ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'reset_pass' ) {
		$title = esc_html__( 'Lost Password', 'paid-memberships-pro' );
	} elseif ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'rp' ) {
		$title = esc_html__( 'Reset Password', 'paid-memberships-pro' );
	}

	return $title;
}
add_filter( 'the_title', 'pmpro_login_the_title', 10, 2 );

/**
 * Filter document_title_parts based on the form action of the Log In Page assigned to $pmpro_pages['login'].
 *
 * @since 2.3
 */
function pmpro_login_document_title_parts( $titleparts ) {
	global $pmpro_pages;

	if ( empty( $pmpro_pages ) || empty ( $pmpro_pages['login'] ) || ! is_page( $pmpro_pages['login'] ) ) {
		return $titleparts;
	}

	if ( is_user_logged_in() ) {
		$titleparts['title'] = esc_html__( 'Welcome', 'paid-memberships-pro' );
	} elseif ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'reset_pass' ) {
		$titleparts['title'] = esc_html__( 'Lost Password', 'paid-memberships-pro' );
	} elseif ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'rp' ) {
		$titleparts['title'] = esc_html__( 'Reset Password', 'paid-memberships-pro' );
	}

	return $titleparts;
}
add_filter( 'document_title_parts', 'pmpro_login_document_title_parts' );

/**
 * Show a member login form or logged in member widget.
 *
 * @since 2.3
 */
function pmpro_login_forms_handler( $show_menu = true, $show_logout_link = true, $display_if_logged_in = true, $location = '', $echo = true ) {
	// Don't show widgets on the login page.
	if ( $location === 'widget' && pmpro_is_login_page() ) {
		return '';
	}

	// Set the message return string.
	$message = '';
	$msgt = 'pmpro_alert';
	$allowed_html = array('strong' => array() );
	if ( isset( $_GET['action'] ) && ! is_user_logged_in() ) {
		$username = isset( $_GET['username'] ) ? sanitize_text_field( $_GET['username'] ) : '';
		switch ( sanitize_text_field( $_GET['action'] ) ) {
			case 'failed':
				$message = esc_html__( 'There was a problem with your username or password.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'invalid_username':
				$message = sprintf(
				/* translators: %s: User name. */
				wp_kses( __( '<strong>Error:</strong> The username <strong>%s</strong> is not registered on this site. If you are unsure of your username, try your email address instead.', 'paid-memberships-pro' ), $allowed_html ),
				$username
				);
				$msgt = 'pmpro_error';
				break;
			case 'invalid_email' :
				$message = esc_html__( 'Unknown email address. Check again or try your username.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'empty_username':
				$message = wp_kses( __( '<strong>Error:</strong> The username field is empty.', 'paid-memberships-pro'), $allowed_html );
				$msgt = 'pmpro_error';
				break;
			case 'empty_password':
				$message = wp_kses( __( '<strong>Error:</strong> The password field is empty.', 'paid-memberships-pro' ), $allowed_html );
				$msgt = 'pmpro_error';
				break;
			case 'incorrect_password':
				$message = sprintf(
				/* translators: %s: User name. */
				wp_kses( __( '<strong>Error:</strong> The password you entered for the username %s is incorrect.', 'paid-memberships-pro' ), $allowed_html ),
				'<strong>' . $username . '</strong>'
			) .
			' <a href="' . pmpro_lostpassword_url() . '">' .
			esc_html__( 'Lost your password?', 'paid-memberships-pro' ) .
			'</a>';
				$msgt = 'pmpro_error';
				break;
			case 'recovered':
				$message = __( 'Check your email for the confirmation link.', 'paid-memberships-pro' );
				break;
			case 'confirmation':
				// Check if we are processing a confirmaction for a Data Request.
				$request_id = pmpro_confirmaction_handler();
				$message = _wp_privacy_account_request_confirmed_message( $request_id );
				$msgt = 'pmpro_success';
				break;
		}
	}

	// Logged Out Errors.
	if ( isset( $_GET['loggedout'] ) ) {
		switch ( sanitize_text_field( $_GET['loggedout'] ) ) {
			case 'true':
				$message = esc_html__( 'You are now logged out.', 'paid-memberships-pro' );
				$msgt = 'pmpro_success';
				break;
			default:
				$message = esc_html__( 'There was a problem logging you out.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}

	// Password reset email confirmation.
	if ( isset( $_GET['checkemail'] ) ) {

		switch ( sanitize_text_field( $_GET['checkemail'] ) ) {
			case 'confirm':
				$message = esc_html__( 'Check your email for a link to reset your password.', 'paid-memberships-pro' );
				break;
			default:
				$message = esc_html__( 'There was an unexpected error regarding your email. Please try again', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}

	// Password errors
	if ( isset( $_GET['login'] ) ) {
		switch ( sanitize_text_field( $_GET['login'] ) ) {
			case 'invalidkey':
				$message = esc_html__( 'Your reset password key is invalid.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'expiredkey':
				$message = esc_html__( 'Your reset password key is expired, please request a new key from the password reset page.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			default:
			break;

		}
	}

	if ( isset( $_GET['password'] ) ) {
		switch( $_GET['password'] ) {
			case 'changed':
				$message = esc_html__( 'Your password has successfully been updated.', 'paid-memberships-pro' );
				$msgt = 'pmpro_success';
				break;
			default:
				$message = esc_html__( 'There was a problem updating your password', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
		}
	}

	// Get Errors from password reset.
	if ( isset( $_REQUEST['errors'] ) ) {
		$password_reset_errors = sanitize_text_field( $_REQUEST['errors'] );
	} elseif ( isset( $_REQUEST['error'] ) ) {
		$password_reset_errors = sanitize_text_field( $_REQUEST['error'] );
	}
	if ( isset( $password_reset_errors ) ) {
		switch ( $password_reset_errors ) {
			case 'invalidcombo':
				$message = esc_html__( 'There is no account with that username or email address.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'empty_username':
				$message = esc_html__( 'Please enter a valid username.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'invalid_email':
				$message = esc_html__( "You've entered an invalid email address.", 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'password_reset_mismatch':
				$message = esc_html__( 'New passwords do not match.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'password_reset_empty':
				$message = esc_html__( 'Please complete all fields.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'retrieve_password_email_failure':
				$message = esc_html__( 'The email could not be sent. This site may not be correctly configured to send emails.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}

	ob_start();
	?>

	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<section id="pmpro_login" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_login' ) ); ?>">
			<?php
				// Note we don't show messages on the widget form.
				if ( $message && $location !== 'widget' ) {
					echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_message ' . $msgt, esc_attr( $msgt ) ) ) . '">'. wp_kses_post( $message ) .'</div>';
				}

				// Get the form title HTML tag.
				if ( $location === 'widget' ) {
					$before_title = '<h3 class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ) . '">';
					$after_title = '</h3>';
				} else {
					$before_title = '<h2 class="' . esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ) . '">';
					$after_title = '</h2>';
				}

				if ( isset( $_REQUEST['action'] ) ) {
					$action = sanitize_text_field( $_REQUEST['action'] );
				} else {
					$action = false;
				}

				// Figure out which login view to show.
				if ( ! is_user_logged_in() ) {
					if ( ! in_array( $action, array( 'reset_pass', 'rp' ) ) ) {
						// Login form.
						if ( empty( $_GET['login'] ) || empty( $_GET['key'] ) ) {
							$username = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : NULL;
							$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( $_REQUEST['redirect_to'] ) : '';

							// Redirect users back to their page that they logged-in from via the widget.
							if( empty( $redirect_to ) && $location === 'widget' && apply_filters( 'pmpro_login_widget_redirect_back', true ) ) {
								$redirect_to = site_url( esc_url_raw( $_SERVER['REQUEST_URI'] ) );
							}
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_login_wrap', 'pmpro_login_wrap' ) ); ?>">
								<?php
									if ( ! pmpro_is_login_page() ) {
										echo $before_title . esc_html__( 'Log In', 'paid-memberships-pro' ) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
									<?php
										$login_form_array = array(
											'value_username' => esc_html( $username ),
											'redirect' => $redirect_to,
										);
										pmpro_login_form( $login_form_array );
									?>
								</div> <!-- end pmpro_card_content -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
									<?php
										pmpro_login_forms_handler_nav( 'login' );
									?>
								</div> <!-- end pmpro_card_actions -->
							</div> <!-- end pmpro_login_wrap -->
							<?php if ( pmpro_is_login_page() ) { ?>
							<script>
								document.getElementById('user_login').focus();
							</script>
							<?php } ?>

							<?php
						}
  			  } elseif ( $location !== 'widget' && ( $action === 'reset_pass' || ( $action === 'rp' && isset( $_REQUEST['error'] ) && in_array( $_REQUEST['error'], array( 'invalidkey', 'expiredkey' ) ) ) ) ) {
					// Reset password form.
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_lost_password_wrap', 'pmpro_lost_password_wrap' ) ); ?>">
							<?php
								if ( ! pmpro_is_login_page() ) {
									echo $before_title . esc_html__( 'Password Reset', 'paid-memberships-pro' ) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">		
								<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_lost_password-instructions' ) ); ?>">
									<?php
										esc_html_e( 'Please enter your username or email address. You will receive a link to create a new password via email.', 'paid-memberships-pro' );
									?>
								</p>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
								<?php
									pmpro_lost_password_form();
								?>
							</div> <!-- end pmpro_card_content -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
								<?php
									pmpro_login_forms_handler_nav( 'lost_password' );
								?>
							</div> <!-- end pmpro_card_actions -->
						</div> <!-- end pmpro_lost_password_wrap -->
						<?php
					} elseif ( $location !== 'widget' && $action === 'rp' ) {
						// Password reset processing key.
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_reset_password_wrap', 'pmpro_reset_password_wrap' ) ); ?>">
							<?php
								if ( ! pmpro_is_login_page() ) {
									echo $before_title . esc_html__( 'Reset Password', 'paid-memberships-pro' ) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
								<?php pmpro_reset_password_form(); ?>
							</div> <!-- end pmpro_card_content -->
						</div> <!-- end pmpro_reset_password_wrap -->
						<?php
					}
				} else {
					// Already signed in.
					if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
						esc_html_e( 'You are already signed in.', 'paid-memberships-pro' );
					} elseif ( ! empty( $display_if_logged_in ) ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_logged_in_welcome_wrap', 'pmpro_logged_in_welcome_wrap' ) ); ?>">
							<?php pmpro_logged_in_welcome( $show_menu, $show_logout_link ); ?>
						</div> <!-- end pmpro_logged_in_welcome_wrap -->
						<?php
					}
				}
			?>
		</section> <!-- end pmpro_login -->
	</div> <!-- end pmpro -->
	<?php

	$content = ob_get_clean();
	if ( $echo ) {
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	return $content;
}

/**
 * Generate a login form for front-end login.
 * @since 2.3
 */
function pmpro_login_form( $args = array() ) {
	static $pmpro_login_form_counter = 1;
	add_filter( 'login_form_top', 'pmpro_login_form_hidden_field' );
	wp_login_form( $args );
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle' ) ); ?>">
		<button type="button" id="pmpro_btn-password-toggle-<?php echo esc_attr( $pmpro_login_form_counter ); ?>" class="pmpro_btn pmpro_btn-plain hide-if-no-js" data-toggle="0">
			<span class="pmpro_icon pmpro_icon-eye" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle-state' ) ); ?>"><?php esc_html_e( 'Show Password', 'paid-memberships-pro' ); ?></span>
		</button>
	</div>
	<script>
		// Password visibility toggle (wp_login_form instance).
		(function() {
			const toggleButton = document.querySelectorAll('#pmpro_btn-password-toggle-<?php echo esc_attr( $pmpro_login_form_counter ); ?>')[0];
			const toggleWrapper = toggleButton.closest('.pmpro_form_field-password-toggle');
			const loginForm = toggleWrapper.previousElementSibling;
			const passwordParagraph = loginForm.querySelector('.login-password');
			const passwordInput = loginForm.querySelector('#user_pass');

			passwordParagraph.appendChild(toggleWrapper);
			toggleButton.classList.remove('hide-if-no-js');
			toggleButton.addEventListener('click', togglePassword);

			function togglePassword() {
				const status = this.getAttribute('data-toggle');
				const passwordInputs = document.querySelectorAll('#user_pass');
				const icon = this.getElementsByClassName('pmpro_icon')[0];
				const state = this.getElementsByClassName('pmpro_form_field-password-toggle-state')[0];

				if (parseInt(status, 10) === 0) {
					this.setAttribute('data-toggle', 1);
					passwordInput.setAttribute( 'type', 'text' );
					icon.innerHTML = `
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off">
							<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
							<line x1="1" y1="1" x2="23" y2="23"></line>
						</svg>`;
					state.textContent = '<?php esc_html_e( 'Hide Password', 'paid-memberships-pro' ); ?>';
				} else {
					this.setAttribute('data-toggle', 0);
					passwordInput.setAttribute( 'type', 'password' );
					icon.innerHTML = `
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
							<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
							<circle cx="12" cy="12" r="3"></circle>
						</svg>`;
					state.textContent = '<?php esc_html_e( 'Show Password', 'paid-memberships-pro' ); ?>';
				}
			}
		})();
	</script>
	<?php
	remove_filter( 'login_form_top', 'pmpro_login_form_hidden_field' );
	$pmpro_login_form_counter++;
}

/**
 * Generate a lost password form for front-end login.
 * @since 2.3
 */
function pmpro_lost_password_form() { ?>
	<form id="lostpasswordform" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'lostpasswordform' ) ); ?>" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" method="post">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-user_login pmpro_form_field-required', 'pmpro_lost_password-field-user_login' ) ); ?>">
				<label for="user_login" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
					<?php esc_html_e( 'Username or Email Address', 'paid-memberships-pro' ); ?>
					<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
				</label>
				<input type="text" name="user_login" id="user_login" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_form_input-required pmpro_form_input-user_login', 'user_login' ) ); ?>" aria-required="true" />
			</div>
		</div> <!-- end pmpro_form_fields -->
		<?php 
		/**
		 * Insert HTML before the submit button on the lost password form.
		 * 
		 * @since 3.1.1
		 */
		do_action( 'pmpro_lost_password_before_submit_button' ); 
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
			<input type="hidden" name="pmpro_login_form_used" value="1" />
			<input type="submit" name="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ) ); ?>" value="<?php esc_attr_e( 'Get New Password', 'paid-memberships-pro' ); ?>" />
		</div>
	</form>
	<?php
}

/**
 * Handle the password reset functionality. Redirect back to login form and show message.
 * Look at function `pmpro_do_password_reset` for actually resetting the user's password.
 * @since 2.3
 */
function pmpro_lost_password_redirect() {

	if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
		return;
	}

	// Don't redirect if we're not using the PMPro login page.
	$redirect_url = pmpro_url( 'login' );
	if ( ! $redirect_url ) {
		return;
	}

	// Don't redirect if we're not on the PMPro form.
	if ( ! isset( $_REQUEST['pmpro_login_form_used'] ) ) {
		return;
	}
	
	$errors = retrieve_password();
	if ( is_wp_error( $errors ) ) {
		$redirect_url = add_query_arg( array( 'errors' => join( ',', $errors->get_error_codes() ), 'action' => urlencode( 'reset_pass' ) ), $redirect_url );
	} else {
		$redirect_url = add_query_arg( array( 'checkemail' => urlencode( 'confirm' ) ), $redirect_url );
	}

	wp_redirect( $redirect_url );
	exit;

}
add_action( 'login_form_lostpassword', 'pmpro_lost_password_redirect' );

/**
 * Redirect Password reset to our own page and honor all $_REQUEST params.
 * @since 2.3
 * @since 2.10.7 Uses the pmpro_url function now.
 */
function pmpro_reset_password_redirect() {

	// Don't redirect if the form is being submitted, i.e. POST.
	if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) {
		return;
	}

	// Don't redirect if we're not using the PMPro login page or if it's empty for some reason.
	$login_url = pmpro_url( 'login' );
	if ( ! $login_url ) {
		return;
	}

	// If the URL we're trying to redirect to isn't the login page, then don't redirect (assume it's coming from elsewhere)
	if ( strpos( $login_url, $_SERVER['REQUEST_URI'] ) === false ) {
		return;
	}

	// Don't redirect any requests coming from the wp-login.php page. (Backup check in case the above case fails for any reason.)
	if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ) {
		return;
	}

	// Get current REQUEST PARAMS and just add it to ours and then redirect to our login_url
	$redirect_url = add_query_arg( array_map( 'sanitize_text_field', $_REQUEST ), $login_url );

	wp_redirect( $redirect_url );
	exit;
}
add_action( 'login_form_rp', 'pmpro_reset_password_redirect' );
add_action( 'login_form_resetpass', 'pmpro_reset_password_redirect' );

/**
 * Show the password reset form after user redirects from email link.
 * @since 2.3
 */
function pmpro_reset_password_form() {
	if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {

		// Check if reset key is valid.
		$user = check_password_reset_key( sanitize_text_field( $_REQUEST['key'] ), sanitize_text_field( $_REQUEST['login'] ) );
		$errors = new WP_Error();
		if ( ! $user || is_wp_error( $user ) ) {
			if ( $user && $user->get_error_code() === 'invalid_key' ) {
				$errors->add( 'invalidkey', esc_html__( 'Your password reset link appears to be invalid. Please request a new link below.', 'paid-memberships-pro' ) );
			} elseif ( $user && $user->get_error_code() === 'expired_key' ) {
				$errors->add( 'expiredkey', esc_html__( 'Your password reset link has expired. Please request a new link below.', 'paid-memberships-pro' ) );
            }
		}

		// Grabbing errors from $_GET like wp-login.php does.
		if ( isset( $_GET['error'] ) ) {
			if ( 'invalidkey' === $_GET['error'] ) {
				$errors->add( 'invalidkey', esc_html__( 'Your password reset link appears to be invalid. Please request a new link below.', 'paid-memberships-pro' ) );
			} elseif ( 'expiredkey' === $_GET['error'] ) {
				$errors->add( 'expiredkey', esc_html__( 'Your password reset link has expired. Please request a new link below.', 'paid-memberships-pro' ) );
			}
		}

		if ( ! empty( $errors ) && $errors->has_errors() ) {
			// Combine errors into one message.
			$message = '';
			foreach ( $errors->get_error_codes() as $code ) {
				foreach ( $errors->get_error_messages( $code ) as $error_message ) {
					$message .= ' ' . $error_message . ' ';
				}
			}

			$msgt = 'pmpro_error';
			echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_message ' . $msgt, esc_attr( $msgt ) ) ) . '">'. esc_html( $message ) .'</div>';
			echo wp_kses_post( pmpro_lost_password_form() );
			return;
		}

		?>
		<form name="resetpassform" id="resetpassform" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'resetpassform' ) ); ?>" action="<?php echo esc_url( site_url( 'wp-login.php?action=resetpass' ) ); ?>" method="post" autocomplete="off">
			<input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['login'] ) ); ?>" autocomplete="username" />
			<input type="hidden" name="rp_key" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['key'] ) ); ?>" />
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password pmpro_form_field-pass1 pmpro_form_field-required', 'pmpro_form_field-pass1' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle' ) ); ?>">
							<label for="pass1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
								<?php esc_html_e( 'New Password', 'paid-memberships-pro' ) ?>
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
							</label>
							<button type="button" class="pmpro_btn pmpro_btn-plain pmpro_btn-password-toggle hide-if-no-js" data-toggle="0" tabindex="2">
								<span class="pmpro_icon pmpro_icon-eye" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle-state' ) ); ?>"><?php esc_html_e( 'Show Password', 'paid-memberships-pro' ); ?></span>
							</button>
						</div> <!-- end pmpro_form_field-password-toggle -->
						<input type="password" name="pass1" id="pass1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required pass1', 'pass1' ) ); ?>" size="20" value="" autocomplete="new-password" aria-required="true" tabindex="1" />
					</div> <!-- end pmpro_form_field-pass1 -->
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password pmpro_form_field-required pmpro_form_field-pass2', 'pmpro_form_field-pass2' ) ); ?>">
						<label for="pass2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
							<?php esc_html_e( 'Confirm New Password', 'paid-memberships-pro' ) ?>
							<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
						</label>
						<input type="password" name="pass2" id="pass2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required pass2', 'pass2' ) ); ?>" size="20" value="" autocomplete="new-password" aria-required="true" tabindex="3" />
					</div> <!-- end pmpro_form_field-pass2 -->
				</div> <!-- end pmpro_cols-2 -->
				<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php echo esc_html( wp_get_password_hint() ); ?></p>
				<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php esc_html_e( 'Strength Indicator', 'paid-memberships-pro' ); ?></div>
			</div> <!-- end pmpro_form_fields -->
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
				<input type="hidden" name="pmpro_login_form_used" value="1" />
				<input type="submit" name="submit" id="resetpass-button" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ) ); ?>" value="<?php esc_attr_e( 'Reset Password', 'paid-memberships-pro' ); ?>" tabindex="4" />
			</div> <!-- end pmpro_form_submit -->
		</form>
		<?php
	}
}

/**
 * Show the nav links below the login form.
 */
function pmpro_login_forms_handler_nav( $pmpro_form ) { ?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
		<?php
			// Build the links to return.
			$links = array();

			if ( $pmpro_form != 'login' ) {
				$links['login'] = sprintf( '<a href="%s">%s</a>', esc_url( pmpro_login_url() ), esc_html__( 'Log In', 'paid-memberships-pro' ) );
			}

			if ( apply_filters( 'pmpro_show_register_link', get_option( 'users_can_register' ) ) ) {
				$levels_page_id = get_option( 'pmpro_levels_page_id' );

				if ( $levels_page_id && pmpro_are_any_visible_levels() ) {
					$links['register'] = sprintf( '<a href="%s">%s</a>', esc_url( pmpro_url( 'levels' ) ), esc_html__( 'Join Now', 'paid-memberships-pro' ) );
				} else {
					$links['register'] = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), esc_html__( 'Register', 'paid-memberships-pro' ) );
				}
			}

			if ( $pmpro_form != 'lost_password' ) {
				$links['lost_password'] = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'action', urlencode( 'reset_pass' ), pmpro_login_url() ) ), esc_html__( 'Lost Password?', 'paid-memberships-pro' ) );
			}

			$links = apply_filters( 'pmpro_login_forms_handler_nav', $links, $pmpro_form );

			$allowed_html = array(
				'a' => array (
					'class' => array(),
					'href' => array(),
					'id' => array(),
					'target' => array(),
					'title' => array(),
				),
			);
			echo wp_kses( implode( pmpro_actions_nav_separator(), $links ), $allowed_html );
		?>
	</div> <!-- end pmpro_actions_nav -->
	<?php
}

/**
 * Function to handle the actually password reset and update password.
 * @since 2.3
 */
function pmpro_do_password_reset() {

	// Only run this code when the password reset it being processed.
    if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
		return;
	}

	// Don't reset if we're not using the PMPro login page.
	$redirect_url = pmpro_url( 'login' );
	if ( ! $redirect_url ) {
		return;
	}

	// Request came from elsewhere, let's bail.
	if ( ! isset( $_REQUEST['pmpro_login_form_used'] ) ) {
		return;
	}

	$rp_key = sanitize_text_field( $_REQUEST['rp_key'] );
	$rp_login = sanitize_text_field( $_REQUEST['rp_login'] );

	$check = check_password_reset_key( $rp_key, $rp_login );

	// If the key is expired or invalid, figure out the correct error code.
	if ( is_wp_error( $check ) ) {
		$error_code = $check->get_error_code() == 'expired_key' ? 'expiredkey' : 'invalidkey';	
	} elseif ( ! is_a( $check,  'WP_User' ) ) {
		// Probably null/false returned from a plugin filtering the check.
		$error_code = 'invalidkey';
	}

	// If there was an error redirect with that code.
	if ( ! empty( $error_code ) ) {		
		wp_redirect( add_query_arg( array( 'login' => urlencode( $error_code ), 'action' => urlencode( 'rp' ) ), $redirect_url ) );
		exit;
	}

	if ( isset( $_POST['pass1'] ) ) {
		
		$password_error = false;

		if ( $_POST['pass1'] != $_POST['pass2'] ) {
			// Passwords don't match
			$redirect_url = add_query_arg( array(
				'key' => urlencode( $rp_key ),
				'login' => urlencode( $rp_login ),
				'error' => urlencode( 'password_reset_mismatch' ),
				'action' => urlencode( 'rp' )
			), $redirect_url );
			$password_error = true;
		}

		if ( empty( $_POST['pass1'] ) ) {
			// Password is empty
			$redirect_url = add_query_arg( array(
				'key' => urlencode( $rp_key ),
				'login' => urlencode( $rp_login ),
				'error' => urlencode( 'password_reset_empty' ),
				'action' => urlencode( 'rp' )
			), $redirect_url );
			$password_error = true;
		}

		if( ! empty( $redirect_url ) && $password_error ) {
			wp_redirect( $redirect_url );
			exit;
		}

		// Parameter checks OK, reset password.
		// Note: Can't sanitize the password.
		// $check must be a WP_User object at this point, otherwise $error_code would be set and we'd have already redirected.
		reset_password( $check, $_POST['pass1'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_redirect( add_query_arg( urlencode( 'password' ), urlencode( 'changed' ), $redirect_url ) );
	} else {
		esc_html_e( 'Invalid Request', 'paid-memberships-pro' );
	}

	exit;
    
}
add_action( 'login_form_rp', 'pmpro_do_password_reset' );
add_action( 'login_form_resetpass', 'pmpro_do_password_reset' );

/**
 * Replace the default URL inside the password reset email
 * with the membership account page login URL instead.
 *
 * @since 2.3
 */
function pmpro_password_reset_email_filter( $message, $key, $user_login ) {

	$login_url = pmpro_url( 'login' );
	if ( ! $login_url ) {
		return $message;
	}

	// Don't replace the password reset link if it came from elsewhere.
	if ( ! isset( $_REQUEST['pmpro_login_form_used'] ) ) {
		return $message;
	}

	if ( strpos( $login_url, '?' ) ) {
		// Login page permalink contains a '?', so we need to replace the '?' already in the login URL with '&'.
		$message = str_replace( network_site_url( 'wp-login.php' ) . '?', $login_url . '&', $message );
	}
	$message = str_replace( network_site_url( 'wp-login.php' ), $login_url, $message );

	return $message;
}
add_filter( 'retrieve_password_message', 'pmpro_password_reset_email_filter', 20, 3 );
add_filter( 'wp_new_user_notification_email', 'pmpro_password_reset_email_filter', 10, 3 );

/**
 * Authenticate the frontend user login.
 *
 * @since 2.3
 *
 */
 function pmpro_authenticate_username_password( $user, $username, $password ) {

	// Only work when the PMPro login form is used.
	if ( empty( $_REQUEST['pmpro_login_form_used'] ) ) {
		return $user;
	}

	// Already logged in.
	if ( is_a( $user, 'WP_User' ) ) {
		return $user;
	}

	// For some reason, WP core doesn't recognize this error.
	if ( ! empty( $username ) && empty( $password ) ) {
		$user = new WP_Error( 'empty_password', wp_kses( __( '<strong>Error:</strong> The password field is empty.', 'paid-memberships-pro' ), array( 'strong' => array() ) ) );
	}

	// check what page the login attempt is coming from
	$referrer = wp_get_referer();

	if ( !empty( $referrer ) && is_wp_error( $user ) ) {

		$error = $user->get_error_code();

		if ( $error ) {
				$error_args = array(
					'action' => urlencode( $error ),
					'username' => sanitize_text_field( $username )
				);
				wp_redirect( add_query_arg( $error_args, pmpro_login_url() ) );
			} else {
				wp_redirect( pmpro_login_url() );
			}
	}

	return $user;
}
add_filter( 'authenticate', 'pmpro_authenticate_username_password', 30, 3);

/**
 * Redirect failed login to referrer for frontend user login.
 *
 * @since 2.3
 *
 * @param string $username The username of the user trying to log in.
 * @param WP_Error|null $error Error object. Added in 2.10.
 */
function pmpro_login_failed( $username, $error = null ) {

	$redirect_url = pmpro_url( 'login' );
	if ( ! $redirect_url ) {
		return;
	}

	$referrer = wp_get_referer();

	$redirect_to = ( ! empty( $_REQUEST['redirect_to'] ) ) ? esc_url_raw( $_REQUEST['redirect_to'] ) : '';

	if ( $referrer && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer, 'wp-admin' ) ) {
		if ( ! strstr( $referrer, '?login=failed') ) {
			// If an error was passed, get the code from there.
			$error_code = is_wp_error( $error ) ? $error->get_error_code() : 'failed';

			$redirect_url = add_query_arg( array( 'action'=> $error_code, 'username' => sanitize_text_field( $username ), 'redirect_to' => urlencode( $redirect_to ) ), pmpro_login_url() );
		} else {
			$redirect_url = add_query_arg( 'action', 'loggedout', pmpro_login_url() );			
		}
		wp_redirect( $redirect_url );
		exit;
	}
}
add_action( 'wp_login_failed', 'pmpro_login_failed', 10, 2 );

/**
 * Show welcome content for a "Logged In" member with Display Name, Log Out link and a "Log In Widget" menu area.
 *
 * @since 2.3
 *
 */
function pmpro_logged_in_welcome( $show_menu = true, $show_logout_link = true ) {
	if ( is_user_logged_in( ) ) {
		// Set the location the user's display_name will link to based on level status.
		global $current_user, $pmpro_pages;
		if ( ! empty( $pmpro_pages ) && ! empty( $pmpro_pages['account'] ) ) {
			$account_page      = get_post( $pmpro_pages['account'] );
			$user_account_link = '<a href="' . esc_url( pmpro_url( 'account' ) ) . '">' . esc_html( preg_replace( '/\@.*/', '', $current_user->display_name ) ) . '</a>';
		} else {
			$user_account_link = '<a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html( preg_replace( '/\@.*/', '', $current_user->display_name ) ) . '</a>';
		}
		?>
		<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large pmpro_member_display_name' ) ); ?>">
			<?php
				/* translators: a generated link to the user's account or profile page */
				printf( esc_html__( 'Welcome, %s', 'paid-memberships-pro' ), $user_account_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</h3>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">

			<?php do_action( 'pmpro_logged_in_welcome_before_menu' ); ?>

			<?php
			/**
			 * Show the "Log In Widget" menu to users.
			 * The menu can be customized per level using the Nav Menus Add On for Paid Memberships Pro.
			 *
			 */
			if ( ! empty( $show_menu ) ) {
				$pmpro_login_widget_menu_defaults = array(
					'theme_location'  => 'pmpro-login-widget',
					'container'       => 'nav',
					'container_id'    => 'pmpro-member-navigation',
					'container_class' => 'pmpro-member-navigation',
					'fallback_cb'	  => false,
					'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				);
				wp_nav_menu( $pmpro_login_widget_menu_defaults );
			}
			?>

			<?php do_action( 'pmpro_logged_in_welcome_after_menu' ); ?>
		</div> <!-- end pmpro_card_content -->
		<?php
		/**
		 * Optionally show a Log Out link.
		 * User will be redirected to the Membership Account page if no other redirect intercepts the process.
		 *
		 */
		if ( ! empty ( $show_logout_link ) ) { ?>
			
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
				<a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php esc_html_e( 'Log Out', 'paid-memberships-pro' ); ?></a>
			</div> <!-- end pmpro_card_actions -->
			<?php
		}
	}
}

/**
 * Allow default WordPress registration page if no level page is set and registrations are open for a site.
 * @since 2.3
 */
function pmpro_no_level_page_register_redirect( $url ) {
	$level = pmpro_url( 'levels' );

	if ( empty( pmpro_url( 'levels' ) ) && get_option( 'users_can_register' ) && ! pmpro_are_any_visible_levels() ) {
		return false;
	}

	return $url;
}
add_action( 'pmpro_register_redirect', 'pmpro_no_level_page_register_redirect' );

/**
 * Process Data Request confirmaction URLs.
 * Called from Account page preheader.
 * Checks first for action=confirmaction param.
 * Code pulled from wp-login.php.
 */
function pmpro_confirmaction_handler() {
	if ( empty( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'confirmaction' ) {
		return false;
	}

	if ( ! isset( $_GET['request_id'] ) ) {
		wp_die( esc_html__( 'Missing request ID.', 'paid-memberships-pro' ) );
	}

	if ( ! isset( $_GET['confirm_key'] ) ) {
		wp_die( esc_html__( 'Missing confirm key.', 'paid-memberships-pro' ) );
	}

	$request_id = (int) $_GET['request_id'];
	$key        = sanitize_text_field( wp_unslash( $_GET['confirm_key'] ) );
	$result     = wp_validate_user_request_key( $request_id, $key );

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result ) );
	}

	/** This action is documented in wp-login.php */
	do_action( 'user_request_action_confirmed', $request_id );

	return $request_id;
}
