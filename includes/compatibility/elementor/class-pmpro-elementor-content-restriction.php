<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class PMPro_Elementor_Content_Restriction extends PMPro_Elementor {
	protected function content_restriction() {
		// Setup controls
		$this->register_controls();

		// Filter elementor render_content hook
		add_action( 'elementor/widget/render_content', array( $this, 'pmpro_elementor_filter_content' ), 10, 2 );
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
			'pmpro_require_membership_heading', array(
				'label'     => __( 'Require Membership Level', 'paid-memberships-pro' ),
				'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
			)
		);

		$element->add_control(
            'pmpro_require_membership', array(
                'type'        => Controls_Manager::SELECT2,
                'options'     => pmpro_elementor_get_all_levels(),
                'multiple'    => 'true',
				'label_block' => 'true',
				'description' => __( 'Require membership level to see this content.', 'paid-memberships-pro' ),
            )
        );

	}

	public function pmpro_elementor_filter_content( $content, $widget ){

        // Don't hide content in editor mode.
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            return $content;
        }

        $widget_settings = $widget->get_active_settings();

        $restricted_levels = $widget_settings['pmpro_require_membership'];

        // Just return content if no setting is set for the current widget.
        if ( ! $restricted_levels ) {
            return $content;
        }

        if ( ! pmpro_hasMembershipLevel( $restricted_levels ) ) {
           $content = '';
        }
        
        return $content;
	}
}

new PMPro_Elementor_Content_Restriction;
