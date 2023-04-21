<?php

/**
 * Calculate the profile start date to be sent to the payment gateway.
 *
 * @since 2.9
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
		 * @since 1.4
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

/**
 * Set up rewrite rules so that pretty permalinks can be used on the checkout page.
 *
 * @since TBD
 */
function pmpro_checkout_rewrite_rules() {
	global $pmpro_pages;

	// If the checkout page is not set, return.
	if ( empty( $pmpro_pages['checkout'] ) ) {
		return;
	}

	// Get the permalink for the checkout page.
	$checkout_page = get_the_permalink( $pmpro_pages['checkout'] );

	// Get the base site url.
	$site_url = get_site_url();

	// Get the base checkout page url.
	$checkout_page_base = str_replace( $site_url . '/', '', $checkout_page );

	// Add the rewrite rule.
	add_rewrite_rule( $checkout_page_base . '([^/]*)/?', 'index.php?page_id=' . $pmpro_pages['checkout'] . '&pmpro_checkout_level=$matches[1]', 'top' );
}
add_action( 'init', 'pmpro_checkout_rewrite_rules' );

/**
 * Adding the pmpro_checkout_level query var so that it can be used in the rewrite rule.
 *
 * @since TBD
 *
 * @param array $query_vars The query vars.
 * @return array The query vars.
 */
function pmpro_checkout_custom_query_vars( $query_vars ) {

    $query_vars[] = 'pmpro_checkout_level';
    
    return $query_vars;
}

add_filter( 'query_vars', 'pmpro_checkout_custom_query_vars', 10, 1 );
