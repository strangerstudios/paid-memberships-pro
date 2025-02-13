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
			// Check if this gateway is enabled.
			if ( ! empty( $_REQUEST['enabled'] ) ) {
				pmpro_setOption( 'gateway', $edit_gateway );
			} else {
				pmpro_setOption( 'gateway', '' );
			}

			// Save the settings for the specific gateway.
			call_user_func( array( $gateway_class_name, 'save_settings_fields' ) );
		} else {
			// Save the global settings.
			$global_settings = array(
				'gateway_environment',
				'currency',
				'tax_state',
				'tax_rate',
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

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_paymentsettings_nonce');?>
		<hr class="wp-header-end">
		<?php
		if ( empty( $gateway_class_name ) ) {
			// Show the table of gateways and global settings.
			?>
			<h1><?php esc_html_e( 'Payment Gateway', 'paid-memberships-pro' );?></h1>
			<div id="choose-gateway" class="pmpro_section" data-visibility="shown" data-activated="true">
				<div class="pmpro_section_toggle">
					<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
						<?php esc_html_e( 'Payment Gateways', 'paid-memberships-pro' ); ?>
					</button>
				</div>
				<div class="pmpro_section_inside">
					<p>
						<?php			
						$gateway_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Payment Gateway Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/payment-gateway-settings/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=payment-gateway-settings">' . esc_html__( 'Payment Gateway Settings', 'paid-memberships-pro' ) . '</a>';
						// translators: %s: Link to Payment Gateway Settings doc.
						printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $gateway_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</p>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>
									<?php esc_html_e( 'Gateway', 'paid-memberships-pro' );?>
								</th>
								<th>
									<?php esc_html_e( 'Enabled?', 'paid-memberships-pro' );?>
								</th>
								<th>
									<?php esc_html_e( 'Edit', 'paid-memberships-pro' );?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $pmpro_gateways as $gateway_slug => $gateway_name ) {
								?>
								<tr class="gateway gateway_<?php echo esc_attr( $gateway_slug );?>">
									<td>
										<?php echo esc_html( $gateway_name );?>
									</td>
									<td>
										<?php echo pmpro_getOption( 'gateway' ) === $gateway_slug ? '<span class="dashicons dashicons-yes"></span>' : ''; ?>
									</td>
									<td>
										<a href="?page=pmpro-paymentsettings&edit_gateway=<?php echo esc_attr( $gateway_slug ); // WPCS: XSS ok. ?>"><?php esc_html_e( 'Edit Settings', 'paid-memberships-pro' );?></a>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			<div id="currency-tax-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
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
								<label for="gateway_environment"><?php esc_html_e('Gateway Environment', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<select id="gateway_environment" name="gateway_environment">
									<option value="sandbox" <?php if( $gateway_environment == "sandbox") { ?>selected="selected"<?php } ?>><?php esc_html_e('Sandbox', 'paid-memberships-pro' );?></option>
									<option value="live" <?php if( $gateway_environment == "live") { ?>selected="selected"<?php } ?>><?php esc_html_e('Live', 'paid-memberships-pro' );?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Most gateways have a sandbox (test) and live environment. You can test transactions using the sandbox.', 'paid-memberships-pro' ); ?></p>
							</td>
						</tr>
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
				</div> <!-- end pmpro_section_inside -->
			</div> <!-- end pmpro_section -->
			
			<?php
		} else {
			// Show the settings for a specific gateway.
			?>
			<h1><?php echo esc_html( $pmpro_gateways[ $edit_gateway ] ); ?></h1>
			<?php
			// Show checkbox for whether the gateway is enabled.
			?>
			<input type="checkbox" id="enabled" name="enabled" value="1" <?php if ( pmpro_getOption( 'gateway' ) === $edit_gateway ) { ?>checked="checked"<?php } ?> />
			<label for="enabled"><?php esc_html_e( 'Enabled', 'paid-memberships-pro' ); ?></label>
			<?php
			call_user_func( array( $gateway_class_name, 'show_settings_fields' ) );
		}
		?>
		<p class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
