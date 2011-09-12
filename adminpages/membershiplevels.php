<?php
	global $wpdb;
	$edit = $_REQUEST['edit'];
	$view = $_REQUEST['view'];
	$copy = $_REQUEST['copy'];
	$s = $_REQUEST['s'];
	
	//get/set settings
	global $pmpro_pages;
	if($_REQUEST['savesettings'])
	{                   
		$view = $_REQUEST['view'];
		
		if($view == "pages")
		{
			//page ids
			pmpro_setOption("account_page_id");
			pmpro_setOption("billing_page_id");
			pmpro_setOption("cancel_page_id");
			pmpro_setOption("checkout_page_id");
			pmpro_setOption("confirmation_page_id");
			pmpro_setOption("invoice_page_id");
			pmpro_setOption("levels_page_id");
			
			//update the pages array
			$pmpro_pages["account"] = pmpro_getOption("account_page_id");
			$pmpro_pages["billing"] = pmpro_getOption("billing_page_id");
			$pmpro_pages["cancel"] = pmpro_getOption("cancel_page_id");
			$pmpro_pages["checkout"] = pmpro_getOption("checkout_page_id");
			$pmpro_pages["confirmation"] = pmpro_getOption("confirmation_page_id");
			$pmpro_pages["invoice"] = pmpro_getOption("invoice_page_id");
			$pmpro_pages["levels"] = pmpro_getOption("levels_page_id");
		}
	
		if($view == "payment")
		{
			pmpro_setOption("sslseal");
			
			//gateway options
			pmpro_setOption("gateway");					
			pmpro_setOption("gateway_environment");
			pmpro_setOption("gateway_email");
			pmpro_setOption("apiusername");
			pmpro_setOption("apipassword");
			pmpro_setOption("apisignature");
			pmpro_setOption("loginname");
			pmpro_setOption("transactionkey");

			//credit cards
			$pmpro_accepted_credit_cards = array();
			if($_REQUEST['creditcards_visa'])
				$pmpro_accepted_credit_cards[] = "Visa";
			if($_REQUEST['creditcards_mastercard'])
				$pmpro_accepted_credit_cards[] = "Mastercard";
			if($_REQUEST['creditcards_amex'])
				$pmpro_accepted_credit_cards[] = "American Express";
			if($_REQUEST['creditcards_discover'])
				$pmpro_accepted_credit_cards[] = "Discover";
			
			//tax
			pmpro_setOption("tax_state");
			pmpro_setOption("tax_rate");
			
			pmpro_setOption("accepted_credit_cards", implode(",", $pmpro_accepted_credit_cards));
		}
		
		if($view == "email")
		{
			//email options
			pmpro_setOption("from_email");
			pmpro_setOption("from_name");
		}
				
		if($view == "advanced")
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
		}
	}

	$nonmembertext = pmpro_getOption("nonmembertext");
	$notloggedintext = pmpro_getOption("notloggedintext");
	$rsstext = pmpro_getOption("rsstext");
	$sslseal = pmpro_getOption("sslseal");
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
	
	$from_email = pmpro_getOption("from_email");
	$from_name = pmpro_getOption("from_name");
	
	$gateway = pmpro_getOption("gateway");
	$gateway_environment = pmpro_getOption("gateway_environment");
	$gateway_email = pmpro_getOption("gateway_email");
	$apiusername = pmpro_getOption("apiusername");
	$apipassword = pmpro_getOption("apipassword");
	$apisignature = pmpro_getOption("apisignature");
	$loginname = pmpro_getOption("loginname");
	$transactionkey = pmpro_getOption("transactionkey");
	
	$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
	
	$tax_state = pmpro_getOption("tax_state");
	$tax_rate = pmpro_getOption("tax_rate");
	
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
	if(!$gateway_environment)
	{
		$gateway_environment = "sandbox";
		pmpro_setOption("gateway_environment", $gateway_environment);
	}
	if(!$pmpro_accepted_credit_cards)
	{
		$pmpro_accepted_credit_cards = "Visa,Mastercard,American Express,Discover";
		pmpro_setOption("accepted_credit_cards", $pmpro_accepted_credit_cards);		
	}
	
	$pmpro_accepted_credit_cards = split(",", $pmpro_accepted_credit_cards);
			
	if(!$from_email)
	{
		$parsed = parse_url(home_url()); 
		$hostname = $parsed[host];
		$hostparts = split("\.", $hostname);				
		$email_domain = $hostparts[count($hostparts) - 2] . "." . $hostparts[count($hostparts) - 1];		
		$from_email = "wordpress@" . $email_domain;
		pmpro_setOption("from_email", $from_email);
	}
	
	if(!$from_name)
	{		
		$from_name = "WordPress";
		pmpro_setOption("from_name", $from_name);
	}
		
	
	//are we generating pages?
	if($_REQUEST['createpages'])
	{
		global $pmpro_pages;
		
		$pages_created = array();
		
		//check the pages array
		foreach($pmpro_pages as $pmpro_page_name => $pmpro_page_id)
		{
			if(!$pmpro_page_id)
			{
				//no id set. create an array to store the page info
				$insert = array(
					'post_title' => 'Membership ' . ucwords($pmpro_page_name),
					'post_status' => 'publish',
					'post_type' => 'page',
					'post_content' => '[pmpro_' . $pmpro_page_name . ']',
					'comment_status' => 'closed',
					'ping_status' => 'closed'
					);
				//create the page
				$pmpro_pages[$pmpro_page_name] = wp_insert_post( $insert );
				
				//add besecure post option to pages that need it
				/* these pages are handling this themselves in the preheader
				if(in_array($pmpro_page_name, array("billing", "checkout")))
					update_post_meta($pmpro_pages[$pmpro_page_name], "besecure", 1);								
				*/
					
				//update the option too
				pmpro_setOption($pmpro_page_name . "_page_id", $pmpro_pages[$pmpro_page_name]);
				$pages_created[] = $pmpro_pages[$pmpro_page_name];
			}
		}
		
		if($pages_created)
		{
			$msg = true;
			$msgt = "The following pages have been created for you: " . implode(", ", $pages_created) . ".";
		}
	}
	
	//any messages?
	if(!$msg)
		$msg = $_REQUEST['msg'];
	if($msg === "1")
		$msgt = "Membership level added successfully.";
	elseif($msg === "-1")
		$msgt = "Error adding membership level.";
	elseif($msg === "2")
		$msgt = "Membership level updated successfully.";
	elseif($msg === "-2")
		$msgt = "Error updating membership level.";	
	elseif($msg === "3")
		$msgt = "Membership level deleted successfully.";
	elseif($msg === "-3")
		$msgt = "Error deleting membership level.";	
	
	global $pmpro_ready;
	$pmpro_ready = pmpro_is_ready();
	if(!$pmpro_ready)
	{
		global $pmpro_level_ready, $pmpro_gateway_ready, $pmpro_pages_ready;
		$view = $_REQUEST['view'];
		$edit = $_REQUEST['edit'];
		if(!$msg)
			$msg = -1;		
		if(!$pmpro_level_ready && !$edit)
			$msgt .= " <a href=\"?page=pmpro-membershiplevels&edit=-1\">Add a membership level</a> to get started.";
		elseif($pmpro_level_ready && !$pmpro_pages_ready && $view != "pages")
			$msgt .= " <a href=\"?page=pmpro-membershiplevels&view=pages\">Setup the membership pages</a>.";		
		elseif($pmpro_level_ready && $pmpro_pages_ready && !$pmpro_gateway_ready && $view != "payment")
			$msgt .= " <a href=\"?page=pmpro-membershiplevels&view=payment\">Setup your SSL certificate and payment gateway</a>.";
			
		if(!$msgt)
			$msg = false;
	}
	
	if($msg)
	{
	?>
		<div id="message" class="<?php if($msg > 0) echo "updated fade"; else echo "error"; ?>"><p><?=$msgt?></p></div>
	<?php
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
	
	<h3 class="nav-tab-wrapper">
		<a href="admin.php?page=pmpro-membershiplevels" class="nav-tab<?php if(!$view || $edit) { ?> nav-tab-active<?php } ?>">Membership Levels</a>
		<a href="admin.php?page=pmpro-membershiplevels&view=pages" class="nav-tab<?php if($view == 'pages') { ?> nav-tab-active<?php } ?>">Pages</a>
		<a href="admin.php?page=pmpro-membershiplevels&view=payment" class="nav-tab<?php if($view == 'payment') { ?> nav-tab-active<?php } ?>">SSL &amp; Payment Gateway</a>
		<a href="admin.php?page=pmpro-membershiplevels&view=email" class="nav-tab<?php if($view == 'email') { ?> nav-tab-active<?php } ?>">Email</a>
		<a href="admin.php?page=pmpro-membershiplevels&view=advanced" class="nav-tab<?php if($view == 'advanced') { ?> nav-tab-active<?php } ?>">Advanced</a>	
	</h3>
	
	<?php	
		if($edit)
		{			
		?>
			
		<h2>
			<?php
				if($edit > 0)
					echo "Edit Membership Level";
				else
					echo "Add New Membership Level";
			?>
		</h2>
			
		<div>
			<?php
				// get the level...
				if($edit > 0)
				{
					$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '$edit' LIMIT 1", OBJECT);
					$temp_id = $level->id;
				}
				elseif($copy > 0)		
				{	
					$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '$copy' LIMIT 1", OBJECT);
					$temp_id = $level->id;
					$level->id = NULL;
				}

				// didn't find a membership level, let's add a new one...
				if(!$level) $edit = -1;

				//defaults for new levels
				if($edit == -1)
				{
					$level->cycle_number = 1;
					$level->cycle_period = "Month";
				}
				
				// grab the categories for the given level...
				$level->categories = $wpdb->get_col("SELECT c.category_id
													FROM $wpdb->pmpro_memberships_categories c
													WHERE c.membership_id = '" . $temp_id . "'");       		
				if(!$level->categories)
					$level->categories = array();			
			?>
			<form action="<?=PMPRO_URL?>/services/pmpro-data.php?action=save_membershiplevel" method="post" enctype="multipart/form-data">
				<input name="saveid" type="hidden" value="<?=$edit?>" />
				<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label>ID:</label></th>
                        <td><?=$level->id?></td>
                    </tr>								                
                    
                    <tr>
                        <th scope="row" valign="top"><label for="name">Name:</label></th>
                        <td><input name="name" type="text" size="50" value="<?=str_replace("\"", "&quot;", stripslashes($level->name))?>" /></td>
                    </tr>
                    
                    <tr>
                        <th scope="row" valign="top"><label for="description">Description:</label></th>
                        <td>
	                        <div id="poststuff" class="pmpro_description">
							<textarea rows="10" cols="80" name="description" id="description"><?=str_replace("\"", "&quot;", stripslashes($level->description))?></textarea>							
                        	</div>    
                        </td>
                    </tr>
				</tbody>
			</table>
			
			<h3 class="topborder">Billing Details</h3>
			<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label for="initial_payment">Initial Payment:</label></th>
                        <td>$<input name="initial_payment" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($level->initial_payment))?>" /> <small>The initial amount collected at registration.</small></td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label>Recurring Subscription:</label></th>
                        <td><input id="recurring" name="recurring" type="checkbox" value="yes" <?php if(pmpro_isLevelRecurring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery('#recurring').is(':checked')) { jQuery('.recurring_info').show(); if(jQuery('#custom_trial').is(':checked')) {jQuery('.trial_info').show();} else {jQuery('.trial_info').hide();} } else { jQuery('.recurring_info').hide();}" /> <small>Check if this level has a recurring subscription payment.</small></td>
                    </tr>
					
					<tr class="recurring_info" <?php if(!pmpro_isLevelRecurring($level)) {?>style="display: none;"<?php } ?>>
                        <th scope="row" valign="top"><label for="billing_amount">Billing Amount:</label></th>
                        <td>
							$<input name="billing_amount" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($level->billing_amount))?>" /> <small>per</small>
							<input id="cycle_number" name="cycle_number" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($level->cycle_number))?>" />
							<select id="cycle_period" name="cycle_period">
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
							<input name="billing_limit" type="text" size="20" value="<?=$level->billing_limit?>" />
							<br /><small>The <strong>total</strong> number of recurring billing cycles for this level, including the trial period (if applicable) but not including the initial payment. Set to zero if membership is indefinite.</small>
						</td>
                    </tr>            								
	
                    <tr class="recurring_info" <?php if (!pmpro_isLevelRecurring($level)) echo "style='display:none;'";?>>
                        <th scope="row" valign="top"><label>Custom Trial:</label></th>
                        <td><input id="custom_trial" name="custom_trial" type="checkbox" value="yes" <?php if ( pmpro_isLevelTrial($level) ) { echo "checked='checked'"; } ?> onclick="jQuery('.trial_info').toggle();" /> Check to add a custom trial period.</td>
                    </tr>
    
                    <tr class="trial_info recurring_info" <?php if (!pmpro_isLevelTrial($level)) echo "style='display:none;'";?>>
                        <th scope="row" valign="top"><label for="trial_amount">Trial Billing Amount:</label></th>
                        <td>
							$<input name="trial_amount" type="text" size="20" value="<?=str_replace("\"", "&quot;", stripslashes($level->trial_amount))?>" />
							<small>for the first</small>
							<input name="trial_limit" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($level->trial_limit))?>" />
                            <small>subscription payments.</small>																			
						</td>
                    </tr>
										 
				</tbody>
			</table>
			<h3 class="topborder">Other Settings</h3>
			<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label>Disable New Signups:</label></th>
                        <td><input name="disable_signups" type="checkbox" value="yes" <?php if($level->id && !$level->allow_signups) { ?>checked="checked"<?php } ?> /> Check to hide this level from the membership levels page and disable registration.</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label>Membership Expiration:</label></th>
                        <td><input id="expiration" name="expiration" type="checkbox" value="yes" <?php if(pmpro_isLevelExpiring($level)) { echo "checked='checked'"; } ?> onclick="if(jQuery('#expiration').is(':checked')) { jQuery('.expiration_info').show(); } else { jQuery('.expiration_info').hide();}" /> <small>Check this to set an expiration date for new sign ups.</small></td>
                    </tr>
					
					<tr class="expiration_info" <?php if(!pmpro_isLevelExpiring($level)) {?>style="display: none;"<?php } ?>>
                        <th scope="row" valign="top"><label for="billing_amount">Expire In:</label></th>
                        <td>							
							<input id="expiration_number" name="expiration_number" type="text" size="10" value="<?=str_replace("\"", "&quot;", stripslashes($level->expiration_number))?>" />
							<select id="expiration_period" name="expiration_period">
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
			<h3 class="topborder">Content Settings</h3>
			<table class="form-table">
				<tbody>
                    <tr>
                        <th scope="row" valign="top"><label>Categories:</label></th>
                        <td>
                            <?php
                            $categories = get_categories( array( 'hide_empty' => 0 ) );
                            echo "<ul>";
                            foreach ( $categories as $cat )
                            {                               								
								$checked = in_array( $cat->term_id, $level->categories ) ? "checked='checked'" : '';
                                echo "<li><input name='membershipcategory_{$cat->term_id}' type='checkbox' value='yes' $checked /> {$cat->name}</li>\n";
                            }
                            echo "</ul>";
                            ?>
                        </td>
                    </tr>
                </tbody>
			</table>			
			<p class="submit topborder">
				<input name="save" type="submit" class="button-primary" value="Save Level" /> 					
				<input name="cancel" type="button" value="Cancel" onclick="location.href='<?=home_url('/wp-admin/admin.php?page=pmpro-membershiplevels')?>';" /> 					
			</p>
		</form>
		</div>
			
		<?php
		}
		elseif($view == 'pages')
		{
		?>		
		<form action="admin.php?page=pmpro-membershiplevels&view=pages" method="post" enctype="multipart/form-data">        	        			
			<h2>Pages</h2> 
			<?php
				global $pmpro_pages_ready;
				if($pmpro_pages_ready)
				{
				?>
					<p>Manage the WordPress pages assigned to each required Paid Memberships Pro page.</p>
				<?php
				} 
				else 
				{ 
				?>
					<p>Assign the WordPress pages for each required Paid Memberships Pro page or <a href="?page=pmpro-membershiplevels&view=pages&createpages=1">click here to let us generate them for you</a>.</p>
				<?php
				}
			?>       			        
            <table class="form-table">
            <tbody>                
                <tr>
                    <th scope="row" valign="top">                        
						<label for="account_page_id">Account Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"account_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[account]));
						?>	
						<?php if($pmpro_pages[account]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[account]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_account].</small>
					</td>
				<tr>
                    <th scope="row" valign="top">
						<label for="billing_page_id">Billing Information Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"billing_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[billing]));
						?>
						<?php if($pmpro_pages[billing]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[billing]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_billing].</small>
					</td>
				<tr>
                    <th scope="row" valign="top">	
						<label for="cancel_page_id">Cancel Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"cancel_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[cancel]));
						?>	
						<?php if($pmpro_pages[cancel]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[cancel]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_cancel].</small>
					</td>
				</tr>
				<tr>
                    <th scope="row" valign="top">	
						<label for="checkout_page_id">Checkout Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"checkout_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[checkout]));
						?>
						<?php if($pmpro_pages[checkout]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[checkout]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_checkout].</small>
					</td>
				</tr>
				<tr>
                    <th scope="row" valign="top">		
						<label for="confirmation_page_id">Confirmation Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"confirmation_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[confirmation]));
						?>	
						<?php if($pmpro_pages[confirmation]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[confirmation]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_confirmation].</small>
					</td>
				</tr>
				<tr>
                    <th scope="row" valign="top">	
						<label for="invoice_page_id">Invoice Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"invoice_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[invoice]));
						?>
						<?php if($pmpro_pages[invoice]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[invoice]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_invoice].</small>
					</td>
				</tr>
				<tr>
                    <th scope="row" valign="top">	
						<label for="levels_page_id">Levels Page:</label>
					</th>
					<td>
                        <?php
							wp_dropdown_pages(array("name"=>"levels_page_id", "show_option_none"=>"-- Choose One --", "selected"=>$pmpro_pages[levels]));
						?>
						<?php if($pmpro_pages[levels]) { ?>
							<a target="_blank" href="post.php?post=<?=$pmpro_pages[levels]?>&action=edit" class="pmpro_page_edit">edit page</a>
						<?php } ?>
						<br /><small class="pmpro_lite">Include the shortcode [pmpro_levels].</small>
					</td>
				</tr>				
			</tbody>
			</table>
			<p class="submit">            
                <input name="savesettings" type="submit" class="button-primary" value="Save Settings" /> 		                			
            </p> 			
		</form>
		<?php
		}
		elseif($view == 'payment')
		{
		?>
		<form action="admin.php?page=pmpro-membershiplevels&view=payment" method="post" enctype="multipart/form-data">         
			<h2>SSL &amp; Payment Gateway Settings</h2>
            
			<p>Learn more about <a title="Paid Memberships Pro - SSL Settings" target="_blank" href="http://www.paidmembershipspro.com/support/initial-plugin-setup/ssl/">SSL</a> or <a title="Paid Memberships Pro - Payment Gateway Settings" target="_blank" href="http://www.paidmembershipspro.com/support/initial-plugin-setup/payment-gateway/">Payment Gateway Settings</a>.</p>
			
            <table class="form-table">
            <tbody>                
               <tr>
                    <th scope="row" valign="top">
                    	<label for="sslseal">SSL Seal Code:</label>
                    </th>
					<td>
						<textarea name="sslseal" rows="3" cols="80"><?=stripslashes($sslseal)?></textarea>
                    </td>
               </tr>
			   <tr>
                    <th scope="row" valign="top">	
                        <label for="gateway">Payment Gateway:</label>
					</th>
					<td>
                        <select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
							<option value="">-- choose one --</option>
                        	<option value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>selected="selected"<?php } ?>>PayPal Express</option>
							<option value="paypal" <?php if($gateway == "paypal") { ?>selected="selected"<?php } ?>>PayPal Website Payments Pro</option>
							<option value="authorizenet" <?php if($gateway == "authorizenet") { ?>selected="selected"<?php } ?>>Authorize.net</option>
                        </select>                        
                    </td>
                </tr> 
                <tr>
                    <th scope="row" valign="top">
                    	<label for="gateway_environment">Gateway Environment:</label>
					</th>
					<td>
                        <select name="gateway_environment">
                        	<option value="sandbox" <?php if($gateway_environment == "sandbox") { ?>selected="selected"<?php } ?>>Sandbox/Testing</option>
                            <option value="live" <?php if($gateway_environment == "live") { ?>selected="selected"<?php } ?>>Live/Production</option>
                        </select>
						<script>
							function pmpro_changeGateway(gateway)
							{
								//hide all gateway options
								jQuery('tr.gateway').hide();
								jQuery('tr.gateway_'+gateway).show();
							}
							pmpro_changeGateway(jQuery().val('#gateway'));
						</script>
                    </td>
               </tr>
               <tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">	
                    	<label for="gateway_email">Gateway Account Email:</label>
					</th>
					<td>
                        <input type="text" name="gateway_email" size="60" value="<?=$gateway_email?>" />
                    </td>
                </tr>                
				<tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">
                    	<label for="apiusername">API Username:</label>
					</th>
					<td>
                        <input type="text" name="apiusername" size="60" value="<?=$apiusername?>" />
                    </td>
                </tr>
                <tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">
                    	<label for="apipassword">API Password:</label>
					</th>
					<td>
                        <input type="text" name="apipassword" size="60" value="<?=$apipassword?>" />
                    </td>
                </tr> 
                <tr class="gateway gateway_paypal gateway_paypalexpress" <?php if($gateway != "paypal" && $gateway != "paypalexpress") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">
                    	<label for="apisignature">API Signature:</label>
					</th>
					<td>
                        <input type="text" name="apisignature" size="60" value="<?=$apisignature?>" />
                    </td>
                </tr> 
				<tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">
                    	<label for="loginname">Login Name:</label>
					</th>
					<td>
                        <input type="text" name="loginname" size="60" value="<?=$loginname?>" />
                    </td>
                </tr>
                <tr class="gateway gateway_authorizenet" <?php if($gateway != "authorizenet") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">
                    	<label for="transactionkey">Transaction Key:</label>
					</th>
					<td>
                        <input type="text" name="transactionkey" size="60" value="<?=$transactionkey?>" />
                    </td>
                </tr>
				<tr class="gateway gateway_authorizenet gateway_paypal" <?php if($gateway != "authorizenet" && $gateway != "paypal") { ?>style="display: none;"<?php } ?>>
                    <th scope="row" valign="top">
                    	<label for="creditcards">Accepted Credit Card Types</label>
					</th>
					<td>
                        <input type="checkbox" name="creditcards_visa" value="1" <?php if(in_array("Visa", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> Visa<br />
						<input type="checkbox" name="creditcards_mastercard" value="1" <?php if(in_array("Mastercard", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> Mastercard<br />
						<input type="checkbox" name="creditcards_amex" value="1" <?php if(in_array("American Express", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> American Express<br />
						<input type="checkbox" name="creditcards_discover" value="1" <?php if(in_array("Discover", $pmpro_accepted_credit_cards)) { ?>checked="checked"<?php } ?> /> Discover<br />
                    </td>
                </tr>
				<tr class="gateway gateway_authorizenet gateway_paypal gateway_paypalexpress" <?php if($gateway != "authorizenet" && $gateway != "paypal") { ?>style="display: none;"<?php } ?>>
					<th scope="row" valign="top">
						<label for="tax">Sales Tax <small>(optional)</small></label>
					</th>
					<td>
						Tax State:
						<input type="text" name="tax_state" size="4" value="<?=$tax_state?>" /> <small>(abbreviation, e.g. "PA")</small>
						&nbsp; Tax Rate:
						<input type="text" name="tax_rate" size="10" value="<?=$tax_rate?>" /> <small>(decimal, e.g. "0.06")</small>
						<p><small>If values are given, tax will be applied for any members ordering from the selected state. For more complex tax rules, use the "pmpro_tax" filter.</small></p>
					</td>
				</tr>
  			</tbody>
            </table>            
            <p class="submit">            
                <input name="savesettings" type="submit" class="button-primary" value="Save Settings" /> 		                			
            </p>             
        </form> 
		<?php
		}
		elseif($view == 'email')
		{
		?>
		<form action="admin.php?page=pmpro-membershiplevels&view=email" method="post" enctype="multipart/form-data"> 
			<h2>Email Settings</h2>
            <p>By default, system generated emails are sent from <em><strong>wordpress@yourdomain.com</strong></em>. You can update this from address using the fields below.</p>
			
			<p>To modify the appearance of system generated emails, add the files <em>email_header.html</em> and <em>email_footer.html</em> to your theme's directory. This will modify both the WordPress default messages as well as messages generated by Paid Memberships Pro. <a title="Paid Memberships Pro - Member Communications" target="_blank" href="http://www.paidmembershipspro.com/support/member-communications/">Click here to learn more about Paid Memberships Pro emails</a>.</p>
						
			<table class="form-table">
            <tbody>                
                <tr>
                    <th scope="row" valign="top">
                    	<label for="from_email">From Email:</label>
					</th>
					<td>
                        <input type="text" name="from_email" size="60" value="<?=$from_email?>" />
                    </td>
				</tr>
				<tr>
					<th scope="row" valign="top">
                    	<label for="from_name">From Name:</label>
					</th>
					<td>
                        <input type="text" name="from_name" size="60" value="<?=$from_name?>" />
                    </td>
                </tr>
			</tbody>
			</table>
			<p class="submit">            
                <input name="savesettings" type="submit" class="button-primary" value="Save Settings" /> 		                			
            </p> 
		</form>
		<?php
		}
		elseif($view == 'advanced')
		{
		?>
		<form action="admin.php?page=pmpro-membershiplevels&view=advanced" method="post" enctype="multipart/form-data"> 
        	<h2>Advanced Settings</h2>
        			        
            <table class="form-table">
            <tbody>                
                <tr>
                    <th scope="row" valign="top">
                        <label for="nonmembertext">Message for Logged-in Non-members:</label>
					</th>
					<td>
                        <textarea name="nonmembertext" rows="3" cols="80"><?=stripslashes($nonmembertext)?></textarea><br />
						<small class="litegray">This message replaces the post content for non-members. Available variables: !!levels!!, !!referrer!!</small>
                    </td>
                </tr> 
                <tr>
                    <th scope="row" valign="top">
                    	<label for="notloggedintext">Message for Logged-out Users:</label>
					</th>
					<td>
                    	<textarea name="notloggedintext" rows="3" cols="80"><?=stripslashes($notloggedintext)?></textarea><br />
						<small class="litegray">This message replaces the post content for logged-out visitors.</small>
                    </td>
                </tr> 
                <tr>
                    <th scope="row" valign="top">
                    	<label for="rsstext">Message for RSS Feed:</label>
					</th>
					<td>
                    	<textarea name="rsstext" rows="3" cols="80"><?=stripslashes($rsstext)?></textarea><br />
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
									$hideadslevels = split(",", $hideadslevels);
								
								$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";						
								$levels = $wpdb->get_results($sqlQuery, OBJECT);								
								foreach($levels as $level) 
								{ 
							?>
                            	<div class="clickable"><input type="checkbox" id="hideadslevels_<?=$level->id?>" name="hideadslevels[]" value="<?=$level->id?>" <?php if(in_array($level->id, $hideadslevels)) { ?>checked="checked"<?php } ?>> <?=$level->name?></div>
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
                        <input type="text" name="recaptcha_publickey" size="60" value="<?=$recaptcha_publickey?>" />
						<br /><br />
						<label for="recaptcha_privatekey">reCAPTCHA Private Key:</label>
                        <input type="text" name="recaptcha_privatekey" size="60" value="<?=$recaptcha_privatekey?>" />						
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
		}
		else
		{
		?>							
					
		<h2>Membership Levels <a href="admin.php?page=pmpro-membershiplevels&edit=-1" class="button add-new-h2">Add New Level</a></h2>
		<form id="posts-filter" method="get" action="">			
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input">Search Levels:</label>
				<input type="hidden" name="page" value="pmpro-membershiplevels" />
				<input id="post-search-input" type="text" value="<?=$s?>" name="s" size="30" />
				<input class="button" type="submit" value="Search Levels" id="search-submit "/>
			</p>		
		</form>	
		
		<br class="clear" />
		
		<table class="widefat">
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Initial Payment</th>
				<th>Billing Cycle</th>        
				<th>Trial Cycle</th>
				<th>Expiration</th>
				<th>Allow Signups</th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
				if($s)
					$sqlQuery .= "WHERE name LIKE '%$s%' ";
				$sqlQuery .= "ORDER BY id ASC";
				
				$levels = $wpdb->get_results($sqlQuery, OBJECT);
				
				foreach($levels as $level)
				{
			?>
			<tr <?php if(!$level->allow_signups) { ?>class="pmpro_gray"<?php } ?>>
				<td><?=$level->id?></td>
				<td><?=$level->name?></td>
				<td>
					<?php if(pmpro_isLevelFree($level)) { ?>
						FREE
					<?php } else { ?>
						$<?=$level->initial_payment?>
					<?php } ?>
				</td>
				<td>
					<?php if(!pmpro_isLevelRecurring($level)) { ?>
						--
					<?php } else { ?>						
						$<?=$level->billing_amount?> every <?=$level->cycle_number.' '.sornot($level->cycle_period,$level->cycle_number)?>
						
						<?php if($level->billing_limit) { ?>(for <?=$level->billing_limit?> <?=sornot($level->cycle_period,$level->cycle_number)?>)<?php } ?>
						
					<?php } ?>
				</td>				
				<td>
					<?php if(!pmpro_isLevelTrial($level)) { ?>
						--
					<?php } else { ?>		
						$<?=$level->trial_amount?> for <?=$level->trial_limit?> <?=sornot("payment",$level->trial_limit)?>
					<?php } ?>
				</td>
				<td>
					<?php if(!pmpro_isLevelExpiring($level)) { ?>
						--
					<?php } else { ?>		
						After <?=$level->expiration_number?> <?=sornot($level->expiration_period,$level->expiration_number)?>
					<?php } ?>
				</td>
				<td><?php if($level->allow_signups) { ?>Yes<?php } else { ?>No<?php } ?></td>
				<td align="center"><a href="admin.php?page=pmpro-membershiplevels&edit=<?=$level->id?>" class="edit">edit</a></td>
				<td align="center"><a href="admin.php?page=pmpro-membershiplevels&copy=<?=$level->id?>&edit=-1" class="edit">copy</a></td>
				<td align="center"><a href="javascript: askfirst('Are you sure you want to delete membership level <?=$level->name?>? All subscriptions will be canceled.','<?=PMPRO_URL?>/services/pmpro-data.php?action=delete_membership_level&deleteid=<?=$level->id?>'); void(0);" class="delete">delete</a></td>
			</tr>
			<?php
				}
			?>
		</tbody>
		</table>	
		<?php
		}
	?>		
</div>
