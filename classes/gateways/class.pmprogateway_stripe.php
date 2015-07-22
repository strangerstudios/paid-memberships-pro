<?php
	//include pmprogateway
	require_once(dirname(__FILE__) . "/class.pmprogateway.php");

	//load classes init method
	add_action('init', array('PMProGateway_stripe', 'init'));

	/**
	 * PMProGateway_stripe Class
	 *
	 * Handles Stripe integration.
	 *
	 * @since  1.4
	 */
	class PMProGateway_stripe extends PMProGateway
	{
		/**
		 * Stripe Class Constructor
		 *
		 * @since 1.4
		 */
		function PMProGateway_stripe($gateway = NULL)
		{
			$this->gateway = $gateway;
			$this->gateway_environment = pmpro_getOption("gateway_environment");

			$this->loadStripeLibrary();
			Stripe::setApiKey(pmpro_getOption("stripe_secretkey"));
			Stripe::setAPIVersion("2015-07-13");

			return $this->gateway;
		}

		/**
		 * Load the Stripe API library.
		 *
		 * @since 1.8
		 * Moved into a method in version 1.8 so we only load it when needed.
		 */
		function loadStripeLibrary()
		{
			//load Stripe library if it hasn't been loaded already (usually by another plugin using Stripe)
			if(!class_exists("Stripe"))
				require_once(dirname(__FILE__) . "/../../includes/lib/Stripe/Stripe.php");
		}

		/**
		 * Run on WP init
		 *
		 * @since 1.8
		 */
		static function init()
		{
			//make sure Stripe is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_stripe', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_stripe', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_stripe', 'pmpro_payment_option_fields'), 10, 2);

			//add some fields to edit user page (Updates)
			add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_stripe', 'user_profile_fields'));
			add_action('profile_update', array('PMProGateway_stripe', 'user_profile_fields_save'));

			//old global RE showing billing address or not
			global $pmpro_stripe_lite;
			$pmpro_stripe_lite = apply_filters("pmpro_stripe_lite", !pmpro_getOption("stripe_billingaddress"));	//default is oposite of the stripe_billingaddress setting

			//updates cron
			add_action('pmpro_activation', array('PMProGateway_stripe', 'pmpro_activation'));
			add_action('pmpro_deactivation', array('PMProGateway_stripe', 'pmpro_deactivation'));
			add_action('pmpro_cron_stripe_subscription_updates', array('PMProGateway_stripe', 'pmpro_cron_stripe_subscription_updates'));

			//code to add at checkout if Stripe is the current gateway
			$gateway = pmpro_getOption("gateway");
			if($gateway == "stripe")
			{
				add_action('pmpro_checkout_preheader', array('PMProGateway_stripe', 'pmpro_checkout_preheader'));
				add_filter('pmpro_checkout_order', array('PMProGateway_stripe', 'pmpro_checkout_order'));
				add_filter('pmpro_include_billing_address_fields', array('PMProGateway_stripe', 'pmpro_include_billing_address_fields'));
				add_filter('pmpro_include_cardtype_field', array('PMProGateway_stripe', 'pmpro_include_billing_address_fields'));
				add_filter('pmpro_include_payment_information_fields', array('PMProGateway_stripe', 'pmpro_include_payment_information_fields'));
			}
		}

		/**
		 * Make sure Stripe is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['stripe']))
				$gateways['stripe'] = __('Stripe', 'pmpro');

			return $gateways;
		}

		/**
		 * Get a list of payment options that the Stripe gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'stripe_secretkey',
				'stripe_publishablekey',
				'stripe_billingaddress',
				'currency',
				'use_ssl',
				'tax_state',
				'tax_rate',
				'accepted_credit_cards'
			);

			return $options;
		}

		/**
		 * Set payment options for payment settings page.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_options($options)
		{
			//get stripe options
			$stripe_options = PMProGateway_stripe::getGatewayOptions();

			//merge with others.
			$options = array_merge($stripe_options, $options);

			return $options;
		}

		/**
		 * Display fields for Stripe options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
		?>
		<tr class="pmpro_settings_divider gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<?php _e('Stripe Settings', 'pmpro'); ?>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_secretkey"><?php _e('Secret Key', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="stripe_secretkey" name="stripe_secretkey" size="60" value="<?php echo esc_attr($values['stripe_secretkey'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_publishablekey"><?php _e('Publishable Key', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="stripe_publishablekey" name="stripe_publishablekey" size="60" value="<?php echo esc_attr($values['stripe_publishablekey'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="stripe_billingaddress"><?php _e('Show Billing Address Fields', 'pmpro');?>:</label>
			</th>
			<td>
				<select id="stripe_billingaddress" name="stripe_billingaddress">
					<option value="0" <?php if(empty($values['stripe_billingaddress'])) { ?>selected="selected"<?php } ?>><?php _e('No', 'pmpro');?></option>
					<option value="1" <?php if(!empty($values['stripe_billingaddress'])) { ?>selected="selected"<?php } ?>><?php _e('Yes', 'pmpro');?></option>
				</select>
				<small><?php _e("Stripe doesn't require billing address fields. Choose 'No' to hide them on the checkout page.<br /><strong>If No, make sure you disable address verification in the Stripe dashboard settings.</strong>", 'pmpro');?></small>
			</td>
		</tr>
		<tr class="gateway gateway_stripe" <?php if($gateway != "stripe") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label><?php _e('Web Hook URL', 'pmpro');?>:</label>
			</th>
			<td>
				<p><?php _e('To fully integrate with Stripe, be sure to set your Web Hook URL to', 'pmpro');?> <pre><?php echo admin_url("admin-ajax.php") . "?action=stripe_webhook";?></pre></p>
			</td>
		</tr>
		<?php
		}

		/**
		 * Code added to checkout preheader.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_preheader()
		{
			global $gateway, $pmpro_level;

			if($gateway == "stripe" && !pmpro_isLevelFree($pmpro_level))
			{
				//stripe js library
				wp_enqueue_script("stripe", "https://js.stripe.com/v2/", array(), NULL);

				//stripe js code for checkout
				function pmpro_stripe_javascript()
				{
					global $pmpro_gateway, $pmpro_level, $pmpro_stripe_lite;
				?>
				<script type="text/javascript">
					// this identifies your website in the createToken call below
					Stripe.setPublishableKey('<?php echo pmpro_getOption("stripe_publishablekey"); ?>');

					var pmpro_require_billing = true;

					jQuery(document).ready(function() {
						jQuery("#pmpro_form, .pmpro_form").submit(function(event) {

						//double check in case a discount code made the level free
						if(pmpro_require_billing)
						{
							//build array for creating token
							var args = {
								number: jQuery('#AccountNumber').val(),
								cvc: jQuery('#CVV').val(),
								exp_month: jQuery('#ExpirationMonth').val(),
								exp_year: jQuery('#ExpirationYear').val()
								<?php
									$pmpro_stripe_verify_address = apply_filters("pmpro_stripe_verify_address", pmpro_getOption('stripe_billingaddress'));
									if(!empty($pmpro_stripe_verify_address))
									{
									?>
									,address_line1: jQuery('#baddress1').val(),
									address_line2: jQuery('#baddress2').val(),
									address_city: jQuery('#bcity').val(),
									address_state: jQuery('#bstate').val(),
									address_zip: jQuery('#bzipcode').val(),
									address_country: jQuery('#bcountry').val()
								<?php
									}
								?>
							};

							if (jQuery('#bfirstname').length && jQuery('#blastname').length)
								args['name'] = jQuery.trim(jQuery('#bfirstname').val() + ' ' + jQuery('#blastname').val());

							//create token
							Stripe.createToken(args, stripeResponseHandler);

							// prevent the form from submitting with the default action
							return false;
						}
						else
							return true;	//not using Stripe anymore
						});
					});

					function stripeResponseHandler(status, response) {
						if (response.error) {
							// re-enable the submit button
							jQuery('.pmpro_btn-submit-checkout').removeAttr("disabled");

							//hide processing message
							jQuery('#pmpro_processing_message').css('visibility', 'hidden');

							// show the errors on the form
							alert(response.error.message);
							jQuery(".payment-errors").text(response.error.message);
						} else {
							var form$ = jQuery("#pmpro_form, .pmpro_form");
							// token contains id, last4, and card type
							var token = response['id'];
							// insert the token into the form so it gets submitted to the server
							form$.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");

							console.log(response);

							//insert fields for other card fields
							if(jQuery('#CardType[name=CardType]').length)
								jQuery('#CardType').val(response['card']['brand']);
							else
								form$.append("<input type='hidden' name='CardType' value='" + response['card']['brand'] + "'/>");
							form$.append("<input type='hidden' name='AccountNumber' value='XXXXXXXXXXXXX" + response['card']['last4'] + "'/>");
							form$.append("<input type='hidden' name='ExpirationMonth' value='" + ("0" + response['card']['exp_month']).slice(-2) + "'/>");
							form$.append("<input type='hidden' name='ExpirationYear' value='" + response['card']['exp_year'] + "'/>");

							// and submit
							form$.get(0).submit();
						}
					}
				</script>
				<?php
				}
				add_action("wp_head", "pmpro_stripe_javascript");

				//don't require the CVV
				function pmpro_stripe_dont_require_CVV($fields)
				{
					unset($fields['CVV']);
					return $fields;
				}
				add_filter("pmpro_required_billing_fields", "pmpro_stripe_dont_require_CVV");
			}
		}

		/**
		 * Filtering orders at checkout.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_order($morder)
		{
			//load up token values
			if(isset($_REQUEST['stripeToken']))
			{
				$morder->stripeToken = $_REQUEST['stripeToken'];
			}

			//stripe lite code to get name from other sources if available
			global $pmpro_stripe_lite, $current_user;
			if(!empty($pmpro_stripe_lite) && empty($morder->FirstName) && empty($morder->LastName))
			{
				if(!empty($current_user->ID))
				{
					$morder->FirstName = get_user_meta($current_user->ID, "first_name", true);
					$morder->LastName = get_user_meta($current_user->ID, "last_name", true);
				}
				elseif(!empty($_REQUEST['first_name']) && !empty($_REQUEST['last_name']))
				{
					$morder->FirstName = $_REQUEST['first_name'];
					$morder->LastName = $_REQUEST['last_name'];
				}
			}

			return $morder;
		}

		/**
		 * Code to run after checkout
		 *
		 * @since 1.8
		 */
		static function pmpro_after_checkout($user_id, $morder)
		{
			global $gateway;

			if($gateway == "stripe")
			{
				if(!empty($morder) && !empty($morer->Gateway) && !empty($morder->Gateway->customer) && !empty($morder->Gateway->customer->id))
				{
					update_user_meta($user_id, "pmpro_stripe_customerid", $morder->Gateway->customer->id);
				}
			}
		}

		/**
		 * Check settings if billing address should be shown.
		 * @since 1.8
		 */
		static function pmpro_include_billing_address_fields($include)
		{
			//check settings RE showing billing address
			if(!pmpro_getOption("stripe_billingaddress"))
				$include = false;

			return $include;
		}

		/**
		 * Use our own payment fields at checkout. (Remove the name attributes.)		
		 * @since 1.8
		 */
		static function pmpro_include_payment_information_fields($include)
		{
			//global vars
			global $pmpro_requirebilling, $pmpro_show_discount_code, $discount_code, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
			
			//get accepted credit cards
			$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
			$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
			$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);

			//include ours
			?>
			<table id="pmpro_payment_information_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0" <?php if(!$pmpro_requirebilling || apply_filters("pmpro_hide_payment_information_fields", false) ) { ?>style="display: none;"<?php } ?>>
			<thead>
				<tr>
					<th><span class="pmpro_thead-msg"><?php printf(__('We Accept %s', 'pmpro'), $pmpro_accepted_credit_cards_string);?></span><?php _e('Payment Information', 'pmpro');?></th>
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
								<label for="CardType"><?php _e('Card Type', 'pmpro');?></label>
								<select id="CardType" class=" <?php echo pmpro_getClassForField("CardType");?>">
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
								jQuery(document).ready(function() {
										jQuery('#AccountNumber').validateCreditCard(function(result) {
											var cardtypenames = {
												"amex":"American Express",
												"diners_club_carte_blanche":"Diners Club Carte Blanche",
												"diners_club_international":"Diners Club International",
												"discover":"Discover",
												"jcb":"JCB",
												"laser":"Laser",
												"maestro":"Maestro",
												"mastercard":"Mastercard",
												"visa":"Visa",
												"visa_electron":"Visa Electron"
											}

											if(result.card_type)
												jQuery('#CardType').val(cardtypenames[result.card_type.name]);
											else
												jQuery('#CardType').val('Unknown Card Type');
										});
								});
							</script>
							<?php
							}
						?>

						<div class="pmpro_payment-account-number">
							<label for="AccountNumber"><?php _e('Card Number', 'pmpro');?></label>
							<input id="AccountNumber" class="input <?php echo pmpro_getClassForField("AccountNumber");?>" type="text" size="25" value="<?php echo esc_attr($AccountNumber)?>" autocomplete="off" />
						</div>

						<div class="pmpro_payment-expiration">
							<label for="ExpirationMonth"><?php _e('Expiration Date', 'pmpro');?></label>
							<select id="ExpirationMonth" class=" <?php echo pmpro_getClassForField("ExpirationMonth");?>">
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
							</select>/<select id="ExpirationYear" class=" <?php echo pmpro_getClassForField("ExpirationYear");?>">
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

						<?php
							$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
							if($pmpro_show_cvv)
							{
						?>
						<div class="pmpro_payment-cvv">
							<label for="CVV"><?php _ex('CVV', 'Credit card security code, CVV/CCV/CVV2', 'pmpro');?></label>
							<input class="input" id="CVV" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr($_REQUEST['CVV']); }?>" class=" <?php echo pmpro_getClassForField("CVV");?>" />  <small>(<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo pmpro_https_filter(PMPRO_URL)?>/pages/popup-cvv.html','cvv','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=600, height=475');"><?php _ex("what's this?", 'link to CVV help', 'pmpro');?></a>)</small>
						</div>
						<?php
							}
						?>

						<?php if($pmpro_show_discount_code) { ?>
						<div class="pmpro_payment-discount-code">
							<label for="discount_code"><?php _e('Discount Code', 'pmpro');?></label>
							<input class="input <?php echo pmpro_getClassForField("discount_code");?>" id="discount_code" name="discount_code" type="text" size="20" value="<?php echo esc_attr($discount_code)?>" />
							<input type="button" id="discount_code_button" name="discount_code_button" value="<?php _e('Apply', 'pmpro');?>" />
							<p id="discount_code_message" class="pmpro_message" style="display: none;"></p>
						</div>
						<?php } ?>

					</td>
				</tr>
			</tbody>
			</table>
			<?php

			//don't include the default
			return false;
		}

		/**
		 * Fields shown on edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields($user)
		{
			global $wpdb, $current_user, $pmpro_currency_symbol;

			$cycles = array( __('Day(s)', 'pmpro') => 'Day', __('Week(s)', 'pmpro') => 'Week', __('Month(s)', 'pmpro') => 'Month', __('Year(s)', 'pmpro') => 'Year' );
			$current_year = date("Y");
			$current_month = date("m");

			//make sure the current user has privileges
			$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
			if(!current_user_can($membership_level_capability))
				return false;

			//more privelges they should have
			$show_membership_level = apply_filters("pmpro_profile_show_membership_level", true, $user);
			if(!$show_membership_level)
				return false;

			//check that user has a current subscription at Stripe
			$last_order = new MemberOrder();
			$last_order->getLastMemberOrder($user->ID);

			//assume no sub to start
			$sub = false;

			//check that gateway is Stripe
			if($last_order->gateway == "stripe")
			{
				//is there a customer?
				$sub = $last_order->Gateway->getSubscription($last_order);
			}

			$customer_id = $user->pmpro_stripe_customerid;

			if(empty($sub))
			{
				//make sure we delete stripe updates
				update_user_meta($user->ID, "pmpro_stripe_updates", array());

				//if the last order has a sub id, let the admin know there is no sub at Stripe
				if(!empty($last_order) && $last_order->gateway == "stripe" && !empty($last_order->subscription_transaction_id) && strpos($last_order->subscription_transaction_id, "sub_") !== false)
				{
				?>
				<p><strong>Note:</strong> Subscription <strong><?php echo $last_order->subscription_transaction_id;?></strong> could not be found at Stripe. It might have been deleted.</p>
				<?php
				}
			}
			else
			{
			?>
			<h3><?php _e("Subscription Updates", "pmpro"); ?></h3>
			<p>
				<?php
					if(empty($_REQUEST['user_id']))
						_e("Subscription updates, allow you to change the member's subscription values at predefined times. Be sure to click Update Profile after making changes.", 'pmpro');
					else
						_e("Subscription updates, allow you to change the member's subscription values at predefined times. Be sure to click Update User after making changes.", 'pmpro');
				?>
			</p>
			<table class="form-table">
				<tr>
					<th><label for="membership_level"><?php _e("Update", "pmpro"); ?></label></th>
					<td id="updates_td">
						<?php
							$old_updates = $user->pmpro_stripe_updates;
							if(is_array($old_updates))
							{
								$updates = array_merge(
									array(array('template'=>true, 'when'=>'now', 'date_month'=>'', 'date_day'=>'', 'date_year'=>'', 'billing_amount'=>'', 'cycle_number'=>'', 'cycle_period'=>'Month')),
									$old_updates
								);
							}
							else
								$updates = array(array('template'=>true, 'when'=>'now', 'date_month'=>'', 'date_day'=>'', 'date_year'=>'', 'billing_amount'=>'', 'cycle_number'=>'', 'cycle_period'=>'Month'));

							foreach($updates as $update)
							{
							?>
							<div class="updates_update" <?php if(!empty($update['template'])) { ?>style="display: none;"<?php } ?>>
								<select class="updates_when" name="updates_when[]">
									<option value="now" <?php selected($update['when'], "now");?>>Now</option>
									<option value="payment" <?php selected($update['when'], "payment");?>>After Next Payment</option>
									<option value="date" <?php selected($update['when'], "date");?>>On Date</option>
								</select>
								<span class="updates_date" <?php if($uwhen != "date") { ?>style="display: none;"<?php } ?>>
									<select name="updates_date_month[]">
										<?php
											for($i = 1; $i < 13; $i++)
											{
											?>
											<option value="<?php echo str_pad($i, 2, "0", STR_PAD_LEFT);?>" <?php if(!empty($update['date_month']) && $update['date_month'] == $i) { ?>selected="selected"<?php } ?>>
												<?php echo date("M", strtotime($i . "/1/" . $current_year));?>
											</option>
											<?php
											}
										?>
									</select>
									<input name="updates_date_day[]" type="text" size="2" value="<?php if(!empty($update['date_day'])) echo esc_attr($update['date_day']);?>" />
									<input name="updates_date_year[]" type="text" size="4" value="<?php if(!empty($update['date_year'])) echo esc_attr($update['date_year']);?>" />
								</span>
								<span class="updates_billing" <?php if($uwhen == "no") { ?>style="display: none;"<?php } ?>>
									<?php echo $pmpro_currency_symbol?><input name="updates_billing_amount[]" type="text" size="10" value="<?php echo esc_attr($update['billing_amount']);?>" />
									<small><?php _e('per', 'pmpro');?></small>
									<input name="updates_cycle_number[]" type="text" size="5" value="<?php echo esc_attr($update['cycle_number']);?>" />
									<select name="updates_cycle_period[]">
									  <?php
										foreach ( $cycles as $name => $value ) {
										  echo "<option value='$value'";
										  if(!empty($update['cycle_period']) && $update['cycle_period'] == $value) echo " selected='selected'";
										  echo ">$name</option>";
										}
									  ?>
									</select>
								</span>
								<span>
									<a class="updates_remove" href="javascript:void(0);">Remove</a>
								</span>
							</div>
							<?php
							}
							?>
						<p><a id="updates_new_update" href="javascript:void(0);">+ New Update</a></p>
					</td>
				</tr>
			</table>
			<script>
				jQuery(document).ready(function() {
					//function to update dropdowns/etc based on when field
					function updateSubscriptionUpdateFields(when)
					{
						if(jQuery(when).val() == 'date')
							jQuery(when).parent().children('.updates_date').show();
						else
							jQuery(when).parent().children('.updates_date').hide();

						if(jQuery(when).val() == 'no')
							jQuery(when).parent().children('.updates_billing').hide();
						else
							jQuery(when).parent().children('.updates_billing').show();
					}

					//and update on page load
					jQuery('.updates_when').each(function() { if(jQuery(this).parent().css('display') != 'none') updateSubscriptionUpdateFields(this); });

					//add a new update when clicking to
					var num_updates_divs = <?php echo count($updates);?>;
					jQuery('#updates_new_update').click(function() {
						//get updates
						updates = jQuery('.updates_update').toArray();

						//clone the first one
						new_div = jQuery(updates[0]).clone();

						//append
						new_div.insertBefore('#updates_new_update');

						//update events
						addUpdateEvents()

						//unhide it
						new_div.show();
						updateSubscriptionUpdateFields(new_div.children('.updates_when'));
					});

					function addUpdateEvents()
					{
						//update when when changes
						jQuery('.updates_when').change(function() {
							updateSubscriptionUpdateFields(this);
						});

						//remove updates when clicking
						jQuery('.updates_remove').click(function() {
							jQuery(this).parent().parent().remove();
						});
					}
					addUpdateEvents();
				});
			</script>
			<?php
			}
		}

		/**
		 * Process fields from the edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields_save($user_id)
		{
			global $wpdb;

			//check capabilities
			$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
			if(!current_user_can($membership_level_capability))
				return false;

			//make sure some value was passed
			if(!isset($_POST['updates_when']) || !is_array($_POST['updates_when']))
				return;

			//vars
			$updates = array();
			$next_on_date_update = "";

			//build array of updates (we skip the first because it's the template field for the JavaScript
			for($i = 1; $i < count($_POST['updates_when']); $i++)
			{
				$update = array();

				//all updates have these values
				$update['when'] = $_POST['updates_when'][$i];
				$update['billing_amount'] = $_POST['updates_billing_amount'][$i];
				$update['cycle_number'] = $_POST['updates_cycle_number'][$i];
				$update['cycle_period'] = $_POST['updates_cycle_period'][$i];

				//these values only for on date updates
				if($_POST['updates_when'][$i] == "date")
				{
					$update['date_month'] = str_pad($_POST['updates_date_month'][$i], 2, "0", STR_PAD_LEFT);
					$update['date_day'] = str_pad($_POST['updates_date_day'][$i], 2, "0", STR_PAD_LEFT);
					$update['date_year'] = $_POST['updates_date_year'][$i];
				}

				//make sure the update is valid
				if(empty($update['cycle_number']))
					continue;

				//if when is now, update the subscription
				if($update['when'] == "now")
				{
					//get level for user
					$user_level = pmpro_getMembershipLevelForUser($user_id);

					//get current plan at Stripe to get payment date
					$last_order = new MemberOrder();
					$last_order->getLastMemberOrder($user_id);
					$last_order->setGateway('stripe');
					$last_order->Gateway->getCustomer($last_order);

					$subscription = $last_order->Gateway->getSubscription($last_order);

					if(!empty($subscription))
					{
						$end_timestamp = $subscription->current_period_end;

						//cancel the old subscription
						if(!$last_order->Gateway->cancelSubscriptionAtGateway($subscription))
						{
							//throw error and halt save
							function pmpro_stripe_user_profile_fields_save_error($errors, $update, $user)
							{
								$errors->add('pmpro_stripe_updates',__('Could not cancel the old subscription. Updates have not been processed.', 'pmpro'));
							}
							add_filter('user_profile_update_errors', 'pmpro_stripe_user_profile_fields_save_error', 10, 3);

							//stop processing updates
							return;
						}
					}

					//if we didn't get an end date, let's set one one cycle out
					if(empty($end_timestamp))
						$end_timestamp = strtotime("+" . $update['cycle_number'] . " " . $update['cycle_period'], current_time('timestamp'));

					//build order object
					$update_order = new MemberOrder();
					$update_order->setGateway('stripe');
					$update_order->user_id = $user_id;
					$update_order->membership_id = $user_level->id;
					$update_order->membership_name = $user_level->name;
					$update_order->InitialPayment = 0;
					$update_order->PaymentAmount = $update['billing_amount'];
					$update_order->ProfileStartDate = date("Y-m-d", $end_timestamp);
					$update_order->BillingPeriod = $update['cycle_period'];
					$update_order->BillingFrequency = $update['cycle_number'];

					//need filter to reset ProfileStartDate
					add_filter('pmpro_profile_start_date', create_function('$startdate, $order', 'return "' . $update_order->ProfileStartDate . 'T0:0:0";'), 10, 2);

					//update subscription
					$update_order->Gateway->subscribe($update_order, false);

					//update membership
					$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users
									SET billing_amount = '" . esc_sql($update['billing_amount']) . "',
										cycle_number = '" . esc_sql($update['cycle_number']) . "',
										cycle_period = '" . esc_sql($update['cycle_period']) . "',
										trial_amount = '',
										trial_limit = ''
									WHERE user_id = '" . esc_sql($user_id) . "'
										AND membership_id = '" . esc_sql($last_order->membership_id) . "'
										AND status = 'active'
									LIMIT 1";

					$wpdb->query($sqlQuery);

					//save order so we know which plan to look for at stripe (order code = plan id)
					$update_order->status = "success";
					$update_order->saveOrder();

					continue;
				}
				elseif($update['when'] == 'date')
				{
					if(!empty($next_on_date_update))
						$next_on_date_update = min($next_on_date_update, $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day']);
					else
						$next_on_date_update = $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'];
				}

				//add to array
				$updates[] = $update;
			}

			//save in user meta
			update_user_meta($user_id, "pmpro_stripe_updates", $updates);

			//save date of next on-date update to make it easier to query for these in cron job
			update_user_meta($user_id, "pmpro_stripe_next_on_date_update", $next_on_date_update);
		}

		/**
		 * Cron activation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_activation()
		{
			wp_schedule_event(time(), 'daily', 'pmpro_cron_stripe_subscription_updates');
		}

		/**
		 * Cron deactivation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_deactivation()
		{
			wp_clear_scheduled_hook('pmpro_cron_stripe_subscription_updates');
		}

		/**
		 * Cron job for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_cron_stripe_subscription_updates()
		{
			global $wpdb;

			//get all updates for today (or before today)
			$sqlQuery = "SELECT *
						 FROM $wpdb->usermeta
						 WHERE meta_key = 'pmpro_stripe_next_on_date_update'
							AND meta_value IS NOT NULL
							AND meta_value <> ''
							AND meta_value < '" . date("Y-m-d", strtotime("+1 day")) . "'";
			$updates = $wpdb->get_results($sqlQuery);
			
			if(!empty($updates))
			{
				//loop through
				foreach($updates as $update)
				{
					//pull values from update
					$user_id = $update->user_id;

					$user = get_userdata($user_id);
					
					//if user is missing, delete the update info and continue
					if(empty($user) || empty($user->ID))
					{						
						delete_user_meta($user_id, "pmpro_stripe_updates");
						delete_user_meta($user_id, "pmpro_stripe_next_on_date_update");
					
						continue;
					}
					
					$user_updates = $user->pmpro_stripe_updates;
					$next_on_date_update = "";					
					
					//loop through updates looking for updates happening today or earlier
					if(!empty($user_updates))
					{
						foreach($user_updates as $key => $update)
						{
							if($update['when'] == 'date' &&
								$update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'] <= date("Y-m-d")
							)
							{
								//get level for user
								$user_level = pmpro_getMembershipLevelForUser($user_id);

								//get current plan at Stripe to get payment date
								$last_order = new MemberOrder();
								$last_order->getLastMemberOrder($user_id);
								$last_order->setGateway('stripe');
								$last_order->Gateway->getCustomer($last_order);

								if(!empty($last_order->Gateway->customer))
								{
									//find the first subscription
									if(!empty($last_order->Gateway->customer->subscriptions['data'][0]))
									{
										$first_sub = $last_order->Gateway->customer->subscriptions['data'][0]->__toArray();
										$end_timestamp = $first_sub['current_period_end'];
									}
								}

								//if we didn't get an end date, let's set one one cycle out
								$end_timestamp = strtotime("+" . $update['cycle_number'] . " " . $update['cycle_period']);

								//build order object
								$update_order = new MemberOrder();
								$update_order->setGateway('stripe');
								$update_order->user_id = $user_id;
								$update_order->membership_id = $user_level->id;
								$update_order->membership_name = $user_level->name;
								$update_order->InitialPayment = 0;
								$update_order->PaymentAmount = $update['billing_amount'];
								$update_order->ProfileStartDate = date("Y-m-d", $end_timestamp);
								$update_order->BillingPeriod = $update['cycle_period'];
								$update_order->BillingFrequency = $update['cycle_number'];

								//update subscription
								$update_order->Gateway->subscribe($update_order, false);

								//update membership
								$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users
												SET billing_amount = '" . esc_sql($update['billing_amount']) . "',
													cycle_number = '" . esc_sql($update['cycle_number']) . "',
													cycle_period = '" . esc_sql($update['cycle_period']) . "'
												WHERE user_id = '" . esc_sql($user_id) . "'
													AND membership_id = '" . esc_sql($last_order->membership_id) . "'
													AND status = 'active'
												LIMIT 1";

								$wpdb->query($sqlQuery);

								//save order
								$update_order->status = "success";
								$update_order->save();

								//remove update from list
								unset($user_updates[$key]);
							}
							elseif($update['when'] == 'date')
							{
								//this is an on date update for the future, update the next on date update
								if(!empty($next_on_date_update))
									$next_on_date_update = min($next_on_date_update, $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day']);
								else
									$next_on_date_update = $update['date_year'] . "-" . $update['date_month'] . "-" . $update['date_day'];
							}
						}
					}

					//save updates in case we removed some
					update_user_meta($user_id, "pmpro_stripe_updates", $user_updates);

					//save date of next on-date update to make it easier to query for these in cron job
					update_user_meta($user_id, "pmpro_stripe_next_on_date_update", $next_on_date_update);
				}
			}
		}

		/**
		 * Process checkout and decide if a charge and or subscribe is needed
		 *
		 * @since 1.4
		 */
		function process(&$order)
		{
			//check for initial payment
			if(floatval($order->InitialPayment) == 0)
			{
				//just subscribe
				return $this->subscribe($order);
			}
			else
			{
				//charge then subscribe
				if($this->charge($order))
				{
					if(pmpro_isLevelRecurring($order->membership_level))
					{
						if($this->subscribe($order))
						{
							//yay!
							return true;
						}
						else
						{
							//try to refund initial charge
							return false;
						}
					}
					else
					{
						//only a one time charge
						$order->status = "success";	//saved on checkout page
						return true;
					}
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Initial payment failed.", "pmpro");
					return false;
				}
			}
		}

		/**
		 * Make a one-time charge with Stripe
		 *
		 * @since 1.4
		 */
		function charge(&$order)
		{
			global $pmpro_currency;

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//what amount to charge?
			$amount = $order->InitialPayment;

			//tax
			$order->subtotal = $amount;
			$tax = $order->getTax(true);
			$amount = round((float)$order->subtotal + (float)$tax, 2);

			//create a customer
			$result = $this->getCustomer($order);

			if(empty($result))
			{
				//failed to create customer
				return false;
			}

			//charge
			try
			{
				$response = Stripe_Charge::create(array(
				  "amount" => $amount * 100, # amount in cents, again
				  "currency" => strtolower($pmpro_currency),
				  "customer" => $this->customer->id,
				  "description" => "Order #" . $order->code . ", " . trim($order->FirstName . " " . $order->LastName) . " (" . $order->Email . ")"
				  )
				);
			}
			catch (Exception $e)
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = "Error: " . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}

			if(empty($response["failure_message"]))
			{
				//successful charge
				$order->payment_transaction_id = $response["id"];
				$order->updateStatus("success");
				$order->saveOrder();
				return true;
			}
			else
			{
				//$order->status = "error";
				$order->errorcode = true;
				$order->error = $response['failure_message'];
				$order->shorterror = $response['failure_message'];
				return false;
			}
		}

		/**
		 * Get a Stripe customer object.
		 *
		 * If $this->customer is set, it returns it.
		 * It first checks if the order has a subscription_transaction_id. If so, that's the customer id.
		 * If not, it checks for a user_id on the order and searches for a customer id in the user meta.
		 * If a customer id is found, it checks for a customer through the Stripe API.
		 * If a customer is found and there is a stripeToken on the order passed, it will update the customer.
		 * If no customer is found and there is a stripeToken on the order passed, it will create a customer.
		 *
		 * @since 1.4
		 * @return Stripe_Customer|false
		 */
		function getCustomer(&$order = false, $force = false)
		{
			global $current_user;

			//already have it?
			if(!empty($this->customer) && !$force)
				return $this->customer;

			//figure out user_id and user
			if(!empty($order->user_id))
				$user_id = $order->user_id;

			//if no id passed, check the current user
			if(empty($user_id) && !empty($current_user->ID))
				$user_id = $current_user->ID;

			if(!empty($user_id))
				$user = get_userdata($user_id);
			else
				$user = NULL;

			//transaction id?
			if(!empty($order->subscription_transaction_id) && strpos($order->subscription_transaction_id, "cus_") !== false)
				$customer_id = $order->subscription_transaction_id;
			else
			{
				//try based on user id
				if(!empty($user_id))
				{
					$customer_id = get_user_meta($user_id, "pmpro_stripe_customerid", true);
				}
			}

			//get name and email values from order in case we update
			$name = trim($order->FirstName . " " . $order->LastName);
			if(empty($name) && !empty($user->ID))
			{
				$name = trim($user->first_name . " " . $user->last_name);

				//still empty?
				if(empty($name))
					$name = $user->user_login;
			}
			elseif(empty($name))
				$name = "No Name";

			$email = $order->Email;
			if(empty($email) && !empty($user->ID))
			{
				$email = $user->user_email;
			}
			elseif(empty($email))
				$email = "No Email";

			//check for an existing stripe customer
			if(!empty($customer_id))
			{
				try
				{
					$this->customer = Stripe_Customer::retrieve($customer_id);

					//update the customer description and card
					if(!empty($order->stripeToken))
					{
						$this->customer->description = $name . " (" . $email . ")";
						$this->customer->email = $email;
						$this->customer->card = $order->stripeToken;
						$this->customer->save();
					}

					return $this->customer;
				}
				catch (Exception $e)
				{
					//assume no customer found
				}
			}

			//no customer id, create one
			if(!empty($order->stripeToken))
			{
				try
				{
					$this->customer = Stripe_Customer::create(array(
							  "description" => $name . " (" . $email . ")",
							  "email" => $order->Email,
							  "card" => $order->stripeToken
							));
				}
				catch (Exception $e)
				{
					$order->error = __("Error creating customer record with Stripe:", "pmpro") . " " . $e->getMessage();
					$order->shorterror = $order->error;
					return false;
				}

				if(!empty($user_id))
				{
					//user logged in/etc
					update_user_meta($user_id, "pmpro_stripe_customerid", $this->customer->id);
				}
				else
				{
					//user not registered yet, queue it up
					global $pmpro_stripe_customer_id;
					$pmpro_stripe_customer_id = $this->customer->id;
					function pmpro_user_register_stripe_customerid($user_id)
					{
						global $pmpro_stripe_customer_id;
						update_user_meta($user_id, "pmpro_stripe_customerid", $pmpro_stripe_customer_id);
					}
					add_action("user_register", "pmpro_user_register_stripe_customerid");
				}

                return apply_filters('pmpro_stripe_create_customer', $this->customer);
			}

			return false;
		}

		/**
		 * Get a Stripe subscription from a PMPro order
		 *
		 * @since 1.8
		 */
		function getSubscription(&$order)
		{
			global $wpdb;

			//no order?
			if(empty($order) || empty($order->code))
				return false;

			$result = $this->getCustomer($order, true);	//force so we don't get a cached sub for someone else

			//no customer?
			if(empty($result))
				return false;

			//is there a subscription transaction id pointing to a sub?
			if(!empty($order->subscription_transaction_id) && strpos($order->subscription_transaction_id, "sub_") !== false)
			{
				try
				{
					$sub = $this->customer->subscriptions->retrieve($order->subscription_transaction_id);
				}
				catch (Exception $e)
				{
					$order->error = __("Error creating plan with Stripe:", "pmpro") . $e->getMessage();
					$order->shorterror = $order->error;
					return false;
				}

				return $sub;
			}
			
			//no subscriptions object in customer
			if(empty($this->customer->subscriptions))
				return false;

			//find subscription based on customer id and order/plan id
			$subscriptions = $this->customer->subscriptions->all();

			//no subscriptions
			if(empty($subscriptions) || empty($subscriptions->data))
				return false;

			//we really want to test against the order codes of all orders with the same subscription_transaction_id (customer id)
			$codes = $wpdb->get_col("SELECT code FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $order->user_id . "' AND subscription_transaction_id = '" . $order->subscription_transaction_id . "' AND status NOT IN('refunded', 'review', 'token', 'error')");

			//find the one for this order
			foreach($subscriptions->data as $sub)
			{
				if(in_array($sub->plan->id, $codes))
				{
					return $sub;
				}
			}

			//didn't find anything yet
			return false;
		}

		/**
		 * Create a new subscription with Stripe
		 *
		 * @since 1.4
		 */
		function subscribe(&$order, $checkout = true)
		{
			global $pmpro_currency;

			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();

			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);

			//figure out the user
			if(!empty($order->user_id))
				$user_id = $order->user_id;
			else
			{
				global $current_user;
				$user_id = $current_user->ID;
			}

			//set up customer
			$result = $this->getCustomer($order);
			if(empty($result))
				return false;	//error retrieving customer

			//set subscription id to custom id
			$order->subscription_transaction_id = $this->customer['id'];	//transaction id is the customer id, we save it in user meta later too

			//figure out the amounts
			$amount = $order->PaymentAmount;
			$amount_tax = $order->getTaxForPrice($amount);
			$amount = round((float)$amount + (float)$amount_tax, 2);

			/*
				There are two parts to the trial. Part 1 is simply the delay until the first payment
				since we are doing the first payment as a separate transaction.
				The second part is the actual "trial" set by the admin.

				Stripe only supports Year or Month for billing periods, but we account for Days and Weeks just in case.
			*/
			//figure out the trial length (first payment handled by initial charge)
			if($order->BillingPeriod == "Year")
				$trial_period_days = $order->BillingFrequency * 365;	//annual
			elseif($order->BillingPeriod == "Day")
				$trial_period_days = $order->BillingFrequency * 1;		//daily
			elseif($order->BillingPeriod == "Week")
				$trial_period_days = $order->BillingFrequency * 7;		//weekly
			else
				$trial_period_days = $order->BillingFrequency * 30;	//assume monthly

			//convert to a profile start date
			$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $trial_period_days . " Day", current_time("timestamp"))) . "T0:0:0";

			//filter the start date
			$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);

			//convert back to days
			$trial_period_days = ceil(abs(strtotime(date("Y-m-d"), current_time("timestamp")) - strtotime($order->ProfileStartDate, current_time("timestamp"))) / 86400);

			//for free trials, just push the start date of the subscription back
			if(!empty($order->TrialBillingCycles) && $order->TrialAmount == 0)
			{
				$trialOccurrences = (int)$order->TrialBillingCycles;
				if($order->BillingPeriod == "Year")
					$trial_period_days = $trial_period_days + (365 * $order->BillingFrequency * $trialOccurrences);	//annual
				elseif($order->BillingPeriod == "Day")
					$trial_period_days = $trial_period_days + (1 * $order->BillingFrequency * $trialOccurrences);		//daily
				elseif($order->BillingPeriod == "Week")
					$trial_period_days = $trial_period_days + (7 * $order->BillingFrequency * $trialOccurrences);	//weekly
				else
					$trial_period_days = $trial_period_days + (30 * $order->BillingFrequency * $trialOccurrences);	//assume monthly
			}
			elseif(!empty($order->TrialBillingCycles))
			{
				/*
					Let's set the subscription to the trial and give the user an "update" to change the sub later to full price (since v2.0)

					This will force TrialBillingCycles > 1 to act as if they were 1
				*/
				$new_user_updates = array();
				$new_user_updates[] = array(
					'when' => 'payment',
					'billing_amount' => $order->PaymentAmount,
					'cycle_period' => $order->BillingPeriod,
					'cycle_number' => $order->BillingFrequency
				);

				//now amount to equal the trial #s
				$amount = $order->TrialAmount;
				$amount_tax = $order->getTaxForPrice($amount);
				$amount = round((float)$amount + (float)$amount_tax, 2);
			}

			//create a plan
			try
			{
                $plan = array(
                    "amount" => $amount * 100,
                    "interval_count" => $order->BillingFrequency,
                    "interval" => strtolower($order->BillingPeriod),
                    "trial_period_days" => $trial_period_days,
                    "name" => $order->membership_name . " for order " . $order->code,
                    "currency" => strtolower($pmpro_currency),
                    "id" => $order->code
                );

				$plan = Stripe_Plan::create(apply_filters('pmpro_stripe_create_plan_array', $plan));
			}
			catch (Exception $e)
			{
				$order->error = __("Error creating plan with Stripe:", "pmpro") . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}

			//before subscribing, let's clear out the updates so we don't trigger any during sub
			if(!empty($user_id))
			{
				$old_user_updates = get_user_meta($user_id, "pmpro_stripe_updates", true);
				update_user_meta($user_id, "pmpro_stripe_updates", array());
			}

			if(empty($order->subscription_transaction_id) && !empty($this->customer['id']))
				$order->subscription_transaction_id = $this->customer['id'];

			//subscribe to the plan
			try
			{
				$subscription = array("plan" => $order->code);
				$result = $this->customer->subscriptions->create(apply_filters('pmpro_stripe_create_subscription_array', $subscription));
			}
			catch (Exception $e)
			{
				//try to delete the plan
				$plan->delete();

				//give the user any old updates back
				if(!empty($user_id))
					update_user_meta($user_id, "pmpro_stripe_updates", $old_user_updates);

				//return error
				$order->error = __("Error subscribing customer to plan with Stripe:", "pmpro") . $e->getMessage();
				$order->shorterror = $order->error;
				return false;
			}

			//delete the plan
			$plan = Stripe_Plan::retrieve($order->code);
			$plan->delete();

			//if we got this far, we're all good
			$order->status = "success";
			$order->subscription_transaction_id = $result['id'];

			//save new updates if this is at checkout
			if($checkout)
			{
				//empty out updates unless set above
				if(empty($new_user_updates))
					$new_user_updates = array();

				//update user meta
				if(!empty($user_id))
					update_user_meta($user_id, "pmpro_stripe_updates", $new_user_updates);
				else
				{
					//need to remember the user updates to save later
					global $pmpro_stripe_updates;
					$pmpro_stripe_updates = $new_user_updates;
					function pmpro_user_register_stripe_updates($user_id)
					{
						global $pmpro_stripe_updates;
						update_user_meta($user_id, "pmpro_stripe_updates", $pmpro_stripe_updates);
					}
					add_action("user_register", "pmpro_user_register_stripe_updates");
				}
			}
			else
			{
				//give them their old updates back
				update_user_meta($user_id, "pmpro_stripe_updates", $old_user_updates);
			}

			return true;
		}

		/**
		 * Helper method to update the customer info via getCustomer
		 *
		 * @since 1.4
		 */
		function update(&$order)
		{
			//we just have to run getCustomer which will look for the customer and update it with the new token
			$result = $this->getCustomer($order);

			if(!empty($result))
			{
				return true;
			}
			else
			{
				return false;	//couldn't find the customer
			}
		}

		/**
		 * Cancel a subscription at Stripe
		 *
		 * @since 1.4
		 */
		function cancel(&$order, $update_status = true)
		{
			//no matter what happens below, we're going to cancel the order in our system
			if($update_status)
				$order->updateStatus("cancelled");

			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;

			//find the customer
			$result = $this->getCustomer($order);

			if(!empty($result))
			{
				//find subscription with this order code
				$subscription = $this->getSubscription($order);

				if(!empty($subscription))
				{
					if($this->cancelSubscriptionAtGateway($subscription))
					{
						//we're okay, going to return true later
					}
					else
					{
						$order->error = __("Could not cancel old subscription.", "pmpro");
						$order->shorterror = $order->error;

						return false;
					}
				}

				/*
					Clear updates for this user. (But not if checking out, we would have already done that.)
				*/
				if(empty($_REQUEST['submit-checkout']))
					update_user_meta($order->user_id, "pmpro_stripe_updates", array());

				return true;
			}
			else
			{
				$order->error = __("Could not find the customer.", "pmpro");
				$order->shorterror = $order->error;
				return false;	//no customer found
			}
		}

		/**
		 * Helper method to cancel a subscription at Stripe and also clear up any upaid invoices.
		 *
		 * @since 1.8
		 */
		function cancelSubscriptionAtGateway($subscription)
		{
			//need a valid sub
			if(empty($subscription->id))
				return false;

			//make sure we get the customer for this subscription
			$order = new MemberOrder();
			$order->getLastMemberOrderBySubscriptionTransactionID($subscription->id);

			//no order?
			if(empty($order))
			{
				//lets cancel anyway, but this is suspicious
				$r = $subscription->cancel();

				return true;
			}

			//okay have an order, so get customer so we can cancel invoices too
			$this->getCustomer($order);

			//get open invoices
			$invoices = $this->customer->invoices();
			$invoices = $invoices->all();

			//found it, cancel it
			try
			{
				//find any open invoices for this subscription and forgive them
				if(!empty($invoices))
				{
					foreach($invoices->data as $invoice)
					{
						if(!$invoice->closed && $invoice->subscription == $subscription->id)
						{
							$invoice->closed = true;
							$invoice->save();
						}
					}
				}

				//cancel
				$r = $subscription->cancel();

				return true;
			}
			catch(Exception $e)
			{
				return false;
			}
		}
	}