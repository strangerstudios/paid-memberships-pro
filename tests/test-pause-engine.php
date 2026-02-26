<?php
/**
 * Quick pause engine test script.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/paid-memberships-pro/tests/test-pause-mode.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $pm_test_pass, $pm_test_fail;
$pm_test_pass = 0;
$pm_test_fail = 0;

function pm_assert( $condition, $label ) {
	global $pm_test_pass, $pm_test_fail;
	if ( $condition ) {
		$pm_test_pass++;
		WP_CLI::success( $label );
	} else {
		$pm_test_fail++;
		WP_CLI::error( $label, false );
	}
}

// Clean slate — force-resume if a previous run left state behind.
$pm = PMPro_Pause_Engine::instance();
if ( $pm->is_paused() ) {
	$pm->resume();
}
delete_option( PMPro_Pause_Engine::OPTION_KEY );
update_option( 'pmpro_as_halted', false );

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Orchestrator ===' );
// ------------------------------------------------------------------

pm_assert( ! $pm->is_paused(), 'Not paused initially' );
pm_assert( $pm->resume() === false, 'Resume when not paused returns false' );
pm_assert( $pm->pause( array( 'nonexistent' ) ) === false, 'Pause with invalid module returns false' );
pm_assert( $pm->pause_with_preset( 'nonexistent' ) === false, 'Pause with invalid preset returns false' );

// Pause with specific modules.
$pm->pause( array( 'pmpro_mutations', 'background_schedules' ), 'test' );
pm_assert( $pm->is_paused(), 'Paused after pause()' );
pm_assert( $pm->is_module_active( 'pmpro_mutations' ), 'Mutations module active' );
pm_assert( $pm->is_module_active( 'background_schedules' ), 'Schedules module active' );
pm_assert( ! $pm->is_module_active( 'pmpro_mail' ), 'Mail module not active' );

$state = $pm->get_state();
pm_assert( $state['enabled'] === true, 'State enabled is true' );
pm_assert( $state['activated_by'] === 'test', 'State activated_by is test' );
pm_assert( ! empty( $state['activated_at'] ), 'State has activated_at' );
pm_assert( in_array( 'pmpro_mutations', $state['modules'], true ), 'State has mutations in modules' );

// Merge more modules.
$pm->pause( array( 'pmpro_mail' ), 'test' );
pm_assert( $pm->is_module_active( 'pmpro_mail' ), 'Mail module active after merge' );
pm_assert( $pm->is_module_active( 'pmpro_mutations' ), 'Mutations still active after merge' );

// Enable/disable at runtime.
pm_assert( $pm->enable_module( 'frontend_block' ), 'Enable frontend_block at runtime' );
pm_assert( $pm->is_module_active( 'frontend_block' ), 'Frontend block now active' );

$pm->disable_module( 'frontend_block' );
pm_assert( ! $pm->is_module_active( 'frontend_block' ), 'Frontend block disabled' );

// Resume.
$pm->resume();
pm_assert( ! $pm->is_paused(), 'Not paused after resume' );
pm_assert( empty( $pm->get_active_modules() ), 'No active modules after resume' );
pm_assert( get_option( PMPro_Pause_Engine::OPTION_KEY ) === false, 'Option deleted after resume' );

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Convenience Functions ===' );
// ------------------------------------------------------------------

pm_assert( ! pmpro_pause_engine_is_active(), 'pmpro_pause_engine_is_active false when not paused' );
$pm->pause( array( 'pmpro_mutations' ), 'test' );
pm_assert( pmpro_pause_engine_is_active(), 'pmpro_pause_engine_is_active true when paused' );
pm_assert( pmpro_pause_module_is_active( 'pmpro_mutations' ), 'pmpro_pause_module_is_active true for active module' );
pm_assert( ! pmpro_pause_module_is_active( 'pmpro_mail' ), 'pmpro_pause_module_is_active false for inactive module' );
$pm->resume();

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Preset: Migration ===' );
// ------------------------------------------------------------------

$pm->pause_with_preset( 'migration' );
$modules = $pm->get_active_modules();
pm_assert( in_array( 'pmpro_mutations', $modules, true ), 'Migration preset has mutations' );
pm_assert( in_array( 'pmpro_gateways', $modules, true ), 'Migration preset has gateways' );
pm_assert( in_array( 'pmpro_mail', $modules, true ), 'Migration preset has mail' );
pm_assert( in_array( 'background_schedules', $modules, true ), 'Migration preset has schedules' );
pm_assert( in_array( 'frontend_block', $modules, true ), 'Migration preset has frontend_block' );
pm_assert( in_array( 'logged_in_sessions', $modules, true ), 'Migration preset has sessions' );
$pm->resume();

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Module D: Schedules ===' );
// ------------------------------------------------------------------

$pm->pause( array( 'background_schedules' ), 'test' );
pm_assert( (bool) get_option( 'pmpro_as_halted', false ), 'AS halted when schedules module active' );
pm_assert( has_filter( 'action_scheduler_before_execute' ) !== false, 'action_scheduler_before_execute filter attached' );
pm_assert( has_filter( 'spawn_cron' ) !== false, 'spawn_cron filter attached' );
$pm->resume();
pm_assert( ! (bool) get_option( 'pmpro_as_halted', false ), 'AS resumed after schedules module deactivated' );

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Module B: Gateways (outbound block) ===' );
// ------------------------------------------------------------------

$pm->pause( array( 'pmpro_gateways' ), 'test' );

$result = apply_filters( 'pre_http_request', false, array(), 'https://api.stripe.com/v1/charges' );
pm_assert( is_wp_error( $result ), 'Outbound to Stripe blocked' );
pm_assert( $result->get_error_code() === 'pmpro_pause_engine_blocked', 'Error code is pmpro_pause_engine_blocked' );

$result2 = apply_filters( 'pre_http_request', false, array(), 'https://api.wordpress.org/plugins/' );
pm_assert( $result2 === false, 'Outbound to WordPress.org allowed' );

$result3 = apply_filters( 'pre_http_request', false, array(), 'https://api.braintreegateway.com/merchants' );
pm_assert( is_wp_error( $result3 ), 'Outbound to Braintree blocked' );

$pm->resume();

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Module C: Mail (intercept) ===' );
// ------------------------------------------------------------------

$pm->pause( array( 'pmpro_mail' ), 'test' );

pm_assert( has_filter( 'pre_wp_mail' ) !== false, 'pre_wp_mail filter attached' );

$atts = array(
	'to'          => 'test@example.com',
	'subject'     => 'Pause Engine Test ' . time(),
	'message'     => 'Test body',
	'headers'     => '',
	'attachments' => array(),
);
$result = apply_filters( 'pre_wp_mail', null, $atts );
pm_assert( $result === false, 'pre_wp_mail returns false (email suppressed)' );

$pm->resume();

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Module E: Frontend (REST block) ===' );
// ------------------------------------------------------------------

$pm->pause( array( 'frontend_block' ), 'test' );

// As a non-admin user.
$subscriber_id = wp_insert_user( array(
	'user_login' => 'pm_test_sub_' . time(),
	'user_pass'  => wp_generate_password(),
	'role'       => 'subscriber',
) );
wp_set_current_user( $subscriber_id );

$result = apply_filters( 'rest_authentication_errors', null );
pm_assert( is_wp_error( $result ), 'REST blocked for subscriber' );

// As admin.
$admin_id = wp_insert_user( array(
	'user_login' => 'pm_test_admin_' . time(),
	'user_pass'  => wp_generate_password(),
	'role'       => 'administrator',
) );
wp_set_current_user( $admin_id );

$result2 = apply_filters( 'rest_authentication_errors', null );
pm_assert( ! is_wp_error( $result2 ), 'REST allowed for admin' );

// Login block — call our callback directly to avoid triggering WP core auth hooks.
$frontend_module = new PMPro_Pause_Module_Frontend();
$frontend_module->activate();

$sub_user = get_user_by( 'ID', $subscriber_id );
$result3 = $frontend_module->block_non_admin_login( $sub_user, $sub_user->user_login );
pm_assert( is_wp_error( $result3 ), 'Login blocked for subscriber' );

$admin_user = get_user_by( 'ID', $admin_id );
$result4 = $frontend_module->block_non_admin_login( $admin_user, $admin_user->user_login );
pm_assert( ! is_wp_error( $result4 ), 'Login allowed for admin' );

$frontend_module->deactivate();

$pm->resume();

// Cleanup test users.
wp_delete_user( $subscriber_id );
wp_delete_user( $admin_id );
wp_set_current_user( 0 );

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Module A: Mutations ===' );
// ------------------------------------------------------------------

$pm->pause( array( 'pmpro_mutations' ), 'test' );

pm_assert( has_filter( 'pmpro_change_level' ) !== false, 'pmpro_change_level filter attached' );
pm_assert( has_filter( 'pmpro_checkout_checks' ) !== false, 'pmpro_checkout_checks filter attached' );

// Non-admin: pmpro_change_level should return false.
wp_set_current_user( 0 );
$filtered = apply_filters( 'pmpro_change_level', 1, 999, 'inactive', null );
pm_assert( $filtered === false, 'Level change blocked for non-admin' );

$pm->resume();

pm_assert( has_filter( 'pmpro_change_level', '__return_false' ) === false, 'pmpro_change_level filter removed after resume' );

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Disable Last Module Auto-Resumes ===' );
// ------------------------------------------------------------------

$pm->pause( array( 'pmpro_mutations' ), 'test' );
$pm->disable_module( 'pmpro_mutations' );
pm_assert( ! $pm->is_paused(), 'Auto-resumed when last module disabled' );

// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( '=== Actions Fired ===' );
// ------------------------------------------------------------------

$activated = false;
$deactivated = false;
$mod_activated = null;
$mod_deactivated = null;

add_action( 'pmpro_pause_engine_activated', function() use ( &$activated ) { $activated = true; } );
add_action( 'pmpro_pause_engine_deactivated', function() use ( &$deactivated ) { $deactivated = true; } );
add_action( 'pmpro_pause_module_activated', function( $s ) use ( &$mod_activated ) { $mod_activated = $s; } );
add_action( 'pmpro_pause_module_deactivated', function( $s ) use ( &$mod_deactivated ) { $mod_deactivated = $s; } );

$pm->pause( array( 'pmpro_mutations' ), 'test' );
pm_assert( $activated, 'pmpro_pause_engine_activated action fired' );
pm_assert( $mod_activated === 'pmpro_mutations', 'pmpro_pause_module_activated fired with correct slug' );

$pm->resume();
pm_assert( $deactivated, 'pmpro_pause_engine_deactivated action fired' );
pm_assert( $mod_deactivated === 'pmpro_mutations', 'pmpro_pause_module_deactivated fired with correct slug' );

// ------------------------------------------------------------------
// Summary
// ------------------------------------------------------------------
WP_CLI::log( '' );
WP_CLI::log( sprintf( '=== Results: %d passed, %d failed ===', $pm_test_pass, $pm_test_fail ) );

if ( $pm_test_fail > 0 ) {
	WP_CLI::error( 'Some tests failed.' );
} else {
	WP_CLI::success( 'All tests passed!' );
}
