<?php
/*
	Upgrade to 2.4.
    
    We need to fix empty $0 orders created by "Stripe Updates",
    which are missing the subscription transaction id.

	1. Find all orders that match:
    - stripe gateway
    - $0 amount
    - empty payment transaction id
    - empty subscription transaction id
    - success status
	2. Loop through and check the Stripe API for a subscription for that user which has a plan with id equal to the order code.
    3. Set the subscription transaction id
*/
function pmpro_upgrade_2_4() {
    global $wpdb;
    $sqlQuery = "SELECT * 
                 FROM $wpdb->pmpro_membership_orders
                 WHERE gateway = 'stripe'
                    AND total = 0
                    AND payment_transaction_id = ''
                    AND subscription_transaction_id = ''
                    AND status = 'success'
				ORDER BY id";
	$orders = $wpdb->get_results( $sqlQuery );
	
	if(!empty($orders)) {
		if(count($orders) > 10) {
			//if more than 10 orders, we'll need to do this via AJAX
			pmpro_addUpdate( 'pmpro_upgrade_2_4_ajax' );
		} else {
			//less than 10, let's just do them now
			pmpro_upgrade_2_4_helper_get_subscriptions_for_orders( $orders, false );
		}
	}

	update_option( 'pmpro_db_version', '2.4' );
	return 2.4;
}

/*
	If a site has > 100 orders then we run this pasrt of the update via AJAX from the updates page.
*/
function pmpro_upgrade_2_4_ajax() {
	global $wpdb;

	//keeping track of which order we're working on
	$last_order_id = get_option( 'pmpro_upgrade_2_4_last_order_id', 0 );
	
	//get orders
	$sqlQuery = "SELECT * 
                 FROM $wpdb->pmpro_membership_orders
                 WHERE id > '" . esc_sql( $last_order_id ) . "'
				 	AND gateway = 'stripe'
                    AND total = 0
                    AND payment_transaction_id = ''
                    AND subscription_transaction_id = ''
                    AND status = 'success'
				ORDER BY id";
	$orders = $wpdb->get_results( $sqlQuery );

	if(empty($orders)) {
		//done with this update
		pmpro_removeUpdate('pmpro_upgrade_2_4_ajax');
		delete_option( 'pmpro_upgrade_2_4_last_order_id' );
	} else {
		pmpro_upgrade_2_4_helper_get_subscriptions_for_orders( $orders, true );
	}
}

/**
 * Populate subscription_ids for Stripe orders.
 *
 * @param array $orders to find subscription_id for.
 * @param bool $update_last_order_id. Should be true if updating via ajax.
 */
function pmpro_upgrade_2_4_helper_get_subscriptions_for_orders( $orders, $update_last_order_id ) {
	global $wpdb;
	$stripe = new PMProGateway_stripe();
	require_once( ABSPATH . "/wp-includes/pluggable.php" );
	foreach($orders as $order) {
		if ( empty( $order->code ) ) {
			continue;
		}
		$customer = $stripe->get_customer_for_user( $order->user_id );

		//no customer or no subscriptions?
		if ( empty( $customer ) || empty( $customer->subscriptions ) ) {
			continue;
		}

		//find subscription based on customer id and order/plan id
		$subscriptions = $customer->subscriptions->all();
		

		//no subscriptions
		if ( empty( $subscriptions ) || empty( $subscriptions->data ) ) {
			return false;
		}

		//we really want to test against the order codes of all orders with the same subscription_transaction_id (customer id)
		$codes = $wpdb->get_col( "SELECT code FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . esc_sql( $order->user_id ) . "' AND subscription_transaction_id = '' AND status NOT IN('refunded', 'review', 'token', 'error')" );

		//find the one for this order
		foreach ( $subscriptions->data as $sub ) {
			if ( in_array( $sub->plan->id, $codes ) ) {
				$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET subscription_transaction_id = '" . esc_sql( $sub->id ) . "' WHERE id = '" . esc_sql( $order->id ) . "' LIMIT 1";
				$wpdb->query( $sqlQuery );
				break;
			}
		}
		$last_order_id = $order->id;
	}

	if ( $update_last_order_id ) {
		update_option( 'pmpro_upgrade_2_4_last_order_id', $last_order_id );
	}
}
