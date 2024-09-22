<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class PMPro_Elementor_Content_Restriction extends PMPro_Elementor {
	protected function content_restriction() {
		// Setup controls
		$this->register_controls();

		// Filter elementor render_content hook
		add_action( 'elementor/widget/render_content', array( $this, 'pmpro_elementor_render_content' ), 10, 2 );
		add_action( 'elementor/frontend/section/should_render', array( $this, 'pmpro_elementor_should_render' ), 10, 2 );
		add_action( 'elementor/frontend/container/should_render', array( $this, 'pmpro_elementor_should_render' ), 10, 2 );

	}

	// Register controls to sections and widgets
	protected function register_controls() {
		foreach( $this->locations as $where ) {
            add_action('elementor/element/'.$where['element'].'/'.$this->section_name.'/before_section_end', array( $this, 'add_controls' ), 10, 2 );
		}
	}

	// Define controls
	public function add_controls( $element, $args ) {
        /**
         *  visibilityBlockEnabled
         *   segment - all, specific, logged_in
         *   levels
         *   show_noaccess
         */
		

        $element->add_control(
			'pmpro_enable', array(
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Enable Paid Memberships Pro module visibility?', 'textdomain' ),
				'options' => array(                
					'yes' => esc_html__( 'Yes', 'textdomain' ),
					'no' => esc_html__( 'No', 'textdomain' ),
                    
                ),
				'default' => 'no',
                
            )
		);

        $element->add_control(
			'pmpro_content_visibility', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'label' => esc_html__( 'Content Visbility', 'textdomain' ),
				'options' => array(                
					'show' => esc_html__( 'Show', 'textdomain' ),
					'hide' => esc_html__( 'Hide', 'textdomain' ),                    
                ),
				'default' => 'show',
                'condition' => [
                    'pmpro_enable' => 'yes',
                ],
            )
		);

        $element->add_control(
			'pmpro_show_content_to', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'label' => esc_html__( 'Show Content To', 'textdomain' ),
				'options' => array(                
					'all' => esc_html__( 'All Members', 'textdomain' ),
					'specific' => esc_html__( 'Specific Membership Levels', 'textdomain' ),
                    'logged_in' => esc_html__( 'Logged-In Users', 'textdomain' ),
                ),
				'default' => 'all',
                'condition' => [
                    'pmpro_content_visibility' => 'show',
                    'pmpro_enable' => 'yes',
                ],
            )
		);

        $element->add_control(
			'pmpro_hide_content_from', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'label' => esc_html__( 'Hide Content From', 'textdomain' ),
				'options' => array(                
					'all' => esc_html__( 'All Members', 'textdomain' ),
					'specific' => esc_html__( 'Specific Membership Levels', 'textdomain' ),
                    'logged_in' => esc_html__( 'Logged-In Users', 'textdomain' ),
                ),
				'default' => 'all',
                'condition' => [
                    'pmpro_content_visibility' => 'hide',
                    'pmpro_enable' => 'yes',
                ],
            )
		);
        
        $element->add_control(
            'pmpro_require_membership', array(
                'type'        => Controls_Manager::SELECT2,
                'options'     => pmpro_elementor_get_all_levels(),
                'multiple'    => 'true',
				'label_block' => 'true',
				'description' => __( 'Membership Levels', 'paid-memberships-pro' ),
                'condition' => [
                    'pmpro_show_content_to' => 'specific',    
                    'pmpro_hide_content_from' => 'specific',
                    'pmpro_enable' => 'yes',                
                ],
            )
        );

		// Only add this option to Widgets as we can replace the contents in widgets, not sections.
		if ( 'widget' === $element->get_type() ) {
			$element->add_control(
				'pmpro_no_access_message', array(
					'label' => esc_html__( 'Show no access message', 'paid-memberships-pro' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'paid-memberships-pro' ),
					'label_off' => esc_html__( 'No', 'paid-memberships-pro' ),
					'return_value' => 'yes',
					'default' => 'no',
                    'condition' => [
                        'pmpro_enable' => 'yes',
                    ],
				)
			);
		}

	}

	/**
	 * Filter sections to render content or not.
	 * If user doesn't have access, hide the section.
	 * @return boolean whether to show or hide section.
	 * @since 2.3
	 */
	public function pmpro_elementor_should_render( $should_render, $element ) {

		// Don't hide content in editor mode.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $should_render;
		}        
        
		// Bypass if it's already hidden.
		if ( $should_render === false ) {
			return $should_render;
		}

        $should_render = pmpro_elementor_has_access( $element );        
		
		return apply_filters( 'pmpro_elementor_section_access', $should_render, $element );
	}

	/**
	 * Filter individual content for members.
	 * @return string Returns the content set from Elementor.
	 * @since 2.0
	 */
	public function pmpro_elementor_render_content( $content, $widget ){

        // Don't hide content in editor mode.
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {            
            return $content;
        }
        
        // We can only use the no access message on a widget
        if ( 'widget' !== $widget->get_type() ) {
            return $content;
        }
        
        $widget_settings = $widget->get_active_settings();
        
        if( isset( $widget_settings['pmpro_enable'] ) && $widget_settings['pmpro_enable'] === 'yes' ) {

            if( isset( $widget_settings['pmpro_content_visibility'] ) && $widget_settings['pmpro_content_visibility'] === 'show' ) {
                
                if( isset( $widget_settings['pmpro_show_content_to'] ) && $widget_settings['pmpro_show_content_to'] === 'all' ) {
                    
                    if( pmpro_hasMembershipLevel() ) {
                        return $content;
                    } else {
                        if( isset( $widget_settings['pmpro_no_access_message'] ) && $widget_settings['pmpro_no_access_message'] === 'yes' ) {
                            return pmpro_get_no_access_message( NULL, array() );
                        } else {
                            return '';
                        }
                    }
                
                } else if( isset( $widget_settings['pmpro_show_content_to'] ) && $widget_settings['pmpro_show_content_to'] === 'specific' ) {
                     
                    if( pmpro_hasMembershipLevel( $widget_settings['pmpro_require_membership'] ) ) {
                        return $content;
                    } else {
                        if( isset( $widget_settings['pmpro_no_access_message'] ) && $widget_settings['pmpro_no_access_message'] === 'yes' ) {
                            return pmpro_get_no_access_message( NULL, $widget_settings['pmpro_require_membership'] );
                        } else {
                            return '';
                        }
                    }

                } else if( isset( $widget_settings['pmpro_show_content_to'] ) && $widget_settings['pmpro_show_content_to'] === 'logged_in' ) {
                    
                    if( is_user_logged_in() ) {
                        return $content;
                    } else {
                        if( isset( $widget_settings['pmpro_no_access_message'] ) && $widget_settings['pmpro_no_access_message'] === 'yes' ) {
                            return pmpro_get_no_access_message( NULL, array() );
                        } else {
                            return '';
                        }
                    }

                }
                
            } else {
                
                if( isset( $widget_settings['pmpro_hide_content_from'] ) && $widget_settings['pmpro_hide_content_from'] === 'all' ) {
                    
                    if( ! pmpro_hasMembershipLevel() ) {
                        return $content;
                    } else {
                        if( isset( $widget_settings['pmpro_no_access_message'] ) && $widget_settings['pmpro_no_access_message'] === 'yes' ) {
                            return pmpro_get_no_access_message( NULL, array() );
                        } else {
                            return '';
                        }
                    }
                
                } else if( isset( $widget_settings['pmpro_hide_content_from'] ) && $widget_settings['pmpro_hide_content_from'] === 'specific' ) {
                     
                    if( pmpro_hasMembershipLevel( $widget_settings['pmpro_require_membership'] ) ) {
                        return $content;
                    } else {
                        if( isset( $widget_settings['pmpro_no_access_message'] ) && $widget_settings['pmpro_no_access_message'] === 'yes' ) {
                            return pmpro_get_no_access_message( NULL, $widget_settings['pmpro_require_membership'] );
                        } else {
                            return '';
                        }
                    }

                } else if( isset( $widget_settings['pmpro_hide_content_from'] ) && $widget_settings['pmpro_hide_content_from'] === 'logged_in' ) {
                    
                    if( is_user_logged_in() ) {
                        return $content;
                    } else {
                        if( isset( $widget_settings['pmpro_no_access_message'] ) && $widget_settings['pmpro_no_access_message'] === 'yes' ) {
                            return pmpro_get_no_access_message( NULL, array() );
                        } else {
                            return '';
                        }
                    }

                }

            }
            
        }

        return $content;
	}

	/**
	 * Figure out if the user has access to restricted content.
	 * @return bool True or false based if the user has access to the content or not.
	 * @since 2.3
	 */
	public function pmpro_elementor_has_access( $element ) {

		$element_settings = $element->get_active_settings();
        
        if( isset( $element_settings['pmpro_no_access_message'] ) && $element_settings['pmpro_no_access_message'] === 'yes' ) {
            /**
             * If the element has the no access message enabled, 
             * we should always show the element. 
             */
            return true;
        } else {
            /**
             * If the element doesn't have the message enabled,
             * we should check the visibility settings first.
             */
            if( isset( $element_settings['pmpro_enable'] ) && $element_settings['pmpro_enable'] === 'yes' ) {

                if( isset( $element_settings['pmpro_content_visibility'] ) && $element_settings['pmpro_content_visibility'] === 'show' ) {
                    
                    if( isset( $element_settings['pmpro_show_content_to'] ) && $element_settings['pmpro_show_content_to'] === 'all' ) {
                        
                        $access = pmpro_hasMembershipLevel();
                    
                    } else if( isset( $element_settings['pmpro_show_content_to'] ) && $element_settings['pmpro_show_content_to'] === 'specific' ) {
                         
                        $access = pmpro_hasMembershipLevel( $element_settings['pmpro_require_membership'] );
    
                    } else if( isset( $element_settings['pmpro_show_content_to'] ) && $element_settings['pmpro_show_content_to'] === 'logged_in' ) {
                        
                        $access = is_user_logged_in();
    
                    }
                    
                } else if( isset( $element_settings['pmpro_content_visibility'] ) && $element_settings['pmpro_content_visibility'] === 'hide' ) {
                    
                    if( isset( $element_settings['pmpro_hide_content_from'] ) && $element_settings['pmpro_hide_content_from'] === 'all' ) {
                        
                        $access = ! pmpro_hasMembershipLevel();
                    
                    } else if( isset( $element_settings['pmpro_hide_content_from'] ) && $element_settings['pmpro_hide_content_from'] === 'specific' ) {
                        
                    
                        $access = ! pmpro_hasMembershipLevel( $element_settings['pmpro_require_membership'] );
    
                    } else if( isset( $element_settings['pmpro_hide_content_from'] ) && $element_settings['pmpro_hide_content_from'] === 'logged_in' ) {
                        
                        $access = ! is_user_logged_in();
    
                    }
                    
                } else {
                    $access = true;
                }
            }
        }
        
        
		return apply_filters( 'pmpro_elementor_has_access', $access, $element, $restricted_levels );
	}
}

new PMPro_Elementor_Content_Restriction;