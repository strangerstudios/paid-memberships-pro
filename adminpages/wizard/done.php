<?php
	$site_type = get_option( 'pmpro_site_type' );

	if ( empty( $site_type ) ) {
		$site_type = 'general';
	}
	// Get Add On recommendations based on site type.
	$addon_cats = pmpro_get_addon_categories();
	if ( ! empty( $addon_cats[$site_type] ) && $addon_cats[$site_type] ) {
		$addon_slug_list = $addon_cats[$site_type];
		$addon_slug_list = array_slice( $addon_slug_list, 0, 4 );
	} else {
		$addon_slug_list = $addon_cats['popular'];
		$addon_slug_list = array_slice( $addon_slug_list, 0, 4 );
	}

	$addon_list = array();
	foreach ( $addon_slug_list as $addon_slug ) {
		$addon = pmpro_getAddonBySlug( $addon_slug );
		if ( ! is_array( $addon ) ) {
			continue;
		}
		$addon_list[] = $addon;
	}

	// Did they choose collect payments? If so, show a nudge to complete the gateway setup.
	$configure_payment = get_option( 'pmpro_wizard_collect_payment' );

	$site_types = pmpro_wizard_get_site_types();
	$site_type_hubs = pmpro_wizard_get_site_type_hubs();
?>
<div class="pmpro-wizard__step pmpro-wizard__step-4">
	<div class="pmpro-wizard__step-header">
		<h2><?php esc_html_e( 'Setup Complete', 'paid-memberships-pro' ); ?></h2>
		<p><strong><?php esc_html_e( 'Congratulations!', 'paid-memberships-pro' ); ?></strong> <a href="<?php echo esc_url( admin_url( '/admin.php?page=pmpro-membershiplevels' ) ); ?>"><?php esc_html_e( 'Your membership site is ready.', 'paid-memberships-pro' ); ?></a></p>
	</div>
	<div class="pmpro-wizard__field"> <!-- Recommended icons -->
		<h3 class="pmpro-wizard__section-title"><?php esc_html_e( "What's next?", 'paid-memberships-pro' ); ?></h3>
		<p>
			<?php
			if ( isset( $site_types[ $site_type ] ) && isset( $site_type_hubs[ $site_type ] ) ) {
				echo sprintf( esc_html__( "In step 1, you chose the %s site type.", 'paid-memberships-pro' ), '<strong>' . esc_html( $site_types[ $site_type ] ) . '</strong>' ) . ' ';
				echo sprintf(
					/* translators: %s: URL to the PMPro use case hub for the chosen site type */
					esc_html__( 'Check out the %s, which guides you through next steps for your unique project.', 'paid-memberships-pro' ),
					'<a href="' . esc_url( $site_type_hubs[ $site_type ] ) . '" target="_blank"><strong>' . esc_html( $site_types[ $site_type ] ) . ' ' . esc_html__( 'hub', 'paid-memberships-pro' ) . '</strong></a>'
				);
			}
			?>
		</p>
		<?php
		if ( ! empty( $addon_list) ) {
			?>
			<p>
				<?php
				esc_html_e( 'Here are some recommended Add Ons for your business.', 'paid-memberships-pro' );
				?>
			</p>
			<div class="pmpro-wizard__addons">
			<?php
				// Get the Add On recommendations.
				foreach( $addon_list as $addon ) {
					// Get the shortened name otherwise set to name.
					if ( ! empty( $addon['ShortName'] ) ) {
						$title = $addon['ShortName'];
					} else {
						$title = str_replace( 'Paid Memberships Pro - ', '', $addon['Title'] );
					}
					$link = $addon['PluginURI'];
					$icon = pmpro_get_addon_icon( $addon['Slug'] );
					if ( $addon['License'] == 'free' ) {
						$license_label = __( 'Free Add On', 'paid-memberships-pro' );
					} elseif( $addon['License'] == 'standard' ) {
						$license_label = __( 'Standard Add On', 'paid-memberships-pro' );
					} elseif( $addon['License'] == 'plus' ) {
						$license_label = __( 'Plus Add On', 'paid-memberships-pro' );
					} elseif( $addon['License'] == 'builder' ) {
						$license_label = __( 'Builder Add On', 'paid-memberships-pro' );
					} elseif( $addon['License'] == 'wordpress.org' ) {
						$license_label = __( 'Free Plugin', 'paid-memberships-pro' );
					} else {
						$license_label = false;
					}
					?>
					<div class="pmpro-wizard__addon">
						<a href="<?php echo esc_url( $link ); ?>" target='_blank' rel='nofollow'>
							<img src="<?php echo esc_url( $icon ); ?>" />
							<div>
								<span><?php echo esc_html( $title ); ?></span>
								<small><?php echo esc_html( $license_label ); ?></small>
							</div>
						</a>
					</div>
					<?php
				}
			?>
			</div> <!-- end .pmpro-wizard__addons -->
			<p class="pmpro-wizard__textbreak"><?php esc_html_e( 'OR', 'paid-memberships-pro' ); ?></p>
			<?php
		}
		?>
		<div class="pmpro-wizard__col">
			<p><span class="pmpro-wizard__subtitle"><?php esc_html_e( 'More functionality', 'paid-memberships-pro' ); ?></span><br>
			<?php esc_html_e( 'Add more features to your membership site.', 'paid-memberships-pro' ); ?></p>
		</div>
		<div class="pmpro-wizard__col">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ); ?>" class="button button-primary button-hero"><?php esc_html_e( 'View available Add Ons', 'paid-memberships-pro' ); ?></a>
		</div>

		<?php if ( $configure_payment ) { ?>
			<div class="pmpro-wizard__col">
				<p><span class="pmpro-wizard__subtitle"><?php esc_html_e( 'Payments', 'paid-memberships-pro' ); ?></span><br>
				<?php esc_html_e( 'Finish configuring your payment gateway.', 'paid-memberships-pro' ); ?></p>
			</div>
			<div class="pmpro-wizard__col">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ); ?>" class="button button-hero"><?php esc_html_e( 'View payment settings', 'paid-memberships-pro' ); ?></a>
			</div>
		<?php } ?>

		<div class="pmpro-wizard__col">
			<p>
				<span class="pmpro-wizard__subtitle"><?php esc_html_e( 'Documentation', 'paid-memberships-pro' ); ?></span><br>
				<?php esc_html_e( 'Not sure where to start? Take a look at our documentation.', 'paid-memberships-pro' ); ?><br />
				<small><?php esc_html_e( 'Free membership account required.', 'paid-memberships-pro' ); ?></small>
			</p>
		</div>
		<div class="pmpro-wizard__col">
			<a aria-label="<?php esc_attr_e( 'View Paid Memberships Pro documentation in a new tab', 'paid-memberships-pro' ); ?>" href="https://www.paidmembershipspro.com/documentation/?utm_source=plugin&utm_medium=setup-wizard&utm_campaign=wizard-done&utm_content=view-docs" target="_blank" class="button button-hero"><?php esc_html_e( 'View docs', 'paid-memberships-pro' ); ?></a>
		</div>
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