<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_discountcodes")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	//vars
	global $wpdb, $pmpro_currency_symbol, $pmpro_stripe_error, $pmpro_braintree_error, $pmpro_pages, $gateway;

	$now = current_time( 'timestamp' );

	if(isset($_REQUEST['edit']))
		$edit = intval($_REQUEST['edit']);
	else
		$edit = false;

	if(isset($_REQUEST['copy']))
		$copy = intval($_REQUEST['copy']);
	else
		$copy = false;

	if(isset($_REQUEST['delete']))
		$delete = intval($_REQUEST['delete']);
	else
		$delete = false;

	if(isset($_REQUEST['saveid']))
		$saveid = intval($_POST['saveid']);
	else
		$saveid = false;

	if(isset($_REQUEST['s']))
		$s = sanitize_text_field($_REQUEST['s']);
	else
		$s = "";

	if ( isset( $_REQUEST['limit'] ) ) {
		$limit = intval( $_REQUEST['limit'] );
	} else {
		/**
		 * Filter to set the default number of items to show per page
		 * on the Discount Codes page in the admin.
		 *
		 * @since 1.9.4
		 *
		 * @param int $limit The number of items to show per page.
		 */
		$limit = apply_filters( 'pmpro_discount_codes_per_page', 15 );
	}

	//check nonce for saving codes
	if (!empty($saveid) && (empty($_REQUEST['pmpro_discountcodes_nonce']) || !check_admin_referer('save', 'pmpro_discountcodes_nonce'))) {
		$pmpro_msgt = 'error';
		$pmpro_msg = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		$saveid = false;
	}

	if($saveid)
	{
		//get vars
		//disallow/strip all non-alphanumeric characters except -
		$code = preg_replace("/[^A-Za-z0-9\-]/", "", sanitize_text_field($_POST['code']));
		$starts_month = intval($_POST['starts_month']);
		$starts_day = intval($_POST['starts_day']);
		$starts_year = intval($_POST['starts_year']);
		$expires_month = intval($_POST['expires_month']);
		$expires_day = intval($_POST['expires_day']);
		$expires_year = intval($_POST['expires_year']);
		$uses = intval($_POST['uses']);
		$one_use_per_user = ! empty( $_POST['one_use_per_user'] ) ? 1 : 0;

		//fix up dates
		$starts = date("Y-m-d", strtotime($starts_month . "/" . $starts_day . "/" . $starts_year, $now ));
		$expires = date("Y-m-d", strtotime($expires_month . "/" . $expires_day . "/" . $expires_year, $now ));

		//insert/update/replace discount code
		pmpro_insert_or_replace(
			$wpdb->pmpro_discount_codes,
			array(
				'id'=>max($saveid, 0),
				'code' => $code,
				'starts' => $starts,
				'expires' => $expires,
				'uses' => $uses,
				'one_use_per_user' => $one_use_per_user
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d'
			)
		);

		//check for errors and show appropriate message if inserted or updated
		if(empty($wpdb->last_error)) {
			if($saveid < 1) {
				//insert
				$pmpro_msg = __("Discount code added successfully.", 'paid-memberships-pro' );
				$pmpro_msgt = "success";
				$saved = true;
				$edit = $wpdb->insert_id;
			} else {
				//updated
				$pmpro_msg = __("Discount code updated successfully.", 'paid-memberships-pro' );
				$pmpro_msgt = "success";
				$saved = true;
				$edit = $saveid;
			}
		} else {
			if($saveid < 1) {
				//error inserting
				$pmpro_msg = __("Error adding discount code. That code may already be in use.", 'paid-memberships-pro' ) . $wpdb->last_error;
				$pmpro_msgt = "error";
			} else {
				//error updating
				$pmpro_msg = __("Error updating discount code. That code may already be in use.", 'paid-memberships-pro' );
				$pmpro_msgt = "error";
			}
		}

		//now add the membership level rows
		if($saved && $edit > 0)
		{
			//get the submitted values
			$all_levels_a = array_map( 'intval', $_REQUEST['all_levels'] );
			if(!empty($_REQUEST['levels']))
				$levels_a = array_map( 'intval', $_REQUEST['levels'] );
			else
				$levels_a = array();
			$initial_payment_a = array_map( 'sanitize_text_field', $_REQUEST['initial_payment'] );

			if(!empty($_REQUEST['recurring']))
				$recurring_a = array_map( 'intval', $_REQUEST['recurring'] );
			$billing_amount_a = array_map( 'sanitize_text_field', $_REQUEST['billing_amount'] );
			$cycle_number_a = array_map( 'intval', $_REQUEST['cycle_number'] );
			$cycle_period_a = array_map( 'sanitize_text_field', $_REQUEST['cycle_period'] );
			$billing_limit_a = array_map( 'intval', $_REQUEST['billing_limit'] );

			if(!empty($_REQUEST['custom_trial']))
				$custom_trial_a = array_map( 'intval', $_REQUEST['custom_trial'] );
			$trial_amount_a = ! empty( $_REQUEST['trial_amount'] ) ? array_map( 'sanitize_text_field', $_REQUEST['trial_amount'] ) : array();
			$trial_limit_a = ! empty( $_REQUEST['trial_limit'] ) ? array_map( 'intval', $_REQUEST['trial_limit'] ) : array();

			if(!empty($_REQUEST['expiration']))
				$expiration_a = array_map( 'intval', $_REQUEST['expiration'] );
			$expiration_number_a = array_map( 'intval', $_REQUEST['expiration_number'] );
			$expiration_period_a = array_map( 'sanitize_text_field', $_REQUEST['expiration_period'] );

			//clear the old rows
			$wpdb->delete($wpdb->pmpro_discount_codes_levels, array('code_id' => $edit), array('%d'));

			//add a row for each checked level
			if(!empty($levels_a))
			{
				foreach($levels_a as $level_id)
				{
					$level_id = intval($level_id);	//sanitized

					//get the values ready
					$n = array_search($level_id, $all_levels_a); 	//this is the key location of this level's values
					$initial_payment = sanitize_text_field($initial_payment_a[$n]);

					//is this recurring?
					if(!empty($recurring_a))
					{
						if(in_array($level_id, $recurring_a))
							$recurring = 1;
						else
							$recurring = 0;
					}
					else
						$recurring = 0;

					if(!empty($recurring))
					{
						$billing_amount = sanitize_text_field($billing_amount_a[$n]);
						$cycle_number = intval($cycle_number_a[$n]);
						$cycle_period = pmpro_sanitize_period( $cycle_period_a[$n] );
						$billing_limit = intval($billing_limit_a[$n]);

						//custom trial
						if(!empty($custom_trial_a))
						{
							if(in_array($level_id, $custom_trial_a))
								$custom_trial = 1;
							else
								$custom_trial = 0;
						}
						else
							$custom_trial = 0;

						if(!empty($custom_trial))
						{
							$trial_amount = isset( $trial_amount_a[$n] ) ? sanitize_text_field( $trial_amount_a[$n] ) : '';
							$trial_limit = isset( $trial_limit_a[$n] ) ? intval( $trial_limit_a[$n] ) : '';
						}
						else
						{
							$trial_amount = '';
							$trial_limit = '';
						}
					}
					else
					{
						$billing_amount = '';
						$cycle_number = '';
						$cycle_period = 'Month';
						$billing_limit = '';
						$custom_trial = 0;
						$trial_amount = '';
						$trial_limit = '';
					}

					if(!empty($expiration_a))
					{
						if(in_array($level_id, $expiration_a))
							$expiration = 1;
						else
							$expiration = 0;
					}
					else
						$expiration = 0;

					if(!empty($expiration))
					{
						$expiration_number = intval($expiration_number_a[$n]);
						$expiration_period = sanitize_text_field($expiration_period_a[$n]);
					}
					else
					{
						$expiration_number = '';
						$expiration_period = 'Month';
					}

					if ( ! empty( $expiration ) && ! empty( $recurring ) ) {
						$expiration_warning_flag = true;
					}

					//okay, do the insert
					$wpdb->insert(
						$wpdb->pmpro_discount_codes_levels,
						array(
							'code_id' => $edit,
							'level_id' => $level_id,
							'initial_payment' => $initial_payment,
							'billing_amount' => $billing_amount,
							'cycle_number' => $cycle_number,
							'cycle_period' => $cycle_period,
							'billing_limit' => $billing_limit,
							'trial_amount' => $trial_amount,
							'trial_limit' => $trial_limit,
							'expiration_number' => $expiration_number,
							'expiration_period' => $expiration_period
						),
						array(
							'%d',
							'%d',
							'%f',
							'%f',
							'%d',
							'%s',
							'%d',
							'%f',
							'%d',
							'%d',
							'%s'
						)
					);

					if(empty($wpdb->last_error))
					{
						//okay
						do_action("pmpro_save_discount_code_level", $edit, $level_id);
					}
					else
					{
						$level = pmpro_getLevel($level_id);
						$level_errors[] = sprintf(__("Error saving values for the %s level.", 'paid-memberships-pro' ), $level->name);
					}
				}
			}

			//merge in any payment schedule (delay/expiration pattern) errors recorded during the level saves
			global $pmpro_payment_schedule_dc_errors;
			if ( ! empty( $pmpro_payment_schedule_dc_errors ) && is_array( $pmpro_payment_schedule_dc_errors ) ) {
				$level_errors = array_merge( ! empty( $level_errors ) ? $level_errors : array(), $pmpro_payment_schedule_dc_errors );
			}

			//errors?
			if(!empty($level_errors))
			{
				$pmpro_msg = __("There were errors updating the level values: ", 'paid-memberships-pro' ) . implode(" ", $level_errors);
				$pmpro_msgt = "error";
			}
			else
			{
				do_action("pmpro_save_discount_code", $edit);

				//all good. set edit = false so we go back to the overview page
				$edit = false;
			}
		}
	}

	//check nonce for deleting codes
	if (!empty($delete) && (empty($_REQUEST['pmpro_discountcodes_nonce']) || !check_admin_referer('delete', 'pmpro_discountcodes_nonce'))) {
		$pmpro_msgt = 'error';
		$pmpro_msg = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		$delete = false;
	}

	//are we deleting?
	if(!empty($delete))
	{
		//is this a code?
		$code = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = %d LIMIT 1", $delete ) );
		if(!empty($code))
		{
			//action
			do_action("pmpro_delete_discount_code", $delete);

			//delete the code levels
			$r1 = $wpdb->delete($wpdb->pmpro_discount_codes_levels, array('code_id'=>$delete), array('%d'));

			if($r1 !== false)
			{
				//delete the code
				$r2 = $wpdb->delete($wpdb->pmpro_discount_codes, array('id'=>$delete), array('%d'));

				if($r2 !== false)
				{
					$pmpro_msg = sprintf(__("Code %s deleted successfully.", 'paid-memberships-pro' ), $code);
					$pmpro_msgt = "success";
				}
				else
				{
					$pmpro_msg = __("Error deleting discount code. The code was only partially deleted. Please try again.", 'paid-memberships-pro' );
					$pmpro_msgt = "error";
				}
			}
			else
			{
				$pmpro_msg = __("Error deleting code. Please try again.", 'paid-memberships-pro' );
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$pmpro_msg = __("Code not found.", 'paid-memberships-pro' );
			$pmpro_msgt = "error";
		}
	}

	if( ! empty( $pmpro_msg ) && ! empty( $expiration_warning_flag ) ) {
		$pmpro_msg .= ' <strong>' . sprintf( __( 'WARNING: A level was set with both a recurring billing amount and an expiration date. You only need to set one of these unless you really want this membership to expire after a specific time period. For more information, <a target="_blank" rel="nofollow noopener" href="%s">see our post here</a>.', 'paid-memberships-pro' ), 'https://www.paidmembershipspro.com/important-notes-on-recurring-billing-and-expiration-dates-for-membership-levels/?utm_source=plugin&utm_medium=pmpro-discountcodes&utm_campaign=blog&utm_content=important-notes-on-recurring-billing-and-expiration-dates-for-membership-levels' ) . '</strong>';

		if( $pmpro_msgt == 'success' ) {
			$pmpro_msgt = 'warning';
		}
	}

	require_once(dirname(__FILE__) . "/admin_header.php");
?>
	<hr class="wp-header-end">
	<?php if($edit) { ?>

		<h1>
			<?php
				if($edit > 0)
					echo esc_html__("Edit Discount Code", 'paid-memberships-pro' );
				else
					echo esc_html__("Add New Discount Code", 'paid-memberships-pro' );
			?>
		</h1>

		<p><?php
			$edit_discount_code_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Discount Codes Documentation', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/admin/discount-codes/?utm_source=plugin&utm_medium=pmpro-discountcodes&utm_campaign=documentation&utm_content=&utm_term=">' . esc_html__( 'Discount Codes', 'paid-memberships-pro' ) . '</a>';
			// translators: %s: Link to Discount Codes doc.
			printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $edit_discount_code_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?></p>

		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo wp_kses_post( $pmpro_msg );?></p></div>
		<?php }
			// get the code...
			if($edit > 0)
			{
				$code = $wpdb->get_row(
					$wpdb->prepare("
					SELECT *, UNIX_TIMESTAMP(CONVERT_TZ(starts, '+00:00', @@global.time_zone)) as starts, UNIX_TIMESTAMP(CONVERT_TZ(expires, '+00:00', @@global.time_zone)) as expires
					FROM $wpdb->pmpro_discount_codes
					WHERE id = %d LIMIT 1",
					$edit ),
					OBJECT
				);

				$uses = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = %d", $code->id ) );
				$levels = $wpdb->get_results( $wpdb->prepare("
				SELECT l.id, l.name, cl.initial_payment, cl.billing_amount, cl.cycle_number, cl.cycle_period, cl.billing_limit, cl.trial_amount, cl.trial_limit
				FROM $wpdb->pmpro_membership_levels l
				LEFT JOIN $wpdb->pmpro_discount_codes_levels cl
				ON l.id = cl.level_id
				WHERE cl.code_id = %s",
				$code->code
				) );
				$temp_code = $code;
			}
			elseif(!empty($copy) && $copy > 0)
			{
				$code = $wpdb->get_row(
					$wpdb->prepare("
					SELECT *, UNIX_TIMESTAMP(CONVERT_TZ(starts, '+00:00', @@global.time_zone)) as starts, UNIX_TIMESTAMP(CONVERT_TZ(expires, '+00:00', @@global.time_zone)) as expires
					FROM $wpdb->pmpro_discount_codes
					WHERE id = %d LIMIT 1",
					$copy ),
					OBJECT
				);

				$temp_code = $code;
			}

			// didn't find a discount code, let's add a new one...
			if(empty($code->id)) $edit = -1;

			//defaults for new codes
			if ( $edit == -1 )
			{
				$code = new stdClass();
				$code->code = pmpro_getDiscountCode();

				if( ! empty( $copy ) && $copy > 0 ) {
					$code->starts = $temp_code->starts;
					$code->expires = $temp_code->expires;
					$code->uses = $temp_code->uses;
				}
			}
		?>
		<form action="" method="post">
			<input name="saveid" type="hidden" value="<?php echo esc_attr( $edit ); ?>" />
			<?php wp_nonce_field('save', 'pmpro_discountcodes_nonce');
			//some vars for the dates
			$current_day = date("j");
			if(!empty($code->starts))
				$selected_starts_day = date("j", $code->starts);
			else
				$selected_starts_day = $current_day;
			if(!empty($code->expires))
				$selected_expires_day = date("j", $code->expires);
			else
				$selected_expires_day = $current_day;

			$current_month = date("M");
			if(!empty($code->starts))
				$selected_starts_month = date("m", $code->starts);
			else
				$selected_starts_month = date("m");
			if(!empty($code->expires))
				$selected_expires_month = date("m", $code->expires);
			else
				$selected_expires_month = date("m");

			$current_year = date("Y");
			if(!empty($code->starts))
				$selected_starts_year = date("Y", $code->starts);
			else
				$selected_starts_year = $current_year;
			if(!empty($code->expires))
				$selected_expires_year = date("Y", $code->expires);
			else
				$selected_expires_year = (int)$current_year + 1;

			// Month options shared by the start and expiration date composites.
			$month_options = array();
			for ( $i = 1; $i < 13; $i++ ) {
				$month_options[ $i ] = date_i18n( 'F', mktime( 0, 0, 0, $i, 2 ) );
			}

			pmpro_build_settings_section( array(
				'id'     => 'general-discount-code-settings',
				'title'  => __( 'General Discount Code Settings', 'paid-memberships-pro' ),
				'fields' => array(
					array(
						'label'   => __( 'ID', 'paid-memberships-pro' ),
						'type'    => 'html',
						'content' => '<p class="description">' . ( ! empty( $code->id ) ? esc_html( $code->id ) : esc_html__( 'This will be generated when you save.', 'paid-memberships-pro' ) ) . '</p>',
					),
					array(
						'name'  => 'code',
						'label' => __( 'Code', 'paid-memberships-pro' ),
						'type'  => 'text',
						'class' => '',
						'attrs' => array( 'size' => 20 ),
						'value' => $code->code,
					),
					array(
						'label'  => __( 'Start Date', 'paid-memberships-pro' ),
						'type'   => 'composite',
						'fields' => array(
							array(
								'name'    => 'starts_month',
								'type'    => 'select',
								'value'   => (int) $selected_starts_month,
								'options' => $month_options,
							),
							array( 'name' => 'starts_day', 'type' => 'text', 'class' => '', 'attrs' => array( 'size' => 2 ), 'value' => $selected_starts_day ),
							array( 'name' => 'starts_year', 'type' => 'text', 'class' => '', 'attrs' => array( 'size' => 4 ), 'value' => $selected_starts_year ),
						),
					),
					array(
						'label'  => __( 'Expiration Date', 'paid-memberships-pro' ),
						'type'   => 'composite',
						'fields' => array(
							array(
								'name'    => 'expires_month',
								'type'    => 'select',
								'value'   => (int) $selected_expires_month,
								'options' => $month_options,
							),
							array( 'name' => 'expires_day', 'type' => 'text', 'class' => '', 'attrs' => array( 'size' => 2 ), 'value' => $selected_expires_day ),
							array( 'name' => 'expires_year', 'type' => 'text', 'class' => '', 'attrs' => array( 'size' => 4 ), 'value' => $selected_expires_year ),
						),
					),
					array(
						'name'        => 'uses',
						'label'       => __( 'Limit Total Uses', 'paid-memberships-pro' ),
						'type'        => 'text',
						'class'       => '',
						'attrs'       => array( 'size' => 10 ),
						'value'       => ! empty( $code->uses ) ? $code->uses : '',
						'description' => esc_html__( 'Define the maximum number of times this discount code can be used across all users.', 'paid-memberships-pro' ) . ' ' . esc_html__( 'Leave blank for unlimited uses.', 'paid-memberships-pro' ),
					),
					array(
						'name'           => 'one_use_per_user',
						'label'          => __( 'Limit Per User', 'paid-memberships-pro' ),
						'type'           => 'checkbox',
						'value'          => ! empty( $code->one_use_per_user ) ? 1 : 0,
						'checkbox_label' => __( 'Restrict this discount code to a single use per unique user.', 'paid-memberships-pro' ),
					),
					array(
						'hook' => 'pmpro_discount_code_after_settings',
						'args' => array( $edit ),
					),
				),
			) );

		// The per-level pricing grid below is bespoke (checkbox-toggled pricing panels with array
		// inputs), so only the collapsible section wrapper uses the shared helpers.
		pmpro_build_settings_section_open( array(
			'id'    => 'discount-code-level-settings',
			'title' => __( 'Membership Level Settings', 'paid-memberships-pro' ),
		) );
		?>
				<p><?php esc_html_e('Which levels will this code apply to?', 'paid-memberships-pro' ); ?></p>

				<div class="pmpro_discount_levels">
				<?php
					$levels = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_membership_levels");
					$levels = pmpro_sort_levels_by_order( $levels );
					foreach($levels as $level)
					{
						//if this level is already managed for this discount code, use the code values
						if($edit > 0 || ! empty( $copy ) )
						{
							$code_level = $wpdb->get_row( $wpdb->prepare("
							SELECT l.id, cl.*, l.name, l.description, l.allow_signups
							FROM $wpdb->pmpro_discount_codes_levels cl
							LEFT JOIN $wpdb->pmpro_membership_levels l
							ON cl.level_id = l.id
							WHERE cl.code_id = %d AND cl.level_id = %d LIMIT 1",
							$temp_code->id,
							$level->id )
						);
							if($code_level)
							{
								$level = $code_level;
								$level->checked = true;
							}
							else
								$level_checked = false;
						}
						else
							$level_checked = false;

						// Load subscription delay and set expiration date for this discount code level.
						$dc_delay = '';
						$dc_set_expiration_date = '';
						if ( $edit > 0 || ! empty( $copy ) ) {
							$dc_delay = pmpro_get_subscription_delay( $level->id, $temp_code->id );
							$dc_set_expiration_date = pmpro_get_set_expiration_date( $level->id, $temp_code->id );
						}
						$dc_delay_type = ! empty( $dc_delay ) ? ( is_numeric( $dc_delay ) ? 'days' : 'date' ) : 'none';
						$dc_exp_type = ! empty( $dc_set_expiration_date ) ? 'date' : 'none';
						$dc_month_names = pmpro_get_month_names();

						$level_checkbox_id          = 'levels_' . $level->id;
						$level_recurring_checkbox_id = 'recurring_' . $level->id;
						$level_trial_checkbox_id     = 'custom_trial_' . $level->id;
						$level_expiration_checkbox_id = 'expiration_' . $level->id;

						$level_is_selected  = ! empty( $level->checked );
						$level_is_recurring = pmpro_isLevelRecurring( $level );
						$level_is_trial     = pmpro_isLevelTrial( $level );
						$level_is_expiring  = pmpro_isLevelExpiring( $level ) || $dc_exp_type === 'date';

						$level_pricing_class     = 'pmpro_discount_levels_pricing level_' . $level->id . ( $level_is_selected ? '' : ' pmpro-hidden' );
						$recurring_info_class    = 'recurring_info' . ( $level_is_recurring ? '' : ' pmpro-hidden' );
						$trial_info_class        = 'trial_info recurring_info' . ( $level_is_recurring && $level_is_trial ? '' : ' pmpro-hidden' );
						$expiration_info_class   = 'expiration_info' . ( $level_is_expiring ? '' : ' pmpro-hidden' );
						$level_pricing_depends   = esc_attr( wp_json_encode( array( array( 'id' => $level_checkbox_id, 'checked' => true ) ) ) );
						$recurring_info_depends  = esc_attr( wp_json_encode( array( array( 'id' => $level_recurring_checkbox_id, 'checked' => true ) ) ) );
						$trial_info_depends      = esc_attr( wp_json_encode( array( array( 'id' => $level_recurring_checkbox_id, 'checked' => true ), array( 'id' => $level_trial_checkbox_id, 'checked' => true ) ) ) );
						$expiration_info_depends = esc_attr( wp_json_encode( array( array( 'id' => $level_expiration_checkbox_id, 'checked' => true ) ) ) );
					?>
					<div class="pmpro_discount_level <?php if ( ! pmpro_check_discount_code_level_for_gateway_compatibility( $level ) ) { ?>pmpro_error<?php } ?>">
						<div class="pmpro_discount_level_select">
							<input type="hidden" name="all_levels[]" value="<?php echo esc_attr( $level->id ); ?>" />
							<input type="checkbox" id="<?php echo esc_attr( $level_checkbox_id ); ?>" name="levels[]" value="<?php echo esc_attr( $level->id ); ?>" <?php checked( $level_is_selected ); ?> />
							<label for="<?php echo esc_attr( $level_checkbox_id ); ?>"><?php echo esc_html( $level->name );?></label>
						</div>
						<div class="<?php echo esc_attr( $level_pricing_class ); ?>" data-pmpro-depends="<?php echo $level_pricing_depends; ?>">
							<table class="form-table">
							<tbody>
								<tr>
									<th scope="row" valign="top"><label for="initial_payment"><?php esc_html_e('Initial Payment', 'paid-memberships-pro' );?></label></th>
									<td>
										<?php
										if(pmpro_getCurrencyPosition() == "left")
											echo wp_kses_post( $pmpro_currency_symbol );
										?>
										<input name="initial_payment[]" type="text" size="20" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $level->initial_payment ) ); ?>" />
										<?php
										if(pmpro_getCurrencyPosition() == "right")
											echo wp_kses_post( $pmpro_currency_symbol );
										?>
										<p class="description"><?php esc_html_e('The initial amount collected at registration.', 'paid-memberships-pro' );?></p>
									</td>
								</tr>

								<tr>
									<th scope="row" valign="top"><label><?php esc_html_e('Recurring Subscription', 'paid-memberships-pro' );?></label></th>
									<td><input class="recurring_checkbox" id="<?php echo esc_attr( $level_recurring_checkbox_id ); ?>" name="recurring[]" type="checkbox" value="<?php echo esc_attr( $level->id ); ?>" <?php checked( $level_is_recurring ); ?> /> <label for="<?php echo esc_attr( $level_recurring_checkbox_id ); ?>"><?php esc_html_e('Check if this level has a recurring subscription payment.', 'paid-memberships-pro' );?></label></td>
								</tr>

								<tr class="<?php echo esc_attr( $recurring_info_class ); ?>" data-pmpro-depends="<?php echo $recurring_info_depends; ?>">
									<th scope="row" valign="top"><label for="billing_amount"><?php esc_html_e('Billing Amount', 'paid-memberships-pro' );?></label></th>
									<td>
										<?php
										if(pmpro_getCurrencyPosition() == "left")
											echo wp_kses_post( $pmpro_currency_symbol );
										?>
										<input name="billing_amount[]" type="text" size="20" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $level->billing_amount ) );?>" />
										<?php
										if(pmpro_getCurrencyPosition() == "right")
											echo wp_kses_post( $pmpro_currency_symbol );
										?>
										<?php esc_html_e('per', 'paid-memberships-pro' ); ?>
										<input name="cycle_number[]" type="text" size="10" value="<?php echo esc_attr( $level->cycle_number ); ?>" />
										<select name="cycle_period[]">
										<?php
											$cycles = array( __('Day(s)', 'paid-memberships-pro' ) => 'Day', __('Week(s)', 'paid-memberships-pro' ) => 'Week', __('Month(s)', 'paid-memberships-pro' ) => 'Month', __('Year(s)', 'paid-memberships-pro' ) => 'Year' );
											foreach ( $cycles as $name => $value ) {
											echo "<option value='" . esc_attr( $value ) . "'";
											if ( $level->cycle_period == $value ) echo " selected='selected'";
											echo ">" . esc_html( $name ) . "</option>";
											}
										?>
										</select>
										<p class="description"><?php esc_html_e('The amount to be billed one cycle after the initial payment.', 'paid-memberships-pro' );?></p>
										<?php if($gateway == "braintree") { ?>
											<strong <?php if(!empty($pmpro_braintree_error)) { ?>class="pmpro_red"<?php } ?>><?php esc_html_e('Braintree integration currently only supports billing periods of "Month" or "Year".', 'paid-memberships-pro' );?></strong>
										<?php } elseif($gateway == "stripe") { ?>
											<p class="description"><strong <?php if(!empty($pmpro_stripe_error)) { ?>class="pmpro_red"<?php } ?>><?php esc_html_e('Stripe integration does not allow billing periods longer than 1 year.', 'paid-memberships-pro' );?></strong></p>
										<?php }?>
									</td>
								</tr>

								<tr class="<?php echo esc_attr( $recurring_info_class ); ?>" data-pmpro-depends="<?php echo $recurring_info_depends; ?>">
									<th scope="row" valign="top"><label for="billing_limit"><?php esc_html_e('Billing Cycle Limit', 'paid-memberships-pro' );?></label></th>
									<td>
										<input name="billing_limit[]" type="text" size="20" value="<?php echo esc_attr( $level->billing_limit ); ?>" />
										<p class="description">
											<?php echo wp_kses( __( 'The <strong>total</strong> number of recurring billing cycles for this level, including the trial period (if applicable) but not including the initial payment. Set to zero if membership is indefinite.', 'paid-memberships-pro' ), array( 'strong' => array() ) ); ?>
									</p>
									</td>
								</tr>

								<tr class="<?php echo esc_attr( $recurring_info_class ); ?>" data-pmpro-depends="<?php echo $recurring_info_depends; ?>">
									<th scope="row" valign="top"><label><?php esc_html_e( 'First Recurring Payment', 'paid-memberships-pro' ); ?></label></th>
									<td>
										<fieldset>
											<label>
												<input type="radio" name="delay_type_<?php echo esc_attr( $level->id ); ?>" value="none" <?php checked( $dc_delay_type, 'none' ); ?>
													onclick="pmpro_dcToggleDelay(<?php echo intval( $level->id ); ?>, 'none');" />
												<?php esc_html_e( 'Default (one billing cycle after checkout)', 'paid-memberships-pro' ); ?>
											</label>
											<br />
											<label>
												<input type="radio" name="delay_type_<?php echo esc_attr( $level->id ); ?>" value="days" <?php checked( $dc_delay_type, 'days' ); ?>
													onclick="pmpro_dcToggleDelay(<?php echo intval( $level->id ); ?>, 'days');" />
												<?php esc_html_e( 'After a number of days (trial)', 'paid-memberships-pro' ); ?>
											</label>
											<span class="pmpro_dc_delay_days_<?php echo esc_attr( $level->id ); ?>" <?php if ( $dc_delay_type !== 'days' ) echo 'style="display:none;"'; ?>>
												&mdash;
												<input name="subscription_delay_days[]" type="number" min="1" class="small-text"
													value="<?php echo esc_attr( $dc_delay_type === 'days' ? $dc_delay : '' ); ?>" />
												<?php esc_html_e( 'days after checkout', 'paid-memberships-pro' ); ?>
											</span>
											<br />
											<label>
												<input type="radio" name="delay_type_<?php echo esc_attr( $level->id ); ?>" value="date" <?php checked( $dc_delay_type, 'date' ); ?>
													onclick="pmpro_dcToggleDelay(<?php echo intval( $level->id ); ?>, 'date');" />
												<?php esc_html_e( 'On a specific date', 'paid-memberships-pro' ); ?>
											</label>
											<div class="pmpro_dc_delay_date_<?php echo esc_attr( $level->id ); ?>" <?php if ( $dc_delay_type !== 'date' ) echo 'style="display:none;"'; ?>>
												<?php pmpro_payment_schedule_render_date_builder( $dc_month_names, 'subscription_delay_date[]', $dc_delay_type === 'date' ? $dc_delay : '' ); ?>
											</div>
											<input type="hidden" name="delay_type[]" value="<?php echo esc_attr( $dc_delay_type ); ?>" class="pmpro_dc_delay_type_<?php echo esc_attr( $level->id ); ?>" />
										</fieldset>
									</td>
								</tr>

								<?php
								// Only show trial settings if the active gateway supports recurring trials or if the level already has a trial set.
								$discount_gateway_class = 'PMProGateway_' . $gateway;
								$discount_gateway_supports_recurring_trials = method_exists( $discount_gateway_class, 'supports' ) && $discount_gateway_class::supports( 'recurring_trials' );
								if ( $discount_gateway_supports_recurring_trials || pmpro_isLevelTrial( $level ) ) {
								?>
									<tr class="<?php echo esc_attr( $recurring_info_class ); ?>" data-pmpro-depends="<?php echo $recurring_info_depends; ?>">
										<th scope="row" valign="top"><label><?php esc_html_e('Custom Trial', 'paid-memberships-pro' );?></label></th>
										<td>
											<input id="<?php echo esc_attr( $level_trial_checkbox_id ); ?>" name="custom_trial[]" type="checkbox" value="<?php echo esc_attr( $level->id ); ?>" <?php checked( $level_is_trial ); ?> /> <label for="<?php echo esc_attr( $level_trial_checkbox_id ); ?>"><?php esc_html_e('Check to add a custom trial period.', 'paid-memberships-pro' );?></label>
											<?php if ( ! $discount_gateway_supports_recurring_trials ) { ?>
												<p class="description"><strong class="pmpro_red"><?php esc_html_e( 'The current payment gateway does not support recurring trials.', 'paid-memberships-pro' ); ?></strong></p>
											<?php } ?>
										</td>
									</tr>

									<tr class="<?php echo esc_attr( $trial_info_class ); ?>" data-pmpro-depends="<?php echo $trial_info_depends; ?>">
										<th scope="row" valign="top"><label for="trial_amount"><?php esc_html_e('Trial Billing Amount', 'paid-memberships-pro' );?></label></th>
										<td>
											<?php
											if(pmpro_getCurrencyPosition() == "left")
												echo wp_kses_post( $pmpro_currency_symbol );
											?>
											<input name="trial_amount[]" type="text" size="20" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $level->trial_amount ) );?>" />
											<?php
											if(pmpro_getCurrencyPosition() == "right")
												echo wp_kses_post( $pmpro_currency_symbol );
											?>
											<?php esc_html_e('for the first', 'paid-memberships-pro' );?>
											<input name="trial_limit[]" type="text" size="10" value="<?php echo esc_attr( $level->trial_limit ); ?>" />
											<?php esc_html_e('subscription payments', 'paid-memberships-pro' );?>.
										</td>
									</tr>
								<?php } else { ?>
									<tr style="display:none;">
										<td>
											<input type="hidden" name="trial_amount[]" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $level->trial_amount ) ); ?>" />
											<input type="hidden" name="trial_limit[]" value="<?php echo esc_attr( $level->trial_limit ); ?>" />
										</td>
									</tr>
								<?php } ?>

								<tr>
									<th scope="row" valign="top"><label><?php esc_html_e('Membership Expiration', 'paid-memberships-pro' );?></label></th>
									<td><input id="<?php echo esc_attr( $level_expiration_checkbox_id ); ?>" name="expiration[]" type="checkbox" value="<?php echo esc_attr( $level->id ); ?>" <?php checked( $level_is_expiring ); ?> /> <label for="<?php echo esc_attr( $level_expiration_checkbox_id ); ?>"><?php esc_html_e('Check this to set when membership access expires.', 'paid-memberships-pro' );?></label></td>
								</tr>

								<tr class="<?php echo esc_attr( $expiration_info_class ); ?>" data-pmpro-depends="<?php echo $expiration_info_depends; ?>">
									<th scope="row" valign="top"><label><?php esc_html_e( 'Expiration Type', 'paid-memberships-pro' ); ?></label></th>
									<td>
										<fieldset>
											<label>
												<input type="radio" name="expiration_date_type_<?php echo esc_attr( $level->id ); ?>" value="none" <?php checked( $dc_exp_type, 'none' ); ?>
													onclick="pmpro_dcToggleExpiration(<?php echo intval( $level->id ); ?>, 'none');" />
												<?php esc_html_e( 'After a set duration', 'paid-memberships-pro' ); ?>
											</label>
											<div class="pmpro_dc_exp_duration_<?php echo esc_attr( $level->id ); ?>" <?php if ( $dc_exp_type === 'date' ) echo 'style="display:none;"'; ?>>
												<input name="expiration_number[]" type="text" size="10" value="<?php echo esc_attr( $level->expiration_number ); ?>" />
												<select name="expiration_period[]">
												<?php
													$cycles = array( __('Hour(s)', 'paid-memberships-pro' ) => 'Hour', __('Day(s)', 'paid-memberships-pro' ) => 'Day', __('Week(s)', 'paid-memberships-pro' ) => 'Week', __('Month(s)', 'paid-memberships-pro' ) => 'Month', __('Year(s)', 'paid-memberships-pro' ) => 'Year' );
													foreach ( $cycles as $name => $value ) {
														echo "<option value='" . esc_attr( $value ) . "'";
														if ( $level->expiration_period == $value ) echo " selected='selected'";
														echo ">" . esc_html( $name ) . "</option>";
													}
												?>
												</select>
												<p class="description"><?php esc_html_e('Membership access will end this long after checkout. Any recurring subscription will be cancelled at that time.', 'paid-memberships-pro' );?></p>
											</div>
											<br />
											<label>
												<input type="radio" name="expiration_date_type_<?php echo esc_attr( $level->id ); ?>" value="date" <?php checked( $dc_exp_type, 'date' ); ?>
													onclick="pmpro_dcToggleExpiration(<?php echo intval( $level->id ); ?>, 'date');" />
												<?php esc_html_e( 'On a specific date', 'paid-memberships-pro' ); ?>
											</label>
											<div class="pmpro_dc_exp_date_<?php echo esc_attr( $level->id ); ?>" <?php if ( $dc_exp_type !== 'date' ) echo 'style="display:none;"'; ?>>
												<?php pmpro_payment_schedule_render_date_builder( $dc_month_names, 'set_expiration_date[]', $dc_set_expiration_date ); ?>
											</div>
											<input type="hidden" name="expiration_date_type[]" value="<?php echo esc_attr( $dc_exp_type ); ?>" class="pmpro_dc_exp_type_<?php echo esc_attr( $level->id ); ?>" />
										</fieldset>
									</td>
								</tr>
							</tbody>
						</table>

						<?php do_action("pmpro_discount_code_after_level_settings", $edit, $level); ?>

						</div>
					</div>
					<?php
					}
				?>
				</div> <!-- end pmpro_levels_div -->
		<?php pmpro_build_settings_section_close(); ?>

		<script>
		function pmpro_dcToggleDelay(levelId, val) {
			jQuery('.pmpro_dc_delay_days_' + levelId).toggle(val === 'days');
			jQuery('.pmpro_dc_delay_date_' + levelId).toggle(val === 'date');
			jQuery('.pmpro_dc_delay_type_' + levelId).val(val);
		}
		function pmpro_dcToggleExpiration(levelId, val) {
			jQuery('.pmpro_dc_exp_duration_' + levelId).toggle(val === 'none');
			jQuery('.pmpro_dc_exp_date_' + levelId).toggle(val === 'date');
			jQuery('.pmpro_dc_exp_type_' + levelId).val(val);
		}
		jQuery(document).ready(function($) {
			// Initialize all date pattern builders on the page.
			$('.pmpro_date_pattern_builder').each(function() {
				var val = $(this).attr('data-existing-value');
				if (val) {
					pmpro_initDateBuilder($(this), val);
				}
			});
		});
		</script>

		<p class="submit">
			<input name="save" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Code', 'paid-memberships-pro' ) ?>" />
			<input name="cancel" type="button" class="button" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' ) ?>" onclick="location.href='<?php echo esc_url( admin_url( 'admin.php?page=pmpro-discountcodes') ); ?>';" />
		</p>

		</form>

	<?php } else { ?>
		<form id="discount-code-list-form" method="get">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Discount Codes', 'paid-memberships-pro' ); ?></h1>
			<a href="admin.php?page=pmpro-discountcodes&edit=-1" class="page-title-action"><?php esc_html_e( 'Add New Discount Code', 'paid-memberships-pro' ); ?></a>
			<?php
				$totalrows = $wpdb->get_var( "SELECT COUNT( DISTINCT id ) FROM $wpdb->pmpro_discount_codes" );

				if( empty( $s ) && empty( $totalrows ) ) { ?>

					<div class="pmpro-new-install">
						<h2><?php esc_html_e( 'No Discount Codes Found', 'paid-memberships-pro' ); ?></h2>
						<h4><?php esc_html_e( 'Discount codes allow you to override your membership level\'s default pricing.', 'paid-memberships-pro' ); ?></h4>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-discountcodes&edit=-1' ) ) ; ?>" class="button-primary"><?php esc_html_e( 'Create a Discount Code', 'paid-memberships-pro' );?></a>
						<a href="<?php echo esc_url( 'https://www.paidmembershipspro.com/documentation/discount-codes/?utm_source=plugin&utm_medium=pmpro-discountcodes&utm_campaign=documentation&utm_content=discount-codes' ); ?>" target="_blank" rel="nofollow noopener" class="button"><?php esc_html_e( 'Documentation: Discount Codes', 'paid-memberships-pro' ); ?></a>
					</div> <!-- end pmpro-new-install -->
				<?php } else { 

					if(!empty($pmpro_msg)) { 
					?>
						<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo wp_kses_post( $pmpro_msg );?></p></div>
					<?php
					}

					$discountcode_list_table = new PMPro_Discount_Code_List_Table();
					$discountcode_list_table->prepare_items();
					
					?>
					<input type="hidden" name="page" value="pmpro-discountcodes" />
					<?php
						$discountcode_list_table->search_box( __( 'Search', 'paid-memberships-pro' ), 'paid-memberships-pro' );
						$discountcode_list_table->display();
				}
			?>
			</form>
			<?php
		}

		require_once(dirname(__FILE__) . "/admin_footer.php");
