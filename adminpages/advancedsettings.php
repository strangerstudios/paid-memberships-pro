<?php
	global $wpdb, $msg, $msgt;
	
	//get/set settings	
	if(!empty($_REQUEST['savesettings']))
	{                   		
		//other settings
		pmpro_setOption("nonmembertext");
		pmpro_setOption("notloggedintext");
		pmpro_setOption("rsstext");		
		pmpro_setOption("showexcerpts");
		pmpro_setOption("hideads");
		pmpro_setOption("hideadslevels");
		pmpro_setOption("redirecttosubscription");					
						
		//captcha
		pmpro_setOption("recaptcha");
		pmpro_setOption("recaptcha_publickey");
		pmpro_setOption("recaptcha_privatekey");					
		
		//tos
		pmpro_setOption("tospage");		
		
		//footer link
		pmpro_setOption("hide_footer_link");
		
		//assume success
		$msg = true;
		$msgt = "Your advanced settings have been updated.";	
	}

	$nonmembertext = pmpro_getOption("nonmembertext");
	$notloggedintext = pmpro_getOption("notloggedintext");
	$rsstext = pmpro_getOption("rsstext");	
	$hideads = pmpro_getOption("hideads");
	$showexcerpts = pmpro_getOption("showexcerpts");
	$hideadslevels = pmpro_getOption("hideadslevels");
	
	if(is_multisite())
		$redirecttosubscription = pmpro_getOption("redirecttosubscription");
	
	$recaptcha = pmpro_getOption("recaptcha");
	$recaptcha_publickey = pmpro_getOption("recaptcha_publickey");
	$recaptcha_privatekey = pmpro_getOption("recaptcha_privatekey");
	
	$tospage = pmpro_getOption("tospage");
	
	$hide_footer_link = pmpro_getOption("hide_footer_link");
		
	//default settings
	if(!$nonmembertext)
	{
		$nonmembertext = "This content is for !!levels!! members only. <a href=\"" . wp_login_url() . "?action=register\">Register here</a>.";
		pmpro_setOption("nonmembertext", $nonmembertext);
	}			
	if(!$notloggedintext)
	{
		$notloggedintext = "Please <a href=\"" . wp_login_url( get_permalink() ) . "\">login</a> to view this content. (<a href=\"" . wp_login_url() . "?action=register\">Register here</a>.)";
		pmpro_setOption("notloggedintext", $notloggedintext);
	}			
	if(!$rsstext)
	{
		$rsstext = "This content is for members only. Visit the site and log in/register to read.";
		pmpro_setOption("rsstext", $rsstext);
	}   				
		
	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );
	
	require_once(dirname(__FILE__) . "/admin_header.php");		
?>

	<form action="" method="post" enctype="multipart/form-data"> 
		<h2>Advanced Settings</h2>
						
		<table class="form-table">
		<tbody>                
			<tr>
				<th scope="row" valign="top">
					<label for="nonmembertext">Message for Logged-in Non-members:</label>
				</th>
				<td>
					<textarea name="nonmembertext" rows="3" cols="80"><?php echo stripslashes($nonmembertext)?></textarea><br />
					<small class="litegray">This message replaces the post content for non-members. Available variables: !!levels!!, !!referrer!!</small>
				</td>
			</tr> 
			<tr>
				<th scope="row" valign="top">
					<label for="notloggedintext">Message for Logged-out Users:</label>
				</th>
				<td>
					<textarea name="notloggedintext" rows="3" cols="80"><?php echo stripslashes($notloggedintext)?></textarea><br />
					<small class="litegray">This message replaces the post content for logged-out visitors.</small>
				</td>
			</tr> 
			<tr>
				<th scope="row" valign="top">
					<label for="rsstext">Message for RSS Feed:</label>
				</th>
				<td>
					<textarea name="rsstext" rows="3" cols="80"><?php echo stripslashes($rsstext)?></textarea><br />
					<small class="litegray">This message replaces the post content in RSS feeds.</small>
				</td>
			</tr> 
			
			<tr>
				<th scope="row" valign="top">
					<label for="showexcerpts">Show Excerpts to Non-Members?</label>
				</th>
				<td>
					<select id="showexcerpts" name="showexcerpts">
						<option value="0" <?php if(!$showexcerpts) { ?>selected="selected"<?php } ?>>No - Hide excerpts.</option>
						<option value="1" <?php if($showexcerpts == 1) { ?>selected="selected"<?php } ?>>Yes - Show excerpts.</option>  
					</select>                        
				</td>
			</tr> 
			<tr>
				<th scope="row" valign="top">
					<label for="hideads">Hide Ads From Members?</label>
				</th>
				<td>
					<select id="hideads" name="hideads" onchange="pmpro_updateHideAdsTRs();">
						<option value="0" <?php if(!$hideads) { ?>selected="selected"<?php } ?>>No</option>
						<option value="1" <?php if($hideads == 1) { ?>selected="selected"<?php } ?>>Hide Ads From All Members</option>
						<option value="2" <?php if($hideads == 2) { ?>selected="selected"<?php } ?>>Hide Ads From Certain Members</option>
					</select>                        
				</td>
			</tr> 				
			<tr id="hideads_explanation" <?php if($hideads < 2) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">&nbsp;</th>
				<td>
					<p class="top0em">Ads from the following plugins will be automatically turned off: <em>Easy Adsense</em>, ...</p>
					<p>To hide ads in your template code, use code like the following:</p>
				<pre lang="PHP">
if(pmpro_displayAds())
{
//insert ad code here
}
				</pre>                   
				</td>
			</tr>                           
			<tr id="hideadslevels_tr" <?php if($hideads != 2) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">
					<label for="hideadslevels">Choose Levels to Hide Ads From:</label>
				</th>
				<td>
					<div class="checkbox_box" <?php if(count($levels) > 5) { ?>style="height: 100px; overflow: auto;"<?php } ?>>
						<?php 																
							$hideadslevels = pmpro_getOption("hideadslevels");
							if(!is_array($hideadslevels))
								$hideadslevels = explode(",", $hideadslevels);
							
							$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";						
							$levels = $wpdb->get_results($sqlQuery, OBJECT);								
							foreach($levels as $level) 
							{ 
						?>
							<div class="clickable"><input type="checkbox" id="hideadslevels_<?php echo $level->id?>" name="hideadslevels[]" value="<?php echo $level->id?>" <?php if(in_array($level->id, $hideadslevels)) { ?>checked="checked"<?php } ?>> <?php echo $level->name?></div>
						<?php 
							} 
						?>
					</div> 
					<script>
						jQuery('.checkbox_box input').click(function(event) {
							event.stopPropagation()
						});

						jQuery('.checkbox_box div.clickable').click(function() {							
							var checkbox = jQuery(this).find(':checkbox');
							checkbox.attr('checked', !checkbox.attr('checked'));
						});
					</script>
				</td>
			</tr> 
			<?php if(is_multisite()) { ?>
			<tr>
				<th scope="row" valign="top">
					<label for="redirecttosubscription">Redirect all traffic from registration page to /susbcription/?: <em>(multisite only)</em></label>
				</th>
				<td>
					<select id="redirecttosubscription" name="redirecttosubscription">
						<option value="0" <?php if(!$redirecttosubscription) { ?>selected="selected"<?php } ?>>No</option>
						<option value="1" <?php if($redirecttosubscription == 1) { ?>selected="selected"<?php } ?>>Yes</option>                           
					</select>                        
				</td>
			</tr> 
			<?php } ?>				
			<tr>
				<th scope="row" valign="top">
					<label for="recaptcha">Use reCAPTCHA?:</label>
				</th>
				<td>
					<select id="recaptcha" name="recaptcha" onchange="pmpro_updateRecaptchaTRs();">
						<option value="0" <?php if(!$recaptcha) { ?>selected="selected"<?php } ?>>No</option>
						<option value="1" <?php if($recaptcha == 1) { ?>selected="selected"<?php } ?>>Yes - Free memberships only.</option>    
						<option value="2" <?php if($recaptcha == 2) { ?>selected="selected"<?php } ?>>Yes - All memberships.</option>
					</select><br />
					<small>A free reCAPTCHA key is required. <a href="https://www.google.com/recaptcha/admin/create">Click here to signup for reCAPTCHA</a>.</small>						
				</td>
			</tr> 
			<tr id="recaptcha_tr" <?php if(!$recaptcha) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">&nbsp;</th>
				<td>                        
					<label for="recaptcha_publickey">reCAPTCHA Public Key:</label>
					<input type="text" name="recaptcha_publickey" size="60" value="<?php echo $recaptcha_publickey?>" />
					<br /><br />
					<label for="recaptcha_privatekey">reCAPTCHA Private Key:</label>
					<input type="text" name="recaptcha_privatekey" size="60" value="<?php echo $recaptcha_privatekey?>" />						
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="tospage">Require Terms of Service on signups?</label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"tospage", "show_option_none"=>"No", "selected"=>$tospage));
					?>
					<br />
					<small>If yes, create a WordPress page containing your TOS agreement and assign it using the dropdown above.</small>
				</td>
			</tr> 
			
			<?php /*
			<tr>
				<th scope="row" valign="top">
					<label for="hide_footer_link">Hide the PMPro Link in the Footer?</label>
				</th>
				<td>
					<select id="hide_footer_link" name="hide_footer_link">
						<option value="0" <?php if(!$hide_footer_link) { ?>selected="selected"<?php } ?>>No - Leave the link. (Thanks!)</option>
						<option value="1" <?php if($hide_footer_link == 1) { ?>selected="selected"<?php } ?>>Yes - Hide the link.</option>  
					</select>                        
				</td>
			</tr> 
			*/ ?>
		</tbody>
		</table>
		<script>
			function pmpro_updateHideAdsTRs()
			{
				var hideads = jQuery('#hideads').val();
				if(hideads == 2) 
				{
					jQuery('#hideadslevels_tr').show();
				} 
				else
				{
					jQuery('#hideadslevels_tr').hide();
				}
				
				if(hideads > 0) 
				{
					jQuery('#hideads_explanation').show();
				} 
				else
				{
					jQuery('#hideads_explanation').hide();
				}
			}
			pmpro_updateHideAdsTRs();
			
			function pmpro_updateRecaptchaTRs()
			{
				var recaptcha = jQuery('#recaptcha').val();
				if(recaptcha > 0) 
				{
					jQuery('#recaptcha_tr').show();
				} 
				else
				{
					jQuery('#recaptcha_tr').hide();
				}										
			}
			pmpro_updateRecaptchaTRs();
		</script>
		
		<p class="submit">            
			<input name="savesettings" type="submit" class="button-primary" value="Save Settings" /> 		                			
		</p> 
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
