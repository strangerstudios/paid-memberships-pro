<?php
/**
 * Plugin Name: Paid Memberships Pro - Blockonomics Bitcoin Gateway
 * Plugin URI: https://www.paidmembershipspro.com/
 * Description: Adds a Blockonomics Bitcoin payment gateway to Paid Memberships Pro.
 * Version: 0.1.0
 * Author: Blockonomics
 * Text Domain: pmpro-blockonomics
 * Domain Path: /languages
 * Requires Plugins: paid-memberships-pro
 *
 * @package PMPro_Blockonomics
 */

defined( 'ABSPATH' ) || exit;

define( 'PMPRO_BLOCKONOMICS_VERSION', '0.1.0' );
define( 'PMPRO_BLOCKONOMICS_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Load the gateway once Paid Memberships Pro is available.
 */
function pmpro_blockonomics_load_gateway() {
	if ( ! class_exists( 'PMProGateway' ) ) {
		add_action( 'admin_notices', 'pmpro_blockonomics_missing_pmpro_notice' );
		return;
	}

	require_once PMPRO_BLOCKONOMICS_DIR . 'classes/class.pmprogateway_blockonomics.php';

	PMProGateway_blockonomics::init();
}
add_action( 'plugins_loaded', 'pmpro_blockonomics_load_gateway', 20 );

/**
 * Register Blockonomics callback handlers.
 */
function pmpro_blockonomics_register_callback_handlers() {
	add_action( 'wp_ajax_nopriv_blockonomics_callback', 'pmpro_blockonomics_ajax_callback' );
	add_action( 'wp_ajax_blockonomics_callback', 'pmpro_blockonomics_ajax_callback' );
}
add_action( 'init', 'pmpro_blockonomics_register_callback_handlers' );

/**
 * Handle the Blockonomics HTTP callback.
 */
function pmpro_blockonomics_ajax_callback() {
	if ( ! class_exists( 'PMProGateway_blockonomics' ) ) {
		status_header( 503 );
		echo esc_html__( 'Blockonomics gateway is not available.', 'pmpro-blockonomics' );
		exit;
	}

	PMProGateway_blockonomics::handle_callback();
	exit;
}

/**
 * Show an admin notice when PMPro is inactive.
 */
function pmpro_blockonomics_missing_pmpro_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Paid Memberships Pro - Blockonomics Bitcoin Gateway requires Paid Memberships Pro to be active.', 'pmpro-blockonomics' ); ?></p>
	</div>
	<?php
}
