<?php

/**
 * Upgrade to 3.0.2
 *
 * Default sites that are already using outdated page templates to continue using them.
 *
 * @since 3.0.2
 */
function pmpro_upgrade_3_0_2() {
	// Create a $template => $path array of all default page templates.
	$default_templates = array(
		'account' => PMPRO_DIR . '/pages/account.php',
		'billing' => PMPRO_DIR . '/pages/billing.php',
		'cancel' => PMPRO_DIR . '/pages/cancel.php',
		'checkout' => PMPRO_DIR . '/pages/checkout.php',
		'confirmation' => PMPRO_DIR . '/pages/confirmation.php',
		'invoice' => PMPRO_DIR . '/pages/invoice.php',
		'levels' => PMPRO_DIR . '/pages/levels.php',
		'login' => PMPRO_DIR . '/pages/login.php',
		'member_profile_edit' => PMPRO_DIR . '/pages/member_profile_edit.php',
	);

	// Filter $default_templates so that Add Ons can add their own templates.
	$default_templates = apply_filters( 'pmpro_default_page_templates', $default_templates );

	// Loop through each template. For each, if a custom page template is being loaded, default the pmpro_use_custom_page_template_[template] option to 'yes'.
	foreach ( $default_templates as $template => $path ) {
		// Gather information about the default and loaded templates.
		$loaded_path = pmpro_get_template_path_to_load( $template );
		if ( $loaded_path !== $path ) {
			update_option( 'pmpro_use_custom_page_template_' . $template, 'yes' );
		}
	}

	// Update the version number
	update_option( 'pmpro_db_version', '3.02' );
}