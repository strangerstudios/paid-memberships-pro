<div class="pmpro-wizard__step pmpro-wizard__step-3">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Payment Settings', 'paid-memberships-pro' ); ?></h1>
				</div>
				<form action="" method="post">
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<?php esc_html_e( 'Select the currency for your membership site.', 'paid-memberships-pro' ); ?>
					</label>
					<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Your payment gateway can accept many currencies and automatically convert payments for you.', 'paid-memberships-pro' ); ?></p>
					<select name="currency" id="currency" class="pmpro-wizard__field-block">
					<?php
					global $pmpro_currencies, $pmpro_currency;
					foreach ( $pmpro_currencies as $ccode => $cdescription ) {
						if ( is_array( $cdescription ) ) {
							$cdescription = $cdescription['name'];
						}
						?>
						<option value="<?php echo esc_attr( $ccode ); ?>" <?php selected( $pmpro_currency, $ccode ); ?>><?php echo esc_html( $cdescription ); ?></option>
						<?php
					}
					?>
					</select>
				</div>
				<div class="pmpro-wizard__field">
					<h2 class="pmpro-wizard__section-title"><?php esc_html_e( 'Configure Your Payment Gateway', 'paid-memberships-pro' ); ?></h2>
					<label class="pmpro-wizard__label-block">
						<input type="radio" name="gateway" value="stripe">
						<?php esc_html_e( 'Stripe', 'paid-memberships-pro' ); ?>
					</label>
					<div class="pmpro-wizard__stripe admin_page_pmpro-paymentsettings" style="display:none;">
						<p>
							<a href='#' class='pmpro-stripe-connect'>
								<span>Connect with Stripe</span>
							</a>
						</p>
						<p style="font-size:12px;text-transform:italic;">If you do not already have a Stripe account, we recommend to set this up later.</p>
					</div>
				</div>
				<!-- <div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="radio"  name="gateway" value="paypal-express">
						<?php esc_html_e( 'PayPal Express', 'paid-memberships-pro' ); ?>
					</label>
				</div> -->
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="radio" name="gateway" value="other">
						<?php esc_html_e( 'Other/Setup Later', 'paid-memberships-pro' ); ?>
					</label>
				</div>
				<div class="pmpro-wizard__field" style="background-color:#F7F7F7;">
					<p><img src="<?php echo plugins_url( '/images/credit-card.svg', dirname( __DIR__ ) ); ?>" style="vertical-align:middle;"/> Payment gateways may be configured under "Payment Gateway & SSL Settings".</p>
				</div>
				<script>
					jQuery(document).ready(function(){
						jQuery("input[name=gateway]").on('change', function(){
							var radio_val = jQuery(this).val();

							if ( radio_val == 'stripe' ) {
								jQuery('.pmpro-wizard__stripe').show();
							} else {
								jQuery('.pmpro-wizard__stripe').hide();
							}
						});
					});
				</script>
				<p class="pmpro_wizard__submit">
					<?php wp_nonce_field( 'pmpro_wizard_step_3_nonce', 'pmpro_wizard_step_3_nonce' ); ?>
					<input type="hidden" name="wizard-action" value="step-3"/>
					<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" />
				</p>
				</form>
			</div> <!-- end pmpro-wizard__step-3 -->
