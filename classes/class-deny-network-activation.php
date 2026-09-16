<?php

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPro_Deny_Network_Activation {

	public function init() {
		register_activation_hook( PMPRO_BASE_FILE, array( $this, 'pmpro_check_network_activation' ) );
		add_action( 'activated_plugin', array( $this, 'check_addon_network_activation' ), 10, 2 );
		add_filter( 'install_plugin_complete_actions', array( $this, 'remove_network_activate_link' ), 10, 3 );
		add_action( 'wp_print_footer_scripts', array( $this, 'wp_admin_style' ) );
		add_action( 'network_admin_notices', array( $this, 'display_message_after_network_activation_attempt' ) );
	}

	public function wp_admin_style() {
		global $current_screen;
		// Bail if not in the dashboard.
		if ( ! is_admin() ) {
			return;
		}
		
		// Bail if there is no current screen.
		if ( empty( $current_screen ) ) {
			return;
		}
		
		// Bail if not on the screens we want.
		if ( 'sites-network' !== $current_screen->id && 'plugins-network' !== $current_screen->id ) {
			return;
		}
		?>
		<style type="text/css">
			.notice.notice-info {
				background-color: #ffd;
			}
		</style>
		<?php
	}

	/**
	 * Sanitize a plugin path like folder/file.php without removing the slash.
	 *
	 * @since TBD
	 *
	 * @param string $plugin_file Plugin path relative to the plugins directory.
	 * @return string Sanitized plugin path.
	 */
	private function sanitize_plugin_path( $plugin_file ) {
		$segments = array();
		foreach ( explode( '/', (string) $plugin_file ) as $segment ) {
			// Skip empty and traversal segments, sanitize_file_name would keep them.
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				continue;
			}
			$segments[] = sanitize_file_name( $segment );
		}

		return implode( '/', $segments );
	}

	/**
	 * Check if a plugin is a PMPro Add On.
	 *
	 * @since TBD
	 *
	 * @param string $plugin_file Plugin file relative to the plugins directory.
	 * @return bool True if the plugin is a PMPro Add On.
	 */
	private function is_pmpro_addon_plugin( $plugin_file ) {
		$plugin_file = plugin_basename( $plugin_file );
		if ( false === strpos( $plugin_file, '/' ) ) {
			return false;
		}

		$folders       = explode( '/', $plugin_file, 2 );
		$plugin_folder = sanitize_key( $folders[0] );
		if ( empty( $plugin_folder ) ) {
			return false;
		}

		$addons = get_option( 'pmpro_addons', array() );
		if ( ! is_array( $addons ) ) {
			$addons = array();
		}

		foreach ( $addons as $addon ) {
			if ( ! is_array( $addon ) || empty( $addon['Slug'] ) ) {
				continue;
			}

			$addon_folder = sanitize_key( $addon['Slug'] );
			if ( $addon_folder === $plugin_folder ) {
				return true;
			}
		}

		return 0 === strpos( $plugin_folder, 'pmpro-' );
	}

	/**
	 * Prevent PMPro Add Ons from being activated network-wide.
	 *
	 * @since TBD
	 *
	 * @param string $plugin       Path to the plugin file relative to the plugins directory.
	 * @param bool   $network_wide Whether the plugin was activated network-wide.
	 */
	public function check_addon_network_activation( $plugin, $network_wide ) {
		if ( ! is_multisite() || ! $network_wide || ! $this->is_pmpro_addon_plugin( $plugin ) ) {
			return;
		}

		$plugin = plugin_basename( $plugin );
		deactivate_plugins( $plugin, true, true );

		$denied_plugins = get_site_transient( 'pmpro_denied_network_activations' );
		if ( ! is_array( $denied_plugins ) ) {
			$denied_plugins = array();
		}
		$denied_plugins[] = $plugin;
		set_site_transient( 'pmpro_denied_network_activations', array_unique( $denied_plugins ), MINUTE_IN_SECONDS );
	}

	/**
	 * Remove the network activation action from the plugin install screen.
	 *
	 * @since TBD
	 *
	 * @param array  $install_actions Available actions after installing the plugin.
	 * @param object $api             Plugin API data.
	 * @param string $plugin_file     Path to the plugin file relative to the plugins directory.
	 * @return array Filtered install actions.
	 */
	public function remove_network_activate_link( $install_actions, $api, $plugin_file ) {
		if ( $this->is_pmpro_addon_plugin( $plugin_file ) ) {
			unset( $install_actions['network_activate'] );
		}

		return $install_actions;
	}

	public function display_message_after_network_activation_attempt() {
		global $current_screen;
		if ( empty( $current_screen ) || ( 'sites-network' !== $current_screen->id && 'plugins-network' !== $current_screen->id ) ) {
			return;
		}

		$denied_plugins = get_site_transient( 'pmpro_denied_network_activations' );
		if ( ! is_array( $denied_plugins ) ) {
			$denied_plugins = array();
		}

		if ( ! empty( $_REQUEST['pmpro_deny_network_activation'] ) ) {
			$plugin           = urldecode( wp_unslash( $_REQUEST['pmpro_deny_network_activation'] ) );
			$denied_plugins[] = $this->sanitize_plugin_path( $plugin );
		}

		if ( empty( $denied_plugins ) ) {
			return;
		}

		delete_site_transient( 'pmpro_denied_network_activations' );
		foreach ( array_unique( $denied_plugins ) as $plugin ) {
			$this->display_network_activation_notice( $plugin );
		}
	}

	/**
	 * Display a notice after a network activation attempt.
	 *
	 * @since TBD
	 *
	 * @param string $plugin Plugin file relative to the plugins directory.
	 */
	private function display_network_activation_notice( $plugin ) {
		$plugin      = $this->sanitize_plugin_path( $plugin );
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
		$plugin_data = get_plugin_data( $plugin_path );
		$plugin_name = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';

		echo '<div class="notice notice-info is-dismissible"><p>';
		/* translators: %s: Name of the plugin that was network activated. */
		$text = sprintf( esc_html__( 'The %s plugin should not be network activated. Activate on each individual site\'s plugin page.', 'paid-memberships-pro' ), $plugin_name );
		echo esc_html( $text );
		echo '</p></div>';
	}

	public function pmpro_check_network_activation( $network_wide ) {
		if ( ! is_multisite() || ! $network_wide ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? $this->sanitize_plugin_path( wp_unslash( $_REQUEST['plugin'] ) ) : '';

		deactivate_plugins( $plugin, true, true );
		if ( ! isset( $_REQUEST['pmpro_deny_network_activation'] ) ) {
			wp_redirect( add_query_arg( 'pmpro_deny_network_activation', $plugin, network_admin_url( 'plugins.php' ) ) );
			exit;
		}
	}
}

// Init the check if the plugin is active.
if ( class_exists( '\PMPro_Deny_Network_Activation' ) ) {
	$pmp_wpmu_deny = new PMPro_Deny_Network_Activation();
	$pmp_wpmu_deny->init();
}
