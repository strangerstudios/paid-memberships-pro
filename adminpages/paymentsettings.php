<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_paymentsettings")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	global $wpdb, $pmpro_currency_symbol, $msg, $msgt;

	// Check if we are editing a specific gateway.
	$edit_gateway = ! empty( $_REQUEST['edit_gateway'] ) ? sanitize_text_field( $_REQUEST['edit_gateway'] ) : '';

	// If we have a gateway, try to build its gateway class name.
	$gateway_class_name = '';
	if ( ! empty( $edit_gateway ) && class_exists( 'PMProGateway_' . $edit_gateway ) ) {
		$gateway_class_name = 'PMProGateway_' . $edit_gateway;
	} else {
		// If the class doesn't exist, empty the gateway.
		$edit_gateway = '';
	}

	/*
		Since 2.0, we let each gateway define what options they have in the class files
	*/
	//define options
	$payment_options = array_unique(apply_filters("pmpro_payment_options", array('gateway')));

	//check nonce for saving settings
	if (!empty($_REQUEST['savesettings']) && (empty($_REQUEST['pmpro_paymentsettings_nonce']) || !check_admin_referer('savesettings', 'pmpro_paymentsettings_nonce'))) {
		$msg = -1;
		$msgt = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset($_REQUEST['savesettings']);
	}

	//get/set settings
	if ( ! empty( $_REQUEST['savesettings'] ) ) {
		// Check whether we are saving the settings for a specific gateway.
		if ( ! empty( $gateway_class_name ) ) {
			// Save the settings for the specific gateway.
			call_user_func( array( $gateway_class_name, 'save_settings_fields' ) );
		} else {
			// Save the global settings.
			$global_settings = array(
				'gateway',
				'gateway_environment',
				'currency',
				'tax_state',
				'tax_rate',
				'tax_settings_beta',
			);
			foreach ( $global_settings as $setting ) {
				pmpro_setOption( $setting );
			}
		}

		/**
		 * Run additional code after payment settings are saved.
		 *
		 * @param array $deprecated Deprecated parameter. Was previously used to pass in an array of settings that were saved.
		 */
		do_action( 'pmpro_after_saved_payment_options', array() );

		//assume success
		$msg = true;
		$msgt = __("Your payment settings have been updated.", 'paid-memberships-pro' );

		// Append a link to main payments settings page.
		if ( isset( $_REQUEST['edit_gateway'] ) ) {
			$msgt .= ' <a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Return to Payment Settings', 'paid-memberships-pro' ) . '</a>';
		}
	}

	/*
		Some special cases that get worked out here.
	*/
	//make sure the tax rate is not > 1
	$tax_state = get_option( "pmpro_tax_state");
	$tax_rate = get_option( "pmpro_tax_rate");
	if((double)$tax_rate > 1)
	{
		//assume the entered X%
		$tax_rate = $tax_rate / 100;
		pmpro_setOption("tax_rate", $tax_rate);
	}

	//default settings
	$gateway_environment = get_option("pmpro_gateway_environment");
	if(empty($gateway_environment))
	{
		$gateway_environment = "sandbox";
		pmpro_setOption("gateway_environment", $gateway_environment);
	}

	// Get a list of all gateways with human readable names.
	$pmpro_gateways = pmpro_gateways();
	$deprecated_gateways = pmpro_get_deprecated_gateways();

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_paymentsettings_nonce');?>
		<hr class="wp-header-end">
		<?php
		if ( empty( $gateway_class_name ) ) {
			// Show the table of gateways and global settings.
			?>
			<h1><?php esc_html_e( 'Payment Settings', 'paid-memberships-pro' );?></h1>
			<div id="global-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
						<?php esc_html_e( 'Global Settings', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row" valign="top">
									<label for="gateway"><?php esc_html_e( 'Payment Gateway', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<select id="gateway" name="gateway">
										<?php
											foreach ( $pmpro_gateways as $gateway_slug => $gateway_name ) {
												?>
												<option value="<?php echo esc_attr( $gateway_slug );?>" <?php selected( pmpro_getOption( 'gateway' ), $gateway_slug ); ?>><?php echo esc_html( $gateway_name );?></option>
												<?php
											}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Select the primary payment gateway for membership checkouts on this site. Before switching, ensure the selected gateway is fully configured for the chosen environment (live or test).', 'paid-memberships-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row" valign="top">
									<label for="gateway_environment"><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' );?></label>
								</th>
								<td>
									<select id="gateway_environment" name="gateway_environment">
										<option value="sandbox" <?php if( $gateway_environment == "sandbox") { ?>selected="selected"<?php } ?>><?php esc_html_e('Sandbox/Testing', 'paid-memberships-pro' );?></option>
										<option value="live" <?php if( $gateway_environment == "live") { ?>selected="selected"<?php } ?>><?php esc_html_e('Live/Production', 'paid-memberships-pro' );?></option>
									</select>
									<p class="description">
										<?php esc_html_e( 'Most gateways have a sandbox (test) and live environment. You can test transactions using the sandbox.', 'paid-memberships-pro' ); ?>
										<?php
											$gateway_testing_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Testing Your Payment Gateway', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/payment-testing/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=testing-your-payment-gateway">' . esc_html__( 'testing your payment gateway for more information', 'paid-memberships-pro' ) . '</a>';
											// translators: %s: Link to Testing Your Payment Gateway doc.
											printf( esc_html__('Gateway settings must be configured for each environment. Refer to our guide on %s.', 'paid-memberships-pro' ), $gateway_testing_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save All Settings', 'paid-memberships-pro' );?>" />
					</p>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			<div id="choose-gateway" class="pmpro_section" data-visibility="shown" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
						<?php esc_html_e( 'Payment Gateway Settings', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside">
					<p>
						<?php esc_html_e( 'Installed payment gateways are listed below. Select a gateway to manage settings in live or sandbox (test) mode.', 'paid-memberships-pro' ); ?>
						<?php			
						$gateway_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Payment Gateway Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/payment-gateway-settings/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=payment-gateway-settings">' . esc_html__( 'Payment Gateway Settings', 'paid-memberships-pro' ) . '</a>';
						// translators: %s: Link to Payment Gateway Settings doc.
						printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $gateway_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</p>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th class="manage-column column-gateway" scope="col">
									<?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?>
								</th>
								<th class="manage-column column-status" scope="col">
									<?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?>
								</th>
								<th class="manage-column column-description" scope="col">
									<?php esc_html_e( 'Description', 'paid-memberships-pro' ); ?>
								</th>
								<th class="manage-column column-edit" scope="col">
									<span class="screen-reader-text"><?php esc_html_e( 'Actions', 'paid-memberships-pro' );?></span>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $pmpro_gateways as $gateway_slug => $gateway_name ) {
								// Get information about what the gateway 'supports'.
								$gateway_class_name = 'PMProGateway_' . $gateway_slug;
								if ( class_exists( $gateway_class_name ) && method_exists( $gateway_class_name, 'supports' ) ) {
									$gateway_instance = new $gateway_class_name();
								}

								// Add a description for the default gateway.
								$gateway_description = $gateway_slug === '' ? esc_html__( 'This gateway is for membership sites with Free levels or for sites that accept payment offline. The default gateway is not connected to a live gateway environment and cannot accept payments.', 'paid-memberships-pro' ) : $gateway_description;
								?>
								<tr class="gateway gateway_<?php echo esc_attr( $gateway_slug );?>">
									<td class="column-gateway">
										<?php echo ! empty( $gateway_instance ) ? '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'edit_gateway' => esc_attr( $gateway_slug ) ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $gateway_name ) . '</a>' : esc_html( $gateway_name ); ?>
									</td>
									<td class="column-status">
										<?php
											$gateway_status_html = pmpro_getOption( 'gateway' ) === $gateway_slug ? '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-info">' . esc_html__( 'Enabled (Primary Gateway)', 'paid-memberships-pro' ) . '</span>' : esc_html__( '&#8212;', 'paid-memberships-pro' );

											// Special Cases for Add Ons that add secondary gateways. These will be removed when core natively supports multiple gateways.
											if (
												( function_exists( 'pmproappe_pmpro_valid_gateways' ) && $gateway_slug === 'paypalexpress' ) || // Add PayPal Express Add On.
												( defined( 'PMPROPBC_VER' ) && $gateway_slug === 'check' ) // Pay by Check Add On.
											) {
												// The Add On is active for the gateway being shown.
												if ( pmpro_getOption( 'gateway' ) === $gateway_slug ) {
													// If this is the primary gateway, add an alert.
													$gateway_status_html = '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-info">' . esc_html__( 'Enabled (Primary Gateway & via Add On)', 'paid-memberships-pro' ) . '</span>';
												} else {
													// Show this as a secondary gateway.
													$gateway_status_html = '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-info">' . esc_html__( 'Enabled (via Add On)', 'paid-memberships-pro' ) . '</span>';
												}	
											}

											// Special case for deprecated gateways.
											if ( in_array( $gateway_slug, $deprecated_gateways, true ) && $gateway_slug === pmpro_getOption( 'gateway' ) ) {
												$gateway_status_html = '<span class="pmpro_tag pmpro_tag-has_icon pmpro_tag-error">' . esc_html__( 'Enabled (Not Supported)', 'paid-memberships-pro' ) . '</span>';
											}

											echo wp_kses_post( $gateway_status_html );
										?>
										</td>
									<td class="column-description">
										<?php echo ! empty( $gateway_instance ) ? esc_html( $gateway_instance->get_description_for_gateway_settings() ) : esc_html__( '&#8212;', 'paid-memberships-pro' ); ?>
									</td>
									<td class="column-edit">
										<?php echo ! empty( $gateway_instance ) ? '<a class="button button-secondary" href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'edit_gateway' => esc_attr( $gateway_slug ) ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Edit Settings', 'paid-memberships-pro' ) . '</a>' : esc_html__( '&#8212;', 'paid-memberships-pro' ); ?>
									</td>
								</tr>
								<?php
								$gateway_class_name = '';
							}
							?>
						</tbody>
					</table>
					<br />
					<p>
						<a target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/add-on-category/payment/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=add-ons&utm_content=other-payment-gateways"><?php esc_html_e( 'Discover other payment gateways available as Paid Memberships Pro Add Ons', 'paid-memberships-pro' ); ?></a>
					</p>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			<div id="other-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
						<?php esc_html_e( 'Other Settings', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside">
					<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<label for="currency"><?php esc_html_e('Currency', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<select name="currency">
								<?php
									global $pmpro_currencies;
									$currency = get_option( 'pmpro_currency' );
									foreach($pmpro_currencies as $ccode => $cdescription)
									{
										if(is_array($cdescription))
											$cdescription = $cdescription['name'];
									?>
									<option value="<?php echo esc_attr( $ccode ) ?>" <?php if($currency == $ccode) { ?>selected="selected"<?php } ?>><?php echo esc_html( $cdescription ); ?></option>
									<?php
									}
								?>
								</select>
								<p class="description"><?php esc_html_e( 'Not all currencies will be supported by every gateway. Please check with your gateway.', 'paid-memberships-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="tax"><?php esc_html_e('Sales Tax', 'paid-memberships-pro' );?> (<?php esc_html_e('optional', 'paid-memberships-pro' );?>)</label>
							</th>
							<td>
								<?php esc_html_e('Tax State', 'paid-memberships-pro' );?>:
								<input type="text" id="tax_state" name="tax_state" value="<?php echo esc_attr($tax_state)?>" class="small-text" /> (<?php esc_html_e('abbreviation, e.g. "PA"', 'paid-memberships-pro' );?>)
								&nbsp; <?php esc_html_e('Tax Rate', 'paid-memberships-pro' ); ?>:
								<input type="text" id="tax_rate" name="tax_rate" size="10" value="<?php echo esc_attr($tax_rate)?>" class="small-text" /> (<?php esc_html_e('decimal, e.g. "0.06"', 'paid-memberships-pro' );?>)					
								<p class="description">
									<?php
										$filter_link = '<a target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/non-us-taxes-paid-memberships-pro/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=blog&utm_content=non-us-taxes-paid-memberships-pro">pmpro_tax filter</a>';
										// translators: %s: A link to the docs for the pmpro_tax filter.
										printf( esc_html__('US only. If values are given, tax will be applied for any members ordering from the selected state. For non-US or more complex tax rules, use the %s.', 'paid-memberships-pro' ), $filter_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</p>
							</td>
						</tr>
					</tbody>
					</table>
					<?php
						/**
						 * Fires after the Payment Settings form fields.
						 *
						 * @since 3.5
						 */
						do_action( 'pmpro_after_payment_settings' );
					?>
					<p class="submit">
						<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
					</p>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			
			<?php
		} else {
			// Show the settings for a specific gateway.
			?>
			<h1 class="wp-heading-inline">
				<?php
					echo sprintf(
						// translators: %s is the Level ID.
						esc_html__('Edit Payment Gateway: %s', 'paid-memberships-pro'),
						esc_html( $pmpro_gateways[ $edit_gateway ] )
					);
				?>
			</h1>
			<?php
			// If this gateway is deprecated, show a warning.
			if ( in_array( $edit_gateway, $deprecated_gateways, true ) ) {
				?>
				<div class="pmpro_message pmpro_error">
					<p><strong><?php esc_html_e('Notice: You Are Using a Deprecated Gateway', 'paid-memberships-pro' ); ?></strong></p>
					<p>
						<?php
						// translators: %s is the gateway name.
						printf(
							esc_html__('The %s gateway has been deprecated and will not receive updates or support. To ensure your payments continue running smoothly, switch to a supported payment gateway.', 'paid-memberships-pro'),
							esc_html( $pmpro_gateways[ $edit_gateway ] )
						);
						?>
					</p>
					<p>
						<a class="button button-secondary" href="https://www.paidmembershipspro.com/documentation/compatibility/incompatible-deprecated-add-ons/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=deprecated-gateways#deprecated-payment-gateways" target="_blank" rel="nofollow noopener"><?php esc_html_e('About Deprecated Gateways', 'paid-memberships-pro' ); ?></a>
						<a class="button" href="https://www.paidmembershipspro.com/switching-payment-gateways/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=blog&utm_content=switching-payment-gateways" target="_blank" rel="nofollow noopener"><?php esc_html_e('How to Switch Payment Gateways', 'paid-memberships-pro' ); ?></a>
					</p>
				</div>
				<?php
			}

			// Show the settings for the specific gateway.
			call_user_func( array( $gateway_class_name, 'show_settings_fields' ) );
			?>
			<p class="submit">
				<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'paid-memberships-pro' ); ?></a>
			</p>
			<?php
		}
		?>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
