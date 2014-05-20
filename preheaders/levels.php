<?php
	//is there a default level to redirect to?	
	if(defined("PMPRO_DEFAULT_LEVEL"))
		$default_level = intval(PMPRO_DEFAULT_LEVEL);
	else
		$default_level = false;
		
	if($default_level)
	{
		wp_redirect(pmpro_url("checkout", "?level=" . $default_level));
		exit;
	}
	
	global $wpdb, $pmpro_msg, $pmpro_msgt;
	if (isset($_REQUEST['msg']))
	{
		if ($_REQUEST['msg']==1)
		{
			$pmpro_msg = __('Your membership status has been updated - Thank you!', 'pmpro');
		}
		else
		{
			$pmpro_msg = __('Sorry, your request could not be completed - please try again in a few moments.', 'pmpro');
			$pmpro_msgt = "pmpro_error";
		}
	}
	else
	{
		$pmpro_msg = false;
	}
	
	global $pmpro_levels;
	$pmpro_levels = pmpro_getAllLevels(false, true);		
	$pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);