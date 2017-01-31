<?php


/**
 * Get all plugins, bu
 *
 * @return array
 */
function pmpro_getPlugins(){
	$plugins     = array();
	include_once ABSPATH  . '/wp-admin/includes/plugin.php';
	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin_file => $plugin_data ) {
		if ( is_plugin_active( $plugin_file ) ) {
			$plugins[ $plugin_data[ 'Name' ] ] = $plugin_data[ 'Version' ];
		}
	}

	return $plugins;

}