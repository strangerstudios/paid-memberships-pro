<?php
/**
 * WP Fusion Lite Compatibility
 *
 * Integrates WP Fusion logic for Paid Memberships Pro, see: https://wpfusion.com/documentation/membership/paid-memberships-pro/
 * For integrations into other CRMs the Pro version of WP Fusion is needed. 
 * 
 * @since 3.6
 */

// WP Fusion Pro is active, then let's give WP Fusion Pro priority to include their own PMPro integration code.
if ( class_exists( 'WP_Fusion' ) ) {
	return;
}

// WP Fusion Lite only sets up wp_fusion()->user once it's connected to a CRM. Until then there's nothing to sync to, and the integration would fatal on every membership level change.
if ( ! function_exists( 'wp_fusion' ) || empty( wp_fusion()->user ) ) {
	return;
}

// Include the WP Fusion PMPro specific classes.
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-base.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-hooks.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-batch.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-approvals.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-admin.php' );
