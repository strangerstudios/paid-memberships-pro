<?php
	class MemberOrder
	{
		function MemberOrder($id = NULL)
		{			
			//setup the gateway			
			$this->setGateway(pmpro_getOption("gateway"));
			
			//get data if an id was passed
			if($id)
			{
				if(is_numeric($id))
					return $this->getMemberOrderByID($id);
				else
					return $this->getMemberOrderByCode($id);
			}
			else
				return true;	//blank constructor
		}	
		
		function getMemberOrderByID($id)
		{
			global $wpdb;
			
			if(!$id)
				return false;
			
			$gmt_offset = get_option('gmt_offset');
			$dbobj = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(timestamp) + " . ($gmt_offset * 3600) . "  as timestamp FROM $wpdb->pmpro_membership_orders WHERE id = '$id' LIMIT 1");
			
			if($dbobj)
			{				
				$this->id = $dbobj->id;
				$this->code = $dbobj->code;
				$this->session_id = $dbobj->session_id;
				$this->user_id = $dbobj->user_id;
				$this->membership_id = $dbobj->membership_id;
				$this->paypal_token = $dbobj->paypal_token;				
				$this->billing = new stdClass();
				$this->billing->name = $dbobj->billing_name;
				$this->billing->street = $dbobj->billing_street;
				$this->billing->city = $dbobj->billing_city;
				$this->billing->state = $dbobj->billing_state;
				$this->billing->zip = $dbobj->billing_zip;
				$this->billing->country = $dbobj->billing_country;
				$this->billing->phone = $dbobj->billing_phone;
				
				//split up some values
				$nameparts = pnp_split_full_name($this->billing->name);
				
				if(!empty($nameparts['fname']))
					$this->FirstName = $nameparts['fname'];
				else
					$this->FirstName = "";
				if(!empty($nameparts['lname']))
					$this->LastName = $nameparts['lname'];
				else
					$this->LastName = "";
				
				$this->Address1 = $this->billing->street;
				
				//get email from user_id
				$this->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . $this->user_id . "' LIMIT 1");
				
				$this->subtotal = $dbobj->subtotal;
				$this->tax = $dbobj->tax;
				$this->couponamount = $dbobj->couponamount;
				$this->certificate_id = $dbobj->certificate_id;
				$this->certificateamount = $dbobj->certificateamount;
				$this->total = $dbobj->total;
				$this->payment_type = $dbobj->payment_type;
				$this->cardtype = $dbobj->cardtype;
				$this->accountnumber = trim($dbobj->accountnumber);
				$this->expirationmonth = $dbobj->expirationmonth;
				$this->expirationyear = $dbobj->expirationyear;
				
				//date formats sometimes useful
				$this->ExpirationDate = $this->expirationmonth . $this->expirationyear;
				$this->ExpirationDate_YdashM = $this->expirationyear . "-" . $this->expirationmonth;				
				
				$this->status = $dbobj->status;
				$this->gateway = $dbobj->gateway;
				$this->gateway_environment = $dbobj->gateway_environment;
				$this->payment_transaction_id = $dbobj->payment_transaction_id;
				$this->subscription_transaction_id = $dbobj->subscription_transaction_id;
				$this->timestamp = $dbobj->timestamp;
				$this->affiliate_id = $dbobj->affiliate_id;
				$this->affiliate_subid = $dbobj->affiliate_subid;
				
				//reset the gateway
				$this->setGateway();
				
				return $this->id;
			}
			else
				return false;	//didn't find it in the DB
		}
		
		function setGateway($gateway = NULL)
		{
			//set the gateway property
			if(isset($gateway))
			{
				$this->gateway = $gateway;
			}
			
			//which one to load?
			$classname = "PMProGateway";	//default test gateway
			if(!empty($this->gateway))
				$classname .= "_" . $this->gateway;	//adding the gateway suffix
							
			//try to load it
			require_once(dirname(__FILE__) . "/gateways/class." . strtolower($classname) . ".php");
			if(class_exists($classname))
				$this->Gateway = new $classname($this->gateway);
			else
				die("Could not locate the gateway class file with class name = " . $classname . ".");
			
			return $this->Gateway;
		}
		
		function getLastMemberOrder($user_id = NULL)
		{
			global $current_user, $wpdb;
			if(!$user_id)
				$user_id = $current_user->ID;
			
			if(!$user_id)
				return false;
				
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' ORDER BY timestamp DESC LIMIT 1");
			
			return $this->getMemberOrderByID($id);
		}
		
		function getMemberOrderByCode($code)
		{
			global $wpdb;
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE code = '" . $code . "' LIMIT 1");
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}
		
		function getMemberOrderByPaymentTransactionID($payment_transaction_id)
		{
			global $wpdb;
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE payment_transaction_id = '" . $wpdb->escape($payment_transaction_id) . "' LIMIT 1");
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}
		
		function getMemberOrderByPayPalToken($token)
		{
			global $wpdb;
			$id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE paypal_token = '" . $token . "' LIMIT 1");			
			if($id)
				return $this->getMemberOrderByID($id);
			else
				return false;
		}
		
		function getDiscountCode($force = false)
		{
			if(!empty($this->discount_code) && !$force)
				return $this->discount_code;
				
			global $wpdb;
			$this->discount_code = $wpdb->get_row("SELECT dc.* FROM $wpdb->pmpro_discount_codes dc LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu ON dc.id = dcu.code_id WHERE dcu.order_id = '" . $this->id . "' LIMIT 1");
			
			return $this->discount_code;
		}
		
		function getUser()
		{
			global $wpdb;
			
			if(!empty($this->user))
				return $this->invoice->user;
				
			$gmt_offset = get_option('gmt_offset');
			$this->user = $wpdb->get_row("SELECT *, UNIX_TIMESTAMP(user_registered) + " . ($gmt_offset * 3600) . "  as user_registered FROM $wpdb->users WHERE ID = '" . $this->user_id . "' LIMIT 1");				
			return $this->user;						
		}
		
		function getMembershipLevel($force = false)
		{
			global $wpdb;
			
			if(!empty($this->membership_level) && empty($force))
				return $this->membership_level;
			
			//check if there is an entry in memberships_users first
			if(!empty($this->user_id))
			{
				$this->membership_level = $wpdb->get_row("SELECT l.id, l.name, l.description, l.allow_signups, mu.*, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE l.id = '" . $this->membership_id . "' AND mu.user_id = '" . $this->user_id . "' LIMIT 1");			
			}			
			
			//okay, do I have a discount code to check? (if there is no membership_level->membership_id value, that means there was no entry in memberships_users)
			if(!empty($this->discount_code) && empty($this->membership_level->membership_id))
			{
				$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $this->discount_code . "' AND cl.level_id = '" . $this->membership_id . "' LIMIT 1";			
				$this->membership_level = $wpdb->get_row($sqlQuery);
			}
			
			//just get the info from the membership table	(sigh, I really need to standardize the column names for membership_id/level_id) but we're checking if we got the information already or not
			if(empty($this->membership_level->membership_id) && empty($this->membership_level->level_id))
			{
				$this->membership_level = $wpdb->get_row("SELECT l.* FROM $wpdb->pmpro_membership_levels l WHERE l.id = '" . $this->membership_id . "' LIMIT 1");			
			}
			
			return $this->membership_level;	
		}
		
		function getTaxForPrice($price)
		{
			//get options
			$tax_state = pmpro_getOption("tax_state");
			$tax_rate = pmpro_getOption("tax_rate");
						
			//default
			$tax = 0;
			
			//calculate tax
			if($tax_state && $tax_rate)
			{
				//we have values, is this order in the tax state?
				if(trim(strtoupper($this->billing->state)) == trim(strtoupper($tax_state)))
				{															
					//return value, pass through filter
					$tax = round((float)$price * (float)$tax_rate, 2);					
				}
			}
			
			//set values array for filter
			$values = array("price" => $price, "tax_state" => $tax_state, "tax_rate" => $tax_rate);
			if(!empty($this->billing->state))
				$values['billing_state'] = $this->billing->state;
			if(!empty($this->billing->city))
				$values['billing_city'] = $this->billing->city;
			if(!empty($this->billing->zip))
				$values['billing_zip'] = $this->billing->zip;
			if(!empty($this->billing->country))
				$values['billing_country'] = $this->billing->country;
						
			//filter
			$tax = apply_filters("pmpro_tax", $tax, $values, $this);			
			return $tax;
		}
		
		function getTax($force = false)
		{
			if(!empty($this->tax) && !$force)
				return $this->tax;
		
			//reset
			$this->tax = $this->getTaxForPrice($this->subtotal);			
						
			return $this->tax;
		}
		
		function saveOrder()
		{			
			global $current_user, $wpdb;
			
			//get a random code to use for the public ID
			if(!$this->code)
				$this->code = $this->getRandomCode();
			
			//figure out how much we charged
			if(!empty($this->InitialPayment))
				$amount = $this->InitialPayment;
			else
				$amount = 0;
						
			//Todo: Tax?!, Coupons, Certificates, affiliates
			$this->subtotal = $amount;
			$tax = $this->getTax(true);
			$this->certificate_id = "";
			$this->certificateamount = "";
			
			//these fix some warnings/notices
			if(empty($this->paypal_token))
				$this->paypal_token = "";
			if(empty($this->couponamount))
				$this->couponamount = "";
			if(empty($this->payment_type))
				$this->payment_type = "";
			if(empty($this->subscription_transaction_id))
				$this->subscription_transaction_id = "";
			if(empty($this->affiliate_id))
				$this->affiliate_id = "";
			if(empty($this->affiliate_subid))
				$this->affiliate_subid = "";
			
			//build query			
			if(!empty($this->id))
			{
				//set up actions
				$before_action = "pmpro_update_order";
				$after_action = "pmpro_updated_order";
				//update
				$this->sqlQuery = "UPDATE $wpdb->pmpro_membership_orders
									SET `code` = '" . $this->code . "',
									`session_id` = '" . $this->session_id . "',
									`user_id` = '" . $this->user_id . "',
									`membership_id` = '" . $this->membership_id . "',
									`paypal_token` = '" . $this->paypal_token . "',
									`billing_name` = '" . $this->billing->name . "',
									`billing_street` = '" . $this->billing->street . "',
									`billing_city` = '" . $this->billing->city . "',
									`billing_state` = '" . $this->billing->state . "',
									`billing_zip` = '" . $this->billing->zip . "',
									`billing_country` = '" . $this->billing->country . "',
									`billing_phone` = '" . $this->billing->phone . "',
									`subtotal` = '" . $this->subtotal . "',
									`tax` = '" . $this->tax . "',
									`couponamount` = '" . $this->couponamount . "',
									`certificate_id` = '" . $this->certificate_id . "',
									`certificateamount` = '" . $this->certificateamount . "',
									`total` = '" . $this->total . "',
									`payment_type` = '" . $this->payment_type . "',
									`cardtype` = '" . $this->cardtype . "',
									`accountnumber` = '" . $this->accountnumber . "',
									`expirationmonth` = '" . $this->expirationmonth . "',
									`expirationyear` = '" . $this->expirationyear . "',
									`status` = '" . $this->status . "',
									`gateway` = '" . $this->gateway . "',
									`gateway_environment` = '" . $this->gateway_environment . "',
									`payment_transaction_id` = '" . $this->payment_transaction_id . "',
									`subscription_transaction_id` = '" . $this->subscription_transaction_id . "',									
									`affiliate_id` = '" . $this->affiliate_id . "',
									`affiliate_subid` = '" . $this->affiliate_subid . "'
									WHERE id = '" . $this->id . "'
									LIMIT 1";
			}
			else
			{
				//set up actions
				$before_action = "pmpro_add_order";
				$after_action = "pmpro_added_order";
				//insert
				$this->sqlQuery = "INSERT INTO $wpdb->pmpro_membership_orders  
								(`code`, `session_id`, `user_id`, `membership_id`, `paypal_token`, `billing_name`, `billing_street`, `billing_city`, `billing_state`, `billing_zip`, `billing_country`, `billing_phone`, `subtotal`, `tax`, `couponamount`, `certificate_id`, `certificateamount`, `total`, `payment_type`, `cardtype`, `accountnumber`, `expirationmonth`, `expirationyear`, `status`, `gateway`, `gateway_environment`, `payment_transaction_id`, `subscription_transaction_id`, `timestamp`, `affiliate_id`, `affiliate_subid`) 
								VALUES('" . $this->code . "',
									   '" . session_id() . "',
									   '" . $this->user_id . "',
									   '" . $this->membership_id . "',
									   '" . $this->paypal_token . "',
									   '" . $wpdb->escape(trim($this->billing->name)) . "',
									   '" . $wpdb->escape(trim($this->billing->street)) . "',
									   '" . $wpdb->escape($this->billing->city) . "',
									   '" . $wpdb->escape($this->billing->state) . "',
									   '" . $wpdb->escape($this->billing->zip) . "',
									   '" . $wpdb->escape($this->billing->country) . "',
									   '" . cleanPhone($this->billing->phone) . "',
									   '" . $amount . "',
									   '" . $tax . "',
									   '" . $this->couponamount. "',
									   '" . $this->certificate_id . "',
									   '" . $this->certificateamount . "',
									   '" . ((float)$amount + (float)$tax) . "',
									   '" . $this->payment_type . "',
									   '" . $this->cardtype . "',
									   '" . hideCardNumber($this->accountnumber, false) . "',
									   '" . substr($this->ExpirationDate, 0, 2) . "',
									   '" . substr($this->ExpirationDate, 2, 4) . "',
									   '" . $this->status . "',
									   '" . pmpro_getOption("gateway") . "', 
									   '" . pmpro_getOption("gateway_environment") . "', 
									   '" . $this->payment_transaction_id . "',
									   '" . $this->subscription_transaction_id . "',
									   now(),
									   '" . $this->affiliate_id . "',
									   '" . $this->affiliate_subid . "'
									   )";
			}
			
			do_action($before_action, $this);
			if($wpdb->query($this->sqlQuery) !== false)
			{
				if(empty($this->id))
					$this->id = $wpdb->insert_id;
				do_action($after_action, $this);
				return $this->getMemberOrderByID($this->id);
			}
			else
			{				
				return false;
			}
		}
				
		function getRandomCode()
		{
			global $wpdb;
			
			while(empty($code))
			{
				$scramble = md5(AUTH_KEY . time() . SECURE_AUTH_KEY);			
				$code = substr($scramble, 0, 10);
				$check = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE code = '$code' LIMIT 1");				
				if($check || is_numeric($code))
					$code = NULL;
			}
			
			return strtoupper($code);
		}
		
		function updateStatus($newstatus)
		{
			global $wpdb;
			
			if(empty($this->id))
				return false;
		
			$this->status = $newstatus;
			$this->sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET status = '" . $wpdb->escape($newstatus) . "' WHERE id = '" . $this->id . "' LIMIT 1";
			if($wpdb->query($this->sqlQuery) !== false)
				return true;
			else
				return false;
		}
		
		function process()
		{
			return $this->Gateway->process($this);						
		}
		
		function cancel()
		{
			//only need to cancel on the gateway if there is a subscription id
			if(empty($this->subscription_transaction_id))
			{
				//just mark as cancelled
				$this->updateStatus("cancelled");					
				return true;
			}
			else
			{			
				//cancel the gateway subscription first				
				return $this->Gateway->cancel($this);					
			}
		}
		
		function updateBilling()
		{
			return $this->Gateway->update($this);						
		}									
	}
