<?php
/**
 * Shortcode to show a specific user field for current user or specified user ID.
 *
 * Example: [pmpro_member field='last_name' user_id='1', levels='1,2,3']
 *
 * @param array       $atts The shortcode attributes passed in.
 * @param string|null $content The content passed in or null if not set.
 * @param string      $shortcode_tag The name of the shortcode tag used.
 *
 * @return string The shortcode output.
 */
function pmpro_member_shortcode( $atts, $content = null, $shortcode_tag = '' ) {
	global $current_user;

	// Get the attributes and their defaults.
	extract(
		shortcode_atts(
			array(
				'user_id' => $current_user->ID,
				'field'   => null,
				'levels'  => null,
				'group'   => null,
			),
			$atts
		)
	);

	// No user_id found, let's bail.
	if ( ! $user_id ) {
		return;
	}

	// Make sure the user_id is of an existing user when not viewing their own information.
	if ( $user_id !== $current_user->ID ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
	} else {
		$user = $current_user;
	}


	// Bail if there's no field attribute.
	if ( empty( $field ) ) {
		return esc_html__( 'The "field" attribute is required in the pmpro_member shortcode.', 'paid-memberships-pro' );
	}

	// Get a list of fields related to the user's level.
	$pmpro_level_fields = array(
		'membership_id',
		'membership_name',
		'membership_description',
		'membership_confirmation',
		'membership_initial_payment',
		'membership_startdate',
		'membership_enddate',
		'level_cost',
	);

	// Get a list of fields related to the user's subscription.
	$pmpro_subscription_fields = array(
		'membership_billing_amount',
		'membership_cycle_number',
		'membership_cycle_period',
		'membership_billing_limit',
		'membership_trial_amount',
		'membership_trial_limit',
		'next_payment_date',
	);

	// Get a list of pmpro-related fields stored in user meta.
	// Note: Most of this information is no longer stored in user meta.
	//       These will likely be deprecated in the future. Use with caution.
	$pmpro_user_meta_fields = array(
		'bfirstname',
		'blastname',
		'baddress1',
		'baddress2',
		'bcity',
		'bstate',
		'bzipcode',
		'bcountry',
		'bphone',
		'bemail',
		'CardType',
		'AccountNumber',
		'ExpirationMonth',
		'ExpirationYear',
	);

	// Get a list of fields saved in the wp_users table.
	$user_column_fields = array(
		'user_login',
		'user_email',
		'user_url',
		'user_registered',
		'display_name',
	);

	// Get a list of date fields.
	$date_fields = array(
		'startdate',
		'enddate',
		'modified',
		'user_registered',
		'next_payment_date',
	);

	// Get a list of price fields.
	$price_fields = array(
		'initial_payment',
		'billing_amount',
		'trial_amount',
	);

	if ( in_array( $field, $pmpro_level_fields ) || in_array( $field, $pmpro_subscription_fields ) || 'level_meta_' === substr( $field, 0, 11 ) ) {
		// Fields about the user's membership or subscription.
		// Get the membership level to show.
		$membership_level = null;
		if ( empty( $levels ) && empty( $group ) ) {
			// Grab any one of the user's levels.
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );
		} else {
			// Find a level from the list of levels or group.
			if ( ! empty( $levels ) ) {
				// Level IDs were passed in.
				$level_ids = explode( ',', $levels );
			} else {
				// A level group was passed in.
				$level_ids = wp_list_pluck( pmpro_get_levels_for_group( intval( $group ) ), 'id' );
			}

			// Grab the first level the user has from the list.
			foreach ( $level_ids as $level_id ) {
				$membership_level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );
				if ( ! empty( $membership_level ) ) {
					break;
				}
			}
		}

		if ( empty( $membership_level ) ) {
			// No level found.
			$r = '';
		} elseif ( in_array( $field, $pmpro_level_fields ) ) {
			// Membership level fields.
			if ( $field === 'level_cost' ) {
				// Special case for level_cost.
				$r = pmpro_getLevelCost( $membership_level, false, true );
			} else {
				// All other fields.
				$field = str_replace( 'membership_', '', $field );
				$r     = $membership_level->{$field};
			}
		} elseif( 'level_meta_' === substr( $field, 0, 11 ) ) {
			// Membership level meta fields.
			$r = get_pmpro_membership_level_meta( $membership_level->id, substr( $field, 11 ), true );
		} else {
			// Subscription fields.
			$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $membership_level->id );
			if ( empty( $subscriptions ) ) {
				// No subscription found.
				$r = '';
			} else {
				$field = str_replace( 'membership_', '', $field );
				$r     = call_user_func( array( $subscriptions[0], 'get_' . $field ) );
			}
		}
	} elseif ( in_array( $field, $pmpro_user_meta_fields ) ) {
		// PMPro-related fields stored in user meta.
		$field = 'pmpro_' . $field;
		$r = get_user_meta($user_id, $field, true );
	} elseif ( in_array( $field, $user_column_fields ) ) {
		// wp_users column.
		$r    = $user->{$field};
	} elseif ( $field === 'avatar' ) {
		// Get the user's avatar.
		$r = get_avatar( $user_id );
	} else {
		// Assume user meta.
		$r = get_user_meta( $user_id, $field, true );
	}

	// Check for dates to reformat them.
	if ( in_array( $field, $date_fields ) ) {
		if ( empty( $r ) || $r === '0000-00-00 00:00:00' ) {
			$r = ''; // Empty date.
		} elseif ( is_numeric( $r ) ) {
			$r = date_i18n( get_option( 'date_format' ), $r ); // Timestamp.
		} else {
			$r = date_i18n( get_option( 'date_format' ), strtotime( $r, current_time( 'timestamp' ) ) ); // YYYY-MM-DD/etc format.
		}
	}

	// Check for prices to reformat them.
	if ( in_array( $field, $price_fields ) ) {
		if ( empty( $r ) || $r == '0.00' ) {
			$r = '';
		} else {
			$r = pmpro_escape_price( pmpro_formatPrice( $r ) );
		}
	}

	// If this is a user field, get the display value.
	$user_field = PMPro_Field_Group::get_field( $field );
	if ( ! empty( $user_field ) ) {
		$r = $user_field->displayValue( $r, false );
	}

	// Check for arrays to reformat them.
	if ( is_array( $r ) ) {
		$r = implode( ', ', $r );
	}

	/**
	 * Filter
	 */
	$r = apply_filters( 'pmpro_member_shortcode_field', $r, $user_id, $field );

	return $r;
}
add_shortcode( 'pmpro_member', 'pmpro_member_shortcode' );

/**
 * Strip the [pmpro_member] shortcode from content if the current user can't edit users.
 *
 * @since 2.12.9

 * @param string|array $content The content to strip the shortcode from.
 *                              If an array is passed in, all elements
 *                              will be filtered recursively.
 *                              Non-strings are ignored.
 *
 * @return mixed The content with the shortcode removed. Will be the same type as the input.
 */
function pmpro_maybe_strip_member_shortcode( $content ) {
	// If the user can edit users, we don't need to strip the shortcode.
	if ( current_user_can( 'edit_users' ) ) {
		return $content;
	}

	// If an array is passed in, filter all elements recursively.
	if ( is_array( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content[ $key ] = pmpro_maybe_strip_member_shortcode( $value );
		}
		return $content;
	}

	// If we're not looking at a string, just return it.
	if ( ! is_string( $content ) ) {
		return $content;
	}
	
	// Okay, we have a string, figure out the regex.
	$shortcodeRegex = get_shortcode_regex( array( 'pmpro_member' ) );	

	// Replace shortcode wrapped in block comments.
	$blockWrapperPattern = "/<!-- wp:shortcode -->\s*$shortcodeRegex\s*<!-- \/wp:shortcode -->/s";
	$content = preg_replace( $blockWrapperPattern, '', $content );

	// Replace the shortcode by itself.
	$shortcodePattern = "/$shortcodeRegex/";
	$content = preg_replace( $shortcodePattern, '', $content );

	return $content;
}
add_filter( 'content_save_pre', 'pmpro_maybe_strip_member_shortcode' );
add_filter( 'excerpt_save_pre', 'pmpro_maybe_strip_member_shortcode' );
add_filter( 'widget_update_callback', 'pmpro_maybe_strip_member_shortcode' );

/**
 * Only allow those with the edit_users capability
 * to use the pmpro_member shortcode in post_meta.
 *
 * @since 2.12.9
 * @param int    $meta_id     ID of the meta data entry.
 * @param int    $object_id   ID of the object the meta is attached to.
 * @param string $meta_key    Meta key.
 * @param mixed  $_meta_value Meta value.
 * @return void
 */
function pmpro_maybe_strip_member_shortcode_from_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	// Bail if the value is not a string or array.
	if ( ! is_string( $_meta_value ) && ! is_array( $_meta_value ) ) {
		return;
	}

	// Strip the shortcode from the meta value.
	$stripped_value = pmpro_maybe_strip_member_shortcode( $_meta_value );

	// If there was a change, save our stripped version.
	if ( $stripped_value !== $_meta_value ) {
		update_post_meta( $object_id, $meta_key, $stripped_value );
	}
}
add_action( 'updated_post_meta', 'pmpro_maybe_strip_member_shortcode_from_post_meta', 10, 4 );
