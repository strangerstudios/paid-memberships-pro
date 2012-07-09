<?php
	if(!function_exists("sornot"))
	{
		function sornot($t, $n)
		{
			if($n == 1)
				return $t;
			else
				return $t . "s";
		}
	}
	
	//setup wpdb for the tables we need
	function pmpro_setDBTables()
	{
		global $table_prefix, $wpdb;
		$wpdb->hide_errors();
		$wpdb->pmpro_membership_levels = $table_prefix . 'pmpro_membership_levels';
		$wpdb->pmpro_memberships_users = $table_prefix . 'pmpro_memberships_users';
		$wpdb->pmpro_memberships_categories = $table_prefix . 'pmpro_memberships_categories';
		$wpdb->pmpro_memberships_pages = $table_prefix . 'pmpro_memberships_pages';
		$wpdb->pmpro_membership_orders = $table_prefix . 'pmpro_membership_orders';
		$wpdb->pmpro_discount_codes = $wpdb->prefix . 'pmpro_discount_codes';
		$wpdb->pmpro_discount_codes_levels = $wpdb->prefix . 'pmpro_discount_codes_levels';
		$wpdb->pmpro_discount_codes_uses = $wpdb->prefix . 'pmpro_discount_codes_uses';
	}	
	pmpro_setDBTables();
	
	//from: http://stackoverflow.com/questions/5266945/wordpress-how-detect-if-current-page-is-the-login-page/5892694#5892694
	function pmpro_is_login_page() {
		return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
	}
	
	//thanks: http://wordpress.org/support/topic/is_plugin_active
	function pmpro_is_plugin_active( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
	}
	
	//scraping - override n if you have more than 1 group of matches and don't want the first group
	function pmpro_getMatches($p, $s, $firstvalue = FALSE, $n = 1)
	{
		$ok = preg_match_all($p, $s, $matches);		
		
		if(!$ok)
			return false;
		else
		{		
			if($firstvalue)
				return $matches[$n][0];
			else
				return $matches[$n];
		}
	}
	
	function pmpro_br2nl($text, $tags = "br")
	{
		if(!is_array($tags))
			$tags = explode(" ", $tags);

		foreach($tags as $tag)
		{
			$text = eregi_replace("<" . $tag . "[^>]*>", "\n", $text);
			$text = eregi_replace("</" . $tag . "[^>]*>", "\n", $text);
		}

		return($text);
	}
	
	function pmpro_getOption($s, $force = false)
	{
		if(isset($_REQUEST[$s]) && !$force)
			return $_REQUEST[$s];
		elseif(get_option("pmpro_" . $s))
			return get_option("pmpro_" . $s);
		else
			return "";
	}
	
	function pmpro_setOption($s, $v = NULL)
	{
		//no value is given, set v to the request var
		if($v === NULL && isset($_REQUEST[$s]))
			$v = $_REQUEST[$s];
				
		if(is_array($v))
			$v = implode(",", $v);
		
		return update_option("pmpro_" . $s, $v);	
	}		
	
	function pmpro_get_slug($post_id)
	{	
		global $pmpro_slugs, $wpdb;
		if(!$pmpro_slugs[$post_id])
			$pmpro_slugs[$post_id] = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE ID = '" . $post_id . "' LIMIT 1");
		
		return $pmpro_slugs[$post_id];			
	}
	
	function pmpro_url($page = NULL, $querystring = "", $scheme = NULL)
	{
		global $besecure;		
		$besecure = apply_filters("besecure", $besecure);
		
		if(!$scheme && $besecure)
			$scheme = "https";
		elseif(!$scheme)
			$scheme = "http";
				
		if(!$page)
			$page = "levels";
			
		global $pmpro_pages;
				
		//? vs &
		if(strpos(get_permalink($pmpro_pages[$page]), "?"))
			return home_url(str_replace(home_url(), "", get_permalink($pmpro_pages[$page])) . str_replace("?", "&", $querystring), $scheme);
		else
			return home_url(str_replace(home_url(), "", get_permalink($pmpro_pages[$page])) . $querystring, $scheme);
	}
		
	function pmpro_isLevelFree(&$level)
	{
		if($level->initial_payment <= 0 && $level->billing_amount <= 0 && $level->trial_amount <= 0)
			return true;
		else
			return false;
	}
	
	function pmpro_isLevelRecurring(&$level)
	{
		if($level->billing_amount > 0 || $level->trial_amount > 0)
			return true;
		else
			return false;
	}
	
	function pmpro_isLevelTrial(&$level)
	{
		if($level->trial_limit > 0)
		{			
			return true;
		}
		else
			return false;
	}
	
	function pmpro_isLevelExpiring(&$level)
	{
		if($level->expiration_number > 0)
			return true;
		else
			return false;
	}
	
	function pmpro_getLevelCost(&$level, $tags = true)
	{
		global $pmpro_currency_symbol;
		$r = '
		The price for membership is <strong>' . $pmpro_currency_symbol . number_format($level->initial_payment, 2) . '</strong> now';
		if($level->billing_amount != '0.00')
		{
			$r .= ' and then <strong>' . $pmpro_currency_symbol . $level->billing_amount;
			if($level->cycle_number == '1') 
			{ 
				$r .= ' per ';
			}
			elseif($level->billing_limit == 1)
			{ 
				$r .= ' after ' . $level->cycle_number . ' ';
			}
			else
			{ 
				$r .= ' every ' . $level->cycle_number . ' ';
			}

			$r .= sornot($level->cycle_period,$level->cycle_number);
			
			if($level->billing_limit > 1)
			{
				$r .= ' for ' . $level->billing_limit . ' more ' . sornot("payment",$level->billing_limit) . '.';
			}
			else
				$r .= '.';
			
			$r .= '</strong>';
		}	
		else
			$r .= '.';
		
		if($level->trial_limit)
		{ 
			$r .= ' After your initial payment, your first ';
			if($level->trial_amount == '0.00') 
			{ 				
				if($level->trial_limit == '1') 
				{ 										
					$r .= 'payment is Free.';
				} 
				else
				{ 					
					$r .= $level->trial_limit . ' payments are Free.';
				} 
			} 
			else
			{ 				
				$r .= $level->trial_limit.' ' .sornot("payment", $level->trial_limit) . ' will cost ' . $pmpro_currency_symbol . $level->trial_amount . '.';
			} 
		}  
		
		//taxes?
		$tax_state = pmpro_getOption("tax_state");
		$tax_rate = pmpro_getOption("tax_rate");
		
		if($tax_state && $tax_rate && !pmpro_isLevelFree($level))
		{
			$r .= " Customers in " . $tax_state . " will be charged " . round($tax_rate * 100, 2) . "% tax.";
		}
		
		if(!$tags)
			$r = strip_tags($r);
		
		$r = apply_filters("pmpro_level_cost_text", $r, $level);		
		return $r;
	}
	
	function pmpro_getLevelExpiration(&$level)
	{		
		if($level->expiration_number)
		{
			$expiration_text = "Membership expires after " . $level->expiration_number . " " . sornot(strtolower($level->expiration_period), $level->expiration_number) . ".";
		}
		else
			$expiration_text = "";
			
		$expiration_text = apply_filters("pmpro_level_expiration_text", $expiration_text, $level);
		return $expiration_text;
	}
	
	function pmpro_hideAds()
	{
		global $pmpro_display_ads;
		return !$pmpro_display_ads;
	}
	
	function pmpro_displayAds()
	{
		global $pmpro_display_ads;
		return $pmpro_display_ads;
	}
	
	function pmpro_next_payment($user_id = NULL)
	{
		global $wpdb, $current_user;
		if(!$user_id)
			$user_id = $current_user->ID;
			
		if(!$user_id)
			return false;
			
		//when were they last billed
		$lastdate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' ORDER BY timestamp DESC LIMIT 1");
				
		if($lastdate)
		{
			//next payment will be same day, following month
			$lastmonth = date("n", $lastdate);
			$lastday = date("j", $lastdate);
			$lastyear = date("Y", $lastdate);
						
			$nextmonth = ((int)$lastmonth) + 1;
			if($nextmonth == 13)
			{
				$nextmonth = 1;
				$nextyear = ((int)$lastyear) + 1;
			}
			else
				$nextyear = $lastyear;
			
			$daysinnextmonth = date("t", strtotime($nextyear . "-" . $nextmonth . "-1"));
			
			if($daysinnextmonth < $lastday)
			{
				$nextday = $daysinnextmonth;
			}
			else
				$nextday = $lastday;
				
			return strtotime($nextyear . "-" . $nextmonth . "-" . $nextday);
		}
		else
		{
			return false;
		}
		
	}
	
	if(!function_exists("last4"))
	{
		function last4($t)
		{
			return substr($t, strlen($t) - 4, 4);
		}	
	}

	if(!function_exists("hideCardNumber"))
	{
		function hideCardNumber($c, $dashes = true)
		{
			if($c)
			{
				if($dashes)
					return "XXXX-XXXX-XXXX-" . substr($c, strlen($c) - 4, 4);
				else
					return "XXXXXXXXXXXX" . substr($c, strlen($c) - 4, 4);
			}
			else
			{
				return "";	
			}
		}
	}
	
	if(!function_exists("cleanPhone"))
	{
		function cleanPhone($phone)
		{
			//if a + is passed, just pass it along
			if(strpos($phone, "+") !== false)
				return $phone;
			
			//clean the phone
			$phone = str_replace("-", "", $phone);
			$phone = str_replace(".", "", $phone);
			$phone = str_replace("(", "", $phone);
			$phone = str_replace(")", "", $phone);
			$phone = str_replace(" ", "", $phone);
		
			return $phone;
		}
	}

	if(!function_exists("formatPhone"))
	{
		function formatPhone($phone)
		{
			$phone = cleanPhone($phone);
			
			if(strlen($phone) == 11)
				return substr($phone, 0, 1) . " (" . substr($phone, 1, 3) . ") " . substr($phone, 4, 3) . "-" . substr($phone, 7, 4);
			elseif(strlen($phone) == 10)
				return "(" . substr($phone, 0, 3) . ") " . substr($phone, 3, 3) . "-" . substr($phone, 6, 4);
			elseif(strlen($phone) == 7)
				return substr($phone, 0, 3) . "-" . substr($phone, 3, 4);
			else
				return $phone;
		}
	}

	function pmpro_showRequiresMembershipMessage()
	{
		//get the correct message
		if(is_feed())
		{
			$content = pmpro_getOption("rsstext");
			$content = str_replace("!!levels!!", implode(", ", $post_membership_levels_names), $content);
		}
		elseif($current_user->ID)
		{		
			//not a member
			$content = pmpro_getOption("nonmembertext");
			$content = str_replace("!!levels!!", implode(", ", $post_membership_levels_names), $content);
		}
		else
		{
			//not logged in!
			$content = pmpro_getOption("notloggedintext");
			$content = str_replace("!!levels!!", implode(", ", $post_membership_levels_names), $content);
		}	
	}

	/* pmpro_hasMembershipLevel() checks if the passed user is a member of the passed level	
	 *
	 * $level may either be the ID or name of the desired membership_level. (or an array of such)
	 * If $user_id is omitted, the value will be retrieved from $current_user.
	 *
	 * Return values:
	 *		Success returns boolean true.
	 *		Failure returns a string containing the error message.
	 */
	function pmpro_hasMembershipLevel($levels = NULL, $user_id = NULL)
	{
		global $current_user, $all_membership_levels, $wpdb;
		
		$r = false;
		$non_member_check = false;
		
		if($user_id)
		{
			//get the membership level from the global array
			$membership_level = $all_membership_levels[$user_id];
			if(!$membership_level)
			{
				//no level, check the db and add to array
				$membership_level = $wpdb->get_row("SELECT l.id AS ID, l.*
													FROM {$wpdb->pmpro_membership_levels} AS l
													JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
													WHERE mu.user_id = $user_id
													LIMIT 1");
				if($membership_level)
					$all_membership_levels[$user_id] = $membership_level;
				else
					$all_memberships_levels[$user_id] = -1;	//not a member of anything
			}
		}
		else
		{
			//no user_id passed, check the current user
			$user_id = $current_user->ID;
			$membership_level = $current_user->membership_level;
		}								
		
		//if 0 was passed, return true if they have no level and false if they have any				
		if(is_array($levels))
		{			
			if($levels[0] === "0" || $levels[0] === 0)
			{
				$non_member_check = true;
				if(!empty($membership_level->ID))
					$r = false;
				else
					$r = true;
			}
		}
		else
		{
			if($levels === "0" || $levels === 0)
			{				
				$non_member_check = true;
				if(!empty($membership_level->ID))
					$r = false;
				else
					$r = true;
			}
		}		
				
		if(!$non_member_check)
		{
			//no levels?
			if($membership_level == "-1" || empty($membership_level))
				$r = false;		
						
			//if no level var was passed, we're just checking if they have any level
			if(!$levels)
			{
				if(!empty($membership_level->ID))
					$r = true;
				else
					$r = false;
			}		
								
			if(!is_array($levels))
				$levels = array($levels);
			
			//okay, so something to check let's set the levels
			if(empty($membership_level))
			{
				//non member check
				foreach($levels as $level)
				{
					if(is_numeric($level) && (int)$level < 0)
						$r = true;	//they don't have a membership level so they don't have this one
				}
			}
			else
			{						
				//check levels against the user's level
				foreach($levels as $level)
				{
					if(is_numeric($level) && (int)$level < 0)
					{
						//passing -1 will return true if the user does not have membership level #1
						$abs_level = abs($level);						
						if($abs_level != $membership_level->ID)
							$r = true;
					}
					elseif($level == $membership_level->ID || $level == $membership_level->name)
					{				
						//the user has this level
						$r = true;
					}
				}
			}
		}
		
		$r = apply_filters("pmpro_has_membership_level", $r, $user_id, $levels);		
		
		return $r;
	}
	
	/* pmpro_changeMembershipLevel() creatues or updates the membership level of the given user to the given level.
	 *
	 * $level may either be the ID or name of the desired membership_level.
	 * If $user_id is omitted, the value will be retrieved from $current_user.
	 *
	 * Return values:
	 *		Success returns boolean true.
	 *		Failure returns boolean false.
	 */
	function pmpro_changeMembershipLevel($level, $user_id = NULL)
	{								
		global $wpdb;
		global $current_user, $pmpro_error;

		if(!$user_id)
			$user_id = $current_user->ID;
			
		if(!$user_id)
		{
			$pmpro_error = "User ID not found.";
			return false;
		}
					
		//was a name passed? (Todo: make sure level names have at least one non-numeric character.		
		if($level !== "" && $level !== false && $level !== NULL && !is_numeric($level))
		{
			$level = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_levels WHERE name = '" . $wpdb->escape($level) . "' LIMIT 1");			
			if(!$level)	
			{
				$pmpro_error = "Membership level not found.";
				return false;
			}
		}
				
		//are they even changing?
		$old_level = $wpdb->get_row("SELECT * FROM  $wpdb->pmpro_memberships_users WHERE user_id = '" . $user_id . "'");
		if(empty($old_level->membership_id) && empty($level))
			return false;	//not changing
		elseif(!empty($old_level->membership_id) && $old_level->membership_id == $level)
			return false;	//not changing
		
		//are they paying? may need to cancel their old membership				
		if(!pmpro_isLevelFree($old_level))
			$paying = true;
		else
			$paying = false;
			
		if($paying)
		{					
			//get last order
			$order = new MemberOrder();
			$order->getLastMemberOrder($user_id);						
						
			if($order->cancel())
			{
				//we're good					
			}
			else
			{				
				//uh oh										
				$pmpro_error = "There was an error canceling your membership: " . $order->error;				
				return false;
			}							
		}
			
		//adding, changing, or deleting
		if($level)
		{						
			//adding, changing
			$sql = "REPLACE INTO $wpdb->pmpro_memberships_users (`membership_id`,`user_id`) VALUES ('" . $level . "','" . $user_id . "')";
		}
		else
		{
			//false or null or 0 was passed, so we're deleting removing
			$sql = "DELETE FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $wpdb->escape($user_id) . "' LIMIT 1";			
		}
		
		//run the query, return		
		if(!$wpdb->query($sql))
		{
			if(mysql_errno())
				$pmpro_error = "Error: " . mysql_error();			
			return false;
		}
		else 
		{
			pmpro_set_current_user();
			do_action("pmpro_after_change_membership_level", $level, $user_id);	//$level is the $level_id here
			return true;
		}
	}

	/* pmpro_toggleMembershipCategory() creates or deletes a linking entry between the membership level and post category tables.
	 *
	 * $level may either be the ID or name of the desired membership_level.
	 * $category must be a valid post category ID.
	 *
	 * Return values:
	 *		Success returns boolean true.
	 *		Failure returns a string containing the error message.
	 */
	function pmpro_toggleMembershipCategory( $level, $category, $value )
	{
		global $wpdb;
		$category = intval($category);

			if ( ($level = intval($level)) <= 0 )
			{
				$safe = addslashes($level);
				if ( ($level = intval($wpdb->get_var("SELECT id FROM {$wpdb->pmpro_membership_levels} WHERE name = '$safe' LIMIT 1"))) <= 0 )
				{
					return "Membership level not found.";
				}
			}

		if ( $value )
		{
		  $sql = "REPLACE INTO {$wpdb->pmpro_memberships_categories} (`membership_id`,`category_id`) VALUES ('$level','$category')";
		  $wpdb->query($sql);		
		  if(mysql_errno()) return mysql_error();
		}
		else
		{
		  $sql = "DELETE FROM {$wpdb->pmpro_memberships_categories} WHERE `membership_id` = '$level' AND `category_id` = '$category' LIMIT 1";
		  $wpdb->query($sql);		
		  if(mysql_errno()) return mysql_error();
		}

		return true;
	}

	/* pmpro_updateMembershipCategories() ensures that all those and only those categories given
	* are associated with the given membership level.
	*
	* $level is a valid membership level ID or name
	* $categories is an array of post category IDs
	*
	* Return values:
	*		Success returns boolean true.
	*		Failure returns a string containing the error message.
	*/
	function pmpro_updateMembershipCategories($level, $categories) 
	{
		global $wpdb;
		
		if(!is_numeric($level))
		{
			$level = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_levels WHERE name = '" . $wpdb->escape($level) . "' LIMIT 1");
			if(empty($level))
			{
				return "Membership level not found.";
			}
		}		

		// remove all existing links...
		$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_categories WHERE `membership_id` = '" . $wpdb->escape($level) . "'";
		$wpdb->query($sqlQuery);		
		if(mysql_errno()) return mysql_error();

		// add the given links [back?] in...
		foreach($categories as $cat)
		{
			if(is_string($r = pmpro_toggleMembershipCategory( $level, $cat, true)))
			{
				//uh oh, error
				return $r;			
			}
		}

		//all good
		return true;
	}
  
	function pmpro_isAdmin($user_id = NULL)
	{
		global $current_user, $wpdb;
		if(!$user_id)
			$user_id = $current_user->ID;
		
		if(!$user_id)
			return false;
					
		$admincap = user_can($user_id, "manage_options");
		if($admincap)
			return true;
		else
			return false;
	}
	
	function pmpro_replaceUserMeta($user_id, $meta_keys, $meta_values, $prev_values = NULL)
	{
		//expects all arrays for last 3 params or all strings
		if(!is_array($meta_keys))
		{
			$meta_keys = array($meta_keys);
			$meta_values = array($meta_values);
			$prev_values = array($prev_values);
		}
		
		for($i = 0; $i < count($meta_values); $i++)
		{
			if($prev_values[$i])
			{
				update_user_meta($user_id, $meta_keys[$i], $meta_values[$i], $prev_values[$i]);				
			}
			else
			{
				$old_value = get_user_meta($user_id, $meta_keys[$i], true);
				if($old_value)
				{
					update_user_meta($user_id, $meta_keys[$i], $meta_values[$i], $old_value);					
				}
				else
				{
					update_user_meta($user_id, $meta_keys[$i], $meta_values[$i]);	
				}
			}
		}
		
		return $i;
	}
	
	function pmpro_getMetavalues($query)
	{
		global $wpdb;
		
		$results = $wpdb->get_results($query);
		foreach($results as $result)
		{
			$r->{$result->key} = $result->value;
		}
		
		return $r;
	}
	
	//function to return the pagination string
	function pmpro_getPaginationString($page = 1, $totalitems, $limit = 15, $adjacents = 1, $targetpage = "/", $pagestring = "&pn=")
	{		
		//defaults
		if(!$adjacents) $adjacents = 1;
		if(!$limit) $limit = 15;
		if(!$page) $page = 1;
		if(!$targetpage) $targetpage = "/";
		
		//other vars
		$prev = $page - 1;									//previous page is page - 1
		$next = $page + 1;									//next page is page + 1
		$lastpage = ceil($totalitems / $limit);				//lastpage is = total items / items per page, rounded up.
		$lpm1 = $lastpage - 1;								//last page minus 1
		
		/* 
			Now we apply our rules and draw the pagination object. 
			We're actually saving the code to a variable in case we want to draw it more than once.
		*/
		$pagination = "";
		if($lastpage > 1)
		{	
			$pagination .= "<div class=\"pmpro_pagination\"";
			if(!empty($margin) || !empty($padding))
			{
				$pagination .= " style=\"";
				if($margin)
					$pagination .= "margin: $margin;";
				if($padding)
					$pagination .= "padding: $padding;";
				$pagination .= "\"";
			}
			$pagination .= ">";

			//previous button
			if ($page > 1) 
				$pagination .= "<a href=\"$targetpage$pagestring$prev\">&laquo; prev</a>";
			else
				$pagination .= "<span class=\"disabled\">&laquo; prev</span>";	
			
			//pages	
			if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
			{	
				for ($counter = 1; $counter <= $lastpage; $counter++)
				{
					if ($counter == $page)
						$pagination .= "<span class=\"current\">$counter</span>";
					else
						$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";					
				}
			}
			elseif($lastpage >= 7 + ($adjacents * 2))	//enough pages to hide some
			{
				//close to beginning; only hide later pages
				if($page < 1 + ($adjacents * 3))		
				{
					for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
					{
						if ($counter == $page)
							$pagination .= "<span class=\"current\">$counter</span>";
						else
							$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";					
					}
					$pagination .= "...";
					$pagination .= "<a href=\"" . $targetpage . $pagestring . $lpm1 . "\">$lpm1</a>";
					$pagination .= "<a href=\"" . $targetpage . $pagestring . $lastpage . "\">$lastpage</a>";		
				}
				//in middle; hide some front and some back
				elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
				{
					$pagination .= "<a href=\"" . $targetpage . $pagestring . "1\">1</a>";
					$pagination .= "<a href=\"" . $targetpage . $pagestring . "2\">2</a>";
					$pagination .= "...";
					for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
					{
						if ($counter == $page)
							$pagination .= "<span class=\"current\">$counter</span>";
						else
							$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";					
					}
					$pagination .= "...";
					$pagination .= "<a href=\"" . $targetpage . $pagestring . $lpm1 . "\">$lpm1</a>";
					$pagination .= "<a href=\"" . $targetpage . $pagestring . $lastpage . "\">$lastpage</a>";		
				}
				//close to end; only hide early pages
				else
				{
					$pagination .= "<a href=\"" . $targetpage . $pagestring . "1\">1</a>";
					$pagination .= "<a href=\"" . $targetpage . $pagestring . "2\">2</a>";
					$pagination .= "...";
					for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++)
					{
						if ($counter == $page)
							$pagination .= "<span class=\"current\">$counter</span>";
						else
							$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";					
					}
				}
			}
			
			//next button
			if ($page < $counter - 1) 
				$pagination .= "<a href=\"" . $targetpage . $pagestring . $next . "\">next &raquo;</a>";
			else
				$pagination .= "<span class=\"disabled\">next &raquo;</span>";
			$pagination .= "</div>\n";
		}
		
		return $pagination;

	}
	
	function pmpro_calculateInitialPaymentRevenue($s = NULL, $l = NULL)
	{
		global $wpdb;
	
		//if we're limiting users by search
		if($s || $l)
		{
			$user_ids_query = "SELECT ID FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um  ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id WHERE 1 ";
			if($s)
				$user_ids_query .= "AND (u.user_login LIKE '%$s%' OR u.user_email LIKE '%$s%' OR um.meta_value LIKE '%$s%') ";
			if($l)
				$user_ids_query .= "AND mu.membership_id = '$l' ";
		}
		
		//query to sum initial payments
		$sqlQuery = "SELECT SUM(initial_payment) FROM $wpdb->pmpro_memberships_users WHERE 1 ";
		if(!empty($user_ids_query))
			$sqlQuery .= "AND user_id IN(" . $user_ids_query . ") ";
		
		$total = $wpdb->get_var($sqlQuery);
				
		return (double)$total;
	}
	
	function pmpro_calculateRecurringRevenue($s, $l)
	{
		global $wpdb;
		
		//if we're limiting users by search
		if($s || $l)
		{
			$user_ids_query = "AND user_id IN(SELECT ID FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um  ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id WHERE 1 ";
			if($s)
				$user_ids_query .= "AND (u.user_login LIKE '%$s%' OR u.user_email LIKE '%$s%' OR um.meta_value LIKE '%$s%') ";
			if($l)
				$user_ids_query .= "AND mu.membership_id = '$l' ";
			$user_ids_query .= ")";
		}
		else
			$user_ids_query = "";
		
		//4 queries to get annual earnings for each cycle period. currently ignoring trial periods and billing limits.
		$sqlQuery = "
			SELECT SUM((12/cycle_number)*billing_amount) FROM $wpdb->pmpro_memberships_users WHERE cycle_period = 'Month' AND cycle_number <> 12 $user_ids_query
				UNION
			SELECT SUM((365/cycle_number)*billing_amount) FROM $wpdb->pmpro_memberships_users WHERE cycle_period = 'Day' AND cycle_number <> 365 $user_ids_query
				UNION
			SELECT SUM((52/cycle_number)*billing_amount) FROM $wpdb->pmpro_memberships_users WHERE cycle_period = 'Week' AND cycle_number <> 52 $user_ids_query
				UNION
			SELECT SUM(billing_amount) FROM $wpdb->pmpro_memberships_users WHERE cycle_period = 'Year' $user_ids_query
		";		
		$annual_revenues = $wpdb->get_col($sqlQuery);
				
		$total = 0;
		foreach($annual_revenues as $r)
		{
			$total += $r;
		}
		
		return $total;
	}
	
	function pmpro_generateUsername($firstname = "", $lastname = "", $email = "")
	{
		global $wpdb;
		
		//try first initial + last name, firstname, lastname
		$firstname = preg_replace("/[^A-Za-z]/", "", $firstname);
		$lastname = preg_replace("/[^A-Za-z]/", "", $lastname);
		if($firstname && $lastname)
		{
			$username = substr($firstname, 0, 1) . $lastname;
		}
		elseif($firstname)
		{
			$username = $firstname;
		}
		elseif($lastname)
		{
			$username = $lastname;
		}
		
		//is it taken?
		$taken = $wpdb->get_var("SELECT user_login FROM $wpdb->users WHERE user_login = '" . $username . "' LIMIT 1");
		
		if(!$taken)
			return $username;
		
		//try the beginning of the email address
		$emailparts = explode("@", "email");
		if(is_array($emailparts))
			$email = preg_replace("/[^A-Za-z]/", "", $emailparts[0]);
		
		if($email)
		{
			$username = $email;
		}
				
		//is this taken? if not, add numbers until it works
		$taken = true;
		$count = 0;
		while($taken)
		{	
			//add a # to the end
			if($count)
			{
				$username = preg_replace("/[0-9]/", "", $username) . $count;
			}
			
			//taken?
			$taken = $wpdb->get_var("SELECT user_login FROM $wpdb->users WHERE user_login = '" . $username . "' LIMIT 1");		
			
			//increment the number
			$count++;
		}
		
		//must have a good username now
		return $username;
	}
	
	//get a new random code for discount codes
	function pmpro_getDiscountCode()
	{
		global $wpdb;
		
		while(empty($code))
		{
			$scramble = md5(AUTH_KEY . time() . SECURE_AUTH_KEY);			
			$code = substr($scramble, 0, 10);
			$check = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE code = '$code' LIMIT 1");				
			if($check || is_numeric($code))
				$code = NULL;
		}
		
		return strtoupper($code);
	}
	
	//is a discount code valid
	function pmpro_checkDiscountCode($code, $level_id = NULL, $return_errors = false)
	{
		global $wpdb;
		
		//no code, no code
		if(empty($code))
		{
			if($return_errors)
				return array(false, "No code was given to check.");
			else
				return false;
		}
			
		//get code from db
		$dbcode = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes WHERE code ='" . $code . "' LIMIT 1");
				
		//did we find it?
		if(empty($dbcode->id))
		{
			if($return_errors)
				return array(false, "The code could not be found.");
			else
				return false;
		}
	
		//fix the date timestamps
		$dbcode->starts = strtotime(date("m/d/Y", $dbcode->starts));
		$dbcode->expires = strtotime(date("m/d/Y", $dbcode->expires));		
	
		//today
		$today = strtotime(date("m/d/Y 00:00:00"));		
	
		//has this code started yet?
		if(!empty($dbcode->starts) && $dbcode->starts > $today)
		{
			if($return_errors)
				return array(false, "This discount code goes into effect on " . date("m/d/Y", $dbcode->starts) . ".");
			else
				return false;
		}
		
		//has this code expired?
		if(!empty($dbcode->expires) && $dbcode->expires < $today)
		{
			if($return_errors)
				return array(false, "This discount code expired on " . date("m/d/Y", $dbcode->expires) . ".");
			else
				return false;
		}
		
		//have we run out of uses?
		if($dbcode->uses > 0)
		{
			$used = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = '" . $dbcode->id . "'");
			if($used >= $dbcode->uses)
			{
				if($return_errors)
					return array(false, "This discount code is no longer valid.");
				else
					return false;
			}
		}
		
		//if a level was passed check if this code applies		
		$pmpro_check_discount_code_levels = apply_filters("pmpro_check_discount_code_levels", true, $dbcode->id);		
		if(!empty($level_id) && $pmpro_check_discount_code_levels)
		{
			$code_level = $wpdb->get_row("SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id WHERE cl.code_id = '" . $dbcode->id . "' AND cl.level_id = '" . $level_id . "' LIMIT 1");
			
			if(empty($code_level))
			{
				if(!empty($return_errors))
					return array(false, "This code does not apply to this membership level.");
				else
					return false;
			}
		}
		
		//guess we're all good		
		if(!empty($return_errors))
			return array(true, "This discount code is okay.");
		else
			return true;
	}
	
	function pmpro_no_quotes($s, $quotes = array("'", '"'))
	{
		return str_replace($quotes, "", $s);
	}
	
	//from: http://www.php.net/manual/en/function.implode.php#86845
	function pmpro_implodeToEnglish($array) 
	{ 
		// sanity check 
		if (!$array || !count ($array)) 
			return ''; 

		// get last element    
		$last = array_pop ($array); 

		// if it was the only element - return it 
		if (!count ($array)) 
			return $last;    

		return implode (', ', $array).' and '.$last; 
	} 
	
	//from yoast wordpress seo
	function pmpro_text_limit( $text, $limit, $finish = '&hellip;') 
	{
		if( strlen( $text ) > $limit ) {
			$text = substr( $text, 0, $limit );
			$text = substr( $text, 0, - ( strlen( strrchr( $text,' ') ) ) );
			$text .= $finish;
		}
		return $text;
	}
	
	function pmpro_getMembershipLevelForUser($user_id = NULL)
	{
		if(empty($user_id))		
		{
			global $current_user;
			$user_id = $current_user->ID;
		}
		
		if(empty($user_id))
			return false;
		
		global $wpdb;		
		return $wpdb->get_row("SELECT l.id AS ID, l.id as id, l.name, l.description, mu.initial_payment, mu.billing_amount, mu.cycle_number, mu.cycle_period, mu.billing_limit, mu.trial_amount, mu.trial_limit, mu.code_id as code_id, UNIX_TIMESTAMP(startdate) as startdate, UNIX_TIMESTAMP(enddate) as enddate
															FROM {$wpdb->pmpro_membership_levels} AS l
															JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
															WHERE mu.user_id = $user_id
															LIMIT 1");
	}
	
	function pmpro_getLevel($level_id)
	{
		global $pmpro_levels;
		if(isset($pmpro_levels[$level_id]))
			return $pmpro_levels[$level_id];
		else
		{
			global $wpdb;
			$pmpro_levels[$level_id] = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . $level_id . "' LIMIT 1");
			return $pmpro_levels[$level_id];
		}
	}
	
	/*
		Function to populate pmpro_levels with all levels. We query the DB every time just to be sure we have the latest. 
		This should be called if you want to be sure you get all levels as $pmpro_levels may only have a subset of levels.
	*/
	function pmpro_getAllLevels($include_hidden = false)
	{
		global $pmpro_levels, $wpdb;
		
		//build query
		$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
		if(!$include_hidden)
			$sqlQuery .= " WHERE allow_signups = 1";
			
		//get levels from the DB
		$raw_levels = $wpdb->get_results($sqlQuery);
		
		//lets put them into an array where the key is the id of the level
		$pmpro_levels = array();
		foreach($raw_levels as $raw_level)
		{
			$pmpro_levels[$raw_level->id] = $raw_level;
		}
				
		return $pmpro_levels;
	}
	
	function pmpro_getCheckoutButton($level_id, $button_text = NULL, $classes = NULL)
	{
		if(empty($button_text))
			$button_text = "Sign Up for !!name!! Now";
			
		if(empty($classes))
			$classes = "btn btn-primary";
			
		if(empty($level_id))
			$r = "Please specify a level id.";
		else
		{
			//get level
			$level = pmpro_getLevel($level_id);
			
			//replace vars
			$replacements = array(
				"!!id!!" => $level->id,
				"!!name!!" => $level->name,
				"!!description!!" => $level->description,
				"!!confirmation!!" => $level->confirmation,
				"!!initial_payment!!" => $level->initial_payment,
				"!!billing_amount!!" => $level->billing_amount,
				"!!cycle_number!!" => $level->cycle_number,
				"!!cycle_period!!" => $level->cycle_period,
				"!!billing_limit!!" => $level->billing_limit,
				"!!trial_amount!!" => $level->trial_amount,
				"!!trial_limit!!" => $level->trial_limit,
				"!!expiration_number!!" => $level->expiration_number,
				"!!expiration_period!!" => $level->expiration_period
			);
			$button_text = str_replace(array_keys($replacements), $replacements, $button_text);			
			
			//button text
			$r = "<a href=\"" . pmpro_url("checkout", "?level=" . $level_id) . "\" class=\"" . $classes . "\">" . $button_text . "</a>";
		}
		return $r;
	}
	
	/**
	 * Get the "domain" from a URL. By domain, we mean the host name, minus any subdomains. So just the domain and TLD.	 
	 *
	 * @param string $url The URL to parse. (generally pass site_url() in WP)
	 * @return string The domain.
	 */
	function pmpro_getDomainFromURL($url = NULL)
	{
		$domainparts = parse_url($url);
		$domainparts = explode(".", $domainparts['host']);
		if(count($domainparts) > 1)
		{
			//check for ips
			$isip = true;
			foreach($domainparts as $part)
			{
				if(!is_numeric($part))
				{
					$isip = false;
					break;
				}
			}
			
			if($isip)
			{
				//ip, e.g. 127.1.1.1
				$domain = implode(".", $domainparts);
			}
			else
			{			
				//www.something.com, etc.
				$domain = $domainparts[count($domainparts)-2] . "." . $domainparts[count($domainparts)-1];
			}
		}
		else
		{
			//localhost or another single word domain
			$domain = $domainparts[0];	
		}
		
		return $domain;
	}
?>