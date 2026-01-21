<?php

class PMPro_Elementor_Widget_Login extends PMPro_Elementor_Widget_Base {

	public function get_name() {
		return 'pmpro_login_widget';
	}

	public function get_title() {
		return __( 'Login Form', 'paid-memberships-pro' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Login Form', 'paid-memberships-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Dynamic form that allows users to log in or recover a lost password. Logged in users can see a welcome message with the selected custom menu.', 'paid-memberships-pro' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_footer_promo_control();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[pmpro_login]' );
	}
}