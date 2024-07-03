<?php
/* This file contains functions used to process required database updates sometimes logged after PMPro is upgraded. */

/*
	Is there an update?
*/
function pmpro_isUpdateRequired() {
	$updates = get_option('pmpro_updates', array());
	return(!empty($updates));
}

/**
 * Update option to require an update.
 * @param string $update
 *
 * @since 1.8.7
 */
function pmpro_addUpdate($update) {
	$updates = get_option('pmpro_updates', array());
	$updates[] = $update;
	$updates = array_values(array_unique($updates));

	update_option('pmpro_updates', $updates, 'no');
}

/**
 * Update option to remove an update.
 * @param string $update
 *
 * @since 1.8.7
 */
function pmpro_removeUpdate($update) {
	$updates = get_option('pmpro_updates', array());
	$key = array_search($update,$updates);
	if($key!==false){
	    unset($updates[$key]);
	}

	$updates = array_values($updates);

	update_option('pmpro_updates', $updates, 'no');
}

/*
	Enqueue updates.js if needed
*/
function pmpro_enqueue_update_js() {
	if(!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-updates') {
		wp_enqueue_script( 'pmpro-updates', plugin_dir_url( dirname(__FILE__) ) . 'js/updates.js', array('jquery'), PMPRO_VERSION );
	}
}
add_action('admin_enqueue_scripts', 'pmpro_enqueue_update_js');

/*
	Load an update via AJAX
*/
function pmpro_wp_ajax_pmpro_updates() {
	//get updates
	$updates = array_values(get_option('pmpro_updates', array()));

	//run update or let them know we're done
	if(!empty($updates)) {
		//get the latest one and run it
		if(function_exists($updates[0]))
			call_user_func($updates[0]);
		else
			echo "[error] Function not found: " . esc_html( $updates[0] );
		echo ". ";
	} else {
		echo "[done]";
	}

	//reset this transient so we know AJAX is running
	set_transient('pmpro_updates_first_load', false, 60*60*24);

	//show progress
	global $pmpro_updates_progress;
	if(!empty($pmpro_updates_progress))
		echo esc_html( $pmpro_updates_progress );

	exit;
}
add_action('wp_ajax_pmpro_updates', 'pmpro_wp_ajax_pmpro_updates');

/*
	Redirect away from updates page if there are no updates
*/
function pmpro_admin_init_updates_redirect() {
	if(is_admin() && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-updates' && !pmpro_isUpdateRequired()) {
		wp_redirect(admin_url('admin.php?page=pmpro-membershiplevels&updatescomplete=1'));
		exit;
	}
}
add_action('init', 'pmpro_admin_init_updates_redirect');

/*
	Show admin notice if an update is required and not already on the updates page.
*/
if(pmpro_isUpdateRequired() && (empty($_REQUEST['page']) || $_REQUEST['page'] != 'pmpro-updates'))
	add_action('admin_notices', 'pmpro_updates_notice');

/*
	Function to show an admin notice linking to the updates page.
*/
function pmpro_updates_notice() {
?>
<div class="update-nag notice notice-warning inline">
	<?php
		echo esc_html__( 'Paid Memberships Pro Data Update Required', 'paid-memberships-pro' ) . '. ';
		/* translators: %s: URL to the updates page. */
		echo wp_kses_post( sprintf(__( '(1) <a target="_blank" href="%s">Backup your WordPress database</a></strong> and then (2) <a href="%s">click here to start the update</a>.', 'paid-memberships-pro' ), esc_url( 'https://www.paidmembershipspro.com/backup-wordpress-site/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=blog&utm_content=backup-notification' ), admin_url('admin.php?page=pmpro-updates')));
	?>
</div>
<?php
}

/*
	Show admin notice when updates are complete.
*/
if(is_admin() && !empty($_REQUEST['updatescomplete']))
	add_action('admin_notices', 'pmpro_updates_notice_complete');

/*
	Function to show an admin notice linking to the updates page.
*/
function pmpro_updates_notice_complete() {
?>
<div class="updated notice notice-success is-dismissible">
	<p>
	<?php
		esc_html_e('All Paid Memberships Pro updates have finished.', 'paid-memberships-pro' );
	?>
	</p>
</div>
<?php
}

/**
 * If there is an upgrade notice for updating PMPro to the latest version, show it.
 *
 * @since 2.9
 *
 * @param array $current_plugin_data {
 *     An array of plugin metadata.
 *
 *     @type string $name         The human-readable name of the plugin.
 *     @type string $plugin_uri   Plugin URI.
 *     @type string $version      Plugin version.
 *     @type string $description  Plugin description.
 *     @type string $author       Plugin author.
 *     @type string $author_uri   Plugin author URI.
 *     @type string $text_domain  Plugin text domain.
 *     @type string $domain_path  Relative path to the plugin's .mo file(s).
 *     @type bool   $network      Whether the plugin can only be activated network wide.
 *     @type string $title        The human-readable title of the plugin.
 *     @type string $author_name  Plugin author's name.
 *     @type bool   $update       Whether there's an available update. Default null.
 * }
 * @param array $update_data {
 *     An array of metadata about the available plugin update.
 *
 *     @type int    $id           Plugin ID.
 *     @type string $slug         Plugin slug.
 *     @type string $new_version  New plugin version.
 *     @type string $url          Plugin URL.
 *     @type string $package      Plugin update package URL.
 * }
 */
function pmpro_maybe_show_upgrade_notices( $current_plugin_data, $update_data ) {
	if ( isset( $update_data->upgrade_notice ) && strlen( trim( $update_data->upgrade_notice ) ) > 0 ) {
		echo '<p class="pmpro_plugin_update_notice"><strong>' . esc_html__( 'Important Upgrade Notice', 'paid-memberships-pro' ) . ':</strong> ' . esc_html( strip_tags( $update_data->upgrade_notice ) ) . '</p>';

	}
}
$pmpro_path_from_plugins_dir_arr = explode( '/wp-content/plugins/', PMPRO_BASE_FILE );
if ( ! empty( $pmpro_path_from_plugins_dir_arr ) ) {
	add_action( 'in_plugin_update_message-' . end( $pmpro_path_from_plugins_dir_arr ), 'pmpro_maybe_show_upgrade_notices', 10, 2 );
}
