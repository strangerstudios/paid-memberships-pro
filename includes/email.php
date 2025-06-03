<?php
// Sanitize all PMPro email bodies. @since 2.6.1
add_filter( 'pmpro_email_body', 'pmpro_kses', 11 );

/**
 * The default name for WP emails is WordPress. Use our setting instead.
 *
 * @param string $from_name The default from name.
 * @return string The from name.
 * @since 3.1
 */
function pmpro_wp_mail_from_name( $from_name ) {
	$default_from_name = 'WordPress';

	//make sure it's the default from name
	if( $from_name == $default_from_name ) {
		$pmpro_from_name = get_option( 'pmpro_from_name' );
		if ($pmpro_from_name) {
			$from_name = $pmpro_from_name;
		}
	}

	return wp_unslash( $from_name );
}

/**
 * The default email address for WP emails is wordpress@sitename. Use our setting instead.
 *
 * @param string $from_email The default from email.
 * @return string The from email.
 * @since 3.1
 */
function pmpro_wp_mail_from( $from_email ) {
	// default from email wordpress@sitename
	if ( isset( $_SERVER['SERVER_NAME'] ) ) {
		$sitename = strtolower( sanitize_text_field( $_SERVER['SERVER_NAME'] ) );
	} else {
		$site_url = get_option( 'siteurl' );
		$parsed_url = parse_url( $site_url );
		$sitename = strtolower( $parsed_url['host'] );
	}

	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}
	$default_from_email = 'wordpress@' . $sitename;

	//make sure it's the default email address
	if ( $from_email == $default_from_email ) {
		$pmpro_from_email = get_option( 'pmpro_from_email' );
		if ( $pmpro_from_email && is_email( $pmpro_from_email ) ) {
			$from_email = $pmpro_from_email;
		}
	}

	return $from_email;
}

// Are we filtering all WP emails or just PMPro ones?
$only_filter_pmpro_emails = get_option( "pmpro_only_filter_pmpro_emails" );
if( $only_filter_pmpro_emails ) {
	add_filter( 'pmpro_email_sender_name', 'pmpro_wp_mail_from_name' );
	add_filter( 'pmpro_email_sender', 'pmpro_wp_mail_from' );
} else {
	add_filter( 'wp_mail_from_name', 'pmpro_wp_mail_from_name' );
	add_filter( 'wp_mail_from', 'pmpro_wp_mail_from' );
}

//If the $email_member_notification option is empty, disable the wp_new_user_notification email at checkout.
$email_member_notification = get_option( "pmpro_email_member_notification" );
if( empty( $email_member_notification ) ) {
	add_filter( "pmpro_wp_new_user_notification", "__return_false", 0 );
}

/**
 * Add template files and change content type to HTML if using PHPMailer directly.
 *
 * @param object $phpmailer The PHPMailer object.
 * @since 3.1
 */
function pmpro_send_html( $phpmailer ) {

	//to check if we should wpautop later
	$original_body = $phpmailer->Body;

	// Set the original plain text message
	$phpmailer->AltBody = wp_specialchars_decode($phpmailer->Body, ENT_QUOTES);
	// Clean < and > around text links in WP 3.1
	$phpmailer->Body = preg_replace('#<(https?://[^*]+)>#', '$1', $phpmailer->Body);

	// If there is no HTML, run through wpautop
	if($phpmailer->Body == strip_tags($phpmailer->Body))
		$phpmailer->Body = wpautop($phpmailer->Body);

	// Convert line breaks & make links clickable
	$phpmailer->Body = make_clickable ($phpmailer->Body);

	// Get header for message if found
	if(file_exists(get_stylesheet_directory() . "/email_header.html"))
		$header = file_get_contents(get_stylesheet_directory() . "/email_header.html");
	elseif(file_exists(get_template_directory() . "/email_header.html"))
		$header = file_get_contents(get_template_directory() . "/email_header.html");
	else
		$header = "";

	//wpautop header if needed
	if(!empty($header) && $header == strip_tags($header))
		$header = wpautop($header);

	// Get footer for message if found
	if(file_exists(get_stylesheet_directory() . "/email_footer.html"))
		$footer = file_get_contents(get_stylesheet_directory() . "/email_footer.html");
	elseif(file_exists(get_template_directory() . "/email_footer.html"))
		$footer =  file_get_contents(get_template_directory() . "/email_footer.html");
	else
		$footer = "";

	//wpautop header if needed
	if(!empty($footer) && $footer == strip_tags($footer))
		$footer = wpautop($footer);

	$header = apply_filters( 'pmpro_email_body_header', $header, $phpmailer );
	$footer = apply_filters( 'pmpro_email_body_footer', $footer, $phpmailer );

	// Add header/footer to the email
	if(!empty($header))
		$phpmailer->Body = $header . "\n" . $phpmailer->Body;
	if(!empty($footer))
		$phpmailer->Body = $phpmailer->Body . "\n" . $footer;

	// Replace variables in email
	global $current_user;
	$data = array(
				"name" => $current_user->display_name,
				"sitename" => get_option("blogname"),
				"login_link" => pmpro_url("account"),
				"login_url" => pmpro_url("account"),
				"display_name" => $current_user->display_name,
				"user_email" => $current_user->user_email,
				"subject" => $phpmailer->Subject
			);
	foreach($data as $key => $value)
	{
		$phpmailer->Body = str_replace("!!" . $key . "!!", $value, $phpmailer->Body);
	}

	do_action("pmpro_after_phpmailer_init", $phpmailer);
	do_action("pmpro_after_pmpmailer_init", $phpmailer);	//typo left in for backwards compatibility
}

/**
 * Change the content type of emails to HTML.
 */
function pmpro_wp_mail_content_type( $content_type ) {
	add_action('phpmailer_init', 'pmpro_send_html');

	// Change to html if not already.
	if( $content_type == 'text/plain') {
		$content_type = 'text/html';
	}

	return $content_type;
}
add_filter('wp_mail_content_type', 'pmpro_wp_mail_content_type');

/**
 * Filter the password reset email for compatibility with the HTML format.
 * We double check the wp_mail_content_type filter hasn't been disabled.
 * We check if there are already <br /> tags before running nl2br.
 * Running make_clickable() multiple times has no effect.
 *
 * @param string $message The message to be sent in the email.
 * @return string The message to be sent in the email.
 * @since 3.1
 */
function pmpro_retrieve_password_message( $message ) {
	if ( has_filter( 'wp_mail_content_type', 'pmpro_wp_mail_content_type' ) ) {
		$message = make_clickable( $message );

		if ( strpos( '<br', strtolower( $message ) ) === false ) {
			$message = nl2br( $message );
		}
	}

	return $message;
}
add_filter( 'retrieve_password_message', 'pmpro_retrieve_password_message', 10, 1 );

/**
 * Ajax endpoint to save template data into the database.
 *
 * @return void Despite it doesn't return anything, it echoes a message to the AJAX callback.
 */
function pmpro_email_templates_save_template_data() {

	check_ajax_referer('pmproet', 'security');

	if ( ! current_user_can( 'pmpro_emailtemplates' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	$template = sanitize_text_field( $_REQUEST['template'] );
	$subject = isset( $_REQUEST['subject'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['subject'] ) ) : '';
	$body = pmpro_kses( wp_unslash( $_REQUEST['body'] ), 'email' );	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	//update this template's settings
	update_option( 'pmpro_email_' . $template . '_subject', $subject );
	update_option( 'pmpro_email_' . $template . '_body', $body );
	delete_transient( 'pmproet_' . $template );
	esc_html_e( 'Template Saved', 'paid-memberships-pro' );

	exit;
}
add_action('wp_ajax_pmpro_email_templates_save_template_data', 'pmpro_email_templates_save_template_data');

/**
 * Reset template data. Ajax endpoint to reset template data to the default values.
 *
 * @return void Despite it doesn't return anything, it echoes the template data.
 * @since 3.1
 */
function pmpro_email_templates_reset_template_data() {

	check_ajax_referer('pmproet', 'security');

	if ( ! current_user_can( 'pmpro_emailtemplates' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	global $pmpro_email_templates_defaults;

	$template = sanitize_text_field( $_REQUEST['template'] );

	delete_option('pmpro_email_' . $template . '_subject');
	delete_option('pmpro_email_' . $template . '_body');
	delete_transient( 'pmproet_' . $template );

	$template_data['subject'] = $pmpro_email_templates_defaults[$template]['subject'];
	$template_data['body'] = pmpro_email_templates_get_template_body($template);

	echo json_encode($template_data);
	exit;
}
add_action('wp_ajax_pmpro_email_templates_reset_template_data', 'pmpro_email_templates_reset_template_data');

/**
 * Disable/Enable template. Ajax endpoint to disable or enable a template.
 *
 * @return void Despite it doesn't return anything, it echoes the template data.
 * @since 3.1
 */
function pmpro_email_templates_disable_template() {

	check_ajax_referer('pmproet', 'security');

	if ( ! current_user_can( 'pmpro_emailtemplates' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	$template = sanitize_text_field( $_REQUEST['template'] );
	$disabled = sanitize_text_field( $_REQUEST['disabled'] );
	$response['result'] = update_option('pmpro_email_' . $template . '_disabled', $disabled );
	$response['status'] = $disabled;
	echo json_encode($response);
	exit;
}
add_action('wp_ajax_pmpro_email_templates_disable_template', 'pmpro_email_templates_disable_template');

/**
 * Send test email. Ajax endpoint to send a test email.
 *
 * @return void Despite it doesn't return anything, it echoes the response.
 * @since 3.1
 */
function pmpro_email_templates_send_test() {

	check_ajax_referer('pmproet', 'security');

	if ( ! current_user_can( 'pmpro_emailtemplates' ) ) {
		die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	//figure out PMPro_Email_Template class from template slug
	$pmpro_email_template = PMPro_Email_Template::get_email_template( str_replace( 'email_', '', sanitize_text_field( $_REQUEST['template'] ) ) );
  
	//it's a class name, not an instance and method is static. Call it directly.
	$response = $pmpro_email_template::send_test( sanitize_email( $_REQUEST['email'] ) );

	//return the response
	echo esc_html( $response );
	exit;
}
add_action('wp_ajax_pmpro_email_templates_send_test', 'pmpro_email_templates_send_test');

function pmpro_email_templates_test_recipient($email) {
	if(!empty($_REQUEST['email']))
		$email = sanitize_email( $_REQUEST['email'] );
	return $email;
}

//for test emails
function pmpro_email_templates_test_body($body, $email = null) {
	$body .= '<br /><br /><b>-- ' . esc_html__('THIS IS A TEST EMAIL', 'paid-memberships-pro') . ' --</b>';
	return $body;
}

function pmpro_email_templates_test_template($email)
{
	if( ! empty( $_REQUEST['template'] ) ) {
		$email->template = str_replace( 'email_', '', sanitize_text_field( $_REQUEST['template'] ) );
	}

	return $email;
}

/* Filter for Variables */
function pmpro_email_templates_email_data($data, $email) {

	global $pmpro_currency_symbol;

	if ( ! empty( $data ) && ! empty( $data['user_login'] ) ) {
		$user = get_user_by( 'login', $data['user_login'] );
	} elseif ( ! empty( $email ) ) {
		$user = get_user_by( 'email', $email->email );
	} else {
		$user = wp_get_current_user();
	}

	// Make sure we have the current membership level data.
	if ( $user instanceof WP_User ) {
		$user->membership_level = pmpro_getMembershipLevelForUser(
			$user->ID,
			true
		);
	}

	//make sure data is an array
	if(!is_array($data))
		$data = array();

	//general data
	$new_data['sitename'] = get_option("blogname");
	$new_data['siteemail'] = get_option("pmpro_from_email");
	if(empty($new_data['login_link'])) {
		$new_data['login_link'] = wp_login_url();
		$new_data['login_url'] = wp_login_url();
	}
	$new_data['levels_link'] = pmpro_url("levels");

	// User Data.
	if ( ! empty( $user ) ) {
		$new_data['name'] = $user->display_name;
		$new_data['user_login'] = $user->user_login;
		$new_data['display_name'] = $user->display_name;
		$new_data['user_email'] = $user->user_email;

		// Membership Information.
		$new_data['membership_expiration'] = '';
		$new_data["membership_change"] = esc_html__("Your membership has been cancelled.", "paid-memberships-pro");
		if ( empty( $user->membership_level ) ) {
			$user->membership_level = pmpro_getMembershipLevelForUser($user->ID, true);
		}
		if ( ! empty( $user->membership_level ) ) {
			if ( ! empty( $user->membership_level->name ) ) {
				$new_data["membership_change"] = sprintf(__("The new level is %s.", "paid-memberships-pro"), $user->membership_level->name);
			}
			if ( ! empty($user->membership_level->startdate) ) {
				$new_data['startdate'] = date_i18n( get_option( 'date_format' ), $user->membership_level->startdate );
			}
			if ( ! empty($user->membership_level->enddate) ) {
				$new_data['enddate'] = date_i18n( get_option( 'date_format' ), $user->membership_level->enddate );
				$new_data['membership_expiration'] = "<p>" . sprintf( esc_html__("This membership will expire on %s.", "paid-memberships-pro"), date_i18n( get_option( 'date_format' ), $user->membership_level->enddate ) ) . "</p>\n";
				$new_data["membership_change"] .= " " . sprintf(__("This membership will expire on %s.", "paid-memberships-pro"), date_i18n( get_option( 'date_format' ), $user->membership_level->enddate ) );
			} else if ( ! empty( $email->expiration_changed ) ) {
				$new_data["membership_change"] .= " " . esc_html__("This membership does not expire.", "paid-memberships-pro");
			}
		}
	}

	// Order data
	if(!empty($data['order_id']))
	{
		$order = new MemberOrder($data['order_id']);
		if(!empty($order) && !empty($order->code))
		{
			$new_data['billing_name'] = $order->billing->name;
			$new_data['billing_street'] = $order->billing->street;
			$new_data['billing_street2'] = $order->billing->street2;
			$new_data['billing_city'] = $order->billing->city;
			$new_data['billing_state'] = $order->billing->state;
			$new_data['billing_zip'] = $order->billing->zip;
			$new_data['billing_country'] = $order->billing->country;
			$new_data['billing_phone'] = $order->billing->phone;
			$new_data['cardtype'] = $order->cardtype;
			$new_data['accountnumber'] = hideCardNumber($order->accountnumber);
			$new_data['expirationmonth'] = $order->expirationmonth;
			$new_data['expirationyear'] = $order->expirationyear;
			$new_data['instructions'] = wpautop(get_option('pmpro_instructions'));
			$new_data['order_id'] = $order->code;
			$new_data['order_total'] = $pmpro_currency_symbol . number_format($order->total, 2);
			$new_data['order_date'] = date_i18n( get_option( 'date_format' ), $order->getTimestamp() );
			$new_data['order_link'] = pmpro_url('invoice', '?invoice=' . $order->code);

				//billing address
			$new_data["billing_address"] = pmpro_formatAddress($order->billing->name,
				$order->billing->street,
				$order->billing->street2,
				$order->billing->city,
				$order->billing->state,
				$order->billing->zip,
				$order->billing->country,
				$order->billing->phone);
		}
	}

	//if others are used in the email look in usermeta
	$et_body = get_option('pmpro_email_' . $email->template . '_body');
	$templates_in_email = preg_match_all("/!!([^!]+)!!/", $et_body, $matches);
	if ( ! empty( $templates_in_email ) && ! empty( $user->ID ) ) {
		$matches = $matches[1];
		foreach($matches as $match) {
			if ( empty( $new_data[ $match ] ) ) {
				$usermeta = get_user_meta($user->ID, $match, true);
				if ( ! empty( $usermeta ) ) {
					if( is_array( $usermeta ) && ! empty( $usermeta['fullurl'] ) ) {
						$new_data[$match] = $usermeta['fullurl'];
					} elseif( is_array($usermeta ) ) {
						$new_data[$match] = implode(", ", $usermeta);
					} else {
						$new_data[$match] = $usermeta;
					}
				}
			}
		}
	}

	//now replace any new_data not already in data
	foreach($new_data as $key => $value)
	{
		if(!isset($data[$key]))
			$data[$key] = $value;
	}

	return $data;
}
add_filter('pmpro_email_data', 'pmpro_email_templates_email_data', 10, 2);


/**
 * Load the default email template. Checks theme, then template, then PMPro directory.
 *
 * @param $template string The template name to load.
 * @return string
 * @since 0.6
 */
function pmpro_email_templates_get_template_body( $template ) {

	global $pmpro_email_templates_defaults;

	// Defaults
	$body = "";
	$file = false;


	// Load the template.
	if ( get_transient( 'pmproet_' . $template ) === false ) {
		// Load template
		if ( ! empty( get_option('pmpro_email_' . $template . '_body') ) ) {
			$body = get_option('pmpro_email_' . $template . '_body');
		}elseif( ! empty($pmpro_email_templates_defaults[$template]['body'])) {
			$body = $pmpro_email_templates_defaults[$template]['body'];
		} elseif ( file_exists( get_stylesheet_directory() . '/paid-memberships-pro/email/' . $template . '.html' ) ) {
			$file = get_stylesheet_directory() . '/paid-memberships-pro/email/' . $template . '.html';
		} elseif ( file_exists( get_template_directory() . '/paid-memberships-pro/email/' . $template . '.html') ) {
			$file = get_template_directory() . '/paid-memberships-pro/email/' . $template . '.html';
		}

		if( $file && ! $body ) {
			ob_start();
			require_once( $file );
			$body = ob_get_contents();
			ob_end_clean();
		}

		if ( ! empty( $body ) ) {
			set_transient( 'pmproet_' . $template, $body, 300 );
		}
	} else {
		$body = get_transient( 'pmproet_' . $template );
	}

	return $body;
}

/**
 * Make sure none of the template vars used in our default emails
 * look like URLs that make_clickable will convert.
 * This could be a vector of attack by agents spamming the checkout page.
 */
function pmpro_sanitize_email_data( $data ) {	
	$keys_to_sanitize = array(
		'name',
		'display_name',
		'user_login',
		'billing_name',
		'billing_street',
		'billing_city',
		'billing_state',
		'billing_zip',
		'billing_country',
		'billing_phone',
		'cardtype',
		'account_number',
		'expirationmonth',
		'expirationyear',
		'billing_address'
	);
	
	foreach( $keys_to_sanitize as $key ) {
		if ( isset( $data[$key] ) ) {
			$data[$key] = str_replace( 'www.', 'www ', $data[$key] );
			$data[$key] = str_replace( 'ftp.', 'ftp ', $data[$key] );
			$data[$key] = str_replace( '://', ': ', $data[$key] );
		}
	}
	
	return $data;
}
add_filter( 'pmpro_email_data', 'pmpro_sanitize_email_data' );