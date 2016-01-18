<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_updates")))
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}

	require_once(dirname(__FILE__) . "/admin_header.php");	
?>

<h2><?php _e('Updating Paid Memberships Pro', 'pmpro');?></h2>

<?php
	$updates = get_option('pmpro_updates', array());
	if(!empty($updates)) {
		//let's process the first one
	?>
	<p id="pmpro_updates_intro"><?php _e('Updates are processing. This may take a few minutes to complete.', 'pmpro');?></p>
	<textarea id="pmpro_updates_status" rows="10" cols="60">Loading...</textarea>
	
	<?php
	} else {
	?><p><?php _e('Update complete.');?></p><?php
	}
?>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");	
?>