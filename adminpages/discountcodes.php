<?php
	//vars
	global $wpdb;
	$edit = $_REQUEST['edit'];
	$delete = $_REQUEST['delete'];
	$saveid = $_POST['saveid'];
	
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
			$sqlQuery = "UPDATE $wpdb->pmpro_discount_codes SET code = '" . $wpdb->escape($code) . "', starts = '" . $starts . "', expires = '" . $expires . "', uses = '" . $uses . "' WHERE id = '" . $saveid . "' LIMIT 1";
			if($wpdb->query($sqlQuery) !== false)
			{
				$pmpro_msg = "Discount code updated successfully.";
				$pmpro_msgt = "success";
				$saved = true;
				$edit = $saveid;								
			}
			else
			{
				$pmpro_msg = "Error updating discount code. That code may already be in use.";
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes (id, code, starts, expires, uses) VALUES('', '" . $wpdb->escape($code) . "', '" . $starts . "', '" . $expires . "', '" . $uses . "')";
			if($wpdb->query($sqlQuery) !== false)
			{
				$pmpro_msg = "Discount code added successfully.";
				$pmpro_msgt = "success";
				$saved = true;
				$edit = $wpdb->insert_id;
			}
			else
			{
				$pmpro_msg = "Error adding discount code. That code may already be in use.";				
				$pmpro_msgt = "error";
			}
		}
		
		//now add the membership level rows		
		if($saved && $edit > 0)
		{
			//get the submitted values
			$all_levels_a = $_REQUEST['all_levels'];
			$levels_a = $_REQUEST['levels'];
			$initial_payment_a = $_REQUEST['initial_payment'];
			$recurring_a = $_REQUEST['recurring'];
			$billing_amount_a = $_REQUEST['billing_amount'];
			$cycle_number_a = $_REQUEST['cycle_number'];
			$cycle_period_a = $_REQUEST['cycle_period'];
			$billing_limit_a = $_REQUEST['billing_limit'];
			$custom_trial_a = $_REQUEST['custom_trial'];
			$trial_amount_a = $_REQUEST['trial_amount'];
			$trial_limit_a = $_REQUEST['trial_limit'];						
			$expiration_a = $_REQUEST['expiration'];
			$expiration_number_a = $_REQUEST['expiration_number'];
			$expiration_period_a = $_REQUEST['expiration_period'];
			
			//clear the old rows
			$sqlQuery = "DELETE FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . $edit . "'";
			$wpdb->query($sqlQuery);
			
			//add a row for each checked level
			foreach($levels_a as $level_id)
			{
				//get the values ready
				$n = array_search($level_id, $all_levels_a); 	//this is the key location of this level's values
				$initial_payment = $initial_payment_a[$n];
				
				//is this recurring?
				if($recurring_a)
				{
					if(in_array($level_id, $recurring_a))
						$recurring = 1;
					else
						$recurring = 0;
				}
				else
					$recurring = 0;
						
				if($recurring)
				{
					$billing_amount = $billing_amount_a[$n];
					$cycle_number = $cycle_number_a[$n];
					$cycle_period = $cycle_period_a[$n];
					$billing_limit = $billing_limit_a[$n];
					
					//custom trial
					if($custom_trial_a)
					{
						if(in_array($level_id, $custom_trial_a))
							$custom_trial = 1;
						else
							$custom_trial = 0;
					}
					else
						$custom_trial = 0;
					
					if($custom_trial)
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
					$cycle_period = '';
					$billing_limit = '';
					$custom_trial = 0;
					$trial_amount = '';
					$trial_limit = '';
				}
				
				if($expiration_a)
				{
					if(in_array($level_id, $expiration_a))
						$expiration = 1;
					else
						$expiration = 0;
				}
				else
					$expiration = 0;
				
				if($expiration)
				{
					$expiration_number = $expiration_number_a[$n];
					$expiration_period = $expiration_period_a[$n];
				}
				else
				{
					$expiration_number = '';
					$expiration_period = '';
				}
				
				//okay, do the insert
				$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes_levels (code_id, level_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, expiration_number, expiration_period) VALUES('" . $wpdb->escape($edit) . "', '" . $wpdb->escape($level_id) . "', '" . $wpdb->escape($initial_payment) . "', '" . $wpdb->escape($billing_amount) . "', '" . $wpdb->escape($cycle_number) . "', '" . $wpdb->escape($cycle_period) . "', '" . $wpdb->escape($billing_limit) . "', '" . $wpdb->escape($trial_amount) . "', '" . $wpdb->escape($trial_limit) . "', '" . $wpdb->escape($expiration_number) . "', '" . $wpdb->escape($expiration_period) . "')";
								
				if($wpdb->query($sqlQuery) !== false)
				{
					//okay
				}
				else
				{
					$level_errors[] = "Error saving values for the " . $wpdb->get_var("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '" . $level_id . "' LIMIT 1") . " level.";
				}
			}
			
			//errors?
			if($level_errors)
			{
				$pmpro_msg = "There were errors updating the level values: " . explode(" ", $level_errors);
				$pmpro_msgt = "error";				
			}
			else
			{
				//all good. set edit = NULL so we go back to the overview page
				$edit = NULL;
			}
		}
	}
	
	//are we deleting?
	if($delete)
	{
		//is this a code?
		$code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $delete . "' LIMIT 1");
		if($code)
		{
			//delete the code levels
			$r1 = $wpdb->query("DELETE FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . $delete . "'");
			
			if($r1 !== false)
			{
				//delete the code
				$r2 = $wpdb->query("DELETE FROM $wpdb->pmpro_discount_codes WHERE id = '" . $delete . "' LIMIT 1");
				
				if($r2 !== false)
				{
					$pmpro_msg = "Code $code deleted successfully.";
					$pmpro_msgt = "success";
				}
				else
				{
					$pmpro_msg = "Error deleting discount code. The code was only partially deleted. Please try again.";
					$pmpro_msgt = "error";
				}
			}
			else
			{
				$pmpro_msg = "Error deleting code. Please try again.";
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$pmpro_msg = "Code not found.";
			$pmpro_msgt = "error";
		}
	}
?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?=pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?=PMPRO_URL?>/images/PaidMembershipsPro.gif" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro_tagline">Membership Plugin for WordPress</div>
		
		<div class="pmpro_meta"><a href="<?=pmpro_https_filter("http://www.paidmembershipspro.com")?>">Plugin Support</a> | <a href="http://www.paidmembershipspro.com/forums/">User Forum</a> | <strong>Version <?=PMPRO_VERSION?></strong></div>
	</div>
	<br style="clear:both;" />
	
	<?php
		//include(pmpro_https_filter("http://www.paidmembershipspro.com/notifications/?v=" . PMPRO_VERSION));
	?>
	<div id="pmpro_notifications">
	</div>
	<script>
		jQuery.get('<?=pmpro_https_filter("http://www.paidmembershipspro.com/notifications/?v=" . PMPRO_VERSION)?>', function(data) {
		  jQuery('#pmpro_notifications').html(data);		 
		});
	</script>
	
	<?php if($edit) { ?>
		
		<h2>
			<?php
				if($edit > 0)
					echo "Edit Discount Code";
				else
					echo "Add New Discount Code";
			?>
		</h2>
		
		<?php if($pmpro_msg){?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?=$pmpro_msg?></p></div>
		<?php } ?>
		
		<div>
			<?php
				// get the code...
				if($edit > 0)
				{
					$code = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes WHERE id = '" . $edit . "' LIMIT 1", OBJECT);
					$uses = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = '" . $code->ID . "'");
					$levels = $wpdb->get_results("SELECT l.id, l.name, cl.initial_payment, cl.billing_amount, cl.cycle_number, cl.period, cl.billing_limit, cl.trial_amount, cl.trial_limit FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_discount_codes_levels cl ON l.id = cl.level_id WHERE cl.code_id = '" . $code->code . "'");
					$temp_id = $code->id;
				}
				elseif($copy > 0)		
				{	
					$code = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes WHERE id = '" . $copy . "' LIMIT 1", OBJECT);					
					$temp_id = $level->id;
					$level->id = NULL;
				}

				// didn't find a discount code, let's add a new one...
				if(!$code->id) $edit = -1;

				//defaults for new codes
				if($edit == -1)
				{
					$code = NULL;
					$code->code = pmpro_getDiscountCode();
				}								
			?>
			<form action="" method="post">
				<input name="saveid" type="hidden" value="<?=$edit?>" />
				<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label>ID:</label></th>
                        <td class="pmpro_lite"><?php if($code->id) echo $code->id; else echo "This will be generated when you save.";?></td>
                    </tr>								                
                    
                    <tr>
                        <th scope="row" valign="top"><label for="code">Code:</label></th>
                        <td><input name="code" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($code->code))?>" /></td>
                    </tr>
                    
					<?php
						//some vars for the dates
						$current_day = date("j");
						if($code->starts) 
							$selected_starts_day = date("j", $code->starts);
						else
							$selected_starts_day = $current_day;
						if($code->expires) 
							$selected_expires_day = date("j", $code->expires);
						else
							$selected_expires_day = $current_day;
							
						$current_month = date("M");
						if($code->starts) 
							$selected_starts_month = date("m", $code->starts);
						else
							$selected_starts_month = date("m");
						if($code->expires) 
							$selected_expires_month = date("m", $code->expires);
						else
							$selected_expires_month = date("m");
							
						$current_year = date("Y");						
						if($code->starts) 
							$selected_starts_year = date("Y", $code->starts);
						else
							$selected_starts_year = $current_year;
						if($code->expires) 
							$selected_expires_year = date("Y", $code->expires);
						else
							$selected_expires_year = (int)$current_year + 1;
					?>
					
					<tr>
                        <th scope="row" valign="top"><label for="starts">Start Date:</label></th>
                        <td>
							<select name="starts_month">
								<?php																
									for($i = 1; $i < 13; $i++)
									{
									?>
									<option value="<?=$i?>" <?php if($i == $selected_starts_month) { ?>selected="selected"<?php } ?>><?=date("M", strtotime($i . "/1/" . $current_year))?></option>
									<?php
									}
								?>
							</select>
							<input name="starts_day" type="text" size="2" value="<?=$selected_starts_day?>" />
							<input name="starts_year" type="text" size="4" value="<?=$selected_starts_year?>" />
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="expires">Expiration Date:</label></th>
                        <td>
							<select name="expires_month">
								<?php																
									for($i = 1; $i < 13; $i++)
									{
									?>
									<option value="<?=$i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?=date("M", strtotime($i . "/1/" . $current_year))?></option>
									<?php
									}
								?>
							</select>
							<input name="expires_day" type="text" size="2" value="<?=$selected_expires_day?>" />
							<input name="expires_year" type="text" size="4" value="<?=$selected_expires_year?>" />
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="uses">Uses:</label></th>
                        <td>
							<input name="uses" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($code->uses))?>" />
							<small class="pmpro_lite">Leave blank for unlimited uses.</small>
						</td>
                    </tr>
                    
				</tbody>
			</table>
			
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
					<input type="hidden" name="all_levels[]" value="<?=$level->id?>" />
					<input type="checkbox" name="levels[]" value="<?=$level->id?>" <?php if($level->checked) { ?>checked="checked"<?php } ?> onclick="if(jQuery(this).is(':checked')) jQuery(this).next().show();	else jQuery(this).next().hide();" />
					<?=$level->name?>
					<div class="pmpro_discount_levels_pricing level_<?=$level->id?>" <?php if(!$level->checked) { ?>style="display: none;"<?php } ?>>
						<table class="form-table">
						<tbody>
							<tr>
								<th scope="row" valign="top"><label for="initial_payment">Initial Payment:</label></th>
								<td>$<input name="initial_payment[]" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($level->initial_payment))?>" /> <small>The initial amount collected at registration.</small></td>
							</tr>
							
							<tr>
								<th scope="row" valign="top"><label>Recurring Subscription:</label></th>
								<td><input class="recurring_checkbox" name="recurring[]" type="checkbox" value="<?=$level->id?>" <?php if(pmpro_isLevelRecurring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery(this).attr('checked')) {					jQuery(this).parent().parent().siblings('.recurring_info').show(); if(!jQuery('#custom_trial_<?=$level->id?>').is(':checked')) jQuery(this).parent().parent().siblings('.trial_info').hide();} else					jQuery(this).parent().parent().siblings('.recurring_info').hide();" /> <small>Check if this level has a recurring subscription payment.</small></td>
							</tr>
							
							<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
								<th scope="row" valign="top"><label for="billing_amount">Billing Amount:</label></th>
								<td>
									$<input name="billing_amount[]" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($level->billing_amount))?>" /> <small>per</small>
									<input name="cycle_number[]" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($level->cycle_number))?>" />
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
									<br /><small>The amount to be billed one cycle after the initial payment.</small>									
								</td>
							</tr>                                        
							
							<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
								<th scope="row" valign="top"><label for="billing_limit">Billing Cycle Limit:</label></th>
								<td>
									<input name="billing_limit[]" type="text" size="20" value="<?=$level->billing_limit?>" />
									<br /><small>The <strong>total</strong> number of billing cycles for this level, including the trial period (if applicable). Set to zero if membership is indefinite.</small>
								</td>
							</tr>            								
			
							<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) echo "style='display:none;'";?>>
								<th scope="row" valign="top"><label>Custom Trial:</label></th>
								<td><input id="custom_trial_<?=$level->id?>" name="custom_trial[]" type="checkbox" value="<?=$level->id?>" <?php if ( pmpro_isLevelTrial($level) ) { echo "checked='checked'"; } ?> onclick="if(jQuery(this).attr('checked')) jQuery(this).parent().parent().siblings('.trial_info').show();	else jQuery(this).parent().parent().siblings('.trial_info').hide();" /> Check to add a custom trial period.</td>
							</tr>
			
							<tr class="trial_info recurring_info" <?php if (!pmpro_isLevelTrial($level)) echo "style='display:none;'";?>>
								<th scope="row" valign="top"><label for="trial_amount">Trial Billing Amount:</label></th>
								<td>
									$<input name="trial_amount[]" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($level->trial_amount))?>" />
									<small>for the first</small>
									<input name="trial_limit[]" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($level->trial_limit))?>" />
									<small>subscription payments.</small>																			
								</td>
							</tr>
							
							<tr>
								<th scope="row" valign="top"><label>Membership Expiration:</label></th>
								<td><input id="expiration" name="expiration[]" type="checkbox" value="<?=$level->id?>" <?php if(pmpro_isLevelExpiring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery(this).is(':checked')) { jQuery(this).parent().parent().siblings('.expiration_info').show(); } else { jQuery(this).parent().parent().siblings('.expiration_info').hide();}" /> <small>Check this to set an expiration date for new sign ups.</small></td>
							</tr>
							
							<tr class="expiration_info" <?php if(!pmpro_isLevelExpiring($level)) {?>style="display: none;"<?php } ?>>
								<th scope="row" valign="top"><label for="billing_amount">Expire In:</label></th>
								<td>							
									<input id="expiration_number" name="expiration_number[]" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($level->expiration_number))?>" />
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
									<br /><small>How long before the expiration expires. Not that any future payments will be canceled when the membership expires.</small>							
								</td>
							</tr> 
						</tbody>
					</table>
					
					</div>					
				</div>
				<script>												
					
				</script>
				<?php
				}
			?>
			</div>
			
			<p class="submit topborder">
				<input name="save" type="submit" class="button-primary" value="Save Code" /> 					
				<input name="cancel" type="button" value="Cancel" onclick="location.href='<?=home_url('/wp-admin/admin.php?page=pmpro-discountcodes')?>';" />
			</p>
			</form>
		</div>
		
	<?php } else { ?>	
	
		<h2>
			Memberships Discount Codes
			<a href="admin.php?page=pmpro-discountcodes&edit=-1" class="button add-new-h2">Add New Discount Code</a>
		</h2>		
		
		<?php if($pmpro_msg){?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?=$pmpro_msg?></p></div>
		<?php } ?>
		
		<form id="posts-filter" method="get" action="">			
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input">Search Discount Codes:</label>
				<input type="hidden" name="page" value="pmpro-discountcodes" />
				<input id="post-search-input" type="text" value="<?=$s?>" name="s" size="30" />
				<input class="button" type="submit" value="Search" id="search-submit "/>
			</p>		
		</form>	
		
		<br class="clear" />
		
		<table class="widefat">
		<thead>
			<tr>
				<th>ID</th>
				<th>Code</th>
				<th>Starts</th>
				<th>Expires</th>        
				<th>Uses</th>
				<th>Levels</th>
				<th></th>		
				<th></th>						
			</tr>
		</thead>
		<tbody>
			<?php
				$sqlQuery = "SELECT *, UNIX_TIMESTAMP(starts) as starts, UNIX_TIMESTAMP(expires) as expires FROM $wpdb->pmpro_discount_codes ";
				if($s)
					$sqlQuery .= "WHERE code LIKE '%$s%' ";
				$sqlQuery .= "ORDER BY id ASC";
				
				$codes = $wpdb->get_results($sqlQuery, OBJECT);
				
				if(!$codes)
				{
				?>
					<tr><td colspan="7" class="pmpro_pad20">					
						<p>Discount codes allow you to offer your memberships at discounted prices to select customers. <a href="admin.php?page=pmpro-discountcodes&edit=-1">Create your first discount code now</a>.</p>
					</td></tr>
				<?php
				}
				else
				{
					foreach($codes as $code)
					{
					?>
					<tr>
						<td><?=$code->id?></td>
						<td>
							<a href="?page=pmpro-discountcodes&edit=<?=$code->id?>"><?=$code->code?></a>
						</td>
						<td>
							<?=date("m/d/Y", $code->starts)?>
						</td>
						<td>
							<?=date("m/d/Y", $code->expires)?>
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
									$level_names[] = "<a target=\"_blank\" href=\"" . pmpro_url("checkout", "?level=" . $level->id . "&discountcode=" . $code->code) . "\">" . $level->name . "</a>";
								if($level_names)
									echo implode(", ", $level_names);														
								else
									echo "None";
							?>
						</td>
						<td>
							<a href="?page=pmpro-discountcodes&edit=<?=$code->id?>">edit</a>																
						</td>
						<td>
							<a href="javascript:askfirst('Are you sure you want to delete the <?=$code->code?> discount code? The subscriptions for existing users will not change, but new users will not be able to use this code anymore.', '?page=pmpro-discountcodes&delete=<?=$code->id?>'); void(0);">delete</a>	
						</td>
					</tr>
					<?php
					}
				}
				?>
		</tbody>
		</table>
		
	<?php } ?>
	
</div>
<?php
?>
