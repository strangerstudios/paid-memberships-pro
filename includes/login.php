<?php
/**
 * Redirect to Membership Account page for user login.
 *
 * @since 2.3
 *
 */
function pmpro_login_redirect( $redirect_to, $request = NULL, $user = NULL ) {
	global $wpdb;

	// Is a user logging in?
	if ( ! empty( $user ) && ! empty( $user->ID ) ) {
		// Logging in, let's figure out where to send them.
		if ( strpos( $redirect_to, "checkout" ) !== false ) {
			// If the redirect url includes the word checkout, leave it alone.
		} elseif ( $wpdb->get_var("SELECT membership_id FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND user_id = '" . $user->ID . "' LIMIT 1" ) ) {
			// If logged in and a member, send to wherever they were going.
		} else {
			// Not a member, send to subscription page.
			$redirect_to = pmpro_url( 'levels' );
		}
	}
	else {
		// Not logging in (login form) so return what was given.
	}

	return apply_filters( 'pmpro_login_redirect_url', $redirect_to, $request, $user );
}
add_filter( 'login_redirect','pmpro_login_redirect', 10, 3 );

//Where is the sign page? Levels page or default multisite page.
function pmpro_wp_signup_location($location)
{
	if(is_multisite() && pmpro_getOption("redirecttosubscription"))
	{
		return pmpro_url("levels");
	}
	else
		return $location;
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
    return $login_url;
}
add_filter( 'login_url', 'pmpro_login_url', 10, 2 );

/**
 * Show a member login form or logged in member widget.
 *
 * @since 2.3
 *
 */
function pmpro_login_form( ) {

	// Set the message return string.
	$message = '';
	if ( ! empty( $_GET['action'] ) ) {
        if ( 'failed' == $_GET['action'] ) {
            $message = 'There was a problem with your username or password.';
        } elseif ( 'loggedout' == $_GET['action'] ) {
            $message = 'You are now logged out.';
        } elseif ( 'recovered' == $_GET['action'] ) {
            $message = 'Check your e-mail for the confirmation link.';
        }
    }

    if ( $message ) {
		echo '<div class="pmpro_message pmpro_alert">'. $message .'</div>';
    }

    // Show the login form.
    if ( ! is_user_logged_in( ) ) {
		wp_login_form( );
		echo '<p><a href="'. wp_lostpassword_url( add_query_arg('action', 'recovered', get_permalink()) ) .'" title="Recover Lost Password">Lost Password?</a>';
	}
}

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
			wp_redirect( add_query_arg('action', 'loggedout', pmpro_login_url()) );
		}
		exit;
	}
}
add_action( 'wp_login_failed', 'pmpro_login_failed', 10, 2 );
