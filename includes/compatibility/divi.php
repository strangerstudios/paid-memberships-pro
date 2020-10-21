<?php

class PMProDivi{

	function __construct(){

		if ( empty( $_GET['page'] ) || 'et_divi_role_editor' !== $_GET['page'] ) {
			add_filter( 'et_builder_main_tabs', array( $this, 'pmpro_tab' ) );
			add_filter( 'et_builder_get_parent_modules', array( $this, 'toggle' ) );
			add_filter( 'et_pb_module_content', array( $this, 'restrict_content' ), 10, 4 );
			add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( $this, 'row_settings' ) );
		}

	}

	public function pmpro_tab( $tabs ) {
		$tabs['pmpro_require_membership'] = 'Paid Memberships Pro';
		return $tabs;
	}

	public function toggle( $modules ) {

		// Add toggle to Rows
		if ( ! empty( $modules ) && is_object( $modules['et_pb_row'] ) ) {
			$modules['et_pb_row']->settings_modal_toggles['pmpro_require_membership'] = array(
				'toggles' => array(
					'pmpro_restrict_content' => array(
						'title' => __( 'Require Membership', 'pmpro' ),
						'priority' => 100
					)
				)
			);
		}

		return $modules;
	}

  
  	public function restrict_content( $output, $props, $attrs, $slug ) {

	    if ( et_fb_is_enabled() ) {
			return $output;
	    }

	    if ( 'et_pb_row' !== $slug ) {
			return $output;
	    }

	    $level = isset( $props['pmpro_require_membership'] ) ? intval( $props['pmpro_require_membership'] ) : false;

	    // Not set to protect
	    if ( false === $level || 'none' === $level ) { //None indicates didn't save as well
			return $output;
	    }
	    
	    if( pmpro_hasMembershipLevel( $level ) ){
	    	return $output;
	    } else {
	    	return;
	    }

	    return $output;

	}

	public function row_settings( $settings ) {

	    $rule_options = array(
			'0' => __( 'Non-Members', 'pmpro' )
	    );

	    global $pmpro_levels;

	    if( !empty( $pmpro_levels ) ){
	    	foreach( $pmpro_levels as $level ){
				$rule_options[$level->id] = $level->name;
	    	}
	    }

	    $settings['pmpro_require_membership'] = array(
			'tab_slug' => 'pmpro_require_membership',
			'label' => __( 'Paid Memberships Pro Level', 'pmpro' ),
			'description' => __( 'Select a level to restrict member content.', 'pmpro' ),
			'type' => 'select',
			'default' => 'none',
			'option_category' => 'configuration',
			'options' => $rule_options,
			'toggle_slug' => 'pmpro_restrict_content',
	    );

		return $settings;

	}	

}
new PMProDivi();