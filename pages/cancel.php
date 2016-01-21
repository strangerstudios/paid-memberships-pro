<?php 
	global $pmpro_msg, $pmpro_msgt, $pmpro_confirm, $current_user;
	
	if(isset($_REQUEST['level']))
		$level = $_REQUEST['level'];
	else
		$level = false;
?>
<div id="pmpro_cancel">		
	<?php
		if($pmpro_msg) 
		{
			?>
			<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
			<?php
		}
	?>
	<?php 
		if(!$pmpro_confirm) 
		{ 
			if($level)
			{
				if($level == "all")
				{
					?>
					<p><?php _e('Are you sure you want to cancel your membership?', 'pmpro'); ?></p>
					<?php
				}
				else
				{
					?>
					<p><?php printf(__('Are you sure you want to cancel your %s membership?', 'pmpro'), $current_user->membership_level->name); ?></p>
					<?php
				}
			?>			
			<div class="pmpro_actionlinks">
				<a class="pmpro_btn pmpro_yeslink yeslink" href="<?php echo pmpro_url("cancel", "?confirm=true")?>"><?php _e('Yes, cancel my account', 'pmpro');?></a>
				<a class="pmpro_btn pmpro_cancel pmpro_nolink nolink" href="<?php echo pmpro_url("account")?>"><?php _e('No, keep my account', 'pmpro');?></a>
			</div>
			<?php
			}
			else
			{
				if($current_user->membership_level->ID) 
				{ 
					?>
					<hr />
					<h3><?php _e("My Memberships", "pmpro");?></h3>
					<table width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th><?php _e("Level", "pmpro");?></th>
								<th><?php _e("Expiration", "pmpro"); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="pmpro_cancel-membership-levelname">
									<?php echo $current_user->membership_level->name?>
								</td>
								<td class="pmpro_cancel-membership-expiration">
								<?php 
									if($current_user->membership_level->enddate) 
										echo date_i18n(get_option('date_format'), $current_user->membership_level->enddate);
									else
										echo "---";
								?>
								</td>
								<td class="pmpro_cancel-membership-cancel">
									<a href="<?php echo pmpro_url("cancel", "?level=" . $current_user->membership_level->id)?>"><?php _e("Cancel", "pmpro");?></a>
								</td>
							</tr>
						</tbody>
					</table>				
					<div class="pmpro_actionlinks">
						<a href="<?php echo pmpro_url("cancel", "?level=all"); ?>"><?php _e("Cancel All Memberships", "pmpro");?></a>
					</div>
					<?php
				}
			}
		}
		else 
		{ 
			?>
			<p><a href="<?php echo get_home_url()?>"><?php _e('Click here to go to the home page.', 'pmpro');?></a></p>
			<?php 
		} 
	?>		
</div> <!-- end pmpro_cancel -->