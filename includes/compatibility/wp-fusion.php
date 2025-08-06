<?php
/**
 * WP Fusion Compatibility
 * 
 * @since TBD
 */

// Running this after plugins_loaded to ensure WP Fusion is loaded.
function pmpro_wpfusion_compatibility() {

    /**
     * WP Fusion Lite is not active, we shouldn't run anything WP Fusion related for now.
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

    /**
     * ToDo
     * Rename these classes to prevent any potential conflicts with WP Fusion.
     */
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-base.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-hooks.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-batch.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-approvals.php' );
    include_once( PMPRO_DIR . '/includes/compatibility/wp-fusion/class-pmpro-admin.php' );

}
add_action( 'plugins_loaded', 'pmpro_wpfusion_compatibility' );