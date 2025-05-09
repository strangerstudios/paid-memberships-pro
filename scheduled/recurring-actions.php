<?php

// This is a new class which registers all the scheduled actions (formerly crons) for PMPro.
// It is a replacement for the old crons.php file.
// The class is instantiated in the pmpro_init function.

/**
 * Class PMPro_Scheduled_Actions
 *
 * @since 2.8
 */
class PMPro_Scheduled_Actions {

	/**
	 * The batch limit for the scheduled actions.
	 */
	private $batch_limit;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Inherit the batch limit from the PMPro cron settings or default to 50.
		$this->batch_limit = defined( 'PMPRO_CRON_LIMIT' ) ? PMPRO_CRON_LIMIT : 50;

		add_action( 'pmpro_schedule_daily', array( $this, 'membership_expiration_reminders' ) );
		add_action( 'pmpro_expiration_reminder_email', array( $this, 'pmpro_send_expiration_notice' ), 10, 2 );
	}
	/**
	 * Schedule the membership expiration reminder emails.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function membership_expiration_reminders() {
		global $wpdb;

		// Don't let anything run if PMPro is paused
		if ( pmpro_is_paused() ) {
			return;
		}

		// clean up errors in the memberships_users table that could cause problems
		pmpro_cleanup_memberships_users_table();

		$today = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

		$pmpro_email_days_before_expiration = apply_filters( 'pmpro_email_days_before_expiration', 7 );

		// Configure the interval to select records from
		$interval_start = $today;
		$interval_end   = date( 'Y-m-d H:i:s', strtotime( "{$today} +{$pmpro_email_days_before_expiration} days", current_time( 'timestamp' ) ) );

		// look for memberships that are going to expire within one week (but we haven't emailed them within a week)
		$sqlQuery = $wpdb->prepare(
			"SELECT DISTINCT
						mu.user_id,
						mu.membership_id,
						mu.startdate,
						mu.enddate,
						um.meta_value AS notice
					FROM {$wpdb->pmpro_memberships_users} AS mu
					LEFT JOIN {$wpdb->usermeta} AS um ON um.user_id = mu.user_id
					AND um.meta_key = CONCAT( 'pmpro_expiration_notice_', mu.membership_id )
				WHERE ( um.meta_value IS NULL OR DATE_ADD(um.meta_value, INTERVAL %d DAY) < %s )
					AND ( mu.status = 'active' )
						AND ( mu.enddate IS NOT NULL )
						AND ( mu.enddate <> '0000-00-00 00:00:00' )
						AND ( mu.enddate BETWEEN %s AND %s )
						AND mu.membership_id IS NOT NULL
						AND mu.membership_id <> 0
				ORDER BY mu.enddate
				",
			$pmpro_email_days_before_expiration,
			$today,
			$interval_start,
			$interval_end
		);

		$query_offset = 0;
		$query_limit  = $this->batch_limit;

		do {
			$batched_query = $sqlQuery . $wpdb->prepare( ' LIMIT %d OFFSET %d', $query_limit, $query_offset );
			$expiring_soon = $wpdb->get_results( $batched_query );

			if ( empty( $expiring_soon ) ) {
				break;
			}

			foreach ( $expiring_soon as $e ) {
				PMPro_Action_Scheduler::instance()->maybe_add_task(
					'pmpro_expiration_reminder_email',
					array(
						'user_id'       => $e->user_id,
						'membership_id' => $e->membership_id,
					),
					'pmpro_expiration_tasks'
				);
			}

			$query_offset += $query_limit;
		} while ( count( $expiring_soon ) === $query_limit );
	}

	/**
	 * Send the expiration notice email.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $enddate The end date of the membership.
	 *
	 * @return void
	 */
	function pmpro_send_expiration_notice( $args ) {

		$user_id       = $args['user_id'] ?? null;
		$membership_id = $args['membership_id'] ?? null;

		if ( empty( $user_id ) || empty( $membership_id ) ) {
			return;
		}

		$send_email = apply_filters( 'pmpro_send_expiration_warning_email', true, $user_id );

		if ( $send_email ) {
			// send an email
			$pmpro_email = new PMProEmail();
			$user        = get_userdata( $user_id );
			if ( ! empty( $user ) ) {
				$pmpro_email->sendMembershipExpiringEmail( $user, $membership_id );

				if ( WP_DEBUG ) {
					error_log( sprintf( esc_html__( 'Membership expiring email sent to %s. ', 'paid-memberships-pro' ), $user->user_email ) );
				}
			}
		}

		// delete all user meta for this key to prevent duplicate user meta rows
		delete_user_meta( $user_id, 'pmpro_expiration_notice' );

		// update user meta so we don't email them again
		update_user_meta( $user_id, 'pmpro_expiration_notice_' . $membership_id, current_time( 'Y-m-d H:i:s' ) );
	}

}
