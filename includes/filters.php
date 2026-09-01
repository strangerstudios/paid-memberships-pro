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

/**
 * Apply a set expiration date to a level object as a duration in days.
 *
 * Only used for the IPN/webhook level filters below: those gateway handlers
 * compute renewal end dates from the level's expiration_number/expiration_period,
 * so the resolved date is converted to "days from now". Checkout itself computes
 * the exact end date natively in pmpro_complete_checkout().
 *
 * @since TBD
 *
 * @param object   $level            The PMPro Level object.
 * @param int|null $discount_code_id Optional discount code ID.
 * @return object|null The modified level object, or null if expired.
 */
function pmpro_apply_set_expiration_date_at_checkout( $level, $discount_code_id = null ) {
	if ( empty( $level ) || empty( $level->id ) ) {
		return $level;
	}

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id, $discount_code_id );
	if ( empty( $set_expiration_date ) ) {
		return $level;
	}

	// Check for Y pattern usage.
	$used_y = ( strpos( strtoupper( $set_expiration_date ), 'Y' ) !== false );

	// Convert the date pattern.
	$resolved_date = pmpro_payment_schedule_resolve_expiration_date( $set_expiration_date );
	if ( empty( $resolved_date ) ) {
		// Malformed stored pattern - ignore it rather than blocking checkout.
		return $level;
	}

	// Calculate days until expiration.
	$todays_date = current_time( 'timestamp' );
	$time_left   = strtotime( $resolved_date ) - $todays_date;

	if ( $time_left > 0 ) {
		$days_left = ceil( $time_left / ( 60 * 60 * 24 ) );
		$level->expiration_number = $days_left;
		$level->expiration_period = 'Day';
		return $level;
	} elseif ( $used_y ) {
		// Date has passed but uses Y pattern - add a year.
		$timestamp   = strtotime( $resolved_date );
		$resolved_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', $timestamp ), date( 'd', $timestamp ), date( 'Y', $timestamp ) + 1 ) );
		$time_left   = strtotime( $resolved_date ) - $todays_date;
		$days_left   = ceil( $time_left / ( 60 * 60 * 24 ) );

		$level->expiration_number = $days_left;
		$level->expiration_period = 'Day';
		return $level;
	} else {
		// Expiration already passed and no dynamic pattern - don't allow signup.
		return null;
	}
}

/**
 * Wrapper for IPN/webhook level handlers.
 *
 * @since TBD
 */
function pmpro_set_expiration_ipnhandler_level( $level, $user_id = null ) {
	if ( empty( $level ) || empty( $level->id ) ) {
		return $level;
	}

	// Respect a discount-code-specific expiration date if a code was used at checkout.
	$code_id  = ! empty( $level->code_id ) ? intval( $level->code_id ) : null;
	$adjusted = pmpro_apply_set_expiration_date_at_checkout( $level, $code_id );

	// A null return means a fixed expiration date has passed. The IPN handlers
	// can't survive a null level and the payment has already been taken, so keep
	// the level and let the end date land per its remaining settings.
	return empty( $adjusted ) ? $level : $adjusted;
}
add_filter( 'pmpro_ipnhandler_level', 'pmpro_set_expiration_ipnhandler_level', 10, 2 );
add_filter( 'pmpro_payfast_itnhandler_level', 'pmpro_set_expiration_ipnhandler_level', 10, 2 );
add_filter( 'pmpro_paystack_webhook_level', 'pmpro_set_expiration_ipnhandler_level', 10, 2 );

/**
 * Block checkout when a level's set expiration date has already passed.
 *
 * Only fixed-date patterns can resolve to the past (Y/M patterns always advance
 * to the next occurrence), so this stops signups for levels or discount codes
 * whose configured end date is behind us.
 *
 * @since TBD
 *
 * @param bool   $okay  Whether the checkout is okay so far.
 * @param object $level The level being checked out for.
 * @return bool Whether the checkout is still okay.
 */
function pmpro_payment_schedule_registration_check( $okay, $level = null ) {
	// Bail if the checkout already failed or we don't have a level.
	if ( ! $okay || empty( $level ) || empty( $level->id ) ) {
		return $okay;
	}

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id, ! empty( $level->code_id ) ? $level->code_id : null );
	if ( empty( $set_expiration_date ) ) {
		return $okay;
	}

	$resolved_date = pmpro_payment_schedule_resolve_expiration_date( $set_expiration_date );
	if ( ! empty( $resolved_date ) && strtotime( $resolved_date ) <= current_time( 'timestamp' ) ) {
		pmpro_setMessage(
			sprintf(
				/* translators: %s: the date that membership access for this level ends. */
				__( 'Membership access for this level ends on %s. New signups are no longer accepted.', 'paid-memberships-pro' ),
				date_i18n( get_option( 'date_format' ), strtotime( $resolved_date, current_time( 'timestamp' ) ) )
			),
			'pmpro_error'
		);
		return false;
	}

	return $okay;
}
add_filter( 'pmpro_registration_checks', 'pmpro_payment_schedule_registration_check', 10, 2 );

/**
 * Show an admin warning for levels or discount code overrides whose set
 * expiration date is in the past (which blocks new signups) or whose stored
 * pattern can no longer be parsed (which is ignored at checkout).
 *
 * @since TBD
 */
function pmpro_set_expiration_date_admin_notice() {
	global $wpdb;

	$past_items    = array();
	$invalid_items = array();

	// Level-wide patterns. pmpro_getAllLevels() returns only allow_signups levels.
	$levels = pmpro_getAllLevels();
	foreach ( $levels as $level ) {
		$set_expiration_date = pmpro_get_set_expiration_date( $level->id );
		if ( empty( $set_expiration_date ) ) {
			continue;
		}

		$link          = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-membershiplevels', 'edit' => $level->id ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $level->name ) . '</a>';
		$resolved_date = pmpro_payment_schedule_resolve_expiration_date( $set_expiration_date );
		if ( empty( $resolved_date ) ) {
			$invalid_items[] = $link;
		} elseif ( $resolved_date <= wp_date( 'Y-m-d' ) ) {
			$past_items[] = $link;
		}
	}

	// Discount code overrides, stored as pmprosed_{level_id}_{code_id}.
	$code_options = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'pmprosed\\_%\\_%' AND option_value <> ''" );
	foreach ( $code_options as $code_option ) {
		if ( ! preg_match( '/^pmprosed_(\d+)_(\d+)$/', $code_option->option_name, $matches ) ) {
			continue;
		}
		$link          = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-discountcodes', 'edit' => intval( $matches[2] ) ), admin_url( 'admin.php' ) ) ) . '">' . sprintf( /* translators: 1: a membership level ID, 2: a discount code ID. */ esc_html__( 'level %1$d via discount code %2$d', 'paid-memberships-pro' ), intval( $matches[1] ), intval( $matches[2] ) ) . '</a>';
		$resolved_date = pmpro_payment_schedule_resolve_expiration_date( $code_option->option_value );
		if ( empty( $resolved_date ) ) {
			$invalid_items[] = $link;
		} elseif ( $resolved_date <= wp_date( 'Y-m-d' ) ) {
			$past_items[] = $link;
		}
	}

	if ( empty( $past_items ) && empty( $invalid_items ) ) {
		return;
	}

	$allowed_html = array(
		'strong' => array(),
		'a'      => array( 'href' => array() ),
	);
	?>
	<div class="notice notice-warning">
		<?php if ( ! empty( $past_items ) ) { ?>
			<p>
			<?php
				printf(
					/* translators: %s: comma-separated list of level names with links */
					wp_kses( __( '<strong>Warning:</strong> The following membership levels have an expiration date that is in the past, so new signups are blocked: %s.', 'paid-memberships-pro' ), $allowed_html ),
					implode( ', ', $past_items ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			?>
			</p>
		<?php } ?>
		<?php if ( ! empty( $invalid_items ) ) { ?>
			<p>
			<?php
				printf(
					/* translators: %s: comma-separated list of level names with links */
					wp_kses( __( '<strong>Warning:</strong> The following membership levels have an expiration date pattern that could not be understood and will be ignored: %s.', 'paid-memberships-pro' ), $allowed_html ),
					implode( ', ', $invalid_items ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			?>
			</p>
		<?php } ?>
	</div>
	<?php
}
if ( isset( $_REQUEST['page'] ) && 'pmpro-membershiplevels' === $_REQUEST['page'] && ! isset( $_REQUEST['edit'] ) ) {
	add_action( 'admin_notices', 'pmpro_set_expiration_date_admin_notice' );
}

/**
 * Save the subscription delay and set expiration date for a discount code level.
 *
 * Runs on pmpro_save_discount_code_level for each checked level.
 *
 * @since TBD
 *
 * @param int $code_id  The discount code ID being saved.
 * @param int $level_id The membership level ID being saved for the code.
 */
function pmpro_payment_schedule_save_discount_code_level( $code_id, $level_id ) {
	global $pmpro_payment_schedule_dc_errors;

	$level_id = intval( $level_id );

	// Match the level save page: a delay only applies while the level's Recurring
	// Subscription box is checked, and an expiration date only while its Membership
	// Expiration box is checked. The rows are hidden when unchecked, but hidden
	// inputs still submit.
	$recurring_levels  = isset( $_REQUEST['recurring'] ) ? array_map( 'intval', (array) $_REQUEST['recurring'] ) : array();
	$expiration_levels = isset( $_REQUEST['expiration'] ) ? array_map( 'intval', (array) $_REQUEST['expiration'] ) : array();

	// Determine the subscription delay value based on the type.
	$delay_type = in_array( $level_id, $recurring_levels, true ) && isset( $_REQUEST[ 'delay_type_' . $level_id ] ) ? sanitize_text_field( $_REQUEST[ 'delay_type_' . $level_id ] ) : 'none';

	$delay_value   = '';
	$delay_invalid = false;
	if ( $delay_type === 'days' && ! empty( $_REQUEST[ 'subscription_delay_days_' . $level_id ] ) ) {
		$delay_value = intval( $_REQUEST[ 'subscription_delay_days_' . $level_id ] );
	} elseif ( $delay_type === 'date' ) {
		$delay_value = pmpro_get_date_pattern_from_request( 'subscription_delay_date_' . $level_id );
		if ( '' !== $delay_value && ! pmpro_is_valid_date_pattern( $delay_value ) ) {
			// Invalid pattern: keep the previous setting and report the error.
			$delay_value   = '';
			$delay_invalid = true;
			$level = pmpro_getLevel( $level_id );
			$pmpro_payment_schedule_dc_errors[] = sprintf(
				/* translators: %s: the membership level name. */
				__( 'The First Recurring Payment date pattern for the %s level was invalid, so that setting was not updated.', 'paid-memberships-pro' ),
				! empty( $level->name ) ? $level->name : $level_id
			);
		}
	}

	$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
	if ( ! is_array( $all_delays ) ) {
		$all_delays = array();
	}
	if ( '' !== $delay_value ) {
		$all_delays[ $code_id ][ $level_id ] = $delay_value;
	} elseif ( ! $delay_invalid ) {
		unset( $all_delays[ $code_id ][ $level_id ] );
	}
	update_option( 'pmpro_discount_code_subscription_delays', $all_delays );

	// Determine the set expiration date value based on the type.
	$exp_type = in_array( $level_id, $expiration_levels, true ) && isset( $_REQUEST[ 'expiration_date_type_' . $level_id ] ) ? sanitize_text_field( $_REQUEST[ 'expiration_date_type_' . $level_id ] ) : 'none';

	$expiration_value   = '';
	$expiration_invalid = false;
	if ( $exp_type === 'date' ) {
		$expiration_value = pmpro_get_date_pattern_from_request( 'set_expiration_date_' . $level_id );
		if ( '' !== $expiration_value && ! pmpro_is_valid_date_pattern( $expiration_value ) ) {
			// Invalid pattern: keep the previous setting and report the error.
			$expiration_value   = '';
			$expiration_invalid = true;
			$level = pmpro_getLevel( $level_id );
			$pmpro_payment_schedule_dc_errors[] = sprintf(
				/* translators: %s: the membership level name. */
				__( 'The expiration date pattern for the %s level was invalid, so that setting was not updated.', 'paid-memberships-pro' ),
				! empty( $level->name ) ? $level->name : $level_id
			);
		}
	}

	$option_key = 'pmprosed_' . $level_id . '_' . intval( $code_id );
	if ( '' !== $expiration_value ) {
		update_option( $option_key, $expiration_value, false );
	} elseif ( ! $expiration_invalid ) {
		delete_option( $option_key );
	}
}
add_action( 'pmpro_save_discount_code_level', 'pmpro_payment_schedule_save_discount_code_level', 10, 2 );

/**
 * Remove payment schedule options for levels that were unchecked from a discount code.
 *
 * Called directly from the discount code save flow in adminpages/discountcodes.php.
 * The per-level save hook only fires for checked levels, so without this an
 * unchecked level's schedule options would silently come back if the level is
 * ever re-checked.
 *
 * @since TBD
 *
 * @param int $code_id The discount code ID that was just saved.
 */
function pmpro_payment_schedule_cleanup_unchecked_levels( $code_id ) {
	$all_levels     = isset( $_REQUEST['all_levels'] ) ? array_map( 'intval', (array) $_REQUEST['all_levels'] ) : array();
	$checked_levels = isset( $_REQUEST['levels'] ) ? array_map( 'intval', (array) $_REQUEST['levels'] ) : array();
	$unchecked      = array_diff( $all_levels, $checked_levels );
	if ( empty( $unchecked ) ) {
		return;
	}

	$code_id    = intval( $code_id );
	$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
	if ( ! is_array( $all_delays ) ) {
		$all_delays = array();
	}
	$delays_changed = false;
	foreach ( $unchecked as $level_id ) {
		delete_option( 'pmprosed_' . $level_id . '_' . $code_id );
		if ( isset( $all_delays[ $code_id ][ $level_id ] ) ) {
			unset( $all_delays[ $code_id ][ $level_id ] );
			$delays_changed = true;
		}
	}
	if ( $delays_changed ) {
		update_option( 'pmpro_discount_code_subscription_delays', $all_delays );
	}
}

/**
 * Delete payment schedule options when a membership level is deleted.
 *
 * @since TBD
 *
 * @param int $level_id The ID of the level being deleted.
 */
function pmpro_payment_schedule_delete_level( $level_id ) {
	global $wpdb;

	$level_id = intval( $level_id );
	delete_option( 'pmpro_subscription_delay_' . $level_id );
	delete_option( 'pmprosed_' . $level_id );

	// Remove per-discount-code expiration dates for this level.
	$code_ids = $wpdb->get_col( "SELECT id FROM $wpdb->pmpro_discount_codes" );
	foreach ( $code_ids as $code_id ) {
		delete_option( 'pmprosed_' . $level_id . '_' . intval( $code_id ) );
	}

	// Remove this level from all per-discount-code delays.
	$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
	if ( is_array( $all_delays ) ) {
		$changed = false;
		foreach ( $all_delays as $code_id => $levels ) {
			if ( is_array( $levels ) && isset( $levels[ $level_id ] ) ) {
				unset( $all_delays[ $code_id ][ $level_id ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( 'pmpro_discount_code_subscription_delays', $all_delays );
		}
	}
}
add_action( 'pmpro_delete_membership_level', 'pmpro_payment_schedule_delete_level' );

/**
 * Delete payment schedule options when a discount code is deleted.
 *
 * @since TBD
 *
 * @param int $code_id The ID of the discount code being deleted.
 */
function pmpro_payment_schedule_delete_discount_code( $code_id ) {
	global $wpdb;

	$code_id = intval( $code_id );

	// Remove per-discount-code expiration dates for this code.
	$level_ids = $wpdb->get_col( "SELECT id FROM $wpdb->pmpro_membership_levels" );
	foreach ( $level_ids as $level_id ) {
		delete_option( 'pmprosed_' . intval( $level_id ) . '_' . $code_id );
	}

	// Remove this code from the per-discount-code delays.
	$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
	if ( is_array( $all_delays ) && isset( $all_delays[ $code_id ] ) ) {
		unset( $all_delays[ $code_id ] );
		update_option( 'pmpro_discount_code_subscription_delays', $all_delays );
	}
}
add_action( 'pmpro_delete_discount_code', 'pmpro_payment_schedule_delete_discount_code' );

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