<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_orders")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
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
		
	if(isset($_REQUEST['start-month']))
		$start_month = $_REQUEST['start-month'];
	else
		$start_month = "1";
		
	if(isset($_REQUEST['start-day']))
		$start_day = $_REQUEST['start-day'];
	else
		$start_day = "1";
		
	if(isset($_REQUEST['start-year']))
		$start_year = $_REQUEST['start-year'];
	else
		$start_year = date("Y");
		
	if(isset($_REQUEST['end-month']))
		$end_month = $_REQUEST['end-month'];
	else
		$end_month = date("n");
		
	if(isset($_REQUEST['end-day']))
		$end_day = $_REQUEST['end-day'];
	else
		$end_day = date("j");
		
	if(isset($_REQUEST['end-year']))
		$end_year = $_REQUEST['end-year'];
	else
		$end_year = date("Y");	
	
	if(isset($_REQUEST['predefined-date']))
		$predefined_date = $_REQUEST['predefined-date'];
	else
		$predefined_date = "This Month";		
			
	if(isset($_REQUEST['status']))
		$status = $_REQUEST['status'];
	else
		$status = "";
	
	
	//deleting?
	if(!empty($_REQUEST['delete']))
	{
		$dorder = new MemberOrder(intval($_REQUEST['delete']));
		if($dorder->deleteMe())
		{
			$pmpro_msg = __("Order deleted successfully.", "pmpro");
			$pmpro_msgt = "success";
		}
		else
		{
			$pmpro_msg = __("Error deleting order.", "pmpro");
			$pmpro_msgt = "error";
		}
	}
	
	if(isset($_REQUEST['filter']))
		$filter = sanitize_text_field($_REQUEST['filter']);
	else
		$filter = "all";
		
	$thisyear = date("Y");
	
	
	
	//this array stores fields that should be read only
	$read_only_fields = apply_filters("pmpro_orders_read_only_fields", array("code", "payment_transaction_id", "subscription_transaction_id"));
	
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
		if(!in_array("code", $read_only_fields))
			$order->code = $_POST['code'];
		if(!in_array("user_id", $read_only_fields))
			$order->user_id = intval($_POST['user_id']);
		if(!in_array("membership_id", $read_only_fields))
			$order->membership_id = intval($_POST['membership_id']);
		if(!in_array("billing_name", $read_only_fields))
			$order->billing->name = stripslashes($_POST['billing_name']);
		if(!in_array("billing_street", $read_only_fields))		
			$order->billing->street = stripslashes($_POST['billing_street']);
		if(!in_array("billing_city", $read_only_fields))
			$order->billing->city = stripslashes($_POST['billing_city']);
		if(!in_array("billing_state", $read_only_fields))
			$order->billing->state = stripslashes($_POST['billing_state']);
		if(!in_array("billing_zip", $read_only_fields))
			$order->billing->zip = $_POST['billing_zip'];
		if(!in_array("billing_country", $read_only_fields))
			$order->billing->country = stripslashes($_POST['billing_country']);
		if(!in_array("billing_phone", $read_only_fields))
			$order->billing->phone = $_POST['billing_phone'];
		if(!in_array("subtotal", $read_only_fields))
			$order->subtotal = $_POST['subtotal'];
		if(!in_array("tax", $read_only_fields))
			$order->tax = $_POST['tax'];
		if(!in_array("couponamount", $read_only_fields))
			$order->couponamount = $_POST['couponamount'];
		if(!in_array("total", $read_only_fields))
			$order->total = $_POST['total'];
		if(!in_array("payment_type", $read_only_fields))
			$order->payment_type = $_POST['payment_type'];
		if(!in_array("cardtype", $read_only_fields))
			$order->cardtype = $_POST['cardtype'];
		if(!in_array("accountnumber", $read_only_fields))
			$order->accountnumber = $_POST['accountnumber'];
		if(!in_array("expirationmonth", $read_only_fields))
			$order->expirationmonth = $_POST['expirationmonth'];		
		if(!in_array("expirationyear", $read_only_fields))
			$order->expirationyear = $_POST['expirationyear'];
		if(!in_array("ExpirationDate", $read_only_fields))
			$order->ExpirationDate = $order->expirationmonth . $order->expirationyear;
		if(!in_array("status", $read_only_fields))
			$order->status = stripslashes($_POST['status']);
		if(!in_array("gateway", $read_only_fields))
			$order->gateway = $_POST['gateway'];
		if(!in_array("gateway_environment", $read_only_fields))
			$order->gateway_environment = $_POST['gateway_environment'];
		if(!in_array("payment_transaction_id", $read_only_fields))
			$order->payment_transaction_id = $_POST['payment_transaction_id'];
		if(!in_array("subscription_transaction_id", $read_only_fields))
			$order->subscription_transaction_id = $_POST['subscription_transaction_id'];
		if(!in_array("notes", $read_only_fields))
			$order->notes = stripslashes($_POST['notes']);
		
		//affiliate stuff
		$affiliates = apply_filters("pmpro_orders_show_affiliate_ids", false);
		if(!empty($affiliates))
		{
			if(!in_array("affiliate_id", $read_only_fields))
				$order->affiliate_id = $_POST['affiliate_id'];
			if(!in_array("affiliate_subid", $read_only_fields))
				$order->affiliate_subid = $_POST['affiliate_subid'];
		}
		
		//save
		if($order->saveOrder() !== false)
		{		
			//handle timestamp
			if($order->updateTimestamp($_POST['ts_year'], $_POST['ts_month'], $_POST['ts_day']) !== false)
			{
				$pmpro_msg = __("Order saved successfully.", "pmpro");
				$pmpro_msgt = "success";
			}
			else
			{
				$pmpro_msg = __("Error updating order timestamp.", "pmpro");
				$pmpro_msgt = "error";
			}
		}	
		else
		{
			$pmpro_msg = __("Error saving order.", "pmpro");
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
				$order->subtotal = "";
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
				$order->affiliate_id = "";
				$order->affiliate_subid = "";
				$order->notes = "";
			}
		}
	}
	
	require_once(dirname(__FILE__) . "/admin_header.php");	
?>

<?php if(!empty($order)) { ?>

	<h2>
		<?php if(!empty($order->id)) { ?>
			<?php _e('Order', 'pmpro');?> #<?php echo $order->id?>: <?php echo $order->code?>
		<?php } else { ?>
			<?php _e('New Order', 'pmpro');?>
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
					<td><?php if(!empty($order->id)) echo $order->id; else echo __("This will be generated when you save.", "pmpro");?></td>
				</tr>								                
				
				<tr>
					<th scope="row" valign="top"><label for="code"><?php _e('Code', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("code", $read_only_fields)) { echo $order->code; } else { ?>
							<input id="code" name="code" type="text" size="50" value="<?php echo esc_attr($order->code);?>" />
						<?php } ?>
						<?php if($order_id < 0) { ?><small class="pmpro_lite"><?php _e('Randomly generated for you.', 'pmpro');?></small><?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="user_id"><?php _e('User ID', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("user_id", $read_only_fields) && $order_id > 0) { echo $order->user_id; } else { ?>
							<input id="user_id" name="user_id" type="text" size="50" value="<?php echo esc_attr($order->user_id);?>" />
						<?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="membership_id"><?php _e('Membership Level ID', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("membership_id", $read_only_fields) && $order_id > 0) { echo $order->membership_id; } else { ?>
							<input id="membership_id" name="membership_id" type="text" size="50" value="<?php echo esc_attr($order->membership_id);?>" />
						<?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="billing_name"><?php _e('Billing Name', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_name", $read_only_fields) && $order_id > 0) { echo $order->billing_name; } else { ?>
							<input id="billing_name" name="billing_name" type="text" size="50" value="<?php echo esc_attr($order->billing->name);?>" />
						<?php } ?>
					</td>
				</tr>				
				<tr>
					<th scope="row" valign="top"><label for="billing_street"><?php _e('Billing Street', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_street", $read_only_fields) && $order_id > 0) { echo $order->billing_street; } else { ?>
							<input id="billing_street" name="billing_street" type="text" size="50" value="<?php echo esc_attr($order->billing->street);?>" /></td>
						<?php } ?>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_city"><?php _e('Billing City', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_city", $read_only_fields) && $order_id > 0) { echo $order->billing_city; } else { ?>
							<input id="billing_city" name="billing_city" type="text" size="50" value="<?php echo esc_attr($order->billing->city);?>" /></td>
						<?php } ?>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_state"><?php _e('Billing State', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_state", $read_only_fields) && $order_id > 0) { echo $order->billing_state; } else { ?>
							<input id="billing_state" name="billing_state" type="text" size="50" value="<?php echo esc_attr($order->billing->state);?>" /></td>
						<?php } ?>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_zip"><?php _e('Billing Postal Code', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_zip", $read_only_fields) && $order_id > 0) { echo $order->billing_zip; } else { ?>
							<input id="billing_zip" name="billing_zip" type="text" size="50" value="<?php echo esc_attr($order->billing->zip);?>" /></td>
						<?php } ?>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_country"><?php _e('Billing Country', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_country", $read_only_fields) && $order_id > 0) { echo $order->billing_country; } else { ?>
							<input id="billing_country" name="billing_country" type="text" size="50" value="<?php echo esc_attr($order->billing->country);?>" />
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="billing_phone"><?php _e('Billing Phone', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("billing_phone", $read_only_fields) && $order_id > 0) { echo $order->billing_phone; } else { ?>
							<input id="billing_phone" name="billing_phone" type="text" size="50" value="<?php echo esc_attr($order->billing->phone);?>" />
						<?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="subtotal"><?php _e('Sub Total', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("subtotal", $read_only_fields) && $order_id > 0) { echo $order->subtotal; } else { ?>
							<input id="subtotal" name="subtotal" type="text" size="10" value="<?php echo esc_attr($order->subtotal);?>" />						
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="tax"><?php _e('Tax', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("tax", $read_only_fields) && $order_id > 0) { echo $order->tax; } else { ?>
							<input id="tax" name="tax" type="text" size="10" value="<?php echo esc_attr($order->tax);?>" />						
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="couponamount"><?php _e('Coupon Amount', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("couponamount", $read_only_fields) && $order_id > 0) { echo $order->couponamount; } else { ?>
							<input id="couponamount" name="couponamount" type="text" size="10" value="<?php echo esc_attr($order->couponamount);?>" />
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="total"><?php _e('Total', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("total", $read_only_fields) && $order_id > 0) { echo $order->total; } else { ?>							
							<input id="total" name="total" type="text" size="10" value="<?php echo esc_attr($order->total);?>" />
						<?php } ?>
						<small class="pmpro_lite"><?php _e('Should be subtotal + tax - couponamount.', 'pmpro');?></small>	
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="payment_type"><?php _e('Payment Type', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("payment_type", $read_only_fields) && $order_id > 0) { echo $order->payment_type; } else { ?>
							<input id="payment_type" name="payment_type" type="text" size="50" value="<?php echo esc_attr($order->payment_type);?>" />
						<?php } ?>
						<small class="pmpro_lite"><?php _e('e.g. PayPal Express, PayPal Standard, Credit Card.', 'pmpro');?></small>						
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="cardtype"><?php _e('Card Type', 'pmpro');?></label></th>
					<td>
						<?php if(in_array("cardtype", $read_only_fields) && $order_id > 0) { echo $order->cardtype; } else { ?>
							<input id="cardtype" name="cardtype" type="text" size="50" value="<?php echo esc_attr($order->cardtype);?>" />
						<?php } ?>
						<small class="pmpro_lite"><?php _e('e.g. Visa, MasterCard, AMEX, etc', 'pmpro');?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="accountnumber"><?php _e('Account Number', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("accountnumber", $read_only_fields) && $order_id > 0) { echo $order->accountnumber; } else { ?>
							<input id="accountnumber" name="accountnumber" type="text" size="50" value="<?php echo esc_attr($order->accountnumber);?>" />
						<?php } ?>
						<small class="pmpro_lite"><?php _e('Obscure all but last 4 digits.', 'pmpro');?></small>
					</td>
				</tr>
				<?php if(in_array("ExpirationDate", $read_only_fields) && $order_id > 0) { echo $order->ExpirationDate; } else { ?>
				<tr>
					<th scope="row" valign="top"><label for="expirationmonth"><?php _e('Expiration Month', 'pmpro');?>:</label></th>
					<td>
						<input id="expirationmonth" name="expirationmonth" type="text" size="10" value="<?php echo esc_attr($order->expirationmonth);?>" />
						<small class="pmpro_lite">MM</small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="expirationyear"><?php _e('Expiration Year', 'pmpro');?>:</label></th>
					<td>
						<input id="expirationyear" name="expirationyear" type="text" size="10" value="<?php echo esc_attr($order->expirationyear);?>" />
						<small class="pmpro_lite">YYYY</small>
					</td>
				</tr>
				<?php } ?>				
				<tr>
					<th scope="row" valign="top"><label for="status"><?php _e('Status', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("status", $read_only_fields) && $order_id > 0) { echo $order->status; } else { ?>
						<?php
							$statuses = array();
							$default_statuses = array("", "success", "cancelled", "review", "token", "refunded");
							$used_statuses = $wpdb->get_col("SELECT DISTINCT(status) FROM $wpdb->pmpro_membership_orders");
							$statuses = array_unique(array_merge($default_statuses, $used_statuses));
							asort($statuses);
							$statuses = apply_filters("pmpro_order_statuses", $statuses);													
						?>
						<select id="status" name="status">
							<?php foreach($statuses as $status) { ?>
								<option value="<?php echo esc_attr($status);?>" <?php selected($order->status, $status);?>><?php echo $status;?></option>
							<?php } ?>
						</select>	
						<?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="gateway"><?php _e('Gateway', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("gateway", $read_only_fields) && $order_id > 0) { echo $order->gateway; } else { ?>
						<select id="gateway" name="gateway" onchange="pmpro_changeGateway(jQuery(this).val());">
							<option value="" <?php if(empty($order->gateway)) { ?>selected="selected"<?php } ?>><?php _e('Testing Only', 'pmpro');?></option>
							<option value="check" <?php if($order->gateway == "check") { ?>selected="selected"<?php } ?>><?php _e('Pay by Check', 'pmpro');?></option>
							<option value="stripe" <?php if($order->gateway == "stripe") { ?>selected="selected"<?php } ?>>Stripe</option>
							<option value="paypalstandard" <?php if($order->gateway == "paypalstandard") { ?>selected="selected"<?php } ?>>PayPal Standard</option>
							<option value="paypalexpress" <?php if($order->gateway == "paypalexpress") { ?>selected="selected"<?php } ?>>PayPal Express</option>
							<option value="paypal" <?php if($order->gateway == "paypal") { ?>selected="selected"<?php } ?>>PayPal Website Payments Pro</option>
							<option value="payflowpro" <?php if($order->gateway == "payflowpro") { ?>selected="selected"<?php } ?>>PayPal Payflow Pro</option>
							<option value="authorizenet" <?php if($order->gateway == "authorizenet") { ?>selected="selected"<?php } ?>>Authorize.net</option>
						</select>  
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="gateway_environment"><?php _e('Gateway Environment', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("gateway_environment", $read_only_fields) && $order_id > 0) { echo $order->gateway_environment; } else { ?>
						<select name="gateway_environment">
							<option value="sandbox" <?php if($order->gateway_environment == "sandbox") { ?>selected="selected"<?php } ?>><?php _e('Sandbox/Testing', 'pmpro');?></option>
							<option value="live" <?php if($order->gateway_environment == "live") { ?>selected="selected"<?php } ?>><?php _e('Live/Production', 'pmpro');?></option>
						</select>
						<?php } ?>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="payment_transaction_id"><?php _e('Payment Transaction ID', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("payment_transaction_id", $read_only_fields) && $order_id > 0) { echo $order->payment_transaction_id; } else { ?>
							<input id="payment_transaction_id" name="payment_transaction_id" type="text" size="50" value="<?php echo esc_attr($order->payment_transaction_id);?>" />
						<?php } ?>
						<small class="pmpro_lite"><?php _e('Generated by the gateway. Useful to cross reference orders.', 'pmpro');?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="subscription_transaction_id"><?php _e('Subscription Transaction ID', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("subscription_transaction_id", $read_only_fields) && $order_id > 0) { echo $order->subscription_transaction_id; } else { ?>
							<input id="subscription_transaction_id" name="subscription_transaction_id" type="text" size="50" value="<?php echo esc_attr($order->subscription_transaction_id);?>" />
						<?php } ?>
						<small class="pmpro_lite"><?php _e('Generated by the gateway. Useful to cross reference subscriptions.', 'pmpro');?></small>
					</td>
				</tr>
				
				<tr>
					<th scope="row" valign="top"><label for="ts_month"><?php _e('Date', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("timestamp", $read_only_fields) && $order_id > 0) { echo date(option("date_format"), $order->timestamp); } else { ?>
						<?php
							//setup date vars
							if(!empty($order->timestamp))
								$timestamp = $order->timestamp;
							else
								$timestamp = current_time('timestamp');
							
							$year = date("Y", $timestamp);
							$month = date("n", $timestamp);
							$day = date("j", $timestamp);
						?>
						<select id="ts_month" name="ts_month">
						<?php																
							for($i = 1; $i < 13; $i++)
							{
							?>
							<option value="<?php echo $i?>" <?php if($i == $month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $year, current_time("timestamp")))?></option>
							<?php
							}
						?>
						</select>
						<input name="ts_day" type="text" size="2" value="<?php echo $day?>" />
						<input name="ts_year" type="text" size="4" value="<?php echo $year?>" />
						<?php } ?>
					</td>
				</tr>
				
				<?php 
					$affiliates = apply_filters("pmpro_orders_show_affiliate_ids", false);
					if(!empty($affiliates)) {					
				?>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_id"><?php _e('Affiliate ID', 'pmpro');?>Affiliate ID:</label></th>
					<td>
						<?php if(in_array("affiliate_id", $read_only_fields) && $order_id > 0) { echo $order->affiliate_id; } else { ?>
							<input id="affiliate_id" name="affiliate_id" type="text" size="50" value="<?php echo esc_attr($order->affiliate_id);?>" />
						<?php } ?>
					</td>						
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="affiliate_subid"><?php _e('Affiliate SubID', 'pmpro');?>Affiliate SubID:</label></th>
					<td>
						<?php if(in_array("affiliate_subid", $read_only_fields) && $order_id > 0) { echo $order->affiliate_subid; } else { ?>
							<input id="affiliate_subid" name="affiliate_subid" type="text" size="50" value="<?php echo esc_attr($order->affiliate_subid);?>" />
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
				
				<tr>
					<th scope="row" valign="top"><label for="notes"><?php _e('Notes', 'pmpro');?>:</label></th>
					<td>
						<?php if(in_array("notes", $read_only_fields) && $order_id > 0) { echo $order->notes; } else { ?>
							<textarea id="notes" name="notes" rows="5" cols="80"><?php echo esc_textarea($order->notes);?></textarea>
						<?php } ?>
					</td>
				</tr>
				
				<?php do_action("pmpro_after_order_settings", $order); ?>								
				
			</tbody>
		</table>
		
		<p class="submit topborder">
			<input name="order" type="hidden" value="<?php if(!empty($order->id)) echo $order->id; else echo $order_id;?>" />
			<input name="save" type="submit" class="button-primary" value="<?php _e('Save Order', 'pmpro');?>" /> 					
			<input name="cancel" type="button" class="cancel button-secondary" value="<?php _e('Cancel', 'pmpro');?>" onclick="location.href='<?php echo get_admin_url(NULL, '/admin.php?page=pmpro-orders')?>';" />			
		</p>
		
	</form>

<?php } else { ?>
	
	<form id="posts-filter" method="get" action="">	
	<h2>
		<?php _e('Orders', 'pmpro');?>
		<a href="admin.php?page=pmpro-orders&order=-1" class="add-new-h2">+ <?php _e('Add New Order', 'pmpro');?></a>
		
		<?php
			//build the export URL
			$export_url = admin_url('admin-ajax.php') . "?action=orders_csv";
			$url_params = array(
				"filter"=>$filter,
				"s"=>$s,
				"l"=>$l,
				"start-month"=>$start_month,
				"start-day"=>$start_day,
				"start-year"=>$start_year,
				"end-month"=>$end_month,
				"end-day"=>$end_day,
				"end-year"=>$end_year,
				"predefined-date"=>$predefined_date,
				"status"=>$status			
			);			
			$export_url = add_query_arg($url_params, $export_url);
		?>		
		<a target="_blank" href="<?php echo $export_url;?>" class="add-new-h2"><?php _e('Export to CSV', 'pmpro');?></a>
	</h2>
	
		

	<?php if(!empty($pmpro_msg)) { ?>
		<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
	<?php } ?>
	
	
	<ul class="subsubsub">
		<li>			
			<?php _ex('Show', 'Dropdown label, e.g. Show Daily Orders for January', 'pmpro')?>
			<select id="filter" name="filter">
				<option value="all" <?php selected($filter, "all");?>><?php _e('All', 'pmpro');?></option>
				<option value="within-a-date-range" <?php selected($filter, "within-a-date-range");?>><?php _e('Within a Date Range', 'pmpro');?></option>
				<option value="predefined-date-range" <?php selected($filter, "predefined-date-range");?>><?php _e('Predefined Date Range', 'pmpro');?></option>
				<option value="within-a-level" <?php selected($filter, "within-a-level");?>><?php _e('Within a Level', 'pmpro');?></option>
				<option value="within-a-status" <?php selected($filter, "within-a-status");?>><?php _e('Within a Status', 'pmpro');?></option>
			</select>
			
			<span id="from"><?php _ex('From', 'Dropdown label', 'pmpro')?></span>
			
			<select id="start-month" name="start-month">
				<?php for($i = 1; $i < 13; $i++) { ?>
					<option value="<?php echo $i;?>" <?php selected($start_month, $i);?>><?php echo date("F", mktime(0, 0, 0, $i));?></option>
				<?php } ?>
			</select>
			
			<input id='start-day' name="start-day" type="text" size="2" value="<?php echo $start_day?>" />
			<input id='start-year' name="start-year" type="text" size="4" value="<?php echo $start_year?>" />


			<span id="to"><?php _ex('To', 'Dropdown label', 'pmpro')?></span>

			<select id="end-month" name="end-month">
				<?php for($i = 1; $i < 13; $i++) { ?>
					<option value="<?php echo $i;?>" <?php selected($end_month, $i);?>><?php echo date("F", mktime(0, 0, 0, $i));?></option>
				<?php } ?>
			</select>
			

			<input id='end-day' name="end-day" type="text" size="2" value="<?php echo $end_day?>" />
			<input id='end-year' name="end-year" type="text" size="4" value="<?php echo $end_year?>" />
			
			<span id="filterby"><?php _ex('filter by ', 'Dropdown label', 'pmpro')?></span>
			
			<select id="predefined-date" name="predefined-date">
				
					<option value="<?php echo "This Month";?>" <?php selected($predefined_date, "This Month");?>><?php echo "This Month";?></option>
					<option value="<?php echo "Last Month";?>" <?php selected($predefined_date, "Last Month");?>><?php echo "Last Month";?></option>
					<option value="<?php echo "This Year";?>" <?php selected($predefined_date, "This Year");?>><?php echo "This Year";?></option>
					<option value="<?php echo "Last Year";?>" <?php selected($predefined_date, "Last Year");?>><?php echo "Last Year";?></option>
				
			</select>

			<?php
			//Note: only orders belonging to current levels can be filtered. There is no option for orders belonging to deleted levels
			 $levels = pmpro_getAllLevels(); 
					
			?>
			<select id="l" name="l">
			<?php foreach($levels as $level) { ?>
				<option value="<?php echo $level->id;?>" <?php selected($l, $level->id);?>><?php echo $level->name;?></option>	
			<?php } ?>
				
			</select>
			
	<?php
	$statuses = array();
	$default_statuses = array("", "success", "cancelled", "review", "token", "refunded");
	$used_statuses = $wpdb->get_col("SELECT DISTINCT(status) FROM $wpdb->pmpro_membership_orders");
	$statuses = array_unique(array_merge($default_statuses, $used_statuses));
	asort($statuses);
	$statuses = apply_filters("pmpro_order_statuses", $statuses);	
	?>
	<select id="status" name="status">
		<?php foreach($statuses as $the_status) { ?>
		<option value="<?php echo esc_attr($the_status);?>" <?php selected($the_status, $status);?>><?php echo $the_status;?></option>
		<?php } ?>
	</select>			
		
		
		<input id="submit" type="submit" value="<?php _ex('Filter', 'Submit button value.', 'pmpro');?>" />
		</li>
	</ul>
	
	<script>
		//update month/year when period dropdown is changed
		jQuery(document).ready(function() {
			jQuery('#filter').change(function() {
				pmpro_ShowMonthOrYear();
			});
		});
		
		function pmpro_ShowMonthOrYear()
		{
			var filter = jQuery('#filter').val();
			if(filter == 'all')
			{
				jQuery('#start-month').hide();
				jQuery('#start-day').hide();
				jQuery('#start-year').hide();
				jQuery('#end-month').hide();
				jQuery('#end-day').hide();
				jQuery('#end-year').hide();
				jQuery('#predefined-date').hide();
				jQuery('#status').hide();
				jQuery('#l').hide();
				jQuery('#from').hide();
				jQuery('#to').hide();
				jQuery('#submit').show();
				jQuery('#filterby').hide();
			}
			else if(filter == 'within-a-date-range')
			{
				jQuery('#start-month').show();
				jQuery('#start-day').show();
				jQuery('#start-year').show();
				jQuery('#end-month').show();
				jQuery('#end-day').show();
				jQuery('#end-year').show();
				jQuery('#predefined-date').hide();
				jQuery('#status').hide();
				jQuery('#l').hide();
				jQuery('#submit').show();
				jQuery('#from').show();
				jQuery('#to').show();
				jQuery('#filterby').hide();
			}
			else if(filter == 'predefined-date-range')
			{
				jQuery('#start-month').hide();
				jQuery('#start-day').hide();
				jQuery('#start-year').hide();
				jQuery('#end-month').hide();
				jQuery('#end-day').hide();
				jQuery('#end-year').hide();
				jQuery('#predefined-date').show();
				jQuery('#status').hide();
				jQuery('#l').hide();
				jQuery('#submit').show();
				jQuery('#from').hide();
				jQuery('#to').hide();
				jQuery('#filterby').show();
			}
			else if(filter == 'within-a-level')
			{
				jQuery('#start-month').hide();
				jQuery('#start-day').hide();
				jQuery('#start-year').hide();
				jQuery('#end-month').hide();
				jQuery('#end-day').hide();
				jQuery('#end-year').hide();
				jQuery('#predefined-date').hide();
				jQuery('#status').hide();
				jQuery('#l').show();
				jQuery('#submit').show();
				jQuery('#from').hide();
				jQuery('#to').hide();
				jQuery('#filterby').show();
			}
			else if(filter == 'within-a-status')
			{
				jQuery('#start-month').hide();
				jQuery('#start-day').hide();
				jQuery('#start-year').hide();
				jQuery('#end-month').hide();
				jQuery('#end-day').hide();
				jQuery('#end-year').hide();
				jQuery('#predefined-date').hide();
				jQuery('#status').show();
				jQuery('#l').hide();
				jQuery('#submit').show();
				jQuery('#from').hide();
				jQuery('#to').hide();
				jQuery('#filterby').show();
			}
		}
		
		pmpro_ShowMonthOrYear();
		
		
	</script>
	
	<p class="search-box">
		<label class="hidden" for="post-search-input"><?php _e('Search Orders', 'pmpro');?>:</label>
		<input type="hidden" name="page" value="pmpro-orders" />		
		<input id="post-search-input" type="text" value="<?php echo $s?>" name="s"/>
		<input class="button" type="submit" value="<?php _e('Search Orders', 'pmpro');?>"/>
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
					
		//filters
		if($filter == "all" || !$filter)
				$condition = "1=1";
		elseif($filter == "within-a-date-range")
		{	
			$start_date = $start_year."-".$start_month."-".$start_day;
			$end_date = $end_year."-".$end_month."-".$end_day;
			
			//add times to dates
			$start_date =  $start_date . " 00:00:00";
			$end_date =  $end_date . " 23:59:59";
			
			$condition = "timestamp BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		elseif($filter == "predefined-date-range")
		{	
			if($predefined_date == "Last Month")
			{
				$start_date = date("Y-m-d", strtotime("first day of last month", current_time("timestamp")));
				$end_date   = date("Y-m-d", strtotime("last day of last month", current_time("timestamp")));
			}
			elseif($predefined_date == "This Month")
			{
				$start_date = date("Y-m-d", strtotime("first day of this month", current_time("timestamp")));
				$end_date   = date("Y-m-d", strtotime("last day of this month", current_time("timestamp")));
			}
			elseif($predefined_date == "This Year")
			{
				$year = date('Y');
				$start_date = date("Y-m-d", strtotime("first day of January $year", current_time("timestamp")));
				$end_date   = date("Y-m-d", strtotime("last day of December $year", current_time("timestamp")));
			}
			
			elseif($predefined_date == "Last Year")
			{
				$year = date('Y') - 1;
				$start_date = date("Y-m-d", strtotime("first day of January $year", current_time("timestamp")));
				$end_date   = date("Y-m-d", strtotime("last day of December $year", current_time("timestamp")));
			}
		
			//add times to dates
			$start_date =  $start_date . " 00:00:00";
			$end_date =  $end_date . " 23:59:59";
		
			$condition = "timestamp BETWEEN '".$start_date."' AND '".$end_date."'";
		}			
		elseif($filter == "within-a-level")
		{
			$condition = "membership_id = $l";
		}			
		elseif($filter == "within-a-status")
		{
			$condition = "status = '$status' ";
		}		
		
		//string search
		if($s)
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS o.id FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->users u ON o.user_id = u.ID LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id ";
			
			$join_with_usermeta = apply_filters("pmpro_orders_search_usermeta", false);
			if($join_with_usermeta)
				$sqlQuery .= "LEFT JOIN $wpdb->usermeta um ON o.user_id = um.user_id ";
			
			$sqlQuery .= "WHERE (1=2 ";
			
			$fields = array("o.id", "o.code", "o.billing_name", "o.billing_street", "o.billing_city", "o.billing_state", "o.billing_zip", "o.billing_phone", "o.payment_type", "o.cardtype", "o.accountnumber", "o.status", "o.gateway", "o.gateway_environment", "o.payment_transaction_id", "o.subscription_transaction_id", "u.user_login", "u.user_email", "u.display_name", "l.name");
			
			if($join_with_usermeta)
				$fields[] = "um.meta_value";
			
			$fields = apply_filters("pmpro_orders_search_fields", $fields);
			
			foreach($fields as $field)
				$sqlQuery .= " OR " . $field . " LIKE '%" . esc_sql($s) . "%' ";
			$sqlQuery .= ") ";
			
			$sqlQuery .= "AND " . $condition . " ";
			
			$sqlQuery .= "GROUP BY o.id ORDER BY o.id DESC, o.timestamp DESC ";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS id FROM $wpdb->pmpro_membership_orders WHERE ".$condition." ORDER BY id DESC, timestamp DESC ";
		}
		
		$sqlQuery .= "LIMIT $start, $limit";
				
		$order_ids = $wpdb->get_col($sqlQuery);
		
		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");
		
		if($order_ids)
		{		
		?>
		<p class="clear"><?php printf(__("%d orders found.", "pmpro"), $totalrows);?></span></p>
		<?php
		}		
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th><?php _e('ID', 'pmpro');?></th>
				<th><?php _e('Code', 'pmpro');?></th>
				<th><?php _e('User', 'pmpro');?></th>
				<?php do_action("pmpro_orders_extra_cols_header", $order_ids);?>
				<th><?php _e('Membership Level', 'pmpro');?></th>
				<th><?php _e('Total', 'pmpro');?></th>
				<th><?php _e('Payment', 'pmpro');?></th>
				<th><?php _e('Gateway', 'pmpro');?></th>
				<th><?php _e('Transaction IDs', 'pmpro');?></th>	
				<th><?php _e('Status', 'pmpro');?></th>
				<th><?php _e('Date', 'pmpro');?></th>	
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
									[<?php _e('deleted', 'pmpro');?>]
								<?php } ?>
							</td>						
							<?php do_action("pmpro_orders_extra_cols_body", $order);?>
							<td><?php echo $order->membership_id;?></td>
							<td><?php echo $pmpro_currency_symbol . $order->total;?></td>
							<td>
								<?php if(!empty($order->payment_type)) echo $order->payment_type . "<br />";?>
								<?php if(!empty($order->accountnumber)) { ?>
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
								<?php _e('Payment', 'pmpro');?>: <?php if(!empty($order->payment_transaction_id)) echo $order->payment_transaction_id; else echo "N/A";?>
								<br />
								<?php _e('Subscription', 'pmpro');?>: <?php if(!empty($order->subscription_transaction_id)) echo $order->subscription_transaction_id; else echo "N/A";?>	
							</td>
							<td><?php echo $order->status;?></td>
							<td><?php echo date(get_option('date_format'), $order->timestamp);?></td>
							<td align="center">
								<a href="admin.php?page=pmpro-orders&order=<?php echo $order->id;?>"><?php _e('edit', 'pmpro');?></a>
							</td>
							<td align="center">
								<a href="admin.php?page=pmpro-orders&order=-1&copy=<?php echo $order->id;?>"><?php _e('copy', 'pmpro');?></a>
							</td>
							<td align="center">
								<a href="javascript:askfirst('<?php printf(__("Deleting orders is permanent and can affect active users. Are you sure you want to delete order %s?", "pmpro"), str_replace("'", "", $order->code));?>', 'admin.php?page=pmpro-orders&delete=<?php echo $order->id;?>'); void(0);"><?php _e('delete', 'pmpro');?></a>
							</td>
						</tr>
					<?php
				}
				
				if(!$order_ids)
				{
				?>
				<tr>
					<td colspan="9"><p><?php _e('No orders found.', 'pmpro');?></p></td>
				</tr>
				<?php
				}
			?>		
		</tbody>
	</table>
	</form>
	
	<?php
		//add normal args
		$pagination_url = add_query_arg($url_params, get_admin_url(NULL, "/admin.php?page=pmpro-orders"));		
		echo pmpro_getPaginationString($pn, $totalrows, $limit, 1, $pagination_url, "&limit=$limit&pn=");
	?>

<?php } ?>
	
<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>
