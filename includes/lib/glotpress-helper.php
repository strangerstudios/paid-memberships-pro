<?php
/**
 * Main Traduttore Registry library.
 *
 * @since 1.0.0
 *
 * Copyright (c) 2018-2020 required (email: info@required.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace PMPro\Required\Traduttore_Registry;

use DateTime;

const TRANSIENT_KEY_PLUGIN = 'traduttore-registry-plugins';
const TRANSIENT_KEY_THEME  = 'traduttore-registry-themes';

/**
 * Adds a new project to load translations for.
 *
 * @since 1.0.0
 *
 * @param string $type    Project type. Either plugin or theme.
 * @param string $slug    Project directory slug.
 * @param string $api_url Full GlotPress API URL for the project.
 */
function add_project( $type, $slug, $api_url ) {
	if ( ! has_action( 'init', __NAMESPACE__ . '\register_clean_translations_cache' ) ) {
		add_action( 'init', __NAMESPACE__ . '\register_clean_translations_cache', 9999 );
	}

	/**
	 * Short-circuits translations API requests for private projects.
	 */
	add_filter(
		'translations_api',
		static function ( $result, $requested_type, $args ) use ( $type, $slug, $api_url ) {
			if ( $type . 's' === $requested_type && $slug === $args['slug'] ) {
				return get_translations( $type, $args['slug'], $api_url );
			}

			return $result;
		},
		10,
		3
	);

	/**
	 * Filters the translations transients to include the private plugin or theme.
	 *
	 * @see wp_get_translation_updates()
	 */
	add_filter(
		'site_transient_update_' . $type . 's',
		static function ( $value ) use ( $type, $slug, $api_url ) {
			if ( ! $value ) {
				$value = new \stdClass();
			}

			if ( ! isset( $value->translations ) ) {
				$value->translations = [];
			}

			$translations = get_translations( $type, $slug, $api_url );

			if ( ! isset( $translations['translations'] ) ) {
				return $value;
			}

			$installed_translations = get_installed_translations( $type );
			$locales                = get_available_locales();

			/** This filter is documented in wp-includes/update.php */
			$locales        = apply_filters( $type . 's_update_check_locales', $locales );
			$active_locales = array_unique( $locales );

			foreach ( (array) $translations['translations'] as $translation ) {
				if ( ! \in_array( $translation['language'], $active_locales, true ) ) {
					continue;
				}

				if ( $translation['updated'] && isset( $installed_translations[ $slug ][ $translation['language'] ] ) ) {
					$local  = sanitize_date( $installed_translations[ $slug ][ $translation['language'] ]['PO-Revision-Date'] );
					$remote = new DateTime( $translation['updated'] );

					if ( $local >= $remote ) {
						continue;
					}
				}

				$translation['type'] = $type;
				$translation['slug'] = $slug;

				$value->translations[] = $translation;
			}

			return $value;
		}
	);
}

/**
 * Registers actions for clearing translation caches.
 *
 * @since 1.1.0
 */
function register_clean_translations_cache() {
	$clear_plugin_translations = static function() {
		clean_translations_cache( 'plugin' );
	};
	$clear_theme_translations  = static function() {
		clean_translations_cache( 'theme' );
	};

	add_action( 'set_site_transient_update_plugins', $clear_plugin_translations );
	add_action( 'delete_site_transient_update_plugins', $clear_plugin_translations );

	add_action( 'set_site_transient_update_themes', $clear_theme_translations );
	add_action( 'delete_site_transient_update_themes', $clear_theme_translations );
}

/**
 * Clears existing translation cache for a given type.
 *
 * @since 1.1.0
 *
 * @param string $type Project type. Either plugin or theme.
 */
function clean_translations_cache( $type ) {
	$transient_key = constant( __NAMESPACE__ . '\TRANSIENT_KEY_' . strtoupper( $type ) );
	$translations  = get_site_transient( $transient_key );

	if ( ! \is_object( $translations ) ) {
		return;
	}

	/*
	 * Don't delete the cache if the transient gets changed multiple times
	 * during a single request. Set cache lifetime to maximum 15 seconds.
	 */
	$cache_lifespan   = 15;
	$time_not_changed = isset( $translations->_last_checked ) && ( time() - $translations->_last_checked ) > $cache_lifespan;

	if ( ! $time_not_changed ) {
		return;
	}

	delete_site_transient( $transient_key );
}

/**
 * Gets the translations for a given project.
 *
 * @since 1.0.0
 *
 * @param string $type Project type. Either plugin or theme.
 * @param string $slug Project directory slug.
 * @param string $url  Full GlotPress API URL for the project.
 * @return array Translation data.
 */
function get_translations( $type, $slug, $url ) {
	$transient_key = constant( __NAMESPACE__ . '\TRANSIENT_KEY_' . strtoupper( $type ) );
	$translations  = get_site_transient( $transient_key );

	if ( ! \is_object( $translations ) ) {
		$translations = new \stdClass();
	}


	if ( isset( $translations->{$slug} ) && \is_array( $translations->{$slug} ) ) {
		return $translations->{$slug};
	}

	$result = json_decode( wp_remote_retrieve_body( wp_remote_get( $url, [ 'timeout' => 2 ] ) ), true );

	if ( ! \is_array( $result ) ) {
		// Cache an empty result in case of a failure
		// and retry on next update check.
		$result = [];
	}

	$translations->{$slug}       = $result;
	$translations->_last_checked = time();

	set_site_transient( $transient_key, $translations );

	return $result;
}

/**
 * Sanitizes a date string.
 *
 * DateTime fails to parse date strings that contain brackets, such as
 * “Tue Dec 22 2015 12:52:19 GMT+0100 (West-Europa)”, which appears in
 * PO-Revision-Date headers. Sanitization ensures such date headers are
 * parsed correctly into DateTime instances.
 *
 * @since 2.1.0
 *
 * @param string $date_string The date string to sanitize.
 * @return \DateTime Date from string if parsable, otherwise the Unix epoch.
 */
function sanitize_date( $date_string ) {
	$date_and_timezone = explode( '(', $date_string );
	$date_no_timezone  = trim( $date_and_timezone[0] );

	try {
		$date = new DateTime( $date_no_timezone );
	} catch ( \Exception $e ) {
		return new DateTime( '1970-01-01' );
	}

	return $date;
}

/**
 * Gets the installed translations.
 *
 * Results are cached.
 *
 * @since 2.2.0
 *
 * @see wp_get_installed_translations()
 *
 * @param string $type Project type. Either plugin or theme.
 * @return array Translation data.
 */
function get_installed_translations( $type ) {
	static $cache = [];

	if ( ! isset( $cache[ $type ] ) ) {
		$cache[ $type ] = wp_get_installed_translations( $type . 's' );
	}

	return $cache[ $type ];
}

/**
 * Gets all available and installed locales.
 *
 * Results are cached.
 *
 * @since 2.2.0
 *
 * @see get_available_languages()
 *
 * @return array List of installed locales.
 */
function get_available_locales() {
	static $cache = null;

	if ( ! isset( $cache ) ) {
		$cache = array_values( get_available_languages() );
	}

	return $cache;
}
