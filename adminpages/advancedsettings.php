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

		// Set up Wisdom tracking cron if needed.
		if ( (int)get_option( "pmpro_wisdom_opt_out") === 0 ) {
			$wisdom_integration = PMPro_Wisdom_Integration::instance();
			$wisdom_integration->wisdom_tracker->schedule_tracking();
		}

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
	$wisdom_opt_out = (int)get_option( "pmpro_wisdom_opt_out");
	$hideadslevels = get_option( "pmpro_hideadslevels");
	if( is_multisite() ) {
		$redirecttosubscription = get_option( "pmpro_redirecttosubscription");
	}
	$uninstall = get_option( 'pmpro_uninstall');

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

	if ( empty( $activity_email_frequency ) ) {
		$activity_email_frequency = 'week';
	}

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_advancedsettings_nonce');?>
		<hr class="wp-header-end">
		<h1><?php esc_html_e( 'Advanced Settings', 'paid-memberships-pro' ); ?></h1>
		<div id="restrict-dashboard-access-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Restrict Dashboard Access', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="block_dashboard"><?php esc_html_e('WordPress Dashboard', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<input id="block_dashboard" name="block_dashboard" type="checkbox" value="yes" <?php checked( $block_dashboard, 'yes' ); ?> /> <label for="block_dashboard"><?php esc_html_e('Block all users with the Subscriber role from accessing the Dashboard.', 'paid-memberships-pro' );?></label>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="hide_toolbar"><?php esc_html_e('WordPress Toolbar', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<input id="hide_toolbar" name="hide_toolbar" type="checkbox" value="yes" <?php checked( $hide_toolbar, 'yes' ); ?> /> <label for="hide_toolbar"><?php esc_html_e('Hide the Toolbar from all users with the Subscriber role.', 'paid-memberships-pro' );?></label>
						</td>
					</tr>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="content-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Content Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="filterqueries"><?php esc_html_e("Filter searches and archives?", 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<select id="filterqueries" name="filterqueries">
								<option value="0" <?php if(!$filterqueries) { ?>selected="selected"<?php } ?>><?php esc_html_e('No - Non-members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
								<option value="1" <?php if($filterqueries == 1) { ?>selected="selected"<?php } ?>><?php esc_html_e('Yes - Only members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="showexcerpts"><?php esc_html_e('Show Excerpts to Non-Members?', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<select id="showexcerpts" name="showexcerpts">
								<option value="0" <?php if(!$showexcerpts) { ?>selected="selected"<?php } ?>><?php esc_html_e('No - Hide excerpts.', 'paid-memberships-pro' ); ?></option>
								<option value="1" <?php if($showexcerpts == 1) { ?>selected="selected"<?php } ?>><?php esc_html_e('Yes - Show excerpts.', 'paid-memberships-pro' ); ?></option>
							</select>
						</td>
					</tr>
					<?php
					// Only show the custom message field if the option is set.
					if ( ! empty( $nonmembertext ) ) {
						?>
						<tr>
							<th scope="row" valign="top">
								<label for="nonmembertext_type"><?php esc_html_e( 'Membership Required Message', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<select id="nonmembertext_type" name="nonmembertext_type">
									<option value="custom"><?php esc_html_e( 'Use my custom membership required message. (Legacy)', 'paid-memberships-pro' ); ?></option>
									<option value=""><?php esc_html_e( 'Let Paid Memberships Pro generate the message.', 'paid-memberships-pro' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'We recommend that you allow Paid Memberships Pro to generate the message for protected content.', 'paid-memberships-pro' ); ?></p>

								<div id="pmpro_notice-nonmembertext_type" class="notice notice-warning pmpro-notice inline" style="display: none;">
									<p><strong><?php esc_html_e( 'Warning: Saving these settings will permanently delete your custom message. This change is irreversible.', 'paid-memberships-pro' ); ?></strong></p>
									<?php esc_html_e( 'We recommend updating to allow PMPro to generate a smart message for protected content. This message is fully compatible with all of your PMPro Add Ons and includes a link to the checkout or levels page, based on whether the content is protected for a single level or multiple levels.', 'paid-memberships-pro' ); ?></p>
								</div>
							</td>
						</tr>
						<tr class="toggle_nonmembertext">
							<th scope="row" valign="top">
								<label for="nonmembertext"><?php esc_html_e( 'Custom Membership Required Message (Legacy)', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<textarea name="nonmembertext" rows="3" cols="50" class="large-text"><?php echo wp_kses_post( stripslashes($nonmembertext) )?></textarea>
								<p class="description"><?php esc_html_e('This is a legacy option that will be removed in a future version of PMPro. This message is shown in place of the post content for non-members. Available variables', 'paid-memberships-pro' );?>: <code>!!levels!!</code> <code>!!referrer!!</code> <code>!!levels_page_url!!</code></p>
							</td>
						</tr>
						<script>
							jQuery(document).ready(function() {
								jQuery('#nonmembertext_type').change(function() {
									if(jQuery(this).val() == 'custom') {
										jQuery('.toggle_nonmembertext').show();
										jQuery('#pmpro_notice-nonmembertext_type').hide();
									} else {
										jQuery('.toggle_nonmembertext').hide();
										jQuery('#pmpro_notice-nonmembertext_type').show();
									}
								});
							});
						</script>
						<?php
					}
					?>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="communication-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Communication Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Notifications', 'paid-memberships-pro' ); ?></th>
						<td>
							<select name="maxnotificationpriority">
								<option value="5" <?php selected( $maxnotificationpriority, 5 ); ?>>
									<?php esc_html_e( 'Show all notifications.', 'paid-memberships-pro' ); ?>
								</option>
								<option value="1" <?php selected( $maxnotificationpriority, 1 ); ?>>
									<?php esc_html_e( 'Show only security notifications.', 'paid-memberships-pro' ); ?>
								</option>
							</select>
							<br />
							<p class="description"><?php esc_html_e('Notifications are occasionally shown on the Paid Memberships Pro settings pages.', 'paid-memberships-pro' );?></p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="activity_email_frequency"><?php esc_html_e('Activity Email Frequency', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<select name="activity_email_frequency">
								<option value="day" <?php selected( $activity_email_frequency, 'day' ); ?>>
									<?php esc_html_e( 'Daily', 'paid-memberships-pro' ); ?>
								</option>
								<option value="week" <?php selected( $activity_email_frequency, 'week' ); ?>>
									<?php esc_html_e( 'Weekly', 'paid-memberships-pro' ); ?>
								</option>
								<option value="month" <?php selected( $activity_email_frequency, 'month' ); ?>>
									<?php esc_html_e( 'Monthly', 'paid-memberships-pro' ); ?>
								</option>
								<option value="never" <?php selected( $activity_email_frequency, 'never' ); ?>>
									<?php esc_html_e( 'Never', 'paid-memberships-pro' ); ?>
								</option>
							</select>
							<br />
							<p class="description"><?php esc_html_e( 'Send periodic sales and revenue updates from this site to the administration email address.', 'paid-memberships-pro' );?></p>
						</td>
					</tr>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="business-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Business Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p class="description">
					<?php esc_html_e( 'Enter your business name and address. This information will be shown to members on the Membership Orders page and Orders print view.', 'paid-memberships-pro' );?>
				</p>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<label for="business_name"><?php esc_html_e( 'Business Name', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<input type="text" id="business_name" name="business_name" value="<?php echo esc_attr( $business_address['name'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="business_street"><?php esc_html_e( 'Business Street', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<input type="text" id="business_street" name="business_street" value="<?php echo esc_attr( $business_address['street'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="business_street2"><?php esc_html_e( 'Business Street 2', 'paid-memberships-pro' );?></label>
							</th>
							<td>
								<input type="text" id="business_street2" name="business_street2" value="<?php echo esc_attr( $business_address['street2'] ); ?>" class="regular-text" />
							</td>
						<tr>
							<th scope="row" valign="top">
								<label for="business_city"><?php esc_html_e( 'Business City', 'paid-memberships-pro' ); ?></label>
							</th>
							<td>
							<input type="text" id="business_city" name="business_city" value="<?php echo esc_attr( $business_address['city'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
							<label for="business_state"><?php esc_html_e( 'Business State', 'paid-memberships-pro' ); ?></label>
							</th>
							<td>
								<input type="text" id="business_state" name="business_state" value="<?php echo esc_attr( $business_address['state'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="business_zip"><?php esc_html_e( 'Business Postal Code', 'paid-memberships-pro' ); ?></label>
							</th>
							<td>
								<input type="text" id="business_zip" name="business_zip" value="<?php echo esc_attr( $business_address['zip'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="business_country"><?php esc_html_e( 'Business Country', 'paid-memberships-pro' ); ?></label>
							</th>
							<td>
								<select id="business_country" name="business_country">
									<option value="0" <?php selected( $business_address['country'], '0' ); ?>><?php esc_html_e( '-- Select a Country --', 'paid-memberships-pro' ); ?></option>
								<?php
									global $pmpro_countries;
									foreach( $pmpro_countries as $abbr => $country ) {
										?>
										<option value="<?php echo esc_attr( $abbr ) ?>" <?php selected( $business_address['country'], $abbr ); ?>><?php echo esc_html( $country ); ?></option>
										<?php
									}
								?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label for="business_phone"><?php esc_html_e( 'Business Phone', 'paid-memberships-pro' ); ?></label>
							</th>
							<td>
								<input type="text" id="business_phone" name="business_phone" value="<?php echo esc_attr( $business_address['phone'] ); ?>" class="regular-text" />
							</td>
						</tr>
					</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="other-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Other Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="pmpro-hideads"><?php esc_html_e("Hide Ads From Members?", 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<select id="pmpro-hideads" name="hideads" onchange="pmpro_updateHideAdsTRs();">
								<option value="0" <?php if(!$hideads) { ?>selected="selected"<?php } ?>><?php esc_html_e('No', 'paid-memberships-pro' );?></option>
								<option value="1" <?php if($hideads == 1) { ?>selected="selected"<?php } ?>><?php esc_html_e('Hide Ads From All Members', 'paid-memberships-pro' );?></option>
								<option value="2" <?php if($hideads == 2) { ?>selected="selected"<?php } ?>><?php esc_html_e('Hide Ads From Certain Members', 'paid-memberships-pro' );?></option>
							</select>
						</td>
					</tr>
					<tr id="hideads_explanation" <?php if($hideads < 2) { ?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top">&nbsp;</th>
						<td>
							<p><?php esc_html_e('To hide ads in your template code, use code like the following', 'paid-memberships-pro' );?>:</p>
						<pre lang="PHP">
if ( function_exists( 'pmpro_displayAds' ) && pmpro_displayAds() ) {
	//insert ad code here
}</pre>
						</td>
					</tr>
					<tr id="hideadslevels_tr" <?php if($hideads != 2) { ?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top">
							<label for="hideadslevels"><?php esc_html_e('Choose Levels to Hide Ads From', 'paid-memberships-pro' );?>:</label>
						</th>
						<td>
							<?php
								// Build the selectors for the checkbox list based on number of levels.
								$classes = array();
								$classes[] = "pmpro_checkbox_box";
								if ( count( $levels ) > 5 ) {
									$classes[] = "pmpro_scrollable";
								}
								$class = implode( ' ', array_unique( $classes ) );
							?>
							<div class="<?php echo esc_attr( $class ); ?>">
								<?php
									$hideadslevels = get_option( "pmpro_hideadslevels" );
									if(!is_array($hideadslevels))
										$hideadslevels = explode(",", $hideadslevels);

									$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
									$levels = $wpdb->get_results($sqlQuery, OBJECT);
									$levels = pmpro_sort_levels_by_order( $levels );
									foreach($levels as $level)
									{
								?>
									<div class="pmpro_clickable">
										<input type="checkbox" id="hideadslevels_<?php echo esc_attr( $level->id ); ?>" name="hideadslevels[]" value="<?php echo esc_attr( $level->id); ?>" <?php checked( in_array( $level->id, $hideadslevels ), true ); ?>>
										<label for="hideadslevels_<?php echo esc_attr( $level->id ); ?>"><?php echo esc_html( $level->name ); ?></label>
									</div>
								<?php
									}
								?>
							</div>
						</td>
					</tr>
					<?php if(is_multisite()) { ?>
					<tr>
						<th scope="row" valign="top">
							<label for="redirecttosubscription"><?php esc_html_e('Redirect all traffic from registration page to /subscription/?', 'paid-memberships-pro' );?>: <em>(<?php esc_html_e('multisite only', 'paid-memberships-pro' );?>)</em></label>
						</th>
						<td>
							<select id="redirecttosubscription" name="redirecttosubscription">
								<option value="0" <?php if(!$redirecttosubscription) { ?>selected="selected"<?php } ?>><?php esc_html_e('No', 'paid-memberships-pro' );?></option>
								<option value="1" <?php if($redirecttosubscription == 1) { ?>selected="selected"<?php } ?>><?php esc_html_e('Yes', 'paid-memberships-pro' );?></option>
							</select>
						</td>
					</tr>
					<?php } ?>
					<?php
					// Filter to Add More Advanced Settings for Misc Plugin Options, etc.
					if (has_action('pmpro_custom_advanced_settings')) {
						$custom_fields = apply_filters('pmpro_custom_advanced_settings', array());
						foreach ($custom_fields as $field) {
						?>
						<tr>
							<th valign="top" scope="row">
								<label
									for="<?php echo esc_attr( $field['field_name'] ); ?>"><?php echo esc_textarea( $field['label'] ); ?></label>
							</th>
							<td>
								<?php
								switch ($field['field_type']) {
									case 'select':
										?>
										<select id="<?php echo esc_attr( $field['field_name'] ); ?>"
												name="<?php echo esc_attr( $field['field_name'] ); ?>">
											<?php
												//For associative arrays, we use the array keys as values. For numerically indexed arrays, we use the array values.
												$is_associative = !empty($field['is_associative']) || (bool)count(array_filter(array_keys($field['options']), 'is_string'));
												foreach ($field['options'] as $key => $option) {
													if(!$is_associative) $key = $option;
													?>
													<option value="<?php echo esc_attr($key); ?>" <?php selected($key, get_option( 'pmpro_' . $field['field_name'] ));?>>
														<?php echo esc_textarea($option); ?>
													</option>
													<?php
												}
											?>
										</select>
										<?php
										break;
									case 'text':
										?>
										<input id="<?php echo esc_attr( $field['field_name'] ); ?>"
											name="<?php echo esc_attr( $field['field_name'] ); ?>"
											type="<?php echo esc_attr( $field['field_type'] ); ?>"
											value="<?php echo esc_attr(get_option( 'pmpro_' . $field['field_name'] )); ?> "
											class="regular-text">
										<?php
										break;
									case 'textarea':
										?>
										<textarea id="<?php echo esc_attr( $field['field_name'] ); ?>"
												name="<?php echo esc_attr( $field['field_name'] ); ?>"
												class="large-text">
											<?php echo esc_textarea(get_option( 'pmpro_' . $field['field_name'] )); ?>
										</textarea>
										<?php
										break;
									case 'callback':
										call_user_func($field['callback']);
										break;
									default:
										break;
								}
								if ( ! empty( $field['description'] ) ) {
									$allowed_pmpro_custom_advanced_settings_html = array (
										'strong' => array(),
										'code' => array(),
										'em' => array(),
										'br' => array(),
										'p' => array(),
										'a' => array (
											'href' => array(),
											'target' => array(),
											'title' => array(),
										)
									);
									?>
									<p class="description"><?php echo wp_kses( $field['description'], $allowed_pmpro_custom_advanced_settings_html ); ?></p>
									<?php } ?>
							</td>
						</tr>
						<?php
						}
					}
					?>
					<tr>
						<th scope="row" valign="top">
							<label for="wisdom_opt_out">
								<?php esc_html_e( 'Enable Tracking', 'paid-memberships-pro' ); ?>
							</label>
						</th>
						<td>
							<p>
								<label>								
									<input name="wisdom_opt_out" type="radio" value="0"<?php checked( 0, $wisdom_opt_out ); ?> />
									<?php esc_html_e( 'Allow usage of Paid Memberships Pro to be tracked.', 'paid-memberships-pro' );?>
								</label>
							</p>
							<p>
								<label>
									<input name="wisdom_opt_out" type="radio" value="1"<?php checked( 1, $wisdom_opt_out ); ?> />
									<?php esc_html_e( 'Do not track usage of Paid Memberships Pro on my site.', 'paid-memberships-pro' );?>
								</label>
							</p>
							<p class="description">
								<?php esc_html_e( 'Sharing non-sensitive membership site data helps us analyze how our plugin is meeting your needs and identify opportunities to improve. Read about what usage data is tracked:', 'paid-memberships-pro' ); ?>
								<a href="https://www.paidmembershipspro.com/privacy-policy/usage-tracking/" title="<?php esc_attr_e( 'PaidMembershipsPro.com Usage Tracking', 'paid-memberships-pro' ); ?>" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'Paid Memberships Pro Usage Tracking', 'paid-memberships-pro' ); ?></a>.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="uninstall"><?php esc_html_e('Uninstall PMPro on deletion?', 'paid-memberships-pro' );?></label>
						</th>
						<td>
							<select id="uninstall" name="uninstall">
								<option value="0" <?php if ( ! $uninstall ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'No', 'paid-memberships-pro' );?></option>
								<option value="1" <?php if ( $uninstall == 1 ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes - Delete all PMPro Data.', 'paid-memberships-pro' );?></option>
							</select>
							<p class="description"><?php esc_html_e( 'To delete all PMPro data from the database, set to Yes, deactivate PMPro, and then click to delete PMPro from the plugins page.', 'paid-memberships-pro' ); ?></p>
						</td>
					</tr>
				</tbody>
				</table>
				<script>
					function pmpro_updateHideAdsTRs()
					{
						var hideads = jQuery('#pmpro-hideads').val();
						if(hideads == 2)
						{
							jQuery('#hideadslevels_tr').show();
						}
						else
						{
							jQuery('#hideadslevels_tr').hide();
						}

						if(hideads > 0)
						{
							jQuery('#hideads_explanation').show();
						}
						else
						{
							jQuery('#hideads_explanation').hide();
						}
					}
					pmpro_updateHideAdsTRs();
				</script>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<p class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
