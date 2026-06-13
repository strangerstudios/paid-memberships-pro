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
		return $day . 'th';
	}
	switch ( $day % 10 ) {
		case 1: return $day . 'st';
		case 2: return $day . 'nd';
		case 3: return $day . 'rd';
		default: return $day . 'th';
	}
}

/**
 * Convert a date pattern string to an actual date.
 *
 * Supports patterns like:
 * - "Y-01-01"  => January 1st of current/next year (whichever is next)
 * - "Y2-06-15" => June 15th, 2 years from now
 * - "Y-M-01"   => 1st of current/next month
 * - "2025-12-31" => Fixed date
 * - "Y" alone in year means "Y1" (next occurrence)
 * - "M" alone in month means "M1" (next occurrence)
 *
 * @since TBD
 *
 * @param string $date       The date pattern string.
 * @param int    $current_date Optional. Unix timestamp to use as "today". Defaults to current_time('timestamp').
 * @return string Date in Y-m-d format, or with T0:0:0 appended.
 */
function pmpro_convert_date_pattern( $date, $current_date = null ) {
	// Handle lower-cased y/m values.
	$set_date = strtoupper( $date );

	// Change "Y-" and "M-" to "Y1-" and "M1-".
	$set_date = preg_replace( '/Y-/', 'Y1-', $set_date );
	$set_date = preg_replace( '/M-/', 'M1-', $set_date );

	// Get number of months and years to add.
	$add_months = 0;
	$add_years  = 0;
	$m_pos      = stripos( $set_date, 'M' );
	$y_pos      = stripos( $set_date, 'Y' );
	if ( $m_pos !== false ) {
		$add_months = min( intval( pmpro_getMatches( '/M([0-9]*)/', $set_date, true ) ), 24 );
	}
	if ( $y_pos !== false ) {
		$add_years = min( intval( pmpro_getMatches( '/Y([0-9]*)/', $set_date, true ) ), 10 );
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
	$temp_d = $set_d;

	// Add months.
	if ( ! empty( $add_months ) ) {
		for ( $i = 0; $i < $add_months; $i++ ) {
			// If "M1", only add months if current date of month has already passed.
			if ( 0 == $i ) {
				if ( $temp_d < $current_d ) {
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
			// If "Y1", only add years if current date has already passed.
			if ( 0 == $i ) {
				$temp_date = strtotime( date( "{$temp_y}-{$temp_m}-{$temp_d}" ) );
				if ( $temp_date < $current_date ) {
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
		if ( ! empty( $all_delays[ $code_id ][ $level_id ] ) ) {
			return $all_delays[ $code_id ][ $level_id ];
		}
	}

	return get_option( 'pmpro_subscription_delay_' . intval( $level_id ), '' );
}

/**
 * Get the set expiration date pattern for a level, optionally with a discount code.
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
		$code_date = get_option( 'pmprosed_' . intval( $level_id ) . '_' . intval( $code_id ), '' );
		if ( ! empty( $code_date ) ) {
			return $code_date;
		}
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
		$level->profile_start_date = pmpro_convert_date_pattern( $subscription_delay ) . 'T0:0:0';
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
 * Apply set expiration date to the checkout level.
 *
 * @since TBD
 *
 * @param object   $level            The PMPro Level object at checkout.
 * @param int|null $discount_code_id Optional discount code ID.
 * @return object|null The modified level object, or null if expired.
 */
function pmpro_apply_set_expiration_date_at_checkout( $level, $discount_code_id = null ) {
	global $wpdb;

	if ( empty( $level ) || empty( $level->id ) ) {
		return $level;
	}

	// Check for discount code.
	if ( empty( $discount_code_id ) && ! empty( $_REQUEST['discount_code'] ) ) {
		$discount_code = preg_replace( '/[^A-Za-z0-9\-]/', '', $_REQUEST['discount_code'] );
		if ( ! empty( $discount_code ) ) {
			$discount_code_id = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $discount_code ) . "' LIMIT 1" );
		}
	}

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id, $discount_code_id );
	if ( empty( $set_expiration_date ) ) {
		return $level;
	}

	// Check for Y pattern usage.
	$used_y = ( strpos( strtoupper( $set_expiration_date ), 'Y' ) !== false );

	// Convert the date pattern.
	$resolved_date = pmpro_convert_date_pattern( $set_expiration_date );

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
add_filter( 'pmpro_checkout_level', 'pmpro_apply_set_expiration_date_at_checkout', 6 );
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

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id );
	if ( ! empty( $set_expiration_date ) ) {
		$enddate = pmpro_convert_date_pattern( $set_expiration_date );
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
function pmpro_subscription_delay_cost_text( $cost, $level ) {
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
	$labels   = array( 'Year', 'Years', 'Month', 'Months', 'Week', 'Weeks', 'Day', 'Days', 'payments' );
	$patterns = array(
		'%s.'          => '%s',
		'%s</strong>.' => '%s</strong>',
	);

	$find = $replace = array();
	foreach ( $labels as $label ) {
		foreach ( $patterns as $pattern_find => $pattern_replace ) {
			$find[]    = sprintf( $pattern_find, __( $label, 'paid-memberships-pro' ) );
			$replace[] = sprintf( $pattern_replace, __( $label, 'paid-memberships-pro' ) );
		}
	}

	if ( is_numeric( $subscription_delay ) ) {
		$cost  = str_replace( $find, $replace, $cost );
		$cost .= sprintf(
			/* translators: %d: number of days */
			__( ' after your <strong>%d</strong> day trial.', 'paid-memberships-pro' ),
			intval( $subscription_delay )
		);
	} else {
		$resolved_date = pmpro_convert_date_pattern( $subscription_delay );
		$cost  = str_replace( $find, $replace, $cost );
		$cost .= ' ' . __( 'starting', 'paid-memberships-pro' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $resolved_date, current_time( 'timestamp' ) ) ) . '.';
	}

	return $cost;
}
add_filter( 'pmpro_level_cost_text', 'pmpro_subscription_delay_cost_text', 10, 2 );

/**
 * Update the level expiration text to reflect set expiration date.
 *
 * @since TBD
 */
function pmpro_set_expiration_date_text( $expiration_text, $level ) {
	if ( empty( $level ) || empty( $level->id ) ) {
		return $expiration_text;
	}

	// Check for discount code.
	$discount_code_id = null;
	if ( ! empty( $_REQUEST['pmpro_discount_code'] ) ) {
		$discount_code = new PMPro_Discount_Code( $_REQUEST['pmpro_discount_code'] );
		$discount_code_id = ! empty( $discount_code->id ) ? $discount_code->id : null;
	} elseif ( ! empty( $_REQUEST['code'] ) ) {
		$discount_code = new PMPro_Discount_Code( $_REQUEST['code'] );
		$discount_code_id = ! empty( $discount_code->id ) ? $discount_code->id : null;
	}

	$set_expiration_date = pmpro_get_set_expiration_date( $level->id, $discount_code_id );
	if ( ! empty( $set_expiration_date ) ) {
		$resolved_date   = pmpro_convert_date_pattern( $set_expiration_date );
		$expiration_text = esc_html__( 'Membership expires on', 'paid-memberships-pro' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $resolved_date, current_time( 'timestamp' ) ) ) . '.';
	}

	return $expiration_text;
}
add_filter( 'pmpro_level_expiration_text', 'pmpro_set_expiration_date_text', 10, 2 );

/**
 * Handle Authorize.net subscription delay compatibility.
 *
 * @since TBD
 */
function pmpro_subscription_delay_subscribe_order( $order, $gateway ) {
	if ( $order->gateway !== 'authorizenet' ) {
		return $order;
	}

	$code_id = null;
	if ( ! empty( $order->discount_code ) ) {
		global $wpdb;
		$code_id = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $order->discount_code ) . "' LIMIT 1" );
	}

	$subscription_delay = pmpro_get_subscription_delay( $order->membership_id, $code_id );

	if ( ! empty( $subscription_delay ) && $order->TrialBillingCycles == 1 ) {
		$order->TrialBillingCycles = 0;
	}

	return $order;
}
add_filter( 'pmpro_subscribe_order', 'pmpro_subscription_delay_subscribe_order', 10, 2 );

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
		if ( $resolved_date < date( 'Y-m-d' ) ) {
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
 */
function pmpro_payment_schedule_render_date_builder( $month_names, $hidden_name, $existing_value ) {
	?>
	<div class="pmpro_date_pattern_builder" data-existing-value="<?php echo esc_attr( $existing_value ); ?>">
		<select class="pmpro_date_pattern_mode" onchange="pmpro_date_mode_changed(this);">
			<option value=""><?php esc_html_e( 'Choose...', 'paid-memberships-pro' ); ?></option>
			<option value="monthly"><?php esc_html_e( 'The same day each month', 'paid-memberships-pro' ); ?></option>
			<option value="yearly"><?php esc_html_e( 'The same date each year', 'paid-memberships-pro' ); ?></option>
			<option value="custom"><?php esc_html_e( 'Custom pattern', 'paid-memberships-pro' ); ?></option>
		</select>
		<span class="pmpro_date_builder_monthly" style="display:none;">
			<?php esc_html_e( 'on the', 'paid-memberships-pro' ); ?>
			<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="pmpro_date_builder_yearly" style="display:none;">
			<?php esc_html_e( 'on', 'paid-memberships-pro' ); ?>
			<select class="pmpro_date_builder_month" onchange="pmpro_assemble_date_pattern(this);">
				<?php foreach ( $month_names as $val => $name ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="pmpro_date_builder_custom" style="display:none;">
			<input type="text" class="pmpro_date_pattern_input" placeholder="<?php esc_attr_e( 'e.g. Y-01-01', 'paid-memberships-pro' ); ?>"
				value="<?php echo esc_attr( $existing_value ); ?>"
				oninput="jQuery(this).closest('.pmpro_date_pattern_builder').find('.pmpro_date_pattern_value').val(this.value);" />
			<p class="description"><?php esc_html_e( 'Y = current/next year, M = current/next month.', 'paid-memberships-pro' ); ?></p>
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

	$delay_value = '';
	if ( $delay_type === 'days' && isset( $delay_days_a[ $key ] ) && intval( $delay_days_a[ $key ] ) > 0 ) {
		$delay_value = intval( $delay_days_a[ $key ] );
	} elseif ( $delay_type === 'date' && ! empty( $delay_date_a[ $key ] ) ) {
		$delay_value = sanitize_text_field( $delay_date_a[ $key ] );
	}

	$all_delays = get_option( 'pmpro_discount_code_subscription_delays', array() );
	if ( ! empty( $delay_value ) ) {
		$all_delays[ $code_id ][ $level_id ] = $delay_value;
	} else {
		unset( $all_delays[ $code_id ][ $level_id ] );
	}
	update_option( 'pmpro_discount_code_subscription_delays', $all_delays );

	// Determine the set expiration date value based on the type.
	$exp_type_a            = isset( $_REQUEST['expiration_date_type'] ) ? $_REQUEST['expiration_date_type'] : array();
	$exp_date_a            = isset( $_REQUEST['set_expiration_date'] ) ? $_REQUEST['set_expiration_date'] : array();
	$exp_type              = isset( $exp_type_a[ $key ] ) ? sanitize_text_field( $exp_type_a[ $key ] ) : 'none';

	$expiration_value = '';
	if ( $exp_type === 'date' && ! empty( $exp_date_a[ $key ] ) ) {
		$expiration_value = sanitize_text_field( $exp_date_a[ $key ] );
	}

	$option_key = 'pmprosed_' . intval( $level_id ) . '_' . intval( $code_id );
	if ( ! empty( $expiration_value ) ) {
		update_option( $option_key, $expiration_value, false );
	} else {
		delete_option( $option_key );
	}
}
add_action( 'pmpro_save_discount_code_level', 'pmpro_payment_schedule_save_discount_code_level', 10, 2 );
