<?php


/**
 * Get all plugins with version
 *
 * @since 1.9
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

/**
 * Check if we have a request to optin/out and handle it
 *
 * @uses "admin_init"
 *
 * @since 1.9
 */
function pmpro_listenForUsageOptin(){

}

/**
 * If needed add an optin notice
 *
 * @uses "admin_notices"
 *
 * @since 1.9
 */
function pmro_addUsageOptinNotice() {


}

/**
 * Trigger send of stats if needed
 *
 * @since 1.9
 */
function pmro_maybeSendUsage(){

}