<?php

/**
 * Shortcode to retrieve and display level information via shortcode.
 */

function pmpro_level_information_shortcode( $atts ) {

	global $wpdb;

	//default attributes
	extract( shortcode_atts( array(
		'id' => NULL,
		'fields' => NULL,
		'show_id' => true,
	), $atts ) );

	// check if $id is numeric or empty first.
	if ( empty( $id ) || !is_numeric( $id ) ) {
		return __( 'No level found, please use a correct ID.', 'paid-memberships-pro' );
	}

	$sqlQuery = $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE `id` = %d" , esc_attr($id) );

	$results = $wpdb->get_row( $sqlQuery, ARRAY_A );

	// Start the output of the content here, let's DIV this to make it customizable via CSS.
	$output .= '<div class="pmpro-level-information">';
	
	if ( $show_id === true || $show_id === 'true' ) {
		$output .= '<p id="pmpro_level_info_id">' . __( 'Level ID', 'paid-memberships-pro' ) . ' ' . $results['id'] . '</p>';
	}
	
	if ( !empty( $fields ) ){
		
		$fields_array = explode( ';', $fields );

		if ( !empty( $fields_array ) ) {

			for ( $i = 0; $i < count( $fields_array ); $i++ ){
				$fields_array[ $i ] = explode( ',', trim( $fields_array[ $i ] ) );
			}

			foreach ( $fields_array as $field ) {
				$output .= '<p id="pmpro_level_info_'. $field[1] . '">' . $field[0] . ' ' . $results[ $field[1] ] . '</p>';			
			}

		}
	}

	$output .= '</div>';

	return $output;

}

add_shortcode( 'pmpro_level_info', 'pmpro_level_information_shortcode' );