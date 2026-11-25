<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

/**
 * Translate a billing period name (Day, Month, ...).
 *
 * @param string $period      The period: Hour, Day, Week, Month or Year.
 * @param int    $number      The number of periods, used to pick singular or plural.
 * @param bool   $in_sentence Whether the period is used inside a sentence (e.g. "$10 per Month").
 *                            Those strings have their own translation context, so languages that
 *                            write period names in lowercase mid-sentence can do so without
 *                            changing the stand-alone labels. English output is unchanged.
 * @return string|null The translated period, or null for an unknown period.
 */
function pmpro_translate_billing_period($period, $number = 1, $in_sentence = false)
{
	if ( $in_sentence ) {
		return pmpro_translate_billing_period_in_sentence( $period, $number );
	}

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
 * Translate a billing period name for use inside a sentence.
 *
 * Same English text as pmpro_translate_billing_period(), but with a translation
 * context, so translators can use the form that fits mid-sentence (for example
 * lowercase "mes" / "meses" in Spanish) while the stand-alone labels keep theirs.
 * Falls back to pmpro_translate_billing_period() while the new strings are untranslated.
 *
 * @since TBD
 *
 * @param string $period The period: Hour, Day, Week, Month or Year.
 * @param int    $number The number of periods, used to pick singular or plural.
 * @return string|null The translated period, or null for an unknown period.
 */
function pmpro_translate_billing_period_in_sentence( $period, $number = 1 ) {
	if ( $number == 1 ) {
		switch ( $period ) {
			case 'Hour':
				$english    = 'Hour';
				$translated = _x( 'Hour', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Day':
				$english    = 'Day';
				$translated = _x( 'Day', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Week':
				$english    = 'Week';
				$translated = _x( 'Week', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Month':
				$english    = 'Month';
				$translated = _x( 'Month', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Year':
				$english    = 'Year';
				$translated = _x( 'Year', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			default:
				return null;
		}
	} else {
		switch ( $period ) {
			case 'Hour':
				$english    = 'Hours';
				$translated = _x( 'Hours', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Day':
				$english    = 'Days';
				$translated = _x( 'Days', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Week':
				$english    = 'Weeks';
				$translated = _x( 'Weeks', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Month':
				$english    = 'Months';
				$translated = _x( 'Months', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			case 'Year':
				$english    = 'Years';
				$translated = _x( 'Years', 'billing period used inside a sentence', 'paid-memberships-pro' );
				break;
			default:
				return null;
		}
	}

	// Not translated yet in this language: fall back to the existing period translation,
	// so sites don't switch to English until translators add the new strings.
	if ( $translated === $english ) {
		return pmpro_translate_billing_period( $period, $number );
	}

	return $translated;
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

	$is_pmpro_admin = ! empty( $_REQUEST['page'] ) && strpos( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'pmpro' ) !== false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only: only checks which admin screen is loading.
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$is_update_or_plugins_page = strpos( $request_uri, 'update-core.php' ) !== false || strpos( $request_uri, 'plugins.php' ) !== false;

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
