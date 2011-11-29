<?php
	global $pmpro_review, $skip_account_fields, $pmpro_paypal_token, $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_requirebilling, $pmpro_level, $pmpro_levels, $tospage, $pmpro_currency_symbol, $pmpro_show_discount_code;
	global $discount_code, $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth,$ExpirationYear;
	
	$gateway = pmpro_getOption("gateway");
?>

<form class="pmpro_form" action="<?php echo pmpro_url("checkout", "", "https")?>" method="post">

	<input type="hidden" id="level" name="level" value="<?php echo esc_attr($pmpro_level->id) ?>" />		
	<?php if($pmpro_msg) 
		{
	?>
		<div id="pmpro_message" class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
	<?php
		}
		else
		{
	?>
		<div id="pmpro_message" class="pmpro_message" style="display: none;"></div>
	<?php
		}
	?>
	
	<?php if($pmpro_review) { ?>
		<p>Almost done. Review the membership information and pricing below then <strong>click the "Complete Payment" button</strong> to finish your order.</p>
	<?php } ?>
		
	<table id="pmpro_pricing_fields" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
	<thead>
		<tr>
			<th>
				<?php if(count($pmpro_levels) > 1) { ?><span class="pmpro_thead-msg"><a href="<?php echo pmpro_url("levels"); ?>">change</a></span><?php } ?>Membership Level
			</th>						
		</tr>
	</thead>
	<tbody>                
		<tr>
			<td>				
				<p>You have selected the <strong><?php echo $pmpro_level->name?></strong> membership level.</p>
				
				<p id="pmpro_level_cost">
					<?php if($discount_code && pmpro_checkDiscountCode($discount_code)) { ?>
						The <strong><?php echo $discount_code?></strong> code has been applied to your order.
					<?php } ?>
					<?php echo pmpro_getLevelCost($pmpro_level)?>
					<?php echo pmpro_getLevelExpiration($pmpro_level)?>
				</p>
				
				<?php do_action("pmpro_checkout_after_level_cost"); ?>				
				
				<?php if($pmpro_show_discount_code) { ?>
				
					<?php if($discount_code && !$pmpro_review) { ?>
						<p id="other_discount_code_p" class="pmpro_small"><a id="other_discount_code_a" href="#discount_code">Click here to change your discount code</a>.</p>
					<?php } elseif(!$pmpro_review) { ?>
						<p id="other_discount_code_p" class="pmpro_small">Do you have a discount code? <a id="other_discount_code_a" href="#discount_code">Click here to enter your discount code</a>.</p>
					<?php } elseif($pmpro_review && $discount_code) { ?>
						<p><strong>Discount Code:</strong> <?php echo $discount_code?></p>
					<?php } ?>
				
				<?php } ?>
			</td>
		</tr>
		<?php if($pmpro_show_discount_code) { ?>
		<tr id="other_discount_code_tr" style="display: none;">
			<td>
				<div>
					<label for="other_discount_code">Discount Code</label>
					<input id="other_discount_code" name="other_discount_code" type="text" class="input" size="20" value="<?php echo esc_attr($discount_code)?>" /> 
					<input type="button" name="other_discount_code_button" id="other_discount_code_button" value="Apply" />					
				</div>				
			</td>
		</tr>
		<?php } ?>
	</tbody>
	</table>
	
	<?php if($pmpro_show_discount_code) { ?>
	<script>
		//update discount code link to show field at top of form
		jQuery('#other_discount_code_a').attr('href', 'javascript:void(0);');
		jQuery('#other_discount_code_a').click(function() {
			jQuery('#other_discount_code_tr').show();
			jQuery('#other_discount_code_p').hide();		
			jQuery('#other_discount_code').focus();
		});
		
		//update real discount code field as the other discount code field is updated
		jQuery('#other_discount_code').keyup(function() {
			jQuery('#discount_code').val(jQuery('#other_discount_code').val());
		});
		jQuery('#other_discount_code').blur(function() {
			jQuery('#discount_code').val(jQuery('#other_discount_code').val());
		});
		
		//update other discount code field as the real discount code field is updated
		jQuery('#discount_code').keyup(function() {
			jQuery('#other_discount_code').val(jQuery('#discount_code').val());
		});
		jQuery('#discount_code').blur(function() {
			jQuery('#other_discount_code').val(jQuery('#discount_code').val());
		});
		
		//applying a discount code
		jQuery('#other_discount_code_button').click(function() {
			var code = jQuery('#other_discount_code').val();
			var level_id = jQuery('#level').val();
												
			if(code)
			{									
				//hide any previous message
				jQuery('.pmpro_discount_code_msg').hide();
				
				//disable the apply button
				jQuery('#other_discount_code_button').attr('disabled', 'disabled');
				
				jQuery.ajax({
					url: '<?php echo plugins_url("paid-memberships-pro/services/applydiscountcode.php")?>',type:'POST',timeout:2000,
					dataType: 'html',
					data: "code=" + code + "&level=" + level_id + "&msgfield=pmpro_message",
					error: function(xml){
						alert('Error applying discount code [1]');
						
						//enable apply button
						jQuery('#other_discount_code_button').removeAttr('disabled');
					},
					success: function(responseHTML){
						if (responseHTML == 'error')
						{
							alert('Error applying discount code [2]');
						}
						else
						{
							jQuery('#pmpro_message').html(responseHTML);
						}		
						
						//enable invite button
						jQuery('#other_discount_code_button').removeAttr('disabled');										
					}
				});
			}																		
		});
	</script>
	<?php } ?>
	
	<?php if(!$skip_account_fields && !$pmpro_review) { ?>
	<table id="pmpro_user_fields" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
	<thead>
		<tr>
			<th>
				<span class="pmpro_thead-msg">If you already have an account, <a href="<?php echo get_bloginfo("url")?>/wp-login.php?redirect_to=<?php echo urlencode(pmpro_url("checkout", "?level=" . $pmpro_level->id))?>">log in here</a>.</span>Account Information
			</th>						
		</tr>
	</thead>
	<tbody>                
		<tr>
			<td>
				<div>
					<label for="username">Username</label>
					<input id="username" name="username" type="text" class="input" size="30" value="<?php echo esc_attr($username)?>" /> 
				</div>
				
				<?php
					do_action('pmpro_checkout_after_username');
				?>
				
				<div>
					<label for="password">Password</label>
					<input id="password" name="password" type="password" class="input" size="30" value="<?php echo esc_attr($password)?>" /> 
				</div>
				<div>
					<label for="password2">Confirm Password</label>
					<input id="password2" name="password2" type="password" class="input" size="30" value="<?php echo esc_attr($password2)?>" /> 
				</div>
				
				<?php
					do_action('pmpro_checkout_after_password');
				?>
				
				<div>
					<label for="bemail">E-mail Address</label>
					<input id="bemail" name="bemail" type="text" class="input" size="30" value="<?php echo esc_attr($bemail)?>" /> 
				</div>
				<div>
					<label for="bconfirmemail">Confirm E-mail</label>
					<input id="bconfirmemail" name="bconfirmemail" type="text" class="input" size="30" value="<?php echo esc_attr($bconfirmemail)?>" /> 
				</div>
				
				<?php
					do_action('pmpro_checkout_after_email');
				?>
				
				<div class="pmpro_hidden">
					<label for="fullname">Full Name</label>
					<input id="fullname" name="fullname" type="text" class="input" size="30" value="<?php echo esc_attr($fullname)?>" /> <strong>LEAVE THIS BLANK</strong>
				</div>				

				<div class="pmpro_captcha">
				<?php 																								
					global $recaptcha, $recaptcha_publickey;
					if($recaptcha == 2 || ($recaptcha == 1 && !(float)$pmpro_level->billing_amount && !(float)$pmpro_level->trial_amount)) 
					{											
						echo recaptcha_get_html($recaptcha_publickey, NULL, true);
					}								
				?>								
				</div>
				
				<?php
					do_action('pmpro_checkout_after_captcha');
				?>
				
			</td>
	</table>   
	<?php } elseif($current_user->ID && !$pmpro_review) { ?>                        	                       										
		<p>You are logged in as <strong><?php echo $current_user->user_login?></strong>. If you would like to use a different account for this membership, <a href="<?php echo wp_logout_url(pmpro_url("checkout", "?level=" . $pmpro_level->id));?>">log out now</a>.</p>
	<?php } ?>
	
	<?php					
		if($tospage && !$pmpro_review)
		{						
		?>
		<table id="pmpro_tos_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
		<tr>
			<th><?php echo $tospage->post_title?></th>
		</tr>
	</thead>
		<tbody>
			<tr class="odd">
				<td>								
					<div id="pmpro_license">
<?php echo wpautop($tospage->post_content)?>
					</div>								
					<input type="checkbox" name="tos" value="1" /> I agree to the <?php echo $tospage->post_title?>
				</td>
			</tr>
		</tbody>
		</table>
		<?php
		}
	?>
	
	<?php do_action("pmpro_checkout_boxes"); ?>	
	
	<table id="pmpro_billing_address_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0" <?php if(!$pmpro_requirebilling || $gateway == "paypalexpress") { ?>style="display: none;"<?php } ?>>
	<thead>
		<tr>
			<th>Billing Address</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<div>
					<label for="bfirstname">First Name</label>
					<input id="bfirstname" name="bfirstname" type="text" class="input" size="30" value="<?php echo esc_attr($bfirstname)?>" /> 
				</div>	
				<div>
					<label for="blastname">Last Name</label>
					<input id="blastname" name="blastname" type="text" class="input" size="30" value="<?php echo esc_attr($blastname)?>" /> 
				</div>					
				<div>
					<label for="baddress1">Address 1</label>
					<input id="baddress1" name="baddress1" type="text" class="input" size="30" value="<?php echo esc_attr($baddress1)?>" /> 
				</div>
				<div>
					<label for="baddress2">Address 2</label>
					<input id="baddress2" name="baddress2" type="text" class="input" size="30" value="<?php echo esc_attr($baddress2)?>" /> <small class="lite">(optional)</small>
				</div>
				
				<?php
					$longform_address = apply_filters("pmpro_longform_address", false);
					if($longform_address)
					{
				?>
					<div>
						<label for="bcity">City</label>
						<input id="bcity" name="bcity" type="text" class="input" size="30" value="<?php echo esc_attr($bcity)?>" /> 
					</div>
					<div>
						<label for="bstate">State</label>
						<input id="bstate" name="bstate" type="text" class="input" size="30" value="<?php echo esc_attr($bstate)?>" /> 
					</div>
					<div>
						<label for="bzipcode">Zip/Postal Code</label>
						<input id="bzipcode" name="bzipcode" type="text" class="input" size="30" value="<?php echo esc_attr($bzipcode)?>" /> 
					</div>					
				<?php
					}
					else
					{
					?>
					<div>
						<label for="bcity_state_zip">City, State Zip</label>
						<input id="bcity" name="bcity" type="text" class="input" size="14" value="<?php echo esc_attr($bcity)?>" />, <input id="bstate" name="bstate" type="text" class="input" size="2" value="<?php echo esc_attr($bstate)?>" /> <input id="bzipcode" name="bzipcode" type="text" class="input" size="5" value="<?php echo esc_attr($bzipcode)?>" /> 
					</div>
					<?php
					}
				?>
				
				<?php
					$show_country = apply_filters("pmpro_international_addresses", false);
					if($show_country)
					{
				?>
				<div>
					<label for="bcountry">Country</label>
					<select name="bcountry">
						<?php
							global $pmpro_countries, $pmpro_default_country;
							foreach($pmpro_countries as $abbr => $country)
							{
								if(!$bcountry)
									$bcountry = $pmpro_default_country;
							?>
							<option value="<?php echo $abbr?>" <?php if($abbr == $bcountry) { ?>selected="selected"<?php } ?>><?php echo $country?></option>
							<?php
							}
						?>
					</select>
				</div>
				<?php
					}
					else
					{
					?>
						<input type="hidden" name="bcountry" value="US" />
					<?php
					}
				?>
				<div>
					<label for="bphone">Phone</label>
					<input id="bphone" name="bphone" type="text" class="input" size="30" value="<?php echo esc_attr($bphone)?>" /> 
					<?php echo formatPhone($bphone); ?>
				</div>		
				<?php if($skip_account_fields) { ?>
				<?php
					if($current_user->ID)
					{
						if(!$bemail && $current_user->user_email)									
							$bemail = $current_user->user_email;
						if(!$bconfirmemail && $current_user->user_email)									
							$bconfirmemail = $current_user->user_email;									
					}
				?>
				<div>
					<label for="bemail">E-mail Address</label>
					<input id="bemail" name="bemail" type="text" class="input" size="30" value="<?php echo esc_attr($bemail)?>" /> 
				</div>
				<div>
					<label for="bconfirmemail">Confirm E-mail</label>
					<input id="bconfirmemail" name="bconfirmemail" type="text" class="input" size="30" value="<?php echo esc_attr($bconfirmemail)?>" /> 

				</div>	                        
				<?php } ?>    
			</td>						
		</tr>											
	</tbody>
	</table>                   
		
	<?php
		$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
		$pmpro_accepted_credit_cards = split(",", $pmpro_accepted_credit_cards);
		if(count($pmpro_accepted_credit_cards) == 1)
		{
			$pmpro_accepted_credit_cards_string = $pmpro_accepted_credit_cards[0];
		}
		elseif(count($pmpro_accepted_credit_cards) == 2)
		{
			$pmpro_accepted_credit_cards_string = $pmpro_accepted_credit_cards[0] . " and " . $pmpro_accepted_credit_cards[1];
		}
		elseif(count($pmpro_accepted_credit_cards) > 2)
		{
			$allbutlast = $pmpro_accepted_credit_cards;
			unset($allbutlast[count($allbutlast) - 1]);
			$pmpro_accepted_credit_cards_string = implode(", ", $allbutlast) . ", and " . $pmpro_accepted_credit_cards[count($pmpro_accepted_credit_cards) - 1];
		}
	?>
	
	<table id="pmpro_payment_information_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0" <?php if(!$pmpro_requirebilling || $gateway == "paypalexpress") { ?>style="display: none;"<?php } ?>>
	<thead>
		<tr>
			<th colspan="2"><span class="pmpro_thead-msg">We Accept <?php echo $pmpro_accepted_credit_cards_string?></span>Payment Information</th>
		</tr>
	</thead>
	<tbody>                    
		<tr valign="top">		
			<td>	
				<?php
					$sslseal = pmpro_getOption("sslseal");
					if($sslseal)
					{
					?>
						<div class="pmpro_sslseal"><?php echo stripslashes($sslseal)?></div>
					<?php
					}
				?>
				<div>
					<label for="CardType">Card Type</label>
					<select name="CardType">
						<?php foreach($pmpro_accepted_credit_cards as $cc) { ?>
							<option value="<?php echo $cc?>" <?php if($CardType == $cc) { ?>selected="selected"<?php } ?>><?php echo $cc?></option>
						<?php } ?>												
					</select> 
				</div>
			
				<div>
					<label for="AccountNumber">Card Number</label>
					<input id="AccountNumber" name="AccountNumber"  class="input" type="text" size="25" value="<?php echo esc_attr($AccountNumber)?>" /> 
				</div>
			
				<div>
					<label for="ExpirationMonth">Expiration Date</label>
					<select name="ExpirationMonth">
						<option value="01" <?php if($ExpirationMonth == "01") { ?>selected="selected"<?php } ?>>01</option>
						<option value="02" <?php if($ExpirationMonth == "02") { ?>selected="selected"<?php } ?>>02</option>
						<option value="03" <?php if($ExpirationMonth == "03") { ?>selected="selected"<?php } ?>>03</option>
						<option value="04" <?php if($ExpirationMonth == "04") { ?>selected="selected"<?php } ?>>04</option>
						<option value="05" <?php if($ExpirationMonth == "05") { ?>selected="selected"<?php } ?>>05</option>
						<option value="06" <?php if($ExpirationMonth == "06") { ?>selected="selected"<?php } ?>>06</option>
						<option value="07" <?php if($ExpirationMonth == "07") { ?>selected="selected"<?php } ?>>07</option>
						<option value="08" <?php if($ExpirationMonth == "08") { ?>selected="selected"<?php } ?>>08</option>
						<option value="09" <?php if($ExpirationMonth == "09") { ?>selected="selected"<?php } ?>>09</option>
						<option value="10" <?php if($ExpirationMonth == "10") { ?>selected="selected"<?php } ?>>10</option>
						<option value="11" <?php if($ExpirationMonth == "11") { ?>selected="selected"<?php } ?>>11</option>
						<option value="12" <?php if($ExpirationMonth == "12") { ?>selected="selected"<?php } ?>>12</option>
					</select>/<select name="ExpirationYear">
						<?php
							for($i = date("Y"); $i < date("Y") + 10; $i++)
							{
						?>
							<option value="<?php echo $i?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } ?>><?php echo $i?></option>
						<?php
							}
						?>
					</select> 
				</div>
			
				<div>
					<label for="CVV">CVV</label>
					<input class="input" id="CVV" name="CVV" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr($_REQUEST['CVV']); }?>" />  <small>(<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo pmpro_https_filter(PMPRO_URL)?>/pages/popup-cvv.html','cvv','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=600, height=475');">what's this?</a>)</small>
				</div>
				
				<?php if($pmpro_show_discount_code) { ?>
				<div>
					<label for="discount_code">Discount Code</label>
					<input class="input" id="discount_code" name="discount_code" type="text" size="20" value="<?php echo esc_attr($discount_code)?>" />
					<input type="button" id="discount_code_button" name="discount_code_button" value="Apply" />
					<p id="discount_code_message" class="pmpro_message" style="display: none;"></p>
				</div>
				<?php } ?>
				
			</td>			
		</tr>
	</tbody>
	</table>	
	<script>
		//checking a discount code
		jQuery('#discount_code_button').click(function() {
			var code = jQuery('#discount_code').val();
			var level_id = jQuery('#level').val();
												
			if(code)
			{									
				//hide any previous message
				jQuery('.pmpro_discount_code_msg').hide();				
				
				//disable the apply button
				jQuery('#discount_code_button').attr('disabled', 'disabled');
				
				jQuery.ajax({
					url: '<?php echo plugins_url("paid-memberships-pro/services/applydiscountcode.php")?>',type:'POST',timeout:2000,
					dataType: 'html',
					data: "code=" + code + "&level=" + level_id + "&msgfield=discount_code_message",
					error: function(xml){
						alert('Error applying discount code [1]');
						
						//enable apply button
						jQuery('#discount_code_button').removeAttr('disabled');
					},
					success: function(responseHTML){
						if (responseHTML == 'error')
						{
							alert('Error applying discount code [2]');
						}
						else
						{
							jQuery('#discount_code_message').html(responseHTML);
						}		
						
						//enable invite button
						jQuery('#discount_code_button').removeAttr('disabled');										
					}
				});
			}																		
		});
	</script>
	
	<div align="center">		
		<?php if($pmpro_review) { ?>
			<input type="hidden" name="confirm" value="1" />
			<input type="hidden" name="token" value="<?php echo esc_attr($pmpro_paypal_token)?>" />
			<input type="submit" class="pmpro_btn pmpro_btn-submit-checkout" value="Complete Payment &raquo;" />
		<?php } elseif($gateway == "paypalexpress") { ?>
			<input type="hidden" name="submit-checkout" value="1" />		
			<input type="image" value="Checkout with PayPal &raquo;" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" />
		<?php } else { ?>
			<input type="hidden" name="submit-checkout" value="1" />		
			<input type="submit" class="pmpro_btn pmpro_btn-submit-checkout" value="Submit and <?php if($pmpro_requirebilling) { ?>Checkout<?php } else { ?>Confirm<?php } ?> &raquo;" />
		<?php } ?>
	</div>	
		
</form>
<script>
	// Find ALL <form> tags on your page
	jQuery('form').submit(function(){
		// On submit disable its submit button
		jQuery('input[type=submit]', this).attr('disabled', 'disabled');
		jQuery('input[type=image]', this).attr('disabled', 'disabled');
	});
</script>
