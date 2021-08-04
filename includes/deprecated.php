<?php
/**
 * Deprecated hooks, filters and functions
 *
 * @since  2.0
 */

/**
 * Check for deprecated filters.
 */
function pmpro_init_check_for_deprecated_filters() {
	global $wp_filter;
	
	$pmpro_map_deprecated_filters = array(
		'pmpro_getfile_extension_blocklist'    => 'pmpro_getfile_extension_blacklist',
		'pmprorh_section_header'			   => 'pmpro_default_field_group_label',
	);
	
	foreach ( $pmpro_map_deprecated_filters as $new => $old ) {
		if ( has_filter( $old ) ) {
			/* translators: 1: the old hook name, 2: the new or replacement hook name */
			trigger_error( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro. Please use the %2$s hook instead.', 'paid-memberships-pro' ), $old, $new ) );
			
			// Add filters back using the new tag.
			foreach( $wp_filter[$old]->callbacks as $priority => $callbacks ) {
				foreach( $callbacks as $callback ) {
					add_filter( $new, $callback['function'], $priority, $callback['accepted_args'] ); 
				}
			}
		}
	}
}
add_action( 'init', 'pmpro_init_check_for_deprecated_filters', 99 );

/**
 * Previously used function for class definitions for input fields to see if there was an error.
 *
 * To filter field values, we now recommend using the `pmpro_element_class` filter.
 *
 */
function pmpro_getClassForField( $field ) {
	return pmpro_get_element_class( '', $field );
}

/**
 * Redirect some old menu items to their new location
 */
function pmpro_admin_init_redirect_old_menu_items() {	
	if ( is_admin()
		&& ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro_license_settings'
		&& basename( $_SERVER['SCRIPT_NAME'] ) == 'options-general.php' ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pmpro-license' ) );
		exit;
	}
}
add_action( 'init', 'pmpro_admin_init_redirect_old_menu_items' );

/**
 * Old Register Helper functions and classes.
 */
function pmpro_register_helper_deprecated() {
	// PMProRH_Field class
	if ( ! class_exists( 'PMProRH_Field' ) ) {
		class PMProRH_Field extends PMPro_Field {
			// Just do what PMPro_Field does.
		}
	}
	
	// pmprorh_add_registration_field function
	if ( ! function_exists( 'pmprorh_add_registration_field' ) ) {
		function pmprorh_add_registration_field( $where, $field ) {
			return pmpro_add_user_field( $where, $field );
		}
	}
	
	// pmprorh_add_checkout_box function
	if ( ! function_exists( 'pmprorh_add_checkout_box' ) ) {
		function pmprorh_add_checkout_box( $name, $label = NULL, $description = '', $order = NULL ) {
			return pmpro_add_field_group( $name, $label, $description, $order );
		}
	}
	
	// pmprorh_getCheckoutBoxByName function
	if ( ! function_exists( 'pmprorh_getCheckoutBoxByName' ) ) {
		function pmprorh_getCheckoutBoxByName( $name ) {
			return pmpro_get_field_group_by_name( $name );
		}
	}
	
	// pmprorh_getCSVFields function
	if ( ! function_exists( 'pmprorh_getCSVFields' ) ) {
		function pmprorh_getCSVFields() {
			return pmpro_get_user_fields_for_csv();
		}
	}
	
	// pmprorh_getProfileFields function
	if ( ! function_exists( 'pmprorh_getProfileFields' ) ) {
		function pmprorh_getProfileFields( $user_id, $withlocations = false  ) {
			return pmpro_get_user_fields_for_profile( $user_id, $withlocations );
		}
	}
	
	// pmprorh_checkFieldForLevel function
	if ( ! function_exists( 'pmprorh_checkFieldForLevel' ) ) {
		function pmprorh_checkFieldForLevel( $field, $scope = 'default', $args = NULL ) {
			return pmpro_check_field_for_level( $field, $scope, $args );
		}
	}
}
add_action( 'plugins_loaded', 'pmpro_register_helper_deprecated', 20 );
