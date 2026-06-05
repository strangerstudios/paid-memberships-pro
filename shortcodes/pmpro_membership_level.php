<?php
/**
 * Shortcode to show a specific field for a specific membership level.
 *
 * Example: [pmpro_membership_level field='name' level='1']
 *
 * @since TBD
 *
 * @param array       $atts The shortcode attributes passed in.
 * @param string|null $content The content passed in or null if not set.
 * @param string      $shortcode_tag The name of the shortcode tag used.
 *
 * @return string The shortcode output.
 */
function pmpro_membership_level_shortcode( $atts, $content = null, $shortcode_tag = '' ) {
	global $current_user;

	// Get the attributes and their defaults.
	extract(
		shortcode_atts(
			array(
				'field'   => null,
				'level'  => null,
			),
			$atts
		)
	);

	// Bail if there's no level attribute.
	if ( empty( $level ) ) {
		return esc_html__( 'The "level" attribute is required in the pmpro_membership_level shortcode.', 'paid-memberships-pro' );
	}

	// Bail if there's no field attribute.
	if ( empty( $field ) ) {
		return esc_html__( 'The "field" attribute is required in the pmpro_membership_level shortcode.', 'paid-memberships-pro' );
	}

		// Bail if there's no field attribute.
	if ( empty( $field ) ) {
		return esc_html__( 'The "field" attribute is required in the pmpro_membership_level shortcode.', 'paid-memberships-pro' );
	}

	// Get the level.
	$pmpro_level = pmpro_getLevel( intval( $level ) );

	// Bail if the level is not found.
	if ( empty( $pmpro_level ) ) {
		return esc_html__( 'Membership level not found.', 'paid-memberships-pro' );
	}

	// Get a list of fields related to the membership level.
	$pmpro_level_fields = array(
		'name',
		'description',
		'initial_payment',
		'billing_amount',
		'cycle_number',
		'cycle_period',
		'billing_limit',
		'trial_amount',
		'trial_limit',
		'expiration_number',
		'expiration_period',
		'level_cost',
		'checkout_url',
	);

	// Get a list of price fields.
	$price_fields = array(
		'initial_payment',
		'billing_amount',
		'trial_amount',
	);

	// Bail if the field is not supported.
	if ( ! in_array( $field, $pmpro_level_fields ) ) {
		return esc_html__( 'This "field" attribute is not supported by the pmpro_membership_level shortcode.', 'paid-memberships-pro' );
	}

	if ( $field === 'level_cost' ) {
		// Special case for level_cost.
		$r = pmpro_getLevelCost( $pmpro_level, false, true );
	} elseif ( $field === 'checkout_url' ) {
		// Special case for checkout_url.
		$r = pmpro_url( 'checkout', '?pmpro_level=' . $pmpro_level->id );
	} elseif ( $field === 'description' ) {
		// Special case for description.
		/**
		 * Apply the level description filter.
		 * We also have a function in includes/filters.php that applies the the_content filters to this description.
		 * @param string $description The level description.
		 * @param object $pmpro_level The PMPro Level object.
		 */
		$r = apply_filters('pmpro_level_description', $pmpro_level->description, $pmpro_level );
	} else {
		// All other fields.
		$r = $pmpro_level->{$field};
	}

	// Check for prices to reformat them.
	if ( in_array( $field, $price_fields ) ) {
		if ( empty( $r ) || $r == '0.00' ) {
			$r = '';
		} else {
			$r = pmpro_escape_price( pmpro_formatPrice( $r ) );
		}
	}

	/**
	 * Filter the output of the pmpro_membership_level shortcode.
	 *
	 * @since TBD
	 *
	 * @param string $r The output of the pmpro_membership_level shortcode.
	 * @param object $pmpro_level The PMPro Level object.
	 * @param string $field The field being output.
	 *
	 * @return string The output of the pmpro_membership_level shortcode.
	 */
	$r = apply_filters( 'pmpro_membership_level_shortcode_field', $r, $pmpro_level, $field );

	if ( $field === 'checkout_url' ) {
		return esc_url( $r );
	} elseif ( $field === 'description' || $field === 'level_cost' || in_array( $field, $price_fields ) ) {
		return wp_kses_post( $r );
	}

	return esc_html( $r );
}
add_shortcode( 'pmpro_membership_level', 'pmpro_membership_level_shortcode' );
