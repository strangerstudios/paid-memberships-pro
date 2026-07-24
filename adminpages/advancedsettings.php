<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_advancedsettings")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	global $wpdb, $msg, $msgt, $allowedposttags;

	//check nonce for saving settings
	if (!empty($_REQUEST['savesettings']) && (empty($_REQUEST['pmpro_advancedsettings_nonce']) || !check_admin_referer('savesettings', 'pmpro_advancedsettings_nonce'))) {
		$msg = -1;
		$msgt = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset($_REQUEST['savesettings']);
	}	

	//get/set settings
	if(!empty($_REQUEST['savesettings']))
	{
		// Dashboard settings.
		pmpro_setOption( 'hide_toolbar' );
		pmpro_setOption( 'block_dashboard' );
		
		// Content settings.
		pmpro_setOption("filterqueries");
		pmpro_setOption("showexcerpts");
		if ( ! empty( $_POST['nonmembertext_type'] ) ) {
			// These use wp_kses for better security handling.
			$nonmembertext = wp_kses( wp_unslash( $_POST['nonmembertext'] ), $allowedposttags );
			update_option( 'pmpro_nonmembertext', $nonmembertext );
		} else {
			delete_option( 'pmpro_nonmembertext' );
		}

		// Communication settings.
		pmpro_setOption("maxnotificationpriority");
		pmpro_setOption("activity_email_frequency");

		// Business settings.
		$business_address = array();
		$business_address['name'] = ! empty( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
		$business_address['street'] = ! empty( $_POST['business_street'] ) ? sanitize_text_field( wp_unslash( $_POST['business_street'] ) ) : '';
		$business_address['street2'] = ! empty( $_POST['business_street2'] ) ? sanitize_text_field( wp_unslash( $_POST['business_street2'] ) ) : '';
		$business_address['city'] = ! empty( $_POST['business_city'] ) ? sanitize_text_field( wp_unslash( $_POST['business_city'] ) ) : '';
		$business_address['state'] = ! empty( $_POST['business_state'] ) ? sanitize_text_field( wp_unslash( $_POST['business_state'] ) ) : '';
		$business_address['zip'] = ! empty( $_POST['business_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['business_zip'] ) ) : '';
		$business_address['country'] = ! empty( $_POST['business_country'] ) ? sanitize_text_field( wp_unslash( $_POST['business_country'] ) ) : '';
		$business_address['phone'] = ! empty( $_POST['business_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['business_phone'] ) ) : '';
		update_option( 'pmpro_business_address', $business_address );

		// Other settings.
		pmpro_setOption("hideads");
		pmpro_setOption("wisdom_opt_out");
		pmpro_setOption("hideadslevels");
		pmpro_setOption("redirecttosubscription");
		pmpro_setOption("uninstall");
		pmpro_setOption( 'avatar_enabled_sitewide' );
		pmpro_setOption("site_type");

        /**
         * Filter to add custom settings to the advanced settings page.
         * @param array $settings Array of settings, each setting an array with keys field_name, field_type, label, description.
         */
        $custom_settings = apply_filters('pmpro_custom_advanced_settings', array());
        foreach($custom_settings as $setting) {
        	if(!empty($setting['field_name']))
        		pmpro_setOption($setting['field_name']);
        }

		// Assume success.
		$msg = true;
		$msgt = __("Your advanced settings have been updated.", 'paid-memberships-pro' );
	}

	// Dashboard settings.
	$hide_toolbar = get_option( 'pmpro_hide_toolbar' );
	$block_dashboard = get_option( 'pmpro_block_dashboard' );

	// Content settings.
	$filterqueries = get_option( 'pmpro_filterqueries');
	$showexcerpts = get_option( 'pmpro_showexcerpts' );
	$nonmembertext = get_option( 'pmpro_nonmembertext' );

	// Business settings.
	$business_address = get_option( 'pmpro_business_address' );
	if ( empty( $business_address ) ) {
		$business_address = array(
			'name' => '',
			'street' => '',
			'street2' => '',
			'city' => '',
			'state' => '',
			'zip' => '',
			'country' => '',
			'phone' => ''
		);
	}

	// Communication settings.
	$maxnotificationpriority = get_option( "pmpro_maxnotificationpriority");
	$activity_email_frequency = get_option( "pmpro_activity_email_frequency");

	// Other settings.
	$hideads = get_option( "pmpro_hideads");
	$wisdom_opt_out = (int) get_option( "pmpro_wisdom_opt_out");
	$hideadslevels = get_option( "pmpro_hideadslevels");
	if( is_multisite() ) {
		$redirecttosubscription = get_option( "pmpro_redirecttosubscription");
	}
	$uninstall = get_option( 'pmpro_uninstall');
	$avatar_enabled_sitewide = get_option( 'pmpro_avatar_enabled_sitewide' );
	$site_type = get_option( 'pmpro_site_type' );

	if ( empty( $activity_email_frequency ) ) {
		$activity_email_frequency = 'week';
	}

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_advancedsettings_nonce');?>
		<hr class="wp-header-end">
		<h1><?php esc_html_e( 'Advanced Settings', 'paid-memberships-pro' ); ?></h1>
		<p><?php
			$advanced_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Advanced Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/advanced-settings/?utm_source=plugin&utm_medium=pmpro-advancedsettings&utm_campaign=documentation&utm_content=advanced-settings">' . esc_html__( 'Advanced Settings', 'paid-memberships-pro' ) . '</a>';
			// translators: %s: Link to Advanced Settings doc.
			printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $advanced_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?></p>
		<?php
		pmpro_build_settings_section( array(
			'id'     => 'restrict-dashboard-access-settings',
			'title'  => __( 'Restrict Dashboard Access', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'name'           => 'block_dashboard',
					'label'          => __( 'WordPress Dashboard', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'checkbox_value' => 'yes',
					'value'          => $block_dashboard,
					'checkbox_label' => __( 'Block all users with the Subscriber role from accessing the Dashboard.', 'paid-memberships-pro' ),
				),
				array(
					'name'           => 'hide_toolbar',
					'label'          => __( 'WordPress Toolbar', 'paid-memberships-pro' ),
					'type'           => 'checkbox',
					'checkbox_value' => 'yes',
					'value'          => $hide_toolbar,
					'checkbox_label' => __( 'Hide the Toolbar from all users with the Subscriber role.', 'paid-memberships-pro' ),
				),
			),
		) );

		// Content Settings. filterqueries/showexcerpts are simple selects; the legacy custom-message
		// block only appears when a custom message already exists and keeps its bespoke markup.
		$content_fields = array(
			array(
				'name'    => 'filterqueries',
				'label'   => __( 'Filter searches and archives?', 'paid-memberships-pro' ),
				'type'    => 'select',
				'value'   => $filterqueries,
				'options' => array(
					0 => __( 'No - Non-members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ),
					1 => __( 'Yes - Only members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ),
				),
			),
			array(
				'name'    => 'showexcerpts',
				'label'   => __( 'Show Excerpts to Non-Members?', 'paid-memberships-pro' ),
				'type'    => 'select',
				'value'   => $showexcerpts,
				'options' => array(
					0 => __( 'No - Hide excerpts.', 'paid-memberships-pro' ),
					1 => __( 'Yes - Show excerpts.', 'paid-memberships-pro' ),
				),
			),
		);
		if ( ! empty( $nonmembertext ) ) {
			$content_fields[] = array(
				'label'    => __( 'Membership Required Message', 'paid-memberships-pro' ),
				'type'     => 'callback',
				'callback' => function() use ( $nonmembertext ) {
					$custom_message_depends    = esc_attr( wp_json_encode( array( array( 'id' => 'nonmembertext_type', 'value' => 'custom' ) ) ) );
					$generated_message_depends = esc_attr( wp_json_encode( array( array( 'id' => 'nonmembertext_type', 'value' => '' ) ) ) );
					?>
					<select id="nonmembertext_type" name="nonmembertext_type">
						<option value="custom"><?php esc_html_e( 'Use my custom membership required message. (Legacy)', 'paid-memberships-pro' ); ?></option>
						<option value=""><?php esc_html_e( 'Let Paid Memberships Pro generate the message.', 'paid-memberships-pro' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'We recommend that you allow Paid Memberships Pro to generate the message for protected content.', 'paid-memberships-pro' ); ?></p>
					<div id="pmpro_notice-nonmembertext_type" class="notice notice-warning pmpro-notice inline pmpro-hidden" data-pmpro-depends="<?php echo $generated_message_depends; ?>">
						<p><strong><?php esc_html_e( 'Warning: Saving these settings will permanently delete your custom message. This change is irreversible.', 'paid-memberships-pro' ); ?></strong></p>
						<p><?php esc_html_e( 'We recommend updating to allow PMPro to generate a smart message for protected content. This message is fully compatible with all of your PMPro Add Ons and includes a link to the checkout or levels page, based on whether the content is protected for a single level or multiple levels.', 'paid-memberships-pro' ); ?></p>
					</div>
					<div class="toggle_nonmembertext" data-pmpro-depends="<?php echo $custom_message_depends; ?>">
						<p><label for="nonmembertext"><strong><?php esc_html_e( 'Custom Membership Required Message (Legacy)', 'paid-memberships-pro' ); ?></strong></label></p>
						<textarea name="nonmembertext" rows="3" cols="50" class="large-text"><?php echo wp_kses_post( stripslashes( $nonmembertext ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'This is a legacy option that will be removed in a future version of PMPro. This message is shown in place of the post content for non-members. Available variables', 'paid-memberships-pro' ); ?>: <code>!!levels!!</code> <code>!!referrer!!</code> <code>!!levels_page_url!!</code></p>
					</div>
					<?php
				},
			);
		}
		pmpro_build_settings_section( array(
			'id'     => 'content-settings',
			'title'  => __( 'Content Settings', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => $content_fields,
		) );

		pmpro_build_settings_section( array(
			'id'     => 'communication-settings',
			'title'  => __( 'Communication Settings', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'name'        => 'maxnotificationpriority',
					'label'       => __( 'Notifications', 'paid-memberships-pro' ),
					'type'        => 'select',
					'value'       => $maxnotificationpriority,
					'options'     => array(
						5 => __( 'Show all notifications.', 'paid-memberships-pro' ),
						1 => __( 'Show only security notifications.', 'paid-memberships-pro' ),
					),
					'description' => __( 'Notifications are occasionally shown on the Paid Memberships Pro settings pages.', 'paid-memberships-pro' ),
				),
				array(
					'name'        => 'activity_email_frequency',
					'label'       => __( 'Activity Email Frequency', 'paid-memberships-pro' ),
					'type'        => 'select',
					'value'       => $activity_email_frequency,
					'options'     => array(
						'day'   => __( 'Daily', 'paid-memberships-pro' ),
						'week'  => __( 'Weekly', 'paid-memberships-pro' ),
						'month' => __( 'Monthly', 'paid-memberships-pro' ),
						'never' => __( 'Never', 'paid-memberships-pro' ),
					),
					'description' => __( 'Send periodic sales and revenue updates from this site to the administration email address.', 'paid-memberships-pro' ),
				),
			),
		) );

		// Business Settings. Individual text fields (saved into the pmpro_business_address array) plus a
		// country dropdown.
		global $pmpro_countries;
		$business_country_options = array( 0 => __( '-- Select a Country --', 'paid-memberships-pro' ) ) + (array) $pmpro_countries;
		pmpro_build_settings_section( array(
			'id'     => 'business-settings',
			'title'  => __( 'Business Settings', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => array(
				array(
					'html' => '<p class="description">' . esc_html__( 'Enter your business name and address. This information will be shown to members on the Membership Orders page and Orders print view.', 'paid-memberships-pro' ) . '</p>',
				),
				array( 'name' => 'business_name', 'label' => __( 'Business Name', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['name'] ),
				array( 'name' => 'business_street', 'label' => __( 'Business Street', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['street'] ),
				array( 'name' => 'business_street2', 'label' => __( 'Business Street 2', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['street2'] ),
				array( 'name' => 'business_city', 'label' => __( 'Business City', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['city'] ),
				array( 'name' => 'business_state', 'label' => __( 'Business State', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['state'] ),
				array( 'name' => 'business_zip', 'label' => __( 'Business Postal Code', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['zip'] ),
				array( 'name' => 'business_country', 'label' => __( 'Business Country', 'paid-memberships-pro' ), 'type' => 'select', 'value' => $business_address['country'], 'options' => $business_country_options ),
				array( 'name' => 'business_phone', 'label' => __( 'Business Phone', 'paid-memberships-pro' ), 'type' => 'text', 'value' => $business_address['phone'] ),
			),
		) );

		// Other Settings.
		$site_type_options = array( '' => __( '-- Select --', 'paid-memberships-pro' ) );
		foreach ( pmpro_get_site_types() as $site_type_key => $site_type_name ) {
			$site_type_options[ $site_type_key ] = $site_type_name;
		}

		// Level options for the "hide ads from certain levels" checklist.
		$hideadslevels_selected = get_option( 'pmpro_hideadslevels' );
		if ( ! is_array( $hideadslevels_selected ) ) {
			$hideadslevels_selected = explode( ',', (string) $hideadslevels_selected );
		}
		$hideads_level_options = array();
		foreach ( pmpro_sort_levels_by_order( $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT ) ) as $hideads_level ) {
			$hideads_level_options[ $hideads_level->id ] = $hideads_level->name;
		}
		$other_fields = array(
			array(
				'name'        => 'site_type',
				'label'       => __( 'What type of membership site are you creating?', 'paid-memberships-pro' ),
				'type'        => 'select',
				'class'       => 'pmpro-wizard__field-block',
				'value'       => $site_type,
				'options'     => $site_type_options,
				'description' => __( 'Choose the answer that best fits the primary value of your membership site.', 'paid-memberships-pro' ),
			),
			array(
				'name'    => 'hideads',
				'label'   => __( 'Hide Ads From Members?', 'paid-memberships-pro' ),
				'type'    => 'select',
				'value'   => $hideads,
				'options' => array(
					0 => __( 'No', 'paid-memberships-pro' ),
					1 => __( 'Hide Ads From All Members', 'paid-memberships-pro' ),
					2 => __( 'Hide Ads From Certain Members', 'paid-memberships-pro' ),
				),
			),
			array(
				'label'      => '',
				'type'       => 'callback',
				'depends'    => array(
					array( 'id' => 'hideads', 'value' => '1', 'current' => $hideads ),
					array( 'id' => 'hideads', 'value' => '2', 'current' => $hideads ),
				),
				'depends_or' => true,
				'callback'   => function() {
					?>
					<p><?php esc_html_e( 'To hide ads in your template code, use code like the following', 'paid-memberships-pro' ); ?>:</p>
					<pre lang="PHP">
if ( function_exists( 'pmpro_displayAds' ) && pmpro_displayAds() ) {
	//insert ad code here
}</pre>
					<?php
				},
			),
			array(
				'name'       => 'hideadslevels',
				'label'      => __( 'Choose Levels to Hide Ads From', 'paid-memberships-pro' ),
				'type'       => 'checklist',
				'options'    => $hideads_level_options,
				'value'      => $hideadslevels_selected,
				'depends'    => array( array( 'id' => 'hideads', 'value' => '2', 'current' => $hideads ) ),
			),
		);

		if ( is_multisite() ) {
			$other_fields[] = array(
				'name'    => 'redirecttosubscription',
				'label'   => __( 'Redirect all traffic from registration page to /subscription/? (multisite only)', 'paid-memberships-pro' ),
				'type'    => 'select',
				'value'   => $redirecttosubscription,
				'options' => array(
					0 => __( 'No', 'paid-memberships-pro' ),
					1 => __( 'Yes', 'paid-memberships-pro' ),
				),
			);
		}

		// Add-on custom fields (pmpro_custom_advanced_settings) rendered through the same field engine.
		if ( has_action( 'pmpro_custom_advanced_settings' ) ) {
			foreach ( apply_filters( 'pmpro_custom_advanced_settings', array() ) as $custom_field ) {
				if ( empty( $custom_field['field_name'] ) ) {
					continue;
				}
				$custom_entry = array(
					'name'        => $custom_field['field_name'],
					'label'       => isset( $custom_field['label'] ) ? $custom_field['label'] : '',
					'description' => isset( $custom_field['description'] ) ? $custom_field['description'] : '',
					'value'       => get_option( 'pmpro_' . $custom_field['field_name'] ),
				);
				$custom_type = isset( $custom_field['field_type'] ) ? $custom_field['field_type'] : 'text';
				if ( 'select' === $custom_type ) {
					$custom_entry['type'] = 'select';
					$custom_options = isset( $custom_field['options'] ) ? $custom_field['options'] : array();
					$is_associative = ! empty( $custom_field['is_associative'] ) || (bool) count( array_filter( array_keys( $custom_options ), 'is_string' ) );
					$normalized_options = array();
					foreach ( $custom_options as $opt_key => $opt_label ) {
						$normalized_options[ $is_associative ? $opt_key : $opt_label ] = $opt_label;
					}
					$custom_entry['options'] = $normalized_options;
				} elseif ( 'textarea' === $custom_type ) {
					$custom_entry['type'] = 'textarea';
					$custom_entry['class'] = 'large-text';
				} elseif ( 'callback' === $custom_type ) {
					$custom_entry['type'] = 'callback';
					$custom_callback = isset( $custom_field['callback'] ) ? $custom_field['callback'] : '__return_false';
					$custom_description = isset( $custom_field['description'] ) ? $custom_field['description'] : '';
					$custom_entry['callback'] = function() use ( $custom_callback, $custom_description ) {
						if ( is_callable( $custom_callback ) ) {
							call_user_func( $custom_callback );
						}
						if ( ! empty( $custom_description ) ) {
							$allowed_pmpro_custom_advanced_settings_html = array(
								'strong' => array(),
								'code'   => array(),
								'em'     => array(),
								'br'     => array(),
								'p'      => array(),
								'a'      => array(
									'href'   => array(),
									'target' => array(),
									'title'  => array(),
								),
							);
							echo '<p class="description">' . wp_kses( $custom_description, $allowed_pmpro_custom_advanced_settings_html ) . '</p>';
						}
					};
				} else {
					$custom_entry['type'] = 'text';
				}
				$other_fields[] = $custom_entry;
			}
		}

		$other_fields[] = array(
			'name'        => 'wisdom_opt_out',
			'label'       => __( 'Enable Plugin Usage Data Sharing', 'paid-memberships-pro' ),
			'type'        => 'radio',
			'value'       => $wisdom_opt_out,
			'options'     => array(
				0 => __( 'Allow usage of Paid Memberships Pro to be shared with us.', 'paid-memberships-pro' ),
				1 => __( 'Do not share usage of Paid Memberships Pro on my site.', 'paid-memberships-pro' ),
			),
			'description' => esc_html__( 'Sharing non-sensitive membership site data helps us analyze how our plugin is meeting your needs and identify opportunities to improve. Read about what usage data is tracked:', 'paid-memberships-pro' ) . ' <a href="https://www.paidmembershipspro.com/privacy-policy/usage-tracking/" title="' . esc_attr__( 'PaidMembershipsPro.com Usage Tracking', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener">' . esc_html__( 'Paid Memberships Pro Usage Tracking', 'paid-memberships-pro' ) . '</a>.',
		);

		if ( get_option( 'show_avatars' ) ) {
			$other_fields[] = array(
				'name'           => 'avatar_enabled_sitewide',
				'label'          => __( 'Profile Pictures', 'paid-memberships-pro' ),
				'type'           => 'checkbox',
				'value'          => $avatar_enabled_sitewide,
				'checkbox_label' => __( 'Enable profile pictures for all users, regardless of membership level.', 'paid-memberships-pro' ),
				'description'    => __( 'When enabled, all logged-in users can upload a custom profile picture. When disabled, profile pictures are only available for membership levels with the "Enable Profile Pictures" setting checked.', 'paid-memberships-pro' ),
			);
		}

		$other_fields[] = array(
			'name'        => 'uninstall',
			'label'       => __( 'Uninstall PMPro on deletion?', 'paid-memberships-pro' ),
			'type'        => 'select',
			'value'       => $uninstall,
			'options'     => array(
				0 => __( 'No', 'paid-memberships-pro' ),
				1 => __( 'Yes - Delete all PMPro Data.', 'paid-memberships-pro' ),
			),
			'description' => __( 'To delete all PMPro data from the database, set to Yes, deactivate PMPro, and then click to delete PMPro from the plugins page.', 'paid-memberships-pro' ),
		);

		pmpro_build_settings_section( array(
			'id'     => 'other-settings',
			'title'  => __( 'Other Settings', 'paid-memberships-pro' ),
			'open'   => true,
			'fields' => $other_fields,
		) );
		?>
		<p class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
