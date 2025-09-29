<?php
/**
 * Paid Memberships Pro Welcome Meta Box
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 * @since 3.5
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_dashboard_welcome_callback() {
	?>
	<div class="pmpro-dashboard-welcome-columns">
		<div class="pmpro-dashboard-welcome-column">
			<iframe width="560" height="315" src="https://www.youtube.com/embed/IZpS9Mx76mw?si=A6OKdMHT6eBRIs9y" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
			<p>
				<?php echo esc_html( __( 'For more guidance as you begin these steps,', 'paid-memberships-pro' ) ); ?>
				<a href="https://www.paidmembershipspro.com/documentation/initial-plugin-setup/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=documentation&utm_content=initial-plugin-setup" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'view the Initial Setup Guide and Docs.', 'paid-memberships-pro' ); ?></a>
			</p>
		</div>
		<div class="pmpro-dashboard-welcome-column">
			<?php global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready; ?>
			<h3><?php esc_html_e( 'Initial Setup', 'paid-memberships-pro' ); ?></h3>
			<ul class="pmpro-dashboard-welcome-steps">
				<?php if ( current_user_can( 'pmpro_membershiplevels' ) ) { ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' . ( empty( $pmpro_level_ready ) ? '&showpopup=1' : '' ) ) ); ?>">
							<i class="dashicons <?php echo empty( $pmpro_level_ready ) ? 'dashicons-marker' : 'dashicons-yes-alt'; ?>"></i>
							<span>
								<?php
								echo empty( $pmpro_level_ready )
									? esc_html__( 'Create a Membership Level', 'paid-memberships-pro' )
									: esc_html__( 'View Membership Levels', 'paid-memberships-pro' );
								?>
							</span>
						</a>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) ); ?>">
							<i class="dashicons <?php echo empty( $pmpro_pages_ready ) ? 'dashicons-marker' : 'dashicons-yes-alt'; ?>"></i>
							<span>
								<?php
								echo empty( $pmpro_pages_ready )
									? esc_html__( 'Generate Membership Pages', 'paid-memberships-pro' )
									: esc_html__( 'Manage Membership Pages', 'paid-memberships-pro' );
								?>
							</span>
						</a>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_paymentsettings' ) ) { ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ); ?>">
							<i class="dashicons <?php echo ( empty( $pmpro_gateway_ready ) || empty( $pmpro_level_ready ) ) ? 'dashicons-marker' : 'dashicons-yes-alt'; ?>"></i>
							<span><?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?></span>
						</a>
					</li>
				<?php } ?>
			</ul>
			<h3><?php esc_html_e( 'Other Settings', 'paid-memberships-pro' ); ?></h3>
			<ul>
				<?php if ( current_user_can( 'pmpro_userfields' ) ) { ?>
				<li>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-userfields' ), get_admin_url( null, 'admin.php' ) ) ); ?>"><?php esc_attr_e( 'Manage User Fields', 'paid-memberships-pro' ); ?></a>
				</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_emailtemplates' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailtemplates' ) ); ?>"><?php esc_html_e( 'Customize Email Templates', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_designsettings' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-designsettings' ) ); ?>"><?php esc_html_e( 'View Design Settings', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_addons' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ); ?>"><?php esc_html_e( 'Explore Add Ons for Additional Features', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>
			</ul>
		</div> <!-- end pmpro-dashboard-welcome-column -->
		<div class="pmpro-dashboard-welcome-column">
			<div class="pmpro_box">
				<?php
					// Get the site type and hub URL.
					$site_type      = get_option( 'pmpro_site_type' );
					$site_types     = pmpro_get_site_types();
					$site_type_hubs = pmpro_get_site_type_hubs();

				if ( empty( $site_type ) ) {
					$site_type = 'general';
				}

					// Initialize the site type hub link.
					$site_type_hub_link = '';

				if ( isset( $site_types[ $site_type ] ) && isset( $site_type_hubs[ $site_type ] ) ) {
					// Add UTM parameters to the site type hub link.
					$site_type_hubs[ $site_type ] = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'dashboard',
							'utm_campaign' => 'welcome',
							'utm_content'  => 'use-case-hub',
						),
						$site_type_hubs[ $site_type ]
					);

					// Add a redirect to the login page with the hub link.
					$site_type_hub_link = add_query_arg(
						array(
							'redirect_to' => urlencode( $site_type_hubs[ $site_type ] ),
						),
						'https://www.paidmembershipspro.com/login/'
					);
				}

				if ( $site_type_hub_link ) {
					?>
						<h3><?php printf( esc_html__( 'Use Case: %s', 'paid-memberships-pro' ), esc_html( $site_types[ $site_type ] ) ); ?></h3>
						<p><?php printf( esc_html__( 'We designed the %s Hub&trade; as a complete resource to help you start, launch, and grow your membership site with Paid Memberships Pro.', 'paid-memberships-pro' ), esc_html( $site_types[ $site_type ] ) ); ?></p>
						<p><a class="button button-primary button-hero" href="<?php echo esc_url( $site_type_hub_link ); ?>" target="_blank" rel="noopener noreferrer"><?php printf( esc_html__( 'Visit the %s Hub&trade;', 'paid-memberships-pro' ), esc_html( $site_types[ $site_type ] ) ); ?></a></p>
						<p><?php esc_html_e( 'You can adjust your site type any time in Advanced Settings.', 'paid-memberships-pro' ); ?></p>
						<?php
				} else {
					?>
						<h3><?php esc_html_e( 'What are you building?', 'paid-memberships-pro' ); ?></h3>
						<p><?php esc_html_e( 'Our Use Case Hubs are designed to jumpstart your membership site success. Get actionable steps for your specific type of membership site, like Associations, Courses, or Communities.', 'paid-memberships-pro' ); ?></p>
						<p>
							<a class="button button-primary button-hero" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-advancedsettings#other-settings' ), get_admin_url( null, 'admin.php' ) ) ); ?>">
								<?php esc_html_e( 'Choose a Site Type', 'paid-memberships-pro' ); ?>
							</a>
						</p>
						<p><?php esc_html_e( 'You can adjust your site type any time in Advanced Settings.', 'paid-memberships-pro' ); ?></p>
						<?php
				}
				?>
			</div> <!-- end pmpro_box -->
		</div> <!-- end pmpro-dashboard-welcome-column -->
	</div> <!-- end pmpro-dashboard-welcome-columns -->
	<?php
}