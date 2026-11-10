<?php
/**
 * WooCommerce Compatibility
 *
 * This code resolves common conflicts between PMPro and WooCommerce.
 * For more advanced integration, see the PMPro WooCommerce Add-On.
 * https://www.paidmembershipspro.com/add-ons/pmpro-woocommerce/
 *
 * @since 2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
/**
 * Make sure the PMPro lost password form
 * doesn't submit to the WC lost password form.
 *
 * @since 2.3
 */
function pmpro_maybe_remove_wc_lostpassword_url_filter() {
	global $pmpro_pages;
	
	if ( ! empty( $pmpro_pages ) && ! empty( $pmpro_pages['login'] ) && is_page( $pmpro_pages['login'] ) ) {
		remove_filter( 'lostpassword_url', 'wc_lostpassword_url', 10, 1 );		
	}
}	
add_action( 'wp', 'pmpro_maybe_remove_wc_lostpassword_url_filter' );

/**
 * Don't let WooCommerce delete current or past members as inactive accounts.
 *
 * WooCommerce's "Retain inactive accounts" setting deletes customers and subscribers who
 * haven't logged in or ordered recently. WooCommerce only applies this filter while building
 * the WP_User_Query that finds those users, so we use it to exclude members from that query alone.
 * It runs last so that no other callback on this filter can run a user query after we arm the exclusion.
 *
 * @since TBD
 *
 * @param array $roles Roles whose inactive accounts WooCommerce deletes.
 * @return array
 */
function pmpro_arm_wc_inactive_account_exclusion( $roles ) {
	add_action( 'pre_user_query', 'pmpro_exclude_members_from_wc_inactive_account_deletion' );
	return $roles;
}
add_filter( 'woocommerce_delete_inactive_account_roles', 'pmpro_arm_wc_inactive_account_exclusion', PHP_INT_MAX );

/**
 * Leave anyone who has ever had a membership out of WooCommerce's inactive accounts query.
 *
 * @since TBD
 *
 * @param WP_User_Query $query The user query.
 */
function pmpro_exclude_members_from_wc_inactive_account_deletion( $query ) {
	global $wpdb;

	// Make sure this is the inactive accounts query, which gets user IDs and filters on wc_last_active.
	$meta_query = $query->get( 'meta_query' );
	if ( 'ID' !== $query->get( 'fields' ) || empty( $meta_query ) || ! is_array( $meta_query ) || ! in_array( 'wc_last_active', array_column( $meta_query, 'key' ), true ) ) {
		return;
	}

	// Only change this one query.
	remove_action( 'pre_user_query', 'pmpro_exclude_members_from_wc_inactive_account_deletion' );

	$query->query_where .= " AND {$wpdb->users}.ID NOT IN ( SELECT user_id FROM {$wpdb->pmpro_memberships_users} )";
}

/**
 * Note on WooCommerce's "Retain inactive accounts" setting that members are excluded from its cleanup.
 *
 * @since TBD
 *
 * @param array $settings WooCommerce account settings.
 * @return array
 */
function pmpro_wc_inactive_accounts_setting_description( $settings ) {
	foreach ( $settings as $key => $setting ) {
		if ( isset( $setting['id'] ) && 'woocommerce_delete_inactive_accounts' === $setting['id'] ) {
			$settings[ $key ]['desc'] = ( ! empty( $setting['desc'] ) ? $setting['desc'] . ' ' : '' ) . sprintf(
				'<span style="display: inline-block; margin-top: 8px; padding: 8px 12px; background: #f0f6fc; border-left: 4px solid #72aee6; color: #1d2327;"><strong>%s</strong> %s</span>',
				esc_html__( 'Paid Memberships Pro:', 'paid-memberships-pro' ),
				esc_html__( 'Current and past members are excluded from this cleanup.', 'paid-memberships-pro' )
			);
			break;
		}
	}

	return $settings;
}
add_filter( 'woocommerce_account_settings', 'pmpro_wc_inactive_accounts_setting_description' );
