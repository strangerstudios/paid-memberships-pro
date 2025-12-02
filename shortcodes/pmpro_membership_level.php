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
		'confirmation',
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

	return $r;
}
add_shortcode( 'pmpro_membership_level', 'pmpro_membership_level_shortcode' );

/**
 * Strip the [pmpro_membership_level] shortcode from content if the current user can't edit users.
 *
 * @since TBD

 * @param string|array $content The content to strip the shortcode from.
 *                              If an array is passed in, all elements
 *                              will be filtered recursively.
 *                              Non-strings are ignored.
 *
 * @return mixed The content with the shortcode removed. Will be the same type as the input.
 */
function pmpro_maybe_strip_membership_level_shortcode( $content ) {
	// If the user can edit users, we don't need to strip the shortcode.
	if ( current_user_can( 'edit_users' ) ) {
		return $content;
	}

	// If an array is passed in, filter all elements recursively.
	if ( is_array( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content[ $key ] = pmpro_maybe_strip_membership_level_shortcode( $value );
		}
		return $content;
	}

	// If we're not looking at a string, just return it.
	if ( ! is_string( $content ) ) {
		return $content;
	}
	
	// Okay, we have a string, figure out the regex.
	$shortcodeRegex = get_shortcode_regex( array( 'pmpro_membership_level' ) );	

	// Replace shortcode wrapped in block comments.
	$blockWrapperPattern = "/<!-- wp:shortcode -->\s*$shortcodeRegex\s*<!-- \/wp:shortcode -->/s";
	$content = preg_replace( $blockWrapperPattern, '', $content );

	// Replace the shortcode by itself.
	$shortcodePattern = "/$shortcodeRegex/";
	$content = preg_replace( $shortcodePattern, '', $content );

	return $content;
}
add_filter( 'content_save_pre', 'pmpro_maybe_strip_membership_level_shortcode' );
add_filter( 'excerpt_save_pre', 'pmpro_maybe_strip_membership_level_shortcode' );
add_filter( 'widget_update_callback', 'pmpro_maybe_strip_membership_level_shortcode' );

/**
 * Only allow those with the edit_users capability
 * to use the pmpro_membership_level shortcode in post_meta.
 *
 * @since TBD
 *
 * @param int    $meta_id     ID of the meta data entry.
 * @param int    $object_id   ID of the object the meta is attached to.
 * @param string $meta_key    Meta key.
 * @param mixed  $_meta_value Meta value.
 * @return void
 */
function pmpro_maybe_strip_membership_level_shortcode_from_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	// Bail if the value is not a string or array.
	if ( ! is_string( $_meta_value ) && ! is_array( $_meta_value ) ) {
		return;
	}

	// Strip the shortcode from the meta value.
	$stripped_value = pmpro_maybe_strip_membership_level_shortcode( $_meta_value );

	// If there was a change, save our stripped version.
	if ( $stripped_value !== $_meta_value ) {
		update_post_meta( $object_id, $meta_key, $stripped_value );
	}
}
add_action( 'updated_post_meta', 'pmpro_maybe_strip_membership_level_shortcode_from_post_meta', 10, 4 );
