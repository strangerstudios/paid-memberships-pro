<?php
	//only admins can get this
	if( !function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) && 
		!current_user_can( "pmpro_securitysettings" ) ) ) {
		die( esc_html__( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
	}

	//Bail if nonce field isn't set
	if ( !empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST[ 'pmpro_securitysettings_nonce' ] ) 
		|| !check_admin_referer( 'savesettings', 'pmpro_paymentsettings_nonce' ) ) ) {
		$msg = -1;
		$msgt = __( "Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset( $_REQUEST[ 'savesettings' ] );
	}

	//get/set settings
	if( !empty( $_REQUEST['savesettings'] ) ) {
		setOption( "pmpro_spamprotection", intval( $_POST['spamprotection'] ) );
		setOption( "pmpro_recaptcha", intval( $_POST['recaptcha'] ) );
		setOption( "pmpro_recaptcha_version", sanitize_text_field( $_POST['recaptcha_version'] ) );
		setOption( "pmpro_recaptcha_publickey", sanitize_text_field( $_POST['recaptcha_publickey'] ) );
		setOption( "pmpro_recaptcha_privatekey", sanitize_text_field( $_POST['recaptcha_privatekey'] ) );
		setOption( "pmpro_sslseal", wp_kses_post( stripslashes( $_POST['sslseal'] ) ) );

	}

	$spamprotection = get_option( "pmpro_spamprotection" );
	$recaptcha = get_option( "pmpro_recaptcha" );
	$recaptcha_version = get_option( "pmpro_recaptcha_version" );
	$recaptcha_publickey = get_option( "pmpro_recaptcha_publickey" );
	$recaptcha_privatekey = get_option( "pmpro_recaptcha_privatekey" );
	$sslseal = get_option( "pmpro_sslseal" );
	$cloudflare_plugin_slug = 'simple-cloudflare-turnstile/simple-cloudflare-turnstile.php';



	require_once(dirname(__FILE__) . "/admin_header.php");


	/**
	 * Check if plugin is installed by getting all plugins from the plugins dir
	 *
	 * @param $plugin_slug
	 *
	 * @return bool
	 */
	function check_plugin_installed( $plugin_slug ) {
		$installed_plugins = get_plugins();
		return array_key_exists( $plugin_slug, $installed_plugins );
	}

?>
	<form action="" method="POST">
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
									and DDoS, enhancing website integrity and availability.', 'paid-memberships-pro' ); ?>
								</p>
								<?php 
								//check cloudflare is active 
								if (! check_plugin_installed( $cloudflare_plugin_slug ) ) {
									//Show a message notice that plugin is not active
								?>
									<div role="alert" class="pmpro_notification">
										<div class="pmpro_notification-error" style="margin:0">
											<?php esc_html_e( 'Cloudflare is not active','paid-memberships-pro' )  ?>
											<a href=https://www.cloudflare.com/application-services/products/waf/ target="_blank" rel="nofollow noopener">
												<?php esc_html_e('Click here to signup for Cloudflare', 'paid-memberships-pro' );?>
											</a>
										<div>
									</div>
								<?php
								} else if ( ! is_plugin_active( $cloudflare_plugin_slug ) ) {
									$security_clodflare_activate_link = wp_nonce_url(
										self_admin_url(
											add_query_arg( array(
												'action' => 'activate',
												'plugin' => $cloudflare_plugin_slug,	
											),
											'plugins.php'
											)
										),
										'activate-plugin_' . $cloudflare_plugin_slug
									);

								?>
									<div role="alert" class="pmpro_notification">
										<div class="pmpro_notification-general notice-warning" style="margin:0; border-left-color:#dba617">
											<?php esc_html_e( 'Cloudflare is installed but not active on your site.', 'paid-memberships-pro' ); ?>
											<a href="<?php echo esc_url( $security_clodflare_activate_link ); ?>"><?php esc_html_e( 'Activate Cloudflare', 'paid-memberships-pro' ); ?></a>
										<div>
											
									</div>
								<?php
								} else {
								?>
									<div role="alert" class="pmpro_notification">
										<div class="pmpro_notification-general" style="margin:0">
											<?php esc_html_e( 'Cloudflare is active on your site.', 'paid-memberships-pro' ); ?>
										<div>
									</div>
								<?php
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