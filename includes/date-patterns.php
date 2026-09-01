<?php
/**
 * Date pattern utilities for subscription delays and set expiration dates.
 *
 * A date pattern is "{year}-{month}-{day}" where the year can be a Y token
 * (Y, Y2-Y99) meaning the current/next year and the month can be an M token
 * (M, M2-M99) meaning the current/next month, e.g. "Y-M-15" for the 15th of
 * each month or "Y-12-31" for December 31st each year. Previously provided by
 * the Subscription Delays and Set Expiration Dates Add Ons, which share these
 * option formats.
 *
 * @since TBD
 */

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
	 * Carried over from the retired Subscription Delays Add On.
	 *
	 * @param int $current_date Unix timestamp of the current date.
	 */
	$current_date = apply_filters( 'pmprosd_current_date', $current_date );

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
 * directly and runs the filters that the retired Set Expiration Dates Add On
 * ran inside pmprosed_fixDate().
 *
 * @since TBD
 *
 * @param string $set_expiration_date The expiration date pattern.
 * @param int    $current_date        Optional. Unix timestamp to use as "today".
 * @return string|false Date in Y-m-d format, or false if the pattern is invalid.
 */
function pmpro_resolve_expiration_date_pattern( $set_expiration_date, $current_date = null ) {
	$set_expiration_date = strtoupper( trim( (string) $set_expiration_date ) );

	/**
	 * Filter the raw expiration date pattern before it is resolved.
	 *
	 * Carried over from the retired Set Expiration Dates Add On.
	 *
	 * @param string $set_expiration_date The uppercased expiration date pattern.
	 */
	$set_expiration_date = apply_filters( 'pmprosed_expiration_date_raw', $set_expiration_date );

	$resolved_date = pmpro_convert_date_pattern( $set_expiration_date, $current_date );
	if ( empty( $resolved_date ) ) {
		return false;
	}

	/**
	 * Filter the resolved expiration date.
	 *
	 * Carried over from the retired Set Expiration Dates Add On.
	 *
	 * @param string $resolved_date The resolved date in Y-m-d format.
	 */
	return apply_filters( 'pmprosed_expiration_date', $resolved_date );
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

	return $subscription_delay;
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

	return $set_expiration_date;
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
function pmpro_render_date_pattern_builder( $field_prefix, $existing_value ) {
	$month_names = array(
		'01' => __( 'January', 'paid-memberships-pro' ), '02' => __( 'February', 'paid-memberships-pro' ),
		'03' => __( 'March', 'paid-memberships-pro' ), '04' => __( 'April', 'paid-memberships-pro' ),
		'05' => __( 'May', 'paid-memberships-pro' ), '06' => __( 'June', 'paid-memberships-pro' ),
		'07' => __( 'July', 'paid-memberships-pro' ), '08' => __( 'August', 'paid-memberships-pro' ),
		'09' => __( 'September', 'paid-memberships-pro' ), '10' => __( 'October', 'paid-memberships-pro' ),
		'11' => __( 'November', 'paid-memberships-pro' ), '12' => __( 'December', 'paid-memberships-pro' ),
	);

	// Parse the stored pattern into the builder's initial state. Anything that
	// isn't a valid monthly or yearly pattern - including unparseable values -
	// belongs in the custom box exactly as stored, so a resave can't change it.
	$mode           = '';
	$monthly_day    = '01';
	$yearly_month   = '01';
	$yearly_day     = '01';
	$custom_pattern = '';
	$existing_value = strtoupper( trim( (string) $existing_value ) );
	if ( pmpro_is_valid_date_pattern( $existing_value ) && preg_match( '/^Y-M-(\d{1,2})$/', $existing_value, $matches ) ) {
		$mode        = 'monthly';
		$monthly_day = str_pad( intval( $matches[1] ), 2, '0', STR_PAD_LEFT );
	} elseif ( pmpro_is_valid_date_pattern( $existing_value ) && preg_match( '/^Y-(\d{1,2})-(\d{1,2})$/', $existing_value, $matches ) ) {
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
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>" <?php selected( $monthly_day, str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>><?php echo esc_html( $d ); ?></option>
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
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>" <?php selected( $yearly_day, str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>><?php echo esc_html( $d ); ?></option>
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
