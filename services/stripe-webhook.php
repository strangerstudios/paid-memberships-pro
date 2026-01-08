<?php
	// in case the file is loaded directly
	if( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	
	// min php requirement for this script
	if ( version_compare( PHP_VERSION, '5.3.29', '<' )) {
		return;
	}

	// For compatibility with old library (Namespace Alias)
	use Stripe\Invoice as Stripe_Invoice;
	use Stripe\Subscription as Stripe_Subscription;
	use Stripe\Charge as Stripe_Charge;
	use Stripe\Event as Stripe_Event;
	use Stripe\PaymentMethod as Stripe_PaymentMethod;
	use Stripe\Customer as Stripe_Customer;
	use Stripe\Checkout\Session as Stripe_Checkout_Session;

	global $logstr;	

	// Make sure that Stripe is loaded and is the correct API version.
	$stripe = new PMProGateway_stripe();

	// Sets the PMPRO_DOING_WEBHOOK constant and fires the pmpro_doing_webhook action.
	pmpro_doing_webhook( 'stripe', true );

	// retrieve the request's body and parse it as JSON
	if(empty($_REQUEST['event_id']))
	{
		$body = @file_get_contents('php://input');
		$post_event = json_decode($body);

		//get the id
		if ( ! empty( $post_event ) ) {
			$event_id = sanitize_text_field($post_event->id);
			$livemode = ! empty( $post_event->livemode );
		} else {
			// No event data passed in body, so use current environment.
			$livemode = get_option( 'pmpro_gateway_environment' ) === 'live';
		}
	}
	else
	{
		$event_id = sanitize_text_field($_REQUEST['event_id']);
		$livemode = get_option( 'pmpro_gateway_environment' ) === 'live'; // User is testing, so use current environment.
	}

	try {
		if ( PMProGateway_stripe::using_api_keys() ) {
			$secret_key = get_option( "pmpro_stripe_secretkey" );
		} elseif ( $livemode ) {
			$secret_key = get_option( 'pmpro_live_stripe_connect_secretkey' );
		} else {
			$secret_key = get_option( 'pmpro_sandbox_stripe_connect_secretkey' );
		}
		Stripe\Stripe::setApiKey( $secret_key );
	} catch ( Exception $e ) {
		$logstr .= "Unable to set API key for Stripe gateway: " . $e->getMessage();
		pmpro_stripeWebhookExit();
	}

	/**
	 * Allow adding other content after the Order Settings table.
	 *
	 * @since 3.0.3
	 */
	do_action( 'pmpro_stripe_before_retrieve_webhook_event' );

	//get the event through the API now
	if(!empty($event_id))
	{
		try
		{
			global $pmpro_stripe_event;
			$pmpro_stripe_event = Stripe_Event::retrieve($event_id);
		}
		catch(Exception $e)
		{
			$logstr .= "Could not find an event with ID #" . $event_id . ". " . $e->getMessage();
			// pmpro_stripeWebhookExit();
			$pmpro_stripe_event = $post_event;			//for testing you may want to assume that the passed in event is legit
		}
	}

	global $wpdb;

	//real event?
	if(!empty($pmpro_stripe_event->id))
	{
		// Send a 200 HTTP response to Stripe to avoid timeout.
		pmpro_send_200_http_response();

		// Log that we have successfully received a webhook from Stripe.
		update_option( 'pmpro_stripe_webhook_last_received_' . ( $livemode ? 'live' : 'sandbox' ) . '_' . $pmpro_stripe_event->type, $pmpro_stripe_event->created );

		/**
		 * Allow code to run when a Stripe webhook is received.
		 *
		 * @since 2.11
		 */
		do_action( 'pmpro_stripe_webhook_event_received', $pmpro_stripe_event );

		//check what kind of event it is
		if($pmpro_stripe_event->type == "invoice.payment_succeeded")
		{
			// Make sure we have the invoice in the desired API version.
			$invoice = Stripe_Invoice::retrieve(
				array(
					'id' => $pmpro_stripe_event->data->object->id,
					'expand' => array(
						'payments',
					)
				)
			);

			if ( $invoice->amount_due <= 0 ) {
				$logstr .= "Ignoring an invoice for $0. Probably for a new subscription just created. Invoice ID #" . $invoice->id . ".";
				pmpro_stripeWebhookExit();
			}

			if ( empty( $invoice->parent->subscription_details->subscription ) ) {
				$logstr .= "No subscription associated with invoice " . $invoice->id . ".";
				pmpro_stripeWebhookExit();
			}

			$logstr .= pmpro_handle_recurring_payment_succeeded_at_gateway( pmpro_stripe_webhook_get_order_data_from_invoice( $invoice ) );
			pmpro_stripeWebhookExit();
		}
		elseif($pmpro_stripe_event->type == "invoice.payment_action_required") {
			// Make sure we have the invoice in the desired API version.
			$invoice = Stripe_Invoice::retrieve( $pmpro_stripe_event->data->object->id );

			// Get the subscription ID for this invoice.
			if ( ! empty( $invoice->parent->subscription_details->subscription ) ) {
				$subscription_id = $invoice->parent->subscription_details->subscription;
			} else {
				$logstr .= "Could not find subscription ID for invoice " . $invoice->id . ".";
				pmpro_stripeWebhookExit();
			}

			// Get the subscription from the invoice.
			$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscription_id, 'stripe', $livemode ? 'live' : 'sandbox' );
			if( ! empty( $subscription ) ) {
				$user_id = $subscription->get_user_id();
				$user = get_userdata($user_id);
				if ( empty( $user ) ) {
					$logstr .= "Couldn't find the subscription's user. Subscription ID = " . $subscription->get_id() . ".";
					pmpro_stripeWebhookExit();
				}

				// Prep order for emails.
				$morder = new MemberOrder();
				$morder->user_id = $user_id;

				// Add invoice link to the order.
				$morder->invoice_url = $invoice->hosted_invoice_url;

				// Email the user and ask them to authenticate their payment.
				$pmproemail = new PMProEmail();
				$pmproemail->sendPaymentActionRequiredEmail($user, $morder);

				// Email admin so they are aware.
				// TODO: Remove?
				$pmproemail = new PMProEmail();
				$pmproemail->sendPaymentActionRequiredAdminEmail($user, $morder);

				$logstr .= "Payment for subscription ID #" . $subscription->get_id() . " requires customer authentication. Sent email to the member and site admin.";
				pmpro_stripeWebhookExit();
			}
			else
			{
				$logstr .= "Could not find the related subscription for event with ID #" . $pmpro_stripe_event->id . ".";
				if(!empty($invoice->customer))
					$logstr .= " Customer ID #" . $invoice->customer . ".";
				pmpro_stripeWebhookExit();
			}
		} elseif($pmpro_stripe_event->type == "charge.failed") {
			// Make sure we have the charge in the desired API version.
			$charge = Stripe_Charge::retrieve( $pmpro_stripe_event->data->object->id );

			// Get the invoice for this charge if it exists.
			if ( ! empty( $charge->payment_intent ) ) {
				$invoice_payment = \Stripe\InvoicePayment::all( array(
					'payment' => array(
						'type' => 'payment_intent',
						'payment_intent' => $charge->payment_intent,
					),
					'expand' => array(
						'data.invoice'
					)
				) );
				$invoice = empty($invoice_payment->data[0]->invoice) ? null : $invoice_payment->data[0]->invoice; // Using data[0] as only one invoice payment should match a search passing a specific payment intent ID.
			}

			// If we don't have an invoice, bail.
			if ( empty( $invoice ) ) {
				$logstr .= "Could not find an invoice for failed charge " . $charge->id . ".";
				pmpro_stripeWebhookExit();
			}

			// If we don't have a subscription on the invoice, bail.
			if ( empty( $invoice->parent->subscription_details->subscription ) ) {
				$logstr .= "No subscription associated with invoice " . $invoice->id . " with failed payment.";
				pmpro_stripeWebhookExit();
			}

			// If the subscription is no longer active in Stripe, bail.
			// We will handle this case with the customer.subscription.deleted event.
			try {
				$subscription = Stripe_Subscription::retrieve( $invoice->parent->subscription_details->subscription );
			} catch ( \Exception $e ) {
				$logstr .= "Error retrieving subscription " . $invoice->parent->subscription_details->subscription . " from Stripe: " . $e->getMessage() . ". No action taken for failed payment.";
				pmpro_stripeWebhookExit();
			}
			if ( empty( $subscription ) || ! in_array( $subscription->status, array( 'trialing', 'active', 'past_due' ) ) ) {
				$logstr .= "Subscription " . ( ! empty( $subscription->id ) ? $subscription->id : $invoice->parent->subscription_details->subscription ) . " is no longer active in Stripe. Status = " . ( ! empty( $subscription->status ) ? $subscription->status : 'unknown' ) . ". No action taken for failed payment.";
				pmpro_stripeWebhookExit();
			}

			$logstr .= pmpro_handle_recurring_payment_failure_at_gateway( pmpro_stripe_webhook_get_order_data_from_invoice( $invoice ) );
			pmpro_stripeWebhookExit();
		}
		elseif($pmpro_stripe_event->type == "customer.subscription.deleted")
		{
			$logstr .= pmpro_handle_subscription_cancellation_at_gateway( $pmpro_stripe_event->data->object->id, 'stripe', $livemode ? 'live' : 'sandbox' );
			pmpro_stripeWebhookExit();
		}
		elseif( $pmpro_stripe_event->type == "charge.refunded" )
		{
			// Make sure we have the charge in the desired API version.
			$charge = Stripe_Charge::retrieve( $pmpro_stripe_event->data->object->id );

			$payment_transaction_id = $charge->id;
			$morder = new MemberOrder();
      		$morder->getMemberOrderByPaymentTransactionID( $payment_transaction_id );
		
			// Initial payment orders are stored using the invoice ID, so check that value too.
			if ( empty( $morder->id ) && ! empty( $charge->payment_intent ) ) {
				// Get the invoice for this charge if it exists.
				$invoice_payment = \Stripe\InvoicePayment::all( array(
					'payment' => array(
						'type' => 'payment_intent',
						'payment_intent' => $charge->payment_intent,
					),
				) );
				$payment_transaction_id = empty($invoice_payment->data[0]->invoice) ? null : $invoice_payment->data[0]->invoice; // Using data[0] as only one invoice payment should match a search passing a specific payment intent ID.
				$morder->getMemberOrderByPaymentTransactionID( $payment_transaction_id );
			}

			//We've got the right order	
			if( !empty( $morder->id ) ) {
				// Ignore orders already in refund status.
				if( $morder->status == 'refunded' ) {					
					$logstr .= sprintf( 'Webhook: Order ID %1$s with transaction ID %2$s was already in refund status.', $morder->id, $payment_transaction_id );									
					pmpro_stripeWebhookExit();
				}
				
				// Handle partial refunds. Only updating the log and notes for now.
				if ( $pmpro_stripe_event->data->object->amount_refunded < $pmpro_stripe_event->data->object->amount ) {
					$logstr .= sprintf( 'Webhook: Order ID %1$s with transaction ID %2$s was partially refunded. The order will need to be updated in the WP dashboard.', $morder->id, $payment_transaction_id );

					$morder->add_order_note( sprintf( 'Webhook: Order ID %1$s was partially refunded for transaction ID %2$s at the gateway.', $morder->id, $payment_transaction_id ) );
					$morder->SaveOrder();
					pmpro_stripeWebhookExit();
				}
				
				// Full refund.	
				$morder->status = 'refunded';
				
				$logstr .= sprintf( 'Webhook: Order ID %1$s successfully refunded on %2$s for transaction ID %3$s at the gateway.', $morder->id, date_i18n('Y-m-d H:i:s'), $payment_transaction_id );

				// Add to order notes.
				$morder->add_order_note( sprintf( 'Webhook: Order ID %1$s successfully refunded for transaction ID %2$s at the gateway.', $morder->id, $payment_transaction_id ) );

				$morder->SaveOrder();

				$user = get_userdata( $morder->user_id );
				if ( empty( $user ) ) {
					$logstr .= "Couldn't find the old order's user. Order ID = " . $morder->id . ".";
					pmpro_stripeWebhookExit();
				}

				// Send an email to the member.
				$myemail = new PMProEmail();
				$myemail->sendRefundedEmail( $user, $morder );

				// Send an email to the admin.
				$myemail = new PMProEmail();
				$myemail->sendRefundedAdminEmail( $user, $morder );

				pmpro_stripeWebhookExit();
			} else {
				//We can't find that order				
				$logstr .= sprintf( 'Webhook: Transaction ID %1$s was refunded at the gateway on %2$s, but we could not find a matching order.', $payment_transaction_id, date_i18n('Y-m-d H:i:s') );

				pmpro_stripeWebhookExit();			
			}		
		}
		elseif($pmpro_stripe_event->type == "checkout.session.completed")
		{
			// Make sure we have the checkout session in the desired API version.
			$checkout_session = Stripe_Checkout_Session::retrieve( $pmpro_stripe_event->data->object->id );

			// Let's then find the PMPro order for the checkout session.
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT pmpro_membership_order_id FROM $wpdb->pmpro_membership_ordermeta WHERE meta_key = 'stripe_checkout_session_id' AND meta_value = %s LIMIT 1", $checkout_session->id ) );
			if ( empty( $order_id ) ) {
				$logstr .= "Could not find an order for Checkout Session " . $checkout_session->id;
				pmpro_stripeWebhookExit();
			}
			$order = new MemberOrder( $order_id );
			if (  empty( $order ) ) {
				$logstr .= "Order ID " . $order_id . " for Checkout Session " . $checkout_session->id . " could not be found.";
				pmpro_stripeWebhookExit();
			}

			// Get the payment method object for this checkout and set transaction and subscription ids.
			$payment_method = null;
			if ( $checkout_session->mode === 'payment' ) {
				// User purchased a one-time payment level. Assign the charge ID to the order.
				try {
					$payment_intent_args = array(
						'id'     => $checkout_session->payment_intent,
						'expand' => array(
							'payment_method',
							'latest_charge',
						),
					);
					$payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_args );
					$order->payment_transaction_id = empty( $checkout_session->invoice ) ? $payment_intent->latest_charge->id : $checkout_session->invoice;
					if ( ! empty( $payment_intent->payment_method ) ) {
						$payment_method = $payment_intent->payment_method;
					}
				} catch ( \Stripe\Error\Base $e ) {
					// Could not get payment intent. We just won't set a payment transaction ID.
				}
			} elseif ( $checkout_session->mode === 'subscription' ) {
				// User purchased a subscription. Assign the subscription ID invoice ID to the order.
				$order->subscription_transaction_id = $checkout_session->subscription;
				try {
					$subscription_args = array(
						'id'     => $checkout_session->subscription,
						'expand' => array(
							'latest_invoice',
							'default_payment_method',
						),
					);
					$subscription = \Stripe\Subscription::retrieve( $subscription_args );
					if ( ! empty( $subscription->latest_invoice->id ) ) {
						$order->payment_transaction_id = $subscription->latest_invoice->id;
					}
					if ( ! empty( $subscription->default_payment_method ) ) {
						$payment_method = $subscription->default_payment_method;
					}
				} catch ( \Stripe\Error\Base $e ) {
					// Could not get invoices. We just won't set a payment transaction ID.
				}

				// Also remove any application fee on the subscription. We will add it to individual invoices when they're created.
				try {
					Stripe_Subscription::update(
						$checkout_session->subscription,
						array(
							'application_fee_percent' => 0,
						)
					);
					$logstr .= "Updated application fee for subscription " . $checkout_session->subscription . " to 0%.";
				} catch ( Exception $e ) {
					$logstr .= "Could not update application fee for subscription " . $checkout_session->subscription . ". " . $e->getMessage();
				}
			}
			// Update payment method and billing address on order.
			if ( empty( $payment_method ) ) {
				$logstr .= "Could not find payment method for Checkout Session " . $checkout_session->id . ".";				
			}
			pmpro_stripe_webhook_populate_order_from_payment( $order, $payment_method, empty( $subscription ) ? null : $subscription->customer );

			// Update the amounts paid.
			global $pmpro_currency;
			$currency = pmpro_get_currency();
			$currency_unit_multiplier = pow( 10, intval( $currency['decimals'] ) );

			$order->total    = (float) $checkout_session->amount_total / $currency_unit_multiplier;
			if ( in_array( get_option( 'pmpro_stripe_tax' ), array( 'inclusive', 'exclusive' ) ) ) {
				// If Stripe calculated tax, use that. Otherwise, keep the tax calculated by PMPro.
				$order->subtotal = (float) $checkout_session->amount_subtotal / $currency_unit_multiplier;
				$order->tax      = (float) $checkout_session->total_details->amount_tax / $currency_unit_multiplier;
			}

			// Was the checkout session successful?
			if ( $checkout_session->payment_status == "paid" || $checkout_session->payment_status == "no_payment_required" ) {
				// Yes. But did we already process this order?
				if ( ! in_array( $order->status , array( 'token', 'pending' ) ) ) {
					$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " has already been processed. Ignoring.";
					pmpro_stripeWebhookExit();
				}
				// No we have not processed this order. Let's process it now.
				if ( pmpro_stripe_webhook_change_membership_level( $order, $checkout_session ) ) {
					$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " was processed successfully.";
				} else {
					$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " could not be processed.";
					$order->status = "error";
					$order->saveOrder();
				}
			} else {
				// No. The user is probably using a delayed notification payment method.
				// Set to pending in the meantime and wait for the next webhook.
				$order->status = "pending";
				$order->saveOrder();
				$logstr .= "Checkout Session " . $checkout_session->id . " has not yet been processed for PMPro order ID " . $order->id . ".";
			}
			pmpro_stripeWebhookExit();

		}
		elseif($pmpro_stripe_event->type == "checkout.session.async_payment_succeeded")
		{
			// Make sure we have the checkout session in the desired API version.
			$checkout_session = Stripe_Checkout_Session::retrieve( $pmpro_stripe_event->data->object->id );

			// Let's then find the PMPro order for the checkout session.
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT pmpro_membership_order_id FROM $wpdb->pmpro_membership_ordermeta WHERE meta_key = 'stripe_checkout_session_id' AND meta_value = %s LIMIT 1", $checkout_session->id ) );
			if ( empty( $order_id ) ) {
				$logstr .= "Could not find an order for Checkout Session " . $checkout_session->id;
				pmpro_stripeWebhookExit();
			}
			$order = new MemberOrder( $order_id );
			if (  empty( $order ) ) {
				$logstr .= "Order ID " . $order_id . " for Checkout Session " . $checkout_session->id . " could not be found.";
				pmpro_stripeWebhookExit();
			}

			// Have we already processed this order?
			if ( ! in_array( $order->status , array( 'token', 'pending' ) ) ) {
				$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " has already been processed. Ignoring.";
				pmpro_stripeWebhookExit();
			}
			// No we have not processed this order. Let's process it now.
			if ( pmpro_stripe_webhook_change_membership_level( $order, $checkout_session ) ) {
				$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " was processed successfully.";
			} else {
				$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " could not be processed.";
				$order->status = "error";
				$order->saveOrder();
			}
			pmpro_stripeWebhookExit();
		}
		elseif($pmpro_stripe_event->type == "checkout.session.async_payment_failed")
		{
			// Make sure we have the checkout session in the desired API version.
			$checkout_session = Stripe_Checkout_Session::retrieve( $pmpro_stripe_event->data->object->id );

			// Let's then find the PMPro order for the checkout session.
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT pmpro_membership_order_id FROM $wpdb->pmpro_membership_ordermeta WHERE meta_key = 'stripe_checkout_session_id' AND meta_value = %s LIMIT 1", $checkout_session->id ) );
			if ( empty( $order_id ) ) {
				$logstr .= "Could not find an order for Checkout Session " . $checkout_session->id;
				pmpro_stripeWebhookExit();
			}
			$order = new MemberOrder( $order_id );
			if (  empty( $order ) ) {
				$logstr .= "Order ID " . $order_id . " for Checkout Session " . $checkout_session->id . " could not be found.";
				pmpro_stripeWebhookExit();
			}

			// Mark the order as failed.
			$order->status = "error";
			$order->saveOrder();

			// Email the user to notify them of failed payment
			$pmproemail = new PMProEmail();
			$pmproemail->sendBillingFailureEmail( get_userdata( $order->user_id ), $order);

			// Email admin so they are aware of the failure
			$pmproemail = new PMProEmail();
			$pmproemail->sendBillingFailureAdminEmail( get_bloginfo( 'admin_email'), $order );

			$logstr .= "Order #" . $order->id . " for Checkout Session " . $checkout_session->id . " could not be processed.";
			pmpro_stripeWebhookExit();
		} elseif ( $pmpro_stripe_event->type == 'invoice.created' || $pmpro_stripe_event->type == 'invoice.upcoming' ) {
			// Make sure we have the invoice in the desired API version.
			if ( ! empty( $pmpro_stripe_event->data->object->id ) ) {
				$invoice = Stripe_Invoice::retrieve( $pmpro_stripe_event->data->object->id );
			} else {
				// We don't have an invoice ID, so we're likely processing the 'invoice.upcoming' event.
				// In this case, we're just trying to remove any application fee from the subscription.
				// Use the data object as-is and let's hope it's in the correct API version. If not, this code will just bail during the following check which is ok too.
				$invoice = $pmpro_stripe_event->data->object;
			}

			// Check if a subscription ID exists on the invoice. If not, this is not a PMPro recurring payment.
			$subscription_id = empty( $invoice->parent->subscription_details->subscription ) ? null : $invoice->parent->subscription_details->subscription;
			if ( empty( $subscription_id ) ) {
				// Upcoming invoices will not have an ID set.
				$invoice_id = empty( $invoice->id ) ? '[upcoming]' : $invoice->id;
				$logstr .= "Invoice " . $invoice_id . " is not for a subscription and is therefore not a PMPro recurring payment. No action taken.";
				pmpro_stripeWebhookExit();
			}

			// We have a subscription ID. Let's make sure that this is a PMPro subscription.
			$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscription_id, 'stripe', $livemode ? 'live' : 'sandbox' );
			if ( empty( $subscription ) ) {
				$logstr .= "Could not find a PMPro subscription with transaction ID " . $subscription_id . ". No action taken.";
				pmpro_stripeWebhookExit();
			}

			// Remove the application fee on the subscription. We will add it to individual invoices when they're created.
			try {
				Stripe_Subscription::update(
					$subscription_id,
					array(
						'application_fee_percent' => 0,
					)
				);
				$logstr .= "Updated application fee for subscription " . $subscription_id . " to 0%.";
			} catch ( Exception $e ) {
				$logstr .= "Could not update application fee for subscription " . $subscription_id . ". " . $e->getMessage();
			}

			// If we're processing 'invoice.upcoming', we don't need to do anything else.
			// We will update the invoice application fee when it is actually created.
			if ( $pmpro_stripe_event->type == 'invoice.upcoming' ) {
				pmpro_stripeWebhookExit();
			}

			// If the invoice is not in draft status, we don't need to do anything.
			if ( $invoice->status !== 'draft' ) {
				$logstr .= "Invoice " . $invoice->id . " is not in draft status. No action taken.";
				pmpro_stripeWebhookExit();
			}

			// If the site is using API keys, we don't need to update the application fee.
			if ( $stripe->using_api_keys() ) {
				$logstr .= "Using API keys, so not updating application fee for invoice " . $invoice->id . ".";
				pmpro_stripeWebhookExit();
			}

			// Update the application fee on the invoice.
			$application_fee = $stripe->get_application_fee_percentage();
			try {
				Stripe_Invoice::update(
					$invoice->id,
					array(
						'application_fee_amount' => floor( $invoice->amount_due * ( $application_fee / 100 ) ),
					)
				);
				$logstr .= "Updated application fee for invoice " . $invoice->id . " to " . $application_fee . "%.";
			} catch ( Exception $e ) {
				$logstr .= "Could not update application fee for invoice " . $invoice->id . ". " . $e->getMessage();
			}
			pmpro_stripeWebhookExit();
		}

		$logstr .= "Not handled event type = " . $pmpro_stripe_event->type;

		pmpro_unhandled_webhook();
		pmpro_stripeWebhookExit();
	}
	else
	{
		if(!empty($event_id))
			$logstr .= "Could not find an event with ID #" . $event_id;
		else
			$logstr .= "No event ID given.";

		pmpro_unhandled_webhook();
		pmpro_stripeWebhookExit();
	}

	function pmpro_stripeWebhookExit()
	{
		global $logstr;

		/**
		 * Allow custom code to run before exiting.
		 *
		 * @since 2.11
		 */
		do_action( 'pmpro_stripe_webhook_before_exit' );

		//for log
		if($logstr)
		{
			$logstr = "Logged On: " . date_i18n("m/d/Y H:i:s") . "\n" . $logstr . "\n-------------\n";

			echo esc_html( $logstr );

			//log in file or email?
			if(defined('PMPRO_STRIPE_WEBHOOK_DEBUG') && PMPRO_STRIPE_WEBHOOK_DEBUG === "log")
			{
				//file
				$logfile = apply_filters( 'pmpro_stripe_webhook_logfile', pmpro_get_restricted_file_path( 'logs', 'stripe-webhook.txt' ) );
				$loghandle = fopen( $logfile, "a+" );
				fwrite( $loghandle, $logstr );
				fclose( $loghandle );
			}
			elseif(defined('PMPRO_STRIPE_WEBHOOK_DEBUG') && false !== PMPRO_STRIPE_WEBHOOK_DEBUG )
			{
				//email
				if(strpos(PMPRO_STRIPE_WEBHOOK_DEBUG, "@"))
					$log_email = PMPRO_STRIPE_WEBHOOK_DEBUG;	//constant defines a specific email address
				else
					$log_email = get_option("admin_email");

				wp_mail( $log_email, get_option( "blogname" ) . " Stripe Webhook Log", nl2br( esc_html( $logstr ) ) );
			}
		}

		exit;
	}

/**
 * Assign a membership level when a checkout is completed via Stripe webhook.
 *
 * Steps:
 * 1. Pull checkout data from order meta.
 * 2. Build checkout level.
 * 3. Change membership level.
 * 4. Mark order as successful.
 * 5. Record discount code use.
 * 6. Save some user meta.
 * 7. Run pmpro_after_checkout.
 * 8. Send checkout emails.
 *
 * @since 2.8
 *
 * @param MemberOrder $morder The order for the checkout being completed.
 * @return bool
 */
function pmpro_stripe_webhook_change_membership_level( $morder ) {
	pmpro_pull_checkout_data_from_order( $morder );
 	return pmpro_complete_async_checkout( $morder );
}

/**
 * Update order information from a Stripe payment method.
 *
 * @since 2.8
 *
 * @param MemberOrder          $order            The order to update.
 * @param Stripe_PaymentMethod $payment_method   The payment method object.
 * @param string               $customer_id      The Stripe customer to try to pull a billing address from if not on the payment method.
 */
function pmpro_stripe_webhook_populate_order_from_payment( $order, $payment_method, $customer_id = null ) {
	global $wpdb;

	// Fill the "Payment Type" and credit card fields.
	if ( ! empty( $payment_method ) && ! empty( $payment_method->type ) ) {
		$order->payment_type = 'Stripe - ' . $payment_method->type;
		if ( ! empty( $payment_method->card ) ) {
			// Paid with a card, let's update order and user meta with the card info.
			$order->cardtype = $payment_method->card->brand;
			$order->accountnumber = hideCardNumber( $payment_method->card->last4 );
			$order->expirationmonth = $payment_method->card->exp_month;
			$order->expirationyear = $payment_method->card->exp_year;
		} else {
			$order->cardtype = '';
			$order->accountnumber = '';
			$order->expirationmonth = '';
			$order->expirationyear = '';
		}
	} else {
		// Some defaults.
		$order->payment_type = 'Stripe';
		$order->cardtype = '';
		$order->accountnumber = '';
		$order->expirationmonth = '';
		$order->expirationyear = '';
	}

	// Check if we have a billing address in the payment method.
	if ( ! empty( $payment_method ) && ! empty( $payment_method->billing_details ) && ! empty( $payment_method->billing_details->address ) && ! empty( $payment_method->billing_details->address->line1 ) ) {
		$order->billing = new stdClass();
		$order->billing->name = empty( $payment_method->billing_details->name ) ? '' : $payment_method->billing_details->name;
		$order->billing->street = empty( $payment_method->billing_details->address->line1 ) ? '' : $payment_method->billing_details->address->line1;
		$order->billing->street2 = empty( $payment_method->billing_details->address->line2 ) ? '' : $payment_method->billing_details->address->line2;
		$order->billing->city = empty( $payment_method->billing_details->address->city ) ? '' : $payment_method->billing_details->address->city;
		$order->billing->state = empty( $payment_method->billing_details->address->state ) ? '' : $payment_method->billing_details->address->state;
		$order->billing->zip = empty( $payment_method->billing_details->address->postal_code ) ? '' : $payment_method->billing_details->address->postal_code;
		$order->billing->country = empty( $payment_method->billing_details->address->country ) ? '' : $payment_method->billing_details->address->country;
		$order->billing->phone = empty( $payment_method->billing_details->phone ) ? '' : $payment_method->billing_details->phone;
	} else {
		// No billing address in the payment method, let's try to get it from the customer.
		if ( ! empty( $customer_id ) ) {
			$customer = Stripe_Customer::retrieve( $customer_id );
		}
		if ( ! empty( $customer ) && ! empty( $customer->address ) && ! empty( $customer->address->line1 ) ) {
			$order->billing = new stdClass();
			$order->billing->name = empty( $customer->name ) ? '' : $customer->name;
			$order->billing->street = empty( $customer->address->line1 ) ? '' : $customer->address->line1;
			$order->billing->street2 = empty( $customer->address->line2 ) ? '' : $customer->address->line2;
			$order->billing->city = empty( $customer->address->city ) ? '' : $customer->address->city;
			$order->billing->state = empty( $customer->address->state ) ? '' : $customer->address->state;
			$order->billing->zip = empty( $customer->address->postal_code ) ? '' : $customer->address->postal_code;
			$order->billing->country = empty( $customer->address->country ) ? '' : $customer->address->country;
			$order->billing->phone = empty( $customer->phone ) ? '' : $customer->phone;
		} else {
			// No billing address in the customer, let's try to get it from the old order or from user meta.
			$order->find_billing_address();
		}
	}
}

/**
 * Build "extra order data" array from invoice to be passed to gateway request handler functions.
 *
 * @since 3.6
 *
 * @param Stripe_Invoice $invoice The invoice object from Stripe.
 * @return array The order data array.
 */
function pmpro_stripe_webhook_get_order_data_from_invoice( $invoice ) {
	global $pmpro_currency, $pmpro_currencies;

	// Build the order data array.
	// Note that we will not set id, user_id, membership_id, or status here.
	// Those will be set in the abstract gateway request handler functions.
	$order_data = array();

	// Set order data that is already formatted correctly.
	$order_data['gateway'] = 'stripe';
	$order_data['gateway_environment'] = ( ! empty( $invoice->livemode ) && $invoice->livemode ) ? 'live' : 'sandbox';
	$order_data['timestamp'] = $invoice->created;
	$order_data['subscription_transaction_id'] = $invoice->parent->subscription_details->subscription;
	$order_data['payment_transaction_id'] = $invoice->id;

	// Set order pricing data.
	$currency_unit_multiplier = 100; // Default to 100 cents / USD
	if ( is_array($pmpro_currencies[$pmpro_currency] ) && isset( $pmpro_currencies[$pmpro_currency]['decimals'] ) ) {
		$currency_unit_multiplier = pow( 10, intval( $pmpro_currencies[$pmpro_currency]['decimals'] ) );
	}
	$order_data['subtotal'] = (! empty( $invoice->subtotal ) ? $invoice->subtotal / $currency_unit_multiplier : 0);
	$order_data['tax'] = (! empty($invoice->tax) ? $invoice->tax / $currency_unit_multiplier : 0);
	$order_data['total'] = (! empty($invoice->total) ? $invoice->total / $currency_unit_multiplier : 0);

	// Set payment information data.
	// Find the payment intent.
	if ( ! empty( $invoice->payments->data[0]->payment->payment_intent ) ) {
		$payment_intent_args = array(
			'id'     => $invoice->payments->data[0]->payment->payment_intent,
			'expand' => array(
				'payment_method',
				'latest_charge',
			),
		);
		$payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_args );

		// Find the payment method.
		$payment_method = null;
		if ( ! empty( $payment_intent->payment_method ) ) {
			$payment_method = $payment_intent->payment_method;
		} elseif( ! empty( $payment_intent->latest_charge ) ) {
			// If we didn't get a payment method, check the charge.
			$payment_method = $payment_intent->latest_charge->payment_method_details;
		}
	}

	// Set the payment type and card info if we have a payment method.
	if ( ! empty( $payment_method ) ) {		       	
		$order_data['payment_type'] = 'Stripe - ' . $payment_method->type;
		if ( ! empty( $payment_method->card ) ) {
			// Paid with a card, let's update order and user meta with the card info.
			$order_data['cardtype'] = $payment_method->card->brand;
			$order_data['accountnumber'] = hideCardNumber( $payment_method->card->last4 );
			$order_data['expirationmonth'] = $payment_method->card->exp_month;
			$order_data['expirationyear'] = $payment_method->card->exp_year;
		}
	} else {
		$order_data['payment_type'] = 'Stripe';
	}

	// Set the billing address.
	if ( ! empty( $payment_method ) && ! empty( $payment_method->billing_details ) && ! empty( $payment_method->billing_details->address ) && ! empty( $payment_method->billing_details->address->line1 ) ) {
		$order_data['billing'] = new stdClass();
		$order_data['billing']->name = empty( $payment_method->billing_details->name ) ? '' : $payment_method->billing_details->name;
		$order_data['billing']->street = empty( $payment_method->billing_details->address->line1 ) ? '' : $payment_method->billing_details->address->line1;
		$order_data['billing']->street2 = empty( $payment_method->billing_details->address->line2 ) ? '' : $payment_method->billing_details->address->line2;
		$order_data['billing']->city = empty( $payment_method->billing_details->address->city ) ? '' : $payment_method->billing_details->address->city;
		$order_data['billing']->state = empty( $payment_method->billing_details->address->state ) ? '' : $payment_method->billing_details->address->state;
		$order_data['billing']->zip = empty( $payment_method->billing_details->address->postal_code ) ? '' : $payment_method->billing_details->address->postal_code;
		$order_data['billing']->country = empty( $payment_method->billing_details->address->country ) ? '' : $payment_method->billing_details->address->country;
		$order_data['billing']->phone = empty( $payment_method->billing_details->phone ) ? '' : $payment_method->billing_details->phone;
	} else {
		// No billing address in the payment method, let's try to get it from the customer.
		if ( ! empty( $invoice->customer ) ) {
			$customer = Stripe_Customer::retrieve( $invoice->customer );
		}
		if ( ! empty( $customer ) && ! empty( $customer->address ) && ! empty( $customer->address->line1 ) ) {
			$order_data['billing'] = new stdClass();
			$order_data['billing']->name = empty( $customer->name ) ? '' : $customer->name;
			$order_data['billing']->street = empty( $customer->address->line1 ) ? '' : $customer->address->line1;
			$order_data['billing']->street2 = empty( $customer->address->line2 ) ? '' : $customer->address->line2;
			$order_data['billing']->city = empty( $customer->address->city ) ? '' : $customer->address->city;
			$order_data['billing']->state = empty( $customer->address->state ) ? '' : $customer->address->state;
			$order_data['billing']->zip = empty( $customer->address->postal_code ) ? '' : $customer->address->postal_code;
			$order_data['billing']->country = empty( $customer->address->country ) ? '' : $customer->address->country;
			$order_data['billing']->phone = empty( $customer->phone ) ? '' : $customer->phone;
		} else {
			// No billing address in the customer, so try to pull it from the most recent subscription order.
			$last_order = MemberOrder::get_order(
				array(
					'gateway'                        => $order_data['gateway'],
					'gateway_environment'            => $order_data['gateway_environment'],
					'subscription_transaction_id'    => $order_data['subscription_transaction_id'],
					'status'                         => 'success',
				)
			);
			if ( ! empty( $last_order ) && ! empty( $last_order->billing ) ) {
				$order_data['billing'] = new stdClass();
				$order_data['billing']->name = empty( $last_order->billing->name ) ? '' : $last_order->billing->name;
				$order_data['billing']->street = empty( $last_order->billing->street ) ? '' : $last_order->billing->street;
				$order_data['billing']->street2 = empty( $last_order->billing->street2 ) ? '' : $last_order->billing->street2;
				$order_data['billing']->city = empty( $last_order->billing->city ) ? '' : $last_order->billing->city;
				$order_data['billing']->state = empty( $last_order->billing->state ) ? '' : $last_order->billing->state;
				$order_data['billing']->zip = empty( $last_order->billing->zip ) ? '' : $last_order->billing->zip;
				$order_data['billing']->country = empty( $last_order->billing->country ) ? '' : $last_order->billing->country;
				$order_data['billing']->phone = empty( $last_order->billing->phone ) ? '' : $last_order->billing->phone;
			}
		}
	}

	return $order_data;
}
