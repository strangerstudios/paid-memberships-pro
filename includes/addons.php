<?php
/**
 * Some of the code in this library was borrowed from the TGM Updater class by Thomas Griffin. (https://github.com/thomasgriffin/TGM-Updater)
 * 
 * DEPRECATED: This file contains legacy functions that have been moved to the PMPro_AddOns class.
 * All functions in this file are deprecated as of version 3.6 and will be removed in a future major release.
 * 
 * These functions are maintained for backward compatibility and simply wrap the new PMPro_AddOns class methods.
 * Developers should update their code to use the new PMPro_AddOns class directly.
 * 
 * @deprecated 3.6
 */

/**
 * Helper function to get the PMPro_AddOns singleton instance.
 * 
 * @since 3.6
 * @return PMPro_AddOns The singleton instance.
 */
function _pmpro_get_addons_manager() {
	static $addons_manager = null;
	if ( null === $addons_manager ) {
		$addons_manager = PMPro_AddOns::instance();
	}
	return $addons_manager;
}

/**
 * Setup plugins api filters
 *
 * @since 1.8.5
 * @deprecated 3.6 Use PMPro_AddOns class instead
 */
function pmpro_setupAddonUpdateInfo() {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns class constructor' );
	
	// The new class handles this automatically, but for backward compatibility
	// we'll trigger the admin hooks manually if they haven't been set up yet
	if ( ! has_filter( 'plugins_api', array( 'PMPro_AddOns', 'plugins_api' ) ) ) {
		$addons_manager = _pmpro_get_addons_manager();
	}
}

/**
 * Get addon information from PMPro server.
 *
 * @since  1.8.5
 * @deprecated 3.6 Use PMPro_AddOns::get_addons() instead
 */
if ( ! function_exists( 'pmpro_getAddons' ) ) {
	function pmpro_getAddons() {
		_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::get_addons()' );
		
		return _pmpro_get_addons_manager()->get_addons();
	}
}

/**
 * Get a list of installed Add Ons with incorrect folder names.
 *
 * @since 3.1
 * @deprecated 3.6 Use PMPro_AddOns::get_add_ons_with_incorrect_folder_names() instead
 *
 * @return array $incorrect_folder_names An array of Add Ons with incorrect folder names. The key is the installed folder name, the value is the Add On data.
 */
function pmpro_get_add_ons_with_incorrect_folder_names() {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::get_add_ons_with_incorrect_folder_names()' );
	
	return _pmpro_get_addons_manager()->get_add_ons_with_incorrect_folder_names();
}

/**
 * Find a PMPro addon by slug.
 *
 * @since 1.8.5
 * @deprecated 3.6 Use PMPro_AddOns::get_addon_by_slug() instead
 *
 * @param object $slug  The identifying slug for the addon (typically the directory name)
 * @return object $addon containing plugin information or false if not found
 */
if ( ! function_exists( 'pmpro_getAddonBySlug' ) ) {
	function pmpro_getAddonBySlug( $slug ) {
		_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::get_addon_by_slug()' );
		
		return _pmpro_get_addons_manager()->get_addon_by_slug( $slug );
	}
}

/**
 * Get the Add On slugs for each category we identify.
 *
 * @since 2.8.x
 * @deprecated 3.6 Use PMPro_AddOns::get_addon_categories() instead
 *
 * @return array $addon_cats An array of plugin categories and plugin slugs within each.
 */
function pmpro_get_addon_categories() {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::get_addon_categories()' );
	
	return _pmpro_get_addons_manager()->get_addon_categories();
}

/**
 * Get the Add On icon from the plugin slug.
 *
 * @since 2.8.x
 * @deprecated 3.6 Use PMPro_AddOns::get_addon_icon() instead
 *
 * @param string $slug The identifying slug for the addon (typically the directory name).
 * @return string $plugin_icon_src The src URL for the plugin icon.
 */
function pmpro_get_addon_icon( $slug ) {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::get_addon_icon()' );
	
	return _pmpro_get_addons_manager()->get_addon_icon( $slug );
}

/**
 * Infuse plugin update details when WordPress runs its update checker.
 *
 * @since 1.8.5
 * @deprecated 3.6 The PMPro_AddOns class handles this automatically
 *
 * @param object $value  The WordPress update object.
 * @return object $value Amended WordPress update object on success, default if object is empty.
 */
function pmpro_update_plugins_filter( $value ) {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::update_plugins_filter()' );
	
	return _pmpro_get_addons_manager()->update_plugins_filter( $value );
}

/**
 * Disables SSL verification to prevent download package failures.
 *
 * @since 1.8.5
 * @deprecated 3.6 The PMPro_AddOns class handles this automatically
 *
 * @param array  $args  Array of request args.
 * @param string $url  The URL to be pinged.
 * @return array $args Amended array of request args.
 */
function pmpro_http_request_args_for_addons( $args, $url ) {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::http_request_args_for_addons()' );
	
	return _pmpro_get_addons_manager()->http_request_args_for_addons( $args, $url );
}

/**
 * Setup plugin updaters
 *
 * @since  1.8.5
 * @deprecated 3.6 The PMPro_AddOns class handles this automatically
 */
function pmpro_plugins_api( $api, $action = '', $args = null ) {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::plugins_api()' );
	
	return _pmpro_get_addons_manager()->plugins_api( $api, $action, $args );
}

/**
 * Convert the format from the pmpro_getAddons function to that needed for plugins_api
 *
 * @since  1.8.5
 * @deprecated 3.6 The PMPro_AddOns class handles this internally
 */
if ( ! function_exists( 'pmpro_getPluginAPIObjectFromAddon' ) ) {
	function pmpro_getPluginAPIObjectFromAddon( $addon ) {
		_deprecated_function( __FUNCTION__, '3.6', 'PMPro_Add_Ons internal API handling via get_plugin_API_object_from_addon($addon)' );
		
		$api = new stdClass();

		if ( empty( $addon ) ) {
			return $api;
		}

		// add info
		$api->name                  = isset( $addon['Name'] ) ? $addon['Name'] : '';
		$api->slug                  = isset( $addon['Slug'] ) ? $addon['Slug'] : '';
		$api->plugin                = isset( $addon['plugin'] ) ? $addon['plugin'] : '';
		$api->version               = isset( $addon['Version'] ) ? $addon['Version'] : '';
		$api->author                = isset( $addon['Author'] ) ? $addon['Author'] : '';
		$api->author_profile        = isset( $addon['AuthorURI'] ) ? $addon['AuthorURI'] : '';
		$api->requires              = isset( $addon['Requires'] ) ? $addon['Requires'] : '';
		$api->tested                = isset( $addon['Tested'] ) ? $addon['Tested'] : '';
		$api->last_updated          = isset( $addon['LastUpdated'] ) ? $addon['LastUpdated'] : '';
		$api->homepage              = isset( $addon['URI'] ) ? $addon['URI'] : '';
		$api->download_link         = isset( $addon['Download'] ) ? $addon['Download'] : '';
		$api->package               = isset( $addon['Download'] ) ? $addon['Download'] : '';

		// add sections
		if ( !empty( $addon['Description'] ) ) {
			$api->sections['description'] = $addon['Description'];
		}
		if ( !empty( $addon['Installation'] ) ) {
			$api->sections['installation'] = $addon['Installation'];
		}
		if ( !empty( $addon['FAQ'] ) ) {
			$api->sections['faq'] = $addon['FAQ'];
		}
		if ( !empty( $addon['Changelog'] ) ) {
			$api->sections['changelog'] = $addon['Changelog'];
		}

		// get license key if one is available
		$key = get_option( 'pmpro_license_key', '' );
		if ( ! empty( $key ) && ! empty( $api->download_link ) ) {
			$api->download_link = add_query_arg( 'key', $key, $api->download_link );
		}
		if ( ! empty( $key ) && ! empty( $api->package ) ) {
			$api->package = add_query_arg( 'key', $key, $api->package );
		}
		
		if ( empty( $api->upgrade_notice ) && pmpro_license_type_is_premium( $addon['License'] ) ) {
			if ( ! pmpro_license_isValid( null, $addon['License'] ) ) {
				$api->upgrade_notice = sprintf( __( 'Important: This plugin requires a valid PMPro %s license key to update.', 'paid-memberships-pro' ), ucwords( $addon['License'] ) );
			}
		}	

		return $api;
	}
}

/**
 * Force update of plugin update data when the PMPro License key is updated
 *
 * @since 1.8
 * @deprecated 3.6 The PMPro_AddOns class handles this automatically
 *
 * @param array  $args  Array of request args.
 * @param string $url  The URL to be pinged.
 * @return array $args Amended array of request args.
 */
function pmpro_reset_update_plugins_cache( $old_value, $value ) {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::reset_update_plugins_cache()' );
	
	return _pmpro_get_addons_manager()->reset_update_plugins_cache( $old_value, $value );
}

/**
 * Detect when trying to update a PMPro Plus plugin without a valid license key.
 *
 * @since 1.9
 * @deprecated 3.6 The PMPro_AddOns class handles this automatically
 */
function pmpro_admin_init_updating_plugins() {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::check_when_updating_plugins()' );
	
	// The new class handles this automatically in the admin_hooks() method
	// This function is kept for backward compatibility but does nothing
}

/**
 * Check if an add on can be downloaded based on it's license.
 * @since 2.7.4
 * @deprecated 3.6 Use PMPro_AddOns::can_download_addon_with_license() instead
 * @param string $addon_license The license type of the add on to check.
 * @return bool True if the user's license key can download that add on,
 *              False if the user's license key cannot download it.
 */
function pmpro_can_download_addon_with_license( $addon_license ) {
	_deprecated_function( __FUNCTION__, '3.6', 'PMPro_AddOns::can_download_addon_with_license()' );
	
	return _pmpro_get_addons_manager()->can_download_addon_with_license( $addon_license );
}
