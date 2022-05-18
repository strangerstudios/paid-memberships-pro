<?php
/**
 * The Setup Wizard mockup page for Paid Memberships Pro
 */
if ( ! empty( $_REQUEST['step'] ) ) {
	$active_step = $_REQUEST['step'];
} else {
	$active_step = 'general';
}

?>
<div class="pmpro-wizard">
	<div style="background-image: url('/wp-content/plugins/paid-memberships-pro/images/bg_icons-white.png');background-repeat: repeat;background-size: 50%;position: absolute; top: 0; left: 0; width: 100%;height: 100vh;opacity: .5; z-index: -1;"></div>
	<div class="pmpro-wizard__header">
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=homepage"><img src="<?php echo esc_url( PMPRO_URL . '/images/Paid-Memberships-Pro.png' ); ?>" width="350" height="75" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro-stepper">
			<div class="pmpro-stepper__steps">
				<?php
					$setup_steps = array(
						'general' => __( 'General Info', 'paid-memberships-pro' ),
						'memberships' => __( 'Memberships', 'paid-memberships-pro' ),
						'payments' => __( 'Payments', 'paid-memberships-pro' ),
						'advanced' => __( 'Advanced', 'paid-memberships-pro' ),
						'done' => __( 'All Set!', 'paid-memberships-pro' ),
					);
					$count = 0;
					foreach ( $setup_steps as $setup_step => $name ) {
						// Build the selectors for the step based on wizard flow.
						$classes = array();
						$classes[] = 'pmpro-stepper__step';
						if ( $setup_step === $active_step ) {
							$classes[] = 'is-active';
						}
						$class = implode( ' ', array_unique( $classes ) );
						$count++;
						?>
						<div class="<?php echo esc_attr( $class ); ?>">
							<div class="pmpro-stepper__step-icon">
								<span class="pmpro-stepper__step-number"><?php echo esc_html( $count ); ?></span>
							</div>
							<span class="pmpro-stepper__step-label">
								<?php echo esc_html( $name ); ?>
								<!-- <a href="/wp-admin/admin.php?page=pmpro-wizard&step=<?php //echo esc_attr( $setup_step ); ?>"><?php //echo esc_html( $name ); ?></a> -->
							</span>
						</div>
						<div class="pmpro-stepper__step-divider"></div>
						<?php
					}
				?>
			</div>
		</div>
	</div>

	<div class="pmpro-wizard__container">
		<?php if ( $active_step === 'general' ) { ?>
			<div class="pmpro-wizard__step pmpro-wizard__step-1">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Welcome to Your New Membership Site', 'paid-memberships-pro' ); ?></h1>
					<p><?php esc_html_e( 'Tell us about your membership site to get up and running in 5 easy steps.', 'paid-memberships-pro'); ?></p>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<?php esc_html_e( 'What type of membership site are you creating?', 'paid-memberships-pro' ); ?>
					</label>
					<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Choose the answer that best fits the primary value of your membership site.', 'paid-memberships-pro' ); ?></p>
					<select class="pmpro-wizard__field-block">
						<option><?php esc_html_e( '-- Select --', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Association', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Community', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Courses', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Digital Downloads', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Directory/Profiles', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Physical Products', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Premium Content', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Other', 'paid-memberships-pro' ); ?></option>
					</select>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="checkbox" id="generate_pages" value="1" checked="">
						<label for="generate_pages"><?php esc_html_e( 'Generate the required plugin pages for me.', 'paid-memberships-pro' ); ?></label><br><br>
						<input type="checkbox" id="collect_payments">
						<label for="collect_payments"><?php esc_html_e( 'Will you be collecting payments for your memberships?', 'paid-memberships-pro' ); ?></label><br/>
					</label>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<?php esc_html_e( 'Enter Your Support License Key (optional)', 'paid-memberships-pro' ); ?>
					</label>
					<p class="pmpro-wizard__field-description"><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?> <a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-wizard&utm_campaign=pricing&utm_content=view-plans-pricing" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a></p>
					<input type="text" class="pmpro-wizard__field-block">
				</div>
				<p class="pmpro_wizard__submit">
					<input type="submit" onclick="window.location.href = '/wp-admin/admin.php?page=pmpro-wizard&step=memberships'; " class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" />
				</p>
			</div> <!-- end pmpro-wizard__step-1 -->
		<?php } elseif ( $active_step === 'memberships' ) { ?>
			<div class="pmpro-wizard__step pmpro-wizard__step-2">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Membership Levels', 'paid-memberships-pro' ); ?></h1>
					<p><?php esc_html_e( 'Set up free and paid membership levels from this wizard. You can set up more membership levels with additional settings later.', 'paid-memberships-pro' ); ?></p>
				</div>
				<div class="pmpro-wizard__field">
					<div class="pmpro-wizard__field__checkbox-group">
						<input type="checkbox" id="pmpro-wizard__free-level" value="1">
						<div class="pmpro-wizard__field__checkbox-content">
							<label for="pmpro-wizard__free-level" class="pmpro-wizard__label-block">
								<?php esc_html_e( 'Free Membership', 'paid-memberships-pro' ); ?>
							</label>
							<div>
								<label><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></label>
								<input type="text" />
							</div>						
						</div>
					</div>
				</div>
				<div class="pmpro-wizard__field">
					<div class="pmpro-wizard__field__checkbox-group">
						<input type="checkbox" id="pmpro-wizard__paid-level" value="1">
						<div class="pmpro-wizard__field__checkbox-content">
							<label for="pmpro-wizard__paid-level" class="pmpro-wizard__label-block">
								<?php esc_html_e( 'Paid Membership', 'paid-memberships-pro' ); ?>
							</label>
							<div>
								<label><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></label>
								<input type="text" />
							</div>
							<div>
								<label><?php esc_html_e( 'Fee', 'paid-memberships-pro' ); ?></label>
								<input type="text" placeholder="<?php esc_attr_e( 'Amount (i.e. "10")', 'paid-memberships-pro' ); ?>" />
								<?php esc_html_e( 'every', 'paid-memberships-pro' ); ?>
								<select id="cycle_period" name="cycle_period">
									<?php
										$cycles = array(
											__('Day', 'paid-memberships-pro' ) => 'Day',
											__('Week', 'paid-memberships-pro' ) => 'Week',
											__('Month', 'paid-memberships-pro' ) => 'Month',
											__('Year', 'paid-memberships-pro' ) => 'Year'
										);
										foreach ( $cycles as $name => $value ) { ?>
											<option <?php selected( $value, 'Month' ); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $name ); ?></option>
										<?php }
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="pmpro-wizard__field" style="background:#F7F7F7;">
					<p><img src="<?php echo plugins_url( '/images/lock.svg', __DIR__ ); ?>" style="vertical-align:top"/> Content restriction settings may be set after the setup wizard.</p>
				</div>
				<p class="pmpro_wizard__submit">
					<input type="submit" onclick="window.location.href = '/wp-admin/admin.php?page=pmpro-wizard&step=payments'; " class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" />
				</p>
			</div> <!-- end pmpro-wizard__step-2 -->
		<?php } elseif ( $active_step === 'payments' ) { ?>
			<div class="pmpro-wizard__step pmpro-wizard__step-3">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Payment Settings', 'paid-memberships-pro' ); ?></h1>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<?php esc_html_e( 'Select the currency for your membership site.', 'paid-memberships-pro' ); ?>
					</label>
					<p class="pmpro-wizard__field-description"><?php esc_html_e( 'Your payment gateway can accept many currencies and automatically convert payments for you.', 'paid-memberships-pro' ); ?></p>
					<select name="currency" class="pmpro-wizard__field-block">
					<?php
						global $pmpro_currencies, $pmpro_currency;
						foreach ( $pmpro_currencies as $ccode => $cdescription ) {
							if ( is_array( $cdescription ) ) {
								$cdescription = $cdescription['name'];
							}
						?>
						<option value="<?php echo esc_attr( $ccode ) ?>" <?php selected( $pmpro_currency, $ccode ); ?>><?php echo esc_html( $cdescription ); ?></option>
						<?php
						}
					?>
					</select>
				</div>
				<div class="pmpro-wizard__field">
					<h2 class="pmpro-wizard__section-title"><?php esc_html_e( 'Configure Your Payment Gateway', 'paid-memberships-pro'); ?></h2>
					<label class="pmpro-wizard__label-block">
						<input type="radio" name="gateway" value="stripe">
						<?php esc_html_e( 'Stripe', 'paid-memberships-pro' ); ?>
					</label>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="radio"  name="gateway" value="paypal-express">
						<?php esc_html_e( 'PayPal Express', 'paid-memberships-pro' ); ?>
					</label>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="radio" name="gateway" value="other">
						<?php esc_html_e( 'Other', 'paid-memberships-pro' ); ?>
					</label>
				</div>
				<p class="pmpro_wizard__submit">
					<input type="submit" onclick="window.location.href = '/wp-admin/admin.php?page=pmpro-wizard&step=advanced'; " class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" />
				</p>
			</div> <!-- end pmpro-wizard__step-3 -->
		<?php } elseif ( $active_step == 'advanced' ) { ?>
			<div class="pmpro-wizard__step pmpro-wizard__step-4">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Advanced Settings', 'paid-memberships-pro' ); ?></h1>
					<p><?php esc_html_e( 'Configure advanced settings relating to your membership site. You can configure additional settings later.', 'paid-memberships-pro' ); ?></p>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<?php esc_html_e( 'Filter searches and archives?', 'paid-memberships-pro' ); ?>
					</label>
					<select class="pmpro-wizard__field-block">
						<option><?php esc_html_e( 'No - Non-members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
						<option><?php esc_html_e( 'Yes - Only members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
					</select><br><br>
				<!-- </div>
				<div class="pmpro-wizard__field"> -->
					<label class="pmpro-wizard__label-block">
						<?php esc_html_e( 'Show excerpts to non-members?', 'paid-memberships-pro' ); ?>
					</label>
					<select class="pmpro-wizard__field-block">
						<option>No - Hide excerpts.</option>
						<option>Yes - Show excerpts.</option>
					</select>
				</div>
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="checkbox" value="1" checked="">
						<?php esc_html_e( 'Enable Spam Protection', 'paid-memberships-pro' ); ?>
					</label>
					<p class="pmpro-wizard__field-description">Block IPs from checkout if there are more than 10 failures within 15 minutes.</p>
				</div>
				
				<div class="pmpro-wizard__field">
					<label class="pmpro-wizard__label-block">
						<input type="checkbox" value="1" checked="">
						<?php esc_html_e( 'Enable Tracking', 'paid-memberships-pro' ); ?>
					</label>
					<p class="pmpro-wizard__field-description">Sharing non-sensitive membership site data helps us analyze how our plugin is meeting your needs and identify opportunities to improve</p>
				</div>
				<!-- <div class="pmpro-wizard__field" style="background:#F7F7F7;">
					<p><img src="<?php echo plugins_url( '/images/book-open.svg', __DIR__ ); ?>" style="vertical-align:top"/> You may configure reCAPTCHA and other settings later.</p>
				</div> -->
				<p class="pmpro_wizard__submit">
					<input type="submit" onclick="window.location.href = '/wp-admin/admin.php?page=pmpro-wizard&step=done'; " class="button button-primary button-hero" value="<?php esc_attr_e( 'Submit and Continue', 'paid-memberships-pro' ); ?>" />
				</p>
			</div> <!-- end pmpro-wizard__step-4 -->
		<?php } elseif ( $active_step == 'done' ) { ?>
			<div class="pmpro-wizard__step pmpro-wizard__step-4">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Setup Complete', 'paid-memberships-pro' ); ?></h1>
				</div>
				<div class="pmpro-wizard__field">
					SOMETHING ELSE.
				</div>
				
				<p class="pmpro_wizard__submit">
					<input type="submit" onclick="window.location.href = '/wp-admin/admin.php?page=pmpro-wizard&step=done'; " class="button button-primary button-hero" value="<?php esc_attr_e( 'Complete!', 'paid-memberships-pro' ); ?>" />
				</p>
			</div> <!-- end pmpro-wizard__step-5 -->
		<?php } ?>
		<p class="pmpro-wizard__exit"><a href=""><?php esc_html_e( 'Exit Wizard and Return to Dashboard', 'paid-memberships-pro' ); ?></a></p>
	</div> <!-- end pmpro-wizard__container -->
</div> <!-- end pmpro-wizard -->
<?php
