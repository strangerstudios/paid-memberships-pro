<?php
	//only admins can get this
	if( !function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) && 
		!current_user_can( "pmpro_securitysettings" ) ) ) {
		die( esc_html__( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
	}

	//Bail if nonce field isn't set
	if ( !empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST[ 'pmpro_securitysettings_nonce' ] ) 
		|| !check_admin_referer( 'savesettings', 'pmpro_securitysettings_nonce' ) ) ) {
		$msg = -1;
		$msgt = __( "Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset( $_REQUEST[ 'savesettings' ] );
	}

	//get/set settings
	if( !empty( $_REQUEST['savesettings'] ) ) {
		pmpro_setOption( "spamprotection", intval( $_POST['spamprotection'] ) );
		pmpro_setOption( "recaptcha", intval( $_POST['recaptcha'] ) );
		pmpro_setOption( "recaptcha_version", sanitize_text_field( $_POST['recaptcha_version'] ) );
		pmpro_setOption( "recaptcha_publickey", sanitize_text_field( $_POST['recaptcha_publickey'] ) );
		pmpro_setOption( "recaptcha_privatekey", sanitize_text_field( $_POST['recaptcha_privatekey'] ) );
		pmpro_setOption( "use_ssl", intval( $_POST['use_ssl'] ) );
		if( !empty( $_POST['nuclear_HTTPS'] ) ) {
			$nuclear_HTTPS = 1;
		} else {
			$nuclear_HTTPS = 0;
		}
		pmpro_setOption( "nuclear_HTTPS", $nuclear_HTTPS );

	}

	$spamprotection = get_option( "pmpro_spamprotection" );
	$recaptcha = get_option( "pmpro_recaptcha" );
	$recaptcha_version = get_option( "pmpro_recaptcha_version" );
	$recaptcha_publickey = get_option( "pmpro_recaptcha_publickey" );
	$recaptcha_privatekey = get_option( "pmpro_recaptcha_privatekey" );
	$use_ssl = get_option( "pmpro_use_ssl" );
	$nuclear_HTTPS = get_option( "pmpro_nuclear_HTTPS" );

	$cloudflare_plugin_slug = 'simple-cloudflare-turnstile/simple-cloudflare-turnstile.php';
	$blogvault_plugin_slug = 'blogvault-real-time-backup/blogvault.php';
	$wordfence_plugin_slug = 'wordfence/wordfence.php';
	$wpsolid_plugin_slug = 'better-wp-security/better-wp-security.php';
	$akismet_plugin_slug = 'akismet/akismet.php';

	require_once(dirname(__FILE__) . "/admin_header.php");


	/**
	 * Check if plugin is installed by getting all plugins from the plugins dir
	 *
	 * @param $plugin_slug The plugin slug to check if it is installed
	 *
	 * @return bool True if plugin is installed, false otherwise
	 * @since TBD
	 */
	function pmpro_check_plugin_installed( $plugin_slug ) {
		$installed_plugins = get_plugins();
		return array_key_exists( $plugin_slug, $installed_plugins );
	}

	/**
	 * Print a general notice that can be customized with different parameters
	 *
	 * @param string $msg The message to display
	 * @param string $type The type of notice to display (error, warning, general)
	 * @param string $plugin_slug The plugin slug to check if it is installed or active
	 * @param string $action The action to take (install, activate)
	 * @param string $style The style to apply to the notice
	 * @return void
	 * @since TBD
	 */
	function pmpro_print_notice( $msg, $type, $plugin_slug, $action = '', $style = 'margin:0;' ) {
		$link_url = $action == 'activate' ? ( 'activate-plugin_' . $plugin_slug ) : 'install-plugin_' . $plugin_slug;
		$link_text = $action == 'activate' ? 'Activate Plugin' : 'Install Plugin';
		$plugin_activate_link = wp_nonce_url(
			self_admin_url(
				add_query_arg( array(
					'action' =>$action,
					'plugin' => $plugin_slug,
				),
				'plugins.php'
				)
			),
			$link_url
		);
		?>
		<div role="alert" class="pmpro_notification">
			<div class="pmpro_notification-<?php echo esc_attr( $type ); ?>" style="<?php echo esc_attr( $style ) ?>">
				<?php echo esc_html( $msg ); ?>
				<?php if (  $type != 'general' ) { ?>
					<a href="<?php echo esc_url( $plugin_activate_link ); ?>">
						<?php echo esc_html(  sprintf( __( '%s', 'paid-memberships-pro' ), $link_text ) ); ?>
					</a>
				<?php } ?>
			<div>
		</div>
		<?php
	}
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
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<label for="use_waf"><?php esc_html_e('Is Akismet Active ?', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<p>
									<?php esc_html_e( 'Akismet is a powerful anti-spam plugin for WordPress that automatically
									filters out spam comments and form submissions. By leveraging advanced algorithms and a
									vast database of spam patterns, Akismet keeps your site clean and improves performance
									by reducing unwanted clutter.', 'paid-memberships-pro' ); ?>
								</p>
								<?php
									//check akismet is installed
									if (! pmpro_check_plugin_installed( $akismet_plugin_slug ) ) {
										//Show a message notice that plugin is not active
										pmpro_print_notice( 'Akismet is not installed', 'error', $akismet_plugin_slug, 'install-plugin');
									} else if ( ! is_plugin_active( $akismet_plugin_slug ) ) {
										pmpro_print_notice( 'Akismet is installed but not active on your site.', 'warning',
											$akismet_plugin_slug, 'activate');
									} else {
										pmpro_print_notice( 'Akismet is active on your site.', 'general', '', '');
									}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="spamprotection"><?php esc_html_e('Enable Spam Protection?', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<select id="spamprotection" name="spamprotection">
									<option value="0" <?php if(!$spamprotection) { ?>selected="selected"<?php } ?>><?php esc_html_e('No', 'paid-memberships-pro' );?></option>
									<!-- For reference, removed the Yes - Free memberships only. option -->
									<option value="2" <?php if( $spamprotection > 0 ) { ?>selected="selected"<?php } ?>><?php esc_html_e('Yes - Enable Spam Protection', 'paid-memberships-pro' );?></option>
								</select>
								<p class="description"><?php printf( esc_html__( 'Block IPs from checkout if there are more than %d failures within %d minutes.', 'paid-memberships-pro' ), (int)PMPRO_SPAM_ACTION_NUM_LIMIT, (int)round(PMPRO_SPAM_ACTION_TIME_LIMIT/60,2) );?></p>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="recaptcha"><?php esc_html_e('Use reCAPTCHA?', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<select id="recaptcha" name="recaptcha">
									<option value="0" <?php if( !$recaptcha ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'No', 'paid-memberships-pro' );?></option>
									<!-- For reference, removed the Yes - Free memberships only. option -->
									<option value="2" <?php if( $recaptcha > 0 ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes - All memberships.', 'paid-memberships-pro' );?></option>
								</select>
								<p class="description"><?php esc_html_e( 'A free reCAPTCHA key is required.', 'paid-memberships-pro' );?> <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="nofollow noopener"><?php esc_html_e('Click here to signup for reCAPTCHA', 'paid-memberships-pro' );?></a>.</p>
							</td>
						</tr>
					</tbody>
				</table>
				<table class="form-table" id="recaptcha_settings" <?php if(!$recaptcha) { ?>style="display: none;"<?php } ?>>
					<tbody>
						<tr>
							<th scope="row" valign="top"><label for="recaptcha_version"><?php esc_html_e( 'reCAPTCHA Version', 'paid-memberships-pro' );?>:</label></th>
							<td>					
								<select id="recaptcha_version" name="recaptcha_version">
									<option value="2_checkbox" <?php selected( '2_checkbox', $recaptcha_version ); ?>><?php esc_html_e( ' v2 - Checkbox', 'paid-memberships-pro' ); ?></option>
									<option value="3_invisible" <?php selected( '3_invisible', $recaptcha_version ); ?>><?php esc_html_e( 'v3 - Invisible', 'paid-memberships-pro' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Changing your version will require new API keys.', 'paid-memberships-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="recaptcha_publickey"><?php esc_html_e('reCAPTCHA Site Key', 'paid-memberships-pro' );?>:</label></th>
							<td>
								<input type="text" id="recaptcha_publickey" name="recaptcha_publickey" value="<?php echo esc_attr($recaptcha_publickey);?>" class="regular-text code" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="recaptcha_privatekey"><?php esc_html_e('reCAPTCHA Secret Key', 'paid-memberships-pro' );?>:</label></th>
							<td>
								<input type="text" id="recaptcha_privatekey" name="recaptcha_privatekey" value="<?php echo esc_attr($recaptcha_privatekey);?>" class="regular-text code" />
							</td>
						</tr>
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
							<label for="nuclear_HTTPS"><?php esc_html_e('Extra HTTPS URL Filter', 'paid-memberships-pro' );?></label>
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
					<?php esc_html_e( 'Web Application Firewall Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<label for="use_waf"><?php esc_html_e('Is Cloudflare Active ?', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<p>
									<?php esc_html_e( 'A Web Application Firewall (WAF) like Cloudflare is important for 
									securing WordPress sites by blocking common attacks such as SQL injection, XSS, 
									and DDoS, enhancing website integrity a availability.', 'paid-memberships-pro' ); ?>
								</p>
								<?php 
									//check cloudflare is installed
									if (! pmpro_check_plugin_installed( $cloudflare_plugin_slug ) ) {
										//Show a message notice that plugin is not active
										pmpro_print_notice( 'Cloudflare is not installed', 'error', $cloudflare_plugin_slug, 'install-plugin');
									} else if ( ! is_plugin_active( $cloudflare_plugin_slug ) ) {
										pmpro_print_notice( 'Cloudflare is installed but not active on your site.', 'warning',
											$cloudflare_plugin_slug, 'activate');
									} else {
										pmpro_print_notice( 'Cloudflare is active on your site.', 'general', '', '');
									}
								?>
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
					<?php esc_html_e( 'WordPress Security Plugins Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
					<tbody>
						<tr>
							<p>
								<?php esc_html_e( 'WordPress security plugins are important for safeguarding your
								WordPress site. They provide comprehensive protection through features like
								reliable backups, real-time threat detection, firewall protection,
								and performance optimization.', 'paid-memberships-pro' ); ?>
							</p>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="use_blogvault"><?php esc_html_e('Is Blogvault Active ?', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<p>
									<?php esc_html_e( 'BlogVault is a real-time backup and security plugin that provides
									automatic backups, website staging, and one-click site restore.', 'paid-memberships-pro' ); ?>
								</p>
								<?php
								//check blogvault is installed
								if (! pmpro_check_plugin_installed( $blogvault_plugin_slug ) ) {
									//Show a message notice that plugin is not active
									pmpro_print_notice( 'Blogvault is not installed', 'error', 'install', '');
								} else if ( ! is_plugin_active( $blogvault_plugin_slug ) ) {
									pmpro_print_notice( 'Blogvault is installed but not active on your site.', 'warning',
										$blogvault_plugin_slug, 'activate');
								} else {
									pmpro_print_notice( 'Blogvault is active on your site.', 'general', '', '');
								}
								?>
								<tr>
									<th scope="row" valign="top">
										<label for="use_wordfence">
											<?php esc_html_e('Is Wordfence Active ?', 'paid-memberships-pro' );?>
										</label>
									</th>
									<td>
										<p>
											<?php esc_html_e( 'Wordfence is a comprehensive security plugin that provides
											firewall protection, malware scanning, and login security.', 'paid-memberships-pro' ); ?>
										</p>
										<?php
										//check wordfence is installed
										if (! pmpro_check_plugin_installed( $wordfence_plugin_slug ) ) {
											//Show a message notice that plugin is not active
											pmpro_print_notice( 'Wordfence is not installed', 'error', 'install', '');
										} else if ( ! is_plugin_active( $wordfence_plugin_slug ) ) {
											pmpro_print_notice( 'Wordfence is installed but not active on your site.', 'warning',
												$wordfence_plugin_slug, 'activate');
										} else {
											pmpro_print_notice( 'Wordfence is active on your site.', 'general', '', '');
										}
									?>
									<td>
								</tr>
								<tr>
									<th scope="row" valign="top">
										<label for="use_wp_solid">
											<?php esc_html_e('Is WPSolid Active ?', 'paid-memberships-pro' );?>
										</label>
									</th>
									<td>
										<p>
											<?php esc_html_e( 'SolidWP simplifies WordPress site management with powerful
											tools for backups, performance optimization, and security. ', 'paid-memberships-pro' ); ?>
										</p>
										<?php
										//check SolidWP is installed
										if (! pmpro_check_plugin_installed( $wpsolid_plugin_slug ) ) {
											//Show a message notice that plugin is not active
											pmpro_print_notice( 'SolidWP is not installed', 'error', 'install', '');
										} else if ( ! is_plugin_active( $wpsolid_plugin_slug ) ) {
											pmpro_print_notice( 'SolidWP is installed but not active on your site.', 'warning',
												$wpsolid_plugin_slug, 'activate');
										} else {
											pmpro_print_notice( 'SolidWP is active on your site.', 'general', '', '');
										}
									?>
									<td>
								</tr>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</div>
	</form>

<script>
	jQuery( document ).ready( function( $ ) {
		//hide/show recaptcha settings
		$( '#recaptcha' ).on( 'change', function( ev ) {
			$( '#recaptcha_settings' ).toggle();
		});
	});
</script>