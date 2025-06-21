<?php
/**
 * Setup Wizard containing file that handles logic and loading of templates.
 */
if ( empty( $_REQUEST['step'] ) ) {
	$previous_step = get_option( 'pmpro_wizard_step' );
	if ( ! empty( $previous_step ) ) {
		$active_step = sanitize_text_field( $previous_step );
	} else {
		$active_step = 'general';
	}
} elseif ( ! empty( $_REQUEST['step'] ) ) {
	$active_step = sanitize_text_field( $_REQUEST['step'] );
} else {
	$active_step = 'general';
}
?>
<div class="pmpro-wizard">
	<div class="pmpro-wizard__background"></div>
	<div class="pmpro-wizard__header">
		<h1><a class="pmpro_logo" target="_blank" rel="noopener noreferrer" href="https://www.paidmembershipspro.com/?utm_source=plugin&utm_medium=pmpro-admin-header&utm_campaign=homepage"><img src="<?php echo esc_url( PMPRO_URL . '/images/Paid-Memberships-Pro.png' ); ?>" width="350" height="75" border="0" alt="<?php esc_attr_e( 'Paid Memberships Pro', 'paid-memberships-pro' ); ?>" /></a></h1>
		<nav class="pmpro-stepper">
			<ul class="pmpro-stepper__steps">
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
						<li class="<?php echo esc_attr( $class ); ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-wizard&step=' . $setup_step ) );?>">
								<div class="pmpro-stepper__step-icon">
									<span class="pmpro-stepper__step-number">
										<span class="screen-reader-text"><?php esc_html_e( 'Step', 'paid-memberships-pro' ); ?></span>
										<?php echo esc_html( $count ); ?>
									</span>
								</div>
								<span class="pmpro-stepper__step-label"<?php echo ( in_array( 'is-active', $classes ) ) ? ' aria-label="' . sprintf( esc_html__( '%s Active Step', 'paid-memberships-pro' ), esc_html( $name ) ) . '"' : ''; ?>>
									<?php echo esc_html( $name ); ?>
								</span>
							</a>
						</li>
						<?php
					}
				?>
			</ul>
			<div class="pmpro-stepper__step-divider"></div>
		</nav>
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
		<p class="pmpro-wizard__exit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-dashboard' ) ); ?>"><?php esc_html_e( 'Exit Wizard and Return to Dashboard', 'paid-memberships-pro' ); ?></a></p>
	</div> <!-- end pmpro-wizard__container -->
</div> <!-- end pmpro-wizard -->
<?php
