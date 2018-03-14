<?php
	class PMProEmail
	{
		function __construct()
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
				$this->subject = sprintf(__("An Email From %s", 'paid-memberships-pro' ), get_option("blogname"));
			
			//decode the subject line in case there are apostrophes/etc in it
			$this->subject = html_entity_decode($this->subject, ENT_QUOTES, 'UTF-8');
	
			if(!$this->template)
				$this->template = "default";
						
			$this->headers = array("Content-Type: text/html");
			
			$this->attachments = NULL;
			
			//load the template			
			$locale = apply_filters("plugin_locale", get_locale(), "paid-memberships-pro");

			if(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/email/" . $locale . "/" . $this->template . ".html"))
				$this->body = file_get_contents(get_stylesheet_directory() . "/paid-memberships-pro/email/" . $locale . "/" . $this->template . ".html");	//localized email folder in child theme
			elseif(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/email/" . $this->template . ".html"))
				$this->body = file_get_contents(get_stylesheet_directory() . "/paid-memberships-pro/email/" . $this->template . ".html");	//email folder in child theme
			elseif(file_exists(get_stylesheet_directory() . "/membership-email-" . $this->template . ".html"))
				$this->body = file_get_contents(get_stylesheet_directory() . "/membership-email-" . $this->template . ".html");			//membership-email- file in child theme
			elseif(file_exists(get_template_directory() . "/paid-memberships-pro/email/" . $locale . "/" . $this->template . ".html"))	
				$this->body = file_get_contents(get_template_directory() . "/paid-memberships-pro/email/" . $locale . "/" . $this->template . ".html");	//localized email folder in parent theme
			elseif(file_exists(get_template_directory() . "/paid-memberships-pro/email/" . $this->template . ".html"))
				$this->body = file_get_contents(get_template_directory() . "/paid-memberships-pro/email/" . $this->template . ".html");	//email folder in parent theme
			elseif(file_exists(get_template_directory() . "/membership-email-" . $this->template . ".html"))
				$this->body = file_get_contents(get_template_directory() . "/membership-email-" . $this->template . ".html");			//membership-email- file in parent theme			
			elseif(file_exists(WP_LANG_DIR . '/pmpro/email/' . $locale . "/" . $this->template . ".html"))
				$this->body = file_get_contents(WP_LANG_DIR . '/pmpro/email/' . $locale . "/" . $this->template . ".html");				//localized email folder in WP language folder
			elseif(file_exists(WP_LANG_DIR . '/pmpro/email/' . $this->template . ".html"))
				$this->body = file_get_contents(WP_LANG_DIR . '/pmpro/email/' . $this->template . ".html");								//email folder in WP language folder
			elseif(file_exists(PMPRO_DIR . "/languages/email/" . $locale . "/" . $this->template . ".html"))
				$this->body = file_get_contents(PMPRO_DIR . "/languages/email/" . $locale . "/" . $this->template . ".html");					//email folder in PMPro language folder
			elseif($this->getDefaultEmailTemplate($this->template))
				$this->body = $this->getDefaultEmailTemplate($this->template);
			elseif(file_exists(PMPRO_DIR . "/email/" . $this->template . ".html"))
				$this->body = file_get_contents(PMPRO_DIR . "/email/" . $this->template . ".html");										//default template in plugin
			elseif(!empty($this->data) && !empty($this->data['body']))
				$this->body = $this->data['body'];																						//data passed in

			//header and footer
			/* This is handled for all emails via the pmpro_send_html function in paid-memberships-pro now
			if(file_exists(get_template_directory() . "/email_header.html"))
			{
				$this->body = file_get_contents(get_template_directory() . "/email_header.html") . "\n" . $this->body;
			}			
			if(file_exists(get_template_directory() . "/email_footer.html"))
			{
				$this->body = $this->body . "\n" . file_get_contents(get_template_directory() . "/email_footer.html");
			}
			*/
			
			//if data is a string, assume we mean to replace !!body!! with it
			if(is_string($this->data))
				$this->data = array("body"=>$data);											
				
			//filter for data
			$this->data = apply_filters("pmpro_email_data", $this->data, $this);	//filter
			
			//swap data into body
			if(is_array($this->data))
			{
				foreach($this->data as $key => $value)
				{
					if ( 'body' != $key ) {
						$this->body = str_replace("!!" . $key . "!!", $value, $this->body);
					}
				}
			}
			
			//filters
			$temail = apply_filters("pmpro_email_filter", $this);		//allows filtering entire email at once

			if ( empty( $temail ) ) {
				return false;
			}

			$this->email = apply_filters("pmpro_email_recipient", $temail->email, $this);
			$this->from = apply_filters("pmpro_email_sender", $temail->from, $this);
			$this->fromname = apply_filters("pmpro_email_sender_name", $temail->fromname, $this);
			$this->subject = apply_filters("pmpro_email_subject", $temail->subject, $this);
			$this->template = apply_filters("pmpro_email_template", $temail->template, $this);
			$this->body = apply_filters("pmpro_email_body", $temail->body, $this);
			$this->headers = apply_filters("pmpro_email_headers", $temail->headers, $this);
			$this->attachments = apply_filters("pmpro_email_attachments", $temail->attachments, $this);
			
			if(wp_mail($this->email,$this->subject,$this->body,$this->headers,$this->attachments))
			{
				return true;
			}
			else
			{
				return false;
			}		
		}
		
		function sendCancelEmail($user = NULL, $old_level_id = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__('Your membership at %s has been CANCELLED', 'paid-memberships-pro'), get_option("blogname"));

			$this->data = array("name" => $user->display_name, "user_login" => $user->user_login, "sitename" => get_option("blogname"), "siteemail" => pmpro_getOption("from_email"));

			if(!empty($old_level_id)) {
				if(!is_array($old_level_id))
					$old_level_id = array($old_level_id);
				$this->data['membership_id'] = $old_level_id[0];	//pass just the first as the level id
				$this->data['membership_level_name'] = pmpro_implodeToEnglish($wpdb->get_col("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id IN('" . implode("','", $old_level_id) . "')"));
			} else {
				$this->data['membership_id'] = '';
				$this->data['membership_level_name'] = __('All Levels', 'paid-memberships-pro' );
			}

			$this->template = apply_filters("pmpro_email_template", "cancel", $this);
			return $this->sendEmail();
		}
		
		function sendCancelAdminEmail($user = NULL, $old_level_id = NULL)
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
			$this->subject = sprintf(__("Membership for %s at %s has been CANCELLED", 'paid-memberships-pro'), $user->user_login, get_option("blogname"));			

			$this->data = array("user_login" => $user->user_login, "user_email" => $user->user_email, "display_name" => $user->display_name, "sitename" => get_option("blogname"), "siteemail" => pmpro_getOption("from_email"), "login_link" => wp_login_url());
			
			if(!empty($old_level_id)) {
				if(!is_array($old_level_id))
					$old_level_id = array($old_level_id);
				$this->data['membership_id'] = $old_level_id[0];	//pass just the first as the level id
				$this->data['membership_level_name'] = pmpro_implodeToEnglish($wpdb->get_col("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id IN('" . implode("','", $old_level_id) . "')"));

				//start and end date
				$startdate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(startdate) as startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND membership_id = '" . $old_level_id[0] . "' AND status IN('inactive', 'cancelled', 'admin_cancelled') ORDER BY id DESC");
				if(!empty($startdate))
					$this->data['startdate'] = date_i18n(get_option('date_format'), $startdate);
				else
					$this->data['startdate'] = "";
				$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) as enddate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND membership_id = '" . $old_level_id[0] . "' AND status IN('inactive', 'cancelled', 'admin_cancelled') ORDER BY id DESC");
				if(!empty($enddate))
					$this->data['enddate'] = date_i18n(get_option('date_format'), $enddate);
				else
					$this->data['enddate'] = "";
			} else {
				$this->data['membership_id'] = '';
				$this->data['membership_level_name'] = __('All Levels', 'paid-memberships-pro' );
				$this->data['startdate'] = '';
				$this->data['enddate'] = '';
			}

			$this->template = apply_filters("pmpro_email_template", "cancel_admin", $this);

			return $this->sendEmail();
		}
		
		function sendCheckoutEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your membership confirmation for %s", 'paid-memberships-pro' ), get_option("blogname"));	
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
								"membership_level_name" => $user->membership_level->name,
								"membership_cost" => pmpro_getLevelCost($user->membership_level),								
								"login_link" => wp_login_url(pmpro_url("account")),
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,								
							);						
						
			if(!empty($invoice) && !pmpro_isLevelFree($user->membership_level))
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

				//BUG: Didn't apply template filter before it was being used in sendEmail()
				$this->template = apply_filters("pmpro_email_template", $this->template, $this);

				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = pmpro_formatPrice($invoice->total);
				$this->data["invoice_date"] = date_i18n(get_option('date_format'), $invoice->timestamp);
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
				$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																	 $invoice->billing->street,
																	 "", //address 2
																	 $invoice->billing->city,
																	 $invoice->billing->state,
																	 $invoice->billing->zip,
																	 $invoice->billing->country,
																	 $invoice->billing->phone);
				
				if($invoice->getDiscountCode())
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $invoice->discount_code->code . "</p>\n";
				else
					$this->data["discount_code"] = "";
			}
			elseif(pmpro_isLevelFree($user->membership_level))
			{
				$this->template = "checkout_free";		
				global $discount_code;
				if(!empty($discount_code))
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $discount_code . "</p>\n";		
				else
					$this->data["discount_code"] = "";		
			}						
			else
			{
				$this->template = "checkout_freetrial";
				global $discount_code;
				if(!empty($discount_code))
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $discount_code . "</p>\n";		
				else
					$this->data["discount_code"] = "";	
			}
			
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
			else
				$this->data["membership_expiration"] = "";
			
			return $this->sendEmail();
		}
		
		function sendCheckoutAdminEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//check settings
			$send = pmpro_getOption("email_admin_checkout");
			if(empty($send))
				return true;	//didn't send, but we also don't want to indicate failure because the settings say to not send
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Member Checkout for %s at %s", 'paid-memberships-pro' ), $user->membership_level->name, get_option("blogname"));	
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
								"membership_level_name" => $user->membership_level->name,
								"membership_cost" => pmpro_getLevelCost($user->membership_level),								
								"login_link" => wp_login_url(pmpro_url("account")),
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,								
							);						
			
			if(!empty($invoice) && !pmpro_isLevelFree($user->membership_level))
			{									
				if($invoice->gateway == "paypalexpress")
					$this->template = "checkout_express_admin";
				elseif($invoice->gateway == "check")
					$this->template = "checkout_check_admin";					
				elseif(pmpro_isLevelTrial($user->membership_level))
					$this->template = "checkout_trial_admin";
				else
					$this->template = "checkout_paid_admin";

				$this->template = apply_filters( "pmpro_email_template", $this->template, $this );

				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = pmpro_formatPrice($invoice->total);
				$this->data["invoice_date"] = date_i18n(get_option('date_format'), $invoice->timestamp);
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
				$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																	 $invoice->billing->street,
																	 "", //address 2
																	 $invoice->billing->city,
																	 $invoice->billing->state,
																	 $invoice->billing->zip,
																	 $invoice->billing->country,
																	 $invoice->billing->phone);
				
				if($invoice->getDiscountCode())
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $invoice->discount_code->code . "</p>\n";
				else
					$this->data["discount_code"] = "";
			}
			elseif(pmpro_isLevelFree($user->membership_level))
			{
				$this->template = "checkout_free_admin";		
				global $discount_code;
				if(!empty($discount_code))
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $discount_code . "</p>\n";		
				else
					$this->data["discount_code"] = "";	
			}						
			else
			{
				$this->template = "checkout_freetrial_admin";
				$this->data["discount_code"] = "";
			}			
			
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
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
			$this->subject = sprintf(__("Your billing information has been updated at %s", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
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
								"login_link" => wp_login_url(pmpro_url("account"))
							);
			$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);

			$this->template = apply_filters( "pmpro_email_template", "billing", $this );

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
			$this->subject = sprintf(__("Billing information has been updated for %s at %s", "paid-memberships-pro"), $user->user_login, get_option("blogname"));
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
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
			$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);

			$this->template = apply_filters( "pmpro_email_template", "billing_admin", $this );

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
			$this->subject = sprintf(__("Membership Payment Failed at %s", "paid-memberships-pro"), get_option("blogname"));
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
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
								"login_link" => wp_login_url(pmpro_url("billing"))
							);
			$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);

			$this->template = apply_filters("pmpro_email_template", "billing_failure", $this);

			return $this->sendEmail();
		}				
		
		function sendBillingFailureAdminEmail($email, $invoice = NULL)
		{		
			if(!$invoice)			
				return false;
				
			$user = get_userdata($invoice->user_id);
			
			$this->email = $email;
			$this->subject = sprintf(__("Membership Payment Failed For %s at %s", "paid-memberships-pro"), $user->display_name, get_option("blogname"));
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => "Admin", 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
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
								"login_link" => wp_login_url(pmpro_url("billing"))
							);
			$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);
			$this->template = apply_filters("pmpro_email_template", "billing_failure_admin", $this);

			return $this->sendEmail();
		}
		
		function sendCreditCardExpiringEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Credit Card on File Expiring Soon at %s", "paid-memberships-pro"), get_option("blogname"));
			
			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
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
								"login_link" => wp_login_url(pmpro_url("billing"))
							);
			$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);

			$this->template = apply_filters("pmpro_email_template", "credit_card_expiring", $this);

			return $this->sendEmail();
		}
		
		function sendInvoiceEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("INVOICE for %s membership", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
								"subject" => $this->subject, 
								"name" => $user->display_name, 
								"user_login" => $user->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"membership_id" => $user->membership_level->id,
								"membership_level_name" => $user->membership_level->name,
								"display_name" => $user->display_name,
								"user_email" => $user->user_email,	
								"invoice_id" => $invoice->code,
								"invoice_total" => pmpro_formatPrice($invoice->total),
								"invoice_date" => date_i18n(get_option('date_format'), $invoice->timestamp),
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
								"login_link" => wp_login_url(pmpro_url("account")),
								"invoice_link" => wp_login_url(pmpro_url("invoice", "?invoice=" . $invoice->code)
							));
			$this->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);
		
			if($invoice->getDiscountCode()) {
				if(!empty($invoice->discount_code->code))
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $invoice->discount_code->code . "</p>\n";
				else
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $invoice->discount_code . "</p>\n";
			} else {
				$this->data["discount_code"] = "";
			}
		
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(enddate) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
			else
				$this->data["membership_expiration"] = "";


			$this->template = apply_filters("pmpro_email_template", "invoice", $this);

			return $this->sendEmail();
		}
		
		function sendTrialEndingEmail($user = NULL)
		{
			global $current_user, $wpdb;
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
			$this->subject = sprintf(__("Your trial at %s is ending soon", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
				"subject" => $this->subject, 
				"name" => $user->display_name, 
				"user_login" => $user->user_login,
				"sitename" => get_option("blogname"), 				
				"membership_id" => $user->membership_level->id,
				"membership_level_name" => $user->membership_level->name, 
				"siteemail" => pmpro_getOption("from_email"), 
				"login_link" => wp_login_url(), 
				"display_name" => $user->display_name, 
				"user_email" => $user->user_email, 
				"billing_amount" => pmpro_formatPrice($user->membership_level->billing_amount), 
				"cycle_number" => $user->membership_level->cycle_number, 
				"cycle_period" => $user->membership_level->cycle_period, 
				"trial_amount" => pmpro_formatPrice($user->membership_level->trial_amount), 
				"trial_limit" => $user->membership_level->trial_limit,
				"trial_end" => date_i18n(get_option('date_format'), strtotime(date_i18n("m/d/Y", $user->membership_level->startdate) . " + " . $user->membership_level->trial_limit . " " . $user->membership_level->cycle_period), current_time("timestamp"))
			);

			$this->template = apply_filters("pmpro_email_template", "trial_ending", $this);

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
			$this->subject = sprintf(__("Your membership at %s has ended", "paid-memberships-pro"), get_option("blogname"));			

			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "user_login" => $user->user_login, "sitename" => get_option("blogname"), "siteemail" => pmpro_getOption("from_email"), "login_link" => wp_login_url(), "display_name" => $user->display_name, "user_email" => $user->user_email, "levels_link" => pmpro_url("levels"));

			$this->template = apply_filters("pmpro_email_template", "membership_expired", $this);

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
			$this->subject = sprintf(__("Your membership at %s will end soon", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "user_login" => $user->user_login, "sitename" => get_option("blogname"), "membership_id" => $user->membership_level->id, "membership_level_name" => $user->membership_level->name, "siteemail" => pmpro_getOption("from_email"), "login_link" => wp_login_url(), "enddate" => date_i18n(get_option('date_format'), $user->membership_level->enddate), "display_name" => $user->display_name, "user_email" => $user->user_email);

			$this->template = apply_filters("pmpro_email_template", "membership_expiring", $this);

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
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID, true);
						
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your membership at %s has been changed", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "user_login" => $user->user_login, "sitename" => get_option("blogname"), "membership_id" => $user->membership_level->id, "membership_level_name" => $user->membership_level->name, "siteemail" => pmpro_getOption("from_email"), "login_link" => wp_login_url());

			if($user->membership_level->ID)
				$this->data["membership_change"] = sprintf(__("The new level is %s", 'paid-memberships-pro' ), $user->membership_level->name);
			else
				$this->data["membership_change"] = __("Your membership has been cancelled", "paid-memberships-pro");

			if(!empty($user->membership_level->enddate))
			{
					$this->data["membership_change"] .= ". " . sprintf(__("This membership will expire on %s", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $user->membership_level->enddate));
			}
			elseif(!empty($this->expiration_changed))
			{
				$this->data["membership_change"] .= ". " . __("This membership does not expire", 'paid-memberships-pro' );
			}

			$this->template = apply_filters("pmpro_email_template", "admin_change", $this);

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
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID, true);
						
			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Membership for %s at %s has been changed", "paid-memberships-pro"), $user->user_login, get_option("blogname"));

			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "user_login" => $user->user_login, "sitename" => get_option("blogname"), "membership_level_name" => $user->membership_level->name, "siteemail" => get_bloginfo("admin_email"), "login_link" => wp_login_url());
			if($user->membership_level->ID)
				$this->data["membership_change"] = sprintf(__("The new level is %s", 'paid-memberships-pro' ), $user->membership_level->name);
			else
				$this->data["membership_change"] = __("Membership has been cancelled", 'paid-memberships-pro' );
			
			if(!empty($user->membership_level->enddate))
			{
					$this->data["membership_change"] .= ". " . sprintf(__("This membership will expire on %s", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $user->membership_level->enddate));
			}
			elseif(!empty($this->expiration_changed))
			{
				$this->data["membership_change"] .= ". " . __("This membership does not expire", 'paid-memberships-pro' );
			}

			$this->template = apply_filters("pmpro_email_template", "admin_change_admin", $this);

			return $this->sendEmail();
		}

		/**
		 * Send billable invoice email.
		 *
		 * @since 1.8.6
		 *
		 * @param WP_User $user
		 * @param MemberOrder $order
		 *
		 * @return bool Whether the email was sent successfully.
		 */
		function sendBillableInvoiceEmail($user = NULL, $order = NULL)
		{
			global $current_user;

			if(!$user)
				$user = $current_user;

			if(!$user || !$order)
				return false;

			$level = pmpro_getLevel($order->membership_id);

			$this->email = $user->user_email;
			$this->subject = __('Invoice for Order #: ', 'paid-memberships-pro') . $order->code;

			// Load invoice template
			if ( file_exists( get_stylesheet_directory() . '/paid-memberships-pro/pages/orders-email.php' ) ) {
				$template = get_stylesheet_directory() . '/paid-memberships-pro/pages/orders-email.php';
			} elseif ( file_exists( get_template_directory() . '/paid-memberships-pro/pages/orders-email.php' ) ) {
				$template = get_template_directory() . '/paid-memberships-pro/pages/orders-email.php';
			} else {
				$template = PMPRO_DIR . '/adminpages/templates/orders-email.php';
			}

			ob_start();
			require_once( $template );

			$invoice = ob_get_contents();
			ob_end_clean();

			$this->data = array(
				'order_code' => $order->code,
				'login_link' => wp_login_url(pmpro_url("account")),
				'invoice_link' => wp_login_url(pmpro_url("invoice", "?invoice=" . $order->code)),
				'invoice' => $invoice
			);

			$this->template = apply_filters("pmpro_email_template", "billable_invoice", $this);

			return $this->sendEmail();
		}
		
		/**
		 * Load the text for each default email template.
		 * This overrides the old /email/*.html templates.
		 */
		function getDefaultEmailTemplate( $template = null ) {
			if( empty( $template ) && !empty( $this->template ) )
				$template = $this->template;
			
			if( empty( $template ) )
				return false;
			
			$r = '';
			
			switch($template) {
				case "admin_change":
					$r = __( "<p>An administrator at !!sitename!! has changed your membership level.</p>

<p>!!membership_change!!.</p>

<p>If you did not request this membership change and would like more information please contact us at !!siteemail!!</p>

<p>Log in to your membership account here: !!login_link!!</p>", 'paid-memberships-pro' );
					break;
				//repeat above for each template
			}
			
			return $r;
		}
	}
