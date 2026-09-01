<?php
/**
 * Payment Schedule - Subscription Delays & Set Expiration Dates
 *
 * Handles subscription delay (delaying the first recurring payment) and
 * set expiration date (fixed/pattern-based expiration dates) functionality
 * that was previously provided by separate add-on plugins.
 *
 * @since TBD
 */

/**
 * Format a day number as an ordinal (1st, 2nd, 3rd, etc.).
 *
 * @since TBD
 *
 * @param int $day Day number 1-31.
 * @return string Formatted ordinal string.
 */
function pmpro_format_day_ordinal( $day ) {
	$day = intval( $day );
	if ( $day >= 11 && $day <= 13 ) {
		/* translators: %d: day of the month, as an ordinal ("4th"). */
		return sprintf( __( '%dth', 'paid-memberships-pro' ), $day );
	}
	switch ( $day % 10 ) {
		case 1:
			/* translators: %d: day of the month, as an ordinal ("1st"). */
			return sprintf( __( '%dst', 'paid-memberships-pro' ), $day );
		case 2:
			/* translators: %d: day of the month, as an ordinal ("2nd"). */
			return sprintf( __( '%dnd', 'paid-memberships-pro' ), $day );
		case 3:
			/* translators: %d: day of the month, as an ordinal ("3rd"). */
			return sprintf( __( '%drd', 'paid-memberships-pro' ), $day );
		default:
			/* translators: %d: day of the month, as an ordinal ("4th"). */
			return sprintf( __( '%dth', 'paid-memberships-pro' ), $day );
	}
}

/**
 * Check whether a string is a valid date pattern.
 *
 * A valid pattern is "{year}-{month}-{day}" where {year} is a 4-digit year or
 * a Y token (Y, Y2-Y99), {month} is a 1-12 month number or an M token
 * (M, M2-M99), and {day} is 1-31. Zero tokens (Y0, M0) and offsets over 99
 * are rejected rather than resolving to surprising dates.
 *
 * @since TBD
 *
 * @param string $pattern The date pattern string to check.
 * @return bool True if the pattern is valid.
 */
function pmpro_is_valid_date_pattern( $pattern ) {
	if ( ! is_string( $pattern ) && ! is_numeric( $pattern ) ) {
		return false;
	}

	if ( ! preg_match( '/^(Y[1-9][0-9]?|Y|\d{4})-(M[1-9][0-9]?|M|\d{1,2})-(\d{1,2})$/', strtoupper( trim( (string) $pattern ) ), $matches ) ) {
		return false;
	}

	// A literal month must be 1-12.
	if ( strpos( $matches[2], 'M' ) !== 0 ) {
		$month = intval( $matches[2] );
		if ( $month < 1 || $month > 12 ) {
			return false;
		}
	}

	// The day must be 1-31.
	$day = intval( $matches[3] );
	return $day >= 1 && $day <= 31;
}

/**
 * Convert a date pattern string to an actual date.
 *
 * Supports patterns like:
 * - "Y-01-01"  => January 1st of current/next year (whichever is next)
 * - "Y2-06-15" => June 15th of next year (Y2 skips the current year's occurrence)
 * - "Y-M-01"   => 1st of current/next month
 * - "2025-12-31" => Fixed date
 * - "Y" alone in year means "Y1" (next occurrence)
 * - "M" alone in month means "M1" (next occurrence)
 *
 * A pattern that resolves to today advances to the next occurrence: the next
 * month for M patterns and the next year for Y patterns.
 *
 * @since TBD
 *
 * @param string $date       The date pattern string.
 * @param int    $current_date Optional. Unix timestamp to use as "today". Defaults to current_time('timestamp').
 * @return string|false Date in Y-m-d format, or false if the pattern is invalid.
 */
function pmpro_convert_date_pattern( $date, $current_date = null ) {
	// Bail on anything that isn't a well-formed pattern so that malformed input
	// can never produce an invalid date string.
	if ( ! pmpro_is_valid_date_pattern( $date ) ) {
		return false;
	}

	// Handle lower-cased y/m values.
	$set_date = strtoupper( trim( $date ) );

	// Change "Y-" and "M-" to "Y1-" and "M1-".
	$set_date = str_replace( array( 'Y-', 'M-' ), array( 'Y1-', 'M1-' ), $set_date );

	// Get number of months and years to add. Token values are capped at 99 by
	// pmpro_is_valid_date_pattern().
	$add_months = 0;
	$add_years  = 0;
	if ( stripos( $set_date, 'M' ) !== false ) {
		$add_months = intval( pmpro_getMatches( '/M([0-9]*)/', $set_date, true ) );
	}
	if ( stripos( $set_date, 'Y' ) !== false ) {
		$add_years = intval( pmpro_getMatches( '/Y([0-9]*)/', $set_date, true ) );
	}

	// Callers may pass a custom "today" timestamp (e.g. the pmprosed_fixDate() shim).
	if ( empty( $current_date ) ) {
		$current_date = current_time( 'timestamp' );
	}

	/**
	 * Filter the current date used for date pattern calculations.
	 *
	 * @since TBD
	 *
	 * @param int $current_date Unix timestamp of the current date.
	 */
	$current_date = apply_filters( 'pmpro_payment_schedule_current_date', $current_date );

	// Back compat with the retired Subscription Delays Add On's filter (same timestamp value).
	$current_date = apply_filters_deprecated( 'pmprosd_current_date', array( $current_date ), 'TBD', 'pmpro_payment_schedule_current_date' );

	// Get current date parts.
	$current_y = intval( date( 'Y', $current_date ) );
	$current_m = intval( date( 'm', $current_date ) );
	$current_d = intval( date( 'd', $current_date ) );

	// Get set date parts. The validator guarantees three parts with a day of 1-31.
	$date_parts = explode( '-', $set_date );
	$set_y      = intval( $date_parts[0] );
	$set_m      = intval( $date_parts[1] );
	$set_d      = intval( $date_parts[2] );

	// Get temporary date parts.
	$temp_y = $set_y > 0 ? $set_y : $current_y;
	$temp_m = $set_m > 0 ? $set_m : $current_m;
	$temp_d = $set_d;

	// Add months.
	if ( ! empty( $add_months ) ) {
		for ( $i = 0; $i < $add_months; $i++ ) {
			// If "M1", only add months if the day of the month has already passed.
			// A pattern that lands on today counts as passed so that the resolved
			// date is always in the future. The day is clamped to the month's
			// length first so that e.g. the "31st" of a 30-day month compares as
			// its last day instead of resolving to today or the past.
			if ( 0 == $i ) {
				$clamped_d = min( $temp_d, intval( date( 't', mktime( 0, 0, 0, $temp_m, 1, $temp_y ) ) ) );
				if ( $clamped_d <= $current_d ) {
					$temp_m++;
					$add_months--;
				}
			} else {
				$temp_m++;
			}

			// If we hit 13, reset to Jan of next year and subtract one of the years to add.
			if ( $temp_m == 13 ) {
				$temp_m = 1;
				$temp_y++;
				$add_years--;
			}
		}
	}

	// Add years.
	if ( ! empty( $add_years ) ) {
		for ( $i = 0; $i < $add_years; $i++ ) {
			// If "Y1", only add years if the date has already passed. The day is
			// clamped to the month's length (e.g. Y-02-31 is the end of February)
			// and the comparison is date-granular so that a date landing on today
			// counts as passed exactly once.
			if ( 0 == $i ) {
				$clamped_d = min( $temp_d, intval( date( 't', mktime( 0, 0, 0, $temp_m, 1, $temp_y ) ) ) );
				if ( mktime( 0, 0, 0, $temp_m, $clamped_d, $temp_y ) <= strtotime( date( 'Y-m-d', $current_date ) ) ) {
					$temp_y++;
					$add_years--;
				}
			} else {
				$temp_y++;
			}
		}
	}

	// Clamp the day to the resolved month's length (the "31st" of a 30-day month
	// is its last day) and put it all together.
	$temp_d = min( $temp_d, intval( date( 't', mktime( 0, 0, 0, $temp_m, 1, $temp_y ) ) ) );

	return sprintf( '%04d-%02d-%02d', $temp_y, $temp_m, $temp_d );
}

/**
 * Resolve a set expiration date pattern to an actual date, running the
 * expiration-specific filters.
 *
 * This is the expiration counterpart to calling pmpro_convert_date_pattern()
 * directly and preserves the filters that the retired Set Expiration Dates
 * Add On ran inside pmprosed_fixDate().
 *
 * @since TBD
 *
 * @param string $set_expiration_date The expiration date pattern.
 * @param int    $current_date        Optional. Unix timestamp to use as "today".
 * @return string|false Date in Y-m-d format, or false if the pattern is invalid.
 */
function pmpro_payment_schedule_resolve_expiration_date( $set_expiration_date, $current_date = null ) {
	$set_expiration_date = strtoupper( trim( (string) $set_expiration_date ) );
	$set_expiration_date = apply_filters_deprecated( 'pmprosed_expiration_date_raw', array( $set_expiration_date ), 'TBD', 'pmpro_get_set_expiration_date' );

	$resolved_date = pmpro_convert_date_pattern( $set_expiration_date, $current_date );
	if ( empty( $resolved_date ) ) {
		return false;
	}

	$resolved_date = apply_filters_deprecated( 'pmprosed_expiration_date', array( $resolved_date ), 'TBD', 'pmpro_get_set_expiration_date' );

	return $resolved_date;
}

/**
 * Get the subscription delay value for a level, optionally with a discount code.
 *
 * When a discount code is passed, only the code's own setting is used - a code
 * with no delay configured means no delay, even if the level has one. This
 * matches the behavior of the retired Subscription Delays Add On.
 *
 * @since TBD
 *
 * @param int      $level_id The membership level ID.
 * @param int|null $code_id  Optional discount code ID.
 * @return string The subscription delay value (days or date pattern), or empty string.
 */
function pmpro_get_subscription_delay( $level_id, $code_id = null ) {
	if ( ! empty( $code_id ) ) {
		// Discount code delays are stored as a nested array in a single option.
		$all_delays         = get_option( 'pmpro_discount_code_subscription_delays', array() );
		$subscription_delay = is_array( $all_delays ) && ! empty( $all_delays[ $code_id ][ $level_id ] ) ? $all_delays[ $code_id ][ $level_id ] : '';
	} else {
		$subscription_delay = get_option( 'pmpro_subscription_delay_' . intval( $level_id ), '' );
	}

	/**
	 * Filter the subscription delay for a level or level/discount code combo.
	 *
	 * @since TBD
	 *
	 * @param string|int $subscription_delay The delay (a number of days or a date pattern), or empty string.
	 * @param int        $level_id           The membership level ID.
	 * @param int|null   $code_id            The discount code ID, if one is in play.
	 */
	return apply_filters( 'pmpro_get_subscription_delay', $subscription_delay, $level_id, $code_id );
}

/**
 * Get the set expiration date pattern for a level, optionally with a discount code.
 *
 * When a discount code is passed, only the code's own setting is used - a code
 * with no expiration date configured means no set expiration date, even if the
 * level has one. This matches the behavior of the retired Set Expiration Dates
 * Add On.
 *
 * @since TBD
 *
 * @param int      $level_id The membership level ID.
 * @param int|null $code_id  Optional discount code ID.
 * @return string The expiration date pattern, or empty string.
 */
function pmpro_get_set_expiration_date( $level_id, $code_id = null ) {
	if ( ! empty( $code_id ) ) {
		// Discount code expiration dates: pmprosed_{level_id}_{code_id}
		$set_expiration_date = get_option( 'pmprosed_' . intval( $level_id ) . '_' . intval( $code_id ), '' );
	} else {
		$set_expiration_date = get_option( 'pmprosed_' . intval( $level_id ), '' );
	}

	/**
	 * Filter the set expiration date pattern for a level or level/discount code combo.
	 *
	 * @since TBD
	 *
	 * @param string   $set_expiration_date The expiration date pattern, or empty string.
	 * @param int      $level_id            The membership level ID.
	 * @param int|null $code_id             The discount code ID, if one is in play.
	 */
	return apply_filters( 'pmpro_get_set_expiration_date', $set_expiration_date, $level_id, $code_id );
}

/**
 * Apply subscription delay to the checkout level by setting profile_start_date.
 *
 * @since TBD
 *
 * @param object $level The PMPro Level object at checkout.
 * @return object The modified level object.
 */
function pmpro_apply_subscription_delay_at_checkout( $level ) {
	if ( empty( $level ) || empty( $level->id ) ) {
		return $level;
	}

	// Only applies to recurring levels.
	if ( ! pmpro_isLevelRecurring( $level ) ) {
		return $level;
	}

	// Get the subscription delay. Check discount code first.
	$code_id = ! empty( $level->code_id ) ? $level->code_id : null;
	$subscription_delay = pmpro_get_subscription_delay( $level->id, $code_id );

	if ( empty( $subscription_delay ) ) {
		return $level;
	}

	// Convert the subscription delay to a profile_start_date.
	if ( is_numeric( $subscription_delay ) ) {
		$level->profile_start_date = date( 'Y-m-d', strtotime( '+ ' . intval( $subscription_delay ) . ' Days', current_time( 'timestamp' ) ) ) . 'T0:0:0';
	} else {
		$resolved_date = pmpro_convert_date_pattern( $subscription_delay );
		if ( empty( $resolved_date ) ) {
			// Malformed stored pattern - ignore the delay rather than sending a bad date to the gateway.
			return $level;
		}
		$level->profile_start_date = $resolved_date . 'T0:0:0';
	}

	// Make sure the profile start date is not before the current date.
	$today = date( 'Y-m-d\T0:0:0', current_time( 'timestamp' ) );
	if ( $level->profile_start_date < $today ) {
		$level->profile_start_date = $today;
	}

	return $level;
}

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
 * Render a date pattern builder.
 *
 * Used on the Edit Level and Edit Discount Code pages. Outputs a mode select
 * plus per-mode inputs named "{$field_prefix}_mode", "{$field_prefix}_monthly_day",
 * "{$field_prefix}_yearly_month", "{$field_prefix}_yearly_day", and
 * "{$field_prefix}_custom". Visibility is handled by the declarative depends
 * engine in pmpro-admin.js; the parts are assembled into a date pattern at save
 * time by pmpro_get_date_pattern_from_request().
 *
 * @since TBD
 *
 * @param string $field_prefix   Prefix for the input names and ids. Must be unique on the page.
 * @param string $existing_value The stored date pattern to preselect.
 */
function pmpro_payment_schedule_render_date_builder( $field_prefix, $existing_value ) {
	$month_names = array(
		'01' => __( 'January', 'paid-memberships-pro' ), '02' => __( 'February', 'paid-memberships-pro' ),
		'03' => __( 'March', 'paid-memberships-pro' ), '04' => __( 'April', 'paid-memberships-pro' ),
		'05' => __( 'May', 'paid-memberships-pro' ), '06' => __( 'June', 'paid-memberships-pro' ),
		'07' => __( 'July', 'paid-memberships-pro' ), '08' => __( 'August', 'paid-memberships-pro' ),
		'09' => __( 'September', 'paid-memberships-pro' ), '10' => __( 'October', 'paid-memberships-pro' ),
		'11' => __( 'November', 'paid-memberships-pro' ), '12' => __( 'December', 'paid-memberships-pro' ),
	);

	// Parse the stored pattern into the builder's initial state.
	$mode           = '';
	$monthly_day    = '01';
	$yearly_month   = '01';
	$yearly_day     = '01';
	$custom_pattern = '';
	$existing_value = strtoupper( trim( (string) $existing_value ) );
	if ( preg_match( '/^Y-M-(\d{1,2})$/', $existing_value, $matches ) ) {
		$mode        = 'monthly';
		$monthly_day = str_pad( intval( $matches[1] ), 2, '0', STR_PAD_LEFT );
	} elseif ( preg_match( '/^Y-(\d{1,2})-(\d{1,2})$/', $existing_value, $matches ) ) {
		$mode         = 'yearly';
		$yearly_month = str_pad( intval( $matches[1] ), 2, '0', STR_PAD_LEFT );
		$yearly_day   = str_pad( intval( $matches[2] ), 2, '0', STR_PAD_LEFT );
	} elseif ( '' !== $existing_value ) {
		$mode           = 'custom';
		$custom_pattern = $existing_value;
	}

	$mode_id = $field_prefix . '_mode';
	?>
	<div class="pmpro_date_pattern_builder">
		<select name="<?php echo esc_attr( $mode_id ); ?>" id="<?php echo esc_attr( $mode_id ); ?>" aria-label="<?php esc_attr_e( 'Date pattern type', 'paid-memberships-pro' ); ?>">
			<option value=""><?php esc_html_e( 'Choose...', 'paid-memberships-pro' ); ?></option>
			<option value="monthly" <?php selected( $mode, 'monthly' ); ?>><?php esc_html_e( 'The same day each month', 'paid-memberships-pro' ); ?></option>
			<option value="yearly" <?php selected( $mode, 'yearly' ); ?>><?php esc_html_e( 'The same date each year', 'paid-memberships-pro' ); ?></option>
			<option value="custom" <?php selected( $mode, 'custom' ); ?>><?php esc_html_e( 'Custom pattern', 'paid-memberships-pro' ); ?></option>
		</select>
		<span class="<?php echo 'monthly' === $mode ? '' : 'pmpro-hidden'; ?>" data-pmpro-depends="<?php echo esc_attr( wp_json_encode( array( array( 'id' => $mode_id, 'value' => 'monthly' ) ) ) ); ?>">
			<?php esc_html_e( 'on the', 'paid-memberships-pro' ); ?>
			<select name="<?php echo esc_attr( $field_prefix ); ?>_monthly_day" aria-label="<?php esc_attr_e( 'Day of the month', 'paid-memberships-pro' ); ?>">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>" <?php selected( $monthly_day, str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="<?php echo 'yearly' === $mode ? '' : 'pmpro-hidden'; ?>" data-pmpro-depends="<?php echo esc_attr( wp_json_encode( array( array( 'id' => $mode_id, 'value' => 'yearly' ) ) ) ); ?>">
			<?php esc_html_e( 'on', 'paid-memberships-pro' ); ?>
			<select name="<?php echo esc_attr( $field_prefix ); ?>_yearly_month" aria-label="<?php esc_attr_e( 'Month', 'paid-memberships-pro' ); ?>">
				<?php foreach ( $month_names as $val => $name ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $yearly_month, $val ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="<?php echo esc_attr( $field_prefix ); ?>_yearly_day" aria-label="<?php esc_attr_e( 'Day of the month', 'paid-memberships-pro' ); ?>">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>" <?php selected( $yearly_day, str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="<?php echo 'custom' === $mode ? '' : 'pmpro-hidden'; ?>" data-pmpro-depends="<?php echo esc_attr( wp_json_encode( array( array( 'id' => $mode_id, 'value' => 'custom' ) ) ) ); ?>">
			<input type="text" name="<?php echo esc_attr( $field_prefix ); ?>_custom" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: an example date pattern. */ __( 'e.g. %s', 'paid-memberships-pro' ), 'Y-01-01' ) ); ?>"
				value="<?php echo esc_attr( $custom_pattern ); ?>"
				aria-label="<?php esc_attr_e( 'Custom date pattern', 'paid-memberships-pro' ); ?>" />
			<p class="description"><?php echo esc_html( sprintf( /* translators: 1: the literal Y token, 2: the literal M token. Do not translate the tokens themselves. */ __( '%1$s = current/next year, %2$s = current/next month.', 'paid-memberships-pro' ), 'Y', 'M' ) ); ?></p>
		</span>
	</div>
	<?php
}

/**
 * Assemble a date pattern from a date pattern builder's submitted fields.
 *
 * @since TBD
 *
 * @param string $field_prefix The prefix used when rendering the builder.
 * @return string The assembled date pattern, or empty string if no mode was chosen.
 */
function pmpro_get_date_pattern_from_request( $field_prefix ) {
	$mode = isset( $_REQUEST[ $field_prefix . '_mode' ] ) ? sanitize_text_field( $_REQUEST[ $field_prefix . '_mode' ] ) : '';

	if ( 'monthly' === $mode ) {
		$day = isset( $_REQUEST[ $field_prefix . '_monthly_day' ] ) ? max( 1, min( 31, intval( $_REQUEST[ $field_prefix . '_monthly_day' ] ) ) ) : 1;
		return 'Y-M-' . str_pad( $day, 2, '0', STR_PAD_LEFT );
	}

	if ( 'yearly' === $mode ) {
		$month = isset( $_REQUEST[ $field_prefix . '_yearly_month' ] ) ? max( 1, min( 12, intval( $_REQUEST[ $field_prefix . '_yearly_month' ] ) ) ) : 1;
		$day   = isset( $_REQUEST[ $field_prefix . '_yearly_day' ] ) ? max( 1, min( 31, intval( $_REQUEST[ $field_prefix . '_yearly_day' ] ) ) ) : 1;
		return 'Y-' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $day, 2, '0', STR_PAD_LEFT );
	}

	if ( 'custom' === $mode && isset( $_REQUEST[ $field_prefix . '_custom' ] ) ) {
		return strtoupper( trim( sanitize_text_field( $_REQUEST[ $field_prefix . '_custom' ] ) ) );
	}

	return '';
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
