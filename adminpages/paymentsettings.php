<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_paymentsettings")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}

	global $wpdb, $pmpro_currency_symbol, $msg, $msgt;

	/*
		Since 2.0, we let each gateway define what options they have in the class files
	*/
	//define options
	$payment_options = array_unique(apply_filters("pmpro_payment_options", array('gateway')));

	//get/set settings
	if(!empty($_REQUEST['savesettings']))
	{
		/*
			Save any value that might have been passed in
		*/
		foreach($payment_options as $option)
			pmpro_setOption($option);

		/*
			Some special case options still worked out here
		*/
		//credit cards
		$pmpro_accepted_credit_cards = array();
		if(!empty($_REQUEST['creditcards_visa']))
			$pmpro_accepted_credit_cards[] = "Visa";
		if(!empty($_REQUEST['creditcards_mastercard']))
			$pmpro_accepted_credit_cards[] = "Mastercard";
		if(!empty($_REQUEST['creditcards_amex']))
			$pmpro_accepted_credit_cards[] = "American Express";
		if(!empty($_REQUEST['creditcards_discover']))
			$pmpro_accepted_credit_cards[] = "Discover";
		if(!empty($_REQUEST['creditcards_dinersclub']))
			$pmpro_accepted_credit_cards[] = "Diners Club";
		if(!empty($_REQUEST['creditcards_enroute']))
			$pmpro_accepted_credit_cards[] = "EnRoute";
		if(!empty($_REQUEST['creditcards_jcb']))
			$pmpro_accepted_credit_cards[] = "JCB";

		pmpro_setOption("accepted_credit_cards", implode(",", $pmpro_accepted_credit_cards));

		//assume success
		$msg = true;
		$msgt = __("Your payment settings have been updated.", "pmpro");
	}

	/*
		Extract values for use later
	*/
	$payment_option_values = array();
	foreach($payment_options as $option)
		$payment_option_values[$option] = pmpro_getOption($option);
	extract($payment_option_values);

	/*
		Some special cases that get worked out here.
	*/
	//make sure the tax rate is not > 1
	$tax_state = pmpro_getOption("tax_state");
	$tax_rate = pmpro_getOption("tax_rate");
	if((double)$tax_rate > 1)
	{
		//assume the entered X%
		$tax_rate = $tax_rate / 100;
		pmpro_setOption("tax_rate", $tax_rate);
	}

	//accepted credit cards
	$pmpro_accepted_credit_cards = $payment_option_values['accepted_credit_cards'];	//this var has the pmpro_ prefix

	//default settings
	if(empty($gateway_environment))
	{
		$gateway_environment = "sandbox";
		pmpro_setOption("gateway_environment", $gateway_environment);
	}
	if(empty($pmpro_accepted_credit_cards))
	{
		$pmpro_accepted_credit_cards = "Visa,Mastercard,American Express,Discover";
		pmpro_setOption("accepted_credit_cards", $pmpro_accepted_credit_cards);
	}
	$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<h2><?php _e('Payment Gateway', 'pmpro');?> &amp; <?php _e('SSL Settings', 'pmpro');?></h2>

		<p><?php _e('Learn more about <a title="Paid Memberships Pro - SSL Settings" target="_blank" href="http://www.paidmembershipspro.com/support/initial-plugin-setup/ssl/">SSL</a> or <a title="Paid Memberships Pro - Payment Gateway Settings" target="_blank" href="http://www.paidmembershipspro.com/support/initial-plugin-setup/payment-gateway/">Payment Gateway Settings</a>.', 'pmpro'); ?></p>

		<table class="form-table">
		<tbody>
			<tr class="pmpro_settings_divider">
				<td colspan="2">
					Choose a Gateway
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="gateway"><?php _e('Payment Gateway', 'pmpro');?>:</label>
				</th>
				<td>
					<select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
						<?php
							$pmpro_gateways = pmpro_gateways();
							foreach($pmpro_gateways as $pmpro_gateway_name => $pmpro_gateway_label)
							{
							?>
							<option value="<?php echo esc_attr($pmpro_gateway_name);?>" <?php selected($gateway, $pmpro_gateway_name);?>><?php echo $pmpro_gateway_label;?></option>
							<?php
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="gateway_environment"><?php _e('Gateway Environment', 'pmpro');?>:</label>
				</th>
				<td>
					<select name="gateway_environment">
						<option value="sandbox" <?php selected( $gateway_environment, "sandbox" ); ?>><?php _e('Sandbox/Testing', 'pmpro');?></option>
						<option value="live" <?php selected( $gateway_environment, "live" ); ?>><?php _e('Live/Production', 'pmpro');?></option>
					</select>
					<script>
						function pmpro_changeGateway(gateway)
						{
							//hide all gateway options
							jQuery('tr.gateway').hide();
							jQuery('tr.gateway_'+gateway).show();
						}
						pmpro_changeGateway(jQuery('#gateway').val());
					</script>
				</td>
			</tr>

			<?php /* Gateway Specific Settings */ ?>
			<?php do_action('pmpro_payment_option_fields', $payment_option_values, $gateway); ?>

			<tr class="pmpro_settings_divider">
				<td colspan="2">
					Currency and Tax Settings
				</td>
			</tr>
			<tr class="gateway gateway_ <?php echo esc_attr(pmpro_getClassesForPaymentSettingsField("currency"));?>" <?php if(!empty($gateway) && $gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "paypalstandard" && $gateway != "braintree" && $gateway != "twocheckout" && $gateway != "cybersource" && $gateway != "payflowpro" && $gateway != "stripe" && $gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="currency"><?php _e('Currency', 'pmpro');?>:</label>
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
						<option value="<?php echo $ccode?>" <?php if($currency == $ccode) { ?>selected="selected"<?php } ?>><?php echo $cdescription?></option>
						<?php
						}
					?>
					</select>
					<small><?php _e( 'Not all currencies will be supported by every gateway. Please check with your gateway.', 'pmpro' ); ?></small>
				</td>
			</tr>
			<tr class="gateway gateway_ <?php echo esc_attr(pmpro_getClassesForPaymentSettingsField("accepted_credit_cards"));?>" <?php if(!empty($gateway) && $gateway != "authorizenet" && $gateway != "paypal" && $gateway != "stripe" && $gateway != "payflowpro" && $gateway != "braintree" && $gateway != "twocheckout" && $gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="creditcards"><?php _e('Accepted Credit Card Types', 'pmpro');?></label>
				</th>
				<td>
					<input type="checkbox" id="creditcards_visa" name="creditcards_visa" value="1" <?php if(in_array("Visa", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> <label for="creditcards_visa">Visa</label><br />
					<input type="checkbox" id="creditcards_mastercard" name="creditcards_mastercard" value="1" <?php if(in_array("Mastercard", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> <label for="creditcards_mastercard">Mastercard</label><br />
					<input type="checkbox" id="creditcards_amex" name="creditcards_amex" value="1" <?php if(in_array("American Express", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> <label for="creditcards_amex">American Express</label><br />
					<input type="checkbox" id="creditcards_discover" name="creditcards_discover" value="1" <?php if(in_array("Discover", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> <label for="creditcards_discover">Discover</label><br />
					<input type="checkbox" id="creditcards_dinersclub" name="creditcards_dinersclub" value="1" <?php if(in_array("Diners Club", $pmpro_accepted_credit_cards)) {?>checked="checked"<?php } ?> /> <label for="creditcards_dinersclub">Diner's Club</label><br />
					<input type="checkbox" id="creditcards_enroute" name="creditcards_enroute" value="1" <?php if(in_array("EnRoute", $pmpro_accepted_credit_cards)) {?>checked="checked"<?php } ?> /> <label for="creditcards_enroute">EnRoute</label><br />
					<input type="checkbox" id="creditcards_jcb" name="creditcards_jcb" value="1" <?php if(in_array("JCB", $pmpro_accepted_credit_cards)) {?>checked="checked"<?php } ?> /> <label for="creditcards_jcb">JCB</label><br />
				</td>
			</tr>
			<tr class="gateway gateway_ <?php echo esc_attr(pmpro_getClassesForPaymentSettingsField("tax_rate"));?>" <?php if(!empty($gateway) && $gateway != "stripe" && $gateway != "authorizenet" && $gateway != "paypal" && $gateway != "paypalexpress" && $gateway != "check" && $gateway != "paypalstandard" && $gateway != "payflowpro" && $gateway != "braintree" && $gateway != "twocheckout" && $gateway != "cybersource") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="tax"><?php _e('Sales Tax', 'pmpro');?> <small>(<?php _e('optional', 'pmpro');?>)</small></label>
				</th>
				<td>
					<?php _e('Tax State', 'pmpro');?>:
					<input type="text" id="tax_state" name="tax_state" size="4" value="<?php echo esc_attr($tax_state)?>" /> <small>(<?php _e('abbreviation, e.g. "PA"', 'pmpro');?>)</small>
					&nbsp; Tax Rate:
					<input type="text" id="tax_rate" name="tax_rate" size="10" value="<?php echo esc_attr($tax_rate)?>" /> <small>(<?php _e('decimal, e.g. "0.06"', 'pmpro');?>)</small>
					<p><small><?php _e('US only. If values are given, tax will be applied for any members ordering from the selected state.<br />For non-US or more complex tax rules, use the <a target="_blank" href="http://www.paidmembershipspro.com/2013/10/non-us-taxes-paid-memberships-pro/">pmpro_tax filter</a>.', 'pmpro');?></small></p>
				</td>
			</tr>

			<tr class="pmpro_settings_divider">
				<td colspan="2">
					SSL Settings
				</td>
			</tr>
			<tr class="gateway gateway_ <?php echo esc_attr(pmpro_getClassesForPaymentSettingsField("use_ssl"));?>">
				<th scope="row" valign="top">
					<label for="use_ssl"><?php _e('Force SSL', 'pmpro');?>:</label>
				</th>
				<td>
					<select id="use_ssl" name="use_ssl">
						<option value="0" <?php if(empty($use_ssl)) { ?>selected="selected"<?php } ?>><?php _e('No', 'pmpro');?></option>
						<option value="1" <?php if(!empty($use_ssl) && $use_ssl == 1) { ?>selected="selected"<?php } ?>><?php _e('Yes', 'pmpro');?></option>
						<option value="2" <?php if(!empty($use_ssl) && $use_ssl == 2) { ?>selected="selected"<?php } ?>><?php _e('Yes (with JavaScript redirects)', 'pmpro');?></option>
					</select>
					<small>Recommended: Yes. Try the JavaScript redirects setting if you are having issues with infinite redirect loops.</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="sslseal"><?php _e('SSL Seal Code', 'pmpro');?>:</label>
				</th>
				<td>
					<textarea id="sslseal" name="sslseal" rows="3" cols="80"><?php echo stripslashes(esc_textarea($sslseal))?></textarea>
					<br /><small>Your <strong><a target="_blank" href="http://www.paidmembershipspro.com/documentation/initial-plugin-setup/ssl/">SSL Certificate</a></strong> must be installed by your web host. Your <strong>SSL Seal</strong> will be a short HTML or JavaScript snippet that can be pasted here.</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="nuclear_HTTPS"><?php _e('Extra HTTPS URL Filter', 'pmpro');?>:</label>
				</th>
				<td>
					<input type="checkbox" id="nuclear_HTTPS" name="nuclear_HTTPS" value="1" <?php if(!empty($nuclear_HTTPS)) { ?>checked="checked"<?php } ?> /> <label for="nuclear_HTTPS"><?php _e('Pass all generated HTML through a URL filter to add HTTPS to URLs used on secure pages. Check this if you are using SSL and have warnings on your checkout pages.', 'pmpro');?></label>
				</td>
			</tr>

		</tbody>
		</table>
		<p class="submit">
			<input name="savesettings" type="submit" class="button-primary" value="<?php _e('Save Settings', 'pmpro');?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
