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
		add_action( 'et_pb_module_shortcode_attributes', array( __CLASS__, 'map_pmpro_divi_legacy_settings_on_page_load' ), 10, 3 );
	}

	/**
	 * Add "Paid Memberships Pro" toggle to Divi (row and section) settings modal.
	 *
	 * @since TBD
	 * 
	 * @param array $modules The Divi modules.
	 * @return array $modules The modified Divi modules.
	 */
	public static function toggle( $modules ) {

		if ( isset( $modules['et_pb_row'] ) && is_object( $modules['et_pb_row'] ) ) {
			$modules['et_pb_row']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'paid-memberships-pro' );
		}

		if ( isset( $modules['et_pb_section'] ) && is_object( $modules['et_pb_section'] ) ) {
			$modules['et_pb_section']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'paid-memberships-pro' );
		}

		return $modules;

	}

	/**
	 * Add settings to the Divi row and section settings modal for Paid Memberships Pro.
	 *
	 * @since TBD
	 * 
	 * @param array $settings The Divi module settings.
	 * @return array $settings The modified Divi module settings.
	 */
	public static function row_settings( $settings ) {
       
	
        $settings['pmpro_enable'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Enable Paid Memberships Pro content visibility?', 'paid-memberships-pro' ),
			'type' => 'yes_no_button',
			'options' => array(
				'off' => __( 'No', 'paid-memberships-pro' ),
				'on' => __( 'Yes', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
		);

        $settings['pmpro_invert_restrictions'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Content Visibility', 'paid-memberships-pro' ),
			'type' => 'select',
			'options' => array(
				'hide' => __( 'Hide content from...', 'paid-memberships-pro' ),
				'show' => __( 'Show content to...', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on',
			),
		);

        $settings['pmpro_segment'] = array(
			'tab_slug' => 'custom_css',
			'type' => 'select',
			'options' => array(
				'all' => __( 'All Members', 'paid-memberships-pro' ),
				'specific' => __( 'Specific Membership Levels', 'paid-memberships-pro' ),
                'logged_in' => __( 'Logged-In Users', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
            'show_if'          => array(
				'pmpro_enable' => 'on'
			),
		);

		$settings['pmpro_levels'] = array(
			'tab_slug' => 'custom_css',
			'label' => __( 'Membership Levels', 'paid-memberships-pro' ),
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

		$settings['pmpro_show_noaccess'] = array(
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
				'pmpro_invert_restrictions' => 'show'
			),
		);

		return $settings;

	}
	
	/**
	 * Restrict Divi content based on PMPro membership levels access.
	 *
	 * @since TBD
	 * 
	 * @param [type] $output
	 * @param [type] $props
	 * @param [type] $attrs
	 * @param [type] $slug
	 * 
	 * @return string $output Returns the output of the content, in some cases it may be a blank string.
	 */
	public static function restrict_content( $output, $props, $attrs, $slug ) {
		// Always allow content in Divi builder mode
		if ( et_fb_is_enabled() ) {
			return $output;
		}

		// Don't try to run this logic on anything besides a row or section since we only add restrictions to these modules.
		if ( $slug !== 'et_pb_row' && $slug !== 'et_pb_section' ) {
			return $output;
		}

		// If PMPro restriction isn’t turned on, let's migrate some options.
		if ( ! isset( $props['pmpro_enable'] ) || $props['pmpro_enable'] !== 'on' ) {
			return $output;
		}

		// Determine if we're in “show” or “hide” mode, defaulting to 'show'.
		$show_mode = $props['pmpro_invert_restrictions'] ?? 'show';
		
		// Pick the right segment key based on mode.
		$segment = $props['pmpro_segment'] ?? 'all';
	
		// If “specific” but no levels provided, just show content
		if ( $segment === 'specific' ) {
			$level_string = trim( $props['pmpro_levels'] ?? '' );
			
			// If no levels are provided, restrict to all levels
			if ( $level_string !== '0' && $level_string === '' ) {
				$levels = array();
			} else {
				// Build an array of level IDs from a comma-separated string
				$levels = strpos( $level_string, ',' ) !== false ? array_map( 'trim', explode( ',', $level_string ) ) : array( $level_string );
			}
		} else {
			$levels = array();
		}

		//Check access based on segment chosen.
		switch ( $segment ) {
			case 'all':
				$has_access = pmpro_hasMembershipLevel();
				break;
			case 'specific':
				$has_access = pmpro_hasMembershipLevel( $levels );
				break;
			case 'logged_in':
				$has_access = is_user_logged_in();
				break;
			default:
				return $output;
		}
		
		// If in “show” mode & they have access, OR in “hide” mode & they do NOT have access, show the content.
		if ( ( $show_mode == 'show' && $has_access ) || ( $show_mode == 'hide' && ! $has_access ) ) {
			return $output;
		}
		
		// Otherwise, they don’t have access—show message or nothing
		if ( isset( $props['pmpro_show_noaccess'] ) && $props['pmpro_show_noaccess'] === 'on' ) {
			return pmpro_get_no_access_message( null, $levels );
		}
		
		// In case we ever make it here, just return nothing. This means they don't have access?
		return '';
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

	/**
	 * Swap out any legacy settings that may happen on page load/frontend.
	 *
	 * @since TBD
	 * 
	 * @param [type] $props
	 * @param [type] $atts
	 * @param [type] $slugs
	 * @return array $props The shortcode properties with the mapped props.
	 */
	static function map_pmpro_divi_legacy_settings_on_page_load( $props, $atts, $slugs ) {
		if ( isset( $props['pmpro_enable'] ) || ! isset( $props['paid-memberships-pro'] ) ) {
			return $props;
		}
		
		// Do nothing
		if ( empty( $props['paid-memberships-pro'] ) ) {
			return $props;
		}
	
		// Let's convert to the new props based on the old props.
		$props['pmpro_enable'] = 'on';
		$props['pmpro_segment'] = 'specific';
		$props['pmpro_levels'] = $props['paid-memberships-pro'];
		$props['pmpro_invert_restrictions'] = 'show';
		$props['pmpro_show_noaccess'] = $props['pmpro_show_no_access_message'] ?? 'off';
		
		return $props;
	}
	
}
new PMProDivi();
