<?php
// In case the file is loaded directly.
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Sets the PMPRO_DOING_WEBHOOK constant and fires the pmpro_doing_webhook action.
pmpro_doing_webhook( 'paypalrest', true );

// Get the event data.
$body       = json_decode( file_get_contents('php://input') );
$event_type = empty( $body->event_type ) ? '' : $body->event_type;
$resource   = empty( $body->resource ) ? '' : $body->resource;

// Get the gateway environment that the webhook is for so that we know which PayPal environment to use.
$headers = getallheaders();
$gateway_environment = ( empty( $headers['PAYPAL-CERT-URL'] ) || false !== strpos( $headers['PAYPAL-CERT-URL'], 'sandbox' ) ) ? 'sandbox' : 'live';

// Set up the log string.
$logstr = '';
if ( 'sandbox' === $gateway_environment ) {
	$logstr .= '(SANDBOX) ';
}
$logstr .= "Received On: " . date_i18n("m/d/Y H:i:s") . "\n-------------\n";

// Check if we're in development mode. If not, validate the webhook request.
if ( defined( 'PMPRO_PAYPALREST_DEVELOPMENT_MODE' ) && PMPRO_PAYPALREST_DEVELOPMENT_MODE ) {
	$validated = true;
} else {
	// Validate the webhook request.
	$webhook_id = get_option( 'pmpro_paypalrest_webhook_id_' . $gateway_environment );
	$validated = false;
	$validate_response = PMProGateway_paypalrest::send_request( 'POST', 'v1/notifications/verify-webhook-signature', array(
		'auth_algo' => empty( $headers['PAYPAL-AUTH-ALGO'] ) ? '' : $headers['PAYPAL-AUTH-ALGO'],
		'cert_url' => empty( $headers['PAYPAL-CERT-URL'] ) ? '' : $headers['PAYPAL-CERT-URL'],
		'transmission_id' => empty( $headers['PAYPAL-TRANSMISSION-ID'] ) ? '' : $headers['PAYPAL-TRANSMISSION-ID'],
		'transmission_sig' => empty( $headers['PAYPAL-TRANSMISSION-SIG'] ) ? '' : $headers['PAYPAL-TRANSMISSION-SIG'],
		'transmission_time' => empty( $headers['PAYPAL-TRANSMISSION-TIME'] ) ? '' : $headers['PAYPAL-TRANSMISSION-TIME'],
		'webhook_id' => empty( $webhook_id ) ? '' : $webhook_id,
		'webhook_event' => $body,
	), $gateway_environment );
	if ( is_string( $validate_response ) ) {
		// An error string was returned. Record it.
		$logstr .= 'Error validating webhook request: ' . $validate_response . "\n";
	} elseif ( 'SUCCESS' !== $validate_response->verification_status ) {
		// The webhook request was not validated. Record the error.
		$logstr .= 'Webhook request not validated.';
	} else {
		// The webhook request was validated.
		$validated = true;

		// Send the 200 OK response early to avoid timeouts.
		pmpro_send_200_http_response();

		// Update the "last received" option.
		update_option( 'pmpro_paypalrest_webhook_last_received_' . $gateway_environment, current_time( 'timestamp' ) );
	}
}



if ( ! $validated ) {
	// The webhook request was not validated. Record the error.
	$logstr .= 'Webhook request not validated.';
} else {
	// The webhook request was validated. Process the event.
	switch ( $event_type ) {
		case 'CHECKOUT.ORDER.APPROVED':
			// Handle one-time payment checkouts.
			$logstr .= 'Processing one-time payment checkout for PayPal order ID ' . $resource->id . '. ';

			// Make sure that we have an updated order object from PayPal.
			$response = PMProGateway_paypalrest::send_request( 'GET', 'v2/checkout/orders/' . $resource->id, array(), $gateway_environment );
			if ( is_string( $response ) ) {
				// An error string was returned. Record it.
				$logstr .= 'Error getting updated order data for order ID ' . $resource->id . ': ' . $response;
				break;
			} else {
				// The order data was retrieved successfully. Update $resource with the new data.
				$resource = $response;
			}

			// Find the order in PMPro.
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT pmpro_membership_order_id FROM $wpdb->pmpro_membership_ordermeta WHERE meta_key = 'paypalrest_order_id' AND meta_value = %s LIMIT 1", $resource->id ) );
			if ( empty( $order_id ) ) {
				$logstr .= "Could not find a PMPro order for PayPal order ID " . $resource->id;
			} else {
				$order = new MemberOrder( $order_id );
				if (  empty( $order ) ) {
					$logstr .= "Order #" . $order_id . " not found.";
				}
			}

			// If we have a PayPal order that still needs to be captured, do so.
			if ( ! empty( $order ) && 'APPROVED' === $resource->status ) {
				$response = PMProGateway_paypalrest::send_request( 'POST', 'v2/checkout/orders/' . $resource->id . '/capture', array(), $gateway_environment );
				if ( is_string( $response ) ) {
					// An error string was returned. Record it.
					$logstr .= 'Error capturing payment for order #' . $order->id . ': ' . $response;
				} else {
					// The payment was captured successfully. Update $resource with the new data.
					$resource = $response;
				}
			}

			// If we now have a PayPal order in the COMPLETED status, complete the checkout if needed.
			if ( ! empty( $order ) && 'COMPLETED' === $resource->status ) {
				$order->payment_transaction_id = $resource->purchase_units[0]->payments->captures[0]->id;
				$order->saveOrder();

				if ( 'token' === $order->status ) {
					// The order is still in token status. Complete the checkout.
					pmpro_pull_checkout_data_from_order( $order );
					if ( pmpro_complete_async_checkout( $order ) ) {
						// The checkout was completed successfully.
						$logstr .= 'Order #' . $order->id . ' completed successfully.';
					} else {
						// The checkout failed. Record the error.
						$logstr .= 'Order #' . $order->id . ' failed to complete.';
					}
				} else {
					// The order is not in token status. Record the error.
					$logstr .= 'Order #' . $order->id . ' has already been completed.';
				}
			}
			break;
		case 'BILLING.SUBSCRIPTION.ACTIVATED':
			// Handle recurring payment checkouts.
			$logstr .= 'Processing recurring payment checkout for PayPal subscription ID ' . $resource->id . '. ';

			// Find the order in PMPro.
			$order_search_args = array(
				'gateway' => 'paypalrest',
				'gateway_environment' => $gateway_environment,
				'status' => 'token',
				'subscription_transaction_id' => $resource->id,
			);
			$order = MemberOrder::get_order( $order_search_args );
			if ( empty( $order ) ) {
				// The order was not found. Record the error.
				$logstr .= 'Token order not found.';
			} else {
				// The order was found. Get the payment transaction ID if there was an initial payment. Search between an hour before and after the subscription creation time.
				$subscription_transactions = PMProGateway_paypalrest::send_request( 'GET', 'v1/billing/subscriptions/' . $resource->id . '/transactions/?' . http_build_query( array( 'start_time' => date( 'c', strtotime( $resource->create_time ) - 3600 ), 'end_time' => date( 'c', strtotime( $resource->create_time ) + 3600 ) ) ), array(), $gateway_environment );
				if ( is_string( $subscription_transactions ) ) {
					// An error string was returned. Record it.
					$logstr .= 'Error getting subscription transactions for subscription ID ' . $resource->id . ': ' . $subscription_transactions;
					break;
				}

				if ( ! empty( $subscription_transactions->transactions ) ) {
					// There is an initial payment. Update the order with the payment transaction ID.
					$order->payment_transaction_id = $subscription_transactions->transactions[0]->id;
					$order->saveOrder();
				}

				// Complete the checkout.
				pmpro_pull_checkout_data_from_order( $order );
				if ( pmpro_complete_async_checkout( $order ) ) {
					// The checkout was completed successfully.
					$logstr .= 'Order #' . $order->id . ' completed successfully.';
				} else {
					// The checkout failed. Record the error.
					$logstr .= 'Order #' . $order->id . ' failed to complete.';
				}
			}
			break;
		case 'PAYMENT.SALE.COMPLETED':
			// Process recurring payments.
			$logstr .= 'Processing a recurring payment ' . $resource->id . ' for PayPal subscription ID ' . $resource->billing_agreement_id . '. ';

			// First, let's make sure that we don't already have an order with this transaction ID.
			$existing_order_search_args = array(
				'gateway' => 'paypalrest',
				'gateway_environment' => $gateway_environment,
				'status' => 'success',
				'payment_transaction_id' => $resource->id,
			);
			$existing_order = MemberOrder::get_order( $existing_order_search_args );
			if ( ! empty( $existing_order ) ) {
				// We already have an order with this transaction ID. Record the error.
				$logstr .= 'Order #' . $existing_order->id . ' already exists.';
				break;
			}

			// We also need to be careful not to edit an order that is already going to be processed by the BILLING.SUBSCRIPTION.ACTIVATED event.
			// We can assume that this is the case when there is token order for the subscription ID.
			$token_order_search_args = array(
				'gateway' => 'paypalrest',
				'gateway_environment' => $gateway_environment,
				'status' => 'token',
				'subscription_transaction_id' => $resource->billing_agreement_id,
			);
			$token_order = MemberOrder::get_order( $token_order_search_args );
			if ( ! empty( $token_order ) ) {
				// We have a token order for this subscription ID. Record the error.
				$logstr .= 'Token order #' . $token_order->id . ' exists for subscription ID ' . $resource->billing_agreement_id . '. This order will be processed by BILLING.SUBSCRIPTION.ACTIVATED. ';
				break;
			}
			
			// This payment has not been processed and is not the initial payment. Let's get the PMPro Subscription object for this PayPal subscription.
			$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $resource->billing_agreement_id, 'paypalrest', $gateway_environment );
			if ( empty( $subscription ) ) {
				// We couldn't find a subscription. Record the error.
				$logstr .= 'Subscription for subscription ID ' . $resource->billing_agreement_id . ' not found.';
				break;
			}

			// We have a subscription. Create a new order.
			$morder = new MemberOrder();
			$morder->user_id = $subscription->get_user_id();
			$morder->membership_id = $subscription->get_membership_level_id();
			$morder->timestamp = strtotime( $resource->create_time );
			$morder->payment_transaction_id = $resource->id;
			$morder->subscription_transaction_id = $resource->billing_agreement_id;
			$morder->gateway = 'paypalrest';
			$morder->gateway_environment = $gateway_environment;
			$morder->status = 'success';
			$morder->total = $resource->amount->total;
			$morder->subtotal = empty( $resource->amount->details->subtotal ) ? $resource->amount->total : $resource->amount->details->subtotal;
			$morder->tax = empty( $resource->amount->details->tax ) ? 0 : $resource->amount->details->tax;
			$morder->saveOrder();

			$logstr .= 'Order #' . $morder->id . ' created successfully.';
			break;
		case 'BILLING.SUBSCRIPTION.SUSPENDED':
		case 'BILLING.SUBSCRIPTION.CANCELLED':
		case 'BILLING.SUBSCRIPTION.EXPIRED':
			// Handle subscription termination.
			// Note that this will not fire if an admin manually cancels a subscription in PayPal, but will fire if the user cancels their subscription in PayPal.
			$logstr .= 'Processing subscription termination for PayPal subscription ID ' . $resource->id . '. ';
			$logstr .= pmpro_handle_subscription_cancellation_at_gateway( $resource->id, 'paypalrest', $gateway_environment );
			break;
		case 'PAYMENT.CAPTURE.REFUNDED':
			// Handle refunds.
			$logstr .= 'Processing refund ' . $resource->id . '. ';

			if ( ! empty( $resource->capture_id ) ) {
				// This is using the v1 API version.
				$logstr .= 'Using v1 API version. ';

				// The transaction ID is given to us.
				$transaction_id = $resource->capture_id;

				// Also get the refund amount.
				$refund_amount = abs( (float)$resource->amount->total );
			} else {
				// This is using the v2 API version.
				$logstr .= 'Using v2 API version. ';

				// We need to parse the transaction ID from the "rel"=>"up" link.
				foreach ( $resource->links as $link ) {
					if ( 'up' === $link->rel ) {
						$transaction_id = basename( $link->href );
						break;
					}
				}

				// Also get the refund amount.
				$refund_amount = abs( (float)$resource->amount->value );
			}

			// If we don't have a transaction ID, record the error.
			if ( empty( $transaction_id ) ) {
				$logstr .= 'Transaction ID not found. ';
				break;
			}

			// Log the transaction ID.
			$logstr .= 'Transaction ID: ' . $transaction_id . '. ';

			// Find the order in PMPro.
			$order_search_args = array(
				'gateway' => 'paypalrest',
				'gateway_environment' => $gateway_environment,
				'payment_transaction_id' => $transaction_id,
			);
			$order = MemberOrder::get_order( $order_search_args );

			// If we don't have an order, record the error.
			if ( empty( $order ) ) {
				$logstr .= 'Corresponding order not found. ';
				break;
			}

			// Check if the order is already refunded.
			if ( 'refunded' === $order->status ) {
				$logstr .= 'Order #' . $order->id . ' is already refunded. ';
				break;
			}

			// If the order isn't in success status, record the error.
			if ( 'success' !== $order->status ) {
				$logstr .= 'Order #' . $order->id . ' is not in success status. ';
				break;
			}

			// Make a note if the refund is partial.
			if ( $refund_amount < $order->total ) {
				$logstr .= 'Partial refund for $' . $refund_amount . '. ';
				$order->notes .= 'Webhook: Partial refund for $' . $refund_amount . '. ';
			}

			// Mark the order as refunded.
			$order->status = 'refunded';
			$order->saveOrder();
			$logstr .= 'Order #' . $order->id . ' marked as refunded. ';

			// Get the user for the order.
			$user = get_userdata( $order->user_id );

			// If we don't have a user, record the error.
			if ( empty( $user ) ) {
				$logstr .= 'User not found. ';
				break;
			}

			// Send emails to the user and admin.
			$pmproemail = new PMProEmail();
			$pmproemail->sendRefundedEmail( $user, $order );
			$pmproemail = new PMProEmail();
			$pmproemail->sendRefundedAdminEmail( $user, $order );
			break;
		case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
			// Handle failed payments.
			$logstr .= 'Processing failed payment for PayPal subscription ID ' . $resource->id . '. ';

			// Get the PMPro Subscription object for this PayPal subscription.
			$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $resource->id, 'paypalrest', $gateway_environment );

			// If we couldn't find a subscription, record the error.
			if ( empty( $subscription ) ) {
				$logstr .= 'Subscription for subscription ID ' . $resource->id . ' not found.';
				break;
			}

			// Get the user for the subscription.
			$user = get_userdata( $subscription->get_user_id() );

			// If we don't have a user, record the error.
			if ( empty( $user ) ) {
				$logstr .= 'User not found.';
				break;
			}

			// Create a fake MemberOrder object to pass to our payment failed email.
			$order = new MemberOrder();
			$order->user_id = $subscription->get_user_id();
			$order->membership_id = $subscription->get_membership_level_id();

			// Send emails to the user and admin.
			$pmproemail = new PMProEmail();
			$pmproemail->sendBillingFailureEmail( $user, $order );
			$pmproemail = new PMProEmail();
			$pmproemail->sendBillingFailureAdminEmail( get_bloginfo( 'admin_email' ), $order );
			break;
		default:
			// Handle other events.
			$logstr .= 'Unknown event type: ' . $event_type;
			break;
	}
}

// Process the log string.
echo esc_html( $logstr );
if ( defined( 'PMPRO_PAYPALREST_WEBHOOK_DEBUG' ) && PMPRO_PAYPALREST_WEBHOOK_DEBUG === 'log' ) {
	// Log to file.
	$logfile = apply_filters( 'pmpro_paypalrest_webhook_logfile', dirname( __FILE__ ) . "/../logs/paypalrest-webhook.txt" );
	$loghandle = fopen( $logfile, "a+" );
	fwrite( $loghandle, $logstr . "\n" );
	fclose( $loghandle );
} elseif( defined('PMPRO_PAYPALREST_WEBHOOK_DEBUG' ) && false !== PMPRO_PAYPALREST_WEBHOOK_DEBUG ) {
	// Send log to email.
	if(strpos(PMPRO_PAYPALREST_WEBHOOK_DEBUG, "@"))
		$log_email = PMPRO_PAYPALREST_WEBHOOK_DEBUG; // Constant defines a specific email address.
	else
		$log_email = get_option("admin_email");

	wp_mail( $log_email, get_option( "blogname" ) . " Stripe Webhook Log", nl2br( esc_html( $logstr ) ) );
}

exit;