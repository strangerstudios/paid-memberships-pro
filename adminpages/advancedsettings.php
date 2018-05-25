<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_advancedsettings")))
	{
		die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
	}

	global $wpdb, $msg, $msgt, $allowedposttags;

	//check nonce for saving settings
	if (!empty($_REQUEST['savesettings']) && (empty($_REQUEST['pmpro_advancedsettings_nonce']) || !check_admin_referer('savesettings', 'pmpro_advancedsettings_nonce'))) {
		$msg = -1;
		$msgt = __("Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
		unset($_REQUEST['savesettings']);
	}
	
	//get/set settings
	if(!empty($_REQUEST['savesettings']))
	{
		//handle the text settings for better security handling		
		$nonmembertext = wp_kses(wp_unslash($_POST['nonmembertext']), $allowedposttags);
		update_option('pmpro_nonmembertext', $nonmembertext);
		
		$notloggedintext = wp_kses(wp_unslash($_POST['notloggedintext']), $allowedposttags);
		update_option('pmpro_notloggedintext', $notloggedintext);
		
		$rsstext = wp_kses(wp_unslash($_POST['rsstext']), $allowedposttags);
		update_option('pmpro_rsstext', $rsstext);		
		
		//other settings
		pmpro_setOption("filterqueries");
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

        /**
         * Filter to add custom settings to the advanced settings page.
         * @param array $settings Array of settings, each setting an array with keys field_name, field_type, label, description.
         */
        $custom_settings = apply_filters('pmpro_custom_advanced_settings', array());
        foreach($custom_settings as $setting) {
        	if(!empty($setting['field_name']))
        		pmpro_setOption($setting['field_name']);
        }
        
		//assume success
		$msg = true;
		$msgt = __("Your advanced settings have been updated.", 'paid-memberships-pro' );
	}

	$nonmembertext = pmpro_getOption("nonmembertext");
	$notloggedintext = pmpro_getOption("notloggedintext");
	$rsstext = pmpro_getOption("rsstext");
	$hideads = pmpro_getOption("hideads");
    $filterqueries = pmpro_getOption('filterqueries');
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
		$nonmembertext = sprintf( __( 'This content is for !!levels!! members only. <a href="%s">Register here</a>.', 'paid-memberships-pro' ), wp_login_url() . "?action=register" );
		pmpro_setOption("nonmembertext", $nonmembertext);
	}
	if(!$notloggedintext)
	{
		$notloggedintext = sprintf( __( 'Please <a href="%s">login</a> to view this content. (<a href="%s">Register here</a>.)', 'paid-memberships-pro' ), wp_login_url( get_permalink() ), wp_login_url() . "?action=register" );
		pmpro_setOption("notloggedintext", $notloggedintext);
	}
	if(!$rsstext)
	{
		$rsstext = __( 'This content is for members only. Visit the site and log in/register to read.', 'paid-memberships-pro' );
		pmpro_setOption("rsstext", $rsstext);
	}

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_advancedsettings_nonce');?>
		
		<h2><?php _e('Advanced Settings', 'paid-memberships-pro' );?></h2>

		<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="nonmembertext"><?php _e('Message for Logged-in Non-members', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<textarea name="nonmembertext" rows="3" cols="80"><?php echo stripslashes($nonmembertext)?></textarea><br />
					<small class="litegray"><?php _e('This message replaces the post content for non-members. Available variables', 'paid-memberships-pro' );?>: !!levels!!, !!referrer!!</small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="notloggedintext"><?php _e('Message for Logged-out Users', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<textarea name="notloggedintext" rows="3" cols="80"><?php echo stripslashes($notloggedintext)?></textarea><br />
					<small class="litegray"><?php _e('This message replaces the post content for logged-out visitors.', 'paid-memberships-pro' );?></small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="rsstext"><?php _e('Message for RSS Feed', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<textarea name="rsstext" rows="3" cols="80"><?php echo stripslashes($rsstext)?></textarea><br />
					<small class="litegray"><?php _e('This message replaces the post content in RSS feeds.', 'paid-memberships-pro' );?></small>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top">
					<label for="filterqueries"><?php _e("Filter searches and archives?", 'paid-memberships-pro' );?></label>
				</th>
				<td>
					<select id="filterqueries" name="filterqueries">
						<option value="0" <?php if(!$filterqueries) { ?>selected="selected"<?php } ?>><?php _e('No - Non-members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' );?></option>
						<option value="1" <?php if($filterqueries == 1) { ?>selected="selected"<?php } ?>><?php _e('Yes - Only members will see restricted posts/pages in searches and archives.', 'paid-memberships-pro' );?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="showexcerpts"><?php _e('Show Excerpts to Non-Members?', 'paid-memberships-pro' );?></label>
            </th>
            <td>
                <select id="showexcerpts" name="showexcerpts">
                    <option value="0" <?php if(!$showexcerpts) { ?>selected="selected"<?php } ?>><?php _e('No - Hide excerpts.', 'paid-memberships-pro' );?></option>
                    <option value="1" <?php if($showexcerpts == 1) { ?>selected="selected"<?php } ?>><?php _e('Yes - Show excerpts.', 'paid-memberships-pro' );?></option>
                </select>
            </td>
            </tr>
            <tr>
				<th scope="row" valign="top">
					<label for="hideads"><?php _e("Hide Ads From Members?", 'paid-memberships-pro' );?></label>
				</th>
				<td>
					<select id="hideads" name="hideads" onchange="pmpro_updateHideAdsTRs();">
						<option value="0" <?php if(!$hideads) { ?>selected="selected"<?php } ?>><?php _e('No', 'paid-memberships-pro' );?></option>
						<option value="1" <?php if($hideads == 1) { ?>selected="selected"<?php } ?>><?php _e('Hide Ads From All Members', 'paid-memberships-pro' );?></option>
						<option value="2" <?php if($hideads == 2) { ?>selected="selected"<?php } ?>><?php _e('Hide Ads From Certain Members', 'paid-memberships-pro' );?></option>
					</select>
				</td>
			</tr>
			<tr id="hideads_explanation" <?php if($hideads < 2) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">&nbsp;</th>
				<td>
					<p class="top0em"><?php _e('Ads from the following plugins will be automatically turned off', 'paid-memberships-pro' );?>: <em>Easy Adsense</em>, ...</p>
					<p><?php _e('To hide ads in your template code, use code like the following', 'paid-memberships-pro' );?>:</p>
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
					<label for="hideadslevels"><?php _e('Choose Levels to Hide Ads From', 'paid-memberships-pro' );?>:</label>
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
					<label for="redirecttosubscription"><?php _e('Redirect all traffic from registration page to /susbcription/?', 'paid-memberships-pro' );?>: <em>(<?php _e('multisite only', 'paid-memberships-pro' );?>)</em></label>
				</th>
				<td>
					<select id="redirecttosubscription" name="redirecttosubscription">
						<option value="0" <?php if(!$redirecttosubscription) { ?>selected="selected"<?php } ?>><?php _e('No', 'paid-memberships-pro' );?></option>
						<option value="1" <?php if($redirecttosubscription == 1) { ?>selected="selected"<?php } ?>><?php _e('Yes', 'paid-memberships-pro' );?></option>
					</select>
				</td>
			</tr>
			<?php } ?>
			<tr>
				<th scope="row" valign="top">
					<label for="recaptcha"><?php _e('Use reCAPTCHA?', 'paid-memberships-pro' );?>:</label>
				</th>
				<td>
					<select id="recaptcha" name="recaptcha" onchange="pmpro_updateRecaptchaTRs();">
						<option value="0" <?php if(!$recaptcha) { ?>selected="selected"<?php } ?>><?php _e('No', 'paid-memberships-pro' );?></option>
						<option value="1" <?php if($recaptcha == 1) { ?>selected="selected"<?php } ?>><?php _e('Yes - Free memberships only.', 'paid-memberships-pro' );?></option>
						<option value="2" <?php if($recaptcha == 2) { ?>selected="selected"<?php } ?>><?php _e('Yes - All memberships.', 'paid-memberships-pro' );?></option>
					</select><br />
					<small><?php _e('A free reCAPTCHA key is required.', 'paid-memberships-pro' );?> <a href="https://www.google.com/recaptcha/admin/create"><?php _e('Click here to signup for reCAPTCHA', 'paid-memberships-pro' );?></a>.</small>
				</td>
			</tr>
			<tr id="recaptcha_tr" <?php if(!$recaptcha) { ?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top">&nbsp;</th>
				<td>
					<label for="recaptcha_publickey"><?php _e('reCAPTCHA Site Key', 'paid-memberships-pro' );?>:</label>
					<input type="text" id="recaptcha_publickey" name="recaptcha_publickey" size="60" value="<?php echo esc_attr($recaptcha_publickey);?>" />
					<br /><br />
					<label for="recaptcha_privatekey"><?php _e('reCAPTCHA Secret Key', 'paid-memberships-pro' );?>:</label>
					<input type="text" id="recaptcha_privatekey" name="recaptcha_privatekey" size="60" value="<?php echo esc_attr($recaptcha_privatekey);?>" />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="tospage"><?php _e('Require Terms of Service on signups?', 'paid-memberships-pro' );?></label>
				</th>
				<td>
					<?php
						wp_dropdown_pages(array("name"=>"tospage", "show_option_none"=>"No", "selected"=>$tospage));
					?>
					<br />
					<small><?php _e('If yes, create a WordPress page containing your TOS agreement and assign it using the dropdown above.', 'paid-memberships-pro' );?></small>
				</td>
			</tr>

			<?php
            // Filter to Add More Advanced Settings for Misc Plugin Options, etc.
            if (has_action('pmpro_custom_advanced_settings')) {
	            $custom_fields = apply_filters('pmpro_custom_advanced_settings', array());
	            foreach ($custom_fields as $field) {
	            ?>
	            <tr>
	                <th valign="top" scope="row">
	                    <label
	                        for="<?php echo esc_attr( $field['field_name'] ); ?>"><?php echo esc_textarea( $field['label'] ); ?></label>
	                </th>
	                <td>
	                    <?php
	                    switch ($field['field_type']) {
	                        case 'select':
	                            ?>
	                            <select id="<?php echo esc_attr( $field['field_name'] ); ?>"
	                                    name="<?php echo esc_attr( $field['field_name'] ); ?>">
	                                <?php 
	                                	//For associative arrays, we use the array keys as values. For numerically indexed arrays, we use the array values.
	                                	$is_associative = (bool)count(array_filter(array_keys($field['options']), 'is_string'));
	                                	foreach ($field['options'] as $key => $option) {
	                                    	if(!$is_associative) $key = $option;
	                                    	?>
	                                    	<option value="<?php echo esc_attr($key); ?>" <?php selected($key, pmpro_getOption($field['field_name']));?>>
	                                    		<?php echo esc_textarea($option); ?>
	                                    	</option>
	                               			<?php
	                                	} 
	                                ?>
	                            </select>
	                            <?php
	                            break;
	                        case 'text':
	                            ?>
	                            <input id="<?php echo esc_attr( $field['field_name'] ); ?>"
	                                   name="<?php echo esc_attr( $field['field_name'] ); ?>"
	                                   type="<?php echo esc_attr( $field['field_type'] ); ?>"
	                                   value="<?php echo esc_attr(pmpro_getOption($field['field_name'])); ?> ">
	                            <?php
	                            break;
	                        case 'textarea':
	                            ?>
	                            <textarea id="<?php echo esc_attr( $field['field_name'] ); ?>"
	                                      name="<?php echo esc_attr( $field['field_name'] ); ?>">
	                                <?php echo esc_textarea(pmpro_getOption($field['field_name'])); ?>
	                            </textarea>
	                            <?php
	                            break;
	                        default:
	                            break;
	                    }
	                    if (!empty($field['description'])) {
	                        ?>
	                        <br>
	                        <small><?php echo esc_textarea( $field['description'] ); ?></small>
	                    <?php
	                    }
	                    ?>
	                </td>
	            </tr>
	            <?php
	            }
	        } 
	        ?>
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
			<input name="savesettings" type="submit" class="button button-primary" value="<?php _e('Save Settings', 'paid-memberships-pro' );?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");
?>
