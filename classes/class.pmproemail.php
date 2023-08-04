<?php
	class PMProEmail
	{		
		/**
		 * Email address to send the email to.
		 *
		 * @var string $email
		 */
		public $email = '';

		/**
		 * From address to send the email from.
		 *
		 * @var string $from
		 */
		public $from = '';

		/**
		 * From name to send the email from.
		 *
		 * @var string $fromname
		 */
		public $fromname = '';

		/**
		 * Subject line for the email.
		 *
		 * @var string $subject
		 */
		public $subject = '';

		/**
		 * Template of the email address to use.
		 *
		 * @var string $template
		 */
		public $template = '';

		/**
		 * Data that accompanies the email.
		 *
		 * @var array $data
		 */
		public $data = '';

		/**
		 * Body content for the email
		 *
		 * @var string $body
		 */
		public $body = '';
		
		/**
		 * Send an email to a member or admin. Uses the wp_mail function.
		 *
		 * @param string $email The user's email that should receive the email.
		 * @param string $from The from address which the email is being sent.
		 * @param string $fromname The from name which the email is being sent.
		 * @param string $subject The subject line for the email.
		 * @param string $template The email templates name.
		 * @param array $data The data associated with the email and it's contents.
		 * 
		 */
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

			// If email is disabled don't send it.
			// Note option may have 'false' stored as a string.
			$template_disabled = pmpro_getOption( 'email_' . $this->template . '_disabled' );
			if ( ! empty( $template_disabled ) && $template_disabled !== 'false' ) {
				return false;
			}

			//default values
			global $current_user, $pmpro_email_templates_defaults;
			if(!$this->email)
				$this->email = $current_user->user_email;
				
			if(!$this->from)
				$this->from = pmpro_getOption("from_email");
			
			if(!$this->fromname)
				$this->fromname = pmpro_getOption("from_name");
	
			if(!$this->template)
				$this->template = "default";
			
			//Okay let's get the subject stuff.
			$template_subject = pmpro_getOption( 'email_' . $this->template . '_subject' );
			if ( ! empty( $template_subject ) ) {
				$this->subject = $template_subject;
			} elseif ( empty( $this->subject ) ) {
				$this->subject = ! empty( $pmpro_email_templates_defaults[$this->template]['subject'] ) ? sanitize_text_field( $pmpro_email_templates_defaults[$this->template]['subject'] ) : sprintf(__("An Email From %s", 'paid-memberships-pro' ), get_option("blogname"));
			}

			//decode the subject line in case there are apostrophes/etc in it
			$this->subject = html_entity_decode($this->subject, ENT_QUOTES, 'UTF-8');
						
			$this->headers = array("Content-Type: text/html");
			
			$this->attachments = array();
			
			//load the template			
			$locale = apply_filters("plugin_locale", get_locale(), "paid-memberships-pro");

			if( empty( $this->data['body'] ) && ! empty( pmpro_getOption( 'email_' . $this->template . '_body' ) ) )
				$this->body = pmpro_getOption( 'email_' . $this->template . '_body' );
			elseif(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/email/" . $locale . "/" . $this->template . ".html"))
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
			elseif( empty( $this->data['body'] ) && ! empty( $pmpro_email_templates_defaults[$this->template]['body'] ) )
				$this->body = $pmpro_email_templates_defaults[$this->template]['body'];									//default template in plugin
			elseif(!empty($this->data) && !empty($this->data['body']))
				$this->body = $this->data['body'];																						//data passed in


			// Get template header.
			if( pmpro_getOption( 'email_header_disabled' ) != 'true' ) {
				$email_header = pmpro_email_templates_get_template_body('header');
			} else {
				$email_header = '';
			}

			// Get template footer
			if( pmpro_getOption( 'email_footer_disabled' ) != 'true' ) {
				$email_footer = pmpro_email_templates_get_template_body('footer');
			} else {
				$email_footer = '';
			}

			// Add header and footer to email body.
			$this->body = $email_header . $this->body . $email_footer;

			//if data is a string, assume we mean to replace !!body!! with it
			if(is_string($this->data))
				$this->data = array("body"=>$data);											
				
			//filter for data
			$this->data = apply_filters("pmpro_email_data", $this->data, $this);	//filter

			// Handle backwards compatibility for the new !!header_name!! variable.
			if( empty ($this->data['header_name'] ) ) {
				$email_user = get_user_by( 'email', $this->email );
				if( $email_user ) {
					$this->data['header_name'] = $email_user->display_name;
				} elseif ( ! empty ( $this->data['name'] ) ) {
					$this->data['header_name'] = $this->data['name'];
				} else {
					$this->data['header_name'] = __( 'User', 'paid-memberships-pro' );
				}
			}
			
			//swap data into body and subject line
			if(is_array($this->data))
			{
				foreach($this->data as $key => $value)
				{
					if ( 'body' != $key ) {
						$this->body = str_replace("!!" . $key . "!!", $value, $this->body);
						$this->subject = str_replace("!!" . $key . "!!", $value, $this->subject);
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
			$this->add_from_to_headers();
			$this->subject = apply_filters("pmpro_email_subject", $temail->subject, $this);
			$this->template = apply_filters("pmpro_email_template", $temail->template, $this);
			$this->body = apply_filters("pmpro_email_body", $temail->body, $this);
			$this->headers = apply_filters("pmpro_email_headers", $temail->headers, $this);
			$this->attachments = apply_filters("pmpro_email_attachments", $temail->attachments, $this);
			
			return wp_mail($this->email,$this->subject,$this->body,$this->headers,$this->attachments);
		}
		
		/**
		 * Add the From Name and Email to the headers.
		 * @since 2.1
		 */
		function add_from_to_headers() {
			// Make sure we have a headers array
			if ( empty( $this->headers ) ) {
				$this->headers = array();
			} elseif ( ! is_array( $this->headers ) ) {
				$this->headers = array( $this->headers );
			}
			
			// Remove any previous from header
			foreach( $this->headers as $key => $header ) {
				if( strtolower( substr( $header, 0, 5 ) ) == 'from:' ) {
					unset( $this->headers[$key] );
				}
			}
			
			// Add From Email and Name or Just Email
			if( !empty( $this->from ) && !empty( $this->fromname ) ) {
				$this->headers[] = 'From:' . $this->fromname . ' <' . $this->from . '>'; 
			} elseif( !empty( $this->from ) ) {
				$this->headers[] = 'From:' . $this->from;
			}
		}
		
		/**
		 * Send the level cancelled email to the member.
		 *
		 * @param object $user The WordPress user object.
		 * @param int $old_level_id The level ID of the level that was cancelled.
		 */
		function sendCancelEmail($user = NULL, $old_level_id = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__('Your membership at %s has been CANCELLED', 'paid-memberships-pro'), get_option("blogname"));

			$this->data = array(
				'user_email' => $user->user_email, 
				'display_name' => $user->display_name,
				'header_name' => $user->display_name,
				'user_login' => $user->user_login, 
				'sitename' => get_option( 'blogname' ), 
				'siteemail' => pmpro_getOption( 'from_email' ),
				'levels_url' => pmpro_url( 'levels' )
			);

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
		
		/**
		 * Send the level cancelled email to the admin.
		 *
		 * @param object $user The WordPress user object of the member.
		 * @param int $old_level_id The level ID of the level that was cancelled.
		 */
		function sendCancelAdminEmail($user = NULL, $old_level_id = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Membership for %s at %s has been CANCELLED", 'paid-memberships-pro'), $user->user_login, get_option("blogname"));			

			$this->data = array(
				'header_name' => $this->get_admin_name( $this->email ),
				'user_login' => $user->user_login,
				'user_email' => $user->user_email, 
				'display_name' => $user->display_name, 
				'sitename' => get_option( 'blogname' ), 
				'siteemail' => pmpro_getOption('from_email'), 
				'login_link' => pmpro_login_url(), 
				'login_url' => pmpro_login_url(),
				'levels_url' => pmpro_url( 'levels' )
			);
			
			if(!empty($old_level_id)) {
				if(!is_array($old_level_id))
					$old_level_id = array($old_level_id);
				$this->data['membership_id'] = $old_level_id[0];	//pass just the first as the level id
				$this->data['membership_level_name'] = pmpro_implodeToEnglish($wpdb->get_col("SELECT name FROM $wpdb->pmpro_membership_levels WHERE id IN('" . implode("','", $old_level_id) . "')"));

				//start and end date
				$startdate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(startdate, '+00:00', @@global.time_zone)) as startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND membership_id = '" . $old_level_id[0] . "' AND status IN('inactive', 'cancelled', 'admin_cancelled') ORDER BY id DESC");
				if(!empty($startdate))
					$this->data['startdate'] = date_i18n(get_option('date_format'), $startdate);
				else
					$this->data['startdate'] = "";
				$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) as enddate FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND membership_id = '" . $old_level_id[0] . "' AND status IN('inactive', 'cancelled', 'admin_cancelled') ORDER BY id DESC");
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

		/**
		 * Send the refunded email to the member.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated with the refund.
		 */
		function sendRefundedEmail( $user = NULL, $invoice = NULL ) {
			global $wpdb, $current_user;
			if ( ! $user ) {
				$user = $current_user;
			}

			if ( ! $user ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $invoice->membership_id );
			if ( ! empty( $membership_level ) ) {
				$membership_level_id = $membership_level->id;
				$membership_level_name = $membership_level->name;
			} else {
				$membership_level_id = '';
				$membership_level_name = __( 'N/A', 'paid-memberships-pro' );
			}

			$this->email = $user->user_email;
			$this->subject = sprintf(__( 'Your invoice for order #%s at %s has been REFUNDED', 'paid-memberships-pro' ), $invoice->code, get_option( 'blogname' ) );

			$this->data = array(
				'user_login' => $user->user_login,
				'user_email' => $user->user_email,
				'display_name' => $user->display_name,
				'header_name' => $user->display_name,
				'sitename' => get_option('blogname'),
				'siteemail' => pmpro_getOption('from_email'),
				'login_link' => pmpro_login_url(),
				'login_url' => pmpro_login_url(),
				'membership_id' => $membership_level_id,
				'membership_level_name' => $membership_level_name,
				'invoice_id' => $invoice->code,
				'invoice_total' => pmpro_formatPrice($invoice->total),
				'invoice_date' => date_i18n(get_option('date_format'), $invoice->getTimestamp()),
				'billing_name' => $invoice->billing->name,
				'billing_street' => $invoice->billing->street,
				'billing_city' => $invoice->billing->city,
				'billing_state' => $invoice->billing->state,
				'billing_zip' => $invoice->billing->zip,
				'billing_country' => $invoice->billing->country,
				'billing_phone' => $invoice->billing->phone,
				'cardtype' => $invoice->cardtype,
				'accountnumber' => hideCardNumber($invoice->accountnumber),
				'expirationmonth' => $invoice->expirationmonth,
				'expirationyear' => $invoice->expirationyear,
				'login_link' => pmpro_login_url(),
				'login_url' => pmpro_login_url(),
				'invoice_link' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $invoice->code ) ),
				'invoice_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $invoice->code ) ),
				'levels_url' => pmpro_url( 'levels' )
			);
			$this->data['billing_address'] = pmpro_formatAddress(
				$invoice->billing->name,
				$invoice->billing->street,
				"", //address 2
				$invoice->billing->city,
				$invoice->billing->state,
				$invoice->billing->zip,
				$invoice->billing->country,
				$invoice->billing->phone
			);

			$this->template = apply_filters( 'pmpro_email_template', 'refund', $this );
			return $this->sendEmail();
		}
		
		/**
		 * Send the refunded email to the member.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated with the refund.
		 */
		function sendRefundedAdminEmail( $user = NULL, $invoice = NULL ) {
			global $wpdb, $current_user;
			if ( ! $user ) {
				$user = $current_user;
			}

			if ( ! $user ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $invoice->membership_id );
			if ( ! empty( $membership_level ) ) {
				$membership_level_id = $membership_level->id;
				$membership_level_name = $membership_level->name;
			} else {
				$membership_level_id = '';
				$membership_level_name = __( 'N/A', 'paid-memberships-pro' );
			}

			$this->email = get_bloginfo( 'admin_email' );
			$this->subject = sprintf(__( 'Invoice for order #%s at %s has been REFUNDED', 'paid-memberships-pro' ), $invoice->code, get_option( 'blogname' ) );

			$this->data = array(
				'user_login' => $user->user_login,
				'user_email' => $user->user_email,
				'display_name' => $user->display_name,
				'header_name' => $this->get_admin_name( $this->email ),
				'sitename' => get_option('blogname'),
				'siteemail' => pmpro_getOption('from_email'),
				'login_link' => pmpro_login_url(),
				'login_url' => pmpro_login_url(),
				'membership_id' => $membership_level_id,
				'membership_level_name' => $membership_level_name,
				'invoice_id' => $invoice->code,
				'invoice_total' => pmpro_formatPrice($invoice->total),
				'invoice_date' => date_i18n(get_option('date_format'), $invoice->getTimestamp()),
				'billing_name' => $invoice->billing->name,
				'billing_street' => $invoice->billing->street,
				'billing_city' => $invoice->billing->city,
				'billing_state' => $invoice->billing->state,
				'billing_zip' => $invoice->billing->zip,
				'billing_country' => $invoice->billing->country,
				'billing_phone' => $invoice->billing->phone,
				'cardtype' => $invoice->cardtype,
				'accountnumber' => hideCardNumber($invoice->accountnumber),
				'expirationmonth' => $invoice->expirationmonth,
				'expirationyear' => $invoice->expirationyear,
				'login_link' => pmpro_login_url(),
				'login_url' => pmpro_login_url(),
				'invoice_link' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $invoice->code ) ),
				'invoice_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $invoice->code ) ),
				'levels_url' => pmpro_url( 'levels' )							

			);
			$this->data['billing_address'] = pmpro_formatAddress(
				$invoice->billing->name,
				$invoice->billing->street,
				"", //address 2
				$invoice->billing->city,
				$invoice->billing->state,
				$invoice->billing->zip,
				$invoice->billing->country,
				$invoice->billing->phone
			);

			$this->template = apply_filters( 'pmpro_email_template', 'refund_admin', $this );

			return $this->sendEmail();
		}
		
		/**
		 * Send the member a confirmation checkout email after succesfully purchasing a membership level.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated with the checkout.
		 */
		function sendCheckoutEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user, $discount_code;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;

			if ( empty( $invoice->membership_id ) ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $invoice->membership_id);

			$confirmation_in_email = get_pmpro_membership_level_meta( $membership_level->id, 'confirmation_in_email', true );
			if ( ! empty( $confirmation_in_email ) ) {
				$confirmation_message = $membership_level->confirmation;
			} else {
				$confirmation_message = '';
			}
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your membership confirmation for %s", 'paid-memberships-pro' ), get_option("blogname"));	
			
			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $user->display_name,
								'name' => $user->display_name,
								'display_name' => $user->display_name,
								'user_login' => $user->user_login,
								'sitename' => get_option('blogname'),
								'siteemail' => pmpro_getOption('from_email'),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'membership_level_confirmation_message' => wpautop( $confirmation_message ),
								'membership_cost' => pmpro_getLevelCost($membership_level),								
								'login_link' => pmpro_login_url(),
								'login_url' => pmpro_login_url(),
								'user_email' => $user->user_email,	
								'levels_url' => pmpro_url( 'levels' )							
							);						
			
			// Figure out which template to use.
			if ( empty( $this->template ) ) {
				if( ! empty( $invoice ) && ! pmpro_isLevelFree( $membership_level ) ) {
					if( $invoice->gateway == "paypalexpress") {
						$this->template = "checkout_express";
					} elseif( $invoice->gateway == "check" ) {
						$this->template = "checkout_check";						
					} elseif( pmpro_isLevelTrial( $membership_level ) ) {
						$this->template = "checkout_trial";
					} else {
						$this->template = "checkout_paid";
					}										
				} elseif( pmpro_isLevelFree( $membership_level ) ) {
					$this->template = "checkout_free";					
				} else {
					$this->template = "checkout_freetrial";					
				}
			}
			
			$this->template = apply_filters( "pmpro_email_template", $this->template, $this );
			
			// Gather data depending on template being used.
			if( in_array( $this->template, array( 'checkout_express', 'checkout_check', 'checkout_trial', 'checkout_paid' ) ) ) {									
				if( $this->template === 'checkout_check' ) {					
					$this->data["instructions"] = wpautop(pmpro_getOption("instructions"));
				}
				
				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = pmpro_formatPrice($invoice->total);
				$this->data["invoice_date"] = date_i18n( get_option( 'date_format' ), $invoice->getTimestamp() );
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
				
				if( $invoice->getDiscountCode() ) {
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $invoice->discount_code->code . "</p>\n";
				} else {
					$this->data["discount_code"] = "";
				}
			} elseif( $this->template === 'checkout_free' ) {				
				if( ! empty( $discount_code ) ) {
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $discount_code . "</p>\n";		
				} else {
					$this->data["discount_code"] = "";
				}
			} elseif ( $this->template === 'checkout_freetrial' ) {				
				if( ! empty( $discount_code ) ) {
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $discount_code . "</p>\n";		
				} else {
					$this->data["discount_code"] = "";
				}
			}
			
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if( $enddate ) {
				$this->data["membership_expiration"] = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
			} else {
				$this->data["membership_expiration"] = "";
			}			
			
			return $this->sendEmail();
		}
		
		/**
		 * Send the admin a confirmation checkout email after the member succesfully purchases a membership level.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated with the checkout.
		 */
		function sendCheckoutAdminEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user, $discount_code;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;

			if ( empty( $invoice->membership_id ) ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $invoice->membership_id);

			$confirmation_in_email = get_pmpro_membership_level_meta( $membership_level->id, 'confirmation_in_email', true );
			if ( ! empty( $confirmation_in_email ) ) {
				$confirmation_message = $membership_level->confirmation;
			} else {
				$confirmation_message = '';
			}
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Member checkout for %s at %s", 'paid-memberships-pro' ), $membership_level->name, get_option("blogname"));	
			
			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $this->get_admin_name( $this->email ),
								'name' => $user->display_name, 
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'membership_level_confirmation_message' => $confirmation_message,
								'membership_cost' => pmpro_getLevelCost($membership_level),								
								'login_link' => pmpro_login_url(),
								'login_url' => pmpro_login_url(),
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,	
								'levels_url' => pmpro_url( 'levels' )							
							);						
			
			// Figure out which template to use.
			if ( empty( $this->template ) ) {
				if( ! empty( $invoice ) && ! pmpro_isLevelFree( $membership_level ) ) {
					if( $invoice->gateway == "paypalexpress") {
						$this->template = "checkout_express_admin";
					} elseif( $invoice->gateway == "check" ) {
						$this->template = "checkout_check_admin";						
					} elseif( pmpro_isLevelTrial( $membership_level ) ) {
						$this->template = "checkout_trial_admin";
					} else {
						$this->template = "checkout_paid_admin";
					}										
				} elseif( pmpro_isLevelFree( $membership_level ) ) {
					$this->template = "checkout_free_admin";					
				} else {
					$this->template = "checkout_freetrial_admin";					
				}
			}
			
			$this->template = apply_filters( "pmpro_email_template", $this->template, $this );
			
			// Gather data depending on template being used.
			if( in_array( $this->template, array( 'checkout_express_admin', 'checkout_check_admin', 'checkout_trial_admin', 'checkout_paid_admin' ) ) ) {
				$this->data["invoice_id"] = $invoice->code;
				$this->data["invoice_total"] = pmpro_formatPrice($invoice->total);
				$this->data["invoice_date"] = date_i18n(get_option('date_format'), $invoice->getTimestamp());
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
				
				if( $invoice->getDiscountCode() ) {
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $invoice->discount_code->code . "</p>\n";
				} else {
					$this->data["discount_code"] = "";
				}
			} elseif( $this->template === 'checkout_free_admin' ) {				
				if( ! empty( $discount_code ) ) {
					$this->data["discount_code"] = "<p>" . __("Discount Code", 'paid-memberships-pro' ) . ": " . $discount_code . "</p>\n";		
				} else {
					$this->data["discount_code"] = "";
				}
			} elseif( $this->template === 'checkout_freetrial_admin' ) {				
				$this->data["discount_code"] = "";
			}
			
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if( $enddate ) {
				$this->data["membership_expiration"] = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
			} else {
				$this->data["membership_expiration"] = "";
			}
			
			return $this->sendEmail();
		}
		
		/**
		 * Send the member a confirmation email when updating their billing details
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated to the member.
		 */
		function sendBillingEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;

			if ( empty( $invoice->membership_id ) ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $invoice->membership_id);
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your billing information has been updated at %s", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $user->display_name,
								'name' => $user->display_name,
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,																	
								'billing_name' => $invoice->billing->name,
								'billing_street' => $invoice->billing->street,
								'billing_city' => $invoice->billing->city,
								'billing_state' => $invoice->billing->state,
								'billing_zip' => $invoice->billing->zip,
								'billing_country' => $invoice->billing->country,
								'billing_phone' => $invoice->billing->phone,
								'cardtype' => $invoice->cardtype,
								'accountnumber' => hideCardNumber($invoice->accountnumber),
								'expirationmonth' => $invoice->expirationmonth,
								'expirationyear' => $invoice->expirationyear,
								'login_link' => pmpro_login_url(),
								'login_url' => pmpro_login_url(),
								'levels_url' => pmpro_url( 'levels' )							
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
		
		/**
		 * Send the admin a confirmation email when a member updatestheir billing details
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated to the member.
		 */
		function sendBillingAdminEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			if ( empty( $invoice->membership_id ) ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $invoice->membership_id);
			
			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Billing information has been updated for %s at %s", "paid-memberships-pro"), $user->user_login, get_option("blogname"));
			
			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $this->get_admin_name( $this->email ),
								'name' => $user->display_name,
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,																	
								'billing_name' => $invoice->billing->name,
								'billing_street' => $invoice->billing->street,
								'billing_city' => $invoice->billing->city,
								'billing_state' => $invoice->billing->state,
								'billing_zip' => $invoice->billing->zip,
								'billing_country' => $invoice->billing->country,
								'billing_phone' => $invoice->billing->phone,
								'cardtype' => $invoice->cardtype,
								'accountnumber' => hideCardNumber($invoice->accountnumber),
								'expirationmonth' => $invoice->expirationmonth,
								'expirationyear' => $invoice->expirationyear,
								'login_link' => pmpro_login_url(),
								'login_url' => pmpro_login_url(),
								'levels_url' => pmpro_url( 'levels' )							
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
		
		/**
		 * Send the member an email when their recurring payment has failed.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated to the member.
		 */
		function sendBillingFailureEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;

			$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $invoice->membership_id );
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Membership payment failed at %s", "paid-memberships-pro"), get_option("blogname"));
			
			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $user->display_name,
								'name' => $user->display_name,
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,									
								'billing_name' => $invoice->billing->name,
								'billing_street' => $invoice->billing->street,
								'billing_city' => $invoice->billing->city,
								'billing_state' => $invoice->billing->state,
								'billing_zip' => $invoice->billing->zip,
								'billing_country' => $invoice->billing->country,
								'billing_phone' => $invoice->billing->phone,
								'cardtype' => $invoice->cardtype,
								'accountnumber' => hideCardNumber($invoice->accountnumber),
								'expirationmonth' => $invoice->expirationmonth,
								'expirationyear' => $invoice->expirationyear,
								'login_link' => pmpro_login_url( pmpro_url( 'billing' ) ),
								'login_url' => pmpro_login_url( pmpro_url( 'billing' ) ),
								'levels_url' => pmpro_url( 'levels' )							
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
		
		/**
		 * Send the admin an email when their recurring payment has failed.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated to the member.
		 */
		function sendBillingFailureAdminEmail($email, $invoice = NULL)
		{		
			if(!$invoice)			
				return false;
				
			$user = get_userdata($invoice->user_id);
			$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $invoice->membership_id );
			
			$this->email = $email;
			$this->subject = sprintf(__("Membership payment failed For %s at %s", "paid-memberships-pro"), $user->display_name, get_option("blogname"));
			
			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $this->get_admin_name( $email ),
								'name' => 'Admin', 
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,									
								'billing_name' => $invoice->billing->name,
								'billing_street' => $invoice->billing->street,
								'billing_city' => $invoice->billing->city,
								'billing_state' => $invoice->billing->state,
								'billing_zip' => $invoice->billing->zip,
								'billing_country' => $invoice->billing->country,
								'billing_phone' => $invoice->billing->phone,
								'cardtype' => $invoice->cardtype,
								'accountnumber' => hideCardNumber($invoice->accountnumber),
								'expirationmonth' => $invoice->expirationmonth,
								'expirationyear' => $invoice->expirationyear,
								'login_link' => pmpro_login_url( get_edit_user_link( $user->ID ) ),
								'login_url' => pmpro_login_url( get_edit_user_link( $user->ID ) ),
								'levels_url' => pmpro_url( 'levels' )							
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

		/**
		 * Send the member an email when their credit card is expiring soon.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated to the member.
		 */
		function sendCreditCardExpiringEmail($user = NULL, $invoice = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			if ( empty( $invoice->membership_id ) ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $invoice->membership_id);
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Credit card on file expiring soon at %s", "paid-memberships-pro"), get_option("blogname"));
			
			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $user->display_name,
								'name' => $user->display_name,
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,									
								'billing_name' => $invoice->billing->name,
								'billing_street' => $invoice->billing->street,
								'billing_city' => $invoice->billing->city,
								'billing_state' => $invoice->billing->state,
								'billing_zip' => $invoice->billing->zip,
								'billing_country' => $invoice->billing->country,
								'billing_phone' => $invoice->billing->phone,
								'cardtype' => $invoice->cardtype,
								'accountnumber' => hideCardNumber($invoice->accountnumber),
								'expirationmonth' => $invoice->expirationmonth,
								'expirationyear' => $invoice->expirationyear,
								'login_link' => pmpro_login_url( pmpro_url( 'billing' ) ),
								'login_url' => pmpro_login_url( pmpro_url( 'billing' ) ),
								'levels_url' => pmpro_url( 'levels' )							

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
		
		/**
		 * Send the member an email when their recurring payment has succeeded.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $invoice The order object that is associated to the member.
		 */
		function sendInvoiceEmail($user = NULL, $invoice = NULL)
		{
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$invoice)
				return false;
			
			if ( empty( $invoice->membership_id ) ) {
				return false;
			}

			$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $invoice->membership_id);
			
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Invoice for %s membership", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
								'subject' => $this->subject,
								'header_name' => $user->display_name,
								'name' => $user->display_name,
								'user_login' => $user->user_login,
								'sitename' => get_option( 'blogname' ),
								'siteemail' => pmpro_getOption( 'from_email' ),
								'membership_id' => $membership_level->id,
								'membership_level_name' => $membership_level->name,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,	
								'invoice_id' => $invoice->code,
								'invoice_total' => pmpro_formatPrice( $invoice->total ),
								'invoice_date' => date_i18n( get_option( 'date_format' ), $invoice->getTimestamp() ),
								'billing_name' => $invoice->billing->name,
								'billing_street' => $invoice->billing->street,
								'billing_city' => $invoice->billing->city,
								'billing_state' => $invoice->billing->state,
								'billing_zip' => $invoice->billing->zip,
								'billing_country' => $invoice->billing->country,
								'billing_phone' => $invoice->billing->phone,
								'cardtype' => $invoice->cardtype,
								'accountnumber' => hideCardNumber($invoice->accountnumber),
								'expirationmonth' => $invoice->expirationmonth,
								'expirationyear' => $invoice->expirationyear,
								'login_link' => pmpro_login_url(),
								'login_url' => pmpro_login_url(),
								'invoice_link' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $invoice->code ) ),
								'invoice_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $invoice->code ) ),
								'levels_url' => pmpro_url( 'levels' )
							);
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
		
			$enddate = $wpdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(enddate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user->ID . "' AND status = 'active' LIMIT 1");
			if($enddate)
				$this->data["membership_expiration"] = "<p>" . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $enddate)) . "</p>\n";
			else
				$this->data["membership_expiration"] = "";


			$this->template = apply_filters("pmpro_email_template", "invoice", $this);

			return $this->sendEmail();
		}
		
		/**
		 * Send the member an email when their trial is ending soon.
		 *
		 * @param object $user The WordPress user object.
		 * @param int $membership_id The member's membership level ID.
		 */
		function sendTrialEndingEmail( $user = NULL, $membership_id = NULL )
		{
			global $current_user;

			_deprecated_function( 'sendTrialEndingEmail', '2.10' );

			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//make sure we have the current membership level data
			if ( empty( $membership_id ) ) {
				$membership_level = pmpro_getMembershipLevelForUser($user->ID);
			} else {
				$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $membership_id);
			}

						
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your trial at %s is ending soon", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
				'subject' => $this->subject,
				'header_name' => $user->display_name,
				'name' => $user->display_name, 
				'user_login' => $user->user_login,
				'sitename' => get_option( 'blogname' ), 				
				'membership_id' => $membership_level->id,
				'membership_level_name' => $membership_level->name, 
				'siteemail' => pmpro_getOption( 'from_email' ), 
				'login_link' => pmpro_login_url(),
				'login_url' => pmpro_login_url(),
				'display_name' => $user->display_name, 
				'user_email' => $user->user_email, 
				'billing_amount' => pmpro_formatPrice( $membership_level->billing_amount ), 
				'cycle_number' => $membership_level->cycle_number, 
				'cycle_period' => $membership_level->cycle_period, 
				'trial_amount' => pmpro_formatPrice( $membership_level->trial_amount ), 
				'trial_limit' => $membership_level->trial_limit,
				'trial_end' => date_i18n( get_option( 'date_format' ), strtotime( date_i18n( 'm/d/Y', $membership_level->startdate ) . ' + ' . $membership_level->trial_limit . ' ' . $membership_level->cycle_period ), current_time( 'timestamp' ) ),
				'levels_url' => pmpro_url( 'levels' )							
			);

			$this->template = apply_filters("pmpro_email_template", "trial_ending", $this);

			return $this->sendEmail();
		}
		
		
		function sendMembershipExpiredEmail( $user = NULL, $membership_id = NULL )
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;						

			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your membership at %s has ended", "paid-memberships-pro"), get_option("blogname"));			

			$this->data = array("subject" => $this->subject, "name" => $user->display_name, "user_login" => $user->user_login, "header_name" => $user->display_name, "sitename" => get_option("blogname"), "siteemail" => pmpro_getOption("from_email"), "login_link" => pmpro_login_url(), "login_url" => pmpro_login_url(), "display_name" => $user->display_name, "user_email" => $user->user_email, "levels_link" => pmpro_url("levels"), "levels_url" => pmpro_url("levels"));

			$this->template = apply_filters("pmpro_email_template", "membership_expired", $this);

			return $this->sendEmail();
		}
		
		/**
		 * Send the member an email when their membership has ended.
		 *
		 * @param object $user The WordPress user object.
		 * @param int $membership_id The member's membership level ID.
		 */
		function sendMembershipExpiringEmail( $user = NULL, $membership_id = NULL )
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			if ( empty( $membership_id ) ) {
				$membership_level = pmpro_getMembershipLevelForUser($user->ID);
			} else {
				$membership_level = pmpro_getSpecificMembershipLevelForUser($user->ID, $membership_id);
			}
						
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your membership at %s will end soon", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
				'subject' => $this->subject,
				'header_name' => $user->display_name,
				'name' => $user->display_name,
				'user_login' => $user->user_login, 
				'sitename' => get_option('blogname'), 
				'membership_id' => $membership_level->id, 
				'membership_level_name' => $membership_level->name, 
				'siteemail' => pmpro_getOption('from_email'), 
				'login_link' => pmpro_login_url(), 
				'login_url' => pmpro_login_url(), 
				'enddate' => date_i18n(get_option('date_format'), $membership_level->enddate), 
				'display_name' => $user->display_name, 
				'user_email' => $user->user_email,
				'levels_url' => pmpro_url( 'levels' )
			);

			$this->template = apply_filters("pmpro_email_template", "membership_expiring", $this);

			return $this->sendEmail();
		}
		
		/**
		 * Send an email to the member when an admin has changed their membership level.
		 *
		 * @param object $user The WordPress user object.
		 */
		function sendAdminChangeEmail($user = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			//make sure we have the current membership level data
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID, true);

			if(!empty($user->membership_level) && !empty($user->membership_level->name)) {
				$membership_level_name = $user->membership_level->name;
				$membership_level_id = $user->membership_level->id;
			} else {
				$membership_level_name = __('None', 'paid-memberships-pro');
				$membership_level_id = '';
			}
						
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Your membership at %s has been changed", "paid-memberships-pro"), get_option("blogname"));

			$this->data = array(
				'subject' => $this->subject,
				'header_name' => $user->display_name,
				'name' => $user->display_name, 
				'display_name' => $user->display_name, 
				'user_login' => $user->user_login, 
				'user_email' => $user->user_email, 
				'sitename' => get_option( 'blogname' ), 
				'membership_id' => $membership_level_id, 
				'membership_level_name' => $membership_level_name, 
				'siteemail' => pmpro_getOption( 'from_email' ), 
				'login_link' => pmpro_login_url(), 
				'login_url' => pmpro_login_url(),
				'levels_url' => pmpro_url( 'levels' )
			);

			if(!empty($user->membership_level) && !empty($user->membership_level->ID)) {
				$this->data["membership_change"] = sprintf(__("The new level is %s.", 'paid-memberships-pro' ), $user->membership_level->name);
			} else {
				$this->data["membership_change"] = __("Your membership has been cancelled.", "paid-memberships-pro");
			}

			if(!empty($user->membership_level->enddate))
			{
					$this->data["membership_change"] .= " " . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $user->membership_level->enddate));
			}
			elseif(!empty($this->expiration_changed))
			{
				$this->data["membership_change"] .= " " . __("This membership does not expire.", 'paid-memberships-pro' );
			}

			$this->template = apply_filters("pmpro_email_template", "admin_change", $this);

			return $this->sendEmail();
		}
		
		/**
		 * Send an email to the admin when an admin has changed a member's membership level.
		 *
		 * @param object $user The WordPress user object.
		 */
		function sendAdminChangeAdminEmail($user = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;

			//make sure we have the current membership level data
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID, true);
						
			if(!empty($user->membership_level) && !empty($user->membership_level->name)) {
				$membership_level_name = $user->membership_level->name;
				$membership_level_id = $user->membership_level->id;
			} else {
				$membership_level_name = __('None', 'paid-memberships-pro');
				$membership_level_id = '';
			}

			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Membership for %s at %s has been changed", "paid-memberships-pro"), $user->user_login, get_option("blogname"));

			$this->data = array(
				'subject' => $this->subject,
				'header_name' => $this->get_admin_name( $this->email ),
				'name' =>$user->display_name,
				'display_name' => $user->display_name,
				'user_login' => $user->user_login, 
				'user_email' => $user->user_email, 
				'sitename' => get_option('blogname'), 
				'membership_id' => $membership_level_id, 
				'membership_level_name' => $membership_level_name,
				'siteemail' => $this->email,
				'login_link' => pmpro_login_url(), 
				'login_url' => pmpro_login_url(),
				'levels_url' => pmpro_url( 'levels' )
			);

			if(!empty($user->membership_level) && !empty($user->membership_level->ID)) {
				$this->data["membership_change"] = sprintf(__("The new level is %s.", 'paid-memberships-pro' ), $user->membership_level->name);
			} else {
				$this->data["membership_change"] = __("Membership has been cancelled.", 'paid-memberships-pro' );	
			}
			
			if(!empty($user->membership_level) && !empty($user->membership_level->enddate))
			{
					$this->data["membership_change"] .= " " . sprintf(__("This membership will expire on %s.", 'paid-memberships-pro' ), date_i18n(get_option('date_format'), $user->membership_level->enddate));
			}
			elseif(!empty($this->expiration_changed))
			{
				$this->data["membership_change"] .= " " . __("This membership does not expire.", 'paid-memberships-pro' );
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
			$this->subject = __('Invoice for order #: ', 'paid-memberships-pro') . $order->code;

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
				'login_link' => pmpro_login_url(),
				'login_url' => pmpro_login_url(),
				'invoice_link' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
				'invoice_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $order->code ) ),
				'invoice_id' => $order->id,
				'invoice' => $invoice,
				'levels_url' => pmpro_url( 'levels' )
			);

			$this->template = apply_filters("pmpro_email_template", "billable_invoice", $this);

			return $this->sendEmail();
		}

		/**
		 * Send the Payment Action is required email to a member. This is used for Stripe payments.
		 *
		 * @param object $user
		 * @param MemberOrder $order 
		 * @param string $invoice_url The link to the invoice that is generated by Stripe.
		 * @return void
		 */
		function sendPaymentActionRequiredEmail($user = NULL, $order = NULL, $invoice_url = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$order)
				return false;

			// if an invoice URL wasn't passed in, grab it from the order
			if(empty($invoice_url) && isset($order->invoice_url))
				$invoice_url = $order->invoice_url;

			// still no invoice URL? bail
			if(empty($invoice_url))
				return false;
				
			$this->email = $user->user_email;
			$this->subject = sprintf(__("Payment action required for your %s membership", 'paid-memberships-pro' ), get_option("blogname"));	
			
			$this->template = "payment_action";

			$this->template = apply_filters("pmpro_email_template", $this->template, $this);

			$this->data = array(
				'subject' => $this->subject,
				'header_name' => $user->display_name,
				'name' => $user->display_name,
				'display_name' => $user->display_name,
				'user_login' => $user->user_login,
				'sitename' => get_option( 'blogname' ),
				'siteemail' => pmpro_getOption( 'from_email' ),
				'invoice_link' => $invoice_url,
				'invoice_url' => $invoice_url,
				'levels_url' => pmpro_url( 'levels' )
			);
						
			return $this->sendEmail();
		}

		/**
		 * Send the Payment Action is required email to the admin when a member's payment requires the payment action intent. This is used for Stripe payments.
		 *
		 * @param object $user
		 * @param MemberOrder $order 
		 * @param string $invoice_url The link to the invoice that is generated by Stripe.
		 * @return void
		 */
		function sendPaymentActionRequiredAdminEmail($user = NULL, $order = NULL, $invoice_url = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$order)
				return false;

			// if an invoice URL wasn't passed in, grab it from the order
			if(empty($invoice_url) && isset($order->invoice_url))
				$invoice_url = $order->invoice_url;

			// still no invoice URL? bail
			if(empty($invoice_url))
				return false;
				
			$this->email = get_bloginfo("admin_email");
			$this->subject = sprintf(__("Payment action required: membership for %s at %s", 'paid-memberships-pro' ), $user->user_login, get_option("blogname"));	
			
			$this->template = "payment_action_admin";

			$this->template = apply_filters("pmpro_email_template", $this->template, $this);

			$this->data = array(
				'subject' => $this->subject,
				'header_name' => $this->get_admin_name( $this->email ),
				'name' => $user->display_name,
				'display_name' => $user->display_name,
				'user_login' => $user->user_login,
				'sitename' => get_option('blogname'),
				'siteemail' => pmpro_getOption('from_email'),
				'user_email' => $user->user_email,
				'invoice_link' => $invoice_url,
				'invoice_url' => $invoice_url,
				'levels_url' => pmpro_url( 'levels' )
			);
						
			return $this->sendEmail();
		}

		/**
		 * Gets the admin user name.
		 *
		 * @since 2.10.6
		 * @param string $email The admin email address.
		 * @return string The admin user display name.
		 */
		private function get_admin_name($email) {
			$admin = get_user_by('email', $email );
			return $admin ? $admin->display_name : 'admin';
		}

	}
