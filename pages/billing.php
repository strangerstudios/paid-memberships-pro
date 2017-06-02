<?php
	global $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $show_paypal_link;
	global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;

	if (! is_user_logged_in())
		wp_redirect(pmpro_url('levels'));

	/**
	 * Filter to set if PMPro uses email or text as the type for email field inputs.
	 * 
	 * @since 1.8.4.5
	 *
	 * @param bool $use_email_type, true to use email type, false to use text type
	 */
	$pmpro_email_field_type = apply_filters('pmpro_email_field_type', true);
	
	$gateway = pmpro_getOption("gateway");

	$level = $current_user->membership_level;


	//Make sure the $level object is a valid level definition
	if(isset($level->id) && !empty($level->id))
	{
	?>
		<p><?php printf(__("Logged in as <strong>%s</strong>.", 'paid-memberships-pro' ), $current_user->user_login);?> <small><a href="<?php echo wp_logout_url(get_bloginfo("url") . "/membership-checkout/?level=" . $level->id);?>"><?php _e("logout", 'paid-memberships-pro' );?></a></small></p>
		<?php
		 /**
		 * pmpro_billing_message_top hook to add in general content to the billing page without using custom page templates.
		 *
		 * @since 1.9.2
		 */
		 do_action('pmpro_billing_message_top'); ?>

		<ul>
			<?php 
			 /**
			 * pmpro_billing_bullets_top hook allows you to add information to the billing list (at the top).
			 *
			 * @since 1.9.2
			 * @param {objects} {$level} {Passes the $level object}
			 */
			do_action('pmpro_billing_bullets_top', $level);?>
			<li><strong><?php _e("Level", 'paid-memberships-pro' );?>:</strong> <?php echo $level->name?></li>
		<?php if($level->billing_amount > 0) { ?>
			<li><strong><?php _e("Membership Fee", 'paid-memberships-pro' );?>:</strong>
				<?php
					$level = $current_user->membership_level;
					if($current_user->membership_level->cycle_number > 1) {
						printf(__('%s every %d %s.', 'paid-memberships-pro' ), pmpro_formatPrice($level->billing_amount), $level->cycle_number, pmpro_translate_billing_period($level->cycle_period, $level->cycle_number));
					} elseif($current_user->membership_level->cycle_number == 1) {
						printf(__('%s per %s.', 'paid-memberships-pro' ), pmpro_formatPrice($level->billing_amount), pmpro_translate_billing_period($level->cycle_period));
					} else {
						echo pmpro_formatPrice($current_user->membership_level->billing_amount);
					}
				?>
				
			</li>
		<?php } ?>

		<?php if($level->billing_limit) { ?>
			<li><strong><?php _e("Duration", 'paid-memberships-pro' );?>:</strong> <?php echo $level->billing_limit.' '.sornot($level->cycle_period,$level->billing_limit)?></li>
		<?php } ?>
		<?php 
		 /**
		 * pmpro_billing_bullets_top hook allows you to add information to the billing list (at the bottom).
		 *
		 * @since 1.9.2
		 * @param {objects} {$level} {Passes the $level object}
		 */
		do_action('pmpro_billing_bullets_bottom', $level);?>
		</ul>
	<?php
	}
?>

<?php if(pmpro_isLevelRecurring($level)) { ?>
	<?php if($show_paypal_link) { ?>

		<p><?php  _e('Your payment subscription is managed by PayPal. Please <a href="http://www.paypal.com">login to PayPal here</a> to update your billing information.', 'paid-memberships-pro' );?></p>

	<?php } else { ?>

		<form id="pmpro_form" class="pmpro_form" action="<?php echo pmpro_url("billing", "", "https")?>" method="post">

			<input type="hidden" name="level" value="<?php echo esc_attr($level->id);?>" />
			<?php if($pmpro_msg)
				{
			?>
				<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
			<?php
				}
			?>

			<?php
				$pmpro_include_billing_address_fields = apply_filters('pmpro_include_billing_address_fields', true);
				if($pmpro_include_billing_address_fields)
				{ 
			?>
			<table id="pmpro_billing_address_fields" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php _e('Billing Address', 'paid-memberships-pro' );?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<div>
							<label for="bfirstname"><?php _e('First Name', 'paid-memberships-pro' );?></label>
							<input id="bfirstname" name="bfirstname" type="text" class="input" size="20" value="<?php echo esc_attr($bfirstname);?>" />
						</div>
						<div>
							<label for="blastname"><?php _e('Last Name', 'paid-memberships-pro' );?></label>
							<input id="blastname" name="blastname" type="text" class="input" size="20" value="<?php echo esc_attr($blastname);?>" />
						</div>
						<div>
							<label for="baddress1"><?php _e('Address 1', 'paid-memberships-pro' );?></label>
							<input id="baddress1" name="baddress1" type="text" class="input" size="20" value="<?php echo esc_attr($baddress1);?>" />
						</div>
						<div>
							<label for="baddress2"><?php _e('Address 2', 'paid-memberships-pro' );?></label>
							<input id="baddress2" name="baddress2" type="text" class="input" size="20" value="<?php echo esc_attr($baddress2);?>" /> <small class="lite">(<?php _e('optional', 'paid-memberships-pro' );?>)</small>
						</div>

						<?php
							$longform_address = apply_filters("pmpro_longform_address", false);
							if($longform_address)
							{
							?>
								<div>
									<label for="bcity"><?php _e('City', 'paid-memberships-pro' );?>City</label>
									<input id="bcity" name="bcity" type="text" class="input" size="30" value="<?php echo esc_attr($bcity)?>" />
								</div>
								<div>
									<label for="bstate"><?php _e('State', 'paid-memberships-pro' );?>State</label>
									<input id="bstate" name="bstate" type="text" class="input" size="30" value="<?php echo esc_attr($bstate)?>" />
								</div>
								<div>
									<label for="bzipcode"><?php _e('Postal Code', 'paid-memberships-pro' );?></label>
									<input id="bzipcode" name="bzipcode" type="text" class="input" size="30" value="<?php echo esc_attr($bzipcode)?>" />
								</div>
							<?php
							}
							else
							{
							?>
								<div>
									<label for="bcity_state_zip"><?php _e('City, State Zip', 'paid-memberships-pro' );?></label>
									<input id="bcity" name="bcity" type="text" class="input" size="14" value="<?php echo esc_attr($bcity)?>" />,
									<?php
										$state_dropdowns = apply_filters("pmpro_state_dropdowns", false);
										if($state_dropdowns === true || $state_dropdowns == "names")
										{
											global $pmpro_states;
										?>
										<select name="bstate">
											<option value="">--</option>
											<?php
												foreach($pmpro_states as $ab => $st)
												{
											?>
												<option value="<?php echo esc_attr($ab);?>" <?php if($ab == $bstate) { ?>selected="selected"<?php } ?>><?php echo $st;?></option>
											<?php } ?>
										</select>
										<?php
										}
										elseif($state_dropdowns == "abbreviations")
										{
											global $pmpro_states_abbreviations;
										?>
											<select name="bstate">
												<option value="">--</option>
												<?php
													foreach($pmpro_states_abbreviations as $ab)
													{
												?>
													<option value="<?php echo esc_attr($ab);?>" <?php if($ab == $bstate) { ?>selected="selected"<?php } ?>><?php echo $ab;?></option>
												<?php } ?>
											</select>
										<?php
										}
										else
										{
										?>
										<input id="bstate" name="bstate" type="text" class="input" size="2" value="<?php echo esc_attr($bstate)?>" />
										<?php
										}
									?>
									<input id="bzipcode" name="bzipcode" type="text" class="input" size="5" value="<?php echo esc_attr($bzipcode)?>" />
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
							<label for="bcountry"><?php _e('Country', 'paid-memberships-pro' );?></label>
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
								<input type="hidden" id="bcountry" name="bcountry" value="US" />
							<?php
							}
						?>
						<div>
							<label for="bphone"><?php _e('Phone', 'paid-memberships-pro' );?></label>
							<input id="bphone" name="bphone" type="text" class="input" size="20" value="<?php echo esc_attr($bphone)?>" />
						</div>
						<?php if($current_user->ID) { ?>
						<?php
							if(!$bemail && $current_user->user_email)
								$bemail = $current_user->user_email;
							if(!$bconfirmemail && $current_user->user_email)
								$bconfirmemail = $current_user->user_email;
						?>
						<div>
							<label for="bemail"><?php _e('E-mail Address', 'paid-memberships-pro' );?></label>
							<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="input" size="20" value="<?php echo esc_attr($bemail)?>" />
						</div>
						<div>
							<label for="bconfirmemail"><?php _e('Confirm E-mail', 'paid-memberships-pro' );?></label>
							<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="input" size="20" value="<?php echo esc_attr($bconfirmemail)?>" />

						</div>
						<?php } ?>
					</td>
				</tr>
			</tbody>
			</table>
			<?php } ?>

			<?php
			//make sure gateways will show up credit card fields
			global $pmpro_requirebilling;
			$pmpro_requirebilling = true;
			
			//do we need to show the payment information (credit card) fields? gateways will override this
			$pmpro_include_payment_information_fields = apply_filters('pmpro_include_payment_information_fields', true);						
			if($pmpro_include_payment_information_fields)
			{
				$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
				$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
				$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);
			?>
			<table id="pmpro_payment_information_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th>
						<span class="pmpro_thead-name"><?php _e('Credit Card Information', 'paid-memberships-pro' );?></span>
						<span class="pmpro_thead-msg"><?php printf(__('We accept %s', 'paid-memberships-pro' ), $pmpro_accepted_credit_cards_string);?></span>
					</th>
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
						
						<?php
							$pmpro_include_cardtype_field = apply_filters('pmpro_include_cardtype_field', false);
							if($pmpro_include_cardtype_field)
							{
							?>
							<div class="pmpro_payment-card-type">
								<label for="CardType"><?php _e('Card Type', 'paid-memberships-pro' );?></label>
								<select id="CardType" name="CardType" class=" <?php echo pmpro_getClassForField("CardType");?>">
									<?php foreach($pmpro_accepted_credit_cards as $cc) { ?>
										<option value="<?php echo $cc?>" <?php if($CardType == $cc) { ?>selected="selected"<?php } ?>><?php echo $cc?></option>
									<?php } ?>
								</select>
							</div>
							<?php
							}
							else
							{
							?>
							<input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr($CardType);?>" />
							<script>
								<!--
								jQuery(document).ready(function() {
										jQuery('#AccountNumber').validateCreditCard(function(result) {
											var cardtypenames = {
												"amex"                      : "American Express",
												"diners_club_carte_blanche" : "Diners Club Carte Blanche",
												"diners_club_international" : "Diners Club International",
												"discover"                  : "Discover",
												"jcb"                       : "JCB",
												"laser"                     : "Laser",
												"maestro"                   : "Maestro",
												"mastercard"                : "Mastercard",
												"visa"                      : "Visa",
												"visa_electron"             : "Visa Electron"
											};

											if(result.card_type)
												jQuery('#CardType').val(cardtypenames[result.card_type.name]);
											else
												jQuery('#CardType').val('Unknown Card Type');
										});
								});
								-->
							</script>
							<?php
							}
						?>
						
						<div>
							<label for="AccountNumber"><?php _e('Card Number', 'paid-memberships-pro' );?></label>
							<input id="AccountNumber" name="AccountNumber" class="input <?php echo pmpro_getClassForField("AccountNumber");?>" type="text" size="25" value="<?php echo esc_attr($AccountNumber)?>" autocomplete="off" />
						</div>

						<div>
							<label for="ExpirationMonth"><?php _e('Expiration Date', 'paid-memberships-pro' );?></label>
							<select id="ExpirationMonth" name="ExpirationMonth">
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
							</select>/<select id="ExpirationYear" name="ExpirationYear">
								<?php
									for($i = date_i18n("Y"); $i < date_i18n("Y") + 10; $i++)
									{
								?>
									<option value="<?php echo $i?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } ?>><?php echo $i?></option>
								<?php
									}
								?>
							</select>
						</div>

						<?php
							$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
							if($pmpro_show_cvv)
							{
								if ( true == ini_get('allow_url_include') ) {
									$cvv_template = pmpro_loadTemplate('popup-cvv', 'url', 'pages', 'html');
								} else {
									$cvv_template = plugins_url( 'paid-memberships-pro/pages/popup-cvv.html', PMPRO_DIR );
								}

						?>
						<div>
							<label for="CVV"><?php _e('CVV', 'paid-memberships-pro' );?></label>
							<input class="input" id="CVV" name="CVV" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr($_REQUEST['CVV']); }?>" class=" <?php echo pmpro_getClassForField("CVV");?>" />  <small>(<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo pmpro_https_filter($cvv_template); ?>','cvv','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=600, height=475');"><?php _e("what's this?", 'paid-memberships-pro' );?></a>)</small>
						</div>
						<?php
							}
						?>
					</td>
				</tr>
			</tbody>
			</table>			
			<?php } // if($pmpro_include_payment_information_fields) ?>
			
			<?php do_action("pmpro_billing_before_submit_button"); ?>
		
			<div align="center">
				<input type="hidden" name="update-billing" value="1" />
				<input type="submit" class="pmpro_btn pmpro_btn-submit" value="<?php _e('Update', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="pmpro_btn pmpro_btn-cancel" value="<?php _e('Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo pmpro_url("account")?>';" />
			</div>

		</form>
		<script>
			<!--
			// Find ALL <form> tags on your page
			jQuery('form').submit(function(){
				// On submit disable its submit button
				jQuery('input[type=submit]', this).attr('disabled', 'disabled');
				jQuery('input[type=image]', this).attr('disabled', 'disabled');
			});
			-->
		</script>
	<?php } ?>
<?php } else { ?>
	<p><?php _e("This subscription is not recurring. So you don't need to update your billing information.", 'paid-memberships-pro' );?></p>
<?php } ?>
