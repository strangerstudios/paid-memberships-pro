<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_memberslist")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}

	//vars
	global $wpdb;
	if(isset($_REQUEST['s']))
		$s = sanitize_text_field(trim($_REQUEST['s']));
	else
		$s = "";

	if(isset($_REQUEST['l']))
		$l = intval($_REQUEST['l']);
	else
		$l = false;

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form id="posts-filter" method="get" action="">
	<h2>
		<?php _e('Members List', 'pmpro');?>
		<a target="_blank" href="<?php echo admin_url('admin-ajax.php');?>?action=memberslist_csv&s=<?php echo esc_attr($s);?>&l=<?php echo $l?>" class="add-new-h2"><?php _e('Export to CSV', 'pmpro');?></a>
	</h2>
	<ul class="subsubsub">
		<li>
			<?php _e('Show', 'pmpro');?>
			<select name="l" onchange="jQuery('#posts-filter').submit();">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php _e('All Levels', 'pmpro');?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
				<?php
					}
				?>
				<option value="cancelled" <?php if($l == "cancelled") { ?>selected="selected"<?php } ?>><?php _e('Cancelled Members', 'pmpro');?></option>
				<option value="expired" <?php if($l == "expired") { ?>selected="selected"<?php } ?>><?php _e('Expired Members', 'pmpro');?></option>
				<option value="oldmembers" <?php if($l == "oldmembers") { ?>selected="selected"<?php } ?>><?php _e('Old Members', 'pmpro');?></option>
			</select>
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input"><?php _e('Search Members', 'pmpro');?>:</label>
		<input type="hidden" name="page" value="pmpro-memberslist" />
		<input id="post-search-input" type="text" value="<?php echo esc_attr($s);?>" name="s"/>
		<input class="button" type="submit" value="<?php _e('Search Members', 'pmpro');?>"/>
	</p>
	<?php
		//some vars for the search
		if(isset($_REQUEST['pn']))
			$pn = intval($_REQUEST['pn']);
		else
			$pn = 1;

		if(isset($_REQUEST['limit']))
			$limit = intval($_REQUEST['limit']);
		else
			$limit = 15;

		$end = $pn * $limit;
		$start = $end - $limit;

		if($s)
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id ";

			if($l == "oldmembers" || $l == "expired" || $l == "cancelled")
				$sqlQuery .= " LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";

			$sqlQuery .= " WHERE mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%') ";

			if($l == "oldmembers")
				$sqlQuery .= " AND mu.status <> 'active' AND mu2.status IS NULL ";
			elseif($l == "expired")
				$sqlQuery .= " AND mu.status = 'expired' AND mu2.status IS NULL ";
			elseif($l == "cancelled")
				$sqlQuery .= " AND mu.status IN('cancelled', 'admin_cancelled') AND mu2.status IS NULL ";
			elseif($l)
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
			else
				$sqlQuery .= " AND mu.status = 'active' ";

			$sqlQuery .= "GROUP BY u.ID ";

			if($l == "oldmembers" || $l == "expired" || $l == "cancelled")
				$sqlQuery .= "ORDER BY enddate DESC ";
			else
				$sqlQuery .= "ORDER BY u.user_registered DESC ";

			$sqlQuery .= "LIMIT $start, $limit";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id";

			if($l == "oldmembers" || $l == "expired" || $l == "cancelled")
				$sqlQuery .= " LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";

			$sqlQuery .= " WHERE mu.membership_id > 0  ";

			if($l == "oldmembers")
				$sqlQuery .= " AND mu.status <> 'active' AND mu2.status IS NULL ";
			elseif($l == "expired")
				$sqlQuery .= " AND mu.status = 'expired' AND mu2.status IS NULL ";
			elseif($l == "cancelled")
				$sqlQuery .= " AND mu.status IN('cancelled', 'admin_cancelled') AND mu2.status IS NULL ";
			elseif($l)
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
			else
				$sqlQuery .= " AND mu.status = 'active' ";
			$sqlQuery .= "GROUP BY u.ID ";

			if($l == "oldmembers" || $l == "expired" || $l == "cancelled")
				$sqlQuery .= "ORDER BY enddate DESC ";
			else
				$sqlQuery .= "ORDER BY u.user_registered DESC ";

			$sqlQuery .= "LIMIT $start, $limit";
		}

		$sqlQuery = apply_filters("pmpro_members_list_sql", $sqlQuery);

		$theusers = $wpdb->get_results($sqlQuery);
		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");

		if($theusers)
		{
			$calculate_revenue = apply_filters("pmpro_memberslist_calculate_revenue", false);
			if($calculate_revenue)
			{
				$initial_payments = pmpro_calculateInitialPaymentRevenue($s, $l);
				$recurring_payments = pmpro_calculateRecurringRevenue($s, $l);
				?>
				<p class="clear"><?php echo strval($totalrows)?> members found. These members have paid <strong>$<?php echo number_format($initial_payments)?> in initial payments</strong> and will generate an estimated <strong>$<?php echo number_format($recurring_payments)?> in revenue over the next year</strong>, or <strong>$<?php echo number_format($recurring_payments/12)?>/month</strong>. <span class="pmpro_lite">(This estimate does not take into account trial periods or billing limits.)</span></p>
				<?php
			}
			else
			{
			?>
			<p class="clear"><?php printf(__("%d members found.", "pmpro"), $totalrows);?></span></p>
			<?php
			}
		}
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th><?php _e('ID', 'pmpro');?></th>
				<th><?php _e('Username', 'pmpro');?></th>
				<th><?php _e('First&nbsp;Name', 'pmpro');?></th>
				<th><?php _e('Last&nbsp;Name', 'pmpro');?></th>
				<th><?php _e('Email', 'pmpro');?></th>
				<?php do_action("pmpro_memberslist_extra_cols_header", $theusers);?>
				<th><?php _e('Billing Address', 'pmpro');?></th>
				<th><?php _e('Membership', 'pmpro');?></th>
				<th><?php _e('Fee', 'pmpro');?></th>
				<th><?php _e('Joined', 'pmpro');?></th>
				<th>
					<?php
						if($l == "oldmembers")
							_e('Ended', 'pmpro');
						else
							_e('Expires', 'pmpro');
					?>
				</th>
			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">
			<?php
				$count = 0;
				foreach($theusers as $auser)
				{
					//get meta
					$theuser = get_userdata($auser->ID);
					?>
						<tr <?php if($count++ % 2 == 0) { ?>class="alternate"<?php } ?>>
							<td><?php echo $theuser->ID?></td>
							<td class="username column-username">
								<?php echo get_avatar($theuser->ID, 32)?>
								<strong>
									<?php
										$userlink = '<a href="user-edit.php?user_id=' . $theuser->ID . '">' . $theuser->user_login . '</a>';
										$userlink = apply_filters("pmpro_members_list_user_link", $userlink, $theuser);
										echo $userlink;
									?>
								</strong>
								<br />
								<?php
									// Set up the hover actions for this user
									$actions = apply_filters( 'pmpro_memberslist_user_row_actions', array(), $theuser );
									$action_count = count( $actions );
									$i = 0;
									if($action_count)
									{
										$out = '<div class="row-actions">';
										foreach ( $actions as $action => $link ) {
											++$i;
											( $i == $action_count ) ? $sep = '' : $sep = ' | ';
											$out .= "<span class='$action'>$link$sep</span>";
										}
										$out .= '</div>';
										echo $out;
									}
								?>
							</td>
							<td><?php echo $theuser->first_name?></td>
							<td><?php echo $theuser->last_name?></td>
							<td><a href="mailto:<?php echo esc_attr($theuser->user_email)?>"><?php echo $theuser->user_email?></a></td>
							<?php do_action("pmpro_memberslist_extra_cols_body", $theuser);?>
							<td>
								<?php
									echo pmpro_formatAddress(trim($theuser->pmpro_bfirstname . " " . $theuser->pmpro_blastname), $theuser->pmpro_baddress1, $theuser->pmpro_baddress2, $theuser->pmpro_bcity, $theuser->pmpro_bstate, $theuser->pmpro_bzipcode, $theuser->pmpro_bcountry, $theuser->pmpro_bphone);
								?>
							</td>
							<td><?php echo $auser->membership?></td>
							<td>
								<?php if((float)$auser->initial_payment > 0) { ?>
									<?php echo pmpro_formatPrice($auser->initial_payment);?>
								<?php } ?>
								<?php if((float)$auser->initial_payment > 0 && (float)$auser->billing_amount > 0) { ?>+<br /><?php } ?>
								<?php if((float)$auser->billing_amount > 0) { ?>
									<?php echo pmpro_formatPrice($auser->billing_amount);?>/<?php if($auser->cycle_number > 1) { echo $auser->cycle_number . " " . $auser->cycle_period . "s"; } else { echo $auser->cycle_period; } ?>
								<?php } ?>
								<?php if((float)$auser->initial_payment <= 0 && (float)$auser->billing_amount <= 0) { ?>
									-
								<?php } ?>
							</td>
							<td><?php echo date(get_option("date_format"), strtotime($theuser->user_registered, current_time("timestamp")))?></td>
							<td>
								<?php
									if($auser->enddate)
										echo apply_filters("pmpro_memberslist_expires_column", date(get_option('date_format'), $auser->enddate), $auser);
									else
										echo __(apply_filters("pmpro_memberslist_expires_column", "Never", $auser), "pmpro");
								?>
							</td>
						</tr>
					<?php
				}

				if(!$theusers)
				{
				?>
				<tr>
					<td colspan="9"><p><?php _e("No members found.", "pmpro");?> <?php if($l) { ?><a href="?page=pmpro-memberslist&s=<?php echo esc_attr($s);?>"><?php _e("Search all levels", "pmpro");?></a>.<?php } ?></p></td>
				</tr>
				<?php
				}
			?>
		</tbody>
	</table>
	</form>

	<?php
	echo pmpro_getPaginationString($pn, $totalrows, $limit, 1, get_admin_url(NULL, "/admin.php?page=pmpro-memberslist&s=" . urlencode($s)), "&l=$l&limit=$limit&pn=");
	?>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
