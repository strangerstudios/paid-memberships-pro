<?php

// Include custom settings to restrict Elementor widgets.
require_once( 'elementor/class-pmpro-elementor.php' );

/**
 * Elementor Compatibility
 */
function pmpro_elementor_compatibility() {
	// Remove the default the_content filter added to membership level descriptions and confirmation messages in PMPro.
	remove_filter( 'the_content', 'pmpro_level_description' );
	remove_filter( 'pmpro_level_description', 'pmpro_pmpro_level_description' );
	remove_filter( 'the_content', 'pmpro_confirmation_message' );
	remove_filter( 'pmpro_confirmation_message', 'pmpro_pmpro_confirmation_message' );
	
    // Filter members-only content later so that the builder's filters run before PMPro.
	remove_filter('the_content', 'pmpro_membership_content_filter', 5);
	add_filter('the_content', 'pmpro_membership_content_filter', 15);
}

/**
 * Get all available levels for elementor widget setting.
 * 
 * @since 2.2.6
 * 
 * @return array Associative array of level ID and name.
 */
function pmpro_elementor_get_all_levels() {

	$levels_array = get_transient( 'pmpro_elementor_levels_cache' );

	if ( empty( $levels_array ) ) {
		$all_levels = pmpro_getAllLevels( true, false );

		$levels_array = array();

		$levels_array[0] = __( 'Non-members', 'paid-memberships-pro' );
		foreach( $all_levels as $level ) {
			$levels_array[ $level->id ] = $level->name;
		}

		set_transient( 'pmpro_elementor_levels_cache', $levels_array, 1 * DAY_IN_SECONDS );
	}
	
	$levels_array = apply_filters( 'pmpro_elementor_levels_array', $levels_array );

	return $levels_array;
}
add_action( 'plugins_loaded', 'pmpro_elementor_compatibility', 15 );

/**
 * Delete the levels array caching whenever a new membership level is created or updated.
 *
 * @since 2.5.9.1
 * 
 * @param int $level_id The membership level ID that we are saving.
 */
function pmpro_elementor_clear_level_cache( $level_id ) {
	delete_transient( 'pmpro_elementor_levels_cache' );
}
add_action( 'pmpro_save_membership_level', 'pmpro_elementor_clear_level_cache' );


/**
 * Add compatibility for the Elementor Caching to avoid caching any dynamic content based on Paid Memberships Pro compatibility.
 *
 * @since 2.6.0
 * 
 * @param bool $is_dynamic_content Whether the content is dynamic or not.
 * @param array $element_raw_data The element's raw data.
 * @param object $element_instance The element's instance.
 * 
 * @return bool.
 */
function pmpro_elementor_is_dynamic_content( $is_dynamic_content, $element_raw_data, $element_instance ) {

	// If it's already dynamic content, bail.
	if ( $is_dynamic_content ) {
		return $is_dynamic_content;
	}

	// If we detect that PMPro enabled option is set, let's make it dynamic content.
	if ( isset( $element_raw_data['settings']['pmpro_enable'] ) && $element_raw_data['settings']['pmpro_enable'] ) {
		return true;
	}

	// Check Elementor text editor for 'pmpro' string.
	if ( ! empty( $element_raw_data['settings']['editor'] ) && str_contains( $element_raw_data['settings']['editor'], 'pmpro' ) ) {
		return true;
	}

	// Check if Elementor shortcode widget has a PMPro shortcode.
	if ( ! empty( $element_raw_data['settings']['shortcode'] ) && str_contains( $element_raw_data['settings']['shortcode'], 'pmpro' ) ) {
		return true;
	}

	return $is_dynamic_content;
}
add_filter( 'elementor/element/is_dynamic_content', 'pmpro_elementor_is_dynamic_content', 10, 3 );