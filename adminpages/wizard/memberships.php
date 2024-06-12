<?php
/**
 * Step 3 file content. [Memberships]
 */
// Get option for collecting payments
$collecting_payment = get_option( 'pmpro_wizard_collect_payment' );
?>
<div class="pmpro-wizard__step pmpro-wizard__step-3">
	<div class="pmpro-wizard__step-header">
		<h2><?php esc_html_e( 'Membership Levels', 'paid-memberships-pro' ); ?></h2>
		<p><?php esc_html_e( 'Set up your first membership levels from this wizard. You can set up more membership levels with additional settings later.', 'paid-memberships-pro' ); ?></p>
	</div>
	<form action="" method="post">
	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Create Your Membership Levels', 'paid-memberships-pro' ); ?></legend>
		<div class="pmpro-wizard__field">
			<div class="pmpro-wizard__field__checkbox-group">
				<input type="checkbox" id="pmpro-wizard__free-level" name="pmpro-wizard__free-level" value="1">
				<label for="pmpro-wizard__free-level" class="pmpro-wizard__label-block">
					<?php esc_html_e( 'Create a Free Membership Level', 'paid-memberships-pro' ); ?>
				</label>
				<div class="pmpro-wizard__field__checkbox-content" style="display: none;">
					<label for="pmpro-wizard__free-level-name"><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></label>
					<input type="text" name="pmpro-wizard__free-level-name" id="pmpro-wizard__free-level-name" placeholder="<?php esc_attr_e( 'Free', 'paid-memberships-pro' ); ?>" />
				</div>
			</div>
		</div>
		<script>
			jQuery(document).ready(function() {
				jQuery('#pmpro-wizard__free-level').on('click',function() {
					pmpro_toggle_elements_by_selector( jQuery(this).parent().find('.pmpro-wizard__field__checkbox-content'), jQuery( this ).prop( 'checked' ) );
				});
			});
		</script>
		<?php if ( $collecting_payment ) { ?>
		<div class="pmpro-wizard__field">	
			<div class="pmpro-wizard__field__checkbox-group">
				<input type="checkbox" id="pmpro-wizard__paid-level" name="pmpro-wizard__paid-level" value="1">
				<label for="pmpro-wizard__paid-level" class="pmpro-wizard__label-block">
					<?php esc_html_e( 'Create a Paid Membership Level', 'paid-memberships-pro' ); ?>
				</label>
				<div class="pmpro-wizard__field__checkbox-content" style="display: none;">
					<div>
						<label for="pmpro-wizard__paid-level-name"><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></label>
						<input type="text" id="pmpro-wizard__paid-level-name" name="pmpro-wizard__paid-level-name" placeholder="<?php esc_attr_e( 'Premium', 'paid-memberships-pro' ); ?>"/>
					</div>
					<div>
						<label for="pmpro-wizard__paid-level-amount"><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></label>
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
		<script>
			jQuery(document).ready(function() {
				jQuery('#pmpro-wizard__paid-level').on('click',function() {
					pmpro_toggle_elements_by_selector( jQuery(this).parent().find('.pmpro-wizard__field__checkbox-content'), jQuery( this ).prop( 'checked' ) );
				});
			});
		</script>
		<?php } ?>
	</fieldset>
	<div class="pmpro-wizard__field pmpro-wizard__field-alt">
		<p>
			<img src="<?php echo esc_url( PMPRO_URL . '/images/lock.svg' ); ?>" />
			<?php esc_html_e( 'Content restrictions are configured after initial setup.', 'paid-memberships-pro' ); ?>
			<?php
				echo sprintf(
					/* translators: %s: URL to the PMPro documentation page on content restriction */
					esc_html__( 'Learn more about %s.', 'paid-memberships-pro' ),
					'<a href="' . esc_url( 'https://www.paidmembershipspro.com/restrict-access-wordpress/?utm_source=plugin&utm_medium=setup-wizard&utm_campaign=wizard-memberships&utm_content=restrict-content' ) . '" target="_blank">' . esc_html__( 'restricting WordPress content here', 'paid-memberships-pro' ) . '</a>'
				);
			?>
		</p>
	</div>
	<p class="pmpro_wizard__submit">
		<?php wp_nonce_field( 'pmpro_wizard_step_3_nonce', 'pmpro_wizard_step_3_nonce' ); ?>
		<input type="hidden" name="wizard-action" value="step-3"/>
		<input type="submit" name="submit" class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" /><br/>
		<a class="pmpro_wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=advanced' ) );?>"><?php esc_html_e( 'Skip', 'paid-memberships-pro' ); ?></a>
	</p>
	</form>
</div> <!-- end pmpro-wizard__step-3 -->
