<?php
/**
 * Welcome Meta Box for the PMPro Dashboard.
 *
 * @package PaidMembershipsPro
 * @subpackage AdminPages
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function pmpro_dashboard_welcome_callback() {
	?>
	<div class="pmpro-dashboard-welcome-columns">
		<div class="pmpro-dashboard-welcome-column">
			<br />
			<iframe width="560" height="315" src="https://www.youtube.com/embed/IZpS9Mx76mw?si=A6OKdMHT6eBRIs9y" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
			<p>
				<?php echo esc_html( __( 'For more guidance as your begin these steps:', 'paid-memberships-pro' ) ); ?>
				<a href="https://www.paidmembershipspro.com/documentation/initial-plugin-setup/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=documentation&utm_content=initial-plugin-setup" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'view the Initial Setup Guide and Docs.', 'paid-memberships-pro' ); ?></a>
			</p>
		</div>
		<div class="pmpro-dashboard-welcome-column">
			<?php global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready; ?>
			<h3><?php esc_html_e( 'Initial Setup', 'paid-memberships-pro' ); ?></h3>
			<ul>
				<?php if ( current_user_can( 'pmpro_membershiplevels' ) ) { ?>
					<li>
						<?php if ( empty( $pmpro_level_ready ) ) { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels&showpopup=1' ) ); ?>"><i class="dashicons dashicons-admin-users"></i> <?php esc_html_e( 'Create a Membership Level', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) ); ?>"><i class="dashicons dashicons-admin-users"></i> <?php esc_html_e( 'View Membership Levels', 'paid-memberships-pro' ); ?></a>
						<?php } ?>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
					<li>
						<?php if ( empty( $pmpro_pages_ready ) ) { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) ); ?>"><i class="dashicons dashicons-welcome-add-page"></i> <?php esc_html_e( 'Generate Membership Pages', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) ); ?>"><i class="dashicons dashicons-welcome-add-page"></i> <?php esc_html_e( 'Manage Membership Pages', 'paid-memberships-pro' ); ?>
						<?php } ?>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
					<li>
						<?php if ( empty( $pmpro_gateway_ready ) ) { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ); ?>"><i class="dashicons dashicons-cart"></i> <?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ); ?>"><i class="dashicons dashicons-cart"></i> <?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?></a>
						<?php } ?>
					</li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_userfields' ) ) { ?>
				<li>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-userfields' ), get_admin_url( null, 'admin.php' ) ) ); ?>"><i class="dashicons dashicons-id"></i> <?php esc_attr_e( 'Manage User Fields', 'paid-memberships-pro' ); ?></a>
				</li>
				<?php } ?>
			</ul>
			<h3><?php esc_html_e( 'Other Settings', 'paid-memberships-pro' ); ?></h3>
			<ul>
				<?php if ( current_user_can( 'pmpro_emailsettings' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailsettings' ) ); ?>"><i class="dashicons dashicons-email"></i> <?php esc_html_e( 'Confirm Email Settings', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_emailtemplates' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailtemplates' ) ); ?>"><i class="dashicons dashicons-editor-spellcheck"></i> <?php esc_html_e( 'Customize Email Templates', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_designsettings' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-designsettings' ) ); ?>"><i class="dashicons dashicons-art"></i> <?php esc_html_e( 'View Design Settings', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_advancedsettings' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-advancedsettings' ) ); ?>"><i class="dashicons dashicons-admin-settings"></i> <?php esc_html_e( 'View Advanced Settings', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_addons' ) ) { ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ); ?>"><i class="dashicons dashicons-admin-plugins"></i> <?php esc_html_e( 'Explore Add Ons for Additional Features', 'paid-memberships-pro' ); ?></a></li>
				<?php } ?>
			</ul>
		</div> <!-- end pmpro-dashboard-welcome-column -->
	</div> <!-- end pmpro-dashboard-welcome-columns -->
	<?php
}