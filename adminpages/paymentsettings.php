<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_paymentsettings")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	global $wpdb, $pmpro_currency_symbol, $msg, $msgt;

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
	if(!empty($_REQUEST['savesettings']))
	{
		/*
			Save any value that might have been passed in
		*/
		foreach($payment_options as $option) {
			//for now we make a special case for check instructions, but we need a way to specify sanitize functions for other fields
			if( in_array( $option, array( 'instructions' ) ) ) {
				global $allowedposttags;
				$html = wp_kses(wp_unslash($_POST[$option]), $allowedposttags);
				update_option("pmpro_{$option}", $html);
            } else {
				pmpro_setOption($option);
			}
		}

		do_action( 'pmpro_after_saved_payment_options', $payment_options );

		//assume success
		$msg = true;
		$msgt = __("Your payment settings have been updated.", 'paid-memberships-pro' );
	}

	/*
		Extract values for use later
	*/
	$payment_option_values = array();
	foreach($payment_options as $option)
		$payment_option_values[$option] = get_option( 'pmpro_' . $option);
	extract($payment_option_values);

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
	if(empty($gateway_environment))
	{
		$gateway_environment = "sandbox";
		pmpro_setOption("gateway_environment", $gateway_environment);
	}

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_paymentsettings_nonce');?>
		<hr class="wp-header-end">
        <h1><?php esc_html_e( 'Payment Gateway', 'paid-memberships-pro' );?></h1>
		<div id="choose-gateway" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Payment Gateway', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php			
					$gateway_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Payment Gateway Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/payment-ssl-settings/?utm_source=plugin&utm_medium=pmpro-paymentsettings&utm_campaign=documentation&utm_content=payment-gateway-settings">' . esc_html__( 'Payment Gateway Settings', 'paid-memberships-pro' ) . '</a>';
					// translators: %s: Link to Payment Gateway Settings doc.
					printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $gateway_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?></p>
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="gateway"><?php esc_html_e('Payment Gateway', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<select id="gateway" name="gateway">
								<?php
									$pmpro_gateways = pmpro_gateways();
									foreach($pmpro_gateways as $pmpro_gateway_name => $pmpro_gateway_label)
									{
									?>
									<option value="<?php echo esc_attr($pmpro_gateway_name);?>" <?php selected($gateway, $pmpro_gateway_name);?>><?php echo esc_html( $pmpro_gateway_label );?></option>
									<?php
									}
								?>
							</select>
							<?php if( pmpro_onlyFreeLevels() ) { ?>
								<div id="pmpro-default-gateway-message" style="display:none;"><p class="description"><?php echo esc_html__( 'This gateway is for membership sites with Free levels or for sites that accept payment offline.', 'paid-memberships-pro' )
								. '<br/>'
								. esc_html__( 'It is not connected to a live gateway environment and cannot accept payments.', 'paid-memberships-pro' ); ?></p></div>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="gateway_environment"><?php esc_html_e('Gateway Environment', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<select id="gateway_environment" name="gateway_environment">
								<option value="sandbox" <?php selected( $gateway_environment, "sandbox" ); ?>><?php esc_html_e('Sandbox/Testing', 'paid-memberships-pro' );?></option>
								<option value="live" <?php selected( $gateway_environment, "live" ); ?>><?php esc_html_e('Live/Production', 'paid-memberships-pro' );?></option>
							</select>
							<script>
							jQuery(document).ready(function(){
								function pmpro_changeGateway() {
									const gateway = jQuery('#gateway').val();
									const gateway_environment = jQuery('#gateway_environment').val();

									//hide all gateway options
									jQuery('tr.gateway').hide();
									jQuery('tr.gateway_'+gateway).show();
									jQuery('tr.gateway_'+gateway+'_'+gateway_environment).show();
									
									//hide sub settings and toggle them on based on triggers
									jQuery('tr.pmpro_toggle_target').hide();
									jQuery( 'input[pmpro_toggle_trigger_for]' ).each( function() {										
										if ( jQuery( this ).is( ':visible' ) ) {
											pmpro_toggle_elements_by_selector( jQuery( this ).attr( 'pmpro_toggle_trigger_for' ), jQuery( this ).prop( 'checked' ) );
										}
									});							

									if ( gateway === '' ) {
										jQuery('#pmpro-default-gateway-message').show();
									} else {
										jQuery('#pmpro-default-gateway-message').hide();
									}
								}
								pmpro_changeGateway();

								// Handle change events.
								jQuery('#gateway, #gateway_environment').on('change', pmpro_changeGateway);
							});
							</script>
						</td>
					</tr>

					<?php /* Gateway Specific Settings */ ?>
					<?php do_action('pmpro_payment_option_fields', $payment_option_values, $gateway); ?>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="currency-tax-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Currency and Tax Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
				<tbody>
					<tr class="gateway gateway_ <?php echo esc_attr(pmpro_getClassesForPaymentSettingsField("currency"));?>" <?php if(!empty($gateway) && $gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "check" && $gateway != "paypalstandard" && $gateway != "braintree" && $gateway != "twocheckout" && $gateway != "cybersource" && $gateway != "payflowpro" && $gateway != "stripe" && $gateway != "authorizenet" && $gateway != "gourl") { ?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top">
							<label for="currency"><?php esc_html_e('Currency', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<select name="currency">
							<?php
								global $pmpro_currencies;
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
					<tr class="gateway gateway_ <?php echo esc_attr(pmpro_getClassesForPaymentSettingsField("tax_rate"));?>" <?php if(!empty($gateway) && $gateway != "stripe" && $gateway != "authorizenet" && $gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "check" && $gateway != "paypalstandard" && $gateway != "payflowpro" && $gateway != "braintree" && $gateway != "twocheckout" && $gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
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
		<p class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
