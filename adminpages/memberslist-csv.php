<?php
	//set the number of users we'll load to protect from OOM errors
	$max_users_per_loop = apply_filters('pmpro_set_max_user_per_export_loop', 2000);

	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_memberslistcsv")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}

	global $wpdb;

	//get users (search input field)
	if(isset($_REQUEST['s']))
		$s = sanitize_text_field($_REQUEST['s']);
	else
		$s = "";

	// requested a level id
	if(isset($_REQUEST['l']))
		$l = sanitize_text_field($_REQUEST['l']);
	else
		$l = false;

	//some vars for the search
	if(!empty($_REQUEST['pn']))
		$pn = intval($_REQUEST['pn']);
	else
		$pn = 1;

	if(!empty($_REQUEST['limit']))
		$limit = intval($_REQUEST['limit']);
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

	$headers = array();
	$headers[] = "Content-type: text/csv";

	if($s && $l == "oldmembers")
		$headers[] = "Content-Disposition: attachment; filename=members_list_expired_" . sanitize_file_name($s) . ".csv";
	elseif($s && $l)
		$headers[] = "Content-Disposition: attachment; filename=members_list_" . intval($l) . "_level_" . sanitize_file_name($s) . ".csv";
	elseif($s)
		$headers[] = "Content-Disposition: attachment; filename=members_list_" . sanitize_file_name($s) . ".csv";
	elseif($l == "oldmembers")
		$headers[] = "Content-Disposition: attachment; filename=members_list_expired.csv";
	else
		$headers[] = "Content-Disposition: attachment; filename=members_list.csv";

	//set default CSV file headers, using comma as delimiter
	$csv_file_header = "id,username,firstname,lastname,email,billing firstname,billing lastname,address1,address2,city,state,zipcode,country,phone,membership,initial payment,fee,term,discount_code_id,discount_code,joined";

	if($l == "oldmembers")
		$csv_file_header .= ",ended";
	else
		$csv_file_header .= ",expires";

	$csv_file_header = apply_filters("pmpro_members_list_csv_heading", $csv_file_header);

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

	//set the preferred date format:
	$dateformat = apply_filters("pmpro_memberslist_csv_dateformat","Y-m-d");

	//any extra columns
	$extra_columns = apply_filters("pmpro_members_list_csv_extra_columns", array());
	if(!empty($extra_columns))
	{
		foreach($extra_columns as $heading => $callback)
		{
			$csv_file_header .= "," . $heading;
		}
	}

	$csv_file_header .= "\n";

	//generate SQL for list of users to process
	$sqlQuery = "
		SELECT
			u.ID,
		FROM $wpdb->users u ";

	if ($s)
		$sqlQuery .= "LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";

	$sqlQuery .= "LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id ";
	$sqlQuery .= "LEFT JOIN {$wpdb->pmpro_membership_levels} m ON mu.membership_id = m.id ";

	$former_members = in_array( $l, array( "oldmembers", "expired", "cancelled"));

	if($former_members)
		$sqlQuery .= " LEFT JOIN {$wpdb->pmpro_memberships_users} mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";

	$sqlQuery .= "WHERE mu.membership_id > 0 ";

	// looking for a specific user
	if($s)
		$sqlQuery .= " AND (u.user_login LIKE '%". esc_sql($s) ."%' OR u.user_email LIKE '%". esc_sql($s) ."%' OR um.meta_value LIKE '%". esc_sql($s) ."%') ";

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

	/* TODO: Shouldn't this be by ID only? Let user(s) re-sort in spreadsheet?
	if($former_members)
		$sqlQuery .= "ORDER BY enddate DESC ";
	else
		$sqlQuery .= "ORDER BY u.user_registered DESC ";
	*/
	// TO process based on limit value(s).
	$sqlQuery .= "ORDER BY u.ID ASC";

	if(!empty($limit))
		$sqlQuery .= "LIMIT {$start}, {$limit}";

	do_action('pmpro_before_members_list_csv_export', $theusers);

	// Generate a temporary file to store the data in.
	$tmp_dir = sys_get_temp_dir();
	$filename = tempnam( $tmp_dir, 'pmpro_ml_');

	// open in append mode
	$csv_fh = fopen($filename, 'a');

	//write the CSV header to the file
	fprintf($csv_fh, '%s', $csv_file_header );

	//get users
	$theusers = $wpdb->get_col($sqlQuery);

	$users_found = count($theusers);

	$i_start = 0;
	$i_limit = 0;
	$iterations = 1;

	$csvoutput = '';

	if($users_found > $max_users_per_loop)
	{
		$iterations = ceil($users_found / $max_users_per_loop);
		$i_limit = $max_users_per_loop;
	}

	//to manage memory footprint, we'll iterate through the membership list multiple times
	for ( $ic = 1 ; $ic <= $iterations ; $ic++ ) {

		// Create list of users to fetch from DB
		$csv_ulist = array_slice( $theusers, $i_start, (($i_limit * $ic)-1) );

		// get first and last user ID to use
		$first_uid = $csv_ulist[0];
		$last_uid = $csv_ulist[(count($csv_ulist) - 1)];

		// attempt to free memory
		unset ($csv_ulist);

		$userSql = $wpdb->prepare("
		SELECT
			u.ID,
			u.user_login,
			u.user_email,
			UNIX_TIMESTAMP(u.user_registered) as joindate,
			u.user_login,
			u.user_nicename,
			u.user_url,
			u.user_registered,
			u.user_status,
			u.display_name,
			mu.membership_id,
			mu.initial_payment,
			mu.billing_amount,
			mu.cycle_period,
			UNIX_TIMESTAMP(mu.enddate) as enddate,
			m.name as membership
		FROM {$wpdb->users} u
		LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
		LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id AND mu.status LIKE %s
		LEFT JOIN {$wpdb->pmpro_membership_levels} m ON mu.membership_id = m.id
		WHERE u.ID BETWEEN (%d, %d)
		ORDER BY mu.id DESC",
			($l == 'oldmembers' ? '%' : 'active'), // if requesting 'oldmembers', we use the wildcard value
			$first_uid,
			$last_uid
		);

		$usr_data = $wpdb->get_results($userSql);
		$userSql = null;

		foreach($usr_data as $user) {

			// Returns array of meta keys containing array(s) of metavalues.
			$um_values = get_user_meta($user->ID);

			//process usermeta
			foreach( $um_values as $key => $value )
				$user->metavalues->{$key} = $value[0];

			unset($um_values);

			//grab discount code info
			$disSql = $wpdb->prepare("
				SELECT
					c.id,
					c.code
				FROM {$wpdb->pmpro_discount_codes_uses} cu
				LEFT JOIN $wpdb->pmpro_discount_codes c ON cu.code_id = c.id WHERE cu.user_id = %d
				ORDER BY c.id DESC LIMIT 1", $user->ID);

			$discount_code = $wpdb->get_row($disSql);

			unset($disSql);

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
			$csvoutput .= "," . pmpro_enclose(date($dateformat, $user->joindate)) . ",";

			if($user->membership_id)
			{
				if($user->enddate)
					$csvoutput .= pmpro_enclose(apply_filters("pmpro_memberslist_expires_column", date($dateformat, $user->enddate), $user));
				else
					$csvoutput .= pmpro_enclose(apply_filters("pmpro_memberslist_expires_column", "Never", $user));
			}
			elseif($l == "oldmembers" && $user->enddate)
			{
				$csvoutput .= pmpro_enclose(date($dateformat, $user->enddate));
			}
			else
				$csvoutput .= "N/A";

			//any extra columns
			if(!empty($extra_columns))
			{
				foreach($extra_columns as $heading => $callback)
				{
					$csvoutput .= "," . pmpro_enclose(call_user_func($callback, $user, $heading));
				}
			}

			unset($discount_code);
			unset($user);

			$csvoutput .= "\n";
			fprintf($csv_fh, "%s", $csvoutput);

			//reset
			$csvoutput = '';
		}

		//free memory for user records
		unset($usr_data);

		// Increment starting position
		if(0 !== $i_limit)
		{
			$i_start += $i_limit;
			$i_limit += $i_limit;
		}
	}

	// free memory
	unset($theusers);

	//close the temp file
	fclose($csv_fh);

	//make sure we get the right file size
	clearstatcache( true, $file );

	//set the download size
	$headers[] = "Content/Length: " . filesize($file);

	// send the data to the remote browser
	pmpro_transmit_content($filename, $headers);

	//allow user to clean up after themselves
	do_action('pmpro_after_members_list_csv_export');
	
	function pmpro_enclose($s)
	{
		return "\"" . str_replace("\"", "\\\"", $s) . "\"";
	}

	// responsible for trasnmitting content of file to remote browser
	function pmpro_transmit_content( $file, $headers = array() ) {

		// Set the headers for transmission
		if (! empty($headers))
		{
			// Iterate through all headers
			foreach($headers as $header)
			{
				header($header);
			}

			// open and write the file to the remote location
			$fh = fopen( $file, 'rb' );
			fpassthru($fh);
			fclose($fh);
			wp_die();
		}
	}
