<?php
/**
 * Template: Checkout
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
 *
 * @author Paid Memberships Pro
 */

global $gateway, $pmpro_review, $skip_account_fields, $pmpro_paypal_token, $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_requirebilling, $pmpro_level, $tospage, $pmpro_show_discount_code, $pmpro_error_fields, $pmpro_default_country;
global $discount_code, $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth,$ExpirationYear;

$pmpro_levels = pmpro_getAllLevels();

/**
 * Filter to set if PMPro uses email or text as the type for email field inputs.
 *
 * @since 1.8.4.5
 *
 * @param bool $use_email_type, true to use email type, false to use text type
 */
$pmpro_email_field_type = apply_filters('pmpro_email_field_type', true);

// Set the wrapping class for the checkout div based on the default gateway;
$default_gateway = get_option( 'pmpro_gateway' );
if ( empty( $default_gateway ) ) {
	$pmpro_checkout_gateway_class = 'pmpro_section pmpro_checkout_gateway-none';
} else {
	$pmpro_checkout_gateway_class = 'pmpro_section pmpro_checkout_gateway-' . $default_gateway;
}
?>

<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">

	<?php do_action( 'pmpro_checkout_before_form' ); ?>

	<section id="pmpro_level-<?php echo intval( $pmpro_level->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( $pmpro_checkout_gateway_class, 'pmpro_level-' . $pmpro_level->id ) ); ?>">

		<form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="<?php if(!empty($_REQUEST['review'])) echo esc_url( pmpro_url("checkout", "?pmpro_level=" . $pmpro_level->id) ); ?>" method="post">

			<input type="hidden" id="pmpro_level" name="pmpro_level" value="<?php echo esc_attr($pmpro_level->id) ?>" />
			<input type="hidden" id="checkjavascript" name="checkjavascript" value="1" />
			<?php if ($discount_code && $pmpro_review) { ?>
				<input class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_alter_price', 'pmpro_discount_code' ) ); ?>" id="pmpro_discount_code" name="pmpro_discount_code" type="hidden" value="<?php echo esc_attr($discount_code) ?>" />
			<?php } ?>

			<?php if($pmpro_msg) { ?>
				<div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
					<?php echo wp_kses_post( apply_filters( 'pmpro_checkout_message', $pmpro_msg, $pmpro_msgt ) ); ?>
				</div>
			<?php } else { ?>
				<div id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
			<?php } ?>

			<?php if ( $pmpro_review ) { ?>
				<p><?php echo wp_kses( __( 'Almost done. Review the membership information and pricing below then <strong>click the "Complete Payment" button</strong> to finish your order.', 'paid-memberships-pro' ), array( 'strong' => array() ) ); ?></p>
			<?php } ?>

			<?php
				$include_pricing_fields = apply_filters( 'pmpro_include_pricing_fields', true );
				if ( $include_pricing_fields ) {
				?>
				<div id="pmpro_pricing_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmpro_pricing_fields' ) ); ?>">

					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Membership Information', 'paid-memberships-pro' ); ?></h2>

					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">

						<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_name_text' ) );?>">
							<?php
							// Tell the user which level they are signing up for.
							printf( esc_html__('You have selected the %s membership level.', 'paid-memberships-pro' ), '<strong>' . esc_html( $pmpro_level->name ) . '</strong>' );

							// If a level will be removed with this purchase, let them know that too.
							// First off, get the group for this level and check if it allows a user to have multiple levels.
							$group_id = pmpro_get_group_id_for_level( $pmpro_level->id );
							$group    = pmpro_get_level_group( $group_id );
							if ( ! empty( $group ) && empty( $group->allow_multiple_selections ) ) {
								// Get all of the user's current membership levels.
								$levels = pmpro_getMembershipLevelsForUser( $current_user->ID );

								// Loop through the levels and see if any are in the same group as the level being purchased.
								if ( ! empty( $levels ) ) {
									foreach ( $levels as $level ) {
										// If this is the level that the user is purchasing, continue.
										if ( $level->id == $pmpro_level->id ) {
											continue;
										}

										// If this level is not in the same group, continue.
										if ( pmpro_get_group_id_for_level( $level->id ) != $group_id ) {
											continue;
										}

										// If we made it this far, the user is going to lose this level after checkout.
										printf( ' ' . esc_html__( 'Your current membership level of %s will be removed when you complete your purchase.', 'paid-memberships-pro' ), '<strong>' . esc_html( $level->name ) . '</strong>' );
									}
								}
							}
							?>
						</p> <!-- end pmpro_level_name_text -->

						<?php
							/**
							 * Allow devs to filter the level description at checkout.
							 * We also have a function in includes/filters.php that applies the the_content filters to this description.
							 * @param string $description The level description.
							 * @param object $pmpro_level The PMPro Level object.
							 */
							$level_description = apply_filters('pmpro_level_description', $pmpro_level->description, $pmpro_level);
							if ( ! empty( $level_description ) ) { ?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_description_text' ) );?>">
									<?php echo wp_kses_post( $level_description ); ?>
								</div> <!-- end pmpro_level_description_text -->
								<?php
							}
						?>

						<div id="pmpro_level_cost">
							<?php if($discount_code && pmpro_checkDiscountCode($discount_code)) { ?>
								<?php
									echo '<p class="' . esc_attr( pmpro_get_element_class( 'pmpro_level_discount_applied' ) ) . '">';
									echo sprintf( esc_html__( 'The %s code has been applied to your order.', 'paid-memberships-pro' ), '<span class="' . esc_attr( pmpro_get_element_class( "pmpro_tag pmpro_tag-discount-code", "pmpro_tag-discount-code" ) ) . '">' . esc_html( $discount_code ) . '</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo '</p> <!-- end pmpro_level_discount_applied -->';
								?>
							<?php } ?>

							<?php
								$level_cost_text = pmpro_getLevelCost( $pmpro_level );
								if ( ! empty( $level_cost_text ) ) { ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_cost_text' ) );?>">
										<?php echo wp_kses_post( wpautop( $level_cost_text ) ); ?>
									</div> <!-- end pmpro_level_cost_text -->
								<?php }
							?>

							<?php
								$level_expiration_text = pmpro_getLevelExpiration( $pmpro_level );
								if ( ! empty( $level_expiration_text ) ) { ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_expiration_text' ) );?>">
										<?php echo wp_kses_post( wpautop( $level_expiration_text ) ); ?>
									</div> <!-- end pmpro_level_expiration_text -->
								<?php }
							?>
						</div> <!-- end #pmpro_level_cost -->

						<?php do_action( 'pmpro_checkout_after_level_cost' ); ?>

					</div> <!-- end pmpro_card_content -->
					<?php if ( $pmpro_show_discount_code ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
							<?php if($discount_code && !$pmpro_review) { ?>
								<span id="other_discount_code_p"><button type="button" id="other_discount_code_toggle"><?php esc_html_e('Click here to change your discount code', 'paid-memberships-pro' );?></button></span>
							<?php } elseif(!$pmpro_review) { ?>
								<span id="other_discount_code_p"><?php esc_html_e('Do you have a discount code?', 'paid-memberships-pro' );?> <button type="button" id="other_discount_code_toggle"><?php esc_html_e('Click here to enter your discount code', 'paid-memberships-pro' );?></button></span>
							<?php } elseif($pmpro_review && $discount_code) { ?>
								<span><strong><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $discount_code ); ?></span>
							<?php } ?>
							<div id="other_discount_code_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>" style="display: none;">
								<label for="pmpro_other_discount_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?></label>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
									<input id="pmpro_other_discount_code" name="pmpro_other_discount_code" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_alter_price', 'other_discount_code' ) ); ?>" value="<?php echo esc_attr($discount_code); ?>" />
									<input aria-label="<?php esc_html_e( 'Apply discount code', 'paid-memberships-pro' ); ?>" type="button" name="other_discount_code_button" id="other_discount_code_button" value="<?php esc_attr_e('Apply', 'paid-memberships-pro' );?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-discount-code', 'other_discount_code_button' ) ); ?>" />
								</div>
							</div>
						</div> <!-- end pmpro_card_actions -->
					<?php } ?>
				</div> <!-- end pmpro_pricing_fields -->
				<?php
				} // if ( $include_pricing_fields )
			?>

			<?php do_action( 'pmpro_checkout_after_pricing_fields' ); ?>

			<fieldset id="pmpro_user_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_user_fields' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
							<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Account Information', 'paid-memberships-pro' ); ?></h2>
						</legend>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">

						<?php if ( ! $skip_account_fields && ! $pmpro_review ) { ?>

							<?php
								// Get discount code from URL parameter, so if the user logs in it will keep it applied.
								$discount_code_link = !empty( $discount_code) ? '&pmpro_discount_code=' . $discount_code : '';
							?>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-username pmpro_form_field-required', 'pmpro_form_field-username' ) ); ?>">
								<label for="username" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Username', 'paid-memberships-pro' );?></label>
								<input id="username" name="username" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_form_input-required', 'username' ) ); ?>" autocomplete="username" value="<?php echo esc_attr($username); ?>" />
							</div> <!-- end pmpro_form_field-username -->

							<?php do_action( 'pmpro_checkout_after_username' ); ?>

							<?php
								/**
								 * Filter to require confirmed password at checkout.
								 *
								 * @param bool $pmpro_checkout_confirm_password, true to require a password confirm field, false to hide.
								 */
								$pmpro_checkout_confirm_password = apply_filters( 'pmpro_checkout_confirm_password', true );

								echo $pmpro_checkout_confirm_password ? '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ) . '">' : '';
							?>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password pmpro_form_field-required' ) ); ?>">
								<label for="password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
									<?php esc_html_e( 'Password', 'paid-memberships-pro' );?>
								</label>
								<input type="password" name="password" id="password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required', 'password' ) ); ?>" autocomplete="new-password" spellcheck="false" value="<?php echo esc_attr($password); ?>" />
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle' ) ); ?>">
									<button type="button" class="pmpro_btn pmpro_btn-plain pmpro_btn-password-toggle hide-if-no-js" data-toggle="0">
										<span class="pmpro_icon pmpro_icon-eye" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle-state' ) ); ?>"><?php esc_html_e( 'Show Password', 'paid-memberships-pro' ); ?></span>
									</button>
								</div> <!-- end pmpro_form_field-password-toggle -->
							</div> <!-- end pmpro_form_field-password -->

							<?php
								if ( $pmpro_checkout_confirm_password ) {
									?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password pmpro_form_field-required', 'pmpro_form_field-password2' ) ); ?>">
										<label for="password2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Confirm Password', 'paid-memberships-pro' );?></label>
										<input type="password" name="password2" id="password2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required', 'password2' ) ); ?>" autocomplete="new-password" spellcheck="false" value="<?php echo esc_attr($password2); ?>" />
									</div> <!-- end pmpro_form_field-password2 -->
									<?php
								} else {
									?>
									<input type="hidden" name="password2_copy" value="1" />
									<?php
								}
							?>

							<?php echo $pmpro_checkout_confirm_password ? '</div>' : ''; ?>

							<?php do_action( 'pmpro_checkout_after_password' ); ?>

							<?php
								/**
								 * Filter to require confirmed email at checkout.
								 *
								 * @param bool $pmpro_checkout_confirm_email, true to require a email confirm field, false to hide.
								 */
								$pmpro_checkout_confirm_email = apply_filters( 'pmpro_checkout_confirm_email', true );

								echo $pmpro_checkout_confirm_email ? '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ) . '">' : '';
							?>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bemail pmpro_form_field-required', 'pmpro_form_field-bemail' ) ); ?>">
								<label for="bemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Email Address', 'paid-memberships-pro' );?></label>
								<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email pmpro_form_input-required', 'bemail' ) ); ?>" value="<?php echo esc_attr($bemail); ?>" />
							</div> <!-- end pmpro_form_field-bemail -->

							<?php
								if ( $pmpro_checkout_confirm_email ) {
									?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bconfirmemail pmpro_form_field-required', 'pmpro_form_field-bconfirmemail' ) ); ?>">
										<label for="bconfirmemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Confirm Email Address', 'paid-memberships-pro' );?></label>
										<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email pmpro_form_input-required', 'bconfirmemail' ) ); ?>" value="<?php echo esc_attr($bconfirmemail); ?>" />
									</div> <!-- end pmpro_form_field-bconfirmemail -->
									<?php
								} else {
									?>
									<input type="hidden" name="bconfirmemail_copy" value="1" />
									<?php
								}
							?>

							<?php echo $pmpro_checkout_confirm_email ? '</div>' : ''; ?>

							<?php do_action( 'pmpro_checkout_after_email' ); ?>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_hidden' ) ); ?>">
								<label for="fullname"><?php esc_html_e('Full Name', 'paid-memberships-pro' );?></label>
								<input id="fullname" name="fullname" type="text" value="" autocomplete="off"/> <strong><?php esc_html_e('LEAVE THIS BLANK', 'paid-memberships-pro' );?></strong>
							</div> <!-- end pmpro_hidden -->

						<?php } elseif ( $current_user->ID && ! $pmpro_review ) { ?>
							<div id="pmpro_account_loggedin">
							<?php
								$allowed_html = array(
									'a' => array(
										'href' => array(),
										'title' => array(),
										'target' => array(),
									),
									'strong' => array(),
								);
								echo wp_kses( sprintf( __('You are logged in as <strong>%s</strong>. If you would like to use a different account for this membership, <a href="%s">log out now</a>.', 'paid-memberships-pro' ), $current_user->user_login, wp_logout_url( esc_url_raw( $_SERVER['REQUEST_URI'] ) ) ), $allowed_html );
							?>
							</div> <!-- end pmpro_account_loggedin -->
						<?php } ?>
						</div>  <!-- end pmpro_form_fields -->
					</div> <!-- end pmpro_card_content -->
					<?php if ( ! $skip_account_fields && ! $pmpro_review ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
							<?php esc_html_e('Already have an account?', 'paid-memberships-pro' );?> <a href="<?php echo esc_url( wp_login_url( apply_filters( 'pmpro_checkout_login_redirect', pmpro_url("checkout", "?pmpro_level=" . $pmpro_level->id . $discount_code_link) ) ) ); ?>"><?php esc_html_e('Log in here', 'paid-memberships-pro' ); ?></a>
						</div> <!-- end pmpro_card_actions -->
					<?php } ?>
				</div> <!-- end pmpro_card -->
			</fieldset> <!-- end pmpro_user_fields -->

			<?php do_action( 'pmpro_checkout_after_user_fields' ); ?>

			<?php do_action( 'pmpro_checkout_boxes' ); ?>

			<?php if ( pmpro_getGateway() == "paypal" && empty($pmpro_review) && true == apply_filters('pmpro_include_payment_option_for_paypal', true ) ) { ?>
			<fieldset id="pmpro_payment_method" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_payment_method' ) ); ?>" <?php if(!$pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
							<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Choose your Payment Method', 'paid-memberships-pro' ); ?></h2>
						</legend>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-radio-items pmpro_cols-2' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-radio-item gateway_paypal', 'gateway_paypal' ) ); ?>">
									<input id="gateway-paypal" type="radio" name="gateway" value="paypal" <?php if(!$gateway || $gateway == "paypal") { ?>checked="checked"<?php } ?> class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-radio' ) ); ?>" />
									<label for="gateway-paypal" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ); ?>">
										<a href="javascript:void(0);"><?php esc_html_e('Check Out with a Credit Card Here', 'paid-memberships-pro' );?></a>
									</label>
								</div> <!-- end gateway_paypal -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-radio-item gateway_paypalexpress', 'gateway_paypalexpress' ) ); ?>">
									<input id="gateway-paypalexpress" type="radio" name="gateway" value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>checked="checked"<?php } ?> class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-radio' ) ); ?>" />
									<label for="gateway-paypalexpress" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ); ?>">
										<a href="javascript:void(0);" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_radio' ) ); ?>"><?php esc_html_e('Check Out with PayPal', 'paid-memberships-pro' );?></a>
									</label>
								</div> <!-- end gateway_paypalexpress -->
							</div> <!-- end pmpro_form_field-radio-items -->
						</div> <!-- end pmpro_form_fields -->
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</fieldset> <!-- end pmpro_payment_method -->
			<?php } ?>

			<?php
				$pmpro_include_billing_address_fields = apply_filters('pmpro_include_billing_address_fields', true);
				if ( $pmpro_include_billing_address_fields ) { ?>
			<fieldset id="pmpro_billing_address_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_billing_address_fields' ) ); ?>" <?php if ( ! $pmpro_requirebilling || apply_filters("pmpro_hide_billing_address_fields", false) ) { ?>style="display: none;"<?php } ?>>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
							<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Billing Address', 'paid-memberships-pro' ); ?></h2>
						</legend>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields pmpro_cols-2' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bfirstname', 'pmpro_form_field-bfirstname' ) ); ?>">
								<label for="bfirstname" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('First Name', 'paid-memberships-pro' );?></label>
								<input id="bfirstname" name="bfirstname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bfirstname' ) ); ?>" value="<?php echo esc_attr($bfirstname); ?>" autocomplete="given-name" />
							</div> <!-- end pmpro_form_field-bfirstname -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-blastname', 'pmpro_form_field-blastname' ) ); ?>">
								<label for="blastname" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Last Name', 'paid-memberships-pro' );?></label>
								<input id="blastname" name="blastname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'blastname' ) ); ?>" value="<?php echo esc_attr($blastname); ?>" autocomplete="family-name" />
							</div> <!-- end pmpro_form_field-blastname -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-baddress1', 'pmpro_form_field-baddress1' ) ); ?>">
								<label for="baddress1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Address 1', 'paid-memberships-pro' );?></label>
								<input id="baddress1" name="baddress1" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'baddress1' ) ); ?>" value="<?php echo esc_attr($baddress1); ?>" autocomplete="billing street-address" />
							</div> <!-- end pmpro_form_field-baddress1 -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-baddress2', 'pmpro_form_field-baddress2' ) ); ?>">
								<label for="baddress2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Address 2', 'paid-memberships-pro' );?></label>
								<input id="baddress2" name="baddress2" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'baddress2' ) ); ?>" value="<?php echo esc_attr($baddress2); ?>" />
							</div> <!-- end pmpro_form_field-baddress2 -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bcity', 'pmpro_form_field-bcity' ) ); ?>">
									<label for="bcity" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('City', 'paid-memberships-pro' );?></label>
									<input id="bcity" name="bcity" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bcity' ) ); ?>" value="<?php echo esc_attr($bcity); ?>" />
								</div> <!-- end pmpro_form_field-bcity -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bstate', 'pmpro_form_field-bstate' ) ); ?>">
									<label for="bstate" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('State', 'paid-memberships-pro' );?></label>
									<input id="bstate" name="bstate" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bstate' ) ); ?>" value="<?php echo esc_attr($bstate); ?>" />
								</div> <!-- end pmpro_form_field-bstate -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bzipcode', 'pmpro_form_field-bzipcode' ) ); ?>">
									<label for="bzipcode" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Postal Code', 'paid-memberships-pro' );?></label>
									<input id="bzipcode" name="bzipcode" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bzipcode' ) ); ?>" value="<?php echo esc_attr($bzipcode); ?>" autocomplete="billing postal-code" />
								</div> <!-- end pmpro_form_field-bzipcode -->
							<?php
								$show_country = apply_filters("pmpro_international_addresses", true);
								if($show_country) { ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_form_field-bcountry', 'pmpro_form_field-bcountry' ) ); ?>">
										<label for="bcountry" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Country', 'paid-memberships-pro' );?></label>
										<select name="bcountry" id="bcountry" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'bcountry' ) ); ?>" autocomplete="billing country">
										<?php
											global $pmpro_countries, $pmpro_default_country;
											if(!$bcountry) {
												$bcountry = $pmpro_default_country;
											}
											foreach($pmpro_countries as $abbr => $country) { ?>
												<option value="<?php echo esc_attr( $abbr ) ?>" <?php if($abbr == $bcountry) { ?>selected="selected"<?php } ?>><?php echo esc_html( $country )?></option>
											<?php } ?>
										</select>
									</div> <!-- end pmpro_form_field-bcountry -->
								<?php } else { ?>
									<input type="hidden" name="bcountry" id="bcountry" value="<?php echo esc_attr( $pmpro_default_country ); ?>" />
								<?php } ?>
							<?php if($skip_account_fields) { ?>
							<?php
								if($current_user->ID) {
									if(!$bemail && $current_user->user_email) {
										$bemail = $current_user->user_email;
									}
									if(!$bconfirmemail && $current_user->user_email) {
										$bconfirmemail = $current_user->user_email;
									}
								}
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bemail', 'pmpro_form_field-bemail' ) ); ?>">
								<label for="bemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Email Address', 'paid-memberships-pro' );?></label>
								<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'bemail' ) ); ?>" value="<?php echo esc_attr($bemail); ?>" autocomplete="email" />
							</div> <!-- end pmpro_form_field-bemail -->
							<?php
								$pmpro_checkout_confirm_email = apply_filters("pmpro_checkout_confirm_email", true);
								if($pmpro_checkout_confirm_email) { ?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bconfirmemail', 'pmpro_form_field-bconfirmemail' ) ); ?>">
										<label for="bconfirmemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Confirm Email', 'paid-memberships-pro' );?></label>
										<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'bconfirmemail' ) ); ?>" value="<?php echo esc_attr($bconfirmemail); ?>" autocomplete="email" />
									</div> <!-- end pmpro_form_field-bconfirmemail -->
								<?php } else { ?>
									<input type="hidden" name="bconfirmemail_copy" value="1" />
								<?php } ?>
							<?php } ?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bphone', 'pmpro_form_field-bphone' ) ); ?>">
								<label for="bphone" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Phone', 'paid-memberships-pro' );?></label>
								<input id="bphone" name="bphone" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bphone' ) ); ?>" value="<?php echo esc_attr(formatPhone($bphone)); ?>" autocomplete="tel" />
							</div> <!-- end pmpro_form_field-bphone -->
						</div> <!-- end pmpro_form_fields -->
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
			</fieldset> <!-- end pmpro_billing_address_fields -->
			<?php } ?>

			<?php do_action( 'pmpro_checkout_after_billing_fields' ); ?>

			<?php
				/**
				 * Filter to set if the payment information fields should be shown.
				 *
				 * @param bool $include_payment_information_fields
				 * @return bool
				 */
				$pmpro_include_payment_information_fields = apply_filters( 'pmpro_include_payment_information_fields', true );
				if ( $pmpro_include_payment_information_fields ) {
					?>
					<fieldset id="pmpro_payment_information_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_payment_information_fields' ) ); ?>" <?php if ( ! $pmpro_requirebilling || apply_filters( 'pmpro_hide_payment_information_fields', false ) ) { ?>style="display: none;"<?php } ?>>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
								<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
									<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Payment Information', 'paid-memberships-pro' ); ?></h2>
								</legend>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
									<input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr($CardType);?>" />
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-account-number', 'pmpro_payment-account-number' ) ); ?>">
										<label for="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Card Number', 'paid-memberships-pro' );?></label>
										<input id="AccountNumber" name="AccountNumber" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'AccountNumber' ) ); ?>" type="text" value="<?php echo esc_attr($AccountNumber); ?>" data-encrypted-name="number" autocomplete="off" />
									</div>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_payment-expiration', 'pmpro_payment-expiration' ) ); ?>">
											<label for="ExpirationMonth" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Expiration Date', 'paid-memberships-pro' );?></label>
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
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
											</div> <!-- end pmpro_form_fields-inline -->
										</div>
										<?php
											$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
											if($pmpro_show_cvv) { ?>
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-cvv', 'pmpro_payment-cvv' ) ); ?>">
												<label for="CVV" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Security Code (CVC)', 'paid-memberships-pro' );?></label>
												<input id="CVV" name="CVV" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr( sanitize_text_field( $_REQUEST['CVV'] ) ); }?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'CVV' ) ); ?>" />
											</div>
										<?php } ?>
									</div> <!-- end pmpro_cols-2 -->
									<?php if($pmpro_show_discount_code) { ?>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_payment-discount-code', 'pmpro_payment-discount-code' ) ); ?>">
												<label for="pmpro_discount_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?></label>
												<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
													<input class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_alter_price', 'discount_code' ) ); ?>" id="pmpro_discount_code" name="pmpro_discount_code" type="text" size="10" value="<?php echo esc_attr($discount_code); ?>" />
													<input aria-label="<?php esc_html_e( 'Apply discount code', 'paid-memberships-pro' ); ?>" type="button" id="discount_code_button" name="discount_code_button" value="<?php esc_attr_e('Apply', 'paid-memberships-pro' );?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-discount-code', 'other_discount_code_button' ) ); ?>" />
												</div> <!-- end pmpro_form_fields-inline -->
												<div id="discount_code_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message', 'discount_code_message' ) ); ?>" style="display: none;"></div>
											</div>
										</div> <!-- end pmpro_cols-2 -->
									<?php } ?>
								</div> <!-- end pmpro_form_fields -->
							</div> <!-- end pmpro_card_content -->
						</div> <!-- end pmpro_card -->
					</fieldset> <!-- end pmpro_payment_information_fields -->
					<?php
				}
			?>

			<?php do_action( 'pmpro_checkout_after_payment_information_fields' ); ?>

			<?php if ( $tospage && ! $pmpro_review ) { ?>
				<fieldset id="pmpro_tos_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_tos_fields' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
						<?php
							if ( isset( $_REQUEST['tos'] ) ) {
								$tos = intval( $_REQUEST['tos'] );
							} else {
								$tos = "";
							}
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox pmpro_form_field-required' ) ); ?>">
							<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_clickable', 'tos' ) ); ?>" for="tos">
								<input type="checkbox" name="tos" value="1" id="tos" <?php checked( 1, $tos ); ?> class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox pmpro_form_input-required', 'tos' ) ); ?>" />
								<?php
									$tos_label = sprintf( __( 'I agree to the <a href="%1$s" target="_blank">%2$s</a>', 'paid-memberships-pro' ), esc_url( get_permalink( $tospage->ID ) ), esc_html( $tospage->post_title ) );
									/**
									 * Filter the Terms of Service field label.
									 *
									 * @since 3.1
									 *
									 * @param string $tos_label The field label.
									 * @param object $tospage The Terms of Service page object.
									 * @return string The filtered field label.
									 */
									$tos_label = apply_filters( 'pmpro_tos_field_label', $tos_label, $tospage );
									echo wp_kses_post( $tos_label );
								?>
							</label>
						</div> <!-- end pmpro_form_field-tos -->
						<?php
							/**
							 * Allow adding text or more checkboxes after the Tos checkbox
							 * This is NOT intended to support multiple Tos checkboxes
							 *
							 * @since 2.8
							 */
							do_action( 'pmpro_checkout_after_tos' );
						?>
					</div> <!-- end pmpro_form_fields -->
				</fieldset> <!-- end pmpro_tos_fields -->
				<?php
				}
			?>

			<?php do_action( 'pmpro_checkout_after_tos_fields' ); ?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_captcha' ) ); ?>">
			<?php
				$recaptcha = get_option( "pmpro_recaptcha");
				if ( $recaptcha == 2 || $recaptcha == 1 ) {
					pmpro_recaptcha_get_html();
				}
			?>
			</div> <!-- end pmpro_captcha -->

			<?php
				do_action( 'pmpro_checkout_after_captcha' );
				do_action( 'pmpro_checkout_before_submit_button' );

				// Add nonce.
				wp_nonce_field( 'pmpro_checkout_nonce', 'pmpro_checkout_nonce' );
			?>

			<?php if ( $pmpro_msg ) { ?>
				<div id="pmpro_message_bottom" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( apply_filters( 'pmpro_checkout_message', $pmpro_msg, $pmpro_msgt ) ); ?></div>
			<?php } else { ?>
				<div id="pmpro_message_bottom" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
			<?php } ?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">

				<?php if ( $pmpro_review ) { ?>

					<span id="pmpro_submit_span">
						<input type="hidden" name="confirm" value="1" />
						<input type="hidden" name="token" value="<?php echo esc_attr($pmpro_paypal_token); ?>" />
						<input type="hidden" name="gateway" value="<?php echo esc_attr($gateway); ?>" />
						<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php esc_attr_e('Complete Payment', 'paid-memberships-pro' );?>" />
					</span>

				<?php } else { ?>

					<?php
						/**
						 * Filter to set the default submit button on the checkout page.
						 *
						 * @param bool $pmpro_checkout_default_submit_button Default is true.
						 * @return bool
						 */
						$pmpro_checkout_default_submit_button = apply_filters('pmpro_checkout_default_submit_button', true);
						if ( $pmpro_checkout_default_submit_button ) {
							?>
							<span id="pmpro_submit_span">
								<input type="hidden" name="submit-checkout" value="1" />
								<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class(  'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php if($pmpro_requirebilling) { esc_html_e('Submit and Check Out', 'paid-memberships-pro' ); } else { esc_html_e('Submit and Confirm', 'paid-memberships-pro' );}?>" />
							</span>
							<?php
							}
						?>

				<?php } ?>

				<div id="pmpro_processing_message" style="visibility: hidden;">
					<?php
						$processing_message = apply_filters("pmpro_processing_message", __("Processing...", 'paid-memberships-pro' ));
						echo wp_kses_post( $processing_message );
					?>
				</div>

			</div> <!-- end pmpro_form_submit -->

		</form> <!-- end pmpro_form -->

		<?php do_action( 'pmpro_checkout_after_form' ); ?>

	</section> <!-- end pmpro_level-ID -->

</div> <!-- end pmpro -->
