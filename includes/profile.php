<?php
/**
 * Add the "membership level" field to the edit user/profile page,
 * along with other membership-related fields.
 */
function pmpro_membership_level_profile_fields($user)
{
	global $current_user;

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	global $wpdb;
	$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );
	$groups = pmpro_get_level_groups_in_order();

	// Add group ID to each level.
	foreach ( $levels as $level ) {
		$level->group_id = pmpro_get_group_id_for_level( $level->id );
	}

	if(!$levels)
		return "";

	?>
	<h3><?php esc_html_e("Membership Levels", 'paid-memberships-pro' ); ?></h3>
	<?php
		$show_membership_level = true;
		$show_membership_level = apply_filters("pmpro_profile_show_membership_level", $show_membership_level, $user);
		if($show_membership_level) {
			?>
			<table class="wp-list-table widefat fixed pmprommpu_levels" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Groups', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Membership Level', 'paid-memberships-pro' ) ?></th>
					<th><?php esc_html_e( 'Expiration', 'paid-memberships-pro' ) ?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
			<?php
				//get levels and groups
				$currentlevels = pmpro_getMembershipLevelsForUser($user->ID);

				//some other vars
				$current_day = date("j", current_time('timestamp'));
				$current_month = date("M", current_time('timestamp'));
				$current_year = date("Y", current_time('timestamp'));

				ob_start();
				?>
				<tr id="new_levels_tr_template" class="new_levels_tr">
					<td>
						<select class="new_levels_group" name="new_levels_group[]">
							<option value="">-- <?php _e("Choose a Group", 'paid-memberships-pro');?> --</option>
							<?php foreach($groups as $group) { ?>
								<option value="<?php echo $group->id;?>"><?php echo $group->name;?></option>
							<?php } ?>
						</select>
					</td>
					<td>
						<em><?php _e('Choose a group first.', 'paid-memberships-pro');?></em>
					</td>
					<td>
						<?php
							//default enddate values
							$end_date = false;
							$selected_expires_day = $current_day;
							$selected_expires_month = date("m");
							$selected_expires_year = (int)$current_year + 1;
						?>
						<select class="expires new_levels_expires" name="new_levels_expires[]">
							<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", 'paid-memberships-pro');?></option>
							<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", 'paid-memberships-pro');?></option>
						</select>
						<span class="expires_date new_levels_expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
							on
							<select name="new_levels_expires_month[]">
								<?php
									for($i = 1; $i < 13; $i++)
									{
									?>
									<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/15/" . $current_year, current_time("timestamp")))?></option>
									<?php
									}
								?>
							</select>
							<input name="new_levels_expires_day[]" type="text" size="2" value="<?php echo $selected_expires_day?>" />
							<input name="new_levels_expires_year[]" type="text" size="4" value="<?php echo $selected_expires_year?>" />
						</span>
					</td>
					<td><a class="remove_level" href="javascript:void(0);"><?php _e('Remove', 'paid-memberships-pro');?></a></td>
				</tr>
				<?php
				$new_level_template_html = preg_replace('/\n\t+/', '', ob_get_contents());
				ob_end_clean();

				//set group for each level
				for($i = 0; $i < count($currentlevels); $i++) {
					$currentlevels[$i]->group = pmpro_get_group_id_for_level( $currentlevels[$i]->id );
				}

				//loop through all groups in order and show levels if the user has any currently
				foreach( $groups as $group ) {
					$group_levels = pmpro_get_level_ids_for_group($group->id);
					if ( ! empty( $group_levels ) && pmpro_hasMembershipLevel( $group_levels, $user->ID ) ) {
						//user has at least one of these levels, so let's show them
						foreach($currentlevels as $level) {
							if($level->group == $group->id) {
							?>
							<tr>
								<td width="25%"><?php echo $group->name;?></td>
								<td width="25%">
									<?php
										echo $level->name;
									?>
									<input class="membership_level_id" type="hidden" name="membership_levels[]" value="<?php echo esc_attr($level->id);?>" />
								</td>
								<td width="25%">
								<?php
									$show_expiration = true;
									$show_expiration = apply_filters("pmpro_profile_show_expiration", $show_expiration, $user);
									if($show_expiration)
									{
										//is there an end date?
										$end_date = !empty($level->enddate);

										//some vars for the dates
										if($end_date)
											$selected_expires_day = date("j", $level->enddate);
										else
											$selected_expires_day = $current_day;

										if($end_date)
											$selected_expires_month = date("m", $level->enddate);
										else
											$selected_expires_month = date("m");

										if($end_date)
											$selected_expires_year = date("Y", $level->enddate);
										else
											$selected_expires_year = (int)$current_year + 1;
									}
									?>
									<select class="expires" name="expires[]">
										<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", 'paid-memberships-pro');?></option>
										<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", 'paid-memberships-pro');?></option>
									</select>
									<span class="expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
										on
										<select name="expires_month[]">
											<?php
												for($i = 1; $i < 13; $i++)
												{
												?>
												<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/15/" . $current_year, current_time("timestamp")))?></option>
												<?php
												}
											?>
										</select>
										<input name="expires_day[]" type="text" size="2" value="<?php echo $selected_expires_day?>" />
										<input name="expires_year[]" type="text" size="4" value="<?php echo $selected_expires_year?>" />
									</span>
								</td>
								<td width="25%"><a class="remove_level" href="javascript:void(0);"><?php _e('Remove', 'paid-memberships-pro');?></a></td>
							</tr>
							<tr class="old_levels_delsettings_tr_template remove_level">
								<td></td>
								<td colspan="3">
									<label for="send_admin_change_email"><input value="<?php echo esc_attr( $level->id ); ?>" id="send_admin_change_email" name="send_admin_change_email[]" type="checkbox"><?php esc_html_e( 'Send the user an email about this change. (Do we still want this?)', 'paid-memberships-pro' ); ?></label><br>
									<label for="cancel_subscription"><input value="<?php echo esc_attr( $level->id ); ?>" id="cancel_subscription" name="cancel_subscription[]" type="checkbox"><?php esc_html_e( "Cancel this user's subscription at the gateway.", 'paid-memberships-pro' ); ?></label>
									<?php
									// Check if we are able to refund the user's last order for this level.
									$order_args = array(
										'user_id' => $user->ID,
										'membership_level_id' => $level->id,
										'status' => 'success',
									);
									$last_order = MemberOrder::get_order( $order_args );
									$allows_refunds = pmpro_allowed_refunds( $last_order );
									?>
									<label for="refund_last_payment"><input value="<?php echo esc_attr( $level->id ); ?>" id="refund_last_payment" name="refund_last_payment[]" type="checkbox"<?php disabled( ! $allows_refunds ); ?>> <?php esc_html_e("Refund this user's most recent order.", "paid-memberships-pro" ); ?></label>
								</td>
							</tr>
							<?php
							}
						}
					}
				}
			?>
			<tr>
				<td colspan="4"><a href="javascript:void(0);" class="add_level">+ <?php _e('Add Level', 'paid-memberships-pro');?></a></td>
			</tr>
			</tbody>
			</table>
			<script type="text/javascript">
				//vars with levels and groups
				var alllevels = <?php echo json_encode( $levels );?>;
				var allgroups = <?php echo json_encode( $groups );?>;
				var delsettingsrow = jQuery(".old_levels_delsettings_tr_template").first().detach();
				jQuery(".old_levels_delsettings_tr_template").detach();

				var new_level_template_html = '<?php echo $new_level_template_html; ?>';

				//update levels when a group dropdown changes
				function updateLevelSelect(e) {
					var groupselect = jQuery(e.target);
					var leveltd = groupselect.parent().next('td');
					var group_id = groupselect.val();

					leveltd.html('');

					//group chosen?
					if(group_id.length > 0) {
						//add level select
						var levelselect = jQuery('<select class="new_levels_level" name="new_levels_level[]"></select>').appendTo(leveltd);
						levelselect.append('<option value="">-- ' + <?php echo json_encode(__('Choose a Level', 'paid-memberships-pro'));?> + ' --</option>');
						for (var i = 0; i < alllevels.length; i++) {
							if ( group_id == alllevels[i].group_id ) {
								levelselect.append('<option value="'+alllevels[i].id+'">'+alllevels[i].name+'</option>');
							}
						}
					} else {
						leveltd.html('<em>' + <?php echo json_encode(__('Choose a group first.', 'paid-memberships-pro'));?> + '</em>');
					}
				}

				//remove level
				function removeLevel(e) {
					var removelink = jQuery(e.target);
					var removetr = removelink.closest('tr');

					if(removetr.hasClass('new_levels_tr')) {
						//new level? just delete the row
						removetr.remove();
					} else if(removetr.hasClass('remove_level')) {
						removetr.removeClass('remove_level');
						removelink.html(<?php echo json_encode(__('Remove', 'paid-memberships-pro'));?>);
						removelink.next('input').remove();
						removetr.nextAll('.old_levels_delsettings_tr_template').first().remove();
					} else {
						//existing level? red it out and add to be removed
						removetr.addClass('remove_level');
						removelink.html(<?php echo json_encode(__('Undo', 'paid-memberships-pro'));?>);
						var olevelid = removelink.closest('tr').find('input.membership_level_id').val();
						jQuery('<input type="hidden" name="remove_levels_id[]" value="'+olevelid+'">').insertAfter(removelink);
						removetr.after(delsettingsrow.clone());
					}
				}

				//bindings
				function pmprommpu_updateBindings() {
					//hide/show expiration dates
					jQuery('select.expires').unbind('change.pmprommpu');
					jQuery('select.expires').bind('change.pmprommpu', function() {
						if(jQuery(this).val() == 1)
							jQuery(this).next('span.expires_date').show();
						else
							jQuery(this).next('span.expires_date').hide();
					});

					//update level selects when groups are updated
					jQuery('select.new_levels_group').unbind('change.pmprommpu');
					jQuery('select.new_levels_group').bind('change.pmprommpu', updateLevelSelect);

					//remove buttons
					jQuery('a.remove_level').unbind('click.pmprommpu');
					jQuery('a.remove_level').bind('click.pmprommpu', removeLevel);

					//clone new level tr
					jQuery('a.add_level').unbind('click.pmprommpu');
					jQuery('a.add_level').bind('click.pmprommpu', function() {
						var newleveltr = jQuery('a.add_level').closest('tbody').append(new_level_template_html);
						pmprommpu_updateBindings();
					});
				}

				//on load
				jQuery(document).ready(function() {
					pmprommpu_updateBindings();
				});
			</script>
			<?php
		}

		$tospage_id = pmpro_getOption( 'tospage' );
		$consent_log = pmpro_get_consent_log( $user->ID, true );

		if( !empty( $tospage_id ) || !empty( $consent_log ) ) {
		?>
		<tr>
			<th><label for="tos_consent_history"><?php esc_html_e("TOS Consent History", 'paid-memberships-pro' ); ?></label></th>
			<td id="tos_consent_history">
				<?php
					if ( ! empty( $consent_log ) ) {
						// Build the selectors for the invoices history list based on history count.
						$consent_log_classes = array();
						$consent_log_classes[] = "pmpro_consent_log";
						if ( count( $consent_log ) > 5 ) {
							$consent_log_classes[] = "pmpro_scrollable";
						}
						$consent_log_class = implode( ' ', array_unique( $consent_log_classes ) );
						echo '<ul class="' . esc_attr( $consent_log_class ) . '">';
						foreach( $consent_log as $entry ) {
							echo '<li>' . pmpro_consent_to_text( $entry ) . '</li>';
						}
						echo '</ul> <!-- end pmpro_consent_log -->';
					} else {
						echo __( 'N/A', 'paid-memberships-pro' );
					}
				?>
			</td>
		</tr>
		<?php
		}
		?>
</table>
<?php
	do_action("pmpro_after_membership_level_profile_fields", $user);
}

/*
	When applied, previous subscriptions won't be cancelled when changing membership levels.
	Use a function here instead of __return_false so we can easily turn add and remove it.
*/
function pmpro_cancel_previous_subscriptions_false()
{
	return false;
}

//save the fields on update
function pmpro_membership_level_profile_fields_update() {
	global $wpdb, $current_user;
	wp_get_current_user();

	if(!empty($_REQUEST['user_id'])) {
		$user_id = $_REQUEST['user_id'];
	} else {
		$user_id = $current_user->ID;
	}

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	// OK. First, we're going to remove them from any levels that they should be dropped from - and keep an array of the levels we're dropping (so we don't adjust expiration later)
	$droppedlevels = array();
	$old_levels = pmpro_getMembershipLevelsForUser($user_id);
	if(array_key_exists('remove_levels_id', $_REQUEST)) {
		foreach($_REQUEST['remove_levels_id'] as $arraykey => $leveltodel) {
			// Check if we should cancel the subscription.
			if ( ! empty( $_REQUEST['cancel_suscription'] ) && in_array( $leveltodel, $_REQUEST['cancel_suscription'] ) ) {
				add_filter('pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false');
			}

			// Cancel the membership.
			pmpro_cancelMembershipLevel($leveltodel, $user_id, 'admin_cancelled');

			// Remove the filter.
			remove_filter('pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false');

			// Check if we should refund the last payment for this level.
			if ( ! empty( $_REQUEST['refund_last_payment'] ) && in_array( $leveltodel, $_REQUEST['refund_last_payment'] ) ) {
				// Get the last order for this level.
				$order_args = array(
					'user_id' => $user_id,
					'membership_id' => $leveltodel,
					'status' => 'success',
				);
				$last_order = MemberOrder::get_order( $order_args );
				if ( pmpro_allowed_refunds( $last_order ) ) {
					pmpro_refund_order( $last_order );
				}
			}

			$droppedlevels[] = $leveltodel;
		}
	}

	// Next, let's update the expiration on any existing levels - as long as the level isn't in one of the ones we dropped them from.
	if(array_key_exists('expires', $_REQUEST)) {
		foreach($_REQUEST['expires'] as $expkey => $doesitexpire) {
			$thislevel = $_REQUEST['membership_levels'][$expkey];
			if(!in_array($thislevel, $droppedlevels)) { // we don't change expiry for a level we've dropped.
				if(!empty($doesitexpire)) { // we're going to expire.
					//update the expiration date
					$expiration_date = intval($_REQUEST['expires_year'][$expkey]) . "-" . str_pad(intval($_REQUEST['expires_month'][$expkey]), 2, "0", STR_PAD_LEFT) . "-" . str_pad(intval($_REQUEST['expires_day'][$expkey]), 2, "0", STR_PAD_LEFT);

					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => $expiration_date ),
						array(
							'status' => 'active',
							'membership_id' => $thislevel,
							'user_id' => $user_id ), // Where clause
						array( '%s' ),  // format for data
						array( '%s', '%d', '%d' ) // format for where clause
					);

				} else { // No expiration for me!
					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => NULL ),
						array(
							'status' => 'active',
							'membership_id' => $thislevel,
							'user_id' => $user_id
						),
						array( NULL ),
						array( '%s', '%d', '%d' )
					);

				}
			}
		}
	}
	// Finally, we'll add any new levels requested. First, we'll try it without forcing, and then if need be, we'll force it (but then we'll know to give a warning about it.)
	if(array_key_exists('new_levels_level', $_REQUEST)) {
		$curlevels = pmpro_getMembershipLevelsForUser($user_id); // have to do it again, because we've made changes since above.
		$curlevids = array();
		foreach($curlevels as $thelev) { $curlevids[] = $thelev->ID; }
		foreach($_REQUEST['new_levels_level'] as $newkey => $leveltoadd) {
			if(! in_array($leveltoadd, $curlevids)) {
				$result = pmpro_give_membership_level($leveltoadd, $user_id);

				$doweexpire = $_REQUEST['new_levels_expires'][$newkey];
				if(!empty($doweexpire)) { // we're going to expire.
					//update the expiration date
					$expiration_date = intval($_REQUEST['new_levels_expires_year'][$newkey]) . "-" . str_pad(intval($_REQUEST['new_levels_expires_month'][$newkey]), 2, "0", STR_PAD_LEFT) . "-" . str_pad(intval($_REQUEST['new_levels_expires_day'][$newkey]), 2, "0", STR_PAD_LEFT);

					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => $expiration_date ),
						array(
							'status' => 'active',
							'membership_id' => $leveltoadd,
							'user_id' => $user_id ), // Where clause
						array( '%s' ),  // format for data
						array( '%s', '%d', '%d' ) // format for where clause
					);

				} else { // No expiration for me!
					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => NULL ),
						array(
							'status' => 'active',
							'membership_id' => $leveltoadd,
							'user_id' => $user_id
						),
						array( NULL ),
						array( '%s', '%d', '%d' )
					);
				}
			}
		}
	}
}
add_action( 'show_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'edit_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'personal_options_update', 'pmpro_membership_level_profile_fields_update' );
add_action( 'edit_user_profile_update', 'pmpro_membership_level_profile_fields_update' );


/**
 * Add the history view to the user profile
 *
 */
function pmpro_membership_history_profile_fields( $user ) {
	global $current_user;
	$membership_level_capability = apply_filters( 'pmpro_edit_member_capability', 'manage_options' );

	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	global $wpdb;

	//Show all invoices for user
	$invoices = $wpdb->get_results( $wpdb->prepare( "SELECT mo.*, UNIX_TIMESTAMP(mo.timestamp) as timestamp, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = %s ORDER BY mo.timestamp DESC", $user->ID ) );

	$subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_subscriptions WHERE user_id = %s ORDER BY startdate DESC", $user->ID ) );

	$levelshistory = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = %s ORDER BY id DESC", $user->ID ) );
	
	$totalvalue = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = %s AND status NOT IN('token','review','pending','error','refunded')", $user->ID ) );

	if ( $invoices || $subscriptions || $levelshistory ) { ?>
		<hr />
		<h3><?php esc_html_e( 'Member History', 'paid-memberships-pro' ); ?></h3>
		<p><strong><?php esc_html_e( 'Total Paid', 'paid-memberships-pro' ); ?></strong> <?php echo pmpro_formatPrice( $totalvalue ); ?></p>
		<ul id="member-history-filters" class="subsubsub">
			<li id="member-history-filters-orders"><a href="javascript:void(0);" class="current orders tab"><?php esc_html_e( 'Order History', 'paid-memberships-pro' ); ?></a> <span>(<?php echo count( $invoices ); ?>)</span></li>
			<li id="member-history-filters-subscriptions">| <a href="javascript:void(0);" class="tab"><?php esc_html_e( 'Subscription History', 'paid-memberships-pro' ); ?></a> <span>(<?php echo count( $subscriptions ); ?>)</span></li>
			<li id="member-history-filters-memberships">| <a href="javascript:void(0);" class="tab"><?php esc_html_e( 'Membership Levels History', 'paid-memberships-pro' ); ?></a> <span>(<?php echo count( $levelshistory ); ?>)</span></li>
		</ul>
		<br class="clear" />
		<?php
			// Build the selectors for the invoices history list based on history count.
			$invoices_classes = array();
			$invoices_classes[] = "widgets-holder-wrap";
			if ( ! empty( $invoices ) && count( $invoices ) > 2 ) {
				$invoices_classes[] = "pmpro_scrollable";
			}
			$invoice_class = implode( ' ', array_unique( $invoices_classes ) );
		?>
		<div id="member-history-orders" class="<?php echo esc_attr( $invoice_class ); ?>">
		<?php if ( $invoices ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Code', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Total', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Discount Code', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					<?php do_action('pmpromh_orders_extra_cols_header');?>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $invoices as $invoice ) { 
					$level = pmpro_getLevel( $invoice->membership_id );
					?>
					<tr>
						<td>
							<?php
								echo esc_html( sprintf(
									// translators: %1$s is the date and %2$s is the time.
									__( '%1$s at %2$s', 'paid-memberships-pro' ),
									esc_html( date_i18n( get_option( 'date_format' ), $invoice->timestamp ) ),
									esc_html( date_i18n( get_option( 'time_format' ), $invoice->timestamp ) )
								) );
							?>
						</td>
						<td class="order_code column-order_code has-row-actions">
							<strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $invoice->code ); ?></a></strong>
							<div class="row-actions">
								<span class="id">
									<?php echo sprintf(
										// translators: %s is the Order ID.
										__( 'ID: %s', 'paid-memberships-pro' ),
										esc_attr( $invoice->id )
									); ?>
								</span> |
								<span class="edit">
									<a title="<?php esc_attr_e( 'Edit', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'paid-memberships-pro' ); ?></a>
								</span> |
								<span class="print">
									<a target="_blank" title="<?php esc_attr_e( 'Print', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $invoice->id ), admin_url('admin-ajax.php' ) ) ); ?>"><?php esc_html_e( 'Print', 'paid-memberships-pro' ); ?></a>
								</span>
								<?php if ( function_exists( 'pmpro_add_email_order_modal' ) ) { ?>
									 |
									<span class="email">
										<a title="<?php esc_attr_e( 'Email', 'paid-memberships-pro' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link" data-order="<?php echo esc_attr( $invoice->id ); ?>"><?php esc_html_e( 'Email', 'paid-memberships-pro' ); ?></a>
									</span>
								<?php } ?>
							</div> <!-- end .row-actions -->
						</td>
						<td>
							<?php
								if ( ! empty( $level ) ) {
									echo esc_html( $level->name );
								} elseif ( $invoice->membership_id > 0 ) { ?>
									[<?php esc_html_e( 'deleted', 'paid-memberships-pro' ); ?>]
								<?php } else {
									esc_html_e( '&#8212;', 'paid-memberships-pro' );
								}
							?>
						</td>
						<td><?php echo pmpro_formatPrice( $invoice->total ); ?></td>
						<td><?php 
							if ( empty( $invoice->code_id ) ) {
								esc_html_e( '&#8212;', 'paid-memberships-pro' );
							} else {
								$discountQuery = "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = ".$invoice->code_id." LIMIT 1";
								$discount_code = $wpdb->get_row( $discountQuery );
								echo '<a href="admin.php?page=pmpro-discountcodes&edit='.$invoice->code_id.'">'. esc_attr( $discount_code->code ) . '</a>';
							}
						?></td>
						<td>
							<?php
								if ( empty( $invoice->status ) ) {
									esc_html_e( '&#8212;', 'paid-memberships-pro' );
								} else { ?>
									<span class="pmpro_order-status pmpro_order-status-<?php esc_attr_e( $invoice->status ); ?>">
										<?php if ( in_array( $invoice->status, array( 'success', 'cancelled' ) ) ) {
											esc_html_e( 'Paid', 'paid-memberships-pro' );
										} else {
											esc_html_e( ucwords( $invoice->status ) );
										} ?>
									</span>
									<?php
								}
							?>
						</td>
						<?php do_action( 'pmpromh_orders_extra_cols_body', $invoice ); ?>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
		<?php } else { ?>
            <table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'No membership orders found.', 'paid-memberships-pro' ); ?></td>
                    </tr>
                </tbody>
            </table>
		<?php } ?>
		</div> <!-- end #member-history-invoices -->
		<?php
			// Build the selectors for the subscription history list based on history count.
			$subscriptions_classes = array();
			$subscriptions_classes[] = "widgets-holder-wrap";
			if ( ! empty( $subscriptions ) && count( $subscriptions ) > 2 ) {
				$subscriptions_classes[] = "pmpro_scrollable";
			}
			$subscriptions_class = implode( ' ', array_unique( $subscriptions_classes ) );
		?>
		<div id="member-history-subscriptions" class="<?php echo esc_attr( $subscriptions_class ); ?>" style="display: none;">
		<?php if ( $subscriptions ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Subscription Transaction ID', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Gateway', 'paid-memberships-pro' ); ?>
					<th><?php esc_html_e( 'Gateway Environment', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Next Payment Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Ended', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $subscriptions as $subscription ) { 
					$level = pmpro_getLevel( $subscription->membership_level_id );
					?>
					<tr>
						<td><?php echo esc_html( $subscription->startdate ); ?></td>
						<td><a href="<?php echo ( esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 's' => $subscription->subscription_transaction_id ), admin_url('admin.php' ) ) ) ); ?>"><?php echo esc_html( $subscription->subscription_transaction_id ); ?></a></td>
						<td><?php if ( ! empty( $level ) ) { echo esc_html( $level->name ); } else { esc_html_e( 'N/A', 'paid-memberships-pro'); } ?></td>
						<td><?php echo esc_html( $subscription->gateway ); ?>
						<td><?php echo esc_html( $subscription->gateway_environment ); ?>
						<td><?php echo esc_html( $subscription->next_payment_date ); ?>
						<td><?php echo esc_html( $subscription->enddate ); ?>
						<td><?php echo esc_html( $subscription->status ); ?>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php } else { 
				esc_html_e( 'No subscriptions found.', 'paid-memberships-pro' );
			} ?>
		</div>
		<?php
			// Build the selectors for the membership levels history list based on history count.
			$levelshistory_classes = array();
			$levelshistory_classes[] = "widgets-holder-wrap";
			if ( ! empty( $levelshistory ) && count( $levelshistory ) > 4 ) {
				$levelshistory_classes[] = "pmpro_scrollable";
			}
			$levelshistory_class = implode( ' ', array_unique( $levelshistory_classes ) );
		?>
		<div id="member-history-memberships" class="<?php echo esc_attr( $levelshistory_class ); ?>" style="display: none;">
		<?php if ( $levelshistory ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Level ID', 'paid-memberships-pro' ); ?>
					<th><?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Start Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Date Modified', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'End Date', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Level Cost', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'paid-memberships-pro' ); ?></th>
					<?php do_action( 'pmpromh_member_history_extra_cols_header' ); ?>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $levelshistory as $levelhistory ) {
					$level = pmpro_getLevel( $levelhistory->membership_id );

					if ( $levelhistory->enddate === null || $levelhistory->enddate == '0000-00-00 00:00:00' ) {
						$levelhistory->enddate = __( 'Never', 'paid-memberships-pro' );
					} else {
						$levelhistory->enddate = date_i18n( get_option( 'date_format'), strtotime( $levelhistory->enddate ) );
					} ?>
					<tr>
						<td><?php if ( ! empty( $level ) ) { echo $level->id; } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
						<td><?php if ( ! empty( $level ) ) { echo $level->name; } else { esc_html_e( 'N/A', 'paid-memberships-pro' ); } ?></td>
						<td><?php echo ( $levelhistory->startdate === '0000-00-00 00:00:00' ? __('N/A', 'paid-memberships-pro') : date_i18n( get_option( 'date_format' ), strtotime( $levelhistory->startdate ) ) ); ?></td>
						<td><?php echo date_i18n( get_option( 'date_format'), strtotime( $levelhistory->modified ) ); ?></td>
						<td><?php echo esc_html( $levelhistory->enddate ); ?></td>
						<td><?php echo pmpro_getLevelCost( $levelhistory, true, true ); ?></td>
						<td>
							<?php 
								if ( empty( $levelhistory->status ) ) {
									echo '-';
								} else {
									echo esc_html( $levelhistory->status ); 
								}
							?>
						</td>
						<?php do_action( 'pmpromh_member_history_extra_cols_body', $user, $level ); ?>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php } else { ?>
                <table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tbody>
                        <tr>
                            <td><?php esc_html_e( 'No membership history found.', 'paid-memberships-pro'); ?></td>
                        </tr>
                    </tbody>
                </table>
			<?php } ?>
		</div> <!-- end #member-history-memberships -->
		<script>
			//tabs
			jQuery(document).ready(function() {
				jQuery('#member-history-filters a.tab').click(function() {
					//which tab?
					var tab = jQuery(this).parent().attr('id').replace('member-history-filters-', '');
					
					//un select tabs
					jQuery('#member-history-filters a.tab').removeClass('current');
					
					//select this tab
					jQuery('#member-history-filters-'+tab+' a').addClass('current');
					
					//show orders?
					if(tab == 'orders')
					{
						jQuery('#member-history-memberships').hide();
						jQuery('#member-history-subscriptions').hide();
						jQuery('#member-history-orders').show();
					}
					else if (tab == 'subscriptions' ) {
						jQuery('#member-history-memberships').hide();
						jQuery('#member-history-subscriptions').show();
						jQuery('#member-history-orders').hide();
					}
					else
					{
						jQuery('div#member-history-orders').hide();
						jQuery('#member-history-subscriptions').hide();
						jQuery('#member-history-memberships').show();

						<?php if ( count( $levelshistory ) > 5 ) { ?>
						jQuery('#member-history-memberships').css({'height': '150px', 'overflow': 'auto' });
						<?php } ?>
					}
				});
			});
		</script>
		<?php
	}	
}
add_action('edit_user_profile', 'pmpro_membership_history_profile_fields');
add_action('show_user_profile', 'pmpro_membership_history_profile_fields');


/**
 * Allow orders to be emailed from the member history section on user profile.
 *
 */
function pmpro_membership_history_email_modal() {
	$screen = get_current_screen();
	if ( $screen->base == 'user-edit' || $screen->base == 'profile' ) {
		// Require the core Paid Memberships Pro Admin Functions.
		if ( defined( 'PMPRO_DIR' ) ) {
			require_once( PMPRO_DIR . '/adminpages/functions.php' );
		}

		// Load the email order modal.
		if ( function_exists( 'pmpro_add_email_order_modal' ) ) {
			pmpro_add_email_order_modal();
		}
	}
}
add_action( 'in_admin_header', 'pmpro_membership_history_email_modal' );



/**
 * Display a frontend Member Profile Edit form and allow user to edit specific fields.
 *
 * @since 2.3
 */
function pmpro_member_profile_edit_form() {
	global $current_user;

	if ( ! is_user_logged_in() ) {
		echo '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_alert', 'pmpro_alert' ) . '"><a href="' . esc_url( pmpro_login_url() ) . '">' . esc_html__( 'Log in to edit your profile.', 'paid-memberships-pro' ) . '</a></div>';
		return;
	}

	// Saving profile updates.
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'update-profile' && $current_user->ID == $_POST['user_id'] && wp_verify_nonce( $_POST['update_user_nonce'], 'update-user_' . $current_user->ID ) ) {
		$update           = true;
		$user     		  = new stdClass;
		$user->ID         = $_POST[ 'user_id' ];
		do_action( 'pmpro_personal_options_update', $user->ID );
	} else {
		$update = false;
	}

	if ( $update ) {

		$errors = array();

		// Get all values from the $_POST, sanitize them, and build the $user object.
		if ( isset( $_POST['user_email'] ) ) {
			$user->user_email = sanitize_text_field( wp_unslash( $_POST['user_email'] ) );
		}
		if ( isset( $_POST['first_name'] ) ) {
			$user->first_name = sanitize_text_field( $_POST['first_name'] );
		}
		if ( isset( $_POST['last_name'] ) ) {
			$user->last_name = sanitize_text_field( $_POST['last_name'] );
		}
		if ( isset( $_POST['display_name'] ) ) {
			$user->display_name = sanitize_text_field( $_POST['display_name'] );
			$user->nickname = $user->display_name;
		}

		// Validate display name.
		if ( empty( $user->display_name ) ) {
			$errors[] = __( 'Please enter a display name.', 'paid-memberships-pro' );
		}

		// Don't allow admins to change their email address.
		if ( current_user_can( 'manage_options' ) ) {
			$user->user_email = $current_user->user_email;
		}

		// Validate email address.
		if ( empty( $user->user_email ) ) {
			$errors[] = __( 'Please enter an email address.', 'paid-memberships-pro' );
		} elseif ( ! is_email( $user->user_email ) ) {
			$errors[] = __( 'The email address isn&#8217;t correct.', 'paid-memberships-pro' );
		} else {
			$owner_id = email_exists( $user->user_email );
			if ( $owner_id && ( ! $update || ( $owner_id != $user->ID ) ) ) {
				$errors[] = __( 'This email is already registered, please choose another one.', 'paid-memberships-pro' );
			}
		}

		/**
		 * Fires before member profile update errors are returned.
		 *
		 * @param $errors WP_Error object (passed by reference).
		 * @param $update Whether this is a user update.
		 * @param $user   User object (passed by reference).
		 */
		do_action_ref_array( 'pmpro_user_profile_update_errors', array( &$errors, $update, &$user ) );

		// Show error messages.
		if ( ! empty( $errors ) ) { ?>
			<div class="<?php echo pmpro_get_element_class( 'pmpro_message pmpro_error', 'pmpro_error' ); ?>">
				<?php
					foreach ( $errors as $key => $value ) {
						echo '<p>' . $value . '</p>';
					}
				?>
			</div>
		<?php } else {
			// Save updated profile fields.
			wp_update_user( $user );
			?>
			<div class="<?php echo pmpro_get_element_class( 'pmpro_message pmpro_success', 'pmpro_success' ); ?>">
				<?php _e( 'Your profile has been updated.', 'paid-memberships-pro' ); ?>
			</div>
		<?php }
	} else {
		// Doing this so fields are set to new values after being submitted.
		$user = $current_user;
	}
	?>
	<div class="<?php echo pmpro_get_element_class( 'pmpro_member_profile_edit_wrap' ); ?>">
		<form id="member-profile-edit" class="<?php echo pmpro_get_element_class( 'pmpro_form' ); ?>" action="" method="post"
			<?php
				/**
				 * Fires inside the member-profile-edit form tag in the pmpro_member_profile_edit_form function.
				 *
				 * @since 2.4.1
				 */
				do_action( 'pmpro_member_profile_edit_form_tag' );
			?>
		>

			<?php wp_nonce_field( 'update-user_' . $current_user->ID, 'update_user_nonce' ); ?>

			<?php
			$user_fields = apply_filters( 'pmpro_member_profile_edit_user_object_fields',
				array(
					'first_name'	=> __( 'First Name', 'paid-memberships-pro' ),
					'last_name'		=> __( 'Last Name', 'paid-memberships-pro' ),
					'display_name'	=> __( 'Display name publicly as', 'paid-memberships-pro' ),
					'user_email'	=> __( 'Email', 'paid-memberships-pro' ),
				)
			);
			?>

			<div class="<?php echo pmpro_get_element_class( 'pmpro_checkout_box-user' ); ?>">
				<div class="<?php echo pmpro_get_element_class( 'pmpro_member_profile_edit-fields' ); ?>">
				<?php foreach ( $user_fields as $field_key => $label ) { ?>
					<div class="<?php echo pmpro_get_element_class( 'pmpro_member_profile_edit-field pmpro_member_profile_edit-field- ' . $field_key, 'pmpro_member_profile_edit-field- ' . $field_key ); ?>">
						<label for="<?php echo esc_attr( $field_key ); ?>"><?php esc_html_e( $label ); ?></label>
						<?php if ( current_user_can( 'manage_options' ) && $field_key === 'user_email' ) { ?>
							<input type="text" readonly="readonly" name="user_email" id="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" class="<?php echo pmpro_get_element_class( 'input', 'user_email' ); ?>" />
							<p class="<?php echo pmpro_get_element_class( 'lite' ); ?>"><?php esc_html_e( 'Site administrators must use the WordPress dashboard to update their email address.', 'paid-memberships-pro' ); ?></p>
						<?php } else { ?>
							<input type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( stripslashes( $user->{$field_key} ) ); ?>" class="<?php echo pmpro_get_element_class( 'input', $field_key ); ?>" />
						<?php } ?>
	            	</div>
				<?php } ?>
				</div> <!-- end pmpro_member_profile_edit-fields -->
			</div> <!-- end pmpro_checkout_box-user -->

			<?php
				/**
				 * Fires after the default Your Member Profile fields.
				 *
				 * @since 2.3
				 *
				 * @param WP_User $current_user The current WP_User object.
				 */
				do_action( 'pmpro_show_user_profile', $current_user );
			?>
			<input type="hidden" name="action" value="update-profile" />
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ) ; ?>" />
			<div class="<?php echo pmpro_get_element_class( 'pmpro_submit' ); ?>">
				<hr />
				<input type="submit" name="submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ); ?>" value="<?php esc_attr_e( 'Update Profile', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ); ?>" value="<?php esc_attr_e( 'Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo pmpro_url( 'account'); ?>';" />
			</div>
		</form>
	</div> <!-- end pmpro_member_profile_edit_wrap -->
	<?php
}

/**
 * Process password updates.
 * Hooks into personal_options_update.
 * Doesn't need to hook into edit_user_profile_update since
 * our change password page is only for the current user.
 *
 * @since 2.3
 */
function pmpro_change_password_process() {
	global $current_user;

	// Make sure we're on the right page.
	if ( empty( $_POST['action'] ) || $_POST['action'] != 'change-password' ) {
		return;
	}

	// Only let users change their own password.
	if ( empty( $current_user ) || empty( $_POST['user_id'] ) || $current_user->ID != $_POST['user_id'] ) {
		return;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $_POST['change_password_user_nonce'], 'change-password-user_' . $current_user->ID ) ) {
		return;
	}

	// Get all password values from the $_POST.
	if ( ! empty( $_POST['password_current'] ) ) {
		$password_current = sanitize_text_field( $_POST['password_current'] );
	} else {
		$password_current = '';
	}
	if ( ! empty( $_POST['pass1'] ) ) {
		$pass1 = sanitize_text_field( $_POST['pass1'] );
	} else {
		$pass1 = '';
	}
	if ( ! empty( $_POST['pass2'] ) ) {
		$pass2 = sanitize_text_field( $_POST['pass2'] );
	} else {
		$pass2 = '';
	}

	// Check that all password information is correct.
	$error = false;
	if ( isset( $password_current ) && ( empty( $pass1 ) || empty( $pass2 ) ) ) {
		$error = __( 'Please complete all fields.', 'paid-memberships-pro' );
	} elseif ( ! empty( $pass1 ) && empty( $password_current ) ) {
		$error = __( 'Please enter your current password.', 'paid-memberships-pro' );
	} elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
		$error = __( 'New passwords do not match.', 'paid-memberships-pro' );
	} elseif ( ! empty( $pass1 ) && ! wp_check_password( $password_current, $current_user->user_pass, $current_user->ID ) ) {
		$error = __( 'Your current password is incorrect.', 'paid-memberships-pro' );
	}

	// Change the password.
	if ( ! empty( $pass1 ) && empty( $error ) ) {
		wp_set_password( $pass1, $current_user->ID );

		//setting some cookies
		wp_set_current_user( $current_user->ID, $current_user->user_login );
		wp_set_auth_cookie( $current_user->ID, true, apply_filters( 'pmpro_checkout_signon_secure', force_ssl_admin() ) );

		pmpro_setMessage( __( 'Your password has been updated.', 'paid-memberships-pro' ), 'pmpro_success' );
	} else {
		pmpro_setMessage( $error, 'pmpro_error' );
	}
}
add_action( 'init', 'pmpro_change_password_process' );


/**
 * Display a frontend Change Password form and allow user to edit their password when logged in.
 *
 * @since 2.3
 */
function pmpro_change_password_form() {
	global $current_user, $pmpro_msg, $pmpro_msgt;
	?>
	<h2><?php esc_html_e( 'Change Password', 'paid-memberships-pro' ); ?></h2>
	<?php if ( ! empty( $pmpro_msg ) ) { ?>
		<div class="<?php echo pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ); ?>">
			<?php echo esc_html( $pmpro_msg ); ?>
		</div>
	<?php } ?>
	<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password_wrap' ); ?>">
		<form id="change-password" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'change-password' ); ?>" action="" method="post">

			<?php wp_nonce_field( 'change-password-user_' . $current_user->ID, 'change_password_user_nonce' ); ?>

			<div class="<?php echo pmpro_get_element_class( 'pmpro_checkout_box-password' ); ?>">
				<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-fields' ); ?>">
					<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-field pmpro_change_password-field-password_current', 'pmpro_change_password-field-password_current' ); ?>">
						<label for="password_current"><?php esc_html_e( 'Current Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="password_current" id="password_current" value="" class="<?php echo pmpro_get_element_class( 'input', 'password_current' ); ?>" />
						<span class="<?php echo pmpro_get_element_class( 'pmpro_asterisk' ); ?>"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_change_password-field-password_current -->
					<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-field pmpro_change_password-field-pass1', 'pmpro_change_password-field-pass1' ); ?>">
						<label for="pass1"><?php esc_html_e( 'New Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="pass1" id="pass1" value="" class="<?php echo pmpro_get_element_class( 'input pass1', 'pass1' ); ?>" autocomplete="off" />
						<span class="<?php echo pmpro_get_element_class( 'pmpro_asterisk' ); ?>"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
						<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php esc_html_e( 'Strength Indicator', 'paid-memberships-pro' ); ?></div>
						<p class="<?php echo pmpro_get_element_class( 'lite' ); ?>"><?php echo wp_get_password_hint(); ?></p>
					</div> <!-- end pmpro_change_password-field-pass1 -->
					<div class="<?php echo pmpro_get_element_class( 'pmpro_change_password-field pmpro_change_password-field-pass2', 'pmpro_change_password-field-pass2' ); ?>">
						<label for="pass2"><?php esc_html_e( 'Confirm New Password', 'paid-memberships-pro' ); ?></label></th>
						<input type="password" name="pass2" id="pass2" value="" class="<?php echo pmpro_get_element_class( 'input', 'pass2' ); ?>" autocomplete="off" />
						<span class="<?php echo pmpro_get_element_class( 'pmpro_asterisk' ); ?>"> <abbr title="<?php _e( 'Required Field', 'paid-memberships-pro' ); ?>">*</abbr></span>
					</div> <!-- end pmpro_change_password-field-pass2 -->
				</div> <!-- end pmpro_change_password-fields -->
			</div> <!-- end pmpro_checkout_box-password -->

			<input type="hidden" name="action" value="change-password" />
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ); ?>" />
			<div class="<?php echo pmpro_get_element_class( 'pmpro_submit' ); ?>">
				<hr />
				<input type="submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit', 'pmpro_btn-submit' ); ?>" value="<?php esc_attr_e('Change Password', 'paid-memberships-pro' );?>" />
				<input type="button" name="cancel" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-cancel', 'pmpro_btn-cancel' ); ?>" value="<?php esc_attr_e('Cancel', 'paid-memberships-pro' );?>" onclick="location.href='<?php echo esc_url( pmpro_url( 'account') ); ?>';" />
			</div>
		</form>
	</div> <!-- end pmpro_change_password_wrap -->
	<?php
}
