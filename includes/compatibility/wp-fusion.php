<?php
/**
 * WP Fusion Compatibility for Paid Memberships Pro.
 * 
 * @since TBD
 */

/**
 * Logic to enable PMPro WP Fusion (Lite) compatibility when WP Fusion Lite is active.
 * 
 * @since TBD
 */
function pmpro_wpfusion_compatibility() {

    // If WP Fusion Lite is not active, then we don't need to do anything.
    if ( ! class_exists( 'WP_Fusion_Lite' ) ) {
        return;
    }

    // If WP Fusion (which has PMPro in it) is running, let their code handle things
    if ( class_exists( 'WP_Fusion' ) ) {
        return;
    }

    // Include the WP Fusion PMPro specific classes.
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-base.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-hooks.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-batch.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-approvals.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-admin.php' );
}
add_action( 'plugins_loaded', 'pmpro_wpfusion_compatibility' );
