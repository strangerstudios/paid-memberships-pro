<?php

/**
 * Calculate the profile start date to be sent to the payment gateway.
 *
 * @since TBD
 *
 * @param MemberOrder $order       The order to calculate the start date for.
 * @param string      $date_format The format to use when formatting the profile start date.
 * @param bool        $filter      Whether to filter the profile start date.
 *
 * @return string The profile start date in UTC time and the desired $date_format.
 */
function pmpro_calculate_profile_start_date( $order, $date_format, $filter = true ) {
	// Calculate the profile start date.
	$profile_start_date = date_i18n( 'Y-m-d H:i:s', strtotime( '+ ' . $order->BillingFrequency . ' ' . $order->BillingPeriod ) );

	// Filter the profile start date if needed.
	if ( $filter ) {
		/**
		 * Filter the profile start date.
		 *
		 * Note: We are passing $profile_start_date to strtotime before returning so
		 * YYYY-MM-DD HH:MM:SS is not 100% necessary, but we should transition add ons and custom code
		 * to use that format in case we update this code in the future.
		 *
		 * @since TBD
		 *
		 * @param string $profile_start_date The profile start date in UTC YYYY-MM-DD HH:MM:SS format.
		 * @param MemberOrder $order         The order that the profile start date is being calculated for.
		 *
		 * @return string The profile start date in UTC YYYY-MM-DD HH:MM:SS format.
		 */
		$profile_start_date = apply_filters( 'pmpro_profile_start_date', $profile_start_date, $order );
	}

	// Convert $profile_start_date to correct format.
	return date_i18n( $date_format, strtotime( $profile_start_date ) );
}
