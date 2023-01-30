<?php
/**
 * Add meta box to dashboard page.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'pmpro_dashboard_welcome',
		__( 'Welcome to Paid Memberships Pro', 'paid-memberships-pro' ),
		'pmpro_dashboard_welcome_callback',
		'toplevel_page_pmpro-dashboard',
		'normal'
	);
} );

/**
 * Callback function for pmpro_dashboard_welcome meta box.
 */
function pmpro_dashboard_welcome_callback() { ?>
    <div class="pmpro-dashboard-welcome-columns">
        <div class="pmpro-dashboard-welcome-column">
			<?php global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready; ?>
            <h3><?php esc_html_e( 'Initial Setup', 'paid-memberships-pro' ); ?></h3>
            <ul>
				<?php if ( current_user_can( 'pmpro_membershiplevels' ) ) { ?>
                    <li>
						<?php if ( empty( $pmpro_level_ready ) ) { ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels&showpopup=1' ) ); ?>"><i
                                        class="dashicons dashicons-admin-users"></i> <?php esc_html_e( 'Create a Membership Level', 'paid-memberships-pro' ); ?>
                            </a>
						<?php } else { ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) ); ?>"><i
                                        class="dashicons dashicons-admin-users"></i> <?php esc_html_e( 'View Membership Levels', 'paid-memberships-pro' ); ?>
                            </a>
						<?php } ?>
                    </li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
                    <li>
						<?php if ( empty( $pmpro_pages_ready ) ) { ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) ); ?>"><i
                                        class="dashicons dashicons-welcome-add-page"></i> <?php esc_html_e( 'Generate Membership Pages', 'paid-memberships-pro' ); ?>
                            </a>
						<?php } else { ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-pagesettings' ) ); ?>"><i
                                    class="dashicons dashicons-welcome-add-page"></i> <?php esc_html_e( 'Manage Membership Pages', 'paid-memberships-pro' ); ?>
							<?php } ?>
                    </li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_pagesettings' ) ) { ?>
                    <li>
						<?php if ( empty( $pmpro_gateway_ready ) ) { ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ); ?>"><i
                                        class="dashicons dashicons-cart"></i> <?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?>
                            </a>
						<?php } else { ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ); ?>"><i
                                        class="dashicons dashicons-cart"></i> <?php esc_html_e( 'Configure Payment Settings', 'paid-memberships-pro' ); ?>
                            </a>
						<?php } ?>
                    </li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_userfields' ) ) { ?>
                    <li>
                        <a href="<?php echo add_query_arg( array( 'page' => 'pmpro-userfields' ), get_admin_url( null, 'admin.php' ) ); ?>"><i
                                    class="dashicons dashicons-id"></i> <?php echo esc_attr_e( 'Manage User Fields', 'paid-memberships-pro' ); ?>
                        </a>
                    </li>
				<?php } ?>
            </ul>
            <h3><?php esc_html_e( 'Other Settings', 'paid-memberships-pro' ); ?></h3>
            <ul>
				<?php if ( current_user_can( 'pmpro_emailsettings' ) ) { ?>
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailsettings' ) ); ?>"><i
                                    class="dashicons dashicons-email"></i> <?php esc_html_e( 'Confirm Email Settings', 'paid-memberships-pro' ); ?>
                        </a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_emailtemplates' ) ) { ?>
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-emailtemplates' ) ); ?>"><i
                                    class="dashicons dashicons-editor-spellcheck"></i> <?php esc_html_e( 'Customize Email Templates', 'paid-memberships-pro' ); ?>
                        </a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_advancedsettings' ) ) { ?>
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-advancedsettings' ) ); ?>"><i
                                    class="dashicons dashicons-admin-settings"></i> <?php esc_html_e( 'View Advanced Settings', 'paid-memberships-pro' ); ?>
                        </a></li>
				<?php } ?>

				<?php if ( current_user_can( 'pmpro_addons' ) ) { ?>
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ); ?>"><i
                                    class="dashicons dashicons-admin-plugins"></i> <?php esc_html_e( 'Explore Add Ons for Additional Features', 'paid-memberships-pro' ); ?>
                        </a></li>
				<?php } ?>
            </ul>
            <hr/>
            <p class="text-center">
				<?php echo esc_html( __( 'For guidance as your begin these steps,', 'paid-memberships-pro' ) ); ?>
                <a href="https://www.paidmembershipspro.com/documentation/initial-plugin-setup/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=documentation&utm_content=initial-plugin-setup"
                   target="_blank"
                   rel="noopener noreferrer"><?php esc_html_e( 'view the Initial Setup Video and Docs.', 'paid-memberships-pro' ); ?></a>
            </p>
        </div> <!-- end pmpro-dashboard-welcome-column -->
        <div class="pmpro-dashboard-welcome-column">
            <h3><?php esc_html_e( 'Support License', 'paid-memberships-pro' ); ?></h3>
			<?php
			// Get saved license.
			$key                 = get_option( 'pmpro_license_key', '' );
			$pmpro_license_check = get_option( 'pmpro_license_check', array( 'license' => false, 'enddate' => 0 ) );
			?>
			<?php if ( ! pmpro_license_isValid() && empty( $key ) ) { ?>
                <p class="pmpro_message pmpro_error">
                    <strong><?php esc_html_e( 'No support license key found.', 'paid-memberships-pro' ); ?></strong><br/>
					<?php printf( __( '<a href="%s">Enter your key here &raquo;</a>', 'paid-memberships-pro' ), admin_url( 'admin.php?page=pmpro-license' ) ); ?>
                </p>
			<?php } elseif ( ! pmpro_license_isValid() ) { ?>
                <p class="pmpro_message pmpro_alert">
                <strong><?php esc_html_e( 'Your license is invalid or expired.', 'paid-memberships-pro' ); ?></strong>
                <br/>
				<?php printf( __( '<a href="%s">View your membership account</a> to verify your license key.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/login/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-dashboard%26utm_campaign%3Dmembership-account%26utm_content%3Dverify-license-key' ); ?>
			<?php } elseif ( pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
                <p class="pmpro_message pmpro_alert"><?php printf( __( 'Your <strong>%1$s</strong> key is active. %1$s accounts include access to documentation and free downloads.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ); ?></p>
			<?php } else { ?>
                <p class="pmpro_message pmpro_success"><?php printf( __( '<strong>Thank you!</strong> A valid <strong>%s</strong> license key has been used to activate your support license on this site.', 'paid-memberships-pro' ), ucwords( $pmpro_license_check['license'] ) ); ?></p>
			<?php } ?>

			<?php if ( ! pmpro_license_isValid() || pmpro_license_isValid() && ! pmpro_license_isValid( $key, pmpro_license_get_premium_types() ) ) { ?>
            <p><?php esc_html_e( 'An annual support license is recommended for websites running Paid Memberships Pro.', 'paid-memberships-pro' ); ?></p>
            <p>
                <a href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-dashboard&utm_campaign=pricing&utm_content=upgrade"
                   target="_blank" rel="noopener noreferrer"
                   class="button button-primary button-hero"><?php esc_html_e( 'View Plans and Pricing', 'paid-memberships-pro' ); ?></a>
				<?php } ?>
            <hr/>
            <p><?php echo wp_kses_post( sprintf( __( 'Paid Memberships Pro and our Add Ons are distributed under the <a target="_blank" href="%s">GPLv2 license</a>. This means, among other things, that you may use the software on this site or any other site free of charge.', 'paid-memberships-pro' ), 'http://www.gnu.org/licenses/gpl-2.0.html' ) ); ?></p>
        </div> <!-- end pmpro-dashboard-welcome-column -->
        <div class="pmpro-dashboard-welcome-column">
            <h3><?php esc_html_e( 'Get Involved', 'paid-memberships-pro' ); ?></h3>
            <p><?php esc_html_e( 'There are many ways you can help support Paid Memberships Pro.', 'paid-memberships-pro' ); ?></p>
            <p><?php esc_html_e( 'Get involved with our plugin development via GitHub.', 'paid-memberships-pro' ); ?> <a
                        href="https://github.com/strangerstudios/paid-memberships-pro"
                        target="_blank"><?php esc_html_e( 'View on GitHub', 'paid-memberships-pro' ); ?></a></p>
            <ul>
                <li><a href="https://www.youtube.com/channel/UCFtMIeYJ4_YVidi1aq9kl5g/" target="_blank"><i
                                class="dashicons dashicons-format-video"></i> <?php esc_html_e( 'Subscribe to our YouTube Channel.', 'paid-memberships-pro' ); ?>
                    </a></li>
                <li><a href="https://www.facebook.com/PaidMembershipsPro" target="_blank"><i
                                class="dashicons dashicons-facebook"></i> <?php esc_html_e( 'Follow us on Facebook.', 'paid-memberships-pro' ); ?>
                    </a></li>
                <li><a href="https://twitter.com/pmproplugin" target="_blank"><i
                                class="dashicons dashicons-twitter"></i> <?php esc_html_e( 'Follow @pmproplugin on Twitter.', 'paid-memberships-pro' ); ?>
                    </a></li>
                <li><a href="https://wordpress.org/plugins/paid-memberships-pro/#reviews" target="_blank"><i
                                class="dashicons dashicons-wordpress"></i> <?php esc_html_e( 'Share an honest review at WordPress.org.', 'paid-memberships-pro' ); ?>
                    </a></li>
            </ul>
            <hr/>
            <p><?php esc_html_e( 'Help translate Paid Memberships Pro into your language.', 'paid-memberships-pro' ); ?>
                <a href="https://translate.wordpress.org/projects/wp-plugins/paid-memberships-pro"
                   target="_blank"><?php esc_html_e( 'Translation Dashboard', 'paid-memberships-pro' ); ?></a></p>
        </div> <!-- end pmpro-dashboard-welcome-column -->
    </div> <!-- end pmpro-dashboard-welcome-columns -->
	<?php
}
