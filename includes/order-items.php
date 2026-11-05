<?php
/**
 * Order items.
 *
 * An order item describes one thing that a checkout order bought: the membership, seats, a
 * donation, a package and so on.
 *
 * - Items describe the order. The order's subtotal, tax and total stay the source of truth for money, and
 *   gateways, tax and reports keep reading them.
 * - Items belong to the order. MemberOrder::get_items() reads them, MemberOrder::set_items() replaces them
 *   and saveOrder() saves them. The checkout sets them on the order it builds. Renewal orders and orders
 *   saved before items existed read as one membership item built from the order. Nothing is backfilled.
 * - On an order with a membership level, the membership item is saved as whatever is left of the order
 *   subtotal after the other items, so code that changes the order's price is reflected on it.
 * - Add Ons add items at checkout with the pmpro_checkout_items filter. An item's billing_amount renews
 *   with the membership.
 * - When no level is being purchased and Add Ons added items, the checkout has no membership level. The
 *   buyer's memberships are not changed.
 *
 * @since TBD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Items.
 */

/**
 * Normalize an item to the shape shared by checkout items and saved items.
 *
 * Checkout items also have a key (see pmpro_build_checkout_items()). Saved items also have an id and an
 * order_id (see MemberOrder::get_items()).
 *
 * Item shape:
 * - item_type      (string) What the item is, such as 'seats' or 'donation'. Up to 64 characters.
 *                           'membership' is core only. Each Add On uses its own types.
 * - object_id      (int)    The level ID for the membership item. Defined by the Add On otherwise, such as
 *                           a post ID. Not negative.
 * - name           (string) Name shown at checkout, on the invoice and in emails.
 * - quantity       (int)    Quantity. At least 1.
 * - subtotal       (float)  Amount charged on this order for the whole quantity, before tax.
 * - billing_amount (float)  Amount added to each recurring payment of the membership. 0 for a one-time item.
 *                           Only used when the level has a billing cycle.
 *
 * @since TBD
 *
 * @param array|object $item The item to normalize.
 * @return array The normalized item. Other fields, such as key or id, are kept as they are.
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

	$item['item_type']      = substr( sanitize_key( $item['item_type'] ), 0, 64 ); // The item_type column is varchar(64).
	$item['object_id']      = max( 0, (int) $item['object_id'] );
	$item['name']           = mb_substr( sanitize_text_field( $item['name'] ), 0, 255 ); // The name column is varchar(255).
	$item['quantity']       = max( 1, (int) $item['quantity'] );
	$item['subtotal']       = pmpro_round_price( (float) $item['subtotal'] );
	$item['billing_amount'] = pmpro_round_price( (float) $item['billing_amount'] );

	return $item;
}

/**
 * Add up one amount of a list of items.
 *
 * @since TBD
 *
 * @param array  $items Items (arrays or objects).
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
 * Checkout items.
 */

/**
 * Build the items being purchased at checkout.
 *
 * Core seeds the membership item from the checkout level, then Add Ons add items with the
 * pmpro_checkout_items filter. Add Ons can't change or remove core's item.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level after the pmpro_checkout_level filter, or null when no level is being purchased.
 * @return array Normalized items keyed by key. Core's item comes first.
 */
function pmpro_build_checkout_items( $level ) {
	$core_items = array();
	if ( ! empty( $level->id ) ) {
		$recurring = pmpro_isLevelRecurring( $level );
		$discount  = pmpro_get_checkout_level_code_discount( $level );

		// With a discount item, the membership item has the level's full price.
		$core_items['membership'] = pmpro_normalize_order_item(
			array(
				'key'            => 'membership',
				'item_type'      => 'membership',
				'object_id'      => (int) $level->id,
				'name'           => $level->name,
				'subtotal'       => $level->initial_payment + ( empty( $discount ) ? 0 : $discount['initial_discount'] ),
				'billing_amount' => $recurring ? $level->billing_amount + ( empty( $discount ) ? 0 : $discount['billing_discount'] ) : 0,
			)
		);

		if ( ! empty( $discount ) ) {
			$core_items['discount'] = pmpro_normalize_order_item(
				array(
					'key'            => 'discount',
					'item_type'      => 'discount',
					'object_id'      => (int) $level->code_id,
					/* translators: %s: discount code. */
					'name'           => sprintf( __( 'Discount code %s', 'paid-memberships-pro' ), $level->discount_code ),
					'subtotal'       => -1 * $discount['initial_discount'],
					'billing_amount' => $recurring ? -1 * $discount['billing_discount'] : 0,
				)
			);
		}
	}

	/**
	 * Filter the items being purchased at checkout.
	 *
	 * When a level is being purchased, $items holds core's 'membership' item. Its object_id is the level ID.
	 * A percentage or fixed discount code adds core's 'discount' item, with negative amounts, and the
	 * membership item has the level's full price. Otherwise the membership item is priced as charged, after
	 * any discount code. Add Ons add their own items. See pmpro_normalize_order_item() for the item shape.
	 * An item's key must be unique in the checkout. It defaults to "{item_type}-{object_id}" and isn't saved.
	 *
	 * - An item's billing_amount is added to each recurring payment of the membership. If the level has no
	 *   billing cycle, or no level is being purchased, every item is one-time and billing_amount is ignored.
	 *   A level can have a billing cycle with a $0 billing amount of its own, such as a free base with paid
	 *   seats.
	 * - A one-time item can have a negative subtotal, such as a credit. A billing_amount can't be negative,
	 *   and the checkout's payments can't add up to less than $0. Discount codes only discount the
	 *   membership, but an Add On may read the code to price its own items.
	 *
	 * When no level is being purchased and this filter returns items, the checkout has no membership level.
	 *
	 * Callbacks read their inputs from the request. This filter runs every time the checkout level is
	 * resolved and every time the items of a checkout without a membership level are read, so callbacks
	 * must not have side effects or call pmpro_getLevelAtCheckout().
	 *
	 * - Inputs can be in the checkout URL's query string, such as ?ap=12, or in checkout fields, such as a
	 *   donation amount. Both are restored when the buyer returns from an offsite gateway.
	 * - Give checkout fields that change an item the pmpro_alter_price class. The checkout then refreshes
	 *   its items and total when they change. Until the form is submitted or refreshed, the request doesn't
	 *   have the field, so price the item with the field's default.
	 * - A checkout without a membership level only opens if this filter returns an item when the page first
	 *   loads. When the amount comes from a checkout field, start the checkout with a query string
	 *   parameter that adds the item at its default amount, even $0. A checkout that is still $0 when
	 *   submitted is refused.
	 *
	 * @since TBD
	 *
	 * @param array       $items Items keyed by key.
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

		// Items only renew when the level has a billing cycle.
		if ( empty( $level->id ) || empty( $level->cycle_number ) ) {
			$item['billing_amount'] = 0;
		}

		$items[ $item['key'] ] = $item;
	}

	return array_merge( $core_items, $items );
}

/**
 * Set up the checkout items for a checkout level.
 *
 * Called at the end of pmpro_getLevelAtCheckout(), after the pmpro_checkout_level filter. When Add Ons
 * added items, the level's initial_payment becomes the total of all items and its billing_amount the
 * total of the recurring items, so that every gateway charges the whole order. The items are kept in the
 * level's checkout_items property. The 'membership' item keeps the membership's own price, for the cost
 * text (see pmpro_get_level_with_membership_price()).
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

	// A filter may hand back a level object that already carries the items. Start again from the membership's price.
	$level = pmpro_get_level_with_membership_price( $level );

	$level->checkout_items = pmpro_build_checkout_items( $level );

	// Core's item is the level's price, so only Add On items change what the level charges.
	if ( 1 === count( $level->checkout_items ) ) {
		return $level;
	}

	$level->initial_payment = pmpro_get_items_total( $level->checkout_items, 'subtotal' );
	if ( ! empty( $level->cycle_number ) ) {
		$level->billing_amount = pmpro_get_items_total( $level->checkout_items, 'billing_amount' );
	}

	return $level;
}

/**
 * Get a checkout level priced at the membership's own price, without the Add On items.
 *
 * When Add Ons added items, the checkout level's initial_payment and billing_amount are the totals of all
 * items. This returns a copy priced as the 'membership' item, such as for the level cost text. A level
 * without Add On items is returned as it is.
 *
 * @since TBD
 *
 * @param object $level The checkout level.
 * @return object The level, or a copy of it at the membership's own price.
 */
function pmpro_get_level_with_membership_price( $level ) {
	if ( ! isset( $level->checkout_items['membership'] ) || count( $level->checkout_items ) < 2 ) {
		return $level;
	}

	// A discount item is part of the membership's price.
	$membership_items = array_intersect_key( $level->checkout_items, array( 'membership' => true, 'discount' => true ) );

	$membership_level                  = clone $level;
	$membership_level->initial_payment = pmpro_get_items_total( $membership_items, 'subtotal' );
	if ( ! empty( $level->cycle_number ) ) {
		$membership_level->billing_amount = pmpro_get_items_total( $membership_items, 'billing_amount' );
	}
	unset( $membership_level->checkout_items );

	return $membership_level;
}

/**
 * Remember what a percentage or fixed discount code took off a checkout level's price.
 *
 * The checkout lists it as a discount item, and the membership item at the level's full price. Set-price
 * codes set a new price instead of taking an amount off, so they don't have a discount item.
 *
 * @since TBD
 *
 * @param object $level             The checkout level, priced with the code.
 * @param object $discount_code_row The discount code, from pmpro_get_discount_code().
 */
function pmpro_set_checkout_level_code_discount( $level, $discount_code_row ) {
	if ( empty( $level->id ) || empty( $discount_code_row->discount_type ) || ! in_array( $discount_code_row->discount_type, array( 'percentage', 'fixed' ), true ) ) {
		return;
	}

	$full_level = pmpro_getLevel( $level->id );
	if ( empty( $full_level ) ) {
		return;
	}

	$level->code_discount = array(
		'initial_payment'  => pmpro_round_price( $level->initial_payment ),
		'billing_amount'   => pmpro_round_price( $level->billing_amount ),
		'initial_discount' => max( 0, pmpro_round_price( $full_level->initial_payment - $level->initial_payment ) ),
		'billing_discount' => max( 0, pmpro_round_price( $full_level->billing_amount - $level->billing_amount ) ),
	);
}

/**
 * Get what a discount code took off a checkout level's price.
 *
 * Only when the level still has the code's price. If a pmpro_checkout_level filter changed the price after
 * the code, the discount can't be told apart from that change, so the membership item keeps the charged
 * price and there is no discount item.
 *
 * @since TBD
 *
 * @param object $level The checkout level.
 * @return array|null The 'initial_discount' and 'billing_discount', or null.
 */
function pmpro_get_checkout_level_code_discount( $level ) {
	if ( empty( $level->code_discount ) || empty( $level->code_id ) ) {
		return null;
	}

	$discount = $level->code_discount;
	if ( pmpro_round_price( $level->initial_payment ) != $discount['initial_payment'] || pmpro_round_price( $level->billing_amount ) != $discount['billing_amount'] ) {
		return null;
	}

	if ( $discount['initial_discount'] <= 0 && ( $discount['billing_discount'] <= 0 || ! pmpro_isLevelRecurring( $level ) ) ) {
		return null;
	}

	return $discount;
}

/**
 * Get the items being purchased at checkout.
 *
 * A level checkout's items are kept on the level by pmpro_getLevelAtCheckout(). A checkout without a
 * membership level has no level to keep them on, so its items are built again.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level, or null (or a level with no ID) when no level is being purchased.
 * @return array Normalized items keyed by key.
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
 * Add Ons should use this, called without an argument, to tell whether the current checkout has a level.
 * Don't check $pmpro_level->id yourself: on a checkout without a membership level, $pmpro_level is null
 * while the checkout is processed and a placeholder level with ID 0 while the page is shown.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level. Defaults to the current checkout's level.
 * @return bool True if no level is being purchased and Add Ons added items to purchase.
 */
function pmpro_is_checkout_without_level( $level = null ) {
	// Without an argument, check the current checkout.
	if ( 0 === func_num_args() ) {
		global $pmpro_level;
		if ( ! pmpro_is_checkout() ) {
			return false;
		}
		$level = $pmpro_level;
	}

	return empty( $level->id ) && ! empty( pmpro_build_checkout_items( null ) );
}

/**
 * Check the checkout items when the checkout form is submitted, before the user is created.
 *
 * @since TBD
 *
 * @param array $items The checkout items.
 * @return bool False if the items can't be purchased. An error message is set.
 */
function pmpro_check_checkout_items( $items ) {
	global $gateway;

	if ( empty( $items ) ) {
		return true;
	}

	foreach ( $items as $key => $item ) {
		// Core's items were checked when they were built.
		if ( in_array( $key, array( 'membership', 'discount' ), true ) ) {
			continue;
		}

		// Only core makes membership and discount items. An Add On's one-time item can be a credit, but a recurring amount can't be negative.
		if ( in_array( $item['item_type'], array( 'membership', 'discount' ), true ) || $item['billing_amount'] < 0 || ( $item['subtotal'] < 0 && $item['billing_amount'] > 0 ) ) {
			pmpro_setMessage( __( 'An item in this checkout has an invalid price. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
			return false;
		}
	}

	// Credits can't make a payment negative.
	if ( pmpro_get_items_total( $items, 'subtotal' ) < 0 || pmpro_get_items_total( $items, 'billing_amount' ) < 0 ) {
		pmpro_setMessage( __( 'An item in this checkout has an invalid price. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	// The rest is only for checkouts without a membership level.
	if ( isset( $items['membership'] ) ) {
		return true;
	}

	if ( pmpro_get_items_total( $items, 'subtotal' ) <= 0 ) {
		pmpro_setMessage( __( 'There is nothing to purchase.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	// The order's items are the only record of what it bought, so refuse until the items table exists. The
	// database is updated the next time an admin page loads.
	if ( version_compare( get_option( 'pmpro_db_version', 0 ), '4.0', '<' ) ) {
		pmpro_setMessage( __( 'This purchase isn\'t available right now. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	// Refuse before anything is charged if the gateway can't complete an order without a membership level.
	//
	// Gateways declare support with the 'checkout_without_level' feature in their supports() method. In core,
	// Stripe, PayPal Express and Check do. A gateway that doesn't may charge the order and then fail to complete
	// it. The check uses the gateway object that checkout uses, after the pmpro_order_gateway_object filter.
	//
	// Before declaring support, a gateway must handle orders with membership_id 0:
	// - Charge the order's total once. There is no subscription and no level to read prices from.
	// - Describe the payment with MemberOrder::get_items_description() instead of the level name.
	// - Send the buyer offsite from process() or call its own handler directly. A checkout without a
	//   membership level doesn't run pmpro_checkout_before_change_membership_level, which offsite
	//   gateways often use for the redirect.
	// - Complete the order with pmpro_complete_checkout(), which doesn't change the buyer's memberships.
	$gateway_object = null;
	if ( ! empty( $gateway ) ) {
		$order = new MemberOrder();
		$order->setGateway( $gateway );
		$gateway_object = $order->Gateway;
	}
	if ( ! is_object( $gateway_object ) || ! method_exists( $gateway_object, 'supports' ) || ! call_user_func( array( get_class( $gateway_object ), 'supports' ), 'checkout_without_level' ) ) {
		pmpro_setMessage( __( 'This purchase can\'t be paid for with the selected payment method. Please contact the site owner.', 'paid-memberships-pro' ), 'pmpro_error' );
		return false;
	}

	return true;
}

/**
 * Get the checkout URL with the current request's query string.
 *
 * Used for the login link and the offsite cancel URL of a checkout without a membership level, so the
 * buyer comes back to the same items.
 *
 * @since TBD
 *
 * @return string
 */
function pmpro_get_checkout_without_level_url() {
	$args = map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, rebuilds the checkout URL.
	$args = array_combine( array_map( 'sanitize_key', array_keys( $args ) ), $args );
	unset( $args['pmpro_order'], $args['submit-checkout'], $args['pmpro_checkout_nonce'] );
	return add_query_arg( urlencode_deep( $args ), pmpro_url( 'checkout' ) );
}

/**
 * Display the checkout items with the total due today.
 *
 * Shown on a checkout without a membership level, and on a level checkout when Add Ons added items. The
 * checkout_level REST endpoint returns the same HTML so the checkout can refresh it when a field with the
 * pmpro_alter_price class changes.
 *
 * @since TBD
 *
 * @param object|null $level The checkout level.
 */
function pmpro_display_checkout_items( $level ) {
	$items = pmpro_get_checkout_items( $level );

	// A level checkout with only the membership item shows the level cost text alone.
	if ( empty( $items ) || ( ! empty( $level->id ) && count( $items ) < 2 ) ) {
		return;
	}

	?>
	<div id="pmpro_checkout_items" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout_items' ) ); ?>">
		<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Description', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $items as $item ) { ?>
					<tr class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout_item pmpro_checkout_item-' . $item['item_type'] ) ); ?>">
						<th data-title="<?php esc_attr_e( 'Description', 'paid-memberships-pro' ); ?>"><?php echo esc_html( pmpro_get_order_item_label( $item ) ); ?></th>
						<td data-title="<?php esc_attr_e( 'Amount', 'paid-memberships-pro' ); ?>">
							<?php echo esc_html( pmpro_format_order_item_price( $item['subtotal'] ) ); ?>
							<?php if ( 0 != $item['billing_amount'] && ! empty( $level->cycle_number ) ) { ?>
								<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-small' ) ); ?>"><?php echo esc_html( pmpro_get_checkout_items_recurring_text( $item['billing_amount'], $level ) ); ?></p>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td><?php esc_html_e( 'Total today', 'paid-memberships-pro' ); ?></td>
					<td data-title="<?php esc_attr_e( 'Total today', 'paid-memberships-pro' ); ?>">
						<?php echo esc_html( pmpro_format_order_item_price( pmpro_get_items_total( $items, 'subtotal' ) ) ); ?>
						<?php
						$billing_total = pmpro_get_items_total( $items, 'billing_amount' );
						if ( $billing_total > 0 && ! empty( $level->cycle_number ) ) {
							?>
							<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-small' ) ); ?>"><?php echo esc_html( pmpro_get_checkout_items_recurring_text( $billing_total, $level ) ); ?></p>
							<?php
						}
						?>
					</td>
				</tr>
			</tfoot>
		</table>
		<?php
		// Applying a discount code by AJAX only knows the level, so tell it when Add On items still need to be paid for.
		$add_on_items = array_diff_key( $items, array( 'membership' => true, 'discount' => true ) );
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
 * Format an item amount, with a minus sign in front of negative amounts, such as "-$5.00".
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
 * Get the label of an item, such as "Seats" or "3 × Seats".
 *
 * @since TBD
 *
 * @param object|array $line A saved item or a checkout item.
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
 * Check whether an order's items should be listed instead of its single summary row.
 *
 * A single membership item keeps the order's existing display.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order.
 * @param object[]    $lines The order's items, from MemberOrder::get_items().
 * @return bool
 */
function pmpro_should_display_order_items( $order, $lines ) {
	if ( empty( $lines ) ) {
		return false;
	}
	return count( $lines ) > 1 || empty( $order->membership_id ) || $lines[0]->quantity > 1;
}

/**
 * Display an order's items as table rows (Description | Amount) on the invoice and admin order pages.
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
 * Get an order's items as HTML for the {{ order_items }} email variable.
 *
 * Empty for an order that is a single membership item, so those emails read as before.
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
