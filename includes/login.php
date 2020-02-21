<?php
/**
 * Redirect to Membership Account page for user login.
 *
 * @since 2.3
 *
 */
function pmpro_login_redirect( $redirect_to, $request = NULL, $user = NULL ) {

	// Is a user logging in?
	if ( ! empty( $user ) && ! empty( $user->ID ) ) {
		// Logging in, let's figure out where to send them.
		if ( strpos( $redirect_to, "checkout" ) !== false ) {
			// If the redirect url includes the word checkout, leave it alone.
		} elseif ( pmpro_hasMembershipLevel() ) {
			// If logged in and a member, send to wherever they were going.
		} else {
			// Not a member, send to subscription page.
			$redirect_to = pmpro_url( 'levels' );
		}
	} else {
		// Not logging in (login form) so return what was given.
	}

	return apply_filters( 'pmpro_login_redirect_url', $redirect_to, $request, $user );
}
add_filter( 'login_redirect','pmpro_login_redirect', 10, 3 );

//Where is the sign page? Levels page or default multisite page.
function pmpro_wp_signup_location( $location ) {
	if ( is_multisite() && pmpro_getOption("redirecttosubscription") ) {
		$location = pmpro_url("levels");
	} 

	return apply_filters( 'pmpro_wp_signup_location', $location );
}
add_filter('wp_signup_location', 'pmpro_wp_signup_location');

//redirect from default login pages to PMPro
function pmpro_login_head()
{
	global $pagenow;

	$login_redirect = apply_filters("pmpro_login_redirect", true);
	
	if((pmpro_is_login_page() || is_page("login") ||
		class_exists("Theme_My_Login") && method_exists('Theme_My_Login', 'is_tml_page') && (Theme_My_Login::is_tml_page("register") || Theme_My_Login::is_tml_page("login")) ||
		function_exists( 'tml_is_action' ) && ( tml_is_action( 'register' ) || tml_is_action( 'login' ) )
		)
		&& $login_redirect
	)
	{
		//redirect registration page to levels page
		if( isset($_REQUEST['action']) && $_REQUEST['action'] == "register" || 
			isset($_REQUEST['registration']) && $_REQUEST['registration'] == "disabled"	||
			!is_admin() && class_exists("Theme_My_Login") && method_exists('Theme_My_Login', 'is_tml_page') && Theme_My_Login::is_tml_page("register") ||
			function_exists( 'tml_is_action' ) && tml_is_action( 'register' )
		)
		{
			//redirect to levels page unless filter is set.
			$link = apply_filters("pmpro_register_redirect", pmpro_url("levels"));						
			if(!empty($link))
			{
				wp_redirect($link);
				exit;
			}
			else
				return;	//don't redirect if pmpro_register_redirect filter returns false or a blank URL
		}

		//if theme my login is installed, redirect all logins to the login page
		if(pmpro_is_plugin_active("theme-my-login/theme-my-login.php"))
		{
			//check for the login page id and redirect there if we're not there already
			global $post;
						
			if(!empty($GLOBALS['theme_my_login']) && is_array($GLOBALS['theme_my_login']->options))
			{
				//an older version of TML stores it this way
				if($GLOBALS['theme_my_login']->options['page_id'] !== $post->ID)
				{
					//redirect to the real login page
					$link = get_permalink($GLOBALS['theme_my_login']->options['page_id']);
					if($_SERVER['QUERY_STRING'])
						$link .= "?" . $_SERVER['QUERY_STRING'];
					wp_redirect($link);
					exit;
				}
			}
			elseif(!empty($GLOBALS['theme_my_login']->options))
			{
				//another older version of TML stores it this way
				if($GLOBALS['theme_my_login']->options->options['page_id'] !== $post->ID)
				{
					//redirect to the real login page
					$link = get_permalink($GLOBALS['theme_my_login']->options->options['page_id']);
					if($_SERVER['QUERY_STRING'])
						$link .= "?" . $_SERVER['QUERY_STRING'];
					wp_redirect($link);
					exit;
				}
			}
			elseif(class_exists("Theme_My_Login") && method_exists('Theme_My_Login', 'get_page_link') && method_exists('Theme_My_Login', 'is_tml_page'))
			{
				//TML > 6.3
				$link = Theme_My_Login::get_page_link("login");
				if(!empty($link))
				{
					//redirect if !is_page(), i.e. we're on wp-login.php
					if(!Theme_My_Login::is_tml_page())
					{
						wp_redirect($link);
						exit;
					}
				}				
			}
			elseif ( function_exists( 'tml_is_action' ) && function_exists( 'tml_get_action_url' ) && function_exists( 'tml_action_exists' ) )
			{
				$action = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';
				if ( tml_action_exists( $action ) ) {
					if ( 'wp-login.php' == $pagenow ) {
						$link = tml_get_action_url( $action );
						wp_redirect( $link );
						exit;
					}
				}
			}

			//make sure users are only getting to the profile when logged in
			global $current_user;
			if(!empty($_REQUEST['action']) && $_REQUEST['action'] == "profile" && !$current_user->ID)
			{
				$link = get_permalink($GLOBALS['theme_my_login']->options->options['page_id']);								
				wp_redirect($link);
				exit;
			}
		}
	}
}
add_action('wp', 'pmpro_login_head');
add_action('login_init', 'pmpro_login_head');

/*
	If a redirect_to value is passed into /login/ and you are logged in already, just redirect there
	
	@since 1.7.14
*/
function pmpro_redirect_to_logged_in()
{	
	if((pmpro_is_login_page() || is_page("login")) && !empty($_REQUEST['redirect_to']) && is_user_logged_in() && (empty($_REQUEST['action']) || $_REQUEST['action'] == 'login') && empty($_REQUEST['reauth']))
	{
		wp_safe_redirect($_REQUEST['redirect_to']);
		exit;
	}
}
add_action("template_redirect", "pmpro_redirect_to_logged_in", 5);
add_action("login_init", "pmpro_redirect_to_logged_in", 5);

/**
 * Redirect to the Membership Account page for member login.
 *
 * @since 2.3
 */
function pmpro_login_url( $login_url='', $redirect='' ) {
	$account_page_id = pmpro_getOption( 'account_page_id' );
    if ( ! empty ( $account_page_id ) ) {
        $login_url = get_permalink( $account_page_id );

        if ( ! empty( $redirect ) )
            $login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url ) ;
    }
    return apply_filters( 'pmpro_login_url', $login_url, $redirect );
}
add_filter( 'login_url', 'pmpro_login_url', 50, 2 );

/**
 * Show a member login form or logged in member widget.
 *
 * @since 2.3
 *
 */
function pmpro_login_form( $show_menu = true, $show_logout_link = true, $display_if_logged_in = true  ) {

	// Set the message return string.
	$message = '';
	$msgt = 'pmpro_alert';
	if ( ! empty( $_GET['action'] ) ) {
		switch ( sanitize_text_field( $_GET['action'] ) ) {
			case 'failed':
				$message = __( 'There was a problem with your username or password.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'recovered':
				$message = __( 'Check your e-mail for the confirmation link.', 'paid-memberships-pro' );
				break;
		}
	}

	// Logged Out Errors.
	if ( ! empty( $_GET['loggedout'] ) ) {
		switch ( sanitize_text_field( $_GET['loggedout'] ) ) {
			case 'true':
				$message = __( 'You are now logged out.', 'paid-memberships-pro' );
				$msgt = 'pmpro_success';
				break;
			default:
				$message = __( 'There was a problem logging you out.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}

	// Password reset email confirmation.
	if ( ! empty( $_GET['checkemail'] ) ) {

		switch ( sanitize_text_field( $_GET['checkemail'] ) ) {
			case 'confirm':
				$message = __( 'Check your email for a link to reset your password.', 'paid-memberships-pro' );
				break;
			default:
				$message = __( 'There was an unexpected error regarding your email. Please try again', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}

	// Password errors
	if ( ! empty( $_GET['login'] ) ) {
		switch ( sanitize_text_field( $_GET['login'] ) ) {
			case 'invalidkey':
				$message = __( 'Your reset password key is invalid.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'expiredkey':
				$message = __( 'Your reset password key is expired, please request a new key from the password reset page.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			default:
			break;
				
		}
	}

	if ( ! empty( $_GET['password'] ) ) {
		switch( sanitize_text_field( $_GET['password'] ) ) {
			case 'changed':
				$message = __( 'Your password has successfully been updated.', 'paid-memberships-pro' );
				$msgt = 'pmpro_success';
				break;
			default:
				$message = __( 'There was a problem updating your password', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
		}
	}

	// Get Errors from password reset.
	if ( ! empty( $_REQUEST['errors'] ) ) {
		switch ( sanitize_text_field( $_REQUEST['errors'] ) ) {
			case 'invalidcombo':
				$message = __( 'There is no account with that username or email address.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'empty_username':
				$message = __( 'Please enter a valid username.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'invalid_email':
				$message = __( "You've entered an invalid email address.", 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}
	
	if ( ! empty( $_REQUEST['error'] ) ) {
		switch ( sanitize_text_field( $_REQUEST['error'] ) ) {
			case 'password_reset_mismatch':
				$message = __( 'Password doesnt match, please try again', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
			case 'password_reset_empty':
				$message = __( 'Password field was empty, please try again.', 'paid-memberships-pro' );
				$msgt = 'pmpro_error';
				break;
		}
	}

    if ( $message ) {
		echo '<div class="pmpro_message ' . esc_attr( $msgt ) . '">'. esc_html( $message ) .'</div>';
    }

    // Show the login form.
    if ( ! is_user_logged_in( ) && $_GET['action'] !== 'reset_pass' ) {
		if ( empty( $_GET['login'] ) || empty( $_GET['key'] ) ) {
			?> <h2><?php _e( 'Login', 'paid-memberships-pro' ); ?></h2> <?php
			wp_login_form( );
			echo '<p><a href="' . add_query_arg( 'action', urlencode( 'reset_pass' ), $login_url )  . '"title="Recover Lost Password">' . esc_html__( 'Lost Password?', 'paid-memberships-pro' ) . '</a>';
		} 
	}

	if ( ! is_user_logged_in() && $_GET['action'] === 'reset_pass' ) {
		pmpro_lost_password_form();
	}

	if ( is_user_logged_in() ) {
		if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
			esc_html_e( 'You are already signed in.', 'paid-memberships-pro' );
		} elseif ( ! empty( $display_if_logged_in ) ) {
			pmpro_logged_in_welcome( $show_menu, $show_logout_link );
		}
	}

	if ( ! is_user_logged_in() && isset( $_REQUEST['key'] ) ) {
		pmpro_reset_password_form();
	}

}

/**
 * Generate a lost password form for front-end login.
 * @since 2.3
 */
function pmpro_lost_password_form() {
	?>
	<h2><?php esc_html_e( 'Password Reset', 'paid-memberships-pro' ); ?></h2>
	 <p>
        <?php
            esc_html_e( "Enter your email address/username and we'll send you a link you can use to pick a new password.", 'paid-memberships-pro' );
        ?>
    </p>
	 <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
        <p class="form-row">
            <label for="user_login"><?php esc_html_e( 'Your email address or username', 'paid-memberships-pro' ); ?>
            <input type="text" name="user_login" id="user_login">
        </p>
 
        <p class="lostpassword-submit">
            <input type="submit" name="submit" class="lostpassword-button"
                   value="<?php esc_attr_e( 'Reset Password', 'paid-memberships-pro' ); ?>"/>
        </p>
    </form>
	<?php
}

/**
 * Handle the password reset functionality. Redirect back to login form and show message.
 * @since 2.3
 */
function pmpro_lost_password_redirect() {
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
		$account_page = pmpro_getOption( 'account_page_id' );
		$redirect_url = $account_page ? get_permalink( $account_page ): '';

		$errors = retrieve_password();
		if ( is_wp_error( $errors ) ) {	
            $redirect_url = add_query_arg( array( 'errors' => join( ',', $errors->get_error_codes() ), 'action' => urlencode( 'reset_pass' ) ), $redirect_url );
		} else {
			$redirect_url = add_query_arg( array( 'checkemail' => urlencode( 'confirm' ) ), $redirect_url );
		}

		wp_redirect( $redirect_url );
		exit;
	}
}
add_action( 'login_form_lostpassword', 'pmpro_lost_password_redirect' );

/**
 * Redirect Password reset to our own page.
 * @since 2.3
 */
function pmpro_reset_password_redirect() {
	if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
		$account_page = pmpro_getOption( 'account_page_id' );
		$redirect_url = $account_page ? get_permalink( $account_page ): '';
		$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
				wp_redirect( add_query_arg( 'login', urlencode( 'expiredkey' ), $redirect_url ) );
            } else {
                wp_redirect( add_query_arg( 'login', urlencode( 'invalidkey' ), $redirect_url ));
            }
            exit;
        }
 
        $redirect_url = add_query_arg( array( 'login' => esc_attr( $_REQUEST['login'] ), 'action' => urlencode( 'rp' ) ), $redirect_url );
        $redirect_url = add_query_arg( array( 'key' => esc_attr( $_REQUEST['key'] ), 'action' => urlencode( 'rp' ) ), $redirect_url );
 
        wp_redirect( $redirect_url );
        exit;
	}
}
add_action( 'login_form_rp', 'pmpro_reset_password_redirect' );
add_action( 'login_form_resetpass', 'pmpro_reset_password_redirect' );

/**
 * Show the password reset form after user redirects from email link.
 * @since 2.3
 */
function pmpro_reset_password_form() {
	if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['error'] ) ) {
			$error_codes = explode( ',', sanitize_text_field( $_REQUEST['error'] ) );
		}
		
		?>
		<h2><?php _e( 'Reset Password', 'paid-memberships-pro' ); ?></h2>
		<form name="resetpassform" id="resetpassform" action="<?php echo esc_url( site_url( 'wp-login.php?action=resetpass' ) ); ?>" method="post" autocomplete="off">
       	 	<input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $_REQUEST['login'] ); ?>" autocomplete="off" />
        	<input type="hidden" name="rp_key" value="<?php echo esc_attr( $_REQUEST['key'] ); ?>" />
 
        <p>
            <label for="pass1"><?php esc_html_e( 'New password', 'paid-memberships-pro' ) ?></label>
            <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
        </p>
        <p>
            <label for="pass2"><?php esc_html_e( 'Repeat new password', 'paid-memberships-pro' ) ?></label>
            <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
        </p>
         
        <p class="description"><?php echo wp_get_password_hint(); ?></p>
         
        <p class="resetpass-submit">
            <input type="submit" name="submit" id="resetpass-button"
                   class="button" value="<?php esc_attr_e( 'Reset Password', 'paid-memberships-pro' ); ?>" />
        </p>
    </form>
	<?php
	}	
}

/**
 * Function to handle the actualy password reset and update password.
 * @since 2.3
 */
function pmpro_do_password_reset() {
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
        $rp_key = sanitize_text_field( $_REQUEST['rp_key'] );
		$rp_login = sanitize_text_field( $_REQUEST['rp_login'] );
		
		$account_page = pmpro_getOption( 'account_page_id' );
		$redirect_url = $account_page ? get_permalink( $account_page ): '';
		$user = check_password_reset_key( $rp_key, $rp_login );
 
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
				wp_redirect( add_query_arg( array( 'login' => urlencode( 'expiredkey' ), 'action' => urlencode( 'rp' ) ), $redirect_url ) );
            } else {
                wp_redirect( add_query_arg( array( 'login' => urlencode( 'invalidkey' ), 'action' => urlencode( 'rp' ) ), $redirect_url ) );
            }
            exit;
        }
 
        if ( isset( $_POST['pass1'] ) ) {
            if ( $_POST['pass1'] != $_POST['pass2'] ) {
				// Passwords don't match
				$redirect_url = add_query_arg( array(
					'key' => urlencode( $rp_key ),
					'login' => urlencode( $rp_login ),
					'error' => urlencode( 'password_reset_mismatch' ),
					'action' => urlencode( 'rp' )
				), $redirect_url );
                
                wp_redirect( $redirect_url );
                exit;
            }
 
            if ( empty( $_POST['pass1'] ) ) {
				// Password is empty
				$redirect_url = add_query_arg( array(
					'key' => urlencode( $rp_key ),
					'login' => urlencode( $rp_login ),
					'error' => urlencode( 'password_reset_empty' ),
					'action' => urlencode( 'rp' )
				), $redirect_url );
 
                wp_redirect( $redirect_url );
                exit;
            }
 
            // Parameter checks OK, reset password
            reset_password( $user, $_POST['pass1'] );
            wp_redirect( add_query_arg( urlencode( 'password' ), urlencode( 'changed' ), $redirect_url ) );
        } else {
           esc_html_e( 'Invalid Request', 'paid-memberships-pro' );
        }
 
        exit;
    }
}
add_action( 'login_form_rp', 'pmpro_do_password_reset' );
add_action( 'login_form_resetpass', 'pmpro_do_password_reset' );

/**
 * Replace the default URL inside the email with the membership account page login URL instead.
 * @since 2.3
 */
function pmpro_password_reset_email_filter( $message, $key, $user_login, $user_data ) {

	$account_page_id = pmpro_getOption( 'account_page_id' );
    if ( ! empty ( $account_page_id ) ) {
		$login_url = get_permalink( $account_page_id );
		
		// Only replace the URL if there's no redirect_to parameter.
		if ( strpos( $message, 'redirect_to' ) === false ) {
			$message = str_replace( site_url( 'wp-login.php' ), $login_url, $message );
		}
	}

	return $message;
}
add_filter( 'retrieve_password_message', 'pmpro_password_reset_email_filter', 10, 4 );

/**
 * Authenticate the frontend user login.
 *
 * @since 2.3
 *
 */
function pmpro_authenticate_username_password( $user, $username, $password ) {
	if ( is_a( $user, 'WP_User' ) ) {
		return $user;
	}

	if ( empty( $username ) || empty( $password ) ) {
		$error = new WP_Error();
		$user  = new WP_Error( 'authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.' ) );

		return $error;
	}
}
add_filter( 'authenticate', 'pmpro_authenticate_username_password', 30, 3);

/**
 * Redirect failed login to referrer for frontend user login.
 *
 * @since 2.3
 *
 */
function pmpro_login_failed( $username ) {
	$referrer = wp_get_referer();

	if ( $referrer && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer, 'wp-admin' ) ) {
		if ( empty( $_GET['loggedout'] ) ) {
			wp_redirect( add_query_arg( 'action', 'failed', pmpro_login_url() ) );
		} else {
			wp_redirect( add_query_arg( 'action', 'loggedout', pmpro_login_url() ) );
		}
		exit;
	}
}
add_action( 'wp_login_failed', 'pmpro_login_failed', 10, 2 );

/**
 * Show welcome content for a "Logged In" member with Display Name, Log Out link and a "PMPro Member" menu area.
 *
 * @since 2.3
 *
 */
function pmpro_logged_in_welcome( $show_menu = true, $show_logout_link = true ) {
	if ( is_user_logged_in( ) ) {
		// Set the location the user's display_name will link to based on level status.
		global $current_user, $pmpro_pages;
		if ( ! empty( $pmpro_pages ) && pmpro_hasMembershipLevel() ) {
			$account_page      = get_post( $pmpro_pages['account'] );
			$user_account_link = '<a href="' . esc_url( pmpro_url( 'account' ) ) . '">' . esc_html( preg_replace( '/\@.*/', '', $current_user->display_name ) ) . '</a>';
		} else {
			$user_account_link = '<a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html( preg_replace( '/\@.*/', '', $current_user->display_name ) ) . '</a>';
		}
		?>
		<h3 class="pmpro_member_display_name">
			<?php
				/* translators: a generated link to the user's account or profile page */
				printf( esc_html__( 'Welcome, %s', 'paid-memberships-pro' ), $user_account_link );
			?>
		</h3>

		<?php
		/**
		 * Show the "Member Form" menu to users with an active membership level.
		 * The menu can be customized per-level using the Nav Menus Add On for Paid Memberships Pro.
		 *
		 */
		if ( ! empty ( $show_menu ) && pmpro_hasMembershipLevel() ) {
			$pmpro_member_menu_defaults = array(
				'theme_location'  => 'pmpro-member',
				'container'       => 'nav',
				'container_id'    => 'pmpro-member-navigation',
				'container_class' => 'pmpro-member-navigation',
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			);
			wp_nav_menu( $member_menu_defaults );
		}
		?>

		<?php
		/**
		 * Optionally show a Log Out link.
		 * User will be redirected to the Membership Account page if no other redirect intercepts the process.
		 *
		 */
		if ( ! empty ( $show_logout_link ) ) { ?>
			<div class="pmpro_member_log_out"><a href="<?php echo wp_logout_url(); ?>"><?php echo esc_html__( 'Log Out', 'paid-memberships-pro' ); ?></a></div>
		<?php
		}
	}
}
