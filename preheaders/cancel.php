<?php
	global $besecure;
	$besecure = false;

	global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_confirm, $pmpro_error;

	// Get the level IDs they are requesting to cancel using the old ?level param.
	if ( ! empty( $_REQUEST['level'] ) && empty( $_REQUEST['levelstocancel'] ) ) {
		$requested_ids = intval( $_REQUEST['level'] );
	}

	// Get the level IDs they are requesting to cancel from the ?levelstocancel param.
	if ( ! empty( $_REQUEST['levelstocancel'] ) && $_REQUEST['levelstocancel'] === 'all' ) {
		$requested_ids = 'all';
	} elseif ( ! empty( $_REQUEST['levelstocancel'] ) ) {		
		// A single ID could be passed, or a few like 1+2+3.
		$requested_ids = str_replace(array(' ', '%20'), '+', sanitize_text_field( $_REQUEST['levelstocancel'] ) );
		$requested_ids = preg_replace("/[^0-9\+]/", "", $requested_ids );
	}	

	// Redirection logic.
	if ( ! is_user_logged_in() ) {
		if ( ! empty( $requested_ids ) ) {
			$redirect = add_query_arg( 'levelstocancel', $requested_ids, pmpro_url( 'cancel' ) );
		} else {
			$redirect = pmpro_url( 'cancel' );
		}
		// Redirect non-user to the login page; pass the Cancel page with specific ?levelstocancel as the redirect_to query arg.
		wp_redirect( add_query_arg( 'redirect_to', urlencode( $redirect ), pmpro_login_url() ) );
		exit;
	} else {
		// Get the membership level for the current user.
		$current_user->membership_level = pmpro_getMembershipLevelForUser( $current_user->ID) ;
		// If user has no membership level, redirect to levels page.
		if ( ! isset( $current_user->membership_level->ID ) ) {
			wp_redirect( pmpro_url( 'levels' ) );
			exit;
		}
	}

	//check if a level was passed in to cancel specifically
	if ( ! empty ( $requested_ids ) && $requested_ids != 'all' ) {		
		$old_level_ids = array_map( 'intval', explode( "+", $requested_ids ) );

		// Make sure the user has the level they are trying to cancel.
		if ( ! pmpro_hasMembershipLevel( $old_level_ids ) ) {
			// If they don't have the level, return to Membership Account.
			wp_redirect( pmpro_url( 'account' ) );
			exit;
		}
	} else {
		$old_level_ids = false;	//cancel all levels
	}

	//are we confirming a cancellation?
	if(isset($_REQUEST['confirm']))
		$pmpro_confirm = (bool)$_REQUEST['confirm'];
	else
		$pmpro_confirm = false;

	if($pmpro_confirm) {
        if ( empty( $old_level_ids ) ) {
        	$old_level_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT(membership_id) FROM $wpdb->pmpro_memberships_users WHERE user_id = %d AND status = 'active'", $current_user->ID ) );
        }

		$worked = true;
		foreach($old_level_ids as $old_level_id) {
			// See if we have an active subscription for this level.
			$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $old_level_id );

			// If the user does have a subscription for this level (possibly multiple), get the furthest next payment date that is after today.
			$next_payment_date = false;
			if ( ! empty( $subscriptions ) ) {
				foreach ( $subscriptions as $sub ) {
					$sub_next_payment_date = $sub->get_next_payment_date();
					if ( ! empty( $sub_next_payment_date ) && $sub_next_payment_date > current_time( 'timestamp' ) && ( empty( $next_payment_date ) || $sub_next_payment_date > $next_payment_date ) ) {
						$next_payment_date = $sub_next_payment_date;
					}
				}
			}

			// If we have a next payment date, we only want to set the enddate for the membership to the next payment date and cancel the subscription.
			// Also add a filter in case a site wants to disable "cancel on next payment date" and cancel immediately.
			if ( ! empty( $next_payment_date ) && apply_filters( 'pmpro_cancel_on_next_payment_date', true, $old_level_id, $current_user->ID ) ) {
				// Set the enddate to the next payment date.
				$enddate = date( 'Y-m-d H:i:s', $next_payment_date );
				$wpdb->update(
					$wpdb->pmpro_memberships_users,
					[
						'enddate' => $enddate,
					],
					[
						'status'        => 'active',
						'membership_id' => $old_level_id,
						'user_id'       => $current_user->ID,
					],
					[
						'%s',
					],
					[
						'%s',
						'%d',
						'%d',
					]
				);

				// Cancel the subscriptions.
				foreach ( $subscriptions as $sub ) {
					$sub->cancel_at_gateway();
				}
			} else {
				$one_worked = pmpro_cancelMembershipLevel($old_level_id, $current_user->ID, 'cancelled');
				$worked = $worked && $one_worked !== false;
			}
		}
        
		if($worked != false && empty($pmpro_error))
		{
			if ( count( $old_level_ids ) > 1 ) {
				$pmpro_msg = __( 'Your memberships have been cancelled.', 'paid-memberships-pro' );
			} else {
				$pmpro_msg = __("Your membership has been cancelled.", 'paid-memberships-pro' );
				if ( ! empty($old_level_ids[0] ) ) {
					$level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $old_level_ids[0] );
					if ( ! empty( $level ) && ! empty( $level->enddate ) ) {
						$pmpro_msg = sprintf( __( 'Your recurring subscription has been cancelled. Your active membership will expire on %s.', 'paid-memberships-pro' ), date_i18n( get_option( 'date_format' ), $level->enddate ) );
					}
				}
			}
			$pmpro_msgt = "pmpro_success";

			// Send a cancellation email for each cancelled level.
			foreach ( $old_level_ids as $old_level_id ) {
				// Send an email to the user.
				$pmproemail = new PMProEmail();
				$pmproemail->sendCancelEmail( $current_user, $old_level_id );

				// Send an an email to the admin.
				$pmproemail = new PMProEmail();
				$pmproemail->sendCancelAdminEmail( $current_user, $old_level_id );
			}
		} else {
			global $pmpro_error;
			$pmpro_msg = $pmpro_error;
			$pmpro_msgt = "pmpro_error";
		}
	}