<?php
/**
 * @var int $edit
 */

global $wpdb, $msg, $msgt, $page_msg, $page_msgt, $pmpro_stripe_error, $pmpro_braintree_error, $pmpro_payflow_error, $pmpro_twocheckout_error, $pmpro_currency_symbol;

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

// Get the Membership Account Message via meta.
if ( ! empty( $temp_id ) ) {
	$membership_account_message = get_pmpro_membership_level_meta( $temp_id, 'membership_account_message', true );
} else {
	$membership_account_message = '';
}

// Get subscription delay and set expiration date settings.
// Uses same wp_options keys as the old add-on plugins for zero-migration compatibility.
if ( ! empty( $temp_id ) ) {
	$subscription_delay  = get_option( 'pmpro_subscription_delay_' . $temp_id, '' );
	$set_expiration_date = get_option( 'pmprosed_' . $temp_id, '' );
} else {
	$subscription_delay  = '';
	$set_expiration_date = '';
}

// Determine the delay type and expiration type for the UI.
if ( ! empty( $subscription_delay ) ) {
	$delay_type = is_numeric( $subscription_delay ) ? 'days' : 'date';
} else {
	$delay_type = 'none';
}

if ( ! empty( $set_expiration_date ) ) {
	$expiration_date_type = 'date';
	// Ensure expiration section shows as active when a date pattern is set.
	if ( empty( $level->expiration_number ) ) {
		$level->expiration_number = 1;
		$level->expiration_period = 'Year';
	}
} else {
	$expiration_date_type = 'none';
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

<p><?php
	$edit_level_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Membership Level Setup Documentation', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/membership-levels/initial-membership-level-setup/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=documentation&utm_content=&utm_term=">' . esc_html__( 'Membership Level Setup', 'paid-memberships-pro' ) . '</a>';
	// translators: %s: Link to Membership Level Setup doc.
	printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $edit_level_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?></p>

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
							<p class="description"><?php
								printf( 
									esc_html__( 'This text appears at checkout and on the pricing page if using the %s. Use it to provide a brief overview of the membership level, highlighting key features and benefits to potential members.', 'paid-memberships-pro' ),
									'<a target="_blank" href="https://www.paidmembershipspro.com/add-ons/pmpro-advanced-levels-shortcode/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=pmpro-advanced-levels-shortcode">' . esc_html__( 'Advanced Levels Page Add On', 'paid-memberships-pro' ) . '</a>' );
								?></p>
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
								echo sprintf(wp_kses(__('Use the placeholder variable <code>%1$s</code> in your checkout <a href="%2$s" title="Edit Membership Email Templates">email templates</a> to include this information.', 'paid-memberships-pro'), $allowed_confirmation_in_email_html), '{{ membership_level_confirmation_message }}', esc_url(add_query_arg('page', 'pmpro-emailtemplates', admin_url('admin.php'))));
								?>
							</p>
						</td>
					</tr>
					<tr>
            			<th scope="row" valign="top"><label for="membership_account_message"><?php esc_html_e( 'Membership Account Message', 'paid-memberships-pro'); ?></label></th>
            			<td class="pmpro_membership_account_message">
                			<?php wp_editor( $membership_account_message, 'membership_account_message', array( 'textarea_rows' => 5 ) ); ?>
                			<p class="description"><?php esc_html_e( 'This message appears only to members of this level in the "My Memberships" section of the account page. Use it to share benefits or link to content specific to this level.', 'paid-memberships-pro' ); ?></p>
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
					<tr class="recurring_info" <?php if ( ! pmpro_isLevelRecurring( $level ) ) { ?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top"><label><?php esc_html_e( 'First Recurring Payment', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<fieldset id="pmpro_subscription_delay_fieldset">
								<label>
									<input type="radio" name="delay_type" value="none" <?php checked( $delay_type, 'none' ); ?>
										onchange="pmpro_toggle_delay_fields();" />
									<?php esc_html_e( 'Default (one billing cycle after checkout)', 'paid-memberships-pro' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="delay_type" value="days" <?php checked( $delay_type, 'days' ); ?>
										onchange="pmpro_toggle_delay_fields();" />
									<?php esc_html_e( 'After a number of days (trial)', 'paid-memberships-pro' ); ?>
								</label>
								<span class="pmpro_delay_field pmpro_delay_field_days" <?php if ( $delay_type !== 'days' ) echo 'style="display:none;"'; ?>>
									&mdash;
									<input id="subscription_delay_days" name="subscription_delay_days" type="number" min="1"
										value="<?php echo esc_attr( $delay_type === 'days' ? $subscription_delay : '' ); ?>"
										class="small-text"
										oninput="pmpro_update_schedule_preview();" />
									<?php esc_html_e( 'days after checkout', 'paid-memberships-pro' ); ?>
								</span>
								<br />
								<label>
									<input type="radio" name="delay_type" value="date" <?php checked( $delay_type, 'date' ); ?>
										onchange="pmpro_toggle_delay_fields();" />
									<?php esc_html_e( 'On a specific date', 'paid-memberships-pro' ); ?>
								</label>
								<div class="pmpro_delay_field pmpro_delay_field_date" <?php if ( $delay_type !== 'date' ) echo 'style="display:none;"'; ?>>
									<div class="pmpro_date_pattern_builder" id="pmpro_delay_date_builder" data-existing-value="<?php echo esc_attr( $delay_type === 'date' ? $subscription_delay : '' ); ?>">
										<select class="pmpro_date_pattern_mode" onchange="pmpro_date_mode_changed(this);">
											<option value=""><?php esc_html_e( 'Choose...', 'paid-memberships-pro' ); ?></option>
											<option value="monthly"><?php esc_html_e( 'The same day each month', 'paid-memberships-pro' ); ?></option>
											<option value="yearly"><?php esc_html_e( 'The same date each year', 'paid-memberships-pro' ); ?></option>
											<option value="custom"><?php esc_html_e( 'Custom pattern', 'paid-memberships-pro' ); ?></option>
										</select>
										<span class="pmpro_date_builder_monthly" style="display:none;">
											<?php esc_html_e( 'on the', 'paid-memberships-pro' ); ?>
											<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);">
												<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
													<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
												<?php endfor; ?>
											</select>
										</span>
										<span class="pmpro_date_builder_yearly" style="display:none;">
											<?php esc_html_e( 'on', 'paid-memberships-pro' ); ?>
											<select class="pmpro_date_builder_month" onchange="pmpro_assemble_date_pattern(this);">
												<?php
												$month_names = array(
													'01' => __( 'January', 'paid-memberships-pro' ), '02' => __( 'February', 'paid-memberships-pro' ),
													'03' => __( 'March', 'paid-memberships-pro' ), '04' => __( 'April', 'paid-memberships-pro' ),
													'05' => __( 'May', 'paid-memberships-pro' ), '06' => __( 'June', 'paid-memberships-pro' ),
													'07' => __( 'July', 'paid-memberships-pro' ), '08' => __( 'August', 'paid-memberships-pro' ),
													'09' => __( 'September', 'paid-memberships-pro' ), '10' => __( 'October', 'paid-memberships-pro' ),
													'11' => __( 'November', 'paid-memberships-pro' ), '12' => __( 'December', 'paid-memberships-pro' ),
												);
												foreach ( $month_names as $val => $name ) :
												?>
													<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $name ); ?></option>
												<?php endforeach; ?>
											</select>
											<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);">
												<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
													<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
												<?php endfor; ?>
											</select>
										</span>
										<span class="pmpro_date_builder_custom" style="display:none;">
											<input id="subscription_delay_date" type="text"
												value="<?php echo esc_attr( $delay_type === 'date' ? $subscription_delay : '' ); ?>"
												class="pmpro_date_pattern_input" placeholder="<?php esc_attr_e( 'e.g. Y-01-01', 'paid-memberships-pro' ); ?>"
												oninput="jQuery(this).closest('.pmpro_date_pattern_builder').find('.pmpro_date_pattern_value').val(this.value); pmpro_update_schedule_preview();" />
											<p class="description"><?php echo esc_html__( 'Y = current/next year, M = current/next month.', 'paid-memberships-pro' ); ?></p>
										</span>
										<input type="hidden" class="pmpro_date_pattern_value" name="subscription_delay_date" value="<?php echo esc_attr( $delay_type === 'date' ? $subscription_delay : '' ); ?>" />
									</div>
								</div>
							</fieldset>
						</td>
					</tr>
					</tbody>
			</table>
			<div class="pmpro_schedule_preview_inline recurring_info" <?php if ( ! pmpro_isLevelRecurring( $level ) ) { ?>style="display: none;"<?php } ?>>
				<div class="pmpro_schedule_preview_bar">
					<span class="pmpro_schedule_preview_title"><?php esc_html_e( 'Payment Schedule Preview', 'paid-memberships-pro' ); ?></span>
					<input type="hidden" id="pmpro_preview_checkout_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" />
					<div id="pmpro_schedule_timeline" class="pmpro_schedule_timeline">
						<div class="pmpro_schedule_timeline_loading"><?php esc_html_e( 'Configure billing settings to see a preview.', 'paid-memberships-pro' ); ?></div>
					</div>
				</div>
			</div>
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
			<?php
			// Only show trial settings if the active gateway supports recurring trials or if the level already has a trial set.
			$gateway_class = 'PMProGateway_' . $gateway;
			$gateway_supports_recurring_trials = method_exists( $gateway_class, 'supports' ) && $gateway_class::supports( 'recurring_trials' );
			if ( $gateway_supports_recurring_trials || pmpro_isLevelTrial( $level ) ) {
			?>
			<table class="form-table">
				<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) echo "style='display:none;'"; ?>>
					<th scope="row" valign="top"><label><?php esc_html_e('Custom Trial', 'paid-memberships-pro'); ?></label></th>
					<td>
						<input id="custom_trial" name="custom_trial" type="checkbox" value="yes" <?php if (pmpro_isLevelTrial($level)) {
																										echo "checked='checked'";
																									} ?> onclick="jQuery('.trial_info').toggle();" /> <label for="custom_trial"><?php esc_html_e('Check to add a custom trial period.', 'paid-memberships-pro'); ?></label>
						<?php if ( ! $gateway_supports_recurring_trials ) { ?>
							<p class="description"><strong class="pmpro_red"><?php esc_html_e( 'The current payment gateway does not support recurring trials.', 'paid-memberships-pro' ); ?></strong></p>
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
					</td>
				</tr>
				</tbody>
			</table>
			<?php } ?>
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
			<p><?php esc_html_e('Control when membership access ends for this level. If left unchecked, membership access will not expire. For recurring memberships, leave expiration unchecked to continue charging members according to your billing settings.', 'paid-memberships-pro'); ?></p>
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
																									} ?> onclick="if(jQuery('#expiration').is(':checked')) { jQuery('.expiration_info').show(); } else { jQuery('.expiration_info').hide();} pmpro_update_schedule_preview();" /> <label for="expiration"><?php esc_html_e('Check this to set when membership access expires.', 'paid-memberships-pro'); ?></label></td>
					</tr>
					<tr class="expiration_info" <?php if ( ! pmpro_isLevelExpiring( $level ) ) { ?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Expiration Type', 'paid-memberships-pro' ); ?></label></th>
						<td>
							<fieldset id="pmpro_expiration_type_fieldset">
								<label>
									<input type="radio" name="expiration_date_type" value="none" <?php checked( $expiration_date_type, 'none' ); ?>
										onchange="pmpro_toggle_expiration_type();" />
									<?php esc_html_e( 'After a set duration', 'paid-memberships-pro' ); ?>
								</label>
								<div class="pmpro_expiration_duration_fields" <?php if ( $expiration_date_type === 'date' ) echo 'style="display:none;"'; ?>>
									<input id="expiration_number" name="expiration_number" type="text" value="<?php echo esc_attr($level->expiration_number); ?>" class="small-text" oninput="pmpro_update_schedule_preview();" />
									<select id="expiration_period" name="expiration_period" onchange="pmpro_update_schedule_preview();">
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
									<p class="description"><?php esc_html_e('Membership access will end this long after checkout. Any recurring subscription will be cancelled at that time.', 'paid-memberships-pro'); ?></p>
								</div>
								<br />
								<label>
									<input type="radio" name="expiration_date_type" value="date" <?php checked( $expiration_date_type, 'date' ); ?>
										onchange="pmpro_toggle_expiration_type();" />
									<?php esc_html_e( 'On a specific date', 'paid-memberships-pro' ); ?>
								</label>
								<div class="pmpro_expiration_date_field" <?php if ( $expiration_date_type !== 'date' ) echo 'style="display:none;"'; ?>>
									<div class="pmpro_date_pattern_builder" id="pmpro_expiration_date_builder" data-existing-value="<?php echo esc_attr( $set_expiration_date ); ?>">
										<select class="pmpro_date_pattern_mode" onchange="pmpro_date_mode_changed(this);">
											<option value=""><?php esc_html_e( 'Choose...', 'paid-memberships-pro' ); ?></option>
											<option value="monthly"><?php esc_html_e( 'The same day each month', 'paid-memberships-pro' ); ?></option>
											<option value="yearly"><?php esc_html_e( 'The same date each year', 'paid-memberships-pro' ); ?></option>
											<option value="custom"><?php esc_html_e( 'Custom pattern', 'paid-memberships-pro' ); ?></option>
										</select>
										<span class="pmpro_date_builder_monthly" style="display:none;">
											<?php esc_html_e( 'on the', 'paid-memberships-pro' ); ?>
											<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);">
												<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
													<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
												<?php endfor; ?>
											</select>
										</span>
										<span class="pmpro_date_builder_yearly" style="display:none;">
											<?php esc_html_e( 'on', 'paid-memberships-pro' ); ?>
											<select class="pmpro_date_builder_month" onchange="pmpro_assemble_date_pattern(this);">
												<?php foreach ( $month_names as $val => $name ) : ?>
													<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $name ); ?></option>
												<?php endforeach; ?>
											</select>
											<select class="pmpro_date_builder_day" onchange="pmpro_assemble_date_pattern(this);">
												<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
													<option value="<?php echo esc_attr( str_pad( $d, 2, '0', STR_PAD_LEFT ) ); ?>"><?php echo esc_html( pmpro_format_day_ordinal( $d ) ); ?></option>
												<?php endfor; ?>
											</select>
										</span>
										<span class="pmpro_date_builder_custom" style="display:none;">
											<input id="set_expiration_date" type="text"
												value="<?php echo esc_attr( $set_expiration_date ); ?>"
												class="pmpro_date_pattern_input" placeholder="<?php esc_attr_e( 'e.g. Y-12-31', 'paid-memberships-pro' ); ?>"
												oninput="jQuery(this).closest('.pmpro_date_pattern_builder').find('.pmpro_date_pattern_value').val(this.value); pmpro_update_schedule_preview();" />
											<p class="description"><?php echo esc_html__( 'Y = current/next year, M = current/next month.', 'paid-memberships-pro' ); ?></p>
										</span>
										<input type="hidden" class="pmpro_date_pattern_value" name="set_expiration_date" value="<?php echo esc_attr( $set_expiration_date ); ?>" />
									</div>
								</div>
							</fieldset>
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
							<p><?php echo sprintf(wp_kses(__('Protect access to other content including custom post types (CPTs), courses, events, products, communities, podcasts, and more. <a href="%s" rel="nofollow noopener" target="_blank">Read our documentation on restricting content</a>.', 'paid-memberships-pro'), $allowed_html), 'https://www.paidmembershipspro.com/restrict-access-wordpress/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=blog&utm_content=pmpro-content-settings'); ?></p>
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
<script type="text/javascript">
(function($) {
	'use strict';

	var previewDebounceTimer = null;
	var monthsShort = [
		'<?php echo esc_js( __( 'Jan', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Feb', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Mar', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Apr', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'May', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Jun', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Jul', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Aug', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Sep', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Oct', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Nov', 'paid-memberships-pro' ) ); ?>',
		'<?php echo esc_js( __( 'Dec', 'paid-memberships-pro' ) ); ?>'
	];

	/**
	 * Get the effective date value for a builder (always from the hidden field).
	 */
	function getBuilderValue($builder) {
		return $builder.find('.pmpro_date_pattern_value').val();
	}

	/* ── Toggle functions ── */

	window.pmpro_toggle_delay_fields = function() {
		var delayType = $('input[name="delay_type"]:checked').val();
		$('.pmpro_delay_field_days').toggle(delayType === 'days');
		$('.pmpro_delay_field_date').toggle(delayType === 'date');
		pmpro_update_schedule_preview();
	};

	window.pmpro_toggle_expiration_type = function() {
		var expType = $('input[name="expiration_date_type"]:checked').val();
		$('.pmpro_expiration_duration_fields').toggle(expType === 'none');
		$('.pmpro_expiration_date_field').toggle(expType === 'date');
		pmpro_update_schedule_preview();
	};

	// Keep old name as alias in case hooks use it.
	window.pmpro_toggle_expiration_date_fields = window.pmpro_toggle_expiration_type;

	/* ── Schedule Preview (client-side) ── */

	var currencySymbol = '<?php echo esc_js( wp_strip_all_tags( $pmpro_currency_symbol ) ); ?>';
	var currencyLeft = <?php echo pmpro_getCurrencyPosition() === 'left' ? 'true' : 'false'; ?>;

	function formatPrice(amount) {
		var n = parseFloat(amount) || 0;
		var formatted = n.toFixed(2);
		return currencyLeft ? currencySymbol + formatted : formatted + currencySymbol;
	}

	/**
	 * Add an interval to a Date. Returns a new Date.
	 */
	function addInterval(date, number, period) {
		var d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
		switch (period) {
			case 'Day':   d.setDate(d.getDate() + number); break;
			case 'Week':  d.setDate(d.getDate() + number * 7); break;
			case 'Month': d.setMonth(d.getMonth() + number); break;
			case 'Year':  d.setFullYear(d.getFullYear() + number); break;
			case 'Hour':  d.setDate(d.getDate() + Math.ceil(number / 24)); break;
		}
		return d;
	}

	/**
	 * Resolve a date pattern string (like Y-M-15 or Y-06-30) relative to a base date.
	 * Client-side equivalent of pmpro_convert_date_pattern().
	 */
	function resolveDatePattern(pattern, baseDate) {
		if (!pattern || pattern.length > 20) return null;
		try {
			var s = pattern.toUpperCase().replace(/Y-/,'Y1-').replace(/M-/,'M1-');
			var addYears = 0, addMonths = 0;
			var ym = s.match(/Y(\d+)/);
			if (ym) addYears = Math.min(parseInt(ym[1]) || 1, 10);
			var mm = s.match(/M(\d+)/);
			if (mm) addMonths = Math.min(parseInt(mm[1]) || 1, 24);

			var parts = s.split('-');
			if (parts.length < 3) return null;
			var setY = parseInt(parts[0]) || 0;
			var setM = parseInt(parts[1]) || 0;
			var setD = parseInt(parts[2]) || 1;
			var curY = baseDate.getFullYear(), curM = baseDate.getMonth() + 1, curD = baseDate.getDate();
			var tmpY = setY > 0 ? setY : curY;
			var tmpM = setM > 0 ? setM : curM;
			var tmpD = Math.max(1, Math.min(setD, 31));

			// Add months (capped iterations).
			var monthIter = addMonths;
			for (var i = 0; i < monthIter && i < 24; i++) {
				if (i === 0) {
					if (tmpD < curD) { tmpM++; monthIter--; }
				} else { tmpM++; }
				if (tmpM === 13) { tmpM = 1; tmpY++; addYears--; }
			}
			// Add years (capped iterations).
			var yearIter = addYears;
			for (var i = 0; i < yearIter && i < 10; i++) {
				if (i === 0) {
					var tmpDate = new Date(tmpY, tmpM - 1, tmpD);
					if (tmpDate < baseDate) { tmpY++; yearIter--; }
				} else { tmpY++; }
			}
			// Clamp day to valid range for the month.
			var maxDay = new Date(tmpY, tmpM, 0).getDate();
			if (tmpD > maxDay) tmpD = maxDay;
			var result = new Date(tmpY, tmpM - 1, tmpD);
			return isNaN(result.getTime()) ? null : result;
		} catch(e) {
			return null;
		}
	}

	function dateToStr(d) {
		return d.getFullYear() + '-' +
			String(d.getMonth() + 1).padStart(2, '0') + '-' +
			String(d.getDate()).padStart(2, '0');
	}

	window.pmpro_update_schedule_preview = function() {
		clearTimeout(previewDebounceTimer);
		previewDebounceTimer = setTimeout(pmpro_do_schedule_preview, 150);
	};

	function pmpro_do_schedule_preview() {
		var $timeline = $('#pmpro_schedule_timeline');
		try {
			var isRecurring = $('#recurring').is(':checked');
			var hasExpiration = $('#expiration').is(':checked');

			if (!isRecurring) {
				$timeline.html('<div class="pmpro_schedule_timeline_empty"><?php echo esc_js( __( 'Enable recurring billing to see a payment schedule.', 'paid-memberships-pro' ) ); ?></div>');
				return;
			}

			// Read form values.
			var checkoutStr = $('#pmpro_preview_checkout_date').val() || dateToStr(new Date());
			var parts = checkoutStr.split('-');
			var checkout = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
			if (isNaN(checkout.getTime())) { checkout = new Date(); }

			var initialPayment = parseFloat($('input[name="initial_payment"]').val()) || 0;
			var billingAmount = parseFloat($('input[name="billing_amount"]').val()) || 0;
			var cycleNumber = parseInt($('input[name="cycle_number"]').val()) || 1;
			if (cycleNumber < 1) cycleNumber = 1;
			var cyclePeriod = $('select[name="cycle_period"]').val() || 'Month';
			var billingLimit = parseInt($('input[name="billing_limit"]').val()) || 0;
			var hasCustomTrial = $('#custom_trial').is(':checked');

			var delayType = $('input[name="delay_type"]:checked').val() || 'none';
			var subscriptionDelay = '';
			if (delayType === 'days') {
				subscriptionDelay = $('#subscription_delay_days').val();
			} else if (delayType === 'date') {
				subscriptionDelay = getBuilderValue($('#pmpro_delay_date_builder'));
			}

			var expirationDateType = $('input[name="expiration_date_type"]:checked').val() || 'none';
			var expirationNumber = parseInt($('#expiration_number').val()) || 0;
			var expirationPeriod = $('#expiration_period').val() || 'Month';
			var setExpirationDate = '';
			if (expirationDateType === 'date') {
				setExpirationDate = getBuilderValue($('#pmpro_expiration_date_builder'));
			}

			var events = [];

			// 1. Determine the expiration date (if any).
			var expirationDate = null;
			if (hasExpiration) {
				if (expirationDateType === 'date' && setExpirationDate) {
					expirationDate = resolveDatePattern(setExpirationDate, checkout);
					if (expirationDate && (isNaN(expirationDate.getTime()) || expirationDate <= checkout)) {
						expirationDate = null;
					}
				} else if (expirationNumber > 0) {
					expirationDate = addInterval(checkout, expirationNumber, expirationPeriod);
					if (isNaN(expirationDate.getTime())) expirationDate = null;
				}
			}

			// 2. Determine the first recurring payment date.
			var firstRecurring = null;
			if (billingAmount > 0) {
				if (delayType === 'days' && subscriptionDelay && !isNaN(subscriptionDelay) && parseInt(subscriptionDelay) > 0) {
					firstRecurring = addInterval(checkout, parseInt(subscriptionDelay), 'Day');
				} else if (delayType === 'date' && subscriptionDelay) {
					firstRecurring = resolveDatePattern(subscriptionDelay, checkout);
					if (!firstRecurring || isNaN(firstRecurring.getTime()) || firstRecurring <= checkout) {
						firstRecurring = addInterval(checkout, cycleNumber, cyclePeriod);
					}
				} else {
					firstRecurring = addInterval(checkout, cycleNumber, cyclePeriod);
				}
			}

			// 3. Generate all payment dates (up to a safe max).
			var allPayments = [];
			if (firstRecurring) {
				var safeMax = billingLimit > 0 ? billingLimit : 100;
				for (var i = 0; i < safeMax; i++) {
					var payDate = (i === 0) ? firstRecurring : addInterval(firstRecurring, cycleNumber * i, cyclePeriod);
					if (isNaN(payDate.getTime())) break;
					if (expirationDate && payDate >= expirationDate) break;
					allPayments.push(payDate);
				}
			}
			var totalPayments = allPayments.length;
			var hitBillingLimit = (billingLimit > 0 && totalPayments === billingLimit);

			// 4. Build the event list.
			// Initial payment.
			events.push({
				date: dateToStr(checkout),
				type: 'initial',
				amount: formatPrice(initialPayment)
			});

			// Show up to 5 payments inline, then "..." if more, then the last payment.
			var maxInline = 5;
			if (totalPayments > 0) {
				var inlineCount = Math.min(totalPayments, maxInline);
				if (totalPayments === maxInline + 1) inlineCount = totalPayments;

				for (var i = 0; i < inlineCount; i++) {
					var isLast = (i === totalPayments - 1);
					events.push({
						date: dateToStr(allPayments[i]),
						type: (isLast && hitBillingLimit) ? 'last_payment' : 'recurring',
						amount: formatPrice(billingAmount),
						number: i + 1
					});
				}

				if (totalPayments > inlineCount) {
					events.push({ date: '', type: 'continuation' });
					events.push({
						date: dateToStr(allPayments[totalPayments - 1]),
						type: hitBillingLimit ? 'last_payment' : 'recurring',
						amount: formatPrice(billingAmount),
						number: totalPayments
					});
				}

				if (!hitBillingLimit && !expirationDate) {
					events.push({ date: '', type: 'continuation' });
				}
			}


			// 5. Expiration marker (always at the end of the timeline).
			if (expirationDate) {
				events.push({
					date: dateToStr(expirationDate),
					type: 'expiration'
				});
			}

			renderTimeline(events);
		} catch (e) {
			$timeline.html('<div class="pmpro_schedule_timeline_empty"><?php echo esc_js( __( 'Unable to generate preview.', 'paid-memberships-pro' ) ); ?></div>');
		}
	}

	function renderTimeline(events) {
		var $timeline = $('#pmpro_schedule_timeline');
		var html = '<div class="pmpro_htimeline">';
		var todayStr = dateToStr(new Date());
		var checkoutDateStr = $('#pmpro_preview_checkout_date').val() || todayStr;
		var isToday = (checkoutDateStr === todayStr);

		for (var i = 0; i < events.length; i++) {
			var event = events[i];
			var typeClass = 'pmpro_htimeline_item--' + event.type;
			var formattedDate = event.date ? formatPreviewDate(event.date) : '';
			var shortLabel = '';
			var amountLabel = '';

			var subtitle = '';
			switch (event.type) {
				case 'initial':
					shortLabel = '<?php echo esc_js( __( 'Checkout', 'paid-memberships-pro' ) ); ?>';
					amountLabel = event.amount || '';
					break;
				case 'recurring':
					shortLabel = '#' + (event.number || '?');
					amountLabel = event.amount || '';
					break;
				case 'last_payment':
					shortLabel = '<?php echo esc_js( __( 'Last Payment', 'paid-memberships-pro' ) ); ?>';
					amountLabel = event.amount || '';
					subtitle = '<?php echo esc_js( __( 'Billing stops, membership continues', 'paid-memberships-pro' ) ); ?>';
					break;
				case 'expiration':
					shortLabel = '<?php echo esc_js( __( 'Membership Ends', 'paid-memberships-pro' ) ); ?>';
					subtitle = '<?php echo esc_js( __( 'Access revoked, billing cancelled', 'paid-memberships-pro' ) ); ?>';
					break;
				case 'continuation':
					shortLabel = '&hellip;';
					break;
			}

			html += '<div class="pmpro_htimeline_item ' + typeClass + '">';
			if (event.type === 'initial') {
				// Checkout item: inline date picker.
				html += '<div class="pmpro_htimeline_dot pmpro_htimeline_dot--calendar"><span class="dashicons dashicons-calendar-alt"></span></div>';
				html += '<div class="pmpro_htimeline_label">' + shortLabel + '</div>';
				if (amountLabel) {
					html += '<div class="pmpro_htimeline_amount">' + amountLabel + '</div>';
				}
				html += '<div class="pmpro_htimeline_date">';
				html += '<input type="date" class="pmpro_htimeline_date_input" value="' + checkoutDateStr + '" onchange="pmpro_set_checkout_date(this.value);" />';
				html += '</div>';
			} else {
				html += '<div class="pmpro_htimeline_dot"></div>';
				html += '<div class="pmpro_htimeline_label">' + shortLabel + '</div>';
				if (amountLabel) {
					html += '<div class="pmpro_htimeline_amount">' + amountLabel + '</div>';
				}
				if (formattedDate) {
					html += '<div class="pmpro_htimeline_date">' + escapeHtml(formattedDate) + '</div>';
				}
				if (subtitle) {
					html += '<div class="pmpro_htimeline_subtitle">' + subtitle + '</div>';
				}
			}
			html += '</div>';
			if (i < events.length - 1) {
				html += '<div class="pmpro_htimeline_connector"></div>';
			}
		}
		html += '</div>';
		if ($('#custom_trial').is(':checked')) {
			html += '<div class="pmpro_htimeline_footnote"><?php echo esc_js( __( 'Note: Custom trial pricing is active. The first payment amounts shown above may differ at checkout.', 'paid-memberships-pro' ) ); ?></div>';
		}
		$timeline.html(html);
	}

	window.pmpro_set_checkout_date = function(val) {
		$('#pmpro_preview_checkout_date').val(val);
		pmpro_update_schedule_preview();
	};

	function formatPreviewDate(dateStr) {
		var parts = dateStr.split('-');
		return monthsShort[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(text));
		return div.innerHTML;
	}

	/* ── Init ── */

	$(document).ready(function() {
		// Initialize all date pattern builders from their data attributes.
		$('.pmpro_date_pattern_builder').each(function() {
			var val = $(this).data('existing-value');
			if (val) {
				pmpro_initDateBuilder($(this), val);
			}
		});

		// Trigger preview on page load.
		pmpro_update_schedule_preview();

		$('#pmpro_preview_checkout_date').on('change', pmpro_update_schedule_preview);

		// Watch all billing/expiration form fields.
		$('input[name="initial_payment"], input[name="billing_amount"], input[name="cycle_number"], input[name="billing_limit"], #expiration_number').on('input change', pmpro_update_schedule_preview);
		$('select[name="cycle_period"], #expiration_period').on('change', pmpro_update_schedule_preview);
		$('#recurring, #expiration').on('change', function() {
			$('.pmpro_schedule_preview_inline').toggle($('#recurring').is(':checked'));
			pmpro_update_schedule_preview();
		});
	});
})(jQuery);
</script>