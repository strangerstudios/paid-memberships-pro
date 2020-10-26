<?php

class PMProDivi{

	function __construct(){

		if ( empty( $_GET['page'] ) || 'et_divi_role_editor' !== $_GET['page'] ) {
			add_filter( 'et_builder_get_parent_modules', array( $this, 'toggle' ) );
			add_filter( 'et_pb_module_content', array( $this, 'restrict_content' ), 10, 4 );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( $this, 'row_settings' ) );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', array( $this, 'section_settings' ) );			
		}

	}

	public function toggle( $modules ) {

		if ( ! empty( $modules ) && is_object( $modules['et_pb_row'] ) ) {
			$modules['et_pb_row']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'pmpro' );
		}

		if ( ! empty( $modules ) && is_object( $modules['et_pb_section'] ) ) {
			$modules['et_pb_section']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'pmpro' );
		}

		return $modules;

	}

	public function row_settings( $settings ) {

	    $settings['paid-memberships-pro'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Paid Memberships Pro Level', 'pmpro' ),
			'description' => __( 'Select a level to restrict member content.', 'pmpro' ),
			'type' => 'multiple_checkboxes',
			'multi_selection' => true,
			'default' => 'none',
			'option_category' => 'configuration',
			'options' => $this->return_levels( $settings ),
			'toggle_slug' => 'paid-memberships-pro',
	    );

		return $settings;

	}

	public function section_settings( $settings ) {

	    $settings['paid-memberships-pro'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Paid Memberships Pro Level', 'pmpro' ),
			'description' => __( 'Select a level to restrict member content.', 'pmpro' ),
			'type' => 'multiple_checkboxes',
			'multi_selection' => true,
			'default' => 'none',
			'option_category' => 'configuration',
			'options' => $this->return_levels( $settings ),
			'toggle_slug' => 'paid-memberships-pro',
	    );

		return $settings;

	}
  
  	public function restrict_content( $output, $props, $attrs, $slug ) {

	    if ( et_fb_is_enabled() ) {
			return $output;
	    }

	    if( 'et_pb_row' !== $slug || 'et_pb_section' !== $slug ){
	    	return $output; //Show content unless it's a section or row
	    }

	    if( !isset( $props['paid-memberships-pro'] ) ){
	    	return $output;
	    }

	    $levels = $this->pair_selected_levels( $props['paid-memberships-pro'] );

	    if( !empty( $levels ) && in_array( 0, $levels ) ){ //Non members included in the list
	    	return $output;
	    }
	    
	    if( pmpro_hasMembershipLevel( $levels ) ){
	    	return $output;
	    } else {
	    	return;
	    }

	    return $output;

	}

	function return_levels( $settings = null ){

		global $pmpro_levels;

	 	$rule_options = array(
			'0' => __( 'Non-Members', 'pmpro' )
	    );	    

	    if( !empty( $pmpro_levels ) ){
	    	foreach( $pmpro_levels as $level ){
				$rule_options[$level->id] = $level->name;
	    	}
	    }

	    $levels = apply_filters( 'pmpro_divi_return_levels', $rule_options, $settings );

	    return $levels;

	}

	function pair_selected_levels( $stored_values ){

		$pairs = array();

		if( !empty( $stored_values ) ){

			$stored_array = explode( "|", $stored_values );

			$levels = $this->return_levels();

			$count = 0;
			
			$size = count( $levels );

			if( !empty( $levels ) ){
				foreach( $levels as $key => $val ){

					if( $count <= $size ){ //Lets not exceed the limit of the array
						if( isset( $stored_array[$count] ) ){
							if( $stored_array[$count] == 'on' ){
								$pairs[] = $key;
							}
						}
					}
					$count++;
				}	
			}

		}

		return $pairs;

	}

}
new PMProDivi();