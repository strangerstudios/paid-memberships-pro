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
	//note as of v1.8, we stopped using _n and split things up to aid in localization
	if($number == 1)
	{
		if($period == "Day")
			return __("Day", "pmpro");
		elseif($period == "Week")
			return __("Week", "pmpro");
		elseif($period == "Month")
			return __("Month", "pmpro");
		elseif($period == "Year")
			return __("Year", "pmpro");
	}
	else
	{
		if($period == "Day")
			return __("Days", "pmpro");
		elseif($period == "Week")
			return __("Weeks", "pmpro");
		elseif($period == "Month")
			return __("Months", "pmpro");
		elseif($period == "Year")
			return __("Years", "pmpro");
	}
}