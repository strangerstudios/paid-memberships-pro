<?php

/**
 * Check if certain plugins or themes are installed and activated
 * and if found dynamically load the relevant /includes/compatibility/ files.
 */
function pmpro_compatibility_checker () {
    $compat_checks = array(
        array(
            'file' => 'siteorigin.php',
            'check_type' => 'constant',
            'check_value' => 'SITEORIGIN_PANELS_VERSION',
        ),
        array(
            'file' => 'elementor.php',
            'check_type' => 'constant', 
            'check_value' => 'ELEMENTOR_VERSION'
        ),
        array(
            'file' => 'beaver-builder.php',
            'check_type' => 'constant', 
            'check_value' => 'FL_BUILDER_VERSION'
        ),
        array(
            'file' => 'theme-my-login.php',
            'check_type' => 'class',
            'check_value' => 'Theme_My_Login'
        ),
		array(
			'file' => 'woocommerce.php',
			'check_type' => 'constant',
			'check_value' => 'WC_PLUGIN_FILE'
		),
		array(
			'file' => 'wp-engine.php',
			'check_type' => 'function',
			'check_value' => 'wpe_filter_site_url'
		)
    );

    foreach ( $compat_checks as $key => $value ) {
        if ( ( $value['check_type'] == 'constant' && defined( $value['check_value'] ) )
          || ( $value['check_type'] == 'function' && function_exists( $value['check_value'] ) )
          || ( $value['check_type'] == 'class' && class_exists( $value['check_value'] ) ) ) {
            include( PMPRO_DIR . '/includes/compatibility/' . $value['file'] ) ;
        }
    }
}
add_action( 'plugins_loaded', 'pmpro_compatibility_checker' );