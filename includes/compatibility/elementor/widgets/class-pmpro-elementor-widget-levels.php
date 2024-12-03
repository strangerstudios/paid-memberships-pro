<?php

class PMPro_Elementor_Widget_Levels extends PMPro_Elementor_Widget_Base {

	public function get_name() {
		return 'pmpro_levels_widget';
	}

	public function get_title() {
		return __( 'Membership Levels and Pricing Table', 'paid-memberships-pro' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Membership Levels and Pricing Table', 'paid-memberships-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Dynamic page section that displays a list of membership levels and pricing, linked to membership checkout. To reorder the display, navigate to Memberships > Settings > Levels.', 'paid-memberships-pro' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_footer_promo_control();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[pmpro_levels]' );
	}
}