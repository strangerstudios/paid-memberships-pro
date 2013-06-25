<?php	
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_orders_csv")))
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
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS o.id FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->users u ON o.user_id = u.ID LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id ";
		
		$join_with_usermeta = apply_filters("pmpro_orders_search_usermeta", false);
		if($join_with_usermeta)
			$sqlQuery .= "LEFT JOIN $wpdb->usermeta um ON o.user_id = um.user_id ";
		
		$sqlQuery .= "WHERE (1=2 ";
						
		$fields = array("o.id", "o.code", "o.billing_name", "o.billing_street", "o.billing_city", "o.billing_state", "o.billing_zip", "o.billing_phone", "o.payment_type", "o.cardtype", "o.accountnumber", "o.status", "o.gateway", "o.gateway_environment", "o.payment_transaction_id", "o.subscription_transaction_id", "u.user_login", "u.user_email", "u.display_name", "l.name");
		
		if($join_with_usermeta)
			$fields[] = "um.meta_value";
				
		$fields = apply_filters("pmpro_orders_search_fields", $fields);
		
		foreach($fields as $field)
			$sqlQuery .= " OR " . $field . " LIKE '%" . $wpdb->escape($s) . "%' ";
		$sqlQuery .= ") ";
		$sqlQuery .= "ORDER BY o.timestamp DESC ";
	}
	else
	{
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS id FROM $wpdb->pmpro_membership_orders ORDER BY timestamp DESC ";
	}
	
	if($limit)
		$sqlQuery .= "LIMIT $start, $limit";
		
	$order_ids = $wpdb->get_col($sqlQuery);	
		
	$csvoutput = "id,user_id,user_login,first_name,last_name,user_email,billing_name,billing_street,billing_city,billing_state,billing_zip,billing_country,billing_phone,membership_id,level_name,subtotal,tax,couponamount,total,payment_type,cardtype,accountnumber,expirationmonth,expirationyear,status,gateway,gateway_environment,payment_transaction_id,subscription_transaction_id,timestamp";
	
	//these are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID)
	$default_columns = array(
		array("order", "id"),
		array("user", "ID"),
		array("user", "user_login"),
		array("user", "first_name"),
		array("user", "last_name"),
		array("user", "user_email"),
		array("order", "billing", "name"),
		array("order", "billing", "street"),
		array("order", "billing", "city"),
		array("order", "billing", "state"),
		array("order", "billing", "zip"),
		array("order", "billing", "country"),
		array("order", "billing", "phone"),
		array("order", "membership_id"),
		array("level", "name"),
		array("order", "subtotal"),
		array("order", "tax"),
		array("order", "couponamount"),
		array("order", "total"),
		array("order", "payment_type"),
		array("order", "cardtype"),
		array("order", "accountnumber"),
		array("order", "expirationmonth"),
		array("order", "expirationyear"),
		array("order", "status"),
		array("order", "gateway"),
		array("order", "gateway_environment"),
		array("order", "payment_transaction_id"),
		array("order", "subscription_transactiond_id")
	);
	
	//any extra columns
	$extra_columns = apply_filters("pmpro_orders_csv_extra_columns", array());
	if(!empty($extra_columns))
	{
		foreach($extra_columns as $heading => $callback)
		{
			$csvoutput .= "," . $heading;
		}
	}
	
	$csvoutput .= "\n";	
	
	if($order_ids)
	{
		foreach($order_ids as $order_id)
		{
			$order = new MemberOrder();
			$order->nogateway = true;
			$order->getMemberOrderByID($order_id);
			$user = get_userdata($order->user_id);
			$level = $order->getMembershipLevel();
			
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
					if(!empty($col[2]) && isset($$col[0]->$col[1]->$col[2]))
						$csvoutput .= pmpro_enclose($$col[0]->$col[1]->$col[2]);	//output the value				
					elseif(!empty($$col[0]->$col[1]))
						$csvoutput .= pmpro_enclose($$col[0]->$col[1]);	//output the value				
				}
			}
									
			//timestamp
			$csvoutput .= "," . pmpro_enclose(date(get_option("date_format"), $order->timestamp));
							
			//any extra columns			
			if(!empty($extra_columns))
			{
				foreach($extra_columns as $heading => $callback)
				{
					$csvoutput .= "," . pmpro_enclose(call_user_func($callback, $order));
				}
			}
				
			$csvoutput .= "\n";
											
		}
	}
		
	$size_in_bytes = strlen($csvoutput);
	header("Content-type: text/csv");
	//header("Content-type: application/vnd.ms-excel");
	if($s && $l)
		header("Content-Disposition: attachment; filename=orders" . intval($l) . "_level" . sanitize_file_name($s) . ".csv; size=$size_in_bytes");
	elseif($s)
		header("Content-Disposition: attachment; filename=orders_" . sanitize_file_name($s) . ".csv; size=$size_in_bytes");
	elseif($l)
		header("Content-Disposition: attachment; filename=orders_level" . intval($l) . ".csv; size=$size_in_bytes");
	else
		header("Content-Disposition: attachment; filename=orders.csv; size=$size_in_bytes");
	
	print $csvoutput;
	
	function pmpro_enclose($s)
	{	
		return "\"" . str_replace("\"", "\\\"", $s) . "\"";
	}
