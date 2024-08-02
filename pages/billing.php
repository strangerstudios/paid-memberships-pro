<?php
/**
 * Template: Billing
 * Version: 3.1.2
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1.2
 *
 * @author Paid Memberships Pro
 */
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
<?php
	global $wpdb, $current_user, $gateway, $pmpro_msg, $pmpro_msgt, $show_check_payment_instructions, $show_paypal_link, $pmpro_billing_subscription, $pmpro_billing_level;
	global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bemail, $bconfirmemail, $bphone, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;

	/**
	 * Filter to set if PMPro uses email or text as the type for email field inputs.
	 *
	 * @since 1.8.4.5
	 *
	 * @param bool $use_email_type, true to use email type, false to use text type
	 */
	$pmpro_email_field_type = apply_filters('pmpro_email_field_type', true);

	// Get the default gateway for the site.
	$default_gateway = get_option( 'pmpro_gateway' );

	// Set the wrapping class for the checkout div based on the default gateway;
	if ( empty( $gateway ) ) {
		$pmpro_billing_gateway_class = 'pmpro_billing_gateway-none';
	} else {
		$pmpro_billing_gateway_class = 'pmpro_billing_gateway-' . $gateway;
	}

	//Make sure the $pmpro_billing_level object is a valid level definition
	if ( ! empty( $pmpro_billing_subscription ) ) {
		$checkout_url = pmpro_url( 'checkout', '?pmpro_level=' . $pmpro_billing_level->id );
		$logout_url = wp_logout_url( $checkout_url );

		?>
		<section id="pmpro_billing-<?php echo esc_attr( $pmpro_billing_level->ID ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section' ) ); ?>">
			<?php
			/**
			 * pmpro_billing_message_top hook to add in general content to the billing page without using custom page templates.
			 *
			 * @since 1.9.2
			 */
			do_action('pmpro_billing_message_top'); ?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">

				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title 	pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Account Information', 'paid-memberships-pro' ); ?></h2>

				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<?php
						$allowed_html = array(
							'a' => array(
								'href' => array(),
								'title' => array(),
								'target' => array(),
							),
							'strong' => array(),
						);
						echo '<p>' . wp_kses( sprintf( __('You are logged in as <strong>%s</strong>. If you would like to update your billing information for a different account, <a href="%s">log out now</a>.', 'paid-memberships-pro' ), $current_user->user_login, wp_logout_url( esc_url_raw( $_SERVER['REQUEST_URI'] ) ) ), $allowed_html ) . '</p>';
					?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
					<?php
						// Check if the gateway for this subscription updates a single subscription at once or all subscriptions at once.
						$subscription_gateway_obj = $pmpro_billing_subscription->get_gateway_object();
						if ( 'individual' === $subscription_gateway_obj->supports( 'payment_method_updates' ) ) {
							// Show the cost text for the subscription.
							?>
							<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain pmpro_list-with-labels pmpro_cols-2' ) ); ?>">
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e('Level', 'paid-memberships-pro' );?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo esc_html( $pmpro_billing_level->name ); ?></span>
								</li>
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Subscription', 'paid-memberships-pro' ); ?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo esc_html( $pmpro_billing_subscription->get_cost_text() ); ?></span>
								</li>
								<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_label' ) ); ?>"><?php esc_html_e( 'Next payment on', 'paid-memberships-pro' ); ?></span>
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item_value' ) ); ?>"><?php echo esc_html( $pmpro_billing_subscription->get_next_payment_date( get_option( 'date_format' ) ) ); ?></span>
								</li>
							</ul> <!-- end pmpro_list -->
							<?php
						} elseif ( 'all' === $subscription_gateway_obj->supports( 'payment_method_updates' ) ) {
							// This is a bit trickier. We need to get all subscriptions that will be updated, which should be all subscriptions for this user
							// that have the same gateway.
							$user_subscriptions = PMPro_Subscription::get_subscriptions_for_user();
							?>
							<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ); ?>">
								<?php foreach ( $user_subscriptions as $user_subscription ) {
									// Skip this subscription if the gateway doesn't match $pmpro_billing_subscription.
									if ( $user_subscription->get_gateway() !== $pmpro_billing_subscription->get_gateway() ) {
										continue;
									}

									// Get the level name for this subscription.
									$level = new PMPro_Membership_Level( $user_subscription->get_membership_level_id() );
									if ( empty( $level ) || empty( $level->name ) ) {
										continue;
									}

									// Show the level name and the cost text for the subscription.
									?>
										<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>"><strong><?php echo esc_html( $level->name ); ?>:</strong> <?php echo esc_html( $user_subscription->get_cost_text() ); ?></li>
										<?php
									}
								?>
							</ul> <!-- end pmpro_list -->
							<?php
						}
					?>
					<?php if ( has_action( 'pmpro_billing_bullets_top' ) || has_action( 'pmpro_billing_bullets_bottom' ) ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
						<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list pmpro_list-plain' ) ); ?>">
							<?php
								/**
								 * pmpro_billing_bullets_top hook allows you to add information to the billing list (at the top).
								 *
								 * @since 1.9.2
								 * @param {objects} {$pmpro_billing_level} {Passes the $pmpro_billing_level object}
								 */
								do_action('pmpro_billing_bullets_top', $pmpro_billing_level);

								/**
								 * pmpro_billing_bullets_bottom hook allows you to add information to the billing list (at the bottom).
								 *
								 * @since 1.9.2
								 * @param {objects} {$pmpro_billing_level} {Passes the $pmpro_billing_level object}
								 */
								do_action('pmpro_billing_bullets_bottom', $pmpro_billing_level); 
							?>
						</ul>
					<?php } ?>
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->

		<?php
		if ( $show_check_payment_instructions ) { ?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
					<?php esc_html_e( 'Payment Instructions', 'paid-memberships-pro' ); ?>
				</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_payment_instructions' ) ); ?>">
						<?php echo wp_kses_post( wpautop( wp_unslash( get_option( 'pmpro_instructions' ) ) ) ); ?>
					</div>
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
		<?php } elseif ( $show_paypal_link ) { ?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
					<?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?>
				</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<p><?php echo wp_kses( __('Your payment subscription is managed by PayPal. Please <a href="https://www.paypal.com">login to PayPal here</a> to update your billing information.', 'paid-memberships-pro' ), array( 'a' => array( 'href' => array() ) ) );?></p>
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
		<?php } elseif ( $gateway != $default_gateway ) {
			// This membership's gateway is not the default site gateway, Pay by Check, or PayPal Express.
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
					<?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?>
				</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<p><?php esc_html_e( 'Your billing information cannot be updated at this time.', 'paid-memberships-pro' ); ?></p>
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
		<?php } else {
			// Show the default gateway form and allow billing information update.
			?>
			<div id="pmpro_level-<?php echo intval( $pmpro_billing_level->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( $pmpro_billing_gateway_class, 'pmpro_level-' . $pmpro_billing_level->id ) ); ?>">
				<form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form pmpro_card' ) ); ?>" action="<?php echo esc_url( pmpro_url( "billing", "", "https") ) ?>" method="post">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<input type="hidden" name="pmpro_subscription_id" value="<?php echo empty( $pmpro_billing_subscription->get_id() ) ? '' : (int) $pmpro_billing_subscription->get_id(); ?>" />
						<input type="hidden" name="pmpro_level" value="<?php echo esc_attr($pmpro_billing_level->id);?>" />
						<div id="pmpro_message" <?php if(! $pmpro_msg) { ?> style="display:none" <?php } ?> class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
							<?php if($pmpro_msg) { echo wp_kses_post( $pmpro_msg ); } ?>
						</div>

						<?php
							$pmpro_include_billing_address_fields = apply_filters('pmpro_include_billing_address_fields', true);
							if($pmpro_include_billing_address_fields )
							{
						?>
						<fieldset id="pmpro_billing_address_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_billing_address_fields' ) ); ?>">
							<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
								<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e('Billing Address', 'paid-memberships-pro' ); ?></h2>
							</legend>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields pmpro_cols-2' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bfirstname', 'pmpro_form_field-bfirstname' ) ); ?>">
									<label for="bfirstname" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('First Name', 'paid-memberships-pro' );?></label>
									<input id="bfirstname" name="bfirstname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bfirstname' ) ); ?>" value="<?php echo esc_attr($bfirstname);?>" autocomplete="given-name" />
								</div> <!-- end pmpro_form_field-bfirstname -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-blastname', 'pmpro_form_field-blastname' ) ); ?>">
									<label for="blastname" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Last Name', 'paid-memberships-pro' );?></label>
									<input id="blastname" name="blastname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'blastname' ) ); ?>" value="<?php echo esc_attr($blastname);?>" autocomplete="family-name" />
								</div> <!-- end pmpro_form_field-blastname -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-baddress1', 'pmpro_form_field-baddress1' ) ); ?>">
									<label for="baddress1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Address 1', 'paid-memberships-pro' );?></label>
									<input id="baddress1" name="baddress1" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'baddress1' ) ); ?>" value="<?php echo esc_attr($baddress1);?>" autocomplete="billing street-address" />
								</div> <!-- end pmpro_form_field-baddress1 -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-baddress2', 'pmpro_form_field-baddress2' ) ); ?>">
									<label for="baddress2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Address 2', 'paid-memberships-pro' );?></label>
									<input id="baddress2" name="baddress2" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'baddress2' ) ); ?>" value="<?php echo esc_attr($baddress2);?>" />
								</div> <!-- end pmpro_form_field-baddress2 -->

								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bcity', 'pmpro_form_field-bcity' ) ); ?>">
									<label for="bcity" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('City', 'paid-memberships-pro' );?></label>
									<input id="bcity" name="bcity" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bcity' ) ); ?>" value="<?php echo esc_attr($bcity)?>" />
								</div> <!-- end pmpro_form_field-bcity -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bstate', 'pmpro_form_field-bstate' ) ); ?>">
									<label for="bstate" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('State', 'paid-memberships-pro' );?></label>
									<input id="bstate" name="bstate" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bstate' ) ); ?>" value="<?php echo esc_attr($bstate)?>" />
								</div> <!-- end pmpro_form_field-bstate -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bzipcode', 'pmpro_form_field-bzipcode' ) ); ?>">
									<label for="bzipcode" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Postal Code', 'paid-memberships-pro' );?></label>
									<input id="bzipcode" name="bzipcode" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bzipcode' ) ); ?>" value="<?php echo esc_attr($bzipcode)?>" autocomplete="billing postal-code" />
								</div> <!-- end pmpro_form_field-bzipcode -->

								<?php
									$show_country = apply_filters("pmpro_international_addresses", true);
									if($show_country)
									{
								?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_form_field-bcountry', 'pmpro_form_field-bcountry' ) ); ?>">
									<label for="bcountry" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Country', 'paid-memberships-pro' );?></label>
									<select id="bcountry" name="bcountry" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'bcountry' ) );?>" autocomplete="billing country">
										<?php
											global $pmpro_countries, $pmpro_default_country;
											foreach($pmpro_countries as $abbr => $country)
											{
												if(!$bcountry)
													$bcountry = $pmpro_default_country;
											?>
											<option value="<?php echo esc_attr( $abbr ) ?>" <?php if($abbr === $bcountry) { ?>selected="selected"<?php } ?>><?php echo esc_html( $country ); ?></option>

											<?php
											}
										?>
									</select>
								</div> <!-- end pmpro_form_field-bcountry -->
								<?php
									}
									else
									{
									?>
										<input type="hidden" id="bcountry" name="bcountry" value="US" />
									<?php
									}
								?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bemail', 'pmpro_form_field-bemail' ) ); ?>">
									<label for="bemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Email Address', 'paid-memberships-pro' );?></label>
									<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'bemail' ) ); ?>" value="<?php echo esc_attr($bemail); ?>" />
								</div> <!-- end pmpro_form_field-bemail -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bconfirmemail', 'pmpro_form_field-bconfirmemail' ) ); ?>">
									<label for="bconfirmemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Confirm Email Address', 'paid-memberships-pro' );?></label>
									<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'bconfirmemail' ) ); ?>" value="<?php echo esc_attr($bconfirmemail); ?>" />
								</div> <!-- end pmpro_form_field-bconfirmemail -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bphone', 'pmpro_form_field-bphone' ) ); ?>">
									<label for="bphone" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Phone', 'paid-memberships-pro' );?></label>
									<input id="bphone" name="bphone" type="tel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bphone' ) ); ?>" value="<?php echo esc_attr($bphone)?>" autocomplete="tel" />
								</div> <!-- end pmpro_form_field-bphone -->
							</div> <!-- end pmpro_form_fields -->
						</fieldset> <!-- end pmpro_billing_address_fields -->
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
						<?php } ?>

						<?php
						//make sure gateways will show up credit card fields
						global $pmpro_requirebilling;
						$pmpro_requirebilling = true;

						//do we need to show the payment information (credit card) fields? gateways will override this
						$pmpro_include_payment_information_fields = apply_filters('pmpro_include_payment_information_fields', true);
						if($pmpro_include_payment_information_fields)
						{
							$pmpro_accepted_credit_cards = get_option("pmpro_accepted_credit_cards");
							$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
							$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);
							?>
							<fieldset id="pmpro_payment_information_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_payment_information_fields' ) ); ?>">
								<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
									<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e('Payment Information', 'paid-memberships-pro' ); ?></h2>
								</legend>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
									<input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr($CardType);?>" />
									<script>
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
									</script>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-account-number', 'pmpro_payment-account-number' ) ); ?>">
										<label for="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Card Number', 'paid-memberships-pro' );?></label>
										<input id="AccountNumber" name="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'AccountNumber' ) );?>" type="text" size="25" value="<?php echo esc_attr($AccountNumber)?>" autocomplete="off" />
									</div>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_payment-expiration', 'pmpro_payment-expiration' ) ); ?>">
										<label for="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Expiration Date', 'paid-memberships-pro' );?></label>
										<select id="ExpirationMonth" name="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'ExpirationMonth' ) ); ?>">
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
										</select>/<select id="ExpirationYear" name="ExpirationYear" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'ExpirationYear' ) ); ?>">
										<?php
											$num_years = apply_filters( 'pmpro_num_expiration_years', 10 );

											for ( $i = date_i18n( 'Y' ); $i < intval( date_i18n( 'Y' ) ) + intval( $num_years ); $i++ )
											{
												?>
												<option value="<?php echo esc_attr( $i ) ?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } elseif($i == date_i18n( 'Y' ) + 1) { ?>selected="selected"<?php } ?>><?php echo esc_html( $i )?></option>
												<?php
											}
										?>
										</select>
									</div>							
									<?php
										$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
										if($pmpro_show_cvv) {
											if ( true == ini_get('allow_url_include') ) {
												$cvv_template = pmpro_loadTemplate('popup-cvv', 'url', 'pages', 'html');
											} else {
												$cvv_template = plugins_url( 'paid-memberships-pro/pages/popup-cvv.html', PMPRO_DIR );
											}
										?>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-cvv', 'pmpro_payment-cvv' ) ); ?>">
											<label for="CVV" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('CVV', 'paid-memberships-pro' );?></label>
											<input id="CVV" name="CVV" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr( sanitize_text_field( $_REQUEST['CVV'] ) ); }?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'CVV' ) ); ?>" />
										</div>
									<?php } ?>
								</div> <!-- end pmpro_form_fields -->
							</fieldset> <!-- end pmpro_payment_information_fields -->
							<?php
						}
						?>

						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_captcha' ) ); ?>">
						<?php
							$recaptcha = get_option("pmpro_recaptcha");
							if ( $recaptcha == 2 || ( $recaptcha == 1 && pmpro_isLevelFree( $pmpro_level ) ) ) {
								pmpro_recaptcha_get_html();
							}
						?>				
						</div> <!-- end pmpro_captcha -->

						<?php do_action("pmpro_billing_before_submit_button"); ?>

						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
							<input type="hidden" name="update-billing" value="1" />
							<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php esc_attr_e('Update', 'paid-memberships-pro' );?>" />

							<input type="button" name="cancel" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ) ); ?>" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo esc_url( pmpro_url("account") )?>';" />
						</div> <!-- end pmpro_form_submit -->
					</div> <!-- end pmpro_card_content -->
				</form> <!-- end pmpro_form -->
				<script>
					<!--
					// Find ALL <form> tags on your page
					jQuery('form').on('submit',function(){
						// On submit disable its submit button
						jQuery('input[type=submit]', this).attr('disabled', 'disabled');
						jQuery('input[type=image]', this).attr('disabled', 'disabled');
					});
					-->
				</script>
			</div> <!-- end pmpro_level-ID -->
		<?php } ?>

	<?php } elseif ( pmpro_hasMembershipLevel() ) {
		// User's level must not be recurring.
		?>
		<p><?php esc_html_e("This subscription is not recurring. So you don't need to update your billing information.", 'paid-memberships-pro' );?></p>
		<?php
	} else {
		// User does not have a membership level.
		$allowed_html = array(
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array(),
				'rel' => array(),
			),
		);
		echo wp_kses( sprintf( __( "You do not have an active membership. <a href='%s'>Choose a membership level.</a>", 'paid-memberships-pro' ), esc_url( pmpro_url( 'levels' ) ) ), $allowed_html );
	} ?>
</div> <!-- end pmpro -->
