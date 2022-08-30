<?php
	$site_type = get_option( 'pmpro_site_type', true );
	// Get Add On recommendations based on site type.
	$addon_cats = pmpro_get_addon_categories();
	
	if ( $addon_cats[$site_type] ) {
		$addon_list = $addon_cats[$site_type];
	} else {
		$addon_list = $addon_cats['popular'];
	}

	$test = pmpro_getAddonBySlug( 'pmpro-approvals');

	// var_dump( $test );
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
					<?php
						// Get some Add On recommendations and only show 3.
						$random_addon = array_rand( $addon_list, 3 );
						foreach( $random_addon as $key ) {
							$addon_slug = $addon_list[$key];
							$addon = pmpro_getAddonBySlug( $addon_slug );

							$title = str_replace( 'Paid Memberships Pro - ', '', $addon['Title'] );
							$link = $addon['PluginURI'];
							$icon = pmpro_get_addon_icon( $addon_slug );
							?>
							<div class="pmpro-wizard__col3">
							<p><a href="<?php echo esc_url( $link ); ?>" target='_blank' rel='nofollow'><img src="<?php echo esc_url( $icon ); ?>" width="70%"/><br>
							<strong><?php esc_html_e( $title ); ?></strong></a>
							</p>
						</div>
							<?php
						}
					?>
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