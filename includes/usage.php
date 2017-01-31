<?php

//Show usage tracking optin notice
add_action( 'admin_notices', 'pmro_addUsageOptinNotice' );

//check for request to optin/optout
add_action( 'admin_init', 'pmpro_listenForUsageOptin' );

//triggered by wp_cron to send tracking
add_action( 'pmpro_cron_send_usage_stats', 'pmro_maybeSendUsage' );

/**
 * Get all plugins with version
 *
 * @since 1.9
 *
 * @return array
 */
function pmpro_getPlugins(){
	$plugins     = array();
	include_once ABSPATH  . '/wp-admin/includes/plugin.php';
	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin_file => $plugin_data ) {
		if ( is_plugin_active( $plugin_file ) ) {
			$plugins[ $plugin_data[ 'Name' ] ] = $plugin_data[ 'Version' ];
		}
	}

	return $plugins;

}

/**
 * Check if we have a request to optin/out and handle it
 *
 * @uses "admin_init"
 *
 * @since 1.9
 */
function pmpro_listenForUsageOptin(){
	if( current_user_can( 'manage_options' ) &&  isset( $_POST, $_POST[ '_pmpro_optin' ], $_POST[ '_pmpro_optin_nonce' ] ) && wp_verify_nonce( $_POST[ '_pmpro_optin_nonce' ], '_pmpro_optin_nonce' ) ){
		if(  $_POST[ '_pmpro_optin' ] ){
			PMProUsageData::get_main_instance()->optin();
		}else{
			PMProUsageData::get_main_instance()->optOut();
		}
	}

}

/**
 * If needed add an optin notice
 *
 * @uses "admin_notices"
 *
 * @since 1.9
 */
function pmro_addUsageOptinNotice() {
	//If they have optin in or out return with no notice displayed
	if( ! current_user_can( 'manage_options' ) || ! PMProUsageData::get_main_instance()->shouldAskForOption() ){
		return;
	}
	$nonce = wp_create_nonce( '_pmpro_optin_nonce' );
	?>
	<div class="notice notice-success ">
		<p>
			<?php esc_html_e( 'Would you like to share usage data with Paid Memberships Pro?', 'pmpro' ); ?>
			<a class="button pmpro-optin-button" id="pmpro-optin-button-accept" href="<?php echo esc_url_raw( add_query_arg( array(
				'_pmpro_optin'       => 'true',
				'_pmpro_optin_nonce' => $nonce,
				admin_url()
			) ) ); ?>">
				<?php esc_html_e( 'Yes', 'pmpro' ); ?>
			</a>
			<a class="button pmpro-optin-button" id="pmpro-optin-button-decline" href="<?php echo esc_url_raw( add_query_arg( array(
				'_pmpro_optin'       => 'false',
				'_pmpro_optin_nonce' => $nonce,
				admin_url()
			) ) ); ?>">
				<?php esc_html_e( 'No', 'pmpro' ); ?>
			</a>
		</p>
	</div>
	<?php

}

/**
 * Trigger send of stats if needed and allowed
 *
 * @since 1.9
 */
function pmro_maybeSendUsage(){
	if ( PMProUsageData::get_main_instance()->canTrack() ) {
		PMProUsageData::get_main_instance()->send();
	}
}