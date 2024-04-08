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
	use Stripe\Event as Stripe_Event;
	use Stripe\PaymentMethod as Stripe_PaymentMethod;
	use Stripe\Customer as Stripe_Customer;

	global $logstr;	

	if(!class_exists("Stripe\Stripe")) {
		require_once( PMPRO_DIR . "/includes/lib/Stripe/init.php" );
	}

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
		if ( PMProGateway_stripe::using_legacy_keys() ) {
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
			if($pmpro_stripe_event->data->object->amount_due > 0)
			{
				//do we have this order yet? (check status too)
				$order = new MemberOrder();
				$order->getMemberOrderByPaymentTransactionID( $pmpro_stripe_event->data->object->id );

				//no? create it
				if(empty($order->id))
				{				
					$old_order = new MemberOrder();
					$old_order->getLastMemberOrderBySubscriptionTransactionID($pmpro_stripe_event->data->object->subscription);
					
					//still can't find the order
					if(empty($old_order) || empty($old_order->id))
					{
						$logstr .= "Couldn't find the original subscription.";
						pmpro_stripeWebhookExit();
					}

					$user_id = $old_order->user_id;
					$user = get_userdata($user_id);

					if ( empty( $user ) ) {
						$logstr .= "Couldn't find the old order's user. Order ID = " . $old_order->id . ".";
						pmpro_stripeWebhookExit();
					}
					$user->membership_level = pmpro_getMembershipLevelForUser($user_id);

					$invoice = $pmpro_stripe_event->data->object;

					//alright. create a new order/invoice
					$morder = new MemberOrder();
					$morder->user_id = $old_order->user_id;
					$morder->membership_id = $old_order->membership_id;
					$morder->timestamp = $invoice->created;
					
					global $pmpro_currency;
					global $pmpro_currencies;
					
					$currency_unit_multiplier = 100; // 100 cents / USD

					//account for zero-decimal currencies like the Japanese Yen
					if(is_array($pmpro_currencies[$pmpro_currency]) && isset($pmpro_currencies[$pmpro_currency]['decimals']) && $pmpro_currencies[$pmpro_currency]['decimals'] == 0)
						$currency_unit_multiplier = 1;
					
					if(isset($invoice->amount))
					{
						$morder->subtotal = $invoice->amount / $currency_unit_multiplier;
						$morder->tax = 0;
					}
					elseif(isset($invoice->subtotal))
					{
						$morder->subtotal = (! empty( $invoice->subtotal ) ? $invoice->subtotal / $currency_unit_multiplier : 0);
						$morder->tax = (! empty($invoice->tax) ? $invoice->tax / $currency_unit_multiplier : 0);
						$morder->total = (! empty($invoice->total) ? $invoice->total / $currency_unit_multiplier : 0);
					}

					$morder->payment_transaction_id = $invoice->id;
					$morder->subscription_transaction_id = $invoice->subscription;

					$morder->gateway = $old_order->gateway;
					$morder->gateway_environment = $old_order->gateway_environment;

					// Find the payment intent.
					$payment_intent_args = array(
						'id'     => $invoice->payment_intent,
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
					if ( empty( $payment_method ) ) {						
						$logstr .= "Could not find payment method for invoice " . $invoice->id . ".";						
					}
					// Update payment method and billing address on order.
					pmpro_stripe_webhook_populate_order_from_payment( $morder, $payment_method, $payment_intent->customer );				

					//save
					$morder->status = "success";
					$morder->saveOrder();
					$morder->getMemberOrderByID($morder->id);

					//email the user their invoice
					$pmproemail = new PMProEmail();
					$pmproemail->sendInvoiceEmail($user, $morder);

					$logstr .= "Created new order with ID #" . $morder->id . ". Event ID #" . $pmpro_stripe_event->id . ".";

					do_action('pmpro_subscription_payment_completed', $morder);

					pmpro_stripeWebhookExit();
				}
				else
				{
					$logstr .= "We've already processed this order with ID #" . $order->id . ". Event ID #" . $pmpro_stripe_event->id . ".";
					pmpro_stripeWebhookExit();
				}
			}
			else
			{
				$logstr .= "Ignoring an invoice for $0. Probably for a new subscription just created. Event ID #" . $pmpro_stripe_event->id . ".";
				pmpro_stripeWebhookExit();
			}
		}
		elseif($pmpro_stripe_event->type == "invoice.payment_action_required") {
			$invoice = $pmpro_stripe_event->data->object;

			// Get the last order for this invoice's subscription.
			if ( ! empty( $invoice->subscription ) ) {
				$old_order = new MemberOrder();
				$old_order->getLastMemberOrderBySubscriptionTransactionID( $invoice->subscription );
			}

			if( ! empty( $old_order ) && ! empty( $old_order->id ) ) {
				$user_id = $old_order->user_id;
				$user = get_userdata($user_id);
				if ( empty( $user ) ) {
					$logstr .= "Couldn't find the old order's user. Order ID = " . $old_order->id . ".";
					pmpro_stripeWebhookExit();
				}

				// Prep order for emails.
				$morder = new MemberOrder();
				$morder->user_id = $user_id;

				// Find the payment intent.
		        $payment_intent_args = array(
		          'id'     => $invoice->payment_intent,
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
				if ( empty( $payment_method ) ) {		       	
					$logstr .= "Could not find payment method for invoice " . $invoice->id;					
				}
				// Update payment method and billing address on order.
				pmpro_stripe_webhook_populate_order_from_payment( $morder, $payment_method, $payment_intent->customer );

				// Add invoice link to the order.
				$morder->invoice_url = $invoice->hosted_invoice_url;

				// Email the user and ask them to authenticate their payment.
				$pmproemail = new PMProEmail();
				$pmproemail->sendPaymentActionRequiredEmail($user, $morder);

				// Email admin so they are aware.
				// TODO: Remove?
				$pmproemail = new PMProEmail();
				$pmproemail->sendPaymentActionRequiredAdminEmail($user, $morder);

				$logstr .= "Subscription payment for order ID #" . $old_order->id . " requires customer authentication. Sent email to the member and site admin.";
				pmpro_stripeWebhookExit();
			}
			else
			{
				$logstr .= "Could not find the related subscription for event with ID #" . $pmpro_stripe_event->id . ".";
				if(!empty($pmpro_stripe_event->data->object->customer))
					$logstr .= " Customer ID #" . $invoice->customer . ".";
				pmpro_stripeWebhookExit();
			}
		} elseif($pmpro_stripe_event->type == "charge.failed") {
			$charge = $pmpro_stripe_event->data->object;

			// Get the invoice for this charge if it exists.
			if ( ! empty( $charge->invoice ) ) {
				try {
					$invoice = Stripe_Invoice::retrieve( $charge->invoice );
				} catch ( Exception $e ) {
					error_log( 'Unable to fetch Stripe Invoice object: ' . $e->getMessage() );
					$invoice = null;
				}
			}

			// If we have an invoice, try to get the subscription ID from it.
			if ( ! empty( $invoice ) ) {
				$subscription_id = $invoice->subscription;
			} else {
				$subscription_id = null;
			}

			// If we have a subscription ID, get the last order for that subscription.
			if ( ! empty( $subscription_id ) ) {
				$old_order = new MemberOrder();
				$old_order->getLastMemberOrderBySubscriptionTransactionID( $subscription_id );
			}

			// If we have an old order, email the user that their payment failed.
			if( ! empty( $old_order ) && ! empty( $old_order->id ) )
			{
				do_action("pmpro_subscription_payment_failed", $old_order);

				$user_id = $old_order->user_id;
				$user = get_userdata($user_id);
				if ( empty( $user ) ) {
					$logstr .= "Couldn't find the old order's user. Order ID = " . $old_order->id . ".";
					pmpro_stripeWebhookExit();
				}

				//prep this order for the failure emails
				$morder = new MemberOrder();
				$morder->user_id = $user_id;
				$morder->membership_id = $old_order->membership_id;
				
				// Find the payment intent.
				$payment_intent_args = array(
					'id'     => $pmpro_stripe_event->data->object->payment_intent,
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
				if ( empty( $payment_method ) ) {
					$logstr .= "Could not find payment method for charge " . $pmpro_stripe_event->data->object->id . ".";
				}
				// Update payment method and billing address on order.
				pmpro_stripe_webhook_populate_order_from_payment( $morder, $payment_method, $payment_intent->customer );

				// Email the user and ask them to update their credit card information
				$pmproemail = new PMProEmail();
				$pmproemail->sendBillingFailureEmail($user, $morder);

				// Email admin so they are aware of the failure
				$pmproemail = new PMProEmail();
				$pmproemail->sendBillingFailureAdminEmail(get_bloginfo("admin_email"), $morder);

				$logstr .= "Subscription payment failed on order ID #" . $old_order->id . ". Sent email to the member and site admin.";
				pmpro_stripeWebhookExit();
			}
			else
			{
				$logstr .= "Could not find the related subscription for event with ID #" . $pmpro_stripe_event->id . ".";
				if(!empty($pmpro_stripe_event->data->object->customer))
					$logstr .= " Customer ID #" . $pmpro_stripe_event->data->object->customer . ".";
				pmpro_stripeWebhookExit();
			}
		}
		elseif($pmpro_stripe_event->type == "customer.subscription.deleted")
		{
			$logstr .= pmpro_handle_subscription_cancellation_at_gateway( $pmpro_stripe_event->data->object->id, 'stripe', $livemode ? 'live' : 'sandbox' );
			pmpro_stripeWebhookExit();
		}
		elseif( $pmpro_stripe_event->type == "charge.refunded" )
		{			
			$payment_transaction_id = $pmpro_stripe_event->data->object->id;
			$morder = new MemberOrder();
      		$morder->getMemberOrderByPaymentTransactionID( $payment_transaction_id );
		
			// Initial payment orders are stored using the invoice ID, so check that value too.
			if ( empty( $morder->id ) && ! empty( $pmpro_stripe_event->data->object->invoice ) ) {
				$payment_transaction_id = $pmpro_stripe_event->data->object->invoice;
				$morder->getMemberOrderByPaymentTransactionID( $payment_transaction_id );
			}

			//We've got the right order	
			if( !empty( $morder->id ) ) {
				// Ingore orders already in refund status.
				if( $morder->status == 'refunded' ) {					
					$logstr .= sprintf( 'Webhook: Order ID %1$s with transaction ID %2$s was already in refund status.', $morder->id, $payment_transaction_id );									
					pmpro_stripeWebhookExit();
				}
				
				// Handle partial refunds. Only updating the log and notes for now.
				if ( $pmpro_stripe_event->data->object->amount_refunded < $pmpro_stripe_event->data->object->amount ) {
					$logstr .= sprintf( 'Webhook: Order ID %1$s with transaction ID %2$s was partially refunded. The order will need to be updated in the WP dashboard.', $morder->id, $payment_transaction_id );
					$morder->notes = trim( $morder->notes . ' ' . sprintf( 'Webhook: Order ID %1$s was partially refunded on %2$s for transaction ID %3$s at the gateway.', $morder->id, date_i18n('Y-m-d H:i:s'), $payment_transaction_id ) );
					$morder->SaveOrder();
					pmpro_stripeWebhookExit();
				}
				
				// Full refund.	
				$morder->status = 'refunded';
				
				$logstr .= sprintf( 'Webhook: Order ID %1$s successfully refunded on %2$s for transaction ID %3$s at the gateway.', $morder->id, date_i18n('Y-m-d H:i:s'), $payment_transaction_id );

				// Add to order notes.
				$morder->notes = trim( $morder->notes . ' ' . sprintf( 'Webhook: Order ID %1$s successfully refunded on %2$s for transaction ID %3$s at the gateway.', $morder->id, date_i18n('Y-m-d H:i:s'), $payment_transaction_id ) );

				$morder->SaveOrder();

				$user = get_user_by( 'email', $morder->Email );
				if ( empty( $user ) ) {
					$logstr .= "Couldn't find the old order's user. Order ID = " . $old_order->id . ".";
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
			// First, let's get the checkout session.
			$checkout_session = $pmpro_stripe_event->data->object;

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
					$order->payment_transaction_id = $payment_intent->latest_charge;
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
			}
			// Update payment method and billing address on order.
			if ( empty( $payment_method ) ) {
				$logstr .= "Could not find payment method for Checkout Session " . $checkout_session->id . ".";				
			}
			pmpro_stripe_webhook_populate_order_from_payment( $order, $payment_method, $subscription->customer );

			// Update the amounts paid.
			global $pmpro_currency;
			$currency = pmpro_get_currency();
			$currency_unit_multiplier = pow( 10, intval( $currency['decimals'] ) );

			$order->total    = (float) $checkout_session->amount_total / $currency_unit_multiplier;
			$order->subtotal = (float) $checkout_session->amount_subtotal / $currency_unit_multiplier;
			$order->tax      = (float) $checkout_session->total_details->amount_tax / $currency_unit_multiplier;

			// Was the checkout session successful?
			if ( $checkout_session->payment_status == "paid" ) {
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
			// First, let's get the checkout session.
			$checkout_session = $pmpro_stripe_event->data->object;

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
			// First, let's get the checkout session.
			$checkout_session = $pmpro_stripe_event->data->object;

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
				$logfile = apply_filters( 'pmpro_stripe_webhook_logfile', dirname( __FILE__ ) . "/../logs/stripe-webhook.txt" );
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
			$order->ExpirationDate = $order->expirationmonth . $order->expirationyear;
			$order->ExpirationDate_YdashM = $order->expirationyear . "-" . $order->expirationmonth;			
		} else {
			$order->cardtype = '';
			$order->accountnumber = '';
			$order->expirationmonth = '';
			$order->expirationyear = '';
			$order->ExpirationDate = '';
			$order->ExpirationDate_YdashM = '';
		}
	} else {
		// Some defaults.
		$order->payment_type = 'Stripe';
		$order->cardtype = '';
		$order->accountnumber = '';
		$order->expirationmonth = '';
		$order->expirationyear = '';
		$order->ExpirationDate = '';
		$order->ExpirationDate_YdashM = '';
	}

	// Check if we have a billing address in the payment method.
	if ( ! empty( $payment_method ) && ! empty( $payment_method->billing_details ) && ! empty( $payment_method->billing_details->address ) && ! empty( $payment_method->billing_details->address->line1 ) ) {
		$order->billing = new stdClass();
		$order->billing->name = empty( $payment_method->billing_details->name ) ? '' : $payment_method->billing_details->name;
		$order->billing->street = empty( $payment_method->billing_details->address->line1 ) ? '' : $payment_method->billing_details->address->line1;
		$order->billing->city = empty( $payment_method->billing_details->address->city ) ? '' : $payment_method->billing_details->address->city;
		$order->billing->state = empty( $payment_method->billing_details->address->state ) ? '' : $payment_method->billing_details->address->state;
		$order->billing->zip = empty( $payment_method->billing_details->address->postal_code ) ? '' : $payment_method->billing_details->address->postal_code;
		$order->billing->country = empty( $payment_method->billing_details->address->country ) ? '' : $payment_method->billing_details->address->country;
		$order->billing->phone = empty( $payment_method->billing_details->phone ) ? '' : $payment_method->billing_details->phone;

		$name_parts = empty( $payment_method->billing_details->name ) ? [] : pnp_split_full_name( $payment_method->billing_details->name );
		$order->FirstName = empty( $name_parts['fname'] ) ? '' : $name_parts['fname'];
		$order->LastName = empty( $name_parts['lname'] ) ? '' : $name_parts['lname'];
	} else {
		// No billing address in the payment method, let's try to get it from the customer.
		if ( ! empty( $customer_id ) ) {
			$customer = Stripe_Customer::retrieve( $customer_id );
		}
		if ( ! empty( $customer ) && ! empty( $customer->address ) && ! empty( $customer->address->line1 ) ) {
			$order->billing = new stdClass();
			$order->billing->name = empty( $customer->name ) ? '' : $customer->name;
			$order->billing->street = empty( $customer->address->line1 ) ? '' : $customer->address->line1;
			$order->billing->city = empty( $customer->address->city ) ? '' : $customer->address->city;
			$order->billing->state = empty( $customer->address->state ) ? '' : $customer->address->state;
			$order->billing->zip = empty( $customer->address->postal_code ) ? '' : $customer->address->postal_code;
			$order->billing->country = empty( $customer->address->country ) ? '' : $customer->address->country;
			$order->billing->phone = empty( $customer->phone ) ? '' : $customer->phone;

			$name_parts = empty( $customer->name ) ? [] : pnp_split_full_name( $customer->name );
			$order->FirstName = empty( $name_parts['fname'] ) ? '' : $name_parts['fname'];
			$order->LastName = empty( $name_parts['lname'] ) ? '' : $name_parts['lname'];
		} else {
			// No billing address in the customer, let's try to get it from the old order or from user meta.
			$order->find_billing_address();
		}
	}
	$order->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . esc_sql( $order->user_id ) . "' LIMIT 1");
	$order->Address1 = $order->billing->street;
	$order->City = $order->billing->city;
	$order->State = $order->billing->state;
	$order->Zip = $order->billing->zip;
	$order->Country = $order->billing->country;
	$order->PhoneNumber = $order->billing->phone;
}
