<?php
/**
 * Deprecated gateway sunset workflow.
 *
 * @since TBD
 */

/**
 * Schedule a deprecated gateway sunset workflow.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $strategy Strategy slug.
 * @param bool   $send_email Whether to email members.
 * @return true|WP_Error
 */
function pmpro_deprecated_gateway_sunset_schedule( $gateway, $strategy, $send_email = true ) {
	$environment = get_option( 'pmpro_gateway_environment', 'sandbox' );
	$gateway = sanitize_key( $gateway );
	$strategy = sanitize_key( $strategy );
	$send_email = ! empty( $send_email );
	if ( ! in_array( $gateway, pmpro_get_deprecated_gateways(), true ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_not_deprecated', __( 'This workflow is only available for deprecated gateways.', 'paid-memberships-pro' ) );
	}

	if ( ! in_array( $strategy, array( 'stripe', 'expiration' ), true ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_invalid_strategy', __( 'Invalid deprecated gateway migration strategy.', 'paid-memberships-pro' ) );
	}

	if ( $gateway === get_option( 'pmpro_gateway' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_no_replacement', __( 'A different gateway must be active before this workflow can start.', 'paid-memberships-pro' ) );
	}

	if ( 'stripe' === $strategy && 'stripe' !== get_option( 'pmpro_gateway' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_stripe_unavailable', __( 'Stripe must be the active payment gateway before subscriptions can be migrated to Stripe.', 'paid-memberships-pro' ) );
	}

	if ( ! function_exists( 'as_enqueue_async_action' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_no_action_scheduler', __( 'Action Scheduler is not available, so the deprecated gateway subscription workflow cannot be scheduled.', 'paid-memberships-pro' ) );
	}

	if ( ! pmpro_deprecated_gateway_sunset_has_active_subscriptions( $gateway, $environment ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_no_subscriptions', __( 'No active subscriptions were found for this gateway.', 'paid-memberships-pro' ) );
	}

	if ( pmpro_deprecated_gateway_sunset_has_pending_or_running_actions( $gateway, $environment ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_already_running', __( 'A deprecated gateway subscription workflow is already queued or running for this gateway.', 'paid-memberships-pro' ) );
	}

	$group = pmpro_deprecated_gateway_sunset_get_action_group( $gateway, $environment );
	$args = array( $gateway, $environment, $strategy, $send_email ? 1 : 0, 0 );
	$action_id = as_enqueue_async_action( 'pmpro_deprecated_gateway_sunset_process_batch', $args, $group );
	if ( empty( $action_id ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_schedule_failed', __( 'The deprecated gateway subscription workflow could not be scheduled. Check the workflow log for details.', 'paid-memberships-pro' ) );
	}

	pmpro_deprecated_gateway_sunset_log(
		sprintf(
			'Queued deprecated gateway sunset workflow. Gateway=%1$s, environment=%2$s, strategy=%3$s, send_email=%4$s.',
			$gateway,
			$environment,
			$strategy,
			$send_email ? 'yes' : 'no'
		)
	);

	return true;
}

/**
 * Process deprecated gateway sunset subscriptions in batches.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param string $strategy Strategy slug.
 * @param bool   $send_email Whether to email members.
 * @param int    $last_subscription_id Last subscription ID processed by the batch worker.
 */
function pmpro_deprecated_gateway_sunset_process_batch( $gateway, $environment, $strategy, $send_email = true, $last_subscription_id = 0 ) {
	$gateway = sanitize_key( $gateway );
	$environment = sanitize_key( $environment );
	$strategy = sanitize_key( $strategy );
	$send_email = ! empty( $send_email );
	$last_subscription_id = (int) $last_subscription_id;

	if ( ! in_array( $strategy, array( 'stripe', 'expiration' ), true ) ) {
		pmpro_deprecated_gateway_sunset_log( 'Stopped processing deprecated gateway sunset workflow because the sunset strategy is invalid. Gateway=' . $gateway . ', environment=' . $environment . ', strategy=' . $strategy . '.' );
		return;
	}

	if ( $environment !== get_option( 'pmpro_gateway_environment', 'sandbox' ) ) {
		$can_process = false;
	} elseif ( 'stripe' === $strategy ) {
		$can_process = 'stripe' === get_option( 'pmpro_gateway' );
	} else {
		$can_process = $gateway !== get_option( 'pmpro_gateway' );
	}

	if ( ! $can_process ) {
		pmpro_deprecated_gateway_sunset_log( 'Stopped processing deprecated gateway sunset workflow because the selected replacement gateway is no longer available. Gateway=' . $gateway . ', environment=' . $environment . ', strategy=' . $strategy . '.' );
		return;
	}

	$batch_size = 10;
	$subscription_ids = pmpro_deprecated_gateway_sunset_get_active_subscription_ids( $gateway, $environment, $last_subscription_id, $batch_size );
	if ( empty( $subscription_ids ) ) {
		pmpro_deprecated_gateway_sunset_log( 'Deprecated gateway sunset workflow processing completed. Gateway=' . $gateway . ', environment=' . $environment . '. No active subscriptions found after subscription #' . $last_subscription_id . '.' );
		return;
	}

	$group = pmpro_deprecated_gateway_sunset_get_action_group( $gateway, $environment );
	$processed = 0;
	foreach ( $subscription_ids as $subscription_id ) {
		$subscription_id = (int) $subscription_id;
		$processed++;
		pmpro_deprecated_gateway_sunset_log( pmpro_deprecated_gateway_sunset_process_subscription( $subscription_id, $gateway, $environment, $strategy, $send_email ) );
	}

	$last_subscription_id = max( $subscription_ids );
	pmpro_deprecated_gateway_sunset_log(
		sprintf(
			'Processed deprecated gateway sunset workflow batch. Gateway=%1$s, environment=%2$s, strategy=%3$s, send_email=%4$s, last_subscription_id=%5$d, processed=%6$d.',
			$gateway,
			$environment,
			$strategy,
			$send_email ? 'yes' : 'no',
			$last_subscription_id,
			$processed
		)
	);

	if ( count( $subscription_ids ) === $batch_size ) {
		$action_id = as_enqueue_async_action( 'pmpro_deprecated_gateway_sunset_process_batch', array( $gateway, $environment, $strategy, $send_email ? 1 : 0, $last_subscription_id ), $group );
		if ( empty( $action_id ) ) {
			pmpro_deprecated_gateway_sunset_log( 'Could not queue the next deprecated gateway sunset workflow batch. Gateway=' . $gateway . ', environment=' . $environment . ', last_subscription_id=' . $last_subscription_id . '.' );
		}
	} else {
		pmpro_deprecated_gateway_sunset_log( 'Deprecated gateway sunset workflow processing completed. Gateway=' . $gateway . ', environment=' . $environment . '.' );
	}
}
add_action( 'pmpro_deprecated_gateway_sunset_process_batch', 'pmpro_deprecated_gateway_sunset_process_batch', 10, 5 );

/**
 * Process one deprecated gateway subscription.
 *
 * @since TBD
 *
 * @param int    $subscription_id Subscription ID.
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param string $strategy Strategy slug.
 * @param bool   $send_email Whether to email members.
 * @return string
 */
function pmpro_deprecated_gateway_sunset_process_subscription( $subscription_id, $gateway, $environment, $strategy, $send_email = true ) {
	$logstr = '';
	$subscription_id = (int) $subscription_id;
	$send_email = ! empty( $send_email );
	$subscription = PMPro_Subscription::get_subscription( $subscription_id );
	if ( empty( $subscription ) ) {
		return 'Skipped missing subscription #' . $subscription_id . '.';
	}

	if ( 'active' !== $subscription->get_status() || $gateway !== $subscription->get_gateway() || $environment !== $subscription->get_gateway_environment() ) {
		return 'Skipped subscription #' . $subscription_id . ' because it is no longer an active subscription for this gateway/environment.';
	}

	if ( ! pmpro_hasMembershipLevel( $subscription->get_membership_level_id(), $subscription->get_user_id() ) ) {
		if ( $subscription->cancel_at_gateway() ) {
			return 'Cancelled subscription #' . $subscription_id . ' without migration because the user no longer has the associated membership level.';
		}

		return 'Could not cancel subscription #' . $subscription_id . ' after skipping migration because the user no longer has the associated membership level. Check the gateway and subscription cancellation error email for details.';
	}

	$handoff_timestamp = $subscription->get_next_payment_date( 'timestamp', false );
	if ( empty( $handoff_timestamp ) ) {
		$logstr .= 'Cannot process subscription #' . $subscription->get_id() . ' because it has no next payment date.';
		return $logstr;
	}

	if ( 'stripe' === $strategy && ! empty( $subscription->get_billing_limit() ) ) {
		$logstr .= 'Cannot migrate subscription #' . $subscription->get_id() . ' to Stripe because it has a billing limit. Use the expiration-date path or migrate this subscription manually.';
		return $logstr;
	}

	$stripe_subscription_id = (int) get_pmpro_subscription_meta( $subscription->get_id(), 'deprecated_gateway_sunset_stripe_subscription_id', true );

	if ( 'stripe' === $strategy ) {
		update_pmpro_subscription_meta( $subscription->get_id(), 'deprecated_gateway_sunset_handoff_date', gmdate( 'Y-m-d H:i:s', $handoff_timestamp ) );

		$stripe_subscription = null;
		if ( ! empty( $stripe_subscription_id ) ) {
			$stripe_subscription = PMPro_Subscription::get_subscription( $stripe_subscription_id );
		}

		if ( empty( $stripe_subscription ) ) {
			if ( ! class_exists( 'PMProGateway_stripe' ) ) {
				return $logstr . 'Failed creating Stripe placeholder for subscription #' . $subscription->get_id() . '. Error: The Stripe gateway class is not available.';
			}

			$stripe_gateway = new PMProGateway_stripe( 'stripe' );
			if ( ! method_exists( $stripe_gateway, 'create_deprecated_gateway_migration_subscription' ) ) {
				return $logstr . 'Failed creating Stripe placeholder for subscription #' . $subscription->get_id() . '. Error: The Stripe gateway does not support deprecated gateway migration subscriptions.';
			}

			$stripe_api_subscription = $stripe_gateway->create_deprecated_gateway_migration_subscription(
				$subscription,
				array(
					'trial_end' => $handoff_timestamp,
				)
			);
			if ( is_wp_error( $stripe_api_subscription ) ) {
				return $logstr . 'Failed creating Stripe placeholder for subscription #' . $subscription->get_id() . '. Error: ' . $stripe_api_subscription->get_error_message();
			}

			$stripe_subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $stripe_api_subscription->id, 'stripe', $environment );
			if ( empty( $stripe_subscription ) ) {
				$stripe_subscription = PMPro_Subscription::create(
					array(
						'user_id'                     => $subscription->get_user_id(),
						'membership_level_id'         => $subscription->get_membership_level_id(),
						'gateway'                     => 'stripe',
						'gateway_environment'         => $environment,
						'subscription_transaction_id' => $stripe_api_subscription->id,
						'status'                      => 'active',
						'startdate'                   => gmdate( 'Y-m-d H:i:s' ),
						'next_payment_date'           => gmdate( 'Y-m-d H:i:s', $handoff_timestamp ),
						'billing_amount'              => $subscription->get_billing_amount(),
						'cycle_number'                => $subscription->get_cycle_number(),
						'cycle_period'                => $subscription->get_cycle_period(),
						'billing_limit'               => $subscription->get_billing_limit(),
					)
				);
			}

			if ( empty( $stripe_subscription ) ) {
				return $logstr . 'Failed creating Stripe placeholder for subscription #' . $subscription->get_id() . '. Error: The Stripe subscription was created, but the local PMPro subscription could not be created.';
			}

			update_pmpro_subscription_meta( $subscription->get_id(), 'deprecated_gateway_sunset_stripe_subscription_id', $stripe_subscription->get_id() );
			update_pmpro_subscription_meta( $stripe_subscription->get_id(), 'deprecated_gateway_sunset_old_subscription_id', $subscription->get_id() );
			$logstr .= 'Created Stripe placeholder subscription #' . $stripe_subscription->get_id() . ' for old subscription #' . $subscription->get_id() . '. ';
		}
	} elseif ( ! empty( $stripe_subscription_id ) ) {
		if ( ! $subscription->cancel_at_gateway() ) {
			$logstr .= 'Did not set an expiration date for subscription #' . $subscription->get_id() . ' because Stripe placeholder subscription #' . $stripe_subscription_id . ' already exists. Could not cancel old subscription. Check the gateway and subscription cancellation error email for details.';
			return $logstr;
		}

		$logstr .= 'Did not set an expiration date for subscription #' . $subscription->get_id() . ' because Stripe placeholder subscription #' . $stripe_subscription_id . ' already exists. Cancelled the old gateway subscription.';
		return $logstr;
	} else {
		pmpro_set_expiration_date( $subscription->get_user_id(), $subscription->get_membership_level_id(), $handoff_timestamp );
		update_pmpro_subscription_meta( $subscription->get_id(), 'deprecated_gateway_sunset_handoff_date', gmdate( 'Y-m-d H:i:s', $handoff_timestamp ) );
	}

	if ( ! $subscription->cancel_at_gateway() ) {
		if ( 'stripe' === $strategy ) {
			$logstr .= 'Stripe placeholder subscription #' . $stripe_subscription->get_id() . ' exists but could not cancel old subscription #' . $subscription->get_id() . '. Check the gateway and subscription cancellation error email for details.';
		} else {
			$logstr .= 'Set expiration date but could not cancel subscription #' . $subscription->get_id() . '. Check the gateway and subscription cancellation error email for details.';
		}
		return $logstr;
	}

	if ( $send_email ) {
		if ( 'stripe' === $strategy ) {
			$user = get_userdata( $stripe_subscription->get_user_id() );
			if ( empty( $user ) || ! class_exists( 'PMPro_Email_Template_Deprecated_Gateway_Stripe_Migration' ) ) {
				$logstr .= 'Could not send Stripe migration email for subscription #' . $subscription->get_id() . ' because the user or email template is missing. ';
			} else {
				$email = new PMPro_Email_Template_Deprecated_Gateway_Stripe_Migration( $stripe_subscription );
				$sent = $email->send();
				if ( $sent ) {
					$logstr .= 'Sent Stripe migration email for subscription #' . $subscription->get_id() . ' to user #' . $subscription->get_user_id() . '. ';
				} else {
					$logstr .= 'Failed sending Stripe migration email for subscription #' . $subscription->get_id() . ' to user #' . $subscription->get_user_id() . '. ';
				}
			}
		} else {
			$user = get_userdata( $subscription->get_user_id() );
			if ( empty( $user ) || ! class_exists( 'PMPro_Email_Template_Deprecated_Gateway_Checkout_Required' ) ) {
				$logstr .= 'Could not send checkout required email for subscription #' . $subscription->get_id() . ' because the user or email template is missing. ';
			} else {
				$email = new PMPro_Email_Template_Deprecated_Gateway_Checkout_Required( $user, $subscription->get_membership_level_id() );
				$sent = $email->send();
				if ( $sent ) {
					$logstr .= 'Sent checkout required email for subscription #' . $subscription->get_id() . ' to user #' . $subscription->get_user_id() . '. ';
				} else {
					$logstr .= 'Failed sending checkout required email for subscription #' . $subscription->get_id() . ' to user #' . $subscription->get_user_id() . '. ';
				}
			}
		}
	} else {
		$logstr .= 'Skipped deprecated gateway sunset email for subscription #' . $subscription->get_id() . ' because email was disabled for this workflow. ';
	}

	if ( 'stripe' === $strategy ) {
		$logstr .= 'Migrated subscription #' . $subscription->get_id() . ' to Stripe placeholder subscription #' . $stripe_subscription->get_id() . ' and cancelled the old gateway subscription.';
	} else {
		$logstr .= 'Set expiration date and cancelled old gateway subscription #' . $subscription->get_id() . '.';
	}

	return $logstr;
}

/**
 * Allow payment settings admins to access deprecated gateway sunset logs.
 *
 * @since TBD
 *
 * @param bool   $can_access Whether the file can be accessed.
 * @param string $file_dir File directory.
 * @param string $file File name.
 * @return bool
 */
function pmpro_deprecated_gateway_sunset_allow_log_access( $can_access, $file_dir, $file ) {
	if ( 'logs' === $file_dir && 'deprecated-gateway-sunset.txt' === $file && ( current_user_can( 'manage_options' ) || current_user_can( 'pmpro_paymentsettings' ) ) ) {
		return true;
	}

	return $can_access;
}
add_filter( 'pmpro_can_access_restricted_file', 'pmpro_deprecated_gateway_sunset_allow_log_access', 20, 3 );

/**
 * Get active deprecated gateway subscription IDs.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @param int    $last_subscription_id Last subscription ID already queried.
 * @param int    $limit Number of subscription IDs to return.
 * @return int[]
 */
function pmpro_deprecated_gateway_sunset_get_active_subscription_ids( $gateway, $environment, $last_subscription_id = 0, $limit = 10 ) {
	global $wpdb;

	$subscription_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
				SELECT id
				FROM {$wpdb->pmpro_subscriptions}
				WHERE gateway = %s
					AND gateway_environment = %s
					AND status = %s
					AND id > %d
				ORDER BY id ASC
				LIMIT %d
			",
			$gateway,
			$environment,
			'active',
			(int) $last_subscription_id,
			(int) $limit
		)
	);

	return array_map( 'intval', $subscription_ids );
}

/**
 * Whether active deprecated gateway subscriptions exist.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return bool
 */
function pmpro_deprecated_gateway_sunset_has_active_subscriptions( $gateway, $environment ) {
	return ! empty( pmpro_deprecated_gateway_sunset_get_active_subscription_ids( $gateway, $environment, 0, 1 ) );
}

/**
 * Get Action Scheduler group.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return string
 */
function pmpro_deprecated_gateway_sunset_get_action_group( $gateway, $environment ) {
	return 'pmpro_deprecated_gateway_sunset_' . sanitize_key( $gateway ) . '_' . sanitize_key( $environment );
}

/**
 * Whether pending or running deprecated gateway sunset actions exist.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return bool
 */
function pmpro_deprecated_gateway_sunset_has_pending_or_running_actions( $gateway, $environment ) {
	if ( ! function_exists( 'as_has_scheduled_action' ) ) {
		return false;
	}

	return as_has_scheduled_action( 'pmpro_deprecated_gateway_sunset_process_batch', null, pmpro_deprecated_gateway_sunset_get_action_group( $gateway, $environment ) );
}

/**
 * Whether gateway cleanup can run.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @param string $environment Gateway environment.
 * @return bool
 */
function pmpro_deprecated_gateway_sunset_can_cleanup_gateway( $gateway, $environment ) {
	if ( pmpro_deprecated_gateway_sunset_has_active_subscriptions( $gateway, $environment ) ) {
		return false;
	}

	return ! pmpro_deprecated_gateway_sunset_has_pending_or_running_actions( $gateway, $environment );
}

/**
 * Cleanup a deprecated gateway after subscriptions are cancelled.
 *
 * @since TBD
 *
 * @param string $gateway Gateway slug.
 * @return true|WP_Error
 */
function pmpro_deprecated_gateway_sunset_cleanup_gateway( $gateway ) {
	$environment = get_option( 'pmpro_gateway_environment', 'sandbox' );
	$gateway = sanitize_key( $gateway );
	if ( ! in_array( $gateway, pmpro_get_deprecated_gateways(), true ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_cleanup_not_deprecated', __( 'This workflow is only available for deprecated gateways.', 'paid-memberships-pro' ) );
	}

	if ( $gateway === get_option( 'pmpro_gateway' ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_cleanup_no_replacement', __( 'A different gateway must be active before this gateway can be removed.', 'paid-memberships-pro' ) );
	}

	if ( ! pmpro_deprecated_gateway_sunset_can_cleanup_gateway( $gateway, $environment ) ) {
		return new WP_Error( 'pmpro_deprecated_gateway_sunset_cleanup_blocked', __( 'This gateway cannot be removed until all subscriptions are cancelled and all scheduled actions are resolved.', 'paid-memberships-pro' ) );
	}

	$undeprecated_gateways = pmpro_get_undeprecated_gateways();
	$undeprecated_gateways = array_values( array_diff( $undeprecated_gateways, array( $gateway ) ) );
	update_option( 'pmpro_undeprecated_gateways', $undeprecated_gateways );

	$option_names = array();
	$shared_paypal_gateways = array( 'paypalexpress', 'paypalwpp', 'paypalstandard' );
	if ( in_array( $gateway, $shared_paypal_gateways, true ) ) {
		if ( empty( array_intersect( $shared_paypal_gateways, $undeprecated_gateways ) ) && ! in_array( get_option( 'pmpro_gateway' ), $shared_paypal_gateways, true ) ) {
			$option_names = array( 'gateway_email', 'apiusername', 'apipassword', 'apisignature', 'paypalexpress_skip_confirmation' );
		}
	} else {
		switch ( $gateway ) {
			case 'authorizenet':
				$option_names = array( 'loginname', 'transactionkey', 'authnet_silent_post_token' );
				break;
			case 'payflowpro':
				$option_names = array( 'payflow_partner', 'payflow_vendor', 'payflow_user', 'payflow_pwd' );
				break;
			case 'braintree':
				$option_names = array( 'braintree_merchantid', 'braintree_publickey', 'braintree_privatekey', 'braintree_encryptionkey' );
				break;
			case 'twocheckout':
				$option_names = array( 'twocheckout_apiusername', 'twocheckout_apipassword', 'twocheckout_accountnumber', 'twocheckout_secretword' );
				break;
			case 'cybersource':
				$option_names = array( 'cybersource_merchantid', 'cybersource_securitykey' );
				break;
		}
	}

	$option_names = array_values( array_unique( $option_names ) );

	foreach ( $option_names as $option_name ) {
		delete_option( 'pmpro_' . $option_name );
	}

	$deleted_options_message = ! empty( $option_names ) ? implode( ', ', $option_names ) : 'none';
	pmpro_deprecated_gateway_sunset_log( 'Deprecated gateway cleanup completed. Gateway=' . $gateway . ', environment=' . $environment . '. Removed gateway from pmpro_undeprecated_gateways. Deleted options: ' . $deleted_options_message . '.' );

	return true;
}

/**
 * Append a completed log string to the deterministic workflow log.
 *
 * @since TBD
 *
 * @param string $logstr Log output.
 */
function pmpro_deprecated_gateway_sunset_log( $logstr ) {
	$logstr = (string) $logstr;
	if ( empty( $logstr ) ) {
		return;
	}

	$logstr = 'Logged On: ' . date_i18n( 'm/d/Y H:i:s' ) . "\n" . $logstr . "\n-------------\n";

	$logfile = pmpro_get_restricted_file_path( 'logs', 'deprecated-gateway-sunset.txt' );
	if ( empty( $logfile ) ) {
		return;
	}

	$loghandle = fopen( $logfile, 'a+' );
	if ( $loghandle ) {
		fwrite( $loghandle, $logstr );
		fclose( $loghandle );
	}
}
