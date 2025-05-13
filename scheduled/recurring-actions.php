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
	 * Singleton instance.
	 *
	 * @var PMPro_Scheduled_Actions|null
	 */
	private static $instance = null;

	/**
	 * The batch limit for the scheduled actions.
	 */
	private $query_batch_limit;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Inherit the batch limit from the former PMPro cron settings or default to 50.
		$this->query_batch_limit = defined( 'PMPRO_CRON_LIMIT' ) ? PMPRO_CRON_LIMIT : 50;

		// Schedule cleanup actions previously handled by pmpro_cleanup_memberships_users_table()
		add_action( 'pmpro_schedule_weekly', array( $this, 'pmpro_check_inactive_memberships' ) );
		add_action( 'pmpro_schedule_weekly', array( $this, 'resolve_duplicate_active_rows' ) );

		// Membership expiration reminders
		add_action( 'pmpro_schedule_daily', array( $this, 'membership_expiration_reminders' ) );
		add_action( 'pmpro_expiration_reminder_email', array( $this, 'pmpro_send_expiration_reminder' ), 10, 2 );

		// Expired Membership Routines
		add_action( 'pmpro_schedule_daily', array( $this, 'pmpro_expire_memberships' ) );
		add_action( 'pmpro_membership_expired_email', array( $this, 'pmpro_send_membership_expired_email' ), 10, 2 );

	}

	/**
	 * Get the singleton instance.
	 *
	 * @return PMPro_Scheduled_Actions
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Check if we need to fix inactive memberships.
	 *
	 * This function is called weekly to fix any inactive memberships.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function pmpro_check_inactive_memberships() {
		if ( pmpro_is_paused() ) {
			return;
		}
		PMPro_Membership_Level::fix_inactive_memberships();
	}

	/**
	 * Check for duplicate active rows in the memberships_users table.
	 *
	 * This function is called weekly to resolve any duplicate active rows
	 * in the memberships_users table.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function resolve_duplicate_active_rows() {
		// Don't let anything run if PMPro is paused
		if ( pmpro_is_paused() ) {
			return;
		}

		PMPro_Membership_Level::resolve_duplicate_active_rows();
	}

	/**
	 * Schedule the membership expiration reminder emails.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function membership_expiration_reminders() {

		global $wpdb;

		$today = date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );

		// Get the number of days before expiration to send the email. Filterable.
		// Default is 7 days.
		$pmpro_email_days_before_expiration = apply_filters( 'pmpro_email_days_before_expiration', 7 );

		// Configure the interval to select records from
		$interval_start = $today;
		$interval_end   = date( 'Y-m-d 00:00:00', strtotime( "+{$pmpro_email_days_before_expiration} days", current_time( 'timestamp' ) ) );

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
					AND mu.status = 'active'
					AND mu.enddate IS NOT NULL
					AND mu.enddate > '1000-01-01 00:00:00'
					AND mu.enddate BETWEEN %s AND %s
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
		$query_limit  = $this->query_batch_limit;

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
	 * Send the membership expiration reminder email.
	 *
	 * @param int $user_id The user ID.
	 * @param int $membership_id The membership ID.
	 *
	 * @return void
	 */
	function pmpro_send_expiration_reminder( $user_id = null, $membership_id = null ) {

		if ( WP_DEBUG ) {
			error_log( '[PMPro Notice Args] ' . print_r( compact( 'user_id', 'membership_id' ), true ) );
		}

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

	/**
	 * Check for expired memberships and send emails.
	 *
	 * This function is called daily.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function pmpro_expire_memberships() {
		global $wpdb;

		$today = date( 'Y-m-d H:i:00', current_time( 'timestamp' ) );

		$sqlQuery = $wpdb->prepare(
			"SELECT mu.user_id, mu.membership_id
	         FROM {$wpdb->pmpro_memberships_users} mu
	         WHERE mu.status = 'active'
	           AND mu.enddate IS NOT NULL
	           AND mu.enddate > '1000-01-01 00:00:00'
	           AND mu.enddate <= %s
	         ORDER BY mu.enddate",
			$today
		);

		$query_offset = 0;
		$query_limit  = $this->query_batch_limit;

		do {
			$batched_query = $sqlQuery . $wpdb->prepare( ' LIMIT %d OFFSET %d', $query_limit, $query_offset );
			$expired       = $wpdb->get_results( $batched_query );

			if ( empty( $expired ) ) {
				break;
			}

			foreach ( $expired as $e ) {
				PMPro_Action_Scheduler::instance()->maybe_add_task(
					'pmpro_membership_expired_email',
					array(
						'user_id'       => $e->user_id,
						'membership_id' => $e->membership_id,
					),
					'pmpro_expiration_tasks'
				);
			}

			$query_offset += $query_limit;
		} while ( count( $expired ) === $query_limit );
	}

	/**
	 * Send the membership expired email.
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param int    $user_id The user ID.
	 * @param int    $membership_id The membership ID.
	 *
	 * @return void
	 */
	public function pmpro_send_membership_expired_email( $user_id, $membership_id ) {

		do_action( 'pmpro_membership_pre_membership_expiry', $user_id, $membership_id );

		// remove their membership
		pmpro_cancelMembershipLevel( $membership_id, $user_id, 'expired' );

		do_action( 'pmpro_membership_post_membership_expiry', $user_id, $membership_id );

		if ( get_user_meta( $user_id, 'pmpro_disable_notifications', true ) ) {
			$send_email = false;
		}

		$send_email = apply_filters( 'pmpro_send_expiration_email', true, $user_id );

		if ( $send_email ) {
			// send an email
			$pmproemail = new PMProEmail();
			$euser      = get_userdata( $user_id );
			if ( ! empty( $euser ) ) {
				$pmproemail->sendMembershipExpiredEmail( $euser, $membership_id );

				if ( WP_DEBUG ) {
					error_log( sprintf( __( 'Membership expired email sent to %s. ', 'paid-memberships-pro' ), $euser->user_email ) );
				}
			}
		}
	}
}
