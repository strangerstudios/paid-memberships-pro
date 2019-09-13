<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Paid_Memberships_Pro
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

/**
 * Loads additional plugins that use main PMP bootstrap file.
 */
if ( ! isset( $pmpro_plugins ) ) {
	$pmpro_plugins = [];
}

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
tests_add_filter( 'muplugins_loaded', function() use ( $pmpro_plugins ) {
	require_once dirname( __FILE__ ) . '/../paid-memberships-pro.php';

	foreach ( $pmpro_plugins as $pmpro_plugin ) {
		require_once $pmpro_plugin;
	}
});

// Start up the WP testing environment.
require_once $_tests_dir . '/includes/bootstrap.php';

require_once dirname( __FILE__ ) . '/_helpers/helpers.php';
require_once dirname( __FILE__ ) . '/class-base.php';

// Activate PMPro and run upgrade check to setup DB/etc
echo "Installing Paid Memberships Pro...\n";
activate_plugin( 'paid-memberships-pro/paid-memberships-pro.php' );
pmpro_checkForUpgrades();