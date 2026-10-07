<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade to version 3.2
 *
 * We are eliminating the built-in PPE option for Website Payments Pro.
 *
 * @since 3.1
 */
function pmpro_upgrade_3_2() {
	if ( 'paypal' === pmpro_getGateway() ) {
		// Set an option to show the notice about the PPE option being removed.
		update_option( 'pmpro_upgrade_3_2_notice_wpp', true );
	}
}

/**
 * Show a notice on the PMPro dashboard if there are orders with user IDs greater than 4294967295.
 * This is to alert users that they may have run into issues with user IDs being too large for the int(11) column.
 *
 * @since 3.1
 */
function pmpro_show_upgrade_3_2_notice_wpp() {
	// Check if we need to show the notice.
	if ( ! get_option( 'pmpro_upgrade_3_2_notice_wpp' ) ) {
		return;
	}

	// Only show to users who can manage options.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Only show on PMPro admin pages.
	if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; only checks which admin page is being viewed.
		return;
	}

	// Check if the user has dismissed the notice.
	if ( ! empty( $_REQUEST['pmpro-upgrade_3_2_notice_wpp'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'pmpro_upgrade_3_2_notice_wpp_dismiss' ) ) {
		delete_option( 'pmpro_upgrade_3_2_notice_wpp' );
		return;
	}

	// Show a dismissable notice. Do not show a link to scrub data.
	?>
	<div class="notice notice-warning" id="pmpro-upgrade-3-2-notice">
		<p><strong><?php esc_html_e( 'Important Notice: Paid Memberships Pro v3.2 Update', 'paid-memberships-pro' ); ?></strong></p>
		<p>
			<?php
			printf(
				wp_kses_post(
					__( 'In previous versions of PMPro, PayPal Express was automatically added as a second payment option on the checkout page when using the Website Payments Pro gateway. This feature has been removed in PMPro v3.2 but can be added back using the PMPro Add PayPal Express Option at Checkout Add On. For more information, read our <a href="%s">v3.2 release notes post here</a>.', 'paid-memberships-pro' )
				),
				esc_url( 'https://www.paidmembershipspro.com/pmpro-update-3-2/' )
			);
			?>
		</p>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'pmpro-upgrade_3_2_notice_wpp', '1' ), 'pmpro_upgrade_3_2_notice_wpp_dismiss' ) ); ?>"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'pmpro_show_upgrade_3_2_notice_wpp' );

