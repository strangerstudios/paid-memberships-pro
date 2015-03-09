<?php 
	global $wpdb, $pmpro_invoice, $pmpro_msg, $pmpro_msgt, $current_user;
	
	if($pmpro_msg)
	{
	?>
	<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
	<?php
	}
?>	

<?php 
	if($pmpro_invoice) 
	{ 
		?>
		<?php
			$pmpro_invoice->getUser();
			$pmpro_invoice->getMembershipLevel();
		?>
		
		<h3>
			<?php printf(__('Invoice #%s on %s', 'pmpro'), $pmpro_invoice->code, date_i18n(get_option('date_format'), $pmpro_invoice->timestamp));?>	
		</h3>
		<a class="pmpro_a-print" href="javascript:window.print()"><?php _e('Print', 'pmpro'); ?></a>
		<ul>
			<?php do_action("pmpro_invoice_bullets_top", $pmpro_invoice); ?>
			<li><strong><?php _e('Account', 'pmpro');?>:</strong> <?php echo $pmpro_invoice->user->display_name?> (<?php echo $pmpro_invoice->user->user_email?>)</li>
			<li><strong><?php _e('Membership Level', 'pmpro');?>:</strong> <?php echo $current_user->membership_level->name?></li>
			<?php if($current_user->membership_level->enddate) { ?>
				<li><strong><?php _e('Membership Expires', 'pmpro');?>:</strong> <?php echo date(get_option('date_format'), $current_user->membership_level->enddate)?></li>
			<?php } ?>
			<?php if($pmpro_invoice->getDiscountCode()) { ?>
				<li><strong><?php _e('Discount Code', 'pmpro');?>:</strong> <?php echo $pmpro_invoice->discount_code->code?></li>
			<?php } ?>
			<?php do_action("pmpro_invoice_bullets_bottom", $pmpro_invoice); ?>
		</ul>
		
		<?php
			//check instructions		
			if($pmpro_invoice->gateway == "check" && !pmpro_isLevelFree($pmpro_invoice->membership_level))
				echo wpautop(pmpro_getOption("instructions"));
		?>
			
		<table id="pmpro_invoice_table" class="pmpro_invoice" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<?php if(!empty($pmpro_invoice->billing->name)) { ?>
						<th><?php _e('Billing Address', 'pmpro');?></th>
					<?php } ?>
					<th><?php _e('Payment Method', 'pmpro');?></th>
					<th><?php _e('Membership Level', 'pmpro');?></th>
					<th align="center"><?php _e('Total Billed', 'pmpro');?></th>
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
							<?php echo $pmpro_invoice->cardtype?> <?php echo __('ending in', 'pmpro');?> <?php echo last4($pmpro_invoice->accountnumber)?><br />
							<small><?php _e('Expiration', 'pmpro');?>: <?php echo $pmpro_invoice->expirationmonth?>/<?php echo $pmpro_invoice->expirationyear?></small>
						<?php } elseif($pmpro_invoice->payment_type) { ?>
							<?php echo $pmpro_invoice->payment_type?>
						<?php } ?>
					</td>
					<td><?php echo $pmpro_invoice->membership_level->name?></td>					
					<td align="center">
						<?php if($pmpro_invoice->total != '0.00') { ?>
							<?php if(!empty($pmpro_invoice->tax)) { ?>
								<?php _e('Subtotal', 'pmpro');?>: <?php echo pmpro_formatPrice($pmpro_invoice->subtotal);?><br />
								<?php _e('Tax', 'pmpro');?>: <?php echo pmpro_formatPrice($pmpro_invoice->tax);?><br />
								<?php if(!empty($pmpro_invoice->couponamount)) { ?>
									<?php _e('Coupon', 'pmpro');?>: (<?php echo pmpro_formatPrice($pmpro_invoice->couponamount);?>)<br />
								<?php } ?>
								<strong><?php _e('Total', 'pmpro');?>: <?php echo pmpro_formatPrice($pmpro_invoice->total);?></strong>
							<?php } else { ?>
								<?php echo pmpro_formatPrice($pmpro_invoice->total);?>
							<?php } ?>						
						<?php } else { ?>
							<small class="pmpro_grey"><?php echo pmpro_formatPrice(0);?></small>
						<?php } ?>		
					</td>
				</tr>
			</tbody>
		</table>
		<?php 
	} 
	else 
	{
		//Show all invoices for user if no invoice ID is passed	
		$invoices = $wpdb->get_results("SELECT o.*, UNIX_TIMESTAMP(o.timestamp) as timestamp, l.name as membership_level_name FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id WHERE o.user_id = '$current_user->ID' ORDER BY timestamp DESC");
		if($invoices)
		{
			?>
			<table id="pmpro_invoices_table" class="pmpro_invoice" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php _e('Date', 'pmpro'); ?></th>
					<th><?php _e('Invoice #', 'pmpro'); ?></th>
					<th><?php _e('Level', 'pmpro'); ?></th>
					<th><?php _e('Total Billed', 'pmpro'); ?></th>					
				</tr>
			</thead>
			<tbody>
			<?php
				foreach($invoices as $invoice)
				{ 
					?>
					<tr>
						<td><a href="<?php echo pmpro_url("invoice", "?invoice=" . $invoice->code)?>"><?php echo date(get_option("date_format"), $invoice->timestamp)?></a></td>
						<td><a href="<?php echo pmpro_url("invoice", "?invoice=" . $invoice->code)?>"><?php echo $invoice->code; ?></a></td>
						<td><?php echo $invoice->membership_level_name;?></td>
						<td><?php echo pmpro_formatPrice($invoice->total);?></td>											
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php
		}
		else
		{
			?>
			<p><?php _e('No invoices found.', 'pmpro');?></p>
			<?php
		}
	} 
?>
<nav id="nav-below" class="navigation" role="navigation">
	<div class="nav-next alignright">
		<a href="<?php echo pmpro_url("account")?>"><?php _e('View Your Membership Account &rarr;', 'pmpro');?></a>
	</div>
	<?php if($pmpro_invoice) { ?>
		<div class="nav-prev alignleft">
			<a href="<?php echo pmpro_url("invoice")?>"><?php _e('&larr; View All Invoices', 'pmpro');?></a>
		</div>
	<?php } ?>
</nav>
