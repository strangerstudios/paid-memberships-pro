<?php 				
	global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_currency_symbol;
	global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
	
	$level = $current_user->membership_level;
	if($level) 
	{ 
	?>
		<p>Logged in as <strong><?=$current_user->user_login?></strong>. <small><a href="<?=wp_logout_url(get_bloginfo("url") . "/membership-checkout/?level=" . $level->id);?>">logout</a></small></p>
		<ul>
			<li><strong>Level:</strong> <?=$level->name?></li>
		<?php if($level->billing_amount > 0) { ?>
			<li><strong>Membership Fee:</strong>
			<?=$pmpro_currency_symbol?><?=$level->billing_amount?>
			<?php if($level->cycle_number > 1) { ?>
				per <?=$level->cycle_number?> <?=sornot($level->cycle_period,$level->cycle_number)?>
			<?php } elseif($level->cycle_number == 1) { ?>
				per <?=$level->cycle_period?>
			<?php } ?>
			</li>
		<?php } ?>						
		
		<?php if($level->billing_limit) { ?>
			<li><strong>Duration:</strong> <?=$level->billing_limit.' '.sornot($level->cycle_period,$level->billing_limit)?></li>
		<?php } ?>
		
		<?php
			//the nextpayment code is not tight yet
			/*
			$nextpayment = pmpro_next_payment();
			if($nextpayment)
			{
			?>
				<li><strong>Next Invoice:</strong> <?=date("F j, Y", $nextpayment)?></li>
			<?php
			}
			*/
		?>
		</ul>
	<?php 
	} 
?>

<?php if(pmpro_isLevelRecurring($level)) { ?>
	<form class="pmpro_form" action="<?=pmpro_url("billing", "", "https")?>" method="post">

		<input type="hidden" name="level" value="<?=$level->id?>" />		
		<?php if($pmpro_msg) 
			{
		?>
			<div class="pmpro_message <?=$pmpro_msgt?>"><?=$pmpro_msg?></div>
		<?php
			}
		?>                        	                       	                       														          
									
		<table class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
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
						<input id="bfirstname" name="bfirstname" type="text" class="input" size="20" value="<?=$bfirstname?>" /> 
					</div>	
					<div>
						<label for="blastname">Last Name</label>
						<input id="blastname" name="blastname" type="text" class="input" size="20" value="<?=$blastname?>" /> 
					</div>					
					<div>
						<label for="baddress1">Address 1</label>
						<input id="baddress1" name="baddress1" type="text" class="input" size="20" value="<?=$baddress1?>" /> 
					</div>
					<div>
						<label for="baddress2">Address 2</label>
						<input id="baddress2" name="baddress2" type="text" class="input" size="20" value="<?=$baddress2?>" /> <small class="lite">(optional)</small>
					</div>
					<div>
						<label for="bcity_state_zip">City, State Zip</label>
						<input id="bcity" name="bcity" type="text" class="input" size="14" value="<?=$bcity?>" />, <input id="bstate" name="bstate" type="text" class="input" size="2" value="<?=$bstate?>" /> <input id="bzipcode" name="bzipcode" type="text" class="input" size="5" value="<?=$bzipcode?>" /> 
					</div>
					<div>
						<label for="bphone">Phone</label>
						<input id="bphone" name="bphone" type="text" class="input" size="20" value="<?=$bphone?>" /> 
					</div>		
					<?php if($current_user->ID) { ?>
					<?php
						if(!$bemail && $current_user->user_email)									
							$bemail = $current_user->user_email;
						if(!$bconfirmemail && $current_user->user_email)									
							$bconfirmemail = $current_user->user_email;									
					?>
					<div>
						<label for="bemail">E-mail Address</label>
						<input id="bemail" name="bemail" type="text" class="input" size="20" value="<?=$bemail?>" /> 
					</div>
					<div>
						<label for="bconfirmemail">Confirm E-mail</label>
						<input id="bconfirmemail" name="bconfirmemail" type="text" class="input" size="20" value="<?=$bconfirmemail?>" /> 

					</div>	                        
					<?php } ?>    
				</td>						
			</tr>											
		</tbody>
		</table>                   
		
		<table class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
			<tr>
				<th colspan="2"><span class="pmpro_thead-msg">We Accept Visa, Mastercard, American Express, and Discover</span>Credit Card Information</th>
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
							<div class="pmpro_sslseal"><?=stripslashes($sslseal)?></div>
						<?php
						}
					?>
					<div>				
						<label for="CardType">Card Type</label>
						<select name="CardType">
							<option value="Visa" <?php if($CardType == "Visa") { ?>selected="selected"<?php } ?>>Visa</option>
							<option value="MasterCard" <?php if($CardType == "MasterCard") { ?>selected="selected"<?php } ?>>MasterCard</option>
							<option value="Amex" <?php if($CardType == "Amex") { ?>selected="selected"<?php } ?>>American Express</option>
							<option value="Discover" <?php if($CardType == "Discover") { ?>selected="selected"<?php } ?>>Discover</option>
						</select> 
					</div>
				
					<div>
						<label for="AccountNumber">Card Number</label>
						<input id="AccountNumber" name="AccountNumber"  class="input" type="text" size="25" value="<?=$AccountNumber?>" /> 
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
								<option value="<?=$i?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } ?>><?=$i?></option>
							<?php
								}
							?>
						</select> 
					</div>
				
					<div>
						<label for="CVV">CVV</label>
						<input class="input" id="CVV" name="CVV" type="text" size="4" value="" />  <small>(<a href="#" onclick="javascript:window.open('<?=plugins_url( "/pages/popup-cvv.html", dirname(__FILE__))?>','cvv','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=600, height=475');">what's this?</a>)</small>
					</div>													
				</td>
			</tr>		
		</tbody>
		</table>																	
		
		<div align="center">
			<input type="hidden" name="update-billing" value="1" />
			<input type="submit" class="pmpro_btn pmpro_btn-submit" value="Update" />
			<input type="button" name="cancel" class="pmpro_btn pmpro_btn-cancel" value="Cancel" onclick="location.href='<?=pmpro_url("account")?>';" />
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
<?php } else { ?>
	<p>This subscription is not recurring. So you don't need to update your billing information.</p>
<?php } ?>	