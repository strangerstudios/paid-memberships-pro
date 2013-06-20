<?php
/*
	PMPro Report
	Title: Logins
	Slug: login
	
	For each report, add a line like:
	global $pmpro_reports;
	$pmpro_reports['slug'] = 'Title';
	
	For each report, also write two functions:
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/
global $pmpro_reports;
$pmpro_reports['login'] = 'Logins';

function pmpro_report_login_widget()
{
	global $wpdb;
	$logins = get_option("pmpro_logins", array("today"=>0, "thisday"=>date("Y-m-d"), "alltime"=>0, "month"=>0, "thismonth"=>date("n")));
?>
<p>Logins Today: <?php echo $logins['today'];?></p>
<p>Logins This Month: <?php echo $logins['month'];?></p>
<p>Logins All Time: <?php echo $logins['alltime'];?></p>
<?php
}

function pmpro_report_login_page()
{
	global $wpdb;
	
	//vars
	if(!empty($_REQUEST['s']))
		$s = $_REQUEST['s'];
	else
		$s = "";
		
	if(!empty($_REQUEST['l']))
		$l = $_REQUEST['l'];
	else
		$l = "";
?>
	<form id="posts-filter" method="get" action="">	
	<h2>
		Logins Report		
	</h2>		
	<ul class="subsubsub">
		<li>			
			Show <select name="l" onchange="jQuery('#posts-filter').submit();">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>>All Users</option>
				<option value="all" <?php if($l == "all") { ?>selected="selected"<?php } ?>>All Levels</option>
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
		<label class="hidden" for="post-search-input">Search <?php if(empty($l)) echo "Users"; else echo "Members";?>:</label>
		<input type="hidden" name="page" value="pmpro-reports" />		
		<input type="hidden" name="report" value="login" />		
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
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE (u.user_login LIKE '%$s%' OR u.user_email LIKE '%$s%' OR um.meta_value LIKE '%$s%') ";
		
			if($l == "all")
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id > 0 ";
			elseif($l)
				$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";					
				
			$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC LIMIT $start, $limit";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id";
			$sqlQuery .= " WHERE 1=1 ";
			
			if($l == "all")
				$sqlQuery .= " AND mu.membership_id > 0  AND mu.status = 'active' ";
			elseif($l)
				$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";
			$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC LIMIT $start, $limit";
		}

		$sqlQuery = apply_filters("pmpro_members_list_sql", $sqlQuery);
		
		$theusers = $wpdb->get_results($sqlQuery);
		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");
		
		if($theusers)
		{
		?>
		<p class="clear"><?php echo strval($totalrows)?> <?php if(empty($l)) echo "users"; else echo "members";?> found.	
		<?php		
		}		
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th>ID</th>
				<th>User</th>	
				<th>Name</th>
				<th>Membership</th>	
				<th>Joined</th>
				<th>Expires</th>
				<th>Last Login</th>
				<th>Logins This Month</th>
				<th>Total Logins</th>				
			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">	
			<?php	
				$count = 0;							
				foreach($theusers as $auser)
				{
					//get meta																					
					$theuser = get_userdata($auser->ID);
					$logins = get_user_meta($auser->ID, "pmpro_logins", true);
					if(empty($logins))
						$logins = array("last"=>"N/A", "month"=>"N/A", "alltime"=>"N/A");
					?>
						<tr <?php if($count++ % 2 == 0) { ?>class="alternate"<?php } ?>>
							<td><?php echo $theuser->ID?></td>
							<td>
								<?php echo get_avatar($theuser->ID, 32)?>
								<strong>
									<?php
										$userlink = '<a href="user-edit.php?user_id=' . $theuser->ID . '">' . $theuser->user_login . '</a>';
										$userlink = apply_filters("pmpro_members_list_user_link", $userlink, $theuser);
										echo $userlink;
									?>																		
								</strong>
							</td>										
							<td>
								<?php echo $theuser->display_name;?>
							</td>
							<td><?php echo $auser->membership?></td>												
							<td><?php echo date("m/d/Y", strtotime($theuser->user_registered))?></td>
							<td>
								<?php 
									if($auser->enddate) 
										echo date(get_option('date_format'), $auser->enddate);
									else
										echo "Never";
								?>
							</td>
							<td><?php echo $logins['last'];?></td>
							<td><?php echo $logins['month'];?></td>
							<td><?php echo $logins['alltime'];?></td>
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
	echo pmpro_getPaginationString($pn, $totalrows, $limit, 1, get_admin_url(NULL, "/admin.php?page=pmpro-reports&report=logins&s=" . urlencode($s)), "&l=$l&limit=$limit&pn=");
	?>
<?php
}

/*
	Other code required for your reports. This file is loaded every time WP loads with PMPro enabled.
*/

//track logins
function pmpro_report_login_wp_login($user_login)
{
	//get user data
	$user = get_user_by("login", $user_login);	
	$logins = $user->pmpro_logins;
	if(empty($logins))
		$logins = array("last"=>"N/A", "month"=>0, "alltime"=>0);
		
	//track logins for user
	$logins['last'] = date(get_option("date_format"));
	$logins['alltime']++;
	$thismonth = date("n");
	if($thismonth == $logins['thismonth'])
		$logins['month']++;
	else
		$logins['month'] = 1;
	
	//update user data
	update_user_meta($user->ID, "pmpro_logins", $logins);
	
	//track logins overall
	$logins = get_option("pmpro_logins");
	if(empty($logins))
		$logins = array("last"=>"N/A", "month"=>0, "alltime"=>0);
	
	$logins['alltime']++;
	$thisdate = date("Y-d-m");
	if($thisdate == $logins['thisdate'])
		$logins['today']++;
	else
		$logins['today'] = 1;
	if($thismonth == $logins['thismonth'])
		$logins['month']++;
	else
		$logins['month'] = 1;
	
	update_option("pmpro_logins", $logins);		
}
add_action("wp_login", "pmpro_report_login_wp_login");