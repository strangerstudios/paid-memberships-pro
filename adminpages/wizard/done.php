<?php
	$site_type = get_option( 'pmpro_site_type', true );
	// Get Add On recommendations based on site type.
?>
<div class="pmpro-wizard__step pmpro-wizard__step-4">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Setup Complete', 'paid-memberships-pro' ); ?></h1>
					<p><strong>Congratulations!</strong> Your membership site is ready.</p>
				</div>
				<div class="pmpro-wizard__field"> <!-- Recommended icons -->
						<h1>What's next?</h1>
						<p><?php _e( sprintf( "You indicated you're building a %s membership site. Here are some recommended Add Ons for your business.", "<strong>$site_type</strong>" ), 'paid-memberships-pro' ); ?></p>
							<div style="text-align:center;">
							<div class="pmpro-wizard__col3">
								<p><img src="<?php echo plugins_url( 'images/add-ons/pmpro-nav-menus.png', dirname( __DIR__ ) ); ?>" width="70%"/><br>
								<strong>Nav Menu</strong>
								</p>
							</div>
							<div class="pmpro-wizard__col3">
								<p><img src="<?php echo plugins_url( 'images/add-ons/pmpro-network.png', dirname( __DIR__ ) ); ?>" width="70%"/><br>
								<strong>Network Membership</strong>
								</p>
							</div>
							<div class="pmpro-wizard__col3">
								<p><img src="<?php echo plugins_url( 'images/add-ons/pmpro-membership-manager-role.png', dirname( __DIR__ ) ); ?>" width="70%"/><br>
								<strong>Membership Manager</strong>
								</p>
							</div>	
					</div> <!-- end of center alignment -->	
					<p class="pmpro-wizard__textbreak"><?php esc_html_e( 'OR', 'paid-memberships-pro' ); ?></p>
					<div class="pmpro-wizard__col">
						<p><span class="pmpro-wizard__subtitle"><?php esc_html_e( 'More functionality', 'paid-memberships-pro' ); ?></span><br>
						<?php esc_html_e( 'Add more features to your membership site.', 'paid-memberships-pro' ); ?></p>
					</div>
					<div class="pmpro-wizard__col">
						<a href="#" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'View available Add Ons', 'paid-memberships-pro' ); ?></a>
					</div>

					<div class="pmpro-wizard__col">
						<p><span class="pmpro-wizard__subtitle"><?php esc_html_e( 'Documentation', 'paid-memberships-pro' ); ?></span><br>
						<?php esc_html_e( 'Not sure where to start, take a look at our documentation.', 'paid-memberships-pro' ); ?></p>
					</div>
					<div class="pmpro-wizard__col">
						<a href="https://www.paidmembershipspro.com/documentation/" target="_blank" class="button button-hero"><?php esc_html_e( 'View docs', 'paid-memberships-pro' ); ?></a>
					</div>
					<p style="text-align:center;"><?php _e( sprintf( "Need Help getting started? %s", "<a href='https://www.paidmembershipspro.com/documentation/initial-plugin-setup/' target='_blank'>" . esc_html__( 'click here', 'paid-memberships-pro' ) . "</a>"), 'paid-memberships-pro' ); ?></p>
				</div>
				<script>
					jQuery(document).ready(function(){
						const run_confetti = () => {
						setTimeout(function() {
							confetti.start()
						}, 1000); //start after 1 second.
						setTimeout(function() {
							confetti.stop()
						}, 4000); //Stop after 4 seconds.
					};
						run_confetti();
					});
				</script>
				
			</div> <!-- end pmpro-wizard__step-5 -->