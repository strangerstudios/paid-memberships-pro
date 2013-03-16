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
	
	//deleting?
	if(!empty($_REQUEST['delete']))
	{
		$dorder = new MemberOrder(intval($_REQUEST['delete']));
		if($dorder->deleteMe())
		{
			$pmpro_msg = "Order deleted successfully.";
			$pmpro_msgt = "success";
		}
		else
		{
			$pmpro_msg = "Error deleting order.";
			$pmpro_msgt = "error";
		}
	}
	
	//saving?
	if(!empty($_REQUEST['save']))
	{
		//start with old order if applicable
		$order_id = intval($_REQUEST['order']);
		if($order_id > 0)
			$order = new MemberOrder($order_id);
		else
			$order = new MemberOrder();
		
		//update values
		$order->code = $_POST['code'];
		$order->user_id = intval($_POST['user_id']);
		$order->membership_id = intval($_POST['membership_id']);
		$order->billing->name = $_POST['billing_name'];
		$order->billing->street = $_POST['billing_street'];
		$order->billing->city = $_POST['billing_city'];
		$order->billing->state = $_POST['billing_state'];
		$order->billing->zip = $_POST['billing_zip'];
		$order->billing->country = $_POST['billing_country'];
		$order->billing->phone = $_POST['billing_phone'];
		$order->tax = $_POST['tax'];
		$order->couponamount = $_POST['couponamount'];
		$order->total = $_POST['total'];
		$order->payment_type = $_POST['payment_type'];
		$order->cardtype = $_POST['cardtype'];
		$order->accountnumber = $_POST['accountnumber'];
		$order->expirationmonth = $_POST['expirationmonth'];		
		$order->expirationyear = $_POST['expirationyear'];
		$order->ExpirationDate = $order->expirationmonth . $order->expirationyear;
		$order->status = $_POST['status'];
		$order->gateway = $_POST['gateway'];
		$order->gateway_environment = $_POST['gateway_environment'];
		$order->payment_transaction_id = $_POST['payment_transaction_id'];
		$order->subscription_transaction_id = $_POST['subscription_transaction_id'];
		
		//affiliate stuff
		$affiliates = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE affiliate_id <> '' LIMIT 1");
		if(!empty($affiliates))
		{
			$order->affiliate_id = $_POST['affiliate_id'];
			$order->affiliate_subid = $_POST['affiliate_subid'];
		}
		
		//save
		if($order->saveOrder() !== false)
		{		
			//handle timestamp
			if($order->updateTimestamp($_POST['ts_year'], $_POST['ts_month'], $_POST['ts_day']) !== false)
			{
				$pmpro_msg = "Order saved successfully.";
				$pmpro_msgt = "success";
			}
			else
			{
				$pmpro_msg = "Error updating order timestamp.";
				$pmpro_msgt = "error";
			}
		}	
		else
		{
			$pmpro_msg = "Error saving order.";
			$pmpro_msgt = "error";
		}
	}
	else
	{	
		//order passed?
		if(!empty($_REQUEST['order']))
		{
			$order_id = intval($_REQUEST['order']);
			if($order_id > 0)
				$order = new MemberOrder($order_id);
			elseif(!empty($_REQUEST['copy']))
			{
				$order = new MemberOrder(intval($_REQUEST['copy']));
				
				//new id
				$order->id = NULL;
				
				//new code
				$order->code = $order->getRandomCode();
			}
			else
			{
				$order = new MemberOrder();			//new order
				
				//defaults
				$order->code = $order->getRandomCode();
				$order->user_id = "";
				$order->membership_id = "";
				$order->billing->name = "";
				$order->billing->street = "";
				$order->billing->city = "";
				$order->billing->state = "";
				$order->billing->zip = "";
				$order->billing->country = "";
				$order->billing->phone = "";
				$order->tax = "";
				$order->couponamount = "";
				$order->total = "";
				$order->payment_type = "";
				$order->cardtype = "";
				$order->accountnumber = "";
				$order->expirationmonth = "";
				$order->expirationyear = "";				
				$order->status = "success";
				$order->gateway = pmpro_getOption("gateway");
				$order->gateway_environment = pmpro_getOption("gateway_environment");
				$order->payment_transaction_id = "";
				$order->subscription_transaction_id = "";
			}
		}
	}
	
?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?php echo PMPRO_URL?>/images/PaidMembershipsPro.gif" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro_tagline">Membership Plugin for WordPress</div>
		
		<div class="pmpro_meta"><a href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>">Plugin Support</a> | <a href="http://www.paidmembershipspro.com/forums/">User Forum</a> | <strong>Version <?php echo PMPRO_VERSION?></strong></div>
	</div>
	<br style="clear:both;" />		

<?php if(!empty($order)) { ?>

	<h2>
		<?php if(!empty($order->id)) { ?>
			Order #<?php echo $order->id?>: <?php echo $order->code?>
		<?php } else { ?>
			New Order
		<?php } ?>
	</h2>
	
	<?php if(!empty($pmpro_msg)) { ?>
		<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
	<?php } ?>
	
	<form method="post" action="">
		
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label>ID:</label></th>
					<td><?php if(!empty($order->id)) echo $order->id; else echo "This will be generated when you save.";?></td>
				</tr>								                
				
				<tr>
					<th scope="row" valign="top"><label for="code">Code:</label></th>
					<td>
						<input id="code" name="code" type="text" size="50" value="<?php echo esc_attr($order->code);?>" />
						<?php if($order_id < 0) { ?><small class="pmpro_lite">Randomly generated for you.</small><?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="user_id">User ID:</label></th>
					<td><input id="user_id" name="user_id" type="text" size="50" value="<?php echo esc_attr($order->user_id);?>" /></td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="membership_id">Membership Level ID:</label></th>
					<td><input id="membership_id" name="membership_id" type="text" size="50" value="<?php echo esc_attr($order->membership_id);?>" /></td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="billing_name">Billing Name:</label></th>
					<td><input id="billing_name" name="billing_name" type="text" size="50" value="<?php echo esc_attr($order->billing->name);?>" /></td>
				</tr>				
				<tr>
					<th scope="row" valign="top"><label for="billing_street">Billing Street:</label></th>
					<td><input id="billing_street" name="billing_street" type="text" size="50" value="<?php echo esc_attr($order->billing->street);?>" /></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_city">Billing City:</label></th>
					<td><input id="billing_city" name="billing_city" type="text" size="50" value="<?php echo esc_attr($order->billing->city);?>" /></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_state">Billing State:</label></th>
					<td><input id="billing_state" name="billing_state" type="text" size="50" value="<?php echo esc_attr($order->billing->state);?>" /></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_zip">Billing Postal Code:</label></th>
					<td><input id="billing_zip" name="billing_zip" type="text" size="50" value="<?php echo esc_attr($order->billing->zip);?>" /></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_country">Billing Country:</label></th>
					<td><input id="billing_country" name="billing_country" type="text" size="50" value="<?php echo esc_attr($order->billing->country);?>" /></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_phone">Billing Phone:</label></th>
					<td><input id="billing_phone" name="billing_phone" type="text" size="50" value="<?php echo esc_attr($order->billing->phone);?>" /></td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="tax">Tax:</label></th>
					<td>
						<input id="tax" name="tax" type="text" size="10" value="<?php echo esc_attr($order->tax);?>" />						
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="couponamount">Coupon Amount:</label></th>
					<td>
						<input id="couponamount" name="couponamount" type="text" size="10" value="<?php echo esc_attr($order->couponamount);?>" />
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="total">Total:</label></th>
					<td>
						<input id="total" name="total" type="text" size="10" value="<?php echo esc_attr($order->total);?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="payment_type">Payment Type:</label></th>
					<td>
						<input id="payment_type" name="payment_type" type="text" size="50" value="<?php echo esc_attr($order->payment_type);?>" />
						<small class="pmpro_lite">e.g. PayPal Express, PayPal Standard, Credit Card.</small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="cardtype">Card Type:</label></th>
					<td>
						<input id="cardtype" name="cardtype" type="text" size="50" value="<?php echo esc_attr($order->cardtype);?>" />
						<small class="pmpro_lite">e.g. Visa, MasterCard, AMEX, etc</small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="accountnumber">Account Number:</label></th>
					<td>
						<input id="accountnumber" name="accountnumber" type="text" size="50" value="<?php echo esc_attr($order->accountnumber);?>" />
						<small class="pmpro_lite">Obscure all but last 4 digits.</small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="expirationmonth">Expiration Month:</label></th>
					<td>
						<input id="expirationmonth" name="expirationmonth" type="text" size="10" value="<?php echo esc_attr($order->expirationmonth);?>" />
						<small class="pmpro_lite">MM</small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="expirationyear">Expiration Year:</label></th>
					<td>
						<input id="expirationyear" name="expirationyear" type="text" size="10" value="<?php echo esc_attr($order->expirationyear);?>" />
						<small class="pmpro_lite">YYYY</small>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="status">Status:</label></th>
					<td>
						<input id="status" name="status" type="text" size="20" value="<?php echo esc_attr($order->status);?>" />
						<small class="pmpro_lite">e.g. success, cancelled, review, token</small>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="gateway">Gateway:</label></th>
					<td>
						<select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
							<option value="" <?php if(empty($order->gateway)) { ?>selected="selected"<?php } ?>>Testing Only</option>
							<option value="check" <?php if($order->gateway == "check") { ?>selected="selected"<?php } ?>>Pay by Check</option>
							<option value="stripe" <?php if($order->gateway == "stripe") { ?>selected="selected"<?php } ?>>Stripe</option>
							<option value="paypalstandard" <?php if($order->gateway == "paypalstandard") { ?>selected="selected"<?php } ?>>PayPal Standard</option>
							<option value="paypalexpress" <?php if($order->gateway == "paypalexpress") { ?>selected="selected"<?php } ?>>PayPal Express</option>
							<option value="paypal" <?php if($order->gateway == "paypal") { ?>selected="selected"<?php } ?>>PayPal Website Payments Pro</option>
							<option value="payflowpro" <?php if($order->gateway == "payflowpro") { ?>selected="selected"<?php } ?>>PayPal Payflow Pro</option>
							<option value="authorizenet" <?php if($order->gateway == "authorizenet") { ?>selected="selected"<?php } ?>>Authorize.net</option>
						</select>  
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="gateway_environment">Gateway Environment:</label></th>
					<td>
						<select name="gateway_environment">
							<option value="sandbox" <?php if($order->gateway_environment == "sandbox") { ?>selected="selected"<?php } ?>>Sandbox/Testing</option>
							<option value="live" <?php if($order->gateway_environment == "live") { ?>selected="selected"<?php } ?>>Live/Production</option>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="payment_transaction_id">Payment Transaction ID:</label></th>
					<td>
						<input id="payment_transaction_id" name="payment_transaction_id" type="text" size="50" value="<?php echo esc_attr($order->payment_transaction_id);?>" />
						<small class="pmpro_lite">Generated by the gateway. Useful to cross reference orders.</small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="subscription_transaction_id">Subscription Transaction ID:</label></th>
					<td>
						<input id="subscription_transaction_id" name="subscription_transaction_id" type="text" size="50" value="<?php echo esc_attr($order->subscription_transaction_id);?>" />
						<small class="pmpro_lite">Generated by the gateway. Useful to cross reference subscriptions.</small>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="ts_month">Date:</label></th>
					<td>
						<?php
							//setup date vars
							if(!empty($order->timestamp))
								$timestamp = $order->timestamp;
							else
								$timestamp = time();
							
							$year = date("Y", $timestamp);
							$month = date("n", $timestamp);
							$day = date("j", $timestamp);
						?>
						<select id="ts_month" name="ts_month">
						<?php																
							for($i = 1; $i < 13; $i++)
							{
							?>
							<option value="<?php echo $i?>" <?php if($i == $month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $year))?></option>
							<?php
							}
						?>
						</select>
						<input name="ts_day" type="text" size="2" value="<?php echo $day?>" />
						<input name="ts_year" type="text" size="4" value="<?php echo $year?>" />
					</td>
				</tr>
				
				<?php 
					$affiliates = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE affiliate_id <> '' LIMIT 1");
					if(!empty($affiliates)) {					
				?>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_id">Affiliate ID:</label></th>
					<td><input id="affiliate_id" name="affiliate_id" type="text" size="50" value="<?php echo esc_attr($order->affiliate_id);?>" /></td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_subid">Affiliate SubID:</label></th>
					<td><input id="affiliate_subid" name="affiliate_subid" type="text" size="50" value="<?php echo esc_attr($order->affiliate_subid);?>" /></td>
				</tr>
				<?php } ?>
				
				<?php do_action("pmpro_after_order_settings", $order); ?>								
				
			</tbody>
		</table>
		
		<p class="submit topborder">
			<input name="order" type="hidden" value="<?php if(!empty($order->id)) echo $order->id; else echo $order_id;?>" />
			<input name="save" type="submit" class="button-primary" value="Save Order" /> 					
			<input name="cancel" type="button" value="Cancel" onclick="location.href='<?php echo get_admin_url(NULL, '/admin.php?page=pmpro-orders')?>';" />			
		</p>
		
	</form>

<?php } else { ?>
	
	<form id="posts-filter" method="get" action="">	
	<h2>
		Orders
		<a href="admin.php?page=pmpro-orders&order=-1" class="button add-new-h2">+ Add New Order</a>
		<a target="_blank" href="<?php echo admin_url('admin-ajax.php');?>?action=orders_csv&s=<?php echo $s?>&l=<?php echo $l?>" class="button add-new-h2">Export to CSV</a>
	</h2>	

	<?php if(!empty($pmpro_msg)) { ?>
		<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
	<?php } ?>
	
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
			$sqlQuery .= "ORDER BY o.id DESC, o.timestamp DESC ";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS id FROM $wpdb->pmpro_membership_orders ORDER BY id DESC, timestamp DESC ";
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
				<?php do_action("pmpro_orders_extra_cols_header", $order_ids);?>
				<th>Membership Level</th>
				<th>Total</th>
				<th>Payment</th>
				<th>Gateway</th>
				<th>Transaction IDs</th>	
				<th>Status</th>
				<th>Date</th>	
				<th></th>
				<th></th>
				<th></th>
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
							<td><a href="admin.php?page=pmpro-orders&order=<?php echo $order->id?>"><?php echo $order->id;?></a></td>
							<td><a href="admin.php?page=pmpro-orders&order=<?php echo $order->id?>"><?php echo $order->code;?></a></td>
							<td>
								<?php $order->getUser(); ?>		
								<?php if(!empty($order->user)) { ?>
									<a href="user-edit.php?user_id=<?php echo $order->user->ID?>"><?php echo $order->user->user_login?></a>
								<?php } else { ?>
									[deleted]
								<?php } ?>
							</td>						
							<?php do_action("pmpro_orders_extra_cols_body", $order);?>
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
							<td align="center">
								<a href="admin.php?page=pmpro-orders&order=<?php echo $order->id;?>">edit</a>
							</td>
							<td align="center">
								<a href="admin.php?page=pmpro-orders&order=-1&copy=<?php echo $order->id;?>">copy</a>
							</td>
							<td align="center">
								<a href="javascript:askfirst('Deleting orders is permanent and can affect active users. Are you sure you want to delete order <?php echo str_replace("'", "", $order->code);?>?', 'admin.php?page=pmpro-orders&delete=<?php echo $order->id;?>'); void(0);">delete</a>
							</td>
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

<?php } ?>
	
</div>
