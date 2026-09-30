<?php
	require_once(dirname(__FILE__) . "/functions.php");

	if(isset($_REQUEST['page']))
		$view = sanitize_text_field($_REQUEST['page']);
	else
		$view = "";

	if ( ! empty( $_REQUEST['edit'] ) ) {
		$edit_level = intval( $_REQUEST['edit'] );
	} else {
		$edit_level = false;
	}

	global $pmpro_ready, $msg, $msgt;
	///$pmpro_ready = pmpro_is_ready();
	if(!$pmpro_ready)
	{
		global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready;		

		if(empty($msg))
			$msg = -1;
		if(empty($pmpro_level_ready) && empty($edit_level) && $view != "pmpro-membershiplevels")
			$msgt .= " <a href=\"" . admin_url('admin.php?page=pmpro-membershiplevels&edit=-1') . "\">" . __("Add a membership level to get started.", 'paid-memberships-pro' ) . "</a>";
		elseif($pmpro_level_ready && !$pmpro_pages_ready && $view != "pmpro-pagesettings")
			$msgt .= " <strong>" . __( 'Next step:', 'paid-memberships-pro' ) . "</strong> <a href=\"" . admin_url('admin.php?page=pmpro-pagesettings') . "\">" . __("Set up the membership pages", 'paid-memberships-pro' ) . "</a>.";
		elseif($pmpro_level_ready && $pmpro_pages_ready && !$pmpro_gateway_ready && $view != "pmpro-paymentsettings" && ! pmpro_onlyFreeLevels())
			$msgt .= " <strong>" . __( 'Next step:', 'paid-memberships-pro' ) . "</strong> <a href=\"" . admin_url('admin.php?page=pmpro-paymentsettings') . "\">" . __("Set up your payment gateway", 'paid-memberships-pro' ) . "</a>.";

		if(empty($msgt))
			$msg = false;
	}

	//check level compatibility
	if(!pmpro_checkLevelForStripeCompatibility())
	{
		$msg = -1;
		$msgt = __("The billing details for some of your membership levels is not supported by Stripe.", 'paid-memberships-pro' );
		if($view == "pmpro-membershiplevels" && !empty($edit_level) && $edit_level > 0)
		{
			if(!pmpro_checkLevelForStripeCompatibility($edit_level))
			{
				global $pmpro_stripe_error;
				$pmpro_stripe_error = true;
				$msg = -1;
				$msgt = __("The billing details for this level are not supported by Stripe. Please review the notes in the Billing Details section below.", 'paid-memberships-pro' );
			}
		}
		elseif($view == "pmpro-membershiplevels")
			$msgt .= " " . __("The levels with issues are highlighted below.", 'paid-memberships-pro' );
		else
			$msgt .= " <a href=\"" . admin_url('admin.php?page=pmpro-membershiplevels') . "\">" . __("Please edit your levels", 'paid-memberships-pro' ) . "</a>.";
	}

	if(!pmpro_checkLevelForPayflowCompatibility())
	{
		$msg = -1;
		$msgt = __("The billing details for some of your membership levels is not supported by Payflow.", 'paid-memberships-pro' );
		if($view == "pmpro-membershiplevels" && !empty($edit_level) && $edit_level > 0)
		{
			if(!pmpro_checkLevelForPayflowCompatibility($edit_level))
			{
				global $pmpro_payflow_error;
				$pmpro_payflow_error = true;
				$msg = -1;
				$msgt = __("The billing details for this level are not supported by Payflow. Please review the notes in the Billing Details section below.", 'paid-memberships-pro' );
			}
		}
		elseif($view == "pmpro-membershiplevels")
			$msgt .= " " . __("The levels with issues are highlighted below.", 'paid-memberships-pro' );
		else
			$msgt .= " <a href=\"" . admin_url('admin.php?page=pmpro-membershiplevels') . "\">" . __("Please edit your levels", 'paid-memberships-pro' ) . "</a>.";
	}

	if(!pmpro_checkLevelForBraintreeCompatibility())
	{
		global $pmpro_braintree_error;

		if ( false == $pmpro_braintree_error ) {
			$msg  = - 1;
			$msgt = __( "The billing details for some of your membership levels is not supported by Braintree.", 'paid-memberships-pro' );
		}
		if($view == "pmpro-membershiplevels" && !empty($edit_level) && $edit_level > 0)
		{
			if(!pmpro_checkLevelForBraintreeCompatibility($edit_level))
			{

				// Don't overwrite existing messages
				if ( false == $pmpro_braintree_error  ) {
					$pmpro_braintree_error = true;
					$msg                   = - 1;
					$msgt                  = __( "The billing details for this level are not supported by Braintree. Please review the notes in the Billing Details section below.", 'paid-memberships-pro' );
				}
			}
		}
		elseif($view == "pmpro-membershiplevels")
			$msgt .= " " . __("The levels with issues are highlighted below.", 'paid-memberships-pro' );
		else {
			if ( false === $pmpro_braintree_error  ) {
				$msgt .= " <a href=\"" . admin_url( 'admin.php?page=pmpro-membershiplevels' ) . "\">" . __( "Please edit your levels", 'paid-memberships-pro' ) . "</a>.";
			}
		}
	}

	if(!pmpro_checkLevelForTwoCheckoutCompatibility())
	{
		$msg = -1;
		$msgt = __("The billing details for some of your membership levels is not supported by TwoCheckout.", 'paid-memberships-pro' );
		if($view == "pmpro-membershiplevels" && !empty($edit_level) && $edit_level > 0)
		{
			if(!pmpro_checkLevelForTwoCheckoutCompatibility($edit_level))
			{
				global $pmpro_twocheckout_error;
				$pmpro_twocheckout_error = true;

				$msg = -1;
				$msgt = __("The billing details for this level are not supported by 2Checkout. Please review the notes in the Billing Details section below.", 'paid-memberships-pro' );
			}
		}
		elseif($view == "pmpro-membershiplevels")
			$msgt .= " " . __("The levels with issues are highlighted below.", 'paid-memberships-pro' );
		else
			$msgt .= " <a href=\"" . admin_url('admin.php?page=pmpro-membershiplevels') . "\">" . __("Please edit your levels", 'paid-memberships-pro' ) . "</a>.";
	}

	if ( ! pmpro_check_discount_code_for_gateway_compatibility() ) {
		$msg = -1;
		$msgt = __( 'The billing details for some of your discount codes are not supported by your gateway.', 'paid-memberships-pro' );
		if ( $view == 'pmpro-discountcodes' && ! empty($edit_level) && $edit_level > 0 ) {
			if ( ! pmpro_check_discount_code_for_gateway_compatibility( $edit_level ) ) {
				$msg = -1;
				$msgt = __( 'The billing details for this discount code are not supported by your gateway.', 'paid-memberships-pro' );
			}
		} elseif ( $view == 'pmpro-discountcodes' ) {
			$msg = -1;
			$msgt .= " " . __("The discount codes with issues are highlighted below.", 'paid-memberships-pro' );
		} else {
			$msgt .= " <a href=\"" . admin_url('admin.php?page=pmpro-discountcodes') . "\">" . __("Please edit your discount codes", 'paid-memberships-pro' ) . "</a>.";

		}
	}

	$gateway = get_option( 'pmpro_gateway' );
	if($gateway == "stripe" && version_compare( PHP_VERSION, '5.3.29', '>=' ) ) {
		PMProGateway_stripe::dependencies();
	} elseif($gateway == "braintree" && version_compare( PHP_VERSION, '5.4.45', '>=' ) ) {
		PMProGateway_braintree::dependencies();
	} elseif($gateway == "stripe" && version_compare( PHP_VERSION, '5.3.29', '<' ) ) {
        $msg = -1;
        $msgt = sprintf(__("The Stripe Gateway requires PHP 5.3.29 or greater. We recommend upgrading to PHP %s or greater. Ask your host to upgrade.", "paid-memberships-pro" ), PMPRO_MIN_PHP_VERSION );
    } elseif($gateway == "braintree" && version_compare( PHP_VERSION, '5.4.45', '<' ) ) {
        $msg = -1;
        $msgt = sprintf(__("The Braintree Gateway requires PHP 5.4.45 or greater. We recommend upgrading to PHP %s or greater. Ask your host to upgrade.", "paid-memberships-pro" ), PMPRO_MIN_PHP_VERSION );
    }

	//if no errors yet, let's check and bug them if < our PMPRO_MIN_PHP_VERSION
	if( empty($msgt) && version_compare( PHP_VERSION, PMPRO_MIN_PHP_VERSION, '<' ) ) {
		$msg = 1;
		$msgt = sprintf(__("We recommend upgrading to PHP %s or greater. Ask your host to upgrade.", "paid-memberships-pro" ), PMPRO_MIN_PHP_VERSION );
	}

	// Show the contextual messages on our admin pages.
	if ( ! empty( $msg ) && ! in_array( $view, array( 'pmpro-dashboard', 'pmpro-member' ) ) ) { ?>
		<div id="message" class="<?php if($msg > 0) echo "updated fade"; else echo "error"; ?>"><p><?php echo wp_kses_post( $msgt );?></p></div>
	<?php } ?>

<div class="wrap pmpro_admin <?php echo 'pmpro_admin-' . esc_attr( $view ); ?>">
    <?php
		// Default to showing notification banners.
		$show_notifications = true;

		// Hide notifications on certain pages.
		$hide_on_these_pages = array( 'pmpro-updates' );
		if ( ! empty( $_REQUEST['page'] ) && in_array( sanitize_text_field( $_REQUEST['page'] ), $hide_on_these_pages ) ) {
			$show_notifications = false;
		}

		// Hide notifications if the user has disabled them.
		$notification_handler = pmpro_get_pmpro_banner_notifier();
		if( $notification_handler->get_max_notification_priority() < 1 ) {
			$show_notifications = false;
		}

		if( $show_notifications ) :
		?>
        <div id="pmpro_notifications">
        </div>
        <?php
            // To debug a specific notification.
            if ( !empty( $_REQUEST['pmpro_notification'] ) ) {
                $specific_notification = '&pmpro_notification=' . intval( $_REQUEST['pmpro_notification'] );
            } else {
                $specific_notification = '';
            }
        ?>
        <script>
            jQuery(document).ready(function() {
                jQuery.get('<?php echo esc_url_raw( admin_url( "admin-ajax.php?action=pmpro_notifications" . $specific_notification ) ); ?>', function(data) {
                    if(data && data != 'NULL')
                        jQuery('#pmpro_notifications').html(data);
                });
            });
        </script>
    <?php endif; ?>

	<?php
		// Get the tabs to show in the dashboard menus.
		$dashboard_tabs = pmpro_get_dashboard_tabs();
		$settings_tabs  = pmpro_get_settings_tabs();

		// Only show the menus on the pages that belong to them.
		if( in_array( $view, array_keys( $dashboard_tabs ), true ) || pmpro_is_settings_tab( $view ) ) { ?>
	<nav class="pmpro-nav-primary" aria-labelledby="pmpro-membership-menu">
		<h2 id="pmpro-membership-menu" class="screen-reader-text"><?php esc_html_e( 'Memberships Area Menu', 'paid-memberships-pro' ); ?></h2>
		<ul>
			<?php
				foreach ( $dashboard_tabs as $tab_slug => $tab ) {
					// Make sure the slug is safe to use in the link URL.
					$tab_slug = sanitize_key( $tab_slug );

					// Skip tabs that are missing data or that the current user cannot see.
					if ( empty( $tab['label'] ) || empty( $tab['capability'] ) || ! current_user_can( $tab['capability'] ) ) {
						continue;
					}

					// Skip tabs that are only shown in certain cases.
					if ( isset( $tab['visible'] ) ) {
						$is_visible = is_callable( $tab['visible'] ) ? call_user_func( $tab['visible'] ) : $tab['visible'];
						if ( ! $is_visible ) {
							continue;
						}
					}

					// The tab is current when it matches the page, or when the page
					// is one of the sub tabs that belong to this tab.
					$is_current = ( $view === $tab_slug ) || ( ! empty( $tab['sub_tabs'] ) && pmpro_is_settings_tab( $tab_slug ) && pmpro_is_settings_tab( $view ) );
					?>
					<li><a href="<?php echo esc_url( add_query_arg( 'page', $tab_slug, admin_url( 'admin.php' ) ) ); ?>"<?php if ( $is_current ) { ?> class="current"<?php } ?>><?php echo esc_html( $tab['label'] ); ?></a></li>
			<?php } ?>
		</ul>
	</nav>

		<?php if( pmpro_is_settings_tab( $view ) ) { ?>
	<nav class="pmpro-nav-secondary" aria-labelledby="pmpro-settings-menu">
		<h2 id="pmpro-settings-menu" class="screen-reader-text"><?php esc_html_e( 'Membership Settings Menu', 'paid-memberships-pro' ); ?></h2>
		<ul>
			<?php
				foreach ( $settings_tabs as $tab_slug => $tab ) {
					// Make sure the slug is safe to use in the link URL.
					$tab_slug = sanitize_key( $tab_slug );

					// Skip tabs that are missing data or that the current user cannot see.
					if ( empty( $tab['label'] ) || empty( $tab['capability'] ) || ! current_user_can( $tab['capability'] ) ) {
						continue;
					}
					?>
					<li><a href="<?php echo esc_url( add_query_arg( 'page', $tab_slug, admin_url( 'admin.php' ) ) ); ?>" class="<?php if ( $view === $tab_slug ) { ?>current<?php } ?>"><?php echo esc_html( $tab['label'] ); ?></a></li>
			<?php } ?>
		</ul>
	</nav>
		<?php } ?>

	<?php
	}

	// Check if the site is using Stripe with a lowered Connect fee.
	// Check if Stripe is the gateway.
	if ( 'stripe' === get_option( 'pmpro_gateway' ) ) {
		// Check if the user is not paying for a license.
		if ( ! pmpro_license_isValid( null, pmpro_license_get_premium_types() ) ) {
			// Check if the user selected to acknowledge the 2% fee. Requires a valid nonce and the payment settings capability.
			if ( current_user_can( 'pmpro_paymentsettings' ) && ! empty( $_REQUEST['pmpro_stripe_fee_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['pmpro_stripe_fee_nonce'] ) ), 'pmpro_acknowledge_stripe_connect_fee' ) && ! empty( $_REQUEST['acknowledge_stripe_connect_fee'] ) && '1' === $_REQUEST['acknowledge_stripe_connect_fee'] ) {
				// Delete the option to acknowledge the fee.
				delete_option( 'pmpro_stripe_connect_reduced_application_fee' );

				// Add option to acknowledge the fee. Include the user ID and timestamp.
				update_option( 'pmpro_stripe_connect_acknowledged_fee', array(
					'user_id' => get_current_user_id(),
					'timestamp' => date_i18n( 'Y-m-d H:i:s' ),
				) );
			}

			// Check if the site is using a lowered Connect fee.
			$reduced_fee = get_option( 'pmpro_stripe_connect_reduced_application_fee' );
			$filtered_fee = apply_filters( 'pmpro_set_application_fee_percentage', 2 );
			if ( empty( get_option( 'pmpro_stripe_connect_acknowledged_fee' ) ) && 2 != $filtered_fee ) {
				?>
				<div class="notice notice-large notice-warning inline">
					<h3><?php esc_html_e( 'Action Required: Your Stripe Connect Fees', 'paid-memberships-pro' ); ?></h3>
					<p><?php esc_html_e( 'Your site is using a custom filter to adjust the Paid Memberships Pro Stripe Connect application fee.', 'paid-memberships-pro' ); ?> <strong><?php esc_html_e( 'Sites that continue using a reduced fee risk being disconnected from Stripe.', 'paid-memberships-pro' ); ?></strong></p>
					<p><?php esc_html_e( 'If you would like to continue using Stripe Connect, please click the button below to accept the 2% application fee.', 'paid-memberships-pro' ); ?></p>
					<p><?php esc_html_e( 'Or, to reduce the fee to 0%, you may either:', 'paid-memberships-pro' ); ?></p>
					<ol>
						<li>
							<?php
								$pmpro_stripe_restricted_key_docs_escaped = '<a target="_blank" href="https://www.paidmembershipspro.com/gateway/stripe/switch-legacy-to-connect/?utm_source=pmpro&utm_medium=plugin&utm_campaign=documentation&utm_content=stripe-restricted-key-setup#h-stripe-restricted-keys-alternative-to-stripe-connect">' . esc_html( 'by following this documentation', 'paid-memberships-pro' ) . '</a>';
								// translators: %s is a link to the PMPro documentation for switching to Stripe Restricted API keys.
								printf( esc_html__( 'Switch to using your own Stripe Restricted API keys %s (bypassing Stripe Connect).', 'paid-memberships-pro' ), $pmpro_stripe_restricted_key_docs_escaped ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</li>
						<li>
							<?php
								$pmpro_premium_license_link_escaped = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-license#pmpro-license-settings' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( 'PMPro Premium license', 'paid-memberships-pro' ) . '</a>';
								// translators: %s is a link to the PMPro Premium license page.
								printf( esc_html__( 'Activate a %s to waive the fee.', 'paid-memberships-pro' ), $pmpro_premium_license_link_escaped ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</li>
					</ol>
					<p>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'acknowledge_stripe_connect_fee' => '1' ), admin_url( 'admin.php' ) ), 'pmpro_acknowledge_stripe_connect_fee', 'pmpro_stripe_fee_nonce' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Accept and Continue With 2% Fee', 'paid-memberships-pro' ); ?>
						</a>
					</p>
				</div>
				<?php
			} elseif ( ! empty( $reduced_fee ) && 2 != $reduced_fee ) {
				?>
				<div class="notice notice-large notice-warning inline">
					<h3><?php esc_html_e( 'Important: Stripe Connect Fee Updated', 'paid-memberships-pro' ); ?></h3>
					<p><?php esc_html_e( 'In 2023, Paid Memberships Pro raised the Stripe Connect application fee for newly connected sites to 2%. Because your site was connected prior to this update, you were allowed to continue using the legacy 1% application fee.', 'paid-memberships-pro' ); ?></p>
					<p>
						<?php esc_html_e( 'With the release of PMPro v3.5, we now charge all sites the same 2% application fee. No legacy rates will be supported from this point forward.', 'paid-memberships-pro' ); ?>
						<a href="https://www.paidmembershipspro.com/pmpro-update-3-5/?utm_source=pmpro&utm_medium=plugin&utm_campaign=blog&utm_content=stripe-connect-fee-update" target="_blank"><?php esc_html_e( 'Learn more in the PMPro v3.5 release notes.', 'paid-memberships-pro' ); ?></a>
					</p>
					<p><?php esc_html_e( 'To reduce the fee to 0%, you may either:', 'paid-memberships-pro' ); ?></p>
					<ol>
						<li>
							<?php
								$pmpro_stripe_restricted_key_docs_escaped = '<a target="_blank" href="https://www.paidmembershipspro.com/gateway/stripe/switch-legacy-to-connect/?utm_source=pmpro&utm_medium=plugin&utm_campaign=documentation&utm_content=stripe-restricted-key-setup#h-stripe-restricted-keys-alternative-to-stripe-connect">' . esc_html( 'by following this documentation', 'paid-memberships-pro' ) . '</a>';
								// translators: %s is a link to the PMPro documentation for switching to Stripe Restricted API keys.
								printf( esc_html__( 'Switch to using your own Stripe Restricted API keys %s (bypassing Stripe Connect).', 'paid-memberships-pro' ), $pmpro_stripe_restricted_key_docs_escaped ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>	
						</li>
						<li>
							<?php
								$pmpro_premium_license_link_escaped = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-license#pmpro-license-settings' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( 'PMPro Premium license', 'paid-memberships-pro' ) . '</a>';
								// translators: %s is a link to the PMPro Premium license page.
								printf( esc_html__( 'Activate a %s to waive the fee.', 'paid-memberships-pro' ), $pmpro_premium_license_link_escaped ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</li>
					</ol>
					<p>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pmpro-paymentsettings', 'acknowledge_stripe_connect_fee' => '1' ), admin_url( 'admin.php' ) ), 'pmpro_acknowledge_stripe_connect_fee', 'pmpro_stripe_fee_nonce' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Dismiss this Notice', 'paid-memberships-pro' ); ?>
						</a>
					</p>
				</div>
				<?php
			}
		}
	}
