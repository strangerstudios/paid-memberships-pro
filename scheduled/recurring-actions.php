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

		// Membership expiration reminders (Daily)
		add_action( 'pmpro_schedule_daily', array( $this, 'membership_expiration_reminders' ) );
		add_action( 'pmpro_expiration_reminder_email', array( $this, 'pmpro_send_expiration_reminder' ), 10, 2 );

		// Expired Membership Routines (Daily)
		add_action( 'pmpro_schedule_daily', array( $this, 'pmpro_expire_memberships' ) );
		add_action( 'pmpro_membership_expired_email', array( $this, 'pmpro_send_membership_expired_email' ), 10, 2 );

		// Admin activity emails (Conditionally Hooked)
		$this->conditionally_hook_admin_activity_email();

		// Schedule recurring payment reminder tasks.
		add_action( 'pmpro_schedule_daily', array( $this, 'schedule_recurring_payment_reminder_tasks' ) );

		// Register admin activity email hook.
		add_action( 'pmpro_admin_activity_email', array( $this, 'pmpro_admin_activity_email' ) );

		// Register recurring payment reminder email hook.
		add_action( 'pmpro_recurring_payment_reminder_email', array( $this, 'send_recurring_payment_reminder_email' ), 10, 3 );

		// Temporary file cleanup (Daily)
		add_action( 'pmpro_schedule_daily', array( $this, 'pmpro_delete_tmp' ) );

		// Backwards compatibility for the old cron hooks.
		add_action( 'pmpro_cron_expire_memberships', array( $this, 'pmpro_expire_memberships' ) );
		add_action( 'pmpro_cron_expiration_reminder_email', array( $this, 'pmpro_send_membership_expired_email' ), 10, 2 );
		add_action( 'pmpro_cron_membership_expiration_reminders', array( $this, 'membership_expiration_reminders' ) );
		add_action( 'pmpro_cron_send_expiration_reminder', array( $this, 'pmpro_send_expiration_reminder' ), 10, 2 );
		add_action( 'pmpro_cron_admin_activity_email', array( $this, 'pmpro_admin_activity_email' ) );
		add_action( 'pmpro_cron_recurring_payment_reminders', 'pmpro_cron_recurring_payment_reminders' );
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
	 * @param int $user_id The user ID.
	 * @param int $membership_id The membership ID.
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

	/**
	 * Send the admin activity email.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function pmpro_admin_activity_email() {

		// Check if the admin activity email is enabled.
		if ( ! get_option( 'pmpro_activity_email_enabled' ) ) {
			return;
		}

		$pmproemail = new PMPro_Admin_Activity_Email();
		$pmproemail->sendAdminActivity();

		if ( WP_DEBUG ) {
			error_log( sprintf( __( 'Admin activity email sent to %s. ', 'paid-memberships-pro' ), $user->user_email ) );
		}
	}

	/**
	 * Conditionally hook pmpro_admin_activity_email to scheduled tasks.
	 *
	 * @since 3.5
	 * @return void
	 */
	private function conditionally_hook_admin_activity_email() {
		add_action(
			'pmpro_schedule_daily',
			function () {
				if ( get_option( 'pmpro_activity_email_frequency' ) === 'day' ) {
					$this->pmpro_admin_activity_email();
				}
			}
		);

		add_action(
			'pmpro_schedule_weekly',
			function () {
				if ( get_option( 'pmpro_activity_email_frequency' ) === 'week' ) {
					$this->pmpro_admin_activity_email();
				}
			}
		);

		add_action(
			'pmpro_schedule_monthly',
			function () {
				if ( get_option( 'pmpro_activity_email_frequency' ) === 'month' ) {
					$this->pmpro_admin_activity_email();
				}
			}
		);
	}

	/**
	 * Schedule recurring payment reminder tasks.
	 *
	 * @return void
	 */
	public function schedule_recurring_payment_reminder_tasks() {
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
					OR last_next_payment_date.meta_value != subscription.next_payment_date
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

			foreach ( $subscriptions_to_notify as $subscription_to_notify ) {
				PMPro_Action_Scheduler::instance()->maybe_add_task(
					'pmpro_recurring_payment_reminder_email',
					array(
						'subscription_id' => $subscription_to_notify->id,
						'template'        => $template,
						'days'            => $days,
					),
					'pmpro_async_tasks'
				);
			}
			$previous_days = $days;
		}
	}

	/**
	 * Send recurring payment reminder email.
	 *
	 * @param int    $subscription_id The subscription ID.
	 * @param string $template The template name.
	 * @param int    $days The number of days before payment.
	 * @return void
	 */
	public function send_recurring_payment_reminder_email( $subscription_id, $template, $days ) {
		$subscription_obj = new PMPro_Subscription( $subscription_id );
		$user             = get_userdata( $subscription_obj->get_user_id() );

		if ( empty( $user ) ) {
			update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_next_payment_date', $subscription_obj->get_next_payment_date() );
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
				'membership_level_name' => empty( $membership_level ) ? sprintf( esc_html__( '[Deleted level #%d]', 'pmpro-recurring-emails' ), $subscription_obj->get_membership_level_id() ) : $membership_level->name,
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

		update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_next_payment_date', $subscription_obj->get_next_payment_date() );
		update_pmpro_subscription_meta( $subscription_id, 'pmprorm_last_days', $days );
	}

	/**
	 * Delete old files in wp-content/uploads/pmpro-register-helper/tmp.
	 *
	 * @since 3.5
	 * @return void
	 */
	public function pmpro_delete_tmp() {
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
}
