<?php
	global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $levels;
	
	//if a member is logged in, show them some info here (1. past invoices. 2. billing information with button to update.)
	if($current_user->membership_level->ID)
	{
	?>			
		<p>Your membership is <strong>active</strong>.</p>
		<ul>
			<li><strong>Level:</strong> <?=$current_user->membership_level->name?></li>
		<?php if($current_user->membership_level->billing_amount > 0) { ?>
			<li><strong>Membership Fee:</strong>
			$<?=$current_user->membership_level->billing_amount?>
			<?php if($current_user->membership_level->cycle_number > 1) { ?>
				per <?=$current_user->membership_level->cycle_number?> <?=sornot($current_user->membership_level->cycle_period,$current_user->membership_level->cycle_number)?>
			<?php } elseif($current_user->membership_level->cycle_number == 1) { ?>
				per <?=$current_user->membership_level->cycle_period?>
			<?php } ?>
			</li>
		<?php } ?>						
		
		<?php if($current_user->membership_level->billing_limit) { ?>
			<li><strong>Duration:</strong> <?=$current_user->membership_level->billing_limit.' '.sornot($current_user->membership_level->cycle_period,$current_user->membership_level->billing_limit)?></li>
		<?php } ?>
		
		<?php if($current_user->membership_level->trial_limit) { ?>
			Your first <?=$current_user->membership_level->trial_limit?> <?=sornot("payment",$current_user->membership_level->trial_limit)?> will cost $<?=$current_user->membership_level->trial_amount?>.
		<?php } ?>   

		<?php
			//the nextpayment code is not tight yet
			/*
			$nextpayment = pmpro_next_payment();
			if($nextpayment)
			{
			?>
				<li><strong>Next Invoice:</strong> <?=date("F j, Y", $nextpayment)?></li>
			<?php
			}
			*/
		?>
		</ul>
		
		<div class="pmpro_left">
			<div class="pmpro_box">
				<?php get_currentuserinfo(); ?> 
				<h3><a class="pmpro_a-right" href="<?=home_url()?>/wp-admin/profile.php">Edit</a>My Account</h3>
				<p>
				<?php if($current_user->user_firstname) { ?>
					<?=$current_user->user_firstname?> <?=$current_user->user_lastname?><br />
				<?php } ?>
				<small>
					<strong>Username:</strong> <?=$current_user->user_login?><br />
					<strong>Email:</strong> <?=$current_user->user_email?><br />
					<strong>Password:</strong> ****** <small><a href="<?=home_url()?>/wp-admin/profile.php">change</a></small>				
				</small>
				</a>
			</div>
			<?php
				//last invoice for current info
				$ssorder = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' AND membership_id = '" . $current_user->membership_level->ID . "' AND status = 'success' ORDER BY timestamp DESC LIMIT 1");				
				$invoices = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' ORDER BY timestamp DESC");
				if($ssorder)
				{
					//default values from DB (should be last order or last update)
					$bfirstname = get_user_meta($current_user->ID, "pmpro_bfirstname", true);
					$blastname = get_user_meta($current_user->ID, "pmpro_blastname", true);
					$baddress1 = get_user_meta($current_user->ID, "pmpro_baddress1", true);
					$baddress2 = get_user_meta($current_user->ID, "pmpro_baddress2", true);
					$bcity = get_user_meta($current_user->ID, "pmpro_bcity", true);
					$bstate = get_user_meta($current_user->ID, "pmpro_bstate", true);
					$bzipcode = get_user_meta($current_user->ID, "pmpro_bzipcode", true);
					$bphone = get_user_meta($current_user->ID, "pmpro_bphone", true);
					$bemail = get_user_meta($current_user->ID, "pmpro_bemail", true);
					$bconfirmemail = get_user_meta($current_user->ID, "pmpro_bconfirmemail", true);
					$CardType = get_user_meta($current_user->ID, "pmpro_CardType", true);
					$AccountNumber = hideCardNumber(get_user_meta($current_user->ID, "pmpro_AccountNumber", true), false);
					$ExpirationMonth = get_user_meta($current_user->ID, "pmpro_ExpirationMonth", true);
					$ExpirationYear = get_user_meta($current_user->ID, "pmpro_ExpirationYear", true);	
				?>		
				<div class="pmpro_box">				
					<h3><a class="pmpro_a-right" href="<?=pmpro_url("billing", "", "https")?>">Edit</a>Billing Information</h3>
					<p>
						<strong>Billing Address</strong><br />
						<?=$bfirstname . " " . $blastname?>
						<br />		
						<?=$baddress1?><br />
						<?php if($baddress2) echo $baddress2 . "<br />";?>
						<?php if($bcity && $bstate) { ?>
							<?=$bcity?>, <?=$bstate?> <?=$bzipcode?>
						<?php } ?>                         
						<br />
						<?=formatPhone($bphone)?>
					</p>
					<p>
						<strong>Payment Method</strong><br />
						<?=$CardType?>: <?=last4($AccountNumber)?> (<?=$ExpirationMonth?>/<?=$ExpirationYear?>)
					</p>
				</div>					
			<?php
			}
			?>
			<div class="pmpro_box">
				<h3>Member Links</h3>
				<ul>
					<?php if($ssorder) { ?>
						<li><a href="<?=pmpro_url("billing", "", "https")?>">Update Billing Information</a></li>
					<?php } ?>
					<?php if(count($pmpro_levels) > 1) { ?>
						<li><a href="<?=pmpro_url("levels")?>">Change Membership Level</a></li>
					<?php } ?>
					<li><a href="<?=pmpro_url("cancel")?>">Cancel Membership</a></li>
				</ul>
			</div>
		</div> <!-- end pmpro_left -->
		
		<div class="pmpro_right">
			<?php if($invoices) { ?>
			<div class="pmpro_box">
				<h3>Past Invoices</h3>
				<ul>
					<?php foreach($invoices as $invoice) { ?>
					<li <?php if($count++ > 10) { ?>class="pmpro_hidden pmpro_invoice"<?php } ?>><a href="<?=pmpro_url("invoice", "?invoice=" . $invoice->code)?>"><?=date("F j, Y", $invoice->timestamp)?> ($<?=$invoice->total?>)</a></li>
					<?php } ?>
					<?php if($count > 10) { ?>
						<li class="pmpro_more pmpro_invoice"><a href="javascript: jQuery('.pmpro_more.pmpro_invoice').hide(); jQuery('.pmpro_hidden.pmpro_invoice').show(); void(0);">show <?=(count($invoices) - 10)?> more</a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>	<!-- end pmpro_right -->
		
<?php
	}
?>