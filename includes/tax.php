<?php

/**
 * Calculate the tax for an order.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order to calculate the tax for.
 * @return MemberOrder The order with the tax calculated.
 */
function pmpro_calculate_inclusive_tax( MemberOrder $order ) {
	// If the order already has tax set, return it.
	if( ! empty( $order->tax ) ) {
		return $order;
	}

	// Get the tax rates.
	$tax_rates = get_option( 'pmpro_tax_rates' );
	if( empty( $tax_rates ) ) {
		return $order;
	}

	// Get the combined tax rate for the order.
	$combined_tax_rate = 0;
	foreach( $tax_rates as $tax_rate ) {
		// Check the country.
		if ( '*' !== $tax_rate['country'] && strtolower( $tax_rate['country'] ) !== strtolower( $order->billing->country ) ) {
			continue;
		}

		// Check the state.
		if ( '*' !== $tax_rate['state'] && strtolower( $tax_rate['state'] ) !== strtolower( $order->billing->state ) ) {
			continue;
		}

		// Check the city.
		if ( '*' !== $tax_rate['city'] && strtolower( $tax_rate['city'] ) !== strtolower( $order->billing->city ) ) {
			continue;
		}

		// Check the zip.
		if ( '*' !== $tax_rate['zip'] && strtolower( $tax_rate['zip'] ) !== strtolower( $order->billing->zip ) ) {
			continue;
		}

		// If we get here, the tax rate matches the order. Add it to the combined rate.
		$combined_tax_rate += $tax_rate['rate'];
	}

	// If we have a combined tax rate, calculate the tax.
	if ( $combined_tax_rate > 0 ) {
		// Right now, the order total is right and we need to go backwards to get the tax and subtotal.
		$order->subtotal = $order->total / ( 1 + ( (float)$combined_tax_rate / 100 ) );
		$order->tax = $order->total - $order->subtotal;

		// Save the order.
		$order->saveOrder();
	}

	return $order;
}

/**
 * Calculate the tax for an order after checkout.
 *
 * @since TBD
 *
 * @param int $user_id The user ID of the order.
 * @param MemberOrder $order The order to calculate the tax for.
 */
function pmpro_calculate_inclusive_tax_after_checkout( $user_id, $order ) {
	pmpro_calculate_inclusive_tax( $order );
}
add_action( 'pmpro_after_checkout', 'pmpro_calculate_inclusive_tax_after_checkout', 10, 2 );

/**
 * Calculate the tax for a recurring order.
 *
 * @since TBD
 *
 * @param MemberOrder $order The order to calculate the tax for.
 */
function pmpro_calculate_inclusive_tax_recurring_order( $order ) {
	pmpro_calculate_inclusive_tax( $order );
}
add_action( 'pmpro_subscription_payment_completed', 'pmpro_calculate_inclusive_tax_recurring_order', 10, 2 );

/**
 * Make sure that billing fields are always shown at checkout when tax is enabled.
 *
 * @since TBD
 *
 * @param bool $show_billing_fields Whether to show the billing fields.
 * @return bool Whether to show the billing fields.
 */
function pmpro_tax_show_billing_address_fields( $show_billing_fields ) {
	global $pmpro_review;

	// If we are in the review step, don't adjust the billing fields.
	if ( $pmpro_review ) {
		return $show_billing_fields;
	}

	// If tax is not enabled, don't adjust the billing fields.
	if ( empty( get_option( 'pmpro_tax_rates' ) ) ) {
		return $show_billing_fields;
	}

	// If we are not in the review step and tax is enabled, show the billing fields.
	return true;
}
add_filter( 'pmpro_include_billing_address_fields', 'pmpro_tax_show_billing_address_fields', 15 );

/**
 * Make sure that billing fields are all required when tax is enabled.
 *
 * @since TBD
 *
 * @param array $fields The fields to check.
 * @return array The fields to check.
 */
function pmpro_tax_required_billing_fields( $fields ) {
	global $bfirstname, $blastname, $baddress1, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail;

	// If tax is not enabled, don't adjust the fields.
	if ( empty( get_option( 'pmpro_tax_rates' ) ) ) {
		return $fields;
	}

	$fields["bfirstname"] = $bfirstname;
	$fields["blastname"] = $blastname;
	$fields["baddress1"] = $baddress1;
	$fields["bcity"] = $bcity;
	$fields["bstate"] = $bstate;
	$fields["bzipcode"] = $bzipcode;
	$fields["bphone"] = $bphone;
	$fields["bemail"] = $bemail;
	$fields["bcountry"] = $bcountry;

	return $fields;
}
add_filter( 'pmpro_required_billing_fields', 'pmpro_tax_required_billing_fields', 15 );

/**
 * Using JS, make sure that the billing fields are always shown at checkout when tax is enabled.
 *
 * @since TBD
 */
function pmpro_tax_show_billing_address_fields_js() {
	// If tax is not enabled, don't show the billing fields.
	if ( empty( get_option( 'pmpro_tax_rates' ) ) ) {
		return;
	}

	?>
	<script>
		jQuery( document ).ready( function( $ ) {
			function maybeShowBillingAddressFields() {
				// Check if the input with name "gateway" is visible.
				// This will be the case if PBC or the Add PayPal Express Add Ons are being used.
				if ( $( 'input[name="gateway"]' ).is( ':visible' ) ) {
					// If there is a gateway choice, we should show the billing fields.
					$( '#pmpro_billing_address_fields' ).show();
				}
			}

			// Run on page load and whenever the gateway changes.
			maybeShowBillingAddressFields();
			$( 'input[name="gateway"]' ).change( function() {
				maybeShowBillingAddressFields();
			} );
		} );
	</script>
	<?php
}
add_action( 'pmpro_checkout_after_form', 'pmpro_tax_show_billing_address_fields_js' );
