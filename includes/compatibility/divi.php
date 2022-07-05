<?php

class PMProDivi{

	function __construct(){

		if ( empty( $_GET['page'] ) || 'et_divi_role_editor' !== $_GET['page'] ) {
			add_filter( 'et_builder_get_parent_modules', array( __CLASS__, 'toggle' ) );
			add_filter( 'et_pb_module_content', array( __CLASS__, 'restrict_content' ), 10, 4 );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( __CLASS__, 'row_settings' ) );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', array( __CLASS__, 'section_settings' ) );			
		}
		
		add_action( 'pmpro_element_class', array( __CLASS__, 'pmpro_element_class' ), 10, 2 );
	}

	public static function toggle( $modules ) {

		if ( isset( $modules['et_pb_row'] ) && is_object( $modules['et_pb_row'] ) ) {
			$modules['et_pb_row']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'paid-memberships-pro' );
		}

		if ( isset( $modules['et_pb_section'] ) && is_object( $modules['et_pb_section'] ) ) {
			$modules['et_pb_section']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'paid-memberships-pro' );
		}

		return $modules;

	}

	public static function row_settings( $settings ) {

		$settings['paid-memberships-pro'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Restrict Row by Level', 'paid-memberships-pro' ),
			'description' => __( 'Enter comma-separated level IDs.', 'paid-memberships-pro' ),
			'type' => 'text',
			'default' => '',
			'option_category' => 'configuration',
			'toggle_slug' => 'paid-memberships-pro',
	    );

		return $settings;

	}

	public static function section_settings( $settings ) {

	    $settings['paid-memberships-pro'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Restrict Section by Level', 'paid-memberships-pro' ),
			'description' => __( 'Enter comma-separated level IDs.', 'paid-memberships-pro' ),
			'type' => 'text',
			'default' => '',
			'option_category' => 'configuration',
			'toggle_slug' => 'paid-memberships-pro',
	    );

		return $settings;

	}
  
  	public static function restrict_content( $output, $props, $attrs, $slug ) {

	    if ( et_fb_is_enabled() ) {
			return $output;
	    }

	    if( !isset( $props['paid-memberships-pro'] ) ){
	    	return $output;
	    }
		
		$level = $props['paid-memberships-pro'];
		
		if ( empty( trim( $level ) ) || trim( $level ) === '0' ) {
			return $output;
		}
		
		if( strpos( $level, "," ) ) {
		   //they specified many levels
		   $levels = explode( ",", $level );
		} else {
		   //they specified just one level
		   $levels = array( $level );
		}

	    if( pmpro_hasMembershipLevel( $levels ) ){
	    	return $output;
	    } else {
	    	return '';
	    }
	}
	
	/**
	 * Filter the element classess added to the no_access messages for improved appearance in Divi.
	 * Hooked into pmpro_element_class.
	 * @since 2.8.2	 
	 */
	public static function pmpro_element_class( $class, $element ) {
		if ( in_array( 'pmpro_content_message', $class ) ) {
			$class[] = 'et_pb_row';
		}
		return $class;
	}
}
new PMProDivi();