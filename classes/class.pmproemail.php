<?php
	class PMProEmail
	{
		function PMProEmail()
		{
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
				
			if(!$this->template)
				$this->template = "default";
			
			//load the template
			if(file_exists(TEMPLATEPATH . "/membership-email-" . $this->template . ".html"))
				$this->body = file_get_contents(TEMPLATEPATH . "/membership-email-" . $this->template . ".html");
			else
				$this->body = file_get_contents(ABSPATH . "/wp-content/plugins/paid-memberships-pro/email/" . $this->template . ".html");			
			
			//header and footer
			if(file_exists(TEMPLATEPATH . "/email_header.html"))
			{
				$this->body = file_get_contents(TEMPLATEPATH . "/email_header.html") . "\n" . $this->body;
			}			
			if(file_exists(TEMPLATEPATH . "/email_footer.html"))
			{
				$this->body = $this->body . "\n" . file_get_contents(TEMPLATEPATH . "/email_footer.html");
			}	
			
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
			
			//prep email
			$this->mailer = new PHPMailer();
			$this->mailer->From = $this->from;
			$this->mailer->FromName = $this->fromname;
			$this->mailer->AddAddress($this->email);	
			$this->mailer->Subject = $this->subject;
			$this->mailer->Body = $this->body;								
			$this->mailer->AltBody = strip_tags(pmpro_br2nl($this->body, array("br", "p")));
			
			//send email			
			if($this->mailer->send())
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
		
		function sendCheckoutEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
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
				if(pmpro_isLevelTrial($user->membership_level))
					$this->template = "checkout_trial";
				else
					$this->template = "checkout_paid";
				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = number_format($invoice->total, 2);
				$this->data["invoice_date"] = date("F j, Y", $invoice->timestamp);
				$this->data["billing_name"] = $invoice->billing->name;
				$this->data["billing_street"] = $invoice->billing->street;
				$this->data["billing_city"] = $invoice->billing->city;
				$this->data["billing_state"] = $invoice->billing->state;
				$this->data["billing_zip"] = $invoice->billing->zip;
				$this->data["billing_phone"] = $invoice->billing->phone;
				$this->data["cardtype"] = $invoice->cardtype;
				$this->data["accountnumber"] = hideCardNumber($invoice->accountnumber);
				$this->data["expirationmonth"] = $invoice->expirationmonth;
				$this->data["expirationyear"] = $invoice->expirationyear;
			}
			elseif(pmpro_isLevelFree($user->membership_level))
			{
				$this->template = "checkout_free";				
			}						
			else
			{
				$this->template = "checkout_freetrial";
			}
			
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
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,																	
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => pmpro_url("account")
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
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,									
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
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
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,									
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
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
			global $current_user;
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
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,	
								"invoice_id" => $invoice->payment_transaction_id,
								"invoice_total" => number_format($invoice->total, 2),
								"invoice_date" => date("F j, Y", $invoice->timestamp),								
								"billing_name" => $invoice->billing->name,
								"billing_street" => $invoice->billing->street,
								"billing_city" => $invoice->billing->city,
								"billing_state" => $invoice->billing->state,
								"billing_zip" => $invoice->billing->zip,
								"billing_phone" => $invoice->billing->phone,
								"cardtype" => $invoice->cardtype,
								"accountnumber" => hideCardNumber($invoice->accountnumber),
								"expirationmonth" => $invoice->expirationmonth,
								"expirationyear" => $invoice->expirationyear,
								"login_link" => pmpro_url("account"),
								"invoice_link" => pmpro_url("invoice", "?invoice=" . $invoice->code)
							);
		
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
			$user->membership_level = $wpdb->get_row("SELECT l.id AS ID, l.name AS name
														FROM {$wpdb->pmpro_membership_levels} AS l
														JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
														WHERE mu.user_id = " . $user->ID . "
														LIMIT 1");		
						
			$this->email = $user->user_email;
			$this->subject = "Your membership at " . get_option("blogname") . " has been changed";
			$this->template = "admin_change";
			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "sitename" => get_option("blogname"), "membership_level_name" => $user->membership_level->name, "siteemail" => get_bloginfo("admin_email"), "login_link" => wp_login_url());
			if($user->membership_level->ID)
				$this->data["membership_change"] = "new level is " . $user->membership_level->name . ". This membership is free";
			else
				$this->data["membership_change"] = "membership has been canceled";
			
			return $this->sendEmail();
		}
	}
?>