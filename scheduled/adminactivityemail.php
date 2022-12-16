<?php
	global $isapage;
	$isapage = true;

	//wp includes
	define( 'WP_USE_THEMES', false );
	require( '../../../../wp-load.php' );

	//Don't let anything run if PMPro is paused
	if( pmpro_is_paused() ) {
		return;
	}
	
	// This function is defined in /scheduled/crons.php.
	pmpro_cron_admin_activity_email();
