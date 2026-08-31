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

	// Get number of months and years to add.
	$add_months = 0;
	$add_years  = 0;
	$m_pos      = stripos( $set_date, 'M' );
	$y_pos      = stripos( $set_date, 'Y' );
	if ( $m_pos !== false ) {
		$add_months = min( intval( pmpro_getMatches( '/M([0-9]*)/', $set_date, true ) ), 120 );
	}
	if ( $y_pos !== false ) {
		$add_years = min( intval( pmpro_getMatches( '/Y([0-9]*)/', $set_date, true ) ), 100 );
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

	// Get current date parts.
	$current_y = intval( date( 'Y', $current_date ) );
	$current_m = intval( date( 'm', $current_date ) );
	$current_d = intval( date( 'd', $current_date ) );

	// Get set date parts.
	$date_parts = explode( '-', $set_date );
	$set_y      = intval( $date_parts[0] );
	$set_m      = isset( $date_parts[1] ) ? intval( $date_parts[1] ) : 1;
	$set_d      = isset( $date_parts[2] ) ? intval( $date_parts[2] ) : 1;

	// Get temporary date parts.
	$temp_y = $set_y > 0 ? $set_y : $current_y;
	$temp_m = $set_m > 0 ? $set_m : $current_m;
	$temp_d = max( 1, min( $set_d, 31 ) );

	// Add months.
	if ( ! empty( $add_months ) ) {
		for ( $i = 0; $i < $add_months; $i++ ) {
			// If "M1", only add months if the day of the month has already passed.
			// A pattern that lands on today counts as passed so that the resolved
			// date is always in the future.
			if ( 0 == $i ) {
				if ( $temp_d <= $current_d ) {
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
			// If "Y1", only add years if the date has already passed. The comparison is
			// date-granular (midnight vs. midnight) so that a date landing on today does
			// not read as passed twice — once here and once in the month loop above —
			// which previously jumped monthly patterns a full year ahead.
			if ( 0 == $i ) {
				$temp_date = strtotime( date( "{$temp_y}-{$temp_m}-{$temp_d}" ) );
				if ( $temp_date <= strtotime( date( 'Y-m-d', $current_date ) ) ) {
					$temp_y++;
					$add_years--;
				}
			} else {
				$temp_y++;
			}
		}
	}

	// Pad dates if necessary.
	$temp_m = str_pad( $temp_m, 2, '0', STR_PAD_LEFT );
	$temp_d = str_pad( $temp_d, 2, '0', STR_PAD_LEFT );

	// Put it all together.
	$set_date = date( "{$temp_y}-{$temp_m}-{$temp_d}" );

	// Make sure we use the right day of the month for dates > 28.
	$dotm = pmpro_getMatches( '/\-([0-3][0-9]$)/', $set_date, true );
	if ( $temp_m == '02' && intval( $dotm ) > 28 || intval( $dotm ) > 30 ) {
		$set_date = date( 'Y-m-t', strtotime( substr( $set_date, 0, 8 ) . '01' ) );
	}

	return $set_date;
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
add_filter( 'pmpro_checkout_level', 'pmpro_apply_subscription_delay_at_checkout', 5 );

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
 * Apply set expiration date to the checkout level.
 *
 * @since TBD
 *
 * @param object   $level            The PMPro Level object at checkout.
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
	$resolved_date = pmpro_convert_date_pattern( $set_expiration_date );
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
// Priority 10 and registered after includes/filters.php so that this runs after
// pmpro_checkout_level_extend_memberships() and replaces (rather than adds to) the
// extended expiration on renewals, matching the retired Set Expiration Dates Add On.
add_filter( 'pmpro_checkout_level', 'pmpro_apply_set_expiration_date_at_checkout', 10 );
add_filter( 'pmpro_discount_code_level', 'pmpro_apply_set_expiration_date_at_checkout', 10, 2 );

/**
 * Force the set expiration date on the checkout end date (for IPN/webhook handlers).
 *
 * @since TBD
 */
function pmpro_force_set_expiration_enddate( $enddate, $user_id, $level, $startdate ) {
	if ( $enddate === 'NULL' || empty( $enddate ) || empty( $level ) || empty( $level->id ) ) {
		return $enddate;
	}

	// Respect a discount-code-specific expiration date if a code was used at checkout.
	$code_id = ! empty( $level->code_id ) ? intval( $level->code_id ) : null;

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id, $code_id );
	if ( ! empty( $set_expiration_date ) ) {
		$resolved_date = pmpro_convert_date_pattern( $set_expiration_date );
		if ( ! empty( $resolved_date ) ) {
			// End of day, matching the enddate format core computes at checkout, so
			// that the member keeps access through the expiration date itself.
			$enddate = $resolved_date . ' 23:59:59';
		}
	}

	return $enddate;
}
add_filter( 'pmpro_checkout_end_date', 'pmpro_force_set_expiration_enddate', 10, 4 );

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
 * Update the level cost text to reflect subscription delay.
 *
 * @since TBD
 */
function pmpro_subscription_delay_cost_text( $cost, $level = null ) {
	// Bail if we don't have a level object with an ID to inspect. Third-party
	// code occasionally fires the pmpro_level_cost_text filter without a level,
	// which would otherwise warn on $level->code_id / $level->id access.
	if ( ! is_object( $level ) || empty( $level->id ) ) {
		return $cost;
	}

	// Check for custom cost text first.
	if ( function_exists( 'pmpro_getCustomLevelCostText' ) ) {
		$custom_text = pmpro_getCustomLevelCostText( $level->id );
		if ( ! empty( $custom_text ) ) {
			return $cost;
		}
	}

	$code_id = ! empty( $level->code_id ) ? $level->code_id : null;
	$subscription_delay = pmpro_get_subscription_delay( $level->id, $code_id );

	if ( empty( $subscription_delay ) ) {
		return $cost;
	}

	// Strip trailing periods from billing frequency labels for grammar.
	$labels   = array(
		__( 'Year', 'paid-memberships-pro' ),
		__( 'Years', 'paid-memberships-pro' ),
		__( 'Month', 'paid-memberships-pro' ),
		__( 'Months', 'paid-memberships-pro' ),
		__( 'Week', 'paid-memberships-pro' ),
		__( 'Weeks', 'paid-memberships-pro' ),
		__( 'Day', 'paid-memberships-pro' ),
		__( 'Days', 'paid-memberships-pro' ),
		__( 'payments', 'paid-memberships-pro' ),
	);
	$patterns = array(
		'%s.'          => '%s',
		'%s</strong>.' => '%s</strong>',
	);

	$find = $replace = array();
	foreach ( $labels as $label ) {
		foreach ( $patterns as $pattern_find => $pattern_replace ) {
			$find[]    = sprintf( $pattern_find, $label );
			$replace[] = sprintf( $pattern_replace, $label );
		}
	}

	if ( is_numeric( $subscription_delay ) ) {
		$cost  = str_replace( $find, $replace, $cost );
		$cost .= ' ' . sprintf(
			/* translators: %d: number of days */
			__( 'after your <strong>%d</strong> day trial.', 'paid-memberships-pro' ),
			intval( $subscription_delay )
		);
	} else {
		$resolved_date = pmpro_convert_date_pattern( $subscription_delay );
		if ( empty( $resolved_date ) ) {
			return $cost;
		}
		$cost  = str_replace( $find, $replace, $cost );
		$cost .= ' ' . sprintf(
			/* translators: %s: the date of the first recurring payment. */
			__( 'starting %s.', 'paid-memberships-pro' ),
			date_i18n( get_option( 'date_format' ), strtotime( $resolved_date, current_time( 'timestamp' ) ) )
		);
	}

	return $cost;
}
add_filter( 'pmpro_level_cost_text', 'pmpro_subscription_delay_cost_text', 10, 2 );

/**
 * Update the level expiration text to reflect set expiration date.
 *
 * @since TBD
 */
function pmpro_set_expiration_date_text( $expiration_text, $level = null ) {
	if ( ! is_object( $level ) || empty( $level->id ) ) {
		return $expiration_text;
	}

	// Check for a discount code, preferring one already resolved on the level.
	if ( ! empty( $level->code_id ) ) {
		$discount_code_id = intval( $level->code_id );
	} else {
		$discount_code_id = pmpro_payment_schedule_get_checkout_code_id();
	}

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id, $discount_code_id );
	if ( ! empty( $set_expiration_date ) ) {
		$resolved_date = pmpro_convert_date_pattern( $set_expiration_date );
		if ( ! empty( $resolved_date ) ) {
			$expiration_text = sprintf(
				/* translators: %s: the date that the membership expires. */
				esc_html__( 'Membership expires on %s.', 'paid-memberships-pro' ),
				date_i18n( get_option( 'date_format' ), strtotime( $resolved_date, current_time( 'timestamp' ) ) )
			);
		}
	}

	return $expiration_text;
}
add_filter( 'pmpro_level_expiration_text', 'pmpro_set_expiration_date_text', 10, 2 );

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

		$resolved_date = pmpro_convert_date_pattern( $set_expiration_date );
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
			// Invalid pattern: keep the previous setting.
			$delay_value   = '';
			$delay_invalid = true;
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
			// Invalid pattern: keep the previous setting.
			$expiration_value   = '';
			$expiration_invalid = true;
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
