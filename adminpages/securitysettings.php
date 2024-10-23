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
		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Spam Protection', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p>
					<?php echo wp_kses( sprintf( __( 'To ensure your site is as protected as possible, we recommend setting up several spam protection methods. Read our full guide on <a href="%s" target="_blank">how to stop spam in your membership site</a> for more information about these options.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/how-to-stop-spam/' ), $allowed_pmpro_spam_protection_strings_html ); ?>
				</p>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<?php esc_html_e( 'Akismet Integration', 'paid-memberships-pro' ); ?>
							</th>
							<td>
								<?php
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
									<?php echo wp_kses( sprintf( __('With the Akismet Integration for Paid Memberships Pro, the same comment spam filters built into Akismet are used to detect and prevent membership checkout form abuse. This integration requires both the <a href="%1$s" target="_blank">Akismet plugin</a> and the <a href="%2$s" target="_blank">Akismet Integration for Paid Memberships Pro</a>.', 'paid-memberships-pro' ), 'https://wordpress.org/plugins/akismet/', 'https://www.paidmembershipspro.com/add-ons/pmpro-akismet/' ), $allowed_pmpro_spam_protection_strings_html ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="spamprotection"><?php esc_html_e( 'Checkout Spam Protection', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<select id="spamprotection" name="spamprotection">
									<option value="0" <?php selected( $spamprotection, false ); ?>><?php esc_html_e('No', 'paid-memberships-pro' );?></option>
									<!-- For reference, removed the Yes - Free memberships only. option -->
									<option value="2" <?php if( $spamprotection > 0 ) { ?>selected="selected"<?php } ?>><?php esc_html_e('Yes - Enable Spam Protection', 'paid-memberships-pro' );?></option>
								</select>
								<p class="description"><?php printf( esc_html__( 'Block IPs from checkout if there are more than %d failures within %d minutes.', 'paid-memberships-pro' ), (int)PMPRO_SPAM_ACTION_NUM_LIMIT, (int)round(PMPRO_SPAM_ACTION_TIME_LIMIT/60,2) );?></p>
							</td>
						</tr>
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
			</div>
		</div>
		<div class="pmpro_section" data-visibility="hidden" data-activated="false">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'HTTPS Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php			
					$ssl_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - SSL Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/initial-plugin-setup/ssl/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=ssl&utm_term=link1">' . esc_html__( 'SSL', 'paid-memberships-pro' ) . '</a>';
					// translators: %s: Link to SSL Settings doc.
					printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $ssl_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?></p>
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="use_ssl"><?php esc_html_e('Force SSL', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<?php if( pmpro_check_site_url_for_https() ) { ?>
									<p class="description">
										<?php esc_html_e( 'Your Site URL starts with https:// and so PMPro will allow your entire site to be served over HTTPS.', 'paid-memberships-pro' ); ?>
									</p>
									<?php
								} else {
									//site is not over HTTPS, show setting
									?>
									<select id="use_ssl" name="use_ssl">
										<option value="0" <?php if( empty( $use_ssl ) ) {?>selected="selected"<?php } ?>><?php esc_html_e( 'No', 'paid-memberships-pro' );?></option>
										<option value="1" <?php if( !empty( $use_ssl ) && $use_ssl == 1 ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' );?></option>
										<option value="2" <?php if( !empty( $use_ssl ) && $use_ssl == 2 ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes (with JavaScript redirects)', 'paid-memberships-pro' );?></option>
									</select>
									<p class="description"><?php esc_html_e('Recommended: Yes. Try the JavaScript redirects setting if you are having issues with infinite redirect loops.', 'paid-memberships-pro' ); ?></p>
									<?php
								}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<?php esc_html_e('Extra HTTPS URL Filter', 'paid-memberships-pro' ); ?>
						</th>
						<td>
							<input type="checkbox" id="nuclear_HTTPS" name="nuclear_HTTPS" value="1" <?php if(!empty($nuclear_HTTPS)) { ?>checked="checked"<?php } ?> /> <label for="nuclear_HTTPS"><?php esc_html_e('Pass all generated HTML through a URL filter to add HTTPS to URLs used on secure pages. Check this if you are using SSL and have warnings on your checkout pages.', 'paid-memberships-pro' );?></label>
						</td>
					</tr>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div class="pmpro_section" data-visibility="hidden" data-activated="false">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'DNS Firewall', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'DNS firewalls like Cloudflare provide distributed denial of service (DDoS) protection, improve page speed by delivering content via a global CDN, and include a web application firewall to block malicious traffic and vulnerabilities.', 'paid-memberships-pro' ); ?></p>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<?php esc_html_e( 'Cloudflare', 'paid-memberships-pro' ); ?>
							</th>
							<td>
								<?php
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
											<?php echo wp_kses( sprintf( __( 'Consider setting up the <a href="%s" target="_blank">Cloudflare DNS firewall</a> to protect your site.', 'paid-memberships-pro' ), 'https://www.cloudflare.com/dns/dns-firewall/' ), $allowed_pmpro_spam_protection_strings_html ); ?>
										</p>
										<?php
									}
								?>
							</td>
							</td>
						</tr>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div class="pmpro_section" data-visibility="hidden" data-activated="false">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'WordPress Security Plugins', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
					<tbody>
						<tr>
							<p>
								<?php esc_html_e( 'WordPress security plugins are important for safeguarding your WordPress site. They protect your site by offering backups, real-time threat detection, firewalls, and performance optimization.', 'paid-memberships-pro' ); ?>
							</p>
						</tr>
						<?php 
						// Arrays to store information about installed security plugins.
						$installed_security_plugins = array();
						$active_security_plugins_count = 0;

						// Check if Malcare Security is installed.
						$malcare_status = pmpro_is_plugin_installed_or_active( $plugin_files['malcare-security'] );
						if ( $malcare_status === 'active' ) {
							$installed_security_plugins[] = array ( $plugin_files['malcare-security'], __( 'MalCare', 'paid-memberships-pro' ), $malcare_status );
							$active_security_plugins_count++;
						}

						// Check if Wordfence is installed.
						$wordfence_status = pmpro_is_plugin_installed_or_active( $plugin_files['wordfence'] );
						if ( $wordfence_status === 'active' ) {
							$installed_security_plugins[] = array ( $plugin_files['wordfence'], __( 'Wordfence', 'paid-memberships-pro' ), $wordfence_status );
							$active_security_plugins_count++;
						}

						// Check if Solid Security is installed.
						$solid_security_status = pmpro_is_plugin_installed_or_active( $plugin_files['better-wp-security'] );
						if ( $solid_security_status === 'active' ) {
							$installed_security_plugins[] = array ( $plugin_files['better-wp-security'], __( 'Solid Security',  'paid-memberships-pro' ), $solid_security_status );
							$active_security_plugins_count++;
						}

						if ( empty( $installed_security_plugins ) ) {
							?>
							<tr>
								<th>
									<?php esc_html_e( 'MalCare', 'paid-memberships-pro' ); ?>
								</th>
								<td>
									<?php
										// Check MalCare status.
										if ( $malcare_status === 'not-installed' ) {
											echo '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-' . esc_attr( $malcare_status ) . '">' . esc_html__( 'Not Installed', 'paid-memberships-pro' ) . '</span> ';
											$malcare_link_url = wp_nonce_url(
												self_admin_url(
													add_query_arg( array(
														'action' => 'install-plugin',
														'plugin' => 'malcare-security'
													),
													'update.php'
													)
												),
												'install-plugin_malcare-security'
											);
											echo '<a href="' . esc_url( $malcare_link_url ) . '">' . esc_html__( 'Click here to install', 'paid-memberships-pro' ) . '</a>';
											// translators: %s: Link to install MalCare security plugin.
											echo '<p class="description">' . wp_kses( sprintf( __( 'We do not detect an active security plugin on your site. <a href="%s">Install MalCare for free now</a> to protect your site. MalCare protects your site without slowing it down.', 'paid-memberships-pro' ), $malcare_link_url ), $allowed_pmpro_spam_protection_strings_html ) . '</p>';
										} else {
											echo '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-' . esc_attr( $malcare_status ) . '">' . esc_html__( 'Inactive', 'paid-memberships-pro' ) . '</span> ';
											$malcare_link_url = wp_nonce_url(
												self_admin_url(
													add_query_arg( array(
														'action' => 'activate',
														'plugin' => $plugin_files['malcare-security'],
													),
													'plugins.php'
												)
												),
												'activate-plugin_' . $plugin_files['malcare-security']
											);
											echo '<a href="' . esc_url( $malcare_link_url ) . '">' . esc_html__( 'Click here to activate', 'paid-memberships-pro' ) . '</a>';
										}
									?>
								</td>
							</tr>
							<?php
						} else {
							// If there are more than one active security plugins, display a warning.
							if ( $active_security_plugins_count > 1 ) {
								?>
								<tr>
									<td colspan="2">
										<div class="notice notice-warning notice-large inline"><p><strong><?php esc_html_e( 'Multiple Security Plugins Active', 'paid-memberships-pro' ); ?></strong><br /><?php esc_html_e( 'Having multiple security plugins active can cause conflicts and slow down your site. Consider deactivating one of the plugins listed as active below.', 'paid-memberships-pro' ); ?></p></div>
									</td>
								</tr>
								<?php
							}
							// Show the status of each installed security plugin.
							foreach ( $installed_security_plugins as $plugin ) {
								?>
								<tr>
									<th><?php echo esc_html( $plugin[1] ); ?></th>
									<td><?php echo '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-' . esc_attr( $plugin[2] ) . '">' . esc_html__( 'Active', 'paid-memberships-pro' ) . '</span> '; ?></td>
								</tr>
								<?php
							}
						}
					?>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</div>
	</form>
