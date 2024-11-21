<?php
global $pmpro_reports;
if( null === $pmpro_reports ) {
	$pmpro_reports = array();
}

/**
 * Populate the $pmpro_reports global.
 *
 * @since 3.3.2
 */
function pmpro_populate_reports() {
	global $pmpro_reports;
	$pmpro_reports = apply_filters( 'pmpro_registered_reports', $pmpro_reports );
}
add_action( 'init', 'pmpro_populate_reports', 5 );

/*
	Load Reports From Theme
*/
$pmpro_reports_theme_dir = get_stylesheet_directory() . "/paid-memberships-pro/reports/";
if(is_dir($pmpro_reports_theme_dir))
{
	$cwd = getcwd();
	chdir($pmpro_reports_theme_dir);
	foreach (glob("*.php") as $filename)
	{
		require_once($filename);
	}
	chdir($cwd);
}
