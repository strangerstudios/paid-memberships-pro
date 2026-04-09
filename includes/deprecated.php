<?php
/**
 * Deprecated hooks, filters and functions
 *
 * @since  2.0
 */

/**
 * Check for deprecated filters.
 */
function pmpro_init_check_for_deprecated_filters() {
	global $wp_filter;

	// Deprecated filter name => new filter name (or null if there is no alternative).
	$pmpro_map_deprecated_filters = array(
		'pmpro_getfile_extension_blacklist' => 'pmpro_getfile_extension_blocklist',
		'pmpro_default_field_group_label'   => 'pmprorh_section_header',
		'pmpro_stripe_subscription_deleted' => null,
		'pmpro_subscription_cancelled'      => null,
		'pmpro_longform_address'            => null,
		'pmpro_include_cardtype_field'      => null,
		'pmpro_paypal_button_image'         => null,
	);

	foreach ( $pmpro_map_deprecated_filters as $old => $new ) {
		if ( has_filter( $old ) ) {
			if ( ! empty( $new ) ) {
				// We have an alternative filter. Let's show an error message and forward to that new filter.
				/* translators: 1: the old hook name, 2: the new or replacement hook name */
				trigger_error( esc_html( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro. Please use the %2$s hook instead.', 'paid-memberships-pro' ), $old, $new ) ) );

				// Add filters back using the new tag.
				foreach( $wp_filter[$old]->callbacks as $priority => $callbacks ) {
					foreach( $callbacks as $callback ) {
						add_filter( $new, $callback['function'], $priority, $callback['accepted_args'] );
					}
				}
			} else {
				// We don't have an alternative filter. Let's just show an error message.
				/* translators: 1: the old hook name */
				trigger_error( esc_html( sprintf( esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro and may not be available in future versions.', 'paid-memberships-pro' ), $old ) ) );
			}
		}
	}
}
add_action( 'init', 'pmpro_init_check_for_deprecated_filters', 99 );

/**
 * Previously used function for class definitions for input fields to see if there was an error.
 *
 * To filter field values, we now recommend using the `pmpro_element_class` filter.
 *
 */
function pmpro_getClassForField( $field ) {
	return pmpro_get_element_class( '', $field );
}

/**
 * Redirect some old menu items to their new location
 */
function pmpro_admin_init_redirect_old_menu_items() {
	if ( is_admin()
		&& ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro_license_settings'
		&& basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) == 'options-general.php' ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pmpro-license' ) );
		exit;
	}
}
add_action( 'init', 'pmpro_admin_init_redirect_old_menu_items' );

/**
 * Check for active Add Ons that are not yet MMPU compatible.
 *
 * @since 3.0
 * @return array[string] Add On names that are not yet MMPU compatible.
 */
function pmpro_get_mmpu_incompatible_add_ons() {
	// Add ons will use this filter to add their own names if they are not yet MMPU compatible.
	return apply_filters( 'pmpro_mmpu_incompatible_add_ons', array() );
}

/**
 * Get a list of deprecated PMPro Add Ons.
 *
 * @since 2.11
 *
 * @return array Add Ons that are deprecated.
 */
function pmpro_get_deprecated_add_ons() {
	global $wpdb;

	// Check if the RH restrict by username or email feature was being used.
	static $pmpro_register_helper_restricting_by_email_or_username = null;
	if ( ! isset( $pmpro_register_helper_restricting_by_email_or_username ) ) {
		$sqlQuery = "SELECT option_value FROM $wpdb->options WHERE option_name LIKE 'pmpro_level_%_restrict_emails' OR option_name LIKE 'pmpro_level_%_restrict_usernames' AND option_value <> '' LIMIT 1";
		$pmpro_register_helper_restricting_by_email_or_username = $wpdb->get_var( $sqlQuery );

		// If the option was not found then the feature was not being used.
		if( $pmpro_register_helper_restricting_by_email_or_username === null ) {
			$pmpro_register_helper_restricting_by_email_or_username = false;
		} else {
			$pmpro_register_helper_restricting_by_email_or_username = true;
		}
	}

	// If the RH restrict by username or email feature was being used, set the message.
	if ( $pmpro_register_helper_restricting_by_email_or_username ) {
		$pmpro_register_helper_message = sprintf( __( 'Restricting members by username or email was not merged into Paid Memberships Pro. If this feature was being used, a <a href="%s" target="_blank">code recipe</a> will be needed to continue using this functionality.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/restrict-membership-signup-by-email-or-username/' );
	} else {
		$pmpro_register_helper_message = '';
	}
	
	// Set the array of deprecated Add Ons.
	$deprecated = array(
		'pmpro-member-history' => array(
			'file' => 'pmpro-member-history.php',
			'label' => 'Member History'
		),
		'pmpro-email-templates' => array(
			'file' => 'pmpro-email-templates.php',
			'label' => 'Email Templates'
		),
		'pmpro-email-templates-addon' => array(
			'file' => 'pmpro-email-templates.php',
			'label' => 'Email Templates'
		),
		'pmpro-better-logins-report' => array(
			'file' => 'pmpro-better-logins-report.php',
			'label' => 'Better Logins Report'
		),
		'pmpro-multiple-memberships-per-user' => array(
			'file' => 'pmpro-multiple-memberships-per-user.php',
			'label' => 'Multiple Memberships Per User'
		),
		'pmpro-cancel-on-next-payment-date' => array(
			'file' => 'pmpro-cancel-on-next-payment-date.php',
			'label' => 'Cancel on Next Payment Date'
    ),
		'pmpro-stripe-billing-limits' => array(
			'file' => 'pmpro-stripe-billing-limits.php',
			'label' => 'Stripe Billing Limits'
		),
		'pmpro-register-helper' => array(
			'file' => 'pmpro-register-helper.php',
			'label' => 'Register Helper',
			'message' => $pmpro_register_helper_message
		),
		'pmpro-table-pages' => array(
			'file' => 'pmpro-table-pages.php',
			'label' => 'Table Layout Plugin Pages'
		),
		'pmpro-recurring-emails' => array(
			'file' => 'pmpro-recurring-emails.php',
			'label' => 'Recurring Emails'
		),
		'pmpro-subscription-check' => array(
			'file' => 'pmpro-subscription-check.php',
			'label' => 'Subscription Check'
		),
	);
	
	$deprecated = apply_filters( 'pmpro_deprecated_add_ons_list', $deprecated );
	
	// If the list is empty or not an array, just bail.
	if ( empty( $deprecated ) || ! is_array( $deprecated ) ) {
		return array();
	}

	return $deprecated;
}

// Check if installed, deactivate it and show a notice now.
function pmpro_check_for_deprecated_add_ons() {
	$deprecated = pmpro_get_deprecated_add_ons();
  	$deprecated_active = array();
	$has_messages = false;
	foreach( $deprecated as $key => $values ) {
		$path = '/' . $key . '/' . $values['file'];
		if ( file_exists( WP_PLUGIN_DIR . $path ) ) {
			$deprecated_active[] = $values;
			if ( ! empty( $values['message'] ) ) {
				$has_messages = true;
			}

			// Try to deactivate it if it's enabled.
			if ( is_plugin_active( plugin_basename( $path ) ) ) {
				deactivate_plugins( $path );
			}
		}
	}

	// If any deprecated add ons are active, show warning.
	if ( ! empty( $deprecated_active ) && is_array( $deprecated_active ) ) {
		// Only show on certain pages.
		if ( ! isset( $_REQUEST['page'] ) || strpos( sanitize_text_field( $_REQUEST['page'] ), 'pmpro' ) === false  ) {
			return;
		}
		?>
		<div class="notice notice-warning">
		<p>
			<?php
				// translators: %s: The list of deprecated plugins that are active.
				echo wp_kses(
					sprintf(
						__( 'Some Add Ons are now merged into the Paid Memberships Pro core plugin. The features of the following plugins are now included in PMPro by default. You should <strong>delete these unnecessary plugins</strong> from your site: <em><strong>%s</strong></em>.', 'paid-memberships-pro' ),
						implode( ', ', wp_list_pluck( $deprecated_active, 'label' ) )
					),
					array(
						'strong' => array(),
						'em' => array(),
					)
				);
			?>
		</p>
		<?php
		// If there are any messages, show them.
		if ( $has_messages ) {
			?>
			<ul>
				<?php
				foreach( $deprecated_active as $deprecated ) {
					if ( empty( $deprecated['message'] ) ) {
						continue;
					}
					?>
					<li>
						<strong><?php echo esc_html( $deprecated['label'] ); ?></strong>:
						<?php
						echo wp_kses(
							$deprecated['message'],
							array(
								'a' => array(
								'href' => array(),
								'target' => array(),
							) )
						);
						?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpro_check_for_deprecated_add_ons' );

/**
 * Remove the "Activate" link on the plugins page for deprecated add ons.
 *
 * @since 2.11
 *
 * @param array  $actions An array of plugin action links.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @return array $actions An array of plugin action links.
 */
 function pmpro_deprecated_add_ons_action_links( $actions, $plugin_file ) {
	$deprecated = pmpro_get_deprecated_add_ons();

	foreach( $deprecated as $key => $values ) {
		if ( $plugin_file == $key . '/' . $values['file'] ) {
			$actions['activate'] = esc_html__( 'Deprecated', 'paid-memberships-pro' );
		}
	}

	return $actions;
}
add_filter( 'plugin_action_links', 'pmpro_deprecated_add_ons_action_links', 10, 2 );

/**
 * Get the list of deprecated gateways.
 *
 * The 2Checkout gateway was deprecated in v2.6.
 * Cybersource was deprecated in 2.10.
 * PayPal Website Payments Pro was deprecated in 2.10.
 * Authorize.net was deprecated in 3.2.
 * PayFlow, PayPal Standard, and Braintree were deprecated in 3.4.
 * PayPal Express was deprecated in 3.7.1.
 * PayPal Website Payments Pro was renamed from 'paypal' to 'paypalwpp' in 3.7.1.
 *
 * @since 3.5
 */
function pmpro_get_deprecated_gateways() {
	return apply_filters( 'pmpro_deprecated_gateways', array(
		'twocheckout',
		'cybersource',
		'paypalwpp',
		'authorizenet',
		'payflowpro',
		'paypalstandard',
		'braintree',
		'paypalexpress',
	) );
}

/**
 * Adds back deprecated gateways if they have ever been the selected gateway.
 * In future versions, we will remove gateway code entirely.
 * And you will have to use a stand alone add on for those gateways
 * or choose a new gateway.
 */
function pmpro_check_for_deprecated_gateways() {
	$undeprecated_gateways = get_option( 'pmpro_undeprecated_gateways' );
	if ( empty( $undeprecated_gateways ) ) {
		$undeprecated_gateways = array();
	} elseif ( is_string( $undeprecated_gateways ) ) {
		// pmpro_setOption turns this into a comma separated string
		$undeprecated_gateways = explode( ',', $undeprecated_gateways );
	}
	$default_gateway = get_option( 'pmpro_gateway' );

	$deprecated_gateways = pmpro_get_deprecated_gateways();
	foreach ( $deprecated_gateways as $deprecated_gateway ) {
		if ( $default_gateway === $deprecated_gateway || in_array( $deprecated_gateway, $undeprecated_gateways ) ) {
			require_once( PMPRO_DIR . '/classes/gateways/class.pmprogateway_' . $deprecated_gateway . '.php' );
			if ( ! in_array( $deprecated_gateway, $undeprecated_gateways ) ) {
				$undeprecated_gateways[] = $deprecated_gateway;
				update_option( 'pmpro_undeprecated_gateways', $undeprecated_gateways );
			}
		}
	}
}

/**
 * Disable uninstall script for duplicates
 */
function pmpro_disable_uninstall_script_for_duplicates( $file ) {
	// bail if not a duplicate
	if ( ! in_array( $file, array_keys( pmpro_get_plugin_duplicates() ) ) ) {
		return;
	}

	// disable uninstall script
	if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		rename(
			WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php',
			WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall-disabled.php'
		);
	}
}
add_action( 'pre_uninstall_plugin', 'pmpro_disable_uninstall_script_for_duplicates' );

/**
 * @return array
 */
function pmpro_get_plugin_duplicates() {
	$all_plugins          = get_plugins();
	$active_plugins_names = get_option( 'active_plugins' );

	$multiple_installations = array();
	foreach ( $all_plugins as $plugin_name => $plugin_headers ) {
		// skip all active plugins
		if ( in_array( $plugin_name, $active_plugins_names ) ) {
			continue;
		}

		// skip plugins without a folder
		if ( false === strpos( $plugin_name, '/' ) ) {
			continue;
		}

		// check if plugin file is paid-memberships-pro.php
		// or Plugin Name: Paid Memberships Pro
		list( $plugin_folder, $plugin_mainfile_php ) = explode( '/', $plugin_name );
		if ( 'paid-memberships-pro.php' === $plugin_mainfile_php || 'Paid Memberships Pro' === $plugin_headers['Name'] ) {
			$multiple_installations[ $plugin_name ] = $plugin_headers;
		}
	}

	return $multiple_installations;
}

/**
 * Show admin notice if site was using a custom-loaded frontend.css file.
 * We no longer enqueue the frontend.css override file by default.
 *
 * @since 3.1
 */
function pmpro_was_loading_frontend_css_notice() {
	global $current_user;

	// If we are not on a PMPro admin page, don't show the notice.
	if ( ! isset( $_REQUEST['page'] ) || ( isset( $_REQUEST['page'] ) && 'pmpro-' !== substr( $_REQUEST['page'], 0, 6 ) ) ) {
		return;
	}

	// Determine if this site was loading a custom frontend.css override file.
	if ( ! file_exists( get_stylesheet_directory() . '/paid-memberships-pro/css/frontend.css' ) && ! file_exists( get_template_directory() . '/paid-memberships-pro/frontend.css' ) ) {
		// No custom frontend.css override file was found. Don't show the notice.
		return;
	}

	// Get notifications that have been archived.
	$archived_notifications = get_user_meta( $current_user->ID, 'pmpro_archived_notifications', true );

	// If the user hasn't dismissed the notice, show it.
	if ( ! is_array( $archived_notifications ) || ! array_key_exists( 'was_loading_frontend_css_notice', $archived_notifications ) ) {
		?>
		<div id="was_loading_frontend_css_notice" class="notice notice-error pmpro_notification pmpro_notification-error">
			<button type="button" class="pmpro-notice-button notice-dismiss" value="was_loading_frontend_css_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'paid-memberships-pro' ); ?></span></button>
			<div class="pmpro_notification-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="pmpro_notification-content">
				<h3><?php esc_html_e( 'Custom Frontend Stylesheet Detected', 'paid-memberships-pro' ); ?></h3>
				<p>
					<?php
					printf(
						wp_kses_post(
							__( 'Paid Memberships Pro detected that you were using a custom override for the frontend stylesheet. As of v3.1 and later, we no longer load your custom stylesheet. For more information, read our <a href="%s">v3.1 release notes post here</a>.', 'paid-memberships-pro' )
						),
						esc_url( 'https://www.paidmembershipspro.com/pmpro-update-3-1/' )
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpro_was_loading_frontend_css_notice' );
