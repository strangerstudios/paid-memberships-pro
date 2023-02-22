<?php
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
