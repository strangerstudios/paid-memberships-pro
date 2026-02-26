<?php

class PMPro_Elementor_Widget_Account extends PMPro_Elementor_Widget_Base {

	public function get_name() {
		return 'pmpro_account_widget';
	}

	public function get_title() {
		return __( 'PMPro Page: Account (Full)', 'paid-memberships-pro' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'PMPro Page: Account (Full)', 'paid-memberships-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Displays all sections of the Membership Account page including Memberships, Profile, Orders, and Member Links. These sections can also be added via separate widgets.', 'paid-memberships-pro' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_footer_promo_control();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[pmpro_account]' );
	}
}