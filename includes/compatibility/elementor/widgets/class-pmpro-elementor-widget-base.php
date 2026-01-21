<?php

abstract class PMPro_Elementor_Widget_Base extends \Elementor\Widget_Base {

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
	}

	public function get_icon() {
		return 'dashicons-before dashicons-admin-users';
	}

	public function get_categories() {
		return array( 'paid-memberships-pro' );
	}

	protected function add_footer_promo_control() {

		$this->add_control(
			'pmpro_footer_promo',
			array(
				'label'           => '',
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<hr><p style="margin-top: 20px;">' . sprintf( esc_html__( 'Learn more about %1$sediting your PMPro-powered membership site with Elementor%2$s', 'paid-memberships-pro' ), '<a target="_blank" href="#">', '</a>' ) . '</p>',
				'content_classes' => 'pmpro-footer-promo',
			)
		);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo 'hi';
	}

	protected function _content_template() {
		// Define your template variables here
	}
}
