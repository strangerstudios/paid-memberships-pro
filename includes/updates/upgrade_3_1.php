<?php
/**
 * Upgrade to version 3.1
 *
 * We are eliminating the SSL Seal Code setting.
 * We are also changing the default text and adding a setting for protected content messages.
 *
 * @since 3.1
 */
function pmpro_upgrade_3_1() {
	global $wpdb;

    delete_option( 'pmpro_sslseal' );
    delete_option( 'pmpro_accepted_credit_cards' );

	// Check if we have a setting for pmpro_nonmembertext and compare it to the default.
    $pmpro_nonmembertext = get_option( 'pmpro_nonmembertext' );
    if ( $pmpro_nonmembertext !== false ) {
		// We have text set, let's compare it to the old default.
		$old_default_nonmembertext = __( 'This content is for !!levels!! members only.<br /><a href="!!levels_page_url!!">Join Now</a>', 'paid-memberships-pro' );
		if ( $pmpro_nonmembertext == $old_default_nonmembertext ) {
			// This is the old default. Delete it.
			delete_option( 'pmpro_nonmembertext' );
		}
    }

	// Delete the pmpro_stripe_update_billing_flow option. The update billing flow now matches the payment flow.
	delete_option( 'pmpro_stripe_update_billing_flow' );

	// In this update, we updated some user_id columns from int(11) to bigint(20). We want to detect sites that may have run into issues with int(11) previously.
	// The plan is to check if there is a PMPro order with a user ID greater than 4294967295. If so, we will show a notice on the PMPro dashboard.
	// We are checking orders because on the vast majority of sites, users will always have an order created if they have a subscription associated with them.
	global $wpdb;
	$high_user_id_order_exists = $wpdb->get_var( "SELECT user_id FROM $wpdb->pmpro_membership_orders WHERE user_id > 4294967295 LIMIT 1" );
	if ( $high_user_id_order_exists ) {
		update_option( 'pmpro_upgrade_3_1_notice', true );
	}
}

/**
 * Show a notice on the PMPro dashboard if there are orders with user IDs greater than 4294967295.
 * This is to alert users that they may have run into issues with user IDs being too large for the int(11) column.
 *
 * @since 3.1
 */
function pmpro_show_upgrade_3_1_notice() {
	// Check if we need to show the notice.
	if ( ! get_option( 'pmpro_upgrade_3_1_notice' ) ) {
		return;
	}

	// Only show on PMPro admin pages.
	if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
		return;
	}

	// Check if the user has dismissed the notice.
	if ( ! empty( $_REQUEST['pmpro-hide-upgrade_3_1-notice'] ) ) {
		delete_option( 'pmpro_upgrade_3_1_notice' );
		return;
	}

	// Show a dismissable notice. Do not show a link to scrub data.
	?>
	<div class="notice notice-warning" id="pmpro-upgrade-3-1-notice">
		<p><strong><?php esc_html_e( 'Important Notice: Paid Memberships Pro v3.1 Update', 'paid-memberships-pro' ); ?></strong></p>
		<p>
			<?php
			printf(
				wp_kses_post(
					__( 'In previous versions of PMPro, we discovered an issue where subscriptions were not linked to the correct user ID in the database. The current version you are running has resolved this issue, however we detect that some of your subscriptions need to be manually corrected. For more information and steps to resolve this for your site, read our <a href="%s">v3.1 release notes post here</a>.', 'paid-memberships-pro' )
				),
				esc_url( 'https://www.paidmembershipspro.com/pmpro-update-3-1/' )
			);
			?>
		</p>
		<p>
			<a href="<?php echo esc_url( add_query_arg( 'pmpro-hide-upgrade_3_1-notice', '1' ) ); ?>"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'pmpro_show_upgrade_3_1_notice' );

