<?php
	global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $levels, $pmpro_currency_symbol;
	
	//if a member is logged in, show them some info here (1. past invoices. 2. billing information with button to update.)
	if($current_user->membership_level->ID)
	{
	?>			
		<p><?php _e("Your membership is <strong>active</strong>.", "pmpro");?></p>
		<ul>
			<li><strong><?php _e("Level", "pmpro");?>:</strong> <?php echo $current_user->membership_level->name?></li>
		<?php if($current_user->membership_level->billing_amount > 0) { ?>
			<li><strong><?php _e("Membership Fee", "pmpro");?>:</strong>
			<?php echo $pmpro_currency_symbol?><?php echo $current_user->membership_level->billing_amount?>
			<?php if($current_user->membership_level->cycle_number > 1) { ?>
				per <?php echo $current_user->membership_level->cycle_number?> <?php echo sornot($current_user->membership_level->cycle_period,$current_user->membership_level->cycle_number)?>
			<?php } elseif($current_user->membership_level->cycle_number == 1) { ?>
				per <?php echo $current_user->membership_level->cycle_period?>
			<?php } ?>
			</li>
		<?php } ?>						
		
		<?php if($current_user->membership_level->billing_limit) { ?>
			<li><strong><?php _e("Duration", "pmpro");?>:</strong> <?php echo $current_user->membership_level->billing_limit.' '.sornot($current_user->membership_level->cycle_period,$current_user->membership_level->billing_limit)?></li>
		<?php } ?>
		
		<?php if($current_user->membership_level->enddate) { ?>
			<li><strong><?php _e("Membership Expires", "pmpro");?>:</strong> <?php echo date(get_option('date_format'), $current_user->membership_level->enddate)?></li>
		<?php } ?>
		
		<?php if($current_user->membership_level->trial_limit == 1) 
		{ 
			printf(__("Your first payment will cost %s.", "pmpro"), $pmpro_currency_symbol . $current_user->membership_level->trial_amount);
		}
		elseif(!empty($current_user->membership_level->trial_limit)) 
		{
			printf(__("Your first %d payments will cost %s.", "pmpro"), $current_user->membership_level->trial_limit, $pmpro_currency_symbol . $current_user->membership_level->trial_amount);
		}
		?>
		</ul>
		
		<div class="pmpro_left">
			<div class="pmpro_box">
				<?php get_currentuserinfo(); ?> 
				<h3><a class="pmpro_a-right" href="<?php echo admin_url('profile.php')?>"><?php _e("Edit", "pmpro");?></a><?php _e("My Account", "pmpro");?></h3>
				<p>
				<?php if($current_user->user_firstname) { ?>
					<?php echo $current_user->user_firstname?> <?php echo $current_user->user_lastname?><br />
				<?php } ?>
				<small>
					<strong><?php _e("Username", "pmpro");?>:</strong> <?php echo $current_user->user_login?><br />
					<strong><?php _e("Email", "pmpro");?>:</strong> <?php echo $current_user->user_email?><br />
					<strong><?php _e("Password", "pmpro");?>:</strong> ****** <small><a href="<?php echo admin_url('profile.php')?>"><?php _ex("change", "As in 'change password'.", "pmpro");?></a></small>				
				</small>
				</p>
			</div>
			<?php
				//last invoice for current info
				//$ssorder = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' AND membership_id = '" . $current_user->membership_level->ID . "' AND status = 'success' ORDER BY timestamp DESC LIMIT 1");				
				$ssorder = new MemberOrder();
				$ssorder->getLastMemberOrder();
				$invoices = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' ORDER BY timestamp DESC");				
				if(!empty($ssorder->id) && $ssorder->gateway != "check" && $ssorder->gateway != "paypalexpress")
				{
					//default values from DB (should be last order or last update)
					$bfirstname = get_user_meta($current_user->ID, "pmpro_bfirstname", true);
					$blastname = get_user_meta($current_user->ID, "pmpro_blastname", true);
					$baddress1 = get_user_meta($current_user->ID, "pmpro_baddress1", true);
					$baddress2 = get_user_meta($current_user->ID, "pmpro_baddress2", true);
					$bcity = get_user_meta($current_user->ID, "pmpro_bcity", true);
					$bstate = get_user_meta($current_user->ID, "pmpro_bstate", true);
					$bzipcode = get_user_meta($current_user->ID, "pmpro_bzipcode", true);
					$bcountry = get_user_meta($current_user->ID, "pmpro_bcountry", true);
					$bphone = get_user_meta($current_user->ID, "pmpro_bphone", true);
					$bemail = get_user_meta($current_user->ID, "pmpro_bemail", true);
					$bconfirmemail = get_user_meta($current_user->ID, "pmpro_bconfirmemail", true);
					$CardType = get_user_meta($current_user->ID, "pmpro_CardType", true);
					$AccountNumber = hideCardNumber(get_user_meta($current_user->ID, "pmpro_AccountNumber", true), false);
					$ExpirationMonth = get_user_meta($current_user->ID, "pmpro_ExpirationMonth", true);
					$ExpirationYear = get_user_meta($current_user->ID, "pmpro_ExpirationYear", true);	
				?>		
				<div class="pmpro_box">				
					<h3><?php if((isset($ssorder->status) && $ssorder->status == "success") && (isset($ssorder->gateway) && in_array($ssorder->gateway, array("authorizenet", "paypal", "stripe")))) { ?><a class="pmpro_a-right" href="<?php echo pmpro_url("billing", "")?>">Edit</a><?php } ?>Billing Information</h3>
					<?php if(!empty($baddress1)) { ?>
					<p>
						<strong><?php _e("Billing Address", "pmpro");?></strong><br />
						<?php echo $bfirstname . " " . $blastname?>
						<br />		
						<?php echo $baddress1?><br />
						<?php if($baddress2) echo $baddress2 . "<br />";?>
						<?php if($bcity && $bstate) { ?>
							<?php echo $bcity?>, <?php echo $bstate?> <?php echo $bzipcode?> <?php echo $bcountry?>
						<?php } ?>                         
						<br />
						<?php echo formatPhone($bphone)?>
					</p>
					<?php } ?>
					
					<?php if(!empty($AccountNumber)) { ?>
					<p>
						<strong><?php _e("Payment Method", "pmpro");?></strong><br />
						<?php echo $CardType?>: <?php echo last4($AccountNumber)?> (<?php echo $ExpirationMonth?>/<?php echo $ExpirationYear?>)
					</p>
					<?php } ?>
				</div>					
			<?php
			}
			?>
			<div class="pmpro_box">
				<h3><?php _e("Member Links", "pmpro");?></h3>
				<ul>
					<?php 
						do_action("pmpro_member_links_top");
					?>
					<?php if((isset($ssorder->status) && $ssorder->status == "success") && (isset($ssorder->gateway) && in_array($ssorder->gateway, array("authorizenet", "paypal", "stripe")))) { ?>
						<li><a href="<?php echo pmpro_url("billing", "", "https")?>"><?php _e("Update Billing Information", "pmpro");?></a></li>
					<?php } ?>
					<?php if(count($pmpro_levels) > 1) { ?>
						<li><a href="<?php echo pmpro_url("levels")?>"><?php _e("Change Membership Level", "pmpro");?></a></li>
					<?php } ?>
					<li><a href="<?php echo pmpro_url("cancel")?>"><?php _e("Cancel Membership", "pmpro");?></a></li>
					<?php 
						do_action("pmpro_member_links_bottom");
					?>
				</ul>
			</div>
		</div> <!-- end pmpro_left -->
		
		<div class="pmpro_right">
			<?php if(!empty($invoices)) { ?>
			<div class="pmpro_box">
				<h3><?php _e("Past Invoices", "pmpro");?></h3>
				<ul>
					<?php 
						$count = 0;
						foreach($invoices as $invoice) 
						{ 
					?>
					<li <?php if($count++ > 10) { ?>class="pmpro_hidden pmpro_invoice"<?php } ?>><a href="<?php echo pmpro_url("invoice", "?invoice=" . $invoice->code)?>"><?php echo date("F j, Y", $invoice->timestamp)?> (<?php echo $pmpro_currency_symbol?><?php echo $invoice->total?>)</a></li>
					<?php } ?>
					<?php if($count > 10) { ?>
						<li class="pmpro_more pmpro_invoice"><a href="javascript: jQuery('.pmpro_more.pmpro_invoice').hide(); jQuery('.pmpro_hidden.pmpro_invoice').show(); void(0);"><?php printf(__("show %d more", "pmpro"), count($invoices) - 10);?></a></li>
					<?php 
						} 
					?>
				</ul>
			</div>
			<?php } ?>
		</div>	<!-- end pmpro_right -->
		
<?php
	}
