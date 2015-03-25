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
				<select name="membership_level">
					<option value="" <?php if(empty($user->membership_level->ID)) { ?>selected="selected"<?php } ?>>-- <?php _e("None", "pmpro");?> --</option>
				<?php
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php selected($level->id, (isset($user->membership_level->ID) ? $user->membership_level->ID : 0 )); ?>><?php echo $level->name?></option>
				<?php
					}
				?>
				</select>
                <span id="current_level_cost">
                <?php
                $membership_values = pmpro_getMembershipLevelForUser($user->ID);
                if(empty($membership_values) || pmpro_isLevelFree($membership_values))
                { ?>
                    <?php _e("Not paying.", "pmpro"); ?>
                <?php }
                else
                {
                    //we tweak the initial payment here so the text here effectively shows the recurring amount
                    $membership_values->initial_payment = $membership_values->billing_amount;
                    echo pmpro_getLevelCost($membership_values, true, true);
                }
                ?>
                </span>
                <p id="cancel_description" class="description hidden"><?php _e("This will not change the subscription at the gateway unless the 'Cancel' checkbox is selected below.", "pmpro"); ?></p>
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
        <tr class="more_level_options">
            <th></th>
            <td>
                <label for="send_admin_change_email"><input value="1" id="send_admin_change_email" name="send_admin_change_email" type="checkbox"> Send the user an email about this change.</label>
            </td>
        </tr>
        <tr class="more_level_options">
            <th></th>
            <td>
                <label for="cancel_subscription"><input value="1" id="cancel_subscription" name="cancel_subscription" type="checkbox"> Cancel this user's subscription at the gateway.</label>
            </td>
        </tr>
		<?php
		}
		?>
</table>
    <script>
        jQuery(document).ready(function() {
            //vars for fields
			var $membership_level_select = jQuery("[name=membership_level]");
            var $expires_select = jQuery("[name=expires]");
			var $expires_month_select = jQuery("[name=expires_month]");
			var $expires_day_text = jQuery("[name=expires_day]");
			var $expires_year_text = jQuery("[name=expires_year]");
			
			//note old data to check for changes
			var old_level = $membership_level_select.val();
            var old_expires = $expires_select.val();
			var old_expires_month = $expires_month_select.val();
			var old_expires_day = $expires_day_text.val();
			var old_expires_year = $expires_year_text.val();
						
			var current_level_cost = jQuery("#current_level_cost").text();

            //hide by default
			jQuery(".more_level_options").hide();

			function pmpro_checkForLevelChangeInProfile()
			{
				//cancelling sub or not
				if($membership_level_select.val() == 0) {
                    jQuery("#cancel_subscription").attr('checked', true);
                    jQuery("#current_level_cost").text("Not paying.");
                }
                else {
                    jQuery("#cancel_subscription").attr('checked', false);
                    jQuery("#current_level_cost").text(current_level_cost);
                }
				
				//did level or expiration change?
                if(
					$membership_level_select.val() != old_level ||
					$expires_select.val() != old_expires ||
					$expires_month_select.val() != old_expires_month ||
					$expires_day_text.val() != old_expires_day ||
					$expires_year_text.val() != old_expires_year
				)
                {
                    jQuery(".more_level_options").show();
                    jQuery("#cancel_description").show();					
                }
                else
                {
                    jQuery(".more_level_options").hide();
                    jQuery("#cancel_description").hide();					
                }
			}
			
			//run check when fields change
            $membership_level_select.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_select.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_month_select.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_day_text.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });
			$expires_year_text.change(function() {
                pmpro_checkForLevelChangeInProfile();
            });			
			
            jQuery("#cancel_subscription").change(function() {
                if(jQuery(this).attr('checked') == 'checked')
                {
                    jQuery("#cancel_description").hide();
                    jQuery("#current_level_cost").text("Not paying.");
                }
                else
                {
                    jQuery("#current_level_cost").text(current_level_cost);
                    jQuery("#cancel_description").show();
                }
            });
        });
    </script>
<?php
	do_action("pmpro_after_membership_level_profile_fields", $user);	
}

/*
	When applied, previous subscriptions won't be cancelled when changing membership levels.
	Use a function here instead of __return_false so we can easily turn add and remove it.
*/
function pmpro_cancel_previous_subscriptions_false()
{
	return false;
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
        //if the level is being set to 0 by the admin, it's a cancellation.
        $changed_or_cancelled = '';
        if($_REQUEST['membership_level'] === 0 ||$_REQUEST['membership_level'] === '0' || $_REQUEST['membership_level'] =='')
        {
            $changed_or_cancelled = 'admin_cancelled';
        }
        else
            $changed_or_cancelled = 'admin_changed';

		//if the cancel at gateway box is not checked, don't cancel 
		if(empty($_REQUEST['cancel_subscription']))
			add_filter('pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false');
				
		//do the change
        if(pmpro_changeMembershipLevel($_REQUEST['membership_level'], $user_ID, $changed_or_cancelled))
        {
            //it changed. send email
            $level_changed = true;
        }
		
		//remove filter after ward		
		if(empty($_REQUEST['cancel_subscription']))
			remove_filter('pmpro_cancel_previous_subscriptions', 'pmpro_cancel_previous_subscriptions_false');
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
		
	//emails if there was a change
	if(!empty($level_changed) || !empty($expiration_changed))
	{
		//email to admin
		$pmproemail = new PMProEmail();
		if(!empty($expiration_changed))
			$pmproemail->expiration_changed = true;
		$pmproemail->sendAdminChangeAdminEmail(get_userdata($user_ID));
		
		//send email
		if(!empty($_REQUEST['send_admin_change_email']))
		{
			//email to member
			$pmproemail = new PMProEmail();
			if(!empty($expiration_changed))
				$pmproemail->expiration_changed = true;
			$pmproemail->sendAdminChangeEmail(get_userdata($user_ID));	
		}
	}
}
add_action( 'show_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'edit_user_profile', 'pmpro_membership_level_profile_fields' );
add_action( 'profile_update', 'pmpro_membership_level_profile_fields_update' );
