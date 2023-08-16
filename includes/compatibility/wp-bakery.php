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
 * Create a text field that allows you to enter in level ID's that can
 * be included/excluded for that element.
 */
function pmpro_wpbakery_levels_parameter() {
    
    return array(
        'type' => 'textfield',
        'heading' => 'Paid Memberships Pro',
        'param_name' => 'pmpro_levels',
        'value' => '',
        'description' => __( 'Enter a comma separated list of level ID\'s this element should so to. Set 0 to Non-Members.', 'paid-memberships-pro' ),        
    );

}

function pmpro_wpbakery_show_noaccess_message() {

    return array(
        'type' => 'dropdown',
        'heading' => 'Paid Memberships Pro - Show No Access Message',
        'param_name' => 'pmpro_no_access_message',
        'value' => array( 'Yes', 'No' ), //Does not accept an associative array, cannot be translated.
        'description' => __( 'Choose to show or hide the no access message when a member does not have the required membership level.', 'paid-memberships-pro' ),
    );

}

/**
 * To add the parameter to additional fields, reference:
 * vc_add_param( 'field_type', pmpro_wpbakery_levels_parameter() );
 */
vc_add_param( 'vc_column_text', pmpro_wpbakery_levels_parameter() ); //Text Field
vc_add_param( 'vc_column_text', pmpro_wpbakery_show_noaccess_message() ); //Text Field
