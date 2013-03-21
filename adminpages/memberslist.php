<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_memberslist")))
	{
		die("You do not have permissions to perform this action.");
	}	
	
	//vars
	global $wpdb, $pmpro_currency_symbol;
	if(isset($_REQUEST['s']))
		$s = $_REQUEST['s'];
	else
		$s = "";
	
	if(isset($_REQUEST['l']))
		$l = $_REQUEST['l'];
	else
		$l = false;
?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?php echo PMPRO_URL?>/images/PaidMembershipsPro.gif" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro_tagline">Membership Plugin for WordPress</div>
		
		<div class="pmpro_meta"><a href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>">Plugin Support</a> | <a href="http://www.paidmembershipspro.com/forums/">User Forum</a> | <strong>Version <?php echo PMPRO_VERSION?></strong></div>
	</div>
	<br style="clear:both;" />		

	<form id="posts-filter" method="get" action="">	
	<h2>
		Members Report
		<small>(<a target="_blank" href="<?php echo admin_url('admin-ajax.php');?>?action=memberslist_csv&s=<?php echo $s?>&l=<?php echo $l?>">Export to CSV</a>)</small>
	</h2>		
	<ul class="subsubsub">
		<li>			
			Show <select name="l" onchange="jQuery('#posts-filter').submit();">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>>All Levels</option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
				<?php
					}
				?>
			</select>			
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input">Search Members:</label>
		<input type="hidden" name="page" value="pmpro-memberslist" />		
		<input id="post-search-input" type="text" value="<?php echo $s?>" name="s"/>
		<input class="button" type="submit" value="Search Members"/>
	</p>
	<?php 
		//some vars for the search
		if(isset($_REQUEST['pn']))
			$pn = $_REQUEST['pn'];
		else
			$pn = 1;
			
		if(isset($_REQUEST['limit']))
			$limit = $_REQUEST['limit'];
		else
			$limit = 15;
		
		$end = $pn * $limit;
		$start = $end - $limit;				
					
		if($s)
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE mu.status = 'active' AND mu.membership_id > 0 AND (u.user_login LIKE '%$s%' OR u.user_email LIKE '%$s%' OR um.meta_value LIKE '%$s%') ";
		
			if($l)
				$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";					
				
			$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC LIMIT $start, $limit";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id";
			$sqlQuery .= " WHERE mu.membership_id > 0  AND mu.status = 'active' ";
			if($l)
				$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";
			$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC LIMIT $start, $limit";
		}
						
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
			<p class="clear"><?php echo strval($totalrows)?> members found.	
			<?php
			}
		}		
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th>ID</th>
				<th>Username</th>
				<th>First&nbsp;Name</th>
				<th>Last&nbsp;Name</th>
				<th>Email</th>
				<?php do_action("pmpro_memberslist_extra_cols_header", $theusers);?>
				<th>Billing Address</th>
				<th>Membership</th>	
				<th>Fee</th>
				<th>Joined</th>
				<th>Expires</th>
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
							<td>
								<?php echo get_avatar($theuser->ID, 32)?>
								<strong><a href="user-edit.php?user_id=<?php echo $theuser->ID?>"><?php echo $theuser->user_login?></a></strong>
							</td>
							<td><?php echo $theuser->first_name?></td>
							<td><?php echo $theuser->last_name?></td>
							<td><a href="mailto:<?php echo $theuser->user_email?>"><?php echo $theuser->user_email?></a></td>
							<?php do_action("pmpro_memberslist_extra_cols_body", $theuser);?>
							<td>
								<?php 
									if(empty($theuser->pmpro_bfirstname))
										$theuser->pmpro_bfirstname = "";
									if(empty($theuser->pmpro_blastname))
										$theuser->pmpro_blastname = "";
									echo trim($theuser->pmpro_bfirstname . " " . $theuser->pmpro_blastname);
								?><br />
								<?php if(!empty($theuser->pmpro_baddress1)) { ?>
									<?php echo $theuser->pmpro_baddress1; ?><br />
									<?php if(!empty($theuser->pmpro_baddress2)) echo $theuser->pmpro_baddress2 . "<br />"; ?>										
									<?php if($theuser->pmpro_bcity && $theuser->pmpro_bstate) { ?>
										<?php echo $theuser->pmpro_bcity?>, <?php echo $theuser->pmpro_bstate?> <?php echo $theuser->pmpro_bzipcode?>  <?php if(!empty($theuser->pmpro_bcountry)) echo $theuser->pmpro_bcountry?><br />												
									<?php } ?>
								<?php } ?>
								<?php if(!empty($theuser->pmpro_bphone)) echo formatPhone($theuser->pmpro_bphone);?>
							</td>
							<td><?php echo $auser->membership?></td>	
							<td>										
								<?php if((float)$auser->initial_payment > 0) { ?>
									<?php echo $pmpro_currency_symbol; ?><?php echo $auser->initial_payment?>
								<?php } ?>
								<?php if((float)$auser->initial_payment > 0 && (float)$auser->billing_amount > 0) { ?>+<br /><?php } ?>
								<?php if((float)$auser->billing_amount > 0) { ?>
									<?php echo $pmpro_currency_symbol; ?><?php echo $auser->billing_amount?>/<?php echo $auser->cycle_period?>
								<?php } ?>
								<?php if((float)$auser->initial_payment <= 0 && (float)$auser->billing_amount <= 0) { ?>
									-
								<?php } ?>
							</td>						
							<td><?php echo date("m/d/Y", strtotime($theuser->user_registered))?></td>
							<td>
								<?php 
									if($auser->enddate) 
										echo date(get_option('date_format'), $auser->enddate);
									else
										echo "Never";
								?>
							</td>
						</tr>
					<?php
				}
				
				if(!$theusers)
				{
				?>
				<tr>
					<td colspan="9"><p>No members found. <?php if($l) { ?><a href="?page=pmpro-memberslist&s=<?php echo $s?>">Search all levels</a>.<?php } ?></p></td>
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
	
</div>
