<?php
function pmpro_load_textdomain() {
	$locale = apply_filters( 'plugin_locale', get_user_locale(), 'paid-memberships-pro' );
	$mofile = esc_attr( 'paid-memberships-pro-' . $locale . '.mo' );

	//paths to local (plugin) and global (WP) language files
	$mofile_local  = dirname( __DIR__ ) . '/languages/' . $mofile;
	$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

	unload_textdomain( 'paid-memberships-pro' );

	//load global first    
	if ( file_exists( $mofile_global ) ) {
		load_textdomain( 'paid-memberships-pro', $mofile_global );
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

/**
 * Handle translation updates from our own translation server.
 * @since 3.4
 */
function pmpro_check_for_translations() {
	// Run it only on a PMPro page in the admin.
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$is_pmpro_admin = ! empty( $_REQUEST['page'] ) && strpos( $_REQUEST['page'], 'pmpro' ) !== false;
	$is_update_or_plugins_page = strpos( $_SERVER['REQUEST_URI'], 'update-core.php' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'plugins.php' ) !== false;

	// Only run this check when we're in the PMPro Page or plugins/update page to save some resources.
	if ( ! $is_pmpro_admin && ! $is_update_or_plugins_page ) {
		return;
	}

	$pmpro_add_ons = ( new PMPro_AddOns() )->get_addons();
	foreach( $pmpro_add_ons as $add_on ) {
		// Skip if the plugin isn't active.
		if ( ! pmpro_is_plugin_active( $add_on['plugin'] ) ) {
			continue;
		}

		$plugin_slug = $add_on['Slug'];

		// This uses the Traduttore plugin to check for translations for locales etc.
		PMPro\Required\Traduttore_Registry\add_project(
			'plugin',
			$plugin_slug,
			'https://translate.strangerstudios.com/api/translations/' . $plugin_slug
		);
	}
	
}
add_action( 'admin_init', 'pmpro_check_for_translations' );
