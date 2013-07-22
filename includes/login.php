<?php
//redirect control
function pmpro_login_redirect($redirect_to, $request, $user)
{
	global $wpdb;

	//is a user logging in?
	if(!empty($user->ID))
	{
		//logging in, let's figure out where to send them
		if(pmpro_isAdmin($user->ID))
		{
			//admins go to dashboard
			$redirect_to = get_bloginfo("url") . "/wp-admin/";			
		}
		elseif(strpos($redirect_to, "checkout") !== false)
		{
			//if the redirect url includes the word checkout, leave it alone
		}
		elseif($wpdb->get_var("SELECT membership_id FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND user_id = '" . $user->ID . "' LIMIT 1"))
		{
			//if logged in and a member, send to wherever they were going			
		}
		else
		{
			//not a member, send to subscription page
			$redirect_to = pmpro_url("levels");
		}
	}
	else
	{
		//not logging in (login form) so return what was given		
	}
	
	//let's strip the https if force_ssl_login is set, but force_ssl_admin is not
	if(force_ssl_login() && !force_ssl_admin())
		$redirect_to = str_replace("https:", "http:", $redirect_to);
	
	return apply_filters("pmpro_login_redirect_url", $redirect_to, $request, $user);
}
add_filter('login_redirect','pmpro_login_redirect', 10, 3);

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
	$login_redirect = apply_filters("pmpro_login_redirect", true);
	if((pmpro_is_login_page() || is_page("login")) && $login_redirect)
	{
		//redirect registration page to levels page
		if(isset($_REQUEST['action']) && $_REQUEST['action'] == "register" || isset($_REQUEST['registration']) && $_REQUEST['registration'] == "disabled")
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
						
			if(is_array($GLOBALS['theme_my_login']->options))
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
			elseif(class_exists("Theme_My_Login") && version_compare(Theme_My_Login::version, "6.3") >= 0)
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