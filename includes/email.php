<?php
/*
	Nicer default emails
*/
function pmpro_wp_mail_from_name($from_name)
{
	$default_from_name = 'WordPress';
	
	//make sure it's the default from name
	if($from_name == $default_from_name)
	{	
		$pmpro_from_name = pmpro_getOption("from_name");
		if ($pmpro_from_name)
			$from_name = $pmpro_from_name;
	}
	
	return $from_name;
}
function pmpro_wp_mail_from($from_email)
{
	// default from email wordpress@sitename
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}
	$default_from_email = 'wordpress@' . $sitename;
		
	//make sure it's the default email address
	if($from_email == $default_from_email)
	{	
		$pmpro_from_email = pmpro_getOption("from_email");
		if ($pmpro_from_email && is_email( $pmpro_from_email ) )
			$from_email = $pmpro_from_email;
	}
	
	return $from_email;
}

$only_filter_pmpro_emails = pmpro_getOption("only_filter_pmpro_emails");
if($only_filter_pmpro_emails)
{
	add_filter('pmpro_email_sender_name', 'pmpro_wp_mail_from_name');
	add_filter('pmpro_email_sender', 'pmpro_wp_mail_from');
}
else
{
	add_filter('wp_mail_from_name', 'pmpro_wp_mail_from_name');
	add_filter('wp_mail_from', 'pmpro_wp_mail_from');
}

/*
	If the $email_member_notification option is empty, disable the wp_new_user_notification email at checkout.
*/
$email_member_notification = pmpro_getOption("email_member_notification");
if(empty($email_member_notification))
	add_filter("pmpro_wp_new_user_notification", "__return_false", 0);

/*
	Adds template files and changes content type to html if using PHPMailer directly.
*/
function pmpro_send_html( $phpmailer ) {
	
	//check if we should wpautop later
	if($phpmailer->Body == strip_tags($phpmailer->Body))
		$wpautop = true;
	else
		$wpautop = false;
	
	// Set the original plain text message
	$phpmailer->AltBody = wp_specialchars_decode($phpmailer->Body, ENT_QUOTES);
	// Clean < and > around text links in WP 3.1
	$phpmailer->Body = preg_replace('#<(http://[^*]+)>#', '$1', $phpmailer->Body);
	// Convert line breaks & make links clickable
	$phpmailer->Body = make_clickable ($phpmailer->Body);

	// Add header to message if found
	if(file_exists(get_stylesheet_directory() . "/email_header.html"))
		$phpmailer->Body = file_get_contents(get_stylesheet_directory() . "/email_header.html") . "\n" . $phpmailer->Body;
	elseif(file_exists(get_template_directory() . "/email_header.html"))
		$phpmailer->Body = file_get_contents(get_template_directory() . "/email_header.html") . "\n" . $phpmailer->Body;
	
	// Add footer to message if found
	if(file_exists(get_stylesheet_directory() . "/email_footer.html"))
		$phpmailer->Body = $phpmailer->Body . "\n" . file_get_contents(get_stylesheet_directory() . "/email_footer.html");
	elseif(file_exists(get_template_directory() . "/email_footer.html"))
		$phpmailer->Body =  $phpmailer->Body . "\n" . file_get_contents(get_template_directory() . "/email_footer.html");

	// Replace variables in email
	global $current_user;
	$data = array(
				"name" => $current_user->display_name,
				"sitename" => get_option("blogname"),
				"login_link" => pmpro_url("account"),
				"display_name" => $current_user->display_name,
				"user_email" => $current_user->user_email,
				"subject" => $phpmailer->Subject
			);
	foreach($data as $key => $value)
	{
		$phpmailer->Body = str_replace("!!" . $key . "!!", $value, $phpmailer->Body);
	}

	if($wpautop)
		$phpmailer->Body = wpautop($phpmailer->Body);
	
	do_action("pmpro_after_phpmailer_init", $phpmailer);
	do_action("pmpro_after_pmpmailer_init", $phpmailer);	//typo left in for backwards compatibility
}

function pmpro_wp_mail_content_type( $content_type ) {
	add_action('phpmailer_init', 'pmpro_send_html');

	//change to html if not already
	if( $content_type == 'text/plain')
	{			
		$content_type = 'text/html';
	}
	return $content_type;
}
add_filter('wp_mail_content_type', 'pmpro_wp_mail_content_type');