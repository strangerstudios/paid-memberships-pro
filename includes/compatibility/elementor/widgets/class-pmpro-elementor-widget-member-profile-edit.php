<?php

class PMPro_Elementor_Widget_Member_Profile_Edit extends PMPro_Elementor_Widget_Base {

	public function get_name() {
		return 'pmpro_member_profile_edit_widget';
	}

	public function get_title() {
		return __( 'PMPro Page: Account Profile Edit', 'paid-memberships-pro' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'PMPro Page: Account Profile Edit', 'paid-memberships-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Dynamic form that allows the current logged in member to edit their default user profile information and any custom user profile fields.', 'paid-memberships-pro' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_footer_promo_control();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[pmpro_member_profile_edit]' );
	}
}