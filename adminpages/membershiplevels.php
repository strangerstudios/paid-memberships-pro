<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_membershiplevels")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}	
	
	global $wpdb, $msg, $msgt, $pmpro_currency_symbol;

	//some vars
	$gateway = pmpro_getOption("gateway");
    $pmpro_level_order = pmpro_getOption('level_order');

	global $pmpro_stripe_error, $pmpro_braintree_error, $pmpro_payflow_error, $pmpro_twocheckout_error, $wp_version;
	
	if(isset($_REQUEST['edit']))
		$edit = intval($_REQUEST['edit']);
	else
		$edit = false;
	if(isset($_REQUEST['copy']))
		$copy = intval($_REQUEST['copy']);
	if(isset($_REQUEST['s']))
		$s = sanitize_text_field($_REQUEST['s']);
	else
		$s = "";
	
	if(isset($_REQUEST['action']))
		$action = sanitize_text_field($_REQUEST['action']);
	else
		$action = false;
		
	if(isset($_REQUEST['saveandnext']))
		$saveandnext = intval($_REQUEST['saveandnext']);

	if(isset($_REQUEST['saveid']))
		$saveid = intval($_REQUEST['saveid']);
	if(isset($_REQUEST['deleteid']))
		$deleteid = intval($_REQUEST['deleteid']);

	if($action == "save_membershiplevel")
	{
		$ml_name = stripslashes($_REQUEST['name']);
		$ml_description = stripslashes($_REQUEST['description']);
		$ml_confirmation = stripslashes($_REQUEST['confirmation']);
		$ml_initial_payment = stripslashes($_REQUEST['initial_payment']);
		if(!empty($_REQUEST['recurring']))
			$ml_recurring = 1;
		else
			$ml_recurring = 0;
		$ml_billing_amount = stripslashes($_REQUEST['billing_amount']);
		$ml_cycle_number = stripslashes($_REQUEST['cycle_number']);
		$ml_cycle_period = stripslashes($_REQUEST['cycle_period']);		
		$ml_billing_limit = stripslashes($_REQUEST['billing_limit']);
		if(!empty($_REQUEST['custom_trial']))
			$ml_custom_trial = 1;
		else
			$ml_custom_trial = 0;
		$ml_trial_amount = stripslashes($_REQUEST['trial_amount']);
		$ml_trial_limit = stripslashes($_REQUEST['trial_limit']);  
		if(!empty($_REQUEST['expiration']))
			$ml_expiration = 1;
		else
			$ml_expiration = 0;
		$ml_expiration_number = stripslashes($_REQUEST['expiration_number']);
		$ml_expiration_period = stripslashes($_REQUEST['expiration_period']);
		$ml_categories = array();
		
		//reversing disable to allow here
		if(empty($_REQUEST['disable_signups']))
			$ml_allow_signups = 1;
		else
			$ml_allow_signups = 0;

		foreach ( $_REQUEST as $key => $value )
		{
			if ( $value == 'yes' && preg_match( '/^membershipcategory_(\d+)$/i', $key, $matches ) )
			{
				$ml_categories[] = $matches[1];
			}
		}

		//clearing out values if checkboxes aren't checked
		if(empty($ml_recurring))
		{
			$ml_billing_amount = $ml_cycle_number = $ml_cycle_period = $ml_billing_limit = $ml_trial_amount = $ml_trial_limit = 0;
		}
		elseif(empty($ml_custom_trial))
		{
			$ml_trial_amount = $ml_trial_limit = 0;
		}
		if(empty($ml_expiration))
		{
			$ml_expiration_number = $ml_expiration_period = 0;
		}

		if($saveid > 0)
		{
			$sqlQuery = " UPDATE {$wpdb->pmpro_membership_levels}
						SET name = '" . esc_sql($ml_name) . "',
						  description = '" . esc_sql($ml_description) . "',
						  confirmation = '" . esc_sql($ml_confirmation) . "',
						  initial_payment = '" . esc_sql($ml_initial_payment) . "',
						  billing_amount = '" . esc_sql($ml_billing_amount) . "',
						  cycle_number = '" . esc_sql($ml_cycle_number) . "',
						  cycle_period = '" . esc_sql($ml_cycle_period) . "',
						  billing_limit = '" . esc_sql($ml_billing_limit) . "',
						  trial_amount = '" . esc_sql($ml_trial_amount) . "',
						  trial_limit = '" . esc_sql($ml_trial_limit) . "',                    
						  expiration_number = '" . esc_sql($ml_expiration_number) . "',
						  expiration_period = '" . esc_sql($ml_expiration_period) . "',
						  allow_signups = '" . esc_sql($ml_allow_signups) . "'
						WHERE id = '$saveid' LIMIT 1;";	 
			$wpdb->query($sqlQuery);
			
			pmpro_updateMembershipCategories( $saveid, $ml_categories );
			if(!mysql_errno())
			{
				$edit = false;
				$msg = 2;
				$msgt = __("Membership level updated successfully.", "pmpro");
			}
			else
			{     
				$msg = -2;
				$msg = true;
				$msgt = __("Error updating membership level.", "pmpro");
			}
		}
		else
		{
			$sqlQuery = " INSERT INTO {$wpdb->pmpro_membership_levels}
						( name, description, confirmation, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, expiration_number, expiration_period, allow_signups)
						VALUES
						( '" . esc_sql($ml_name) . "', '" . esc_sql($ml_description) . "', '" . esc_sql($ml_confirmation) . "', '" . esc_sql($ml_initial_payment) . "', '" . esc_sql($ml_billing_amount) . "', '" . esc_sql($ml_cycle_number) . "', '" . esc_sql($ml_cycle_period) . "', '" . esc_sql($ml_billing_limit) . "', '" . esc_sql($ml_trial_amount) . "', '" . esc_sql($ml_trial_limit) . "', '" . esc_sql($ml_expiration_number) . "', '" . esc_sql($ml_expiration_period) . "', '" . esc_sql($ml_allow_signups) . "' )";
			$wpdb->query($sqlQuery);
			if(!mysql_errno())
			{
				$saveid = $wpdb->insert_id;
				pmpro_updateMembershipCategories( $saveid, $ml_categories );
				
				$edit = false;
				$msg = 1;
				$msgt = __("Membership level added successfully.", "pmpro");
			}
			else
			{
				$msg = -1;				
				$msgt = __("Error adding membership level.", "pmpro");
			}
		}
		
		do_action("pmpro_save_membership_level", $saveid);
	}	
	elseif($action == "delete_membership_level")
	{
		global $wpdb;

		$ml_id = intval($_REQUEST['deleteid']);
	  
		if($ml_id > 0)
		{	  
			do_action("pmpro_delete_membership_level", $ml_id);
			
			//remove any categories from the ml
			$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_categories WHERE membership_id = '$ml_id'";	  			
			$r1 = $wpdb->query($sqlQuery);
							
			//cancel any subscriptions to the ml
			$r2 = true;
			$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = '$ml_id' AND status = 'active'");			
			foreach($user_ids as $user_id)
			{
				//change there membership level to none. that will handle the cancel
				if(pmpro_changeMembershipLevel(0, $user_id))
				{
					//okay
				}
				else
				{
					//couldn't delete the subscription
					//we should probably notify the admin	
					$pmproemail = new PMProEmail();			
					$pmproemail->data = array("body"=>"<p>" . sprintf(__("There was an error canceling the subscription for user with ID=%d. You will want to check your payment gateway to see if their subscription is still active.", "pmpro"), $user_id) . "</p>");
					$last_order = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' ORDER BY timestamp DESC LIMIT 1");
					if($last_order)
						$pmproemail->data["body"] .= "<p>" . __("Last Invoice", "pmpro") . ":<br />" . nl2br(var_export($last_order, true)) . "</p>";
					$pmproemail->sendEmail(get_bloginfo("admin_email"));	

					$r2 = false;
				}	
			}					
			
			//delete the ml
			$sqlQuery = "DELETE FROM $wpdb->pmpro_membership_levels WHERE id = '$ml_id' LIMIT 1";	  			
			$r3 = $wpdb->query($sqlQuery);
					
			if($r1 !== FALSE && $r2 !== FALSE && $r3 !== FALSE)
			{
				$msg = 3;
				$msgt = __("Membership level deleted successfully.", "pmpro");
			}
			else
			{
				$msg = -3;
				$msgt = __("Error deleting membership level.", "pmpro");	
			}
		}
		else
		{
			$msg = -3;
			$msgt = __("Error deleting membership level.", "pmpro");
		}
	}  
		
	require_once(dirname(__FILE__) . "/admin_header.php");		
?>

<?php		
	if($edit)
	{			
	?>
		
	<h2>
		<?php
			if($edit > 0)
				echo __("Edit Membership Level", "pmpro");
			else
				echo __("Add New Membership Level", "pmpro");
		?>
	</h2>
		
	<div>
		<?php
			// get the level...
			if(!empty($edit) && $edit > 0)
			{
				$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '$edit' LIMIT 1", OBJECT);
				$temp_id = $level->id;
			}
			elseif(!empty($copy) && $copy > 0)		
			{	
				$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '$copy' LIMIT 1", OBJECT);
				$temp_id = $level->id;
				$level->id = NULL;
			}
			else

			// didn't find a membership level, let's add a new one...
			if(empty($level))
			{
				$level = new stdClass();
				$level->id = NULL;
				$level->name = NULL;
				$level->description = NULL;
				$level->confirmation = NULL;
				$level->billing_amount = NULL;
				$level->trial_amount = NULL;
				$level->initial_payment = NULL;
				$level->billing_limit = NULL;
				$level->trial_limit = NULL;
				$level->expiration_number = NULL;
				$level->expiration_period = NULL;
				$edit = -1;
			}	

			//defaults for new levels
			if(empty($copy) && $edit == -1)
			{			
				$level->cycle_number = 1;
				$level->cycle_period = "Month";
			}
			
			// grab the categories for the given level...
			if(!empty($temp_id))
				$level->categories = $wpdb->get_col("SELECT c.category_id
												FROM $wpdb->pmpro_memberships_categories c
												WHERE c.membership_id = '" . $temp_id . "'");       		
			if(empty($level->categories))
				$level->categories = array();	
			
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<input name="saveid" type="hidden" value="<?php echo esc_attr($edit); ?>" />
			<input type="hidden" name="action" value="save_membershiplevel" />
			<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php _e('ID', 'pmpro');?>:</label></th>
					<td>
						<?php echo $level->id?>						
					</td>
				</tr>								                
				
				<tr>
					<th scope="row" valign="top"><label for="name"><?php _e('Name', 'pmpro');?>:</label></th>
					<td><input name="name" type="text" size="50" value="<?php echo esc_attr($level->name);?>" /></td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="description"><?php _e('Description', 'pmpro');?>:</label></th>
					<td>
						<div id="poststuff" class="pmpro_description">						
						<?php 							
							if(version_compare($wp_version, "3.3") >= 0)
								wp_editor($level->description, "description", array("textarea_rows"=>5)); 
							else
							{
							?>
							<textarea rows="10" cols="80" name="description" id="description"><?php echo esc_textarea($level->description);?></textarea>
							<?php
							}
						?>	
						</div>    
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="confirmation"><?php _e('Confirmation Message', 'pmpro');?>:</label></th>
					<td>
						<div class="pmpro_confirmation">					
						<?php 
							if(version_compare($wp_version, "3.3") >= 0)
								wp_editor($level->confirmation, "confirmation", array("textarea_rows"=>5)); 
							else
							{
							?>
							<textarea rows="10" cols="80" name="confirmation" id="confirmation"><?php echo esc_textarea($level->confirmation);?></textarea>	
							<?php
							}
						?>	
						</div>    
					</td>
				</tr>
			</tbody>
		</table>
		
		<h3 class="topborder"><?php _e('Billing Details', 'pmpro');?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="initial_payment"><?php _e('Initial Payment', 'pmpro');?>:</label></th>
					<td>
						<?php
						if(pmpro_getCurrencyPosition() == "left")
							echo $pmpro_currency_symbol;
						?>
						<input name="initial_payment" type="text" size="20" value="<?php echo esc_attr($level->initial_payment);?>" /> 
						<?php
						if(pmpro_getCurrencyPosition() == "right")
							echo $pmpro_currency_symbol;
						?>
						<small><?php _e('The initial amount collected at registration.', 'pmpro');?></small></td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label><?php _e('Recurring Subscription', 'pmpro');?>:</label></th>
					<td><input id="recurring" name="recurring" type="checkbox" value="yes" <?php if(pmpro_isLevelRecurring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery('#recurring').is(':checked')) { jQuery('.recurring_info').show(); if(jQuery('#custom_trial').is(':checked')) {jQuery('.trial_info').show();} else {jQuery('.trial_info').hide();} } else { jQuery('.recurring_info').hide();}" /> <label for="recurring"><?php _e('Check if this level has a recurring subscription payment.', 'pmpro');?></label></td>
				</tr>
				
				<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="billing_amount"><?php _e('Billing Amount', 'pmpro');?>:</label></th>
					<td>
						<?php
						if(pmpro_getCurrencyPosition() == "left")
							echo $pmpro_currency_symbol;
						?>
						<input name="billing_amount" type="text" size="20" value="<?php echo esc_attr($level->billing_amount);?>" /> 
						<?php
						if(pmpro_getCurrencyPosition() == "right")
							echo $pmpro_currency_symbol;
						?>
						<small><?php _e('per', 'pmpro');?></small>
						<input id="cycle_number" name="cycle_number" type="text" size="10" value="<?php echo esc_attr($level->cycle_number);?>" />
						<select id="cycle_period" name="cycle_period">
						  <?php
							$cycles = array( __('Day(s)', 'pmpro') => 'Day', __('Week(s)', 'pmpro') => 'Week', __('Month(s)', 'pmpro') => 'Month', __('Year(s)', 'pmpro') => 'Year' );
							foreach ( $cycles as $name => $value ) {
							  echo "<option value='$value'";
							  if ( $level->cycle_period == $value ) echo " selected='selected'";
							  echo ">$name</option>";
							}
						  ?>
						</select>
						<br /><small>							
							<?php _e('The amount to be billed one cycle after the initial payment.', 'pmpro');?>							
							<?php if($gateway == "stripe") { ?>
								<br /><strong <?php if(!empty($pmpro_stripe_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Stripe integration currently only supports billing periods of "Week", "Month" or "Year".', 'pmpro');?>
							<?php } elseif($gateway == "braintree") { ?>
								<br /><strong <?php if(!empty($pmpro_braintree_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Braintree integration currently only supports billing periods of "Month" or "Year".', 'pmpro');?>						
							<?php } elseif($gateway == "payflowpro") { ?>
								<br /><strong <?php if(!empty($pmpro_payflow_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Payflow integration currently only supports billing frequencies of 1 and billing periods of "Week", "Month" or "Year".', 'pmpro');?>
							<?php } ?>
						</small>	
						<?php if($gateway == "braintree" && $edit < 0) { ?>
							<p class="pmpro_message"><strong><?php _e('Note', 'pmpro');?>:</strong> <?php _e('After saving this level, make note of the ID and create a "Plan" in your Braintree dashboard with the same settings and the "Plan ID" set to <em>pmpro_#</em>, where # is the level ID.', 'pmpro');?></p>
						<?php } elseif($gateway == "braintree") { ?>
							<p class="pmpro_message"><strong><?php _e('Note', 'pmpro');?>:</strong> <?php _e('You will need to create a "Plan" in your Braintree dashboard with the same settings and the "Plan ID" set to', 'pmpro');?> <em>pmpro_<?php echo $level->id;?></em>.</p>
						<?php } ?>						
					</td>
				</tr>                                        
				
				<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="billing_limit"><?php _e('Billing Cycle Limit', 'pmpro');?>:</label></th>
					<td>
						<input name="billing_limit" type="text" size="20" value="<?php echo $level->billing_limit?>" />
						<br /><small>
							<?php _e('The <strong>total</strong> number of recurring billing cycles for this level, including the trial period (if applicable) but not including the initial payment. Set to zero if membership is indefinite.', 'pmpro');?>							
							<?php if($gateway == "stripe") { ?>
								<br /><strong <?php if(!empty($pmpro_stripe_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Stripe integration currently does not support billing limits. You can still set an expiration date below.', 'pmpro');?></strong>							
							<?php } ?>
						</small>
					</td>
				</tr>            								

				<tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) echo "style='display:none;'";?>>
					<th scope="row" valign="top"><label><?php _e('Custom Trial', 'pmpro');?>:</label></th>
					<td>
						<input id="custom_trial" name="custom_trial" type="checkbox" value="yes" <?php if ( pmpro_isLevelTrial($level) ) { echo "checked='checked'"; } ?> onclick="jQuery('.trial_info').toggle();" /> <label for="custom_trial"><?php _e('Check to add a custom trial period.', 'pmpro');?></label>
												
						<?php if($gateway == "twocheckout") { ?>
							<br /><small><strong <?php if(!empty($pmpro_twocheckout_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('2Checkout integration does not support custom trials. You can do one period trials by setting an initial payment different from the billing amount.', 'pmpro');?></strong></small>
						<?php } ?>
					</td>
				</tr>

				<tr class="trial_info recurring_info" <?php if (!pmpro_isLevelTrial($level)) echo "style='display:none;'";?>>
					<th scope="row" valign="top"><label for="trial_amount"><?php _e('Trial Billing Amount', 'pmpro');?>:</label></th>
					<td>
						<?php
						if(pmpro_getCurrencyPosition() == "left")
							echo $pmpro_currency_symbol;
						?>
						<input name="trial_amount" type="text" size="20" value="<?php echo esc_attr($level->trial_amount);?>" />
						<?php
						if(pmpro_getCurrencyPosition() == "right")
							echo $pmpro_currency_symbol;
						?>
						<small><?php _e('for the first', 'pmpro');?></small>
						<input name="trial_limit" type="text" size="10" value="<?php echo esc_attr($level->trial_limit);?>" />
						<small><?php _e('subscription payments', 'pmpro');?>.</small>	
						<?php if($gateway == "stripe") { ?>
							<br /><small>
							<strong <?php if(!empty($pmpro_stripe_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Stripe integration currently does not support trial amounts greater than $0.', 'pmpro');?></strong>
							</small>							
						<?php } elseif($gateway == "braintree") { ?>
							<br /><small>
							<strong <?php if(!empty($pmpro_braintree_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Braintree integration currently does not support trial amounts greater than $0.', 'pmpro');?></strong>
							</small>
						<?php } elseif($gateway == "payflowpro") { ?>
							<br /><small>
							<strong <?php if(!empty($pmpro_payflow_error)) { ?>class="pmpro_red"<?php } ?>><?php _e('Payflow integration currently does not support trial amounts greater than $0.', 'pmpro');?></strong>
							</small>						
						<?php } ?>
					</td>
				</tr>
									 
			</tbody>
		</table>
				
		<h3 class="topborder"><?php _e('Other Settings', 'pmpro');?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php _e('Disable New Signups', 'pmpro');?>:</label></th>
					<td><input id="disable_signups" name="disable_signups" type="checkbox" value="yes" <?php if($level->id && !$level->allow_signups) { ?>checked="checked"<?php } ?> /> <label for="disable_signups"><?php _e('Check to hide this level from the membership levels page and disable registration.', 'pmpro');?></label></td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label><?php _e('Membership Expiration', 'pmpro');?>:</label></th>
					<td><input id="expiration" name="expiration" type="checkbox" value="yes" <?php if(pmpro_isLevelExpiring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery('#expiration').is(':checked')) { jQuery('.expiration_info').show(); } else { jQuery('.expiration_info').hide();}" /> <label for="expiration"><?php _e('Check this to set when membership access expires.', 'pmpro');?></a></td>
				</tr>
				
				<tr class="expiration_info" <?php if(!pmpro_isLevelExpiring($level)) {?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top"><label for="billing_amount"><?php _e('Expires In', 'pmpro');?>:</label></th>
					<td>							
						<input id="expiration_number" name="expiration_number" type="text" size="10" value="<?php echo esc_attr($level->expiration_number);?>" />
						<select id="expiration_period" name="expiration_period">
						  <?php
							$cycles = array( __('Day(s)', 'pmpro') => 'Day', __('Week(s)', 'pmpro') => 'Week', __('Month(s)', 'pmpro') => 'Month', __('Year(s)', 'pmpro') => 'Year' );
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
		
		<?php do_action("pmpro_membership_level_after_other_settings"); ?>				
		
		<h3 class="topborder"><?php _e('Content Settings', 'pmpro');?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php _e('Categories', 'pmpro');?>:</label></th>
					<td>
						<?php
						$categories = get_categories( array( 'hide_empty' => 0 ) );
						echo "<ul>";
						foreach ( $categories as $cat )
						{                               								
							$checked = in_array( $cat->term_id, $level->categories ) ? "checked='checked'" : '';
							echo "<li><input id='membershipcategory_{$cat->term_id}' name='membershipcategory_{$cat->term_id}' type='checkbox' value='yes' $checked /> <label for='membershipcategory_{$cat->term_id}'>{$cat->name}</label></li>\n";
						}
						echo "</ul>";
						?>
					</td>
				</tr>
			</tbody>
		</table>				
		<p class="submit topborder">
			<input name="save" type="submit" class="button-primary" value="<?php _e('Save Level', 'pmpro'); ?>" /> 					
			<input name="cancel" type="button" value="<?php _e('Cancel', 'pmpro'); ?>" onclick="location.href='<?php echo get_admin_url(NULL, '/admin.php?page=pmpro-membershiplevels')?>';" /> 					
		</p>
	</form>
	</div>
		
	<?php
	}	
	else
	{
		$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
		if($s)
			$sqlQuery .= "WHERE name LIKE '%$s%' ";
		$sqlQuery .= "ORDER BY id ASC";
		
		$levels = $wpdb->get_results($sqlQuery, OBJECT);						
		
        if(empty($_REQUEST['s']) && !empty($pmpro_level_order)) {
            //reorder levels
            $order = explode(',', $pmpro_level_order);
			
			//put level ids in their own array
			$level_ids = array();
			foreach($levels as $level)
				$level_ids[] = $level->id;
			
			//remove levels from order if they are gone
			foreach($order as $key => $level_id)
				if(!in_array($level_id, $level_ids))
					unset($order[$key]);
					
			//add levels to the end if they aren't in the order array
			foreach($level_ids as $level_id)
				if(!in_array($level_id, $order))
					$order[] = $level_id;
			
			//remove dupes
			$order = array_unique($order);
			
			//save the level order
			pmpro_setOption('level_order', implode(',', $order));

			//reorder levels here
            $reordered_levels = array();
            foreach ($order as $level_id) {
                foreach ($levels as $level) {
                    if ($level_id == $level->id)
                        $reordered_levels[] = $level;
                }
            }								
        }
		else
			$reordered_levels = $levels;

		if(empty($_REQUEST['s']) && count($reordered_levels) > 1)
		{
			?>
		    <script>
		        jQuery(document).ready(function($) {

		            // Return a helper with preserved width of cells
		            // from http://www.foliotek.com/devblog/make-table-rows-sortable-using-jquery-ui-sortable/
		            var fixHelper = function(e, ui) {
		                ui.children().each(function() {
		                    $(this).width($(this).width());
		                });
		                return ui;
		            };

		            $("table.membership-levels tbody").sortable({
		                helper: fixHelper,
		                placeholder: 'testclass',
		                forcePlaceholderSize: true,
		                update: update_level_order
		            });

		            function update_level_order(event, ui) {
		                level_order = [];
		                $("table.membership-levels tbody tr").each(function() {
		                    $(this).removeClass('alternate');
		                    level_order.push(parseInt( $("td:first", this).text()));
		                });

		                //update styles
		                $("table.membership-levels tbody tr:odd").each(function() {
		                    $(this).addClass('alternate');
		                });

		                data = {
		                    action: 'pmpro_update_level_order',
		                    level_order: level_order
		                };

		                $.post(ajaxurl, data, function(response) {
		                });
		            }
		        });
		    </script>
			<?php
			}
		?>

		<h2 class="alignleft"><?php _e('Membership Levels', 'pmpro');?> <a href="admin.php?page=pmpro-membershiplevels&edit=-1" class="add-new-h2"><?php _e('Add New Level', 'pmpro');?></a></h2>
		<form id="posts-filter" method="get" action="">			
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input"><?php _e('Search Levels', 'pmpro');?>:</label>
				<input type="hidden" name="page" value="pmpro-membershiplevels" />
				<input id="post-search-input" type="text" value="<?php echo esc_attr($s); ?>" name="s" size="30" />
				<input class="button" type="submit" value="<?php _e('Search Levels', 'pmpro');?>" id="search-submit" />
			</p>
		</form>

		<?php if(empty($_REQUEST['s']) && count($reordered_levels) > 1) { ?>
			<br class="clear" />
		    <p><?php _e('Drag and drop membership levels to reorder them on the Levels page.', 'pmpro'); ?></p>
	    <?php } ?>

	    <table class="widefat membership-levels">
		<thead>
			<tr>
				<th><?php _e('ID', 'pmpro');?></th>
				<th><?php _e('Name', 'pmpro');?></th>
				<th><?php _e('Billing Details', 'pmpro');?></th>
				<th><?php _e('Expiration', 'pmpro');?></th>
				<th><?php _e('Allow Signups', 'pmpro');?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$count = 0;
				foreach($reordered_levels as $level)
				{
			?>
			<tr class="<?php if($count++ % 2 == 1) { ?>alternate<?php } ?> <?php if(!$level->allow_signups) { ?>pmpro_gray<?php } ?> <?php if(!pmpro_checkLevelForStripeCompatibility($level) || !pmpro_checkLevelForBraintreeCompatibility($level) || !pmpro_checkLevelForPayflowCompatibility($level) || !pmpro_checkLevelForTwoCheckoutCompatibility($level)) { ?>pmpro_error<?php } ?>">			
				<td><?php echo $level->id?></td>
				<td class="level_name"><a href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>"><?php echo $level->name?></a></td>
				<td>
					<?php if(pmpro_isLevelFree($level)) { ?>
						<?php _e('FREE', 'pmpro');?>
					<?php } else { ?>
						<?php echo str_replace( 'The price for membership is', '', pmpro_getLevelCost($level)); ?>
					<?php } ?>
				</td>
				<td>
					<?php if(!pmpro_isLevelExpiring($level)) { ?>
						--
					<?php } else { ?>		
						<?php _e('After', 'pmpro');?> <?php echo $level->expiration_number?> <?php echo sornot($level->expiration_period,$level->expiration_number)?>
					<?php } ?>
				</td>
				<td><?php if($level->allow_signups) { ?><a href="<?php echo pmpro_url("checkout", "?level=" . $level->id);?>"><?php _e('Yes', 'pmpro');?></a><?php } else { ?><?php _e('No', 'pmpro');?><?php } ?></td>

				<td><a title="<?php _e('edit','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>" class="button-primary"><?php _e('edit','pmpro'); ?></a>&nbsp;<a title="<?php _e('copy','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&copy=<?php echo $level->id?>&edit=-1" class="button-secondary"><?php _e('copy','pmpro'); ?></a>&nbsp;<a title="<?php _e('delete','pmpro'); ?>" href="javascript: askfirst('<?php echo str_replace("'", "\'", sprintf("Are you sure you want to delete membership level %s? All subscriptions will be cancelled.", "pmpro"), $level->name);?>','admin.php?page=pmpro-membershiplevels&action=delete_membership_level&deleteid=<?php echo $level->id?>'); void(0);" class="button-secondary"><?php _e('delete','pmpro'); ?></a></td>
			</tr>
			<?php
				}
			?>
		</tbody>
		</table>	
	<?php
	}
	?>		
	
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
