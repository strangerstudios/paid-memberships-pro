<?php

class PMProSiteHealth{

	public static function init() {

		if ( ! is_object( self::$instance ) ) {
			self::$instance = new PMProSiteHealth();
		}

		return self::$instance;
	}

	public function __construct(){

		add_filter( 'debug_information', array( $this, 'debug_information' ) );

	}

	public function debug_information( $info ) {

		$info['pmpro'] = array(
			'label'       => 'Paid Memberships Pro',
			'description' => __( 'Debug information for your Paid Memberships Pro Installation.', 'paid-memberships-pro' ),
			'fields'      => array(
				'pmpro-membership-levels' => array(
					'label' => __( 'Membership Levels', 'paid-membership-levels' ),
					'value' => self::get_levels()
				),
				'pmpro-discount-codes' => array(
					'label' => __( 'Discount Codes', 'paid-membership-levels' ),
					'value' => self::count_discount_codes()
				),
				'pmpro-gateway' => array(
					'label' => __( 'Payment Gateway', 'paid-membership-levels' ),
					'value' => self::default_gateway()
				),
				'pmpro-gateway-env' => array(
					'label' => __( 'Payment Gateway Environment', 'paid-membership-levels' ),
					'value' => self::default_gateway_env()
				),
				'pmpro-custom-templates' => array(
					'label' => __( 'Using Custom Templates', 'paid-membership-levels' ),
					'value' => self::check_custom_templates()
				),
				'pmpro-cron-jobs' => array(
					'label' => __( 'Are Cron Jobs Operational?', 'paid-membership-levels' ),
					'value' => self::cron_jobs()
				),
			)
		);

		return $info;

	}

	public function get_levels() {

		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_membership_levels LIMIT 10" );                
		if( $results ){
			
			return print_r( $results, true );

		} else {
			return __( 'No Levels Found', 'paid-memberships-pro' );
		}
	}

	public function count_discount_codes() {

		global $wpdb;

		$codes = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes" );

		return $codes;

	}

	public function default_gateway() {

		return get_option( 'pmpro_gateway' );

	}

	public function default_gateway_env() {

		return get_option( 'pmpro_gateway_environment' );

	}

	public function check_custom_templates() {

		$theme_url = get_stylesheet_directory()."/paid-memberships-pro/";

		if( is_dir( $theme_url ) ) {
			return 'Yes';
		}

		return 'No';

	}

	public function cron_jobs() {

		$crons = _get_cron_array();

		$cron_times = array();

		if( $crons ){			

			foreach( $crons as $time => $cron ){
				
				if( !empty( $cron['pmpro_cron_expire_memberships'] ) ) {
					$cron_times[] = 'pmpro_cron_expire_memberships - '.date( 'Y-m-d H:i:s', $time );
				} else if ( !empty( $cron['pmpro_cron_expiration_warnings'] ) ) {
					$cron_times[] = 'pmpro_cron_expiration_warnings - '.date( 'Y-m-d H:i:s', $time );
				} else if ( !empty( $cron['pmpro_cron_credit_card_expiring_warnings'] ) ) {
					$cron_times[] = 'pmpro_cron_credit_card_expiring_warnings - '.date( 'Y-m-d H:i:s', $time );
				} else if( !empty( $cron['pmpro_cron_admin_activity_email'] ) ) {
					$cron_times[] = 'pmpro_cron_admin_activity_email - '.date( 'Y-m-d H:i:s', $time );
				}
			}
		}
			
		return implode( ' | ', $cron_times );

	}

}
new PMProSiteHealth();