<?php

defined( 'ABSPATH' ) or die( 'File cannot be accessed directly' );

class Deny_Network_Activation {

	public function init() {
		register_activation_hook( DENY_PLUGIN_BASE_FILE, array( $this, 'pmpro_check_network_activation' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_admin_style' ) );
		add_action( 'network_admin_notices', array( $this, 'display_message_after_network_activation_attempt' ) );
		// add_action( 'admin_notices', array( $this, 'display_message_after_network_activation_attempt' ) );

		// On the blog list page, show the plugins and theme active on each blog
		add_filter( 'manage_sites-network_columns', array( $this, 'add_sites_column' ), 10, 1 );
		add_action( 'manage_sites_custom_column', array( $this, 'manage_sites_custom_column' ), 10, 3 );
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


	function add_sites_column( $column_details ) {
		$column_details['active_plugins'] = __( 'PMPro Active?', 'pmpro' );

		// $column_details['active_theme'] = __( 'Active Theme', 'pmpro' );

		return $column_details;
	}

	function manage_sites_custom_column( $column_name, $blog_id ) {
		if ( 'active_plugins' !== $column_name ) {
		    return;
		}

		$output = '';

		if ( '1' === $blog_id ) {
			$button_text = __( 'PMPro Active', 'pmpro' );
			$style = __( 'button-secondary inactive', 'pmpro' );
		} else {
			$button_text = __( 'Activate PMPro', 'pmpro' );
			$style = __( 'button-primary', 'pmpro' );
		}

		$output .= '<button class="' . $style . '">' . esc_html( $button_text ) . '</button>';

		echo $output;
	}
}

$deny_network = new Deny_Network_Activation();
$deny_network->init();
