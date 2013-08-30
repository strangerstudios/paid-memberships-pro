<?php
function pmpro_load_textdomain()
{
    //get the locale
	$locale = apply_filters("plugin_locale", get_locale(), "pmpro");
	$mofile = "pmpro-" . $locale . ".mo";	
	
	//paths to local (plugin) and global (WP) language files
	$mofile_local  = dirname(__FILE__)."/../languages/" . $mofile;
	$mofile_global = WP_LANG_DIR . '/pmpro/' . $mofile;
	
	//load global first
    load_textdomain("pmpro", $mofile_global);

	//load local second
	load_textdomain("pmpro", $mofile_local);	
}
add_action("init", "pmpro_load_textdomain", 1);

function pmpro_translate_billing_period($period, $number = 1)
{
	if($period == "Day")
		return _n("Day", "Days", $number, "pmpro");
	elseif($period == "Week")
		return _n("Week", "Weeks", $number, "pmpro");
	elseif($period == "Month")
		return _n("Month", "Months", $number, "pmpro");
	elseif($period == "Year")
		return _n("Year", "Years", $number, "pmpro");	
}