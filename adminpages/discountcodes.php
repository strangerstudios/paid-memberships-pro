<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_discountcodes")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}	
	
	//vars
	global $wpdb, $pmpro_currency_symbol;
	
	if(isset($_REQUEST['edit']))	
		$edit = $_REQUEST['edit'];
	else
		$edit = false;
	
	if(isset($_REQUEST['delete']))
		$delete = $_REQUEST['delete'];
	else
		$delete = false;
		
	if(isset($_REQUEST['saveid']))
		$saveid = $_POST['saveid'];
	else
		$saveid = false;			
	
	if($saveid)
	{
		//get vars
		$code = $_POST['code'];
		$starts_month = $_POST['starts_month'];
		$starts_day = $_POST['starts_day'];
		$starts_year = $_POST['starts_year'];
		$expires_month = $_POST['expires_month'];
		$expires_day = $_POST['expires_day'];
		$expires_year = $_POST['expires_year'];
		$uses = $_POST['uses'];
		
		//fix up dates		
		$starts = date("Y-m-d", strtotime($starts_month . "/" . $starts_day . "/" . $starts_year));
		$expires = date("Y-m-d", strtotime($expires_month . "/" . $expires_day . "/" . $expires_year));
		
		//updating or new?
		if($saveid > 0)
		{
			$sqlQuery = "UPDATE $wpdb->pmpro_discount_codes SET code = '" . esc_sql($code) . "', starts = '" . $starts . "', expires = '" . $expires . "', uses = '" . intval($uses) . "' WHERE id = '" . $saveid . "' LIMIT 1";
			if($wpdb->query($sqlQuery) !== false)
			{
				$pmpro_msg = __("Discount code updated successfully.", "pmpro");
				$pmpro_msgt = "success";
				$saved = true;
				$edit = $saveid;								
			}
			else
			{
				$pmpro_msg = __("Error updating discount code. That code may already be in use.", "pmpro");
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes (code, starts, expires, uses) VALUES('" . esc_sql($code) . "', '" . $starts . "', '" . $expires . "', '" . intval($uses) . "')";
			if($wpdb->query($sqlQuery) !== false)
			{
				$pmpro_msg = __("Discount code added successfully.", "pmpro");
				$pmpro_msgt = "success";
				$saved = true;
				$edit = $wpdb->insert_id;
			}
			else
			{				
				$pmpro_msg = __("Error adding discount code. That code may already be in use.", "pmpro") . $wpdb->last_error;				
				$pmpro_msgt = "error";
			}
		}
		
		//now add the membership level rows		
		if($saved && $edit > 0)
		{
			//get the submitted values
			$all_levels_a = $_REQUEST['all_levels'];
			if(!empty($_REQUEST['levels']))
				$levels_a = $_REQUEST['levels'];
			else
				$levels_a = array();
			$initial_payment_a = $_REQUEST['initial_payment'];
			if(!empty($_REQUEST['recurring']))
				$recurring_a = $_REQUEST['recurring'];
			$billing_amount_a = $_REQUEST['billing_amount'];
			$cycle_number_a = $_REQUEST['cycle_number'];
			$cycle_period_a = $_REQUEST['cycle_period'];
			$billing_limit_a = $_REQUEST['billing_limit'];
			if(!empty($_REQUEST['custom_trial']))
				$custom_trial_a = $_REQUEST['custom_trial'];
			$trial_amount_a = $_REQUEST['trial_amount'];
			$trial_limit_a = $_REQUEST['trial_limit'];						
			if(!empty($_REQUEST['expiration']))
				$expiration_a = $_REQUEST['expiration'];
			$expiration_number_a = $_REQUEST['expiration_number'];
			$expiration_period_a = $_REQUEST['expiration_period'];
			
			//clear the old rows
			$sqlQuery = "DELETE FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . $edit . "'";
			$wpdb->query($sqlQuery);
			
			//add a row for each checked level
			if(!empty($levels_a))
			{
				foreach($levels_a as $level_id)
				{
					//get the values ready
					$n = array_search($level_id, $all_levels_a); 	//this is the key location of this level's values
					$initial_payment = $initial_payment_a[$n];
					
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
						$billing_amount = $billing_amount_a[$n];
						$cycle_number = $cycle_number_a[$n];
						$cycle_period = $cycle_period_a[$n];
						$billing_limit = $billing_limit_a[$n];
						
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
							$trial_amount = $trial_amount_a[$n];
							$trial_limit = $trial_limit_a[$n];
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
						$expiration_number = $expiration_number_a[$n];
						$expiration_period = $expiration_period_a[$n];
					}
					else
					{
						$expiration_number = '';
						$expiration_period = 'Month';
					}
					
					//okay, do the insert
					$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes_levels (code_id, level_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, expiration_number, expiration_period) VALUES('" . esc_sql($edit) . "', '" . esc_sql($level_id) . "', '" . (double)esc_sql($initial_payment) . "', '" . (double)esc_sql($billing_amount) . "', '" . intval(esc_sql($cycle_number)) . "', '" . esc_sql($cycle_period) . "', '" . intval(esc_sql($billing_limit)) . "', '" . (double)esc_sql($trial_amount) . "', '" . intval(esc_sql($trial_limit)) . "', '" . intval(esc_sql($expiration_number)) . "', '" . esc_sql($expiration_period) . "')";
										
					if($wpdb->query($sqlQuery) !== false)
					{
						//okay												
						do_action("pmpro_save_discount_code_level", $edit, $level_id);						
					}
					else
					{					
						$level_errors[] = sprintf(__("Error saving values for the %s level.", "pmpro"), $wpdb->get_var("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '" . $level_id . "' LIMIT 1"));
					}
				}
			}
			
			//errors?
			if(!empty($level_errors))
			{				
				$pmpro_msg = __("There were errors updating the level values: ", "pmpro") . implode(" ", $level_errors);
				$pmpro_msgt = "error";				
			}
			else
			{
				//all good. set edit = NULL so we go back to the overview page
				$edit = NULL;
				
				do_action("pmpro_save_discount_code", $saveid);
			}
		}
	}
	
	//are we deleting?
	if(!empty($delete))
	{
		//is this a code?
		$code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $delete . "' LIMIT 1");
		if(!empty($code))
		{
			//delete the code levels
			$r1 = $wpdb->query("DELETE FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . $delete . "'");
			
			if($r1 !== false)
			{
				//delete the code
				$r2 = $wpdb->query("DELETE FROM $wpdb->pmpro_discount_codes WHERE id = '" . $delete . "' LIMIT 1");
				
				if($r2 !== false)
				{
					$pmpro_msg = sprintf(__("Code %s deleted successfully.", "pmpro"), $code);
					$pmpro_msgt = "success";
				}
				else
				{
					$pmpro_msg = __("Error deleting discount code. The code was only partially deleted. Please try again.", "pmpro");
					$pmpro_msgt = "error";
				}
			}
			else
			{
				$pmpro_msg = __("Error deleting code. Please try again.", "pmpro");
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$pmpro_msg = __("Code not found.", "pmpro");
			$pmpro_msgt = "error";
		}
	}
	
	require_once(dirname(__FILE__) . "/admin_header.php");	
?>
	
	<?php if($edit) { ?>
		
		<h2>
			<?php
				if($edit > 0)
					echo __("Edit Discount Code", "pmpro");
				else
					echo __("Add New Discount Code", "pmpro");
			?>
		</h2>
		
		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
		<?php } ?>
		
		<div>
			<?php
				// get the code...
				if($edit > 0)
				{
					$code = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes WHERE id = '" . $edit . "' LIMIT 1", OBJECT);
					$uses = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = '" . $code->id . "'");
					$levels = $wpdb->get_results("SELECT l.id, l.name, cl.initial_payment, cl.billing_amount, cl.cycle_number, cl.cycle_period, cl.billing_limit, cl.trial_amount, cl.trial_limit FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_discount_codes_levels cl ON l.id = cl.level_id WHERE cl.code_id = '" . $code->code . "'");
					$temp_id = $code->id;
				}
				elseif(!empty($copy) && $copy > 0)		
				{	
					$code = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes WHERE id = '" . $copy . "' LIMIT 1", OBJECT);					
					$temp_id = $level->id;
					$level->id = NULL;
				}

				// didn't find a discount code, let's add a new one...
				if(empty($code->id)) $edit = -1;

				//defaults for new codes
				if($edit == -1)
				{
					$code = new stdClass();
					$code->code = pmpro_getDiscountCode();
				}								
			?>
			<form action="" method="post">
				<input name="saveid" type="hidden" value="<?php echo $edit?>" />
				<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e('ID', 'pmpro');?>:</label></th>
                        <td class="pmpro_lite"><?php if(!empty($code->id)) echo $code->id; else echo __("This will be generated when you save.", "pmpro");?></td>
                    </tr>								                
                    
                    <tr>
                        <th scope="row" valign="top"><label for="code"><?php _e('Code', 'pmpro');?>:</label></th>
                        <td><input name="code" type="text" size="20" value="<?php echo str_replace("\"", "&quot;", stripslashes($code->code))?>" /></td>
                    </tr>
                    
					<?php
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
					?>
					
					<tr>
                        <th scope="row" valign="top"><label for="starts"><?php _e('Start Date', 'pmpro');?>:</label></th>
                        <td>
							<select name="starts_month">
								<?php																
									for($i = 1; $i < 13; $i++)
									{
									?>
									<option value="<?php echo $i?>" <?php if($i == $selected_starts_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year))?></option>
									<?php
									}
								?>
							</select>
							<input name="starts_day" type="text" size="2" value="<?php echo $selected_starts_day?>" />
							<input name="starts_year" type="text" size="4" value="<?php echo $selected_starts_year?>" />
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="expires"><?php _e('Expiration Date', 'pmpro');?>:</label></th>
                        <td>
							<select name="expires_month">
								<?php																
									for($i = 1; $i < 13; $i++)
									{
									?>
									<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year))?></option>
									<?php
									}
								?>
							</select>
							<input name="expires_day" type="text" size="2" value="<?php echo $selected_expires_day?>" />
							<input name="expires_year" type="text" size="4" value="<?php echo $selected_expires_year?>" />
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="uses"><?php _ex('Uses', 'Number of uses for a discount code', 'pmpro');?>:</label></th>
                        <td>
							<input name="uses" type="text" size="10" value="<?php if(!empty($code->uses)) echo str_replace("\"", "&quot;", stripslashes($code->uses));?>" />
							<small class="pmpro_lite"><?php _e('Leave blank for unlimited uses.', 'pmpro');?></small>
						</td>
                    </tr>
                    					
				</tbody>
			</table>
			
			<?php do_action("pmpro_discount_code_after_settings"); ?>
			
			<h3>Which Levels Will This Code Apply To?</h3>
			
			<div class="pmpro_discount_levels">
			<?php
				$levels = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_membership_levels");
				foreach($levels as $level)
				{
					//if this level is already managed for this discount code, use the code values
					if($edit > 0)
					{
						$code_level = $wpdb->get_row("SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id WHERE cl.code_id = '" . $edit . "' AND cl.level_id = '" . $level->id . "' LIMIT 1");
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
				?>
				<div>
					<input type="hidden" name="all_levels[]" value="<?php echo $level->id?>" />
					<input type="checkbox" name="levels[]" value="<?php echo $level->id?>" <?php if(!empty($level->checked)) { ?>checked="checked"<?php } ?> onclick="if(jQuery(this).is(':checked')) jQuery(this).next().show();	else jQuery(this).next().hide();" />
					<?php echo $level->name?>
					<div class="pmpro_discount_levels_pricing level_<?php echo $level->id?>" <?php if(empty($level->checked)) { ?>style="display: none;"<?php } ?>>
						<table class="form-table">
						<tbody>
							<tr>
								<th scope="row" valign="top"><label for="initial_payment"><?php _e('Initial Payment', 'pmpro');?>:</label></th>
								<td><?php echo $pmpro_currency_symbol?><input name="initial_payment[]" type="text" size="20" value="<?php echo str_replace("\"", "&quot;", stripslashes($level->initial_payment))?>" /> <small><?php _e('The initial amount collected at registration.', 'pmpro');?></small></td>
							</tr>
							
							<tr>
								<th scope="row" valign="top"><label><?php _e('Recurring Subscription', 'pmpro');?>:</label></th>
								<td><input class="recurring_checkbox" name="recurring[]" type="checkbox" value="<?php echo $level->id?>" <?php if(pmpro_isLevelRecurring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery(this).attr('checked')) {					jQuery(this).parent().parent().siblings('.recurring_info').show(); if(!jQuery('#custom_trial_<?php echo $level->id?>').is(':checked')) jQuery(this).parent().parent().siblings('.trial_info').hide();} else					jQuery(this).parent().parent().siblings('.recurring_info').hide();" /> <small><?php _e('Check if this level has a recurring subscription payment.', 'pmpro');?></small></td>
							</tr>
							
							<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
								<th scope="row" valign="top"><label for="billing_amount"><?php _e('Billing Ammount', 'pmpro');?>:</label></th>
								<td>
									<?php echo $pmpro_currency_symbol?><input name="billing_amount[]" type="text" size="20" value="<?php echo str_replace("\"", "&quot;", stripslashes($level->billing_amount))?>" /> <small>per</small>
									<input name="cycle_number[]" type="text" size="10" value="<?php echo str_replace("\"", "&quot;", stripslashes($level->cycle_number))?>" />
									<select name="cycle_period[]" onchange="updateCyclePeriod();">
									  <?php
										$cycles = array( 'Day(s)' => 'Day', 'Week(s)' => 'Week', 'Month(s)' => 'Month', 'Year(s)' => 'Year' );
										foreach ( $cycles as $name => $value ) {
										  echo "<option value='$value'";
										  if ( $level->cycle_period == $value ) echo " selected='selected'";
										  echo ">$name</option>";
										}
									  ?>
									</select>
									<br /><small><?php _e('The amount to be billed one cycle after the initial payment.', 'pmpro');?></small>									
								</td>
							</tr>                                        
							
							<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
								<th scope="row" valign="top"><label for="billing_limit"><?php _e('Billing Cycle Limit', 'pmpro');?>:</label></th>
								<td>
									<input name="billing_limit[]" type="text" size="20" value="<?php echo $level->billing_limit?>" />
									<br /><small><?php _e('The <strong>total</strong> number of recurring billing cycles for this level, including the trial period (if applicable) but not including the initial payment. Set to zero if membership is indefinite.', 'pmpro');?></small>
								</td>
							</tr>            								
			
							<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) echo "style='display:none;'";?>>
								<th scope="row" valign="top"><label><?php _e('Custom Trial', 'pmpro');?>:</label></th>
								<td><input id="custom_trial_<?php echo $level->id?>" name="custom_trial[]" type="checkbox" value="<?php echo $level->id?>" <?php if ( pmpro_isLevelTrial($level) ) { echo "checked='checked'"; } ?> onclick="if(jQuery(this).attr('checked')) jQuery(this).parent().parent().siblings('.trial_info').show();	else jQuery(this).parent().parent().siblings('.trial_info').hide();" /> <?php _e('Check to add a custom trial period.', 'pmpro');?></td>
							</tr>
			
							<tr class="trial_info recurring_info" <?php if (!pmpro_isLevelTrial($level)) echo "style='display:none;'";?>>
								<th scope="row" valign="top"><label for="trial_amount"><?php _e('Trial Billing Amount', 'pmpro');?>:</label></th>
								<td>
									<?php echo $pmpro_currency_symbol?><input name="trial_amount[]" type="text" size="20" value="<?php echo str_replace("\"", "&quot;", stripslashes($level->trial_amount))?>" />
									<small><?php _e('for the first', 'pmpro');?></small>
									<input name="trial_limit[]" type="text" size="10" value="<?php echo str_replace("\"", "&quot;", stripslashes($level->trial_limit))?>" />
									<small><?php _e('subscription payments', 'pmpro');?>.</small>																			
								</td>
							</tr>
							
							<tr>
								<th scope="row" valign="top"><label><?php _e('Membership Expiration', 'pmpro');?>:</label></th>
								<td><input id="expiration" name="expiration[]" type="checkbox" value="<?php echo $level->id?>" <?php if(pmpro_isLevelExpiring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery(this).is(':checked')) { jQuery(this).parent().parent().siblings('.expiration_info').show(); } else { jQuery(this).parent().parent().siblings('.expiration_info').hide();}" /> <?php _e('Check this to set when membership access expires.', 'pmpro');?></td>
							</tr>
							
							<tr class="expiration_info" <?php if(!pmpro_isLevelExpiring($level)) {?>style="display: none;"<?php } ?>>
								<th scope="row" valign="top"><label for="billing_amount"><?php _e('Expires In', 'pmpro');?>:</label></th>
								<td>							
									<input id="expiration_number" name="expiration_number[]" type="text" size="10" value="<?php echo str_replace("\"", "&quot;", stripslashes($level->expiration_number))?>" />
									<select id="expiration_period" name="expiration_period[]">
									  <?php
										$cycles = array( 'Day(s)' => 'Day', 'Week(s)' => 'Week', 'Month(s)' => 'Month', 'Year(s)' => 'Year' );
										foreach ( $cycles as $name => $value ) {
										  echo "<option value='$value'";
										  if ( $level->expiration_period == $value ) echo " selected='selected'";
										  echo ">$name</option>";
										}
									  ?>
									</select>
									<br /><small><?php _e('Set the duration of membership access. Note that the any future payments (recurring subscription, if any) will be cancelled when the membership expires.', 'pmpro');?></small>							
								</td>
							</tr> 
						</tbody>
					</table>
					
					<?php do_action("pmpro_discount_code_after_level_settings", $edit, $level); ?>
					
					</div>					
				</div>
				<script>												
					
				</script>
				<?php
				}
			?>
			</div>
			
			<p class="submit topborder">
				<input name="save" type="submit" class="button button-primary" value="Save Code" /> 					
				<input name="cancel" type="button" class="button button-secondary" value="Cancel" onclick="location.href='<?php echo get_admin_url(NULL, '/admin.php?page=pmpro-discountcodes')?>';" />
			</p>
			</form>
		</div>
		
	<?php } else { ?>	
	
		<h2>
			<?php _e('Memberships Discount Codes', 'pmpro');?>
			<a href="admin.php?page=pmpro-discountcodes&edit=-1" class="add-new-h2"><?php _e('Add New Discount Code', 'pmpro');?></a>
		</h2>		
		
		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
		<?php } ?>
		
		<form id="posts-filter" method="get" action="">			
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input"><?php _e('Search Discount Codes', 'pmpro');?>:</label>
				<input type="hidden" name="page" value="pmpro-discountcodes" />
				<input id="post-search-input" type="text" value="<?php if(!empty($s)) echo $s;?>" name="s" size="30" />
				<input class="button" type="submit" value="<?php _e('Search', 'pmpro');?>" id="search-submit "/>
			</p>		
		</form>	
		
		<br class="clear" />
		
		<table class="widefat">
		<thead>
			<tr>
				<th><?php _e('ID', 'pmpro');?></th>
				<th><?php _e('Code', 'pmpro');?></th>
				<th><?php _e('Starts', 'pmpro');?></th>
				<th><?php _e('Expires', 'pmpro');?></th>        
				<th><?php _e('Uses', 'pmpro');?></th>
				<th><?php _e('Levels', 'pmpro');?></th>
				<th></th>		
				<th></th>						
			</tr>
		</thead>
		<tbody>
			<?php
				$sqlQuery = "SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes ";
				if(!empty($s))
					$sqlQuery .= "WHERE code LIKE '%$s%' ";
				$sqlQuery .= "ORDER BY id ASC";
				
				$codes = $wpdb->get_results($sqlQuery, OBJECT);
				
				if(!$codes)
				{
				?>
					<tr><td colspan="7" class="pmpro_pad20">					
						<p><?php _e('Discount codes allow you to offer your memberships at discounted prices to select customers.', 'pmpro');?> <a href="admin.php?page=pmpro-discountcodes&edit=-1"><?php _e('Create your first discount code now', 'pmpro');?></a>.</p>
					</td></tr>
				<?php
				}
				else
				{
					foreach($codes as $code)
					{
					?>
					<tr>
						<td><?php echo $code->id?></td>
						<td>
							<a href="?page=pmpro-discountcodes&edit=<?php echo $code->id?>"><?php echo $code->code?></a>
						</td>
						<td>
							<?php echo date(get_option('date_format'), $code->starts)?>
						</td>
						<td>
							<?php echo date(get_option('date_format'), $code->expires)?>
						</td>				
						<td>
							<?php
								$uses = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = '" . $code->id . "'");
								if($code->uses > 0)
									echo "<strong>" . (int)$uses . "</strong>/" . $code->uses;
								else
									echo "<strong>" . (int)$uses . "</strong>/unlimited";
							?>
						</td>
						<td>
							<?php								
								$sqlQuery = "SELECT l.id, l.name FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_discount_codes_levels cl ON l.id = cl.level_id WHERE cl.code_id = '" . $code->id . "'";								
								$levels = $wpdb->get_results($sqlQuery);
								
								$level_names = array();
								foreach($levels as $level)
									$level_names[] = "<a target=\"_blank\" href=\"" . pmpro_url("checkout", "?level=" . $level->id . "&discount_code=" . $code->code) . "\">" . $level->name . "</a>";
								if($level_names)
									echo implode(", ", $level_names);														
								else
									echo "None";
							?>
						</td>
						<td>
							<a href="?page=pmpro-discountcodes&edit=<?php echo $code->id?>"><?php _e('edit', 'pmpro');?></a>																
						</td>
						<td>
							<a href="javascript:askfirst('<?php printf(__('Are you sure you want to delete the %s discount code? The subscriptions for existing users will not change, but new users will not be able to use this code anymore.', 'pmpro'), $code->code);?>', '?page=pmpro-discountcodes&delete=<?php echo $code->id?>'); void(0);"><?php _e('delete', 'pmpro');?></a>	
						</td>
					</tr>
					<?php
					}
				}
				?>
		</tbody>
		</table>
		
	<?php } ?>
	
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
