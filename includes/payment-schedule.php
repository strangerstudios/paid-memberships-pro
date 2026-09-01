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
	$set_date = preg_replace( '/Y-/', 'Y1-', $set_date );
	$set_date = preg_replace( '/M-/', 'M1-', $set_date );

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

	// Allow custom "today" date for previews and testing.
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

	/**
	 * Filter the raw expiration date pattern before it is resolved.
	 *
	 * @since TBD
	 *
	 * @param string $set_expiration_date The uppercased expiration date pattern.
	 */
	$set_expiration_date = apply_filters( 'pmpro_payment_schedule_expiration_date_raw', $set_expiration_date );
	$set_expiration_date = apply_filters_deprecated( 'pmprosed_expiration_date_raw', array( $set_expiration_date ), 'TBD', 'pmpro_payment_schedule_expiration_date_raw' );

	$resolved_date = pmpro_convert_date_pattern( $set_expiration_date, $current_date );
	if ( empty( $resolved_date ) ) {
		return false;
	}

	/**
	 * Filter the resolved expiration date.
	 *
	 * @since TBD
	 *
	 * @param string $resolved_date The resolved date in Y-m-d format.
	 */
	$resolved_date = apply_filters( 'pmpro_payment_schedule_expiration_date', $resolved_date );
	$resolved_date = apply_filters_deprecated( 'pmprosed_expiration_date', array( $resolved_date ), 'TBD', 'pmpro_payment_schedule_expiration_date' );

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
		$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
		if ( is_array( $all_delays ) && ! empty( $all_delays[ $code_id ][ $level_id ] ) ) {
			return $all_delays[ $code_id ][ $level_id ];
		}
		return '';
	}

	return get_option( 'pmpro_subscription_delay_' . intval( $level_id ), '' );
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
		return get_option( 'pmprosed_' . intval( $level_id ) . '_' . intval( $code_id ), '' );
	}

	return get_option( 'pmprosed_' . intval( $level_id ), '' );
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
 * Get the discount code ID in play for the current checkout request, if any.
 *
 * Accepts both the modern pmpro_discount_code and the legacy discount_code
 * request params, matching how pmpro_getLevelAtCheckout() resolves the code.
 *
 * @since TBD
 *
 * @return int|null The discount code ID, or null if no code is in the request.
 */
function pmpro_payment_schedule_get_checkout_code_id() {
	global $wpdb;

	if ( ! empty( $_REQUEST['pmpro_discount_code'] ) && is_scalar( $_REQUEST['pmpro_discount_code'] ) ) {
		$discount_code = sanitize_text_field( $_REQUEST['pmpro_discount_code'] );
	} elseif ( ! empty( $_REQUEST['discount_code'] ) && is_scalar( $_REQUEST['discount_code'] ) ) {
		$discount_code = sanitize_text_field( $_REQUEST['discount_code'] );
	} else {
		return null;
	}

	$discount_code = preg_replace( '/[^A-Za-z0-9\-]/', '', $discount_code );
	if ( empty( $discount_code ) ) {
		return null;
	}

	$code_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = %s LIMIT 1", $discount_code ) );
	return ! empty( $code_id ) ? intval( $code_id ) : null;
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

	// Check for a discount code in the request if one wasn't passed in.
	if ( empty( $discount_code_id ) ) {
		$discount_code_id = pmpro_payment_schedule_get_checkout_code_id();
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
 * @param bool $okay Whether the checkout is okay so far.
 * @return bool Whether the checkout is still okay.
 */
function pmpro_payment_schedule_registration_check( $okay ) {
	global $pmpro_level;

	// Bail if the checkout already failed or we don't have a level.
	if ( ! $okay || empty( $pmpro_level ) || empty( $pmpro_level->id ) ) {
		return $okay;
	}

	$set_expiration_date = pmpro_get_set_expiration_date( $pmpro_level->id, ! empty( $pmpro_level->code_id ) ? $pmpro_level->code_id : null );
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
add_filter( 'pmpro_registration_checks', 'pmpro_payment_schedule_registration_check' );

/**
 * Wrapper for IPN/webhook level handlers.
 *
 * @since TBD
 */
function pmpro_set_expiration_ipnhandler_level( $level, $user_id = null ) {
	return pmpro_apply_set_expiration_date_at_checkout( $level, null );
}
add_filter( 'pmpro_ipnhandler_level', 'pmpro_set_expiration_ipnhandler_level', 10, 2 );
add_filter( 'pmpro_payfast_itnhandler_level', 'pmpro_set_expiration_ipnhandler_level', 10, 2 );
add_filter( 'pmpro_paystack_webhook_level', 'pmpro_set_expiration_ipnhandler_level', 10, 2 );

/**
 * Show admin warning if any levels have a past set expiration date.
 *
 * @since TBD
 */
function pmpro_set_expiration_date_admin_notice() {
	$levels = pmpro_getAllLevels( true, false );
	$problem_levels = array();

	foreach ( $levels as $level ) {
		if ( ! $level->allow_signups ) {
			continue;
		}

		$set_expiration_date = pmpro_get_set_expiration_date( $level->id );
		if ( empty( $set_expiration_date ) ) {
			continue;
		}

		$resolved_date = pmpro_payment_schedule_resolve_expiration_date( $set_expiration_date );
		if ( ! empty( $resolved_date ) && $resolved_date < wp_date( 'Y-m-d' ) ) {
			$problem_levels[ $level->id ] = '<a href="' . esc_url( add_query_arg(
				array(
					'page' => 'pmpro-membershiplevels',
					'edit' => $level->id,
				),
				admin_url( 'admin.php' )
			) ) . '">' . esc_html( $level->name ) . '</a>';
		}
	}

	if ( ! empty( $problem_levels ) ) {
		$levels_list = implode( ', ', $problem_levels );
		?>
		<div class="notice notice-warning">
			<p>
			<?php
				printf(
					/* translators: %s: comma-separated list of level names with links */
					wp_kses(
						__( '<strong>Warning:</strong> The following membership levels have an expiration date that is in the past: %s.', 'paid-memberships-pro' ),
						array( 'strong' => array(), 'a' => array( 'href' => array() ) )
					),
					$levels_list
				);
			?>
			</p>
		</div>
		<?php
	}
}
if ( isset( $_REQUEST['page'] ) && 'pmpro-membershiplevels' === $_REQUEST['page'] && ! isset( $_REQUEST['edit'] ) ) {
	add_action( 'admin_notices', 'pmpro_set_expiration_date_admin_notice' );
}

// Payment schedule preview is calculated client-side in the admin JS.

/**
 * Render a date pattern builder UI block.
 *
 * Used on the Edit Level and Edit Discount Code pages. Outputs a self-contained
 * builder with mode select (monthly/yearly/custom), day/month dropdowns, and a
 * hidden input that stores the assembled pattern.
 *
 * @since TBD
 *
 * @param array  $month_names    Associative array of month number => translated name.
 * @param string $hidden_name    The name attribute for the hidden input storing the pattern.
 * @param string $existing_value The existing date pattern value.
 * @param string $id             Optional. An id attribute for the builder wrapper element.
 */
function pmpro_payment_schedule_render_date_builder( $month_names, $hidden_name, $existing_value, $id = '' ) {
	?>
	<div class="pmpro_date_pattern_builder"<?php if ( ! empty( $id ) ) { echo ' id="' . esc_attr( $id ) . '"'; } ?> data-existing-value="<?php echo esc_attr( $existing_value ); ?>">
		<select class="pmpro_date_pattern_mode" onchange="pmpro_date_mode_changed(this);" aria-label="<?php esc_attr_e( 'Date pattern type', 'paid-memberships-pro' ); ?>">
			<option value=""><?php esc_html_e( 'Choose...', 'paid-memberships-pro' ); ?></option>
			<option value="monthly"><?php esc_html_e( 'The same day each month', 'paid-memberships-pro' ); ?></option>
			<option value="yearly"><?php esc_html_e( 'The same date each year', 'paid-memberships-pro' ); ?></option>
			<option value="custom"><?php esc_html_e( 'Custom pattern', 'paid-memberships-pro' ); ?></option>
		</select>
		<span class="pmpro_date_builder_monthly" style="display:none;">
			<?php esc_html_e( 'on the', 'paid-memberships-pro' ); ?>
			<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);" aria-label="<?php esc_attr_e( 'Day of the month', 'paid-memberships-pro' ); ?>">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="pmpro_date_builder_yearly" style="display:none;">
			<?php esc_html_e( 'on', 'paid-memberships-pro' ); ?>
			<select class="pmpro_date_builder_month" onchange="pmpro_assemble_date_pattern(this);" aria-label="<?php esc_attr_e( 'Month', 'paid-memberships-pro' ); ?>">
				<?php foreach ( $month_names as $val => $name ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);" aria-label="<?php esc_attr_e( 'Day of the month', 'paid-memberships-pro' ); ?>">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="pmpro_date_builder_custom" style="display:none;">
			<input type="text" class="pmpro_date_pattern_input" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: an example date pattern. */ __( 'e.g. %s', 'paid-memberships-pro' ), 'Y-01-01' ) ); ?>"
				value="<?php echo esc_attr( $existing_value ); ?>"
				aria-label="<?php esc_attr_e( 'Custom date pattern', 'paid-memberships-pro' ); ?>"
				oninput="jQuery(this).closest('.pmpro_date_pattern_builder').find('.pmpro_date_pattern_value').val(this.value);" />
			<p class="description"><?php echo esc_html( sprintf( /* translators: 1: the literal Y token, 2: the literal M token. Do not translate the tokens themselves. */ __( '%1$s = current/next year, %2$s = current/next month.', 'paid-memberships-pro' ), 'Y', 'M' ) ); ?></p>
		</span>
		<input type="hidden" class="pmpro_date_pattern_value" name="<?php echo esc_attr( $hidden_name ); ?>" value="<?php echo esc_attr( $existing_value ); ?>" />
	</div>
	<?php
}

/**
 * Get the standard month names array for date builders.
 *
 * @since TBD
 *
 * @return array Associative array of two-digit month number => translated month name.
 */
function pmpro_get_month_names() {
	return array(
		'01' => __( 'January', 'paid-memberships-pro' ), '02' => __( 'February', 'paid-memberships-pro' ),
		'03' => __( 'March', 'paid-memberships-pro' ), '04' => __( 'April', 'paid-memberships-pro' ),
		'05' => __( 'May', 'paid-memberships-pro' ), '06' => __( 'June', 'paid-memberships-pro' ),
		'07' => __( 'July', 'paid-memberships-pro' ), '08' => __( 'August', 'paid-memberships-pro' ),
		'09' => __( 'September', 'paid-memberships-pro' ), '10' => __( 'October', 'paid-memberships-pro' ),
		'11' => __( 'November', 'paid-memberships-pro' ), '12' => __( 'December', 'paid-memberships-pro' ),
	);
}

function pmpro_payment_schedule_save_discount_code_level( $code_id, $level_id ) {
	$all_levels_a = isset( $_REQUEST['all_levels'] ) ? $_REQUEST['all_levels'] : array();
	$key          = array_search( $level_id, $all_levels_a );
	if ( $key === false ) {
		return;
	}

	// Determine the subscription delay value based on the type.
	$delay_type_a         = isset( $_REQUEST['delay_type'] ) ? $_REQUEST['delay_type'] : array();
	$delay_days_a         = isset( $_REQUEST['subscription_delay_days'] ) ? $_REQUEST['subscription_delay_days'] : array();
	$delay_date_a         = isset( $_REQUEST['subscription_delay_date'] ) ? $_REQUEST['subscription_delay_date'] : array();
	$delay_type           = isset( $delay_type_a[ $key ] ) ? sanitize_text_field( $delay_type_a[ $key ] ) : 'none';

	$delay_value   = '';
	$delay_invalid = false;
	if ( $delay_type === 'days' && isset( $delay_days_a[ $key ] ) && intval( $delay_days_a[ $key ] ) > 0 ) {
		$delay_value = intval( $delay_days_a[ $key ] );
	} elseif ( $delay_type === 'date' && ! empty( $delay_date_a[ $key ] ) ) {
		$delay_value = strtoupper( trim( sanitize_text_field( $delay_date_a[ $key ] ) ) );
		if ( ! pmpro_is_valid_date_pattern( $delay_value ) ) {
			// Invalid pattern: keep the previous setting and report the error.
			$delay_value   = '';
			$delay_invalid = true;
			pmpro_payment_schedule_add_discount_code_error(
				sprintf(
					/* translators: %s: the membership level name. */
					__( 'The First Recurring Payment date pattern for the %s level was invalid, so that setting was not updated.', 'paid-memberships-pro' ),
					pmpro_payment_schedule_get_level_name( $level_id )
				)
			);
		}
	}

	$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
	if ( ! is_array( $all_delays ) ) {
		$all_delays = array();
	}
	if ( ! empty( $delay_value ) ) {
		$all_delays[ $code_id ][ $level_id ] = $delay_value;
	} elseif ( ! $delay_invalid ) {
		unset( $all_delays[ $code_id ][ $level_id ] );
	}
	update_option( 'pmpro_discount_code_subscription_delays', $all_delays );

	// Determine the set expiration date value based on the type.
	$exp_type_a            = isset( $_REQUEST['expiration_date_type'] ) ? $_REQUEST['expiration_date_type'] : array();
	$exp_date_a            = isset( $_REQUEST['set_expiration_date'] ) ? $_REQUEST['set_expiration_date'] : array();
	$exp_type              = isset( $exp_type_a[ $key ] ) ? sanitize_text_field( $exp_type_a[ $key ] ) : 'none';

	$expiration_value   = '';
	$expiration_invalid = false;
	if ( $exp_type === 'date' && ! empty( $exp_date_a[ $key ] ) ) {
		$expiration_value = strtoupper( trim( sanitize_text_field( $exp_date_a[ $key ] ) ) );
		if ( ! pmpro_is_valid_date_pattern( $expiration_value ) ) {
			// Invalid pattern: keep the previous setting and report the error.
			$expiration_value   = '';
			$expiration_invalid = true;
			pmpro_payment_schedule_add_discount_code_error(
				sprintf(
					/* translators: %s: the membership level name. */
					__( 'The expiration date pattern for the %s level was invalid, so that setting was not updated.', 'paid-memberships-pro' ),
					pmpro_payment_schedule_get_level_name( $level_id )
				)
			);
		}
	}

	$option_key = 'pmprosed_' . intval( $level_id ) . '_' . intval( $code_id );
	if ( ! empty( $expiration_value ) ) {
		update_option( $option_key, $expiration_value, false );
	} elseif ( ! $expiration_invalid ) {
		delete_option( $option_key );
	}
}
add_action( 'pmpro_save_discount_code_level', 'pmpro_payment_schedule_save_discount_code_level', 10, 2 );

/**
 * Record a payment schedule error during a discount code save.
 *
 * adminpages/discountcodes.php merges these into its $level_errors list so the
 * admin stays on the edit form and sees the error.
 *
 * @since TBD
 *
 * @param string $error The error message to record.
 */
function pmpro_payment_schedule_add_discount_code_error( $error ) {
	global $pmpro_payment_schedule_dc_errors;
	if ( ! is_array( $pmpro_payment_schedule_dc_errors ) ) {
		$pmpro_payment_schedule_dc_errors = array();
	}
	$pmpro_payment_schedule_dc_errors[] = $error;
}

/**
 * Get a level name for error messages.
 *
 * @since TBD
 *
 * @param int $level_id The membership level ID.
 * @return string The level name, or the ID if the level can't be loaded.
 */
function pmpro_payment_schedule_get_level_name( $level_id ) {
	$level = pmpro_getLevel( $level_id );
	return ! empty( $level->name ) ? $level->name : (string) $level_id;
}

/**
 * Remove payment schedule options for levels that were unchecked from a discount code.
 *
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
add_action( 'pmpro_save_discount_code', 'pmpro_payment_schedule_cleanup_unchecked_levels' );

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

/**
 * AJAX handler powering the Payment Schedule Preview on the Edit Level page.
 *
 * The schedule is computed server-side with the same date engine used at
 * checkout so that the preview and real checkouts can never disagree, and all
 * strings are localized/formatted server-side (date_i18n, pmpro_formatPrice).
 *
 * @since TBD
 */
function pmpro_payment_schedule_preview_ajax() {
	// Security checks.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pmpro_payment_schedule_preview' ) ) {
		wp_send_json_error( __( 'Security check failed.', 'paid-memberships-pro' ) );
	}
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_membershiplevels' ) ) {
		wp_send_json_error( __( 'You do not have permission to do that.', 'paid-memberships-pro' ) );
	}

	// Gather and sanitize inputs.
	$checkout_date = isset( $_POST['checkout_date'] ) ? sanitize_text_field( $_POST['checkout_date'] ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $checkout_date ) || ! strtotime( $checkout_date ) ) {
		$checkout_date = wp_date( 'Y-m-d' );
	}
	$checkout_ts = strtotime( $checkout_date );

	$recurring       = ! empty( $_POST['recurring'] );
	$has_expiration  = ! empty( $_POST['expiration'] );
	$custom_trial    = ! empty( $_POST['custom_trial'] );
	$initial_payment = isset( $_POST['initial_payment'] ) ? floatval( $_POST['initial_payment'] ) : 0;
	$billing_amount  = isset( $_POST['billing_amount'] ) ? floatval( $_POST['billing_amount'] ) : 0;
	$cycle_number    = isset( $_POST['cycle_number'] ) ? max( 1, intval( $_POST['cycle_number'] ) ) : 1;
	$cycle_period    = isset( $_POST['cycle_period'] ) && in_array( $_POST['cycle_period'], array( 'Day', 'Week', 'Month', 'Year' ), true ) ? $_POST['cycle_period'] : 'Month';
	$billing_limit   = isset( $_POST['billing_limit'] ) ? intval( $_POST['billing_limit'] ) : 0;

	$delay_type = isset( $_POST['delay_type'] ) && in_array( $_POST['delay_type'], array( 'none', 'days', 'date' ), true ) ? $_POST['delay_type'] : 'none';
	$delay_days = isset( $_POST['delay_days'] ) ? intval( $_POST['delay_days'] ) : 0;
	$delay_date = isset( $_POST['delay_date'] ) ? sanitize_text_field( $_POST['delay_date'] ) : '';

	$expiration_type     = isset( $_POST['expiration_type'] ) && 'date' === $_POST['expiration_type'] ? 'date' : 'none';
	$expiration_number   = isset( $_POST['expiration_number'] ) ? intval( $_POST['expiration_number'] ) : 0;
	$expiration_period   = isset( $_POST['expiration_period'] ) && in_array( $_POST['expiration_period'], array( 'Hour', 'Day', 'Week', 'Month', 'Year' ), true ) ? $_POST['expiration_period'] : 'Month';
	$set_expiration_date = isset( $_POST['set_expiration_date'] ) ? sanitize_text_field( $_POST['set_expiration_date'] ) : '';

	if ( ! $recurring ) {
		wp_send_json_success( array( 'empty' => __( 'Enable recurring billing to see a payment schedule.', 'paid-memberships-pro' ) ) );
	}

	$notes = array();

	// 1. Determine the expiration date, if any.
	$expiration_ts = null;
	if ( $has_expiration ) {
		if ( 'date' === $expiration_type && '' !== $set_expiration_date ) {
			$resolved = pmpro_payment_schedule_resolve_expiration_date( $set_expiration_date, $checkout_ts );
			if ( empty( $resolved ) ) {
				$notes[] = __( 'The expiration date pattern is invalid and will be ignored.', 'paid-memberships-pro' );
			} elseif ( strtotime( $resolved ) <= $checkout_ts ) {
				// A fixed date in the past blocks checkout entirely; mirror that in the preview.
				wp_send_json_success( array( 'empty' => __( 'The expiration date is in the past. This level cannot be purchased until the expiration date is updated.', 'paid-memberships-pro' ) ) );
			} else {
				$expiration_ts = strtotime( $resolved );
			}
		} elseif ( 'date' !== $expiration_type && $expiration_number > 0 ) {
			$expiration_ts = strtotime( '+ ' . $expiration_number . ' ' . $expiration_period, $checkout_ts );
		}
	}

	// 2. Determine the first recurring payment date.
	$first_ts = null;
	if ( $billing_amount > 0 ) {
		if ( 'days' === $delay_type && $delay_days > 0 ) {
			$first_ts = strtotime( '+ ' . $delay_days . ' Days', $checkout_ts );
		} elseif ( 'date' === $delay_type && '' !== $delay_date ) {
			$resolved = pmpro_convert_date_pattern( $delay_date, $checkout_ts );
			if ( empty( $resolved ) ) {
				$notes[]  = __( 'The first recurring payment date pattern is invalid and will be ignored.', 'paid-memberships-pro' );
				$first_ts = strtotime( '+ ' . $cycle_number . ' ' . $cycle_period, $checkout_ts );
			} else {
				// Checkout clamps a past start date up to the checkout date.
				$first_ts = max( strtotime( $resolved ), $checkout_ts );
			}
		} else {
			$first_ts = strtotime( '+ ' . $cycle_number . ' ' . $cycle_period, $checkout_ts );
		}
	}

	// 3. Generate the payment dates (bounded).
	$payments = array();
	if ( ! empty( $first_ts ) ) {
		$safe_max = $billing_limit > 0 ? $billing_limit : 100;
		for ( $i = 0; $i < $safe_max; $i++ ) {
			$pay_ts = 0 === $i ? $first_ts : strtotime( '+ ' . ( $cycle_number * $i ) . ' ' . $cycle_period, $first_ts );
			if ( empty( $pay_ts ) || ( $expiration_ts && $pay_ts >= $expiration_ts ) ) {
				break;
			}
			$payments[] = $pay_ts;
		}
	}
	$total_payments    = count( $payments );
	$hit_billing_limit = $billing_limit > 0 && $total_payments === $billing_limit;
	// With no billing limit and no expiration the schedule is open-ended; the
	// generated list is truncated, so there is no real "last payment" to show.
	$open_ended = $billing_limit < 1 && empty( $expiration_ts );

	// 4. Build the display events. All strings are plain text; JS escapes them.
	$date_format    = get_option( 'date_format' );
	$format_price   = function( $amount ) {
		return html_entity_decode( wp_strip_all_tags( pmpro_formatPrice( $amount ) ), ENT_QUOTES );
	};
	$last_label     = __( 'Last Payment', 'paid-memberships-pro' );
	$last_subtitle  = __( 'Billing stops, membership continues', 'paid-memberships-pro' );

	$events   = array();
	$events[] = array(
		'type'   => 'initial',
		'label'  => __( 'Checkout', 'paid-memberships-pro' ),
		'amount' => $format_price( $initial_payment ),
		'date'   => date_i18n( $date_format, $checkout_ts ),
	);

	if ( $total_payments > 0 ) {
		$max_inline   = 5;
		$inline_count = min( $total_payments, $max_inline );
		if ( ! $open_ended && $total_payments === $max_inline + 1 ) {
			$inline_count = $total_payments;
		}

		for ( $i = 0; $i < $inline_count; $i++ ) {
			$is_last  = ( $i === $total_payments - 1 );
			$events[] = array(
				'type'     => ( $is_last && $hit_billing_limit ) ? 'last_payment' : 'recurring',
				'label'    => ( $is_last && $hit_billing_limit ) ? $last_label : '#' . ( $i + 1 ),
				'amount'   => $format_price( $billing_amount ),
				'date'     => date_i18n( $date_format, $payments[ $i ] ),
				'subtitle' => ( $is_last && $hit_billing_limit ) ? $last_subtitle : '',
			);
		}

		if ( $open_ended ) {
			// Open-ended subscription: no meaningful final payment to display.
			$events[] = array( 'type' => 'continuation' );
		} elseif ( $total_payments > $inline_count ) {
			$events[] = array( 'type' => 'continuation' );
			$events[] = array(
				'type'     => $hit_billing_limit ? 'last_payment' : 'recurring',
				'label'    => $hit_billing_limit ? $last_label : '#' . $total_payments,
				'amount'   => $format_price( $billing_amount ),
				'date'     => date_i18n( $date_format, $payments[ $total_payments - 1 ] ),
				'subtitle' => $hit_billing_limit ? $last_subtitle : '',
			);
		}
	}

	if ( ! empty( $expiration_ts ) ) {
		$events[] = array(
			'type'     => 'expiration',
			'label'    => __( 'Membership Ends', 'paid-memberships-pro' ),
			'date'     => date_i18n( $date_format, $expiration_ts ),
			'subtitle' => __( 'Access revoked, billing cancelled', 'paid-memberships-pro' ),
		);
	}

	$footnotes = array();
	if ( $custom_trial ) {
		$footnotes[] = __( 'Note: Custom trial pricing is active. The first payment amounts shown above may differ at checkout.', 'paid-memberships-pro' );
	}

	wp_send_json_success(
		array(
			'events'    => $events,
			'notes'     => $notes,
			'footnotes' => $footnotes,
		)
	);
}
add_action( 'wp_ajax_pmpro_payment_schedule_preview', 'pmpro_payment_schedule_preview_ajax' );
