<?php
	class PMProEmail
	{
		function PMProEmail()
		{
			$this->email = $this->from = $this->fromname = $this->subject = $this->template = $this->data = $this->body = NULL;
		}					
		
		function sendEmail($email = NULL, $from = NULL, $fromname = NULL, $subject = NULL, $template = NULL, $data = NULL)
		{
			//if values were passed
			if($email)
				$this->email = $email;
			if($from)
				$this->from = $from;
			if($fromname)
				$this->fromname = $fromname;
			if($subject)
				$this->subject = $subject;
			if($template)
				$this->template = $template;
			if($data)
				$this->data = $data;
		
			//default values
			global $current_user;
			if(!$this->email)
				$this->email = $current_user->user_email;
				
			if(!$this->from)
				$this->from = pmpro_getOption("from_email");
			
			if(!$this->fromname)
				$this->fromname = pmpro_getOption("from_name");
			
			if(!$this->subject)
				$this->subject = "An Email From " . get_option("blogname");
			
			//decode the subject line in case there are apostrophes/etc in it
			$this->subject = html_entity_decode($this->subject, ENT_QUOTES, 'UTF-8');
	
			if(!$this->template)
				$this->template = "default";
						
			$this->headers = array("Content-Type: text/html");
			
			//load the template
			if(file_exists(TEMPLATEPATH . "/membership-email-" . $this->template . ".html"))
				$this->body = file_get_contents(TEMPLATEPATH . "/membership-email-" . $this->template . ".html");
			else
				$this->body = file_get_contents(PMPRO_DIR . "/email/" . $this->template . ".html");			
						
			//header and footer
			/* This is handled for all emails via the pmpro_send_html function in paid-memberships-pro now
			if(file_exists(TEMPLATEPATH . "/email_header.html"))
			{
				$this->body = file_get_contents(TEMPLATEPATH . "/email_header.html") . "\n" . $this->body;
			}			
			if(file_exists(TEMPLATEPATH . "/email_footer.html"))
			{
				$this->body = $this->body . "\n" . file_get_contents(TEMPLATEPATH . "/email_footer.html");
			}
			*/
			
			//swap data
			if(is_string($this->data))
				$data = array("body"=>$data);			
			if(is_array($this->data))
			{
				foreach($this->data as $key => $value)
				{
					$this->body = str_replace("!!" . $key . "!!", $value, $this->body);
				}
			}
			
			//filters
			$this->email = apply_filters("pmpro_email_recipient", $this->email, $this);
			$this->from = apply_filters("pmpro_email_sender", $this->from, $this);
			$this->fromname = apply_filters("pmpro_email_sender_name", $this->fromname, $this);
			$this->subject = apply_filters("pmpro_email_subject", $this->subject, $this);
			$this->template = apply_filters("pmpro_email_template", $this->template, $this);
			$this->body = apply_filters("pmpro_email_body", $this->body, $this);
			$this->headers = apply_filters("pmpro_email_headers", $this->headers, $this);
			
			if(wp_mail($this->email,$this->subject,$this->body,$this->headers))
			{
				return true;
			}
			else
			{
				return false;
			}		
		}
		
		function sendCancelEmail($user = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = "Your membership at " . get_option("blogname") . " has been CANCELED";
			$this->template = "cancel";
			$this->data = array("name" => $user->display_name, "sitename" => get_option("blogname"), "siteemail" => pmpro_getOption("from_email"));
			
			return $this->sendEmail();
		}
		
		function sendCancelAdminEmail($user = NULL, $old_level_id)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//check settings
			$send = pmpro_getOption("email_admin_cancels");
			if(empty($send))
				return true;	//didn't send, but we also don't want to indicate failure because the settings say to not send
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = "Membership for " . $user->user_login . " at " . get_option("blogname") . " has been CANCELED";
			$this->template = "cancel_admin";
			$this->data = array("user_login" => $user->user_login, "user_email" => $user->user_email, "display_name" => $user->display_name, "sitename" => get_option("blogname"), "siteemail" => pmpro_getOption("from_email"));
			$this->data['membership_level_name'] = $wpdb->get_var("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id = '" . $old_level_id . "' LIMIT 1");
			
			//start and end date
			$startdate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(startdate) as startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND membership_id = '" . $old_level_id . "' AND status = 'inactive' ORDER BY id DESC");
			if(!empty($startdate))
				$this->data['startdate'] = date(get_option('date_format'), $startdate);
			else
				$this->data['startdate'] = "";
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) as enddate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND membership_id = '" . $old_level_id . "' AND status = 'inactive' ORDER BY id DESC");
			if(!empty($enddate))
				$this->data['enddate'] = date(get_option('date_format'), $enddate);
			else
				$this->data['enddate'] = "";	
				
			return $this->sendEmail();
		}
		
		function sendCheckoutEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user, $pmpro_currency_symbol;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = "Your membership confirmation for " . get_option("blogname");	
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"membership_cost" => pmpro_getLevelCost($user->membership_level),								
								"login_link" => pmpro_url("account"),
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,0								
							);						
			
			if($invoice)
			{									
				if($invoice->gateway == "paypalexpress")
					$this->template = "checkout_express";
				elseif($invoice->gateway == "check")
				{
					$this->template = "checkout_check";
					$this->data["instructions"] = wpautop(pmpro_getOption("instructions"));
				}
				elseif(pmpro_isLevelTrial($user->membership_level))
					$this->template = "checkout_trial";
				else
					$this->template = "checkout_paid";
				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = $pmpro_currency_symbol . number_format($invoice->total, 2);
				$this->data["invoice_date"] = date(get_option('date_format'), $invoice->timestamp);
				$this->data["billing_name"] = $invoice->billing->name;
				$this->data["billing_street"] = $invoice->billing->street;
				$this->data["billing_city"] = $invoice->billing->city;
				$this->data["billing_state"] = $invoice->billing->state;
				$this->data["billing_zip"] = $invoice->billing->zip;
				$this->data["billing_country"] = $invoice->billing->country;
				$this->data["billing_phone"] = $invoice->billing->phone;
				$this->data["cardtype"] = $invoice->cardtype;
				$this->data["accountnumber"] = hideCardNumber($invoice->accountnumber);
				$this->data["expirationmonth"] = $invoice->expirationmonth;
				$this->data["expirationyear"] = $invoice->expirationyear;
				
				if($invoice->getDiscountCode())
					$this->data["discount_code"] = "<p>Discount Code: " . $invoice->discount_code->code . "</p>\n";
				else
					$this->data["discount_code"] = "";
			}
			elseif(pmpro_isLevelFree($user->membership_level))
			{
				$this->template = "checkout_free";		
				$this->data["discount_code"] = "";		
			}						
			else
			{
				$this->template = "checkout_freetrial";
				$this->data["discount_code"] = "";
			}
			
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>This membership will expire on " . date(get_option('date_format'), $enddate) . ".</p>\n";
			else
				$this->data["membership_expiration"] = "";
			
			return $this->sendEmail();
		}
		
		function sendCheckoutAdminEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user, $pmpro_currency_symbol;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//check settings
			$send = pmpro_getOption("email_admin_checkout");
			if(empty($send))
				return true;	//didn't send, but we also don't want to indicate failure because the settings say to not send
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = "Member Checkout for " . $user->membership_level->name . " at " . get_option("blogname");	
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"membership_cost" => pmpro_getLevelCost($user->membership_level),								
								"login_link" => pmpro_url("account"),
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,0								
							);						
			
			if($invoice)
			{									
				if($invoice->gateway == "paypalexpress")
					$this->template = "checkout_express_admin";
				elseif($invoice->gateway == "check")
					$this->template = "checkout_check_admin";					
				elseif(pmpro_isLevelTrial($user->membership_level))
					$this->template = "checkout_trial_admin";
				else
					$this->template = "checkout_paid_admin";
				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = $pmpro_currency_symbol . number_format($invoice->total, 2);
				$this->data["invoice_date"] = date(get_option('date_format'), $invoice->timestamp);
				$this->data["billing_name"] = $invoice->billing->name;
				$this->data["billing_street"] = $invoice->billing->street;
				$this->data["billing_city"] = $invoice->billing->city;
				$this->data["billing_state"] = $invoice->billing->state;
				$this->data["billing_zip"] = $invoice->billing->zip;
				$this->data["billing_country"] = $invoice->billing->country;
				$this->data["billing_phone"] = $invoice->billing->phone;
				$this->data["cardtype"] = $invoice->cardtype;
				$this->data["accountnumber"] = hideCardNumber($invoice->accountnumber);
				$this->data["expirationmonth"] = $invoice->expirationmonth;
				$this->data["expirationyear"] = $invoice->expirationyear;
				
				if($invoice->getDiscountCode())
					$this->data["discount_code"] = "<p>Discount Code: " . $invoice->discount_code->code . "</p>\n";
				else
					$this->data["discount_code"] = "";
			}
			elseif(pmpro_isLevelFree($user->membership_level))
			{
				$this->template = "checkout_free_admin";		
				$this->data["discount_code"] = "";		
			}						
			else
			{
				$this->template = "checkout_freetrial_admin";
				$this->data["discount_code"] = "";
			}
			
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>This membership will expire on " . date(get_option('date_format'), $enddate) . ".</p>\n";
			else
				$this->data["membership_expiration"] = "";
			
			return $this->sendEmail();
		}
		
		function sendBillingEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = "Your billing information has been udpated at " . get_option("blogname");	
			$this->template = "billing";
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,																	
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_country" => $invoice->billing->country,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => pmpro_url("account")
							);
		
			return $this->sendEmail();
		}
		
		function sendBillingAdminEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			//check settings
			$send = pmpro_getOption("email_admin_billing");
			if(empty($send))
				return true;	//didn't send, but we also don't want to indicate failure because the settings say to not send
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = "Billing information has been udpated for " . $user->user_login . " at " . get_option("blogname");	
			$this->template = "billing_admin";
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,																	
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_country" => $invoice->billing->country,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => wp_login_url()
							);
		
			return $this->sendEmail();
		}
		
		function sendBillingFailureEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = "Membership Payment Failed at " . get_option("blogname");	
			$this->template = "billing_failure";
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,									
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_country" => $invoice->billing->country,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => pmpro_url("billing")
							);
		
			return $this->sendEmail();
		}
		
		function sendBillingFailureAdminEmail($email, $invoice = NULL)
		{		
			if(!$invoice)			
				return false;
				
			$user = get_userdata($invoice->user_id);
			
			$this->email = $email;
			$this->subject = "Membership Payment Failed For " . $user->display_name . " at " . get_option("blogname");	
			$this->template = "billing_failure_admin";
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => "Admin", 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,									
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_country" => $invoice->billing->country,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => pmpro_url("billing")
							);
		
			return $this->sendEmail();
		}
		
		function sendInvoiceEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user, $pmpro_currency_symbol;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = "INVOICE for " . get_option("blogname") . " membership";	
			$this->template = "invoice";
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,	
								"invoice_id" => $invoice->payment_transaction_id,
								"invoice_total" => $pmpro_currency_symbol . number_format($invoice->total, 2),
								"invoice_date" => date(get_option('date_format'), $invoice->timestamp),								
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_country" => $invoice->billing->country,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => pmpro_url("account"),
								"invoice_link" => pmpro_url("invoice", "?invoice=" . $invoice->code)
							);
		
			if($invoice->getDiscountCode())
				$this->data["discount_code"] = "<p>Discount Code: " . $invoice->discount_code . "</p>\n";
			else
				$this->data["discount_code"] = "";
		
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>This membership will expire on " . date(get_option('date_format'), $enddate) . ".</p>\n";
			else
				$this->data["membership_expiration"] = "";
				
			return $this->sendEmail();
		}
		
		function sendTrialEndingEmail($user = NULL)
		{
			global $current_user, $wpdb, $pmpro_currency_symbol;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//make sure we have the current membership level data
			/*$user->membership_level = $wpdb->get_row("SELECT l.id AS ID, l.name AS name, UNIX_TIMESTAMP(mu.startdate) as startdate, mu.billing_amount, mu.cycle_number, mu.cycle_period, mu.trial_amount, mu.trial_limit
														FROM {$wpdb->pmpro_membership_levels} AS l
														JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
														WHERE mu.user_id = " . $user->ID . "
														LIMIT 1");*/
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
						
			$this->email = $user->user_email;
			$this->subject = "Your trial membership at " . get_option("blogname") . " is ending soon";
			$this->template = "trial_ending";
			$this->data = array(
				"subject" => $this->subject, 
				"name" => $user->display_name, 
				"sitename" => get_option("blogname"), 				
				"membership_level_name" => $user->membership_level->name, 
				"siteemail" => get_bloginfo("admin_email"), 
				"login_link" => wp_login_url(), 
				"display_name" => $user->display_name, 
				"user_email" => $user->user_email, 
				"billing_amount" => $pmpro_currency_symbol . $user->membership_level->billing_amount, 
				"cycle_number" => $user->membership_level->cycle_number, 
				"cycle_period" => $user->membership_level->cycle_period, 
				"trial_amount" => $pmpro_currency_symbol . $user->membership_level->trial_amount, 
				"trial_limit" => $user->membership_level->trial_limit,
				"trial_end" => date(get_option('date_format'), strtotime(date("m/d/Y", $user->membership_level->startdate) . " + " . $user->membership_level->trial_limit . " " . $user->membership_level->cycle_period))
			);			
			
			return $this->sendEmail();
		}
		
		function sendMembershipExpiredEmail($user = NULL)
		{
			global $current_user, $wpdb;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;						
						
			$this->email = $user->user_email;
			$this->subject = "Your membership at " . get_option("blogname") . " has ended";
			$this->template = "membership_expired";
			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "sitename" => get_option("blogname"), "siteemail" => get_bloginfo("admin_email"), "login_link" => wp_login_url(), "display_name" => $user->display_name, "user_email" => $user->user_email, "levels_link" => pmpro_url("levels"));			
			
			return $this->sendEmail();
		}
		
		function sendMembershipExpiringEmail($user = NULL)
		{
			global $current_user, $wpdb;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//make sure we have the current membership level data
			/*$user->membership_level = $wpdb->get_row("SELECT l.id AS ID, l.name AS name, UNIX_TIMESTAMP(mu.enddate) as enddate
														FROM {$wpdb->pmpro_membership_levels} AS l
														JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
														WHERE mu.user_id = " . $user->ID . "
														LIMIT 1");*/
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
						
			$this->email = $user->user_email;
			$this->subject = "Your membership at " . get_option("blogname") . " will end soon";
			$this->template = "membership_expiring";
			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "sitename" => get_option("blogname"), "membership_level_name" => $user->membership_level->name, "siteemail" => get_bloginfo("admin_email"), "login_link" => wp_login_url(), "enddate" => date(get_option('date_format'), $user->membership_level->enddate), "display_name" => $user->display_name, "user_email" => $user->user_email);			
			
			return $this->sendEmail();
		}
		
		function sendAdminChangeEmail($user = NULL)
		{
			global $current_user, $wpdb;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//make sure we have the current membership level data
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
						
			$this->email = $user->user_email;
			$this->subject = "Your membership at " . get_option("blogname") . " has been changed";
			$this->template = "admin_change";
			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "sitename" => get_option("blogname"), "membership_level_name" => $user->membership_level->name, "siteemail" => get_bloginfo("admin_email"), "login_link" => wp_login_url());
			if($user->membership_level->ID)
				$this->data["membership_change"] = "new level is " . $user->membership_level->name . ". This membership is free";
			else
				$this->data["membership_change"] = "membership has been canceled";
			
			if(!empty($user->membership_level->enddate))
			{
					$this->data["membership_change"] .= ". Your membership will expire on " . date(get_option('date_format'), $user->membership_level->enddate);
			}
			elseif(!empty($this->expiration_changed))
			{
				$this->data["membership_change"] .= ". Your membership does not expire";
			}
			
			return $this->sendEmail();
		}
		
		function sendAdminChangeAdminEmail($user = NULL)
		{
			global $current_user, $wpdb;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//check settings
			$send = pmpro_getOption("email_admin_changes");
			if(empty($send))
				return true;	//didn't send, but we also don't want to indicate failure because the settings say to not send
			
			//make sure we have the current membership level data
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
						
			$this->email = get_bloginfo("admin_email");
			$this->subject = "Membership for " . $user->user_login . " at " . get_option("blogname") . " has been changed";
			$this->template = "admin_change_admin";
			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "sitename" => get_option("blogname"), "membership_level_name" => $user->membership_level->name, "siteemail" => get_bloginfo("admin_email"), "login_link" => wp_login_url());
			if($user->membership_level->ID)
				$this->data["membership_change"] = "The new level is " . $user->membership_level->name . ". This membership is free";
			else
				$this->data["membership_change"] = "membership has been canceled";
			
			if(!empty($user->membership_level->enddate))
			{
					$this->data["membership_change"] .= ". This membership will expire on " . date(get_option('date_format'), $user->membership_level->enddate);
			}
			elseif(!empty($this->expiration_changed))
			{
				$this->data["membership_change"] .= ". This membership does not expire";
			}
			
			return $this->sendEmail();
		}
	}
