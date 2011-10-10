<?php
	//is there a default level to redirect to?	
	$default_level = intval(PMPRO_DEFAULT_LEVEL);
	if($default_level)
	{
		wp_redirect(pmpro_url("checkout", "?level=" . $default_level, "https"));
		exit;
	}
	
	global $wpdb, $pmpro_msg, $pmpro_msgt;
	if (isset($_REQUEST['msg']))
	{
		if ($_REQUEST['msg']==1)
		{
			$pmpro_msg = 'Your membership status has been updated - Thank you!';
		}
		else
		{
			$pmpro_msg = 'Sorry, your request could not be completed - please try again in a few moments.';
			$pmpro_msgt = "pmpro_error";
		}
	}
	else
	{
		$pmpro_msg = false;
	}
	
	global $pmpro_levels;
	$pmpro_levels = $wpdb->get_results( "SELECT * FROM " . $wpdb->pmpro_membership_levels . " WHERE allow_signups = 1 ORDER BY id", OBJECT );	
	$pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);
?>