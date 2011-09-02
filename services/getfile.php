<?php
	global $isapage;
	$isapage = true;
		
	//wp includes	
	define('WP_USE_THEMES', false);
	require('../../../../wp-load.php');	
	require_once('../classes/class.mimetype.php');
	
	$uri = $_SERVER['REQUEST_URI'];
	if($uri[0] == "/")
		$uri = substr($uri, 1, strlen($uri) - 1);
	$filename = ABSPATH . $uri;
	$pathParts = pathinfo($filename);			
		
	//only checking if the image is pulled from outside the admin
	if(!is_admin())
	{
		//get some info to use
		$upload_dir = wp_upload_dir();			//wp upload dir
		$filename_small = substr($filename, strlen($upload_dir[basedir]) + 1, strlen($filename) - strlen($upload_dir[basedir]) - 1);  //just the part wp saves				
		
		//look the file up in the db				
		$sqlQuery = "SELECT post_parent FROM $wpdb->posts WHERE ID = (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = '" . $wpdb->escape($filename_small) . "' LIMIT 1) LIMIT 1";		
		$file_post_parent = $wpdb->get_var($sqlQuery);
				
		//has access?
		if($file_post_parent)
		{
			if(!pmpro_has_membership_access($file_post_parent))
			{
				//nope
				echo "HTTP/1.1 503 Service Unavailable";
				header('HTTP/1.1 503 Service Unavailable', true, 503);
				exit;
			}
		}		
	}
		
	//otherwise show it
	$mimetype = new pmpro_mimetype();       		
	header("Content-type: " . $mimetype->getType($filename)); 	
	readfile($filename);
	exit;
?>