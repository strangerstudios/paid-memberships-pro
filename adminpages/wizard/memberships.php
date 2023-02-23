<?php
/**
 * Step 3 file content. [Memberships]
 */
// Get option for collecting payments
$collecting_payment = pmpro_getOption( 'wizard_collect_payment' );
?>
<div class="pmpro-wizard__step pmpro-wizard__step-3">
	<div class="pmpro-wizard__step-header">
		<h1><?php esc_html_e( 'Membership Levels', 'paid-memberships-pro' ); ?></h1>
		<p><?php esc_html_e( 'Set up various membership levels from this wizard. You can set up more membership levels with additional settings later.', 'paid-memberships-pro' ); ?></p>
	</div>
	<form action="" method="post">
	<div class="pmpro-wizard__field">
		<div class="pmpro-wizard__field__checkbox-group">
			<input type="checkbox" id="pmpro-wizard__free-level" name="pmpro-wizard__free-level" value="1">
			<div class="pmpro-wizard__field__checkbox-content">
				<label for="pmpro-wizard__free-level" class="pmpro-wizard__label-block">
					<?php esc_html_e( 'Free Membership', 'paid-memberships-pro' ); ?>
				</label>
				<div>
					<label><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></label>
					<input type="text" name="pmpro-wizard__free-level-name" id="pmpro-wizard__free-level-name" placeholder="<?php esc_attr_e( 'Free', 'paid-memberships-pro' ); ?>" />
				</div>						
			</div>
		</div>
	</div>
	<?php if ( $collecting_payment ) { ?>
	<div class="pmpro-wizard__field">	
		<div class="pmpro-wizard__field__checkbox-group">
			<input type="checkbox" id="pmpro-wizard__paid-level" name="pmpro-wizard__paid-level" value="1">
			<div class="pmpro-wizard__field__checkbox-content">
				<label for="pmpro-wizard__paid-level" class="pmpro-wizard__label-block">
					<?php esc_html_e( 'Paid Membership', 'paid-memberships-pro' ); ?>
				</label>
				<div>
					<label><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></label>
					<input type="text" id="pmpro-wizard__paid-level-name" name="pmpro-wizard__paid-level-name" placeholder="<?php esc_attr_e( 'Premium', 'paid-memberships-pro' ); ?>"/>
				</div>
				<div>
					<label><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></label>
					<input type="text" id="pmpro-wizard__paid-level-amount" name="pmpro-wizard__paid-level-amount" placeholder="<?php esc_attr_e( 'Amount (i.e. "10")', 'paid-memberships-pro' ); ?>" />
					<?php esc_html_e( 'every', 'paid-memberships-pro' ); ?>
					<select id="cycle_period" name="cycle_period">
						<?php
							$cycles = array(
								__( 'Day', 'paid-memberships-pro' ) => 'Day',
								__( 'Week', 'paid-memberships-pro' ) => 'Week',
								__( 'Month', 'paid-memberships-pro' ) => 'Month',
								__( 'Year', 'paid-memberships-pro' ) => 'Year',
							);
							foreach ( $cycles as $name => $value ) {
								?>
								<option <?php selected( $value, 'Month' ); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $name ); ?></option>
								<?php
							}
							?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<div class="pmpro-wizard__field pmpro-wizard__field-alt">
		<p><img src="<?php echo esc_url( PMPRO_URL . '/images/lock.svg' ); ?>" /> <?php esc_html_e( 'Content restriction settings may be set after the setup wizard.', 'paid-memberships-pro' ); ?></p>
	</div>
	<p class="pmpro_wizard__submit">
		<?php wp_nonce_field( 'pmpro_wizard_step_3_nonce', 'pmpro_wizard_step_3_nonce' ); ?>
		<input type="hidden" name="wizard-action" value="step-3"/>
		<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
		<a class="pmpro_wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=advanced' ) );?>"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></a>
	</p>
	</form>
</div> <!-- end pmpro-wizard__step-3 -->
