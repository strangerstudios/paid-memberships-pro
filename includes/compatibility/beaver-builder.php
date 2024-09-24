<?php 
/** 
 * Beaver Builder Compatibility
 */
function pmpro_beaver_builder_compatibility() {
	// Filter members-only content later so that the builder's filters run before PMPro.
	remove_filter('the_content', 'pmpro_membership_content_filter', 5);
	add_filter('the_content', 'pmpro_membership_content_filter', 15);
}
add_action( 'init', 'pmpro_beaver_builder_compatibility' );

/**
 * Add PMPro to row settings.
 *
 * @param array  $form Row form settings.
 * @param string $id The node/row ID.
 *
 * @return array Updated form settings.
 */
function pmpro_beaver_builder_settings_form( $form, $id ) {
	if ( 'row' !== $id ) {
		return $form;
	}
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return $form;
	}
	global $membership_levels;
	$levels = array();
	$levels[0] = __( 'Non-members', 'paid-memberships-pro' );
	foreach ( $membership_levels as $level ) {
		$levels[ $level->id ] = $level->name;
	}

	$row_settings_pmpro = array(
		'title'    => __( 'PMPro', 'paid-memberships-pro' ),
		'sections' => array(
			'paid-memberships-pro' => array(
				'title'  => __( 'General', 'paid-memberships-pro' ),
				'fields' => array(
					'pmpro_enable'      => array(
						'type'    => 'select',
						'label'   => __( 'Enable Paid Memberships Pro module visibility?', 'paid-memberships-pro' ),
						'options' => array(
							'yes' => __( 'Yes', 'paid-memberships-pro' ),
							'no'  => __( 'No', 'paid-memberships-pro' ),
						),
						'default' => 'no',
						'toggle'  => array(
							'yes' => array(
								'fields' => array(
									'visibility',
                                    'pmpro_segment',
                                    'pmpro_memberships',
                                    'pmpro_memberships_hide',
                                    'pmpro_no_access'
								),
							),
						),
					),
                    'visibility'      => array(
						'type'    => 'select',
						'label'   => __( 'Content Visibility', 'paid-memberships-pro' ),
						'options' => array(
							'show' => __( 'Show', 'paid-memberships-pro' ),
							'hide'  => __( 'Hide', 'paid-memberships-pro' ),
						),
						'default' => 'show',
						'toggle'  => array(
							'show' => array(
								'fields' => array(
									'pmpro_segment',
                                    'pmpro_memberships',
								),
							),
                            'hide' => array(
                                'fields' => array(
                                    'pmpro_segment_hide',
                                    'pmpro_memberships_hide',
                                ),
                            )
						),
					),     
                    'pmpro_segment' => array(
						'type'    => 'select',
						'label'   => __( 'Show Content To', 'paid-memberships-pro' ),
						'options' => array(
							'all' => __( 'All Members', 'paid-memberships-pro' ),
							'specific'  => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                            'logged_in'  => __( 'Logged-In Users', 'paid-memberships-pro' ),
						),
						'default' => 'all',
						'toggle'  => array(
							'specific' => array(
								'fields' => array(
									'pmpro_memberships',
								),
							),
                            
						),
					),    
                    'pmpro_segment_hide' => array(
						'type'    => 'select',
						'label'   => __( 'Hide Content From', 'paid-memberships-pro' ),
						'options' => array(
							'all' => __( 'All Members', 'paid-memberships-pro' ),
							'specific'  => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                            'logged_in'  => __( 'Logged-In Users', 'paid-memberships-pro' ),
						),
						'default' => 'all',
						'toggle'  => array(
							'specific' => array(
								'fields' => array(
									'pmpro_memberships_hide',
								),
							),
                            
						),
					),                      
					'pmpro_memberships' => array(
						'label'        => __( 'Show module for selected levels', 'paid-memberships-pro' ),
						'type'         => 'select',
						'options'      => $levels,
						'multi-select' => true,
					),
                    'pmpro_memberships_hide' => array(
                        'label'        => __( 'Hide module for selected levels', 'paid-memberships-pro' ),
                        'type'         => 'select',
                        'options'      => $levels,
                        'multi-select' => true,
                    ),
                    'pmpro_no_access'      => array(
						'type'    => 'select',
						'label'   => __( 'Show no access', 'paid-memberships-pro' ),
						'options' => array(
							'yes' => __( 'Yes', 'paid-memberships-pro' ),
							'no'  => __( 'No', 'paid-memberships-pro' ),
						),
						'default' => 'no',
						
					),
				),
			),
		),
	);

	$form['tabs'] = array_merge(
		array_slice( $form['tabs'], 0, 2 ),
		array( 'PMPro' => $row_settings_pmpro ),
		array_slice( $form['tabs'], 2 )
	);
	return $form;
}
add_filter( 'fl_builder_register_settings_form', 'pmpro_beaver_builder_settings_form', 10, 2 );

/**
 * Determine if the node (row/module) should be visible based on membership level.
 *
 * @param bool   $is_visible Whether the module/row is visible.
 * @param object $node The node type.
 *
 * @return bool True if visible, false if not.
 */
function pmpro_beaver_builder_check_field_connections( $is_visible, $node ) {

	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return $is_visible;
	}
    
	if ( isset( $node->settings->pmpro_enable ) && 'yes' === $node->settings->pmpro_enable ) { 

        if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
            return true;
        }
        
        if( isset( $node->settings->pmpro_segment ) && 'all' === $node->settings->pmpro_segment ) {

            if( isset( $node->settings->visibility ) && 'show' === $node->settings->visibility ){
                //Show to members
                if ( pmpro_hasMembershipLevel() ) {                
                    return true;
                }
            } else {
                //Hide from members
                if ( ! pmpro_hasMembershipLevel() ) {                                                  
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        return true;
                    }
                    return false;
                }
            }

            
        }
        
        if( isset( $node->settings->pmpro_segment ) && 'specific' === $node->settings->pmpro_segment ) {   
            
            if( isset( $node->settings->visibility ) && 'show' === $node->settings->visibility ){
                //Show to members
                if ( pmpro_hasMembershipLevel( $node->settings->pmpro_memberships ) ) {
                    return true;
                }
            } else {
                //Hide from members
                if ( ! pmpro_hasMembershipLevel( $node->settings->pmpro_memberships ) ) {                    
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        return true;
                    }
                    return false;
                }
            }

            
        }

        if( isset( $node->settings->pmpro_segment ) && 'logged_in' === $node->settings->pmpro_segment ) {

            if( isset( $node->settings->visibility ) && 'show' === $node->settings->visibility ){
                if( is_user_logged_in() ) {
                    return true;
                }             
            } else {
                if( ! is_user_logged_in() ) {                    
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        return true;
                    }
                    return false;
                }
            }

            
        }
        
	}

	return $is_visible;
}
add_filter( 'fl_builder_is_node_visible', 'pmpro_beaver_builder_check_field_connections', 200, 2 );

/**
 * Show content based on membership level, if enabled, and if we should show or hide a message
 *
 * @param string $content The content to show.
 * @param object $node The node type.
 *
 * @return string The content to show.
 */
function pmpro_beaver_builder_show_content( $content, $node ) {
    
    if ( isset( $node->settings->pmpro_enable ) && 'yes' === $node->settings->pmpro_enable ) {
        
        if( isset( $node->settings->pmpro_segment ) && 'all' === $node->settings->pmpro_segment ) {
            
            if( isset( $node->settings->visibility ) && 'show' === $node->settings->visibility ){

                if ( ! pmpro_hasMembershipLevel() ) {                
                              
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        $content = '';
                        return pmpro_get_no_access_message( $content, array() );
                    }
                    
                }

            } else {

                if ( pmpro_hasMembershipLevel() ) {                
                              
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        $content = '';
                        return pmpro_get_no_access_message( $content, array() );
                    }
                    
                }

            }
            
        }
        
        if( isset( $node->settings->pmpro_segment ) && 'specific' === $node->settings->pmpro_segment ) {            
            
            if( isset( $node->settings->visibility ) && 'show' === $node->settings->visibility ){

                if ( ! pmpro_hasMembershipLevel( $node->settings->pmpro_memberships ) ) {
               
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        $content = '';
                        return pmpro_get_no_access_message( $content, $node->settings->pmpro_memberships );
                    }
                    
                }

            } else {

                if ( pmpro_hasMembershipLevel( $node->settings->pmpro_memberships ) ) {
               
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        $content = '';
                        return pmpro_get_no_access_message( $content, $node->settings->pmpro_memberships );
                    }
                    
                }

            }
            
        }

        if( isset( $node->settings->pmpro_segment ) && 'logged_in' === $node->settings->pmpro_segment ) {
            
            if( isset( $node->settings->visibility ) && 'show' === $node->settings->visibility ){

                if( ! is_user_logged_in() ) {
                
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        $content = '';
                        return pmpro_get_no_access_message( $content, array() );
                    }
                    
                }

            } else {

                if( is_user_logged_in() ) {
                
                    if( isset( $node->settings->pmpro_no_access ) && 'yes' === $node->settings->pmpro_no_access ) {
                        $content = '';
                        return pmpro_get_no_access_message( $content, array() );
                    }
                    
                }

            }
            
            
        }
        
	}
    
    return $content;

}


add_filter( 'fl_builder_render_module_content', 'pmpro_beaver_builder_show_content', 10, 2 );

/**
 * Add PMPro to all modules in Beaver Builder
 *
 * @param array  $form The form to add a custom tab for.
 * @param string $slug The module slug.
 *
 * @return array The updated form array.
 */
function pmpro_beaver_builder_add_custom_tab_all_modules( $form, $slug ) {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return $form;
	}
	$modules = FLBuilderModel::get_enabled_modules(); // * getting all active modules slug

	if ( in_array( $slug, $modules, true ) ) {
		global $membership_levels;
		$levels = array();
		$levels[0] = __( 'Non-members', 'paid-memberships-pro' );
		foreach ( $membership_levels as $level ) {
			$levels[ $level->id ] = $level->name;
		}
		$form['pmpro-bb'] = array(
			'title'    => __( 'PMPro', 'paid-memberships-pro' ),
			'sections' => array(
			'paid-memberships-pro' => array(
				'title'  => __( 'General', 'paid-memberships-pro' ),
				'fields' => array(
					'pmpro_enable'      => array(
						'type'    => 'select',
						'label'   => __( 'Enable Paid Memberships Pro module visibility?', 'paid-memberships-pro' ),
						'options' => array(
							'yes' => __( 'Yes', 'paid-memberships-pro' ),
							'no'  => __( 'No', 'paid-memberships-pro' ),
						),
						'default' => 'no',
						'toggle'  => array(
							'yes' => array(
								'fields' => array(
									'visibility',
                                    'pmpro_segment',
                                    'pmpro_memberships',
                                    'pmpro_memberships_hide',
                                    'pmpro_no_access'
								),
							),
						),
					),
                    'visibility'      => array(
						'type'    => 'select',
						'label'   => __( 'Content Visibility', 'paid-memberships-pro' ),
						'options' => array(
							'show' => __( 'Show', 'paid-memberships-pro' ),
							'hide'  => __( 'Hide', 'paid-memberships-pro' ),
						),
						'default' => 'show',
						'toggle'  => array(
							'show' => array(
								'fields' => array(
									'pmpro_segment',
                                    'pmpro_memberships',
								),
							),
                            'hide' => array(
                                'fields' => array(
                                    'pmpro_segment_hide',
                                    'pmpro_memberships_hide',
                                ),
                            )
						),
					),     
                    'pmpro_segment' => array(
						'type'    => 'select',
						'label'   => __( 'Show Content To', 'paid-memberships-pro' ),
						'options' => array(
							'all' => __( 'All Members', 'paid-memberships-pro' ),
							'specific'  => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                            'logged_in'  => __( 'Logged-In Users', 'paid-memberships-pro' ),
						),
						'default' => 'all',
						'toggle'  => array(
							'specific' => array(
								'fields' => array(
									'pmpro_memberships',
								),
							),
                            
						),
					),    
                    'pmpro_segment_hide' => array(
						'type'    => 'select',
						'label'   => __( 'Hide Content From', 'paid-memberships-pro' ),
						'options' => array(
							'all' => __( 'All Members', 'paid-memberships-pro' ),
							'specific'  => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                            'logged_in'  => __( 'Logged-In Users', 'paid-memberships-pro' ),
						),
						'default' => 'all',
						'toggle'  => array(
							'specific' => array(
								'fields' => array(
									'pmpro_memberships_hide',
								),
							),
                            
						),
					),                      
					'pmpro_memberships' => array(
						'label'        => __( 'Show module for selected levels', 'paid-memberships-pro' ),
						'type'         => 'select',
						'options'      => $levels,
						'multi-select' => true,
					),
                    'pmpro_memberships_hide' => array(
                        'label'        => __( 'Hide module for selected levels', 'paid-memberships-pro' ),
                        'type'         => 'select',
                        'options'      => $levels,
                        'multi-select' => true,
                    ),
                    'pmpro_no_access'      => array(
						'type'    => 'select',
						'label'   => __( 'Show no access message', 'paid-memberships-pro' ),
						'options' => array(
							'yes' => __( 'Yes', 'paid-memberships-pro' ),
							'no'  => __( 'No', 'paid-memberships-pro' ),
						),
						'default' => 'no',
						
					),
				),
			),
		),  
		);
	}

	return $form;
}
add_filter( 'fl_builder_register_settings_form', 'pmpro_beaver_builder_add_custom_tab_all_modules', 10, 2 );