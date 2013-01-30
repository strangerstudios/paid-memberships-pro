<?php
	global $isapage;
	$isapage = true;
		
	//in case the file is loaded directly
	if(!function_exists("get_userdata"))
	{
		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}
	
	require_once(dirname(__FILE__) . '/../classes/class.mimetype.php');
	
	global $wpdb;
	
	$uri = $_SERVER['REQUEST_URI'];
	if($uri[0] == "/")
		$uri = substr($uri, 1, strlen($uri) - 1);
	
	//if WP is installed in a subdirectory, that directory(s) will be in both the PATH and URI
	$home_url_parts = explode("/", str_replace("//", "", home_url()));	
	if(count($home_url_parts) > 1)
	{
		//found a directory or more
		$uri_parts = explode("/", $uri);
		
		//create new uri without the directories in front
		$new_uri_parts = array();		
		for($i = count($home_url_parts) - 1; $i < count($uri_parts); $i++)
			$new_uri_parts[] = $uri_parts[$i];
		$new_uri = implode("/", $new_uri_parts);
	}
	else
		$new_uri = $uri;
	
	$filename = ABSPATH . $new_uri;
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
				header('HTTP/1.1 503 Service Unavailable', true, 503);
				echo "HTTP/1.1 503 Service Unavailable";
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