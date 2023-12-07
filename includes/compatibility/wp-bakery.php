<?php
/**
 * WP Bakery Compatibility
 *
 * @since TBD
 */

/**
 * Set the VC directory to our compat folder
 * 
 */
vc_set_shortcodes_templates_dir( PMPRO_DIR . '/includes/compatibility/wp-bakery' );

/**
 * Create a text field that allows you to enter the level IDs that can see this content.
 */
function pmpro_wpbakery_levels_parameter() {
	return array(
		'type' => 'textfield',
		'heading' => 'Paid Memberships Pro - Require Membership Level',
		'param_name' => 'pmpro_levels',
		'value' => '',
		'description' => __( 'Enter comma-separated level IDs. Set to "0" to show this content for non-members.', 'paid-memberships-pro' ),
	);
}

function pmpro_wpbakery_show_noaccess_message() {
	return array(
		'type' => 'dropdown',
		'heading' => 'Paid Memberships Pro - Show No Access Message',
		'param_name' => 'pmpro_no_access_message',
		'value' => array( 'Yes', 'No' ), //Does not accept an associative array, cannot be translated.
		'description' => __( 'Displays a no access message to non-members.', 'paid-memberships-pro' ),
	);
}

/**
 * To add the parameter to additional fields, reference:
 * vc_add_param( 'field_type', pmpro_wpbakery_levels_parameter() );
 */
vc_add_param( 'vc_column_text', pmpro_wpbakery_levels_parameter() ); //Text Field
vc_add_param( 'vc_column_text', pmpro_wpbakery_show_noaccess_message() ); //Text Field

/**
 * Determines if content should be shown or not
 * 
 * @since TBD
 */
function pmpro_wpbakery_show_content( $output, $atts ) {

	// Don't run if we're in the VC editor.
	if ( vc_is_inline() ) {
		return $output;
	}

	// Return content if no levels are set.
	if ( empty( $atts['pmpro_levels'] ) ) {
		return $output;
	}

	// Get the levels that can see this content.
	$restricted_levels = $atts['pmpro_levels'];
	$levels_array = explode( ",", $restricted_levels );

	// Get the show no access message setting.
	$show_no_access_message = ( ! empty( $atts['pmpro_no_access_message'] ) ) ? $atts['pmpro_no_access_message'] : 'Yes';

	// Check if the current user has access to this content.
	if ( ! pmpro_hasMembershipLevel( $levels_array ) ) {
		$access = false;
	} else {
		$access = true;
	}

	// Allow filtering of the access.
	$access = apply_filters( 'pmpro_wpbakery_has_access', $access, $output, $levels_array, $atts );

	// Show the content or not.
	if ( ! $access ) {
		// User doesn't have access. Don't show the content.
		// Optionally show the no access message.
		if ( $show_no_access_message === 'Yes' ) {
			return pmpro_get_no_access_message( NULL, $levels_array );
		}
	} else {
		// User has access. Show the content.
		return $output;
	}

}
