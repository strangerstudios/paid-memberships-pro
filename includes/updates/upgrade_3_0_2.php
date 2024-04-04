<?php

/**
 * Upgrade to 3.0.2
 *
 * Default sites that are already using outdated page templates to continue using them.
 *
 * @since 3.0.2
 */
function pmpro_upgrade_3_0_2() {
    // Check if the site has an outdated page template.
    if ( ! empty( pmpro_get_outdated_page_templates() ) ) {
        // If the site has an outdated page template, set the option to use custom page templates.
        update_option( 'pmpro_use_custom_page_templates', 'yes' );
    }

    // Update the version number
	update_option( 'pmpro_db_version', '3.02' );
}