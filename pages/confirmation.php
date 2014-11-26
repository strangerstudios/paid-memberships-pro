<?php 
	global $wpdb, $current_user, $pmpro_invoice, $pmpro_msg, $pmpro_msgt;
	
	if($pmpro_msg)
	{
	?>
		<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
	<?php
	}
	
	if(empty($current_user->membership_level))
		$confirmation_message = "<p>" . __('Your payment has been submitted. Your membership will be activated shortly.', 'pmpro') . "</p>";
	else
		$confirmation_message = "<p>" . sprintf(__('Thank you for your membership to %s. Your %s membership is now active.', 'pmpro'), get_bloginfo("name"), $current_user->membership_level->name) . "</p>";		
	
	//confirmation message for this level
	$level_message = $wpdb->get_var("SELECT l.confirmation FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE mu.status = 'active' AND mu.user_id = '" . $current_user->ID . "' LIMIT 1");
	if(!empty($level_message))
		$confirmation_message .= "\n" . stripslashes($level_message) . "\n";
?>	

<?php if(!empty($pmpro_invoice)) { ?>		
	
	<?php
		$pmpro_invoice->getUser();
		$pmpro_invoice->getMembershipLevel();			
				
		$confirmation_message .= "<p>" . sprintf(__('Below are details about your membership account and a receipt for your initial membership invoice. A welcome email with a copy of your initial membership invoice has been sent to %s.', 'pmpro'), $pmpro_invoice->user->user_email) . "</p>";
		
		//check instructions		
		if($pmpro_invoice->gateway == "check" && !pmpro_isLevelFree($pmpro_invoice->membership_level))
			$confirmation_message .= wpautop(pmpro_getOption("instructions"));
		
		$confirmation_message = apply_filters("pmpro_confirmation_message", $confirmation_message, $pmpro_invoice);				
		
		echo apply_filters("the_content", $confirmation_message);		
	?>
	
	
	<h3>
		<?php printf(__('Invoice #%s on %s', 'pmpro'), $pmpro_invoice->code, date_i18n(get_option('date_format'), $pmpro_invoice->timestamp));?>		
	</h3>
	<a class="pmpro_a-print" href="javascript:window.print()"><?php _e('Print', 'pmpro');?></a>
	<ul>
		<?php do_action("pmpro_invoice_bullets_top", $pmpro_invoice); ?>
		<li><strong><?php _e('Account', 'pmpro');?>:</strong> <?php echo $current_user->display_name?> (<?php echo $current_user->user_email?>)</li>
		<li><strong><?php _e('Membership Level', 'pmpro');?>:</strong> <?php echo $current_user->membership_level->name?></li>
		<?php if($current_user->membership_level->enddate) { ?>
			<li><strong><?php _e('Membership Expires', 'pmpro');?>:</strong> <?php echo date(get_option('date_format'), $current_user->membership_level->enddate)?></li>
		<?php } ?>
		<?php if($pmpro_invoice->getDiscountCode()) { ?>
			<li><strong><?php _e('Discount Code', 'pmpro');?>:</strong> <?php echo $pmpro_invoice->discount_code->code?></li>
		<?php } ?>
		<?php do_action("pmpro_invoice_bullets_bottom", $pmpro_invoice); ?>
	</ul>
	
	<table id="pmpro_confirmation_table" class="pmpro_invoice" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
			<tr>
				<?php if(!empty($pmpro_invoice->billing->name)) { ?>
				<th><?php _e('Billing Address', 'pmpro');?></th>
				<?php } ?>
				<th><?php _e('Payment Method', 'pmpro');?></th>
				<th><?php _e('Membership Level', 'pmpro');?></th>
				<th><?php _e('Total Billed', 'pmpro');?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<?php if(!empty($pmpro_invoice->billing->name)) { ?>
				<td>
					<?php echo $pmpro_invoice->billing->name?><br />
					<?php echo $pmpro_invoice->billing->street?><br />						
					<?php if($pmpro_invoice->billing->city && $pmpro_invoice->billing->state) { ?>
						<?php echo $pmpro_invoice->billing->city?>, <?php echo $pmpro_invoice->billing->state?> <?php echo $pmpro_invoice->billing->zip?> <?php echo $pmpro_invoice->billing->country?><br />												
					<?php } ?>
					<?php echo formatPhone($pmpro_invoice->billing->phone)?>
				</td>
				<?php } ?>
				<td>
					<?php if($pmpro_invoice->accountnumber) { ?>
						<?php echo $pmpro_invoice->cardtype?> <?php _e('ending in', 'pmpro');?> <?php echo last4($pmpro_invoice->accountnumber)?><br />
						<small><?php _e('Expiration', 'pmpro');?>: <?php echo $pmpro_invoice->expirationmonth?>/<?php echo $pmpro_invoice->expirationyear?></small>
					<?php } elseif($pmpro_invoice->payment_type) { ?>
						<?php echo $pmpro_invoice->payment_type?>
					<?php } ?>
				</td>
				<td><?php echo $pmpro_invoice->membership_level->name?></td>					
				<td><?php if($pmpro_invoice->total) echo pmpro_formatPrice($pmpro_invoice->total); else echo "---";?></td>
			</tr>
		</tbody>
	</table>		
<?php 
	} 
	else 
	{
		$confirmation_message .= "<p>" . sprintf(__('Below are details about your membership account. A welcome email has been sent to %s.', 'pmpro'), $current_user->user_email) . "</p>";
		
		$confirmation_message = apply_filters("pmpro_confirmation_message", $confirmation_message, false);
		
		echo $confirmation_message;
	?>	
	<ul>
		<li><strong><?php _e('Account', 'pmpro');?>:</strong> <?php echo $current_user->display_name?> (<?php echo $current_user->user_email?>)</li>
		<li><strong><?php _e('Membership Level', 'pmpro');?>:</strong> <?php if(!empty($current_user->membership_level)) echo $current_user->membership_level->name; else _ex("Pending", "User without membership is in {pending} status.", "pmpro");?></li>
	</ul>	
<?php 
	} 
?>  
<nav id="nav-below" class="navigation" role="navigation">
	<div class="nav-next alignright">
		<?php if(!empty($current_user->membership_level)) { ?>
			<a href="<?php echo pmpro_url("account")?>"><?php _e('View Your Membership Account &rarr;', 'pmpro');?></a>
		<?php } else { ?>
			<?php _e('If your account is not activated within a few minutes, please contact the site owner.', 'pmpro');?>
		<?php } ?>
	</div>
</nav>
