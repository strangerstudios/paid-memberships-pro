<?php
global $wpdb, $msg, $msgt, $pmpro_stripe_error, $pmpro_braintree_error, $pmpro_payflow_error, $pmpro_twocheckout_error, $pmpro_currency_symbol;

// Get level templates.
$level_templates = pmpro_edit_level_templates();

// Get level groups in order.
$level_groups = pmpro_get_level_groups_in_order();

// Get the template if passed in the URL.
if ( isset( $_REQUEST['template'] ) ) {
	$template = sanitize_text_field( $_REQUEST['template'] );
} else {
	$template = false;
}

// Are we copying a level?
if ( isset( $_REQUEST['copy'] ) ) {
	$copy = intval($_REQUEST['copy']);
}

// Set up the level group if copying or if group is passed in the URL.
if ( ! empty( $copy ) && $copy > 0 ) {
	// If we're copying, get the group from the copied level.
	$current_group = pmpro_get_group_id_for_level( $copy );
} else {
	$current_group = isset( $_REQUEST['level_group'] ) ? intval( $_REQUEST['level_group'] ) : 0;
}

// Get the primary gateway.
$gateway = get_option( "pmpro_gateway");

// Set up the level or create a new one.
if (!empty($edit) && $edit > 0) {
	$level = $wpdb->get_row(
		$wpdb->prepare(
			"
				SELECT * FROM $wpdb->pmpro_membership_levels
				WHERE id = %d LIMIT 1",
			$edit
		),
		OBJECT
	);
	$temp_id = $level->id;
} elseif (!empty($copy) && $copy > 0) {
	// We're copying a previous level, get that level's info.
	$level = $wpdb->get_row(
		$wpdb->prepare(
			"
        SELECT * FROM $wpdb->pmpro_membership_levels
        WHERE id = %d LIMIT 1",
			$copy
		),
		OBJECT
	);
	$temp_id = $level->id;
	$level->id = NULL;
}

// If we still don't have a level, set up a new one.
if (empty($level)) {
	$level = new stdClass();
	$level->id = NULL;
	$level->name = NULL;
	$level->description = '';
	$level->confirmation = '';
	$level->initial_payment = NULL;
	$level->billing_amount = NULL;
	$level->cycle_number = 1;
	$level->cycle_period = 'Month';
	$level->billing_limit = NULL;
	$level->trial_amount = NULL;
	$level->trial_limit = NULL;
	$level->expiration_number = NULL;
	$level->expiration_period = NULL;
	$edit = -1;

	// If we have a level template, override and set some defaults.
	if (!empty($template) && $template != 'none') {
		if ($template === 'free') {
			$level->billing_amount = NULL;
			$level->trial_amount = NULL;
			$level->initial_payment = NULL;
			$level->billing_limit = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
			$level->cycle_number = 1;
			$level->cycle_period = 'Month';
		} elseif ($template === 'onetime') {
			$level->initial_payment = 100;
			$level->billing_amount = NULL;
			$level->cycle_number = 1;
			$level->cycle_period = 'Year';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = 1;
			$level->expiration_period = 'Year';
		} elseif ($template === 'monthly') {
			$level->initial_payment = 25;
			$level->billing_amount = 25;
			$level->cycle_number = 1;
			$level->cycle_period = 'Month';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		} elseif ($template === 'annual') {
			$level->initial_payment = 100;
			$level->billing_amount = 100;
			$level->cycle_number = 1;
			$level->cycle_period = 'Year';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		} elseif ($template === 'lifetime') {
			$level->initial_payment = 500;
			$level->billing_amount = NULL;
			$level->cycle_number = 1;
			$level->cycle_period = 'Year';
			$level->billing_limit = NULL;
			$level->trial_amount = NULL;
			$level->trial_limit = NULL;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		} elseif ($template === 'trial') {
			$level->initial_payment = 0;
			$level->billing_amount = 25;
			$level->cycle_number = 1;
			$level->cycle_period = 'Month';
			$level->billing_limit = NULL;
			$level->trial_amount = 0;
			$level->trial_limit = 0;
			$level->expiration_number = NULL;
			$level->expiration_period = NULL;
		}
	}
	$level = apply_filters('pmpro_membershiplevels_template_level', $level, $template);
}

// Set some defaults for new levels.
if (empty($level->cycle_number)) {
	$level->cycle_number = 1;
}
if (empty($level->cycle_period)) {
	$level->cycle_period = 'Month';
}

// Grab the categories for the given level.
if (!empty($temp_id)) {
	$level->categories = $wpdb->get_col($wpdb->prepare(
		"
            SELECT c.category_id
            FROM $wpdb->pmpro_memberships_categories c
            WHERE c.membership_id = %d",
		$temp_id
	));
}

// If no categories, set up an empty array for the save event.
if (empty($level->categories)) {
	$level->categories = array();
}

// Grab the meta for the given level.
if (!empty($temp_id)) {
	$confirmation_in_email = get_pmpro_membership_level_meta($temp_id, 'confirmation_in_email', true);
} else {
	$confirmation_in_email = 0;
}
?>
<hr class="wp-header-end">
<?php if (!empty($level->id)) { ?>
	<h1 class="wp-heading-inline">
		<?php
		echo sprintf(
			// translators: %s is the Level ID.
			esc_html__('Edit Level ID: %s', 'paid-memberships-pro'),
			esc_attr($level->id)
		);
		?>
	</h1>
	<?php
	$view_checkout_url = pmpro_url('checkout', '?pmpro_level=' . $level->id, 'https');
	$view_orders_url = add_query_arg(array('page' => 'pmpro-orders', 'l' => $level->id, 'filter' => 'within-a-level'), admin_url('admin.php'));
	$view_members_url = add_query_arg(array('page' => 'pmpro-memberslist', 'l' => $level->id), admin_url('admin.php'));
	?>
	<a title="<?php esc_attr_e('View at Checkout', 'paid-memberships-pro'); ?>" href="<?php echo esc_url($view_checkout_url); ?>" target="_blank" class="page-title-action"><?php esc_html_e('View at Checkout', 'paid-memberships-pro'); ?></a>
	<a title="<?php esc_attr_e('View Members', 'paid-memberships-pro'); ?>" href="<?php echo esc_url($view_members_url); ?>" target="_blank" class="page-title-action"><?php esc_html_e('View Members', 'paid-memberships-pro'); ?></a>
	<a title="<?php esc_attr_e('View Orders', 'paid-memberships-pro'); ?>" href="<?php echo esc_url($view_orders_url); ?>" target="_blank" class="page-title-action"><?php esc_html_e('View Orders', 'paid-memberships-pro'); ?></a>
<?php } else { ?>
	<h1 class="wp-heading-inline"><?php esc_html_e('Add New Membership Level', 'paid-memberships-pro'); ?></h1>
<?php } ?>

<?php
// Show the settings page message.
if (!empty($page_msg)) { ?>
	<div class="inline notice notice-large <?php echo $page_msg > 0 ? 'notice-success' : 'notice-error'; ?>">
		<p><?php echo wp_kses_post( $page_msgt ); ?></p>
	</div>
<?php }
?>
<form action="" method="post" enctype="multipart/form-data">
	<input name="saveid" type="hidden" value="<?php echo esc_attr($edit); ?>" />
	<input type="hidden" name="action" value="save_membershiplevel" />
	<?php wp_nonce_field('save_membershiplevel', 'pmpro_membershiplevels_nonce'); ?>

	<div id="general-information" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e('General Information', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="name"><?php esc_html_e('Name', 'paid-memberships-pro'); ?></label></th>
						<td><input id="name" name="name" type="text" value="<?php echo esc_attr($level->name); ?>" class="regular-text" required /></td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="level_group"><?php esc_html_e('Group', 'paid-memberships-pro'); ?></label></th>
						<td>
							<select name="level_group" id="level_group">
								<?php
								if (empty($current_group)) {
									$current_group = pmpro_get_group_id_for_level($level->id);
								}
								foreach ($level_groups as $level_group) {
								?>
									<option value="<?php echo esc_attr($level_group->id); ?>" <?php selected($level_group->id, $current_group); ?>><?php echo esc_html($level_group->name); ?></option>
								<?php
								}
								?>
							</select>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="description"><?php esc_html_e('Description', 'paid-memberships-pro'); ?></label></th>
						<td class="pmpro_description">
							<?php wp_editor($level->description, 'description', array('textarea_rows' => 5)); ?>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="confirmation"><?php esc_html_e('Confirmation Message', 'paid-memberships-pro'); ?></label></th>
						<td class="pmpro_confirmation">
							<?php wp_editor($level->confirmation, 'confirmation', array('textarea_rows' => 5)); ?>
							<p><input id="confirmation_in_email" name="confirmation_in_email" type="checkbox" value="yes" <?php checked($confirmation_in_email, 1); ?> aria-describedby="confirmation_in_email_description" /> <label for="confirmation_in_email"><?php esc_html_e('Check to include this message in the membership confirmation email.', 'paid-memberships-pro'); ?></label></p>
							<p id="confirmation_in_email_description" class="description">
								<?php
								$allowed_confirmation_in_email_html = array(
									'a' => array(
										'href' => array(),
										'target' => array(),
										'title' => array(),
										'rel' => array(),
									),
									'code' => array(),
								);
								echo sprintf(wp_kses(__('Use the placeholder variable <code>%1$s</code> in your checkout <a href="%2$s" title="Edit Membership Email Templates">email templates</a> to include this information.', 'paid-memberships-pro'), $allowed_confirmation_in_email_html), '!!membership_level_confirmation_message!!', esc_url(add_query_arg('page', 'pmpro-emailtemplates', admin_url('admin.php'))));
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			/**
			 * Allow adding form fields inside the General Information section.
			 *
			 * @since 2.5.10
			 *
			 * @param object $level The Membership Level object.
			 */
			do_action('pmpro_membership_level_after_general_information', $level);
			?>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<?php
	/**
	 * Allow adding form fields before the Billing Information section.
	 *
	 * @since 2.9
	 *
	 * @param object $level The Membership Level object.
	 */
	do_action('pmpro_membership_level_before_billing_information', $level);
	?>

	<?php
	if (!pmpro_isLevelFree($level) || $template === 'none') {
		$section_visibility = 'shown';
		$section_activated = 'true';
	} else {
		$section_visibility = 'hidden';
		$section_activated = 'false';
	}
	?>
	<div id="billing-details" class="pmpro_section" data-visibility="<?php echo esc_attr($section_visibility); ?>" data-activated="<?php echo esc_attr($section_activated); ?>">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
				<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
				<?php esc_html_e('Billing Details', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside" <?php echo $section_visibility === 'hidden' ? 'style="display: none"' : ''; ?>>
			<?php
			$allowed_sd_html = array(
				'a' => array(
					'href' => array(),
					'target' => array(),
					'title' => array(),
				),
			);
			echo '<p>' . wp_kses(__('Set the member pricing for this level. The initial payment is collected immediately at checkout. Recurring payments, if applicable, begin one cycle after the initial payment. Changing the level price only applies to new members and does not affect existing members of this level.', 'paid-memberships-pro'), $allowed_sd_html) . '</p>';
			if (!function_exists('pmprosd_pmpro_membership_level_after_other_settings')) {
				echo '<p>' . sprintf(wp_kses(__('Optional: Allow more customizable trial periods and renewal dates using the <a href="%s" title="Paid Memberships Pro - Subscription Delays Add On" target="_blank" rel="nofollow noopener">Subscription Delays Add On</a>.', 'paid-memberships-pro'), $allowed_sd_html), 'https://www.paidmembershipspro.com/add-ons/subscription-delays/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=subscription-delays') . '</p>';
			}
			?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="initial_payment"><?php esc_html_e('Initial Payment', 'paid-memberships-pro'); ?></label></th>
						<td>
							<?php
							if (pmpro_getCurrencyPosition() == "left")
								echo wp_kses_post( $pmpro_currency_symbol );
							?>
							<input name="initial_payment" type="text" value="<?php echo esc_attr(pmpro_filter_price_for_text_field($level->initial_payment)); ?>" class="regular-text" />
							<?php
							if (pmpro_getCurrencyPosition() == "right")
								echo wp_kses_post( $pmpro_currency_symbol );
							?>
							<p class="description"><?php esc_html_e('The initial amount collected at registration.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e('Recurring Subscription', 'paid-memberships-pro'); ?></label></th>
						<td><input id="recurring" name="recurring" type="checkbox" value="yes" <?php if (pmpro_isLevelRecurring($level)) {
																									echo "checked='checked'";
																								} ?> onclick="if(jQuery('#recurring').is(':checked')) { jQuery('.recurring_info').show(); if(jQuery('#custom_trial').is(':checked')) {jQuery('.trial_info').show();} else {jQuery('.trial_info').hide();} } else { jQuery('.recurring_info').hide();}" /> <label for="recurring"><?php esc_html_e('Check if this level has a recurring subscription payment.', 'paid-memberships-pro'); ?></label></td>
					</tr>

					<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) { ?>style="display: none;" <?php } ?>>
						<th scope="row" valign="top"><label for="billing_amount"><?php esc_html_e('Billing Amount', 'paid-memberships-pro'); ?></label></th>
						<td>
							<?php
							if (pmpro_getCurrencyPosition() == "left")
								echo wp_kses_post( $pmpro_currency_symbol );
							?>
							<input name="billing_amount" type="text" value="<?php echo esc_attr(pmpro_filter_price_for_text_field($level->billing_amount)); ?>" class="regular-text" />
							<?php
							if (pmpro_getCurrencyPosition() == "right")
								echo wp_kses_post( $pmpro_currency_symbol );
							?>
							<?php esc_html_e('per', 'paid-memberships-pro'); ?>
							<input id="cycle_number" name="cycle_number" type="text" value="<?php echo esc_attr($level->cycle_number); ?>" class="small-text" />
							<select id="cycle_period" name="cycle_period">
								<?php
								$cycles = array(
									__('Day(s)', 'paid-memberships-pro') => 'Day',
									__('Week(s)', 'paid-memberships-pro') => 'Week',
									__('Month(s)', 'paid-memberships-pro') => 'Month',
									__('Year(s)', 'paid-memberships-pro') => 'Year',
								);
								foreach ($cycles as $name => $value) {
									echo '<option value="' . esc_attr( $value ) . '"';
									if (empty($level->cycle_period) && $value === 'Month') {
										echo 'selected';
									} else {
										selected($level->cycle_period, $value, true);
									}
									echo '>' . esc_html( $name ) . '</option>';
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e('The amount to be billed one cycle after the initial payment.', 'paid-memberships-pro'); ?>
								<?php if ($gateway == "braintree") { ?>
									<strong <?php if (!empty($pmpro_braintree_error)) { ?>class="pmpro_red" <?php } ?>><?php esc_html_e('Braintree integration currently only supports billing periods of "Month" or "Year".', 'paid-memberships-pro'); ?></strong>
								<?php } elseif ($gateway == "stripe") { ?>
							<p class="description"><strong <?php if (!empty($pmpro_stripe_error)) { ?>class="pmpro_red" <?php } ?>><?php esc_html_e('Stripe integration does not allow billing periods longer than 1 year.', 'paid-memberships-pro'); ?></strong></p>
						<?php } ?>
						</p>
						<?php if ($gateway == "braintree" && $edit < 0) { ?>
							<p class="pmpro_message"><strong><?php esc_html_e('Note', 'paid-memberships-pro'); ?>:</strong> <?php echo wp_kses_post(__('After saving this level, make note of the ID and create a "Plan" in your Braintree dashboard with the same settings and the "Plan ID" set to <em>pmpro_#</em>, where # is the level ID.', 'paid-memberships-pro')); ?></p>
						<?php } elseif ($gateway == "braintree") {
							$has_bt_plan = PMProGateway_braintree::checkLevelForPlan($level->id);
						?>
							<p class="pmpro_message <?php if (!$has_bt_plan) { ?>pmpro_error<?php } ?>">
								<strong><?php esc_html_e('Note', 'paid-memberships-pro'); ?>:</strong> <?php echo esc_html(sprintf(__('You will need to create a "Plan" in your Braintree dashboard with the same settings and the "Plan ID" set to %s.', 'paid-memberships-pro'), PMProGateway_braintree::get_plan_id($level->id))); ?>
							</p>
						<?php } ?>
						</td>
					</tr>
					<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) { ?>style="display: none;" <?php } ?>>
						<th scope="row" valign="top"><label for="billing_limit"><?php esc_html_e('Billing Cycle Limit', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input name="billing_limit" type="text" value="<?php echo esc_attr($level->billing_limit) ?>" class="small-text" />
							<p class="description">
								<?php echo wp_kses( __( 'The <strong>total</strong> number of recurring billing cycles for this level, including the trial period (if applicable) but not including the initial payment. Set to zero if membership is indefinite.', 'paid-memberships-pro'), array( 'strong' => array() ) ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			/**
			 * Allow adding form fields after the Billing Details Settings section.
			 *
			 * @since 2.5.10
			 *
			 * @param object $level The Membership Level object.
			 */
			do_action('pmpro_membership_level_after_billing_details_settings', $level);
			?>
			<table class="form-table">
				<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) echo "style='display:none;'"; ?>>
					<th scope="row" valign="top"><label><?php esc_html_e('Custom Trial', 'paid-memberships-pro'); ?></label></th>
					<td>
						<input id="custom_trial" name="custom_trial" type="checkbox" value="yes" <?php if (pmpro_isLevelTrial($level)) {
																										echo "checked='checked'";
																									} ?> onclick="jQuery('.trial_info').toggle();" /> <label for="custom_trial"><?php esc_html_e('Check to add a custom trial period.', 'paid-memberships-pro'); ?></label>

						<?php if ($gateway == "twocheckout") { ?>
							<p class="description"><strong <?php if (!empty($pmpro_twocheckout_error)) { ?>class="pmpro_red" <?php } ?>><?php esc_html_e('2Checkout integration does not support custom trials. You can do one period trials by setting an initial payment different from the billing amount.', 'paid-memberships-pro'); ?></strong></p>
						<?php } ?>
					</td>
				</tr>
				<tr class="trial_info recurring_info" <?php if (!pmpro_isLevelTrial($level)) echo "style='display:none;'"; ?>>
					<th scope="row" valign="top"><label for="trial_amount"><?php esc_html_e('Trial Billing Amount', 'paid-memberships-pro'); ?></label></th>
					<td>
						<?php
						if (pmpro_getCurrencyPosition() == "left")
							echo wp_kses_post( $pmpro_currency_symbol );
						?>
						<input name="trial_amount" type="text" value="<?php echo esc_attr(pmpro_filter_price_for_text_field($level->trial_amount)); ?>" class="regular-text" />
						<?php
						if (pmpro_getCurrencyPosition() == "right")
							echo wp_kses_post( $pmpro_currency_symbol );
						?>
						<?php esc_html_e('for the first', 'paid-memberships-pro'); ?>
						<input name="trial_limit" type="text" value="<?php echo esc_attr($level->trial_limit); ?>" class="small-text" />
						<?php esc_html_e('subscription payments', 'paid-memberships-pro'); ?>.
						<?php if ($gateway == "stripe") { ?>
							<p class="description"><strong <?php if (!empty($pmpro_stripe_error)) { ?>class="pmpro_red" <?php } ?>><?php esc_html_e('Stripe integration currently does not support trial amounts greater than $0.', 'paid-memberships-pro'); ?></strong></p>
						<?php } elseif ($gateway == "braintree") { ?>
							<p class="description"><strong <?php if (!empty($pmpro_braintree_error)) { ?>class="pmpro_red" <?php } ?>><?php esc_html_e('Braintree integration currently does not support trial amounts greater than $0.', 'paid-memberships-pro'); ?></strong></p>
						<?php } elseif ($gateway == "payflowpro") { ?>
							<p class="description"><strong <?php if (!empty($pmpro_payflow_error)) { ?>class="pmpro_red" <?php } ?>><?php esc_html_e('Payflow integration currently does not support trial amounts greater than $0.', 'paid-memberships-pro'); ?></strong></p>
						<?php } ?>
					</td>
				</tr>
				</tbody>
			</table>
			<?php
			/**
			 * Allow adding form fields after the Trial Settings section.
			 *
			 * @since 2.5.10
			 *
			 * @param object $level The Membership Level object.
			 */
			do_action('pmpro_membership_level_after_trial_settings', $level);
			?>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<?php
	if (pmpro_isLevelExpiring($level) || $template === 'none') {
		$section_visibility = 'shown';
		$section_activated = 'true';
	} else {
		$section_visibility = 'hidden';
		$section_activated = 'false';
	}
	?>
	<div id="expiration-details" class="pmpro_section" data-visibility="<?php echo esc_attr($section_visibility); ?>" data-activated="<?php echo esc_attr($section_activated); ?>">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
				<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
				<?php esc_html_e('Expiration Settings', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside" <?php echo $section_visibility === 'hidden' ? 'style="display: none"' : ''; ?>>
			<div id="pmpro_expiration_warning" style="display: none;" class="notice notice-alt notice-error inline">
				<p><?php
					$allowed_html = array(
						'a' => array(
							'target' => array(),
							'rel' => array(),
							'href' => array(),
						),
					);
					echo wp_kses( sprintf( __('WARNING: This level is set with both a recurring billing amount and an expiration date. You only need to set one of these unless you really want this membership to expire after a certain number of payments. For more information, <a target="_blank" rel="nofollow noopener" href="%s">see our post here</a>.', 'paid-memberships-pro'), 'https://www.paidmembershipspro.com/membership-level-recurring-billing-and-expiration-date/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=blog&utm_content=important-notes-on-recurring-billing-and-expiration-dates-for-membership-levels'), $allowed_html );
				?></p>
			</div>
			<script>
				jQuery(document).ready(function() {
					function pmpro_expirationWarningCheck() {
						if (jQuery('#recurring:checked').length && jQuery('#expiration:checked').length) {
							jQuery('#pmpro_expiration_warning').show();
						} else {
							jQuery('#pmpro_expiration_warning').hide();
						}
					}

					pmpro_expirationWarningCheck();

					jQuery('#recurring,#expiration').on('change',function() {
						pmpro_expirationWarningCheck();
					});
				});
			</script>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e('Membership Expiration', 'paid-memberships-pro'); ?></label></th>
						<td><input id="expiration" name="expiration" type="checkbox" value="yes" <?php if (pmpro_isLevelExpiring($level)) {
																										echo "checked='checked'";
																									} ?> onclick="if(jQuery('#expiration').is(':checked')) { jQuery('.expiration_info').show(); } else { jQuery('.expiration_info').hide();}" /> <label for="expiration"><?php esc_html_e('Check this to set when membership access expires.', 'paid-memberships-pro'); ?></label></a></td>
					</tr>
					<?php if (!function_exists('pmprosed_pmpro_membership_level_after_other_settings')) {
						$allowed_sed_html = array(
							'a' => array(
								'href' => array(),
								'title' => array(),
								'target' => array(),
								'rel' => array(),
							),
						);
						echo '<tr><th>&nbsp;</th><td><p class="description">' . sprintf(wp_kses(__('Optional: Allow more customizable expiration dates using the <a href="%s" title="Paid Memberships Pro - Set Expiration Date Add On" target="_blank" rel="nofollow noopener">Set Expiration Date Add On</a>.', 'paid-memberships-pro'), $allowed_sed_html), 'https://www.paidmembershipspro.com/add-ons/pmpro-expiration-date/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=pmpro-expiration-date') . '</p></td></tr>';
					} ?>
					<tr class="expiration_info" <?php if (!pmpro_isLevelExpiring($level)) { ?>style="display: none;" <?php } ?>>
						<th scope="row" valign="top"><label for="billing_amount"><?php esc_html_e('Expires In', 'paid-memberships-pro'); ?></label></th>
						<td>
							<input id="expiration_number" name="expiration_number" type="text" value="<?php echo esc_attr($level->expiration_number); ?>" class="small-text" />
							<select id="expiration_period" name="expiration_period">
								<?php
								$cycles = array(
									__('Hour(s)', 'paid-memberships-pro') => 'Hour',
									__('Day(s)', 'paid-memberships-pro') => 'Day',
									__('Week(s)', 'paid-memberships-pro') => 'Week',
									__('Month(s)', 'paid-memberships-pro') => 'Month',
									__('Year(s)', 'paid-memberships-pro') => 'Year',
								);
								foreach ($cycles as $name => $value) {
									echo '<option value="' . esc_attr( $value ) . '"';
									if (empty($level->expiration_period) && $value === 'Month') {
										echo 'selected';
									} else {
										selected($level->expiration_period, $value, true);
									}
									echo '>' . esc_html( $name ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e('Set the duration of membership access. Note that the any future payments (recurring subscription, if any) will be cancelled when the membership expires.', 'paid-memberships-pro'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			/**
			 * Allow adding form fields after the Expiration Settings section.
			 *
			 * @since 2.5.10
			 *
			 * @param object $level The Membership Level object.
			 */
			do_action('pmpro_membership_level_after_expiration_settings', $level);
			?>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<?php
	/**
	 * Allow adding form fields before the Content Settings Information section.
	 *
	 * @since 2.9
	 *
	 * @param object $level The Membership Level object.
	 */
	do_action('pmpro_membership_level_before_content_settings', $level);
	?>

	<div id="content-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e('Content Settings', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<?php
			$allowed_html = array(
				'a' => array(
					'href' => array(),
					'title' => array(),
					'target' => array(),
					'title' => array(),
				),
			);
			?>
			<p><?php echo wp_kses( sprintf( __('Protect access to posts, pages, and content sections with built-in PMPro features. If you want to protect more content types, <a href="%s" rel="nofollow noopener" target="_blank">read our documentation on restricting content</a>.', 'paid-memberships-pro'), 'https://www.paidmembershipspro.com/documentation/content-controls/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=documentation&utm_content=pmpro-content-settings'), $allowed_html ); ?></p>
			<table class="form-table">
				<tbody>
					<tr class="membership_categories">
						<th scope="row" valign="top"><label><?php esc_html_e('Categories', 'paid-memberships-pro'); ?></label></th>
						<td>
							<p><?php esc_html_e('Select:', 'paid-memberships-pro'); ?> <a id="pmpro-membership-categories-checklist-select-all" href="javascript:void(0);"><?php esc_html_e('All', 'paid-memberships-pro'); ?></a> | <a id="pmpro-membership-categories-checklist-select-none" href="javascript:void(0);"><?php esc_html_e('None', 'paid-memberships-pro'); ?></a></p>
							<script type="text/javascript">
								jQuery('#pmpro-membership-categories-checklist-select-all').on('click',function() {
									jQuery('#pmpro-membership-categories-checklist input').prop('checked', true);
								});
								jQuery('#pmpro-membership-categories-checklist-select-none').on('click',function() {
									jQuery('#pmpro-membership-categories-checklist input').prop('checked', false);
								});
							</script>
							<?php
							$args = array(
								'hide_empty' => false,
							);
							$cats = get_categories(apply_filters('pmpro_list_categories_args', $args));
							// Build the selectors for the checkbox list based on number of levels.
							$classes = array();
							$classes[] = 'pmpro_checkbox_box';

							if (count($cats) > 5) {
								$classes[] = 'pmpro_scrollable';
							}
							$class = implode(' ', array_unique($classes));
							?>
							<div id="pmpro-membership-categories-checklist" class="<?php echo esc_attr($class); ?>">
								<?php pmpro_listCategories(0, $level->categories); ?>
							</div>
							<p class="description">
								<?php esc_html_e('Select categories to bulk protect posts.', 'paid-memberships-pro'); ?>
								<?php
								// Get the Advanced Settings for filtering queries and showing excerpts.
								$filterqueries = get_option('pmpro_filterqueries');
								$showexcerpts = get_option("pmpro_showexcerpts");
								if ($filterqueries == 1) {
									// Show a message that posts in these categories are hidden.
									echo sprintf(wp_kses(__('Non-members will not see posts in these categories. You can <a href="%s" title="Advanced Settings" target="_blank">update this setting here</a>.', 'paid-memberships-pro'), $allowed_html), esc_url( admin_url('admin.php?page=pmpro-advancedsettings')));
								} else {
									if ($showexcerpts == 1) {
										// Show a message that posts in these categories will show title and excerpt.
										echo sprintf(wp_kses(__('Non-members will see the title and excerpt for posts in these categories. You can <a href="%s" title="Advanced Settings" target="_blank">update this setting here</a>.', 'paid-memberships-pro'), $allowed_html), esc_url( admin_url('admin.php?page=pmpro-advancedsettings')));
									} else {
										// Show a message that posts in these categories will show only the title.
										echo sprintf(wp_kses(__('Non-members will see the title only for posts in these categories. You can <a href="%s" title="Advanced Settings" target="_blank">update this setting here</a>.', 'paid-memberships-pro'), $allowed_html), esc_url( admin_url('admin.php?page=pmpro-advancedsettings')));
									}
								}
								?>
							</p>
						</td>
					</tr>
					<tr class="membership_posts">
						<th scope="row" valign="top"><label><?php esc_html_e('Single Posts', 'paid-memberships-pro'); ?></label></th>
						<td>
							<p><?php echo sprintf(wp_kses(__('<a target="_blank" href="%1$s">Add</a> or <a target="_blank" href="%2$s">edit</a> a single post to protect it.', 'paid-memberships-pro'), $allowed_html), esc_url(admin_url('post-new.php')), esc_url(admin_url('edit.php'))); ?></p>
						</td>
					</tr>
					<tr class="membership_posts">
						<th scope="row" valign="top"><label><?php esc_html_e('Single Pages', 'paid-memberships-pro'); ?></label></th>
						<td>
							<p><?php echo sprintf(wp_kses(__('<a target="_blank" href="%1$s">Add</a> or <a target="_blank" href="%2$s">edit</a> a single page to protect it.', 'paid-memberships-pro'), $allowed_html), esc_url(add_query_arg(array('post_type' => 'page'), admin_url('post-new.php'))), esc_url(add_query_arg(array('post_type' => 'page'), admin_url('edit.php')))); ?></p>
						</td>
					</tr>
					<tr class="membership_posts">
						<th scope="row" valign="top"><label><?php esc_html_e('Other Content Types', 'paid-memberships-pro'); ?></label></th>
						<td>
							<p><?php echo sprintf(wp_kses(__('Protect access to other content including custom post types (CPTs), courses, events, products, communities, podcasts, and more. <a href="%s" rel="nofollow noopener" target="_blank">Read our documentation on restricting content</a>.', 'paid-memberships-pro'), $allowed_html), 'https://www.paidmembershipspro.com/documentation/content-controls/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=documentation&utm_content=pmpro-content-settings'); ?></p>
						</td>
				</tbody>
			</table>
			<?php
			/**
			 * Allow adding form fields after the Content Settings section.
			 *
			 * @since 2.5.10
			 *
			 * @param object $level The Membership Level object.
			 */
			do_action('pmpro_membership_level_after_content_settings', $level);
			?>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<?php
	//'type' attribute is used to open "other settings" on page load.
	$is_addon = !empty($level_templates[$template]['type']) && $level_templates[$template]['type'] == 'add_on';
	if ($template == 'none' || $is_addon) {
		$section_visibility = 'shown';
		$section_activated = 'true';
	} else {
		$section_visibility = 'hidden';
		$section_activated = 'false';
	}
	?>
	<div id="other-settings" class="pmpro_section" data-visibility="<?php echo esc_attr($section_visibility); ?>" data-activated="<?php echo esc_attr($section_activated); ?>">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
				<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
				<?php esc_html_e('Other Settings', 'paid-memberships-pro'); ?>
			</button>
		</div>
		<div class="pmpro_section_inside" <?php echo $section_visibility === 'hidden' ? 'style="display: none"' : ''; ?>>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label><?php esc_html_e('Disable New Signups', 'paid-memberships-pro'); ?></label></th>
						<td><input id="disable_signups" name="disable_signups" type="checkbox" value="yes" <?php if ($level->id && !$level->allow_signups) { ?>checked="checked" <?php } ?> /> <label for="disable_signups"><?php esc_html_e('Check to hide this level from the membership levels page and disable registration.', 'paid-memberships-pro'); ?></label></td>
					</tr>
				</tbody>
			</table>
			<?php
			/**
			 * Allow adding form fields after the Other Settings section.
			 *
			 * @since 2.5.10
			 *
			 * @param object $level The Membership Level object.
			 */
			do_action('pmpro_membership_level_after_other_settings', $level);
			?>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->

	<p class="submit">
		<input name="save" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Level', 'paid-memberships-pro'); ?>" />
		<input name="cancel" type="button" class="button" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro'); ?>" onclick="location.href='<?php echo esc_url(add_query_arg('page', 'pmpro-membershiplevels', admin_url('admin.php'))); ?>';" />
	</p>
</form>