<?php

add_action( 'pmpro_cron_expiration_warnings', 'pmpro_cron_expiration_warnings' );

function pmpro_cron_expiration_warnings() {
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
 			    AND ( mu.membership_id <> 0 OR mu.membership_id <> NULL )
			ORDER BY mu.enddate
			",
		$pmpro_email_days_before_expiration,
		$today,
		$interval_start,
		$interval_end
	);

	if ( defined( 'PMPRO_CRON_LIMIT' ) ) {
		$sqlQuery .= ' LIMIT ' . PMPRO_CRON_LIMIT;
	}

	$expiring_soon = $wpdb->get_results( $sqlQuery );

	foreach ( $expiring_soon as $e ) {
		$send_email = apply_filters( 'pmpro_send_expiration_warning_email', true, $e->user_id );
		if ( $send_email ) {
			// send an email
			$pmproemail = new PMProEmail();
			$euser      = get_userdata( $e->user_id );
			if ( ! empty( $euser ) ) {
				$pmproemail->sendMembershipExpiringEmail( $euser, $e->membership_id );

				if ( WP_DEBUG ) {
					error_log( sprintf( esc_html__( 'Membership expiring email sent to %s. ', 'paid-memberships-pro' ), $euser->user_email ) );
				}
			}
		}

		// delete all user meta for this key to prevent duplicate user meta rows
		delete_user_meta( $e->user_id, 'pmpro_expiration_notice' );

		// update user meta so we don't email them again
		update_user_meta( $e->user_id, 'pmpro_expiration_notice_' . $e->membership_id, $today );
	}
}
