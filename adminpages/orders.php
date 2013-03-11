<?php
	//only admins can get this
	if(!function_exists("current_user_can") || !current_user_can("manage_options"))
	{
		die("You do not have permissions to perform this action.");
	}	
	
	//vars
	global $wpdb, $pmpro_currency_symbol;
	if(isset($_REQUEST['s']))
		$s = $_REQUEST['s'];
	else
		$s = "";
	
	if(isset($_REQUEST['l']))
		$l = $_REQUEST['l'];
	else
		$l = false;
?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?php echo PMPRO_URL?>/images/PaidMembershipsPro.gif" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro_tagline">Membership Plugin for WordPress</div>
		
		<div class="pmpro_meta"><a href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>">Plugin Support</a> | <a href="http://www.paidmembershipspro.com/forums/">User Forum</a> | <strong>Version <?php echo PMPRO_VERSION?></strong></div>
	</div>
	<br style="clear:both;" />		

	<form id="posts-filter" method="get" action="">	
	<h2>
		Orders Report
		<small>(<a target="_blank" href="<?php echo admin_url('admin-ajax.php');?>?action=orders_csv&s=<?php echo $s?>&l=<?php echo $l?>">Export to CSV</a>)</small>
	</h2>		
	<ul class="subsubsub">
		<li>			
				
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input">Search Orders:</label>
		<input type="hidden" name="page" value="pmpro-orders" />		
		<input id="post-search-input" type="text" value="<?php echo $s?>" name="s"/>
		<input class="button" type="submit" value="Search Orders"/>
	</p>
	<?php 
		//some vars for the search
		if(isset($_REQUEST['pn']))
			$pn = $_REQUEST['pn'];
		else
			$pn = 1;
			
		if(isset($_REQUEST['limit']))
			$limit = $_REQUEST['limit'];
		else
			$limit = 15;
		
		$end = $pn * $limit;
		$start = $end - $limit;				
					
		if($s)
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS o.id FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->users u ON o.user_id = u.ID LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id WHERE (1=2 ";
			$fields = array("o.id", "o.code", "o.billing_name", "o.billing_street", "o.billing_city", "o.billing_state", "o.billing_zip", "o.billing_phone", "o.payment_type", "o.cardtype", "o.accountnumber", "o.status", "o.gateway", "o.gateway_environment", "o.payment_transaction_id", "o.subscription_transaction_id", "u.user_login", "u.user_email", "u.display_name", "l.name");
			foreach($fields as $field)
				$sqlQuery .= " OR " . $field . " LIKE '%" . $wpdb->escape($s) . "%' ";
			$sqlQuery .= ") ";
			$sqlQuery .= "ORDER BY o.timestamp DESC ";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS id FROM $wpdb->pmpro_membership_orders ORDER BY timestamp DESC ";
		}
		
		$sqlQuery .= "LIMIT $start, $limit";
				
		$order_ids = $wpdb->get_col($sqlQuery);
		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");
		
		if($order_ids)
		{		
		?>
		<p class="clear"><?php echo strval($totalrows)?> orders found.</span></p>
		<?php
		}		
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th>ID</th>
				<th>Code</th>
				<th>User</th>
				<th>Membership Level</th>
				<th>Total</th>
				<th>Payment</th>
				<th>Gateway</th>
				<th>Transaction IDs</th>	
				<th>Status</th>
				<th>Date</th>				
			</tr>
		</thead>
		<tbody id="orders" class="list:order orders-list">	
			<?php	
				$count = 0;											
				foreach($order_ids as $order_id)
				{										
					$order = new MemberOrder();
					$order->nogateway = true;
					$order->getMemberOrderByID($order_id);
					?>
						<tr <?php if($count++ % 2 == 0) { ?>class="alternate"<?php } ?>>
							<td><?php echo $order->id;?></td>
							<td><?php echo $order->code;?></td>
							<td>
								<?php $order->getUser(); ?>		
								<?php if(!empty($order->user)) { ?>
									<a href="user-edit.php?user_id=<?php echo $order->user->ID?>"><?php echo $order->user->user_login?></a>
								<?php } else { ?>
									[deleted]
								<?php } ?>
							</td>						
							<td><?php echo $order->membership_id;?></td>
							<td><?php echo $pmpro_currency_symbol . $order->total;?></td>
							<td>
								<?php if(!empty($order->payment_type)) echo $order->payment_type . "<br />";?>
								<?php if(!empty($order->cardtype)) { ?>
									<?php echo $order->cardtype;?>: x<?php echo last4($order->accountnumber);?><br />
								<?php } ?>
								<?php if(!empty($order->billing->street)) { ?>
									<?php echo $order->billing->street; ?><br />																		
									<?php if( $order->billing->city &&  $order->billing->state) { ?>
										<?php echo  $order->billing->city?>, <?php echo  $order->billing->state?> <?php echo  $order->billing->zip?>  <?php if(!empty( $order->billing->country)) echo  $order->billing->country?><br />												
									<?php } ?>
								<?php } ?>
								<?php if(!empty($order->billing->phone)) echo formatPhone($order->billing->phone);?>
							</td>
							<td><?php echo $order->gateway;?><?php if($order->gateway_environment == "test") echo "(test)";?></td>
							<td>
								Payment: <?php if(!empty($order->payment_transaction_id)) echo $order->payment_transaction_id; else echo "N/A";?>
								<br />
								Subscription: <?php if(!empty($order->subscription_transaction_id)) echo $order->subscription_transaction_id; else echo "N/A";?>	
							</td>
							<td><?php echo $order->status;?></td>
							<td><?php echo date(get_option('date_format'), $order->timestamp);?></td>
						</tr>
					<?php
				}
				
				if(!$order_ids)
				{
				?>
				<tr>
					<td colspan="9"><p>No orders found.</p></td>
				</tr>
				<?php
				}
			?>		
		</tbody>
	</table>
	</form>
	
	<?php
	echo pmpro_getPaginationString($pn, $totalrows, $limit, 1, get_admin_url(NULL, "/admin.php?page=pmpro-orders&s=" . urlencode($s)), "&l=$l&limit=$limit&pn=");
	?>
	
</div>
