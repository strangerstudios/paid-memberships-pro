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
		 * Email headers
		 *
		 * @var array $headers
		 */
		public $headers = array();

		/**
		 * Email attachments
		 *
		 * @var array $attachments
		 */
		 public $attachments = array();

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
			$template_disabled = get_option( 'pmpro_email_' . $this->template . '_disabled' );
			if ( ! empty( $template_disabled ) && $template_disabled !== 'false' ) {
				return false;
			}

			//default values
			global $current_user, $pmpro_email_templates_defaults;
			if(!$this->email)
				$this->email = $current_user->user_email;
				
			if(!$this->from)
				$this->from = get_option("pmpro_from_email");
			
			if(!$this->fromname)
				$this->fromname = get_option("pmpro_from_name");
	
			if(!$this->template)
				$this->template = "default";
			
			//Okay let's get the subject stuff.
			$template_subject = get_option( 'pmpro_email_' . $this->template . '_subject' );
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

			if( empty( $this->data['body'] ) && ! empty( get_option( 'pmpro_email_' . $this->template . '_body' ) ) )
				$this->body = get_option( 'pmpro_email_' . $this->template . '_body' );
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
					$this->data['header_name'] = esc_html__( 'User', 'paid-memberships-pro' );
				}
			}

			// We switched our wording from using "invoice" to "order", but we want to keep backwards compat for variables with "invoice" in the name.
			if ( is_array( $this->data ) ) {
				$data_keys = array_keys( $this->data );
				foreach ( $data_keys as $key ) {
					// If this key has "order" in it, add an identical entry for "invoice" if it doesn't already exist.
					if ( strpos( $key, 'order' ) !== false ) {
						$invoice_key = str_replace( 'order', 'invoice', $key );
						if ( ! isset( $this->data[ $invoice_key ] ) ) {
							$this->data[ $invoice_key ] = $this->data[ $key ];
						}
					}
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

			// Get template header.
			$email_header = '';
			if ( pmpro_getOption( 'email_header_disabled' ) != 'true' ) {
				$email_header = pmpro_email_templates_get_template_body( 'header' );
				if ( has_filter( 'pmpro_email_body', 'pmpro_kses' ) ) {
					$email_header = pmpro_kses( $email_header );
				}

				$email_header = apply_filters( 'pmpro_email_header', $email_header, $this );
			}

			// Get template footer
			$email_footer = '';
			if ( get_option( 'pmpro_email_footer_disabled' ) != 'true' ) {
				$email_footer = pmpro_email_templates_get_template_body( 'footer' );
				if ( has_filter( 'pmpro_email_body', 'pmpro_kses' ) ) {
					$email_footer = pmpro_kses( $email_footer );
				}

				$email_footer = apply_filters( 'pmpro_email_footer', $email_footer, $this );
			}

			// Add header and footer to email body.
			$this->body = $email_header . $this->body . $email_footer;

			// Swap data into body and subject line again in case filters changed them or in case we added header/footer.
			if ( is_array( $this->data ) ) {
				foreach ( $this->data as $key => $value ) {
					if ( 'body' != $key ) {
						$this->body = str_replace("!!" . $key . "!!", $value, $this->body);
						$this->subject = str_replace("!!" . $key . "!!", $value, $this->subject);
					}
				}
			}
			
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
			// If an array is passed for $old_level_id, throw doing it wrong warning.
			if ( is_array( $old_level_id ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'The $old_level_id parameter should be an integer, not an array.', 'paid-memberships-pro' ), '3.0' );
			}

			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;
			
			$email = new PMPro_Email_Template_Cancel( $user, $old_level_id );
			return $email->send();
		}
		
		/**
		 * Send the level cancelled email to the admin.
		 *
		 * @param object $user The WordPress user object of the member.
		 * @param int $old_level_id The level ID of the level that was cancelled.
		 */
		function sendCancelAdminEmail($user = NULL, $old_level_id = NULL)
		{
			// If an array is passed for $old_level_id, throw doing it wrong warning.
			if ( is_array( $old_level_id ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'The $old_level_id parameter should be an integer, not an array.', 'paid-memberships-pro' ), '3.0' );
			}

			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;

			$email = new PMPro_Email_Template_Cancel_Admin( $user, $old_level_id );
			return $email->send();
		}

		/**
		 * Send the "cancel on next payment date" email to the member.
		 *
		 * @param WP_User $user The WordPress user object.
		 * @param int $level_id The level ID of the level that was cancelled.
		 */
		function sendCancelOnNextPaymentDateEmail( $user, $level_id ) {
			// If an array is passed for $level_id, throw doing it wrong warning.
			if ( is_array( $level_id ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'The $level_id parameter should be an integer, not an array.', 'paid-memberships-pro' ), '3.0' );
			}

			// Make sure that the user object is a WP_User object.
			if ( ! is_a( $user, 'WP_User' ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'The $user parameter should be a WP_User object.', 'paid-memberships-pro' ), '3.0' );
			}

			$email = new PMPro_Email_Template_Cancel_On_Next_Payment_Date( $user, $level_id );
			return $email->send();
		}

		/**
		 * Send the "cancel on next payment date" email to the admin.
		 *
		 * @param WP_User $user The WordPress user object.
		 * @param int $level_id The level ID of the level that was cancelled.
		 * @return bool True if the email was sent, false otherwise.
		 * @since 3.1
		 */
		function sendCancelOnNextPaymentDateAdminEmail( $user, $level_id ) {
			// If an array is passed for $level_id, throw doing it wrong warning.
			if ( is_array( $level_id ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'The $level_id parameter should be an integer, not an array.', 'paid-memberships-pro' ), '3.0' );
			}

			// Make sure that the user object is a WP_User object.
			if ( ! is_a( $user, 'WP_User' ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'The $user parameter should be a WP_User object.', 'paid-memberships-pro' ), '3.0' );
			}

			// Get the level object.
			$level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $level_id );

			// Make sure that the level is now set to expire.
			if ( empty( $level ) || empty( $level->enddate ) ) {
				return false;
			}

			$email = new PMPro_Email_Template_Cancel_On_Next_Payment_Date_Admin( $user, $level_id );
			return $email->send();
		}

		/**
		 * Send the refunded email to the member.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated with the refund.
		 */
		function sendRefundedEmail( $user = NULL, $order = NULL ) {
			global $wpdb, $current_user;
			if ( ! $user ) {
				$user = $current_user;
			}

			if ( ! $user ) {
				return false;
			}

			$email = new PMPro_Email_Template_Refund( $user, $order );
			return $email->send();
		}
		
		/**
		 * Send the refunded email to the member.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated with the refund.
		 */
		function sendRefundedAdminEmail( $user = NULL, $order = NULL ) {
			global $wpdb, $current_user;
			if ( ! $user ) {
				$user = $current_user;
			}

			if ( ! $user ) {
				return false;
			}

			$email = new PMPro_Email_Template_Refund_Admin( $user, $order );
			return $email->send();
		}

		/**
		 * Send the member a confirmation checkout email after successfully purchasing a membership level.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated with the checkout.
		 */
		function sendCheckoutEmail($user = NULL, $order = NULL)
		{
			global $wpdb, $current_user, $discount_code;
			if(!$user)
				$user = $current_user;
			
			if(!$user)
				return false;

			if ( empty( $order->membership_id ) ) {
				return false;
			}

			$email =  null;
			if( !empty( $this->template ) ) {
				switch ( $this->template )  {
					case 'checkout_check':
					$email = new PMPro_Email_Template_Checkout_Check( $user, $order );
					break;
					case 'checkout_free':
					$email = new PMPro_Email_Template_Checkout_Free( $user, $order );
					break;
					case 'checkout_paid':
					$email = new PMPro_Email_Template_Checkout_Paid( $user, $order );
					break;
				}
			} else {
				//Get the level for this user
				$membership_level = pmpro_getSpecificMembershipLevelForUser( $user->ID, $order->membership_id );
				if( ! empty( $order ) && ! pmpro_isLevelFree( $membership_level ) ) {
					if( $order->gateway == "check" ) {
						$email = new PMPro_Email_Template_Checkout_Check( $user, $order );
					} else {
						$email = new PMPro_Email_Template_Checkout_Paid( $user, $order );
					}
				} elseif( pmpro_isLevelFree( $membership_level ) ) {
					$email = new PMPro_Email_Template_Checkout_Free( $user, $order );
				}
			}
			//Bail if $email is null
			if( $email == null ) {
				return false;
			}
			return $email->send();
		}
		
		/**
		 * Send the admin a confirmation checkout email after the member successfully purchases a membership level.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated with the checkout.
		 */
		function sendCheckoutAdminEmail( $user = NULL, $order = NULL ) {
			global $wpdb, $current_user;
			if ( ! $user ) {
				$user = $current_user;
			}
			
			if ( ! $user ) {
				return false;
			}

			if ( empty( $order->membership_id ) ) {
				return false;
			}

			$email = null;
			if ( ! empty( $this->template ) ) {
				switch( $this->template ) {
					case 'checkout_check_admin':
						$email = new PMPro_Email_Template_Checkout_Check_Admin( $user, $order );
						break;
					case 'checkout_free_admin':
						$email = new PMPro_Email_Template_Checkout_Free_Admin( $user, $order );
						break;
					case 'checkout_paid_admin':
						$email = new PMPro_Email_Template_Checkout_Paid_Admin( $user, $order );
						break;
				}
			} else {
				// Get the membership level for this order, to see if it's a free level.
				$membership_level =  $order->getMembershipLevel();
				if ( ! empty( $order ) && ! pmpro_isLevelFree( $membership_level ) ) {
					if( $order->gateway == "check" ) {
						$email = new PMPro_Email_Template_Checkout_Check_Admin( $user, $order );
					} else {
						$email = new PMPro_Email_Template_Checkout_Paid_Admin( $user, $order );
					}										
				} elseif ( pmpro_isLevelFree( $membership_level ) ) {
					$email = new PMPro_Email_Template_Checkout_Free_Admin( $user, $order );
				}
			}
			//Bail if $email is null
			if ( $email == null ) { 
				return false;
			}
			return $email->send();
		}

		/**
		 * Send the member a confirmation email when updating their billing details
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated to the member.
		 */
		function sendBillingEmail($user = NULL, $order = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$order)
				return false;

			if ( empty( $order->membership_id ) ) {
				return false;
			}

			$email = new PMPro_Email_Template_Billing( $user, $order );
			return $email->send();
		}
		
		/**
		 * Send the admin a confirmation email when a member updatestheir billing details
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated to the member.
		 */
		function sendBillingAdminEmail($user = NULL, $order = NULL)
		{
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$order)
				return false;
			
			if ( empty( $order->membership_id ) ) {
				return false;
			}

			$email = new PMPro_Email_Template_Billing_Admin( $user, $order );
			return $email->send();
		}
		
		/**
		 * Send the member an email when their recurring payment has failed.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated to the member.
		 */
		function sendBillingFailureEmail( $user = NULL, $order = NULL ) {
			global $current_user;
			if(!$user)
				$user = $current_user;

			if(!$user || !$order)
				return false;

			$email = new PMPro_Email_Template_Billing_Failure( $user, $order );
			return $email->send();
		}

		/**
		 * Send the admin an email when their recurring payment has failed.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated to the member.
		 */
		function sendBillingFailureAdminEmail($email, $order = NULL) {
			if(!$order)			
				return false;

			$user = get_userdata( $order->user_id );
			if(!$user)
				return false;

			$email = new PMPro_Email_Template_Billing_Failure_Admin( $user, $order );
			return $email->send();
		}

		/**
		 * Send the member an email when their credit card is expiring soon.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated to the member.
		 * @return bool True if the email was sent, false otherwise.
		 * @deprecated 3.1
		 */
		function sendCreditCardExpiringEmail($user = NULL, $order = NULL) {
			_deprecated_function( 'sendCreditCardExpiringEmail', '3.1' );

			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$order)
				return false;
			
			if ( empty( $order->membership_id ) ) {
				return false;
			}

			$email = new PMPro_Email_Template_Credit_Card_Expiring( $user, $order );
			return $email->send();
		}
		
		/**
		 * Send the member an email when their recurring payment has succeeded.
		 *
		 * @param object $user The WordPress user object.
		 * @param MemberOrder $order The order object that is associated to the member.
		 */
		function sendInvoiceEmail( $user = NULL, $order = NULL ) {
			global $wpdb, $current_user;
			if(!$user)
				$user = $current_user;
			
			if(!$user || !$order)
				return false;
			
			//Bail if no membership level in the order
			if ( empty( $order->membership_id ) ) {
				return false;
			}

			$email = new PMPro_Email_Template_Invoice( $user, $order );
			return $email->send();
		}
		
		/**
		 * Send the member an email when their trial is ending soon.
		 *
		 * @param object $user The WordPress user object.
		 * @param int $membership_id The member's membership level ID.
		 * @deprecated 2.10
		 */
		function sendTrialEndingEmail( $user = NULL, $membership_id = NULL ) {
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
				'siteemail' => get_option( 'pmpro_from_email' ),
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
		
		/**
		 * Send the member an email when their membership has ended.
		 *
		 * @param object $user The WordPress user object.
		 * @param int $membership_id The member's membership level ID.
		 * @return bool Whether the email was sent successfully.
		 * @since 3.1
		 */
		function sendMembershipExpiredEmail( $user = NULL, $membership_id = NULL ) {
			global $current_user;
			if( !$user ) {
				$user = $current_user;
			}
			//Bail if still we don't have a user.
			if( !$user ) {
				return false;
			}

			$email = new PMPro_Email_Template_Membership_Expired( $user, $membership_id );
			return $email->send();
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
				$membership_level = pmpro_getMembershipLevelForUser( $user->ID );
				$membership_id = $membership_level->id;
			}
						
			$email = new PMPro_Email_Template_Membership_Expiring( $user, $membership_id );
			return $email->send();
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

			$email = new PMPro_Email_Template_Admin_Change( $user );
			return $email->send();
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

			$email = new PMPro_Email_Template_Admin_Change_Admin( $user );
			return $email->send();
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
		 * @deprecated 3.1 Use sendInvoiceEmail instead.
		 */
		function sendBillableInvoiceEmail( $user = NULL, $order = NULL ) {
			_deprecated_function( 'sendBillableInvoiceEmail', '3.1', 'sendInvoiceEmail' );
			return $this->sendInvoiceEmail( $user, $order );
		}

		/**
		 * Send the Payment Action is required email to a member. This is used for Stripe payments.
		 *
		 * @param object $user
		 * @param MemberOrder $order 
		 * @param string $order_url The link to the order that is generated by Stripe.
		 * @return void
		 */
		function sendPaymentActionRequiredEmail( $user = NULL, $order = NULL, $order_url = NULL ) {
			global $current_user;
			if(!$user)
				$user = $current_user;
			
			if( !$user || !$order )
				return false;

			// if an order URL wasn't passed in, grab it from the order
			if( empty( $order_url ) && isset( $order->order_url ) )
				$order_url = $order->order_url;

			// if an order URL wasn't passed in, grab it from the order
			if(empty($order_url) && isset($order->invoice_url))
			$order_url = $order->invoice_url;

			// still no order URL? bail
			if( empty( $order_url ) )
				return false;

			$email = new PMPro_Email_Template_Payment_Action( $user, $order_url );
			return $email->send();
		}

		/**
		 * Send the Payment Action is required email to the admin when a member's payment requires the payment action intent. This is used for Stripe payments.
		 *
		 * @param object $user
		 * @param MemberOrder $order 
		 * @param string $order_url The url to the order that is generated by Stripe.
		 * @return void
		 */
		function sendPaymentActionRequiredAdminEmail($user = NULL, $order = NULL, $order_url = NULL) {
			global $current_user;
			if( !$user )
				$user = $current_user;
			
			if( !$user || !$order )
				return false;

			// if an invoice URL wasn't passed in, grab it from the order
			if( empty( $order_url ) && isset( $order->invoice_url ) )
				$order_url = $order->invoice_url;

			// still no invoice URL? bail
			if( empty( $order_url ) )
				return false;

			$email = new PMPro_Email_Template_Payment_Action_Admin( $user, $order_url );
			return $email->send();
		}

		/**
		 * Send the payment reminder email to a member.
		 *
		 * @param object $user The WordPress user object.
		 * @return void
		 * @since 3.4
		 */
		function send_recurring_payment_reminder( $subscription_obj = NULL ) {
			// Bail if we don't have a subscription object.
			if ( ! $subscription_obj ) {
				return false;
			}
			$email = new PMPro_Email_Template_Membership_Recurring( $subscription_obj );
			return $email->send();
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
