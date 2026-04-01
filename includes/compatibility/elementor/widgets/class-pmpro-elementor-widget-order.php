<?php

class PMPro_Elementor_Widget_Order extends PMPro_Elementor_Widget_Base {

	public function get_name() {
		return 'pmpro_order_widget';
	}

	public function get_title() {
		return __( 'PMPro Page: Orders', 'paid-memberships-pro' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'PMPro Page: Orders', 'paid-memberships-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Dynamic page section that displays a list of all orders (purchase history) for the active member. Each order can be selected and viewed in full detail.', 'paid-memberships-pro' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_footer_promo_control();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[pmpro_invoice]' );
	}
}