<?php

// This is a new class which registers all the Action Scheduler recurring actions (formerly crons) for PMPro.
// The class is instantiated/hooked on plugins_loaded in paid-memberships-pro.php.

/**
 * Class PMPro_Recurring_Actions
 *
 * @since 2.8
 */
class PMPro_Recurring_Actions {

	/**
	 * Singleton instance.
	 *
	 * @var PMPro_Recurring_Actions|null
	 */
	private static $instance = null;

	/**
	 * The batch limit for the scheduled actions.
	 */
	private $query_batch_limit;

	/**
	 * The minimum date used for PMPro magic dates.
	 *
	 * This is used to ensure that we don't have any invalid dates in the database.
	 * It is also used as a default value for end dates in the memberships_users table.
	 *
	 * @since 3.5
	 */
	const PMPRO_MAGIC_MIN_DATE = '1000-01-01 00:00:00';

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Check if Action Scheduler is installed and activated.
		if ( ! class_exists( \ActionScheduler::class ) ) {
			return;
		}

		// Inherit the batch limit from the former PMPro cron constant, or default to 250.
		$this->query_batch_limit = defined( 'PMPRO_CRON_LIMIT' ) ? PMPRO_CRON_LIMIT : 250;

		// Schedule cleanup actions previously handled by pmpro_cleanup_memberships_users_table()
		add_action( 'pmpro_schedule_weekly', array( $this, 'check_inactive_memberships' ) );
		add_action( 'pmpro_schedule_weekly', array( $this, 'check_duplicate_active_rows' ) );

		// Make sure that the restricted files directory is set up.
		add_action( 'pmpro_schedule_daily', 'pmpro_set_up_restricted_files_directory' );

		// Expired Membership Routines (Every 15 minutes)
		add_action( 'pmpro_schedule_quarter_hourly', array( $this, 'check_for_expired_memberships' ) );
		add_action( 'pmpro_expire_memberships', array( $this, 'expire_memberships' ), 10, 2 );
		add_action( 'pmpro_membership_expired_email', array( $this, 'send_membership_expired_email' ), 10, 2 );

		// Membership expiration reminders (Every 15 minutes)
		add_action( 'pmpro_schedule_quarter_hourly', array( $this, 'membership_expiration_reminders' ), 99 );
		add_action( 'pmpro_expiration_reminder_email', array( $this, 'send_expiration_reminder_email' ), 99, 2 );

		// Admin activity emails (Conditionally Hooked based on frequency)
		$this->conditionally_hook_admin_activity_email();

		// Register recurring payment reminders.
		add_action( 'pmpro_schedule_quarter_hourly', array( $this, 'recurring_payment_reminders' ) );
		add_action( 'pmpro_recurring_payment_reminder_email', array( $this, 'send_recurring_payment_reminder_email' ), 10, 3 );

		// Temporary file cleanup (Daily)
		add_action( 'pmpro_schedule_daily', array( $this, 'delete_temp_files' ) );

		// License check (Monthly)
		add_action( 'pmpro_schedule_monthly', 'pmpro_license_check_key' );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return PMPro_Recurring_Actions
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
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function check_inactive_memberships() {
		if ( pmpro_is_paused() ) {
			return;
		}
		self::fix_inactive_memberships();
	}

	/**
	 * Check for duplicate active rows in the memberships_users table.
	 *
	 * This function is called weekly to resolve any duplicate active rows
	 * in the memberships_users table.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function check_duplicate_active_rows() {
		// Don't let anything run if PMPro is paused
		if ( pmpro_is_paused() ) {
			return;
		}

		self::resolve_duplicate_active_rows();
	}

	/**
	 * Schedule the membership expiration reminder emails.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function membership_expiration_reminders() {

		global $wpdb;

		$today = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

		// Get the number of days before expiration to send the email. Filterable.
		// Default is 7 days.
		$pmpro_email_days_before_expiration = apply_filters( 'pmpro_email_days_before_expiration', 7 );

		// Configure the interval to select records from
		$interval_start = $today;
		$interval_end   = date( 'Y-m-d H:i:s', strtotime( "+{$pmpro_email_days_before_expiration} days", current_time( 'timestamp' ) ) );

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
					AND mu.enddate > %s
					AND mu.enddate BETWEEN %s AND %s
					AND mu.membership_id IS NOT NULL
					AND mu.membership_id <> 0
				ORDER BY mu.enddate
				",
			$pmpro_email_days_before_expiration,
			$today,
			self::PMPRO_MAGIC_MIN_DATE,
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

			// Halt Action Scheduler processing to wait until we finish adding tasks.
			PMPro_Action_Scheduler::instance()->halt();

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

		// If we paused the Action Scheduler, unpause it now.
		PMPro_Action_Scheduler::instance()->resume();
	}

	/**
	 * Send the membership expiration reminder email.
	 *
	 * @access public
	 * @since 3.5
	 * @param int $user_id The user ID.
	 * @param int $membership_id The membership ID.
	 * @return void
	 */
	public function send_expiration_reminder_email( $user_id = null, $membership_id = null ) {

		if ( WP_DEBUG ) {
			error_log( '[PMPro Notice Args] ' . print_r( compact( 'user_id', 'membership_id' ), true ) );
		}

		if ( empty( $user_id ) || empty( $membership_id ) ) {
			return;
		}

		// Double-check: Only send if the user still meets the requirements.
		// There may be a gap between the time we check and the time we actually send them.
		$membership = pmpro_getSpecificMembershipLevelForUser( $user_id, $membership_id );
		if (
		empty( $membership ) ||
		empty( $membership->enddate ) ||
		$membership->enddate <= current_time( 'timestamp' )
		) {
			// User is not eligible for a reminder email.
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

		// Update user meta so we don't email them again
		update_user_meta( $user_id, 'pmpro_expiration_notice_' . $membership_id, current_time( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Check for expired memberships, remove them, and send emails.
	 *
	 * This function is called daily.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function check_for_expired_memberships() {
		global $wpdb;

		$today = date( 'Y-m-d H:i:00', current_time( 'timestamp' ) );

		$sqlQuery = $wpdb->prepare(
			"SELECT mu.user_id, mu.membership_id
			 FROM {$wpdb->pmpro_memberships_users} mu
			 WHERE mu.status = 'active'
			   AND mu.enddate IS NOT NULL
			   AND mu.enddate > %s
			   AND mu.enddate <= %s
			 ORDER BY mu.enddate",
			self::PMPRO_MAGIC_MIN_DATE,
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

			// Halt Action Scheduler processing to wait until we finish adding tasks.
			PMPro_Action_Scheduler::instance()->halt();

			foreach ( $expired as $e ) {
				// Add the task to send the membership expired email.
				PMPro_Action_Scheduler::instance()->maybe_add_task(
					'pmpro_expire_memberships',
					array(
						'user_id'       => $e->user_id,
						'membership_id' => $e->membership_id,
					),
					'pmpro_expiration_tasks'
				);
			}

			$query_offset += $query_limit;
		} while ( count( $expired ) === $query_limit );

		// If we paused the Action Scheduler, unpause it now.
		PMPro_Action_Scheduler::instance()->resume();
	}

	/**
	 * Expire memberships for a user and send an email.
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param int $user_id The user ID.
	 * @param int $membership_id The membership ID.
	 *
	 * @return void
	 */
	public function expire_memberships( $user_id, $membership_id ) {

		// Double-check: Only expire if the user still meets the requirements for expiration.
		// There may be a gap between the time we check for expired memberships and the time we actually expire them.
		$membership = pmpro_getSpecificMembershipLevelForUser( $user_id, $membership_id );
		if (
			empty( $membership ) ||
			empty( $membership->enddate ) ||
			// Check if the membership end date is in the future
			$membership->enddate > current_time( 'timestamp' )
		) {
			// User is not eligible for expiration.
			return;
		}

		do_action( 'pmpro_membership_pre_membership_expiry', $user_id, $membership_id );
		// Remove their membership
		pmpro_cancelMembershipLevel( $membership_id, $user_id, 'expired' );
		do_action( 'pmpro_membership_post_membership_expiry', $user_id, $membership_id );
		$this->send_membership_expired_email( $user_id, $membership_id );
	}

	/**
	 * Send the membership expired email.
	 *
	 * @access private
	 * @since 3.5
	 *
	 * @param int $user_id The user ID.
	 * @param int $membership_id The membership ID.
	 *
	 * @return void
	 */
	private function send_membership_expired_email( $user_id, $membership_id ) {

		$send_email = true;

		if ( get_user_meta( $user_id, 'pmpro_disable_notifications', true ) ) {
			$send_email = false;
		}

		// Allow filtering of the email sending.
		$send_email = apply_filters( 'pmpro_send_expiration_email', true, $user_id );

		if ( $send_email ) {
			// send an email
			$pmproemail = new PMProEmail();
			$euser      = get_userdata( $user_id );
			if ( ! empty( $euser ) ) {
				$pmproemail->sendMembershipExpiredEmail( $euser, $membership_id );
				// Delete the expiration notice for this membership
				delete_user_meta( $user_id, 'pmpro_expiration_notice_' . $membership_id );
				if ( WP_DEBUG ) {
					error_log( sprintf( __( 'Membership expired email sent to %s. ', 'paid-memberships-pro' ), $euser->user_email ) );
				}
			}
		}
	}

	/**
	 * Send the admin activity email.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function send_admin_activity_email() {
		(new PMPro_Admin_Activity_Email())->sendAdminActivity();
	}

	/**
	 * Conditionally hook admin_activity_email to scheduled tasks.
	 *
	 * @access private
	 * @since 3.5
	 * @return void
	 */
	private function conditionally_hook_admin_activity_email() {
		add_action(
			'pmpro_schedule_daily',
			function () {
				if ( get_option( 'pmpro_activity_email_frequency' ) === 'day' ) {
					$this->send_admin_activity_email();
				}
			}
		);

		add_action(
			'pmpro_schedule_weekly',
			function () {
				if ( get_option( 'pmpro_activity_email_frequency' ) === 'week' ) {
					$this->send_admin_activity_email();
				}
			}
		);

		add_action(
			'pmpro_schedule_monthly',
			function () {
				if ( get_option( 'pmpro_activity_email_frequency' ) === 'month' ) {
					$this->send_admin_activity_email();
				}
			}
		);
	}

	/**
	 * Schedule recurring payment reminder tasks.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function recurring_payment_reminders() {
		global $wpdb;

		$emails = apply_filters(
			'pmpro_upcoming_recurring_payment_reminder',
			array( 7 => 'membership_recurring' )
		);
		ksort( $emails, SORT_NUMERIC );

		$previous_days = 0;

		foreach ( $emails as $days => $template ) {
			$sqlQuery = $wpdb->prepare(
				"SELECT subscription.*
				FROM {$wpdb->pmpro_subscriptions} subscription
				LEFT JOIN {$wpdb->pmpro_subscriptionmeta} last_next_payment_date 
					ON subscription.id = last_next_payment_date.pmpro_subscription_id
					AND last_next_payment_date.meta_key = 'pmprorm_last_next_payment_date'
				LEFT JOIN {$wpdb->pmpro_subscriptionmeta} last_days
					ON subscription.id = last_days.pmpro_subscription_id
					AND last_days.meta_key = 'pmprorm_last_days'
				WHERE subscription.status = 'active'
				AND subscription.next_payment_date >= %s
				AND subscription.next_payment_date < %s
				AND ( last_next_payment_date.meta_value IS NULL
					OR last_next_payment_date.meta_value != DATE(subscription.next_payment_date)
					OR last_days.meta_value > %d
				)",
				date_i18n( 'Y-m-d', strtotime( "+{$previous_days} days", current_time( 'timestamp' ) ) ),
				date_i18n( 'Y-m-d', strtotime( "+{$days} days", current_time( 'timestamp' ) ) ),
				$days
			);

			$subscriptions_to_notify = $wpdb->get_results( $sqlQuery );
			if ( is_wp_error( $subscriptions_to_notify ) ) {
				continue;
			}

			if ( count( $subscriptions_to_notify ) > 250 ) {
				PMPro_Action_Scheduler::instance()->halt();
			}

			foreach ( $subscriptions_to_notify as $subscription_to_notify ) {
				PMPro_Action_Scheduler::instance()->maybe_add_task(
					'pmpro_recurring_payment_reminder_email',
					array(
						'subscription_id' => $subscription_to_notify->id,
						'template'        => $template,
					),
					'pmpro_async_tasks'
				);
			}
			$previous_days = $days;

			// If we paused the Action Scheduler, unpause it now.
			PMPro_Action_Scheduler::instance()->resume();
		}
	}

	/**
	 * Send recurring payment reminder email.
	 *
	 * @access public
	 * @since 3.5
	 * @param int    $subscription_id The subscription ID.
	 * @param string $template The template name.
	 * @param int    $days The number of days before payment.
	 * @return void
	 */
	public function send_recurring_payment_reminder_email( $subscription_id, $template ) {
		$subscription_obj = new PMPro_Subscription( $subscription_id );
		$user             = get_userdata( $subscription_obj->get_user_id() );

		// Calculate the days until the next payment.
		$days = floor( ( $subscription_obj->get_next_payment_date() - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );

		if ( empty( $user ) ) {
			update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_next_payment_date', $subscription_obj->get_next_payment_date( 'Y-m-d', false ) );
			update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_days', $days );
			return;
		}

		$send_email  = apply_filters( 'pmpro_send_recurring_payment_reminder_email', true, $subscription_obj, $days );
		$send_emails = apply_filters_deprecated( 'pmprorm_send_reminder_to_user', array( $send_email, $user, null ), '3.2' );

		if ( $send_emails && 'membership_recurring' == $template ) {
			$pmproemail = new PMPro_Email_Template_Membership_Recurring( $subscription_obj );
			$pmproemail->send();
		} elseif ( $send_emails ) {
			$membership_level     = pmpro_getLevel( $subscription_obj->get_membership_level_id() );
			$pmproemail           = new PMProEmail();
			$pmproemail->email    = $user->user_email;
			$pmproemail->template = $template;
			$pmproemail->data     = array(
				'subject'               => $pmproemail->subject,
				'name'                  => $user->display_name,
				'user_login'            => $user->user_login,
				'sitename'              => get_option( 'blogname' ),
				'membership_id'         => $subscription_obj->get_membership_level_id(),
				'membership_level_name' => empty( $membership_level ) ? sprintf( esc_html__( '[Deleted level #%d]', 'paid-memberships-pro' ), $subscription_obj->get_membership_level_id() ) : $membership_level->name,
				'membership_cost'       => $subscription_obj->get_cost_text(),
				'billing_amount'        => pmpro_formatPrice( $subscription_obj->get_billing_amount() ),
				'renewaldate'           => date_i18n( get_option( 'date_format' ), $subscription_obj->get_next_payment_date() ),
				'siteemail'             => get_option( 'pmpro_from_email' ),
				'login_link'            => wp_login_url(),
				'display_name'          => $user->display_name,
				'user_email'            => $user->user_email,
				'cancel_link'           => wp_login_url( pmpro_url( 'cancel' ) ),
			);
			$pmproemail->sendEmail();
		}

		update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_next_payment_date', $subscription_obj->get_next_payment_date( 'Y-m-d', false ) );
		update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_days', $days );
	}

	/**
	 * Delete old files in wp-content/uploads/pmpro-register-helper/tmp.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function delete_temp_files() {
		$upload_dir  = wp_upload_dir();
		$pmprorh_dir = trailingslashit( $upload_dir['basedir'] ) . 'paid-memberships-pro/tmp/';

		if ( ! file_exists( $pmprorh_dir ) || ! is_dir( $pmprorh_dir ) ) {
			return;
		}

		if ( $handle = opendir( $pmprorh_dir ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				$filepath = $pmprorh_dir . $file;

				// Skip special directories.
				if ( in_array( $file, array( '.', '..' ), true ) ) {
					continue;
				}

				// Only delete real files older than an hour.
				if ( is_file( $filepath ) && ! is_link( $filepath ) && ( time() - filemtime( $filepath ) ) > HOUR_IN_SECONDS ) {
					unlink( $filepath );

					if ( WP_DEBUG ) {
						error_log( '[PMPro] Deleted temp file: ' . $filepath );
					}
				}
			}
			closedir( $handle );
		}
	}

	/**
	 * Disable memberships referencing non-existent levels.
	 *
	 * @access private
	 * @since 3.5
	 * @global object $wpdb
	 *
	 * @return void
	 */
	private static function fix_inactive_memberships() {
		global $wpdb;
		$sql_query = "UPDATE {$wpdb->pmpro_memberships_users} mu
			LEFT JOIN {$wpdb->pmpro_membership_levels} l ON mu.membership_id = l.id 
			SET mu.status = 'inactive' 
			WHERE mu.status = 'active' 
			AND l.id IS NULL";
		$wpdb->query( $sql_query );
	}

	/**
	 * Resolves duplicate active rows for a single user and membership level.
	 *
	 * @access private
	 * @since 3.5
	 * @global object $wpdb
	 *
	 * @return void
	 */
	private static function resolve_duplicate_active_rows() {
		global $wpdb;
		// Fix rows where there is more than one active status for the same user/level
		$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users t1
				INNER JOIN (SELECT mu1.id as id
				FROM $wpdb->pmpro_memberships_users mu1, $wpdb->pmpro_memberships_users mu2
				WHERE mu1.id < mu2.id
					AND mu1.user_id = mu2.user_id
					AND mu1.membership_id = mu2.membership_id
					AND mu1.status = 'active'
					AND mu2.status = 'active'
				GROUP BY mu1.id
				ORDER BY mu1.user_id, mu1.id DESC) t2
				ON t1.id = t2.id
				SET t1.status = 'inactive'";
		$wpdb->query( $sqlQuery );
	}
}
