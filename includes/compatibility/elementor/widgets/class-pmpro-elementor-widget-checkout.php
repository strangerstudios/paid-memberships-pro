<?php

class PMPro_Elementor_Widget_Checkout extends PMPro_Elementor_Widget_Base {

	public function get_name() {
		return 'pmpro_checkout_widget';
	}

	public function get_title() {
		return __( 'Membership Checkout Form', 'paid-memberships-pro' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Membership Checkout Form', 'paid-memberships-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Dynamic form that allows users to complete free registration or paid checkout for the selected membership level.', 'paid-memberships-pro' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_footer_promo_control();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[pmpro_checkout]' );
	}
}