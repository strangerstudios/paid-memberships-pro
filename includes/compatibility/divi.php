<?php

class PMProDivi{

	function __construct(){

		if ( empty( $_GET['page'] ) || 'et_divi_role_editor' !== $_GET['page'] ) {
			add_filter( 'et_builder_get_parent_modules', array( __CLASS__, 'toggle' ) );
			add_filter( 'et_pb_module_content', array( __CLASS__, 'restrict_content' ), 10, 4 );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( __CLASS__, 'row_settings' ) );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', array( __CLASS__, 'row_settings' ) );			
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
       
        $settings['pmpro_enable'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Enable Paid Memberships Pro module visibility?', 'paid-memberships-pro' ),
			'type' => 'yes_no_button',
			'options' => array(
				'off' => __( 'No', 'paid-memberships-pro' ),
				'on' => __( 'Yes', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
		);

        $settings['pmpro_visibility'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Content Visibility', 'paid-memberships-pro' ),
			'type' => 'select',
			'options' => array(
				'hide' => __( 'Hide', 'paid-memberships-pro' ),
				'show' => __( 'Show', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
			),
		);

        $settings['pmpro_segment'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Show Content To', 'paid-memberships-pro' ),			
			'type' => 'select',
			'options' => array(
				'all' => __( 'All Members', 'paid-memberships-pro' ),
				'specific' => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                'logged_in' => __( 'Logged-In Users', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
                'pmpro_visibility' => 'show',
			),
		);

        $settings['pmpro_segment_hide'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Hide Content From', 'paid-memberships-pro' ),
			'type' => 'select',
			'options' => array(
				'all' => __( 'All Members', 'paid-memberships-pro' ),
				'specific' => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                'logged_in' => __( 'Logged-In Users', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
                'pmpro_visibility' => 'hide',
			),
		);

		$settings['paid-memberships-pro'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Show module for specific levels', 'paid-memberships-pro' ),
			'description' => __( 'Enter comma-separated level IDs.', 'paid-memberships-pro' ),
			'type' => 'text',
			'default' => '',
			'option_category' => 'configuration',
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
                'pmpro_segment' => 'specific',
			),
	    );

        $settings['paid-memberships-pro-hide'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Hide module for specific levels', 'paid-memberships-pro' ),
			'description' => __( 'Enter comma-separated level IDs.', 'paid-memberships-pro' ),
			'type' => 'text',
			'default' => '',
			'option_category' => 'configuration',
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
                'pmpro_segment_hide' => 'specific',
			),
	    );

		$settings['pmpro_show_no_access_message'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Show no access message', 'paid-memberships-pro' ),
			'description' => __( 'Displays a no access message to non-members.', 'paid-memberships-pro' ),
			'type' => 'yes_no_button',
			'options' => array(
				'off' => __( 'No', 'paid-memberships-pro' ),
				'on' => __( 'Yes', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
			),
		);

		return $settings;

	}
  
  	public static function restrict_content( $output, $props, $attrs, $slug ) {

	    if ( et_fb_is_enabled() ) {
			return $output;
	    }
        
        // if ( 'et_pb_row' !== $slug || 'et_pb_section' !== $slug ) {
        //     return $output;
        // }

	    if( !isset( $props['paid-memberships-pro'] ) ){
	    	return $output;
	    }
        

        if( ! isset( $props['pmpro_enable'] ) || 'on' !== $props['pmpro_enable'] ){
        	return $output;
        }

        if( isset( $props['pmpro_visibility'] ) && 'show' === $props['pmpro_visibility'] ){

            if( isset( $props['pmpro_segment'] ) && 'all' === $props['pmpro_segment'] ){
                
                if( pmpro_hasMembershipLevel() ){
                    return $output;
                } else {
                    if( isset( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ){
                        return pmpro_get_no_access_message( NULL, array() );
                    } else {
                        return '';
                    }
                }
                
            }

            if( isset( $props['pmpro_segment'] ) && 'specific' === $props['pmpro_segment'] ){
                
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
                    if ( ! empty( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ) {
                        return pmpro_get_no_access_message( NULL, $levels );
                    } else {
                        return '';
                    }
                    
                }
                
            }

            if( isset( $props['pmpro_segment'] ) && 'logged_in' === $props['pmpro_segment'] ){
                
                if( is_user_logged_in() ){
                    return $output;
                } else {
                    if ( ! empty( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ) {
                        return pmpro_get_no_access_message( NULL, $levels );
                    } else {
                        return '';
                    }
                    
                }
                
            }

        } else {

            if( isset( $props['pmpro_segment_hide'] ) && 'all' === $props['pmpro_segment_hide'] ){
                
                if( ! pmpro_hasMembershipLevel() ){
                    return $output;
                } else {
                    if( isset( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ){
                        return pmpro_get_no_access_message( NULL, array());
                    } else {
                        return '';
                    }
                }
                
            }

            if( isset( $props['pmpro_segment_hide'] ) && 'specific' === $props['pmpro_segment_hide'] ){
                
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

                if( ! pmpro_hasMembershipLevel( $levels ) ){
                    return $output;
                } else {
                    if ( ! empty( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ) {
                        return pmpro_get_no_access_message( NULL, $levels );
                    } else {
                        return '';
                    }
                    
                }
                
            }

            if( isset( $props['pmpro_segment_hide'] ) && 'logged_in' === $props['pmpro_segment_hide'] ){
                
                if( ! is_user_logged_in() ){
                    return $output;
                } else {
                    if ( ! empty( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ) {
                        return pmpro_get_no_access_message( NULL, $levels );
                    } else {
                        return '';
                    }
                    
                }
                
            }

            return $output;

        }

        return $output;

	}
	
	/**
	 * Filter the element classes added to the no_access messages for improved appearance in Divi.
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