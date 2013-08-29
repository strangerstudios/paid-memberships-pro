<?php	
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_memberslist_csv")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
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
	else
	{
		$end = NULL;
		$start = NULL;
	}	
	
	if($s)
	{
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, u.user_login, u.user_nicename, u.user_url, u.user_registered, u.user_status, u.display_name, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE mu.status = 'active' AND mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%') ";
	
		if($l)
			$sqlQuery .= " AND mu.membership_id = '" . esc_sql($l) . "' ";					
			
		$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC ";
		
		if($limit)
			$sqlQuery .= "LIMIT $start, $limit";
	}
	else
	{
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, u.user_login, u.user_nicename, u.user_url, u.user_registered, u.user_status, u.display_name, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id";
		$sqlQuery .= " WHERE mu.membership_id > 0 AND mu.status = 'active' ";
		if($l)
			$sqlQuery .= " AND mu.membership_id = '" . $l . "' ";										
		$sqlQuery .= "ORDER BY user_registered DESC ";
		if($limit)
			$sqlQuery .= "LIMIT $start, $limit";
	}
	
	$sqlQuery = apply_filters("pmpro_members_list_sql", $sqlQuery);	
	
	$theusers = $wpdb->get_results($sqlQuery);	

	$heading = "id,username,firstname,lastname,email,billing firstname,billing lastname,address1,address2,city,state,zipcode,country,phone,membership,initial payment,fee,term,discount_code_id,discount_code,joined,expires";
	$heading = apply_filters("pmpro_members_list_csv_heading", $heading);
	$csvoutput = $heading;
	
	//these are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID)
	$default_columns = array(
		array("theuser", "ID"),
		array("theuser", "user_login"),
		array("metavalues", "first_name"),
		array("metavalues", "last_name"),
		array("theuser", "user_email"),
		array("metavalues", "pmpro_bfirstname"),
		array("metavalues", "pmpro_blastname"),
		array("metavalues", "pmpro_baddress1"),
		array("metavalues", "pmpro_baddress2"),
		array("metavalues", "pmpro_bcity"),
		array("metavalues", "pmpro_bstate"),
		array("metavalues", "pmpro_bzipcode"),
		array("metavalues", "pmpro_bcountry"),
		array("metavalues", "pmpro_bphone"),
		array("theuser", "membership"),
		array("theuser", "initial_payment"),
		array("theuser", "billing_amount"),
		array("theuser", "cycle_period"),
		array("discount_code", "id"),
		array("discount_code", "code")
		//joindate and enddate are handled specifically below
	);

	//filter
	$default_columns = apply_filters("pmpro_members_list_csv_default_columns", $default_columns);
	
	//any extra columns
	$extra_columns = apply_filters("pmpro_members_list_csv_extra_columns", array());
	if(!empty($extra_columns))
	{
		foreach($extra_columns as $heading => $callback)
		{
			$csvoutput .= "," . $heading;
		}
	}
	
	$csvoutput .= "\n";	
	
	if($theusers)
	{
		foreach($theusers as $theuser)
		{
			//get meta
			$sqlQuery = "SELECT meta_key as `key`, meta_value as `value` FROM $wpdb->usermeta WHERE $wpdb->usermeta.user_id = '" . $theuser->ID . "'";								
			$metavalues = pmpro_getMetavalues($sqlQuery);	
			$theuser->metavalues = $metavalues;
			$sqlQuery = "SELECT c.id, c.code FROM $wpdb->pmpro_discount_codes_uses cu LEFT JOIN $wpdb->pmpro_discount_codes c ON cu.code_id = c.id WHERE cu.user_id = '" . $theuser->ID . "' ORDER BY c.id DESC LIMIT 1";			
			$discount_code = $wpdb->get_row($sqlQuery);
			
			//default columns			
			if(!empty($default_columns))
			{
				$count = 0;
				foreach($default_columns as $col)
				{
					//add comma after the first item
					$count++;
					if($count > 1)
						$csvoutput .= ",";
						
					//checking $object->property. note the double $$
					if(!empty($$col[0]->$col[1]))
						$csvoutput .= pmpro_enclose($$col[0]->$col[1]);	//output the value				
				}
			}
									
			//joindate and enddate
			$csvoutput .= "," . pmpro_enclose(date("Y-m-d", $theuser->joindate)) . ",";
			if($theuser->enddate)
				$csvoutput .= pmpro_enclose(date("Y-m-d", $theuser->enddate));
			else
				$csvoutput .= pmpro_enclose("Never");
				
			//any extra columns			
			if(!empty($extra_columns))
			{
				foreach($extra_columns as $heading => $callback)
				{
					$csvoutput .= "," . pmpro_enclose(call_user_func($callback, $theuser));
				}
			}
				
			$csvoutput .= "\n";
											
		}
	}
		
	$size_in_bytes = strlen($csvoutput);
	header("Content-type: text/csv");
	//header("Content-type: application/vnd.ms-excel");
	if($s && $l)
		header("Content-Disposition: attachment; filename=members_list_" . intval($l) . "_level" . sanitize_file_name($s) . ".csv; size=$size_in_bytes");
	elseif($s)
		header("Content-Disposition: attachment; filename=members_list_" . sanitize_file_name($s) . ".csv; size=$size_in_bytes");
	elseif($l)
		header("Content-Disposition: attachment; filename=members_list_level" . intval($l) . ".csv; size=$size_in_bytes");
	else
		header("Content-Disposition: attachment; filename=members_list.csv; size=$size_in_bytes");
	
	print $csvoutput;
	
	function pmpro_enclose($s)
	{
		return "\"" . str_replace("\"", "\\\"", $s) . "\"";
	}