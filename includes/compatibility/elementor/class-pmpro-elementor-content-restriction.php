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
		add_action( 'elementor/editor/init', array( $this, 'migrate_settings' ) ); // Migrate on editor load.

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
				'label' => esc_html__( 'Enable Paid Memberships Pro module visibility?', 'paid-memberships-pro' ),
				'default' => 'no',
            )
		);

        $element->add_control(
			'pmpro_invert_restrictions', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(                
					'0' => esc_html__( 'Show content to...', 'paid-memberships-pro' ),
					'1' => esc_html__( 'Hide content from...', 'paid-memberships-pro' ),                    
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
					'all' => esc_html__( 'All Members', 'paid-memberships-pro' ),
					'specific' => esc_html__( 'Specific Membership Levels', 'paid-memberships-pro' ),
                    'logged_in' => esc_html__( 'Logged-In Users', 'paid-memberships-pro' ),
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
            'invert_restrictions' => filter_var( $element_settings['pmpro_invert_restrictions'], FILTER_VALIDATE_BOOLEAN ),
            'show_noaccess' => ! empty( $element_settings['pmpro_show_noaccess'] ) ? filter_var( $element_settings['pmpro_show_noaccess'], FILTER_VALIDATE_BOOLEAN ) : false,
        );
        $should_render = ! empty( pmpro_apply_block_visibility( $apply_block_visibility_params, 'sample content' ) );
		
		return apply_filters_deprecated( 'pmpro_elementor_section_access', array( $should_render, $element ), '3.5' );
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
            'invert_restrictions' => filter_var( $widget_settings['pmpro_invert_restrictions'], FILTER_VALIDATE_BOOLEAN ),
            'show_noaccess' => filter_var( $widget_settings['pmpro_show_noaccess'], FILTER_VALIDATE_BOOLEAN ),
        );
        return pmpro_apply_block_visibility( $apply_block_visibility_params, $content );

	}

	/**
	 * Migrate settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
	 *
	 * @since 3.5
	 */
	public function migrate_settings() {
		// Get the post being viewed.
		$post_id = get_the_ID();
		if ( empty( $post_id ) ) {
			return;
		}

		// Get the _elementor_data for the post.
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $elementor_data ) ) {
			return;
		}

		// Decode the _elementor_data if needed.
		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		// If the data is still empty after decoding, bail.
		if ( empty( $elementor_data ) || ! is_array( $elementor_data ) ) {
			return;
		}

		$migrated_data = false;
		foreach ( $elementor_data as $key => &$container ) {
			// Migrate the settings for this container.
			$migrated_settings = $this->migrate_settings_helper( $container );
			if ( ! empty( $migrated_settings ) ) {
				$elementor_data[ $key ]['settings'] = $migrated_settings['settings'];
				$elementor_data[ $key ]['elements'] = $migrated_settings['elements'] ?? array();
				// Track that we made changes.
				$migrated_data = true;
			}
		}

		// Only save if something actually changed.
		if ( ! empty( $migrated_data ) ) {
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
		}
	}

	/**
	 * Helper method for migrating settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
	 *
	 * @since 3.5
	 *
	 * @param array $container An elementor container possibly containing $settings and $elements arrays to migrate.
	 * @return array|null Returns the updated settings array if changes were made, or null if no changes were needed.
	 */
	private function migrate_settings_helper( $container ) {
		// Track whether we made any changes for child elements.
		$migrated_child_elements = false;

		// Loop through the elements and call this method recursively.
		if ( ! empty( $container['elements'] ) && is_array( $container['elements'] ) ) {
			foreach ( $container['elements'] as &$element ) {
				$migrated_settings = $this->migrate_settings_helper( $element );
				if ( ! empty( $migrated_settings ) ) {
					$element['settings'] = $migrated_settings['settings'];
					$element['elements'] = $migrated_settings['elements'] ?? array();
					$migrated_child_elements = true;
				}
			}
			unset( $element );
		}

		// Migrate the the settings for this container.
		// If pmpro_require_membership is not set, bail.
		if ( ! isset( $container['settings']['pmpro_require_membership'] ) || empty( $container['settings']['pmpro_require_membership'] ) ) {
			return $migrated_child_elements ? $container : null;
		}

		// Let's convert the settings to the new format now.
		$container['settings']['pmpro_enable'] = 'yes';
		$container['settings']['pmpro_invert_restrictions'] = '0'; // 0 = Show content to members, 1 = Hide content from members.
		$container['settings']['pmpro_segment'] = 'specific'; // Elementor would always be "specific" during upgrade.
		$container['settings']['pmpro_levels'] = $container['settings']['pmpro_require_membership'];
		$container['settings']['pmpro_show_noaccess'] = ! empty( $container['settings']['pmpro_no_access_message'] ) ? $container['settings']['pmpro_no_access_message'] : 'no';

		// Remove the old pmpro_require_membership settings to clean up.
		unset( $container['settings']['pmpro_require_membership'] );
		unset( $container['settings']['pmpro_no_access_message'] );

		return $container;
	}

	/**
	 * Figure out if the user has access to restricted content.
	 * @return bool True or false based if the user has access to the content or not.
	 * @since 2.3
     * @deprecated 3.5
	 */
	public function pmpro_elementor_has_access( $element ) {
        _deprecated_function( __METHOD__, '3.5' );

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

}
new PMPro_Elementor_Content_Restriction;
