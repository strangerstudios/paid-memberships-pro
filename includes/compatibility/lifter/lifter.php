<?php

function enable_streamlined_feature() {

$is_lifter_streamnlined_enabled = get_option( 'toggle_streamline' ) == 'true';

    if($is_lifter_streamnlined_enabled ) { 

    $lifter_streamline =  plugins_url() . '/paid-memberships-pro/css/lifter-streamline.css' ;
    wp_register_style( 'pmpro_lifter', $lifter_streamline, [], PMPRO_VERSION, 'screen' );
  
    wp_enqueue_style( 'pmpro_lifter' );  
  } else {
    wp_dequeue_style( 'pmpro_lifter' );
  }
}

add_action( 'admin_init','enable_streamlined_feature' );


function wp_detect_plugin_activation( $plugin, $network_activation ) {
	if ( $plugin == 'lifterlms/lifterlms.php' ) {
		add_option( 'toggle_streamline', 'true' );
		exit( wp_redirect("/wp-admin/admin.php?page=pmpro-lifter-streamline") );
	}
}

add_action( 'activated_plugin', 'wp_detect_plugin_activation', 10, 2 );

function toggle_streamline() {
  $status = "true";  $_POST['status'];
  update_option( 'toggle_streamline', $status);
  exit();
}

add_action( 'wp_ajax_toggle_streamline', 'toggle_streamline' );
?>