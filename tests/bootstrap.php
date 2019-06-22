<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Sample_Plugin
 */
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';
/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../paid-memberships-pro.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Activate PMPro and run upgrade check to setup DB/etc
echo "Installing Paid Memberships Pro...\n";
activate_plugin( 'paid-memberships-pro/paid-memberships-pro.php' );
pmpro_checkForUpgrades();