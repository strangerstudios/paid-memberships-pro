<?php	
	//only admins can get this
	if(!function_exists("current_user_can") || !current_user_can("manage_options"))
	{
		die("You do not have permissions to perform this action.");
	}	
	
	global $wpdb;	
	
	//get users	
	if(isset($_REQUEST['s']))
		$s = $_REQUEST['s'];
	else
		$s = "";
	
	if(isset($_REQUEST['l']))
		$l = $_REQUEST['l'];
	else
		$l = false;
	
	//some vars for the search
	if(!empty($_REQUEST['pn']))
		$pn = $_REQUEST['pn'];
	else
		$pn = 1;
	
	if(!empty($_REQUEST['limit']))
		$limit = $_REQUEST['limit'];
	else
		$limit = false;
		
	if($limit)
	{	
		$end = $pn * $limit;
		$start = $end - $limit;		
	}
		
	if($s)
	{
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.billing_amount, mu.cycle_period, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE mu.membership_id > 0 AND (u.user_login LIKE '%$s%' OR u.user_email LIKE '%$s%' OR um.meta_value LIKE '%$s%') ";
	
		if($l)
			$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";					
			
		$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC LIMIT $start, $limit";
	}
	else
	{
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.billing_amount, mu.cycle_period, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id ";
		$sqlQuery .= "WHERE mu.membership_id > 0 ";
		if($l)
			$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";										
		$sqlQuery .= "ORDER BY user_registered DESC ";
		if($limit)
			$sqlQuery .= "LIMIT $start, $limit";
	}
		
	$theusers = $wpdb->get_results($sqlQuery);	
	$csvoutput = "id,username,firstname,lastname,email,billing firstname,billing lastname,address1,address2,city,state,zipcode,country,phone,membership,fee,term,joined,expires\n";	
	
	if($theusers)
	{
		foreach($theusers as $theuser)
		{
			//get meta
			$sqlQuery = "SELECT meta_key as `key`, meta_value as `value` FROM $wpdb->usermeta WHERE $wpdb->usermeta.user_id = '" . $theuser->ID . "'";					
			$metavalues = pmpro_getMetavalues($sqlQuery);	
			
			/*
				Fixing notice with bcountry. Should probably check other values just in case.				
			*/
			if(empty($metavalues->pmpro_bcountry))
				$metavalues->pmpro_bcountry = "";
			
			$csvoutput .= enclose($theuser->ID) . "," .
						  enclose($theuser->user_login) . "," .						  
						  enclose($metavalues->first_name) . "," .
						  enclose($metavalues->last_name) . "," .
						  enclose($theuser->user_email) . "," .
						  enclose($metavalues->pmpro_bfirstname) . "," .
						  enclose($metavalues->pmpro_blastname) . "," .
						  enclose($metavalues->pmpro_baddress1) . "," .
						  enclose($metavalues->pmpro_baddress2) . "," .
						  enclose($metavalues->pmpro_bcity) . "," .
						  enclose($metavalues->pmpro_bstate) . "," .
						  enclose($metavalues->pmpro_bzipcode) . "," .
						  enclose($metavalues->pmpro_bcountry) . "," .
						  enclose($metavalues->pmpro_bphone) . "," .
						  enclose($theuser->membership) . "," .
						  enclose($theuser->billing_amount) . "," .
						  enclose($theuser->cycle_period) . "," .					  
						  enclose(date("m/d/Y", $theuser->joindate)) . ",";
			if($theuser->enddate)
				$csvoutput .= enclose(date("m/d/Y", $theuser->enddate));
			else
				$csvoutput .= enclose("Never");
			$csvoutput .= "\n";
											
		}
	}
	
	$size_in_bytes = strlen($csvoutput);
	header("Content-type: text/csv");
	//header("Content-type: application/vnd.ms-excel");
	if($s && $l)
		header("Content-Disposition: attachment; filename=members_list_" . $l . "_level" . $s . ".csv; size=$size_in_bytes");
	elseif($s)
		header("Content-Disposition: attachment; filename=members_list_" . $s . ".csv; size=$size_in_bytes");
	elseif($l)
		header("Content-Disposition: attachment; filename=members_list_level" . $l . ".csv; size=$size_in_bytes");
	else
		header("Content-Disposition: attachment; filename=members_list.csv; size=$size_in_bytes");
	
	print $csvoutput;
	
	function enclose($s)
	{
		return "\"" . str_replace("\"", "\\\"", $s) . "\"";
	}
?>
