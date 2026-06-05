<?php
/*
	This file was added in version 1.5.5 of the plugin. This file is meant to store various hacks, filters, and actions that were originally developed outside of the PMPro core and brought in later... or just things that are cleaner/easier to implement via hooks and filters.
*/

/*
	If checking out for the same level, add remaining days to the enddate.
	Pulled in from: https://gist.github.com/3678054
*/
function pmpro_checkout_level_extend_memberships( $level ) {
	global $pmpro_msg, $pmpro_msgt;

	// does this level expire? are they an existing user of this level?
	if ( ! empty( $level ) && ! empty( $level->expiration_number ) && pmpro_hasMembershipLevel( $level->id ) ) {
		// get the current enddate of their membership
		global $current_user;
		$user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $level->id );

		// bail if their existing level doesn't have an end date
		if ( empty( $user_level ) || empty( $user_level->enddate ) ) {
			return $level;
		}

		// calculate days left
		$todays_date = strtotime( current_time( 'Y-m-d' ) );
		$expiration_date = strtotime( date( 'Y-m-d', $user_level->enddate ) );
		$time_left = $expiration_date - $todays_date;

		// time left?
		if ( $time_left > 0 ) {
			// Calculate when the new expiration date should be.
			$new_expiration_date = strtotime( '+' . $level->expiration_number . ' ' . $level->expiration_period, $expiration_date);

			// Set the level to expire in that many days.
			$days_until_new_expiration = floor( ( $new_expiration_date - $todays_date ) / ( 60 * 60 * 24 ) );
			$level->expiration_number = $days_until_new_expiration;
			$level->expiration_period = 'Day';
		}
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'pmpro_checkout_level_extend_memberships' );
/*
	Same thing as above but when processed by the ipnhandler for PayPal standard.
*/
function pmpro_ipnhandler_level_extend_memberships( $level, $user_id ) {
	global $pmpro_msg, $pmpro_msgt;

	// does this level expire? are they an existing user of this level?
	if ( ! empty( $level ) && ! empty( $level->expiration_number ) && pmpro_hasMembershipLevel( $level->id, $user_id ) ) {
		// get the current enddate of their membership
		$user_level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level->id );

		// bail if their existing level doesn't have an end date
		if ( empty( $user_level ) || empty( $user_level->enddate ) ) {
			return $level;
		}

		// calculate days left
		$todays_date = current_time( 'timestamp' );
		$expiration_date = $user_level->enddate;
		$time_left = $expiration_date - $todays_date;

		// time left?
		if ( $time_left > 0 ) {
			// convert to days and add to the expiration date (assumes expiration was 1 year)
			$days_left = floor( $time_left / ( 60 * 60 * 24 ) );

			// figure out days based on period
			if ( $level->expiration_period == 'Day' ) {
				$total_days = $days_left + $level->expiration_number;
			} elseif ( $level->expiration_period == 'Week' ) {
				$total_days = $days_left + $level->expiration_number * 7;
			} elseif ( $level->expiration_period == 'Month' ) {
				$total_days = $days_left + $level->expiration_number * 30;
			} elseif ( $level->expiration_period == 'Year' ) {
				$total_days = $days_left + $level->expiration_number * 365;
			}

			// update number and period
			$level->expiration_number = $total_days;
			$level->expiration_period = 'Day';
		}
	}

	return $level;
}
add_filter( 'pmpro_ipnhandler_level', 'pmpro_ipnhandler_level_extend_memberships', 10, 2 );

/*
	If checking out for the same level, keep your old startdate.
	Added with 1.5.5
*/
function pmpro_checkout_start_date_keep_startdate( $startdate, $user_id, $level ) {
	global $wpdb;
	if ( ! empty( $level ) && pmpro_hasMembershipLevel( $level->id, $user_id ) ) {
		$sqlQuery = "SELECT startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . esc_sql( $user_id ) . "' AND membership_id = '" . esc_sql( $level->id ) . "' AND status = 'active' ORDER BY id DESC LIMIT 1";
		$old_startdate = $wpdb->get_var( $sqlQuery );

		if ( ! empty( $old_startdate ) ) {
			$startdate = "'" . $old_startdate . "'";
		}
	}

	return $startdate;
}
add_filter( 'pmpro_checkout_start_date', 'pmpro_checkout_start_date_keep_startdate', 10, 3 );

/*
	Stripe Lite Pulled into Core Plugin
*/
// Stripe Lite, Set the Globals/etc
$stripe_billingaddress = get_option( 'pmpro_stripe_billingaddress' );
if ( empty( $stripe_billingaddress ) ) {
	global $pmpro_stripe_lite;
	$pmpro_stripe_lite = true;
	add_filter( 'pmpro_stripe_lite', '__return_true' );
	add_filter( 'pmpro_required_billing_fields', 'pmpro_required_billing_fields_stripe_lite' );
}

// Stripe Lite, Don't Require Billing Fields
function pmpro_required_billing_fields_stripe_lite( $fields ) {
	global $gateway;

	// ignore if not using stripe
	if ( $gateway != 'stripe' ) {
		return $fields;
	}

	// some fields to remove
	$remove = array( 'bfirstname', 'blastname', 'baddress1', 'bcity', 'bstate', 'bzipcode', 'bphone', 'bcountry' );

	// if a user is logged in, don't require bemail either
	global $current_user;
	if ( ! empty( $current_user->user_email ) ) {
		$remove[] = 'bemail';
	}

	// remove the fields
	foreach ( $remove as $field ) {
		unset( $fields[ $field ] );
	}

	// ship it!
	return $fields;
}

// copy other discount code to discount code if latter is not set
if ( empty( $_REQUEST['pmpro_discount_code'] ) && ! empty( $_REQUEST['pmpro_other_discount_code'] ) ) {
	$_REQUEST['pmpro_discount_code'] = sanitize_text_field( $_REQUEST['pmpro_other_discount_code'] );
}
if ( empty( $_POST['pmpro_discount_code'] ) && ! empty( $_POST['pmpro_other_discount_code'] ) ) {
	$_POST['pmpro_discount_code'] = sanitize_text_field( $_POST['pmpro_other_discount_code'] );	
}
if ( empty( $_GET['pmpro_discount_code'] ) && ! empty( $_GET['pmpro_other_discount_code'] ) ) {
	$_GET['pmpro_discount_code'] = sanitize_text_field( $_GET['pmpro_other_discount_code'] );	
}

// apply all the_content filters to confirmation messages for levels
function pmpro_pmpro_confirmation_message( $message ) {
	return wpautop( $message );
}
add_filter( 'pmpro_confirmation_message', 'pmpro_pmpro_confirmation_message' );

// apply all the_content filters to level descriptions
function pmpro_pmpro_level_description( $description ) {
	return wpautop( $description );
}
add_filter( 'pmpro_level_description', 'pmpro_pmpro_level_description' );

/*
	PayPal doesn't allow start dates > 1 year out.
	So if we detect that, let's try to squeeze some of
	that time into a trial.

	Otherwise, let's cap at 1 year out.

	Note that this affects PayPal Standard as well, but the fix
	for that flavor of PayPal is different and may be included in future
	updates.

	This function is being deprecated as ProfileStartDate is no longer stored as an order property.
	This is now coded directly into the PayPal Express subscribe() function.
	@deprecated 3.2
*/
function pmpro_pmpro_subscribe_order_startdate_limit( $order, $gateway ) {
	_deprecated_function( __FUNCTION__, '3.2' );
	return $order;
}

/**
 * Before changing membership at checkout,
 * let's remember the order for checkout
 * so we can ignore that when cancelling old orders.
 */
function pmpro_set_checkout_order_before_changing_membership_levels( $user_id, $order ) {
	global $pmpro_checkout_order;
	$pmpro_checkout_order = $order;
}
add_action( 'pmpro_checkout_before_change_membership_level', 'pmpro_set_checkout_order_before_changing_membership_levels', 10, 2);

/**
 * Ignore the checkout order when cancelling old orders.
 */
function pmpro_ignore_checkout_order_when_cancelling_old_orders( $order_ids ) {
	global $pmpro_checkout_order;

	if ( ! empty( $pmpro_checkout_order ) && ! empty( $pmpro_checkout_order->id ) ) {
		$order_ids = array_diff( $order_ids, array( $pmpro_checkout_order->id ) );
	}

	return $order_ids;
}
add_filter( 'pmpro_other_order_ids_to_cancel', 'pmpro_ignore_checkout_order_when_cancelling_old_orders' );

/**
 * Default the get_option call for pmpro_spam_protection option to '2'.
 *
 * @since 2.11
 *
 * @param string $default The default value for the option.
 * @return string The default value for the option.
 */
function pmpro_default_option_pmpro_spamprotection( $default ) {
	return '2';
}
add_filter( 'default_option_pmpro_spamprotection', 'pmpro_default_option_pmpro_spamprotection' );