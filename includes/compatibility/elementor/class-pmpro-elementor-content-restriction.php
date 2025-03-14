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

		// Migrate settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
		add_action( 'wp', array( $this, 'migrate_settings' ) ); // Migrate on frontend (also runs when editing but too late).
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'migrate_settings' ) ); // Migrate on editor load.

	}

	// Register controls to sections and widgets
	protected function register_controls() {
		foreach( $this->locations as $where ) {
            add_action('elementor/element/'.$where['element'].'/'.$this->section_name.'/before_section_end', array( $this, 'add_controls' ), 10, 2 );
		}
	}

	// Define controls
	public function add_controls( $element, $args ) {
        $element->add_control(
			'pmpro_enable', array(
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Enable Paid Memberships Pro module visibility?', 'textdomain' ),
				'default' => 'no',
            )
		);

        $element->add_control(
			'pmpro_invert_restrictions', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(                
					'0' => esc_html__( 'Show content to...', 'textdomain' ),
					'1' => esc_html__( 'Hide content from...', 'textdomain' ),                    
                ),
                'label_block' => 'true',
				'default' => '0',
                'condition' => [
                    'pmpro_enable' => 'yes',
                ],
            )
		);

        $element->add_control(
			'pmpro_segment', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(                
					'all' => esc_html__( 'All Members', 'textdomain' ),
					'specific' => esc_html__( 'Specific Membership Levels', 'textdomain' ),
                    'logged_in' => esc_html__( 'Logged-In Users', 'textdomain' ),
                ),
                'label_block' => 'true',
				'default' => 'all',
                'condition' => [
                    'pmpro_enable' => 'yes',
                ],
            )
		);
        
        $element->add_control(
            'pmpro_levels', array(
                'type'        => Controls_Manager::SELECT2,
                'options'     => pmpro_elementor_get_all_levels(),
                'multiple'    => 'true',
				'label' => __( 'Membership Levels', 'paid-memberships-pro' ),
                'condition' => [
                    'pmpro_segment' => 'specific',    
                    'pmpro_enable' => 'yes',                
                ],
            )
        );

		// Only add this option to Widgets as we can replace the contents in widgets, not sections.
		if ( 'widget' === $element->get_type() ) {
			$element->add_control(
				'pmpro_show_noaccess', array(
					'label' => esc_html__( 'Show no access message', 'paid-memberships-pro' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default' => 'no',
                    'condition' => [
                        'pmpro_enable' => 'yes',
                        'pmpro_invert_restrictions' => '0',
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

        $element_settings = $element->get_active_settings();

        // If the block is not being restricted, then the user should be able to view it.
        if ( empty( $element_settings['pmpro_enable'] ) || 'no' === $element_settings['pmpro_enable'] ) {
            return true;
        }

        $apply_block_visibility_params = array(
            'segment' => $element_settings['pmpro_segment'],
            'levels' => $element_settings['pmpro_levels'],
            'invert_restrictions' => $element_settings['pmpro_invert_restrictions'],
            'show_noaccess' => ! empty( $element_settings['pmpro_show_noaccess'] ),
        );
        $should_render = ! empty( pmpro_apply_block_visibility( $apply_block_visibility_params, 'sample content' ) );
		
		return apply_filters_deprecated( 'pmpro_elementor_section_access', array( $should_render, $element ), 'TBD' );
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

        // If the block is not being restricted, bail.
        if ( empty( $widget_settings['pmpro_enable'] ) || 'no' === $widget_settings['pmpro_enable'] ) {
            return $content;
        }

        // Use the pmpro_apply_block_visibility() method to generate output.
        $apply_block_visibility_params = array(
            'segment' => $widget_settings['pmpro_segment'],
            'levels' => $widget_settings['pmpro_levels'],
            'invert_restrictions' => $widget_settings['pmpro_invert_restrictions'],
            'show_noaccess' => $widget_settings['pmpro_show_noaccess'],
        );
        return pmpro_apply_block_visibility( $apply_block_visibility_params, $content );

	}

	/**
	 * Figure out if the user has access to restricted content.
	 * @return bool True or false based if the user has access to the content or not.
	 * @since 2.3
     * @deprecated TBD
	 */
	public function pmpro_elementor_has_access( $element ) {
        _deprecated_function( __METHOD__, 'TBD' );

		$element_settings = $element->get_active_settings();

        // If the block is not being restricted, then the user has access.
        if ( empty( $element_settings['pmpro_enable'] ) || 'no' === $element_settings['pmpro_enable'] ) {
            return true;
        }

        // If pmpro_apply_block_visibility returns content, then we want the user to see it.
        $apply_block_visibility_params = array(
            'segment' => $element_settings['pmpro_segment'],
            'levels' => $element_settings['pmpro_levels'],
            'invert_restrictions' => $element_settings['pmpro_invert_restrictions'],
            'show_noaccess' => $element_settings['pmpro_show_noaccess'],
        );
        $access = ! empty( pmpro_apply_block_visibility( $apply_block_visibility_params, 'sample content' ) );
        
		return apply_filters( 'pmpro_elementor_has_access', $access, $element, $element_settings['pmpro_levels'] );
	}

	/**
	 * Migrate settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
	 *
	 * @since TBD
	 */
	public function migrate_settings() {
		// Get the post being viewed.
		$post_id = get_the_ID();
		if ( empty( $post_id ) ) {
			return;
		}

		// Get the _elementor_data for the post.
		$elementor_data_string = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $elementor_data_string ) ) {
			return;
		}

		// Decode the _elementor_data.
		$elementor_data = json_decode( $elementor_data_string, true );
		if ( empty( $elementor_data ) ) {
			return;
		}

		// Track if we've migrated.
		$migrated = false;

		// Get the container settings.
		$container_settings = $elementor_data[0]['settings'];
		if ( isset( $container_settings['pmpro_require_membership'] ) ) {
			// Migrate the settings.
			$elementor_data[0]['settings'] = $this->migrate_settings_helper( $container_settings );
			$migrated = true;
		}

		// Loop through each element and migrate the settings.
		foreach ( $elementor_data[0]['elements'] as $key => $element ) {
			// Get the element settings.
			$element_settings = $element['settings'];

			// If the element has the old pmpro_require_membership setting, migrate it.
			if ( isset( $element_settings['pmpro_require_membership'] ) ) {
				$elementor_data[0]['elements'][ $key ]['settings'] = $this->migrate_settings_helper( $element_settings );
				$migrated = true;
			}
		}

		// If we've migrated, save the updated _elementor_data.
		if ( $migrated ) {
			update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elementor_data ) );
		}
	}

	/**
	 * Heloper method for migrating settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
	 *
	 * @since TBD
	 *
	 * @param array $settings The settings array to migrate.
	 * @return array The migrated settings array.
	 */
	private function migrate_settings_helper( $settings ) {
		// If pmpro_require_membership is not set, bail.
		if ( ! isset( $settings['pmpro_require_membership'] ) ) {
			return $settings;
		}

		// Figure out how to migrate the settings.
		$settings['pmpro_enable'] = 'yes';
		if ( ! in_array( '0', $settings['pmpro_require_membership'] ) ) {
			// If '0' is not in the array, then we can copy the pmpro_require_membership value and use "restrict to specific levels".
			$settings['pmpro_levels'] = $settings['pmpro_require_membership'];
			$settings['pmpro_segment'] = 'specific';
			$settings['pmpro_invert_restrictions'] = '0';
			$settings['pmpro_show_noaccess'] = empty( $settings['pmpro_no_access_message'] ) ? 'no' : $settings['pmpro_no_access_message'];
		} elseif ( 1 === count( $settings['pmpro_require_membership'] ) ) {
			// '0' is the only value in the array. This means that we should restrict to non-members.
			$settings['pmpro_levels'] = array();
			$settings['pmpro_segment'] = 'all';
			$settings['pmpro_invert_restrictions'] = '1';
			$settings['pmpro_show_noaccess'] = 'no';
		} else {
			// '0' is in the array, but there are other values. This means that we need to block access to all levels that are not in the array.
			// First, get all PMPro level IDs.
			$all_levels = pmpro_getAllLevels( true, false );
			$all_levels_ids = wp_list_pluck( $all_levels, 'id' );

			// Get the levels that are not in the pmpro_require_membership array.
			$settings['pmpro_levels'] = array_values( array_diff( $all_levels_ids, $settings['pmpro_require_membership'] ) );
			$settings['pmpro_segment'] = 'specific';
			$settings['pmpro_invert_restrictions'] = '1';
			$settings['pmpro_show_noaccess'] = 'no';
		}

		// Remove the old pmpro_require_membership setting.
		unset( $settings['pmpro_require_membership'] );
		unset( $settings['pmpro_no_access_message'] );

		return $settings;
	}
}

new PMPro_Elementor_Content_Restriction;