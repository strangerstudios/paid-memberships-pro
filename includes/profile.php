<?php
/*
	These functions add the "membership level" field to the edit user/profile page
*/
//add the fields
function pmpro_membership_level_profile_fields($user)
{
	global $current_user;

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	global $wpdb;
	/*$user->membership_level = $wpdb->get_row("SELECT l.id AS ID, l.name AS name
														FROM {$wpdb->pmpro_membership_levels} AS l
														JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
														WHERE mu.user_id = " . $user->ID . "
														LIMIT 1");*/
	$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

	if(!$levels)
		return "";
?>
<h3><?php _e("Membership Level", "pmpro"); ?></h3>
<table class="form-table">
    <?php
		$show_membership_level = true;
		$show_membership_level = apply_filters("pmpro_profile_show_membership_level", $show_membership_level, $user);
		if($show_membership_level)
		{
		?>
		<tr>
			<th><label for="membership_level"><?php _e("Current Level", "pmpro"); ?></label></th>
			<td>
				<select name="membership_level" onchange="pmpro_mchange_warning();">
					<option value="" <?php if(empty($user->membership_level->ID)) { ?>selected="selected"<?php } ?>>-- <?php _e("None", "pmpro");?> --</option>
				<?php
					foreach($levels as $level)
					{
						$current_level = ($user->membership_level->ID == $level->id);
				?>
					<option value="<?php echo $level->id?>" <?php if($current_level) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
				<?php
					}
				?>
				</select>
				<script>
					var pmpro_mchange_once = 0;
					function pmpro_mchange_warning()
					{
						if(pmpro_mchange_once == 0)
						{
							alert('Warning: The existing membership will be cancelled, and the new membership will be free.');
							pmpro_mchange_once = 1;
						}
					}
				</script>
				<?php
					$membership_values = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND user_id = '" . $user->ID . "' LIMIT 1");
					if(!empty($membership_values->billing_amount) || !empty($membership_values->trial_amount))
					{
					?>
						<?php if($membership_values->billing_amount > 0) { ?>
							at <?php echo pmpro_formatPrice($membership_values->billing_amount);?>
							<?php if($membership_values->cycle_number > 1) { ?>
								per <?php echo $membership_values->cycle_number?> <?php echo sornot($membership_values->cycle_period,$membership_values->cycle_number)?>
							<?php } elseif($membership_values->cycle_number == 1) { ?>
								per <?php echo $membership_values->cycle_period?>
							<?php } ?>
						<?php } ?>

						<?php if($membership_values->billing_limit) { ?> for <?php echo $membership_values->billing_limit.' '.sornot($membership_values->cycle_period,$membership_values->billing_limit)?><?php } ?>.

						<?php if($membership_values->trial_limit) { ?>
							The first <?php echo $membership_values->trial_limit?> <?php echo sornot("payments",$membership_values->trial_limit)?> will cost <?php echo pmpro_formatPrice($membership_values->trial_amount);?>.
						<?php } ?>
					<?php
					}
					else
					{
						_e("User is not paying.", "pmpro");
					}
				?>
			</td>
		</tr>
		<?php
		}
		
		$show_expiration = true;
		$show_expiration = apply_filters("pmpro_profile_show_expiration", $show_expiration, $user);
		if($show_expiration)
		{					
			//is there an end date?
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
			$end_date = !empty($user->membership_level->enddate);
			
			//some vars for the dates
			$current_day = date("j");			
			if($end_date)
				$selected_expires_day = date("j", $user->membership_level->enddate);
			else
				$selected_expires_day = $current_day;
				
			$current_month = date("M");			
			if($end_date)
				$selected_expires_month = date("m", $user->membership_level->enddate);
			else
				$selected_expires_month = date("m");
				
			$current_year = date("Y");									
			if($end_date)
				$selected_expires_year = date("Y", $user->membership_level->enddate);
			else
				$selected_expires_year = (int)$current_year + 1;
		?>
		<tr>
			<th><label for="expiration"><?php _e("Expires", "pmpro"); ?></label></th>
			<td>
				<select id="expires" name="expires">
					<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", "pmpro");?></option>
					<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", "pmpro");?></option>
				</select>
				<span id="expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
					on
					<select name="expires_month">
						<?php																
							for($i = 1; $i < 13; $i++)
							{
							?>
							<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year, current_time("timestamp")))?></option>
							<?php
							}
						?>
					</select>
					<input name="expires_day" type="text" size="2" value="<?php echo $selected_expires_day?>" />
					<input name="expires_year" type="text" size="4" value="<?php echo $selected_expires_year?>" />
				</span>
				<script>
					jQuery('#expires').change(function() {
						if(jQuery(this).val() == 1)
							jQuery('#expires_date').show();
						else
							jQuery('#expires_date').hide();
					});
				</script>
			</td>
		</tr>
		<?php
		}
		?>
</table>
<?php
}

//save the fields on update
function pmpro_membership_level_profile_fields_update()
{
	//get the user id
	global $wpdb, $current_user, $user_ID;
	get_currentuserinfo();
	
	if(!empty($_REQUEST['user_id'])) 
		$user_ID = $_REQUEST['user_id'];

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;
		
	//level change
	if(isset($_REQUEST['membership_level']))
	{
		if(pmpro_changeMembershipLevel($_REQUEST['membership_level'], $user_ID))
		{
			//it changed. send email
			$level_changed = true;
		}		
	}
	
	//expiration change
	if(!empty($_REQUEST['expires']))
	{
		//update the expiration date
		$expiration_date = intval($_REQUEST['expires_year']) . "-" . str_pad(intval($_REQUEST['expires_month']), 2, "0", STR_PAD_LEFT) . "-" . str_pad(intval($_REQUEST['expires_day']), 2, "0", STR_PAD_LEFT);
		$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users SET enddate = '" . $expiration_date . "' WHERE status = 'active' AND membership_id = '" . intval($_REQUEST['membership_level']) . "' AND user_id = '" . $user_ID . "' LIMIT 1";		
		if($wpdb->query($sqlQuery))
			$expiration_changed = true;
	}
	elseif(isset($_REQUEST['expires']))
	{
		//already blank? have to check for null or '0000-00-00 00:00:00' or '' here.
		$sqlQuery = "SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE (enddate IS NULL OR enddate = '' OR enddate = '0000-00-00 00:00:00') AND status = 'active' AND user_id = '" . $user_ID . "' LIMIT 1";
		$blank = $wpdb->get_var($sqlQuery);
		
		if(empty($blank))
		{		
			//null out the expiration
			$sqlQuery = "UPDATE $wpdb->pmpro_memberships_users SET enddate = NULL WHERE status = 'active' AND membership_id = '" . intval($_REQUEST['membership_level']) . "' AND user_id = '" . $user_ID . "' LIMIT 1";
			if($wpdb->query($sqlQuery))
				$expiration_changed = true;
		}
	}
	
	//send email
	if(!empty($level_changed) || !empty($expiration_changed))
	{
		//email to member
		$pmproemail = new PMProEmail();
		if(!empty($expiration_changed))
			$pmproemail->expiration_changed = true;
		$pmproemail->sendAdminChangeEmail(get_userdata($user_ID));
		
		//email to admin
		$pmproemail = new PMProEmail();
		if(!empty($expiration_changed))
			$pmproemail->expiration_changed = true;
		$pmproemail->sendAdminChangeAdminEmail(get_userdata($user_ID));
	}
}
add_action( 'show_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'edit_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'profile_update', 'pmpro_membership_level_profile_fields_update' );