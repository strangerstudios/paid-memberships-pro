<?php
function pmpro_load_textdomain() {
	$locale = apply_filters( 'plugin_locale', get_user_locale(), 'paid-memberships-pro' );
	$mofile = esc_attr( 'paid-memberships-pro-' . $locale . '.mo' );

	//paths to local (plugin) and global (WP) language files
	$mofile_local  = dirname( __DIR__ ) . '/languages/' . $mofile;
	$mofile_global = WP_LANG_DIR . '/pmpro/' . $mofile;
	$mofile_global2 = WP_LANG_DIR . '/paid-memberships-pro/' . $mofile;

	unload_textdomain( 'paid-memberships-pro' );

	//load global first    
	if ( file_exists( $mofile_global ) ) {
		load_textdomain( 'paid-memberships-pro', $mofile_global );
	} elseif ( file_exists( $mofile_global2 ) ) {
		load_textdomain( 'paid-memberships-pro', $mofile_global2 );
	}

	//load local second
	load_textdomain( 'paid-memberships-pro', $mofile_local );

	//load via plugin_textdomain/glotpress
	load_plugin_textdomain( 'paid-memberships-pro', false, dirname( __DIR__) . '/languages/' );
}
add_action( 'init', 'pmpro_load_textdomain', 1 );

function pmpro_translate_billing_period($period, $number = 1)
{	
	//note as of v1.8, we stopped using _n and split things up to aid in localization
	if($number == 1)
	{

		if( $period == "Hour" ){
			return __("Hour", "paid-memberships-pro" );
		} else if($period == "Day")
			return __("Day", 'paid-memberships-pro' );
		elseif($period == "Week")
			return __("Week", 'paid-memberships-pro' );
		elseif($period == "Month")
			return __("Month", 'paid-memberships-pro' );
		elseif($period == "Year")
			return __("Year", 'paid-memberships-pro' );
	}
	else
	{	
		if( $period == "Hour" ){
			return __("Hours", "paid-memberships-pro" );
		} else if($period == "Day")
			return __("Days", 'paid-memberships-pro' );
		elseif($period == "Week")
			return __("Weeks", 'paid-memberships-pro' );
		elseif($period == "Month")
			return __("Months", 'paid-memberships-pro' );
		elseif($period == "Year")
			return __("Years", 'paid-memberships-pro' );
	}
}
