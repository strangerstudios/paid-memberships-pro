<?php
/**
 * Setup Wizard containing file that handles logic and loading of templates.
 */
if ( empty( $_REQUEST['step'] ) ) {
	$previous_step = pmpro_getOption( 'wizard_step' );
	$active_step = sanitize_text_field( $previous_step );
} elseif ( ! empty( $_REQUEST['step'] ) ) {
	$active_step = sanitize_text_field( $_REQUEST['step'] );
} else {
	$active_step = 'general';
}

/**
 * Helper function to get all site types and their human-readable labels.
 * Can only be used within the wizard.
 *
 * @since TBD.
 *
 * @return array
 */
function pmpro_wizard_get_site_types() {
	// These values will all be escaped when displayed.
	return array(
		'association'       => __( 'Association', 'paid-memberships-pro' ),
		'community'         => __( 'Community', 'paid-memberships-pro' ),
		'courses'           => __( 'Courses', 'paid-memberships-pro' ),
		'digital_downloads' => __( 'Digital Downloads', 'paid-memberships-pro' ),
		'directory'         => __( 'Directory/Profiles', 'paid-memberships-pro' ),
		'physical_products' => __( 'Physical Products', 'paid-memberships-pro' ),
		'premium_content'   => __( 'Premium Content', 'paid-memberships-pro' ),
		'other'             => __( 'Other', 'paid-memberships-pro' ),
	);
}

?>
<div class="pmpro-wizard">
	<div class="pmpro-wizard__background"></div>
	<div class="pmpro-wizard__header">
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=homepage"><img src="<?php echo esc_url( PMPRO_URL . '/images/Paid-Memberships-Pro.png' ); ?>" width="350" height="75" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro-stepper">
			<div class="pmpro-stepper__steps">
				<?php
					$setup_steps = array(
						'general' => __( 'General Info', 'paid-memberships-pro' ),
						'payments' => __( 'Payments', 'paid-memberships-pro' ),
						'memberships' => __( 'Memberships', 'paid-memberships-pro' ),
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
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=' . $setup_step ) );?>"><?php echo esc_html( $name ); ?></a>								
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
		<?php
			// Load the wizard page template based on the current step.
			if ( ! empty( $active_step ) && $setup_steps[$active_step] ) {
				include $active_step . '.php';
			} else {
				include 'general.php';
			}
			
		?>
		<p class="pmpro-wizard__exit"><a href="<?php echo esc_url( admin_url( '/admin.php?page=pmpro-dashboard' ) ); ?>"><?php esc_html_e( 'Exit Wizard and Return to Dashboard', 'paid-memberships-pro' ); ?></a></p>
	</div> <!-- end pmpro-wizard__container -->
</div> <!-- end pmpro-wizard -->
<?php
