<?php
/**
 * Functions for resolving discount code pricing.
 *
 * Discount codes have a `discount_type` which determines how the final
 * checkout pricing is calculated for a level:
 * - `set_price`: The pmpro_discount_codes_levels row contains the final
 *   price schedule for the level. This is the legacy behavior.
 * - `percentage`: The level's own pricing is reduced by `discount_value` percent.
 * - `fixed`: The level's own pricing is reduced by `discount_value` in the site currency.
 *
 * For `percentage` and `fixed` codes, the `apply_to_initial` and `apply_to_recurring`
 * flags control which payments are discounted, and all non-price level settings
 * (billing cycle, billing limit, trial, expiration) are inherited from the level
 * itself. The pmpro_discount_codes_levels
 * row still marks which levels a code can be used for, and its pricing columns
 * hold a best-effort snapshot of the calculated prices for backwards compatibility
 * with code that reads that table directly, but the calculation at checkout is
 * always performed against the level's current pricing.
 *
 * @since TBD
 */

/**
 * Get the valid discount code types.
 *
 * @since TBD
 *
 * @return array Array of type slug => label.
 */
function pmpro_get_discount_code_types() {
	return array(
		'set_price'  => __( 'Set custom pricing per level', 'paid-memberships-pro' ),
		'percentage' => __( 'Percentage discount', 'paid-memberships-pro' ),
		'fixed'      => __( 'Fixed amount discount', 'paid-memberships-pro' ),
	);
}

/**
 * Get a discount code row by code string or ID.
 *
 * Codes can be fully numeric, so a matching code string wins over an ID match.
 *
 * @since TBD
 *
 * @param string|int|object $code The discount code string, ID, or a row object from pmpro_discount_codes.
 * @return object|null The discount code row or null if not found.
 */
function pmpro_get_discount_code( $code ) {
	global $wpdb;

	if ( is_object( $code ) ) {
		return isset( $code->id ) ? $code : null;
	}

	$code_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE code = %s LIMIT 1", $code ) );
	if ( empty( $code_row ) && is_numeric( $code ) ) {
		$code_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = %d LIMIT 1", $code ) );
	}

	return empty( $code_row ) ? null : $code_row;
}

/**
 * Apply a percentage or fixed discount to a single price.
 *
 * @since TBD
 *
 * @param float  $price          The price before the discount.
 * @param string $discount_type  The discount type, `percentage` or `fixed`.
 * @param float  $discount_value The discount value, e.g. 20 for 20% off or 10 for 10 off.
 * @return float The discounted price, rounded for the site currency and never below 0.
 */
function pmpro_calculate_discounted_price( $price, $discount_type, $discount_value ) {
	$price          = (float) $price;
	$discount_value = (float) $discount_value;

	if ( 'percentage' === $discount_type ) {
		$discounted = $price - ( $price * $discount_value / 100 );
	} elseif ( 'fixed' === $discount_type ) {
		$discounted = $price - $discount_value;
	} else {
		$discounted = $price;
	}

	return max( 0, pmpro_round_price( $discounted ) );
}

/**
 * Get the effective level object for a discount code.
 *
 * This is the single source of truth for what a discount code does to a
 * level's pricing. Checkout, the AJAX code application service, order
 * fallbacks, and gateway callbacks should all resolve pricing through here.
 *
 * Note: This function performs the calculation only. It does not validate
 * the code's dates or uses (see pmpro_checkDiscountCode()) and it does not
 * apply the `pmpro_discount_code_level` filter; callers apply that filter
 * so that existing filter contracts are preserved.
 *
 * @since TBD
 *
 * @param object|int        $level The base level object (a full pmpro_membership_levels row) or level ID.
 * @param string|int|object $code  The discount code string, ID, or row object.
 * @return object|null The effective level object with discounted pricing, or null if
 *                     the code does not apply to this level.
 */
function pmpro_get_discounted_level_for_code( $level, $code ) {
	global $wpdb;

	// Get the base level.
	if ( is_numeric( $level ) ) {
		$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1", $level ) );
	}
	if ( empty( $level ) || empty( $level->id ) ) {
		return null;
	}

	// Get the discount code.
	$code_row = pmpro_get_discount_code( $code );
	if ( empty( $code_row ) ) {
		return null;
	}

	// Make sure the code applies to this level.
	$code_level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = %d AND level_id = %d LIMIT 1", $code_row->id, $level->id ) );
	if ( empty( $code_level ) ) {
		return null;
	}

	$discount_type = ! empty( $code_row->discount_type ) && array_key_exists( $code_row->discount_type, pmpro_get_discount_code_types() ) ? $code_row->discount_type : 'set_price';

	if ( 'set_price' === $discount_type ) {
		// Legacy behavior: the code level row is the final price schedule.
		// Build the same object shape as the old JOIN of cl.* with the level's display fields.
		$discounted_level                    = clone $code_level;
		$discounted_level->id                = $level->id;
		$discounted_level->name              = $level->name;
		$discounted_level->description       = $level->description;
		$discounted_level->allow_signups     = $level->allow_signups;
		$discounted_level->confirmation      = $level->confirmation;
	} else {
		// Formula pricing: start from the level's own pricing and discount the amounts.
		// Cycle, billing limit, trial structure, and expiration are inherited from the level.
		$discounted_level           = clone $level;
		$discounted_level->level_id = $level->id; // Match the object shape of legacy codes_levels rows.

		if ( ! empty( $code_row->apply_to_initial ) ) {
			$discounted_level->initial_payment = pmpro_calculate_discounted_price( $level->initial_payment, $discount_type, $code_row->discount_value );
		}
		if ( ! empty( $code_row->apply_to_recurring ) && ! empty( $level->cycle_number ) ) {
			$discounted_level->billing_amount = pmpro_calculate_discounted_price( $level->billing_amount, $discount_type, $code_row->discount_value );
		}
	}

	$discounted_level->code_id       = $code_row->id;
	$discounted_level->discount_code = $code_row->code;

	/**
	 * Filter the effective level calculated for a discount code.
	 *
	 * Runs before the legacy `pmpro_discount_code_level` filter at the call sites,
	 * so existing extensions hooking that filter continue to receive final prices.
	 *
	 * @since TBD
	 *
	 * @param object $discounted_level The effective level object with discounted pricing.
	 * @param object $level            The base level object.
	 * @param object $code_row         The discount code row.
	 */
	return apply_filters( 'pmpro_get_discounted_level_for_code', $discounted_level, $level, $code_row );
}

/**
 * Build the pmpro_discount_codes_levels row values for a code and level.
 *
 * For `set_price` codes, the values come from the submitted pricing fields.
 * For `percentage` and `fixed` codes, the pricing columns are a snapshot of the
 * calculated prices at save time. The snapshot is only for backwards compatibility
 * with code reading the table directly; checkout always recalculates from the
 * level's current pricing. Snapshots are refreshed whenever the code or the
 * level is saved.
 *
 * @since TBD
 *
 * @param object $code_row The discount code row (with discount_type, discount_value, apply_to_* set).
 * @param object $level    The base level object.
 * @return array|null Associative array of codes_levels column values, or null if the level is missing.
 */
function pmpro_get_discount_code_level_snapshot( $code_row, $level ) {
	global $wpdb;

	if ( is_numeric( $level ) ) {
		$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1", $level ) );
	}
	if ( empty( $level ) ) {
		return null;
	}

	$discount_type  = ! empty( $code_row->discount_type ) ? $code_row->discount_type : 'set_price';
	$discount_value = ! empty( $code_row->discount_value ) ? $code_row->discount_value : 0;

	$initial_payment = ! empty( $code_row->apply_to_initial ) ? pmpro_calculate_discounted_price( $level->initial_payment, $discount_type, $discount_value ) : $level->initial_payment;
	$billing_amount  = ( ! empty( $code_row->apply_to_recurring ) && ! empty( $level->cycle_number ) ) ? pmpro_calculate_discounted_price( $level->billing_amount, $discount_type, $discount_value ) : $level->billing_amount;

	return array(
		'initial_payment'   => $initial_payment,
		'billing_amount'    => $billing_amount,
		'cycle_number'      => $level->cycle_number,
		'cycle_period'      => $level->cycle_period,
		'billing_limit'     => $level->billing_limit,
		'trial_amount'      => $level->trial_amount,
		'trial_limit'       => $level->trial_limit,
		'expiration_number' => $level->expiration_number,
		'expiration_period' => $level->expiration_period,
	);
}

/**
 * Refresh the codes_levels pricing snapshots for formula discount codes on a level.
 *
 * Runs when a membership level is saved so that percentage/fixed codes' snapshot
 * rows track the level's current pricing.
 *
 * @since TBD
 *
 * @param int $level_id The membership level ID that was saved.
 */
function pmpro_refresh_discount_code_level_snapshots( $level_id ) {
	global $wpdb;

	$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1", $level_id ) );
	if ( empty( $level ) ) {
		return;
	}

	$formula_codes = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT dc.* FROM $wpdb->pmpro_discount_codes dc
				INNER JOIN $wpdb->pmpro_discount_codes_levels cl ON cl.code_id = dc.id
			WHERE cl.level_id = %d AND dc.discount_type IN ( 'percentage', 'fixed' )",
			$level_id
		)
	);

	foreach ( $formula_codes as $code_row ) {
		$snapshot = pmpro_get_discount_code_level_snapshot( $code_row, $level );
		if ( ! empty( $snapshot ) ) {
			$wpdb->update(
				$wpdb->pmpro_discount_codes_levels,
				$snapshot,
				array(
					'code_id'  => $code_row->id,
					'level_id' => $level_id,
				)
			);
		}
	}
}
add_action( 'pmpro_save_membership_level', 'pmpro_refresh_discount_code_level_snapshots' );

/**
 * Get a short human-readable description of a discount code's rule.
 *
 * Used on the discount codes admin list table and edit screen.
 *
 * @since TBD
 *
 * @param object $code_row The discount code row.
 * @return string Description of the discount, or an empty string for set_price codes.
 */
function pmpro_get_discount_code_rule_text( $code_row ) {
	if ( empty( $code_row->discount_type ) || 'set_price' === $code_row->discount_type ) {
		return '';
	}

	if ( 'percentage' === $code_row->discount_type ) {
		// translators: %s is the percentage amount.
		$amount_text = sprintf( __( '%s%% off', 'paid-memberships-pro' ), pmpro_filter_price_for_text_field( $code_row->discount_value ) );
	} else {
		// translators: %s is the formatted price amount.
		$amount_text = sprintf( __( '%s off', 'paid-memberships-pro' ), pmpro_formatPrice( $code_row->discount_value ) );
	}

	$applies_to = array();
	if ( ! empty( $code_row->apply_to_initial ) ) {
		$applies_to[] = __( 'initial payment', 'paid-memberships-pro' );
	}
	if ( ! empty( $code_row->apply_to_recurring ) ) {
		$applies_to[] = __( 'recurring payments', 'paid-memberships-pro' );
	}

	if ( empty( $applies_to ) ) {
		return $amount_text;
	}

	// translators: %1$s is the discount amount (e.g. "20% off"), %2$s is the list of payments it applies to.
	return sprintf( __( '%1$s %2$s', 'paid-memberships-pro' ), $amount_text, implode( ', ', $applies_to ) );
}
