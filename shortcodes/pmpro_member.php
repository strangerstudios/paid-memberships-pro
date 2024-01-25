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
			),
			$atts
		)
	);

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

	if ( in_array( $field, $pmpro_level_fields ) || in_array( $field, $pmpro_subscription_fields ) ) {
		// Fields about the user's membership or subscription.
		// Get the membership level to show.
		$membership_level = null;
		if ( empty( $levels ) ) {
			// Grab any one of the user's levels.
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );
		} else {
			// Grab the first level in the list.
			$levels = explode( ',', $levels );
			foreach ( $levels as $level_id ) {
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
		$user = get_userdata( $user_id );
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

	// If this is a user field with an associative array of options, get the label(s) for the value(s).
	$r = pmpro_get_label_for_user_field_value( $field, $r );

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
