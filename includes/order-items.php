<?php
/**
 * Order items.
 *
 * An order item (a "line") describes one thing that a checkout order bought: the membership, a discount
 * code's saving, seats, a donation, a package and so on.
 *
 * - Lines describe the order. The order's subtotal, tax and total stay the source of truth for money, and
 *   gateways, tax and reports keep reading them.
 * - Checkout orders save their lines. Renewal orders and orders saved before lines existed read as one
 *   membership line built from the order. Nothing is backfilled.
 * - On an order with a membership level, the membership line is whatever is left of the order subtotal
 *   after the other lines, so code that changes the order's price is reflected on the membership line.
 * - Add Ons add lines at checkout with the pmpro_checkout_items filter. Lines under the membership line
 *   renew with it. Lines next to it are one-time.
 * - When no level is being purchased and Add Ons added lines, the checkout has no membership level. The
 *   buyer's memberships are not changed.
 *
 * @since TBD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- CRUD for the PMPro order items custom table, which has no WordPress API or object cache layer.

/*
 * Checkout lines.
 */

/**
 * Normalize a checkout line to the shape used by the pmpro_checkout_items filter.
 *
 * Line shape:
 * - key            (string)      Unique key for this line in the checkout. Defaults to "{type}-{object_id}".
 * - type           (string)      What the line is, such as 'seats' or 'donation'. 'membership' and 'discount' are core only.
 * - object_id      (int)         The level ID for the membership line. Defined by the Add On otherwise.
 * - name           (string)      Name shown at checkout, on the invoice and in emails.
 * - quantity       (int)         Quantity. At least 1.
 * - subtotal       (float)       Amount charged on this order for the whole quantity, before tax.
 * - billing_amount (float)       Amount added to each recurring payment. Always 0 for a one-time line.
 * - parent         (string|null) 'membership' for a line under the membership line, or null for a top-level line.
 *
 * @since TBD
 *
 * @param array|object $item The line to normalize.
 * @return array The normalized line.
 */
function pmpro_normalize_checkout_item( $item ) {
	$item = wp_parse_args(
		(array) $item,
		array(
			'key'            => '',
			'type'           => '',
			'object_id'      => 0,
			'name'           => '',
			'quantity'       => 1,
			'subtotal'       => 0,
			'billing_amount' => 0,
			'parent'         => null,
		)
	);

	$item['type']           = sanitize_key( $item['type'] );
	$item['object_id']      = (int) $item['object_id'];
	$item['name']           = mb_substr( sanitize_text_field( $item['name'] ), 0, 255 ); // The name column is varchar(255).
	$item['quantity']       = max( 1, (int) $item['quantity'] );
	$item['subtotal']       = pmpro_round_price( (float) $item['subtotal'] );
	$item['billing_amount'] = pmpro_round_price( (float) $item['billing_amount'] );
	$item['parent']         = ( null === $item['parent'] || '' === $item['parent'] ) ? null : (string) $item['parent'];
	$item['key']            = ( '' === (string) $item['key'] ) ? $item['type'] . '-' . $item['object_id'] : (string) $item['key'];

	return $item;
}

/**
 * Add up one amount of a list of lines.
 *
 * @since TBD
 *
 * @param array  $items Lines (arrays or objects).
 * @param string $field 'subtotal' or 'billing_amount'.
 * @return float The total.
 */
function pmpro_get_items_total( $items, $field = 'subtotal' ) {
	$total = 0;
	foreach ( (array) $items as $item ) {
		$item   = (array) $item;
		$total += isset( $item[ $field ] ) ? (float) $item[ $field ] : 0;
	}
	return pmpro_round_price( $total );
}

/**
 * Get the membership line and its discount line for a checkout level.
 *
 * When a discount code applies, the membership line shows the level's price before the code and a
 * discount line under it carries the negative difference, for the first payment and for each renewal.
 * A part of the price (initial or recurring) gets no discount amount when:
 * - the code does not lower it,
 * - the code is a set-price code that changes the level's billing structure (cycle, billing limit or trial), or
 * - a callback changed it after the code was applied, such as on the pmpro_checkout_level filter.
 * Then the membership line shows what is actually charged for that part.
 *
 * @since TBD
 *
 * @param object      $level      The checkout level after the pmpro_checkout_level filter.
 * @param object|null $code_level The level as priced by the discount code, from pmpro_get_discounted_level_for_code().
 * @return array Lines keyed by key: 'membership', and 'discount' when there is a discount amount.
 */
function pmpro_get_membership_checkout_items( $level, $code_level = null ) {
	$recurring          = pmpro_isLevelRecurring( $level );
	$initial_discount   = 0;
	$recurring_discount = 0;

	$code       = ( empty( $code_level->code_id ) || empty( $level->code_id ) ) ? null : pmpro_get_discount_code( $code_level->code_id );
	$base_level = empty( $code ) ? null : pmpro_getLevel( $level->id );
	if ( ! empty( $code ) && ! empty( $base_level ) ) {
		$discount_type = ( ! empty( $code->discount_type ) && array_key_exists( $code->discount_type, pmpro_get_discount_code_types() ) ) ? $code->discount_type : 'set_price';

		// Percentage and fixed codes keep the level's billing structure. Set-price codes must keep it too.
		$same_structure = true;
		if ( 'set_price' === $discount_type ) {
			$same_structure = (int) $code_level->cycle_number === (int) $base_level->cycle_number
				&& ( empty( $base_level->cycle_number ) || (string) $code_level->cycle_period === (string) $base_level->cycle_period )
				&& (int) $code_level->billing_limit === (int) $base_level->billing_limit
				&& pmpro_round_price( (float) $code_level->trial_amount ) === pmpro_round_price( (float) $base_level->trial_amount )
				&& (int) $code_level->trial_limit === (int) $base_level->trial_limit;
		}

		if ( $same_structure ) {
			// Only count a part that nothing changed after the code was applied.
			$code_initial = pmpro_round_price( (float) $code_level->initial_payment );
			if ( pmpro_round_price( (float) $level->initial_payment ) === $code_initial ) {
				$initial_discount = max( 0, pmpro_round_price( (float) $base_level->initial_payment - $code_initial ) );
			}

			$code_billing = pmpro_round_price( (float) $code_level->billing_amount );
			if ( $recurring && pmpro_round_price( (float) $level->billing_amount ) === $code_billing ) {
				$recurring_discount = max( 0, pmpro_round_price( (float) $base_level->billing_amount - $code_billing ) );
			}
		}
	}

	$items = array(
		'membership' => pmpro_normalize_checkout_item(
			array(
				'key'            => 'membership',
				'type'           => 'membership',
				'object_id'      => (int) $level->id,
				'name'           => $level->name,
				'subtotal'       => (float) $level->initial_payment + $initial_discount,
				'billing_amount' => $recurring ? (float) $level->billing_amount + $recurring_discount : 0,
			)
		),
	);

	if ( $initial_discount > 0 || $recurring_discount > 0 ) {
		$items['discount'] = pmpro_normalize_checkout_item(
			array(
				'key'            => 'discount',
				'type'           => 'discount',
				'object_id'      => (int) $code->id,
				/* translators: %s: discount code. */
				'name'           => sprintf( __( 'Discount code %s', 'paid-memberships-pro' ), $code->code ),
				'subtotal'       => - $initial_discount,
				'billing_amount' => - $recurring_discount,
				'parent'         => 'membership',
			)
		);
	}

	return $items;
}

/**
 * Build the lines being purchased at checkout.
 *
 * Core seeds the membership line (and its discount line) from the checkout level, then Add Ons add lines
 * with the pmpro_checkout_items filter. Add Ons can't change or remove core's lines.
 *
 * @since TBD
 *
 * @param object|null $level      The checkout level after the pmpro_checkout_level filter, or null when no level is being purchased.
 * @param object|null $code_level The level as priced by the discount code, if one applies.
 * @return array Normalized lines keyed by key. Core's lines come first.
 */
function pmpro_build_checkout_items( $level, $code_level = null ) {
	$core_items = empty( $level->id ) ? array() : pmpro_get_membership_checkout_items( $level, $code_level );

	/**
	 * Filter the lines being purchased at checkout.
	 *
	 * When a level is being purchased, $items holds core's 'membership' line (its object_id is the level ID)
	 * and, when a discount code lowers the price, a 'discount' line under it. Add Ons add their own lines.
	 * See pmpro_normalize_checkout_item() for the line shape.
	 *
	 * - A line with 'parent' set to 'membership' sits under the membership line and renews with it. If the
	 *   membership is one-time, the line is one-time too.
	 * - A line without a parent is one-time. Its billing_amount is ignored.
	 * - Amounts can't be negative. Discount codes only discount the membership line, but an Add On may read
	 *   the code to price its own lines.
	 *
	 * When no level is being purchased and this filter returns lines, the checkout has no membership level.
	 *
	 * This filter runs every time the checkout level is resolved and every time the lines of a checkout
	 * without a membership level are read, so callbacks must not have side effects or call
	 * pmpro_getLevelAtCheckout(). Read your inputs from the request and keep the parameters that start a
	 * purchase in the checkout URL's query string. Offsite gateways send the buyer back to that URL.
	 *
	 * @since TBD
	 *
	 * @param array       $items Lines keyed by key.
	 * @param object|null $level The checkout level, or null when no level is being purchased.
	 */
	$filtered = apply_filters( 'pmpro_checkout_items', $core_items, $level );

	$items = array();
	foreach ( (array) $filtered as $item ) {
		$item = pmpro_normalize_checkout_item( $item );
		if ( isset( $core_items[ $item['key'] ] ) ) {
			continue;
		}

		// Top-level lines are one-time. Lines under a one-time membership are one-time too.
		if ( null === $item['parent'] || empty( $core_items['membership']['billing_amount'] ) ) {
			$item['billing_amount'] = 0;
		}

		$items[ $item['key'] ] = $item;
	}

	return array_merge( $core_items, $items );
}

/**
 * Set up the checkout lines for a checkout level.
 *
 * Called at the end of pmpro_getLevelAtCheckout(), after the pmpro_checkout_level filter. When Add Ons
 * added lines, the level's initial_payment becomes the total of all lines and its billing_amount the
 * total of the recurring lines, so that every gateway charges the whole order. The lines are kept in the
 * level's checkout_items property, and the membership's own price in its pmpro_membership_price property
 * for the cost text.
 *
 * @since TBD
 *
 * @param object|null $level      The checkout level.
 * @param object|null $code_level The level as priced by the discount code, if one applies.
 * @return object|null The checkout level.
 */
function pmpro_set_up_checkout_items_for_level( $level, $code_level = null ) {
	if ( empty( $level->id ) ) {
		return $level;
	}

	// A filter may hand back a level object that already carries the lines. Start again from the membership's price.
	if ( ! empty( $level->pmpro_membership_price ) ) {
		$level->initial_payment = $level->pmpro_membership_price['initial_payment'];
		$level->billing_amount  = $level->pmpro_membership_price['billing_amount'];
		unset( $level->pmpro_membership_price );
	}

	$level->checkout_items = pmpro_build_checkout_items( $level, $code_level );

	// Core's lines add up to the level's price, so only Add On lines change what the level charges.
	if ( empty( array_diff_key( $level->checkout_items, array_flip( array( 'membership', 'discount' ) ) ) ) ) {
		return $level;
	}

	$level->pmpro_membership_price = array(
		'initial_payment' => $level->initial_payment,
		'billing_amount'  => $level->billing_amount,
	);
	$level->initial_payment = pmpro_get_items_total( $level->checkout_items, 'subtotal' );
	if ( pmpro_isLevelRecurring( $level ) ) {
		$level->billing_amount = pmpro_get_items_total( $level->checkout_items, 'billing_amount' );
	}

	return $level;
}

/**
 * Get the lines being purchased at checkout.
 *
 * A level checkout's lines are kept on the level by pmpro_getLevelAtCheckout(). A checkout without a
 * membership level has no level to keep them on, so its lines are built again.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level, or null (or a level with no ID) when no level is being purchased.
 * @return array Normalized lines keyed by key.
 */
function pmpro_get_checkout_items( $level ) {
	if ( empty( $level->id ) ) {
		return pmpro_build_checkout_items( null );
	}
	return empty( $level->checkout_items ) ? array() : $level->checkout_items;
}

/**
 * Check whether a checkout has no membership level.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level.
 * @return bool True if no level is being purchased and Add Ons added lines to purchase.
 */
function pmpro_is_membershipless_checkout( $level ) {
	return empty( $level->id ) && ! empty( pmpro_build_checkout_items( null ) );
}

/**
 * Check the checkout lines when the checkout form is submitted, before the user is created.
 *
 * @since TBD
 *
 * @param array $items The checkout lines.
 * @return bool False if the lines can't be purchased. An error message is set.
 */
function pmpro_check_checkout_items( $items ) {
	if ( empty( $items ) ) {
		return true;
	}

	foreach ( $items as $key => $item ) {
		// Core's lines were checked when they were built.
		if ( in_array( $key, array( 'membership', 'discount' ), true ) ) {
			continue;
		}

		// Only core makes membership and discount lines, and only core lines can be negative.
		if ( in_array( $item['type'], array( 'membership', 'discount' ), true ) || $item['subtotal'] < 0 || $item['billing_amount'] < 0 ) {
			pmpro_setMessage( __( 'An item in this checkout has an invalid price. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
			return false;
		}

		// A line under the membership needs a membership in this checkout.
		if ( null !== $item['parent'] && ( 'membership' !== $item['parent'] || ! isset( $items['membership'] ) ) ) {
			pmpro_setMessage( __( 'An item in this checkout requires a membership that is not being purchased.', 'paid-memberships-pro' ), 'pmpro_error' );
			return false;
		}
	}

	// The rest is only for checkouts without a membership level.
	if ( isset( $items['membership'] ) ) {
		return true;
	}

	if ( pmpro_get_items_total( $items, 'subtotal' ) <= 0 ) {
		pmpro_setMessage( __( 'There is nothing to purchase.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	return true;
}
/**
 * Get the checkout URL with the current request's query string.
 *
 * Used for the login link and the offsite cancel URL of a checkout without a membership level, so the
 * buyer comes back to the same lines.
 *
 * @since TBD
 *
 * @return string
 */
function pmpro_get_membershipless_checkout_url() {
	$args = map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, rebuilds the checkout URL.
	$args = array_combine( array_map( 'sanitize_key', array_keys( $args ) ), $args );
	unset( $args['pmpro_order'], $args['submit-checkout'], $args['pmpro_checkout_nonce'] );
	return add_query_arg( urlencode_deep( $args ), pmpro_url( 'checkout' ) );
}

/**
 * Display the checkout lines with the total due today.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level.
 */
function pmpro_display_checkout_items( $level ) {
	$items = pmpro_get_checkout_items( $level );
	if ( empty( $items ) ) {
		return;
	}

	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout_items' ) ); ?>">
		<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ); ?>">
			<?php foreach ( $items as $item ) { ?>
				<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item pmpro_checkout_item pmpro_checkout_item-' . $item['type'] ) ); ?>">
					<?php echo esc_html( pmpro_get_order_item_label( (object) $item ) ); ?>:
					<strong><?php echo esc_html( pmpro_format_order_item_price( $item['subtotal'] ) ); ?></strong>
					<?php
					if ( 0 != $item['billing_amount'] && ! empty( $level->cycle_number ) ) {
						echo ' ' . esc_html( pmpro_get_checkout_items_recurring_text( $item['billing_amount'], $level ) );
					}
					?>
				</li>
			<?php } ?>
		</ul>
		<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout_items_total' ) ); ?>">
			<strong>
				<?php
				/* translators: %s: total due today. */
				echo esc_html( sprintf( __( 'Total today: %s', 'paid-memberships-pro' ), pmpro_format_order_item_price( pmpro_get_items_total( $items, 'subtotal' ) ) ) );
				?>
			</strong>
			<?php
			$billing_total = pmpro_get_items_total( $items, 'billing_amount' );
			if ( $billing_total > 0 && ! empty( $level->cycle_number ) ) {
				echo ' ' . esc_html( pmpro_get_checkout_items_recurring_text( $billing_total, $level ) );
			}
			?>
		</p>
	</div>
	<?php
}

/**
 * Get the "then $X per Month" text for a recurring amount at checkout.
 *
 * @since TBD
 *
 * @param float  $amount The recurring amount.
 * @param object $level  The level, for its billing cycle.
 * @return string
 */
function pmpro_get_checkout_items_recurring_text( $amount, $level ) {
	$price = pmpro_format_order_item_price( $amount );
	if ( 1 == $level->cycle_number ) {
		/* translators: 1: price, 2: billing period. */
		return sprintf( __( 'then %1$s per %2$s', 'paid-memberships-pro' ), $price, pmpro_translate_billing_period( $level->cycle_period ) );
	}
	/* translators: 1: price, 2: number of periods, 3: billing period. */
	return sprintf( __( 'then %1$s every %2$d %3$s', 'paid-memberships-pro' ), $price, $level->cycle_number, pmpro_translate_billing_period( $level->cycle_period, $level->cycle_number ) );
}

/*
 * Display.
 */

/**
 * Format a line amount, with a minus sign in front of negative amounts, such as "-$5.00".
 *
 * @since TBD
 *
 * @param float $amount The amount.
 * @return string The formatted amount, as plain text.
 */
function pmpro_format_order_item_price( $amount ) {
	$formatted = wp_strip_all_tags( pmpro_formatPrice( abs( (float) $amount ) ) );
	return ( (float) $amount < 0 ) ? '-' . $formatted : $formatted;
}

/**
 * Get the label of a line, such as "Seats" or "3 × Seats".
 *
 * @since TBD
 *
 * @param object $line The order line or checkout line (as an object).
 * @return string
 */
function pmpro_get_order_item_label( $line ) {
	if ( $line->quantity > 1 ) {
		/* translators: 1: quantity, 2: item name. */
		return sprintf( __( '%1$d × %2$s', 'paid-memberships-pro' ), $line->quantity, $line->name );
	}
	return $line->name;
}

/**
 * Check whether an order's lines should be listed instead of its single summary row.
 *
 * A single membership line keeps the order's existing display.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order.
 * @param object[]    $lines The order's lines.
 * @return bool
 */
function pmpro_should_display_order_items( $order, $lines ) {
	if ( empty( $lines ) ) {
		return false;
	}
	return count( $lines ) > 1 || empty( $order->membership_id ) || $lines[0]->quantity > 1;
}

/**
 * Display an order's lines as table rows (Description | Amount) on the invoice and admin order pages.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order.
 * @return bool True if rows were displayed. False means the caller should display its single summary row.
 */
function pmpro_display_order_item_rows( $order ) {
	$lines = $order->get_items();
	if ( ! pmpro_should_display_order_items( $order, $lines ) ) {
		return false;
	}

	foreach ( $lines as $line ) {
		?>
		<tr class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_order_item pmpro_order_item-' . $line->item_type ) ); ?>">
			<th data-title="<?php esc_attr_e( 'Description', 'paid-memberships-pro' ); ?>">
				<?php echo esc_html( ( $line->parent_item_id ? '— ' : '' ) . pmpro_get_order_item_label( $line ) ); ?>
			</th>
			<td data-title="<?php esc_attr_e( 'Amount', 'paid-memberships-pro' ); ?>">
				<?php echo esc_html( pmpro_format_order_item_price( $line->subtotal ) ); ?>
			</td>
		</tr>
		<?php
	}

	return true;
}

/**
 * Get an order's lines as HTML for the {{ order_lines }} email variable.
 *
 * Empty for an order that is a single membership line, so those emails read as before.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order.
 * @return string
 */
function pmpro_get_order_items_email_html( $order ) {
	$lines = $order->get_items();
	if ( ! pmpro_should_display_order_items( $order, $lines ) ) {
		return '';
	}

	$html = '<ul>';
	foreach ( $lines as $line ) {
		$html .= '<li>' . esc_html( ( $line->parent_item_id ? '— ' : '' ) . pmpro_get_order_item_label( $line ) . ': ' . pmpro_format_order_item_price( $line->subtotal ) ) . '</li>';
	}
	$html .= '</ul>';

	return $html;
}
