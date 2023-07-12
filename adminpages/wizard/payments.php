<div class="pmpro-wizard__step pmpro-wizard__step-2">
	<div class="pmpro-wizard__step-header">
		<h2><?php esc_html_e( 'Payment Settings', 'paid-memberships-pro' ); ?></h2>
	</div>
	<form action="" method="post">
	<div class="pmpro-wizard__field">
		<label for="currency" class="pmpro-wizard__label-block">
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
	<fieldset>
		<legend><?php esc_html_e( 'Configure Your Payment Gateway', 'paid-memberships-pro' ); ?></legend>
		<div class="pmpro-wizard__field">
			<label for="stripe" class="pmpro-wizard__label-block">
				<input type="radio" name="gateway" id="stripe" value="stripe" <?php checked( 'stripe' === pmpro_getOption( 'gateway', true ) ); ?>>
				<?php esc_html_e( 'Stripe', 'paid-memberships-pro' ); ?>
			</label>
			<?php if ( PMProGateway_Stripe::has_connect_credentials( apply_filters( 'pmpro_wizard_stripe_environment', 'live' ) ) ) {
				echo "<p class='pmpro-wizard__field-description'><span class='pmpro_wizard_stripe-connected'>" . esc_html__( 'We have detected you previously connected to Stripe, to change your Stripe account please adjust it in the "Payment Gateway & SSL" settings.', 'paid-memberships-pro' ) . "</span></p>";
			} else { ?>
				<div class="pmpro-wizard__stripe admin_page_pmpro-paymentsettings" <?php if ( 'stripe' !== pmpro_getOption( 'gateway', true ) ) { echo 'style="display:none;"'; } ?>>
				<p class="pmpro-wizard__field-description"><?php esc_html_e( 'After clicking "Submit and Continue", you will be redirected to Stripe to finish connecting PMPro to your Stripe account. If you do not already have a Stripe account and do not want to set one up at this time, please select "Other/Setup Later" instead.', 'paid-memberships-pro' ); ?></p>
				<p class="pmpro-wizard__field-description">
					<?php
					$allowed_webhook_message_html = array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					);
					printf( wp_kses( __( 'After connecting to Stripe, it is important to <a href="%s" target="_blank">set up your Stripe webhooks</a> to make sure that PMPro is notified of events that happen in Stripe.', 'paid-memberships-pro' ), $allowed_webhook_message_html ), 'https://www.paidmembershipspro.com/gateway/stripe/setup/#webhook' );
					?>
				</p>
				</div>
			<?php } ?>
		</div>
		<div class="pmpro-wizard__field">
			<label for="other" class="pmpro-wizard__label-block">
				<input type="radio" name="gateway" id="other" value="other">
				<?php esc_html_e( 'Other/Setup Later', 'paid-memberships-pro' ); ?>
			</label>
		</div>
	</fieldset>

	<div class="pmpro-wizard__field pmpro-wizard__field-alt">
		<p><img src="<?php echo esc_url( PMPRO_URL . '/images/credit-card.svg' ); ?>" /> <?php esc_html_e( 'Payment gateways may be configured under "Payment Gateway & SSL Settings".', 'paid-memberships-pro' ); ?></p>
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
		<?php wp_nonce_field( 'pmpro_wizard_step_2_nonce', 'pmpro_wizard_step_2_nonce' ); ?>
		<input type="hidden" name="wizard-action" value="step-2"/>
		<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
		<a class="pmpro_wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=memberships' ) );?>"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></a>		
	</p>
	</form>
</div> <!-- end pmpro-wizard__step-2 -->
