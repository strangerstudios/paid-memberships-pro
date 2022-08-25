<div class="pmpro-wizard__step pmpro-wizard__step-4">
				<div class="pmpro-wizard__step-header">
					<h1><?php esc_html_e( 'Setup Complete', 'paid-memberships-pro' ); ?></h1>
					<p><strong>Congratulations!</strong> Your membership site is ready.</p>
				</div>
				<div class="pmpro-wizard__field"> <!-- Recommended icons -->
						<h1>What's next?</h1>
						<p>You indicated you’re building a [category] membership site. Here are some recommended Add Ons for your business</p>
							<div style="text-align:center;">
							<div class="pmpro-wizard__col3">
								<p><img src="<?php echo plugins_url( 'images/add-ons/pmpro-nav-menus.png', __DIR__ ); ?>" width="70%"/><br>
								<strong>Nav Menu</strong>
								</p>
							</div>
							<div class="pmpro-wizard__col3">
								<p><img src="<?php echo plugins_url( 'images/add-ons/pmpro-network.png', __DIR__ ); ?>" width="70%"/><br>
								<strong>Network Membership</strong>
								</p>
							</div>
							<div class="pmpro-wizard__col3">
								<p><img src="<?php echo plugins_url( 'images/add-ons/pmpro-membership-manager-role.png', __DIR__ ); ?>" width="70%"/><br>
								<strong>Membership Manager</strong>
								</p>
							</div>	
					</div> <!-- end of center alignment -->	
					<p class="pmpro-wizard__textbreak">OR</p>
					<div class="pmpro-wizard__col">
						<p><span class="pmpro-wizard__subtitle">More functionality</span><br>
						Add more features to your membership site.</p>
					</div>
					<div class="pmpro-wizard__col">
						<a href="#" class="button button-primary button-hero">View available Add Ons</a>
					</div>

					<div class="pmpro-wizard__col">
						<p><span class="pmpro-wizard__subtitle">Documentation</span><br>
						Not sure where to start, take a look at our documentation.</p>
					</div>
					<div class="pmpro-wizard__col">
						<a href="#" class="button button-hero">View docs</a>
					</div>
					<p style="text-align:center;">Need Help getting started? <a href='#'>click here</a></p>
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