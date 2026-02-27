<?php
/**
 * Plugin Name: PMPro Pause Engine Test Code
 * Description: Test code for PMPro plugin.
 * Version: 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quick Pause Engine tester.
 *
 * Usage (as admin, on any admin page):
 *   ?pmpro_pause=migration      — Activate "Migration" preset (full lockdown)
 *   ?pmpro_pause=maintenance    — Activate "Maintenance" preset
 *   ?pmpro_pause=resume         — Resume all modules
 *   ?pmpro_pause=status         — Dump current state to screen
 *   ?pmpro_pause=custom&modules=pmpro_mutations,pmpro_gateways  — Activate specific modules
 */
add_action( 'admin_init', function() {
	if ( ! isset( $_GET['pmpro_pause'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action     = sanitize_text_field( $_GET['pmpro_pause'] );
	$engine     = PMPro_Pause_Engine::instance();
	$page_title = 'PMPro Pause Engine Test Suite';

	switch ( $action ) {
		case 'migration':
		case 'maintenance':
			$result = $engine->pause_with_preset( $action );
			wp_die(
				'<h2>Pause Engine: ' . esc_html( $action ) . ' preset</h2>'
				. '<p>Result: ' . ( $result ? 'Activated' : 'Failed (already active or invalid)' ) . '</p>'
				. '<p><a href="' . esc_url( admin_url( '?pmpro_pause=status' ) ) . '">View status</a> | '
				. '<a href="' . esc_url( admin_url( '?pmpro_pause=resume' ) ) . '">Resume</a></p>',
				$page_title
			);
			break;

		case 'custom':
			$modules = isset( $_GET['modules'] ) ? array_map( 'trim', explode( ',', sanitize_text_field( $_GET['modules'] ) ) ) : array();
			$result  = $engine->pause( $modules, 'manual-test' );
			wp_die(
				'<h2>Pause Engine: Custom modules</h2>'
				. '<p>Modules requested: <code>' . esc_html( implode( ', ', $modules ) ) . '</code></p>'
				. '<p>Result: ' . ( $result ? 'Activated' : 'Failed (no valid modules)' ) . '</p>'
				. '<p><a href="' . esc_url( admin_url( '?pmpro_pause=status' ) ) . '">View status</a> | '
				. '<a href="' . esc_url( admin_url( '?pmpro_pause=resume' ) ) . '">Resume</a></p>',
				$page_title
			);
			break;

		case 'resume':
			$result = $engine->resume();
			wp_die(
				'<h2>Pause Engine: Resume</h2>'
				. '<p>Result: ' . ( $result ? 'Resumed' : 'Was not paused' ) . '</p>'
				. '<p><a href="' . esc_url( admin_url( '?pmpro_pause=status' ) ) . '">View status</a></p>',
				$page_title
			);
			break;

		case 'status':
			$state   = $engine->get_state();
			$paused  = $engine->is_paused();
			$output  = '<h2>Pause Engine Status</h2>';
			$output .= '<p><strong>Active:</strong> ' . ( $paused ? 'YES' : 'No' ) . '</p>';

			if ( $paused ) {
				$output .= '<p><strong>Activated by:</strong> ' . esc_html( $state['activated_by'] ?? 'unknown' ) . '</p>';
				$output .= '<p><strong>Activated at:</strong> ' . esc_html( date( 'Y-m-d H:i:s', $state['activated_at'] ?? 0 ) ) . '</p>';
				$output .= '<p><strong>Active modules:</strong></p><ul>';
				foreach ( $engine->get_active_modules() as $slug ) {
					$output .= '<li><code>' . esc_html( $slug ) . '</code> — is_active(): ' . ( $engine->is_module_active( $slug ) ? 'true' : 'false' ) . '</li>';
				}
				$output .= '</ul>';
				$output .= '<p><a href="' . esc_url( admin_url( '?pmpro_pause=resume' ) ) . '">Resume</a></p>';

				// Module diagnostics.
				$output .= '<hr><h3>Module Diagnostics</h3>';

				// Mutations: try a level change.
				if ( $engine->is_module_active( 'pmpro_mutations' ) ) {
					$test_result = apply_filters( 'pmpro_change_level', 1, 0, 'active', 0 );
					$output .= '<p><strong>pmpro_mutations:</strong> pmpro_change_level filter returns: <code>' . var_export( $test_result, true ) . '</code>';
					$output .= ( false === $test_result ) ? ' — but admin bypass should allow it, so this returns the level ID since you are admin.' : '';
					$output .= '</p>';
				}

				// Gateways: test outbound HTTP to Stripe.
				if ( $engine->is_module_active( 'pmpro_gateways' ) ) {
					$test_response = wp_remote_get( 'https://api.stripe.com/v1/tokens', array( 'timeout' => 5 ) );
					if ( is_wp_error( $test_response ) && 'pmpro_pause_engine_blocked' === $test_response->get_error_code() ) {
						$output .= '<p><strong>pmpro_gateways:</strong> Outbound to api.stripe.com — BLOCKED (correct)</p>';
					} else {
						$code = is_wp_error( $test_response ) ? $test_response->get_error_code() : wp_remote_retrieve_response_code( $test_response );
						$output .= '<p><strong>pmpro_gateways:</strong> Outbound to api.stripe.com — NOT blocked (got: ' . esc_html( $code ) . ')</p>';
					}
				}

				// Mail: check for queued emails in Action Scheduler.
				if ( $engine->is_module_active( 'pmpro_mail' ) ) {
					$output .= '<p><strong>pmpro_mail:</strong> ';
					if ( function_exists( 'as_get_scheduled_actions' ) ) {
						$queued = as_get_scheduled_actions( array(
							'hook'   => 'pmpro_pause_engine_send_queued_email',
							'status' => ActionScheduler_Store::STATUS_PENDING,
						) );
						$output .= count( $queued ) . ' email(s) queued in Action Scheduler.';
						$output .= ' <a href="' . esc_url( admin_url( '?pmpro_pause=test_mail' ) ) . '">Send a test email</a>';
					} else {
						$output .= 'Action Scheduler not available.';
					}
					$output .= '</p>';
				}

				// Schedules: check cron and AS state.
				if ( $engine->is_module_active( 'background_schedules' ) ) {
					$cron_blocked = has_filter( 'spawn_cron', '__return_false' );
					$as_blocked   = has_filter( 'action_scheduler_before_execute', '__return_false' );
					$output .= '<p><strong>background_schedules:</strong> ';
					$output .= 'spawn_cron blocked: ' . ( $cron_blocked ? 'YES' : 'NO' );
					$output .= ' | AS execution blocked: ' . ( $as_blocked ? 'YES' : 'NO' );
					$output .= '</p>';
				}

			} else {
				$output .= '<p>Engine is idle.</p>';
			}

			$output .= '<hr><h3>Quick Actions</h3><ul>'
				. '<li><a href="' . esc_url( admin_url( '?pmpro_pause=migration' ) ) . '">Activate Migration preset</a></li>'
				. '<li><a href="' . esc_url( admin_url( '?pmpro_pause=maintenance' ) ) . '">Activate Maintenance preset</a></li>'
				. '<li><a href="' . esc_url( admin_url( '?pmpro_pause=custom&modules=pmpro_mutations' ) ) . '">Activate mutations only</a></li>'
				. '<li><a href="' . esc_url( admin_url( '?pmpro_pause=custom&modules=pmpro_mail,background_schedules' ) ) . '">Activate mail + schedules</a></li>'
				. '</ul>';

			wp_die( $output, $page_title );
			break;

		case 'test_mail':
			// Send a test email to trigger the mail queue.
			wp_mail( get_option( 'admin_email' ), 'Pause Engine Test Email', 'This is a test email queued while the Pause Engine is active.' );
			wp_die(
				'<h2>Test Email Sent</h2>'
				. '<p>wp_mail() was called. If the mail module is active, it should be queued in Action Scheduler instead of sent.</p>'
				. '<p><a href="' . esc_url( admin_url( '?pmpro_pause=status' ) ) . '">Back to status</a></p>',
				$page_title
			);
			break;
	}
});

