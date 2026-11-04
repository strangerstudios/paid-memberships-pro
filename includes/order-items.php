<?php
/**
 * Order items.
 *
 * An order item (a "line") describes one thing that a checkout order bought: the membership, seats, a
 * donation, a package and so on.
 *
 * - Lines describe the order. The order's subtotal, tax and total stay the source of truth for money, and
 *   gateways, tax and reports keep reading them.
 * - Lines belong to the order. MemberOrder::get_items() reads them, MemberOrder::set_items() replaces them
 *   and saveOrder() saves them. The checkout sets them on the order it builds. Renewal orders and orders
 *   saved before lines existed read as one membership line built from the order. Nothing is backfilled.
 * - On an order with a membership level, the membership line is saved as whatever is left of the order
 *   subtotal after the other lines, so code that changes the order's price is reflected on it.
 * - Add Ons add lines at checkout with the pmpro_checkout_items filter. A line's billing_amount renews
 *   with the membership.
 * - When no level is being purchased and Add Ons added lines, the checkout has no membership level. The
 *   buyer's memberships are not changed.
 *
 * @since TBD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Lines.
 */

/**
 * Normalize a line to the shape shared by checkout lines and saved lines.
 *
 * Checkout lines also have a key (see pmpro_build_checkout_items()). Saved lines also have an id and an
 * order_id (see MemberOrder::get_items()).
 *
 * Line shape:
 * - item_type      (string) What the line is, such as 'seats' or 'donation'. 'membership' is core only.
 * - object_id      (int)    The level ID for the membership line. Defined by the Add On otherwise.
 * - name           (string) Name shown at checkout, on the invoice and in emails.
 * - quantity       (int)    Quantity. At least 1.
 * - subtotal       (float)  Amount charged on this order for the whole quantity, before tax.
 * - billing_amount (float)  Amount added to each recurring payment of the membership. 0 for a one-time line.
 *
 * @since TBD
 *
 * @param array|object $item The line to normalize.
 * @return array The normalized line. Other fields, such as key or id, are kept as they are.
 */
function pmpro_normalize_order_item( $item ) {
	$item = wp_parse_args(
		(array) $item,
		array(
			'item_type'      => '',
			'object_id'      => 0,
			'name'           => '',
			'quantity'       => 1,
			'subtotal'       => 0,
			'billing_amount' => 0,
		)
	);

	$item['item_type']      = sanitize_key( $item['item_type'] );
	$item['object_id']      = (int) $item['object_id'];
	$item['name']           = mb_substr( sanitize_text_field( $item['name'] ), 0, 255 ); // The name column is varchar(255).
	$item['quantity']       = max( 1, (int) $item['quantity'] );
	$item['subtotal']       = pmpro_round_price( (float) $item['subtotal'] );
	$item['billing_amount'] = pmpro_round_price( (float) $item['billing_amount'] );

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

/*
 * Checkout lines.
 */

/**
 * Build the lines being purchased at checkout.
 *
 * Core seeds the membership line from the checkout level, then Add Ons add lines with the
 * pmpro_checkout_items filter. Add Ons can't change or remove core's line.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level after the pmpro_checkout_level filter, or null when no level is being purchased.
 * @return array Normalized lines keyed by key. Core's line comes first.
 */
function pmpro_build_checkout_items( $level ) {
	$core_items = array();
	if ( ! empty( $level->id ) ) {
		$core_items['membership'] = pmpro_normalize_order_item(
			array(
				'key'            => 'membership',
				'item_type'      => 'membership',
				'object_id'      => (int) $level->id,
				'name'           => $level->name,
				'subtotal'       => $level->initial_payment,
				'billing_amount' => pmpro_isLevelRecurring( $level ) ? $level->billing_amount : 0,
			)
		);
	}

	/**
	 * Filter the lines being purchased at checkout.
	 *
	 * When a level is being purchased, $items holds core's 'membership' line. Its object_id is the level ID
	 * and it is priced as charged, after any discount code. Add Ons add their own lines. See
	 * pmpro_normalize_order_item() for the line shape. A line's key must be unique in the checkout. It
	 * defaults to "{item_type}-{object_id}" and isn't saved.
	 *
	 * - A line's billing_amount is added to each recurring payment of the membership. If the membership is
	 *   one-time, or no level is being purchased, every line is one-time and billing_amount is ignored.
	 * - Amounts can't be negative. Discount codes only discount the membership line, but an Add On may read
	 *   the code to price its own lines.
	 *
	 * When no level is being purchased and this filter returns lines, the checkout has no membership level.
	 *
	 * Callbacks read their inputs from the request. This filter runs every time the checkout level is
	 * resolved and every time the lines of a checkout without a membership level are read, so callbacks
	 * must not have side effects or call pmpro_getLevelAtCheckout().
	 *
	 * - Inputs can be in the checkout URL's query string, such as ?ap=12, or in checkout fields, such as a
	 *   donation amount. Both are restored when the buyer returns from an offsite gateway.
	 * - Give checkout fields that change a line the pmpro_alter_price class. The checkout then refreshes
	 *   its lines and total when they change. Until the form is submitted or refreshed, the request doesn't
	 *   have the field, so price the line with the field's default.
	 * - A checkout without a membership level only opens if this filter returns a line when the page first
	 *   loads. When the amount comes from a checkout field, start the checkout with a query string
	 *   parameter that adds the line at its default amount, even $0. A checkout that is still $0 when
	 *   submitted is refused.
	 *
	 * @since TBD
	 *
	 * @param array       $items Lines keyed by key.
	 * @param object|null $level The checkout level, or null when no level is being purchased.
	 */
	$filtered = apply_filters( 'pmpro_checkout_items', $core_items, $level );

	$items = array();
	foreach ( (array) $filtered as $item ) {
		$item        = pmpro_normalize_order_item( $item );
		$item['key'] = ( isset( $item['key'] ) && '' !== (string) $item['key'] ) ? (string) $item['key'] : $item['item_type'] . '-' . $item['object_id'];
		if ( isset( $core_items[ $item['key'] ] ) ) {
			continue;
		}

		// Lines only renew with a recurring membership.
		if ( empty( $core_items['membership']['billing_amount'] ) ) {
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
 * @param object|null $level The checkout level.
 * @return object|null The checkout level.
 */
function pmpro_set_up_checkout_items_for_level( $level ) {
	if ( empty( $level->id ) ) {
		return $level;
	}

	// A filter may hand back a level object that already carries the lines. Start again from the membership's price.
	if ( ! empty( $level->pmpro_membership_price ) ) {
		$level->initial_payment = $level->pmpro_membership_price['initial_payment'];
		$level->billing_amount  = $level->pmpro_membership_price['billing_amount'];
		unset( $level->pmpro_membership_price );
	}

	$level->checkout_items = pmpro_build_checkout_items( $level );

	// Core's line is the level's price, so only Add On lines change what the level charges.
	if ( 1 === count( $level->checkout_items ) ) {
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
	global $gateway;

	if ( empty( $items ) ) {
		return true;
	}

	foreach ( $items as $key => $item ) {
		// Core's line was checked when it was built.
		if ( 'membership' === $key ) {
			continue;
		}

		// Only core makes membership lines, and amounts can't be negative.
		if ( 'membership' === $item['item_type'] || $item['subtotal'] < 0 || $item['billing_amount'] < 0 ) {
			pmpro_setMessage( __( 'An item in this checkout has an invalid price. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
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

	// Refuse before anything is charged if the gateway can't complete an order without a membership level.
	if ( ! pmpro_gateway_supports_membershipless_checkout( $gateway ) ) {
		pmpro_setMessage( __( 'This purchase can\'t be paid for with the selected payment method. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	return true;
}

/**
 * Check whether a gateway can process a checkout without a membership level.
 *
 * Gateways declare support with the 'membershipless_checkout' feature in their supports() method. In
 * core, Stripe, PayPal Express and Check do. A gateway that doesn't may charge the order and then fail
 * to complete it, so checkouts without a membership level are refused for it. The check uses the gateway object that
 * checkout uses, after the pmpro_order_gateway_object filter.
 *
 * @since TBD
 *
 * @param string $gateway The gateway slug.
 * @return bool
 */
function pmpro_gateway_supports_membershipless_checkout( $gateway ) {
	if ( empty( $gateway ) ) {
		return false;
	}

	$order = new MemberOrder();
	$order->setGateway( $gateway );
	$gateway_object = $order->Gateway;

	return is_object( $gateway_object ) && method_exists( $gateway_object, 'supports' ) && call_user_func( array( get_class( $gateway_object ), 'supports' ), 'membershipless_checkout' );
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
 * Shown on a checkout without a membership level, and on a level checkout when Add Ons added lines. The
 * checkout_level REST endpoint returns the same HTML so the checkout can refresh it when a field with the
 * pmpro_alter_price class changes.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level.
 */
function pmpro_display_checkout_items( $level ) {
	$items = pmpro_get_checkout_items( $level );

	// A level checkout with only the membership line shows the level cost text alone.
	if ( empty( $items ) || ( ! empty( $level->id ) && count( $items ) < 2 ) ) {
		return;
	}

	?>
	<div id="pmpro_checkout_items" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout_items' ) ); ?>">
		<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ); ?>">
			<?php foreach ( $items as $item ) { ?>
				<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item pmpro_checkout_item pmpro_checkout_item-' . $item['item_type'] ) ); ?>">
					<?php echo esc_html( pmpro_get_order_item_label( $item ) ); ?>:
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
		<?php
		// Applying a discount code by AJAX only knows the level, so tell it when Add On lines still need to be paid for.
		$add_on_items = array_diff_key( $items, array( 'membership' => true ) );
		if ( ! empty( $level->id ) && ( pmpro_get_items_total( $add_on_items ) > 0 || pmpro_get_items_total( $add_on_items, 'billing_amount' ) > 0 ) ) {
			echo '<input type="hidden" id="pmpro_checkout_has_items" name="pmpro_checkout_has_items" value="1" />';
		}
		?>
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
 * @param object|array $line A saved line or a checkout line.
 * @return string
 */
function pmpro_get_order_item_label( $line ) {
	$line = (object) $line;
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
 * @param object[]    $lines The order's lines, from MemberOrder::get_items().
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
				<?php echo esc_html( pmpro_get_order_item_label( $line ) ); ?>
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
		$html .= '<li>' . esc_html( pmpro_get_order_item_label( $line ) . ': ' . pmpro_format_order_item_price( $line->subtotal ) ) . '</li>';
	}
	$html .= '</ul>';

	return $html;
}
