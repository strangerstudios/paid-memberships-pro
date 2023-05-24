<?php
/**
 * Functionality related to cron operations.
 *
 * The crons themselves are located in /scheduled/crons.php and in corresponding Add-Ons.
 */

/**
 * Get the list of registered crons for Paid Memberships Pro.
 *
 * @since 2.8
 *
 * @return array The list of registered crons for Paid Memberships Pro.
 */
function pmpro_get_crons() {
	$crons = [
		'pmpro_cron_expire_memberships'            => [
			'interval' => 'hourly',
		],
		'pmpro_cron_expiration_warnings'           => [
			'interval'  => 'hourly',
			'timestamp' => current_time( 'timestamp' ) + 1,
		],
		'pmpro_cron_credit_card_expiring_warnings' => [
			'interval' => 'monthly',
		],
		'pmpro_cron_admin_activity_email'          => [
			'interval'  => 'daily',
			'timestamp' => strtotime( '10:30:00' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ),
		],
		'pmpro_cron_delete_tmp'          => [
			'interval'  => 'daily',
			'timestamp' => strtotime( '10:30:00' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ),
		],
		'pmpro_license_check_key'                  => [
			'interval' => 'monthly',
		],
	];

	/**
	 * Allow filtering the list of registered crons for Paid Memberships Pro.
	 *
	 * @since 2.8
	 *
	 * @param array $crons The list of registered crons for Paid Memberships Pro.
	 */
	$crons = (array) apply_filters( 'pmpro_registered_crons', $crons );

	// Set up the default information for each cron if not set.
	foreach ( $crons as $hook => $cron ) {
		if ( empty( $cron['timestamp'] ) ) {
			$cron['timestamp'] = current_time( 'timestamp' );
		}

		if ( empty( $cron['interval'] ) ) {
			$cron['interval'] = 'hourly';
		}

		if ( empty( $cron['args'] ) ) {
			$cron['args'] = [];
		}

		$crons[ $hook ] = $cron;
	}

	return $crons;
}

/**
 * Maybe schedule our registered crons.
 *
 * @since 2.8
 */
function pmpro_maybe_schedule_crons() {
	$crons = pmpro_get_crons();

	foreach ( $crons as $hook => $cron ) {
		pmpro_maybe_schedule_event( $cron['timestamp'], $cron['interval'], $hook, $cron['args'] );
	}
}

/**
 * Clear all PMPro related crons.
 * @since 2.8.1
 */
function pmpro_clear_crons() {	
	$crons = array_keys( pmpro_get_crons() );
	foreach( $crons as $cron ) {
		wp_clear_scheduled_hook( $cron );
	}
}

/**
 * Handle rescheduling Paid Memberships Pro crons when checking for ready cron tasks.
 *
 * @since 2.8
 *
 * @param null|array[] $pre Array of ready cron tasks to return instead. Default null
 *                          to continue using results from _get_cron_array().
 *
 * @return null|array[] Array of ready cron tasks to return instead. Default null
 *                      to continue using results from _get_cron_array().
 */
function pmpro_handle_schedule_crons_on_cron_ready_check( $pre ) {
	pmpro_maybe_schedule_crons();

	return $pre;
}

add_filter( 'pre_get_ready_cron_jobs', 'pmpro_handle_schedule_crons_on_cron_ready_check' );

/**
 * Schedule a periodic event unless one with the same hook is already scheduled.
 *
 * @since 2.8
 *
 * @see  wp_schedule_event()
 * @link https://developer.wordpress.org/reference/functions/wp_schedule_event/
 *
 * @param int    $timestamp  Unix timestamp (UTC) for when to next run the event.
 * @param string $recurrence How often the event should subsequently recur.
 *                           See wp_get_schedules() for accepted values.
 * @param string $hook       Action hook to execute when the event is run.
 * @param array  $args       Optional. Array containing arguments to pass to the
 *                           hook's callback function. Each value in the array
 *                           is passed to the callback as an individual parameter.
 *                           The array keys are ignored. Default empty array.
 *
 * @return bool|WP_Error True when an event is scheduled, WP_Error on failure, and false if the event was already scheduled.
 */
function pmpro_maybe_schedule_event( $timestamp, $recurrence, $hook, $args = [] ) {
	$next = wp_next_scheduled( $hook, $args );

	if ( empty( $next ) ) {
		return wp_schedule_event( $timestamp, $recurrence, $hook, $args, true );
	}

	return false;
}
