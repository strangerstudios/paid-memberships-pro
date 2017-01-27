<?php

defined( 'ABSPATH' ) or die( 'File cannot be accessed directly' );

class Deny_Network_Activation {

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_admin_style' ) );
		// add_action( 'admin_notices', array( $this, 'display_message_after_network_activation_attempt' ) );
		add_action( 'network_admin_notices', array( $this, 'display_message_after_network_activation_attempt' ) );
		register_activation_hook( DENY_PLUGIN_BASE_FILE, array( $this, 'pmpro_check_network_activation' ) );
	}

	public function wp_admin_style() {
		global $current_screen;
		if ( 'sites-network' === $current_screen->id || 'plugins-network' === $current_screen->id ) {
	?>
		<style type="text/css">
			.notice.notice-info {
				background-color: #ffd;
			}
		</style>
	<?php
		}
	}

	public function display_message_after_network_activation_attempt() {
		global $current_screen;
		if ( 'sites-network' === $current_screen->id || 'plugins-network' === $current_screen->id ) {
				echo '<div class="notice notice-info is-dismissible"><p>';
				$text = sprintf(  'The %2$s should not be network activated. <br> We\'ve installed %2$s on the main site. To deactivate, <a href="%1$s">click here</a>, or select from below to setup on another site.', admin_url( 'plugins.php' ), 'Paid Memberships Pro', basename( DENY_PLUGIN_BASE_FILE ) );
				echo $text;
				echo '</p></div>';
		}
	}

	public function pmpro_check_network_activation( $network_wide ) {
		if ( is_multisite() && ! $network_wide ) {
			return;
		}

		deactivate_plugins( plugin_basename( DENY_PLUGIN_BASE_FILE ), true, true );
		switch_to_blog( $blog_id );
		$result = activate_plugin( plugin_basename( DENY_PLUGIN_BASE_FILE ) );
		if ( is_wp_error( $result ) ) {
			// Process Error
			echo $result;
		}
		restore_current_blog();
		header( 'Location: ' . network_admin_url( 'sites.php' ) );
		die();
	}
}

$deny_network = new Deny_Network_Activation();
$deny_network->init();
