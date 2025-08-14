<?php
/**
 * WP Fusion Compatibility for Paid Memberships Pro.
 * This file runs in the plugins_loaded hook to ensure WP Fusion is loaded.
 * 
 * @since TBD
 */

/**
 * Logic to enable PMPro WP Fusion (Lite) compatibility when WP Fusion Lite is active.
 * 
 * @since TBD
 */
if( ! class_exists( 'WP_Fusion_Lite' ) ) {
    return;
}

/**
 * WP Fusion (which has PMPro in it is running), let their code handle things
 */
if( class_exists( 'WP_Fusion' ) ) {
    return;
}

// Include the WP Fusion PMPro specific classes.
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-base.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-hooks.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-batch.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-approvals.php' );
include_once( PMPRO_DIR . '/includes/lib/wp-fusion/class-pmpro-admin.php' );