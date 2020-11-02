<?php
/*
	This file handles the support licensing control for Paid Memberships Pro
	and PMPro addons.
	
	How it works:
	- All source code and resource files bundled with this plugin are licensed under the GPLv2 license unless otherwise noted (e.g. included third-party libraries).
	- An additional "support license" can be purchased at https://www.paidmembershipspro.com/pricing/
	  which will simultaneous support the development of this plugin and also give you access to support forums and documentation.
	- Once your license has been purchased, visit Settings --> PMPro License in your WP dashboard to enter your license.
	- Once the license is activated all "nags" will be disabled in the dashboard and member links will be added where appropriate.
    - This plugin will function 100% even if the support license is not installed.
    - If no support license is detected on this site, prompts will show in the admin to encourage you to purchase one.
	- You can override these prompts by setting the PMPRO_LICENSE_NAG constant to false.
*/

/*
	Developers, add this line to your wp-config.php to remove PMPro license nags even if no license has been purchased.
	
	define('PMPRO_LICENSE_NAG', false);	//consider purchasing a license at https://www.paidmembershipspro.com/pricing/
*/

/*
	Constants
*/
define('PMPRO_LICENSE_SERVER', 'https://license.paidmembershipspro.com/');

/*
	Check license.
*/
function pmpro_license_isValid($key = NULL, $type = NULL, $force = false) {		
	//check cache first
	$pmpro_license_check = get_option('pmpro_license_check', false);
	if(empty($force) && $pmpro_license_check !== false && $pmpro_license_check['enddate'] > current_time('timestamp'))
	{
		if(empty($type))
			return true;
		elseif($type == $pmpro_license_check['license'])
			return true;
		else
			return false;
	}
	
	//get key and site url
	if(empty($key))
		$key = get_option("pmpro_license_key", "");
	
	//no key
	if(!empty($key)) 
	{
		return pmpro_license_check_key($key);
	}
	else
	{
		//no key
		delete_option('pmpro_license_check');
		add_option('pmpro_license_check', array('license'=>false, 'enddate'=>0), NULL, 'no');
	
		return false;
	}
}

/*
	Activation/Deactivation. Check keys once a month.
*/
//activation
function pmpro_license_activation() {
	pmpro_maybe_schedule_event(current_time('timestamp'), 'monthly', 'pmpro_license_check_key');
}
register_activation_hook(__FILE__, 'pmpro_activation');

//deactivation
function pmpro_license_deactivation() {
	wp_clear_scheduled_hook('pmpro_license_check_key');
}
register_deactivation_hook(__FILE__, 'pmpro_deactivation');

//check keys with PMPro once a month
function pmpro_license_check_key($key = NULL) {
	//get key
	if(empty($key))
		$key = get_option('pmpro_license_key');
	
	//key? check with server
	if(!empty($key))
	{
		//check license server
		$url = add_query_arg(array('license'=>$key, 'domain'=>site_url()), PMPRO_LICENSE_SERVER);

        /**
         * Filter to change the timeout for this wp_remote_get() request.
         *
         * @since 1.8.5.1
         *
         * @param int $timeout The number of seconds before the request times out
         */
        $timeout = apply_filters("pmpro_license_check_key_timeout", 5);

        $r = wp_remote_get($url, array("timeout" => $timeout));

        //test response
        if(is_wp_error($r)) {
            //error
            pmpro_setMessage("Could not connect to the PMPro License Server to check key Try again later.", "error");
        }
        elseif(!empty($r) && $r['response']['code'] == 200)
		{
			$r = json_decode($r['body']);
						
			if($r->active == 1)
			{
				//valid key save enddate
				if(!empty($r->enddate))
					$enddate = strtotime($r->enddate, current_time('timestamp'));
				else
					$enddate = strtotime("+1 Year", current_time("timestamp"));
					
				delete_option('pmpro_license_check');
				add_option('pmpro_license_check', array('license'=>$r->license, 'enddate'=>$enddate), NULL, 'no');		
				return true;
			}
			elseif(!empty($r->error))
			{
				//invalid key
				global $pmpro_license_error;
				$pmpro_license_error = $r->error;
				
				delete_option('pmpro_license_check');
				add_option('pmpro_license_check', array('license'=>false, 'enddate'=>0), NULL, 'no');
                
			}
		}	
	}

    //no key or there was an error
    return false;
}
add_action('pmpro_license_check_key', 'pmpro_license_check_key');
