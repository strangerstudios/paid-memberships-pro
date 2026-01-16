<?php
//in case the file is loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//uncomment to log requests in logs/ipn.txt
//define('PMPRO_IPN_DEBUG', true);

//some globals
global $wpdb, $gateway_environment, $logstr;
$logstr = "";    //will put debug info here and write to ipnlog.txt

// Sets the PMPRO_DOING_WEBHOOK constant and fires the pmpro_doing_webhook action.
pmpro_doing_webhook( 'paypal', true );

//validate?
if ( ! pmpro_ipnValidate() ) {
	//validation failed
	pmpro_ipnExit();
}

//assign posted variables to local variables
$txn_type               = pmpro_getParam( "txn_type", "POST" );
$subscr_id              = pmpro_getParam( "subscr_id", "POST" );
$txn_id                 = pmpro_getParam( "txn_id", "POST" );
$item_name              = pmpro_getParam( "item_name", "POST" );
$item_number            = pmpro_getParam( "item_number", "POST" );
$initial_payment_txn_id = pmpro_getParam( "initial_payment_txn_id", "POST" );
$initial_payment_status = strtolower( pmpro_getParam( "initial_payment_status", "POST" ) );
$payment_amount         = pmpro_getParam( "payment_amount", "POST" );
$payment_currency       = pmpro_getParam( "payment_currency", "POST" );
$receiver_email         = pmpro_getParam( "receiver_email", "POST", '', 'sanitize_email' );
$refund_amount          = pmpro_getParam( "refund_amount", "POST" );
$business_email         = pmpro_getParam( "business", "POST", '', 'sanitize_email'  );
$payer_email            = pmpro_getParam( "payer_email", "POST", '', 'sanitize_email'  );
$recurring_payment_id   = pmpro_getParam( "recurring_payment_id", "POST" );
$profile_status         = strtolower( pmpro_getParam( "profile_status", "POST" ) );
$payment_status 		= strtolower( pmpro_getParam( "payment_status", "POST" ) );
$parent_txn_id  		= pmpro_getParam( "parent_txn_id", "POST" );

if ( empty( $subscr_id ) ) {
	$subscr_id = $recurring_payment_id;
}

//check the receiver_email
if ( ! pmpro_ipnCheckReceiverEmail( array( strtolower( $receiver_email ), strtolower( $business_email ) ) ) ) {
	//not our request
	pmpro_ipnExit();
}

/*
	PayPal Standard
	- we will get txn_type subscr_signup and subscr_payment (or subscr_eot or subscr_failed or subscr_cancel)
	- subscr_signup (if amount1 = 0, then we need to update membership, else ignore and wait for payment. create order for $0 with just subscr_id)
	- subscr_payment (check if we should update membership, add order for amount with subscr_id and payment_id)
	- subscr_eot (usually sent for every subscription that doesn't have recurring activated, at the end)
	- subscr_failed (usually sent if a recurring payment fails)
	- subscr_cancel (sent on recurring payment profile cancellation)
	- web_accept for 1-time payment only

	PayPal Express
	- we will get txn_type express_checkout, or recurring_payment_profile_created, or recurring_payment (or recurring_payment_expired, or recurring_payment_skipped)

*/

//PayPal Standard Sign Up
if ( $txn_type == "subscr_signup" ) {
	//if there is no amount1, this membership has a trial, and we need to update membership/etc
	$amount = pmpro_getParam( "amount1", "POST" );

	if ( (float) $amount <= 0 ) {
		//trial, get the order
		$morder = new MemberOrder( $item_number );

		//No order?
		if ( empty( $morder ) || empty( $morder->id ) ) {
			ipnlog( "ERROR: No order found item_number/code = " . $item_number . "." );
		} else {
			//get some more order info
			$morder->getMembershipLevel();
			$morder->getUser();

			//no txn_id on these, so let's use the subscr_id
			if ( empty( $txn_id ) ) {
				$txn_id = $subscr_id;
			}

			//Check that the corresponding order has a $0 initial payment as well
			if ( (float) $amount != (float) $morder->total ) {
				ipnlog( "ERROR: PayPal subscription #" . $subscr_id . " initial payment amount (" . $amount . ") is not the same as the PMPro order #" . $morder->code . " (" . $morder->total . ")." );
			} else {
				//update membership
				if ( pmpro_ipnChangeMembershipLevel( $txn_id, $morder ) ) {
					ipnlog( "Checkout processed (" . $morder->code . ") success!" );
				} else {
					ipnlog( "ERROR: Couldn't change level for order (" . $morder->code . ")." );
				}
			}
		}
	} else {
		//we're ignoring this. we will get a payment notice from IPN and process that
		ipnlog( "Going to wait for the first payment to go through." );
	}

	pmpro_ipnExit();
}

//PayPal Standard Subscription Payment
if ( $txn_type == "subscr_payment" ) {
	//is this a first payment?
	$last_subscription_order = new MemberOrder();
	if ( $last_subscription_order->getLastMemberOrderBySubscriptionTransactionID( $subscr_id ) == false ) {
		//first payment, get order
		$morder = new MemberOrder( sanitize_text_field( $_POST['item_number'] ) );

		//No order?
		if ( empty( $morder ) || empty( $morder->id ) ) {
			ipnlog( "ERROR: No order found item_number/code = " . $item_number . "." );
		} else {
			//get some more order info
			$morder->getMembershipLevel();
			$morder->getUser();

			//Check that the corresponding order has the same amount as what we're getting from PayPal
			$amount = sanitize_text_field( $_POST['mc_gross'] );
			
			//Adjust gross for tax if provided
			if( !empty($_POST['tax']) ) {
				$amount = (float)$amount - (float)$_POST['tax'];
			} else {
				$morder->tax = 0;
			}
			
			if ( (float) $amount != (float) $morder->total ) {
				ipnlog( "ERROR: PayPal transaction #" . $txn_id . " amount (" . $amount . ") is not the same as the PMPro order #" . $morder->code . " (" . $morder->total . ")." );
			} else {
				//update membership
				if ( pmpro_ipnChangeMembershipLevel( $txn_id, $morder ) ) {
					ipnlog( "Checkout processed (" . $morder->code . ") success!" );
				} else {
					ipnlog( "ERROR: Couldn't change level for order (" . $morder->code . ")." );
				}
			}
		}

		pmpro_ipnExit();
	} else {
		/**
		 * Payment statuses that should be treated as failures.
		 *
		 * @param array List of statuses to be treated as failures.
		 */
		$failed_payment_statuses = apply_filters( 'pmpro_paypal_renewal_failed_statuses', array( 'Failed', 'Voided', 'Denied', 'Expired' ) );
		$failed_payment_statuses = array_map( 'strtolower', $failed_payment_statuses );

		//subscription payment, completed or failure?
		if ( $payment_status == "completed" ) {
			pmpro_ipnSaveOrder( $txn_id, $last_subscription_order );
		} elseif ( in_array( $payment_status, $failed_payment_statuses ) ) {
			pmpro_ipnFailedPayment( $last_subscription_order );
		} else {
			ipnlog( 'Payment status is ' . $payment_status . '.' );
		}

		pmpro_ipnExit();
	}
}

//PayPal Standard Single Payment
if ( $txn_type == "web_accept" && ! empty( $item_number ) ) {
	//initial payment, get the order
	$morder = new MemberOrder( $item_number );

	//No order?
	if ( empty( $morder ) || empty( $morder->id ) ) {
		ipnlog( "ERROR: No order found item_number/code = " . $item_number . "." );
	} else {
		//get some more order info
		$morder->getMembershipLevel();
		$morder->getUser();

		//Check that the corresponding order has the same amount
		$amount = sanitize_text_field( $_POST['mc_gross'] );
		
		//Adjust gross for tax if provided
		if(!empty($_POST['tax']) ) {
			$amount = (float)$amount - (float)$_POST['tax'];
		}

		if ( (float) $amount != (float) $morder->total ) {
			ipnlog( "ERROR: PayPal transaction #" . $txn_id . " amount (" . $amount . ") is not the same as the PMPro order #" . $morder->code . " (" . $morder->total . ")." );
		} else {
			//update membership
			if ( pmpro_ipnChangeMembershipLevel( $txn_id, $morder ) ) {
				ipnlog( "Checkout processed (" . $morder->code . ") success!" );
			} else {
				ipnlog( "ERROR: Couldn't change level for order (" . $morder->code . ")." );
			}
		}
	}

	pmpro_ipnExit();
}

//PayPal Express Recurring Payments
if ( $txn_type == "recurring_payment" ) {
	$subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $subscr_id, 'paypalexpress', ( get_option( 'pmpro_gateway_environment' ) == 'sandbox' ) ? 'sandbox' : 'live' );
	if ( ! empty( $subscription ) ) {
		/**
		 * Payment statuses that should be treated as failures.
		 * 
		 * @param array List of statuses to be treated as failures.		 
		 */
		$failed_payment_statuses = apply_filters( 'pmpro_paypal_renewal_failed_statuses', array( 'Failed', 'Voided', 'Denied', 'Expired' ) );
		$failed_payment_statuses = array_map( 'strtolower', $failed_payment_statuses );

		//subscription payment, completed or failure?
		if ( $payment_status == "completed" ) {
			pmpro_ipnSaveOrder( $txn_id, $subscription );
		} elseif ( in_array( $payment_status, $failed_payment_statuses ) ) {
			// Check if the subscription has been suspended/paused in PPE.
			if ( $profile_status == "suspended") {
				// Subscription was suspended. This should trigger another IPN message which should treat this as a cancellation (recurring_payment_suspended_due_to_max_failed_payment).
				ipnlog( 'Subscription is suspended. Waiting for another IPN message to handle this.' );
			} else {
				// Subscription is not suspended. Send failed payment email.
				pmpro_ipnFailedPayment( $subscription );
			}
		} else {
			ipnlog( 'Payment status is ' . $payment_status . '.' );
		}
	} else {
		ipnlog( "ERROR: Couldn't find a subscription for this recurring payment (" . $subscr_id . ")." );
	}
		
	pmpro_ipnExit();
}

/**
 * IPN Txn Types that should be treated as failures.
 *
 * @param array List of txn types to be treated as failures.
 */
$failed_payment_txn_types = apply_filters( 'pmpro_paypal_renewal_failed_txn_types', array(
	'recurring_payment_suspended',
	'recurring_payment_skipped',
	'subscr_failed'
) );

if ( in_array( $txn_type, $failed_payment_txn_types ) ) {
	$last_subscription_order = new MemberOrder();
	if ( $last_subscription_order->getLastMemberOrderBySubscriptionTransactionID( $subscr_id ) ) {
		// the payment failed
		pmpro_ipnFailedPayment( $last_subscription_order );
	} else {
		ipnlog( "ERROR: Couldn't find last order for this recurring payment (" . $subscr_id . ")." );
	}

	pmpro_ipnExit();
}

// Recurring Payment Profile Cancelled (PayPal Express)
if ( $txn_type == 'recurring_payment_profile_cancel' || $txn_type == 'recurring_payment_failed' || $txn_type == 'recurring_payment_suspended_due_to_max_failed_payment' ) {
	// If the subscription was cancelled due to failed payments, make sure that we don't "cancel on next payment date".
	if ( $txn_type == 'recurring_payment_failed' || $txn_type == 'recurring_payment_suspended_due_to_max_failed_payment' ) {
		add_filter( 'pmpro_cancel_on_next_payment_date', '__return_false' );
	}

	// Handle the cancellation.
	ipnlog( pmpro_handle_subscription_cancellation_at_gateway( $recurring_payment_id, 'paypalexpress', $gateway_environment ) );

	// If the subscription was suspended due to max failed payments or paused due to failed payments, make sure that the subscription is definitely set to cancelled.
	if ( $txn_type == 'recurring_payment_failed' || $txn_type == 'recurring_payment_suspended_due_to_max_failed_payment' ) {
		$pmpro_subscription = PMPro_Subscription::get_subscription_from_subscription_transaction_id( $recurring_payment_id, 'paypalexpress', $gateway_environment );
		if ( ! empty( $pmpro_subscription ) ) { 
			$pmpro_subscription->cancel_at_gateway();
		}
	}

	pmpro_ipnExit();
}

// Recurring Payment Profile Created (PayPal Express)
if ( $txn_type == 'recurring_payment_profile_created' ) {
	$last_subscription_order = new MemberOrder();
	if ( $last_subscription_order->getLastMemberOrderBySubscriptionTransactionID( $subscr_id ) ) {
		$wpdb->update( $wpdb->pmpro_membership_orders, array( 'payment_transaction_id' => $initial_payment_txn_id ), array(
			'id' => $last_subscription_order->id
		), array( '%s' ), array( '%d' ) );

		ipnlog( 'Confirmation of profile creation for this recurring payment (' . $subscr_id . ').' );
	} else {
		ipnlog( 'ERROR: Could not find last order for this recurring payment (' . $subscr_id . ').' );
	}

	pmpro_ipnExit();
}

// Order completed (PayPal Express)
if ( $txn_type === 'express_checkout' ) {
	ipnlog( 'Confirmation of order completion for this payment: ' . print_r( $_POST, true ) );

	pmpro_ipnExit();
}

//Subscription Cancelled (PayPal Standard)
if ( $txn_type == "subscr_cancel" ) {
	// Find subscription.
	ipnlog( pmpro_handle_subscription_cancellation_at_gateway( $subscr_id, 'paypalstandard', $gateway_environment ) );
	pmpro_ipnExit();
}

if ( strtolower( $payment_status ) === 'refunded' ) {
	$payment_transaction_id = $parent_txn_id;

	if ( $payment_transaction_id ) {
		$morder = new MemberOrder();
		$morder->getMemberOrderByPaymentTransactionID( $payment_transaction_id );
		
		// Make sure we found a matching order.
		if ( empty( $morder ) || empty( $morder->id ) ) {
			ipnlog( sprintf( 'IPN: Order refunded on %1$s for transaction ID %2$s at the gateway, but we could not find a matching order.', date_i18n('Y-m-d H:i:s'), $payment_transaction_id ) );			
			pmpro_ipnExit();
		}
			
		// Ignore orders already in refund status.
		if( $morder->status == 'refunded' ) {				
			$logstr .= sprintf( 'IPN: Order ID %1$s with transaction ID %2$s was already in refund status.', $morder->id, $payment_transaction_id );
			pmpro_ipnExit();
		}
		
		// Handle partial refunds. Only updating the log and notes for now.
		if ( abs( (float)$_POST['mc_gross'] ) < (float)$morder->total ) {				
			ipnlog( sprintf( 'IPN: Order was partially refunded on %1$s for transaction ID %2$s at the gateway. The order will need to be updated in the WP dashboard.', date_i18n('Y-m-d H:i:s'), $payment_transaction_id ) );

			$morder->add_order_note( sprintf( 'IPN: Order was partially refunded for transaction ID %1$s at the gateway. The order will need to be updated in the WP dashboard.', $payment_transaction_id ) );
			$morder->SaveOrder();
			pmpro_ipnExit();
		}

		// Full refund.
		$morder->status = 'refunded';

		$morder->add_order_note( sprintf( 'IPN: Order successfully refunded for transaction ID %1$s at the gateway.', $payment_transaction_id ) );

		ipnlog( sprintf( 'IPN: Order successfully refunded on %1$s for transaction ID %2$s at the gateway.', date_i18n('Y-m-d H:i:s'), $payment_transaction_id ) );

		$user = get_userdata( $morder->user_id );

		// Send an email to the member.
		$myemail = new PMProEmail();
		$myemail->sendRefundedEmail( $user, $morder );

		// Send an email to the admin.
		$myemail = new PMProEmail();
		$myemail->sendRefundedAdminEmail( $user, $morder );

		$morder->SaveOrder();
		pmpro_ipnExit();		
	}
}

//Other
//if we got here, this is a different kind of txn
ipnlog( "No recurring payment id or item number. txn_type = " . $txn_type );

pmpro_unhandled_webhook();
pmpro_ipnExit();

/*
	Add message to ipnlog string
*/
function ipnlog( $s ) {
	global $logstr;
	$logstr .= "\t" . $s . "\n";
}

/*
	Output ipnlog and exit;
*/
function pmpro_ipnExit() {
	global $logstr;

	//for log
	if ( $logstr ) {
		$logstr = "Logged On: " . date_i18n( "m/d/Y H:i:s" ) . "\n" . $logstr . "\n-------------\n";

		echo esc_html( $logstr );

		//log or dont log? log in file or email?
		//- dont log if constant is undefined or defined but false
		//- log to file if constant is set to TRUE or 'log'
		//- log to file if constant is defined to a valid email address
		if ( defined( 'PMPRO_IPN_DEBUG' ) ) {
			if( PMPRO_IPN_DEBUG === false ){
				//dont log here. false mean no.
				//should avoid counterintuitive interpretation of false.
			} elseif ( PMPRO_IPN_DEBUG === "log" ) {
				//file
				$logfile = apply_filters( 'pmpro_ipn_logfile', pmpro_get_restricted_file_path( 'logs', 'ipn.txt' ) );
				$loghandle = fopen( $logfile, "a+" );
				fwrite( $loghandle, $logstr );
				fclose( $loghandle );
			} elseif ( is_email( PMPRO_IPN_DEBUG ) ) {
				//email to specified address
				wp_mail( PMPRO_IPN_DEBUG, get_option( "blogname" ) . " IPN Log", nl2br( esc_html( $logstr ) ) );							
			} else {
				//email to admin
				wp_mail( get_option( "admin_email" ), get_option( "blogname" ) . " IPN Log", nl2br( esc_html( $logstr ) ) );							
			}
		}
	}

	exit;
}

/*
	Validate the $_POST with PayPal
*/
function pmpro_ipnValidate() {
	//read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';

	//generate string to check with PayPal
	foreach ( $_POST as $key => $value ) {
		$value = urlencode( stripslashes( $value ) );
		$req .= "&$key=$value";
	}

	//post back to PayPal system to validate
	$gateway_environment = get_option( "pmpro_gateway_environment" );
	if ( $gateway_environment == "sandbox" ) {
		$paypal_url = 'https://www.' . $gateway_environment . '.paypal.com/cgi-bin/webscr';
	} else {
		$paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
	}

	$paypal_params = array(
		"body"        => $req,
		"httpversion" => "1.1",
		"Host"        => "www.paypal.com",
		"Connection"  => "Close",
		"user-agent"  => PMPRO_USER_AGENT
	);

	$fp = wp_remote_post( $paypal_url, $paypal_params );

	//log post vars
	ipnlog( print_r( $_POST, true ) );

	//assume invalid
	$r = false;

	if ( empty( $fp ) ) {
		//HTTP ERROR
		ipnlog( "HTTP ERROR" );

		$r = false;
	} elseif ( ! empty( $fp->errors ) ) {
		//error from PayPal
		ipnlog( "ERROR" );
		ipnlog( "Error Info: " . print_r( $fp->errors, true ) . "\n" );

		//log fb object
		ipnlog( print_r( $fp, true ) );

		$r = false;
	} else {
		ipnlog( "FP!" );

		//log fp object
		///ipnlog( print_r( $fp, true ) );

		$res = wp_remote_retrieve_body( $fp );
		//ipnlog( print_r( $res, true ) );

		if ( strcmp( $res, "VERIFIED" ) == 0 ) {
			//all good so far
			ipnlog( "VERIFIED" );
			$r = true;
		} else {
			//log for manual investigation
			ipnlog( "INVALID" );
			$r = false;
		}
	}

	/**
	 * Filter if an ipn request is valid or not.
	 *
	 * @since 1.8.6.3
	 *
	 * @param bool $r true or false if the request is valid
	 * @param mixed $fp remote post object from request to PayPal
	 */
	$r = apply_filters( 'pmpro_ipn_validate', $r, $fp );

	return $r;
}

/*
	Check that the email sent by PayPal matches our settings.
*/
function pmpro_ipnCheckReceiverEmail( $email ) {
	if ( ! is_array( $email ) ) {
		$email = array( $email );
	}

	if ( ! in_array( strtolower( get_option( 'pmpro_gateway_email' ) ), $email ) ) {
		$r = false;
	} else {
		$r = true;
	}

	$r = apply_filters( 'pmpro_ipn_check_receiver_email', $r, $email );

	if ( $r ) {
		return true;
	} else {
		if ( ! empty( $_POST['receiver_email'] ) ) {
			$receiver_email = sanitize_text_field( $_POST['receiver_email'] );
		} else {
			$receiver_email = "N/A";
		}

		if ( ! empty( $_POST['business'] ) ) {
			$business = sanitize_text_field( $_POST['business'] );
		} else {
			$business = "N/A";
		}

		//not yours
		ipnlog( "ERROR: receiver_email (" . $receiver_email . ") and business email (" . $business . ") did not match (" . get_option( 'pmpro_gateway_email' ) . ")" );

		return false;
	}

}

/*
	Change the membership level. We also update the membership order to include filtered values.
*/
function pmpro_ipnChangeMembershipLevel( $txn_id, &$morder ) {

	global $wpdb;

	//filter for level
	$morder->membership_level = apply_filters( "pmpro_ipnhandler_level", $morder->membership_level, $morder->user_id );

	//set the start date to current_time('timestamp') but allow filters  (documented in preheaders/checkout.php)
	$startdate = apply_filters( "pmpro_checkout_start_date", "'" . current_time( 'mysql' ) . "'", $morder->user_id, $morder->membership_level );

	//fix expiration date
	if ( ! empty( $morder->membership_level->expiration_number ) ) {
		$enddate = "'" . date_i18n( "Y-m-d", strtotime( "+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period, current_time( "timestamp" ) ) ) . "'";
	} else {
		$enddate = "NULL";
	}

	//filter the enddate (documented in preheaders/checkout.php)
	$enddate = apply_filters( "pmpro_checkout_end_date", $enddate, $morder->user_id, $morder->membership_level, $startdate );

	//get discount code
	$morder->getDiscountCode();
	if ( ! empty( $morder->discount_code ) ) {
		//update membership level
		$morder->getMembershipLevel( true );
		$discount_code_id = $morder->discount_code->id;
	} else {
		$discount_code_id = "";
	}


	//custom level to change user to
	$custom_level = array(
		'user_id'         => $morder->user_id,
		'membership_id'   => $morder->membership_level->id,
		'code_id'         => $discount_code_id,
		'initial_payment' => $morder->membership_level->initial_payment,
		'billing_amount'  => $morder->membership_level->billing_amount,
		'cycle_number'    => $morder->membership_level->cycle_number,
		'cycle_period'    => $morder->membership_level->cycle_period,
		'billing_limit'   => $morder->membership_level->billing_limit,
		'trial_amount'    => $morder->membership_level->trial_amount,
		'trial_limit'     => $morder->membership_level->trial_limit,
		'startdate'       => $startdate,
		'enddate'         => $enddate
	);

	global $pmpro_error;
	if ( ! empty( $pmpro_error ) ) {
		echo esc_html( $pmpro_error );
		ipnlog( $pmpro_error );
	}

	//change level and continue "checkout"
	if ( pmpro_changeMembershipLevel( $custom_level, $morder->user_id, 'changed' ) !== false ) {
		//update order status and transaction ids
		$morder->status                 = "success";
		$morder->payment_transaction_id = $txn_id;
		if ( ! empty( $_POST['subscr_id'] ) ) {
			$morder->subscription_transaction_id = sanitize_text_field( $_POST['subscr_id'] );
		} else {
			$morder->subscription_transaction_id = "";
		}
		$morder->saveOrder();

		//add discount code use
		if ( ! empty( $discount_code ) && ! empty( $use_discount_code ) ) {

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->pmpro_discount_codes_uses} 
						( code_id, user_id, order_id, timestamp ) 
						VALUES( %d, %d, %s, %s )",
					$discount_code_id),
					$morder->user_id,
					$morder->id,
					current_time( 'mysql' )
				);
		}

		//save first and last name fields
		if ( ! empty( $_POST['first_name'] ) ) {
			$old_firstname = get_user_meta( $morder->user_id, "first_name", true );
			if ( empty( $old_firstname ) ) {
				update_user_meta( $morder->user_id, "first_name", sanitize_text_field( $_POST['first_name'] ) );
			}
		}
		if ( ! empty( $_POST['last_name'] ) ) {
			$old_lastname = get_user_meta( $morder->user_id, "last_name", true );
			if ( empty( $old_lastname ) ) {
				update_user_meta( $morder->user_id, "last_name", sanitize_text_field( $_POST['last_name'] ) );
			}
		}

		//hook
		do_action( "pmpro_after_checkout", $morder->user_id, $morder );

		//setup some values for the emails
		if ( ! empty( $morder ) ) {
			$order = new MemberOrder( $morder->id );
		} else {
			$order = null;
		}

		$user = get_userdata( $morder->user_id );

		//send email to member
		$pmproemail = new PMProEmail();
		$pmproemail->sendCheckoutEmail( $user, $order );

		//send email to admin
		$pmproemail = new PMProEmail();
		$pmproemail->sendCheckoutAdminEmail( $user, $order );

		return true;
	} else {
		return false;
	}
}

/*
	Send an email RE a failed payment.
	$subscription The subscription that had the failed payment or the last order for the subscription (legacy).
*/
function pmpro_ipnFailedPayment( $subscription ) {
	// If a "last order" was passed, get the subscription object.
	if ( is_a( $subscription, 'MemberOrder' ) ) {
		$subscription = $subscription->get_subscription();
	}

	// Make sure we have a subscription.
	if ( empty( $subscription ) ) {
		ipnlog( "ERROR: Couldn't find subscription for failed payment." );
		return false;
	}

	// Process the failed payment.
	ipnlog( pmpro_handle_recurring_payment_failure_at_gateway( pmpro_ipn_get_order_data( $subscription ) ) );
	return true;
}

/*
	Save a new order from IPN info.
	$subscription The subscription to save an order for or the last order for the subscription (legacy).
*/
function pmpro_ipnSaveOrder( $txn_id, $subscription ) {
	// If a "last order" was passed, get the subscription object.
	if ( is_a( $subscription, 'MemberOrder' ) ) {
		$subscription = $subscription->get_subscription();
	}

	// Make sure we have a subscription.
	if ( empty( $subscription ) ) {
		ipnlog( "ERROR: Couldn't find subscription for failed payment." );
		return false;
	}

	// Save the event ID for the last processed user/IPN (in case we want to be able to replay IPN requests)
	$ipn_id = isset($_POST['ipn_track_id']) ? sanitize_text_field( $_POST['ipn_track_id'] ) : null;

	// Get the data that should be used to create the order.
	$order_data = pmpro_ipn_get_order_data( $subscription );
	$order_data['payment_transaction_id'] = $txn_id;

	// Process the recurring payment.
	ipnlog( pmpro_handle_recurring_payment_succeeded_at_gateway( $order_data ) );

	// Get the MemberOrder object for the order we just created.
	$morder = new MemberOrder();
	$morder->getMemberOrderByPaymentTransactionID( $txn_id );
	if ( empty( $morder ) || empty( $morder->id ) ) {
		ipnlog( "ERROR: Could not find order just created for this recurring payment (" . $subscription->get_subscription_transaction_id() . ")." );
		return false;
	}

	// Add an order note with the IPN ID.
	if ( ! empty( $ipn_id ) ) {
		$morder->add_order_note( "[IPN_ID]{$ipn_id}[/IPN_ID]" );
		$morder->SaveOrder();
	}

	// Get card info if appropriate.
	if ( $morder->gateway == "paypal" ) {   //website payments pro
		//Updates this order with the most recent orders payment method information and saves it. 
		pmpro_update_order_with_recent_payment_method( $morder );
	}

	/**
	 * Post processing for a specific subscription related IPN event ID
	 *
	 * @param       string      $ipn_id     - The ipn_track_id from the PayPal IPN request
	 * @param       MemberOrder $morder     - The completed Member Order object for the IPN request
	 */
	do_action('pmpro_subscription_ipn_event_processed', $ipn_id, $morder );

	if ( ! is_null( $ipn_id ) ) {
		if ( false === update_user_meta( $morder->user_id, "pmpro_last_{$morder->gateway}_ipn_id", $ipn_id )) {
			ipnlog( "Unable to save the IPN event ID ({$ipn_id}) to usermeta for {$morder->user_id} " );
		}
	}

	return true;
}

/**
 * Helper function to build order data array from subscription and $_POST data.
 *
 * @since 3.6
 *
 * @param PMPro_Subscription $subscription The subscription to get recurring order data for.
 */
function pmpro_ipn_get_order_data( $subscription ) {
	$order_data = array(
		'gateway'        => $subscription->get_gateway(),
		'gateway_environment' => $subscription->get_gateway_environment(),
		'subscription_transaction_id' => $subscription->get_subscription_transaction_id(),
		'timestamp' => ! empty( $_POST['payment_date'] ) ? strtotime( sanitize_text_field( $_POST['payment_date'] ) ) : current_time( 'timestamp' ),
	);

	//set amount based on which PayPal type
	if ( false !== stripos( $order_data['gateway'], "paypal" ) ) {

		if ( isset( $_POST['mc_gross'] ) && ! empty( $_POST['mc_gross'] ) ) {
			$order_data['total']  = sanitize_text_field( $_POST['mc_gross'] );
		} elseif ( isset( $_POST['amount'] ) && ! empty( $_POST['amount'] ) ) {
			$order_data['total']  = sanitize_text_field( $_POST['amount'] );
		} elseif ( isset( $_POST['payment_gross'] )  && ! empty( $_POST['payment_gross' ] ) ) {
			$order_data['total']  = sanitize_text_field( $_POST['payment_gross'] );
		}
		
		//check for tax
		if ( isset( $_POST['tax'] ) && ! empty( $_POST['tax'] ) ) {
			$order_data['tax'] = (float) $_POST['tax'];
			if ( isset( $_POST['amount'] ) && ! empty( $_POST['amount'] ) && $order_data['total'] > (float) $_POST['amount'] ) {
				$order_data['tax'] *= (float) $order_data['total'] / (float) $_POST['amount'];
			}
			$order_data['subtotal'] = $order_data['total'] - $order_data['tax'];
		} else {
			$order_data['tax'] = 0;
			$order_data['subtotal'] = $order_data['total'];
		}
	}

	// Get the most recent order so that we can copy the billing info.
	$sub_orders = $subscription->get_orders(
		array(
			'limit' => 1,
			'status' => 'success',
		)
	);
	if ( ! empty( $sub_orders ) ) {
		$last_order = $sub_orders[0];

		// Copy billing address info.
		if ( ! empty( $last_order->billing ) ) {
			$order_data['billing'] = new stdClass();
			$order_data['billing']->name    = $last_order->billing->name;
			$order_data['billing']->street  = $last_order->billing->street;
			$order_data['billing']->street2 = $last_order->billing->street2;
			$order_data['billing']->city    = $last_order->billing->city;
			$order_data['billing']->state   = $last_order->billing->state;
			$order_data['billing']->zip     = $last_order->billing->zip;
			$order_data['billing']->country = $last_order->billing->country;
			$order_data['billing']->phone   = $last_order->billing->phone;
		}

		// Copy payment method info.
		$order_data['payment_type'] = empty( $last_order->payment_type ) ? '' : $last_order->payment_type;
		$order_data['cardtype'] = empty( $last_order->cardtype ) ? '' : $last_order->cardtype;
		$order_data['accountnumber'] = empty( $last_order->accountnumber ) ? '' : $last_order->accountnumber;
		$order_data['expirationmonth'] = empty( $last_order->expirationmonth ) ? '' : $last_order->expirationmonth;
		$order_data['expirationyear' ] = empty( $last_order->expirationyear ) ? '' : $last_order->expirationyear;
	} elseif ( 'paypalexpress' === $order_data['gateway'] ) {
		$order_data['payment_type'] = 'PayPal Express';
	}

	return $order_data;
}