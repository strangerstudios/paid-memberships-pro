<?php

/**
 * Get the template path that should be loaded for a given page.
 *
 * @since 2.11
 *
 * @param null   $page_name - Name of the page/template
 * @param string $where - `local` or `url` (whether to load from FS or over http)
 * @param string $type - Type of template (valid: 'email' or 'pages', 'adminpages', 'preheader')
 * @param string $ext - File extension ('php', 'html', 'htm', etc)
 * @return string|null - The HTML for the template or null if not found.
 */
function pmpro_get_template_path_to_load( $page_name = null, $where = 'local', $type = 'pages', $ext = 'php' ) {
	// called from page handler shortcode
	if ( is_null( $page_name ) ) {
		global $pmpro_page_name;
	   $page_name = $pmpro_page_name;
   }
   if ( $where == 'local' ) {
	   // template paths in order of priority (array gets reversed)
	   $default_templates = array(
		   PMPRO_DIR . "/{$type}/{$page_name}.{$ext}", // default plugin path
		   get_template_directory() . "/paid-memberships-pro/{$type}/{$page_name}.{$ext}", // parent theme
		   get_stylesheet_directory() . "/paid-memberships-pro/{$type}/{$page_name}.{$ext}", // child / active theme
	   );
   } elseif ( $where == 'url' ) {
	   // template paths in order of priority (array gets reversed)
	   $default_templates = array(
		   PMPRO_URL . "/{$type}/{$page_name}.{$ext}", // default plugin path
		   get_template_directory_uri() . "/paid-memberships-pro/{$type}/{$page_name}.{$ext}", // parent theme
		   get_stylesheet_directory_uri() . "/paid-memberships-pro/{$type}/{$page_name}.{$ext}", // child / active theme
	   );
   }
   // Valid types: 'email', 'pages'
   $templates = apply_filters( "pmpro_{$type}_custom_template_path", $default_templates, $page_name, $type, $where, $ext );
   $user_templates = array_diff( $templates, $default_templates );
   $allowed_default_templates = array_intersect( $templates, $default_templates );
   // user specified a custom template path, so it has priority.
   if ( ! empty( $user_templates ) ) {
	   $templates = array_merge($allowed_default_templates, $user_templates);
   }
   // last element included in the array is the most first one we try to load
	$templates = array_reverse( $templates );

	// look for template file to include
	foreach ( $templates as $template_path ) {
		// If loading a local file, check if it exists first
		if ( $where == 'url' || file_exists( $template_path ) ) {
			return $template_path;
		}
	}

	return null;
}

/**
 * Loads a template from one of the default paths (PMPro plugin or theme), or from filtered path
 *
 * @param null   $page_name - Name of the page/template
 * @param string $where - `local` or `url` (whether to load from FS or over http)
 * @param string $type - Type of template (valid: 'email' or 'pages', 'adminpages', 'preheader')
 * @param string $ext - File extension ('php', 'html', 'htm', etc)
 * @return string - The HTML for the template.
 *
 * TODO - Allow localized template files to be loaded?
 *
 * @since 1.8.9
 */
function pmpro_loadTemplate( $page_name = null, $where = 'local', $type = 'pages', $ext = 'php' ) {
	// Get the path of the template to load.
	$path = pmpro_get_template_path_to_load( $page_name, $where, $type, $ext );

	// Get the default plugin path.
	$default_path = PMPRO_DIR . "/{$type}/{$page_name}.{$ext}";

	// If this is a custom page template, check if we should load it.
	if ( $type = 'pages' && $path !== $default_path ) {
		$use_custom_page_template = get_option( 'pmpro_use_custom_page_template_' . $page_name );
		switch( $use_custom_page_template ) {
			case 'yes':
				break;
			case 'no':
				$path = $default_path;
				break;
			default:
				// Check if the custom template is newer than the default template.
				$default_version = pmpro_get_version_for_page_template_at_path( $default_path );
				$custom_version = pmpro_get_version_for_page_template_at_path( $path );
				if ( $default_version != $custom_version ) {
					$path = $default_path;
				}
				break;
		}
	}

	// If the template exists, load it.
	ob_start();
	if ( ! empty( $path ) && file_exists( $path ) ) {
		include $path;
	}
	$template = ob_get_clean();

	// Return template content.
	return $template;
}

/**
 * Get the version of a page template at a given path.
 *
 * @since 2.11
 *
 * @param string $path Path to the page template.
 * @return string|null Version of the page template, or null if not found.
 */
function pmpro_get_version_for_page_template_at_path( $path ) {
	if ( ! file_exists( $path ) ) {
		return null;
	}

	$file_header_data = get_file_data( $path, array( 'version' => 'version' ) );
	return empty( $file_header_data['version'] ) ? null : $file_header_data['version'];
}

/**
 * List all outdated page templates being used.
 *
 * @since 2.11
 *
 * @return array List of outdated page templates.
 */
function pmpro_get_outdated_page_templates() {
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

	// Loop through each template. For each, get the version for the default template and
	// compare it to the version for the template that is actually being loaded. If the
	// version for the template that is actually being loaded is older, add it to an
	// $outdated_templates array.
	$outdated_templates = array(); // Array of $template => array( 'default_version' => $default_version, 'loaded_version' => $loaded_version, 'loaded_path' => $loaded_path ).
	foreach ( $default_templates as $template => $path ) {
		// Check if the custom page template would actually be loaded.
		if ( 'yes' !== get_option( 'pmpro_use_custom_page_template_' . $template ) ) {
			continue;
		}

		// Get the version for the default template.
		$default_version = pmpro_get_version_for_page_template_at_path( $path );

		// All templates started at 2.0. If the core version is still outdated, let's not call the custom template outdated.
		if ( '2.0' === $default_version ) {
			continue;
		}

		// Get the version for the template that is actually being loaded.
		$loaded_path = pmpro_get_template_path_to_load( $template );
		$loaded_version = pmpro_get_version_for_page_template_at_path( $loaded_path );

		// If either version is null or the loaded version is older than the default version, add it to the $outdated_templates array.
		if ( $default_version !== $loaded_version ) {
			$outdated_templates[ $template ] = array(
				'default_version' => $default_version,
				'loaded_version' => $loaded_version,
				'loaded_path' => $loaded_path,
			);
		}
	}
	return $outdated_templates;
}

/**
 * Displays a warning notice regarding outdated templates
 *
 * @since 2.11
 *
 * @return mixed|string - Empty, or the HTML containing the notice
 */
function pmpro_page_template_notices() {

	//Only show this notice on PMPro admin pages
	if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false || $_REQUEST['page'] === 'pmpro-pagesettings' ) {
		return;
	}

	/**
	 * Permanently disable any template version notices
	 *
	 * @param bool To permanently hide template version notices
	 * 
	 * @since 2.11
	 *
	 */
	$hide_template_notices = apply_filters( 'pmpro_hide_template_version_notices', (bool) get_option( 'pmpro_disable_outdated_template_warning' ) );

	if( $hide_template_notices ) {
		return;
	}

	$outdated_templates = pmpro_get_outdated_page_templates();

	if( ! empty( $outdated_templates ) ) {
		// Build a string listing the outdated template names and paths.
		$outdated_templates_string = '';
		foreach ( $outdated_templates as $template_name => $template_data ) {
			$outdated_templates_string .= '<li><strong>' . esc_html( $template_name ) . '</strong> - ' . esc_html( $template_data['loaded_path'] ) . '</li>';
		}
		?>
		<div class="notice notice-error pmpro_notification pmpro_notification-error">
			<div class="pmpro_notification-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="pmpro_notification-content">
				<h3><?php esc_html_e( 'Outdated Page Templates Detected', 'paid-memberships-pro' ); ?></h3>
				<p>
					<?php
						esc_html_e( 'Paid Memberships Pro has detected that your site is using outdated frontend page templates. If you are experiencing an issue on the frontend of your site, use the Settings > Pages screen to change which custom template is being loaded for your frontend pages.', 'paid-memberships-pro' );
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-pagesettings#pmpro-custom-page-template-settings' ), admin_url( 'admin.php' ) ) ); ?>" class="button"><?php esc_html_e( 'View outdated page templates', 'paid-memberships-pro' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}	

}
add_action( 'admin_notices', 'pmpro_page_template_notices' );