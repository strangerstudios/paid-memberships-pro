<?php
	// Only admins can access this page.
	if( !function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) && 
		!current_user_can( "pmpro_securitysettings" ) ) ) {
		die( esc_html__( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
	}

	global $msg, $msgt;

	// Bail if nonce field isn't set.
	if ( !empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST[ 'pmpro_securitysettings_nonce' ] ) 
		|| !check_admin_referer( 'savesettings', 'pmpro_securitysettings_nonce' ) ) ) {
		$msg = -1;
		$msgt = __( "Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset( $_REQUEST[ 'savesettings' ] );
	}

	// Save settings.
	if( !empty( $_REQUEST['savesettings'] ) ) {
		pmpro_setOption( "spamprotection", intval( $_POST['spamprotection'] ) );

		// Save the captcha setting. Note: This must be saved before the
		// pmpro_save_security_settings hook fires so that the captcha services
		// saving their settings on that hook can see the updated value.
		$captcha = isset( $_POST['captcha'] ) ? sanitize_text_field( $_POST['captcha'] ) : '';
		if ( ! array_key_exists( $captcha, pmpro_get_captcha_services() ) ) {
			$captcha = '';
		}
		pmpro_setOption( 'captcha', $captcha );

		if ( isset( $_POST['use_ssl'] ) ) {
			// REQUEST['use_ssl'] will not be set if the entire site is already over HTTPS.
			pmpro_setOption( "use_ssl", intval( $_POST['use_ssl'] ) );
		}
		if( !empty( $_POST['nuclear_HTTPS'] ) ) {
			$nuclear_HTTPS = 1;
		} else {
			$nuclear_HTTPS = 0;
		}
		pmpro_setOption( "nuclear_HTTPS", $nuclear_HTTPS );

		/**
		 * Fires after security settings are saved.
		 *
		 * @since 3.2
		 */
		do_action( 'pmpro_save_security_settings' );

		// Assume success.
		$msg = true;
		$msgt = __("Your security settings have been updated.", 'paid-memberships-pro' );

	}

	// Get settings.
	$spamprotection = get_option( 'pmpro_spamprotection' );
	$use_ssl = get_option( 'pmpro_use_ssl' );
	$nuclear_HTTPS = get_option( 'pmpro_nuclear_HTTPS' );

	// Create an array of plugin files to check.
	$plugin_files['pmpro-akismet'] = 'pmpro-akismet/pmpro-akismet.php';
	$plugin_files['malcare-security'] = 'malcare-security/malcare.php';
	$plugin_files['wordfence'] = 'wordfence/wordfence.php';
	$plugin_files['better-wp-security'] = 'better-wp-security/better-wp-security.php';

	// Load the admin header.
	require_once( dirname(__FILE__) . '/admin_header.php' );

	/**
	 * Check if plugin is active, installed, or not installed.
	 *
	 * @since 3.1
	 *
	 * @param $plugin_file The plugin file to check.
	 * @return string The status of the plugin (active, inactive, not installed).
	 */
	function pmpro_is_plugin_installed_or_active( $plugin_file ) {
		if ( is_plugin_active( $plugin_file ) ) {
			$status = 'active';
		} elseif ( file_exists( ABSPATH . 'wp-content/plugins/' . $plugin_file ) ) {
			$status = 'inactive';
		} else {
			$status = 'not-installed';
		}
		return $status;
	}

	// Allowed strings for kses checks below.
	$allowed_pmpro_spam_protection_strings_html = array (
		'a' => array (
			'href' => array(),
			'target' => array(),
			'title' => array()
		),
		'strong' => array(),
		'em' => array()
	);
?>
	<form action="" method="POST" enctype="multipart/form-data">
		<?php wp_nonce_field( 'savesettings', 'pmpro_securitysettings_nonce' );?>
		<hr class="wp-header-end">
        <h1><?php esc_html_e( 'Security Settings', 'paid-memberships-pro' );?></h1>
		<p><?php
			$security_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Security Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/security-settings/?utm_source=plugin&utm_medium=pmpro-securitysettings&utm_campaign=documentation&utm_content=security-settings">' . esc_html__( 'Security Settings', 'paid-memberships-pro' ) . '</a>';
			// translators: %s: Link to Security Settings doc.
			printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $security_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?></p>
		<?php
		pmpro_build_settings_section( array(
			'title'  => __( 'Spam Protection', 'paid-memberships-pro' ),
			'fields' => array(
				array(
					'html' => '<p>' . wp_kses( sprintf( __( 'To ensure your site is as protected as possible, we recommend setting up several spam protection methods. Read our full guide on <a href="%s" target="_blank">how to stop spam in your membership site</a> for more information about these options.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/how-to-stop-spam/?utm_source=plugin&utm_medium=pmpro-securitysettings&utm_campaign=blog&utm_content=stop-spam' ), $allowed_pmpro_spam_protection_strings_html ) . '</p>',
				),
				array(
					'label'       => __( 'Akismet Integration', 'paid-memberships-pro' ),
					'type'        => 'html',
					'content'     => function() use ( $plugin_files, $allowed_pmpro_spam_protection_strings_html ) {
						// Check PMPro Akismet status.
						$pmpro_akismet_status = pmpro_is_plugin_installed_or_active( $plugin_files['pmpro-akismet'] );
						if ( $pmpro_akismet_status === 'not-installed' ) {
							echo '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-' . esc_attr( $pmpro_akismet_status ) . '">' . esc_html__( 'Not Installed', 'paid-memberships-pro' ) . '</span> ';
							$pmpro_akismet_link_url = wp_nonce_url(
								self_admin_url(
									add_query_arg( array(
										'action' => 'install-plugin',
										'plugin' => 'pmpro-akismet'
									),
									'update.php'
									)
								),
								'install-plugin_pmpro-akismet'
							);
							echo '<a href="' . esc_url( $pmpro_akismet_link_url ) . '">' . esc_html__( 'Click here to install', 'paid-memberships-pro' ) . '</a>';
						} else if ( $pmpro_akismet_status === 'active' ) {
							echo '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-' . esc_attr( $pmpro_akismet_status ) . '">' . esc_html__( 'Active', 'paid-memberships-pro' ) . '</span> ';
						} else {
							echo '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-' . esc_attr( $pmpro_akismet_status ) . '">' . esc_html__( 'Inactive', 'paid-memberships-pro' ) . '</span> ';
							$pmpro_akismet_link_url = wp_nonce_url(
								self_admin_url(
									add_query_arg( array(
										'action' => 'activate',
										'plugin' => $plugin_files['pmpro-akismet'],
									),
									'plugins.php'
								)
								),
								'activate-plugin_' . $plugin_files['pmpro-akismet']
							);
							echo '<a href="' . esc_url( $pmpro_akismet_link_url ) . '">' . esc_html__( 'Click here to activate', 'paid-memberships-pro' ) . '</a>';
						}
						?>
						<p class="description">
							<?php echo wp_kses( sprintf( __('With the Akismet Integration for Paid Memberships Pro, the same comment spam filters built into Akismet are used to detect and prevent membership checkout form abuse. This integration requires both the <a href="%1$s" target="_blank">Akismet plugin</a> and the <a href="%2$s" target="_blank">Akismet Integration for Paid Memberships Pro</a>.', 'paid-memberships-pro' ), 'https://wordpress.org/plugins/akismet/', 'https://www.paidmembershipspro.com/add-ons/pmpro-akismet/?utm_source=plugin&utm_medium=pmpro-securitysettings&utm_campaign=add-ons&utm_content=pmpro-akismet' ), $allowed_pmpro_spam_protection_strings_html ); ?>
						</p>
						<?php
					},
				),
				array(
					'name'        => 'spamprotection',
					'label'       => __( 'Spam Protection', 'paid-memberships-pro' ),
					'type'        => 'select',
					// For reference, removed the Yes - Free memberships only. option.
					'value'       => $spamprotection > 0 ? 2 : 0,
					'options'     => array(
						0 => __( 'No', 'paid-memberships-pro' ),
						2 => __( 'Yes - Enable Spam Protection', 'paid-memberships-pro' ),
					),
					'description' => sprintf( esc_html__( 'Block IPs from checkout and login if there are more than %d failures within %d minutes.', 'paid-memberships-pro' ), (int) PMPRO_SPAM_ACTION_NUM_LIMIT, (int) round( PMPRO_SPAM_ACTION_TIME_LIMIT / 60, 2 ) ),
				),
				array(
					'name'        => 'captcha',
					'label'       => __( 'Captcha', 'paid-memberships-pro' ),
					'type'        => 'select',
					'value'       => pmpro_captcha(),
					'options'     => array( '' => __( 'No', 'paid-memberships-pro' ) ) + pmpro_get_captcha_services(),
					'description' => __( 'Protect your checkout, login, and password reset forms with a captcha challenge. On login and password reset forms, the captcha is only shown after a failed login attempt or other suspicious activity from the visitor\'s IP address.', 'paid-memberships-pro' ),
				),
				array(
					// The callbacks hooked here echo their own <tr> rows, so give them a table.
					'html' => function() {
						if ( ! has_action( 'pmpro_security_spam_fields' ) ) {
							return;
						}
						?>
						<table class="form-table">
							<tbody>
								<?php
								/**
								 * Fires after the spam protection settings are displayed.
								 * Can be used to add additional spam protection settings.
								 *
								 * @since 3.2
								 */
								do_action( 'pmpro_security_spam_fields' );
								?>
							</tbody>
						</table>
						<?php
					},
				),
			),
		) );

		// Restricted Files.
		$restricted_file_path = pmpro_get_restricted_file_path();
		$restricted_dir_tag_class = 'alert';
		$restricted_dir_tag_label = __( 'Unable to determine', 'paid-memberships-pro' );

		if ( function_exists( 'pmpro_is_restricted_directory_protected' ) ) {
			$restricted_dir_protected = pmpro_is_restricted_directory_protected();
			if ( true === $restricted_dir_protected ) {
				$restricted_dir_tag_class = 'success';
				$restricted_dir_tag_label = __( 'Protected', 'paid-memberships-pro' );
			} elseif ( false === $restricted_dir_protected ) {
				$restricted_dir_tag_class = 'error';
				$restricted_dir_tag_label = __( 'Accessible', 'paid-memberships-pro' );
			}
		}

		$restricted_files_fields = array(
			array(
				'html' => '<p>' . esc_html__( 'To keep your membership data safe, we store certain sensitive files in the following protected directory:', 'paid-memberships-pro' ) . '</p>'
					. '<p><code>' . esc_html( $restricted_file_path ) . '</code></p>',
			),
			array(
				'label'   => __( 'Status', 'paid-memberships-pro' ),
				'type'    => 'html',
				'content' => function() use ( $restricted_dir_tag_class, $restricted_dir_tag_label ) {
					?>
					<div class="pmpro_tag pmpro_tag-has_icon pmpro_tag-<?php echo esc_attr( $restricted_dir_tag_class ); ?>"><?php echo esc_html( $restricted_dir_tag_label ); ?></div>
					<p><?php
						$restricted_file_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Restricted File Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/security-settings/?utm_source=plugin&utm_medium=pmpro-securitysettings&utm_campaign=documentation&utm_content=restricted-file-settings#restricted-files">' . esc_html__( 'Restricted File Settings', 'paid-memberships-pro' ) . '</a>';
						// translators: %s: Link to Security Settings doc.
						printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $restricted_file_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?></p>
					<?php
				},
			),
		);

		/**
		 * Filter to determine if the site is using NGINX.
		 *
		 * @since 3.5
		 *
		 * @param bool $is_nginx Whether the site is using NGINX.
		 */
		$is_nginx = apply_filters( 'pmpro_is_nginx', ! empty( $GLOBALS['is_nginx'] && $GLOBALS['is_nginx'] ) );
		if ( $is_nginx ) {
			$restricted_files_fields[] = array(
				'html' => function() {
					?>
					<hr />
					<p><?php esc_html_e( 'If your site is hosted on NGINX, you will need to manually restrict access to this folder by adding the following lines to your server config:', 'paid-memberships-pro' ); ?></p>
					<textarea readonly rows="4" cols="50" class="pmpro_restricted_files_code">
location ~ ^/wp-content/uploads/pmpro-[^/]+/ {
	deny all;
	return 403;
}</textarea>
					<?php
				},
			);
		}

		pmpro_build_settings_section( array(
			'title'  => __( 'Restricted Files', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => $restricted_files_fields,
		) );

		// HTTPS Settings. When the site is already served over https, Force SSL is display-only.
		if ( pmpro_check_site_url_for_https() ) {
			$force_ssl_field = array(
				'label'   => __( 'Force SSL', 'paid-memberships-pro' ),
				'type'    => 'html',
				'content' => '<p class="description">' . esc_html__( 'Your Site URL starts with https:// and so PMPro will allow your entire site to be served over HTTPS.', 'paid-memberships-pro' ) . '</p>',
			);
		} else {
			$force_ssl_field = array(
				'name'        => 'use_ssl',
				'label'       => __( 'Force SSL', 'paid-memberships-pro' ),
				'type'        => 'select',
				'value'       => empty( $use_ssl ) ? 0 : (int) $use_ssl,
				'options'     => array(
					0 => __( 'No', 'paid-memberships-pro' ),
					1 => __( 'Yes', 'paid-memberships-pro' ),
					2 => __( 'Yes (with JavaScript redirects)', 'paid-memberships-pro' ),
				),
				'description' => __( 'Recommended: Yes. Try the JavaScript redirects setting if you are having issues with infinite redirect loops.', 'paid-memberships-pro' ),
			);
		}

		pmpro_build_settings_section( array(
			'title'  => __( 'HTTPS Settings', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				$force_ssl_field,
				array(
					'name'           => 'nuclear_HTTPS',
					'label'          => __( 'Extra HTTPS URL Filter', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'value'          => $nuclear_HTTPS,
					'checkbox_label' => __( 'Pass all generated HTML through a URL filter to add HTTPS to URLs used on secure pages. Check this if you are using SSL and have warnings on your checkout pages.', 'paid-memberships-pro' ),
				),
			),
		) );

		pmpro_build_settings_section( array(
			'title'  => __( 'DNS Firewall', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'html' => '<p>' . esc_html__( 'DNS firewalls like Cloudflare provide distributed denial of service (DDoS) protection, improve page speed by delivering content via a global CDN, and include a web application firewall to block malicious traffic and vulnerabilities.', 'paid-memberships-pro' ) . '</p>',
				),
				array(
					'label'   => __( 'Cloudflare', 'paid-memberships-pro' ),
					'type'    => 'html',
					'content' => function() use ( $allowed_pmpro_spam_protection_strings_html ) {
						// Assume Cloudflare DNS Firewall is not active.
						$cloudflare_active = 'inactive';

						// Check if the site is using the Cloudflase DNS Firewall.
						$response = wp_remote_get( home_url() );
						if ( ! is_wp_error( $response ) ) {
							$headers = wp_remote_retrieve_headers($response);

							// Check for common Cloudflare headers.
							$cloudflare_headers = array( 'cf-ray', 'cf-connecting-ip', 'cf-cache-status' );
							foreach ( $cloudflare_headers as $header ) {
								if ( isset( $headers[$header] ) ) {
									$cloudflare_active = 'active';
									break;
								}
							}
						}
						?>
						<div class="pmpro_tag pmpro_tag-has_icon pmpro_tag-<?php echo esc_attr( $cloudflare_active ); ?>"><?php echo $cloudflare_active === 'active' ? esc_html__( 'Active', 'paid-memberships-pro' ) : esc_html__( 'Not Detected', 'paid-memberships-pro' ); ?></div>
						<?php
						if ( $cloudflare_active === 'inactive' ) {
							?>
							<p class="description">
								<?php echo wp_kses( sprintf( __( 'Consider setting up the <a href="%s" target="_blank">Cloudflare DNS firewall</a> to protect your site.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/documentation/hosting-docs/dns/?utm_source=plugin&utm_medium=pmpro-securitysettings&utm_campaign=documentation&utm_content=dns-firewall' ), $allowed_pmpro_spam_protection_strings_html ); ?>
							</p>
							<?php
						}
					},
				),
			),
		) );

		// WordPress Security. Detect active security plugins, then build the section's rows from
		// what was found.
		$installed_security_plugins = array();

		// Check if PMPro Hosting is installed.
		$pmpro_max_status = getenv( 'PMPRO_HOSTING' ) === '1' ? 'active' : 'not-installed';
		if ( $pmpro_max_status === 'active' ) {
			$installed_security_plugins[] = array( 'pmpro-hosting/pmpro-hosting.php', __( 'PMPro Max', 'paid-memberships-pro' ) );
		}

		// Check if other known security plugins are installed.
		$security_plugins_to_check = array(
			'malcare-security'   => __( 'MalCare', 'paid-memberships-pro' ),
			'wordfence'          => __( 'Wordfence', 'paid-memberships-pro' ),
			'better-wp-security' => __( 'Solid Security', 'paid-memberships-pro' ),
		);
		foreach ( $security_plugins_to_check as $slug => $label ) {
			$status = pmpro_is_plugin_installed_or_active( $plugin_files[ $slug ] );
			if ( $status === 'active' ) {
				$installed_security_plugins[] = array( $plugin_files[ $slug ], $label );
			}
		}

		// Build some links for use in this section.
		$pmpro_max_security_url = 'https://www.paidmembershipspro.com/documentation/hosting-docs/?utm_source=plugin&utm_medium=pmpro-securitysettings&utm_campaign=documentation&utm_content=security-malware';

		$wp_security_intro = $pmpro_max_status === 'active'
			? esc_html__( 'Your PMPro Max site has built-in security at multiple layers: from the server to your WordPress site. Malware scanning and removal are included with your plan.', 'paid-memberships-pro' )
			: esc_html__( 'A secure WordPress environment requires multiple layers of protection: from the server to the WordPress site itself. This section highlights if you are using a known security plugin to safeguard your WordPress site. PMPro Max customers have security handled for them, no additional plugins or services required.', 'paid-memberships-pro' );
		$wp_security_intro .= ' ' . sprintf(
			// translators: %s: Link to information about PMPro Max security.
			esc_html__( 'Learn more about %s.', 'paid-memberships-pro' ),
			'<a title="' . esc_attr__( 'Paid Memberships Pro - Security and Malware Protection', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="' . esc_url( $pmpro_max_security_url ) . '">' . esc_html__( 'security, performance, and malware protection in PMPro Max', 'paid-memberships-pro' ) . '</a>'
		);

		$wp_security_fields = array(
			array( 'html' => '<p>' . $wp_security_intro . '</p>' ),
		);

		if ( empty( $installed_security_plugins ) ) {
			$wp_security_fields[] = array(
				'label'   => __( 'Security Status', 'paid-memberships-pro' ),
				'type'    => 'html',
				'content' => '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-inactive">' . esc_html__( 'No Security Detected', 'paid-memberships-pro' ) . '</span> '
					. '<p class="description">' . esc_html__( 'We do not detect an active security plugin on your site.', 'paid-memberships-pro' ) . ' '
					. '<a target="_blank" rel="nofollow noopener" href="' . esc_url( $pmpro_max_security_url ) . '">' . esc_html__( 'Explore PMPro Max now to protect your site', 'paid-memberships-pro' ) . '</a></p>',
			);
		} else {
			// If there are more than one active security plugins, display a warning.
			if ( count( $installed_security_plugins ) > 1 ) {
				$wp_security_fields[] = array(
					'html' => '<div class="pmpro_message pmpro_alert"><p><strong>' . esc_html__( 'Multiple Security Plugins Active', 'paid-memberships-pro' ) . '</strong><br />' . esc_html__( 'Having multiple security plugins active can cause conflicts and slow down your site. Consider deactivating one of the plugins listed as active below.', 'paid-memberships-pro' ) . '</p></div>',
				);
			}
			// Show the status of each installed security plugin.
			foreach ( $installed_security_plugins as $plugin ) {
				$plugin_row_content = '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-active">' . esc_html__( 'Active', 'paid-memberships-pro' ) . '</span> ';
				if ( $pmpro_max_status === 'active' && count( $installed_security_plugins ) > 1 && $plugin[0] !== 'pmpro-hosting/pmpro-hosting.php' ) {
					$plugin_row_content .= '<p class="description">' . sprintf(
						// translators: %s: The name of the installed security plugin.
						esc_html__( 'Security plugins like %s are not needed on PMPro Max and may negatively impact your performance. We recommend deactivating this plugin.', 'paid-memberships-pro' ),
						esc_html( $plugin[1] )
					) . '</p>';
				}
				$wp_security_fields[] = array(
					'label'   => $plugin[1],
					'type'    => 'html',
					'content' => $plugin_row_content,
				);
			}
		}

		pmpro_build_settings_section( array(
			'title'  => __( 'WordPress Security', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => $wp_security_fields,
		) );
		?>
		<div class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</div>
	</form>
