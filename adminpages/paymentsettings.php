<?php
	global $wpdb, $pmpro_currency_symbol, $msg, $msgt;
	
	//get/set settings	
	if(!empty($_REQUEST['savesettings']))
	{                   
		pmpro_setOption("sslseal");
			
		//gateway options
		pmpro_setOption("gateway");					
		pmpro_setOption("gateway_environment");
		pmpro_setOption("gateway_email");
		pmpro_setOption("apiusername");
		pmpro_setOption("apipassword");
		pmpro_setOption("apisignature");
		pmpro_setOption("loginname");
		pmpro_setOption("transactionkey");

		//currency
		$currency_paypal = $_POST['currency_paypal'];
		$currency_authorizenet = $_POST['currency_authorizenet'];
		if($_POST['gateway'] == "authorizenet")
			pmpro_setOption("currency", $currency_authorizenet);
		else
			pmpro_setOption("currency", $currency_paypal);
			
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
		
		//tax
		pmpro_setOption("tax_state");
		pmpro_setOption("tax_rate");
		
		pmpro_setOption("accepted_credit_cards", implode(",", $pmpro_accepted_credit_cards));	

		//assume success
		$msg = true;
		$msgt = "Your payment settings have been updated.";			
	}

	$sslseal = pmpro_getOption("sslseal");
	
	$gateway = pmpro_getOption("gateway");
	$gateway_environment = pmpro_getOption("gateway_environment");
	$gateway_email = pmpro_getOption("gateway_email");
	$apiusername = pmpro_getOption("apiusername");
	$apipassword = pmpro_getOption("apipassword");
	$apisignature = pmpro_getOption("apisignature");
	$loginname = pmpro_getOption("loginname");
	$transactionkey = pmpro_getOption("transactionkey");
	
	$currency = pmpro_getOption("currency");
	
	$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
	
	$tax_state = pmpro_getOption("tax_state");
	$tax_rate = pmpro_getOption("tax_rate");
	
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
		<h2>SSL &amp; Payment Gateway Settings</h2>
		
		<p>Learn more about <a title="Paid Memberships Pro - SSL Settings" target="_blank" href="http://www.paidmembershipspro.com/support/initial-plugin-setup/ssl/">SSL</a> or <a title="Paid Memberships Pro - Payment Gateway Settings" target="_blank" href="http://www.paidmembershipspro.com/support/initial-plugin-setup/payment-gateway/">Payment Gateway Settings</a>.</p>
		
		<table class="form-table">
		<tbody>                
		   <tr>
				<th scope="row" valign="top">
					<label for="sslseal">SSL Seal Code:</label>
				</th>
				<td>
					<textarea name="sslseal" rows="3" cols="80"><?php echo stripslashes($sslseal)?></textarea>
				</td>
		   </tr>
		   <tr>
				<th scope="row" valign="top">	
					<label for="gateway">Payment Gateway:</label>
				</th>
				<td>
					<select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
						<option value="">-- choose one --</option>
						<option value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>selected="selected"<?php } ?>>PayPal Express</option>
						<option value="paypal" <?php if($gateway == "paypal") { ?>selected="selected"<?php } ?>>PayPal Website Payments Pro</option>
						<option value="authorizenet" <?php if($gateway == "authorizenet") { ?>selected="selected"<?php } ?>>Authorize.net</option>
					</select>                        
				</td>
			</tr> 
			<tr>
				<th scope="row" valign="top">
					<label for="gateway_environment">Gateway Environment:</label>
				</th>
				<td>
					<select name="gateway_environment">
						<option value="sandbox" <?php if($gateway_environment == "sandbox") { ?>selected="selected"<?php } ?>>Sandbox/Testing</option>
						<option value="live" <?php if($gateway_environment == "live") { ?>selected="selected"<?php } ?>>Live/Production</option>
					</select>
					<script>
						function pmpro_changeGateway(gateway)
						{
							//hide all gateway options
							jQuery('tr.gateway').hide();
							jQuery('tr.gateway_'+gateway).show();
						}
						pmpro_changeGateway(jQuery().val('#gateway'));
					</script>
				</td>
		   </tr>
		   <tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">	
					<label for="gateway_email">Gateway Account Email:</label>
				</th>
				<td>
					<input type="text" name="gateway_email" size="60" value="<?php echo $gateway_email?>" />
				</td>
			</tr>                
			<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="apiusername">API Username:</label>
				</th>
				<td>
					<input type="text" name="apiusername" size="60" value="<?php echo $apiusername?>" />
				</td>
			</tr>
			<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="apipassword">API Password:</label>
				</th>
				<td>
					<input type="text" name="apipassword" size="60" value="<?php echo $apipassword?>" />
				</td>
			</tr> 
			<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="apisignature">API Signature:</label>
				</th>
				<td>
					<input type="text" name="apisignature" size="60" value="<?php echo $apisignature?>" />
				</td>
			</tr> 
			<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="loginname">Login Name:</label>
				</th>
				<td>
					<input type="text" name="loginname" size="60" value="<?php echo $loginname?>" />
				</td>
			</tr>
			<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="transactionkey">Transaction Key:</label>
				</th>
				<td>
					<input type="text" name="transactionkey" size="60" value="<?php echo $transactionkey?>" />
				</td>
			</tr>
			<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="transactionkey">Currency:</label>
				</th>
				<td>
					<input type="hidden" name="currency_authorizenet" size="60" value="USD" />
					USD
				</td>
			</tr>
			<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="transactionkey">Currency:</label>
				</th>
				<td>
					<select name="currency_paypal">
					<?php 
						global $pmpro_currencies;
						foreach($pmpro_currencies as $ccode => $cdescription)
						{
						?>
						<option value="<?php echo $ccode?>" <?php if($currency == $ccode) { ?>selected="selected"<?php } ?>><?php echo $cdescription?></option>
						<?php
						}
					?>
					</select>
				</td>
			</tr>
			
			<tr class="gateway gateway_authorizenet gateway_paypal" <?php if($gateway != "authorizenet" && $gateway != "paypal") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="creditcards">Accepted Credit Card Types</label>
				</th>
				<td>
					<input type="checkbox" name="creditcards_visa" value="1" <?php if(in_array("Visa", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> Visa<br />
					<input type="checkbox" name="creditcards_mastercard" value="1" <?php if(in_array("Mastercard", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> Mastercard<br />
					<input type="checkbox" name="creditcards_amex" value="1" <?php if(in_array("American Express", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> American Express<br />
					<input type="checkbox" name="creditcards_discover" value="1" <?php if(in_array("Discover", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> Discover<br />
					<input type="checkbox" name="creditcards_dinersclub" value="1" <?php if(in_array("Diners Club", $pmpro_accepted_credit_cards)) {?>checked="checked"<?php } ?> /> Diner's Club<br />
					<input type="checkbox" name="creditcards_enroute" value="1" <?php if(in_array("EnRoute", $pmpro_accepted_credit_cards)) {?>checked="checked"<?php } ?> /> EnRoute<br />
					<input type="checkbox" name="creditcards_jcb" value="1" <?php if(in_array("JCB", $pmpro_accepted_credit_cards)) {?>checked="checked"<?php } ?> /> JCB<br />
				</td>
			</tr>
			<tr class="gateway gateway_authorizenet gateway_paypal gateway_paypalexpress" <?php if($gateway != "authorizenet" && $gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="tax">Sales Tax <small>(optional)</small></label>
				</th>
				<td>
					Tax State:
					<input type="text" name="tax_state" size="4" value="<?php echo $tax_state?>" /> <small>(abbreviation, e.g. "PA")</small>
					&nbsp; Tax Rate:
					<input type="text" name="tax_rate" size="10" value="<?php echo $tax_rate?>" /> <small>(decimal, e.g. "0.06")</small>
					<p><small>If values are given, tax will be applied for any members ordering from the selected state. For more complex tax rules, use the "pmpro_tax" filter.</small></p>
				</td>
			</tr>
		</tbody>
		</table>            
		<p class="submit">            
			<input name="savesettings" type="submit" class="button-primary" value="Save Settings" /> 		                			
		</p>             
	</form> 
		
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
